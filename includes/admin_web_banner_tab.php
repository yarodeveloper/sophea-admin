<?php
/**
 * Banner/Logo Tab Content for Admin Web Panel
 * 
 * This file contains the banner and logo management interface
 */

// Ensure variables are set
$currentBanner = $currentBanner ?? '';
$currentLogo = $currentLogo ?? '';
?>

<!-- Banner Section -->
<div class="mb-8">
    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
        <span class="material-symbols-outlined">image</span>
        <span>Banner Principal</span>
    </h3>
    
    <!-- Información de medidas -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
        <div class="flex items-start gap-2">
            <span class="material-symbols-outlined text-blue-600 dark:text-blue-400">info</span>
            <div>
                <p class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-1">Medidas Ideales para el Banner:</p>
                <ul class="text-xs text-blue-700 dark:text-blue-400 space-y-1 list-disc list-inside">
                    <li><strong>Recomendado:</strong> 1920 x 600 píxeles (ratio 16:5)</li>
                    <li><strong>Alternativa:</strong> 1920 x 800 píxeles (ratio 12:5)</li>
                    <li><strong>Mínimo:</strong> 1200 x 400 píxeles</li>
                    <li><strong>Formato:</strong> JPG, PNG, WEBP o GIF</li>
                    <li><strong>Tamaño máximo:</strong> 5MB</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Current Banner Preview -->
    <?php if ($currentBanner): ?>
        <div class="mb-6 p-4 bg-slate-50 dark:bg-surface-dark rounded-lg border border-slate-200 dark:border-slate-700">
            <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Banner Actual:</p>
            <img src="<?php echo htmlspecialchars($currentBanner); ?>" 
                 alt="Banner Actual" 
                 class="max-w-full h-auto rounded-lg border border-slate-300 dark:border-slate-600"
                 onerror="this.style.display='none';">
        </div>
    <?php endif; ?>

    <!-- Banner Form -->
    <form method="POST" action="admin_banner.php" enctype="multipart/form-data" class="space-y-4">
        <input type="hidden" name="action" value="update_banner">
        <input type="hidden" name="redirect_to" value="admin_web.php?tab=banner">
        
        <!-- Upload File -->
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                <span class="material-symbols-outlined align-middle mr-1">upload</span>
                Subir Banner desde tu Computadora
            </label>
            <input type="file" 
                   name="banner_image" 
                   id="banner_image"
                   accept="image/jpeg,image/png,image/jpg,image/webp,image/gif"
                   class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Selecciona una imagen desde tu escritorio</p>
            
            <!-- Preview -->
            <div id="banner-preview" class="mt-3 hidden">
                <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Vista Previa:</p>
                <img id="banner-preview-img" src="" alt="Preview" class="max-w-full h-auto rounded-lg border border-slate-300 dark:border-slate-600">
            </div>
        </div>
        
        <!-- Or URL -->
        <div class="border-t border-slate-200 dark:border-slate-700 pt-4">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                <span class="material-symbols-outlined align-middle mr-1">link</span>
                O Ingresa una URL de Imagen
            </label>
            <input type="url" 
                   name="banner_url"
                   id="banner_url"
                   value="<?php echo htmlspecialchars($currentBanner && strpos($currentBanner, 'http') === 0 ? $currentBanner : ''); ?>"
                   placeholder="https://ejemplo.com/banner.jpg"
                   class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Si prefieres usar una imagen desde internet</p>
        </div>
        
        <button type="submit" class="w-full bg-primary text-white px-6 py-3 rounded-lg hover:bg-primary/90 transition font-semibold flex items-center justify-center gap-2">
            <span class="material-symbols-outlined">save</span>
            <span>Guardar Banner</span>
        </button>
    </form>
</div>

