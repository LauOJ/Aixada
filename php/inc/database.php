<?php

/**
 * Database abstraction layer for Aixada.
 * All database access goes through DBWrap — never use mysqli directly.
 *
 * @package Aixada
 */

require_once(__ROOT__ . 'local_config'.DS.'config.php');
require_once(__ROOT__ . 'php'.DS.'utilities'.DS.'general.php');
require_once(__ROOT__ . 'php'.DS.'lib'.DS.'exceptions.php');
require_once(__ROOT__ . 'local_config'.DS.'lang'.DS. get_session_language() . '.php');


/**
 * Singleton class that wraps all database operations.
 *
 * Usage: $db = DBWrap::get_instance();
 *
 * @package Aixada
 * @subpackage Database_Management
 */
class DBWrap {

  private $mysqli = false;
  private static $instance = false;

  /**
   * @var string The last SQL query sent to the database. Useful for debugging errors.
   */
  public $last_query_SQL = '';

  /**
   * @var string The second-to-last SQL query sent to the database.
   */
  public $next_to_last_query_SQL = '';

  /**
   * Connects to the database using credentials from configuration_vars.
   * Supports an optional port in the host string (e.g. "localhost:3307").
   * Sets charset to UTF-8, disables strict SQL mode, and raises the
   * GROUP_CONCAT length limit to 10000.
   *
   * @throws InternalException if the connection fails or charset cannot be set.
   */
  private function __construct()
  {
    $cv = configuration_vars::get_instance();
    $host = $cv->db_host;
    $db_name = $cv->db_name;
    $user = $cv->db_user;
    $password = $cv->db_password;
    $host_ = explode(":", $host);

    if (count($host_) > 1) {
        $this->mysqli = new mysqli($host_[0], $user, $password, $db_name, $host_[1]);
    } else {
        $this->mysqli = new mysqli($host, $user, $password, $db_name);
    }
    if (mysqli_connect_errno())
      throw new InternalException('Unable to connect to database. ' . mysqli_connect_error());
    if (!$this->mysqli->set_charset("utf8"))
        throw new InternalException('Unable to select charset utf8. Current character set: '
                                    . $this->mysqli->character_set_name());
    $this->mysqli->query("SET @@SQL_MODE = ' ';"); // At least one blank space is required!
                                                   // otherwise, it does not act in MariaDB 10.3.13
    $this->mysqli->query("SET @@group_concat_max_len = 10000;");
  }

  /**
   * Returns the single shared instance of DBWrap (Singleton pattern).
   * Creates it on the first call; returns the existing one on subsequent calls.
   *
   * @return DBWrap
   */
  public static function get_instance()
  {
    if (self::$instance === false)
      self::$instance = new DBWrap;
    return self::$instance;
  }

  /**
   * Starts a database transaction.
   * Use commit() to confirm or rollback() to undo.
   */
  public function start_transaction() {
    return $this->mysqli->query("START TRANSACTION;");
  }

  /**
   * Commits the current transaction, making all changes permanent.
   */
  public function commit() {
    return $this->mysqli->query("COMMIT;");
  }

  /**
   * Rolls back the current transaction, undoing all changes since start_transaction().
   */
  public function rollback() {
    return $this->mysqli->query("ROLLBACK;");
  }

