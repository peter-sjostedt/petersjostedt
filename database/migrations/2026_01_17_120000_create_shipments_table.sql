-- Migration: 2026_01_17_120000_create_shipments_table.sql
-- Description: Skapar shipments-tabell för försändelser mellan organisationer
-- Created: 2026-01-17 12:00:00

-- ==================================================
-- UP: Kör denna SQL för att applicera migrationen
-- ==================================================

-- Shipments: Försändelser mellan organisationer
-- Används för både utgående (shipment) och inkommande (delivery) perspektiv
CREATE TABLE shipments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    qr_code VARCHAR(100) NOT NULL UNIQUE COMMENT 'Unik QR-kod för försändelsen (SH-YYYY-XXXXX)',
    from_org_id VARCHAR(20) NOT NULL COMMENT 'Avsändande organisation',
    to_org_id VARCHAR(20) NOT NULL COMMENT 'Mottagande organisation',
    from_unit_id INT DEFAULT NULL COMMENT 'Avsändande enhet (valfritt)',
    to_unit_id INT DEFAULT NULL COMMENT 'Mottagande enhet (valfritt)',
    sales_order_id VARCHAR(50) DEFAULT NULL COMMENT 'Försäljningsordernummer hos avsändaren',
    purchase_order_id VARCHAR(50) DEFAULT NULL COMMENT 'Inköpsordernummer hos mottagaren',
    status ENUM('prepared', 'shipped', 'received', 'cancelled') DEFAULT 'prepared' COMMENT 'Status: prepared=förberedd, shipped=skickad, received=mottagen, cancelled=avbruten',
    notes TEXT DEFAULT NULL COMMENT 'Anteckningar',
    metadata JSON DEFAULT NULL COMMENT 'Extra metadata (JSON)',
    created_by_user_id INT UNSIGNED DEFAULT NULL COMMENT 'Skapades av användare',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    shipped_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Tidpunkt när försändelsen skickades',
    shipped_by_user_id INT UNSIGNED DEFAULT NULL COMMENT 'Användare som registrerade skickad',
    received_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Tidpunkt när försändelsen mottogs',
    received_by_user_id INT UNSIGNED DEFAULT NULL COMMENT 'Användare som registrerade mottagen',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Index
    INDEX idx_qr_code (qr_code),
    INDEX idx_from_org (from_org_id),
    INDEX idx_to_org (to_org_id),
    INDEX idx_from_unit (from_unit_id),
    INDEX idx_to_unit (to_unit_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_shipped_at (shipped_at),
    INDEX idx_received_at (received_at),

    -- Foreign Keys
    CONSTRAINT fk_shipments_from_org FOREIGN KEY (from_org_id) REFERENCES organizations(id) ON DELETE RESTRICT,
    CONSTRAINT fk_shipments_to_org FOREIGN KEY (to_org_id) REFERENCES organizations(id) ON DELETE RESTRICT,
    CONSTRAINT fk_shipments_from_unit FOREIGN KEY (from_unit_id) REFERENCES units(id) ON DELETE SET NULL,
    CONSTRAINT fk_shipments_to_unit FOREIGN KEY (to_unit_id) REFERENCES units(id) ON DELETE SET NULL,
    CONSTRAINT fk_shipments_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_shipments_shipped_by FOREIGN KEY (shipped_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_shipments_received_by FOREIGN KEY (received_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lägg till shipment_id i events-tabellen för koppling
ALTER TABLE events
    ADD COLUMN shipment_id INT DEFAULT NULL COMMENT 'FK till shipments (för shipped/received events)' AFTER template_id,
    ADD INDEX idx_shipment_id (shipment_id),
    ADD CONSTRAINT fk_events_shipment FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE SET NULL;


-- ==================================================
-- DOWN: Kör denna SQL för att rulla tillbaka
-- ==================================================

-- ALTER TABLE events DROP FOREIGN KEY fk_events_shipment;
-- ALTER TABLE events DROP INDEX idx_shipment_id;
-- ALTER TABLE events DROP COLUMN shipment_id;
-- DROP TABLE shipments;
