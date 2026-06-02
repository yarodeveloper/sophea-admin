<?php
/**
 * SOPHEA - Authentication Class
 * 
 * Secure authentication system with:
 * - Password hashing (bcrypt)
 * - Rate limiting (brute force protection)
 * - Secure session tokens
 * - Auto-logout on inactivity
 */

class Auth {
    private $db;
    private $sessionTimeout = 1800; // 30 minutes in seconds
    private $maxLoginAttempts = 5;
    private $lockoutTime = 900; // 15 minutes in seconds
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->initSession();
    }
    
    /**
     * Initialize secure session
     */
    private function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure session configuration
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Lax'); // Changed to Lax for better cross-tab compatibility
            
            // Set session cookie parameters
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            session_start();
        }
        
        // Regenerate session ID periodically to prevent session fixation
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 300) { // Every 5 minutes
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $this->sessionTimeout)) {
            $this->logout();
            return false;
        }
        
        // Check session token
        if (!isset($_SESSION['session_token']) || !$this->validateSessionToken($_SESSION['session_token'])) {
            $this->logout();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Login user
     */
    public function login($username, $password) {
        // Check rate limiting
        if ($this->isLockedOut()) {
            return [
                'success' => false,
                'error' => 'Demasiados intentos fallidos. Por favor intenta de nuevo en ' . $this->getRemainingLockoutTime() . ' minutos.',
                'locked' => true
            ];
        }
        
        // Validate input
        if (empty($username) || empty($password)) {
            $this->recordFailedAttempt();
            return [
                'success' => false,
                'error' => 'Usuario y contraseña son requeridos'
            ];
        }
        
        try {
            // Get user from database
            $sql = "SELECT id, username, password_hash, email, full_name, is_active, last_login 
                    FROM admin_users 
                    WHERE username = :username AND is_active = 1 
                    LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $this->recordFailedAttempt();
                return [
                    'success' => false,
                    'error' => 'Usuario o contraseña incorrectos'
                ];
            }
            
            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                $this->recordFailedAttempt();
                return [
                    'success' => false,
                    'error' => 'Usuario o contraseña incorrectos'
                ];
            }
            
            // Clear failed attempts on successful login
            $this->clearFailedAttempts();
            
            // Create secure session
            session_regenerate_id(true);
            $tokenTimestamp = time();
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_email'] = $user['email'];
            $_SESSION['admin_name'] = $user['full_name'];
            $_SESSION['last_activity'] = time();
            $_SESSION['token_timestamp'] = $tokenTimestamp;
            $_SESSION['session_token'] = $this->generateSessionToken($user['id'], $user['username'], $tokenTimestamp);
            
            // Update last login
            $this->updateLastLogin($user['id']);
            
            return [
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name']
                ]
            ];
            
        } catch (PDOException $e) {
            error_log("Auth login error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error al iniciar sesión. Por favor intenta más tarde.'
            ];
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        // Clear all session data
        $_SESSION = array();
        
        // Delete session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy session
        session_destroy();
    }
    
    /**
     * Get current user info
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['admin_id'] ?? null,
            'username' => $_SESSION['admin_username'] ?? null,
            'email' => $_SESSION['admin_email'] ?? null,
            'full_name' => $_SESSION['admin_name'] ?? null
        ];
    }
    
    /**
     * Change password
     */
    public function changePassword($currentPassword, $newPassword) {
        if (!$this->isLoggedIn()) {
            return [
                'success' => false,
                'error' => 'No estás autenticado'
            ];
        }
        
        // Validate new password
        if (strlen($newPassword) < 8) {
            return [
                'success' => false,
                'error' => 'La nueva contraseña debe tener al menos 8 caracteres'
            ];
        }
        
        try {
            // Get current user password hash
            $sql = "SELECT password_hash FROM admin_users WHERE id = :id LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $_SESSION['admin_id']]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return [
                    'success' => false,
                    'error' => 'Usuario no encontrado'
                ];
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password_hash'])) {
                return [
                    'success' => false,
                    'error' => 'La contraseña actual es incorrecta'
                ];
            }
            
            // Hash new password
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $sql = "UPDATE admin_users SET password_hash = :password_hash WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':password_hash' => $newPasswordHash,
                ':id' => $_SESSION['admin_id']
            ]);
            
            // Regenerate session token after password change
            $tokenTimestamp = time();
            $_SESSION['token_timestamp'] = $tokenTimestamp;
            $_SESSION['session_token'] = $this->generateSessionToken($_SESSION['admin_id'], $_SESSION['admin_username'], $tokenTimestamp);
            
            return [
                'success' => true,
                'message' => 'Contraseña actualizada correctamente'
            ];
            
        } catch (PDOException $e) {
            error_log("Auth changePassword error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error al cambiar la contraseña'
            ];
        }
    }
    
    /**
     * Check if IP is locked out
     */
    private function isLockedOut() {
        $ip = $this->getClientIP();
        
        try {
            $sql = "SELECT COUNT(*) as attempts, MAX(attempt_time) as last_attempt 
                    FROM login_attempts 
                    WHERE ip_address = :ip 
                    AND attempt_time > DATE_SUB(NOW(), INTERVAL :lockout_time SECOND)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':ip' => $ip,
                ':lockout_time' => $this->lockoutTime
            ]);
            
            $result = $stmt->fetch();
            
            return ($result['attempts'] >= $this->maxLoginAttempts);
            
        } catch (PDOException $e) {
            error_log("Auth isLockedOut error: " . $e->getMessage());
            return false; // Don't block on error
        }
    }
    
    /**
     * Record failed login attempt
     */
    private function recordFailedAttempt() {
        $ip = $this->getClientIP();
        
        try {
            $sql = "INSERT INTO login_attempts (ip_address, attempt_time) 
                    VALUES (:ip, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':ip' => $ip]);
            
        } catch (PDOException $e) {
            error_log("Auth recordFailedAttempt error: " . $e->getMessage());
        }
    }
    
    /**
     * Clear failed attempts for IP
     */
    private function clearFailedAttempts() {
        $ip = $this->getClientIP();
        
        try {
            $sql = "DELETE FROM login_attempts WHERE ip_address = :ip";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':ip' => $ip]);
            
        } catch (PDOException $e) {
            error_log("Auth clearFailedAttempts error: " . $e->getMessage());
        }
    }
    
    /**
     * Get remaining lockout time in minutes
     */
    private function getRemainingLockoutTime() {
        $ip = $this->getClientIP();
        
        try {
            $sql = "SELECT MAX(attempt_time) as last_attempt 
                    FROM login_attempts 
                    WHERE ip_address = :ip";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':ip' => $ip]);
            $result = $stmt->fetch();
            
            if ($result && $result['last_attempt']) {
                $lastAttempt = strtotime($result['last_attempt']);
                $remaining = ($this->lockoutTime - (time() - $lastAttempt)) / 60;
                return max(1, ceil($remaining));
            }
            
        } catch (PDOException $e) {
            error_log("Auth getRemainingLockoutTime error: " . $e->getMessage());
        }
        
        return 15;
    }
    
    /**
     * Generate secure session token
     */
    private function generateSessionToken($userId, $username, $timestamp = null) {
        if ($timestamp === null) {
            $timestamp = time();
        }
        $data = $userId . '|' . $username . '|' . $timestamp;
        return hash_hmac('sha256', $data, $this->getSecretKey());
    }
    
    /**
     * Validate session token
     */
    private function validateSessionToken($token) {
        if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username']) || !isset($_SESSION['token_timestamp'])) {
            return false;
        }
        
        // Use the stored timestamp to generate the expected token
        $expectedToken = $this->generateSessionToken($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['token_timestamp']);
        return hash_equals($expectedToken, $token);
    }
    
    /**
     * Get secret key for tokens
     */
    private function getSecretKey() {
        // In production, store this in config or environment variable
        // For now, use a combination of server variables
        $key = defined('AUTH_SECRET_KEY') ? AUTH_SECRET_KEY : 'sophea_auth_secret_key_change_in_production';
        return $key;
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Update last login timestamp
     */
    private function updateLastLogin($userId) {
        try {
            $sql = "UPDATE admin_users SET last_login = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $userId]);
        } catch (PDOException $e) {
            error_log("Auth updateLastLogin error: " . $e->getMessage());
        }
    }
    
    /**
     * Create admin user (for setup)
     */
    public static function createAdminUser($username, $password, $email, $fullName = '') {
        $db = Database::getInstance()->getConnection();
        
        try {
            // Check if user exists
            $sql = "SELECT id FROM admin_users WHERE username = :username LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([':username' => $username]);
            
            if ($stmt->fetch()) {
                return [
                    'success' => false,
                    'error' => 'El usuario ya existe'
                ];
            }
            
            // Hash password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $sql = "INSERT INTO admin_users (username, password_hash, email, full_name, is_active) 
                    VALUES (:username, :password_hash, :email, :full_name, 1)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':username' => $username,
                ':password_hash' => $passwordHash,
                ':email' => $email,
                ':full_name' => $fullName
            ]);
            
            return [
                'success' => true,
                'message' => 'Usuario admin creado correctamente',
                'user_id' => $db->lastInsertId()
            ];
            
        } catch (PDOException $e) {
            error_log("Auth createAdminUser error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error al crear usuario: ' . $e->getMessage()
            ];
        }
    }
}
