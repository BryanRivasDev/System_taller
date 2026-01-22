<?php
require_once 'config/db.php';

try {
    echo "Updating Schema for Diagnosis Modal...\n";

    // 1. Add Columns to service_orders
    $columns = ['diagnosis_procedure', 'diagnosis_conclusion'];
    foreach ($columns as $col) {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM service_orders LIKE ?");
        $stmt->execute([$col]);
        if (!$stmt->fetch()) {
            echo "Adding column $col...\n";
            $pdo->exec("ALTER TABLE service_orders ADD COLUMN $col TEXT DEFAULT NULL");
        } else {
            echo "Column $col already exists.\n";
        }
    }

    // 2. Create diagnosis_images table
    echo "Checking diagnosis_images table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS diagnosis_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            service_order_id INT NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (service_order_id) REFERENCES service_orders(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Table diagnosis_images ensured.\n";

    echo "Schema update completed successfully.\n";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage() . "\n");
}
?>
