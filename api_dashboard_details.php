<?php
/**
 * SOPHEA - Dashboard Details API
 * Returns detailed lists for dashboard metrics (Paid Income, Pending Income, Expenses)
 */

require_once 'admin_auth_helper.php';
$auth_data = requireAdminAuth(); // Ensure user is authenticated

header('Content-Type: application/json');

require_once 'classes/Database.php';

$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$type = isset($_GET['type']) ? $_GET['type'] : '';

$db = Database::getInstance()->getConnection();
$results = [];

try {
    if ($type === 'paid_income') {
        // Ingresos cobrados en el mes (status = paid, paid_at en el mes/año)
        $sql = "SELECT p.id, p.amount, p.status, p.paid_at as date, p.invoice_number, 
                       c.company_name, c.id as client_id, s.service_name
                FROM payments p
                LEFT JOIN clients c ON p.client_id = c.id
                LEFT JOIN services s ON p.service_id = s.id
                WHERE p.status = 'paid'
                AND MONTH(p.paid_at) = :month AND YEAR(p.paid_at) = :year
                ORDER BY p.paid_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':month' => $month, ':year' => $year]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($type === 'pending_income') {
        // Ingresos esperados (status in pending/overdue, due_date en el mes/año)
        $sql = "SELECT p.id, p.amount, p.status, p.payment_date as date, p.invoice_number, 
                       c.company_name, c.id as client_id, s.service_name
                FROM payments p
                LEFT JOIN clients c ON p.client_id = c.id
                LEFT JOIN services s ON p.service_id = s.id
                WHERE p.status IN ('pending', 'overdue')
                AND MONTH(p.payment_date) = :month AND YEAR(p.payment_date) = :year
                AND (p.service_id IS NULL OR s.status NOT IN ('completed', 'cancelled', 'finished'))
                ORDER BY p.payment_date ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':month' => $month, ':year' => $year]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($type === 'expenses') {
        // Gastos pagados en el mes (status = paid, payment_date en el mes/año)
        $sql = "SELECT e.id, e.amount, e.status, e.payment_date as date, e.expense_type, 
                       e.vendor as company_name, e.description as service_name, e.client_id
                FROM expenses e
                WHERE e.status = 'paid'
                AND MONTH(e.payment_date) = :month AND YEAR(e.payment_date) = :year
                ORDER BY e.payment_date DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':month' => $month, ':year' => $year]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        echo json_encode(['error' => 'Invalid type specified.']);
        exit;
    }

    echo json_encode(['success' => true, 'data' => $results]);
} catch (Exception $e) {
    error_log("Dashboard API Error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred.']);
}
