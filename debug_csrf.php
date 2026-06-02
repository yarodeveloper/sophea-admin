<?php
/**
 * SOPHEA - CSRF Token Debug Script
 * 
 * Use this to debug CSRF token issues
 * Access: http://localhost/sopheaadmin/debug_csrf.php
 * DELETE THIS FILE AFTER DEBUGGING!
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate token if it doesn't exist
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug CSRF Token - SOPHEA</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">🔍 Debug CSRF Token</h1>
        
        <div class="bg-white rounded-lg shadow p-6 space-y-4">
            <h2 class="text-xl font-semibold mb-4">Información de Sesión:</h2>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <strong>Session ID:</strong>
                    <code class="block bg-gray-100 p-2 rounded mt-1"><?php echo session_id(); ?></code>
                </div>
                <div>
                    <strong>Session Status:</strong>
                    <code class="block bg-gray-100 p-2 rounded mt-1">
                        <?php 
                        $status = session_status();
                        echo $status === PHP_SESSION_ACTIVE ? 'ACTIVE' : ($status === PHP_SESSION_NONE ? 'NONE' : 'DISABLED');
                        ?>
                    </code>
                </div>
            </div>

            <div class="mt-4">
                <strong>CSRF Token en Sesión:</strong>
                <code class="block bg-gray-100 p-2 rounded mt-1 break-all"><?php echo htmlspecialchars($_SESSION['csrf_token'] ?? 'NO EXISTE'); ?></code>
            </div>

            <div class="mt-4">
                <strong>Token Length:</strong>
                <code class="block bg-gray-100 p-2 rounded mt-1">
                    <?php echo isset($_SESSION['csrf_token']) ? strlen($_SESSION['csrf_token']) : '0'; ?> caracteres
                </code>
            </div>

            <div class="mt-4">
                <strong>Cookie de Sesión:</strong>
                <pre class="bg-gray-100 p-2 rounded mt-1 text-sm overflow-x-auto"><?php print_r($_COOKIE); ?></pre>
            </div>

            <div class="mt-4">
                <strong>Variables de Sesión:</strong>
                <pre class="bg-gray-100 p-2 rounded mt-1 text-sm overflow-x-auto"><?php print_r($_SESSION); ?></pre>
            </div>

            <div class="mt-6 p-4 bg-blue-50 border-l-4 border-blue-500">
                <p class="font-semibold">📝 Instrucciones:</p>
                <ol class="list-decimal list-inside mt-2 space-y-1 text-sm">
                    <li>Verifica que el Session ID sea el mismo en esta página y en el formulario</li>
                    <li>Verifica que el CSRF Token exista y tenga 64 caracteres</li>
                    <li>Verifica que la cookie de sesión esté configurada</li>
                    <li>Si el token no existe, recarga esta página para generarlo</li>
                    <li>Luego prueba el formulario de nuevo</li>
                </ol>
            </div>

            <div class="mt-6">
                <h3 class="font-semibold mb-2">Test del Formulario:</h3>
                <form id="test-form" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div>
                        <label class="block mb-2">Nombre de Prueba:</label>
                        <input type="text" name="nombre" value="Test" class="w-full px-4 py-2 border rounded">
                    </div>
                    <div>
                        <label class="block mb-2">Especialidad:</label>
                        <input type="text" name="especialidad" value="Test" class="w-full px-4 py-2 border rounded">
                    </div>
                    <div>
                        <label class="block mb-2">WhatsApp:</label>
                        <input type="text" name="whatsapp" value="+521234567890" class="w-full px-4 py-2 border rounded">
                    </div>
                    <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded hover:bg-purple-700">
                        Probar Envío
                    </button>
                </form>
                <div id="test-result" class="mt-4"></div>
            </div>
        </div>

        <div class="mt-6">
            <a href="index.php" class="text-purple-600 hover:text-purple-700 font-medium">← Volver al sitio</a>
        </div>
    </div>

    <script>
        document.getElementById('test-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const resultDiv = document.getElementById('test-result');
            resultDiv.innerHTML = '<p class="text-blue-600">Enviando...</p>';

            const formData = new FormData(this);
            
            try {
                const response = await fetch('process_form.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    resultDiv.innerHTML = '<div class="bg-green-100 border border-green-500 text-green-800 p-4 rounded">✅ Éxito: ' + result.message + '</div>';
                } else {
                    resultDiv.innerHTML = '<div class="bg-red-100 border border-red-500 text-red-800 p-4 rounded">❌ Error: ' + result.message + (result.error_details ? '<br><small>' + result.error_details + '</small>' : '') + '</div>';
                }
            } catch (error) {
                resultDiv.innerHTML = '<div class="bg-red-100 border border-red-500 text-red-800 p-4 rounded">❌ Error de red: ' + error.message + '</div>';
            }
        });
    </script>
</body>
</html>

