<?php
/**
 * Tests Tab Content for Admin Tools Panel
 * 
 * This file contains the tests/diagnostics tools interface
 */
?>

<div class="mb-8">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Test Webhook -->
        <a href="tests/test_webhook.php" target="_blank" class="bg-white dark:bg-card-dark rounded-xl shadow-sm p-6 border border-slate-200 dark:border-slate-800 hover:shadow-md hover:border-blue-300 dark:hover:border-blue-600 transition-all group">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-blue-100 dark:bg-blue-900/30 p-3 rounded-lg group-hover:bg-blue-200 dark:group-hover:bg-blue-900/50 transition">
                    <span class="material-symbols-outlined text-2xl text-blue-600 dark:text-blue-400">webhook</span>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400">Test Webhook</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Verificar webhook</p>
                </div>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Prueba la verificación del webhook de WhatsApp y simula solicitudes de Meta.</p>
        </a>

        <!-- Test Send WhatsApp -->
        <a href="tests/test_send_whatsapp.php" target="_blank" class="bg-white dark:bg-card-dark rounded-xl shadow-sm p-6 border border-slate-200 dark:border-slate-800 hover:shadow-md hover:border-emerald-300 dark:hover:border-emerald-600 transition-all group">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-emerald-100 dark:bg-emerald-900/30 p-3 rounded-lg group-hover:bg-emerald-200 dark:group-hover:bg-emerald-900/50 transition">
                    <span class="material-symbols-outlined text-2xl text-emerald-600 dark:text-emerald-400">send</span>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400">Test Envío WhatsApp</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Enviar mensajes</p>
                </div>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Prueba el envío de mensajes de WhatsApp y ve la respuesta detallada de la API.</p>
        </a>

        <!-- Test DB Connection -->
        <a href="tests/test_db_connection.php" target="_blank" class="bg-white dark:bg-card-dark rounded-xl shadow-sm p-6 border border-slate-200 dark:border-slate-800 hover:shadow-md hover:border-purple-300 dark:hover:border-purple-600 transition-all group">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-purple-100 dark:bg-purple-900/30 p-3 rounded-lg group-hover:bg-purple-200 dark:group-hover:bg-purple-900/50 transition">
                    <span class="material-symbols-outlined text-2xl text-purple-600 dark:text-purple-400">database</span>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400">Test Conexión DB</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Verificar base de datos</p>
                </div>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Verifica la conexión a la base de datos y muestra información de configuración.</p>
        </a>

        <!-- Test DB Config -->
        <a href="tests/test_db_config.php" target="_blank" class="bg-white dark:bg-card-dark rounded-xl shadow-sm p-6 border border-slate-200 dark:border-slate-800 hover:shadow-md hover:border-amber-300 dark:hover:border-amber-600 transition-all group">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-amber-100 dark:bg-amber-900/30 p-3 rounded-lg group-hover:bg-amber-200 dark:group-hover:bg-amber-900/50 transition">
                    <span class="material-symbols-outlined text-2xl text-amber-600 dark:text-amber-400">settings</span>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400">Test Config DB</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Configuración DB</p>
                </div>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Muestra y verifica la configuración de la base de datos.</p>
        </a>

        <!-- Test Testimonials -->
        <a href="tests/test_testimonials.php" target="_blank" class="bg-white dark:bg-card-dark rounded-xl shadow-sm p-6 border border-slate-200 dark:border-slate-800 hover:shadow-md hover:border-rose-300 dark:hover:border-rose-600 transition-all group">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-rose-100 dark:bg-rose-900/30 p-3 rounded-lg group-hover:bg-rose-200 dark:group-hover:bg-rose-900/50 transition">
                    <span class="material-symbols-outlined text-2xl text-rose-600 dark:text-rose-400">format_quote</span>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900 dark:text-white group-hover:text-rose-600 dark:group-hover:text-rose-400">Test Testimonios</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Diagnóstico testimonios</p>
                </div>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Verifica el sistema de testimonios: tablas, conexión, permisos y funcionalidad.</p>
        </a>
    </div>

    <!-- Quick Info -->
    <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 dark:border-blue-400 p-6 rounded-lg">
        <h3 class="font-semibold text-blue-800 dark:text-blue-300 mb-2 flex items-center gap-2">
            <span class="material-symbols-outlined">info</span>
            <span>Información sobre los Tests</span>
        </h3>
        <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-2 ml-8 list-disc">
            <li><strong>Test Webhook:</strong> Verifica que el webhook esté configurado correctamente y pueda recibir solicitudes de Meta.</li>
            <li><strong>Test Envío WhatsApp:</strong> Prueba el envío de mensajes y muestra información detallada de la respuesta de la API.</li>
            <li><strong>Test Conexión DB:</strong> Verifica que la conexión a la base de datos funcione correctamente.</li>
            <li><strong>Test Config DB:</strong> Muestra la configuración actual de la base de datos (sin exponer contraseñas).</li>
            <li><strong>Test Testimonios:</strong> Diagnostica el sistema de testimonios: verifica tablas, conexión, permisos y funcionalidad.</li>
        </ul>
    </div>
</div>

