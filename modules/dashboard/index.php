<?php
// modules/dashboard/index.php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Check Dashboard Access
if (!can_access_module('dashboard', $pdo)) {
    die("Acceso denegado al Dashboard.");
}

$page_title = 'Dashboard';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

// --- ROLE CONTEXT ---
$role_id = $_SESSION['role_id'];
$user_id = $_SESSION['user_id'];

$is_admin = ($role_id == 1);
$is_reception = ($role_id == 4);
$is_tech = ($role_id == 3);
$is_warehouse = ($role_id == 2);

// KPI Variables Initialization
$kpi1_val = 0; $kpi1_label = ''; $kpi1_icon = ''; $kpi1_bg = ''; $kpi1_color = '';
$kpi2_val = 0; $kpi2_label = ''; $kpi2_icon = ''; $kpi2_bg = ''; $kpi2_color = '';
$kpi3_val = 0; $kpi3_label = ''; $kpi3_icon = ''; $kpi3_bg = ''; $kpi3_color = '';
$kpi4_val = 0; $kpi4_label = ''; $kpi4_icon = ''; $kpi4_bg = ''; $kpi4_color = '';

$chartLabels = [];
$chartCounts = [];
$weeklyLabels = [];
$weeklyCounts = [];
$recentItems = []; // Generic items for table
$recentType = 'services'; // 'services' or 'tools'

// --- DATA FETCHING LOGIC ---

