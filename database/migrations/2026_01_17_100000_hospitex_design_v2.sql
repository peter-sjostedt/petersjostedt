-- Migration: 2026_01_17_100000_hospitex_design_v2.sql
-- Description: Implementerar Hospitex Design v2 - event_templates, event_types och utökade kolumner
-- Created: 2026-01-17

-- ==================================================
-- UP: Kör denna SQL för att applicera migrationen
-- ==================================================

-- Event Types: Fördefinierade händelsetyper
CREATE TABLE event_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE COMMENT 'Tekniskt namn: receive, send, wash, inventory, etc.',
    name_sv VARCHAR(100) NOT NULL COMMENT 'Svenskt namn',
    name_en VARCHAR(100) NOT NULL COMMENT 'Engelskt namn',
    description_sv TEXT DEFAULT NULL,
    description_en TEXT DEFAULT NULL,
    is_transfer BOOLEAN DEFAULT FALSE COMMENT 'Kräver from_unit_id och to_unit_id',
    increments_wash_count BOOLEAN DEFAULT FALSE COMMENT 'Räknas som en tvätt',
    is_system BOOLEAN DEFAULT TRUE COMMENT 'Systemdefinierad (ej redigerbar)',
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_is_active (is_active),
    INDEX idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Event Templates: Förberedelser med QR-koder
CREATE TABLE event_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id VARCHAR(20) NOT NULL,
    event_type_id INT NOT NULL COMMENT 'Typ av händelse',
    label VARCHAR(200) NOT NULL COMMENT 'Namn/etikett för mallen',
    unit_id INT DEFAULT NULL COMMENT 'Enhet som skapade mallen',
    target_unit_id INT DEFAULT NULL COMMENT 'Målenhet för transfer-händelser',
    is_reusable BOOLEAN DEFAULT TRUE COMMENT 'TRUE=repetitiv, FALSE=engångs',
    notes TEXT DEFAULT NULL,
    created_by_user_id INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_organization (organization_id),
    INDEX idx_event_type (event_type_id),
    INDEX idx_unit (unit_id),
    INDEX idx_is_reusable (is_reusable),
    CONSTRAINT fk_event_templates_organization FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_templates_event_type FOREIGN KEY (event_type_id) REFERENCES event_types(id) ON DELETE RESTRICT,
    CONSTRAINT fk_event_templates_unit FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL,
    CONSTRAINT fk_event_templates_target_unit FOREIGN KEY (target_unit_id) REFERENCES units(id) ON DELETE SET NULL,
    CONSTRAINT fk_event_templates_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Utöka events-tabellen
ALTER TABLE events
    ADD COLUMN event_type_id INT DEFAULT NULL COMMENT 'FK till event_types' AFTER event_type,
    ADD COLUMN template_id INT DEFAULT NULL COMMENT 'FK till event_templates (om skapad från mall)' AFTER event_type_id,
    ADD COLUMN from_unit_id INT DEFAULT NULL COMMENT 'Avsändande enhet (för transfers)' AFTER template_id,
    ADD COLUMN to_unit_id INT DEFAULT NULL COMMENT 'Mottagande enhet (för transfers)' AFTER from_unit_id,
    ADD COLUMN scanned_by_unit_id INT DEFAULT NULL COMMENT 'Enheten som utförde skanningen' AFTER to_unit_id;

