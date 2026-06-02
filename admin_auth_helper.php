<?php
/**
 * SOPHEA - Admin Authentication Helper
 * 
 * Helper function to check authentication in admin panels
 * Include this at the top of admin pages
 */

if (!function_exists('requireAdminAuth')) {
    function requireAdminAuth() {
        require_once 'config.php';
        require_once 'config_db.php';
        require_once 'classes/Database.php';
        require_once 'classes/Auth.php';
    
    $auth = new Auth();
    
    // Handle logout
    if (isset($_GET['logout'])) {
        $auth->logout();
        header('Location: ' . basename($_SERVER['PHP_SELF']));
        exit;
    }
    
    // Handle login
    $login_error = '';
    $login_locked = false;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
        $result = $auth->login($_POST['username'], $_POST['password']);
        
        if ($result['success']) {
            // Redirect to prevent form resubmission
            header('Location: ' . basename($_SERVER['PHP_SELF']));
            exit;
        } else {
            $login_error = $result['error'];
            $login_locked = $result['locked'] ?? false;
        }
    }
    
    // Check if user is logged in
    if (!$auth->isLoggedIn()) {
        // Show login form
        $pageTitle = 'Admin - SOPHEA';
        if (isset($GLOBALS['admin_page_title'])) {
            $pageTitle = $GLOBALS['admin_page_title'];
        }
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo htmlspecialchars($pageTitle); ?></title>
            <script src="https://cdn.tailwindcss.com"></script>
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
            <style>* { font-family: 'Inter', sans-serif; }</style>
        </head>
        <body class="bg-gray-100">
            <div class="min-h-screen flex items-center justify-center p-4">
                <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md">
                    <div class="text-center mb-6">
                        <h1 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($pageTitle); ?></h1>
                        <p class="text-sm text-gray-600">Sistema de autenticación seguro</p>
                    </div>
                    
                    <?php if ($login_error): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <?php echo htmlspecialchars($login_error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($login_locked): ?>
                        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                            <strong>Cuenta bloqueada:</strong> Demasiados intentos fallidos. Por favor espera antes de intentar de nuevo.
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label class="block text-gray-700 font-medium mb-2">Usuario</label>
                            <input type="text" name="username" required autocomplete="username"
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                                placeholder="Usuario"
                                <?php echo $login_locked ? 'disabled' : ''; ?>>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 font-medium mb-2">Contraseña</label>
                            <input type="password" name="password" required autocomplete="current-password"
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                                placeholder="Contraseña"
                                <?php echo $login_locked ? 'disabled' : ''; ?>>
                        </div>
                        
                        <button type="submit" 
                            class="w-full bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                            <?php echo $login_locked ? 'disabled' : ''; ?>>
                            Iniciar Sesión
                        </button>
                    </form>
                    
                    <div class="mt-4 text-center text-sm text-gray-600">
                        <p>¿Olvidaste tu contraseña? Contacta al administrador.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
    
    // Return auth instance and current user
    return [
        'auth' => $auth,
        'user' => $auth->getCurrentUser()
    ];
    }
}
