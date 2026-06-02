<?php
require_once 'config_db.php';
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- CHECKING CLIENT ID 7 --- \n";
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = 7");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        print_r($row);
    } else {
        echo "No client found with ID 7.\n";
    }

    echo "\n--- CHECKING HIGHEST CLIENT ID --- \n";
    $stmt = $pdo->query("SELECT MAX(id) FROM clients");
    echo "Max ID: " . $stmt->fetchColumn() . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
