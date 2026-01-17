-- Migration: 2026_01_16_100000_fix_event_at_timestamp.sql
-- Description: Fix event_at (allow NULL) and created_at (require NOT NULL) on events table
-- Created: 2026-01-16

-- ==================================================
-- UP: Kör denna SQL för att applicera migrationen
-- ==================================================

-- event_at: NULL = event hasn't occurred yet, set by scanner API when it happens
ALTER TABLE events MODIFY COLUMN event_at TIMESTAMP NULL DEFAULT NULL COMMENT 'När händelsen inträffade (NULL = ej inträffat ännu)';

-- created_at: Always set when row is created
ALTER TABLE events MODIFY COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'När raden skapades';



-- ==================================================
-- DOWN: Kör denna SQL för att rulla tillbaka
-- ==================================================

-- Återställ event_at till NOT NULL med default
-- ALTER TABLE events MODIFY COLUMN event_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'När händelsen inträffade';

-- Återställ created_at till att tillåta NULL
-- ALTER TABLE events MODIFY COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
