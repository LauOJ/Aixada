<?php

/**
 * Database backup and SQL execution utilities for the Aixada admin panel.
 *
 * @package Aixada
 */

/**
 * Returns a timestamped backup filename based on the configured database name.
 *
 * @return string Backup name (without extension), e.g. 'aixada.2024-01-15_1430'.
 */
function get_backup_name()
{
    return get_config('db_name') . '.' . date('Y-m-d_Hi');
}

/**
 * Creates a compressed backup of the database and saves it to a local folder.
 *
 * @param string $output_folder Relative path from __ROOT__ where the backup is saved.
 * @param string $backup_name   Base filename for the backup (without extension).
 * @return string Full path to the generated .sql.gz file.
 * @throws Exception If the backup fails for any reason.
 */
function backup_as_internal($output_folder, $backup_name)
{
    set_error_handler(function ($err_severity, $err_msg, $err_file, $err_line) {
        throw new Exception($err_msg);
    });
    try {
        $cv = configuration_vars::get_instance();
        $result = $output_folder . backup_by_mysqli(
            $output_folder, $backup_name,
            $cv->db_host, $cv->db_name, $cv->db_user, $cv->db_password
        );
        restore_error_handler();
        return $result;
    } catch (Exception $e) {
        restore_error_handler();
        throw $e;
    }
}

/**
 * Opens a MySQLi connection to the given database.
 * Supports host:port syntax in $host.
 *
 * @param string $host    Hostname, optionally with port (e.g. 'localhost:3306').
 * @param string $db_name Database name.
 * @param string $user    Database user.
 * @param string $pass    Database password.
 * @return mysqli
 * @throws Exception If the connection fails or the charset cannot be set.
 */
function connect_by_mysqli($host, $db_name, $user, $pass)
{
    $host_ = explode(":", $host);
    if (count($host_) > 1) {
        $db = new mysqli($host_[0], $user, $pass, $db_name, $host_[1]);
    } else {
        $db = new mysqli($host, $user, $pass, $db_name);
    }
    if ($db->connect_errno) {
        ob_clean();
        throw new Exception(
            "MySQL Error: {$db->connect_errno}-{$db->connect_error}\n" .
            "Connecting to: host='{$host}' database='{$db_name}' user='{$user}'\n"
        );
    }
    if (!$db->set_charset("utf8")) {
        ob_clean();
        throw new Exception(
            "Not able to set charset='utf8', charset is: {$db->character_set_name()}"
        );
    }
    return $db;
}

/**
 * Executes a list of SQL files against the given database connection.
 *
 * @param mysqli   $db           Active database connection.
 * @param string   $sql_folder   Relative path from __ROOT__ containing the SQL files.
 * @param string[] $sqlFilesArray List of SQL filenames to execute.
 * @return string Log of executed files.
 * @throws Exception If any file fails to execute.
 */
function execute_sql_files($db, $sql_folder, $sqlFilesArray)
{
    $result = '';
    $file = '';
    try {
        foreach ($sqlFilesArray as $file) {
            $result .= "\n * " . execute_sql_file($db, __ROOT__ . $sql_folder, $file);
        }
    } catch (Exception $e) {
        throw new Exception("Error running \"{$file}\": " . $e->getMessage());
    }
    return $result . "\n";
}

/**
 * Creates a compressed (.sql.gz) dump of all tables in the database.
 * Inspiration: http://davidwalsh.name/backup-mysql-database-php
 *
 * @param string $output_folder Relative path from __ROOT__ for the output file.
 * @param string $backup_name   Base filename (without extension).
 * @param string $host          Database host.
 * @param string $db_name       Database name.
 * @param string $user          Database user.
 * @param string $pass          Database password.
 * @return string Filename of the generated .sql.gz file.
 * @throws Exception If the file cannot be created.
 */
