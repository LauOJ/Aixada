<?php
if (!defined('DS'))       define('DS', DIRECTORY_SEPARATOR);
if (!defined('__ROOT__')) define('__ROOT__', dirname(__DIR__) . DS);
include __ROOT__ . 'php/inc/header.inc.php';
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Estoc &middot; La Vinagreta</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f2f4; color: #333; min-height: 100vh;
            -webkit-tap-highlight-color: transparent;
        }
        .app-header {
            background: #4a5f6f; color: #fff; padding: 14px 16px;
            display: flex; align-items: center; gap: 12px; position: sticky; top: 0;
        }
        .app-header .back { font-size: 1.4rem; line-height: 1; cursor: pointer; padding: 2px 6px;
            color: #fff; text-decoration: none; }
        .app-header .title { font-weight: 600; font-size: 1.05rem; }
        .wrap { max-width: 520px; margin: 0 auto; padding: 60px 24px; text-align: center; }
        .wrap .icon { font-size: 3.2rem; }
        .wrap h2 { margin: 18px 0 10px; color: #2f3e4a; }
        .wrap p { color: #7a8894; line-height: 1.5; }
        .btn { display: block; width: 100%; border: none; border-radius: 12px; padding: 16px;
            font-size: 1.05rem; font-weight: 600; margin-top: 28px; background: #fff; color: #4a5f6f;
            border: 1px solid #cdd5db; text-decoration: none; }
    </style>
</head>
<body>
<header class="app-header">
    <a class="back" href="index.php">&#8249;</a>
    <span class="title">Estoc</span>
</header>
<div class="wrap">
    <div class="icon">&#128230;</div>
    <h2>Aviat disponible</h2>
    <p>La compra de la botiga des del mòbil encara està en preparació.<br>
       De moment pots fer servir la vista web completa.</p>
    <a class="btn" href="../shop_and_orderstock.php?what=Shop&amp;force_desktop=1">Obrir la botiga (vista web)</a>
    <a class="btn" href="index.php">Tornar a l'inici</a>
</div>
</body>
</html>