<!-- Logo Section -->
<div class="border-t border-slate-200 dark:border-slate-800 pt-8">
    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
        <span class="material-symbols-outlined">image</span>
        <span>Logo del Sitio</span>
    </h3>
    
    <!-- Información de medidas -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
        <div class="flex items-start gap-2">
            <span class="material-symbols-outlined text-blue-600 dark:text-blue-400">info</span>
            <div>
                <p class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-1">Medidas Ideales para el Logo:</p>
                <ul class="text-xs text-blue-700 dark:text-blue-400 space-y-1 list-disc list-inside">
                    <li><strong>Recomendado:</strong> 300 x 300 píxeles (cuadrado) o 400 x 200 píxeles (rectangular)</li>
                    <li><strong>Formato:</strong> PNG con transparencia, SVG, JPG</li>
                    <li><strong>Tamaño máximo:</strong> 2MB</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Current Logo Preview -->
    <?php if ($currentLogo): ?>
        <div class="mb-6 p-4 bg-slate-50 dark:bg-surface-dark rounded-lg border border-slate-200 dark:border-slate-700">
            <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Logo Actual:</p>
            <img src="<?php echo htmlspecialchars($currentLogo); ?>" 
                 alt="Logo Actual" 
                 class="max-w-xs h-auto rounded-lg border border-slate-300 dark:border-slate-600"
                 onerror="this.style.display='none';">
        </div>
    <?php endif; ?>

    <!-- Logo Form -->
    <form method="POST" action="admin_banner.php" enctype="multipart/form-data" class="space-y-4">
        <input type="hidden" name="action" value="update_logo">
        <input type="hidden" name="redirect_to" value="admin_web.php?tab=banner">
        
        <!-- Upload File -->
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                <span class="material-symbols-outlined align-middle mr-1">upload</span>
                Subir Logo desde tu Computadora
            </label>
            <input type="file" 
                   name="logo_image" 
                   id="logo_image"
                   accept="image/jpeg,image/png,image/jpg,image/webp,image/svg+xml"
                   class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Selecciona una imagen desde tu escritorio</p>
            
            <!-- Preview -->
            <div id="logo-preview" class="mt-3 hidden">
                <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Vista Previa:</p>
                <img id="logo-preview-img" src="" alt="Preview" class="max-w-xs h-auto rounded-lg border border-slate-300 dark:border-slate-600">
            </div>
        </div>
        
        <!-- Or URL -->
        <div class="border-t border-slate-200 dark:border-slate-700 pt-4">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                <span class="material-symbols-outlined align-middle mr-1">link</span>
                O Ingresa una URL de Imagen
            </label>
            <input type="url" 
                   name="logo_url"
                   id="logo_url"
                   value="<?php echo htmlspecialchars($currentLogo && strpos($currentLogo, 'http') === 0 ? $currentLogo : ''); ?>"
                   placeholder="https://ejemplo.com/logo.png"
                   class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-card-dark text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Si prefieres usar una imagen desde internet</p>
        </div>
        
        <button type="submit" class="w-full bg-primary text-white px-6 py-3 rounded-lg hover:bg-primary/90 transition font-semibold flex items-center justify-center gap-2">
            <span class="material-symbols-outlined">save</span>
            <span>Guardar Logo</span>
        </button>
    </form>
</div>

<script>
    // Banner preview
    const bannerInput = document.getElementById('banner_image');
    const bannerPreview = document.getElementById('banner-preview');
    const bannerPreviewImg = document.getElementById('banner-preview-img');
    const bannerUrlInput = document.getElementById('banner_url');
    
    if (bannerInput) {
        bannerInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    bannerPreviewImg.src = e.target.result;
                    bannerPreview.classList.remove('hidden');
                    if (bannerUrlInput) bannerUrlInput.value = '';
                };
                reader.readAsDataURL(file);
            } else {
                bannerPreview.classList.add('hidden');
            }
        });
    }
    
    if (bannerUrlInput) {
        bannerUrlInput.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                bannerPreview.classList.add('hidden');
                if (bannerInput) bannerInput.value = '';
            }
        });
    }
    
    // Logo preview
    const logoInput = document.getElementById('logo_image');
    const logoPreview = document.getElementById('logo-preview');
    const logoPreviewImg = document.getElementById('logo-preview-img');
    const logoUrlInput = document.getElementById('logo_url');
    
    if (logoInput) {
        logoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    logoPreviewImg.src = e.target.result;
                    logoPreview.classList.remove('hidden');
                    if (logoUrlInput) logoUrlInput.value = '';
                };
                reader.readAsDataURL(file);
            } else {
                logoPreview.classList.add('hidden');
            }
        });
    }
    
    if (logoUrlInput) {
        logoUrlInput.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                logoPreview.classList.add('hidden');
                if (logoInput) logoInput.value = '';
            }
        });
    }
</script>

