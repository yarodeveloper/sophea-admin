<?php
/**
 * SOPHEA - Database Connection Class
 * 
 * Handles database connections using PDO with error handling
 */

class Database {
    private $conn = null;
    private static $instance = null;
    
    /**
     * Private constructor for singleton pattern
     */
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Database Connection Error: " . $e->getMessage());
            } else {
                error_log("Database Connection Error: " . $e->getMessage());
                die("Error de conexión a la base de datos. Por favor, contacte al administrador.");
            }
        }
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection() {
        return $this->conn;
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    /**
     * Insert a new lead
     */
    public function insertLead($data) {
        try {
            $sql = "INSERT INTO leads (nombre, especialidad, whatsapp, mensaje, ip_address, user_agent, source) 
                    VALUES (:nombre, :especialidad, :whatsapp, :mensaje, :ip_address, :user_agent, :source)";
            
            $stmt = $this->conn->prepare($sql);
            
            $stmt->execute([
                ':nombre' => $data['nombre'],
                ':especialidad' => $data['especialidad'],
                ':whatsapp' => $data['whatsapp'],
                ':mensaje' => $data['mensaje'] ?? null,
                ':ip_address' => $data['ip_address'] ?? null,
                ':user_agent' => $data['user_agent'] ?? null,
                ':source' => $data['source'] ?? 'website'
            ]);
            
            return $this->conn->lastInsertId();
            
        } catch (PDOException $e) {
            error_log("Error inserting lead: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all leads
     */
    public function getAllLeads($limit = 100, $offset = 0) {
        try {
            $sql = "SELECT * FROM leads ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Error fetching leads: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get lead by ID
     */
    public function getLeadById($id) {
        try {
            $sql = "SELECT * FROM leads WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("Error fetching lead: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update lead status
     */
    public function updateLeadStatus($id, $status, $notes = null) {
        try {
            $sql = "UPDATE leads SET status = :status";
            
            if ($notes !== null) {
                $sql .= ", notes = :notes";
            }
            
            $sql .= " WHERE id = :id";
            
            $stmt = $this->conn->prepare($sql);
            
            $params = [
                ':id' => $id,
                ':status' => $status
            ];
            
            if ($notes !== null) {
                $params[':notes'] = $notes;
            }
            
            return $stmt->execute($params);
            
        } catch (PDOException $e) {
            error_log("Error updating lead: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get new leads count (status = 'nuevo')
     */
    public function getNewLeadsCount($year = null, $month = null) {
        try {
            if ($year && $month) {
                $sql = "SELECT COUNT(*) as count 
                        FROM leads 
                        WHERE status = 'nuevo'
                        AND MONTH(created_at) = :month 
                        AND YEAR(created_at) = :year";
                
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    ':month' => $month,
                    ':year' => $year
                ]);
            } else {
                // Get all new leads regardless of date
                $sql = "SELECT COUNT(*) as count 
                        FROM leads 
                        WHERE status = 'nuevo'";
                
                $stmt = $this->conn->prepare($sql);
                $stmt->execute();
            }
            
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
            
        } catch (PDOException $e) {
            error_log("Error counting new leads: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get leads conversion rate (leads converted to clients)
     */
    public function getLeadsConversionRate() {
        try {
            // Get total leads
            $sql = "SELECT COUNT(*) as total FROM leads";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $totalLeads = $stmt->fetch()['total'] ?? 0;
            
            // Get converted leads (status = 'convertido')
            $sql = "SELECT COUNT(*) as converted FROM leads WHERE status = 'convertido'";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $convertedLeads = $stmt->fetch()['converted'] ?? 0;
            
            if ($totalLeads > 0) {
                return round(($convertedLeads / $totalLeads) * 100, 1);
            }
            
            return 0;
            
        } catch (PDOException $e) {
            error_log("Error calculating conversion rate: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get leads by month for chart (last 6 months)
     */
    public function getLeadsByMonth($months = 6) {
        try {
            $sql = "SELECT 
                        YEAR(created_at) as year,
                        MONTH(created_at) as month,
                        COUNT(*) as total
                    FROM leads 
                    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                    GROUP BY YEAR(created_at), MONTH(created_at)
                    ORDER BY year ASC, month ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':months' => $months]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error getting leads by month: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get lead statistics
     */
    public function getLeadStats() {
        try {
            $sql = "SELECT * FROM lead_stats LIMIT 1";
            $stmt = $this->conn->query($sql);
            
            $result = $stmt->fetch();
            
            // If no results, return default stats
            if (!$result) {
                return [
                    'total_leads' => 0,
                    'nuevos' => 0,
                    'contactados' => 0,
                    'calificados' => 0,
                    'convertidos' => 0,
                    'hoy' => 0,
                    'esta_semana' => 0,
                    'este_mes' => 0
                ];
            }
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("Error fetching stats: " . $e->getMessage());
            return [
                'total_leads' => 0,
                'nuevos' => 0,
                'contactados' => 0,
                'calificados' => 0,
                'convertidos' => 0,
                'hoy' => 0,
                'esta_semana' => 0,
                'este_mes' => 0
            ];
        }
    }
    
    /**
     * Log email sent
     */
    public function logEmail($leadId, $recipient, $subject, $status = 'sent', $errorMessage = null) {
        try {
            $sql = "INSERT INTO email_log (lead_id, recipient, subject, status, error_message) 
                    VALUES (:lead_id, :recipient, :subject, :status, :error_message)";
            
            $stmt = $this->conn->prepare($sql);
            
            return $stmt->execute([
                ':lead_id' => $leadId,
                ':recipient' => $recipient,
                ':subject' => $subject,
                ':status' => $status,
                ':error_message' => $errorMessage
            ]);
            
        } catch (PDOException $e) {
            error_log("Error logging email: " . $e->getMessage());
            return false;
        }
    }
}
