<?php
/**
 * SOPHEA - Testimonials Management Class
 *
 * Handles all testimonial operations (CRUD)
 */

require_once __DIR__ . '/Database.php';

class Testimonials {
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
        
        // Ensure uniqueness
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
            // Validate required fields
            if (empty($data['client_name'])) {
                throw new Exception('El nombre del cliente es obligatorio');
            }
            
            if (empty($data['testimonial_text'])) {
                throw new Exception('El testimonio es obligatorio');
            }
            
            $slug = isset($data['slug']) && !empty($data['slug']) 
                ? $data['slug'] 
                : $this->generateSlug($data['client_name']);
            
            $sql = "INSERT INTO testimonials 
                    (client_name, client_title, client_company, client_location, client_avatar,
                     testimonial_text, full_story, slug, featured_image, status, featured, display_order,
                     metric1_label, metric1_value, metric1_color,
                     metric2_label, metric2_value, metric2_color,
                     metric3_label, metric3_value, metric3_color,
                     services_used, sector, meta_title, meta_description, meta_keywords, published_at) 
                    VALUES 
                    (:client_name, :client_title, :client_company, :client_location, :client_avatar,
                     :testimonial_text, :full_story, :slug, :featured_image, :status, :featured, :display_order,
                     :metric1_label, :metric1_value, :metric1_color,
                     :metric2_label, :metric2_value, :metric2_color,
                     :metric3_label, :metric3_value, :metric3_color,
                     :services_used, :sector, :meta_title, :meta_description, :meta_keywords, :published_at)";
            
            $stmt = $this->db->prepare($sql);
            
            $publishedAt = null;
            if (isset($data['status']) && $data['status'] === 'published') {
                $publishedAt = isset($data['published_at']) && !empty($data['published_at'])
                    ? $data['published_at']
                    : date('Y-m-d H:i:s');
            }
            
            $params = [
                ':client_name' => $data['client_name'],
                ':client_title' => $data['client_title'] ?? null,
                ':client_company' => $data['client_company'] ?? null,
                ':client_location' => $data['client_location'] ?? null,
                ':client_avatar' => $data['client_avatar'] ?? null,
                ':testimonial_text' => $data['testimonial_text'],
                ':full_story' => $data['full_story'] ?? null,
                ':slug' => $slug,
                ':featured_image' => $data['featured_image'] ?? null,
                ':status' => $data['status'] ?? 'draft',
                ':featured' => isset($data['featured']) ? (int)$data['featured'] : 0,
                ':display_order' => $data['display_order'] ?? 0,
                ':metric1_label' => $data['metric1_label'] ?? null,
                ':metric1_value' => $data['metric1_value'] ?? null,
                ':metric1_color' => $data['metric1_color'] ?? 'purple',
                ':metric2_label' => $data['metric2_label'] ?? null,
                ':metric2_value' => $data['metric2_value'] ?? null,
                ':metric2_color' => $data['metric2_color'] ?? 'blue',
                ':metric3_label' => $data['metric3_label'] ?? null,
                ':metric3_value' => $data['metric3_value'] ?? null,
                ':metric3_color' => $data['metric3_color'] ?? 'green',
                ':services_used' => $data['services_used'] ?? null,
                ':sector' => $data['sector'] ?? 'general',
                ':meta_title' => $data['meta_title'] ?? null,
                ':meta_description' => $data['meta_description'] ?? null,
                ':meta_keywords' => $data['meta_keywords'] ?? null,
                ':published_at' => $publishedAt
            ];
            
