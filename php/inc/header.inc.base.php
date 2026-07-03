<?php

/**
 * Base initialization loaded by every Aixada page via header.inc.php.
 * Loads config, utilities, language file, and defines JS/CSS helper functions.
 *
 * @package Aixada
 */

if (!defined('__ROOT__')) {
    define('DS', DIRECTORY_SEPARATOR);
    define('__ROOT__', dirname(__DIR__, 2) . DS);
}

require_once(__DIR__ . "/header.inc.version.php");
require_once(__ROOT__ . 'local_config' . DS . 'config.php');
require_once(__ROOT__ . 'php' . DS . 'utilities' . DS . 'general.php');
require_once(__ROOT__ . 'php' . DS . 'utilities' . DS . 'negative_balances.php');

$language      = get_session_language();
$default_theme = get_session_theme();

require_once(__ROOT__ . 'local_config' . DS . 'lang' . DS . $language . '.php');

/**
 * Returns the cache-buster version string for JS and CSS assets.
 *
 * @return string Version date string (e.g. '20181210_140125').
 */
function aixada_js_version()
{
    global $aixada_vesion_lastDate;
    return $aixada_vesion_lastDate;
}

/**
 * Returns HTML script tags for the core Aixada JavaScript files.
 *
 * @param bool   $useMenus Include menu JS files (set false for login/print pages).
 * @param string $rootJs   Path prefix to the JS root, e.g. '../' for subpages.
 * @return string HTML <script> tags ready to embed.
 */
function aixada_js_src($useMenus = true, $rootJs = '')
{
    global $aixada_vesion_lastDate;
    $v = $aixada_vesion_lastDate;

    $src = '';
    if ($useMenus) {
        $src .= "
        <script src=\"{$rootJs}js/fgmenu/fg.menu.js?v={$v}\"></script>
        <script src=\"{$rootJs}js/aixadautilities/jquery.aixadaMenu.js?v={$v}\"></script>";
    }
    $src .= "
        <script src=\"{$rootJs}js/aixadautilities/jquery.aixadaXML2HTML.js?v={$v}\"></script>
        <script src=\"{$rootJs}js/aixadautilities/jquery.aixadaUtilities.js?v={$v}\"></script>
        <script src=\"{$rootJs}js/aixadautilities/i18n/aixadaUtilities-" . get_session_language() .
            ".js?v={$v}\"></script>";

    if (get_config('use_ajaxQueue')) {
        $src .= "
        <script src=\"{$rootJs}js/jquery-ajaxQueue/jQuery.ajaxQueue.js?v={$v}\"></script>";
    } else {
        $src .= "
        <script src=\"{$rootJs}js/jquery-ajaxQueue/jQuery.ajaxQueueNo.js?v={$v}\"></script>";
    }

    $src .= include_negative_balances_js();

    return $src . "\n";
}

/**
 * Returns an HTML link tag for the coop-specific custom CSS, if it exists.
 * Uses the same cache-buster version as JS assets.
 *
 * @return string HTML <link> tag, or empty string if no custom CSS file is present.
 */
function aixada_custom_css()
{
    global $aixada_vesion_lastDate;
    if (!file_exists(__ROOT__ . 'css' . DS . 'custom.css')) {
        return '';
    }
    return '<link rel="stylesheet" type="text/css" media="screen" href="css/custom.css?v=' . $aixada_vesion_lastDate . '"/>' . "\n";
}
