<?php
// Debug script to check client data
session_start();
require_once 'config/db.php';

echo "<h2>Debug: Verificando datos de clientes y órdenes</h2>";

// Check orders with client info
$stmt = $pdo->prepare("
    SELECT 
        so.id, 
        so.status,
        c.id as client_id,
        c.name as client_name, 
        e.brand, 
        e.model
    FROM service_orders so
    JOIN clients c ON so.client_id = c.id
    JOIN equipments e ON so.equipment_id = e.id
    WHERE so.status IN ('ready') 
    ORDER BY so.created_at DESC
    LIMIT 10
");
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Órdenes en estado 'ready':</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Order ID</th><th>Client ID</th><th>Client Name</th><th>Equipment</th></tr>";

foreach ($orders as $order) {
    echo "<tr>";
    echo "<td>{$order['id']}</td>";
    echo "<td>{$order['client_id']}</td>";
    echo "<td><strong>" . htmlspecialchars($order['client_name']) . "</strong></td>";
    echo "<td>{$order['brand']} {$order['model']}</td>";
    echo "</tr>";
}

echo "</table>";

// Group by client
$clientGroups = [];
foreach ($orders as $order) {
    $clientId = $order['client_id'];
    if (!isset($clientGroups[$clientId])) {
        $clientGroups[$clientId] = [];
    }
    $clientGroups[$clientId][] = $order['id'];
}

echo "<h3>Agrupación por cliente:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Client ID</th><th>Order IDs</th><th>Count</th></tr>";

foreach ($clientGroups as $clientId => $orderIds) {
    echo "<tr>";
    echo "<td>{$clientId}</td>";
    echo "<td>" . implode(', ', $orderIds) . "</td>";
    echo "<td>" . count($orderIds) . "</td>";
    echo "</tr>";
}

echo "</table>";

// Show multi-client groups
$multiClientGroups = array_filter($clientGroups, function($ids) { return count($ids) > 1; });

echo "<h3>Clientes con múltiples equipos:</h3>";
if (empty($multiClientGroups)) {
    echo "<p style='color: red;'>No hay clientes con múltiples equipos en estado 'ready'</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Client ID</th><th>Client Name</th><th>Equipment Count</th></tr>";
    
    foreach ($multiClientGroups as $clientId => $orderIds) {
        $clientName = 'NOT FOUND';
        foreach ($orders as $order) {
            if ($order['client_id'] == $clientId) {
                $clientName = $order['client_name'];
                break;
            }
        }
        
        echo "<tr>";
        echo "<td>{$clientId}</td>";
        echo "<td><strong style='font-size: 16px; color: blue;'>" . htmlspecialchars($clientName) . "</strong></td>";
        echo "<td>" . count($orderIds) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}
?>
