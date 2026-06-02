<?php
/**
 * Dashboard Tab Content for WhatsApp Marketing Panel
 * 
 * This file contains the dashboard content with credits, metrics, and statistics
 */
?>

<!-- Credits Alert -->
<?php if ($creditsInfo['percentage_used'] > 80): ?>
<div class="mb-6 p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300">
    <div class="flex items-center">
        <span class="material-symbols-outlined text-2xl mr-3">warning</span>
        <div>
            <p class="font-semibold">⚠️ Créditos Bajos</p>
            <p class="text-sm">
                Has usado el <?php echo $creditsInfo['percentage_used']; ?>% de tus créditos disponibles. 
                Considera recargar para evitar interrupciones.
            </p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Credits Overview -->
<div class="bg-gradient-to-r from-emerald-500 to-emerald-600 dark:from-emerald-600 dark:to-emerald-700 rounded-xl shadow-lg p-6 mb-8 text-white">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-2xl font-bold mb-2">Créditos Disponibles</h2>
            <p class="text-emerald-100">Control de créditos de WhatsApp Business API</p>
        </div>
        <span class="material-symbols-outlined text-5xl opacity-80">credit_card</span>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
        <div class="bg-white bg-opacity-20 rounded-lg p-4 backdrop-blur">
            <p class="text-emerald-100 text-sm mb-1">Disponibles</p>
            <p class="text-3xl font-bold"><?php echo number_format($creditsInfo['available']); ?></p>
        </div>
        <div class="bg-white bg-opacity-20 rounded-lg p-4 backdrop-blur">
            <p class="text-emerald-100 text-sm mb-1">Usados (Mes)</p>
            <p class="text-3xl font-bold"><?php echo number_format($creditsInfo['used_month']); ?></p>
        </div>
        <div class="bg-white bg-opacity-20 rounded-lg p-4 backdrop-blur">
            <p class="text-emerald-100 text-sm mb-1">Restantes</p>
            <p class="text-3xl font-bold"><?php echo number_format($creditsInfo['remaining']); ?></p>
        </div>
        <div class="bg-white bg-opacity-20 rounded-lg p-4 backdrop-blur">
            <p class="text-emerald-100 text-sm mb-1">Porcentaje Usado</p>
            <p class="text-3xl font-bold"><?php echo $creditsInfo['percentage_used']; ?>%</p>
        </div>
    </div>
    
    <!-- Progress Bar -->
    <div class="mt-6">
        <div class="flex justify-between text-sm mb-2">
            <span>Uso de Créditos</span>
            <span><?php echo $creditsInfo['percentage_used']; ?>%</span>
        </div>
        <div class="w-full bg-white bg-opacity-30 rounded-full h-3">
            <div class="bg-white rounded-full h-3 transition-all duration-500" 
                 style="width: <?php echo min(100, $creditsInfo['percentage_used']); ?>%"></div>
        </div>
    </div>
</div>

<!-- Metrics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Messages Sent Today -->
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-sm p-6 border border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-blue-100 dark:bg-blue-900/30 p-3 rounded-lg">
                <span class="material-symbols-outlined text-2xl text-blue-600 dark:text-blue-400">send</span>
            </div>
            <span class="text-xs text-slate-500 dark:text-slate-400">Hoy</span>
        </div>
        <h3 class="text-slate-600 dark:text-slate-400 text-sm font-medium mb-1">Mensajes Enviados</h3>
        <p class="text-3xl font-bold text-slate-900 dark:text-white"><?php echo number_format($metrics['sent_today']); ?></p>
    </div>
    
    <!-- Delivery Rate -->
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-sm p-6 border border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-emerald-100 dark:bg-emerald-900/30 p-3 rounded-lg">
                <span class="material-symbols-outlined text-2xl text-emerald-600 dark:text-emerald-400">check_circle</span>
            </div>
            <span class="text-xs text-slate-500 dark:text-slate-400">30 días</span>
        </div>
        <h3 class="text-slate-600 dark:text-slate-400 text-sm font-medium mb-1">Tasa de Entrega</h3>
        <p class="text-3xl font-bold text-slate-900 dark:text-white"><?php echo number_format($metrics['delivery_rate'], 1); ?>%</p>
    </div>
    
    <!-- Read Rate -->
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-sm p-6 border border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-purple-100 dark:bg-purple-900/30 p-3 rounded-lg">
                <span class="material-symbols-outlined text-2xl text-purple-600 dark:text-purple-400">visibility</span>
            </div>
            <span class="text-xs text-slate-500 dark:text-slate-400">30 días</span>
        </div>
        <h3 class="text-slate-600 dark:text-slate-400 text-sm font-medium mb-1">Tasa de Lectura</h3>
        <p class="text-3xl font-bold text-slate-900 dark:text-white"><?php echo number_format($metrics['read_rate'], 1); ?>%</p>
    </div>
    
    <!-- Reply Rate -->
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-sm p-6 border border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-amber-100 dark:bg-amber-900/30 p-3 rounded-lg">
                <span class="material-symbols-outlined text-2xl text-amber-600 dark:text-amber-400">chat</span>
            </div>
            <span class="text-xs text-slate-500 dark:text-slate-400">30 días</span>
        </div>
        <h3 class="text-slate-600 dark:text-slate-400 text-sm font-medium mb-1">Tasa de Respuesta</h3>
        <p class="text-3xl font-bold text-slate-900 dark:text-white"><?php echo number_format($metrics['reply_rate'], 1); ?>%</p>
    </div>
