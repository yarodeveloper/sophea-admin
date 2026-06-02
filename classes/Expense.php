<?php
/**
 * SOPHEA - Expense Management Class
 * 
 * Handles all expense operations (CRUD)
 */

require_once __DIR__ . '/Database.php';

class Expense {
    private $db;
    private $lastError = null;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get the last error message
     */
    public function getLastError() {
        return $this->lastError;
    }
    
    /**
     * Check if a column exists in the expenses table
     */
    private function columnExists($columnName) {
        static $columnsCache = null;
        
        if ($columnsCache === null) {
            try {
                $stmt = $this->db->query("SHOW COLUMNS FROM expenses");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $columnsCache = array_flip($columns);
            } catch (PDOException $e) {
                error_log("Error checking columns: " . $e->getMessage());
                $columnsCache = [];
            }
        }
        
        return isset($columnsCache[$columnName]);
    }
    
    /**
     * Generate unique expense number (EXP-YYYY-MM-XXXX)
     */
    public function generateExpenseNumber() {
        try {
            $year = date('Y');
            $month = str_pad(date('m'), 2, '0', STR_PAD_LEFT);
            
            $stmt = $this->db->prepare("SELECT MAX(CAST(SUBSTRING(expense_number, 13) AS UNSIGNED)) as max_num 
                                        FROM expenses 
                                        WHERE expense_number LIKE ?");
            $stmt->execute(["EXP-{$year}-{$month}-%"]);
            $result = $stmt->fetch();
            
            $sequence = ($result && $result['max_num']) ? $result['max_num'] + 1 : 1;
            return "EXP-{$year}-{$month}-" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
            
        } catch (PDOException $e) {
            error_log("Error generating expense number: " . $e->getMessage());
            return "EXP-" . date('Y-m') . "-" . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
    }
    
    /**
     * Get expense type labels
     */
    public function getExpenseTypeLabels() {
        return [
            'hosting' => 'Hosting',
            'domain' => 'Dominio',
            'platform' => 'Plataforma',
            'software' => 'Software',
            'salary' => 'Sueldo',
            'freelancer' => 'Freelancer',
            'marketing' => 'Marketing',
            'ads_facebook' => 'Facebook Ads',
            'ads_google' => 'Google Ads',
            'ads_instagram' => 'Instagram Ads',
            'ads_tiktok' => 'TikTok Ads',
            'ads_linkedin' => 'LinkedIn Ads',
            'ads_other' => 'Otros Ads',
            'office' => 'Oficina',
            'utilities' => 'Servicios',
            'other' => 'Otro'
        ];
    }
    
    /**
     * Get reimbursement status labels
     */
    public function getReimbursementStatusLabels() {
        return [
            'not_required' => 'No requerido',
            'pending' => 'Pendiente',
            'billed' => 'Facturado',
            'paid' => 'Pagado'
        ];
    }
    
    /**
     * Check if expense type is an Ads type
     */
    public function isAdsType($expenseType) {
        return strpos($expenseType, 'ads_') === 0;
    }
    
    /**
     * Get billing cycle labels
     */
    public function getBillingCycleLabels() {
        return [
            'one_time' => 'Una vez',
            'monthly' => 'Mensual',
            'quarterly' => 'Trimestral',
            'yearly' => 'Anual'
        ];
    }
    
    /**
     * Create a new expense
     */
    public function createExpense($data) {
        try {
            // Validate client service cost
            $isClientServiceCost = isset($data['is_client_service_cost']) && $data['is_client_service_cost'];
            
            if ($isClientServiceCost) {
                if (empty($data['client_id']) || empty($data['service_id'])) {
                    $this->lastError = "Se requiere cliente y servicio cuando el gasto es de costo de servicio al cliente";
                    error_log("Error: is_client_service_cost is TRUE but client_id or service_id is missing");
                    return false;
                }
            }
            
            // Auto-detect if it's a client service cost based on expense type
            if (!$isClientServiceCost && isset($data['expense_type']) && $this->isAdsType($data['expense_type'])) {
                $isClientServiceCost = true;
                // If client_id and service_id are provided, use them
                if (empty($data['client_id']) || empty($data['service_id'])) {
                    error_log("Warning: Ads expense type detected but client_id or service_id missing");
                }
            }
            
            $expenseNumber = $this->generateExpenseNumber();
            
            // Determine status based on payment_date
            $status = 'pending';
            if (isset($data['payment_date']) && !empty($data['payment_date'])) {
                $paymentDate = new DateTime($data['payment_date']);
                $today = new DateTime();
                if ($paymentDate <= $today) {
                    $status = 'paid';
                } elseif (isset($data['due_date']) && !empty($data['due_date'])) {
                    $dueDate = new DateTime($data['due_date']);
                    if ($dueDate < $today) {
                        $status = 'overdue';
                    }
                }
            }
            
            // Build SQL dynamically based on available columns
            $columns = ['expense_number', 'expense_type', 'category', 'description', 'amount', 'currency',
                       'payment_method', 'payment_date', 'due_date', 'billing_cycle', 'vendor',
                       'invoice_number', 'receipt_url', 'status', 'is_recurring', 'notes', 'created_by'];
            $placeholders = [':expense_number', ':expense_type', ':category', ':description', ':amount', ':currency',
                           ':payment_method', ':payment_date', ':due_date', ':billing_cycle', ':vendor',
                           ':invoice_number', ':receipt_url', ':status', ':is_recurring', ':notes', ':created_by'];
            $values = [
                ':expense_number' => $expenseNumber,
                ':expense_type' => $data['expense_type'],
                ':category' => $data['category'],
                ':description' => $data['description'] ?? null,
                ':amount' => $data['amount'],
                ':currency' => $data['currency'] ?? 'MXN',
                ':payment_method' => $data['payment_method'] ?? 'transfer',
                ':payment_date' => $data['payment_date'],
                ':due_date' => $data['due_date'] ?? null,
                ':billing_cycle' => $data['billing_cycle'] ?? 'monthly',
                ':vendor' => $data['vendor'] ?? null,
                ':invoice_number' => $data['invoice_number'] ?? null,
                ':receipt_url' => $data['receipt_url'] ?? null,
                ':status' => $status,
                ':is_recurring' => isset($data['is_recurring']) ? ($data['is_recurring'] ? 1 : 0) : 1,
                ':notes' => $data['notes'] ?? null,
                ':created_by' => $data['created_by'] ?? null
            ];
            
            // Add optional columns if they exist in the table
            $optionalColumns = [
                'client_id' => $isClientServiceCost ? ($data['client_id'] ?? null) : null,
                'service_id' => $isClientServiceCost ? ($data['service_id'] ?? null) : null,
                'is_client_service_cost' => $isClientServiceCost ? 1 : 0,
                'campaign_id' => $data['campaign_id'] ?? null,
                'billing_period_start' => $data['billing_period_start'] ?? null,
                'billing_period_end' => $data['billing_period_end'] ?? null,
                'reimbursement_status' => $data['reimbursement_status'] ?? 'not_required'
            ];
            
            foreach ($optionalColumns as $col => $val) {
                if ($this->columnExists($col)) {
                    $columns[] = $col;
                    $placeholders[] = ':' . $col;
                    $values[':' . $col] = $val;
                }
            }
            
            $sql = "INSERT INTO expenses (" . implode(', ', $columns) . ") 
                    VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $this->db->prepare($sql);
            
            if (!$stmt) {
                $errorInfo = $this->db->errorInfo();
                $this->lastError = "Error al preparar la consulta SQL: " . ($errorInfo[2] ?? 'Error desconocido');
                error_log("Error preparing SQL: " . print_r($errorInfo, true));
                return false;
            }
            
            $result = $stmt->execute($values);
            
            if ($result) {
                return $this->db->lastInsertId();
            } else {
                $errorInfo = $stmt->errorInfo();
                $this->lastError = "Error al ejecutar la consulta: " . ($errorInfo[2] ?? 'Error desconocido');
                error_log("Error executing SQL: " . print_r($errorInfo, true));
                error_log("SQL: " . $sql);
                error_log("Data: " . print_r($data, true));
                return false;
            }
            
        } catch (PDOException $e) {
            $this->lastError = "Error de base de datos: " . $e->getMessage();
            error_log("Error creating expense: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        } catch (Exception $e) {
            $this->lastError = "Error general: " . $e->getMessage();
            error_log("Error creating expense (general): " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Update an expense
     */
    public function updateExpense($id, $data) {
        try {
            // Validate client service cost
            $isClientServiceCost = isset($data['is_client_service_cost']) && $data['is_client_service_cost'];
            
            if ($isClientServiceCost) {
                if (empty($data['client_id']) || empty($data['service_id'])) {
                    $this->lastError = "Se requiere cliente y servicio cuando el gasto es de costo de servicio al cliente";
                    error_log("Error: is_client_service_cost is TRUE but client_id or service_id is missing");
                    return false;
                }
            }
            
            // Determine status
            $status = $data['status'] ?? 'pending';
            if (isset($data['payment_date']) && !empty($data['payment_date'])) {
                $paymentDate = new DateTime($data['payment_date']);
                $today = new DateTime();
                if ($paymentDate <= $today && $status === 'pending') {
                    $status = 'paid';
                }
            }
            
            // Build SQL dynamically based on available columns
            $setParts = [
                'expense_type = :expense_type',
                'category = :category',
                'description = :description',
                'amount = :amount',
                'currency = :currency',
                'payment_method = :payment_method',
                'payment_date = :payment_date',
                'due_date = :due_date',
                'billing_cycle = :billing_cycle',
                'vendor = :vendor',
                'invoice_number = :invoice_number',
                'receipt_url = :receipt_url',
                'status = :status',
                'is_recurring = :is_recurring',
                'notes = :notes'
            ];
            
            $values = [
                ':id' => $id,
                ':expense_type' => $data['expense_type'],
                ':category' => $data['category'],
                ':description' => $data['description'] ?? null,
                ':amount' => $data['amount'],
                ':currency' => $data['currency'] ?? 'MXN',
                ':payment_method' => $data['payment_method'] ?? 'transfer',
                ':payment_date' => $data['payment_date'],
                ':due_date' => $data['due_date'] ?? null,
                ':billing_cycle' => $data['billing_cycle'] ?? 'monthly',
                ':vendor' => $data['vendor'] ?? null,
                ':invoice_number' => $data['invoice_number'] ?? null,
                ':receipt_url' => $data['receipt_url'] ?? null,
                ':status' => $status,
                ':is_recurring' => isset($data['is_recurring']) ? ($data['is_recurring'] ? 1 : 0) : 1,
                ':notes' => $data['notes'] ?? null
            ];
            
            // Add optional columns if they exist in the table
            $optionalColumns = [
                'client_id' => $isClientServiceCost ? ($data['client_id'] ?? null) : null,
                'service_id' => $isClientServiceCost ? ($data['service_id'] ?? null) : null,
                'is_client_service_cost' => $isClientServiceCost ? 1 : 0,
                'campaign_id' => $data['campaign_id'] ?? null,
                'billing_period_start' => $data['billing_period_start'] ?? null,
                'billing_period_end' => $data['billing_period_end'] ?? null,
                'reimbursement_status' => $data['reimbursement_status'] ?? 'not_required'
            ];
            
            foreach ($optionalColumns as $col => $val) {
                if ($this->columnExists($col)) {
                    $setParts[] = $col . ' = :' . $col;
                    $values[':' . $col] = $val;
                }
            }
            
            $sql = "UPDATE expenses SET " . implode(', ', $setParts) . " WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            
            if (!$stmt) {
                $errorInfo = $this->db->errorInfo();
                $this->lastError = "Error al preparar la consulta SQL: " . ($errorInfo[2] ?? 'Error desconocido');
                error_log("Error preparing SQL: " . print_r($errorInfo, true));
                return false;
            }
            
            $result = $stmt->execute($values);
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                $this->lastError = "Error al ejecutar la consulta: " . ($errorInfo[2] ?? 'Error desconocido');
                error_log("Error executing SQL: " . print_r($errorInfo, true));
                return false;
            }
            
            return true;
            
        } catch (PDOException $e) {
            $this->lastError = "Error de base de datos: " . $e->getMessage();
            error_log("Error updating expense: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        } catch (Exception $e) {
            $this->lastError = "Error general: " . $e->getMessage();
            error_log("Error updating expense (general): " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Get expense by ID
     */
    public function getExpenseById($id) {
        try {
            $sql = "SELECT * FROM expenses WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching expense: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all expenses with filters
     */
    public function getAllExpenses($filters = []) {
        try {
            $sql = "SELECT * FROM expenses WHERE 1=1";
            $params = [];
            
            if (isset($filters['expense_type']) && !empty($filters['expense_type'])) {
                $sql .= " AND expense_type = :expense_type";
                $params[':expense_type'] = $filters['expense_type'];
            }
            
            // Handle status filter - exclude cancelled by default unless explicitly requested
            if (isset($filters['status']) && !empty($filters['status'])) {
                $sql .= " AND status = :status";
                $params[':status'] = $filters['status'];
            } else {
                // Exclude cancelled expenses by default
                $sql .= " AND status != 'cancelled'";
            }
            
            if (isset($filters['billing_cycle']) && !empty($filters['billing_cycle'])) {
                $sql .= " AND billing_cycle = :billing_cycle";
                $params[':billing_cycle'] = $filters['billing_cycle'];
            }
            
            if (isset($filters['is_recurring']) && $filters['is_recurring'] !== '') {
                $sql .= " AND is_recurring = :is_recurring";
                $params[':is_recurring'] = $filters['is_recurring'] ? 1 : 0;
            }
            
            if (isset($filters['date_from']) && !empty($filters['date_from'])) {
                $sql .= " AND payment_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            
            if (isset($filters['date_to']) && !empty($filters['date_to'])) {
                $sql .= " AND payment_date <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }
            
            if (isset($filters['search']) && !empty($filters['search'])) {
                $sql .= " AND (category LIKE :search OR description LIKE :search OR vendor LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            // Client filter
            if (isset($filters['client_id']) && !empty($filters['client_id'])) {
                $sql .= " AND client_id = :client_id";
                $params[':client_id'] = $filters['client_id'];
            }
            
            // Service filter
            if (isset($filters['service_id']) && !empty($filters['service_id'])) {
                $sql .= " AND service_id = :service_id";
                $params[':service_id'] = $filters['service_id'];
            }
            
            // Client service cost filter
            if (isset($filters['is_client_service_cost']) && $filters['is_client_service_cost'] !== '') {
                $sql .= " AND is_client_service_cost = :is_client_service_cost";
                $params[':is_client_service_cost'] = $filters['is_client_service_cost'] ? 1 : 0;
            }
            
            // Order by
            $orderBy = $filters['order_by'] ?? 'payment_date';
            $orderDir = $filters['order_dir'] ?? 'DESC';
            // Sanitize order_by to prevent SQL injection
            $allowedOrderBy = ['payment_date', 'amount', 'created_at', 'expense_type', 'category'];
            if (!in_array($orderBy, $allowedOrderBy)) {
                $orderBy = 'payment_date';
            }
            $sql .= " ORDER BY {$orderBy} {$orderDir}";
            
            // Limit
            if (isset($filters['limit']) && $filters['limit'] > 0) {
                $sql .= " LIMIT :limit";
                $params[':limit'] = $filters['limit'];
                
                if (isset($filters['offset']) && $filters['offset'] > 0) {
                    $sql .= " OFFSET :offset";
                    $params[':offset'] = $filters['offset'];
                }
            }
            
            $stmt = $this->db->prepare($sql);
            
            // Bind all parameters
            foreach ($params as $key => $value) {
                if ($key === ':limit' || $key === ':offset') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            
            // Execute without passing params again (already bound)
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching expenses: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total count of expenses
     */
    public function getTotalCount($filters = []) {
        try {
            $sql = "SELECT COUNT(*) as total FROM expenses WHERE 1=1";
            $params = [];
            
            if (isset($filters['expense_type']) && !empty($filters['expense_type'])) {
                $sql .= " AND expense_type = :expense_type";
                $params[':expense_type'] = $filters['expense_type'];
            }
            
            // Handle status filter - exclude cancelled by default unless explicitly requested
            if (isset($filters['status']) && !empty($filters['status'])) {
                $sql .= " AND status = :status";
                $params[':status'] = $filters['status'];
            } else {
                // Exclude cancelled expenses by default
                $sql .= " AND status != 'cancelled'";
            }
            
            if (isset($filters['date_from']) && !empty($filters['date_from'])) {
                $sql .= " AND DATE(payment_date) >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            
            if (isset($filters['date_to']) && !empty($filters['date_to'])) {
                $sql .= " AND DATE(payment_date) <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }
            
            if (isset($filters['search']) && !empty($filters['search'])) {
                $sql .= " AND (category LIKE :search OR description LIKE :search OR vendor LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            // Client filter
            if (isset($filters['client_id']) && !empty($filters['client_id'])) {
                $sql .= " AND client_id = :client_id";
                $params[':client_id'] = $filters['client_id'];
            }
            
            // Service filter
            if (isset($filters['service_id']) && !empty($filters['service_id'])) {
                $sql .= " AND service_id = :service_id";
                $params[':service_id'] = $filters['service_id'];
            }
            
            // Client service cost filter
            if (isset($filters['is_client_service_cost']) && $filters['is_client_service_cost'] !== '') {
                $sql .= " AND is_client_service_cost = :is_client_service_cost";
                $params[':is_client_service_cost'] = $filters['is_client_service_cost'] ? 1 : 0;
            }
            
            // Billing cycle filter
            if (isset($filters['billing_cycle']) && !empty($filters['billing_cycle'])) {
                $sql .= " AND billing_cycle = :billing_cycle";
                $params[':billing_cycle'] = $filters['billing_cycle'];
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
            
        } catch (PDOException $e) {
            error_log("Error counting expenses: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get monthly expenses
     */
    public function getMonthlyExpenses($year = null, $month = null) {
        try {
            if (!$year) $year = date('Y');
            if (!$month) $month = date('m');
            
            $sql = "SELECT COALESCE(SUM(amount), 0) as total 
                    FROM expenses 
                    WHERE status = 'paid'
                    AND MONTH(payment_date) = :month 
                    AND YEAR(payment_date) = :year";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':month' => $month,
                ':year' => $year
            ]);
            
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
            
        } catch (PDOException $e) {
            error_log("Error calculating monthly expenses: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get expenses by month for chart (last 6 months)
     */
    public function getExpensesByMonth($months = 6) {
        try {
            $sql = "SELECT 
                        YEAR(payment_date) as year,
                        MONTH(payment_date) as month,
                        COALESCE(SUM(amount), 0) as total
                    FROM expenses 
                    WHERE status = 'paid' 
                    AND payment_date >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                    GROUP BY YEAR(payment_date), MONTH(payment_date)
                    ORDER BY year ASC, month ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':months' => $months]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error getting expenses by month: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get monthly expenses for a full year (Jan to Dec)
     */
    public function getMonthlyExpensesForYear($year) {
        try {
            $sql = "SELECT 
                        MONTH(payment_date) as month,
                        COALESCE(SUM(amount), 0) as total
                    FROM expenses 
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
            error_log("Error getting yearly expenses: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get expenses by type
     */
    public function getExpensesByType($year = null, $month = null) {
        try {
            if (!$year) $year = date('Y');
            if (!$month) $month = date('m');
            
            $sql = "SELECT 
                        expense_type,
                        COALESCE(SUM(amount), 0) as total,
                        COUNT(*) as count
                    FROM expenses 
                    WHERE status = 'paid'
                    AND MONTH(payment_date) = :month 
                    AND YEAR(payment_date) = :year
                    GROUP BY expense_type
                    ORDER BY total DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':month' => $month,
                ':year' => $year
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error getting expenses by type: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Delete an expense (soft delete by setting status to cancelled)
     */
    public function deleteExpense($id) {
        try {
            $sql = "UPDATE expenses SET status = 'cancelled' WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Error deleting expense: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get expenses by client ID
     */
    public function getExpensesByClient($clientId, $filters = []) {
        $filters['client_id'] = $clientId;
        $filters['is_client_service_cost'] = true;
        return $this->getAllExpenses($filters);
    }
    
    /**
     * Get expenses by service ID
     */
    public function getExpensesByService($serviceId, $filters = []) {
        $filters['service_id'] = $serviceId;
        $filters['is_client_service_cost'] = true;
        return $this->getAllExpenses($filters);
    }
    
    /**
     * Get total costs for a client
     */
    public function getTotalClientCosts($clientId) {
        try {
            $sql = "SELECT 
                    COALESCE(SUM(amount), 0) as total_cost,
                    COUNT(*) as total_count
                    FROM expenses 
                    WHERE client_id = :client_id 
                    AND is_client_service_cost = TRUE
                    AND status != 'cancelled'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':client_id' => $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'total_cost' => floatval($result['total_cost'] ?? 0),
                'total_count' => intval($result['total_count'] ?? 0)
            ];
        } catch (PDOException $e) {
            error_log("Error getting total client costs: " . $e->getMessage());
            return ['total_cost' => 0, 'total_count' => 0];
        }
    }
    
    /**
     * Get total costs for a service
     */
    public function getTotalServiceCosts($serviceId) {
        try {
            $sql = "SELECT 
                    COALESCE(SUM(amount), 0) as total_cost,
                    COUNT(*) as total_count
                    FROM expenses 
                    WHERE service_id = :service_id 
                    AND is_client_service_cost = TRUE
                    AND status != 'cancelled'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':service_id' => $serviceId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'total_cost' => floatval($result['total_cost'] ?? 0),
                'total_count' => intval($result['total_count'] ?? 0)
            ];
        } catch (PDOException $e) {
            error_log("Error getting total service costs: " . $e->getMessage());
            return ['total_cost' => 0, 'total_count' => 0];
        }
    }
    
    /**
     * Get expenses with client and service information
     */
    public function getExpensesWithClientService($filters = []) {
        try {
            // Check if client_id and service_id columns exist
            $hasClientId = $this->columnExists('client_id');
            $hasServiceId = $this->columnExists('service_id');
            
            // Build SELECT with conditional JOINs
            $sql = "SELECT e.*";
            
            if ($hasClientId) {
                $sql .= ", c.company_name, c.client_number";
            } else {
                $sql .= ", NULL as company_name, NULL as client_number";
            }
            
            if ($hasServiceId) {
                $sql .= ", s.service_name";
            } else {
                $sql .= ", NULL as service_name";
            }
            
            $sql .= " FROM expenses e";
            
            if ($hasClientId) {
                $sql .= " LEFT JOIN clients c ON e.client_id = c.id";
            }
            
            if ($hasServiceId) {
                $sql .= " LEFT JOIN services s ON e.service_id = s.id";
            }
            
            $sql .= " WHERE 1=1";
            $params = [];
            
            // Client filter (only if column exists)
            if ($hasClientId && isset($filters['client_id']) && !empty($filters['client_id'])) {
                $sql .= " AND e.client_id = :client_id";
                $params[':client_id'] = $filters['client_id'];
            }
            
            // Service filter (only if column exists)
            if ($hasServiceId && isset($filters['service_id']) && !empty($filters['service_id'])) {
                $sql .= " AND e.service_id = :service_id";
                $params[':service_id'] = $filters['service_id'];
            }
            
            // Client service cost filter (only if column exists)
            if ($this->columnExists('is_client_service_cost') && isset($filters['is_client_service_cost'])) {
                $sql .= " AND e.is_client_service_cost = :is_client_service_cost";
                $params[':is_client_service_cost'] = $filters['is_client_service_cost'] ? 1 : 0;
            }
            
            // Expense type filter
            if (isset($filters['expense_type']) && !empty($filters['expense_type'])) {
                $sql .= " AND e.expense_type = :expense_type";
                $params[':expense_type'] = $filters['expense_type'];
            }
            
            // Billing cycle filter
            if (isset($filters['billing_cycle']) && !empty($filters['billing_cycle'])) {
                $sql .= " AND e.billing_cycle = :billing_cycle";
                $params[':billing_cycle'] = $filters['billing_cycle'];
            }
            
            // Date range filters
            if (isset($filters['date_from']) && !empty($filters['date_from'])) {
                $sql .= " AND DATE(e.payment_date) >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            if (isset($filters['date_to']) && !empty($filters['date_to'])) {
                $sql .= " AND DATE(e.payment_date) <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }
            
            // Search filter
            if (isset($filters['search']) && !empty($filters['search'])) {
                $searchTerm = '%' . $filters['search'] . '%';
                $sql .= " AND (e.expense_number LIKE :search OR e.category LIKE :search OR e.description LIKE :search OR e.vendor LIKE :search OR e.invoice_number LIKE :search)";
                $params[':search'] = $searchTerm;
            }
            
            // Status filter
            if (isset($filters['status']) && !empty($filters['status'])) {
                $sql .= " AND e.status = :status";
                $params[':status'] = $filters['status'];
            } else {
                $sql .= " AND e.status != 'cancelled'";
            }
            
            // Order by - sanitize to prevent SQL injection
            $orderBy = $filters['order_by'] ?? 'e.payment_date';
            // Remove any potential SQL injection attempts
            $orderBy = preg_replace('/[^a-zA-Z0-9_.]/', '', $orderBy);
            // Ensure it starts with 'e.' for expense table
            if (strpos($orderBy, 'e.') !== 0 && strpos($orderBy, 'payment_date') !== false) {
                $orderBy = 'e.payment_date';
            } elseif (strpos($orderBy, 'e.') !== 0) {
                $orderBy = 'e.' . $orderBy;
            }
            $orderDir = strtoupper($filters['order_dir'] ?? 'DESC');
            if ($orderDir !== 'ASC' && $orderDir !== 'DESC') {
                $orderDir = 'DESC';
            }
            $sql .= " ORDER BY {$orderBy} {$orderDir}";
            
            // Limit - use direct values instead of bindValue for LIMIT/OFFSET
            if (isset($filters['limit']) && $filters['limit'] > 0) {
                $limit = intval($filters['limit']);
                $offset = isset($filters['offset']) && $filters['offset'] >= 0 ? intval($filters['offset']) : 0;
                $sql .= " LIMIT {$limit} OFFSET {$offset}";
            }
            
            $stmt = $this->db->prepare($sql);
            
            // Bind parameters (excluding limit/offset which are now in SQL directly)
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            // Execute query
            try {
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("Error executing getExpensesWithClientService query: " . $e->getMessage());
                error_log("SQL: " . $sql);
                error_log("Params: " . print_r($params, true));
                if ($stmt) {
                    error_log("Error Info: " . print_r($stmt->errorInfo(), true));
                }
                return [];
            }
            
            // Debug logging (always log when no results to help diagnose)
            if (empty($results)) {
                error_log("=== getExpensesWithClientService - No results found ===");
                error_log("SQL: " . $sql);
                error_log("Params: " . print_r($params, true));
                error_log("Has client_id column: " . ($hasClientId ? 'yes' : 'no'));
                error_log("Has service_id column: " . ($hasServiceId ? 'yes' : 'no'));
                
                // Try a simpler query to see if there are any expenses at all
                $testSql = "SELECT COUNT(*) as count FROM expenses WHERE status != 'cancelled'";
                $testStmt = $this->db->prepare($testSql);
                $testStmt->execute();
                $testResult = $testStmt->fetch();
                error_log("Total expenses (non-cancelled): " . ($testResult['count'] ?? 0));
                
                // Try query without date filters
                if (isset($filters['date_from']) || isset($filters['date_to'])) {
                    $testSql2 = "SELECT COUNT(*) as count FROM expenses WHERE status != 'cancelled'";
                    if (isset($filters['date_from'])) {
                        $testSql2 .= " AND DATE(payment_date) >= '" . $filters['date_from'] . "'";
                    }
                    if (isset($filters['date_to'])) {
                        $testSql2 .= " AND DATE(payment_date) <= '" . $filters['date_to'] . "'";
                    }
                    $testStmt2 = $this->db->prepare($testSql2);
                    $testStmt2->execute();
                    $testResult2 = $testStmt2->fetch();
                    error_log("Total expenses with date filters (direct query): " . ($testResult2['count'] ?? 0));
                    
                    // Check actual payment dates
                    $checkSql = "SELECT id, expense_number, payment_date, status FROM expenses WHERE status != 'cancelled' ORDER BY payment_date DESC LIMIT 5";
                    $checkStmt = $this->db->prepare($checkSql);
                    $checkStmt->execute();
                    $checkResults = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
                    error_log("Sample expenses (last 5): " . print_r($checkResults, true));
                }
            }
            
            return $results;
            
        } catch (PDOException $e) {
            error_log("Error getting expenses with client/service: " . $e->getMessage());
            error_log("SQL: " . $sql);
            error_log("Params: " . print_r($params, true));
            error_log("Stack trace: " . $e->getTraceAsString());
            return [];
        }
    }
}

