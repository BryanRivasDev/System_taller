<?php
require_once 'config/db.php';
echo "<h2>Table Schema</h2>";
$stmt = $pdo->query("SHOW CREATE TABLE user_custom_modules");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<pre>" . print_r($row, true) . "</pre>";

echo "<h2>Current Data (Duplicates Check)</h2>";
$stmt = $pdo->query("SELECT user_id, module_name, COUNT(*) as c FROM user_custom_modules GROUP BY user_id, module_name HAVING c > 1");
$dupes = $stmt->fetchAll(PDO::FETCH_ASSOC);
if ($dupes) {
    echo "<h3>Found Duplicates!</h3>";
    echo "<pre>" . print_r($dupes, true) . "</pre>";
} else {
    echo "<p>No duplicates found.</p>";
}

echo "<h2>All Data</h2>";
$stmt = $pdo->query("SELECT * FROM user_custom_modules ORDER BY user_id");
echo "<pre>" . print_r($stmt->fetchAll(PDO::FETCH_ASSOC), true) . "</pre>";
?>
