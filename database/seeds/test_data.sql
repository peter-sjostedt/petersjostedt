-- Testdata för Hospitex RFID Tracking System
-- Kör denna fil för att fylla databasen med testdata
-- OBS: Lösenordet för alla testanvändare är: password123

-- ==================================================
-- Organisationer (kunder)
-- ==================================================

INSERT INTO organizations (id, name, org_type, address, postal_code, city, country, phone, email, is_active, article_schema) VALUES
('SE556677-8899', 'Karolinska Universitetssjukhuset', 'customer', 'Eugeniavägen 3', '171 76', 'Solna', 'SE', '08-517 700 00', 'info@karolinska.se', 1, '[{"label":"Artikelnamn","type":"text","required":true},{"label":"Storlek","type":"select","options":["XS","S","M","L","XL"]},{"label":"Material","type":"text"}]'),
('SE556011-2233', 'Sahlgrenska Universitetssjukhuset', 'customer', 'Per Dubbsgatan 15', '413 45', 'Göteborg', 'SE', '031-342 10 00', 'info@sahlgrenska.se', 1, '[{"label":"Artikelnamn","type":"text","required":true},{"label":"Avdelning","type":"text"}]'),
('SE556445-6677', 'Skånes Universitetssjukhus', 'customer', 'Jan Waldenströms gata 35', '205 02', 'Malmö', 'SE', '040-33 10 00', 'info@sus.se', 1, NULL),
('SE556889-0011', 'Akademiska Sjukhuset', 'customer', 'Sjukhusvägen 1', '752 37', 'Uppsala', 'SE', '018-611 00 00', 'info@akademiska.se', 1, NULL),
('SE556223-4455', 'Norrlands Universitetssjukhus', 'customer', 'Norrlandsvägen 10', '901 85', 'Umeå', 'SE', '090-785 00 00', 'info@nus.se', 0, NULL);

-- ==================================================
-- Enheter (avdelningar)
-- ==================================================

-- Karolinska enheter
INSERT INTO units (organization_id, name, password, api_key, is_active) VALUES
('SE556677-8899', 'Sterilcentralen', '$argon2id$v=19$m=65536,t=4,p=3$YWJjZGVmZ2hpamtsbW5v$K8Qv0vUYx8k9FjJhY2JlZGZnaGlqa2xtbm9wcXJzdHV2', 'api_karolinska_steril_001', 1),
('SE556677-8899', 'Kirurgi A', '$argon2id$v=19$m=65536,t=4,p=3$YWJjZGVmZ2hpamtsbW5v$K8Qv0vUYx8k9FjJhY2JlZGZnaGlqa2xtbm9wcXJzdHV2', 'api_karolinska_kirurgia_002', 1),
('SE556677-8899', 'Kirurgi B', '$argon2id$v=19$m=65536,t=4,p=3$YWJjZGVmZ2hpamtsbW5v$K8Qv0vUYx8k9FjJhY2JlZGZnaGlqa2xtbm9wcXJzdHV2', 'api_karolinska_kirurgib_003', 1),
('SE556677-8899', 'Akutmottagningen', '$argon2id$v=19$m=65536,t=4,p=3$YWJjZGVmZ2hpamtsbW5v$K8Qv0vUYx8k9FjJhY2JlZGZnaGlqa2xtbm9wcXJzdHV2', 'api_karolinska_akut_004', 1),
('SE556677-8899', 'IVA', '$argon2id$v=19$m=65536,t=4,p=3$YWJjZGVmZ2hpamtsbW5v$K8Qv0vUYx8k9FjJhY2JlZGZnaGlqa2xtbm9wcXJzdHV2', 'api_karolinska_iva_005', 1);

-- Sahlgrenska enheter
INSERT INTO units (organization_id, name, password, api_key, is_active) VALUES
('SE556011-2233', 'Sterilcentralen SU', '$argon2id$v=19$m=65536,t=4,p=3$YWJjZGVmZ2hpamtsbW5v$K8Qv0vUYx8k9FjJhY2JlZGZnaGlqa2xtbm9wcXJzdHV2', 'api_sahlgrenska_steril_001', 1),
('SE556011-2233', 'Ortopedi', '$argon2id$v=19$m=65536,t=4,p=3$YWJjZGVmZ2hpamtsbW5v$K8Qv0vUYx8k9FjJhY2JlZGZnaGlqa2xtbm9wcXJzdHV2', 'api_sahlgrenska_ortopedi_002', 1),
('SE556011-2233', 'Kardiologi', '$argon2id$v=19$m=65536,t=4,p=3$YWJjZGVmZ2hpamtsbW5v$K8Qv0vUYx8k9FjJhY2JlZGZnaGlqa2xtbm9wcXJzdHV2', 'api_sahlgrenska_kardio_003', 1);

