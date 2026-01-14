-- Migration: 2026_01_14_100000_simplify_articles.sql
-- Description: Lägg till is_used kolumn i articles
-- Created: 2026-01-14

-- ==================================================
-- UP: Kör denna SQL för att applicera migrationen
-- ==================================================

ALTER TABLE articles ADD COLUMN is_used BOOLEAN DEFAULT FALSE AFTER sku;
CREATE INDEX idx_is_used ON articles(is_used);

-- ==================================================
-- DOWN: Kör denna SQL för att rulla tillbaka
-- ==================================================

DROP INDEX idx_is_used ON articles;
ALTER TABLE articles DROP COLUMN is_used;
