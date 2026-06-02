<?php
/**
 * SOPHEA - Quote HTML Template
 * 
 * Template for generating quote HTML
 */

if (!isset($templateData)) {
    die('Quote data not provided');
}

$quote = $templateData['quote'];
$client = $templateData['client'];
$company = $templateData['company'];
$bankDetails = $templateData['bank_details'];
$logo = $templateData['logo'];
$companyAddress = $templateData['company_address'];
$companyPhone = $templateData['company_phone'];
$companyPhoneWhatsapp = $templateData['company_phone_whatsapp'] ?? '';
$companyPhoneLandline = $templateData['company_phone_landline'] ?? '';
$companyEmail = $templateData['company_email'];
$companyChatbot = $templateData['company_chatbot'] ?? '';
$socialFacebook = $templateData['social_facebook'] ?? '';
$socialInstagram = $templateData['social_instagram'] ?? '';
$socialLinkedIn = $templateData['social_linkedin'] ?? '';
$socialYouTube = $templateData['social_youtube'] ?? '';

$quoteNumber = $quote['quote_number'];
$quoteDate = date('d/m/Y', strtotime($quote['created_at']));
$validUntil = $quote['valid_until'] ? date('d/m/Y', strtotime($quote['valid_until'])) : '';

// Format client info
$clientCompanyName = $client['company_name'] ?? 'Cliente';
$clientContactName = $client['contact_name'] ?? '';
$clientPhone = $client['phone'] ?? '';
$clientPhoneCode = $client['phone_country_code'] ?? '+52';
$clientFullPhone = $clientPhone ? ($clientPhoneCode . ' ' . $clientPhone) : '';
$clientContact = trim($clientContactName . ($clientFullPhone ? ' - ' . $clientFullPhone : ''));
$clientAddress = ($client['address'] ?? '') . ($client['city'] ? ', ' . $client['city'] : '');

