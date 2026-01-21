<?php
// update_structure_v3.php
require_once 'config/db.php';

try {
    $pdo->beginTransaction();

    // 1. Create system_sequences table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `system_sequences` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `code` varchar(50) NOT NULL UNIQUE,
      `current_value` int(11) NOT NULL DEFAULT 0,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. Insert default sequences if they don't exist
    $sequences = ['diagnosis', 'repair', 'exit_doc'];
    foreach ($sequences as $seq) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM system_sequences WHERE code = ?");
        $stmt->execute([$seq]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $pdo->prepare("INSERT INTO system_sequences (code, current_value) VALUES (?, 0)");
            $stmt->execute([$seq]);
            echo "Created sequence: $seq<br>";
        }
    }

    // 3. Alter service_orders table to add new columns
    // check if columns exist first to avoid error
    $columns = [
        'diagnosis_number' => 'ADD COLUMN `diagnosis_number` INT DEFAULT NULL AFTER `id`',
        'repair_number' => 'ADD COLUMN `repair_number` INT DEFAULT NULL AFTER `diagnosis_number`',
        'exit_doc_number' => 'ADD COLUMN `exit_doc_number` INT DEFAULT NULL AFTER `repair_number`'
    ];

    $stmt = $pdo->query("SHOW COLUMNS FROM service_orders");
    $existing_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($columns as $col => $sql) {
        if (!in_array($col, $existing_columns)) {
            $pdo->exec("ALTER TABLE `service_orders` $sql");
            echo "Added column: $col<br>";
        }
    }

    $pdo->commit();
    echo "Database structure updated successfully.";

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error updating database: " . $e->getMessage());
}
?>
