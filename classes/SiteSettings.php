<?php
/**
 * SOPHEA - Site Settings Management Class
 *
 * Handles site settings like banner and logo
 */

require_once __DIR__ . '/Database.php';

class SiteSettings {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get a setting value by key
     */
    public function getSetting($key, $default = '') {
        try {
            $stmt = $this->db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['setting_value'] : $default;
        } catch (PDOException $e) {
            error_log("Error getting setting {$key}: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Set a setting value
     */
    public function setSetting($key, $value, $type = 'text') {
        try {
            // Validar que la conexión a la base de datos esté activa
            if (!$this->db) {
                error_log("Error: Database connection is null when setting {$key}");
                throw new Exception("No hay conexión a la base de datos");
            }
            
            // Validar parámetros
            if (empty($key)) {
                error_log("Error: setting_key is empty");
                throw new Exception("La clave del setting está vacía");
            }
            
            // Verificar si la tabla existe
            $tableCheck = $this->db->query("SHOW TABLES LIKE 'site_settings'");
            if ($tableCheck->rowCount() === 0) {
                error_log("Error: Table site_settings does not exist");
                throw new Exception("La tabla site_settings no existe en la base de datos. Por favor, ejecuta el script de migración.");
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO site_settings (setting_key, setting_value, setting_type) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    setting_value = VALUES(setting_value),
                    setting_type = VALUES(setting_type),
                    updated_at = CURRENT_TIMESTAMP
            ");
            
            $result = $stmt->execute([$key, $value, $type]);
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("Error executing setSetting for {$key}: " . print_r($errorInfo, true));
                throw new Exception("Error SQL: " . ($errorInfo[2] ?? 'Error desconocido'));
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("PDOException setting setting {$key}: " . $e->getMessage());
            error_log("SQL State: " . $e->getCode());
            throw new Exception("Error de base de datos: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Exception setting setting {$key}: " . $e->getMessage());
            throw $e; // Re-lanzar para que el código que llama pueda manejarlo
        }
    }

    /**
     * Get main banner image
     */
    public function getMainBanner() {
        return $this->getSetting('main_banner', '');
    }

    /**
     * Get main logo
     */
    public function getMainLogo() {
        return $this->getSetting('main_logo', '');
    }

    /**
     * Set main banner image
     */
    public function setMainBanner($imagePath) {
        return $this->setSetting('main_banner', $imagePath, 'image');
    }

    /**
     * Set main logo
     */
    public function setMainLogo($logoPath) {
        return $this->setSetting('main_logo', $logoPath, 'image');
    }

    /**
     * Get all settings
     */
    public function getAllSettings() {
        try {
            $stmt = $this->db->query("SELECT setting_key, setting_value, setting_type FROM site_settings");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $settings = [];
            foreach ($results as $row) {
                $settings[$row['setting_key']] = [
                    'value' => $row['setting_value'],
                    'type' => $row['setting_type']
                ];
            }
            return $settings;
        } catch (PDOException $e) {
            error_log("Error getting all settings: " . $e->getMessage());
            return [];
        }
    }
}
