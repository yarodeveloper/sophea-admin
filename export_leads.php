<?php
/**
 * SOPHEA - Export Leads to CSV/Excel
 * 
 * Exports leads to CSV format with optional filters
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(401);
    die('No autorizado');
}

require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';

// Get filters from URL
$statusFilter = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$format = $_GET['format'] ?? 'csv'; // csv or excel

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Build query with filters
    $sql = "SELECT id, nombre, especialidad, whatsapp, mensaje, status, source, created_at, updated_at, notes 
            FROM leads 
            WHERE 1=1";
    $params = [];
    
    if (!empty($statusFilter)) {
        $sql .= " AND status = :status";
        $params[':status'] = $statusFilter;
    }
    
    if (!empty($dateFrom)) {
        $sql .= " AND DATE(created_at) >= :date_from";
        $params[':date_from'] = $dateFrom;
    }
    
    if (!empty($dateTo)) {
        $sql .= " AND DATE(created_at) <= :date_to";
        $params[':date_to'] = $dateTo;
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate filename with timestamp
    $timestamp = date('Y-m-d_His');
    $filename = "leads_export_{$timestamp}";
    
    if ($format === 'excel') {
        // Excel format (CSV with UTF-8 BOM for Excel compatibility)
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        // Add UTF-8 BOM for Excel
        echo "\xEF\xBB\xBF";
        
        $output = fopen('php://output', 'w');
        
        // Headers
        fputcsv($output, [
            'ID',
            'Nombre',
            'Especialidad',
            'WhatsApp',
            'Mensaje',
            'Estado',
            'Origen',
            'Fecha Creación',
            'Última Actualización',
            'Notas'
        ], ';'); // Use semicolon for Excel compatibility
        
        // Data rows
        foreach ($leads as $lead) {
            fputcsv($output, [
                $lead['id'],
                $lead['nombre'],
                $lead['especialidad'],
                $lead['whatsapp'],
                $lead['mensaje'] ?? '',
                $lead['status'],
                $lead['source'] ?? 'website',
                date('d/m/Y H:i', strtotime($lead['created_at'])),
                $lead['updated_at'] ? date('d/m/Y H:i', strtotime($lead['updated_at'])) : '',
                $lead['notes'] ?? ''
            ], ';');
        }
        
        fclose($output);
    } else {
        // CSV format (standard CSV)
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        fputcsv($output, [
            'ID',
            'Nombre',
            'Especialidad',
            'WhatsApp',
            'Mensaje',
            'Estado',
            'Origen',
            'Fecha Creación',
            'Última Actualización',
            'Notas'
        ]);
        
        // Data rows
        foreach ($leads as $lead) {
            fputcsv($output, [
                $lead['id'],
                $lead['nombre'],
                $lead['especialidad'],
                $lead['whatsapp'],
                $lead['mensaje'] ?? '',
                $lead['status'],
                $lead['source'] ?? 'website',
                date('d/m/Y H:i', strtotime($lead['created_at'])),
                $lead['updated_at'] ? date('d/m/Y H:i', strtotime($lead['updated_at'])) : '',
                $lead['notes'] ?? ''
            ]);
        }
        
        fclose($output);
    }
    
    exit;
    
} catch (Exception $e) {
    error_log("Error exporting leads: " . $e->getMessage());
    http_response_code(500);
    die('Error al exportar los leads: ' . $e->getMessage());
}