-- Skåne enheter
INSERT INTO units (organization_id, name, password, api_key, is_active) VALUES
('SE556445-6677', 'Sterilcentral Malmö', '$argon2id$v=19$m=65536,t=4,p=3$YWJjZGVmZ2hpamtsbW5v$K8Qv0vUYx8k9FjJhY2JlZGZnaGlqa2xtbm9wcXJzdHV2', 'api_sus_steril_001', 1),
('SE556445-6677', 'Operation Lund', '$argon2id$v=19$m=65536,t=4,p=3$YWJjZGVmZ2hpamtsbW5v$K8Qv0vUYx8k9FjJhY2JlZGZnaGlqa2xtbm9wcXJzdHV2', 'api_sus_op_lund_002', 1);

-- Akademiska enheter
INSERT INTO units (organization_id, name, password, api_key, is_active) VALUES
('SE556889-0011', 'Sterilcentralen Uppsala', '$argon2id$v=19$m=65536,t=4,p=3$YWJjZGVmZ2hpamtsbW5v$K8Qv0vUYx8k9FjJhY2JlZGZnaGlqa2xtbm9wcXJzdHV2', 'api_akademiska_steril_001', 1);

-- ==================================================
-- Användare
-- Lösenord: password123 (hashat med Argon2id)
-- ==================================================

-- Organisationsadmins (org_admin)
INSERT INTO users (email, password, name, role, organization_id) VALUES
('admin@karolinska.se', '$argon2id$v=19$m=65536,t=4,p=3$c2FsdHNhbHRzYWx0c2FsdA$wHdQz9y3YfZ8kqL6mN2rVvXjB5tHxK1cP4sG7nM0oAE', 'Anna Karlsson', 'org_admin', 'SE556677-8899'),
('chef@sahlgrenska.se', '$argon2id$v=19$m=65536,t=4,p=3$c2FsdHNhbHRzYWx0c2FsdA$wHdQz9y3YfZ8kqL6mN2rVvXjB5tHxK1cP4sG7nM0oAE', 'Erik Lindberg', 'org_admin', 'SE556011-2233'),
('admin@sus.se', '$argon2id$v=19$m=65536,t=4,p=3$c2FsdHNhbHRzYWx0c2FsdA$wHdQz9y3YfZ8kqL6mN2rVvXjB5tHxK1cP4sG7nM0oAE', 'Maria Svensson', 'org_admin', 'SE556445-6677');

-- Vanliga användare (user)
INSERT INTO users (email, password, name, role, organization_id) VALUES
('lars.berg@karolinska.se', '$argon2id$v=19$m=65536,t=4,p=3$c2FsdHNhbHRzYWx0c2FsdA$wHdQz9y3YfZ8kqL6mN2rVvXjB5tHxK1cP4sG7nM0oAE', 'Lars Berg', 'user', 'SE556677-8899'),
('eva.holm@karolinska.se', '$argon2id$v=19$m=65536,t=4,p=3$c2FsdHNhbHRzYWx0c2FsdA$wHdQz9y3YfZ8kqL6mN2rVvXjB5tHxK1cP4sG7nM0oAE', 'Eva Holm', 'user', 'SE556677-8899'),
('johan.nyman@sahlgrenska.se', '$argon2id$v=19$m=65536,t=4,p=3$c2FsdHNhbHRzYWx0c2FsdA$wHdQz9y3YfZ8kqL6mN2rVvXjB5tHxK1cP4sG7nM0oAE', 'Johan Nyman', 'user', 'SE556011-2233'),
('karin.lund@sus.se', '$argon2id$v=19$m=65536,t=4,p=3$c2FsdHNhbHRzYWx0c2FsdA$wHdQz9y3YfZ8kqL6mN2rVvXjB5tHxK1cP4sG7nM0oAE', 'Karin Lund', 'user', 'SE556445-6677'),
('per.strom@akademiska.se', '$argon2id$v=19$m=65536,t=4,p=3$c2FsdHNhbHRzYWx0c2FsdA$wHdQz9y3YfZ8kqL6mN2rVvXjB5tHxK1cP4sG7nM0oAE', 'Per Ström', 'user', 'SE556889-0011');

-- ==================================================
-- Sammanfattning av testdata:
-- ==================================================
-- Organisationer: 5 st (+ SYSTEM som redan finns)
--   - 4 aktiva, 1 inaktiv (Norrlands)
-- Enheter: 12 st
-- Användare: 8 st (nya)
--   - 3 org_admin
--   - 5 user
--
-- Testkonton:
-- admin@karolinska.se / password123 (org_admin för Karolinska)
-- chef@sahlgrenska.se / password123 (org_admin för Sahlgrenska)
-- admin@sus.se / password123 (org_admin för Skåne)
-- lars.berg@karolinska.se / password123 (user)
-- ==================================================