  /**
   * Translates a MySQL error code into a human-readable exception.
   * Handles foreign key violations (1451, 1452) with specific messages;
   * all other errors throw a generic DataException.
   *
   * TODO: replace fragile string parsing with regex.
   * TODO: move error messages to $Text for translation support.
   *
   * @param int $errno MySQL error number
   * @param string $error MySQL error message
   * @param string $safe_sql_string The query that caused the error
   * @throws ForeignKeyException|DataException
   */
  private function handle_execute_error($errno, $error, $safe_sql_string)
  {
      switch ($errno) {
      case 1451:
          /*
           foreign key constraint violated. sample error message:
           Cannot delete or update a parent row: a foreign key constraint fails (`aixada`.`aixada_order_item`, CONSTRAINT `aixada_order_item_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `aixada_product` (`id`))
          */
          $msg_array = explode('`', $error);
          $tmp = $msg_array[5]; // aixada_order_item_ibfk_2 in the example
          $upos = strrpos($tmp, '_');
          $upos = strrpos($tmp, '_', $upos-strlen($tmp)-1);
          throw new ForeignKeyException('ERROR 10: Cannot modifiy this entry because entries in ' . substr($tmp, 0, $upos) . ' depend on it');

      case 1452:
          /*
           Cannot add or update a child row: a foreign key constraint fails (`aixada`.`aixada_provider`, CONSTRAINT `aixada_provider_ibfk_1` FOREIGN KEY (`responsible_uf_id`) REFERENCES `aixada_uf` (`id`)
          */
          $msg_array = explode('`', $error);
          $bad_field = $msg_array[7]; // responsible_uf_id in the example
          global $Text;
          if (isset($Text[$bad_field]))
              $bad_field = $Text[$bad_field];
          $tmp = substr($bad_field, 0, strrpos($bad_field, '_'));
          if (isset($Text[$tmp]))
              $bad_field = $Text[$tmp];
          throw new ForeignKeyException('ERROR 20: Foreign Key exception. Please check the field "' .
                                        $bad_field .
                                        '". It either does not exist in the db or does not fullfil a foreign key constraint?');

      default:
          throw new DataException($safe_sql_string . ' generated error ' . $errno . ': ' . $error);
      }
  }

  /**
   * Internal method that actually executes a query against the database.
   * All other query methods go through here.
   * Stores the query in last_query_SQL for debugging.
   *
   * @param string $safe_sql_string An already-escaped SQL query string.
   * @param bool $multi If true, uses multi_query to run several statements at once.
   * @return mixed Query result resource, or true for non-SELECT queries.
   * @throws ForeignKeyException|DataException on failure.
   */
  private function do_Execute($safe_sql_string, $multi = false)
  {
    $rs = ($multi ?
	   $this->mysqli->multi_query($safe_sql_string) :
	   $this->mysqli->query($safe_sql_string));
    if (!$rs)
      $this->handle_execute_error($this->mysqli->errno, $this->mysqli->error, $safe_sql_string);
    $this->next_to_last_query_SQL = $this->last_query_SQL;
    $this->last_query_SQL = $safe_sql_string;
    return $rs;
  }

  /**
   * Executes a pre-built SQL query string with no placeholders.
   * Use Execute() instead when you need to pass parameters safely.
   *
   * @param string $strSQL A complete, already-safe SQL query string.
   * @return mixed Query result.
   */
  public function do_stored_query ($strSQL)
  {
    return $this->do_Execute($strSQL);
  }

  /**
   * Returns the auto-generated ID from the last INSERT operation.
   * Use this immediately after Insert() to get the new record's ID.
   *
   * @return int The ID generated by AUTO_INCREMENT.
   */
  public function last_insert_id()
  {
      return $this->mysqli->insert_id;
  }


  /**
   * Frees any pending results from a multi_query execution.
   * Call this after MultiExecute() to release memory held by unconsumed results.
   */
  public function free_next_results()
  {
      while ($this->mysqli->more_results()) {
          $this->mysqli->next_result();
          $rs = $this->mysqli->use_result();
          if ($rs instanceof mysqli_result)
              $rs->free();
      }
  }


  /**
   * Escapes special characters in a string to make it safe for use in an SQL query.
   *
   * @param string $text The raw input string.
   * @return string The escaped string, safe to embed in SQL.
   */
  public function escape_string($text) {
        return $this->mysqli->real_escape_string($text);
  }

