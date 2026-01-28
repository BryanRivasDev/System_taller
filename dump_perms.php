<?php
require_once 'config/db.php';
$stmt = $pdo->query("SELECT * FROM permissions");
echo "ID | Code | Description\n";
echo "---|---|---\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "{$row['id']} | {$row['code']} | {$row['description']}\n";
}
