<?php
/**
 * SOPHEA - Debug Testimonials
 * 
 * Script to debug why testimonials are not showing
 */

require_once 'config.php';
require_once 'config_db.php';
require_once 'classes/Testimonials.php';

$testimonials = new Testimonials();

echo "<h2>Debug de Testimonios</h2>";

// Get all testimonials
$all = $testimonials->getAllTestimonials(100);
echo "<h3>Total de testimonios: " . count($all) . "</h3>";

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Nombre</th><th>Status</th><th>Featured</th><th>Published At</th></tr>";

foreach ($all as $t) {
    echo "<tr>";
    echo "<td>" . $t['id'] . "</td>";
    echo "<td>" . htmlspecialchars($t['client_name']) . "</td>";
    echo "<td>" . $t['status'] . "</td>";
    echo "<td>" . ($t['featured'] ? 'Sí' : 'No') . "</td>";
    echo "<td>" . ($t['published_at'] ?? 'NULL') . "</td>";
    echo "</tr>";
}

echo "</table>";

// Test featured query
echo "<h3>Testimonios Destacados (featured=1, status=published):</h3>";
$featured = $testimonials->getPublishedTestimonials(10, 0, true);
echo "<p>Cantidad: " . count($featured) . "</p>";
if (!empty($featured)) {
    foreach ($featured as $t) {
        echo "<p>- " . htmlspecialchars($t['client_name']) . " (Featured: " . ($t['featured'] ? 'Sí' : 'No') . ", Status: " . $t['status'] . ")</p>";
    }
} else {
    echo "<p style='color:red;'>No hay testimonios destacados</p>";
}

// Test published query
echo "<h3>Testimonios Publicados (status=published, sin filtro featured):</h3>";
$published = $testimonials->getPublishedTestimonials(10, 0, false);
echo "<p>Cantidad: " . count($published) . "</p>";
if (!empty($published)) {
    foreach ($published as $t) {
        echo "<p>- " . htmlspecialchars($t['client_name']) . " (Featured: " . ($t['featured'] ? 'Sí' : 'No') . ", Status: " . $t['status'] . ")</p>";
    }
} else {
    echo "<p style='color:red;'>No hay testimonios publicados</p>";
}
?>
