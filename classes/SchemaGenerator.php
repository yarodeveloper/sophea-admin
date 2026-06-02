<?php
/**
 * SOPHEA - Schema Generator Class
 * 
 * Generates Schema.org JSON-LD markup for rich snippets and SEO
 */

class SchemaGenerator {
    
    /**
     * Generate Organization Schema
     */
    public static function organization($config) {
        return [
            "@context" => "https://schema.org",
            "@type" => "Organization",
            "name" => $config['name'],
            "url" => $config['url'],
            "logo" => $config['logo'] ?? $config['url'] . "/logo.png",
            "description" => $config['description'] ?? '',
            "foundingDate" => $config['founding_date'] ?? '',
            "contactPoint" => [
                "@type" => "ContactPoint",
                "telephone" => $config['phone'],
                "contactType" => "customer service",
                "areaServed" => "MX",
                "availableLanguage" => ["es", "es_MX"]
            ],
            "sameAs" => array_filter([
                $config['facebook'] ?? null,
                $config['instagram'] ?? null,
                $config['linkedin'] ?? null,
                $config['twitter'] ?? null,
                $config['youtube'] ?? null
            ]),
            "address" => [
                "@type" => "PostalAddress",
                "streetAddress" => $config['address'] ?? '',
                "addressLocality" => $config['city'] ?? '',
                "addressRegion" => $config['region'] ?? '',
                "postalCode" => $config['postal_code'] ?? '',
                "addressCountry" => "MX"
            ]
        ];
    }
    
    /**
     * Generate LocalBusiness Schema
     */
    public static function localBusiness($config) {
        return [
            "@context" => "https://schema.org",
            "@type" => "LocalBusiness",
            "name" => $config['name'],
            "image" => $config['logo'] ?? $config['url'] . "/logo.png",
            "url" => $config['url'],
            "telephone" => $config['phone'],
            "priceRange" => $config['price_range'] ?? "$$",
            "address" => [
                "@type" => "PostalAddress",
                "streetAddress" => $config['address'] ?? '',
                "addressLocality" => $config['city'] ?? '',
                "addressRegion" => $config['region'] ?? '',
                "postalCode" => $config['postal_code'] ?? '',
                "addressCountry" => "MX"
            ],
            "geo" => [
                "@type" => "GeoCoordinates",
                "latitude" => $config['latitude'] ?? '',
                "longitude" => $config['longitude'] ?? ''
            ],
            "openingHoursSpecification" => [
                [
                    "@type" => "OpeningHoursSpecification",
                    "dayOfWeek" => ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
                    "opens" => "09:00",
                    "closes" => "18:00"
                ]
            ],
            "areaServed" => [
                "@type" => "State",
                "name" => $config['region'] ?? ''
            ]
        ];
        
        // Add Google Maps URL if provided
        if (!empty($config['google_maps'])) {
            $schema['hasMap'] = $config['google_maps'];
        }
        
        return $schema;
    }
    
    /**
     * Generate ProfessionalService Schema
     */
    public static function professionalService($config) {
        $schema = self::localBusiness($config);
        $schema["@type"] = "ProfessionalService";
        $schema["serviceType"] = $config['service_type'] ?? "Marketing Digital y Compliance Regulatorio";
        $schema["hasOfferCatalog"] = [
            "@type" => "OfferCatalog",
            "name" => "Servicios de Marketing Digital",
            "itemListElement" => $config['services'] ?? []
        ];
        return $schema;
    }
    
    /**
     * Generate Person Schema (for director/founder)
     */
    public static function person($config) {
        return [
            "@context" => "https://schema.org",
            "@type" => "Person",
            "name" => $config['name'],
            "jobTitle" => $config['job_title'] ?? '',
            "description" => $config['description'] ?? '',
            "url" => $config['url'] ?? '',
            "image" => $config['image'] ?? '',
            "sameAs" => array_filter([
                $config['linkedin'] ?? null,
                $config['twitter'] ?? null
            ]),
            "worksFor" => [
                "@type" => "Organization",
                "name" => $config['company_name'] ?? ''
            ],
            "knowsAbout" => $config['expertise'] ?? ["Marketing Digital", "Compliance COFEPRIS", "Crecimiento Empresarial"]
        ];
    }
    
    /**
     * Generate Service Schema
     */
    public static function service($config) {
        return [
            "@context" => "https://schema.org",
            "@type" => "Service",
            "name" => $config['name'],
            "description" => $config['description'],
            "provider" => [
                "@type" => "Organization",
                "name" => $config['provider_name'] ?? ''
            ],
            "areaServed" => [
                "@type" => "State",
                "name" => $config['area_served'] ?? ''
            ],
            "serviceType" => $config['service_type'] ?? '',
            "offers" => [
                "@type" => "Offer",
                "priceCurrency" => "MXN",
                "availability" => "https://schema.org/InStock",
                "priceSpecification" => [
                    "@type" => "UnitPriceSpecification",
                    "priceCurrency" => "MXN"
                ]
            ]
        ];
    }
    