-- Index och FK för events
ALTER TABLE events
    ADD INDEX idx_event_type_id (event_type_id),
    ADD INDEX idx_template_id (template_id),
    ADD INDEX idx_from_unit (from_unit_id),
    ADD INDEX idx_to_unit (to_unit_id),
    ADD INDEX idx_scanned_by (scanned_by_unit_id),
    ADD CONSTRAINT fk_events_event_type FOREIGN KEY (event_type_id) REFERENCES event_types(id) ON DELETE RESTRICT,
    ADD CONSTRAINT fk_events_template FOREIGN KEY (template_id) REFERENCES event_templates(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_events_from_unit FOREIGN KEY (from_unit_id) REFERENCES units(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_events_to_unit FOREIGN KEY (to_unit_id) REFERENCES units(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_events_scanned_by FOREIGN KEY (scanned_by_unit_id) REFERENCES units(id) ON DELETE SET NULL;

-- Utöka rfids-tabellen med tvätträknare och första/sista händelse
ALTER TABLE rfids
    ADD COLUMN wash_count INT UNSIGNED DEFAULT 0 COMMENT 'Antal tvättar' AFTER status,
    ADD COLUMN first_event_id INT DEFAULT NULL COMMENT 'Första händelsen för denna tagg' AFTER wash_count,
    ADD COLUMN first_event_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Tidpunkt för första händelsen' AFTER first_event_id,
    ADD COLUMN last_event_id INT DEFAULT NULL COMMENT 'Senaste händelsen för denna tagg' AFTER first_event_at,
    ADD COLUMN last_event_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Tidpunkt för senaste händelsen' AFTER last_event_id;

-- Index och FK för rfids
ALTER TABLE rfids
    ADD INDEX idx_wash_count (wash_count),
    ADD INDEX idx_first_event (first_event_id),
    ADD INDEX idx_last_event (last_event_id),
    ADD CONSTRAINT fk_rfids_first_event FOREIGN KEY (first_event_id) REFERENCES events(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_rfids_last_event FOREIGN KEY (last_event_id) REFERENCES events(id) ON DELETE SET NULL;

-- Utöka units-tabellen med inventering
ALTER TABLE units
    ADD COLUMN last_inventory_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Senaste inventering' AFTER last_login_at,
    ADD COLUMN last_inventory_event_id INT DEFAULT NULL COMMENT 'Event-ID för senaste inventering' AFTER last_inventory_at;

-- Index och FK för units
ALTER TABLE units
    ADD INDEX idx_last_inventory (last_inventory_at),
    ADD CONSTRAINT fk_units_last_inventory FOREIGN KEY (last_inventory_event_id) REFERENCES events(id) ON DELETE SET NULL;

-- Seed data för event_types
INSERT INTO event_types (code, name_sv, name_en, description_sv, description_en, is_transfer, increments_wash_count, is_system, sort_order) VALUES
('receive', 'Mottagning', 'Receive', 'Plagg mottaget på enhet', 'Garment received at unit', TRUE, FALSE, TRUE, 10),
('send', 'Skicka', 'Send', 'Plagg skickat från enhet', 'Garment sent from unit', TRUE, FALSE, TRUE, 20),
('wash', 'Tvätt', 'Wash', 'Plagg tvättat', 'Garment washed', FALSE, TRUE, TRUE, 30),
('inventory', 'Inventering', 'Inventory', 'Plagg inventerat på enhet', 'Garment inventoried at unit', FALSE, FALSE, TRUE, 40),
('scrap', 'Kassering', 'Scrap', 'Plagg kasserat', 'Garment scrapped', FALSE, FALSE, TRUE, 50),
('lost', 'Förlorad', 'Lost', 'Plagg markerat som förlorat', 'Garment marked as lost', FALSE, FALSE, TRUE, 60),
('found', 'Återfunnen', 'Found', 'Plagg återfunnet', 'Garment found', FALSE, FALSE, TRUE, 70),
('repetitive', 'Repetitiv', 'Repetitive', 'Repetitiv händelse (bakåtkompatibilitet)', 'Repetitive event (backwards compatibility)', FALSE, FALSE, TRUE, 100);



-- ==================================================
-- DOWN: Kör denna SQL för att rulla tillbaka
-- ==================================================

-- Ta bort FK och index från units
-- ALTER TABLE units DROP FOREIGN KEY fk_units_last_inventory;
-- ALTER TABLE units DROP INDEX idx_last_inventory;
-- ALTER TABLE units DROP COLUMN last_inventory_event_id;
-- ALTER TABLE units DROP COLUMN last_inventory_at;

-- Ta bort FK och index från rfids
-- ALTER TABLE rfids DROP FOREIGN KEY fk_rfids_last_event;
-- ALTER TABLE rfids DROP FOREIGN KEY fk_rfids_first_event;
-- ALTER TABLE rfids DROP INDEX idx_last_event;
-- ALTER TABLE rfids DROP INDEX idx_first_event;
-- ALTER TABLE rfids DROP INDEX idx_wash_count;
-- ALTER TABLE rfids DROP COLUMN last_event_at;
-- ALTER TABLE rfids DROP COLUMN last_event_id;
-- ALTER TABLE rfids DROP COLUMN first_event_at;
-- ALTER TABLE rfids DROP COLUMN first_event_id;
-- ALTER TABLE rfids DROP COLUMN wash_count;

-- Ta bort FK och index från events
-- ALTER TABLE events DROP FOREIGN KEY fk_events_scanned_by;
-- ALTER TABLE events DROP FOREIGN KEY fk_events_to_unit;
-- ALTER TABLE events DROP FOREIGN KEY fk_events_from_unit;
-- ALTER TABLE events DROP FOREIGN KEY fk_events_template;
-- ALTER TABLE events DROP FOREIGN KEY fk_events_event_type;
-- ALTER TABLE events DROP INDEX idx_scanned_by;
-- ALTER TABLE events DROP INDEX idx_to_unit;
-- ALTER TABLE events DROP INDEX idx_from_unit;
-- ALTER TABLE events DROP INDEX idx_template_id;
-- ALTER TABLE events DROP INDEX idx_event_type_id;
-- ALTER TABLE events DROP COLUMN scanned_by_unit_id;
-- ALTER TABLE events DROP COLUMN to_unit_id;
-- ALTER TABLE events DROP COLUMN from_unit_id;
-- ALTER TABLE events DROP COLUMN template_id;
-- ALTER TABLE events DROP COLUMN event_type_id;

-- Ta bort tabeller
-- DROP TABLE event_templates;
-- DROP TABLE event_types;
