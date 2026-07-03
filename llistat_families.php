<?php include "php/inc/header.inc.php" ?>
<?php
if (!is_created_session()) {
    header('Location: login.php');
    exit;
}

try {
    $db = DBWrap::get_instance();
    $rs = $db->Execute(
        "SELECT m.uf_id,
                GROUP_CONCAT(DISTINCT m.name ORDER BY m.name SEPARATOR ', ') AS family_names
         FROM aixada_member m
         WHERE m.name IS NOT NULL AND m.name != '' AND m.uf_id > 0
         GROUP BY m.uf_id
         ORDER BY m.uf_id"
    );
    $families = [];
    while ($row = $rs->fetch_assoc()) {
        $families[] = $row;
    }
    DBWrap::get_instance()->free_next_results();
} catch (Exception $e) {
    $families = [];
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <title>Llistat de Famílies</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        h1 { font-size: 1.4rem; margin-bottom: 16px; }
        table { border-collapse: collapse; width: 100%; max-width: 700px; }
        th, td { border: 1px solid #ccc; padding: 7px 12px; text-align: left; }
        th { background: #f0f0f0; font-weight: bold; }
        tr:nth-child(even) { background: #f9f9f9; }
        td:first-child { white-space: nowrap; width: 60px; }
    </style>
</head>
<body>
    <h1>Llistat de Famílies</h1>
    <table>
        <thead>
            <tr>
                <th>UF</th>
                <th>Membres</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($families as $row): ?>
            <tr>
                <td><?= htmlspecialchars((string)$row['uf_id']) ?></td>
                <td><?= htmlspecialchars((string)$row['family_names']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
