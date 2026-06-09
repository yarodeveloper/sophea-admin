<?php
/**
 * SOPHEA - Project Transaction Management Class
 * 
 * Handles all project transaction operations for Ads services
 * Manages: income_fee, income_ads, expense_ads_consumed
 */

require_once __DIR__ . '/Database.php';

class ProjectTransaction {
    private $db;
    private $lastError = null;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get last error message
     */
    public function getLastError() {
        return $this->lastError;
    }
    
    /**
     * Create a new transaction
     */
    public function createTransaction($data) {
        try {
            $sql = "INSERT INTO project_transactions 
                    (service_id, client_id, transaction_type, amount, currency, description,
                     payment_id, receipt_id, platform, billing_period_start, billing_period_end,
                     transaction_date, reference_number, notes, created_by) 
                    VALUES 
                    (:service_id, :client_id, :transaction_type, :amount, :currency, :description,
                     :payment_id, :receipt_id, :platform, :billing_period_start, :billing_period_end,
                     :transaction_date, :reference_number, :notes, :created_by)";
            
            $stmt = $this->db->prepare($sql);
            
            $result = $stmt->execute([
                ':service_id' => $data['service_id'],
                ':client_id' => $data['client_id'],
                ':transaction_type' => $data['transaction_type'],
                ':amount' => $data['amount'],
                ':currency' => $data['currency'] ?? 'MXN',
                ':description' => $data['description'] ?? null,
                ':payment_id' => $data['payment_id'] ?? null,
                ':receipt_id' => $data['receipt_id'] ?? null,
                ':platform' => $data['platform'] ?? null,
                ':billing_period_start' => $data['billing_period_start'] ?? null,
                ':billing_period_end' => $data['billing_period_end'] ?? null,
                ':transaction_date' => $data['transaction_date'] ?? date('Y-m-d'),
                ':reference_number' => $data['reference_number'] ?? null,
                ':notes' => $data['notes'] ?? null,
                ':created_by' => $data['created_by'] ?? null
            ]);
            
            if ($result) {
                $transactionId = $this->db->lastInsertId();
                
                // Actualizar consumed_budget en services si es expense_ads_consumed
                if ($data['transaction_type'] === 'expense_ads_consumed') {
                    $this->updateServiceConsumedBudget($data['service_id']);
                }
                
                return $transactionId;
            }
            
            return false;
            
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Error creating project transaction: " . $e->getMessage());
            error_log("SQL: " . $sql);
            error_log("Data: " . print_r($data, true));
            return false;
        }
    }
    
