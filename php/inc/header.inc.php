<?php

/**
 * Main entry point included by every Aixada page.
 * Defines constants, loads the base header, sets up print templates,
 * and enforces role-based access control.
 *
 * @package Aixada
 */

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
if (!defined('__ROOT__')) define('__ROOT__', dirname(__DIR__, 2) . DS);
require_once(__ROOT__ . "/php/inc/header.inc.base.php");

$tpl_print_orders    = configuration_vars::get_instance()->print_order_template;
$tpl_print_myorders  = configuration_vars::get_instance()->print_my_orders_template;
$tpl_print_bill      = configuration_vars::get_instance()->print_bill_template;
$tpl_print_incidents = configuration_vars::get_instance()->print_incidents_template;

try {
    $fp       = configuration_vars::get_instance()->forbidden_pages;
    $uri      = $_SERVER['REQUEST_URI'];
    $role     = get_current_role();
    $forbidden = false;
    foreach ($fp[$role] as $page) {
        if (strpos($uri, $page) !== false) {
            $forbidden = true;
            break;
        }
    }
    if ($forbidden) {
        header("Location: index.php");
    }
} catch (AuthException $e) {
    header("Location: login.php?originating_uri=" . $_SERVER['REQUEST_URI']);
    exit;
}
