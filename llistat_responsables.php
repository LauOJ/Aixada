<?php include "php/inc/header.inc.php" ?>
<?php
if (!is_created_session()) {
    header('Location: login.php');
    exit;
}

try {
    $db = DBWrap::get_instance();
    $rs = $db->Execute(
        "SELECT p.name AS provider_name,
                p.responsible_uf_id AS uf_id,
                m.name AS member_name,
                m.phone1, m.phone2, u.email
         FROM aixada_provider p
         LEFT JOIN aixada_user u ON u.uf_id = p.responsible_uf_id
         LEFT JOIN aixada_member m ON m.id = u.member_id
         WHERE p.active = 1
         ORDER BY p.name"
    );
    $responsables = [];
    while ($row = $rs->fetch_assoc()) {
        $responsables[] = $row;
    }
    DBWrap::get_instance()->free_next_results();
} catch (Exception $e) {
    $responsables = [];
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <title>Responsables de comanda</title>
    <?= aixada_custom_css() ?>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        h1 { font-size: 1.4rem; margin-bottom: 16px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 7px 12px; text-align: left; }
        th { background: #f0f0f0; font-weight: bold; }
        tr:nth-child(even) { background: #f9f9f9; }
        td.uf { white-space: nowrap; width: 60px; text-align: center; }
        .sense { color: #999; font-style: italic; }
    </style>
</head>
<body>
    <h1>Responsables de comanda per proveïdor</h1>
    <table>
        <thead>
            <tr>
                <th>Proveïdor</th>
                <th>UF responsable</th>
                <th>Contacte</th>
                <th>Telèfon 1</th>
                <th>Telèfon 2</th>
                <th>Email</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($responsables as $row): ?>
            <tr>
                <td><?= htmlspecialchars((string)$row['provider_name']) ?></td>
                <?php if (!empty($row['uf_id'])): ?>
                <td class="uf"><?= htmlspecialchars((string)$row['uf_id']) ?></td>
                <td><?= htmlspecialchars((string)($row['member_name'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string)($row['phone1'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string)($row['phone2'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string)($row['email'] ?? '')) ?></td>
                <?php else: ?>
                <td class="uf sense">—</td>
                <td class="sense" colspan="4">Sense responsable assignat</td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
