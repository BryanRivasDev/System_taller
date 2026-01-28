<?php
require_once 'config/db.php';

$code = 'module_re_enter_workshop';

echo "<h2>Checking Permission: $code</h2>";

// 1. Check if permission exists
$stmt = $pdo->prepare("SELECT * FROM permissions WHERE code = ?");
$stmt->execute([$code]);
$perm = $stmt->fetch(PDO::FETCH_ASSOC);

if ($perm) {
    echo "✅ Permission found: ID {$perm['id']}, Description: {$perm['description']}<br>";
    
    // 2. Check which roles have it
    $stmtRoles = $pdo->prepare("
        SELECT r.id, r.name 
        FROM role_permissions rp 
        JOIN roles r ON rp.role_id = r.id 
        WHERE rp.permission_id = ?
    ");
    $stmtRoles->execute([$perm['id']]);
    $roles = $stmtRoles->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Assigned Roles:</h3>";
    if ($roles) {
        foreach ($roles as $r) {
            echo "- ID {$r['id']}: {$r['name']}<br>";
        }
    } else {
        echo "❌ No roles assigned yet.<br>";
    }
    
} else {
    echo "❌ Permission NOT found in database.<br>";
    
    // Attempt to insert it if missing (Quick Fix)
    $stmtIns = $pdo->prepare("INSERT INTO permissions (code, description) VALUES (?, ?)");
    if ($stmtIns->execute([$code, 'Reingresar a Taller'])) {
        echo "✅ Permission created automatically. Please re-assign in settings.<br>";
    } else {
        echo "❌ Failed to create permission.<br>";
    }
}
?>
