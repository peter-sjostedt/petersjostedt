-- Migration: 2026_01_12_131905_add_folder_to_files.sql
-- Description: Lägg till folder-kolumn i files-tabellen
-- Created: 2026-01-12 13:19:05

-- ==================================================
-- UP: Kör denna SQL för att applicera migrationen
-- ==================================================

ALTER TABLE files
ADD COLUMN folder VARCHAR(100) NULL AFTER file_path,
ADD INDEX idx_folder (folder);

-- ==================================================
-- DOWN: Kör denna SQL för att rulla tillbaka
-- ==================================================

ALTER TABLE files
DROP INDEX idx_folder,
DROP COLUMN folder;