    /**
     * Generate Article Schema (for blog posts)
     */
    public static function article($config) {
        return [
            "@context" => "https://schema.org",
            "@type" => "BlogPosting",
            "headline" => $config['title'],
            "description" => $config['description'],
            "image" => $config['image'] ?? '',
            "datePublished" => $config['date_published'] ?? date('c'),
            "dateModified" => $config['date_modified'] ?? date('c'),
            "author" => [
                "@type" => "Person",
                "name" => $config['author_name'] ?? '',
                "url" => $config['author_url'] ?? ''
            ],
            "publisher" => [
                "@type" => "Organization",
                "name" => $config['publisher_name'] ?? '',
                "logo" => [
                    "@type" => "ImageObject",
                    "url" => $config['publisher_logo'] ?? ''
                ]
            ],
            "mainEntityOfPage" => [
                "@type" => "WebPage",
                "@id" => $config['url'] ?? ''
            ],
            "articleSection" => $config['category'] ?? '',
            "keywords" => $config['keywords'] ?? ''
        ];
    }
    
    /**
     * Generate Review/AggregateRating Schema
     */
    public static function aggregateRating($config) {
        return [
            "@context" => "https://schema.org",
            "@type" => "Organization",
            "name" => $config['name'],
            "aggregateRating" => [
                "@type" => "AggregateRating",
                "ratingValue" => $config['rating'] ?? "5",
                "reviewCount" => $config['review_count'] ?? "1",
                "bestRating" => "5",
                "worstRating" => "1"
            ]
        ];
    }
    
    /**
     * Generate Review Schema
     */
    public static function review($config) {
        return [
            "@context" => "https://schema.org",
            "@type" => "Review",
            "itemReviewed" => [
                "@type" => "Organization",
                "name" => $config['organization_name'] ?? ''
            ],
            "reviewRating" => [
                "@type" => "Rating",
                "ratingValue" => $config['rating'] ?? "5",
                "bestRating" => "5",
                "worstRating" => "1"
            ],
            "author" => [
                "@type" => "Person",
                "name" => $config['author_name'] ?? ''
            ],
            "reviewBody" => $config['review_text'] ?? '',
            "datePublished" => $config['date_published'] ?? date('c')
        ];
    }
    
    /**
     * Generate FAQ Schema
     */
    public static function faq($faqs) {
        $mainEntity = [];
        foreach ($faqs as $faq) {
            $mainEntity[] = [
                "@type" => "Question",
                "name" => $faq['question'],
                "acceptedAnswer" => [
                    "@type" => "Answer",
                    "text" => $faq['answer']
                ]
            ];
        }
        
        return [
            "@context" => "https://schema.org",
            "@type" => "FAQPage",
            "mainEntity" => $mainEntity
        ];
    }
    
    /**
     * Generate BreadcrumbList Schema
     */
    public static function breadcrumbs($items) {
        $listItems = [];
        $position = 1;
        
        foreach ($items as $item) {
            $listItems[] = [
                "@type" => "ListItem",
                "position" => $position++,
                "name" => $item['name'],
                "item" => $item['url'] ?? ''
            ];
        }
        
        return [
            "@context" => "https://schema.org",
            "@type" => "BreadcrumbList",
            "itemListElement" => $listItems
        ];
    }
    
    /**
     * Generate WebSite Schema with SearchAction
     */
    public static function website($config) {
        return [
            "@context" => "https://schema.org",
            "@type" => "WebSite",
            "name" => $config['name'],
            "url" => $config['url'],
            "description" => $config['description'] ?? '',
            "publisher" => [
                "@type" => "Organization",
                "name" => $config['name']
            ],
            "potentialAction" => [
                "@type" => "SearchAction",
                "target" => [
                    "@type" => "EntryPoint",
                    "urlTemplate" => $config['search_url'] ?? $config['url'] . "/blog.php?search={search_term_string}"
                ],
                "query-input" => "required name=search_term_string"
            ]
        ];
    }
    
    /**
     * Generate ItemList Schema (for blog listings, services, etc.)
     */
    public static function itemList($config) {
        $items = [];
        foreach ($config['items'] as $item) {
            $items[] = [
                "@type" => $config['item_type'] ?? "ListItem",
                "position" => $item['position'] ?? count($items) + 1,
                "name" => $item['name'],
                "url" => $item['url'] ?? '',
                "description" => $item['description'] ?? ''
            ];
        }
        
        return [
            "@context" => "https://schema.org",
            "@type" => "ItemList",
            "name" => $config['name'],
            "description" => $config['description'] ?? '',
            "itemListElement" => $items
        ];
    }
    
    /**
     * Generate HowTo Schema
     */
    public static function howTo($config) {
        $steps = [];
        foreach ($config['steps'] as $index => $step) {
            $steps[] = [
                "@type" => "HowToStep",
                "position" => $index + 1,
                "name" => $step['name'],
                "text" => $step['text'],
                "image" => $step['image'] ?? ''
            ];
        }
        
        return [
            "@context" => "https://schema.org",
            "@type" => "HowTo",
            "name" => $config['name'],
            "description" => $config['description'],
            "step" => $steps,
            "totalTime" => $config['total_time'] ?? ''
        ];
    }
    
    /**
     * Output schema as JSON-LD script tag
     */
    public static function output($schema) {
        return '<script type="application/ld+json">' . "\n" . 
               json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n" . 
               '</script>';
    }
    
    /**
     * Output multiple schemas
     */
    public static function outputMultiple($schemas) {
        $output = '';
        foreach ($schemas as $schema) {
            $output .= self::output($schema) . "\n";
        }
        return $output;
    }
}

