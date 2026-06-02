-- SOPHEA Testimonials Table
-- Table to store testimonials/case studies

USE sophea_db;

-- Testimonials table
CREATE TABLE IF NOT EXISTS testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(255) NOT NULL,
    client_title VARCHAR(255),
    client_company VARCHAR(255),
    client_location VARCHAR(255),
    client_avatar VARCHAR(500),
    testimonial_text TEXT NOT NULL,
    full_story LONGTEXT,
    slug VARCHAR(255) NOT NULL UNIQUE,
    featured_image VARCHAR(500),
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    featured BOOLEAN DEFAULT 0,
    display_order INT DEFAULT 0,
    
    -- Metrics
    metric1_label VARCHAR(100),
    metric1_value VARCHAR(50),
    metric1_color VARCHAR(50) DEFAULT 'purple',
    
    metric2_label VARCHAR(100),
    metric2_value VARCHAR(50),
    metric2_color VARCHAR(50) DEFAULT 'blue',
    
    metric3_label VARCHAR(100),
    metric3_value VARCHAR(50),
    metric3_color VARCHAR(50) DEFAULT 'green',
    
    -- Services used
    services_used TEXT,
    
    -- Sector
    sector ENUM('salud', 'general', 'retail', 'servicios') DEFAULT 'general',
    
    -- SEO
    meta_title VARCHAR(255),
    meta_description TEXT,
    meta_keywords VARCHAR(500),
    
    -- Dates
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at DATETIME NULL,
    
    -- Views
    views INT DEFAULT 0,
    
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_featured (featured),
    INDEX idx_display_order (display_order),
    INDEX idx_published_at (published_at),
    INDEX idx_sector (sector)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Testimonial images table (for gallery)
CREATE TABLE IF NOT EXISTS testimonial_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    testimonial_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    image_alt VARCHAR(255),
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (testimonial_id) REFERENCES testimonials(id) ON DELETE CASCADE,
    INDEX idx_testimonial_id (testimonial_id),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
