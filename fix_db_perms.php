<?php
require_once 'config/db.php';
echo "<h2>Fixing Database Schema</h2>";

try {
    // 1. Delete duplicates, keeping the most recent one (highest ID)
    // Using a multi-table DELETE trick or temporary table approach. Given MySQL compatibility, we'll loop.
    
    echo "Deleting duplicates...<br>";
    
    // Find duplicates
    $stmt = $pdo->query("
        SELECT user_id, module_name, MAX(id) as max_id 
        FROM user_custom_modules 
        GROUP BY user_id, module_name 
        HAVING COUNT(*) > 1
    ");
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($groups as $group) {
        $uid = $group['user_id'];
        $mod = $group['module_name'];
        $maxKey = $group['max_id'];
        
        $pdo->prepare("DELETE FROM user_custom_modules WHERE user_id = ? AND module_name = ? AND id != ?")
            ->execute([$uid, $mod, $maxKey]);
            
        echo "Cleaned duplicates for User $uid / Module '$mod' (Kept ID $maxKey)<br>";
    }
    
    // 2. Add Unique Index
    echo "Adding UNIQUE Index...<br>";
    // Check if index exists first to avoid error, or just TRY catch it
    // Note: IF NOT EXISTS syntax for ADD UNIQUE is not standard in older MySQL, so we use TRY.
    
    $pdo->exec("ALTER TABLE user_custom_modules ADD UNIQUE KEY unique_user_module (user_id, module_name)");
    echo "UNIQUE KEY added successfully.<br>";
    
    echo "<h3>Success! Schema fixed.</h3>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Show current schema
echo "<h2>New Schema</h2>";
$stmt = $pdo->query("SHOW CREATE TABLE user_custom_modules");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<pre>" . print_r($row, true) . "</pre>";
?>
