-- Schema för prod (utan users-tabell)
-- Kör denna när users-tabellen redan finns

-- Organizations: Alla organisationer i systemet
CREATE TABLE organizations (
    id VARCHAR(20) NOT NULL PRIMARY KEY COMMENT 'Organisationsnummer med landskod, t.ex. SE556123-4567',
    name VARCHAR(100) NOT NULL,
    org_type ENUM('system', 'customer', 'supplier', 'laundry') DEFAULT 'customer' COMMENT 'system=Hospitex, customer=sjukhus/vård, supplier=textilproducent, laundry=tvätteri',
    article_schema JSON DEFAULT NULL COMMENT 'Organisationens artikelattribut-struktur',
    address VARCHAR(255) DEFAULT NULL,
    postal_code VARCHAR(20) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    country VARCHAR(2) DEFAULT 'SE',
    phone VARCHAR(50) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_org_type (org_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lägg till organization_id kolumn och foreign key på users (tabellen finns redan)
ALTER TABLE users ADD COLUMN organization_id VARCHAR(20) DEFAULT NULL AFTER role;
ALTER TABLE users ADD INDEX idx_organization_id (organization_id);
ALTER TABLE users ADD CONSTRAINT fk_users_organization FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

-- Sessions: Aktiva sessioner
CREATE TABLE sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at),
    CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Logs: Systemloggar
CREATE TABLE logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    action VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_created (created_at),
    CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Settings: Systeminställningar
CREATE TABLE settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    INDEX idx_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Files: Uppladdade filer
CREATE TABLE files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    folder VARCHAR(100) DEFAULT NULL,
    uploaded_by INT UNSIGNED DEFAULT NULL,
    parent_id INT DEFAULT NULL,
    size_variant VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_uploaded_by (uploaded_by),
    INDEX idx_created_at (created_at),
    INDEX idx_parent_id (parent_id),
    INDEX idx_folder (folder)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Units: Enheter inom organisationer (skannrar, avdelningar)
CREATE TABLE units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    api_key VARCHAR(64) DEFAULT NULL UNIQUE COMMENT 'För systemintegration och skannrar',
    password VARCHAR(255) NOT NULL COMMENT 'Hashat med password_hash()',
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_organization (organization_id),
    INDEX idx_is_active (is_active),
    CONSTRAINT fk_units_organization FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Organization_relations: Relationer mellan organisationer (kund/leverantör)
CREATE TABLE organization_relations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id VARCHAR(20) NOT NULL COMMENT 'Organisationen som äger relationen',
    partner_org_id VARCHAR(20) NOT NULL COMMENT 'Partnerorganisationen',
    relation_type ENUM('customer', 'supplier') NOT NULL COMMENT 'Typ av relation',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_relation (organization_id, partner_org_id, relation_type),
    INDEX idx_organization (organization_id),
    INDEX idx_partner (partner_org_id),
    INDEX idx_type (relation_type),
    CONSTRAINT fk_org_relations_organization FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_org_relations_partner FOREIGN KEY (partner_org_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Articles: Artiklar per organisation
CREATE TABLE articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id VARCHAR(20) NOT NULL,
    sku VARCHAR(100) NOT NULL COMMENT 'Artikelnummer, unikt per organisation',
    name VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    data JSON DEFAULT NULL COMMENT 'Artikeldata enligt organisationens article_schema',
    is_used BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_org_sku (organization_id, sku),
    INDEX idx_sku (sku),
    INDEX idx_is_active (is_active),
    INDEX idx_is_used (is_used),
    CONSTRAINT fk_articles_organization FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Article_mappings: Koppling mellan artiklar från olika organisationer
CREATE TABLE article_mappings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_org_id VARCHAR(20) NOT NULL COMMENT 'Mottagaren som ansvarar för mappningen',
    sender_org_id VARCHAR(20) NOT NULL COMMENT 'Avsändaren',
    sender_article_id INT NOT NULL COMMENT 'Avsändarens artikel',
    my_article_id INT NOT NULL COMMENT 'Mottagarens artikel',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_mapping (owner_org_id, sender_article_id),
    INDEX idx_owner (owner_org_id),
    INDEX idx_sender (sender_org_id),
    CONSTRAINT fk_article_mappings_owner FOREIGN KEY (owner_org_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_article_mappings_sender FOREIGN KEY (sender_org_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_article_mappings_sender_article FOREIGN KEY (sender_article_id) REFERENCES articles(id) ON DELETE CASCADE,
    CONSTRAINT fk_article_mappings_my_article FOREIGN KEY (my_article_id) REFERENCES articles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rfids: RFID-taggar
CREATE TABLE rfids (
    id INT AUTO_INCREMENT PRIMARY KEY,
    epc VARCHAR(100) NOT NULL UNIQUE COMMENT 'RFID-taggens unika ID',
    article_id INT NOT NULL COMMENT 'Kopplad artikel',
    owner_org_id VARCHAR(20) NOT NULL COMMENT 'Nuvarande ägare',
    location_unit_id INT DEFAULT NULL COMMENT 'Var taggen befinner sig just nu',
    status ENUM('active', 'inactive', 'lost', 'scrapped') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_epc (epc),
    INDEX idx_article (article_id),
    INDEX idx_owner (owner_org_id),
    INDEX idx_status (status),
    CONSTRAINT fk_rfids_article FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE RESTRICT,
    CONSTRAINT fk_rfids_owner FOREIGN KEY (owner_org_id) REFERENCES organizations(id) ON DELETE RESTRICT,
    CONSTRAINT fk_rfids_location FOREIGN KEY (location_unit_id) REFERENCES units(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Events: Alla händelser i systemet (försändelser, skanningar, etc.)
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id VARCHAR(20) NOT NULL,
    event_type VARCHAR(50) NOT NULL COMMENT 'shipment, scan, ownership_change, etc.',
    metadata JSON DEFAULT NULL COMMENT 'Typspecifik data',
    event_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'När händelsen inträffade',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_organization (organization_id),
    INDEX idx_event_type (event_type),
    INDEX idx_event_at (event_at),
    INDEX idx_created (created_at),
    CONSTRAINT fk_events_organization FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rfids_events: Koppling mellan RFID-taggar och events
CREATE TABLE rfids_events (
    event_id INT NOT NULL,
    rfid VARCHAR(100) NOT NULL,
    PRIMARY KEY (event_id, rfid),
    INDEX idx_rfid (rfid),
    CONSTRAINT fk_rfids_events_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
