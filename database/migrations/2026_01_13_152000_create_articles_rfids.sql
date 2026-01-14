-- Articles: organisationernas artiklar
CREATE TABLE articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id VARCHAR(20) NOT NULL,
    sku VARCHAR(100) NOT NULL COMMENT 'Artikelnummer, unikt per organisation',
    name VARCHAR(200) NOT NULL,
    description TEXT,
    data JSON COMMENT 'Artikeldata enligt organisationens article_schema',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_org_sku (organization_id, sku),
    INDEX idx_sku (sku),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- RFIDs: taggar kopplade till artiklar
CREATE TABLE rfids (
    id INT AUTO_INCREMENT PRIMARY KEY,
    epc VARCHAR(100) NOT NULL UNIQUE COMMENT 'RFID-taggens unika ID',
    article_id INT NOT NULL COMMENT 'Ägarens artikel',
    owner_org_id VARCHAR(20) NOT NULL COMMENT 'Nuvarande ägare',
    location_unit_id INT COMMENT 'Var taggen befinner sig just nu',
    status ENUM('active', 'inactive', 'lost', 'scrapped') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE RESTRICT,
    FOREIGN KEY (owner_org_id) REFERENCES organizations(id) ON DELETE RESTRICT,
    FOREIGN KEY (location_unit_id) REFERENCES units(id) ON DELETE SET NULL,
    INDEX idx_epc (epc),
    INDEX idx_article (article_id),
    INDEX idx_owner (owner_org_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Article mappings: översättning mellan organisationers SKU
CREATE TABLE article_mappings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_org_id VARCHAR(20) NOT NULL COMMENT 'Mottagaren som ansvarar för mappningen',
    sender_org_id VARCHAR(20) NOT NULL COMMENT 'Avsändaren',
    sender_article_id INT NOT NULL COMMENT 'Avsändarens artikel',
    my_article_id INT NOT NULL COMMENT 'Mottagarens artikel',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_org_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_org_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (my_article_id) REFERENCES articles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_mapping (owner_org_id, sender_article_id),
    INDEX idx_owner (owner_org_id),
    INDEX idx_sender (sender_org_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
