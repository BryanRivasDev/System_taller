<?php
// update_structure_entry_doc.php
require_once 'config/db.php';

try {
    // 1. Create system_sequences table if it doesn't exist (it should, but safety first)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `system_sequences` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `code` varchar(50) NOT NULL UNIQUE,
      `current_value` int(11) NOT NULL DEFAULT 0,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. Insert entry_doc sequence
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM system_sequences WHERE code = 'entry_doc'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO system_sequences (code, current_value) VALUES ('entry_doc', 0)");
        echo "Created sequence: entry_doc<br>";
    }

    // 3. Alter service_orders table to add entry_doc_number
    $stmt = $pdo->query("SHOW COLUMNS FROM service_orders LIKE 'entry_doc_number'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `service_orders` ADD COLUMN `entry_doc_number` INT DEFAULT NULL AFTER `id`");
        echo "Added column: entry_doc_number<br>";
    }

    echo "Update complete.";

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
