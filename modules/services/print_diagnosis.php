<?php
// modules/services/print_diagnosis.php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    die("Acceso denegado.");
}

$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID no especificado.");
}

// Fetch Settings
$settings = [];
$stmtAll = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
while ($row = $stmtAll->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$system_logo = $settings['system_logo'] ?? '';
$company_name = $settings['company_name'] ?? 'SYSTEM TALLER';
$company_email = $settings['company_email'] ?? 'contacto@taller.com';
$company_phone = $settings['company_phone'] ?? '(555) 123-4567';

// Fetch Order Details
$stmt = $pdo->prepare("
    SELECT 
        so.*,
        c.name as client_name, c.phone, c.email,
        e.brand, e.model, e.serial_number, e.type as equipment_type,
        u_auth.username as authorized_by_name
    FROM service_orders so
    JOIN clients c ON so.client_id = c.id
    JOIN equipments e ON so.equipment_id = e.id
    LEFT JOIN users u_auth ON so.authorized_by_user_id = u_auth.id
    WHERE so.id = ?
");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    die("Orden no encontrada.");
}

// Fetch User who elaborated
$elaborated_by = $_SESSION['username']; 
$elaborated_role = $_SESSION['role_name'] ?? 'Técnico';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico #<?php echo str_pad($order['diagnosis_number'] ?? $order['id'], 6, '0', STR_PAD_LEFT); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --text-color: #000000;
            --bg-page: #f0f0f0;
        }
        
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        html, body {
            margin: 0;
            padding: 0;
            background-color: var(--bg-page);
        }

        body {
            font-family: 'Roboto', sans-serif;
            color: var(--text-color);
            font-size: 14px;
        }
        
        /* PAPER PREVIEW ON SCREEN */
        .page-container {
            width: 210mm;
            height: 297mm;
            margin: 20px auto;
            background: white;
            position: relative;
            padding: 2cm 1.5cm;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
        }

        /* ACTIONS (BUTTONS) */
        .actions {
            position: fixed; top: 20px; right: 20px; display: flex; gap: 10px; z-index: 999;
        }
        .btn { padding: 8px 16px; border-radius: 4px; cursor: pointer; border: none; font-weight: bold; text-decoration: none; display: inline-block; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-secondary { background: white; color: #333; border: 1px solid #ccc; }

        /* HEADER */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5cm;
        }

        .header-logo img {
            max-height: 60px;
        }
        
        .header-info {
            text-align: right;
            font-size: 12px;
        }
        .header-info h2 { margin: 0; font-size: 14px; color: #666; font-weight: bold; }
        .header-info p { margin: 2px 0; }

        /* CONTENT AREA */
        .doc-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        .date-line {
            text-align: right;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .info-block {
            margin-bottom: 20px;
            line-height: 1.4;
        }
        .info-row {
            display: flex;
            margin-bottom: 2px;
        }
        .label {
            font-weight: bold;
            width: 140px; 
        }
        .value {
            flex: 1;
        }
        .uc-text { text-transform: uppercase; }

        .section-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-top: 25px;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }

        .text-content {
            font-size: 13px;
            line-height: 1.4;
            text-align: justify;
            margin-bottom: 10px;
            white-space: pre-line;
        }

        /* FOOTER (SIGNATURE) - LOCKED TO BOTTOM */
        .footer-signatures {
            position: absolute;
            bottom: 2cm;
            left: 1.5cm;
            right: 1.5cm;
            border-top: 1px solid transparent; /* Just for spacing */
        }
        .elaborated-by {
            font-weight: bold;
            margin-bottom: 5px;
        }

        /* PRINT STYLES */
        @media print {
            html, body {
                background: white !important;
            }
            @page {
                size: A4;
                margin: 0;
            }
            .actions {
                display: none !important;
            }
            .page-container {
                margin: 0 !important;
                box-shadow: none !important;
                width: 210mm !important;
                height: 297mm !important;
                padding: 2cm 1.5cm !important;
                border: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="actions">
        <button onclick="history.back()" class="btn btn-secondary">Volver</button>
        <button onclick="window.print()" class="btn btn-primary">Imprimir</button>
    </div>

    <div class="page-container">
        <!-- Header -->
        <div class="header">
            <div class="header-logo">
                <?php if($system_logo): ?>
                    <img src="../../assets/uploads/<?php echo $system_logo; ?>" alt="Logo">
                <?php else: ?>
                    <h2><?php echo htmlspecialchars($company_name); ?></h2>
                <?php endif; ?>
            </div>
            <div class="header-info">
                <h2>SOPORTE TÉCNICO</h2>
                <p>Telf: <?php echo htmlspecialchars($company_phone); ?></p>
                <p><?php echo htmlspecialchars($company_email); ?></p>
            </div>
        </div>

        <div class="content">
            <div class="date-line">
                Fecha: <?php echo date('d \d\e F \d\e Y'); ?>
            </div>

            <div class="doc-title">
                REPORTE DE DIAGNÓSTICO
            </div>

            <div class="info-block">
                <div class="info-row">
                    <span class="label">Tipo:</span>
                    <span class="value"><?php echo ($order['service_type'] == 'warranty') ? 'Garantía' : 'Servicio'; ?></span>
                </div>
                <div class="info-row">
                    <span class="label">No. Caso:</span>
                    <span class="value"><?php echo $order['id']; ?></span>
                </div>
                <?php if($order['invoice_number']): ?>
                <div class="info-row">
                    <span class="label">No. Factura:</span>
                    <span class="value"><?php echo htmlspecialchars($order['invoice_number']); ?></span>
                </div>
                <?php endif; ?>
                <?php if($order['diagnosis_number']): ?>
                <div class="info-row">
                    <span class="label">No. Diagnóstico:</span>
                    <span class="value"><?php echo $order['diagnosis_number']; ?></span>
                </div>
                <?php endif; ?>
                
                <div class="info-row">
                    <span class="label">Dispositivo:</span>
                    <span class="value uc-text"><?php echo htmlspecialchars($order['equipment_type']); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Marca:</span>
                    <span class="value uc-text"><?php echo htmlspecialchars($order['brand']); ?></span>
                </div>
                 <div class="info-row">
                    <span class="label">Modelo:</span>
                    <span class="value"><?php echo htmlspecialchars($order['model']); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">No. Serie:</span>
                    <span class="value"><?php echo htmlspecialchars($order['serial_number']); ?></span>
                </div>
                <div class="info-row" style="margin-top: 5px;">
                    <span class="label">Falla Reportada:</span>
                    <span class="value uc-text"><?php echo htmlspecialchars($order['problem_reported']); ?></span>
                </div>
            </div>

            <div class="section-title">Procedimiento:</div>
            <div class="text-content">
                <?php echo $order['diagnosis_procedure'] ? nl2br(htmlspecialchars($order['diagnosis_procedure'])) : 'No registrado.'; ?>
            </div>

            <div class="section-title">Conclusión/Solución</div>
            <div class="text-content">
                <?php echo $order['diagnosis_conclusion'] ? nl2br(htmlspecialchars($order['diagnosis_conclusion'])) : 'No registrado.'; ?>
            </div>
        </div>

        <!-- Footer signatures anchored to bottom -->
        <div class="footer-signatures">
            <div class="elaborated-by">Elaborado por:</div>
            <div style="font-size: 14px; font-weight: bold;"><?php echo htmlspecialchars($elaborated_by); ?></div>
            <div style="font-size: 13px;"><?php echo htmlspecialchars($elaborated_role); ?> <?php echo htmlspecialchars($company_name); ?>.</div>
        </div>
    </div>

</body>
</html>
