<?php
/**
 * SOPHEA - Payment Management Class
 * 
 * Handles all payment operations (CRUD)
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Service.php';
require_once __DIR__ . '/ProjectTransaction.php';

class Payment {
    private $db;
    private $service;
    private $projectTransaction;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->service = new Service();
        $this->projectTransaction = new ProjectTransaction();
    }
    
    /**
     * Generate unique payment number (PAY-YYYY-MM-XXXX)
     */
    public function generatePaymentNumber() {
        try {
            $year = date('Y');
            $month = str_pad(date('m'), 2, '0', STR_PAD_LEFT);
            
            $stmt = $this->db->prepare("SELECT MAX(CAST(SUBSTRING(payment_number, 13) AS UNSIGNED)) as max_num 
                                        FROM payments 
                                        WHERE payment_number LIKE ?");
            $stmt->execute(["PAY-{$year}-{$month}-%"]);
            $result = $stmt->fetch();
            
            $sequence = ($result && $result['max_num']) ? $result['max_num'] + 1 : 1;
            return "PAY-{$year}-{$month}-" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
            
        } catch (PDOException $e) {
            error_log("Error generating payment number: " . $e->getMessage());
            return "PAY-" . date('Y-m') . "-" . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
    }
    
    /**
     * Generate unique invoice number (#XXXX)
     */
    public function generateInvoiceNumber() {
        try {
            $stmt = $this->db->prepare("SELECT MAX(CAST(SUBSTRING(invoice_number, 2) AS UNSIGNED)) as max_num 
                                        FROM payments 
                                        WHERE invoice_number LIKE '#%'");
            $stmt->execute();
            $result = $stmt->fetch();
            
            $sequence = ($result && $result['max_num']) ? $result['max_num'] + 1 : 1;
            return "#" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
            
        } catch (PDOException $e) {
            error_log("Error generating invoice number: " . $e->getMessage());
            return "#" . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
    }
    
    /**
     * Create a new payment
     */
    public function createPayment($data) {
        try {
            $paymentNumber = $this->generatePaymentNumber();
            $invoiceNumber = $this->generateInvoiceNumber();
            
            // Determine status based on payment_date
            $status = 'pending';
            if (isset($data['paid_at']) && !empty($data['paid_at'])) {
                $status = 'paid';
            } elseif (isset($data['due_date']) && !empty($data['due_date'])) {
                $dueDate = new DateTime($data['due_date']);
                $today = new DateTime();
                if ($dueDate < $today) {
                    $status = 'overdue';
                }
            }
            
            $sql = "INSERT INTO payments 
                    (client_id, service_id, quote_id, invoice_number, payment_number, amount, currency,
                     payment_method, payment_date, due_date, status, paid_at, reference_number, notes, created_by) 
                    VALUES 
                    (:client_id, :service_id, :quote_id, :invoice_number, :payment_number, :amount, :currency,
                     :payment_method, :payment_date, :due_date, :status, :paid_at, :reference_number, :notes, :created_by)";
            
            $stmt = $this->db->prepare($sql);
            
            $result = $stmt->execute([
                ':client_id' => $data['client_id'],
                ':service_id' => $data['service_id'] ?? null,
                ':quote_id' => $data['quote_id'] ?? null,
                ':invoice_number' => $invoiceNumber,
                ':payment_number' => $paymentNumber,
                ':amount' => $data['amount'],
                ':currency' => $data['currency'] ?? 'MXN',
                ':payment_method' => $data['payment_method'] ?? 'transfer',
                ':payment_date' => $data['payment_date'],
                ':due_date' => $data['due_date'] ?? null,
                ':status' => $status,
                ':paid_at' => $data['paid_at'] ?? null,
                ':reference_number' => $data['reference_number'] ?? null,
                ':notes' => $data['notes'] ?? null,
                ':created_by' => $data['created_by'] ?? null
            ]);
            
            if ($result) {
                $paymentId = $this->db->lastInsertId();
                
                // Si el servicio es Ads, desglosar el pago en transacciones
                if (!empty($data['service_id'])) {
                    $isAds = $this->service->isAdsService($data['service_id']);
                    
                    // Debug log
                    error_log("Payment::createPayment - Service ID: {$data['service_id']}, isAds: " . var_export($isAds, true) . ", fee_amount: " . (isset($data['fee_amount']) ? $data['fee_amount'] : 'NOT SET') . ", ads_amount: " . (isset($data['ads_amount']) ? $data['ads_amount'] : 'NOT SET'));
                    
                    if ($isAds) {
                        // Obtener montos de desglose (si vienen en $data)
                        $feeAmount = isset($data['fee_amount']) ? floatval($data['fee_amount']) : 0;
                        $adsAmount = isset($data['ads_amount']) ? floatval($data['ads_amount']) : 0;
                        
                        // Si no se especificaron, usar el monto total como fee (servicios no-Ads)
                        if ($feeAmount == 0 && $adsAmount == 0) {
                            $feeAmount = floatval($data['amount']);
                            error_log("Payment::createPayment - No split amounts provided, using full amount as fee: {$feeAmount}");
                        }
                        
                        // Crear transacciones si hay montos
                        if ($feeAmount > 0 || $adsAmount > 0) {
                            error_log("Payment::createPayment - Creating transactions: fee={$feeAmount}, ads={$adsAmount}");
                            $this->projectTransaction->splitPaymentIntoTransactions(
                                $paymentId,
                                $data['service_id'],
                                $data['client_id'],
                                $feeAmount,
                                $adsAmount,
                                $data['payment_date'],
                                $data['created_by'] ?? null
                            );
                        } else {
                            error_log("Payment::createPayment - WARNING: Both fee_amount and ads_amount are 0, no transactions created");
                        }
                    } else {
                        error_log("Payment::createPayment - Service is NOT Ads, skipping transaction split");
                    }
                }
                
                return $paymentId;
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Error creating payment: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update a payment
     */
    public function updatePayment($id, $data) {
        try {
            // Determine status
            $status = $data['status'] ?? 'pending';
            if ($status === 'paid' && !isset($data['paid_at'])) {
                $data['paid_at'] = date('Y-m-d H:i:s');
            }
            
            $sql = "UPDATE payments SET 
                    client_id = :client_id,
                    service_id = :service_id,
                    amount = :amount,
                    currency = :currency,
                    payment_method = :payment_method,
                    payment_date = :payment_date,
                    due_date = :due_date,
                    status = :status,
                    paid_at = :paid_at,
                    reference_number = :reference_number,
                    notes = :notes
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            
            $result = $stmt->execute([
                ':id' => $id,
                ':client_id' => $data['client_id'],
                ':service_id' => $data['service_id'] ?? null,
                ':amount' => $data['amount'],
                ':currency' => $data['currency'] ?? 'MXN',
                ':payment_method' => $data['payment_method'] ?? 'transfer',
                ':payment_date' => $data['payment_date'],
                ':due_date' => $data['due_date'] ?? null,
                ':status' => $status,
                ':paid_at' => $data['paid_at'] ?? null,
                ':reference_number' => $data['reference_number'] ?? null,
                ':notes' => $data['notes'] ?? null
            ]);

            if ($result) {
                // Si el servicio es Ads, actualizar transacciones
                if (!empty($data['service_id'])) {
                    $isAds = $this->service->isAdsService($data['service_id']);
                    
                    if ($isAds) {
                        // Eliminar transacciones anteriores de este pago
                        $sqlTrans = "DELETE FROM project_transactions WHERE payment_id = :id";
                        $stmtTrans = $this->db->prepare($sqlTrans);
                        $stmtTrans->execute([':id' => $id]);
                        
                        $feeAmount = isset($data['fee_amount']) ? floatval($data['fee_amount']) : 0;
                        $adsAmount = isset($data['ads_amount']) ? floatval($data['ads_amount']) : 0;
                        
                        if ($feeAmount == 0 && $adsAmount == 0) {
                            $feeAmount = floatval($data['amount']);
                        }
                        
                        if ($feeAmount > 0 || $adsAmount > 0) {
                            $this->projectTransaction->splitPaymentIntoTransactions(
                                $id,
                                $data['service_id'],
                                $data['client_id'],
                                $feeAmount,
                                $adsAmount,
                                $data['payment_date'],
                                $data['created_by'] ?? null
                            );
                        }
                    }
                }
                return true;
            }
            return false;
            
        } catch (PDOException $e) {
            error_log("Error updating payment: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark payment as paid
     */
    public function markAsPaid($id, $referenceNumber = null) {
        try {
            $sql = "UPDATE payments SET 
                    status = 'paid',
                    paid_at = NOW(),
                    reference_number = :reference_number
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':reference_number' => $referenceNumber
            ]);
            
        } catch (PDOException $e) {
            error_log("Error marking payment as paid: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get payment by ID
     */
    public function getPaymentById($id) {
        try {
            $sql = "SELECT p.*, c.company_name, c.client_number, s.service_name,
                           COALESCE((SELECT SUM(amount) FROM project_transactions WHERE payment_id = p.id AND transaction_type = 'income_fee'), 0) as fee_amount,
                           COALESCE((SELECT SUM(amount) FROM project_transactions WHERE payment_id = p.id AND transaction_type = 'income_ads'), 0) as ads_amount
                    FROM payments p
                    INNER JOIN clients c ON p.client_id = c.id
                    LEFT JOIN services s ON p.service_id = s.id
                    WHERE p.id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching payment: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update invoice information for a payment
     * @param int $id Payment ID
     * @param bool $invoiceSent Whether invoice was sent
     * @param string|null $invoiceSentAt Date/time when invoice was sent
     * @param string|null $invoiceUrl URL of the invoice
     * @param string|null $invoiceSentVia Method used to send (whatsapp, email, both)
     * @return bool Success
     */
    public function updatePaymentInvoiceInfo($id, $invoiceSent = false, $invoiceSentAt = null, $invoiceUrl = null, $invoiceSentVia = null) {
        try {
            $sql = "UPDATE payments SET 
                    invoice_sent = :invoice_sent,
                    invoice_sent_at = :invoice_sent_at,
                    invoice_url = :invoice_url,
                    invoice_sent_via = :invoice_sent_via
                    WHERE id = :id";
            
            $params = [
                ':id' => $id,
                ':invoice_sent' => $invoiceSent ? 1 : 0,
                ':invoice_sent_at' => $invoiceSentAt,
                ':invoice_url' => $invoiceUrl,
                ':invoice_sent_via' => $invoiceSentVia
            ];
            
            error_log("Updating payment invoice info for payment ID: $id");
            error_log("Params: " . print_r($params, true));
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                $rowsAffected = $stmt->rowCount();
                error_log("Payment invoice info updated successfully. Rows affected: $rowsAffected");
                return true;
            } else {
                error_log("Failed to update payment invoice info. No rows affected.");
                return false;
            }
            
        } catch (PDOException $e) {
            error_log("Error updating payment invoice info: " . $e->getMessage());
            error_log("SQL Error Info: " . print_r($e->errorInfo ?? [], true));
            // If columns don't exist, return false so we know there's a problem
            if (strpos($e->getMessage(), "Unknown column") !== false) {
                error_log("ERROR: Invoice columns do not exist in payments table. Run database/add_invoice_fields_to_payments.sql");
                return false;
            }
            return false;
        }
    }
    
    /**
     * Get all payments with optional filters
     */
    public function getAllPayments($filters = []) {
        try {
            $sql = "SELECT p.*, c.company_name, c.client_number, s.service_name, s.status as service_status,
                           COALESCE((SELECT SUM(amount) FROM project_transactions WHERE payment_id = p.id AND transaction_type = 'income_fee'), 0) as fee_amount,
                           COALESCE((SELECT SUM(amount) FROM project_transactions WHERE payment_id = p.id AND transaction_type = 'income_ads'), 0) as ads_amount
                    FROM payments p
                    INNER JOIN clients c ON p.client_id = c.id
                    LEFT JOIN services s ON p.service_id = s.id
                    WHERE 1=1";
            $params = [];
            
            // Status filter
            if (isset($filters['status']) && !empty($filters['status'])) {
                $sql .= " AND p.status = :status";
                $params[':status'] = $filters['status'];
            }
            
            // Client filter
            if (isset($filters['client_id']) && !empty($filters['client_id'])) {
                $sql .= " AND p.client_id = :client_id";
                $params[':client_id'] = $filters['client_id'];
            }
            
            // Service filter
            if (isset($filters['service_id']) && !empty($filters['service_id'])) {
                $sql .= " AND p.service_id = :service_id";
                $params[':service_id'] = $filters['service_id'];
            }
            
            // Date range filter
            if (isset($filters['date_from']) && !empty($filters['date_from'])) {
                $sql .= " AND p.payment_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            
            if (isset($filters['date_to']) && !empty($filters['date_to'])) {
                $sql .= " AND p.payment_date <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }
            
            // Order by
            $orderBy = $filters['order_by'] ?? 'p.payment_date';
            $orderDir = $filters['order_dir'] ?? 'DESC';
            $sql .= " ORDER BY {$orderBy} {$orderDir}";
            
            // Limit and offset
            if (isset($filters['limit'])) {
                $sql .= " LIMIT :limit";
                $params[':limit'] = (int)$filters['limit'];
                
                if (isset($filters['offset'])) {
                    $sql .= " OFFSET :offset";
                    $params[':offset'] = (int)$filters['offset'];
                }
            }
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                if ($key === ':limit' || $key === ':offset') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching payments: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get payments by client ID
     */
    public function getPaymentsByClient($clientId, $status = null) {
        $filters = ['client_id' => $clientId];
        if ($status) {
            $filters['status'] = $status;
        }
        return $this->getAllPayments($filters);
    }
    
    /**
     * Get payments by service ID
     */
    public function getPaymentsByService($serviceId, $status = null) {
        $filters = ['service_id' => $serviceId];
        if ($status) {
            $filters['status'] = $status;
        }
        return $this->getAllPayments($filters);
    }
    
    /**
     * Get service payment summary
     */
    public function getServicePaymentSummary($serviceId) {
        try {
            $sql = "SELECT 
                    COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) as total_paid,
                    COALESCE(SUM(CASE WHEN status IN ('pending', 'overdue') THEN amount ELSE 0 END), 0) as total_pending,
                    COUNT(*) as total_count
                    FROM payments 
                    WHERE service_id = :service_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':service_id' => $serviceId]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return [
                'total_paid' => floatval($result['total_paid'] ?? 0),
                'total_pending' => floatval($result['total_pending'] ?? 0),
                'total_count' => intval($result['total_count'] ?? 0)
            ];
            
        } catch (PDOException $e) {
            error_log("Error getting service payment summary: " . $e->getMessage());
            return [
                'total_paid' => 0,
                'total_pending' => 0,
                'total_count' => 0
            ];
        }
    }
    
    /**
     * Get pending payments
     */
    public function getPendingPayments($clientId = null) {
        $filters = ['status' => 'pending'];
        if ($clientId) {
            $filters['client_id'] = $clientId;
        }
        return $this->getAllPayments($filters);
    }
    
    /**
     * Get overdue payments
     */
    public function getOverduePayments($clientId = null) {
        $filters = ['status' => 'overdue'];
        if ($clientId) {
            $filters['client_id'] = $clientId;
        }
        return $this->getAllPayments($filters);
    }
    
    /**
     * Get payments due soon (next 7 days)
     */
    public function getPaymentsDueSoon($days = 7) {
        try {
            $sql = "SELECT p.*, c.company_name, c.client_number 
                    FROM payments p
                    INNER JOIN clients c ON p.client_id = c.id
                    WHERE p.status IN ('pending', 'overdue')
                    AND p.due_date IS NOT NULL
                    AND p.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
                    ORDER BY p.due_date ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':days' => $days]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching payments due soon: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total count of payments with optional filters
     */
    public function getTotalCount($filters = []) {
        try {
            $sql = "SELECT COUNT(*) as total 
                    FROM payments p
                    INNER JOIN clients c ON p.client_id = c.id
                    LEFT JOIN services s ON p.service_id = s.id
                    WHERE 1=1";
            $params = [];
            
            // Status filter
            if (isset($filters['status']) && !empty($filters['status'])) {
                $sql .= " AND p.status = :status";
                $params[':status'] = $filters['status'];
            }
            
            // Client filter
            if (isset($filters['client_id']) && !empty($filters['client_id'])) {
                $sql .= " AND p.client_id = :client_id";
                $params[':client_id'] = $filters['client_id'];
            }
            
            // Service filter
            if (isset($filters['service_id']) && !empty($filters['service_id'])) {
                $sql .= " AND p.service_id = :service_id";
                $params[':service_id'] = $filters['service_id'];
            }
            
            // Date range filter
            if (isset($filters['date_from']) && !empty($filters['date_from'])) {
                $sql .= " AND p.payment_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            
            if (isset($filters['date_to']) && !empty($filters['date_to'])) {
                $sql .= " AND p.payment_date <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
            
        } catch (PDOException $e) {
            error_log("Error counting payments: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get total pending amount
     */
    public function getTotalPending($clientId = null) {
        try {
            $sql = "SELECT COALESCE(SUM(amount), 0) as total 
                    FROM payments 
                    WHERE status IN ('pending', 'overdue')";
            $params = [];
            
            if ($clientId) {
                $sql .= " AND client_id = :client_id";
                $params[':client_id'] = $clientId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
            
        } catch (PDOException $e) {
            error_log("Error calculating total pending: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get total paid amount
     */
    public function getTotalPaid($clientId = null, $month = null, $year = null) {
        try {
            $sql = "SELECT COALESCE(SUM(amount), 0) as total 
                    FROM payments 
                    WHERE status = 'paid'";
            $params = [];
            
            if ($clientId) {
                $sql .= " AND client_id = :client_id";
                $params[':client_id'] = $clientId;
            }
            
            if ($month && $year) {
                $sql .= " AND MONTH(paid_at) = :month AND YEAR(paid_at) = :year";
                $params[':month'] = $month;
                $params[':year'] = $year;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
            
        } catch (PDOException $e) {
            error_log("Error calculating total paid: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get monthly revenue
     */
    public function getMonthlyRevenue($year = null, $month = null) {
        if (!$year) $year = date('Y');
        if (!$month) $month = date('m');
        
        return $this->getTotalPaid(null, $month, $year);
    }
    
    /**
     * Get expected income for a month (pending payments)
     */
    public function getExpectedIncome($year = null, $month = null) {
        try {
            if (!$year) $year = date('Y');
            if (!$month) $month = date('m');
            
            $sql = "SELECT COALESCE(SUM(amount), 0) as total 
                    FROM payments 
                    WHERE status IN ('pending', 'overdue')
                    AND MONTH(due_date) = :month 
                    AND YEAR(due_date) = :year";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':month' => $month,
                ':year' => $year
            ]);
            
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
            
        } catch (PDOException $e) {
            error_log("Error calculating monthly estimated expenses: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get revenue by month for chart (last 6 months)
     */
    public function getRevenueByMonth($months = 6) {
        try {
            $sql = "SELECT 
                        YEAR(paid_at) as year,
                        MONTH(paid_at) as month,
                        COALESCE(SUM(amount), 0) as total
                    FROM payments 
                    WHERE status = 'paid' 
                    AND paid_at >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                    GROUP BY YEAR(paid_at), MONTH(paid_at)
                    ORDER BY year ASC, month ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':months' => $months]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error getting revenue by month: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get monthly revenue for a full year (Jan to Dec)
     */
    public function getMonthlyRevenueForYear($year) {
        try {
            $sql = "SELECT 
                        MONTH(payment_date) as month,
                        COALESCE(SUM(amount), 0) as total
                    FROM payments 
                    WHERE status != 'cancelled' 
                    AND YEAR(payment_date) = :year
                    GROUP BY MONTH(payment_date)
                    ORDER BY month ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':year' => $year]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Re-format to ensure all 12 months are present
            $monthlyData = array_fill(1, 12, 0);
            foreach ($results as $row) {
                $monthlyData[intval($row['month'])] = floatval($row['total']);
            }
            
            $finalData = [];
            foreach ($monthlyData as $month => $total) {
                $finalData[] = [
                    'month' => $month,
                    'total' => $total
                ];
            }
            
            return $finalData;
            
        } catch (PDOException $e) {
            error_log("Error getting yearly revenue: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if a payment can be deleted
     * @param int $id Payment ID
     * @return array ['can_delete' => bool, 'reason' => string]
     */
    public function canDelete($id) {
        try {
            $paymentData = $this->getPaymentById($id);
            if (!$paymentData) {
                return ['can_delete' => false, 'reason' => 'Pago no encontrado'];
            }

            // Always allow deleting pending or overdue payments (cleaning up "rezagados")
            if (in_array($paymentData['status'], ['pending', 'overdue', 'cancelled'])) {
                return ['can_delete' => true, 'reason' => ''];
            }

            // If it's paid, check service status
            if ($paymentData['status'] === 'paid') {
                if (!empty($paymentData['service_id'])) {
                    $serviceData = $this->service->getServiceById($paymentData['service_id']);
                    // If service is completed/finished, do not allow deletion of paid history
                    if ($serviceData && in_array($serviceData['status'], ['completed', 'finished'])) {
                        return [
                            'can_delete' => false, 
                            'reason' => 'No se puede eliminar un pago recibido de un servicio ya completado/finalizado.'
                        ];
                    }
                }
                
                // Additional condition: Check if it has an invoice that might be considered critical
                // For now, if it's paid but service is still active, we allow it (e.g. error correction)
                // but with a warning in UI if possible.
            }

            return ['can_delete' => true, 'reason' => ''];
            
        } catch (Exception $e) {
            error_log("Error in canDelete check: " . $e->getMessage());
            return ['can_delete' => false, 'reason' => 'Error al validar el pago'];
        }
    }

    /**
     * Delete a payment (Permanent)
     */
    public function deletePaymentPermanent($id) {
        try {
            // Check if deletable first
            $check = $this->canDelete($id);
            if (!$check['can_delete']) {
                error_log("Attempted to delete non-deletable payment $id: " . $check['reason']);
                return false;
            }

            $this->db->beginTransaction();

            // 1. Delete associated project transactions (income_fee, income_ads)
            $sqlTrans = "DELETE FROM project_transactions WHERE payment_id = :id";
            $stmtTrans = $this->db->prepare($sqlTrans);
            $stmtTrans->execute([':id' => $id]);

            // 2. Delete the payment record
            $sql = "DELETE FROM payments WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([':id' => $id]);

            $this->db->commit();
            return $result;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error deleting payment permanently: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get monthly payments by client for report
     */
    public function getMonthlyPaymentsByClient($year, $month) {
        try {
            $sql = "SELECT c.company_name, SUM(p.amount) as total_amount
                    FROM payments p
                    INNER JOIN clients c ON p.client_id = c.id
                    WHERE p.status = 'paid'
                    AND YEAR(p.paid_at) = :year
                    AND MONTH(p.paid_at) = :month
                    GROUP BY c.id, c.company_name
                    ORDER BY total_amount DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':year' => $year, ':month' => $month]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting monthly payments by client: " . $e->getMessage());
            return [];
        }
    }
}

