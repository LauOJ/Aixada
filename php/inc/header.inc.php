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

// On a mobile device the default role is consumidora (everyone has it) and
// the user is sent to the app UI. Other roles/tasks are meant for the desktop
// web view, reachable via ?force_desktop=1 ("Vista web completa").
if (is_created_session()) {
    if (isset($_GET['force_desktop'])) {
        $_SESSION['aixada_desktop_mode'] = true;
    }
    $is_mobile_page  = strpos($_SERVER['PHP_SELF'] ?? '', '/mobile/') !== false;
    $is_desktop_mode = !empty($_SESSION['aixada_desktop_mode']);
    if (!$is_desktop_mode) {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (preg_match('/Mobile|Android|iPhone|iPad|iPod|Windows Phone/i', $ua)) {
            // Find the consumidora role however it is named for this user.
            $roles = get_session_value('roles');
            $consumidora_role = null;
            if (is_array($roles)) {
                foreach (['consumidora', 'Consumidora', 'Consumer'] as $r) {
                    if (in_array($r, $roles)) { $consumidora_role = $r; break; }
                }
            }
            if ($consumidora_role !== null) {
                // Force the active role to consumidora while on mobile.
                if (get_current_role() !== $consumidora_role) {
                    change_session_role($consumidora_role);
                }
                // Send non-app pages to the app home.
                if (!$is_mobile_page) {
                    $base = rtrim(dirname($_SERVER['PHP_SELF'] ?? '/'), '/\\') . '/';
                    header('Location: ' . $base . 'mobile/index.php');
                    exit;
                }
            }
        }
    }
}
