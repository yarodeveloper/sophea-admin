<?php
/**
 * SOPHEA - Service Management Class
 * 
 * Handles all service operations (CRUD)
 */

require_once __DIR__ . '/Database.php';

class Service {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new service
     */
    public function createService($data) {
        try {
            $sql = "INSERT INTO services 
                    (client_id, quote_id, service_type, service_name, description, project_description,
                     monthly_fee, setup_fee, billing_cycle, start_date, end_date, renewal_date,
                     progress_percentage, status, project_url, legal_coverage, 
                     is_ads_service, initial_investment_amount, created_by,
                     is_recurring, renewal_mode, base_service_id, period_number) 
                    VALUES 
                    (:client_id, :quote_id, :service_type, :service_name, :description, :project_description,
                     :monthly_fee, :setup_fee, :billing_cycle, :start_date, :end_date, :renewal_date,
                     :progress_percentage, :status, :project_url, :legal_coverage,
                     :is_ads_service, :initial_investment_amount, :created_by,
                     :is_recurring, :renewal_mode, :base_service_id, :period_number)";
            
            $stmt = $this->db->prepare($sql);
            
            $result = $stmt->execute([
                ':client_id' => $data['client_id'],
                ':quote_id' => $data['quote_id'] ?? null,
                ':service_type' => $data['service_type'],
                ':service_name' => $data['service_name'],
                ':description' => $data['description'] ?? null,
                ':project_description' => $data['project_description'] ?? null,
                ':monthly_fee' => $data['monthly_fee'] ?? 0.00,
                ':setup_fee' => $data['setup_fee'] ?? 0.00,
                ':billing_cycle' => $data['billing_cycle'] ?? 'monthly',
                ':start_date' => $data['start_date'],
                ':end_date' => $data['end_date'] ?? null,
                ':renewal_date' => $data['renewal_date'] ?? null,
                ':progress_percentage' => $data['progress_percentage'] ?? 0,
                ':status' => $data['status'] ?? 'active',
                ':project_url' => $data['project_url'] ?? null,
                ':legal_coverage' => $data['legal_coverage'] ?? null,
                ':is_ads_service' => isset($data['is_ads_service']) ? ($data['is_ads_service'] ? 1 : 0) : 0,
                ':initial_investment_amount' => $data['initial_investment_amount'] ?? 0.00,
                ':created_by' => $data['created_by'] ?? null,
                ':is_recurring' => isset($data['is_recurring']) ? ($data['is_recurring'] ? 1 : 0) : 0,
                ':renewal_mode' => $data['renewal_mode'] ?? 'manual',
                ':base_service_id' => $data['base_service_id'] ?? null,
                ':period_number' => $data['period_number'] ?? 1
            ]);
            
            if ($result) {
                $serviceId = $this->db->lastInsertId();
                
                // Add tasks if provided
                if (isset($data['tasks']) && is_array($data['tasks'])) {
                    $this->addServiceTasks($serviceId, $data['tasks']);
                }
                
                return $serviceId;
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Error creating service: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update a service
     */
    public function updateService($id, $data) {
        try {
            $sql = "UPDATE services SET 
                    service_type = :service_type,
                    service_name = :service_name,
                    description = :description,
                    project_description = :project_description,
                    monthly_fee = :monthly_fee,
                    setup_fee = :setup_fee,
                    billing_cycle = :billing_cycle,
                    start_date = :start_date,
                    end_date = :end_date,
                    renewal_date = :renewal_date,
                    progress_percentage = :progress_percentage,
                    status = :status,
                    project_url = :project_url,
                    legal_coverage = :legal_coverage,
                    is_ads_service = :is_ads_service,
                    initial_investment_amount = :initial_investment_amount,
                    is_recurring = :is_recurring,
                    renewal_mode = :renewal_mode,
                    base_service_id = :base_service_id,
                    period_number = :period_number
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([
                ':id' => $id,
                ':service_type' => $data['service_type'],
                ':service_name' => $data['service_name'],
                ':description' => $data['description'] ?? null,
                ':project_description' => $data['project_description'] ?? null,
                ':monthly_fee' => $data['monthly_fee'] ?? 0.00,
                ':setup_fee' => $data['setup_fee'] ?? 0.00,
                ':billing_cycle' => $data['billing_cycle'] ?? 'monthly',
                ':start_date' => $data['start_date'],
                ':end_date' => $data['end_date'] ?? null,
                ':renewal_date' => $data['renewal_date'] ?? null,
                ':progress_percentage' => $data['progress_percentage'] ?? 0,
                ':status' => $data['status'] ?? 'active',
                ':project_url' => $data['project_url'] ?? null,
                ':legal_coverage' => $data['legal_coverage'] ?? null,
                ':is_ads_service' => isset($data['is_ads_service']) ? ($data['is_ads_service'] ? 1 : 0) : 0,
                ':initial_investment_amount' => $data['initial_investment_amount'] ?? 0.00,
                ':is_recurring' => isset($data['is_recurring']) ? ($data['is_recurring'] ? 1 : 0) : 0,
                ':renewal_mode' => $data['renewal_mode'] ?? 'manual',
                ':base_service_id' => $data['base_service_id'] ?? null,
                ':period_number' => $data['period_number'] ?? 1
            ]);
            
        } catch (PDOException $e) {
            error_log("Error updating service: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }
    
    public $lastError = '';
    
    /**
     * Get service by ID
     */
    public function getServiceById($id) {
        try {
            $sql = "SELECT s.*, c.company_name, c.client_number 
                    FROM services s
                    INNER JOIN clients c ON s.client_id = c.id
                    WHERE s.id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $service = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($service) {
                $service['tasks'] = $this->getServiceTasks($id);
            }
            
            return $service;
            
        } catch (PDOException $e) {
            error_log("Error fetching service: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all services with optional filters
     */
    public function getAllServices($filters = []) {
        try {
            $sql = "SELECT s.*, c.company_name, c.client_number, c.logo_url 
                    FROM services s
                    INNER JOIN clients c ON s.client_id = c.id
                    WHERE 1=1";
            $params = [];
            
            // Status filter
            if (isset($filters['status']) && !empty($filters['status'])) {
                $sql .= " AND s.status = :status";
                $params[':status'] = $filters['status'];
            }
            
            // Client filter
            if (isset($filters['client_id']) && !empty($filters['client_id'])) {
                $sql .= " AND s.client_id = :client_id";
                $params[':client_id'] = $filters['client_id'];
            }
            
            // Service type filter
            if (isset($filters['service_type']) && !empty($filters['service_type'])) {
                $sql .= " AND s.service_type = :service_type";
                $params[':service_type'] = $filters['service_type'];
            }
            
            // is_ads_service filter
            if (isset($filters['is_ads_service']) && $filters['is_ads_service'] === true) {
                $sql .= " AND s.is_ads_service = 1";
            }
            
            // Order by
            $orderBy = $filters['order_by'] ?? 's.created_at';
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
            error_log("Error fetching services: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get services by client ID
     */
    public function getServicesByClient($clientId, $status = 'active') {
        return $this->getAllServices([
            'client_id' => $clientId,
            'status' => $status
        ]);
    }
    
    /**
     * Add tasks to a service
     */
    public function addServiceTasks($serviceId, $tasks) {
        try {
            $sql = "INSERT INTO service_tasks (service_id, task_name, task_description, is_completed, due_date, display_order) 
                    VALUES (:service_id, :task_name, :task_description, :is_completed, :due_date, :display_order)";
            $stmt = $this->db->prepare($sql);
            
            $order = 0;
            foreach ($tasks as $task) {
                $stmt->execute([
                    ':service_id' => $serviceId,
                    ':task_name' => $task['task_name'],
                    ':task_description' => $task['task_description'] ?? null,
                    ':is_completed' => $task['is_completed'] ?? false,
                    ':due_date' => $task['due_date'] ?? null,
                    ':display_order' => $order++
                ]);
            }
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Error adding service tasks: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get service tasks
     */
    public function getServiceTasks($serviceId) {
        try {
            $sql = "SELECT * FROM service_tasks WHERE service_id = :service_id ORDER BY display_order ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':service_id' => $serviceId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching service tasks: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update task completion status
     */
    public function updateTaskStatus($taskId, $isCompleted) {
        try {
            $sql = "UPDATE service_tasks SET 
                    is_completed = :is_completed,
                    completed_at = " . ($isCompleted ? "NOW()" : "NULL") . "
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $taskId,
                ':is_completed' => $isCompleted ? 1 : 0
            ]);
            
        } catch (PDOException $e) {
            error_log("Error updating task status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update service progress
     */
    public function updateProgress($id, $percentage) {
        try {
            $sql = "UPDATE services SET progress_percentage = :percentage WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':percentage' => max(0, min(100, $percentage))
            ]);
        } catch (PDOException $e) {
            error_log("Error updating service progress: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get average progress of all active projects
     */
    public function getAverageProgress() {
        try {
            $sql = "SELECT 
                        COALESCE(AVG(progress_percentage), 0) as avg_progress,
                        COUNT(*) as total_projects
                    FROM services 
                    WHERE status = 'active' 
                    AND progress_percentage IS NOT NULL";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            $result = $stmt->fetch();
            return [
                'average' => round($result['avg_progress'] ?? 0, 1),
                'total' => $result['total_projects'] ?? 0
            ];
            
        } catch (PDOException $e) {
            error_log("Error calculating average progress: " . $e->getMessage());
            return ['average' => 0, 'total' => 0];
        }
    }
    
    /**
     * Get active services count
     */
    public function getActiveCount($clientId = null) {
        try {
            $sql = "SELECT COUNT(*) as count FROM services WHERE status = 'active'";
            $params = [];
            
            if ($clientId) {
                $sql .= " AND client_id = :client_id";
                $params[':client_id'] = $clientId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
            
        } catch (PDOException $e) {
            error_log("Error counting active services: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get services expiring soon
     */
    public function getServicesExpiringSoon($days = 30) {
        try {
            $sql = "SELECT s.*, c.company_name 
                    FROM services s
                    INNER JOIN clients c ON s.client_id = c.id
                    WHERE s.status = 'active' 
                    AND s.renewal_date IS NOT NULL
                    AND s.renewal_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
                    ORDER BY s.renewal_date ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':days' => $days]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching expiring services: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Delete a service
     */
    public function deleteService($id) {
        try {
            $sql = "UPDATE services SET status = 'cancelled' WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Error deleting service: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get total expected from active services for a client
     */
    public function getTotalExpectedFromServices($clientId) {
        try {
            $sql = "SELECT COALESCE(SUM(monthly_fee), 0) as total 
                    FROM services 
                    WHERE client_id = :client_id AND status = 'active'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':client_id' => $clientId]);
            
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
            
        } catch (PDOException $e) {
            error_log("Error calculating total expected from services: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get total expected income from all active services (monthly)
     */
    public function getTotalExpectedIncome() {
        try {
            $sql = "SELECT COALESCE(SUM(monthly_fee), 0) as total 
                    FROM services 
                    WHERE status = 'active'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
            
        } catch (PDOException $e) {
            error_log("Error calculating total expected income: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Check if a service already exists for a quote_id
     * Returns the service ID if exists, false otherwise
     */
    public function getServiceByQuoteId($quoteId) {
        try {
            $sql = "SELECT id, service_name, status 
                    FROM services 
                    WHERE quote_id = :quote_id 
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':quote_id' => $quoteId]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result : false;
            
        } catch (PDOException $e) {
            error_log("Error checking service by quote_id: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get services with payment summary for a client
     */
    public function getServicesWithPaymentSummary($clientId) {
        try {
            $sql = "SELECT s.*,
                    COALESCE(SUM(CASE WHEN p.status = 'paid' THEN p.amount ELSE 0 END), 0) as total_paid,
                    COALESCE(SUM(CASE WHEN p.status IN ('pending', 'overdue') THEN p.amount ELSE 0 END), 0) as total_pending,
                    COUNT(p.id) as payments_count
                    FROM services s
                    LEFT JOIN payments p ON s.id = p.service_id
                    WHERE s.client_id = :client_id AND s.status = 'active'
                    GROUP BY s.id
                    ORDER BY s.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':client_id' => $clientId]);
            
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate pending balance for each service
            foreach ($services as &$service) {
                $service['total_paid'] = floatval($service['total_paid']);
                $service['total_pending'] = floatval($service['total_pending']);
                $service['monthly_fee'] = floatval($service['monthly_fee']);
                $service['pending_balance'] = $service['monthly_fee'] - $service['total_paid'] - $service['total_pending'];
            }
            
            return $services;
            
        } catch (PDOException $e) {
            error_log("Error fetching services with payment summary: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if service is Ads service (with third-party investment)
     */
    public function isAdsService($serviceId) {
        try {
            $sql = "SELECT is_ads_service FROM services WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $serviceId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result && $result['is_ads_service'] == 1;
            
        } catch (PDOException $e) {
            error_log("Error checking if service is Ads: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get Ads services for a client
     */
    public function getAdsServicesByClient($clientId) {
        try {
            return $this->getAllServices([
                'client_id' => $clientId,
                'is_ads_service' => true,
                'status' => 'active'
            ]);
        } catch (Exception $e) {
            error_log("Error getting Ads services: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Process all services that due for automatic renewal
     * Returns count of renewed services
     */
    public function autoRenewDueServices() {
        try {
            // Get active recurring services with automatic renewal that expired or expire today
            $sql = "SELECT id FROM services 
                    WHERE status = 'active' 
                    AND is_recurring = 1 
                    AND renewal_mode = 'automatic' 
                    AND renewal_date <= CURDATE()";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $dueServices = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $count = 0;
            foreach ($dueServices as $svc) {
                if ($this->renewService($svc['id'])) {
                    // Update old service to 'completed' or 'archived'? 
                    // Usually we set the old to 'completed' so it doesn't show as active
                    $updateSql = "UPDATE services SET status = 'completed' WHERE id = :id";
                    $updateStmt = $this->db->prepare($updateSql);
                    $updateStmt->execute([':id' => $svc['id']]);
                    $count++;
                }
            }
            return $count;
        } catch (Exception $e) {
            error_log("Error in autoRenewDueServices: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Renew a service for a new period
     */
    public function renewService($serviceId) {
        try {
            $currentSvc = $this->getServiceById($serviceId);
            if (!$currentSvc) return false;

            // Prepare data for new period
            $newData = $currentSvc;
            unset($newData['id']);
            unset($newData['created_at']);
            unset($newData['updated_at']);
            
            // Step 3: Copy tasks as templates for the new month
            $oldTasks = $this->getServiceTasks($serviceId);
            $newTasks = [];
            foreach ($oldTasks as $task) {
                // Copy task properties but reset status
                $newTasks[] = [
                    'task_name' => $task['task_name'],
                    'task_description' => $task['task_description'],
                    'display_order' => $task['display_order'],
                    'is_completed' => 0
                ];
            }
            $newData['tasks'] = $newTasks;

            // Update dates for next month (or billing cycle)
            $interval = '+1 month';
            if (($currentSvc['billing_cycle'] ?? 'monthly') === 'quarterly') $interval = '+3 months';
            if (($currentSvc['billing_cycle'] ?? 'monthly') === 'yearly') $interval = '+12 months';

            $startDate = new DateTime($currentSvc['renewal_date'] ?? $currentSvc['start_date']);
            $newData['start_date'] = $startDate->format('Y-m-d');
            
            $startDate->modify($interval);
            $newData['renewal_date'] = $startDate->format('Y-m-d');
            
            if ($currentSvc['end_date']) {
                $endDate = new DateTime($currentSvc['end_date']);
                $endDate->modify($interval);
                $newData['end_date'] = $endDate->format('Y-m-d');
            }

            // Update recurrence info
            $newData['base_service_id'] = $currentSvc['base_service_id'] ?? $currentSvc['id'];
            $newData['period_number'] = ($currentSvc['period_number'] ?? 1) + 1;
            $newData['progress_percentage'] = 0;
            
            // Set status
            $newData['status'] = 'active';

            // Adjust service name to reflect period
            if (preg_match('/ - Mes \d+$/', $newData['service_name'])) {
                $newData['service_name'] = preg_replace('/ - Mes \d+$/', ' - Mes ' . $newData['period_number'], $newData['service_name']);
            } else {
                $newData['service_name'] .= ' - Mes ' . $newData['period_number'];
            }

            $newServiceId = $this->createService($newData);

            // Step 1: Automatic Payment Generation
            if ($newServiceId) {
                // We need Payment class. Using require_once inside method to avoid circular dependency
                require_once __DIR__ . '/Payment.php';
                $payment = new Payment();
                
                $paymentData = [
                    'client_id' => $currentSvc['client_id'],
                    'service_id' => $newServiceId,
                    'quote_id' => $currentSvc['quote_id'] ?? null,
                    'amount' => $currentSvc['monthly_fee'],
                    'currency' => $currentSvc['currency'] ?? 'MXN',
                    'payment_date' => date('Y-m-d'), // Emission date
                    'due_date' => $newData['start_date'], // Due on start of period
                    'payment_method' => 'transfer',
                    'notes' => 'Generado automáticamente por renovación de servicio: ' . $newData['service_name'],
                    'created_by' => $currentSvc['created_by']
                ];
                
                $payment->createPayment($paymentData);
            }

            return $newServiceId;

        } catch (Exception $e) {
            error_log("Error renewing service: " . $e->getMessage());
            return false;
        }
    }
}

