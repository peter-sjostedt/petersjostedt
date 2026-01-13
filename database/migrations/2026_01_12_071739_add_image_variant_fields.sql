-- Migration: 2026_01_12_071739_add_image_variant_fields.sql
-- Description: Lägg till fält för att gruppera bildvarianter (thumbnail, medium, large)
-- Created: 2026-01-12 07:17:39

-- ==================================================
-- UP: Kör denna SQL för att applicera migrationen
-- ==================================================

ALTER TABLE files
ADD COLUMN parent_id INT NULL AFTER uploaded_by,
ADD COLUMN size_variant VARCHAR(20) NULL AFTER parent_id,
ADD INDEX idx_parent_id (parent_id);

-- ==================================================
-- DOWN: Kör denna SQL för att rulla tillbaka
-- ==================================================

ALTER TABLE files
DROP INDEX idx_parent_id,
DROP COLUMN size_variant,
DROP COLUMN parent_id;


