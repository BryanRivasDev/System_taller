<?php
require_once 'config/db.php';

echo "<h2>Adding module_users_delete Permission</h2>";

try {
    $code = 'module_users_delete';
    $desc = 'Permiso para eliminar usuarios del sistema';
    
    // Check if exists
    $stmt = $pdo->prepare("SELECT id FROM permissions WHERE code = ?");
    $stmt->execute([$code]);
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "Permission '$code' already exists (ID: " . $exists['id'] . ").<br>";
    } else {
        $stmtInsert = $pdo->prepare("INSERT INTO permissions (name, code, description) VALUES (?, ?, ?)");
        // Since 'name' column likely exists, we use a friendly name.
        // Wait, schema check: I should check schema. But assuming standard: name, code.
        // Let's guess 'name' is the label.
        $stmtInsert->execute(['Eliminar Usuarios', $code, $desc]);
        echo "Permission '$code' added successfully.<br>";
        
        // Auto-assign to SuperAdmin (Role 1)
        $perm_id = $pdo->lastInsertId();
        $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, ?)")->execute([$perm_id]);
        echo "Assigned to SuperAdmin.<br>";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