  /**
   * Substitutes placeholders (:1, :2, ...) in an SQL string with escaped values.
   * Use :Nq (e.g. :1q) to wrap the value in single quotes (required for strings).
   * Use :N (e.g. :1) for numeric values without quotes.
   *
   * TODO: replace with MySQLi prepared statements for safety and clarity.
   *
   * @param array &$binds Array where index 0 is the SQL string and the rest are the values.
   * @return string The SQL string with placeholders replaced by escaped values.
   */
  private function make_safe_sql_str (&$binds)
  {
    $strSQL = array_shift($binds);
    foreach ($binds as $index => $name) {
      $replace = $this->mysqli->real_escape_string($name);
      $i  = $index+1;
      $qpos = strpos($strSQL, ":$i") + 2;
      if ($i>9) $qpos++;
      if ($i>99) $qpos++;
      if (strpos($strSQL, 'q', $qpos) == $qpos) {
	$replace = "'" . $replace . "'";
	$strSQL = str_replace(":{$i}q", $replace, $strSQL);
      } else {
	$strSQL = str_replace(":$i", $replace, $strSQL);
      }
    }
    return $strSQL;
  }

  /**
   * Executes an SQL query with placeholder substitution.
   * Pass the SQL string first, then each value as a separate argument.
   * Accepts any number of arguments, or a single array containing them all.
   *
   * Example: $db->Execute("SELECT * FROM aixada_provider WHERE id=:1q AND active=:2", $id, 1);
   *
   * @return mixed Query result resource, or true for non-SELECT queries.
   */
  public function Execute ()
  {
    $binds = func_get_args();
    if (is_array($binds[0])) {
      $binds = $binds[0];
    }
    return $this->do_Execute($this->make_safe_sql_str($binds));
  }

  /**
   * Like Execute(), but runs multiple SQL statements separated by semicolons.
   * Returns the result of the first statement.
   * Call free_next_results() afterwards to release memory from remaining results.
   *
   * @return mixed Result of the first query.
   */
  public function MultiExecute ()
  {
    $binds = func_get_args();
    $this->do_Execute($this->make_safe_sql_str($binds), true);
    return $this->mysqli->use_result();
  }

  /**
   * Inserts a new row into a database table.
   * The array must contain a 'table' key with the table name,
   * plus one key per column to insert. Only columns listed in col_names.php
   * are accepted — others are silently ignored.
   *
   * Example: $db->Insert(['table' => 'aixada_provider', 'name' => 'La Tavella', 'active' => 1]);
   *
   * @param array $arrData Associative array with 'table' and column => value pairs.
   * @throws InternalException if 'table' is missing or the table is not whitelisted.
   */
  public function Insert ($arrData)
  {
      if (!array_key_exists('table', $arrData))
	  	throw new InternalException('Insert: Input array ' . $arrData . ' does not contain a field named "table"');
      $table_name = $arrData['table'];

      $strSQL = 'INSERT INTO ' . $this->mysqli->real_escape_string($table_name) . ' (';
      $strVAL = 'VALUES (';
      $all_col_names = unserialize(file_get_contents(__ROOT__ .'col_names.php'));
      if (!array_key_exists($table_name, $all_col_names)) {
	  throw new InternalException('Inserting into table ' . $table_name . ' not permitted');
      }
      $col_names = $all_col_names[$table_name];
      $ct = 0;
      foreach ($arrData as $field => $value) {
	  if (in_array($field, $col_names)) {
	      if ($ct > 0) {
		  $strSQL .= ',';
		  $strVAL .= ',';
	      } else $ct++;

	      $strSQL .= $this->mysqli->real_escape_string($field);
	      $strVAL .= "'" . $this->mysqli->real_escape_string($value) . "'";
	  }
      }
      $strSQL .= ') ' . $strVAL . ');';
      return $this->do_Execute($strSQL); // TODO: extract new index
  }

