<?php
require 'config_db.php';

try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // SQL Statements from database/add_recurring_fields_to_services.sql
    $sql1 = "ALTER TABLE services 
             ADD COLUMN is_recurring TINYINT(1) DEFAULT 0 COMMENT 'Indica si el servicio es recurrente',
             ADD COLUMN renewal_mode ENUM('automatic', 'manual') DEFAULT 'manual' COMMENT 'Modo de renovacion',
             ADD COLUMN base_service_id INT NULL COMMENT 'ID del servicio original o plantilla',
             ADD COLUMN period_number INT DEFAULT 1 COMMENT 'Numero de periodo/mes transcurrido'";
    
    $sql2 = "ALTER TABLE services 
             ADD CONSTRAINT fk_base_service 
             FOREIGN KEY (base_service_id) REFERENCES services(id) ON DELETE SET NULL";
             
    try {
        $pdo->exec($sql1);
        echo "Columns is_recurring, renewal_mode, base_service_id, period_number added successfully.\n";
    } catch(PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "Columns already exist in services table.\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $pdo->exec($sql2);
        echo "Foreign key fk_base_service added successfully.\n";
    } catch(PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "Foreign key fk_base_service already exists.\n";
        } else {
            // Ignore if key exists error might be different format depending on MySQL version
            echo "Foreign key constraint status: " . $e->getMessage() . "\n";
        }
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
