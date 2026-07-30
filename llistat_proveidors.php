<?php include "php/inc/header.inc.php" ?>
<?php
if (!is_created_session()) {
    header('Location: login.php');
    exit;
}

try {
    $db = DBWrap::get_instance();
    $rs = $db->Execute(
        "SELECT id, name, notes
         FROM aixada_provider
         WHERE active = 1
         ORDER BY name"
    );
    $proveidors = [];
    while ($row = $rs->fetch_assoc()) {
        $proveidors[] = $row;
    }
    DBWrap::get_instance()->free_next_results();
} catch (Exception $e) {
    $proveidors = [];
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <title>Info de proveïdores</title>
    <?= aixada_custom_css() ?>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; color: #333; }
        h1 { font-size: 1.5rem; margin-bottom: 16px; }
        .index {
            background: #f4f6f7; border: 1px solid #dfe3e6; border-radius: 8px;
            padding: 14px 16px; margin-bottom: 28px;
        }
        .index h2 { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.04em;
            color: #7a8894; margin-bottom: 10px; }
        .index-links { display: flex; flex-wrap: wrap; gap: 8px; }
        .index-links a {
            display: inline-block; background: #fff; border: 1px solid #cdd5db;
            border-radius: 16px; padding: 5px 12px; font-size: 0.9rem;
            text-decoration: none; color: #4a5f6f;
        }
        .index-links a:hover { background: #4a5f6f; color: #fff; }
        .prov {
            border-top: 1px solid #e5e8eb; padding: 18px 0;
            scroll-margin-top: 16px;
        }
        .prov h2 { font-size: 1.15rem; margin-bottom: 6px; color: #2f3e4a; }
        .prov .notes { white-space: pre-line; line-height: 1.5; }
        .prov .sense { color: #999; font-style: italic; }
        .prov .top-link { font-size: 0.82rem; margin-top: 8px; display: inline-block; color: #7a8894; text-decoration: none; }
        .empty-msg { color: #7a8894; padding: 20px 0; }
    </style>
</head>
<body id="top">
    <h1>Info de proveïdores</h1>

    <?php if (empty($proveidors)): ?>
        <p class="empty-msg">No hi ha proveïdores actives.</p>
    <?php else: ?>

    <div class="index">
        <h2>Tria una proveïdora</h2>
        <div class="index-links">
            <?php foreach ($proveidors as $p): ?>
            <a href="#prov-<?= (int)$p['id'] ?>"><?= htmlspecialchars((string)$p['name']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php foreach ($proveidors as $p): ?>
    <div class="prov" id="prov-<?= (int)$p['id'] ?>">
        <h2><?= htmlspecialchars((string)$p['name']) ?></h2>
        <?php $notes = trim((string)($p['notes'] ?? '')); ?>
        <?php if ($notes !== ''): ?>
        <div class="notes"><?= htmlspecialchars($notes) ?></div>
        <?php else: ?>
        <div class="notes sense">Sense descripció. Les responsables la poden afegir al camp de notes del proveïdor.</div>
        <?php endif; ?>
        <a href="#top" class="top-link">↑ tornar a dalt</a>
    </div>
    <?php endforeach; ?>

    <?php endif; ?>
</body>
</html>
