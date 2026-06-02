<?php
/**
 * Blog Tab Content for Admin Web Panel
 * 
 * This file contains the blog management interface
 */

// Ensure variables are set
$blogAction = $blogAction ?? ($action ?? 'list');
$blogEditId = $blogEditId ?? ($editId ?? null);
$posts = $posts ?? [];
$editPost = $editPost ?? null;
$allCategories = $allCategories ?? [];

// CKEditor Script
echo '<script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>';

if ($blogAction === 'list'):
?>
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-slate-900 dark:text-white">Artículos del Blog</h3>
        <a href="admin_web.php?tab=blog&action=new" class="flex items-center gap-2 h-10 px-5 bg-primary hover:bg-primary/90 text-white rounded-lg font-bold text-sm shadow-lg shadow-primary/25 transition-all">
            <span class="material-symbols-outlined text-[20px]">add</span>
            <span>Nuevo Artículo</span>
        </a>
    </div>

    <?php if (empty($posts)): ?>
        <div class="text-center py-12">
            <span class="material-symbols-outlined text-6xl text-slate-300 dark:text-slate-600 mb-4 block">article</span>
            <p class="text-slate-600 dark:text-slate-400 mb-4 font-medium">No hay artículos aún</p>
            <a href="admin_web.php?tab=blog&action=new" class="inline-block bg-primary text-white px-6 py-3 rounded-lg hover:bg-primary/90 transition font-semibold">
                Crear Primer Artículo
            </a>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 dark:bg-surface-dark/50 text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wider font-semibold">
                    <tr>
                        <th class="px-6 py-4">Título</th>
                        <th class="px-6 py-4">Estado</th>
                        <th class="px-6 py-4">Autor</th>
                        <th class="px-6 py-4">Fecha</th>
                        <th class="px-6 py-4">Vistas</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                    <?php foreach ($posts as $post): ?>
                        <tr class="group hover:bg-slate-50 dark:hover:bg-surface-dark/40 transition-colors">
                            <td class="px-6 py-4 font-medium text-slate-900 dark:text-white">
                                <a href="post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" target="_blank" class="text-primary hover:text-primary/80">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $statusColors = [
                                    'published' => ['bg' => 'bg-emerald-100 dark:bg-emerald-500/20', 'text' => 'text-emerald-700 dark:text-emerald-400'],
                                    'draft' => ['bg' => 'bg-amber-100 dark:bg-amber-500/20', 'text' => 'text-amber-700 dark:text-amber-400'],
                                    'archived' => ['bg' => 'bg-slate-100 dark:bg-slate-700', 'text' => 'text-slate-700 dark:text-slate-300']
                                ];
                                $statusInfo = $statusColors[$post['status']] ?? $statusColors['draft'];
                                $statusLabels = ['published' => 'Publicado', 'draft' => 'Borrador', 'archived' => 'Archivado'];
                                ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold <?php echo $statusInfo['bg'] . ' ' . $statusInfo['text']; ?>">
                                    <span class="w-1.5 h-1.5 rounded-full <?php echo str_replace(['/20', 'bg-'], ['', 'bg-'], $statusInfo['bg']); ?>"></span>
                                    <?php echo $statusLabels[$post['status']] ?? 'Borrador'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400"><?php echo htmlspecialchars($post['author_name']); ?></td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400">
                                <?php echo $post['published_at'] ? date('d/m/Y', strtotime($post['published_at'])) : '-'; ?>
                            </td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400"><?php echo number_format($post['views'] ?? 0); ?></td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="admin_web.php?tab=blog&action=edit&id=<?php echo $post['id']; ?>" 
                                       class="text-primary hover:text-primary/80 font-medium text-sm flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[16px]">edit</span>
                                        <span>Editar</span>
                                    </a>
                                    <a href="post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" 
                                       target="_blank"
                                       class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 font-medium text-sm flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[16px]">visibility</span>
                                        <span>Ver</span>
                                    </a>
                                    <form method="POST" action="admin_blog.php" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar este artículo?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
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

<?php elseif ($blogAction === 'new' || $blogAction === 'edit'): ?>
    <!-- Create/Edit Form -->
    <div class="mb-6">
        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6">
            <?php echo $blogAction === 'edit' ? 'Editar Artículo' : 'Nuevo Artículo'; ?>
        </h3>
        
        <!-- Redirect form to admin_blog.php for processing, then back to admin_web.php -->
        <form method="POST" action="admin_blog.php" id="postForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?php echo $blogAction === 'edit' ? 'update' : 'create'; ?>">
            <input type="hidden" name="redirect_to" value="admin_web.php?tab=blog">
            <?php if ($blogAction === 'edit' && $editPost): ?>
                <input type="hidden" name="id" value="<?php echo $editPost['id']; ?>">
            <?php endif; ?>

            <div class="space-y-6">
                <!-- Title -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Título *</label>
                    <input type="text" name="title" required
                           value="<?php echo htmlspecialchars($editPost['title'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <!-- Slug -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Slug (URL)</label>
                    <input type="text" name="slug" id="slug"
                           value="<?php echo htmlspecialchars($editPost['slug'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Se genera automáticamente desde el título si se deja vacío</p>
                </div>

                <!-- Excerpt -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Resumen</label>
                    <textarea name="excerpt" rows="3"
                              class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"><?php echo htmlspecialchars($editPost['excerpt'] ?? ''); ?></textarea>
                </div>

                <!-- Content -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Contenido *</label>
                    <textarea name="content" id="content" required
                              class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"><?php echo htmlspecialchars($editPost['content'] ?? ''); ?></textarea>
                </div>

                <!-- Featured Image -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Imagen Destacada</label>
                    <input type="text" name="featured_image" 
                           value="<?php echo htmlspecialchars($editPost['featured_image'] ?? ''); ?>"
                           placeholder="URL de la imagen"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary mb-2">
                    <input type="file" name="featured_image_file" accept="image/*"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Estado</label>
                    <select name="status" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="draft" <?php echo ($editPost['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Borrador</option>
                        <option value="published" <?php echo ($editPost['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Publicado</option>
                        <option value="archived" <?php echo ($editPost['status'] ?? '') === 'archived' ? 'selected' : ''; ?>>Archivado</option>
                    </select>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                    <button type="submit" class="flex items-center gap-2 h-10 px-5 bg-primary hover:bg-primary/90 text-white rounded-lg font-bold text-sm shadow-lg shadow-primary/25 transition-all">
                        <span class="material-symbols-outlined text-[20px]">save</span>
                        <span>Guardar Artículo</span>
                    </button>
                    <a href="admin_web.php?tab=blog" class="flex items-center gap-2 h-10 px-5 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg font-medium text-sm hover:bg-slate-50 dark:hover:bg-surface-dark transition-all">
                        <span>Cancelar</span>
                    </a>
                </div>
            </div>
        </form>

        <script>
            // Auto-generate slug from title
            document.querySelector('input[name="title"]')?.addEventListener('input', function() {
                const slugInput = document.getElementById('slug');
                if (!slugInput.value || slugInput.dataset.autoGenerated === 'true') {
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
            if (document.getElementById('content')) {
                CKEDITOR.replace('content', {
                    height: 400,
                    toolbar: [
                        { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike'] },
                        { name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Blockquote'] },
                        { name: 'links', items: ['Link', 'Unlink'] },
                        { name: 'insert', items: ['Image'] },
                        { name: 'styles', items: ['Format'] },
                        { name: 'colors', items: ['TextColor', 'BGColor'] },
                        { name: 'tools', items: ['Source'] }
                    ]
                });
            }
        </script>
    </div>
<?php endif; ?>

