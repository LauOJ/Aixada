<?php include "php/inc/header.inc.php" ?>
<?php
if (!is_created_session()) {
    header('Location: login.php');
    exit;
}

try {
    $db = DBWrap::get_instance();
    $rs = $db->Execute(
        "SELECT DISTINCT u.member_id, m.uf_id, m.name AS member_name,
                m.phone1, m.phone2, u.email
         FROM aixada_member m
         INNER JOIN aixada_user u ON m.uf_id = u.uf_id
         WHERE m.id = u.member_id
           AND m.uf_id IS NOT NULL AND m.uf_id != ''
         ORDER BY m.uf_id"
    );
    $contactes = [];
    while ($row = $rs->fetch_assoc()) {
        $contactes[] = $row;
    }
    DBWrap::get_instance()->free_next_results();
} catch (Exception $e) {
    $contactes = [];
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <title>Llistat de Contactes</title>
    <?= aixada_custom_css() ?>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        h1 { font-size: 1.4rem; margin-bottom: 16px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 7px 12px; text-align: left; }
        th { background: #f0f0f0; font-weight: bold; }
        tr:nth-child(even) { background: #f9f9f9; }
        td:first-child { white-space: nowrap; width: 60px; }
    </style>
</head>
<body>
    <h1>Llistat de Contactes per UF</h1>
    <table>
        <thead>
            <tr>
                <th>UF</th>
                <th>Nom</th>
                <th>Telèfon 1</th>
                <th>Telèfon 2</th>
                <th>Email</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($contactes as $row): ?>
            <tr>
                <td><?= htmlspecialchars((string)$row['uf_id']) ?></td>
                <td><?= htmlspecialchars((string)$row['member_name']) ?></td>
                <td><?= htmlspecialchars((string)($row['phone1'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string)($row['phone2'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string)($row['email'] ?? '')) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
