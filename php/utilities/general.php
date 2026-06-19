<?php

// These requires are needed when general.php is loaded directly (e.g. wordpress-aixada-integration.php).
// In normal page flow, database.php already loads both, so require_once silently skips them.
require_once(__ROOT__ . 'php'.DS.'inc'.DS.'database.php');
require_once(__ROOT__ . 'local_config'.DS.'config.php');


// ─── Session management ───────────────────────────────────────────────────────

/**
 * Creates a new Aixada session for the user after a successful login.
 * Stores all user data (ID, roles, language, theme...) in $_SESSION['userdata'].
 *
 * @param int $user_id
 * @param string $login
 * @param int $uf_id
 * @param int $member_id
 * @param int $provider_id
 * @param array $roles List of roles available to the user.
 * @param string $current_role The active role.
 * @param array $language_keys List of available language codes.
 * @param array $language_names List of available language display names.
 * @param string $current_language_key The active language code.
 * @param string $theme The active UI theme.
 */
function create_session(
        $user_id,
        $login,
        $uf_id,
        $member_id,
        $provider_id,
        $roles,
        $current_role,
        $language_keys,
        $language_names,
        $current_language_key,
        $theme
    ) {
    load_session();
    $_SESSION['userdata'] = array(
        'user_id' => $user_id,
        'login' => $login,
        'uf_id' => $uf_id,
        'member_id' => $member_id,
        'provider_id' => $provider_id,
        'roles' => $roles,
        'current_role' => $current_role,
        'language_keys' => $language_keys,
        'language_names' => $language_names,
        'language' => $current_language_key,
        'theme' => $theme,
        'cli_addr' => $_SERVER['REMOTE_ADDR'],
        'cli_agent' => $_SERVER['HTTP_USER_AGENT'],
        't_created' => time(),
        't_saved' => time()
    );
    save_session();
}

/**
 * Starts the PHP session if it has not been started yet.
 */
function load_session() {
    if (!isset($_SESSION)) {
         session_start();
    }
}

/**
 * Returns true if the user has an active Aixada session, false otherwise.
 *
 * @return bool
 */
function is_created_session() {
    load_session();
    return isset($_SESSION['userdata']);
}

/**
 * Destroys the current session securely.
 * Regenerates the session ID first to prevent session fixation attacks.
 */
function logout_session() {
    load_session();
    session_regenerate_id(true);
    session_unset();
    session_destroy();
}

/**
 * Persists the current session data and updates the last-saved timestamp.
 */
function save_session() {
    $_SESSION['userdata']['t_saved'] = time();
    session_commit();
}

/**
 * Validates the current session. Throws AuthException if the user is not
 * logged in, if the session has been inactive for more than 30 days,
 * or if the client IP or browser has changed (possible session hijacking).
 * Refreshes the session timestamp every 15 minutes.
 *
 * @throws AuthException
 */
