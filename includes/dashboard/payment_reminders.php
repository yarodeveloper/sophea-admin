<?php
/**
 * Dashboard Payment Reminders
 */

// Get pending and overdue payments for reminders
$pendingPaymentsReminders = [];
$overduePaymentsReminders = [];
$paymentsDueSoon = [];

if ($payment) {
    try {
        $pendingPaymentsReminders = $payment->getPendingPayments();
        $overduePaymentsReminders = $payment->getOverduePayments();
        $paymentsDueSoon = $payment->getPaymentsDueSoon(7); // Next 7 days
    } catch (Exception $e) {
        error_log("Error fetching payment reminders: " . $e->getMessage());
    }
}

$totalReminders = count($overduePaymentsReminders) + count($paymentsDueSoon);
?>

<?php if ($totalReminders > 0): ?>
<div class="bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 rounded-xl border-l-4 border-yellow-500 dark:border-yellow-400 shadow-sm p-6 mb-6">
    <div class="flex items-start justify-between mb-4">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-3xl text-yellow-600 dark:text-yellow-400">notifications_active</span>
            <div>
                <h3 class="text-lg font-bold text-yellow-900 dark:text-yellow-200">Recordatorios de Pagos</h3>
                <p class="text-sm text-yellow-700 dark:text-yellow-300">Tienes <?php echo $totalReminders; ?> pago<?php echo $totalReminders > 1 ? 's' : ''; ?> que requieren atención</p>
            </div>
        </div>
        <a href="admin_payments.php?status=pending" 
           class="text-yellow-700 dark:text-yellow-300 hover:text-yellow-900 dark:hover:text-yellow-100 font-medium text-sm flex items-center gap-1">
            Ver todos
            <span class="material-symbols-outlined text-lg">arrow_forward</span>
        </a>
    </div>
    
    <div class="space-y-3">
        <?php if (count($overduePaymentsReminders) > 0): ?>
            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">
                <div class="flex items-center gap-2 mb-2">
                    <span class="material-symbols-outlined text-red-600 dark:text-red-400">warning</span>
                    <h4 class="font-semibold text-red-900 dark:text-red-200">Pagos Vencidos (<?php echo count($overduePaymentsReminders); ?>)</h4>
                </div>
                <div class="space-y-2 max-h-40 overflow-y-auto">
                    <?php foreach (array_slice($overduePaymentsReminders, 0, 5) as $pay): ?>
                        <div class="flex items-center justify-between text-sm">
                            <div>
                                <span class="font-medium text-red-900 dark:text-red-200"><?php echo htmlspecialchars($pay['invoice_number'] ?? 'N/A'); ?></span>
                                <span class="text-red-700 dark:text-red-300"> - $<?php echo number_format($pay['amount'], 2); ?></span>
                            </div>
                            <a href="admin_payments.php?search=<?php echo urlencode($pay['invoice_number']); ?>" 
                               class="text-red-600 dark:text-red-400 hover:underline text-xs">
                                Ver →
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (count($paymentsDueSoon) > 0): ?>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-800">
                <div class="flex items-center gap-2 mb-2">
                    <span class="material-symbols-outlined text-yellow-600 dark:text-yellow-400">schedule</span>
                    <h4 class="font-semibold text-yellow-900 dark:text-yellow-200">Próximos a Vencer (<?php echo count($paymentsDueSoon); ?>)</h4>
                </div>
                <div class="space-y-2 max-h-40 overflow-y-auto">
                    <?php foreach (array_slice($paymentsDueSoon, 0, 5) as $pay): ?>
                        <div class="flex items-center justify-between text-sm">
                            <div>
                                <span class="font-medium text-yellow-900 dark:text-yellow-200"><?php echo htmlspecialchars($pay['invoice_number'] ?? 'N/A'); ?></span>
                                <span class="text-yellow-700 dark:text-yellow-300"> - $<?php echo number_format($pay['amount'], 2); ?></span>
                                <?php if ($pay['due_date']): ?>
                                    <span class="text-yellow-600 dark:text-yellow-400 text-xs ml-2">
                                        Vence: <?php echo date('d/m/Y', strtotime($pay['due_date'])); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <a href="admin_payments.php?search=<?php echo urlencode($pay['invoice_number']); ?>" 
                               class="text-yellow-600 dark:text-yellow-400 hover:underline text-xs">
                                Ver →
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
