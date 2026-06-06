<?php
/**
 * SOPHEA - Client Management Class
 * 
 * Handles all client operations (CRUD)
 */

require_once __DIR__ . '/Database.php';

class Client {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Generate unique client number (C-YYYY-XXX)
     */
    public function generateClientNumber() {
        try {
            $year = date('Y');
            
            // Try stored procedure first (if it exists)
            // DISABLED TEMPORARILY - Using direct query instead
            /*
            try {
                $stmt = $this->db->prepare("CALL sp_generate_client_number(@client_num)");
                $stmt->execute();
                $stmt->closeCursor();
                
                $stmt = $this->db->query("SELECT @client_num as client_number");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result && !empty($result['client_number']) && preg_match('/^C-\d{4}-\d{3}$/', $result['client_number'])) {
                    // Verify it doesn't exist
                    $checkStmt = $this->db->prepare("SELECT id FROM clients WHERE client_number = ?");
                    $checkStmt->execute([$result['client_number']]);
                    if ($checkStmt->rowCount() === 0) {
                        return $result['client_number'];
                    }
                    // If exists, continue to fallback
                }
            } catch (PDOException $e) {
                // Stored procedure doesn't exist or failed, use fallback
                error_log("Stored procedure failed, using fallback: " . $e->getMessage());
            }
            */
            
            // Fallback: Get max sequence number for current year
            // Use SUBSTRING_INDEX to extract the last part (sequence number) after the last dash
            $stmt = $this->db->prepare("
                SELECT client_number,
                       CAST(SUBSTRING_INDEX(client_number, '-', -1) AS UNSIGNED) as sequence_num
                FROM clients 
                WHERE client_number LIKE ? 
                  AND client_number REGEXP '^C-[0-9]{4}-[0-9]{3}$'
                ORDER BY sequence_num DESC 
                LIMIT 1
            ");
            $stmt->execute(["C-{$year}-%"]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $sequence = 1;
            if ($result && !empty($result['client_number'])) {
                // Use the extracted sequence number
                if (isset($result['sequence_num']) && is_numeric($result['sequence_num']) && $result['sequence_num'] < 1000) {
                    $sequence = intval($result['sequence_num']) + 1;
                } else {
                    // Fallback: Extract manually if CAST didn't work or returned invalid value
                    $parts = explode('-', $result['client_number']);
                    if (count($parts) === 3 && is_numeric($parts[2])) {
                        $sequence = intval($parts[2]) + 1;
                    }
                }
            } else {
                // No clients found for this year, check if there are any clients at all
                // to ensure we're starting from 1
                $checkStmt = $this->db->prepare("SELECT COUNT(*) as count FROM clients WHERE client_number LIKE ?");
                $checkStmt->execute(["C-{$year}-%"]);
                $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
                if ($checkResult && $checkResult['count'] > 0) {
                    // There are clients but our query didn't find them (format issue)
                    // Try to find any client number for this year and extract manually
                    $manualStmt = $this->db->prepare("
                        SELECT client_number 
                        FROM clients 
                        WHERE client_number LIKE ? 
                        ORDER BY id DESC 
                        LIMIT 10
                    ");
                    $manualStmt->execute(["C-{$year}-%"]);
                    $manualResults = $manualStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $maxSeq = 0;
                    foreach ($manualResults as $row) {
                        $parts = explode('-', $row['client_number']);
                        if (count($parts) === 3 && is_numeric($parts[2])) {
                            $seq = intval($parts[2]);
                            if ($seq > $maxSeq) {
                                $maxSeq = $seq;
                            }
                        }
                    }
                    if ($maxSeq > 0) {
                        $sequence = $maxSeq + 1;
                    }
                }
            }
            
            // Ensure sequence is within valid range (1-999)
            if ($sequence < 1) $sequence = 1;
            if ($sequence > 999) {
                error_log("Warning: Client sequence exceeded 999 for year {$year}, resetting to 1");
                $sequence = 1;
            }
            
            $newClientNumber = "C-{$year}-" . str_pad($sequence, 3, '0', STR_PAD_LEFT);
            
            // Double-check that this number doesn't already exist (safety check)
            $verifyStmt = $this->db->prepare("SELECT id FROM clients WHERE client_number = ?");
            $verifyStmt->execute([$newClientNumber]);
            $exists = $verifyStmt->rowCount() > 0;
            
            if ($exists) {
                // Number exists, find the next available one
                $attempts = 0;
                $maxAttempts = 100; // Check up to 100 numbers
                
                while ($exists && $attempts < $maxAttempts) {
                    $sequence++;
                    if ($sequence > 999) {
                        error_log("Warning: Sequence exceeded 999 for year {$year}, cannot generate more numbers");
                        break;
                    }
                    
                    $newClientNumber = "C-{$year}-" . str_pad($sequence, 3, '0', STR_PAD_LEFT);
                    $verifyStmt->execute([$newClientNumber]);
                    $exists = $verifyStmt->rowCount() > 0;
                    $attempts++;
                }
                
                if ($attempts >= $maxAttempts) {
                    error_log("Error: Could not find available client number after {$maxAttempts} attempts for year {$year}");
                    // Fallback: use timestamp-based number
                    $timestamp = time();
                    $random = rand(100, 999);
                    $newClientNumber = "C-{$year}-" . str_pad($random, 3, '0', STR_PAD_LEFT);
                }
            }
            
            return $newClientNumber;
            
        } catch (PDOException $e) {
            error_log("Error generating client number: " . $e->getMessage());
            // Simple fallback with timestamp to ensure uniqueness
            $timestamp = time();
            $random = rand(1, 99);
            $sequence = ($timestamp % 900) + 100; // Ensure 3 digits
            return "C-" . date('Y') . "-" . str_pad($sequence, 3, '0', STR_PAD_LEFT);
        }
    }
    
    /**
     * Create a new client
     */
    public function createClient($data) {
        try {
            // Validate required fields
            if (empty($data['company_name']) || empty($data['contact_name']) || empty($data['email'])) {
                error_log("Error creating client: Missing required fields. company_name: " . (isset($data['company_name']) ? 'set' : 'missing') . ", contact_name: " . (isset($data['contact_name']) ? 'set' : 'missing') . ", email: " . (isset($data['email']) ? 'set' : 'missing'));
                return false;
            }
            
            // Generate client number
            $clientNumber = $this->generateClientNumber();
            
            if (empty($clientNumber) || !preg_match('/^C-\d{4}-\d{3}$/', $clientNumber)) {
                error_log("Error creating client: Invalid client number generated: " . $clientNumber);
                return false;
            }
            
            // Check if client number already exists (shouldn't happen, but safety check)
            $checkStmt = $this->db->prepare("SELECT id FROM clients WHERE client_number = ?");
            $checkStmt->execute([$clientNumber]);
            if ($checkStmt->rowCount() > 0) {
                error_log("Error creating client: Client number already exists: " . $clientNumber);
                // Try generating a new one
                $clientNumber = $this->generateClientNumber();
            }
            
            // Build phone with country code if provided
            $phone = null;
            if (!empty($data['phone'])) {
                $phoneCode = $data['phone_country_code'] ?? '+52';
                $phone = $phoneCode . trim($data['phone']);
            }
            
            // Build whatsapp with country code if provided
            $whatsapp = null;
            if (!empty($data['whatsapp'])) {
                $whatsappCode = $data['whatsapp_country_code'] ?? '+52';
                $whatsapp = $whatsappCode . trim($data['whatsapp']);
            }
            
            $sql = "INSERT INTO clients 
                    (client_number, company_name, contact_name, email, phone, whatsapp, 
                     address, city, state, country, tax_id, website, industry, 
                     client_type, legal_risk, legal_compliance, last_audit_date, 
                     status, notes, logo_url, contract_path, contract_note, created_by) 
                    VALUES 
                    (:client_number, :company_name, :contact_name, :email, :phone, :whatsapp,
                     :address, :city, :state, :country, :tax_id, :website, :industry,
                     :client_type, :legal_risk, :legal_compliance, :last_audit_date,
                     :status, :notes, :logo_url, :contract_path, :contract_note, :created_by)";
            
            $stmt = $this->db->prepare($sql);
            
            $params = [
                ':client_number' => $clientNumber,
                ':company_name' => trim($data['company_name']),
                ':contact_name' => trim($data['contact_name']),
                ':email' => trim($data['email']),
                ':phone' => $phone,
                ':whatsapp' => $whatsapp,
                ':address' => !empty($data['address']) ? trim($data['address']) : null,
                ':city' => !empty($data['city']) ? trim($data['city']) : null,
                ':state' => !empty($data['state']) ? trim($data['state']) : null,
                ':country' => !empty($data['country']) ? trim($data['country']) : 'México',
                ':tax_id' => !empty($data['tax_id']) ? trim($data['tax_id']) : null,
                ':website' => !empty($data['website']) ? trim($data['website']) : null,
                ':industry' => !empty($data['industry']) ? trim($data['industry']) : null,
                ':client_type' => $data['client_type'] ?? 'regular',
                ':legal_risk' => $data['legal_risk'] ?? 'low',
                ':legal_compliance' => isset($data['legal_compliance']) ? floatval($data['legal_compliance']) : 100.00,
                ':last_audit_date' => !empty($data['last_audit_date']) ? $data['last_audit_date'] : null,
                'status' => $data['status'] ?? 'prospect',
                ':notes' => !empty($data['notes']) ? trim($data['notes']) : null,
                ':logo_url' => !empty($data['logo_url']) ? trim($data['logo_url']) : null,
                ':contract_path' => !empty($data['contract_path']) ? trim($data['contract_path']) : null,
                ':contract_note' => !empty($data['contract_note']) ? trim($data['contract_note']) : null,
                ':created_by' => !empty($data['created_by']) ? intval($data['created_by']) : null
            ];
            
            $result = $stmt->execute($params);
            
            if ($result) {
                $clientId = $this->db->lastInsertId();
                error_log("Client created successfully: ID={$clientId}, Number={$clientNumber}");
                return $clientId;
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("Error creating client: SQL execution failed. Error: " . print_r($errorInfo, true));
                return false;
            }
            
        } catch (PDOException $e) {
            error_log("Error creating client: PDOException - " . $e->getMessage());
            error_log("Error creating client: SQL State - " . $e->getCode());
            error_log("Error creating client: Stack trace - " . $e->getTraceAsString());
            return false;
        } catch (Exception $e) {
            error_log("Error creating client: General Exception - " . $e->getMessage());
            error_log("Error creating client: Stack trace - " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Update an existing client
     */
    public function updateClient($id, $data) {
        try {
            $sql = "UPDATE clients SET 
                    company_name = :company_name,
                    contact_name = :contact_name,
                    email = :email,
                    phone = :phone,
                    phone_country_code = :phone_country_code,
                    whatsapp = :whatsapp,
                    whatsapp_country_code = :whatsapp_country_code,
                    address = :address,
                    city = :city,
                    state = :state,
                    country = :country,
                    tax_id = :tax_id,
                    website = :website,
                    industry = :industry,
                    client_type = :client_type,
                    legal_risk = :legal_risk,
                    legal_compliance = :legal_compliance,
                    last_audit_date = :last_audit_date,
                    status = :status,
                    notes = :notes,
                    logo_url = :logo_url,
                    contract_path = :contract_path,
                    contract_note = :contract_note
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([
                ':id' => $id,
                ':company_name' => $data['company_name'],
                ':contact_name' => $data['contact_name'],
                ':email' => $data['email'],
                ':phone' => $data['phone'] ?? null,
                ':phone_country_code' => $data['phone_country_code'] ?? '+52',
                ':whatsapp' => $data['whatsapp'] ?? null,
                ':whatsapp_country_code' => $data['whatsapp_country_code'] ?? '+52',
                ':address' => $data['address'] ?? null,
                ':city' => $data['city'] ?? null,
                ':state' => $data['state'] ?? null,
                ':country' => $data['country'] ?? 'México',
                ':tax_id' => $data['tax_id'] ?? null,
                ':website' => $data['website'] ?? null,
                ':industry' => $data['industry'] ?? null,
                ':client_type' => $data['client_type'] ?? 'regular',
                ':legal_risk' => $data['legal_risk'] ?? 'low',
                ':legal_compliance' => $data['legal_compliance'] ?? 100.00,
                ':last_audit_date' => $data['last_audit_date'] ?? null,
                ':status' => $data['status'] ?? 'prospect',
                ':notes' => $data['notes'] ?? null,
                ':logo_url' => $data['logo_url'] ?? null,
                ':contract_path' => $data['contract_path'] ?? null,
                ':contract_note' => $data['contract_note'] ?? null
            ]);
            
        } catch (PDOException $e) {
            error_log("Error updating client: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get client by ID
     */
    public function getClientById($id) {
        try {
            $sql = "SELECT * FROM clients WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching client: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get client by client number
     */
    public function getClientByNumber($clientNumber) {
        try {
            $sql = "SELECT * FROM clients WHERE client_number = :client_number";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':client_number' => $clientNumber]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching client: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all clients with optional filters
     */
    public function getAllClients($filters = []) {
        try {
            $sql = "SELECT c.*, 
                    (SELECT COUNT(*) FROM services s 
                     WHERE s.client_id = c.id 
                     AND s.status IN ('active', 'paused') 
                     AND (s.end_date IS NULL OR s.end_date >= CURDATE())) as active_projects_count,
                    (SELECT COUNT(*) FROM services s 
                     WHERE s.client_id = c.id 
                     AND s.status = 'active') as active_services_count,
                    (SELECT COUNT(*) FROM services s 
                     WHERE s.client_id = c.id 
                     AND s.status = 'active' 
                     AND s.end_date IS NOT NULL 
                     AND s.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 15 DAY)) as expiring_services_count,
                    (SELECT COUNT(*) FROM services s 
                     WHERE s.client_id = c.id 
                     AND s.status = 'active' 
                     AND s.end_date IS NOT NULL 
                     AND s.end_date < CURDATE()) as overdue_services_count,
                    (SELECT COUNT(*) FROM services s 
                     WHERE s.client_id = c.id 
                     AND s.status = 'completed'
                     AND EXISTS (SELECT 1 FROM payments p WHERE p.service_id = s.id AND p.status IN ('pending', 'overdue'))) as completed_with_debt_count
                    FROM clients c WHERE 1=1";
            $params = [];
            
            // Status filter
            if (isset($filters['status']) && !empty($filters['status'])) {
                $sql .= " AND c.status = :status";
                $params[':status'] = $filters['status'];
            }
            
            // Client type filter
            if (isset($filters['client_type']) && !empty($filters['client_type'])) {
                $sql .= " AND c.client_type = :client_type";
                $params[':client_type'] = $filters['client_type'];
            }
            
            // Search filter
            if (isset($filters['search']) && !empty($filters['search'])) {
                $sql .= " AND (c.company_name LIKE :search OR c.contact_name LIKE :search OR c.email LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            // Order by
            $orderBy = $filters['order_by'] ?? 'created_at';
            $orderDir = $filters['order_dir'] ?? 'DESC';
            
            // Validate order_by field to prevent SQL injection and handle alias
            $allowedOrderBy = ['created_at', 'company_name', 'contact_name', 'status', 'active_projects_count', 'active_services_count', 'expiring_services_count', 'overdue_services_count'];
            if (!in_array($orderBy, $allowedOrderBy)) {
                $orderBy = 'created_at';
            }
            
            $sql .= " ORDER BY {$orderBy} {$orderDir}, c.created_at DESC";
            
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
            error_log("Error fetching clients: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get client summary (with stats)
     */
    public function getClientSummary($clientId) {
        try {
            $sql = "SELECT * FROM v_client_summary WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $clientId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching client summary: " . $e->getMessage());
            // Fallback to manual calculation
            return $this->calculateClientSummary($clientId);
        }
    }
    
    /**
     * Calculate client summary manually (fallback)
     */
    private function calculateClientSummary($clientId) {
        try {
            $client = $this->getClientById($clientId);
            if (!$client) return null;
            
            // Get active services count
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM services WHERE client_id = :id AND status = 'active'");
            $stmt->execute([':id' => $clientId]);
            $services = $stmt->fetch();
            
            // Get quotes count
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM quotes WHERE client_id = :id");
            $stmt->execute([':id' => $clientId]);
            $quotes = $stmt->fetch();
            
            // Get pending quotes
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM quotes WHERE client_id = :id AND status IN ('sent', 'draft')");
            $stmt->execute([':id' => $clientId]);
            $pendingQuotes = $stmt->fetch();
            
            // Get total pending
            $stmt = $this->db->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE client_id = :id AND status = 'pending'");
            $stmt->execute([':id' => $clientId]);
            $pending = $stmt->fetch();
            
            // Get total paid
            $stmt = $this->db->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE client_id = :id AND status = 'paid'");
            $stmt->execute([':id' => $clientId]);
            $paid = $stmt->fetch();
            
            return [
                'id' => $client['id'],
                'client_number' => $client['client_number'],
                'company_name' => $client['company_name'],
                'status' => $client['status'],
                'client_type' => $client['client_type'],
                'active_services_count' => $services['count'] ?? 0,
                'total_quotes' => $quotes['count'] ?? 0,
                'pending_quotes' => $pendingQuotes['count'] ?? 0,
                'total_pending' => $pending['total'] ?? 0,
                'total_paid' => $paid['total'] ?? 0
            ];
            
        } catch (PDOException $e) {
            error_log("Error calculating client summary: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Delete a client (soft delete by changing status)
     */
    public function deleteClient($id) {
        try {
            $sql = "UPDATE clients SET status = 'archived' WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
            
        } catch (PDOException $e) {
            error_log("Error deleting client: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a client and all related data (PERMANENT)
     * USE WITH CAUTION
     */
    public function deleteClientPermanently($id) {
        try {
            $this->db->beginTransaction();
            
            // Delete related records that might have foreign key constraints
            // (Assumes tables are structured with CASCADE or manually delete)
            
            // Delete documents
            $stmt = $this->db->prepare("DELETE FROM client_documents WHERE client_id = :id");
            $stmt->execute([':id' => $id]);
            
            // Delete service tasks related to services of this client
            $stmt = $this->db->prepare("DELETE FROM service_tasks WHERE service_id IN (SELECT id FROM services WHERE client_id = :id)");
            $stmt->execute([':id' => $id]);
            
            // Delete payments related to services of this client
            $stmt = $this->db->prepare("DELETE FROM payments WHERE service_id IN (SELECT id FROM services WHERE client_id = :id)");
            $stmt->execute([':id' => $id]);
            
            // Delete services
            $stmt = $this->db->prepare("DELETE FROM services WHERE client_id = :id");
            $stmt->execute([':id' => $id]);
            
            // Delete quotes
            $stmt = $this->db->prepare("DELETE FROM quotes WHERE client_id = :id");
            $stmt->execute([':id' => $id]);
            
            // Finally delete client
            $stmt = $this->db->prepare("DELETE FROM clients WHERE id = :id");
            $stmt = $stmt->execute([':id' => $id]);
            
            $this->db->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error permanently deleting client: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get total count of clients
     */
    public function getTotalCount($filters = []) {
        try {
            $sql = "SELECT COUNT(*) as total FROM clients WHERE 1=1";
            $params = [];
            
            if (isset($filters['status']) && !empty($filters['status'])) {
                $sql .= " AND status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (isset($filters['search']) && !empty($filters['search'])) {
                $sql .= " AND (company_name LIKE :search OR contact_name LIKE :search OR email LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
            
        } catch (PDOException $e) {
            error_log("Error counting clients: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get new clients count for a specific month
     */
    public function getNewClientsCount($year = null, $month = null) {
        try {
            if (!$year) $year = date('Y');
            if (!$month) $month = date('m');
            
            $sql = "SELECT COUNT(*) as count 
                    FROM clients 
                    WHERE MONTH(created_at) = :month 
                    AND YEAR(created_at) = :year";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':month' => $month,
                ':year' => $year
            ]);
            
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
            
        } catch (PDOException $e) {
            error_log("Error counting new clients: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get active clients count
     */
    public function getActiveCount() {
        return $this->getTotalCount(['status' => 'active']);
    }
    
    /**
     * Get client's pending payments total
     */
    public function getPendingPaymentsTotal($clientId) {
        try {
            $sql = "SELECT COALESCE(SUM(amount), 0) as total 
                    FROM payments 
                    WHERE client_id = :client_id AND status IN ('pending', 'overdue')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':client_id' => $clientId]);
            
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
            
        } catch (PDOException $e) {
            error_log("Error getting pending payments: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get client's active services count
     */
    public function getActiveServicesCount($clientId) {
        try {
            $sql = "SELECT COUNT(*) as count 
                    FROM services 
                    WHERE client_id = :client_id AND status = 'active'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':client_id' => $clientId]);
            
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
            
        } catch (PDOException $e) {
            error_log("Error getting active services count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Convert a lead to a client
     * 
     * @param int $leadId Lead ID to convert
     * @param array $additionalData Additional data to override or supplement lead data
     * @param int $createdBy Admin user ID who performs the conversion
     * @return array|false Returns array with 'success' and 'client_id' or false on error
     */
    public function convertLeadToClient($leadId, $additionalData = [], $createdBy = null) {
        try {
            // Get lead data
            $sql = "SELECT * FROM leads WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $leadId]);
            $lead = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$lead) {
                return [
                    'success' => false,
                    'error' => 'Lead no encontrado'
                ];
            }
            
            // Check if lead is already converted
            if ($lead['status'] === 'convertido') {
                // Check if client already exists with this lead info
                $checkSql = "SELECT id FROM clients WHERE notes LIKE :lead_ref";
                $checkStmt = $this->db->prepare($checkSql);
                $checkStmt->execute([':lead_ref' => '%Lead ID: ' . $leadId . '%']);
                $existingClient = $checkStmt->fetch();
                
                if ($existingClient) {
                    return [
                        'success' => false,
                        'error' => 'Este lead ya fue convertido a cliente',
                        'client_id' => $existingClient['id']
                    ];
                }
            }
            
            // Map lead data to client data
            // Split nombre into contact_name and potentially company_name
            $nombreParts = explode(' ', trim($lead['nombre']), 2);
            $contactName = $lead['nombre'];
            $companyName = $additionalData['company_name'] ?? ($nombreParts[0] ?? $lead['nombre']);
            
            // Build client data
            $clientData = [
                'company_name' => $additionalData['company_name'] ?? $companyName,
                'contact_name' => $additionalData['contact_name'] ?? $contactName,
                'email' => $additionalData['email'] ?? '',
                'phone' => $additionalData['phone'] ?? null,
                'phone_country_code' => $additionalData['phone_country_code'] ?? '+52',
                'whatsapp' => $additionalData['whatsapp'] ?? $lead['whatsapp'],
                'whatsapp_country_code' => $additionalData['whatsapp_country_code'] ?? '+52',
                'address' => $additionalData['address'] ?? null,
                'city' => $additionalData['city'] ?? null,
                'state' => $additionalData['state'] ?? null,
                'country' => $additionalData['country'] ?? 'México',
                'tax_id' => $additionalData['tax_id'] ?? null,
                'website' => $additionalData['website'] ?? null,
                'industry' => $additionalData['industry'] ?? $lead['especialidad'],
                'client_type' => $additionalData['client_type'] ?? 'prospect',
                'legal_risk' => $additionalData['legal_risk'] ?? 'low',
                'legal_compliance' => $additionalData['legal_compliance'] ?? 100.00,
                'last_audit_date' => $additionalData['last_audit_date'] ?? null,
                'status' => $additionalData['status'] ?? 'prospect',
                'notes' => $this->buildClientNotes($lead, $additionalData['notes'] ?? null),
                'logo_url' => $additionalData['logo_url'] ?? null,
                'created_by' => $createdBy
            ];
            
            // Create client
            $clientId = $this->createClient($clientData);
            
            if (!$clientId) {
                return [
                    'success' => false,
                    'error' => 'Error al crear el cliente'
                ];
            }
            
            // Update lead status to 'convertido'
            $conversionNote = "\n\n[Convertido a Cliente ID: " . $clientId . " el " . date('Y-m-d H:i:s') . "]";
            $updateSql = "UPDATE leads SET status = 'convertido', notes = CONCAT(COALESCE(notes, ''), :conversion_note) WHERE id = :lead_id";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->execute([
                ':conversion_note' => $conversionNote,
                ':lead_id' => $leadId
            ]);
            
            return [
                'success' => true,
                'client_id' => $clientId,
                'client_number' => $this->getClientById($clientId)['client_number'] ?? null
            ];
            
        } catch (PDOException $e) {
            error_log("Error converting lead to client: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error en la base de datos: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Build client notes from lead data
     */
    private function buildClientNotes($lead, $additionalNotes = null) {
        $notes = [];
        
        // Add lead reference
        $notes[] = "=== Convertido desde Lead ===";
        $notes[] = "Lead ID: " . $lead['id'];
        $notes[] = "Fecha de conversión: " . date('Y-m-d H:i:s');
        
        // Add lead message if exists
        if (!empty($lead['mensaje'])) {
            $notes[] = "";
            $notes[] = "Mensaje original del lead:";
            $notes[] = $lead['mensaje'];
        }
        
        // Add lead notes if exists
        if (!empty($lead['notes'])) {
            $notes[] = "";
            $notes[] = "Notas del lead:";
            $notes[] = $lead['notes'];
        }
        
        // Add additional notes
        if (!empty($additionalNotes)) {
            $notes[] = "";
            $notes[] = "Notas adicionales:";
            $notes[] = $additionalNotes;
        }
        
        return implode("\n", $notes);
    }
    
    /**
     * Get available leads for conversion (not yet converted)
     */
    public function getAvailableLeads($limit = 50) {
        try {
            $sql = "SELECT id, nombre, especialidad, whatsapp, mensaje, status, created_at 
                    FROM leads 
                    WHERE status != 'convertido' AND status != 'descartado'
                    ORDER BY created_at DESC 
                    LIMIT :limit";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching available leads: " . $e->getMessage());
            return [];
        }
    }
}

