<?php
// includes/sidebar.php (Now actually a Navbar)
?>
<header class="navbar">
    <!-- Brand -->
    <div class="navbar-brand">
        <div class="brand-logo-small">
            <i class="ph-bold ph-wrench"></i>
        </div>
        <div>
            <h3 style="margin:0; font-size: 1.1rem;">System<span style="color: var(--primary-500);">Taller</span></h3>
        </div>
    </div>
    

    <!-- Menu -->
    <nav class="navbar-menu">
        <?php if(can_access_module('dashboard', $pdo)): ?>
        <a href="/System_Taller/modules/dashboard/index.php" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false ? 'active' : ''; ?>">
            <i class="ph ph-squares-four"></i> Dashboard
        </a>
        <?php endif; ?>
        
        <?php if(can_access_module('clients', $pdo)): ?>
        <a href="/System_Taller/modules/clients/index.php" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'clients') !== false ? 'active' : ''; ?>">
            <i class="ph ph-users"></i> Clientes
        </a>
        <?php endif; ?>
        
        <?php if(can_access_module('equipment', $pdo)): ?>
        <div class="dropdown">
            <a href="#" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'equipment') !== false && strpos($_SERVER['REQUEST_URI'], 'type=warranty') === false) ? 'active' : ''; ?>">
                <i class="ph ph-desktop"></i> Equipos <i class="ph-bold ph-caret-down" style="font-size: 0.8rem;"></i>
            </a>
            <div class="dropdown-content">
                <a href="/System_Taller/modules/equipment/entry.php" class="dropdown-item">
                    <i class="ph ph-arrow-right-in"></i> Entrada
                </a>
                <a href="/System_Taller/modules/equipment/exit.php" class="dropdown-item">
                    <i class="ph ph-arrow-left-out"></i> Salida
                </a>

            </div>
        </div>
        <?php endif; ?>
        
        <?php if(can_access_module('new_warranty', $pdo)): ?>
        <a href="/System_Taller/modules/equipment/entry.php?type=warranty" class="nav-link <?php echo ((strpos($_SERVER['REQUEST_URI'], 'entry.php') !== false && isset($_GET['type']) && $_GET['type'] === 'warranty') || (isset($_GET['return_to']) && $_GET['return_to'] === 'entry')) ? 'active' : ''; ?>">
            <i class="ph ph-plus-circle"></i> Registro de Garantía
        </a>
        <?php endif; ?>


        <?php if(can_access_module('tools', $pdo)): ?>
        <a href="/System_Taller/modules/tools/index.php" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'tools') !== false ? 'active' : ''; ?>">
            <i class="ph ph-wrench"></i> Herramientas
        </a>
        <?php endif; ?>

        <!-- Solicitud Dropdown (Services & Warranties) -->
        <?php 
            $can_services = can_access_module('services', $pdo);
            $can_warranties = can_access_module('warranties', $pdo);
            $can_history = can_access_module('history', $pdo);
            
            if ($can_services || $can_warranties || $can_history):
        ?>
        <div class="dropdown">
            <a href="#" class="nav-link <?php echo ((strpos($_SERVER['REQUEST_URI'], 'warranties') !== false || strpos($_SERVER['REQUEST_URI'], 'services') !== false) && (!isset($_GET['return_to']) || $_GET['return_to'] !== 'entry')) ? 'active' : ''; ?>">
                <i class="ph ph-clipboard-text"></i> Solicitud <i class="ph-bold ph-caret-down" style="font-size: 0.8rem;"></i>
            </a>
            <div class="dropdown-content">
                <?php if($can_services): ?>
                <a href="/System_Taller/modules/services/index.php" class="dropdown-item">
                    <i class="ph ph-wrench"></i> Servicios
                </a>
                <?php endif; ?>
                
                <?php if($can_warranties): ?>
                <a href="/System_Taller/modules/warranties/index.php" class="dropdown-item">
                    <i class="ph ph-shield-check"></i> Garantías
                </a>
                <?php endif; ?>

                <?php if($can_history): ?>
                <a href="/System_Taller/modules/history/index.php" class="dropdown-item">
                    <i class="ph ph-clock-counter-clockwise"></i> Historial
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if(can_access_module('reports', $pdo)): ?>
        <div style="width: 1px; height: 24px; background: var(--border-color); margin: 0 0.5rem;"></div>
        
        <a href="/System_Taller/modules/reports/index.php" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'reports') !== false ? 'active' : ''; ?>">
            <i class="ph ph-chart-bar"></i> Reportes
        </a>
        <?php endif; ?>


    </nav>
    
    <!-- User Profile & Dropdown -->
    <div class="navbar-user dropdown" style="cursor: pointer; padding-right: 0;">
        <div class="user-avatar-sm">
            <?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?>
        </div>
        <div style="line-height: 1.2;">
            <p class="text-sm font-medium"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></p>
            <p class="text-xs text-muted"><?php echo htmlspecialchars($_SESSION['role_name'] ?? 'Role'); ?></p>
        </div>
        <i class="ph-bold ph-caret-down" style="font-size: 0.8rem; margin-left: 0.5rem; color: var(--text-secondary);"></i>
        
        <div class="dropdown-content" style="left: auto; right: 0; min-width: 180px; top: 100%;">
            <?php if(can_access_module('settings', $pdo)): ?>
            <a href="/System_Taller/modules/settings/index.php" class="dropdown-item">
                <i class="ph ph-gear"></i> Configuración
            </a>
            <?php endif; ?>
            <div style="height: 1px; background: var(--border-color); margin: 0.25rem 0;"></div>
            <a href="/System_Taller/modules/auth/logout.php" class="dropdown-item" style="color: var(--danger);">
                <i class="ph ph-sign-out"></i> Salir
            </a>
        </div>
    </div>
</header>
<main class="main-content">
