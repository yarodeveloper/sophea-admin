<?php
/**
 * Testimonials Tab Content for Admin Web Panel
 * 
 * This file contains the testimonials management interface
 */

// Ensure variables are set
$testimonialAction = $testimonialAction ?? ($action ?? 'list');
$testimonialEditId = $testimonialEditId ?? ($editId ?? null);
$allTestimonials = $allTestimonials ?? [];
$editTestimonial = $editTestimonial ?? null;

// CKEditor Script
echo '<script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>';

if ($testimonialAction === 'list'):
?>
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-slate-900 dark:text-white">Lista de Testimonios</h3>
        <a href="admin_web.php?tab=testimonials&action=new" class="flex items-center gap-2 h-10 px-5 bg-primary hover:bg-primary/90 text-white rounded-lg font-bold text-sm shadow-lg shadow-primary/25 transition-all">
            <span class="material-symbols-outlined text-[20px]">add</span>
            <span>Nuevo Testimonio</span>
        </a>
    </div>

    <?php if (empty($allTestimonials)): ?>
        <div class="text-center py-16">
            <span class="material-symbols-outlined text-6xl text-slate-300 dark:text-slate-600 mb-4 block">quote</span>
            <h3 class="text-xl font-bold text-slate-700 dark:text-slate-300 mb-2">No hay testimonios</h3>
            <p class="text-slate-500 dark:text-slate-400 mb-6">Crea tu primer testimonio para comenzar</p>
            <a href="admin_web.php?tab=testimonials&action=new" class="bg-primary text-white px-6 py-3 rounded-lg hover:bg-primary/90 transition font-semibold">
                Crear Testimonio
            </a>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 dark:bg-surface-dark/50 text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wider font-semibold">
                    <tr>
                        <th class="px-6 py-4">Cliente</th>
                        <th class="px-6 py-4">Sector</th>
                        <th class="px-6 py-4">Estado</th>
                        <th class="px-6 py-4">Destacado</th>
                        <th class="px-6 py-4">Orden</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                    <?php foreach ($allTestimonials as $testimonial): ?>
                        <tr class="group hover:bg-slate-50 dark:hover:bg-surface-dark/40 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <?php if ($testimonial['client_avatar']): ?>
                                        <img src="<?php echo htmlspecialchars($testimonial['client_avatar']); ?>" 
                                             alt="<?php echo htmlspecialchars($testimonial['client_name']); ?>"
                                             class="w-10 h-10 rounded-full object-cover">
                                    <?php else: ?>
                                        <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-bold">
                                            <?php echo strtoupper(substr($testimonial['client_name'], 0, 2)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="font-semibold text-slate-900 dark:text-white"><?php echo htmlspecialchars($testimonial['client_name']); ?></p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo htmlspecialchars($testimonial['client_title'] ?? ''); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-400">
                                    <?php echo ucfirst($testimonial['sector'] ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $statusColors = [
                                    'published' => ['bg' => 'bg-emerald-100 dark:bg-emerald-500/20', 'text' => 'text-emerald-700 dark:text-emerald-400'],
                                    'draft' => ['bg' => 'bg-amber-100 dark:bg-amber-500/20', 'text' => 'text-amber-700 dark:text-amber-400']
                                ];
                                $statusInfo = $statusColors[$testimonial['status']] ?? $statusColors['draft'];
                                ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold <?php echo $statusInfo['bg'] . ' ' . $statusInfo['text']; ?>">
                                    <span class="w-1.5 h-1.5 rounded-full <?php echo str_replace(['/20', 'bg-'], ['', 'bg-'], $statusInfo['bg']); ?>"></span>
                                    <?php echo $testimonial['status'] === 'published' ? 'Publicado' : 'Borrador'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($testimonial['is_featured']): ?>
                                    <span class="material-symbols-outlined text-amber-500">star</span>
                                <?php else: ?>
                                    <span class="material-symbols-outlined text-slate-300 dark:text-slate-600">star</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400">
                                <?php echo $testimonial['display_order'] ?? 0; ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="admin_web.php?tab=testimonials&action=edit&id=<?php echo $testimonial['id']; ?>" 
                                       class="text-primary hover:text-primary/80 font-medium text-sm flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[16px]">edit</span>
                                        <span>Editar</span>
                                    </a>
                                    <form method="POST" action="admin_testimonials.php" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar este testimonio?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $testimonial['id']; ?>">
                                        <input type="hidden" name="redirect_to" value="admin_web.php?tab=testimonials">
                                        <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 font-medium text-sm flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                            <span>Eliminar</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

<?php elseif ($testimonialAction === 'new' || $testimonialAction === 'edit'): ?>
    <!-- Create/Edit Form -->
    <div class="mb-6">
        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6">
            <?php echo $testimonialAction === 'edit' ? 'Editar Testimonio' : 'Nuevo Testimonio'; ?>
        </h3>
        
        <form method="POST" action="admin_testimonials.php" id="testimonialForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?php echo $testimonialAction === 'edit' ? 'update' : 'create'; ?>">
            <input type="hidden" name="redirect_to" value="admin_web.php?tab=testimonials">
            <?php if ($testimonialAction === 'edit' && $editTestimonial): ?>
                <input type="hidden" name="id" value="<?php echo $editTestimonial['id']; ?>">
            <?php endif; ?>

            <div class="space-y-6">
                <!-- Client Name -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Nombre del Cliente *</label>
                    <input type="text" name="client_name" required
                           value="<?php echo htmlspecialchars($editTestimonial['client_name'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <!-- Client Title -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Cargo/Título del Cliente</label>
                    <input type="text" name="client_title"
                           value="<?php echo htmlspecialchars($editTestimonial['client_title'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <!-- Sector -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Sector</label>
                    <input type="text" name="sector"
                           value="<?php echo htmlspecialchars($editTestimonial['sector'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <!-- Testimonial Text -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Testimonio *</label>
                    <textarea name="testimonial_text" id="testimonial_text" required rows="6"
                              class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"><?php echo htmlspecialchars($editTestimonial['testimonial_text'] ?? ''); ?></textarea>
                </div>

                <!-- Client Avatar -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Avatar del Cliente (URL)</label>
                    <input type="url" name="client_avatar"
                           value="<?php echo htmlspecialchars($editTestimonial['client_avatar'] ?? ''); ?>"
                           placeholder="https://ejemplo.com/avatar.jpg"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Estado</label>
                    <select name="status" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="draft" <?php echo ($editTestimonial['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Borrador</option>
                        <option value="published" <?php echo ($editTestimonial['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Publicado</option>
                    </select>
                </div>

                <!-- Is Featured -->
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_featured" value="1"
                               <?php echo ($editTestimonial['is_featured'] ?? false) ? 'checked' : ''; ?>
                               class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-primary focus:ring-primary">
                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Destacado</span>
                    </label>
                </div>

                <!-- Display Order -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Orden de Visualización</label>
                    <input type="number" name="display_order"
                           value="<?php echo $editTestimonial['display_order'] ?? 0; ?>"
                           min="0"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                    <button type="submit" class="flex items-center gap-2 h-10 px-5 bg-primary hover:bg-primary/90 text-white rounded-lg font-bold text-sm shadow-lg shadow-primary/25 transition-all">
                        <span class="material-symbols-outlined text-[20px]">save</span>
                        <span>Guardar Testimonio</span>
                    </button>
                    <a href="admin_web.php?tab=testimonials" class="flex items-center gap-2 h-10 px-5 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg font-medium text-sm hover:bg-slate-50 dark:hover:bg-surface-dark transition-all">
                        <span>Cancelar</span>
                    </a>
                </div>
            </div>
        </form>

        <script>
            // Auto-generate slug from client name
            document.querySelector('input[name="client_name"]')?.addEventListener('input', function() {
                const slugInput = document.getElementById('slug');
                if (slugInput && (!slugInput.value || slugInput.dataset.autoGenerated === 'true')) {
                    const slug = this.value.toLowerCase()
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '')
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/(^-|-$)/g, '');
                    slugInput.value = slug;
                    slugInput.dataset.autoGenerated = 'true';
                }
            });

            // Initialize CKEditor
            if (document.getElementById('testimonial_text')) {
                CKEDITOR.replace('testimonial_text', {
                    height: 300,
                    toolbar: [
                        { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline'] },
                        { name: 'paragraph', items: ['NumberedList', 'BulletedList'] },
                        { name: 'links', items: ['Link', 'Unlink'] }
                    ]
                });
            }
        </script>
    </div>
<?php endif; ?>

