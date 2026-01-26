<?php
require_once 'config/db.php';
try {
    $stmt = $pdo->query("DESCRIBE permissions");
    echo "<pre>";
    print_r($stmt->fetchAll());
    echo "</pre>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
