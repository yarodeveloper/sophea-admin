<?php
/**
 * SOPHEA - Setup Admin User
 * 
 * Script to create initial admin user with secure password
 * Run this once to set up your admin account
 * 
 * IMPORTANT: Delete this file after creating your admin user!
 */

require_once '../config.php';
require_once '../config_db.php';
require_once '../classes/Database.php';
require_once '../classes/Auth.php';

// Only allow this script to run if no admin users exist (or via direct access with confirmation)
$db = Database::getInstance();

// Check if admin users exist
$sql = "SELECT COUNT(*) as count FROM admin_users";
$stmt = $db->prepare($sql);
$stmt->execute();
$result = $stmt->fetch();
$userCount = $result['count'];

$action = $_GET['action'] ?? 'form';
$message = '';
$error = '';

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    
    // Validation
    if (empty($username) || empty($password) || empty($email)) {
        $error = 'Todos los campos son requeridos';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres';
    } elseif ($password !== $confirmPassword) {
        $error = 'Las contraseñas no coinciden';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email no es válido';
    } else {
        // Create user
        $result = Auth::createAdminUser($username, $password, $email, $fullName);
        
        if ($result['success']) {
            $message = 'Usuario admin creado correctamente. Ahora puedes iniciar sesión.';
            $action = 'success';
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
    <title>Setup Admin - SOPHEA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md">
            <h1 class="text-2xl font-bold mb-6 text-center">Setup Admin User - SOPHEA</h1>
            
            <?php if ($action === 'success'): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <div class="text-center">
                    <a href="admin.php" class="inline-block bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition">
                        Ir al Panel de Admin
                    </a>
                </div>
            <?php else: ?>
                <?php if ($userCount > 0): ?>
                    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                        <strong>Advertencia:</strong> Ya existen usuarios admin en el sistema. 
                        Si continúas, se creará un nuevo usuario.
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="?action=create">
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-2">Usuario</label>
                        <input type="text" name="username" required
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="admin">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-2">Email</label>
                        <input type="email" name="email" required
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="admin@sophea.com.mx">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-2">Nombre Completo</label>
                        <input type="text" name="full_name"
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="Administrador">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-2">Contraseña</label>
                        <input type="password" name="password" required minlength="8"
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="Mínimo 8 caracteres">
                        <p class="text-xs text-gray-500 mt-1">Mínimo 8 caracteres</p>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-gray-700 font-medium mb-2">Confirmar Contraseña</label>
                        <input type="password" name="confirm_password" required minlength="8"
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="Repite la contraseña">
                    </div>
                    
                    <button type="submit" 
                        class="w-full bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700 transition">
                        Crear Usuario Admin
                    </button>
                </form>
                
                <div class="mt-6 text-center text-sm text-gray-600">
                    <p><strong>Importante:</strong> Elimina este archivo después de crear el usuario.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
