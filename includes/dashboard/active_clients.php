<?php
/**
 * Dashboard Active Clients Grid
 */
?>
<!-- Section: Active Clients -->
<div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden mb-6">
    <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Cartera de Clientes Activos</h3>
        <a href="admin_clients.php" class="text-sm text-primary font-bold hover:underline">Ver Directorio</a>
    </div>
    <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php if (empty($activeClients)): ?>
            <div class="col-span-2 text-center py-8 text-slate-500 dark:text-slate-400">
                <?php if (!$client): ?>
                    <div class="flex flex-col items-center gap-2">
                        <span class="material-symbols-outlined text-4xl text-slate-300">database</span>
                        <p>Base de datos no configurada</p>
                        <p class="text-xs">Ejecuta el script SQL primero</p>
                    </div>
                <?php else: ?>
                    No hay clientes activos
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($activeClients as $clientItem): 
                // Get client services
                $serviceTags = [];
                $renewalDate = null;
                
                try {
                    if (class_exists('Service')) {
                        $serviceObj = new Service();
                        $clientServices = $serviceObj->getServicesByClient($clientItem['id'], 'active');
                        
                        // Get service tags
                        foreach ($clientServices as $svc) {
                            $type = $svc['service_type'] ?? '';
                            $tagMap = [
                                'redes_sociales' => 'SMM',
                                'community_manager' => 'CM',
                                'diseno_web' => 'WEB',
                                'ads' => 'ADS',
                                'branding' => 'BRAND',
                                'chatbot' => 'BOT'
                            ];
                            if (isset($tagMap[$type])) {
                                $serviceTags[] = $tagMap[$type];
                            }
                        }
                        
                        // Get renewal date from first service
                        if (!empty($clientServices)) {
                            $renewalDate = $clientServices[0]['renewal_date'] ?? null;
                        }
                    }
                } catch (Exception $e) {
                    // Silently fail if services table doesn't exist
                }
            ?>
            <div class="flex items-start gap-4 p-4 rounded-lg bg-slate-50 dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50">
                <?php if (!empty($clientItem['logo_url'])): ?>
                    <div class="bg-cover bg-center h-12 w-12 rounded-lg shrink-0" style="background-image: url('<?php echo htmlspecialchars($clientItem['logo_url']); ?>');"></div>
                <?php else: ?>
                    <div class="h-12 w-12 rounded-lg shrink-0 bg-gradient-to-br from-primary to-blue-600 flex items-center justify-center text-white font-bold">
                        <?php echo strtoupper(substr($clientItem['company_name'] ?? 'CL', 0, 2)); ?>
                    </div>
                <?php endif; ?>
                <div class="flex-1 min-w-0">
                    <h4 class="text-base font-bold text-slate-900 dark:text-white truncate"><?php echo htmlspecialchars($clientItem['company_name'] ?? 'Cliente'); ?></h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">
                        <?php if ($renewalDate): ?>
                            Contrato Mensual • Renueva <?php echo date('d M', strtotime($renewalDate)); ?>
                        <?php else: ?>
                            Cliente Activo
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($serviceTags)): ?>
                        <div class="flex gap-2">
                            <?php foreach (array_unique($serviceTags) as $tag): ?>
                                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300"><?php echo $tag; ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <a href="admin_client_detail.php?id=<?php echo $clientItem['id'] ?? ''; ?>" class="text-slate-400 hover:text-primary">
                    <span class="material-symbols-outlined">more_vert</span>
                </a>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div><?php ?>