  /**
   * Updates an existing row in a database table.
   * The array must contain 'table' and 'id' keys.
   * Only columns listed in col_names.php are updated — others are silently ignored.
   *
   * @param array $arrData Associative array with 'table', 'id', and column => value pairs.
   * @throws InternalException if 'table' or 'id' are missing, or the table is not whitelisted.
   * @see Insert
   */
  public function Update($arrData)
  {
      if (!array_key_exists('table', $arrData))
	  throw new InternalException('Update: Input array ' . $arrData . ' does not contain a field named "table"');
      $table_name = $arrData['table'];

      if (!array_key_exists('id', $arrData))
	  throw new InternalException('Update: Input array ' . $arrData . ' for table ' . $table_name . ' does not contain a field named "id"');
      $strSQL = 'UPDATE ' . $this->mysqli->real_escape_string($table_name) . ' SET ';

      $all_col_names = unserialize(file_get_contents(__ROOT__ .'col_names.php'));
      if (!array_key_exists($table_name, $all_col_names)) {
	  throw new InternalException('Updating table ' . $table_name . ' not permitted');
      }
      $col_names = $all_col_names[$table_name];

      $ct=0;
      foreach ($arrData as $field => $value) {
	  if ($field != 'id' and in_array($field, $col_names)) {
	      if ($ct > 0) $strSQL .= ','; else $ct++;
	      $strSQL .= $this->mysqli->real_escape_string($field) . "='"
		  . $this->mysqli->real_escape_string($value) . "'";
	  }
      }
      $strSQL .= ' WHERE id=' . $this->mysqli->real_escape_string($arrData['id']) . ';';

      return $this->do_Execute($strSQL);
  }

  /**
   * Deletes a row from a database table by ID.
   * Special case: deleting a product also deletes its price history in a transaction.
   *
   * @param string $_tn Table name.
   * @param int $_id ID of the row to delete.
   * @throws InternalException if the table is not whitelisted.
   */
  public function Delete($_tn, $_id)
  {
      $table_name = $this->mysqli->real_escape_string($_tn);
      $all_col_names = unserialize(file_get_contents(__ROOT__ .'col_names.php'));
      if (!array_key_exists($table_name, $all_col_names)) {
	  throw new InternalException('Deleting from table ' . $table_name . ' not permitted');
      }
      $id = $this->mysqli->real_escape_string($_id);
      if ($table_name == 'aixada_product') {
	  $strSQL = "
start transaction;
delete from aixada_price where product_id='{$id}';
delete from aixada_product where id='{$id}';
commit;";
	  $multi = true;
      } else {
	  $strSQL = "delete from {$table_name} where id='{$id}'";
	  $multi = false;
      }
      return $this->do_Execute($strSQL, $multi);
  }

  /**
   * Calculates pagination offsets for a given page and page size.
   *
   * @param int $count Total number of records in the table.
   * @param int $page The requested page number (1-based).
   * @param int $limit Number of records per page.
   * @return array [$start, $total_pages] where $start is the SQL LIMIT offset.
   */
  public function calculate_page_limits ($count, $page, $limit)
  {
    $total_pages = ($count>0) ? ceil($count/$limit) : 0;
    if ($page < 0)
      $page = 0;
    if ($page > $total_pages && $total_pages>0)
      $page = $total_pages;
    $start = $limit * $page - $limit;
    return array($start, $total_pages);
  }

  /**
   * Builds a COUNT(*) query string for a given table and optional filter.
   * Used internally by canned_select() to calculate pagination.
   *
   * @param string $table_name The table to count rows from.
   * @param string $filter Optional WHERE clause (without the WHERE keyword).
   * @return string The COUNT query string.
   */
  public function make_count_string ($table_name, $filter)
  {
    $strSQL = 'SELECT COUNT(*) AS count';
    $strSQL .= ' FROM ' . $this->mysqli->real_escape_string($table_name);
    if ($filter)
      $strSQL .= ' WHERE ' . $this->mysqli->real_escape_string($filter);
    return $strSQL;
  }

