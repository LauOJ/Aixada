<?php

define('DS', DIRECTORY_SEPARATOR);
define('__ROOT__', dirname(__DIR__, 2) . DS);

require_once(__ROOT__ . "local_config/config.php");
require_once(__ROOT__ . "php/inc/database.php");
require_once(__ROOT__ . "php/inc/authentication.inc.php");
require_once(__ROOT__ . "php/utilities/general.php");
require_once(__ROOT__ . "php/utilities/useruf.php");
require_once(__ROOT__ . "php/lib/exceptions.php");



try{
  switch ($_REQUEST['oper']) {
	
	  case 'logout':
	      try {
	        logout_session();
	      }
	      catch (AuthException $e) {
	        die($e->getMessage());
	      }
	      exit;
	
	  case 'login':
	      $uri = get_param('originating_uri',''); 
	      if (!$uri) {
	          $uri = 'index.php';
	      }
	      
	      try {
	          $auth = new Authentication();
	          list($user_id,
	               $login,
	               $uf_id,
	               $member_id,
	               $provider_id,
	               $roles,
	               $current_role,
	               $current_language_key,
	               $theme) = $auth->check_credentials(get_param('login'), get_param('password'));
	      	  
	          $langs = existing_languages();
	          create_session( 
	                               $user_id, 
	                               $login, 
	                               $uf_id, 
	                               $member_id, 
	                               $provider_id, 
	                               $roles, 
	                               $current_role, 
	                               array_keys($langs), 
	                               array_values($langs), 
	                               $current_language_key,
	                               $theme);
	      }	catch (AuthException $e) {
		  	header("HTTP/1.1 401 Unauthorized " . $e->getMessage());
	        die($e->getMessage());
	      }	
	      exit; 

      case 'recoverPassword':
          echo reset_password_by_login(get_param('login', ''));
          exit;
	      	
	  default:
	    throw new Exception("ctrl/Login: operation {$_REQUEST['oper']} not supported");
	    
  }

} 

catch(Exception $e) {
   header('HTTP/1.0 419 ' . $e->getMessage());
   die($e->getMessage());
}  


?>