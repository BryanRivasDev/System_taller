<?php
require_once 'config/db.php';
try {
    echo "<h1>Tools Table Schema</h1>";
    $stmt = $pdo->query("DESCRIBE tools");
    echo "<pre>";
    print_r($stmt->fetchAll());
    echo "</pre>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
