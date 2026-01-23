<?php
// modules/reports/index.php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Check auth
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../modules/auth/login.php");
    exit;
}

// Fetch all service orders with relevant details
$sql = "
    SELECT 
        so.id, 
        so.service_type,
        TRIM(so.status) as status, 
        so.entry_date, 
        so.diagnosis_number,
        c.id as client_id,
        c.name as client_name, 
        c.phone as client_phone,
        e.brand, 
        e.model, 
        e.type as equipment_type,
        u.username as tech_name
    FROM service_orders so
    LEFT JOIN clients c ON so.client_id = c.id
    LEFT JOIN equipments e ON so.equipment_id = e.id
    LEFT JOIN users u ON so.assigned_tech_id = u.id
    WHERE so.status IN ('delivered', 'diagnosing', 'ready', 'pending_approval', 'in_repair')
    ORDER BY so.entry_date DESC
";

try {
    $stmt = $pdo->query($sql);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al cargar los datos: " . $e->getMessage());
}

// Group orders by client for multi-equipment delivery detection
$clientGroups = [];
foreach ($orders as $order) {
    $clientId = $order['client_id'];
    if (!isset($clientGroups[$clientId])) {
        $clientGroups[$clientId] = [];
    }
    // Only include ready/delivered orders for multi-print
    if (in_array($order['status'], ['ready', 'delivered'])) {
        $clientGroups[$clientId][] = $order['id'];
    }
}

