<?php
// update_structure_submodel.php
require_once 'config/db.php';

try {
    // Add submodel column to equipments table
    $stmt = $pdo->query("SHOW COLUMNS FROM equipments LIKE 'submodel'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `equipments` ADD COLUMN `submodel` VARCHAR(100) DEFAULT NULL AFTER `model`");
        echo "Added column: submodel to equipments<br>";
    } else {
        echo "Column submodel already exists in equipments<br>";
    }

    echo "Update complete.";

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
