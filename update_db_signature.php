<?php
require_once 'config/db.php';

try {
    // Check if column exists
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'signature_path'");
    $stmt->execute();
    if ($stmt->fetch()) {
        echo "Column 'signature_path' already exists.\n";
    } else {
        $pdo->exec("ALTER TABLE users ADD COLUMN signature_path VARCHAR(255) DEFAULT NULL");
        echo "Column 'signature_path' added successfully.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
