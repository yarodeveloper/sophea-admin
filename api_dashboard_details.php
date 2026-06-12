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
        $sql = "SELECT p.id, p.paid_amount as amount, p.status, p.paid_at as date, p.invoice_number, 
                       c.company_name, c.id as client_id, s.service_name
                FROM payments p
                LEFT JOIN clients c ON p.client_id = c.id
                LEFT JOIN services s ON p.service_id = s.id
                WHERE p.status IN ('paid', 'partially_paid')
                AND MONTH(p.paid_at) = :month AND YEAR(p.paid_at) = :year
                ORDER BY p.paid_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':month' => $month, ':year' => $year]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($type === 'pending_income') {
        // Ingresos esperados (status in pending/overdue, due_date en el mes/año)
        $results = [];
        
        // 1. Pagos pendientes reales
        $sql = "SELECT p.id, p.pending_amount as amount, p.status, p.payment_date as date, p.invoice_number, 
                       c.company_name, c.id as client_id, s.service_name
                FROM payments p
                LEFT JOIN clients c ON p.client_id = c.id
                LEFT JOIN services s ON p.service_id = s.id
                WHERE p.status IN ('pending', 'overdue', 'partially_paid')
                AND (p.service_id IS NULL OR s.status NOT IN ('completed', 'cancelled', 'finished'))
                ORDER BY p.payment_date ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $realPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($realPayments as $rp) {
            $results[] = $rp;
        }
        
        // 2. Saldos pendientes (Servicios activos donde tarifa > pagado + pendiente)
        $sql2 = "SELECT s.id as service_id, s.service_name, s.monthly_fee,
                        c.company_name, c.id as client_id,
                        COALESCE(SUM(p.paid_amount), 0) as total_paid,
                        COALESCE(SUM(p.pending_amount), 0) as total_pending
                 FROM services s
                 LEFT JOIN clients c ON s.client_id = c.id
                 LEFT JOIN payments p ON s.id = p.service_id AND p.status != 'cancelled'
                 WHERE s.status NOT IN ('completed', 'cancelled', 'finished')
                 GROUP BY s.id, s.service_name, s.monthly_fee, c.company_name, c.id";
        $stmt2 = $db->prepare($sql2);
        $stmt2->execute();
        $services = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($services as $svc) {
            $pendingBalance = floatval($svc['monthly_fee']) - floatval($svc['total_paid']) - floatval($svc['total_pending']);
            if ($pendingBalance > 0) {
                $results[] = [
                    'id' => null,
                    'amount' => $pendingBalance,
                    'status' => 'pending',
                    'date' => null,
                    'invoice_number' => null,
                    'company_name' => $svc['company_name'] ?? 'Desconocido',
                    'client_id' => $svc['client_id'],
                    'service_name' => ($svc['service_name'] ?? 'Servicio') . ' (Saldo Pendiente sin registrar)'
                ];
            }
        }

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