            $result = $stmt->execute($params);
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception('Error en la base de datos: ' . ($errorInfo[2] ?? 'Error desconocido'));
            }
            
            $testimonialId = $this->db->lastInsertId();
            
            if (!$testimonialId) {
                throw new Exception('No se pudo obtener el ID del testimonio creado');
            }
            
            // Handle images if provided
            if (isset($data['images']) && is_array($data['images']) && !empty($data['images'])) {
                $imageResult = $this->setTestimonialImages($testimonialId, $data['images']);
                if (!$imageResult) {
                    error_log("Warning: Testimonial created but images could not be saved (ID: $testimonialId)");
                }
            }
            
            return $testimonialId;
            
        } catch (PDOException $e) {
            error_log("PDO Error creating testimonial: " . $e->getMessage());
            error_log("SQL State: " . $e->getCode());
            throw new Exception('Error de base de datos: ' . $e->getMessage());
        } catch (Exception $e) {
            error_log("Error creating testimonial: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update a testimonial
     */
    public function updateTestimonial($id, $data) {
        try {
            // Validate required fields
            if (empty($data['client_name'])) {
                throw new Exception('El nombre del cliente es obligatorio');
            }
            
            if (empty($data['testimonial_text'])) {
                throw new Exception('El testimonio es obligatorio');
            }
            
            $sql = "UPDATE testimonials SET
                    client_name = :client_name,
                    client_title = :client_title,
                    client_company = :client_company,
                    client_location = :client_location,
                    client_avatar = :client_avatar,
                    testimonial_text = :testimonial_text,
                    full_story = :full_story,
                    slug = :slug,
                    featured_image = :featured_image,
                    status = :status,
                    featured = :featured,
                    display_order = :display_order,
                    metric1_label = :metric1_label,
                    metric1_value = :metric1_value,
                    metric1_color = :metric1_color,
                    metric2_label = :metric2_label,
                    metric2_value = :metric2_value,
                    metric2_color = :metric2_color,
                    metric3_label = :metric3_label,
                    metric3_value = :metric3_value,
                    metric3_color = :metric3_color,
                    services_used = :services_used,
                    sector = :sector,
                    meta_title = :meta_title,
                    meta_description = :meta_description,
                    meta_keywords = :meta_keywords,
                    published_at = :published_at
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            
            $publishedAt = null;
            if (isset($data['status']) && $data['status'] === 'published') {
                $publishedAt = isset($data['published_at']) && !empty($data['published_at'])
                    ? $data['published_at']
                    : date('Y-m-d H:i:s');
            }
            
            $params = [
                ':id' => $id,
                ':client_name' => $data['client_name'],
                ':client_title' => $data['client_title'] ?? null,
                ':client_company' => $data['client_company'] ?? null,
                ':client_location' => $data['client_location'] ?? null,
                ':client_avatar' => $data['client_avatar'] ?? null,
                ':testimonial_text' => $data['testimonial_text'],
                ':full_story' => $data['full_story'] ?? null,
                ':slug' => $data['slug'] ?? $this->generateSlug($data['client_name']),
                ':featured_image' => $data['featured_image'] ?? null,
                ':status' => $data['status'] ?? 'draft',
                ':featured' => isset($data['featured']) ? (int)$data['featured'] : 0,
                ':display_order' => $data['display_order'] ?? 0,
                ':metric1_label' => $data['metric1_label'] ?? null,
                ':metric1_value' => $data['metric1_value'] ?? null,
                ':metric1_color' => $data['metric1_color'] ?? 'purple',
                ':metric2_label' => $data['metric2_label'] ?? null,
                ':metric2_value' => $data['metric2_value'] ?? null,
                ':metric2_color' => $data['metric2_color'] ?? 'blue',
                ':metric3_label' => $data['metric3_label'] ?? null,
                ':metric3_value' => $data['metric3_value'] ?? null,
                ':metric3_color' => $data['metric3_color'] ?? 'green',
                ':services_used' => $data['services_used'] ?? null,
                ':sector' => $data['sector'] ?? 'general',
                ':meta_title' => $data['meta_title'] ?? null,
                ':meta_description' => $data['meta_description'] ?? null,
                ':meta_keywords' => $data['meta_keywords'] ?? null,
                ':published_at' => $publishedAt
            ];
            
            $result = $stmt->execute($params);
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception('Error en la base de datos: ' . ($errorInfo[2] ?? 'Error desconocido'));
            }
            
            if (isset($data['images']) && is_array($data['images'])) {
                $imageResult = $this->setTestimonialImages($id, $data['images']);
                if (!$imageResult) {
                    error_log("Warning: Testimonial updated but images could not be saved (ID: $id)");
                }
            }
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("PDO Error updating testimonial: " . $e->getMessage());
            error_log("SQL State: " . $e->getCode());
            throw new Exception('Error de base de datos: ' . $e->getMessage());
        } catch (Exception $e) {
            error_log("Error updating testimonial: " . $e->getMessage());
            throw $e;
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
            
            $testimonial = $stmt->fetch();
            if ($testimonial) {
                $testimonial['images'] = $this->getTestimonialImages($id);
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
            $sql = "SELECT * FROM testimonials WHERE slug = :slug";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':slug' => $slug]);
            
            $testimonial = $stmt->fetch();
            if ($testimonial) {
                $testimonial['images'] = $this->getTestimonialImages($testimonial['id']);
            }
            
            return $testimonial;
            
        } catch (PDOException $e) {
            error_log("Error fetching testimonial: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get published testimonials
     */
    public function getPublishedTestimonials($limit = 10, $offset = 0, $featured = false) {
        try {
            $sql = "SELECT * FROM testimonials WHERE status = 'published'";
            
            if ($featured) {
                $sql .= " AND featured = 1";
            }
            
            // Only include testimonials with published_at date (or allow NULL for flexibility)
            // $sql .= " AND published_at IS NOT NULL";
            
            $sql .= " ORDER BY display_order ASC, published_at DESC, created_at DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $testimonials = $stmt->fetchAll();
            
            // Get images for each testimonial
            foreach ($testimonials as &$testimonial) {
                $testimonial['images'] = $this->getTestimonialImages($testimonial['id']);
            }
            
            return $testimonials;
            
        } catch (PDOException $e) {
            error_log("Error fetching testimonials: " . $e->getMessage());
            error_log("SQL: " . $sql);
            return [];
        }
    }

    /**
     * Get all testimonials (for admin)
     */
    public function getAllTestimonials($limit = 50, $offset = 0) {
        try {
            $sql = "SELECT * FROM testimonials ORDER BY display_order ASC, created_at DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
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

    /**
     * Get testimonial images
     */
    public function getTestimonialImages($testimonialId) {
        try {
            $sql = "SELECT * FROM testimonial_images 
                    WHERE testimonial_id = :testimonial_id 
                    ORDER BY display_order ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':testimonial_id' => $testimonialId]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Error fetching testimonial images: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Set testimonial images
     */
    public function setTestimonialImages($testimonialId, $images) {
        try {
            // Delete existing images
            $sql = "DELETE FROM testimonial_images WHERE testimonial_id = :testimonial_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':testimonial_id' => $testimonialId]);
            
            // Insert new images
            if (!empty($images)) {
                $sql = "INSERT INTO testimonial_images (testimonial_id, image_path, image_alt, display_order) 
                        VALUES (:testimonial_id, :image_path, :image_alt, :display_order)";
                $stmt = $this->db->prepare($sql);
                
                foreach ($images as $index => $image) {
                    $stmt->execute([
                        ':testimonial_id' => $testimonialId,
                        ':image_path' => $image['path'],
                        ':image_alt' => $image['alt'] ?? '',
                        ':display_order' => $image['order'] ?? $index
                    ]);
                }
            }
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Error setting testimonial images: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Increment testimonial views
     */
    public function incrementViews($testimonialId) {
        try {
            $sql = "UPDATE testimonials SET views = views + 1 WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $testimonialId]);
            
        } catch (PDOException $e) {
            error_log("Error incrementing views: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get testimonials count
     */
    public function getPublishedCount($featured = false) {
        try {
            $sql = "SELECT COUNT(*) as total FROM testimonials WHERE status = 'published'";
            
            if ($featured) {
                $sql .= " AND featured = 1";
            }
            
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch();
            
            return $result['total'] ?? 0;
            
        } catch (PDOException $e) {
            error_log("Error counting testimonials: " . $e->getMessage());
            return 0;
        }
    }
}
