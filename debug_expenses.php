<?php
/**
 * Script de depuración para verificar gastos
 */

require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Database.php';
require_once 'classes/Expense.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Debug de Gastos</h2>";

try {
    $expense = new Expense();
    $db = Database::getInstance()->getConnection();
    
    // Fechas del mes actual
    $firstDayOfMonth = date('Y-m-01');
    $lastDayOfMonth = date('Y-m-t');
    
    echo "<h3>Fechas de filtro:</h3>";
    echo "<p>Desde: {$firstDayOfMonth}</p>";
    echo "<p>Hasta: {$lastDayOfMonth}</p>";
    
    // Verificar todos los gastos
    echo "<h3>1. Todos los gastos (sin filtros):</h3>";
    $allExpenses = $db->query("SELECT id, expense_number, category, amount, payment_date, status FROM expenses ORDER BY payment_date DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($allExpenses, true) . "</pre>";
    
    // Verificar gastos del mes actual
    echo "<h3>2. Gastos del mes actual (sin filtro de status):</h3>";
    $sql = "SELECT id, expense_number, category, amount, payment_date, status 
            FROM expenses 
            WHERE DATE(payment_date) >= :date_from 
            AND DATE(payment_date) <= :date_to
            ORDER BY payment_date DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':date_from' => $firstDayOfMonth,
        ':date_to' => $lastDayOfMonth
    ]);
    $monthExpenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Total encontrados: " . count($monthExpenses) . "</p>";
    echo "<pre>" . print_r($monthExpenses, true) . "</pre>";
    
    // Verificar con el método de la clase
    echo "<h3>3. Usando getExpensesWithClientService:</h3>";
    $filters = [
        'date_from' => $firstDayOfMonth,
        'date_to' => $lastDayOfMonth,
        'limit' => 20,
        'offset' => 0,
        'order_by' => 'e.payment_date',
        'order_dir' => 'DESC'
    ];
    $expenses = $expense->getExpensesWithClientService($filters);
    echo "<p>Total encontrados: " . count($expenses) . "</p>";
    echo "<pre>" . print_r($expenses, true) . "</pre>";
    
    // Verificar getTotalCount
    echo "<h3>4. Usando getTotalCount:</h3>";
    $total = $expense->getTotalCount($filters);
    echo "<p>Total count: {$total}</p>";
    
    // Verificar getMonthlyExpenses
    echo "<h3>5. Usando getMonthlyExpenses:</h3>";
    $monthly = $expense->getMonthlyExpenses(date('Y'), date('m'));
    echo "<p>Total mensual (paid): {$monthly}</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
