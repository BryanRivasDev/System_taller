<?php
require_once 'config/db.php';

echo "<h2>Adding 'tools' Permission</h2>";

try {
    $code = 'tools';
    $desc = 'Acceso al módulo de herramientas';
    
    // Check if exists
    $stmt = $pdo->prepare("SELECT id FROM permissions WHERE code = ?");
    $stmt->execute([$code]);
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "Permission '$code' already exists (ID: " . $exists['id'] . ").<br>";
        $perm_id = $exists['id'];
    } else {
        // FIXED: Removed 'name' column
        $stmtInsert = $pdo->prepare("INSERT INTO permissions (code, description) VALUES (?, ?)");
        $stmtInsert->execute([$code, $desc]);
        $perm_id = $pdo->lastInsertId();
        echo "Permission '$code' added successfully.<br>";
    }

    // Auto-assign to SuperAdmin (Role 1)
    $stmt = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, ?)");
    $stmt->execute([$perm_id]);
    echo "Assigned to SuperAdmin (Role 1).<br>";

    // Also assign to any other role that might need it? 
    // For now just Admin.

    echo "<br><a href='modules/tools/index.php'>Go to Tools Module</a>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
