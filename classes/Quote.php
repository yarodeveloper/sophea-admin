<?php
/**
 * SOPHEA - Quote Management Class
 * 
 * Handles all quote operations (CRUD)
 */

require_once __DIR__ . '/Database.php';

class Quote {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Generate unique quote number (COT-YYYY-MM-XXXX)
     */
    public function generateQuoteNumber() {
        try {
            $year = date('Y');
            $month = str_pad(date('m'), 2, '0', STR_PAD_LEFT);
            
            $stmt = $this->db->prepare("SELECT MAX(CAST(SUBSTRING(quote_number, 13) AS UNSIGNED)) as max_num 
                                        FROM quotes 
                                        WHERE quote_number LIKE ?");
            $stmt->execute(["COT-{$year}-{$month}-%"]);
            $result = $stmt->fetch();
            
            $sequence = ($result && $result['max_num']) ? $result['max_num'] + 1 : 1;
            return "COT-{$year}-{$month}-" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
            
        } catch (PDOException $e) {
            error_log("Error generating quote number: " . $e->getMessage());
            return "COT-" . date('Y-m') . "-" . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
    }
    
    /**
     * Create a new quote
     */
    public function createQuote($data) {
        try {
            $this->db->beginTransaction();
            
            $quoteNumber = $this->generateQuoteNumber();
            
            // Calculate totals
            $subtotal = 0;
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $itemTotal = ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
                    $subtotal += $itemTotal;
                }
            }
            
            $taxRate = $data['tax_rate'] ?? 16.00;
            $taxAmount = $subtotal * ($taxRate / 100);
            $total = $subtotal + $taxAmount;
            
            $sql = "INSERT INTO quotes 
                    (quote_number, client_id, title, description, subtotal, tax_rate, tax_amount, total,
                     currency, status, valid_until, notes, terms_conditions, created_by) 
                    VALUES 
                    (:quote_number, :client_id, :title, :description, :subtotal, :tax_rate, :tax_amount, :total,
                     :currency, :status, :valid_until, :notes, :terms_conditions, :created_by)";
            
            $stmt = $this->db->prepare($sql);
            
            $result = $stmt->execute([
                ':quote_number' => $quoteNumber,
                ':client_id' => $data['client_id'],
                ':title' => $data['title'],
                ':description' => $data['description'] ?? null,
                ':subtotal' => $subtotal,
                ':tax_rate' => $taxRate,
                ':tax_amount' => $taxAmount,
                ':total' => $total,
                ':currency' => $data['currency'] ?? 'MXN',
                ':status' => $data['status'] ?? 'draft',
                ':valid_until' => $data['valid_until'] ?? null,
                ':notes' => $data['notes'] ?? null,
                ':terms_conditions' => $data['terms_conditions'] ?? null,
                ':created_by' => $data['created_by'] ?? null
            ]);
            
            if ($result) {
                $quoteId = $this->db->lastInsertId();
                
                // Insert items
                if (isset($data['items']) && is_array($data['items'])) {
                    $this->addQuoteItems($quoteId, $data['items']);
                }
                
                $this->db->commit();
                return $quoteId;
            }
            
            $this->db->rollBack();
            return false;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error creating quote: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add items to a quote
     */
    public function addQuoteItems($quoteId, $items) {
        try {
            $sql = "INSERT INTO quote_items (quote_id, service_type, item_type, ads_platform, description, quantity, unit_price, total, display_order) 
                    VALUES (:quote_id, :service_type, :item_type, :ads_platform, :description, :quantity, :unit_price, :total, :display_order)";
            $stmt = $this->db->prepare($sql);
            
            $order = 0;
            foreach ($items as $item) {
                $quantity = $item['quantity'] ?? 1;
                $unitPrice = $item['unit_price'] ?? 0;
                $total = $quantity * $unitPrice;
                
                $stmt->execute([
                    ':quote_id'     => $quoteId,
                    ':service_type' => $item['service_type'],
                    ':item_type'    => $item['item_type'] ?? 'fee',
                    ':ads_platform' => $item['ads_platform'] ?? null,
                    ':description'  => $item['description'],
                    ':quantity'     => $quantity,
                    ':unit_price'   => $unitPrice,
                    ':total'        => $total,
                    ':display_order'=> $order++
                ]);
            }
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Error adding quote items: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update a quote
     */
    public function updateQuote($id, $data) {
        try {
            $this->db->beginTransaction();
            
            // Recalculate totals if items changed
            $subtotal = 0;
            if (isset($data['items']) && is_array($data['items'])) {
                // Delete existing items
                $this->deleteQuoteItems($id);
                
                // Add new items
                $this->addQuoteItems($id, $data['items']);
                
                // Calculate new subtotal
                foreach ($data['items'] as $item) {
                    $itemTotal = ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
                    $subtotal += $itemTotal;
                }
            } else {
                // Get existing subtotal
                $existing = $this->getQuoteById($id);
                $subtotal = $existing['subtotal'] ?? 0;
            }
            
            $taxRate = $data['tax_rate'] ?? 16.00;
            $taxAmount = $subtotal * ($taxRate / 100);
            $total = $subtotal + $taxAmount;
            
            $sql = "UPDATE quotes SET 
                    client_id = :client_id,
                    title = :title,
                    description = :description,
                    subtotal = :subtotal,
                    tax_rate = :tax_rate,
                    tax_amount = :tax_amount,
                    total = :total,
                    currency = :currency,
                    status = :status,
                    valid_until = :valid_until,
                    notes = :notes,
                    terms_conditions = :terms_conditions
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            
            $result = $stmt->execute([
                ':id' => $id,
                ':client_id' => $data['client_id'],
                ':title' => $data['title'],
                ':description' => $data['description'] ?? null,
                ':subtotal' => $subtotal,
                ':tax_rate' => $taxRate,
                ':tax_amount' => $taxAmount,
                ':total' => $total,
                ':currency' => $data['currency'] ?? 'MXN',
                ':status' => $data['status'] ?? 'draft',
                ':valid_until' => $data['valid_until'] ?? null,
                ':notes' => $data['notes'] ?? null,
                ':terms_conditions' => $data['terms_conditions'] ?? null
            ]);
            
            if ($result) {
                $this->db->commit();
                return true;
            }
            
            $this->db->rollBack();
            return false;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error updating quote: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete quote items
     */
    private function deleteQuoteItems($quoteId) {
        try {
            $sql = "DELETE FROM quote_items WHERE quote_id = :quote_id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':quote_id' => $quoteId]);
        } catch (PDOException $e) {
            error_log("Error deleting quote items: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get quote by ID
     */
    public function getQuoteById($id) {
        try {
            $sql = "SELECT * FROM quotes WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $quote = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($quote) {
                $quote['items'] = $this->getQuoteItems($id);
            }
            
            return $quote;
            
        } catch (PDOException $e) {
            error_log("Error fetching quote: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get quote items
     */
    public function getQuoteItems($quoteId) {
        try {
            $sql = "SELECT * FROM quote_items WHERE quote_id = :quote_id ORDER BY display_order ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':quote_id' => $quoteId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching quote items: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all quotes with optional filters
     */
    public function getAllQuotes($filters = []) {
        try {
            $sql = "SELECT q.*, c.company_name, c.client_number 
                    FROM quotes q
                    INNER JOIN clients c ON q.client_id = c.id
                    WHERE 1=1";
            $params = [];
            
            // Status filter
            if (isset($filters['status']) && !empty($filters['status'])) {
                $sql .= " AND q.status = :status";
                $params[':status'] = $filters['status'];
            }
            
            // Client filter
            if (isset($filters['client_id']) && !empty($filters['client_id'])) {
                $sql .= " AND q.client_id = :client_id";
                $params[':client_id'] = $filters['client_id'];
            }
            
            // Search filter
            if (isset($filters['search']) && !empty($filters['search'])) {
                $sql .= " AND (q.title LIKE :search OR q.quote_number LIKE :search OR c.company_name LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            // Order by
            $orderBy = $filters['order_by'] ?? 'q.created_at';
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
            error_log("Error fetching quotes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update quote status
     */
    public function updateStatus($id, $status) {
        try {
            $sql = "UPDATE quotes SET status = :status";
            $params = [':id' => $id, ':status' => $status];
            
            if ($status === 'sent') {
                $sql .= ", sent_at = NOW()";
            } elseif ($status === 'accepted') {
                $sql .= ", accepted_at = NOW()";
            } elseif ($status === 'rejected') {
                $sql .= ", rejected_at = NOW()";
            }
            
            $sql .= " WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
            
        } catch (PDOException $e) {
            error_log("Error updating quote status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get total count of quotes with optional filters
     */
    public function getTotalCount($filters = []) {
        try {
            $sql = "SELECT COUNT(*) as total 
                    FROM quotes q
                    INNER JOIN clients c ON q.client_id = c.id
                    WHERE 1=1";
            $params = [];
            
            // Status filter
            if (isset($filters['status']) && !empty($filters['status'])) {
                $sql .= " AND q.status = :status";
                $params[':status'] = $filters['status'];
            }
            
            // Client filter
            if (isset($filters['client_id']) && !empty($filters['client_id'])) {
                $sql .= " AND q.client_id = :client_id";
                $params[':client_id'] = $filters['client_id'];
            }
            
            // Search filter
            if (isset($filters['search']) && !empty($filters['search'])) {
                $sql .= " AND (q.title LIKE :search OR q.quote_number LIKE :search OR c.company_name LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
            
        } catch (PDOException $e) {
            error_log("Error counting quotes: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get pending quotes count
     */
    public function getPendingCount() {
        try {
            $sql = "SELECT COUNT(*) as count FROM quotes WHERE status IN ('sent', 'draft')";
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error counting pending quotes: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get estimated value of pending quotes
     */
    public function getPendingValue() {
        try {
            $sql = "SELECT COALESCE(SUM(total), 0) as total FROM quotes WHERE status IN ('sent', 'draft')";
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error calculating pending value: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Delete a quote
     */
    public function deleteQuote($id) {
        try {
            $sql = "DELETE FROM quotes WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Error deleting quote: " . $e->getMessage());
            return false;
        }
    }
}