$items = $quote['items'] ?? [];
$subtotal = $quote['subtotal'] ?? 0;
$taxRate = $quote['tax_rate'] ?? 0;
$taxAmount = $quote['tax_amount'] ?? 0;
$total = $quote['total'] ?? 0;
$currency = $quote['currency'] ?? 'MXN';
$termsConditions = $quote['terms_conditions'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotización - <?php echo htmlspecialchars($quoteNumber); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #333;
            background: #fff;
            padding: 15px;
        }
        
        .quote-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
        }
        
        .header {
            width: 100%;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
            overflow: hidden;
        }
        
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .header-table td {
            vertical-align: top;
            padding: 0;
        }
        
        .logo-section {
            width: 150px;
        }
        
        .logo-section img {
            max-width: 120px;
            max-height: 60px;
        }
        
        .title-section {
            text-align: right;
        }
        
        .title-section h1 {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        
        .quote-info {
            text-align: right;
            font-size: 10px;
            color: #666;
        }
        
        .info-section {
            width: 100%;
            margin-bottom: 15px;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .info-table td {
            width: 50%;
            vertical-align: top;
            padding: 8px;
            background: #f9f9f9;
            border: 1px solid #ddd;
        }
        
        .info-box h3 {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        
        .info-box p {
            margin: 3px 0;
            font-size: 10px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .items-table th {
            background: #333;
            color: #fff;
            padding: 8px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
        }
        
        .items-table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
            font-size: 10px;
        }
        
        .items-table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .totals-section {
            margin-top: 15px;
            margin-bottom: 15px;
        }
        
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .totals-table td {
            padding: 5px 8px;
            font-size: 10px;
        }
        
        .totals-table .label {
            text-align: right;
            padding-right: 15px;
        }
        
        .totals-table .amount {
            text-align: right;
            font-weight: bold;
        }
        
        .totals-table .total-row {
            background: #f0f0f0;
            font-weight: bold;
            font-size: 12px;
            border-top: 2px solid #333;
        }
        
        .terms-section {
            margin-top: 20px;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ddd;
        }
        
        .terms-section h3 {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }
        
        .terms-section p {
            font-size: 10px;
            line-height: 1.2;
            margin: 2px 0;
        }
        
        .bank-section {
            margin-top: 15px;
            padding: 10px;
            background: #f0f0f0;
            border: 1px solid #ddd;
        }
        
        .bank-section h3 {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }
        
        .bank-section p {
            font-size: 10px;
            margin: 3px 0;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #333;
            font-size: 9px;
            color: #666;
        }
        
        .footer-content {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .footer-left, .footer-right {
            display: table-cell;
            vertical-align: top;
            width: 50%;
        }
        
        .footer-left p {
            margin: 2px 0;
        }
        
        .footer-social {
            margin-top: 8px;
        }
        
        .footer-social a {
            display: inline-block;
            margin-right: 8px;
            text-decoration: none;
            color: #333;
        }
        
        .footer-social img {
            width: 16px;
            height: 16px;
            vertical-align: middle;
        }
        
        .quote-content ul, .quote-content ol {
            margin-left: 25px;
            margin-top: 5px;
            margin-bottom: 10px;
        }
        
        .quote-content li {
            margin-bottom: 3px;
        }
        
        @media print {
            body {
                padding: 0;
            }
            
            .quote-container {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="quote-container">
        <!-- Header -->
        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="logo-section">
                        <?php if (!empty($logo)): ?>
                            <img src="<?php echo htmlspecialchars($logo); ?>" alt="SOPHEA Logo">
                        <?php else: ?>
                            <h2 style="font-size: 20px; color: #333; margin: 0;">SOPHEA</h2>
                        <?php endif; ?>
                    </td>
                    <td class="title-section">
                        <h1>COTIZACIÓN</h1>
                        <div class="quote-info">
                            <p><strong>Fecha:</strong> <?php echo htmlspecialchars($quoteDate); ?></p>
                            <p><strong>No. Cotización:</strong> <?php echo htmlspecialchars($quoteNumber); ?></p>
                            <?php if ($validUntil): ?>
                                <p><strong>Válida hasta:</strong> <?php echo htmlspecialchars($validUntil); ?></p>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Client Info -->
        <div class="info-section">
            <table class="info-table">
                <tr>
                    <td style="width: 100%;">
                        <div class="info-box">
                            <h3># Cliente</h3>
                            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($clientCompanyName); ?></p>
                            <p><strong>Contacto:</strong> <?php echo htmlspecialchars($clientContact); ?></p>
                            <p><strong>Ubicación:</strong> <?php echo htmlspecialchars($clientAddress); ?></p>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <?php if (!empty($quote['title'])): ?>
        <div style="margin-bottom: 15px;">
            <h2 style="font-size: 14px; font-weight: bold; color: #333;"><?php echo htmlspecialchars($quote['title']); ?></h2>
        </div>
        <?php endif; ?>

        <?php if (!empty($quote['description'])): ?>
        <div class="quote-content" style="margin-bottom: 15px; font-size: 10px; line-height: 1.5;">
            <?php echo $quote['description']; ?>
        </div>
        <?php endif; ?>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-right">Precio Unit.</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['description'] ?? ''); ?></td>
                    <td class="text-center"><?php echo number_format($item['quantity'] ?? 1, 2); ?></td>
                    <td class="text-right">$ <?php echo number_format($item['unit_price'] ?? 0, 2); ?></td>
                    <td class="text-right">$ <?php echo number_format(($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0), 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td class="label">Subtotal:</td>
                    <td class="amount">$ <?php echo number_format($subtotal, 2); ?></td>
                </tr>
                <?php if ($taxRate > 0): ?>
                <tr>
                    <td class="label">IVA (<?php echo number_format($taxRate, 2); ?>%):</td>
                    <td class="amount">$ <?php echo number_format($taxAmount, 2); ?></td>
                </tr>
                <?php endif; ?>
                <tr class="total-row">
                    <td class="label">Total:</td>
                    <td class="amount">$ <?php echo number_format($total, 2); ?> <?php echo htmlspecialchars($currency); ?></td>
                </tr>
            </table>
        </div>

        <!-- Bank Details -->
        <div class="bank-section">
            <h3>Datos Bancarios</h3>
            <p><strong><?php echo htmlspecialchars($bankDetails['account_holder']); ?></strong></p>
            <p>Banco: <?php echo htmlspecialchars($bankDetails['bank_name']); ?></p>
            <p>Cuenta: <?php echo htmlspecialchars($bankDetails['account_number']); ?></p>
            <p>CLABE: <?php echo htmlspecialchars($bankDetails['clabe']); ?></p>
            <?php if (!empty($bankDetails['debit_card'])): ?>
                <p>Tarjeta de débito: <?php echo htmlspecialchars($bankDetails['debit_card']); ?></p>
            <?php endif; ?>
        </div>

        <!-- Terms and Conditions -->
        <?php if (!empty($termsConditions)): ?>
        <div class="terms-section">
            <h3>Condiciones Generales del Servicio</h3>
            <p><?php echo nl2br(htmlspecialchars($termsConditions)); ?></p>
        </div>
        <?php endif; ?>
        
        <!-- Footer -->
        <div class="footer">
            <div class="footer-content">
                <div class="footer-left">
                    <p><strong>SOPHEA Marketing</strong></p>
                    <p><?php echo htmlspecialchars($companyAddress ?: (defined('CONTACT_ADDRESS') ? CONTACT_ADDRESS : '')); ?></p>
                    <?php if (!empty($companyPhone)): ?>
                        <p>Tel: <?php echo htmlspecialchars($companyPhone); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($companyPhoneWhatsapp)): ?>
                        <p>WhatsApp: <?php echo htmlspecialchars($companyPhoneWhatsapp); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($companyPhoneLandline)): ?>
                        <p>Tel. Fijo: <?php echo htmlspecialchars($companyPhoneLandline); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($companyEmail)): ?>
                        <p>Email: <?php echo htmlspecialchars($companyEmail); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($companyChatbot)): ?>
                        <p>Chatbot: <?php echo htmlspecialchars($companyChatbot); ?></p>
                    <?php endif; ?>
                </div>
                <div class="footer-right">
                    <div class="footer-social">
                        <?php if (!empty($socialFacebook)): ?>
                            <a href="<?php echo htmlspecialchars($socialFacebook); ?>" target="_blank" title="Facebook">
                                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%231877F2'%3E%3Cpath d='M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z'/%3E%3C/svg%3E" alt="Facebook">
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($socialInstagram)): ?>
                            <a href="<?php echo htmlspecialchars($socialInstagram); ?>" target="_blank" title="Instagram">
                                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23E4405F'%3E%3Cpath d='M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.98-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.98-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z'/%3E%3C/svg%3E" alt="Instagram">
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($socialLinkedIn)): ?>
                            <a href="<?php echo htmlspecialchars($socialLinkedIn); ?>" target="_blank" title="LinkedIn">
                                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%230A66C2'%3E%3Cpath d='M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z'/%3E%3C/svg%3E" alt="LinkedIn">
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($socialYouTube)): ?>
                            <a href="<?php echo htmlspecialchars($socialYouTube); ?>" target="_blank" title="YouTube">
                                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23FF0000'%3E%3Cpath d='M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z'/%3E%3C/svg%3E" alt="YouTube">
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

