-- Migration: Create organizations and units tables for Hospitex RFID Tracking System
-- Created: 2026-01-13
-- Description: Organizations represent companies/hospitals, Units represent departments/functions

-- ==================================================
-- UP: Kör denna SQL för att applicera migrationen
-- ==================================================

-- Organizations: företag/sjukhus som använder systemet
CREATE TABLE organizations (
    id VARCHAR(20) PRIMARY KEY COMMENT 'Organisationsnummer med landskod, t.ex. SE556123-4567',
    name VARCHAR(100) NOT NULL,
    org_type ENUM('system', 'customer') DEFAULT 'customer',
    article_schema JSON COMMENT 'Organisationens artikelattribut-struktur',
    address VARCHAR(255),
    postal_code VARCHAR(20),
    city VARCHAR(100),
    country VARCHAR(2) DEFAULT 'SE',
    phone VARCHAR(50),
    email VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_org_type (org_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Units: avdelningar/enheter som loggar in (ej personliga användare)
CREATE TABLE units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    api_key VARCHAR(64) UNIQUE COMMENT 'För systemintegration och skannrar',
    password VARCHAR(255) NOT NULL COMMENT 'Hashat med password_hash()',
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    INDEX idx_organization (organization_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Skapa system-organisation för admin
INSERT INTO organizations (id, name, org_type)
VALUES ('SYSTEM', 'Hospitex System', 'system');


-- ==================================================
-- DOWN: Kör denna SQL för att rulla tillbaka
-- ==================================================

DROP TABLE IF EXISTS units;
DROP TABLE IF EXISTS organizations;
