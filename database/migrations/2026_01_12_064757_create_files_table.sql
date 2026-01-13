-- Migration: 2026_01_12_064757_create_files_table.sql
-- Description: Skapa tabell för filuppladdningar
-- Created: 2026-01-12 06:47:57

-- ==================================================
-- UP: Kör denna SQL för att applicera migrationen
-- ==================================================

CREATE TABLE files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_uploaded_by (uploaded_by),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================
-- DOWN: Kör denna SQL för att rulla tillbaka
-- ==================================================

DROP TABLE files;


