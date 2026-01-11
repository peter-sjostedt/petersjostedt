-- Migration: YYYY_MM_DD_HHMMSS_beskrivning.sql
-- Description: Beskriv vad denna migration gör
-- Created: YYYY-MM-DD HH:MM:SS

-- ==================================================
-- UP: Kör denna SQL för att applicera migrationen
-- ==================================================

-- Exempel 1: Skapa ny tabell
-- CREATE TABLE exempel (
--     id INT PRIMARY KEY AUTO_INCREMENT,
--     name VARCHAR(255) NOT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exempel 2: Lägg till kolumn
-- ALTER TABLE users ADD COLUMN phone VARCHAR(20);

-- Exempel 3: Lägg till index
-- CREATE INDEX idx_email ON users(email);

-- Exempel 4: Ändra kolumntyp
-- ALTER TABLE users MODIFY bio TEXT;

-- Exempel 5: Lägg till foreign key
-- ALTER TABLE posts
-- ADD CONSTRAINT fk_user
-- FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;



-- ==================================================
-- DOWN: Kör denna SQL för att rulla tillbaka
-- ==================================================

-- Exempel 1: Ta bort tabell
-- DROP TABLE exempel;

-- Exempel 2: Ta bort kolumn
-- ALTER TABLE users DROP COLUMN phone;

-- Exempel 3: Ta bort index
-- DROP INDEX idx_email ON users;

-- Exempel 4: Återställ kolumntyp
-- ALTER TABLE users MODIFY bio VARCHAR(255);

-- Exempel 5: Ta bort foreign key
-- ALTER TABLE posts DROP FOREIGN KEY fk_user;


