<?php
// install_tools.php
require_once 'config/db.php';

echo "<h1>Instalación del Módulo de Herramientas</h1>";

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Disable Foreign Key Checks to allow dropping tables freely
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Forces clean install
    echo "Cleaning up old tables... ";
    $pdo->exec("DROP TABLE IF EXISTS tool_assignment_items");
    $pdo->exec("DROP TABLE IF EXISTS tool_assignments");
    $pdo->exec("DROP TABLE IF EXISTS tools");
    echo "<span style='color:green'>Done</span><br>";

    // Re-enable Foreign Key Checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // 1. Create tools table
    echo "Creating 'tools' table... ";
    $sql_tools = "
    CREATE TABLE tools (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        quantity INT NOT NULL DEFAULT 1,
        status ENUM('available', 'assigned', 'maintenance', 'lost') DEFAULT 'available',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql_tools);
    echo "<span style='color:green'>OK</span><br>";

    // 2. Create tool_assignments table
    echo "Creating 'tool_assignments' table... ";
    $sql_assign = "
    CREATE TABLE tool_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_name VARCHAR(255) NOT NULL,
        assigned_to VARCHAR(255) NOT NULL,
        technician_1 VARCHAR(255),
        technician_2 VARCHAR(255),
        technician_3 VARCHAR(255),
        delivery_date DATE NOT NULL,
        return_date DATE,
        observations TEXT,
        status ENUM('pending', 'delivered', 'returned') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql_assign);
    echo "<span style='color:green'>OK</span><br>";

    // 3. Create tool_assignment_items table
    echo "Creating 'tool_assignment_items' table... ";
    $sql_items = "
    CREATE TABLE tool_assignment_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        assignment_id INT NOT NULL,
        tool_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        status ENUM('pending', 'delivered', 'returned') DEFAULT 'pending',
        delivery_confirmed BOOLEAN DEFAULT FALSE,
        return_confirmed BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (assignment_id) REFERENCES tool_assignments(id) ON DELETE CASCADE,
        FOREIGN KEY (tool_id) REFERENCES tools(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql_items);
    echo "<span style='color:green'>OK</span><br>";

    // 4. Checking/Inserting Permission
    echo "Checking permissions... ";
    $stmt = $pdo->prepare("SELECT id FROM permissions WHERE code = 'tools'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $stmtInsert = $pdo->prepare("INSERT INTO permissions (code, description) VALUES (?, ?)");
        $stmtInsert->execute(['tools', 'Acceso al módulo de herramientas']);
        $perm_id = $pdo->lastInsertId();
        // Role 1 = Admin typically
        $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, ?)")->execute([$perm_id]);
        echo "<span style='color:green'>Created and Assigned to Admin</span><br>";
    } else {
        echo "<span style='color:blue'>Already exists</span><br>";
    }

    // 5. Populate Data
    echo "Populating sample tools... ";
    $sql_data = "INSERT INTO tools (name, description, quantity, status) VALUES
    ('Cargador de Baterías', 'Cargador de baterías para herramientas', 2, 'available'),
    ('Crimpiadora', 'Herramienta para crimpar', 1, 'available'),
    ('Estuche Milwake', 'Estuche de herramientas Milwake', 3, 'available'),
    ('Ponchadora de Impacto', 'Ponchadora de impacto', 1, 'available'),
    ('Generador de Tono', 'Generador de tono para cableado', 2, 'available'),
    ('Tester de Red', 'Tester de red', 2, 'available'),
    ('Desforradora', 'Desforrador de cables', 3, 'available'),
    ('Navaja Linera', 'Navaja de línea', 5, 'available'),
    ('Tenaza Picuda', 'Tenaza picuda', 4, 'available'),
    ('Alicate', 'Alicate multiuso', 5, 'available'),
    ('Tenaza Corte Diagonal', 'Tenaza de corte diagonal', 3, 'available'),
    ('Cola de Zorro', 'Sierra cola de zorro', 2, 'available'),
    ('Desarmador de Estrella', 'Desarmador de estrella', 6, 'available'),
    ('Desarmador de Ranura', 'Desarmador de ranura', 6, 'available'),
    ('Etiquetadora Brother', 'Etiquetadora Brother', 1, 'available'),
    ('Taladro Alámbrico', 'Taladro alámbrico', 2, 'available'),
    ('Taladro Inalámbrico', 'Taladro inalámbrico', 2, 'available'),
    ('Conos de Seguridad', 'Conos de seguridad', 4, 'available'),
    ('Laptop con su Cargador', 'Laptop con cargador incluido', 3, 'available'),
    ('Escalera de 6 pies', 'Escalera de 6 pies', 2, 'available'),
    ('Escalera de 8 pies', 'Escalera de 8 pies', 1, 'available'),
    ('Sacabocados', 'Sacabocados', 2, 'available'),
    ('Soplete', 'Soplete', 1, 'available'),
    ('Extensión Eléctrica', 'Extensión eléctrica', 4, 'available'),
    ('Extensión UPS', 'Extensión UPS', 2, 'available'),
    ('Multímetro', 'Multímetro digital', 3, 'available'),
    ('Llave perra', 'Llave perra ajustable', 4, 'available'),
    ('Martillo', 'Martillo', 3, 'available');";
    $pdo->exec($sql_data);
    echo "<span style='color:green'>Inserted sample data</span><br>";

    echo "<h3>¡Instalación Completada!</h3>";
    echo "<a href='modules/tools/index.php'>Ir al Módulo de Herramientas</a>";

} catch (PDOException $e) {
    echo "<h3 style='color:red'>Error: " . $e->getMessage() . "</h3>";
}
?>
