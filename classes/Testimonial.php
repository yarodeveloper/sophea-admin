<?php
/**
 * SOPHEA - Testimonial Management Class
 *
 * Handles all testimonial/case study operations (CRUD)
 */

require_once __DIR__ . '/Database.php';

class Testimonial {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Generate URL-friendly slug from client name
     */
    public function generateSlug($name) {
        $slug = strtolower($name);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        $originalSlug = $slug;
        $counter = 1;
        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Check if slug already exists
     */
    private function slugExists($slug, $excludeId = null) {
        $sql = "SELECT id FROM testimonials WHERE slug = :slug";
        $params = [':slug' => $slug];
        
        if ($excludeId !== null) {
            $sql .= " AND id != :id";
            $params[':id'] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch() !== false;
    }

    /**
     * Create a new testimonial
     */
    public function createTestimonial($data) {
        try {
            $slug = isset($data['slug']) && !empty($data['slug']) 
                ? $data['slug'] 
                : $this->generateSlug($data['client_name']);
            
            // Handle gallery images (convert array to JSON)
            $galleryImages = '';
            if (isset($data['gallery_images']) && is_array($data['gallery_images'])) {
                $galleryImages = json_encode($data['gallery_images']);
            } elseif (isset($data['gallery_images']) && is_string($data['gallery_images'])) {
                $galleryImages = $data['gallery_images'];
            }
            
            $sql = "INSERT INTO testimonials 
                    (client_name, client_title, client_business, client_location, client_initials, slug,
                     testimonial_text, full_story, featured_image, gallery_images,
                     metric_1_label, metric_1_value, metric_1_color,
                     metric_2_label, metric_2_value, metric_2_color,
                     metric_3_label, metric_3_value, metric_3_color,
                     services_used, sector, status, featured, display_order, published_at) 
                    VALUES 
                    (:client_name, :client_title, :client_business, :client_location, :client_initials, :slug,
                     :testimonial_text, :full_story, :featured_image, :gallery_images,
                     :metric_1_label, :metric_1_value, :metric_1_color,
                     :metric_2_label, :metric_2_value, :metric_2_color,
                     :metric_3_label, :metric_3_value, :metric_3_color,
                     :services_used, :sector, :status, :featured, :display_order, :published_at)";
            
            $stmt = $this->db->prepare($sql);
            
            $publishedAt = null;
            if (isset($data['status']) && $data['status'] === 'published') {
                $publishedAt = isset($data['published_at']) && !empty($data['published_at'])
                    ? $data['published_at']
                    : date('Y-m-d H:i:s');
            }
            
            $result = $stmt->execute([
                ':client_name' => $data['client_name'],
                ':client_title' => $data['client_title'] ?? null,
                ':client_business' => $data['client_business'] ?? null,
                ':client_location' => $data['client_location'] ?? null,
                ':client_initials' => $data['client_initials'] ?? null,
                ':slug' => $slug,
                ':testimonial_text' => $data['testimonial_text'],
                ':full_story' => $data['full_story'] ?? null,
                ':featured_image' => $data['featured_image'] ?? null,
                ':gallery_images' => $galleryImages,
                ':metric_1_label' => $data['metric_1_label'] ?? null,
                ':metric_1_value' => $data['metric_1_value'] ?? null,
                ':metric_1_color' => $data['metric_1_color'] ?? 'purple',
                ':metric_2_label' => $data['metric_2_label'] ?? null,
                ':metric_2_value' => $data['metric_2_value'] ?? null,
                ':metric_2_color' => $data['metric_2_color'] ?? 'blue',
                ':metric_3_label' => $data['metric_3_label'] ?? null,
                ':metric_3_value' => $data['metric_3_value'] ?? null,
                ':metric_3_color' => $data['metric_3_color'] ?? 'green',
                ':services_used' => $data['services_used'] ?? null,
                ':sector' => $data['sector'] ?? 'general',
                ':status' => $data['status'] ?? 'draft',
                ':featured' => isset($data['featured']) ? (int)$data['featured'] : 0,
                ':display_order' => isset($data['display_order']) ? (int)$data['display_order'] : 0,
                ':published_at' => $publishedAt
            ]);
            
            return $result ? $this->db->lastInsertId() : false;
            
        } catch (PDOException $e) {
            error_log("Error creating testimonial: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update a testimonial
     */
    public function updateTestimonial($id, $data) {
        try {
            $slug = isset($data['slug']) && !empty($data['slug']) 
                ? $data['slug'] 
                : $this->generateSlug($data['client_name']);
            
            // Handle gallery images
            $galleryImages = '';
            if (isset($data['gallery_images']) && is_array($data['gallery_images'])) {
                $galleryImages = json_encode($data['gallery_images']);
            } elseif (isset($data['gallery_images']) && is_string($data['gallery_images'])) {
                $galleryImages = $data['gallery_images'];
            }
            
            $sql = "UPDATE testimonials SET
                    client_name = :client_name,
                    client_title = :client_title,
                    client_business = :client_business,
                    client_location = :client_location,
                    client_initials = :client_initials,
                    slug = :slug,
                    testimonial_text = :testimonial_text,
                    full_story = :full_story,
                    featured_image = :featured_image,
                    gallery_images = :gallery_images,
                    metric_1_label = :metric_1_label,
                    metric_1_value = :metric_1_value,
                    metric_1_color = :metric_1_color,
                    metric_2_label = :metric_2_label,
                    metric_2_value = :metric_2_value,
                    metric_2_color = :metric_2_color,
                    metric_3_label = :metric_3_label,
                    metric_3_value = :metric_3_value,
                    metric_3_color = :metric_3_color,
                    services_used = :services_used,
                    sector = :sector,
                    status = :status,
                    featured = :featured,
                    display_order = :display_order,
                    published_at = :published_at
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            
            $publishedAt = null;
            if (isset($data['status']) && $data['status'] === 'published') {
                $publishedAt = isset($data['published_at']) && !empty($data['published_at'])
                    ? $data['published_at']
                    : date('Y-m-d H:i:s');
            }
            
            return $stmt->execute([
                ':id' => $id,
                ':client_name' => $data['client_name'],
                ':client_title' => $data['client_title'] ?? null,
                ':client_business' => $data['client_business'] ?? null,
                ':client_location' => $data['client_location'] ?? null,
                ':client_initials' => $data['client_initials'] ?? null,
                ':slug' => $slug,
                ':testimonial_text' => $data['testimonial_text'],
                ':full_story' => $data['full_story'] ?? null,
                ':featured_image' => $data['featured_image'] ?? null,
                ':gallery_images' => $galleryImages,
                ':metric_1_label' => $data['metric_1_label'] ?? null,
                ':metric_1_value' => $data['metric_1_value'] ?? null,
                ':metric_1_color' => $data['metric_1_color'] ?? 'purple',
                ':metric_2_label' => $data['metric_2_label'] ?? null,
                ':metric_2_value' => $data['metric_2_value'] ?? null,
                ':metric_2_color' => $data['metric_2_color'] ?? 'blue',
                ':metric_3_label' => $data['metric_3_label'] ?? null,
                ':metric_3_value' => $data['metric_3_value'] ?? null,
                ':metric_3_color' => $data['metric_3_color'] ?? 'green',
                ':services_used' => $data['services_used'] ?? null,
                ':sector' => $data['sector'] ?? 'general',
                ':status' => $data['status'] ?? 'draft',
                ':featured' => isset($data['featured']) ? (int)$data['featured'] : 0,
                ':display_order' => isset($data['display_order']) ? (int)$data['display_order'] : 0,
                ':published_at' => $publishedAt
            ]);
            
        } catch (PDOException $e) {
            error_log("Error updating testimonial: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get testimonial by ID
     */
    public function getTestimonialById($id) {
        try {
            $sql = "SELECT * FROM testimonials WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $testimonial = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($testimonial && !empty($testimonial['gallery_images'])) {
                $testimonial['gallery_images'] = json_decode($testimonial['gallery_images'], true) ?: [];
            } else {
                $testimonial['gallery_images'] = [];
            }
            
            return $testimonial;
            
        } catch (PDOException $e) {
            error_log("Error fetching testimonial: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get testimonial by slug
     */
    public function getTestimonialBySlug($slug) {
        try {
            $sql = "SELECT * FROM testimonials WHERE slug = :slug AND status = 'published'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':slug' => $slug]);
            
            $testimonial = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($testimonial && !empty($testimonial['gallery_images'])) {
                $testimonial['gallery_images'] = json_decode($testimonial['gallery_images'], true) ?: [];
            } else {
                $testimonial['gallery_images'] = [];
            }
            
            return $testimonial;
            
        } catch (PDOException $e) {
            error_log("Error fetching testimonial: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get featured testimonials for homepage
     */
    public function getFeaturedTestimonials($limit = 6) {
        try {
            $sql = "SELECT * FROM testimonials 
                    WHERE status = 'published' AND featured = 1
                    ORDER BY display_order ASC, published_at DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($testimonials as &$testimonial) {
                if (!empty($testimonial['gallery_images'])) {
                    $testimonial['gallery_images'] = json_decode($testimonial['gallery_images'], true) ?: [];
                } else {
                    $testimonial['gallery_images'] = [];
                }
            }
            
            return $testimonials;
            
        } catch (PDOException $e) {
            error_log("Error fetching featured testimonials: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all published testimonials
     */
    public function getPublishedTestimonials($limit = 50, $offset = 0) {
        try {
            $sql = "SELECT * FROM testimonials 
                    WHERE status = 'published'
                    ORDER BY display_order ASC, published_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($testimonials as &$testimonial) {
                if (!empty($testimonial['gallery_images'])) {
                    $testimonial['gallery_images'] = json_decode($testimonial['gallery_images'], true) ?: [];
                } else {
                    $testimonial['gallery_images'] = [];
                }
            }
            
            return $testimonials;
            
        } catch (PDOException $e) {
            error_log("Error fetching testimonials: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all testimonials (for admin)
     */
    public function getAllTestimonials($limit = 100, $offset = 0) {
        try {
            $sql = "SELECT * FROM testimonials 
                    ORDER BY created_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($testimonials as &$testimonial) {
                if (!empty($testimonial['gallery_images'])) {
                    $testimonial['gallery_images'] = json_decode($testimonial['gallery_images'], true) ?: [];
                } else {
                    $testimonial['gallery_images'] = [];
                }
            }
            
            return $testimonials;
            
        } catch (PDOException $e) {
            error_log("Error fetching testimonials: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete a testimonial
     */
    public function deleteTestimonial($id) {
        try {
            $sql = "DELETE FROM testimonials WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
            
        } catch (PDOException $e) {
            error_log("Error deleting testimonial: " . $e->getMessage());
            return false;
        }
    }
}
