<?php
/**
 * SOPHEA - Change Password
 * 
 * Admin page to change password
 */

require_once 'admin_auth_helper.php';
$GLOBALS['admin_page_title'] = 'Cambiar Contraseña - SOPHEA';
$auth_result = requireAdminAuth();
$auth = $auth_result['auth'];
$currentUser = $auth_result['user'];

$message = '';
$error = '';

// Get message from URL if exists
if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    $messageType = isset($_GET['type']) ? $_GET['type'] : 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Todos los campos son requeridos';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Las nuevas contraseñas no coinciden';
    } elseif (strlen($newPassword) < 8) {
        $error = 'La nueva contraseña debe tener al menos 8 caracteres';
    } else {
        $result = $auth->changePassword($currentPassword, $newPassword);
        
        if ($result['success']) {
            // Redirect to prevent form resubmission and session issues
            header('Location: admin_change_password.php?message=' . urlencode($result['message']) . '&type=success');
            exit;
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - SOPHEA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <i class="ph-bold ph-shield-check text-3xl text-purple-600"></i>
                <h1 class="text-2xl font-bold text-gray-800">Cambiar Contraseña</h1>
            </div>
            <div class="flex items-center space-x-4">
                <a href="admin.php" class="text-gray-600 hover:text-gray-800 font-medium">
                    <i class="ph ph-arrow-left"></i> Volver al Panel
                </a>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8 max-w-2xl">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <i class="ph ph-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <i class="ph ph-warning"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2">Contraseña Actual</label>
                    <input type="password" name="current_password" required autocomplete="current-password"
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2">Nueva Contraseña</label>
                    <input type="password" name="new_password" required autocomplete="new-password" minlength="8"
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <p class="text-xs text-gray-500 mt-1">Mínimo 8 caracteres</p>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2">Confirmar Nueva Contraseña</label>
                    <input type="password" name="confirm_password" required autocomplete="new-password" minlength="8"
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                
                <div class="flex items-center space-x-4">
                    <button type="submit" name="change_password"
                        class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition">
                        <i class="ph ph-key"></i> Cambiar Contraseña
                    </button>
                    <a href="admin.php" class="text-gray-600 hover:text-gray-800">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
