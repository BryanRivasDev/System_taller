<?php
require_once 'config/db.php';
$stmt = $pdo->query("SELECT DISTINCT status, COUNT(*) as count FROM service_orders GROUP BY status");
echo "<h2>Database Status Counts:</h2>";
echo "<pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
?>
