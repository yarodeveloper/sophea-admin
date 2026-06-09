<?php
/**
 * SOPHEA - Service Catalog Management Class
 * 
 * Handles all service catalog operations (CRUD)
 */

require_once __DIR__ . '/Database.php';

class ServiceCatalog {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get all services from catalog
     */
    public function getAllServices($filters = []) {
        try {
            $sql = "SELECT * FROM services_catalog WHERE 1=1";
            $params = [];
            
            // Active filter
            if (isset($filters['is_active']) && $filters['is_active'] !== '') {
                $sql .= " AND is_active = :is_active";
                $params[':is_active'] = $filters['is_active'];
            }
            
            // Service type filter
            if (isset($filters['service_type']) && !empty($filters['service_type'])) {
                $sql .= " AND service_type = :service_type";
                $params[':service_type'] = $filters['service_type'];
            }
            
            // Order by
            $orderBy = $filters['order_by'] ?? 'display_order';
            $orderDir = $filters['order_dir'] ?? 'ASC';
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
            error_log("Error fetching services catalog: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get service by ID
     */
    public function getServiceById($id) {
        try {
            $sql = "SELECT * FROM services_catalog WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching service: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create a new service in catalog
     */
    public function createService($data) {
        try {
            $sql = "INSERT INTO services_catalog 
                    (service_name, service_type, suggested_price, currency, description, observations, 
                     is_active, display_order, created_by) 
                    VALUES 
                    (:service_name, :service_type, :suggested_price, :currency, :description, :observations, 
                     :is_active, :display_order, :created_by)";
            
            $stmt = $this->db->prepare($sql);
            
            $result = $stmt->execute([
                ':service_name' => $data['service_name'],
                ':service_type' => $data['service_type'],
                ':suggested_price' => $data['suggested_price'] ?? 0.00,
                ':currency' => $data['currency'] ?? 'MXN',
                ':description' => $data['description'] ?? null,
                ':observations' => $data['observations'] ?? null,
                ':is_active' => $data['is_active'] ?? true,
                ':display_order' => $data['display_order'] ?? 0,
                ':created_by' => $data['created_by'] ?? null
            ]);
            
            if ($result) {
                return $this->db->lastInsertId();
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Error creating service in catalog: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update a service in catalog
     */
    public function updateService($id, $data) {
        try {
            $sql = "UPDATE services_catalog SET 
                    service_name = :service_name,
                    service_type = :service_type,
                    suggested_price = :suggested_price,
                    currency = :currency,
                    description = :description,
                    observations = :observations,
                    is_active = :is_active,
                    display_order = :display_order
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([
                ':id' => $id,
                ':service_name' => $data['service_name'],
                ':service_type' => $data['service_type'],
                ':suggested_price' => $data['suggested_price'] ?? 0.00,
                ':currency' => $data['currency'] ?? 'MXN',
                ':description' => $data['description'] ?? null,
                ':observations' => $data['observations'] ?? null,
                ':is_active' => $data['is_active'] ?? true,
                ':display_order' => $data['display_order'] ?? 0
            ]);
            
        } catch (PDOException $e) {
            error_log("Error updating service in catalog: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a service from catalog
     */
    public function deleteService($id) {
        try {
            $sql = "DELETE FROM services_catalog WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
            
        } catch (PDOException $e) {
            error_log("Error deleting service from catalog: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get active services for dropdown
     */
    public function getActiveServices() {
        return $this->getAllServices(['is_active' => true, 'order_by' => 'display_order', 'order_dir' => 'ASC']);
    }
    
    /**
     * Get service type labels
     */
    public static function getServiceTypeLabels() {
        return [
            'redes_sociales' => 'Redes Sociales',
            'community_manager' => 'Community Manager',
            'diseno_web' => 'Diseño Web',
            'ads' => 'Campañas de Ads',
            'branding' => 'Branding',
            'chatbot' => 'Chatbot',
            'seo' => 'SEO',
            'content_marketing' => 'Content Marketing',
            'email_marketing' => 'Email Marketing',
            'consultoria_legal' => 'Consultoría Legal',
            'auditoria_datos' => 'Auditoría de Datos',
            'hosting_dominio' => 'Hosting / Dominio',
            'otro' => 'Otro'
        ];
    }
}