    /**
     * Split payment into transactions (fee + ads investment)
     */
    public function splitPaymentIntoTransactions($paymentId, $serviceId, $clientId, $feeAmount, $adsAmount, $paymentDate, $createdBy = null, $receiptId = null) {
        try {
            $this->db->beginTransaction();
            
            $transactions = [];
            
            // Transaction 1: Honorarios (income_fee)
            if ($feeAmount > 0) {
                $transaction1 = $this->createTransaction([
                    'service_id' => $serviceId,
                    'client_id' => $clientId,
                    'transaction_type' => 'income_fee',
                    'amount' => $feeAmount,
                    'currency' => 'MXN',
                    'description' => 'Honorarios de gestión',
                    'payment_id' => $paymentId,
                    'receipt_id' => $receiptId,
                    'transaction_date' => $paymentDate,
                    'created_by' => $createdBy
                ]);
                
                if (!$transaction1) {
                    throw new Exception("Error creating fee transaction");
                }
                
                $transactions[] = $transaction1;
            }
            
            // Transaction 2: Inversión publicitaria (income_ads)
            if ($adsAmount > 0) {
                $transaction2 = $this->createTransaction([
                    'service_id' => $serviceId,
                    'client_id' => $clientId,
                    'transaction_type' => 'income_ads',
                    'amount' => $adsAmount,
                    'currency' => 'MXN',
                    'description' => 'Fondo para inversión publicitaria',
                    'payment_id' => $paymentId,
                    'receipt_id' => $receiptId,
                    'transaction_date' => $paymentDate,
                    'created_by' => $createdBy
                ]);
                
                if (!$transaction2) {
                    throw new Exception("Error creating ads investment transaction");
                }
                
                $transactions[] = $transaction2;
            }
            
            $this->db->commit();
            return $transactions;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error splitting payment into transactions: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get custody balance for a service (Saldo en Custodia)
     * Returns: total_investment, total_consumed, balance
     */
    public function getCustodyBalance($serviceId) {
        try {
            $sql = "SELECT 
                        COALESCE(SUM(CASE WHEN transaction_type = 'income_ads' THEN amount ELSE 0 END), 0) as total_investment,
                        COALESCE(SUM(CASE WHEN transaction_type = 'expense_ads_consumed' THEN ABS(amount) ELSE 0 END), 0) as total_consumed,
                        (COALESCE(SUM(CASE WHEN transaction_type = 'income_ads' THEN amount ELSE 0 END), 0) - 
                         COALESCE(SUM(CASE WHEN transaction_type = 'expense_ads_consumed' THEN ABS(amount) ELSE 0 END), 0)) as balance
                    FROM project_transactions
                    WHERE service_id = :service_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':service_id' => $serviceId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return [
                    'total_investment' => floatval($result['total_investment']),
                    'total_consumed' => floatval($result['total_consumed']),
                    'balance' => floatval($result['balance']),
                    'is_negative' => floatval($result['balance']) < 0
                ];
            }
            
            return [
                'total_investment' => 0,
                'total_consumed' => 0,
                'balance' => 0,
                'is_negative' => false
            ];
            
        } catch (PDOException $e) {
            error_log("Error getting custody balance: " . $e->getMessage());
            return [
                'total_investment' => 0,
                'total_consumed' => 0,
                'balance' => 0,
                'is_negative' => false
            ];
        }
    }
    
    /**
     * Get all custody balances for all Ads services
     */
    public function getAllCustodyBalances($clientId = null) {
        try {
            $sql = "SELECT 
                        s.id as service_id,
                        s.service_name,
                        s.client_id,
                        c.company_name,
                        COALESCE(SUM(CASE WHEN pt.transaction_type = 'income_ads' THEN pt.amount ELSE 0 END), 0) as total_investment,
                        COALESCE(SUM(CASE WHEN pt.transaction_type = 'expense_ads_consumed' THEN ABS(pt.amount) ELSE 0 END), 0) as total_consumed,
                        (COALESCE(SUM(CASE WHEN pt.transaction_type = 'income_ads' THEN pt.amount ELSE 0 END), 0) - 
                         COALESCE(SUM(CASE WHEN pt.transaction_type = 'expense_ads_consumed' THEN ABS(pt.amount) ELSE 0 END), 0)) as balance
                    FROM services s
                    LEFT JOIN clients c ON s.client_id = c.id
                    LEFT JOIN project_transactions pt ON s.id = pt.service_id
                    WHERE s.is_ads_service = TRUE";
            
            $params = [];
            
            if ($clientId) {
                $sql .= " AND s.client_id = :client_id";
                $params[':client_id'] = $clientId;
            }
            
            $sql .= " GROUP BY s.id, s.service_name, s.client_id, c.company_name
                      ORDER BY s.client_id, s.service_name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $balances = [];
            foreach ($results as $row) {
                $balance = floatval($row['balance']);
                $balances[] = [
                    'service_id' => intval($row['service_id']),
                    'service_name' => $row['service_name'],
                    'client_id' => intval($row['client_id']),
                    'company_name' => $row['company_name'],
                    'total_investment' => floatval($row['total_investment']),
                    'total_consumed' => floatval($row['total_consumed']),
                    'balance' => $balance,
                    'is_negative' => $balance < 0
                ];
            }
            
            return $balances;
            
        } catch (PDOException $e) {
            error_log("Error getting all custody balances: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get transactions by service
     */
    public function getTransactionsByService($serviceId, $filters = []) {
        try {
            $sql = "SELECT pt.*, 
                           s.service_name,
                           c.company_name,
                           p.payment_number
                    FROM project_transactions pt
                    JOIN services s ON pt.service_id = s.id
                    JOIN clients c ON pt.client_id = c.id
                    LEFT JOIN payments p ON pt.payment_id = p.id
                    WHERE pt.service_id = :service_id";
            
            $params = [':service_id' => $serviceId];
            
            if (isset($filters['transaction_type'])) {
                $sql .= " AND pt.transaction_type = :transaction_type";
                $params[':transaction_type'] = $filters['transaction_type'];
            }
            
            if (isset($filters['date_from'])) {
                $sql .= " AND pt.transaction_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            
            if (isset($filters['date_to'])) {
                $sql .= " AND pt.transaction_date <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }
            
            $sql .= " ORDER BY pt.transaction_date DESC, pt.created_at DESC";
            
            if (isset($filters['limit'])) {
                $sql .= " LIMIT " . intval($filters['limit']);
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error getting transactions by service: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get gross profit (sum of all income_fee)
     */
    public function getGrossProfit($serviceId = null, $dateFrom = null, $dateTo = null) {
        try {
            $sql = "SELECT SUM(amount) as gross_profit
                    FROM project_transactions
                    WHERE transaction_type = 'income_fee'";
            
            $params = [];
            
            if ($serviceId) {
                $sql .= " AND service_id = :service_id";
                $params[':service_id'] = $serviceId;
            }
            
            if ($dateFrom) {
                $sql .= " AND transaction_date >= :date_from";
                $params[':date_from'] = $dateFrom;
            }
            
            if ($dateTo) {
                $sql .= " AND transaction_date <= :date_to";
                $params[':date_to'] = $dateTo;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return floatval($result['gross_profit'] ?? 0);
            
        } catch (PDOException $e) {
            error_log("Error getting gross profit: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Update service consumed_budget
     */
    private function updateServiceConsumedBudget($serviceId) {
        try {
            $balance = $this->getCustodyBalance($serviceId);
            
            $sql = "UPDATE services 
                    SET consumed_budget = :consumed_budget
                    WHERE id = :service_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':consumed_budget' => $balance['total_consumed'],
                ':service_id' => $serviceId
            ]);
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Error updating service consumed budget: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Record consumed budget (expense_ads_consumed)
     */
    public function recordConsumedBudget($serviceId, $clientId, $amount, $platform = null, $billingPeriodStart = null, $billingPeriodEnd = null, $description = null, $createdBy = null) {
        try {
            return $this->createTransaction([
                'service_id' => $serviceId,
                'client_id' => $clientId,
                'transaction_type' => 'expense_ads_consumed',
                'amount' => -abs($amount), // Negativo para representar salida
                'currency' => 'MXN',
                'description' => $description ?? 'Presupuesto consumido en plataforma',
                'platform' => $platform,
                'billing_period_start' => $billingPeriodStart,
                'billing_period_end' => $billingPeriodEnd,
                'transaction_date' => date('Y-m-d'),
                'created_by' => $createdBy
            ]);
            
        } catch (Exception $e) {
            error_log("Error recording consumed budget: " . $e->getMessage());
            return false;
        }
    }
}

