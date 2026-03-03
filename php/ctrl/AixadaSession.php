<?php

define('DS', DIRECTORY_SEPARATOR);
define('__ROOT__', dirname(dirname(dirname(__FILE__))).DS); 

require_once __ROOT__ . 'php/lib/exceptions.php';
require_once __ROOT__ . 'php/utilities/general.php';

try {

    $uri = (isset($_REQUEST['originating_uri']) ? $_REQUEST['originating_uri'] : 'index.php');
    if (isset($_REQUEST['change_role_to'])) {
        $new_role = $_REQUEST['change_role_to'];
        if (is_created_session()) {
            try {
                change_session_role($new_role);
                // Verify the role was actually changed
                $current_role = get_current_role();
                if ($current_role !== $new_role) {
                    throw new Exception("Role change failed. Current role: " . $current_role . ", requested: " . $new_role);
                }
                $fp = get_config('forbidden_pages');
                if (!$uri || isset($fp[$new_role])) {
                    foreach($fp[$new_role] as $page) {
                        if (strpos($uri, $page) !== false) {
                            $uri = 'index.php';
                            break;
                        }
                    }
                }
            } catch (Exception $e) {
                // If role change fails, redirect to index with error message
                header('HTTP/1.0 400 ' . $e->getMessage());
                $uri = 'index.php';
            }
        } else {
            $uri = 'login.php';
        }
        printXML('<row><navigation>' . $uri . '</navigation></row>');
        exit;
    }
    
    if (isset($_REQUEST['change_lang_to'])) {
        $new_lang = $_REQUEST['change_lang_to'];
        if (is_created_session()) {
            change_session_language($new_lang);
        } else {
            $uri = 'login.php';
        }
        printXML('<row><navigation>' . $uri . '</navigation></row>');
        exit;        
    }
}

catch(Exception $e) {
  header('HTTP/1.0 401 ' . $e->getMessage());
  die($e->getMessage());
}  
