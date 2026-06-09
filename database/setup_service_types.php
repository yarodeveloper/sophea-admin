<?php
require_once __DIR__ . '/../config_db.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Crear tabla
    $sql = "CREATE TABLE IF NOT EXISTS service_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(50) UNIQUE NOT NULL,
        name VARCHAR(100) NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        display_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);
    echo "Tabla service_types creada exitosamente.\n";

    // 2. Poblar datos iniciales
    $initial_types = [
        ['redes_sociales', 'Manejo de Redes', 10],
        ['desarrollo_web', 'Desarrollo Web', 20],
        ['branding', 'Branding y Diseño', 30],
        ['seo_sem', 'SEO / SEM', 40],
        ['campanas_ads', 'Campañas Ads', 50],
        ['fotografia_video', 'Fotografía / Video', 60],
        ['asesoria', 'Asesoría / Consultoría', 70],
        ['email_marketing', 'Email Marketing', 80],
        ['consultoria_legal', 'Consultoría Legal/Médica', 90],
        ['auditoria_datos', 'Auditoría de Datos', 100],
        ['hosting_dominio', 'Hosting / Dominio', 110],
        ['otro', 'Otro', 999]
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO service_types (slug, name, display_order) VALUES (?, ?, ?)");
    
    foreach ($initial_types as $type) {
        $stmt->execute($type);
    }
    
    echo "Datos iniciales insertados correctamente.\n";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
