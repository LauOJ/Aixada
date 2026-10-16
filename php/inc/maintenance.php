<?php

/**
 * Temporary maintenance gate for the production deploy.
 * Requests from any other address get a maintenance page.
 * Remove the require in database.php when the deploy is finished.
 */

if (defined('AIXADA_MAINTENANCE_CHECKED')) {
    return;
}
define('AIXADA_MAINTENANCE_CHECKED', true);

if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
    return;
}

$aixada_maintenance_allowed_ip = '31.221.163.176';

$aixada_seen_ips = array();
if (!empty($_SERVER['REMOTE_ADDR'])) {
    $aixada_seen_ips[] = trim($_SERVER['REMOTE_ADDR']);
}
foreach (array('HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR') as $aixada_ip_header) {
    if (empty($_SERVER[$aixada_ip_header])) {
        continue;
    }
    foreach (explode(',', $_SERVER[$aixada_ip_header]) as $aixada_ip_part) {
        $aixada_ip_part = trim($aixada_ip_part);
        if ($aixada_ip_part !== '') {
            $aixada_seen_ips[] = $aixada_ip_part;
        }
    }
}

if (in_array($aixada_maintenance_allowed_ip, $aixada_seen_ips, true)) {
    return;
}

$aixada_detected_ip = isset($aixada_seen_ips[0]) ? $aixada_seen_ips[0] : '';
if (!filter_var($aixada_detected_ip, FILTER_VALIDATE_IP)) {
    $aixada_detected_ip = '';
}

http_response_code(503);
header('Retry-After: 7200');
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
?>
<!DOCTYPE html>
<html lang="ca">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Manteniment · La Vinagreta</title>
<style>
body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f0f2f4; color: #333; }
main { max-width: 32rem; margin: 12vh auto; padding: 2rem; background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
h1 { margin: 0 0 .75rem; font-size: 1.4rem; color: #4a5f6f; }
p { margin: 0; line-height: 1.5; }
.ip { margin-top: 1.5rem; font-size: .8rem; color: #777; }
</style>
</head>
<body>
<main>
<h1>Manteniment</h1>
<p>Estem actualitzant l'Aixada. Torna a entrar d'aquí una estona.</p>
<?php if ($aixada_detected_ip !== '') { ?>
<p class="ip">Adreça detectada: <?php echo htmlspecialchars($aixada_detected_ip, ENT_QUOTES, 'UTF-8'); ?></p>
<?php } ?>
</main>
</body>
</html>
<?php
exit;
