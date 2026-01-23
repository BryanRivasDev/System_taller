<?php
// Script to add test equipment for multi-delivery testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Iniciando script de datos de prueba...\n\n";

try {
    require_once 'config/db.php';
    echo "✓ Conexión a base de datos establecida\n\n";
} catch (Exception $e) {
    die("Error conectando a la base de datos: " . $e->getMessage() . "\n");
}

// Get a client to use for testing
try {
    $stmt = $pdo->query("SELECT id, name FROM clients LIMIT 1");
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error obteniendo cliente: " . $e->getMessage() . "\n");
}

if (!$client) {
    die("No hay clientes en la base de datos. Por favor crea un cliente primero.\n");
}

echo "Usando cliente: {$client['name']} (ID: {$client['id']})\n\n";

// Create 3 test equipment entries for the same client
$equipmentData = [
    ['brand' => 'HP', 'model' => 'Pavilion 15', 'type' => 'Laptop', 'serial' => 'HP-TEST-001'],
    ['brand' => 'Dell', 'model' => 'Inspiron 14', 'type' => 'Laptop', 'serial' => 'DELL-TEST-002'],
    ['brand' => 'Lenovo', 'model' => 'ThinkPad X1', 'type' => 'Laptop', 'serial' => 'LEN-TEST-003'],
];

$createdOrders = [];

foreach ($equipmentData as $equip) {
    try {
        $pdo->beginTransaction();
        
        // Insert equipment
        $stmtEquip = $pdo->prepare("
            INSERT INTO equipments (client_id, brand, model, type, serial_number, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmtEquip->execute([
            $client['id'],
            $equip['brand'],
            $equip['model'],
            $equip['type'],
            $equip['serial']
        ]);
        $equipmentId = $pdo->lastInsertId();
        
        // Create service order in 'ready' status
        $stmtOrder = $pdo->prepare("
            INSERT INTO service_orders 
            (client_id, equipment_id, service_type, status, entry_date, problem_reported, diagnosis_notes, work_done, final_cost, created_at) 
            VALUES (?, ?, 'service', 'ready', NOW(), ?, ?, ?, 100.00, NOW())
        ");
        $stmtOrder->execute([
            $client['id'],
            $equipmentId,
            "Problema de prueba - {$equip['brand']} {$equip['model']}",
            "Diagnóstico de prueba: Requiere limpieza y actualización",
            "Trabajo realizado: Limpieza completa, actualización de sistema"
        ]);
        $orderId = $pdo->lastInsertId();
        
        // Add diagnosis number
        $diagNumber = 1000 + $orderId;
        $stmtUpdate = $pdo->prepare("UPDATE service_orders SET diagnosis_number = ? WHERE id = ?");
        $stmtUpdate->execute([$diagNumber, $orderId]);
        
        $pdo->commit();
        
        $createdOrders[] = [
            'order_id' => $orderId,
            'equipment' => "{$equip['brand']} {$equip['model']}",
            'serial' => $equip['serial']
        ];
        
        echo "✓ Creado: Orden #{$orderId} - {$equip['brand']} {$equip['model']} (Serial: {$equip['serial']})\n";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "✗ Error creando {$equip['brand']} {$equip['model']}: " . $e->getMessage() . "\n";
    }
}

echo "\n========================================\n";
echo "RESUMEN DE PRUEBA\n";
echo "========================================\n";
echo "Cliente: {$client['name']}\n";
echo "Total de equipos creados: " . count($createdOrders) . "\n";
echo "Estado: ready (listos para entrega)\n\n";

echo "Ahora puedes:\n";
echo "1. Ir a modules/equipment/exit.php\n";
echo "2. Ver la alerta verde de 'Entregas Múltiples Disponibles'\n";
echo "3. Hacer clic en la tarjeta del cliente para imprimir todos los equipos\n\n";

if (!empty($createdOrders)) {
    $orderIds = array_column($createdOrders, 'order_id');
    echo "IDs de órdenes creadas: " . implode(', ', $orderIds) . "\n";
    echo "URL de prueba: modules/equipment/print_delivery_multi.php?ids=" . implode(',', $orderIds) . "\n";
}
?>