function validate_session() {
    load_session();
    if (!isset($_SESSION['userdata'])) {
        throw new AuthException("Not logged in");
    }
    // For compatibility with old versions the creation date is forced if it does not exist.
    if (!isset($_SESSION['userdata']['t_saved'])) {
        $_SESSION['userdata']['t_saved'] = time();
        $_SESSION['userdata']['cli_addr'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['userdata']['cli_agent'] = $_SERVER['HTTP_USER_AGENT'];
    }

    if ((time() - $_SESSION['userdata']['t_saved']) > 30 * 86400 || // More than 30 days inactive
        $_SESSION['userdata']['cli_addr'] !== $_SERVER['REMOTE_ADDR'] || // Client IP changed
        $_SESSION['userdata']['cli_agent'] !== $_SERVER['HTTP_USER_AGENT'] // Client browser changed
    ) {
        logout_session();
        throw new AuthException("Not logged in");
    }
    if ((time() - $_SESSION['userdata']['t_saved']) > 15 * 60) { // > 15 min
        save_session();
        load_session();
    }
}

/**
 * Returns a value from the current session. Validates the session first.
 *
 * @param string $name The session key (e.g. 'user_id', 'login', 'current_role').
 * @return mixed
 * @throws AuthException if not logged in.
 */
function get_session_value($name) {
    validate_session();
    return $_SESSION['userdata'][$name];
}

/**
 * Returns the user_id of the logged-in user.
 * @return int
 */
function get_session_user_id() {
    return get_session_value('user_id');
}

/**
 * Returns the uf_id of the logged-in user.
 * @return int
 */
function get_session_uf_id() {
    return get_session_value('uf_id');
}

/**
 * Returns the member_id of the logged-in user.
 * @return int
 */
function get_session_member_id() {
    return get_session_value('member_id');
}

/**
 * Returns the login name of the logged-in user.
 * @return string
 */
function get_session_login() {
    return get_session_value('login');
}

/**
 * Returns the active language code for the current user.
 * Does NOT validate the session — safe to call before login (e.g. on login page).
 * Falls back to the default language from config.php if no session exists.
 *
 * @return string Language code (e.g. 'ca-va', 'es', 'en').
 */
function get_session_language() {
    if (is_created_session()) {
        return $_SESSION['userdata']['language'];
    } else {
        return configuration_vars::get_instance()->default_language;
    }
}

/**
 * Returns the active UI theme for the current user.
 * Falls back to the default theme from config.php if no session exists.
 *
 * @return string Theme name.
 */
function get_session_theme() {
    if (is_created_session()) {
        return $_SESSION['userdata']['theme'];
    } else {
        return configuration_vars::get_instance()->default_theme;
    }
}

/**
 * Returns the active role of the logged-in user.
 *
 * @return string Role name (e.g. 'Consumer', 'Hacker Commission').
 */
function get_current_role()
{
    return get_session_value('current_role');
}

/**
 * Changes the active role of the current user.
 * Only roles already assigned to the user are accepted.
 *
 * @param string $new_role The role to switch to.
 * @throws AuthException if the role is not assigned to the user.
 */
function change_session_role($new_role) {
    validate_session();
    if (!in_array($new_role, $_SESSION['userdata']['roles'])) {
        throw new AuthException("Not logged in role. Available roles: " . implode(', ', $_SESSION['userdata']['roles']) . ". Requested: " . $new_role);
    }
    $_SESSION['userdata']['current_role'] = $new_role;
    save_session();
}

/**
 * Changes the active language of the current user.
 * Only languages available in the session are accepted.
 *
 * @param string $new_language_key The language code to switch to.
 * @throws AuthException if the language is not valid.
 */
function change_session_language($new_language_key) {
    validate_session();
    if (!in_array($new_language_key, $_SESSION['userdata']['language_keys'])) {
        throw new AuthException("Language is not valid");
    }
    $_SESSION['userdata']['language'] = $new_language_key;
    save_session();
}


// ─── URL parameter helpers ────────────────────────────────────────────────────

/**
 * Reads a parameter from the HTTP request ($_REQUEST covers both GET and POST).
 * Returns $default if the parameter is missing or empty.
 * Throws an exception if neither the parameter nor a default is available.
 *
 * Special shortcut: passing -1 for 'uf_id', 'user_id' or 'member_id'
 * automatically returns the value from the current session.
 *
 * Supported $transform values: 'lowercase', 'array2String', '' (none).
 *
 * @param string $param_name The request parameter name.
 * @param mixed $default Default value if parameter is missing.
 * @param string $transform Optional transformation to apply to the value.
 * @return mixed
 * @throws Exception if parameter is missing and no default is provided.
 */
function get_param($param_name, $default=null, $transform = '') {
    $value = null;

    if (isset($_REQUEST[$param_name])) {
        $value = $_REQUEST[$param_name];
        if (($value == '' || $value == 'undefined') && isset($default)) {
            $value = $default;
        } else if (($value == '' || $value == 'undefined') && !isset($default)) {
            throw new Exception("get_param: Parameter: {$param_name} has no value and no default value");
        }
    } else if (isset($default) and $default !== null) {
        $value = $default;
    } else {
        throw new Exception("get_param: Missing or wrong parameter name: {$param_name} in URL");
    }

    // Shortcut: -1 means "use the current session value"
    if ($param_name == "uf_id" && $value == -1) {
        $value = get_session_uf_id();
    } else if ($param_name == "user_id" && $value == -1) {
        $value = get_session_user_id();
    } else if ($param_name == "member_id" && $value == -1) {
        $value = get_session_member_id();
    }

    switch ($transform) {
        case 'lowercase':
            $value = strtolower($value);
            break;
        case 'array2String':
            $str = "";
            foreach ($value as $v) {
                $str .= $v.",";
            }
            $value = rtrim($str,",");
            break;
        case '':
            break;
        default:
            throw new Exception("get_param: transform '{$transform}' on URL parameter not supported.");
    }
    return $value;
}

/**
 * Like get_param() but ensures the returned value is numeric.
 * Returns $default (if numeric) or null if the value is not numeric.
 *
 * @param string $param_name
 * @param number|null $default
 * @return number|null
 */
function get_param_numeric($param_name, $default=null) {
    $val = get_param($param_name, false);
    if (!is_numeric($val)) {
        return (is_numeric($default) ? $default + 0 : null);
    }
    return $val + 0;
}

/**
 * Like get_param_numeric() but ensures the value is an integer (no decimals).
 *
 * @param string $param_name
 * @param int|null $default
 * @return int|null
 */
function get_param_int($param_name, $default=null) {
    $val = get_param_numeric($param_name);
    if (!is_int($val)) {
        return (is_numeric($default) && is_int($default+0) ? $default+0 : null);
    }
    return $val;
}

/**
 * Like get_param() but parses a comma-separated string into an array of integers.
 * Returns null if any element is not an integer.
 *
 * @param string $param_name
 * @param array|string|null $default
 * @param string $separator Delimiter character (default ',').
 * @return array|null
 */
function get_param_array_int($param_name, $default=null, $separator=',') {
    $val = get_param($param_name, false);
    if ($val !== false) {
        $str_array = explode($separator, $val);
    } else {
        if (!is_array($default) && !$default) { return null; }
        if (is_array($default)) {
            $str_array = $default;
        } else {
            $str_array = explode($separator, $default);
        }
    }
    $result = array();
    foreach ($str_array as $item) {
        if (!is_numeric($item) || !is_int($item+0)) { return null; }
        array_push($result, $item+0);
    }
    return $result;
}

/**
 * Like get_param() but validates and normalises a date value.
 * Accepts any format via $input_format and always returns 'Y-m-d'.
 * Returns null if the date is invalid and no valid default is provided.
 *
 * @param string $param_name
 * @param string|null $default Default date in 'Y-m-d' format.
 * @param string $input_format Expected input format (default 'Y-m-d').
 * @return string|null Date in 'Y-m-d' format, or null.
 */
function get_param_date($param_name, $default=null, $input_format='Y-m-d') {
    $val = get_param($param_name, false);
    if ($val) {
        $date = date_parse_from_format($input_format, $val);
    }
    if (!$val || $date['error_count'] !== 0) {
        if ($default === null) {
            return null;
        }
        $date = date_parse_from_format('Y-m-d', $default);
        if ($date['error_count'] !== 0) {
            return null;
        }
    }
    $date_o = new DateTime();
    $date_o->setDate($date['year'], $date['month'], $date['day']);
    return $date_o->format('Y-m-d');
}


// ─── Configuration helpers ────────────────────────────────────────────────────

/**
 * Reads a configuration value from config.php.
 * Returns $default if the key is not defined.
 *
 * @param string $param_name The configuration key.
 * @param mixed $default Default value if the key is not set.
 * @return mixed
 */
function get_config($param_name, $default=null) {
    $cfg = configuration_vars::get_instance();
    if (isset($cfg->$param_name)) {
        return $cfg->$param_name;
    } else {
        return $default;
    }
}

/**
 * Returns the path to the cooperative's logo.
 * If img/logo.png exists (placed manually per instance, gitignored),
 * it is used. Otherwise falls back to the default Aixada logo.
 *
 * @return string Relative path to the logo image.
 */
function get_coop_logo() {
    $custom = 'img/logo.png';
    return file_exists($custom) ? $custom : 'img/logo-aixada.png';
}

/**
 * Converts an array of field type codes into display format descriptors,
 * based on the 'type_formats' configuration in config.php.
 *
 * @param array $field_types Associative array of field_name => type_code.
 * @return array|null Associative array of field_name => format descriptor, or null if not configured.
 */
function cnv_config_formats($field_types) {
    $cfg_formats = get_config('type_formats');
    if (!$cfg_formats) {
        return null;
    }
    $field_formats = array();
    foreach ($field_types as $field_name => $content) {
        foreach (array('dates','numbers') as $type) {
            if (isset($cfg_formats[$type]) &&
                        array_key_exists($content, $cfg_formats[$type])) {
                $field_formats[$field_name] = array(
                    'type' => $type,
                    'format' => $cfg_formats[$type][$content]
                );
                break;
            }
        }
    }
    return $field_formats;
}


// ─── Internationalisation (i18n) ──────────────────────────────────────────────

/**
 * Translates a text code using the active language's $Text array.
 * If the code is not found, returns the code itself (useful for spotting missing translations).
 * Supports placeholder substitution: {key} in the translated string is replaced by $replace['key'].
 *
 * @param string $text_code The translation key.
 * @param array|null $replace Associative array of placeholder => value substitutions.
 * @return string Translated (and substituted) string.
 */
function i18n($text_code, $replace=null) {
    global $Text;
    if (!isset($Text[$text_code])) {
        return $text_code;
    }
    $text = $Text[$text_code];
    if ($replace && count($replace)){
        $r_search = array();
        $r_replace = array();
        foreach ($replace as $key => $value) {
            array_push($r_search, '{'.$key.'}');
            array_push($r_replace, $value);
        }
        $text = str_replace($r_search, $r_replace, $text);
    }
    return $text;
}

/**
 * Like i18n() but returns a string safe to embed in JavaScript code (quotes and special chars escaped).
 *
 * @param string $text_code The translation key.
 * @param array|null $replace Placeholder substitutions.
 * @return string Translated and JS-escaped string.
 */
function i18n_js($text_code, $replace=null) {
    return to_js_str(i18n($text_code, $replace));
}

/**
 * Escapes a string for safe embedding inside JavaScript string literals.
 * Escapes backslashes, newlines, tabs, double quotes and single quotes.
 *
 * @param string $text
 * @return string
 */
function to_js_str($text) {
    return str_replace(
        array("\\",   "\n",  "\r",  "\t", '"',   "'"  ),
        array("\\\\", "\\n", "\\r", "\\t",'\\"', "\\'"),
        $text
    );
}


// ─── Email ────────────────────────────────────────────────────────────────────

/**
 * Sends an HTML email.
 * The sender address is taken from 'admin_email' in config.php.
 *
 * Supports three sending modes configured via 'email_SMTP_host' in config.php:
 * - Not set: uses PHP's built-in mail() function.
 * - 'debug': writes the email to local_config/debug_mail/ instead of sending.
 * - Any SMTP host: sends via Symfony Mailer over SMTP.
 *
 * @param string $to Recipient email address.
 * @param string $subject Email subject.
 * @param string $bodyHTML HTML body (without <html>/<head> wrapper).
 * @param array $options Optional keys: 'reply_to', 'cc', 'bcc', 'prepend_coop_name'.
 * @return bool True if the email was sent successfully.
 */
function send_mail($to, $subject, $bodyHTML, $options=array())
{
    // get URL of aixada root
    $pos_root = strrpos($_SERVER['SCRIPT_NAME'], '/php/ctrl/');
    if ($pos_root === false) {
        $pos_root = strrpos($_SERVER['SCRIPT_NAME'], '/');
    }
    $ssl_on = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $url_root = (isset($_SERVER['HTTP_HOST']) ?
                    $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']).
                substr($_SERVER['SCRIPT_NAME'],0,$pos_root);
    // get HTML message
    $prepend_coop_name = !isset($options['prepend_coop_name']) || !!$options['prepend_coop_name'];
    if ($prepend_coop_name) {
        $subject = get_config('coop_name') . ': ' . $subject;
    }
    $messageHTML =
        '<html><head><title>'.$subject."</title></head>\r\n".
        '<body style="font-family: Lucida Grande, Lucida Sans, Arial, sans-serif;">'.
        "\r\n".$bodyHTML."\r\n".
        '<hr><div style="color:#888; text-align: center;">'.
                get_config('coop_name') . ': <a href="'.
                    ($ssl_on ? 'https://' : 'http://').
                    $url_root.
                    '/index.php" style="color:#888;">'.$url_root.'</a>'.
            "</div>\r\n".
        "</body></html>";
    $from = get_config('admin_email');
    if (get_config('email_safe_replyTo')) {
        $reply_to = $from;
    } elseif (isset($options['reply_to']) && $options['reply_to']) {
        $reply_to = $options['reply_to'];
    } else {
        $reply_to = $from;
    }

    if (!get_config('email_SMTP_host')) { // Send using PHP's mail()
        $headers =
            'From: '.$from."\r\n".
            'Reply-To: ' . $reply_to . "\r\n" .
            (isset($options['cc']) ? 'Cc:'.$options['cc']."\r\n" : '') .
            (isset($options['bcc']) ? 'Bcc:'.$options['bcc']."\r\n" : '') .
            'Return-Path: '.$from."\r\n".
            "X-Mailer: PHP\r\n".
            "MIME-Version: 1.0\r\n".
            "Content-Type: text/html; charset=UTF-8\r\n";
        mb_language("uni");
        mb_internal_encoding("UTF-8");
        $subject64 = mb_encode_mimeheader($subject);
        return mail($to, $subject64, $messageHTML, $headers);
    } elseif (get_config('email_SMTP_host') === 'debug') { // Write to file instead of sending
        return !!file_put_contents(
            __ROOT__ . '/local_config/debug_mail/mail_' . date("Y-m-d_H-i-s") . '.html',
            "<!DOCTYPE html><html>
            <head><meta charset='utf-8'></head>
            <body>
                To: {$to}<br>
                From: {$from}<br>
                Reply-To: {$reply_to}<br>
                Options:<pre style='margin: 0 0 0 3em'>" .
                    var_export($options, true) . "</pre>
            <h1 style=\"background-color:#bbb;padding:3px\">{$subject}</h1>
            {$messageHTML}
            </body></html>"
        );
    } else { // Send via SMTP using Symfony Mailer
        require_once __ROOT__ . 'external/php74/symfony-mailer/vendor/autoload.php';
        require_once __ROOT__ . 'php/utilities/send_symfony_mail.php';
        return send_symfony_mail($from, $reply_to, $to, $subject, $messageHTML, $options);
    }
}


// ─── Database query helpers ───────────────────────────────────────────────────

/**
 * Calls a stored procedure (CALL) with the given arguments.
 * The first argument is the procedure name; subsequent arguments are its parameters.
 *
 * @return mysqli_result
 * @throws DataException if arguments contain both ' and " characters.
 */
function do_stored_query()
{
  $args = func_get_args();
  if (is_array($args[0])) {
    $args = $args[0];
  }
  for ($i=1; $i<count($args); ++$i) {
    if (is_array($args[$i])) {
      $args[$i] = $args[$i][0];
    }
  }

  $sql_func = array_shift($args);

  $strSQL = 'CALL ' . $sql_func . '(';
  foreach ($args as $arg) {
      if (strpos($arg, "'") !== false) {
          if (strpos($arg, '"') !== false)
              throw new DataException('Cannot use both symbols \' and " in text');
          $strSQL .= '"' . $arg . '",';
      } else
          $strSQL .= "'" . $arg . "',";
  }
  if (count($args))
    $strSQL = rtrim($strSQL, ',');
  $strSQL .= ')';

  return DBWrap::get_instance()->do_stored_query($strSQL);
}

/**
 * Executes a SQL query and returns the first row as an array.
 * Returns $not_found if no rows are found.
 *
 * @param string $strSQL A SQL query string.
 * @param mixed $not_found Value to return if no row is found (default null).
 * @return array|mixed
 */
function get_row_query($strSQL, $not_found = null) {
    $db = DBWrap::get_instance();
    $rs = $db->Execute($strSQL);
    $row = $rs->fetch_array();
    if (!$row) {
        return $not_found;
    }
    $db->free_next_results();
    return $row;
}

/**
 * Executes a SQL query and returns a flat string of values from the first column,
 * joined by $separator.
 *
 * @param string|array $strSQL A SQL query string.
 * @param string $separator Delimiter between values (default ',').
 * @param string $text_delimiter Optional character to wrap each value (default '').
 * @return string
 */
function get_list_query($strSQL, $separator=',', $text_delimiter='') {
    return get_list_rs(
        DBWrap::get_instance()->Execute($strSQL),
        0,
        $separator,
        $text_delimiter
    );
}

/**
 * Iterates a mysqli_result and returns a flat string of values from one column,
 * joined by $separator.
 *
 * @param mysqli_result $rs
 * @param int|string $field Column index or name (default 0).
 * @param string $separator Delimiter between values (default ',').
 * @param string $text_delimiter Optional character to wrap each value (default '').
 * @return string
 */
function get_list_rs($rs, $field=0, $separator=',', $text_delimiter='') {
    $list = array();
    while ($row = $rs->fetch_array()) {
        if (isset($row[$field])) {
            array_push($list, $text_delimiter.$row[$field].$text_delimiter);
        }
    }
    $db = DBWrap::get_instance();
    $db->free_next_results();
    return implode($separator, $list);
}


// ─── XML generation (for jqGrid) ─────────────────────────────────────────────

/**
 * Converts a mysqli_result into a jqGrid-compatible XML string (<rowset>).
 */
class output_formatter {
  public function rowset_to_jqgrid_XML($rs, $total_entries=0, $page=0, $limit=0, $total_pages=0)
  {
    $strXML = '';
    if ($rs) {
      $strXML .= '<rowset>';
      if ($page)
	$strXML .= '<page>' . $page . '</page>';
      if ($total_pages)
	$strXML .= '<total>' . $total_pages . '</total>';
      $strXML .= '<records>' . $total_entries . '</records>';
      $strXML .= "<rows>";
      while ($row = $rs->fetch_assoc())
	$strXML .= $this->row_to_XML($row);
      $rs->free();
      $strXML .= "</rows>";
      $strXML .= "</rowset>";
    }
    return $strXML;
  }

  public function row_to_XML($row)
  {
      global $Text;
      $strXML = '<row id="' . $row['id'] . '">';
      $rowXML = '';
      foreach ($row as $field => $value) {
          if (isset($Text[$value]))
              $value = $Text[$value];
          $rowXML
              .= '<' . $field . ' f="' . $field
              . '"><![CDATA[' . clean_zeros($value) . "]]></$field>";
      }

      $strXML .= $rowXML . '</row>';
      return $strXML;
  }
}

/**
 * Executes a stored procedure and returns the results as an XML string.
 * Arguments: procedure_name, group_tag, row_tag, [params...]
 *
 * @return string XML string.
 */
function stored_query_XML()
{
  $params = func_get_args();
  $strSQL = array_shift($params);
  $group_tag = array_shift($params);
  $row_tag = array_shift($params);
  array_unshift($params, $strSQL);

  $strXML = "<$group_tag>";
  $rs = do_stored_query($params);
  global $Text;
  while ($row = $rs->fetch_array()) {
      $value = ( ($row_tag == 'description' and isset($Text[$row[1]])) ?
                 $Text[$row[1]] : $row[1] );
      $strXML
          .= '<row><id f="id">' . $row[0]
          . '</id><' . $row_tag
          . ' f="' . $row_tag
          . '"><![CDATA[' . clean_zeros($value) . ']]></' . $row_tag
          . '></row>';
  }
  $strXML .= "</$group_tag>";
  return $strXML;
}

/**
 * Like stored_query_XML() but returns full field data for each row.
 *
 * @return string XML string.
 */
function stored_query_XML_fields()
{
    return rs_XML_fields(do_stored_query(func_get_args()));
}

/**
 * Executes a SQL query and returns the results as a <rowset> XML string.
 *
 * @param string|array $strSQL A SQL query string.
 * @return string XML string.
 */
function query_XML_fields($strSQL) {
    return '<rowset>'.query_to_XML($strSQL).'</rowset>';
}

/**
 * Executes a SQL query and returns a list of <row> XML elements.
 *
 * @param string|array $strSQL A SQL query string.
 * @param array|null $field_formats Optional format descriptors (from cnv_config_formats()).
 * @return string XML string.
 */
function query_to_XML($strSQL, $field_formats = null) {
    return rs_to_XML(
        DBWrap::get_instance()->Execute(func_get_args()), $field_formats
    );
}

/**
 * Wraps rs_to_XML() output in a <rowset> element.
 *
 * @param mysqli_result $rs
 * @param array|null $field_formats Optional format descriptors.
 * @return string XML string.
 */
function rs_XML_fields($rs, $field_formats = null) {
    return '<rowset>'.rs_to_XML($rs, $field_formats).'</rowset>';
}

/**
 * Iterates a mysqli_result and returns a list of <row> XML elements.
 *
 * @param mysqli_result $rs
 * @param array|null $field_formats Optional format descriptors.
 * @return string XML string.
 */
function rs_to_XML($rs, $field_formats = null) {
    $strXML = '';
    if ($rs) {
        while ($row = $rs->fetch_assoc()) {
            $strXML .= array_to_XML($row, $field_formats);
        }
        $rs->free();
    }
    return $strXML;
}

/**
 * Converts an associative array (one DB row) into a <row> XML element.
 * Applies optional field format transformations (dates, numbers).
 * Translates 'description' field values via $Text if available.
 *
 * @param array $ass_array Associative array of field => value.
 * @param array|null $field_formats Optional format descriptors.
 * @return string XML string.
 */
function array_to_XML($ass_array, $field_formats = null) {
    global $Text;
    $strXML = '<row';
    if (isset($ass_array['id'])) {
        $strXML .= ' id ="'.$ass_array['id'].'"';
    }
    $strXML .= '>';
    foreach ($ass_array as $field => $value) {
        if ($field_formats && isset($field_formats[$field])) {
            $format = $field_formats[$field];
            if (isset($format['type'])) {
                $format_f = $format['format'];
                switch ($format['type']) {
                case 'dates':
                    if (!$format_f) {
                        $value_f = $value;
                    } else {
                        if ($format['format'] == 'timestamp') {
                            $value_f = strtotime($value);
                        } else {
                            $value_f = date($format['format'], strtotime($value));
                        }
                    }
                    break;
                case 'numbers':
                    if (!$format_f) {
                        $value_f = $value;
                    } elseif (substr($format_f,0,1) == 'z') {
                        $value_f = clean_zeros($value);
                    } else {
                        $value_f = number_format($value,
                            substr($format_f,0,1),
                            substr($format_f,1,1),
                            substr($format_f,2,1)
                        );
                    }
                    break;
                default:
                    $value_f = $value;
                }
            } else {
                $value_f = $value;
            }
            $strXML .= "<{$field} f=\"{$field}\">{$value_f}</{$field}>";
        } else {
            if ($field == 'description' and isset($Text[$value])) {
                $value = $Text[$value];
            }
            $strXML .= "<{$field} f=\"{$field}\"><![CDATA[".
                clean_zeros($value)."]]></{$field}>";
        }
    }
    return $strXML .= '</row>';
}

/**
 * Executes a SQL query and returns results as an XML string with a custom group and row tag.
 * Arguments: sql_query, group_tag, row_tag, [params...]
 *
 * @return string XML string.
 */
function query_XML()
{
  $params = func_get_args();
  $strSQL = array_shift($params);
  $group_tag = array_shift($params);
  $row_tag = array_shift($params);
  array_unshift($params, $strSQL);

  $strXML = "<$group_tag>";
  $rs = DBWrap::get_instance()->Execute($params);
  while ($row = $rs->fetch_array()) {
      $value = ( ($row_tag == 'description' and isset($Text[$row[1]])) ?
                 $Text[$row[1]] : $row[1] );
      $strXML
          .= '<row><id f="id">' . $row[0]
          . '</id><' . $row_tag
          . ' f="' . $row_tag
          . '"><![CDATA[' . $value . ']]></' . $row_tag
          . '></row>';
  }
  $strXML .= "</$group_tag>";
  return $strXML;
}

/**
 * Like query_XML() but emits a compact format (no id element, just the row tag value).
 *
 * @return string XML string.
 */
function query_XML_compact()
{
  $params = func_get_args();
  $strSQL = array_shift($params);
  $group_tag = array_shift($params);
  $row_tag = array_shift($params);
  array_unshift($params, $strSQL);

  $strXML = "<$group_tag>";
  $rs = DBWrap::get_instance()->Execute($params);
  while ($row = $rs->fetch_array()) {
    $strXML
      .= '<' . $row_tag
      . ' f="' . $row_tag
      . '"><![CDATA[' . $row[0] . ']]></' . $row_tag
      . '>';
  }
  $strXML .= "</$group_tag>";
  return $strXML;
}

/**
 * Calls a stored procedure with no parameters and returns results as XML.
 *
 * @param string $queryname The stored procedure name (also used as the XML group tag).
 * @return string XML string.
 */
function query_XML_noparam($queryname)
{
  $strXML = "<$queryname>";
  $rs = do_stored_query($queryname);
  while ($row = $rs->fetch_assoc()) {
     $strXML .= "<row>";
      foreach ($row as $field => $value) {
          if ($field == 'description' and isset($Text[$value]))
              $value = $Text[$value];
          $strXML .= "<{$field}>{$value}</{$field}>";
      }
       $strXML .= "</row>";
  }
  $strXML .= "</$queryname>";
  return $strXML;
}

/**
 * Outputs an XML string to the browser with appropriate HTTP headers (no cache).
 *
 * @param string $str The XML content (without the XML declaration).
 */
function printXML($str) {
  $newstr = '<?xml version="1.0" encoding="utf-8"?>';
  $newstr .= $str;
  header('Content-Type: text/xml');
  header('Last-Modified: '.date(DATE_RFC822));
  header('Pragma: no-cache');
  header('Cache-Control: no-cache, must-revalidate');
  header('Expires: '. date(DATE_RFC822, time() - 3600));
  echo $newstr;
}


// ─── Miscellaneous utilities ──────────────────────────────────────────────────

/**
 * Writes an HTML string to a file on disk.
 *
 * @param string $strHTML The HTML content to write.
 * @param string $filename The target file path.
 */
function HTMLwrite($strHTML, $filename)
{
  if(is_writeable($filename)) {
    if (!$handle = fopen($filename, 'w')) {
      echo "Cannot open file ($filename)";
      exit;
    }
    if (fwrite($handle, $strHTML) === FALSE) {
      echo "Cannot write to file ($filename)";
      exit;
    }
    fclose($handle);
  } else {
      echo "The file $filename is not writable";
  }
}

/**
 * Returns an XML string describing the navigation items available to a given role,
 * based on the 'menu_config' setting in config.php.
 *
 * @param string $user_role The role to generate navigation for.
 * @return string XML string.
 * @throws Exception if the role is not defined in config.php.
 */
function get_config_menu($user_role)
{
    $XML = "<navigation>\n";
    $mconf = configuration_vars::get_instance()->menu_config;
    if (!isset($mconf[$user_role])) {
        throw new Exception("Role '" . $user_role . "' not defined in local_config/config.php");
    }
    foreach ($mconf[$user_role] as $navItem => $status) {
        $XML .= '<' . $navItem . '>' . $status . '</' . $navItem . ">\n";
    }
    return $XML . '</navigation>';
}

/**
 * Returns an XML list of fields that are allowed to be imported for a given table,
 * based on the 'allow_import_for' setting in config.php.
 *
 * @param string $db_table_name The database table name.
 * @return string XML string.
 * @throws Exception if imports are not configured for this table.
 */
function get_import_rights($db_table_name)
{
    $import_rights = configuration_vars::get_instance()->allow_import_for;

    if (!isset($import_rights[$db_table_name])) {
        throw new Exception("Import error: no imports allowed for '" . $db_table_name . ".' Check local_config/config.php");
    }
    $xml = '<rows>';
    foreach ($import_rights[$db_table_name] as $field => $value) {
        if ($value == 'allow') {
            $xml .= '<row><db_field>'.$field.'</db_field></row>';
        }
    }
    return $xml . "</rows>";
}

/**
 * Returns an XML list of import template names for a given table.
 *
 * @param string $db_table_name The database table name.
 * @return string XML string.
 */
function get_import_templates_list($db_table_name) {
    $templates = get_import_templates($db_table_name);
    $xml = '<rows>';
    foreach ($templates as $field => $value) {
        $xml .= '<row><db_field>'.$field.'</db_field></row>';
    }
    return $xml.'</rows>';
}

/**
 * Returns the import templates defined in config.php for a given table.
 *
 * @param string $db_table_name The database table name.
 * @return array Array of templates, or empty array if none defined.
 */
function get_import_templates($db_table_name) {
    $cfg = configuration_vars::get_instance();
    if (isset($cfg->import_templates)) {
        $import_templates = $cfg->import_templates;
        if (isset($import_templates[$db_table_name])) {
            return $import_templates[$db_table_name];
        }
    }
    return array();
}

/**
 * Returns an XML <select> element with options from a database table.
 * Used to populate dropdown menus in the UI.
 *
 * @param string $table The table to query.
 * @param string $field1 The value field (id).
 * @param string $field2 The label field.
 * @param string $field3 Optional additional field (added as addInfo attribute).
 * @return string XML <select> string.
 */
function get_field_options_live($table, $field1, $field2, $field3='')
{
    global $Text;
    $strXML = '<select>';
    if ($field3 != ''){
        $strSQL = 'select :1, :2, :3 from :4';
    } else {
        $strSQL = 'select :1, :2 from :3';
    }

    if (in_array($table, array('aixada_unit_measure'))) {
        $strSQL .= ' order by name';
    } else if (in_array($table, array('aixada_orderable_type'))) {
        $strSQL .= ' order by description';
    }

    if ($field3 != ''){
        $rs = DBWrap::get_instance()->Execute($strSQL, $field1, $field2, $field3, $table);
    } else {
        $rs = DBWrap::get_instance()->Execute($strSQL, $field1, $field2, $table);
    }

    if ($table == 'aixada_uf') {
        $strXML .= "<option value='-1'>".$Text['sel_uf']."</option>";
    }
    while ($row = $rs->fetch_array()) {
        $ot = (isset($Text[$row[1]]) ? $Text[$row[1]] : $row[1]);
        if ($table == 'aixada_uf'){
            $ot = $row[0] . ' ' . $ot;
        }

        if ($field3 != ''){
            $strXML .= "<option value='{$row[0]}' addInfo='{$row[2]}'";
        } else {
            $strXML .= "<option value='{$row[0]}'";
        }

        if ($row[0] == 1) {
            $strXML .= ' selected';
        }
        $strXML .= ">{$ot}</option>";
    }
    return $strXML . '</select>';
}

/**
 * Returns an XML list of available UI themes (subdirectories of css/ui-themes/).
 *
 * @return string XML string.
 */
function get_existing_themes_XML()
{
    $exclude_list = array(".", "..", "example.txt");
    $folders = array_diff( scandir(__ROOT__ . 'css/ui-themes'), $exclude_list);

    $XML = '<themes>';
    foreach ($folders as $theme) {
        $XML .= "<theme><name>{$theme}</name></theme>";
    }
    return $XML . '</themes>';
}

/**
 * Returns an associative array of available languages (code => display name),
 * detected from the language files in local_config/lang/.
 * Each file must contain a line like: $Text['ca-va'] = 'Català'
 *
 * @return array
 */
function existing_languages()
{
    $languages = array();
    foreach (glob(__ROOT__ . "local_config/lang/*.php") as $lang_file) {
        $a = strpos($lang_file, 'lang/');
        $lang = substr($lang_file, $a+5, strpos($lang_file, '.', $a)-$a-5);
        $handle = @fopen($lang_file, "r");
        $line = fgets($handle);
        while (strpos($line, "Text['{$lang}']") === false and !feof($handle)) {
            $line = fgets($handle);
        }
        if (feof($handle))
            $lang_desc = '';
        else {
            $tmp = trim(substr($line, strpos($line, '=')));
            $lang_desc = trim($tmp, " =;'\"");
        }
        $languages[$lang] = $lang_desc;
    }
    return $languages;
}

/**
 * Returns an XML list of available languages detected from local_config/lang/.
 *
 * @return string XML string.
 */
function existing_languages_XML()
{
    $XML = '<languages>';
    foreach (existing_languages() as $lang => $lang_desc) {
        $XML .= "<language><id>{$lang}</id><description>{$lang_desc} ({$lang})</description></language>";
    }
    return $XML . '</languages>';
}

/**
 * Returns an XML list of all defined user roles, taken from the 'forbidden_pages'
 * keys in config.php.
 *
 * @return string XML string.
 */
function get_roles()
{
    $XML = '<roles>';
    foreach (array_keys(configuration_vars::get_instance()->forbidden_pages) as $role) {
        $XML .= "<role><description>{$role}</description></role>";
    }
    return $XML . '</roles>';
}

/**
 * Returns an XML list of commission roles (all roles except Consumer, Checkout, Producer).
 *
 * @return string XML string.
 */
function get_commissions()
{
    $XML = '<rows>';
    foreach (array_keys(configuration_vars::get_instance()->forbidden_pages) as $role) {
        if (!in_array($role, array('Consumer', 'Checkout', 'Producer'))) {
            $XML .= "<row><description>{$role}</description></row>";
        }
    }
    return $XML . '</rows>';
}

/**
 * Removes trailing zeros from decimal numbers for display purposes.
 * e.g. "1.500" → "1.5", "2.000" → "2", "abc" → "abc"
 *
 * @param mixed $value
 * @return mixed
 */
function clean_zeros($value)
{
  return (isset($value) && (strpos($value, '.') !== false) ?
	  rtrim(rtrim($value, '0'), '.')
	  : $value);
}

?>
