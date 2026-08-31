<?php
if (!defined('DS'))       define('DS', DIRECTORY_SEPARATOR);
if (!defined('__ROOT__')) define('__ROOT__', dirname(__DIR__) . DS);
include __ROOT__ . 'php/inc/header.inc.php';

$member_name = '';
$uf_id       = get_session_value('uf_id');
try {
    $db = DBWrap::get_instance();
    $rs = $db->Execute('SELECT name FROM aixada_member WHERE id = :1q', get_session_member_id());
    if ($row = $rs->fetch_assoc()) { $member_name = $row['name']; }
    DBWrap::get_instance()->free_next_results();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>La Vinagreta</title>
    <script src="../js/jquery/jquery.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f2f4;
            color: #333;
            min-height: 100vh;
        }

        /* ── Capçalera ── */
        .app-header {
            background: #4a5f6f;
            color: white;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .app-header .user-name { font-weight: 600; font-size: 1rem; }
        .app-header .user-uf   { font-size: 0.78rem; opacity: 0.75; margin-top: 2px; }
        .logout-btn {
            background: none;
            border: 1px solid rgba(255,255,255,0.55);
            color: white;
            border-radius: 6px;
            padding: 7px 14px;
            font-size: 0.85rem;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }
        .logout-btn:active { opacity: 0.7; }

        /* ── Cos principal ── */
        .app-main {
            padding: 28px 20px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            max-width: 480px;
            margin: 0 auto;
        }

        /* ── Botons d'app ── */
        .app-btn {
            display: flex;
            align-items: center;
            gap: 16px;
            background: white;
            border: none;
            border-radius: 14px;
            padding: 22px 20px;
            font-size: 1.15rem;
            font-weight: 600;
            color: #222;
            text-decoration: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
            transition: transform 0.1s ease, box-shadow 0.1s ease;
        }
        .app-btn:active {
            transform: scale(0.97);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            color: #222;
        }
        .app-btn:hover { color: #222; }
        .btn-icon  { font-size: 1.9rem; width: 48px; text-align: center; flex-shrink: 0; }
        .btn-label { flex: 1; }
        .btn-arrow { color: #bbb; font-size: 1.1rem; }

        .app-btn-secondary {
            background: none;
            border: 1px solid #d0d5da;
            box-shadow: none;
            color: #666;
            font-weight: 400;
            font-size: 1rem;
            padding: 16px 20px;
            margin-top: 10px;
        }
        .app-btn-secondary:active { color: #333; background: #f5f5f5; transform: scale(0.98); }
        .app-btn-secondary:hover  { color: #333; }
        .app-btn-secondary .btn-icon { font-size: 1.3rem; }
    </style>
</head>
<body>

<header class="app-header">
    <div>
        <div class="user-name"><?= htmlspecialchars($member_name ?: get_session_value('login')) ?></div>
        <div class="user-uf">Unitat Familiar <?= (int)$uf_id ?></div>
    </div>
    <button class="logout-btn" id="logout-btn">Surt</button>
</header>

<main class="app-main">

    <a href="order.php" class="app-btn">
        <span class="btn-icon">🛒</span>
        <span class="btn-label">Fer comanda</span>
        <span class="btn-arrow">›</span>
    </a>

    <a href="stock.php" class="app-btn">
        <span class="btn-icon">📦</span>
        <span class="btn-label">Estoc</span>
        <span class="btn-arrow">›</span>
    </a>

    <a href="../aixada_main.php?force_desktop=1" class="app-btn app-btn-secondary">
        <span class="btn-icon">🖥️</span>
        <span class="btn-label">Vista web completa</span>
        <span class="btn-arrow">›</span>
    </a>

</main>

<script>
$('#logout-btn').on('click', function () {
    $.post('../php/ctrl/Login.php?oper=logout', function () {
        window.location.href = '../login.php';
    });
});
</script>
</body>
</html>
