<?php
/**
 * SOPHEA - Blog Management Class
 * 
 * Handles all blog post operations (CRUD)
 */

require_once __DIR__ . '/Database.php';

class Blog {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Generate URL-friendly slug from title
     */
    public function generateSlug($title) {
        // Convert to lowercase
        $slug = strtolower($title);
        
        // Replace spaces and special characters with hyphens
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        
        // Remove leading/trailing hyphens
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
        $sql = "SELECT id FROM blog_posts WHERE slug = :slug";
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
     * Create a new blog post
     */
    public function createPost($data) {
        try {
            $slug = isset($data['slug']) && !empty($data['slug']) 
                ? $data['slug'] 
                : $this->generateSlug($data['title']);
            
            $sql = "INSERT INTO blog_posts 
                    (title, slug, excerpt, content, featured_image, author_name, status, published_at, meta_title, meta_description, meta_keywords) 
                    VALUES 
                    (:title, :slug, :excerpt, :content, :featured_image, :author_name, :status, :published_at, :meta_title, :meta_description, :meta_keywords)";
            
            $stmt = $this->db->prepare($sql);
            
            $publishedAt = null;
            if (isset($data['status']) && $data['status'] === 'published') {
                $publishedAt = isset($data['published_at']) && !empty($data['published_at'])
                    ? $data['published_at']
                    : date('Y-m-d H:i:s');
            }
            
            $result = $stmt->execute([
                ':title' => $data['title'],
                ':slug' => $slug,
                ':excerpt' => $data['excerpt'] ?? null,
                ':content' => $data['content'],
                ':featured_image' => $data['featured_image'] ?? null,
                ':author_name' => $data['author_name'] ?? 'SOPHEA',
                ':status' => $data['status'] ?? 'draft',
                ':published_at' => $publishedAt,
                ':meta_title' => $data['meta_title'] ?? null,
                ':meta_description' => $data['meta_description'] ?? null,
                ':meta_keywords' => $data['meta_keywords'] ?? null
            ]);
            
            if ($result) {
                $postId = $this->db->lastInsertId();
                
                // Handle categories if provided
                if (isset($data['categories']) && is_array($data['categories'])) {
                    $this->setPostCategories($postId, $data['categories']);
                }
                
                return $postId;
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Error creating blog post: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update an existing blog post
     */
    public function updatePost($id, $data) {
        try {
            $slug = isset($data['slug']) && !empty($data['slug']) 
                ? $data['slug'] 
                : $this->generateSlug($data['title']);
            
            // Check if slug exists for another post
            if ($this->slugExists($slug, $id)) {
                $slug = $this->generateSlug($data['title'] . '-' . $id);
            }
            
            $sql = "UPDATE blog_posts SET 
                    title = :title,
                    slug = :slug,
                    excerpt = :excerpt,
                    content = :content,
                    featured_image = :featured_image,
                    author_name = :author_name,
                    status = :status,
                    published_at = :published_at,
                    meta_title = :meta_title,
                    meta_description = :meta_description,
                    meta_keywords = :meta_keywords
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            
            $publishedAt = null;
            if (isset($data['status']) && $data['status'] === 'published') {
                // If already published, keep original date, otherwise set new date
                $existing = $this->getPostById($id);
                if ($existing && $existing['published_at']) {
                    $publishedAt = $existing['published_at'];
                } else {
                    $publishedAt = isset($data['published_at']) && !empty($data['published_at'])
                        ? $data['published_at']
                        : date('Y-m-d H:i:s');
                }
            }
            
            $result = $stmt->execute([
                ':id' => $id,
                ':title' => $data['title'],
                ':slug' => $slug,
                ':excerpt' => $data['excerpt'] ?? null,
                ':content' => $data['content'],
                ':featured_image' => $data['featured_image'] ?? null,
                ':author_name' => $data['author_name'] ?? 'SOPHEA',
                ':status' => $data['status'] ?? 'draft',
                ':published_at' => $publishedAt,
                ':meta_title' => $data['meta_title'] ?? null,
                ':meta_description' => $data['meta_description'] ?? null,
                ':meta_keywords' => $data['meta_keywords'] ?? null
            ]);
            
            if ($result && isset($data['categories']) && is_array($data['categories'])) {
                $this->setPostCategories($id, $data['categories']);
            }
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("Error updating blog post: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get post by ID
     */
    public function getPostById($id) {
        try {
            $sql = "SELECT * FROM blog_posts WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $post = $stmt->fetch();
            
            if ($post) {
                $post['categories'] = $this->getPostCategories($id);
            }
            
            return $post;
            
        } catch (PDOException $e) {
            error_log("Error fetching blog post: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get post by slug
     */
    public function getPostBySlug($slug) {
        try {
            $sql = "SELECT * FROM blog_posts WHERE slug = :slug";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':slug' => $slug]);
            
            $post = $stmt->fetch();
            
            if ($post) {
                $post['categories'] = $this->getPostCategories($post['id']);
                
                // Increment views
                $this->incrementViews($post['id']);
            }
            
            return $post;
            
        } catch (PDOException $e) {
            error_log("Error fetching blog post: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all published posts
     */
    public function getPublishedPosts($limit = 10, $offset = 0, $categoryId = null, $searchQuery = null, $year = null, $month = null) {
        try {
            $sql = "SELECT DISTINCT p.* FROM blog_posts p";
            $params = [];
            $conditions = ["p.status = 'published'"];
            
            // Category filter
            if ($categoryId !== null) {
                $sql .= " INNER JOIN blog_post_categories pc ON p.id = pc.post_id";
                $conditions[] = "pc.category_id = :category_id";
                $params[':category_id'] = $categoryId;
            }
            
            // Search filter
            if ($searchQuery !== null && !empty(trim($searchQuery))) {
                $conditions[] = "(MATCH(p.title, p.content, p.excerpt) AGAINST(:search_query IN NATURAL LANGUAGE MODE)
                                 OR p.title LIKE :search_like 
                                 OR p.content LIKE :search_like
                                 OR p.excerpt LIKE :search_like)";
                $params[':search_query'] = $searchQuery;
                $params[':search_like'] = '%' . $searchQuery . '%';
            }
            
            // Date filters
            if ($year !== null) {
                $conditions[] = "YEAR(p.published_at) = :year";
                $params[':year'] = $year;
            }
            
            if ($month !== null && $year !== null) {
                $conditions[] = "MONTH(p.published_at) = :month";
                $params[':month'] = $month;
            }
            
            $sql .= " WHERE " . implode(" AND ", $conditions);
            $sql .= " ORDER BY p.published_at DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            
            // Bind all parameters
            foreach ($params as $key => $value) {
                if (strpos($key, ':category_id') !== false || strpos($key, ':year') !== false || strpos($key, ':month') !== false) {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $posts = $stmt->fetchAll();
            
            // Get categories for each post
            foreach ($posts as &$post) {
                $post['categories'] = $this->getPostCategories($post['id']);
            }
            
            return $posts;
            
        } catch (PDOException $e) {
            error_log("Error fetching blog posts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get available years and months for filtering
     */
    public function getAvailableDates() {
        try {
            $sql = "SELECT DISTINCT 
                    YEAR(published_at) as year,
                    MONTH(published_at) as month,
                    DATE_FORMAT(published_at, '%M') as month_name
                    FROM blog_posts 
                    WHERE status = 'published' AND published_at IS NOT NULL
                    ORDER BY year DESC, month DESC";
            
            $stmt = $this->db->query($sql);
            $results = $stmt->fetchAll();
            
            $dates = [];
            foreach ($results as $row) {
                if (!isset($dates[$row['year']])) {
                    $dates[$row['year']] = [];
                }
                $dates[$row['year']][] = [
                    'month' => $row['month'],
                    'month_name' => $row['month_name']
                ];
            }
            
            return $dates;
            
        } catch (PDOException $e) {
            error_log("Error fetching available dates: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all posts (including drafts) - for admin
     */
    public function getAllPosts($limit = 50, $offset = 0) {
        try {
            $sql = "SELECT * FROM blog_posts ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $posts = $stmt->fetchAll();
            
            // Get categories for each post
            foreach ($posts as &$post) {
                $post['categories'] = $this->getPostCategories($post['id']);
            }
            
            return $posts;
            
        } catch (PDOException $e) {
            error_log("Error fetching blog posts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Delete a blog post
     */
    public function deletePost($id) {
        try {
            $sql = "DELETE FROM blog_posts WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
            
        } catch (PDOException $e) {
            error_log("Error deleting blog post: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get post categories
     */
    public function getPostCategories($postId) {
        try {
            $sql = "SELECT c.* FROM blog_categories c
                    INNER JOIN blog_post_categories pc ON c.id = pc.category_id
                    WHERE pc.post_id = :post_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':post_id' => $postId]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Error fetching post categories: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Set post categories
     */
    public function setPostCategories($postId, $categoryIds) {
        try {
            // Delete existing categories
            $sql = "DELETE FROM blog_post_categories WHERE post_id = :post_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':post_id' => $postId]);
            
            // Insert new categories
            if (!empty($categoryIds)) {
                $sql = "INSERT INTO blog_post_categories (post_id, category_id) VALUES (:post_id, :category_id)";
                $stmt = $this->db->prepare($sql);
                
                foreach ($categoryIds as $categoryId) {
                    $stmt->execute([
                        ':post_id' => $postId,
                        ':category_id' => $categoryId
                    ]);
                }
            }
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Error setting post categories: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all categories
     */
    public function getAllCategories() {
        try {
            $sql = "SELECT * FROM blog_categories ORDER BY name ASC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Error fetching categories: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Increment post views
     */
    public function incrementViews($postId) {
        try {
            $sql = "UPDATE blog_posts SET views = views + 1 WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $postId]);
            
        } catch (PDOException $e) {
            error_log("Error incrementing views: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get total count of published posts
     */
    public function getPublishedCount($categoryId = null, $searchQuery = null, $year = null, $month = null) {
        try {
            $sql = "SELECT COUNT(DISTINCT p.id) as total FROM blog_posts p";
            $params = [];
            $conditions = ["p.status = 'published'"];
            
            // Category filter
            if ($categoryId !== null) {
                $sql .= " INNER JOIN blog_post_categories pc ON p.id = pc.post_id";
                $conditions[] = "pc.category_id = :category_id";
                $params[':category_id'] = $categoryId;
            }
            
            // Search filter
            if ($searchQuery !== null && !empty(trim($searchQuery))) {
                $conditions[] = "(MATCH(p.title, p.content, p.excerpt) AGAINST(:search_query IN NATURAL LANGUAGE MODE)
                                 OR p.title LIKE :search_like 
                                 OR p.content LIKE :search_like
                                 OR p.excerpt LIKE :search_like)";
                $params[':search_query'] = $searchQuery;
                $params[':search_like'] = '%' . $searchQuery . '%';
            }
            
            // Date filters
            if ($year !== null) {
                $conditions[] = "YEAR(p.published_at) = :year";
                $params[':year'] = $year;
            }
            
            if ($month !== null && $year !== null) {
                $conditions[] = "MONTH(p.published_at) = :month";
                $params[':month'] = $month;
            }
            
            $sql .= " WHERE " . implode(" AND ", $conditions);
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
            
        } catch (PDOException $e) {
            error_log("Error counting posts: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Search posts
     */
    public function searchPosts($query, $limit = 10, $offset = 0) {
        try {
            $sql = "SELECT * FROM blog_posts 
                    WHERE status = 'published' 
                    AND (MATCH(title, content, excerpt) AGAINST(:query IN NATURAL LANGUAGE MODE)
                         OR title LIKE :like_query 
                         OR content LIKE :like_query)
                    ORDER BY published_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $likeQuery = '%' . $query . '%';
            
            $stmt->bindValue(':query', $query);
            $stmt->bindValue(':like_query', $likeQuery);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $posts = $stmt->fetchAll();
            
            // Get categories for each post
            foreach ($posts as &$post) {
                $post['categories'] = $this->getPostCategories($post['id']);
            }
            
            return $posts;
            
        } catch (PDOException $e) {
            error_log("Error searching posts: " . $e->getMessage());
            return [];
        }
    }
}
