<?php
/**
 * SOPHEA - Reset Admin Password
 * 
 * Script to reset admin password (use only if you lost access)
 * IMPORTANT: Delete this file after use!
 */

require_once '../config.php';
require_once '../config_db.php';
require_once '../classes/Database.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $username = trim($_POST['username'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($username) || empty($newPassword)) {
        $error = 'Todos los campos son requeridos';
    } elseif (strlen($newPassword) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Las contraseñas no coinciden';
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Check if user exists
            $sql = "SELECT id FROM admin_users WHERE username = :username LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $error = 'El usuario no existe';
            } else {
                // Hash new password
                $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                
                // Update password
                $sql = "UPDATE admin_users SET password_hash = :password_hash WHERE username = :username";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':password_hash' => $passwordHash,
                    ':username' => $username
                ]);
                
                $message = "Contraseña actualizada correctamente para el usuario: " . htmlspecialchars($username);
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Get all users
$users = [];
try {
    $db = Database::getInstance()->getConnection();
    $sql = "SELECT username, email FROM admin_users WHERE is_active = 1";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error al obtener usuarios: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - SOPHEA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md">
            <h1 class="text-2xl font-bold mb-6 text-center">Resetear Contraseña Admin</h1>
            
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                <strong>⚠️ Advertencia:</strong> Este script es solo para emergencias. Elimínalo después de usar.
            </div>
            
            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($users)): ?>
                <div class="mb-4">
                    <p class="text-sm text-gray-600 mb-2">Usuarios disponibles:</p>
                    <ul class="list-disc list-inside text-sm">
                        <?php foreach ($users as $user): ?>
                            <li><strong><?php echo htmlspecialchars($user['username']); ?></strong> - <?php echo htmlspecialchars($user['email']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">Usuario</label>
                    <input type="text" name="username" required
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="admin">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">Nueva Contraseña</label>
                    <input type="password" name="new_password" required minlength="8"
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="Mínimo 8 caracteres">
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2">Confirmar Contraseña</label>
                    <input type="password" name="confirm_password" required minlength="8"
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="Repite la contraseña">
                </div>
                
                <button type="submit" name="reset_password"
                    class="w-full bg-red-600 text-white py-2 rounded-lg hover:bg-red-700 transition">
                    Resetear Contraseña
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <a href="check_admin_users.php" class="text-sm text-purple-600 hover:text-purple-800">
                    Ver usuarios en la base de datos
                </a>
            </div>
        </div>
    </div>
</body>
</html>
