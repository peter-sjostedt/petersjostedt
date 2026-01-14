-- Event types: typer av händelser
CREATE TABLE event_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE COMMENT 'scanned, ownership_changed, location_changed, etc.',
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_system BOOLEAN DEFAULT FALSE COMMENT 'Systemhändelser kan inte raderas',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Events: logg över alla händelser (för spårning och fakturering)
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type_id INT NOT NULL,
    rfid_id INT NOT NULL,
    unit_id INT NOT NULL COMMENT 'Enheten som utförde händelsen',
    data JSON COMMENT 'Extra data: previous_owner, new_owner, etc.',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_type_id) REFERENCES event_types(id) ON DELETE RESTRICT,
    FOREIGN KEY (rfid_id) REFERENCES rfids(id) ON DELETE CASCADE,
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
    INDEX idx_event_type (event_type_id),
    INDEX idx_rfid (rfid_id),
    INDEX idx_unit (unit_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Grundläggande händelsetyper
INSERT INTO event_types (code, name, description, is_system) VALUES
('scanned', 'Skannad', 'RFID-tagg skannad', TRUE),
('ownership_changed', 'Ägarbyte', 'Taggen bytte ägare', TRUE),
('location_changed', 'Platsändring', 'Taggen flyttades till annan enhet', TRUE),
('status_changed', 'Statusändring', 'Taggens status ändrades', TRUE),
('created', 'Skapad', 'Ny RFID-tagg registrerad', TRUE);