if ($is_tech) {
    // --- TECHNICIAN VIEW ---
    
    // KPI 1: My Assignments
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM service_orders WHERE assigned_tech_id = ? AND status NOT IN ('delivered', 'cancelled')");
    $stmt->execute([$user_id]);
    $kpi1_val = $stmt->fetchColumn();
    $kpi1_label = "Mis Asignaciones";
    $kpi1_icon = "ph-user-focus";
    $kpi1_color = "var(--primary-500)";
    $kpi1_bg = "rgba(99, 102, 241, 0.1)";

    // KPI 2: Diagnosing
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM service_orders WHERE assigned_tech_id = ? AND status = 'diagnosing'");
    $stmt->execute([$user_id]);
    $kpi2_val = $stmt->fetchColumn();
    $kpi2_label = "En Diagnóstico";
    $kpi2_icon = "ph-stethoscope";
    $kpi2_color = "var(--warning)";
    $kpi2_bg = "rgba(234, 179, 8, 0.1)";

    // KPI 3: In Repair
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM service_orders WHERE assigned_tech_id = ? AND status = 'in_repair'");
    $stmt->execute([$user_id]);
    $kpi3_val = $stmt->fetchColumn();
    $kpi3_label = "En Reparación";
    $kpi3_icon = "ph-wrench";
    $kpi3_color = "var(--purple-500)";
    $kpi3_bg = "rgba(168, 85, 247, 0.1)";

    // KPI 4: Completed This Month
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM service_orders WHERE assigned_tech_id = ? AND status IN ('ready', 'delivered') AND DATE_FORMAT(entry_date, '%Y-%m') = ?");
    $stmt->execute([$user_id, date('Y-m')]);
    $kpi4_val = $stmt->fetchColumn();
    $kpi4_label = "Finalizados (Mes)";
    $kpi4_icon = "ph-check-square-offset";
    $kpi4_color = "var(--success)";
    $kpi4_bg = "rgba(34, 197, 94, 0.1)";

    // Chart: My Status Distribution
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM service_orders WHERE assigned_tech_id = ? AND status NOT IN ('delivered', 'cancelled') GROUP BY status");
    $stmt->execute([$user_id]);
    $statusData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Recent Activity: My Orders
    $recentSql = "
        SELECT so.id, so.entry_date, so.status, c.name as client_name, e.brand, e.model
        FROM service_orders so
        JOIN clients c ON so.client_id = c.id
        JOIN equipments e ON so.equipment_id = e.id
        WHERE so.assigned_tech_id = ?
        ORDER BY so.entry_date DESC LIMIT 5
    ";
    $stmt = $pdo->prepare($recentSql);
    $stmt->execute([$user_id]);
    $recentItems = $stmt->fetchAll();

} elseif ($is_reception) {
    // --- RECEPTION VIEW ---

    // KPI 1: Received Today
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM service_orders WHERE DATE(entry_date) = CURDATE()");
    $stmt->execute();
    $kpi1_val = $stmt->fetchColumn();
    $kpi1_label = "Recibidos Hoy";
    $kpi1_icon = "ph-box-arrow-down";
    $kpi1_color = "var(--primary-500)";
    $kpi1_bg = "rgba(99, 102, 241, 0.1)";

    // KPI 2: Ready for Pickup (To Call)
    $stmt = $pdo->query("SELECT COUNT(*) FROM service_orders WHERE status = 'ready'");
    $kpi2_val = $stmt->fetchColumn();
    $kpi2_label = "Listos para Entrega";
    $kpi2_icon = "ph-phone-outgoing";
    $kpi2_color = "var(--success)";
    $kpi2_bg = "rgba(34, 197, 94, 0.1)";

    // KPI 3: Pending Approval (To Call)
    $stmt = $pdo->query("SELECT COUNT(*) FROM service_orders WHERE status = 'pending_approval'");
    $kpi3_val = $stmt->fetchColumn();
    $kpi3_label = "Por Aprobar";
    $kpi3_icon = "ph-clock-alert";
    $kpi3_color = "var(--warning)";
    $kpi3_bg = "rgba(234, 179, 8, 0.1)";

    // KPI 4: Active Warranties
    $stmt = $pdo->query("SELECT COUNT(*) FROM warranties WHERE status = 'active'");
    $kpi4_val = $stmt->fetchColumn();
    $kpi4_label = "Garantías Activas";
    $kpi4_icon = "ph-shield-warning";
    $kpi4_color = "var(--danger)";
    $kpi4_bg = "rgba(239, 68, 68, 0.1)";

    // Chart: Global Status (Reception monitors flow)
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM service_orders WHERE status NOT IN ('delivered', 'cancelled') GROUP BY status");
    $statusData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Recent Activity: All Orders
    $stmt = $pdo->query("
        SELECT so.id, so.entry_date, so.status, c.name as client_name, e.brand, e.model
        FROM service_orders so
        JOIN clients c ON so.client_id = c.id
        JOIN equipments e ON so.equipment_id = e.id
        ORDER BY so.entry_date DESC LIMIT 5
    ");
    $recentItems = $stmt->fetchAll();

} elseif ($is_warehouse) {
    // --- WAREHOUSE VIEW ---
    $recentType = 'tools';

    // Check if tool_assignments table exists or similar
    // Assuming table 'tool_assignments' for loans based on 'tools' module inspection
    
    // KPI 1: Total Tools
    $stmt = $pdo->query("SELECT SUM(quantity) FROM tools"); // Or COUNT(*) for types? Let's use COUNT(*) types
    $kpi1_val = $pdo->query("SELECT COUNT(*) FROM tools")->fetchColumn();
    $kpi1_label = "Tipos de Herramientas";
    $kpi1_icon = "ph-toolbox";
    $kpi1_color = "var(--primary-500)";
    $kpi1_bg = "rgba(99, 102, 241, 0.1)";

    // KPI 2: Active Loans
    // We need to check if tool_assignments exists, handle error if not
    try {
        $kpi2_val = $pdo->query("SELECT COUNT(*) FROM tool_assignments WHERE status = 'active'")->fetchColumn();
    } catch (Exception $e) { $kpi2_val = 0; }
    $kpi2_label = "Préstamos Activos";
    $kpi2_icon = "ph-hand-giving";
    $kpi2_color = "var(--warning)";
    $kpi2_bg = "rgba(234, 179, 8, 0.1)";

    // KPI 3: Low Stock? Or Maintenance
    try {
        $kpi3_val = $pdo->query("SELECT COUNT(*) FROM tools WHERE status = 'maintenance'")->fetchColumn();
    } catch (Exception $e) { $kpi3_val = 0; }
    $kpi3_label = "En Mantenimiento";
    $kpi3_icon = "ph-wrench";
    $kpi3_color = "var(--danger)";
    $kpi3_bg = "rgba(239, 68, 68, 0.1)";
    
    // KPI 4: Total Products? If no products table, maybe Total Inventory Value or placeholder
    // Using Total Items (SUM quantity)
    $kpi4_val = (int)$pdo->query("SELECT SUM(quantity) FROM tools")->fetchColumn();
    $kpi4_label = "Stock Total (Unds)";
    $kpi4_icon = "ph-stack";
    $kpi4_color = "var(--success)";
    $kpi4_bg = "rgba(34, 197, 94, 0.1)";

    // Chart: Tool Status Distribution
    // Available vs Assigned vs Maintenance
    // We need to calculate 'Assigned' numbers if not stored in 'tools' status column
    // tools might have status column? Step 696 showed 'status' column in table headers.
    // Values: 'available', 'assigned', 'maintenance', 'lost'
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM tools GROUP BY status");
    $statusData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Translate Logic for Tools
    /*
        $statusLabels = [
            'available' => 'Disponible',
            'assigned' => 'Prestado',
            'maintenance' => 'Mantenimiento',
            'lost' => 'Perdido'
        ];
    */

    // Recent Activity: Tool Loans
    // Assume table tool_assignments
    try {
        $stmt = $pdo->query("
            SELECT ta.id, ta.assigned_at as date, ta.status, t.name as item_name, u.username as user_name
            FROM tool_assignments ta
            JOIN tools t ON ta.tool_id = t.id
            JOIN users u ON ta.user_id = u.id
            ORDER BY ta.assigned_at DESC LIMIT 5
        ");
        $recentItems = $stmt->fetchAll();
    } catch(Exception $e) {
        $recentItems = [];
    }

} else {
    // --- ADMIN / DEFAULT VIEW ---
    
    // KPI 1: Active Jobs (Total Breakdown)
    $stmtSrv = $pdo->query("SELECT COUNT(*) FROM service_orders WHERE status NOT IN ('delivered', 'cancelled') AND service_type = 'service'");
    $activeSrv = $stmtSrv->fetchColumn();
    
    // Match logic from warranties/index.php
    $stmtWar = $pdo->query("SELECT COUNT(*) 
                            FROM service_orders so 
                            LEFT JOIN warranties w ON so.id = w.service_order_id 
                            WHERE so.status NOT IN ('delivered', 'cancelled') 
                            AND so.service_type = 'warranty' 
                            AND (w.product_code IS NULL OR w.product_code = '')");
    $activeWar = $stmtWar->fetchColumn();

    $activeTotal = $activeSrv + $activeWar;

    $kpi1_val = $activeTotal;
    $kpi1_label = "Equipos en Taller <div style='font-size:0.75rem; font-weight:400; margin-top:4px; opacity:0.8; display:flex; align-items:center; gap:6px;'><span style='display:flex; align-items:center; gap:3px;'><i class='ph-bold ph-wrench'></i> $activeSrv</span> <span style='display:flex; align-items:center; gap:3px;'><i class='ph-bold ph-shield-check'></i> $activeWar</span></div>";
    $kpi1_icon = "ph-wrench";
    $kpi1_color = "var(--primary-500)";
    $kpi1_bg = "rgba(99, 102, 241, 0.1)";

    // KPI 2: Ready
    $stmt = $pdo->query("SELECT COUNT(*) FROM service_orders WHERE status = 'ready'");
    $kpi2_val = $stmt->fetchColumn();
    $kpi2_label = "Listos para Entrega";
    $kpi2_icon = "ph-check-circle";
    $kpi2_color = "var(--success)";
    $kpi2_bg = "rgba(34, 197, 94, 0.1)";

    // KPI 3: Deliveries Month (Total Breakdown)
    $currentMonth = date('Y-m');
    
    // Service Deliveries (Total History)
    $stmtDelSrv = $pdo->prepare("SELECT COUNT(*) FROM service_orders WHERE status = 'delivered' AND service_type = 'service'");
    $stmtDelSrv->execute();
    $delSrv = $stmtDelSrv->fetchColumn();
    
    // Warranty Deliveries (Total History - Precise Logic)
    $stmtDelWar = $pdo->prepare("SELECT COUNT(*) 
                                 FROM service_orders so 
                                 LEFT JOIN warranties w ON so.id = w.service_order_id 
                                 WHERE so.status = 'delivered' 
                                 AND so.service_type = 'warranty' 
                                 AND (w.product_code IS NULL OR w.product_code = '')");
    $stmtDelWar->execute();
    $delWar = $stmtDelWar->fetchColumn();
    
    // Total displayed is Sum of visible lists
    $delTotal = $delSrv + $delWar;

    $kpi3_val = $delTotal;
    $kpi3_label = "Total Entregados <div style='font-size:0.75rem; font-weight:400; margin-top:4px; opacity:0.8; display:flex; align-items:center; gap:6px;'><span style='display:flex; align-items:center; gap:3px;'><i class='ph-bold ph-wrench'></i> $delSrv</span> <span style='display:flex; align-items:center; gap:3px;'><i class='ph-bold ph-shield-check'></i> $delWar</span></div>";
    $kpi3_icon = "ph-calendar-plus";
    $kpi3_color = "var(--warning)";
    $kpi3_bg = "rgba(234, 179, 8, 0.1)";

    // KPI 4: Active Warranties
    $stmt = $pdo->query("SELECT COUNT(*) FROM warranties WHERE status = 'active'");
    $kpi4_val = $stmt->fetchColumn();
    $kpi4_label = "Garantías Activas";
    $kpi4_icon = "ph-shield-check";
    $kpi4_color = "var(--danger)";
    $kpi4_bg = "rgba(239, 68, 68, 0.1)";

    // Chart: Global Status
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM service_orders WHERE status NOT IN ('delivered', 'cancelled') GROUP BY status");
    $statusData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Recent Activity: All Orders
    $stmt = $pdo->query("
        SELECT so.id, so.entry_date, so.status, c.name as client_name, e.brand, e.model
        FROM service_orders so
        JOIN clients c ON so.client_id = c.id
        JOIN equipments e ON so.equipment_id = e.id
        ORDER BY so.entry_date DESC LIMIT 5
    ");
    $recentItems = $stmt->fetchAll();
}

// --- COMPILE CHART DATA (Universal Logic) ---

// Status Chart (Doughnut)
if($is_warehouse) {
    // Tool Status Mapping
    $labelsMap = [
        'available' => 'Disponible',
        'assigned' => 'Prestado',
        'maintenance' => 'Mantenimiento',
        'lost' => 'Perdido/Baja'
    ];
} else {
    // Service Status Mapping
    $labelsMap = [
        'received' => 'Recibido',
        'diagnosing' => 'Diagnóstico',
        'pending_approval' => 'En Espera',
        'in_repair' => 'Reparación',
        'ready' => 'Listo',
        'delivered' => 'Entregado',
        'cancelled' => 'Cancelado'
    ];
}

foreach ($labelsMap as $key => $label) {
    if (isset($statusData[$key])) {
        $chartLabels[] = $label;
        $chartCounts[] = $statusData[$key];
    }
}

// Weekly Activity (Last 7 Days)
// Only for Services (Admin, Tech, Reception)
// Warehouse doesn't need this graph or logic is different?
// Let's keep it for Services views. Warehouse can show Empty or hide.
if (!$is_warehouse) {
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        
        $wSql = "SELECT COUNT(*) FROM service_orders WHERE DATE(entry_date) = ?";
        if ($is_tech) {
            $wSql .= " AND assigned_tech_id = " . intval($user_id);
        }
        // Reception sees global intake
        
        $stmtDaily = $pdo->prepare($wSql);
        $stmtDaily->execute([$date]);
        $weeklyLabels[] = date('d/m', strtotime($date));
        $weeklyCounts[] = $stmtDaily->fetchColumn();
    }
}

?>

<!-- CHART.JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="animate-enter">
    <div style="margin-bottom: 2rem;">
        <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
        <p class="text-muted">
            <?php 
                if($is_reception) echo 'Gestión de clientes y recepción de equipos.';
                elseif($is_warehouse) echo 'Gestión de inventario y herramientas.';
                elseif($is_tech) echo 'Resumen de asignaciones y reparaciones.';
                else echo 'Resumen general de las operaciones del taller.';
            ?>
        </p>
    </div>

    <!-- KPIS GRID -->
    <div class="stats-grid">
        <!-- Cards -->
        <?php 
        $cards = [
            [$kpi1_val, $kpi1_label, $kpi1_icon, $kpi1_color, $kpi1_bg],
            [$kpi2_val, $kpi2_label, $kpi2_icon, $kpi2_color, $kpi2_bg],
            [$kpi3_val, $kpi3_label, $kpi3_icon, $kpi3_color, $kpi3_bg],
            [$kpi4_val, $kpi4_label, $kpi4_icon, $kpi4_color, $kpi4_bg]
        ];
        
        foreach($cards as $card): 
            list($val, $lbl, $icon, $col, $bg) = $card;
        ?>
        <div class="card stat-card">
            <div>
                <div class="stat-icon" style="background: <?php echo $bg; ?>; color: <?php echo $col; ?>;">
                    <i class="ph <?php echo $icon; ?>"></i>
                </div>
                <div class="stat-value"><?php echo $val; ?></div>
                <div class="stat-label"><?php echo $lbl; ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- CHARTS ROW -->
    <?php if (!$is_warehouse): ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
        <!-- Status Chart -->
        <div class="card" style="min-height: 400px;">
            <h3 class="mb-4">Estado de Reparaciones</h3>
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
        <!-- Weekly Chart -->
        <div class="card" style="min-height: 400px;">
            <h3 class="mb-4">Ingresos de la Semana</h3>
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="weeklyChart"></canvas>
            </div>
        </div>
    </div>
    <?php elseif ($is_warehouse): ?>
    <!-- Warehouse Charts Row (Single Chart?) -->
    <div style="margin-bottom: 2rem;">
        <div class="card" style="min-height: 400px; max-width: 600px; margin: 0 auto;">
            <h3 class="mb-4">Estado del Inventario</h3>
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="statusChart"></canvas> <!-- Reusing statusChart ID -->
            </div>
        </div>
    </div>
    <?php endif; ?>


    <!-- RECENT ACTIVITY & QUICK ACTIONS -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        
        <!-- Recent Table -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0;">
                    <?php echo $recentType == 'tools' ? 'Últimos Préstamos' : 'Servicios Recientes'; ?>
                </h3>
                <a href="<?php echo $recentType == 'tools' ? '../tools/assignments.php' : '../services/index.php'; ?>" class="btn btn-sm btn-secondary">Ver Todo</a>
            </div>
            
            <div class="table-container">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="padding: 0.75rem;">Fecha</th>
                            <?php if($recentType == 'tools'): ?>
                                <th style="padding: 0.75rem;">Usuario</th>
                                <th style="padding: 0.75rem;">Herramienta</th>
                                <th style="padding: 0.75rem;">Estado</th>
                            <?php else: ?>
                                <th style="padding: 0.75rem;">Cliente</th>
                                <th style="padding: 0.75rem;">Equipo</th>
                                <th style="padding: 0.75rem;">Estado</th>
                                <th style="padding: 0.75rem;"></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($recentItems) > 0): ?>
                            <?php foreach($recentItems as $item): ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 0.75rem;">
                                    <?php 
                                        $d = $recentType == 'tools' ? $item['date'] : $item['entry_date'];
                                        echo date('d/m', strtotime($d)); 
                                    ?>
                                </td>
                                
                                <?php if($recentType == 'tools'): ?>
                                    <td style="padding: 0.75rem;"><?php echo htmlspecialchars($item['user_name']); ?></td>
                                    <td style="padding: 0.75rem;"><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td style="padding: 0.75rem;">
                                        <span class="badge"><?php echo ucfirst($item['status']); ?></span>
                                    </td>
                                <?php else: ?>
                                    <td style="padding: 0.75rem;"><?php echo htmlspecialchars($item['client_name']); ?></td>
                                    <td style="padding: 0.75rem;">
                                        <span class="text-sm text-muted"><?php echo htmlspecialchars($item['brand'] . ' ' . $item['model']); ?></span>
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <?php 
                                            $s = $item['status'];
                                            $col = 'gray'; // default
                                            if ($s == 'received') $col = 'blue';
                                            if ($s == 'diagnosing') $col = 'yellow';
                                            if ($s == 'in_repair') $col = 'purple';
                                            if ($s == 'ready') $col = 'green';
                                            if ($s == 'delivered') $col = 'gray';
                                        ?>
                                        <span class="status-badge status-<?php echo $col; ?>"><?php echo ucfirst($s); ?></span>
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <a href="../services/view.php?id=<?php echo $item['id']; ?>" class="btn-icon">
                                            <i class="ph ph-caret-right"></i>
                                        </a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                             <tr>
                                <td colspan="5" class="text-center" style="padding: 2rem; color: var(--text-secondary);">
                                    Sin actividad reciente.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <h3 class="mb-4">Accesos Rápidos</h3>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                
                <?php if(!$is_tech && !$is_warehouse): ?>
                <a href="../services/add.php" class="btn btn-secondary w-full" style="justify-content: flex-start; padding: 1rem;">
                    <div style="background: rgba(99, 102, 241, 0.2); padding: 8px; border-radius: 8px; margin-right: 0.5rem;">
                        <i class="ph ph-plus" style="color: var(--primary-500);"></i>
                    </div>
                    <div>
                        <div style="font-weight: 600;">Nuevo Servicio</div>
                        <div class="text-xs text-muted">Registrar entrada</div>
                    </div>
                </a>
                <a href="../clients/add.php" class="btn btn-secondary w-full" style="justify-content: flex-start; padding: 1rem;">
                     <div style="background: rgba(34, 197, 94, 0.2); padding: 8px; border-radius: 8px; margin-right: 0.5rem;">
                        <i class="ph ph-user-plus" style="color: var(--success);"></i>
                    </div>
                    <div>
                        <div style="font-weight: 600;">Nuevo Cliente</div>
                        <div class="text-xs text-muted">Agregar cliente</div>
                    </div>
                </a>
                <?php endif; ?>
                
                <?php if($is_warehouse): ?>
                <a href="../tools/add.php" class="btn btn-secondary w-full" style="justify-content: flex-start; padding: 1rem;">
                    <div style="background: rgba(99, 102, 241, 0.2); padding: 8px; border-radius: 8px; margin-right: 0.5rem;">
                        <i class="ph ph-plus" style="color: var(--primary-500);"></i>
                    </div>
                    <div>
                        <div style="font-weight: 600;">Nueva Herramienta</div>
                        <div class="text-xs text-muted">Registrar item</div>
                    </div>
                </a>
                <a href="../tools/assign.php" class="btn btn-secondary w-full" style="justify-content: flex-start; padding: 1rem;">
                     <div style="background: rgba(234, 179, 8, 0.2); padding: 8px; border-radius: 8px; margin-right: 0.5rem;">
                        <i class="ph ph-hand-giving" style="color: var(--warning);"></i>
                    </div>
                    <div>
                        <div style="font-weight: 600;">Asignar / Prestar</div>
                        <div class="text-xs text-muted">Registrar salida</div>
                    </div>
                </a>
                <?php endif; ?>

                <?php if(!$is_warehouse): ?>
                <a href="../services/index.php" class="btn btn-secondary w-full" style="justify-content: flex-start; padding: 1rem;">
                     <div style="background: rgba(234, 179, 8, 0.2); padding: 8px; border-radius: 8px; margin-right: 0.5rem;">
                        <i class="ph ph-list-checks" style="color: var(--warning);"></i>
                    </div>
                    <div>
                        <div style="font-weight: 600;"><?php echo $is_tech ? 'Mis Asignaciones' : 'Lista de Servicios'; ?></div>
                        <div class="text-xs text-muted">Ver órdenes</div>
                    </div>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Theme Colors
    const isLight = document.body.classList.contains('light-mode');
    const textColor = isLight ? '#475569' : '#cbd5e1';
    const gridColor = isLight ? '#e2e8f0' : 'rgba(255, 255, 255, 0.1)';

    // Status Chart
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($chartLabels); ?>,
            datasets: [{
                data: <?php echo json_encode($chartCounts); ?>,
                backgroundColor: [
                    '#3b82f6', // Blue
                    '#eab308', // Yellow
                    '#f97316', // Orange
                    '#a855f7', // Purple
                    '#22c55e', // Green
                    '#ef4444'  // Red
                ],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: { color: textColor }
                }
            },
            cutout: '70%'
        }
    });

    <?php if(!$is_warehouse): ?>
    // Weekly Chart
    const ctxWeekly = document.getElementById('weeklyChart').getContext('2d');
    new Chart(ctxWeekly, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($weeklyLabels); ?>,
            datasets: [{
                label: '<?php echo $is_tech ? "Asignaciones" : "Equipos Recibidos"; ?>',
                data: <?php echo json_encode($weeklyCounts); ?>,
                backgroundColor: '#6366f1',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor },
                    ticks: { color: textColor, precision: 0 }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: textColor }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
    <?php endif; ?>
</script>

<?php
require_once '../../includes/footer.php';
?>