</div>

<!-- Charts and Activity Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Usage Chart -->
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-sm p-6 border border-slate-200 dark:border-slate-800">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined">show_chart</span>
            Uso de Créditos (Últimos 30 días)
        </h3>
        <canvas id="usageChart" height="200"></canvas>
    </div>
    
    <!-- Recent Activity -->
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-sm p-6 border border-slate-200 dark:border-slate-800">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined">schedule</span>
            Actividad Reciente
        </h3>
        <div class="space-y-4">
            <?php if (empty($recentActivity['campaigns'])): ?>
                <p class="text-slate-500 dark:text-slate-400 text-center py-8">No hay actividad reciente</p>
            <?php else: ?>
                <?php foreach (array_slice($recentActivity['campaigns'], 0, 5) as $campaign): ?>
                <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-900/50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="bg-emerald-100 dark:bg-emerald-900/30 p-2 rounded">
                            <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400">campaign</span>
                        </div>
                        <div>
                            <p class="font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars($campaign['name']); ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                <?php 
                                $statusLabels = [
                                    'draft' => 'Borrador',
                                    'scheduled' => 'Programada',
                                    'sending' => 'Enviando',
                                    'completed' => 'Completada',
                                    'paused' => 'Pausada',
                                    'cancelled' => 'Cancelada'
                                ];
                                echo $statusLabels[$campaign['status']] ?? $campaign['status'];
                                ?>
                            </p>
                        </div>
                    </div>
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                        <?php echo date('d/m/Y', strtotime($campaign['created_at'])); ?>
                    </span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Additional Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-sm p-6 border border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-slate-900 dark:text-white">Mensajes Esta Semana</h3>
            <span class="material-symbols-outlined text-2xl text-blue-600 dark:text-blue-400">calendar_month</span>
        </div>
        <p class="text-3xl font-bold text-slate-900 dark:text-white"><?php echo number_format($metrics['sent_week']); ?></p>
    </div>
    
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-sm p-6 border border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-slate-900 dark:text-white">Mensajes Este Mes</h3>
            <span class="material-symbols-outlined text-2xl text-purple-600 dark:text-purple-400">calendar_today</span>
        </div>
        <p class="text-3xl font-bold text-slate-900 dark:text-white"><?php echo number_format($metrics['sent_month']); ?></p>
    </div>
    
    <div class="bg-white dark:bg-card-dark rounded-xl shadow-sm p-6 border border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-slate-900 dark:text-white">Campañas Activas</h3>
            <span class="material-symbols-outlined text-2xl text-emerald-600 dark:text-emerald-400">play_arrow</span>
        </div>
        <p class="text-3xl font-bold text-slate-900 dark:text-white"><?php echo $metrics['active_campaigns']; ?></p>
        <?php if ($metrics['pending_campaigns'] > 0): ?>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2"><?php echo $metrics['pending_campaigns']; ?> programadas</p>
        <?php endif; ?>
    </div>
</div>

<!-- Lead Statistics -->
<div class="bg-white dark:bg-card-dark rounded-xl shadow-sm p-6 border border-slate-200 dark:border-slate-800">
    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
        <span class="material-symbols-outlined">groups</span>
        Estadísticas de Leads
    </h3>
    
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="text-center p-4 bg-slate-50 dark:bg-slate-900/50 rounded-lg">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo number_format($leadStats['total']); ?></p>
            <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">Total Leads</p>
        </div>
        <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?php echo number_format($leadStats['with_whatsapp']); ?></p>
            <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">Con WhatsApp</p>
        </div>
        <div class="text-center p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400"><?php echo number_format($leadStats['recent_30_days']); ?></p>
            <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">Últimos 30 días</p>
        </div>
        <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400"><?php echo number_format($leadStats['by_status']['nuevo'] ?? 0); ?></p>
            <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">Nuevos</p>
        </div>
        <div class="text-center p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400"><?php echo number_format($leadStats['by_status']['convertido'] ?? 0); ?></p>
            <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">Convertidos</p>
        </div>
    </div>
    
    <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between">
            <a href="admin.php" class="text-sm text-primary hover:text-primary/80 font-medium flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">arrow_forward</span>
                Ver todos los leads
            </a>
            <span class="text-xs text-slate-500 dark:text-slate-400">
                <?php echo count($allEspecialidades); ?> especialidades únicas
            </span>
        </div>
    </div>
</div>

<script>
// Usage Chart
const ctx = document.getElementById('usageChart');
if (ctx) {
    const chartData = <?php echo json_encode($chartData); ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.map(d => {
                const date = new Date(d.date);
                return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' });
            }),
            datasets: [{
                label: 'Créditos Usados',
                data: chartData.map(d => d.credits_used),
                borderColor: 'rgb(16, 185, 129)',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}
</script>