  /**
   * Builds a SELECT query string from its component parts.
   * Used internally by Select() and canned_select().
   *
   * @param string|array $fields Columns to select: either "*", "id, name", or ["id", "name"].
   * @param string $table_name The table to select from.
   * @param string $filter Optional WHERE clause (without the WHERE keyword).
   * @param string $order_by Column to sort by.
   * @param string $order_sense 'asc' or 'desc'.
   * @param int $page Page number for pagination (-1 = no pagination).
   * @param int $limit Rows per page (-1 = no pagination).
   * @return string The SELECT query string.
   */
  public function make_select_string ($fields, $table_name, $filter, $order_by, $order_sense='asc', $page=-1, $limit=-1)
  {
    $the_table = $this->mysqli->real_escape_string($table_name);
    $the_filter = $this->mysqli->real_escape_string($filter);

    $strSQL = 'SELECT ';
    if (is_string($fields))
      $strSQL .= $this->mysqli->real_escape_string($fields);
    else if (is_array($fields)) {
      $ct=0;
      foreach ($fields as $field) {
	if ($ct>0)
	  $strSQL .= ',';
	else $ct++;
	$strSQL .= $this->mysqli->real_escape_string($field);
      }
    } else throw new InternalException('Argument ' . $field . ' is neither string nor array');

    $strSQL .= ' FROM ' . $the_table;
    if ($filter)
      $strSQL .= ' WHERE ' . $the_filter;
    if ($order_by)
      $strSQL .= ' ORDER BY ' . $this->mysqli->real_escape_string($order_by) . ' ' . $order_sense;
    return $strSQL;
  }

  /**
   * Executes a SELECT with optional pagination.
   * If $page and $limit are provided, first runs a COUNT query to calculate
   * total pages, then runs the real query with LIMIT.
   *
   * @param string $count_querySQL A COUNT(*) query string.
   * @param string $real_querySQL The actual SELECT query string.
   * @param int $page Page number (-1 = return all rows).
   * @param int $limit Rows per page.
   * @return mixed Result resource, or [$result, $count, $total_pages] if paginating.
   */
  public function canned_select($count_querySQL, $real_querySQL, $page=-1, $limit=-1)
  {
    if ($page != -1 && $limit != null) {
      $rs = $this->do_Execute($count_querySQL);
      $row = $rs->fetch_array();
      $count = $row[0];
      list($start, $total_pages) = $this->calculate_page_limits($count, $page, $limit);
      $real_querySQL .= ' LIMIT ' . $start . ', ' . $limit;
    }
    $rs = $this->do_Execute($real_querySQL);
    if ($page != -1)
      return array($rs, $count, $total_pages);
    else return $rs;
  }

  /**
   * Main public SELECT method. Builds and executes a SELECT query with optional
   * filtering, ordering and pagination.
   *
   * NOTE: the WHERE clause has a known security limitation — prefer Execute()
   * with explicit placeholders for complex filtered queries.
   *
   * @param string|array $fields Columns to select.
   * @param string $table_name Table to query.
   * @param string $filter Optional WHERE clause.
   * @param string $order_by Column to sort by.
   * @param string $order_sense 'asc' or 'desc'.
   * @param int $page Page number for jqGrid (-1 = no pagination).
   * @param int $limit Rows per page for jqGrid.
   * @return mixed Result resource, or [$result, $count, $total_pages] if paginating.
   */
  public function Select($fields, $table_name, $filter, $order_by, $order_sense='asc', $page=-1, $limit=-1)
  {
    $count_querySQL = $this->make_count_string($table_name, $filter);
    $real_querySQL = $this->make_select_string($fields, $table_name, $filter, $order_by, $order_sense, $page, $limit);
    return $this->canned_select($count_querySQL, $real_querySQL, $page, $limit);
  }
}

?>
