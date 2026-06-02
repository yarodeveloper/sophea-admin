<?php
/**
 * SOPHEA - Invoice/Receipt HTML Template
 * 
 * Template for generating invoice/receipt HTML
 */

if (!isset($invoiceData)) {
    die('Invoice data not provided');
}

$client = $invoiceData['client'];
$company = $invoiceData['company'];
$services = $invoiceData['services'];
$totals = $invoiceData['totals'];
$invoiceNumber = $invoiceData['invoice_number'];
$invoiceDate = $invoiceData['date'];
$serviceNameForTotal = $invoiceData['service_name_for_total'] ?? 'Servicio';
$serviceNamesList = $invoiceData['service_names_list'] ?? [];
$isMultipleServices = $invoiceData['is_multiple_services'] ?? false;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado de Cuenta - <?php echo htmlspecialchars($invoiceNumber); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333333;
            background: #ffffff;
            padding: 20px;
        }
        
        .invoice-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .header-table {
            margin-bottom: 25px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 15px;
        }
        
        .header-left {
            width: 50%;
            vertical-align: middle;
        }
        
        .header-right {
            width: 50%;
            text-align: right;
            vertical-align: middle;
        }
        
        .logo-section img {
            max-width: 180px;
            max-height: 80px;
        }
        
        .title-section h1 {
            font-size: 22px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .invoice-info {
            font-size: 11px;
            color: #666666;
        }
        
        .info-table {
            margin-bottom: 25px;
        }
        
        .info-col {
            width: 50%;
            vertical-align: top;
        }
        
        .info-card {
            background: #f8fafc;
            padding: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        
        .info-card h3 {
            font-size: 10px;
            text-transform: uppercase;
            color: #666666;
            margin-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }
        
        .info-card p {
            margin: 3px 0;
            font-size: 11px;
            color: #333333;
        }
        
        .info-card strong {
            color: #1a1a1a;
            font-weight: bold;
        }
        
        .services-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .services-table th {
            background: #1e293b;
            color: #ffffff;
            padding: 10px 12px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .services-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
            color: #333333;
        }
        
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        
        .header-row {
            background: #f8fafc;
            font-weight: bold;
        }
        
        .payment-row td {
            padding-left: 25px;
        }
        
        .totals-table {
            margin-top: 20px;
        }
        
        .totals-left {
            width: 60%;
            vertical-align: top;
            padding-right: 30px;
        }
        
        .totals-right {
            width: 40%;
            vertical-align: top;
        }
        
        .total-box-table {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        
        .total-box-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .total-box-table tr:last-child td {
            border-bottom: none;
        }
        
        .grand-total td {
            background: #1e293b;
            color: #ffffff;
            font-weight: bold;
        }
        
        .grand-total td.label { color: #cbd5e1; }
        .grand-total td.val { font-size: 14px; }
        
        .normal-total td.label { font-weight: bold; color: #64748b; }
        .normal-total td.val { font-weight: bold; color: #333333; text-align: right; }
        
        .balance-total td { background: #f8fafc; font-weight: bold; border-top: 1px solid #e2e8f0; }
        .balance-total td.label { color: #333333; }
        .balance-total td.val { color: #1a1a1a; font-size: 13px; text-align: right; }
        
        .footer-table {
            margin-top: 30px;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }
        
        .company-notice {
            text-align: center;
            font-size: 9px;
            color: #666666;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td class="header-left">
                    <div class="logo-section">
                        <?php if (!empty($company['logo'])): ?>
                            <img src="<?php echo htmlspecialchars($company['logo']); ?>" alt="Logo">
                        <?php else: ?>
                            <h2 style="font-size: 24px; font-weight: bold; color: #1a1a1a; margin: 0;">SOPHEA</h2>
                        <?php endif; ?>
                    </div>
                </td>
                <td class="header-right">
                    <div class="title-section">
                        <h1>ESTADO DE CUENTA</h1>
                        <div class="invoice-info">
                            <p><strong>Folio:</strong> <?php echo htmlspecialchars($invoiceNumber); ?></p>
                            <p><strong>Fecha Emisión:</strong> <?php echo htmlspecialchars($invoiceDate); ?></p>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Info Grid -->
        <table class="info-table">
            <tr>
                <td class="info-col" style="padding-right: 10px;">
                    <div class="info-card">
                        <h3>DATOS DEL CLIENTE</h3>
                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($client['name']); ?></p>
                        <p><strong>Contacto:</strong> <?php echo htmlspecialchars($client['contact']); ?></p>
                        <p><strong>Dirección:</strong> <?php echo htmlspecialchars($client['location']); ?></p>
                    </div>
                </td>
                <td class="info-col" style="padding-left: 10px;">
                    <div class="info-card">
                        <h3>DATOS DEL EMISOR</h3>
                        <p><strong>Empresa:</strong> <?php echo htmlspecialchars($company['name']); ?></p>
                        <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($company['phone']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($company['email']); ?></p>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Table -->
        <table class="services-table">
            <thead>
                <tr>
                    <th style="width: 45%;">Descripción del Cargo / Servicio</th>
                    <th class="text-center" style="width: 15%;">Fecha</th>
                    <th class="text-center" style="width: 10%;">Cant.</th>
                    <th class="text-right" style="width: 15%;">Unitario</th>
                    <th class="text-right" style="width: 15%;">Importe</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $item): 
                    $isHeader = isset($item['is_service_header']) && $item['is_service_header'];
                ?>
                <tr class="<?php echo $isHeader ? 'header-row' : 'payment-row'; ?>">
                    <td>
                        <?php echo htmlspecialchars($item['description']); ?>
                    </td>
                    <td class="text-center"><?php echo !empty($item['date']) ? date('d/m/Y', strtotime($item['date'])) : '-'; ?></td>
                    <td class="text-center"><?php echo $item['quantity'] ?: '-'; ?></td>
                    <td class="text-right"><?php echo $item['price'] > 0 ? '$' . number_format($item['price'], 2) : '-'; ?></td>
                    <td class="text-right"><?php echo $item['total'] !== '' ? '$' . number_format($item['total'], 2) : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <table class="totals-table">
            <tr>
                <td class="totals-left">
                    <p style="font-size: 10px; color: #666666; line-height: 1.5; padding: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px;">
                        <strong>NOTA IMPORTANTE:</strong><br>
                        Este documento sirve como estado de cuenta informativo. Los pagos pendientes deben liquidarse antes de la fecha de vencimiento para evitar la suspensión de servicios digitales. Cualquier aclaración, favor de contactar a su asesor asignado.
                    </p>
                </td>
                <td class="totals-right">
                    <table class="total-box-table">
                        <tr class="grand-total">
                            <td class="label">TOTAL CARGOS</td>
                            <td class="val text-right">$<?php echo number_format($totals['service_total'], 2); ?></td>
                        </tr>
                        <tr class="normal-total">
                            <td class="label">TOTAL PAGADO</td>
                            <td class="val">$<?php echo number_format($totals['paid_total'], 2); ?></td>
                        </tr>
                        <?php if (isset($totals['iva']) && $totals['iva'] > 0): ?>
                        <tr class="normal-total">
                            <td class="label">IVA (16%)</td>
                            <td class="val">$<?php echo number_format($totals['iva'], 2); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr class="balance-total">
                            <td class="label">SALDO PENDIENTE</td>
                            <td class="val text-right">$<?php echo number_format($totals['remaining_total'], 2); ?></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Footer -->
        <table class="footer-table">
            <tr>
                <td>
                    <div class="company-notice">
                        <p><?php echo htmlspecialchars($company['name']); ?> | <?php echo htmlspecialchars($company['location']); ?></p>
                        <p><?php echo htmlspecialchars($company['email']); ?> | <?php echo htmlspecialchars($company['phone']); ?></p>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
