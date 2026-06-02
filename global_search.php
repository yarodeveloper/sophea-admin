<?php
require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    $searchTerm = 'C-2026-007';
    echo "--- SEARCHING FOR '$searchTerm' IN ALL TABLES ---\n";
    
    $tables = ['clients', 'payments', 'quotes', 'services', 'invoices', 'leads'];
    foreach ($tables as $table) {
        echo "Searching in $table...\n";
        try {
            $stmt = $db->query("SELECT * FROM $table LIMIT 1");
            $firstRow = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($firstRow === false) {
                 // Check if table exists but is empty
                 $stmt = $db->query("DESCRIBE $table");
                 $columnsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
                 $columns = array_map(function($c) { return $c['Field']; }, $columnsData);
            } else {
                 $columns = array_keys($firstRow);
            }
            
            if (empty($columns)) continue;
            
            $where = [];
            $params = [];
            foreach ($columns as $idx => $col) {
                $where[] = "`$col` LIKE :search_$idx";
                $params[":search_$idx"] = "%$searchTerm%";
            }
            
            $sql = "SELECT * FROM $table WHERE " . implode(" OR ", $where);
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($results)) {
                echo "FOUND in $table:\n";
                foreach ($results as $res) {
                    print_r($res);
                }
            }
        } catch (Exception $e) {
            echo "Error in $table: " . $e->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
