$files = @(
    "admin.php",
    "admin_banner.php",
    "admin_blog.php",
    "admin_clients.php",
    "admin_dashboard.php",
    "admin_expenses.php",
    "admin_invoice_history.php",
    "admin_payments.php",
    "admin_services.php",
    "admin_testimonials.php"
)

$startPattern = '(?s)<!-- Sidebar[^>]*?>\s*<\?php include ''includes/admin_sidebar\.php''; \?>\s*<div class="relative flex h-screen w-full overflow-hidden">\s*<!-- Spacer for sidebar on desktop -->\s*<div class="hidden md:block w-64 flex-shrink-0"></div>\s*<!-- Main Content -->\s*<main class="flex-1 overflow-y-auto custom-scrollbar bg-background-light dark:bg-background-dark p-6 lg:p-10">\s*<!-- Mobile Menu Button -->\s*<button id="sidebar-toggle-btn"[^>]*?>\s*<span class="material-symbols-outlined text-2xl">menu</span>\s*</button>\s*<div class="mx-auto max-w-\[1400px\]">'

$startReplacement = "<?php include 'includes/layout_start.php'; ?>"

$endPattern = '(?s)\s*</div>\s*</main>\s*</div>\s*<\?php include ''includes/admin_footer\.php''; \?>'
$endReplacement = "`r`n<?php include 'includes/layout_end.php'; ?>"

foreach ($file in $files) {
    if (Test-Path $file) {
        $content = [System.IO.File]::ReadAllText("$PWD\$file")
        
        $newContent = [regex]::Replace($content, $startPattern, $startReplacement)
        $newContent = [regex]::Replace($newContent, $endPattern, $endReplacement)
        
        if ($content -ne $newContent) {
            [System.IO.File]::WriteAllText("$PWD\$file", $newContent, [System.Text.Encoding]::UTF8)
            Write-Host "Updated $file"
        } else {
            Write-Host "No changes needed for $file or pattern didn't match"
        }
    }
}