function backup_by_mysqli($output_folder, $backup_name, $host, $db_name, $user, $pass)
{
    $db = connect_by_mysqli($host, $db_name, $user, $pass);
    $tables = array();
    $result = $db->query('SHOW TABLES;');
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    $result->close();

    $gzfile_name = $backup_name . '.sql.gz';
    $chunk_max_len = 1024 * 256;
    try {
        $fp = gzopen(__ROOT__ . $output_folder . $gzfile_name, 'w9');
    } catch (Exception $e) {
        throw new Exception($e->getMessage());
    }

    $from = "/* =========\n" .
            "   Backup by mysqli from:\n" .
            "     - server http: " . $_SERVER['HTTP_HOST'] . "\n" .
            "     - mysql host:  $host \n" .
            "     - db_name:     $db_name \n" .
            "     - user:        $user \n" .
            "     - date-time:   " . date('Y-m-d H:i') . "\n" .
            "   ========= */\n";
    gzwrite($fp, $from, strlen($from));

    $db->query('SET sql_quote_show_create=0;');
    $drop_tables   = "\n\n/* =========\n   DROP TABLES\n   ========= */\n" .
                     "SET SESSION FOREIGN_KEY_CHECKS=0;\n\n";
    $create_tables = "\n\n/* =========\n   CREATE TABLES\n   ========= */\n";
    foreach ($tables as $table) {
        $drop_tables .= 'DROP TABLE IF EXISTS ' . $table . ";\n";
        $rs2 = $db->query('SHOW CREATE TABLE ' . $table);
        $row2 = $rs2->fetch_row();
        $create_tables .= $row2[1] . ";\n\n";
    }
    gzwrite($fp, $drop_tables, strlen($drop_tables));
    gzwrite($fp, $create_tables, strlen($create_tables));

    $data = "\n\n/* =========\n   INSERTS\n   ========= */\n" .
            "SET SESSION SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n" .
            "SET SESSION UNIQUE_CHECKS=0;\n" .
            "SET SESSION FOREIGN_KEY_CHECKS=0;\n" .
            "SET SESSION SQL_NOTES=0;\n\n";

    foreach ($tables as $table) {
        $result = $db->query('SELECT * FROM ' . $table);
        $num_fields = $result->field_count;

        $fields = $result->fetch_fields();
        $fields_list = '';
        foreach ($fields as $val) {
            $fields_list .= ',`' . $val->name . '`';
        }
        if ($fields_list == '') {
            continue;
        }
        $insert_into = 'INSERT INTO ' . $table .
            ' (' . substr($fields_list, 1) . ") VALUES\n";
        $row_count  = 0;
        $from_count = 1;
        $sub_count  = 100;
        while ($row = $result->fetch_row()) {
            if ($sub_count >= 100) {
                if ($row_count > 0) {
                    $data .= '); -- from:' . $from_count . ' to:' . $row_count . "\n";
                    $from_count += $sub_count;
                }
                $sub_count = 0;
                $data .= $insert_into;
            } elseif ($row_count > 0) {
                $data .= "),\n";
            }
            $row_count++;
            $sub_count++;
            $data .= '(';
            for ($j = 0; $j < $num_fields; $j++) {
                if (isset($row[$j])) {
                    $data .= '"' . $db->real_escape_string($row[$j]) . '"';
                } elseif (is_null($row[$j])) {
                    $data .= 'NULL';
                } else {
                    $data .= '""';
                }
                if ($j < ($num_fields - 1)) {
                    $data .= ',';
                }
            }
            if (strlen($data) > $chunk_max_len) {
                gzwrite($fp, substr($data, 0, $chunk_max_len), $chunk_max_len);
                $data = substr($data, $chunk_max_len);
            }
        }
        if ($row_count > 0) {
            $data .= '); -- from:' . $from_count . ' to:' . $row_count . "  [END OF TABLE]\n";
        } else {
            $data .= '-- No rows on table: `' . $table . "`  [END OF TABLE]\n";
        }
        $result->close();
        $data .= "\n\n";
    }
    if ($data !== '') {
        gzwrite($fp, $data, strlen($data));
    }
    gzclose($fp);

    return $gzfile_name;
}

/**
 * Executes a single SQL file against the given database connection.
 * Strips DELIMITER directives (not supported by MySQLi multi_query).
 *
 * @param mysqli $db     Active database connection.
 * @param string $folder Absolute path to the folder containing the SQL file.
 * @param string $file   SQL filename.
 * @return string Execution summary.
 * @throws Exception If the SQL execution fails.
 */
function execute_sql_file($db, $folder, $file)
{
    $text = file_get_contents($folder . $file);
    $text = str_replace(array("\r\n", "\r"), array("\n", "\n"), $text);
    $delimeted = preg_split("/delimiter /i", $text);
    $text2 = $delimeted[0] . "\n";
    for ($i = 1; $i < count($delimeted); $i++) {
        $pos = strpos($delimeted[$i], "\n");
        $deli = substr($delimeted[$i], 0, $pos);
        $text2 .=
            "-- START_DELIMETER_REMOVED '{$deli}' --\n" .
            str_replace(
                $deli . "\n",
                "; -- DELIMETER_TO_SEMICOLON --\n",
                substr($delimeted[$i] . "\n", $pos + 1)
            );
    }
    $i = 0;
    if ($db->multi_query($text2)) {
        do {
            $i++;
            if (!$db->more_results()) {
                break;
            }
        } while ($db->next_result());
    }
    if ($db->errno) {
        throw new Exception($db->error);
    }
    return "{$file}: {$i} statements executed correctly.";
}
