<?php
$dsn = "mysql:host=localhost;dbname=u997099361_veterinaria;charset=utf8mb4";
$user = "sopheadmin";
$pass = "z*B4D5N#k59CIbs!";

try {
    $db = new PDO($dsn, $user, $pass);
    echo "--- TABLES IN u997099361_veterinaria ---\n";
    $stmt = $db->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo $row[0] . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
