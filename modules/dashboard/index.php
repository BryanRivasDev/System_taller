<?php
// modules/dashboard/index.php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

$page_title = 'Dashboard';

// Check Dashboard Access
if (!can_access_module('dashboard', $pdo)) {
    die("Acceso denegado al Dashboard.");
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="dashboard-container">
    <h1>Dashboard</h1>
    <p>Preparado para el nuevo diseño.</p>
</div>

<?php
require_once '../../includes/footer.php';
?>
