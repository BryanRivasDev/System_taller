<?php
require_once 'config/db.php';

// Mock Session for Admin
$_SESSION['role_id'] = 1;
$_SESSION['user_id'] = 1;

echo "<h1>Query Comparison</h1>";

// 1. Dashboard Query (Admin/Reception)
echo "<h2>Dashboard Query (Recent Activity)</h2>";
$dashSql = "
    SELECT so.id, so.entry_date, so.status, so.service_type, c.name as client_name, e.brand, e.model
    FROM service_orders so
    JOIN clients c ON so.client_id = c.id
    JOIN equipments e ON so.equipment_id = e.id
    ORDER BY so.entry_date DESC LIMIT 10
";
$stmt = $pdo->query($dashSql);
$dashItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'><tr><th>ID</th><th>Date</th><th>Type</th><th>Client</th></tr>";
foreach ($dashItems as $i) {
    echo "<tr><td>{$i['id']}</td><td>{$i['entry_date']}</td><td>{$i['service_type']}</td><td>{$i['client_name']}</td></tr>";
}
echo "</table>";

// 2. Warranties Query (from warranties/index.php)
echo "<h2>Warranties Module Query</h2>";
$warSql = "
    SELECT 
        so.id, so.status, so.entry_date
    FROM service_orders so
    LEFT JOIN warranties w ON so.id = w.service_order_id
    WHERE so.service_type = 'warranty'
      AND (w.product_code IS NULL OR w.product_code = '')
    ORDER BY so.entry_date DESC LIMIT 10
";
$stmt = $pdo->query($warSql);
$warItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'><tr><th>ID</th><th>Date</th><th>Status</th></tr>";
foreach ($warItems as $i) {
    echo "<tr><td>{$i['id']}</td><td>{$i['entry_date']}</td><td>{$i['status']}</td></tr>";
}
echo "</table>";
?>