$page_title = 'Reportes e Impresiones';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="main-content" style="padding: 2.5rem;">
    <div class="page-header" style="margin-bottom: 2.5rem;">
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
            <div style="width: 48px; height: 48px; background: rgba(var(--primary-rgb), 0.1); border-radius: 14px; display: flex; align-items: center; justify-content: center;">
                <i class="ph-fill ph-chart-bar" style="font-size: 24px; color: var(--primary);"></i>
            </div>
            <div>
                <h1 class="page-title" style="font-size: 1.8rem; font-weight: 700; margin: 0; letter-spacing: -0.5px;">Centro de Reportes</h1>
            </div>
        </div>
        <p class="text-muted" style="margin: 0; padding-left: 4rem; opacity: 0.8;">Gestión centralizada de documentación e impresión de servicios.</p>
    </div>

    <!-- Filters & Search -->
    <div class="premium-filter-bar">
        <div class="search-input-wrapper">
            <i class="ph ph-magnifying-glass search-icon"></i>
            <input type="text" id="searchInput" class="premium-input search-box" placeholder="Buscar por cliente, equipo, ID...">
        </div>
        
        <div class="select-wrapper">
            <select id="statusFilter" class="premium-select">
                <option value="all">Todos los Estados</option>
                <option value="diagnosed">Solo Diagnosticados</option>
                <option value="delivered">Solo Entregados</option>
            </select>
            <i class="ph ph-caret-down select-caret"></i>
        </div>

        <div class="select-wrapper">
             <select id="typeFilter" class="premium-select">
                <option value="all">Todos los Tipos</option>
                <option value="service">Servicio Técnico</option>
                <option value="warranty">Garantía</option>
            </select>
            <i class="ph ph-caret-down select-caret"></i>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table" id="reportsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Equipo</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th># Diagnóstico</th>
                            <th>Técnico</th>
                            <th class="text-end">Imprimir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr data-status="<?php echo strtolower($order['status']); ?>" data-type="<?php echo strtolower($order['service_type']); ?>">
                                <td><span class="badge-tag">#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></span></td>
                                <td><?php echo date('d/m/Y', strtotime($order['entry_date'])); ?></td>
                                <td>
                                    <div class="fw-medium"><?php echo htmlspecialchars($order['client_name']); ?></div>
                                    <div class="text-xs text-muted"><?php echo htmlspecialchars($order['client_phone']); ?></div>
                                </td>
                                <td>
                                    <div class="fw-medium"><?php echo htmlspecialchars($order['brand'] . ' ' . $order['model']); ?></div>
                                    <div class="text-xs text-muted"><?php echo htmlspecialchars($order['equipment_type']); ?></div>
                                </td>
                                <td>
                                    <?php if ($order['service_type'] == 'warranty'): ?>
                                        <span class="badge badge-warning">Garantía</span>
                                    <?php else: ?>
                                        <span class="badge badge-blue">Servicio</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $statusLabels = [
                                        'pending' => ['Pendiente', 'warning'],
                                        'received' => ['Recibido', 'warning'],
                                        'diagnosing' => ['Diagnosticado', 'info'],
                                        'pending_approval' => ['En Espera', 'orange'],
                                        'approved' => ['Aprobado', 'primary'],
                                        'in_repair' => ['En Proceso', 'purple'],
                                        'ready' => ['Listo', 'success'],
                                        'delivered' => ['Entregado', 'secondary'],
                                    ];
                                    $st = $statusLabels[$order['status']] ?? [$order['status'], 'secondary'];
                                    ?>
                                    <span class="status-badge status-<?php echo $st[1]; ?>">
                                        <?php echo $st[0]; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($order['diagnosis_number'])): ?>
                                        <span class="badge" style="background: rgba(168, 85, 247, 0.1); color: #a855f7; border: 1px solid rgba(168, 85, 247, 0.2); padding: 4px 10px; border-radius: 6px; font-family: 'JetBrains Mono', monospace; font-size: 0.85rem;">
                                            #<?php echo str_pad($order['diagnosis_number'], 5, '0', STR_PAD_LEFT); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $order['tech_name'] ? htmlspecialchars($order['tech_name']) : '<span class="text-muted">-</span>'; ?></td>
                                <td class="text-end" style="padding-right: 1.5rem;">
                                    <div class="report-dropdown">
                                        <button class="premium-action-btn" onclick="toggleReportDropdown(this)">
                                            <i class="ph-bold ph-printer"></i>
                                        </button>
                                        <div class="report-dropdown-menu">
                                            <div class="dropdown-header">DOCUMENTOS</div>
                                            <a href="../equipment/print_entry.php?id=<?php echo $order['id']; ?>" class="dropdown-item">
                                                <i class="ph-fill ph-file-text" style="color: var(--primary);"></i> Hoja de Entrada
                                            </a>
                                            
                                            <?php if (!empty($order['diagnosis_number'])): ?>
                                            <a href="../services/print_diagnosis.php?id=<?php echo $order['id']; ?>" class="dropdown-item">
                                                <i class="ph-fill ph-stethoscope" style="color: #a855f7;"></i> Diagnóstico
                                            </a>
                                            <?php endif; ?>

                                            <?php if (in_array($order['status'], ['repaired', 'delivered', 'ready'])): ?>
                                            <a href="../equipment/print_delivery.php?id=<?php echo $order['id']; ?>" class="dropdown-item">
                                                <i class="ph-fill ph-check-circle" style="color: #10b981;"></i> Hoja de Salida
                                            </a>
                                            <?php endif; ?>

                                            <?php 
                                            // Show "Print All" if client has multiple ready/delivered equipment
                                            $clientId = $order['client_id'];
                                            if (isset($clientGroups[$clientId]) && count($clientGroups[$clientId]) > 1): 
                                                $allIds = implode(',', $clientGroups[$clientId]);
                                            ?>
                                            <div style="border-top: 1px solid var(--border-color); margin: 0.5rem 0;"></div>
                                            <a href="../equipment/print_delivery_multi.php?ids=<?php echo $allIds; ?>" class="dropdown-item" style="background: rgba(16, 185, 129, 0.05);">
                                                <i class="ph-fill ph-stack" style="color: #10b981;"></i> Imprimir Todos (<?php echo count($clientGroups[$clientId]); ?>)
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">No se encontraron registros.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
/* PREMIUM UI SYSTEM */
.premium-filter-bar {
    display: grid;
    grid-template-columns: 1fr 240px 240px;
    gap: 1.5rem;
    background: rgba(var(--bg-card-rgb), 0.4);
    backdrop-filter: blur(10px);
    border: 1px solid var(--border-color);
    padding: 1.25rem;
    border-radius: 18px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.search-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.search-icon {
    position: absolute;
    left: 1.25rem;
    color: var(--text-muted);
    font-size: 1.2rem;
    pointer-events: none;
}

.premium-input {
    width: 100%;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 0.85rem 1rem 0.85rem 3.5rem;
    color: var(--text-main);
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.premium-input:focus {
    background: rgba(var(--primary-rgb), 0.05);
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(var(--primary-rgb), 0.1);
    outline: none;
}

.select-wrapper {
    position: relative;
}

.premium-select {
    width: 100%;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 0.85rem 1.25rem;
    color: var(--text-main);
    font-size: 0.95rem;
    appearance: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.premium-select:focus {
    border-color: var(--primary);
    outline: none;
}

.premium-select option {
    background-color: #1a1c23; /* Dark background for options */
    color: white; /* White text for options */
}

.select-caret {
    position: absolute;
    right: 1.25rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    pointer-events: none;
}

/* Table Enhancements */
.table-responsive {
    border-radius: 20px;
    overflow: hidden;
    border: 1px solid var(--border-color);
    background: rgba(var(--bg-card-rgb), 0.2);
}

.table thead th {
    background: rgba(255,255,255,0.02);
    padding: 1.25rem 1rem;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    border-bottom: 2px solid var(--border-color);
}

.badge-tag {
    background: rgba(var(--primary-rgb), 0.1);
    color: var(--primary-light);
    border: 1px solid rgba(var(--primary-rgb), 0.2);
    padding: 4px 10px;
    border-radius: 6px;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.85rem;
}

/* Action Dropdown Modern */
.premium-action-btn {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--border-color);
    color: var(--text-main);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.premium-action-btn:hover {
    background: var(--primary);
    color: white;
    transform: scale(1.1);
    box-shadow: 0 8px 15px rgba(var(--primary-rgb), 0.25);
    border-color: var(--primary);
}

.report-dropdown {
    position: relative;
    display: inline-block;
}

.report-dropdown-menu {
    display: none;
    position: absolute;
    right: 0;
    top: calc(100% + 10px);
    background: rgba(var(--bg-card-rgb), 0.8);
    backdrop-filter: blur(20px);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.5);
    min-width: 240px;
    z-index: 1000;
    padding: 0.75rem;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.report-dropdown.active .report-dropdown-menu {
    display: block;
}

.dropdown-header {
    font-size: 0.65rem;
    font-weight: 800;
    color: var(--text-muted);
    padding: 0.5rem 1rem;
    letter-spacing: 1.5px;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    color: var(--text-main);
    text-decoration: none;
    border-radius: 10px;
    transition: all 0.2s ease;
    font-size: 0.95rem;
    font-weight: 500;
}

.dropdown-item:hover {
    background: rgba(255, 255, 255, 0.05);
    transform: translateX(5px);
}

.dropdown-item i {
    font-size: 1.2rem;
}
</style>

<script>
// Search and Filtering
const searchInput = document.getElementById('searchInput');
const statusFilter = document.getElementById('statusFilter');
const typeFilter = document.getElementById('typeFilter');
const tableRows = document.querySelectorAll('#reportsTable tbody tr');

function filterTable() {
    const searchTerm = searchInput.value.toLowerCase();
    const statusValue = statusFilter.value.toLowerCase();
    const typeValue = typeFilter.value.toLowerCase();

    tableRows.forEach(row => {
        if(row.cells.length < 2) return;

        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value;
        const typeValue = typeFilter.value;

        const rowText = row.innerText.toLowerCase();
        const rowStatus = row.getAttribute('data-status');
        const rowType = row.getAttribute('data-type');

        const matchesSearch = rowText.includes(searchTerm);
        const matchesType = typeValue === 'all' || rowType === typeValue;
        
        // Logical filter: 'diagnosed' covers everything not 'delivered'
        let matchesStatus = false;
        if (statusValue === 'all') {
            matchesStatus = true;
        } else if (statusValue === 'delivered') {
            matchesStatus = (rowStatus === 'delivered');
        } else if (statusValue === 'diagnosed') {
            matchesStatus = (rowStatus !== 'delivered');
        }

        if (matchesSearch && matchesStatus && matchesType) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

searchInput.addEventListener('keyup', filterTable);
statusFilter.addEventListener('change', filterTable);
typeFilter.addEventListener('change', filterTable);

// Dropdown Handler
function toggleReportDropdown(btn) {
    // Close all other dropdowns
    document.querySelectorAll('.report-dropdown.active').forEach(d => {
        if (d !== btn.parentElement) d.classList.remove('active');
    });
    
    btn.parentElement.classList.toggle('active');
    event.stopPropagation();
}

// Close dropdowns on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('.report-dropdown')) {
        document.querySelectorAll('.report-dropdown.active').forEach(d => d.classList.remove('active'));
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>
