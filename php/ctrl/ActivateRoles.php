<?php

define('DS', DIRECTORY_SEPARATOR);
define('__ROOT__', dirname(dirname(dirname(__FILE__))).DS); 

require_once(__ROOT__ . "local_config/config.php");
require_once(__ROOT__ . "php/inc/database.php");
require_once(__ROOT__ . "php/utilities/general.php");

try{
  validate_session(); // The user must be logged in.
    
  $user_id = isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : '';


  switch($_REQUEST['oper']) {

  case 'listUsers':
      printXML(stored_query_XML_fields('get_users'));
      exit;

  case 'getActivatedRoles':
      printXML(stored_query_XML_fields('get_active_roles', $user_id));
      exit;

  case 'getDeactivatedRoles':
      $rs = do_stored_query('get_active_roles', $user_id);
      $roles = array_keys(configuration_vars::get_instance()->forbidden_pages);
      $active_roles = array();
      while ($row = $rs->fetch_array()) {
          $active_roles[] = $row[0];
      }
      $inactive_roles = array_diff($roles, $active_roles);
      $XML = '<rowset>';
      foreach ($inactive_roles as $role) {
          $XML .= "<row><role>{$role}</role></row>";
      }
      printXML($XML . '</rowset>');
      exit;

  case 'getActivatedUsers':
      printXML(stored_query_XML_fields('get_active_users_for_role', $_REQUEST['role']));
      exit;

  case 'getDeactivatedUsers':
      printXML(stored_query_XML_fields('get_inactive_users_for_role', $_REQUEST['role']));
      exit;
      
  case 'activateRoles':
      $db = DBWrap::get_instance();
      $user_id = intval($user_id); // Sanitize user_id
      
      // Start transaction to ensure atomicity
      $db->start_transaction();
      
      try {
          // Delete existing roles for this user
          $db->Execute("DELETE FROM aixada_user_role WHERE user_id = :1", $user_id);
          
          // Insert new roles
          if (!empty($_REQUEST['role_ids'])) {
              $role_ids = explode(',', $_REQUEST['role_ids']);
              foreach ($role_ids as $role) {
                  $role = trim($role);
                  if (!empty($role)) {
                      $db->Execute("INSERT INTO aixada_user_role (user_id, role) VALUES (:1, :2q)", $user_id, $role);
                  }
              }
          }
          
          // Commit transaction
          $db->commit();
      } catch (Exception $e) {
          // Rollback on error
          $db->rollback();
          throw $e;
      }
      exit;

  case 'activateUsers':
      $db = DBWrap::get_instance();
      $role = trim($_REQUEST['role']);
      
      if (empty($role)) {
          throw new Exception("Role cannot be empty");
      }
      
      // Start transaction to ensure atomicity
      $db->start_transaction();
      
      try {
          // Delete existing users with this role
          $db->Execute("DELETE FROM aixada_user_role WHERE role = :1q", $role);
          
          // Insert new users with this role
          if (!empty($_REQUEST['user_ids'])) {
              $user_ids = explode(',', $_REQUEST['user_ids']);
              foreach ($user_ids as $user_id) {
                  $user_id = intval(trim($user_id));
                  if ($user_id > 0) {
                      $db->Execute("INSERT INTO aixada_user_role (user_id, role) VALUES (:1, :2q)", $user_id, $role);
                  }
              }
          }
          
          // Commit transaction
          $db->commit();
      } catch (Exception $e) {
          // Rollback on error
          $db->rollback();
          throw $e;
      }
      exit;

  default:
    throw new Exception("ctrlActivateProducts: variable oper not recognized in query");
    
  }
} 

catch(Exception $e) {
  header('HTTP/1.0 401 ' . $e->getMessage());
  die ($e->getMessage());
}  


?>