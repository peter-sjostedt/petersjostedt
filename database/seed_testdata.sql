-- Seed testdata
-- Run this after initial migration

-- Organizations
INSERT INTO organizations (id, name, org_type, article_schema, address, postal_code, city, country, phone, email, is_active) VALUES
('SYSTEM', 'Hospitex System', 'system', NULL, NULL, NULL, NULL, 'SE', NULL, NULL, 1),
('SE556112-3344', 'Textilia AB', 'supplier', '[{"label": "Artikelnamn", "sort_order": "0"}, {"label": "Storlek", "sort_order": "1"}, {"label": "Material", "sort_order": "2"}, {"label": "Färg", "sort_order": "3"}]', 'Industrigatan 45', '602 38', 'Norrköping', 'SE', '011-123 45 67', 'info@textilia.se', 1),
('SE556011-2233', 'Sahlgrenska Universitetssjukhuset', 'customer', '[{"label": "Artikelnamn", "sort_order": "0"}, {"label": "Avdelning", "sort_order": "1"}]', 'Per Dubbsgatan 15', '413 45', 'Göteborg', 'SE', '031-342 10 00', 'info@sahlgrenska.se', 1),
('SE556677-8899', 'Karolinska Universitetssjukhuset', 'customer', '[{"type": "text", "label": "Artikelnamn", "required": true}, {"type": "select", "label": "Storlek", "options": ["XS", "S", "M", "L", "XL"]}, {"type": "text", "label": "Material"}]', 'Eugeniavägen 3', '171 76', 'Solna', 'SE', '08-517 700 00', 'info@karolinska.se', 1),
('SE556445-6677', 'Skånes Universitetssjukhus', 'customer', NULL, 'Jan Waldenströms gata 35', '205 02', 'Malmö', 'SE', '040-33 10 00', 'info@sus.se', 1),
('SE556889-0011', 'Akademiska Sjukhuset', 'customer', NULL, 'Sjukhusvägen 1', '752 37', 'Uppsala', 'SE', '018-611 00 00', 'info@akademiska.se', 1),
('SE556223-4455', 'Norrlands Universitetssjukhus', 'customer', NULL, 'Norrlandsvägen 10', '901 85', 'Umeå', 'SE', '090-785 00 00', 'info@nus.se', 0),
('SE556998-7766', 'MediTex Scandinavia', 'supplier', NULL, 'Väversgatan 12', '411 05', 'Göteborg', 'SE', '031-987 65 43', 'order@meditex.se', 1),
('SE556334-5566', 'Sjukvårdstextil Sverige', 'supplier', NULL, 'Fabriksvägen 8', '721 31', 'Västerås', 'SE', '021-456 78 90', 'kontakt@sjukvardstextil.se', 1),
('DK12345678', 'Nordic Healthcare Textiles', 'supplier', NULL, 'Vesterbrogade 100', '1620', 'København', 'DK', '+45 33 12 34 56', 'sales@nht.dk', 1),
('SE556445-1122', 'Berendsen Textil Service', 'laundry', NULL, 'Tvättvägen 5', '169 70', 'Solna', 'SE', '08-555 123 00', 'info@berendsen.se', 1),
('SE556778-9900', 'Tvättman AB', 'laundry', NULL, 'Renhållningsvägen 22', '421 30', 'Västra Frölunda', 'SE', '031-712 00 00', 'order@tvattman.se', 1),
('SE556223-1199', 'CleanCare Medical', 'laundry', NULL, 'Sterilgatan 15', '212 35', 'Malmö', 'SE', '040-680 50 00', 'service@cleancare.se', 1);

-- Users (password: Test123!)
INSERT INTO users (id, email, password, name, role, organization_id) VALUES
(1, 'admin@petersjostedt.se', '$argon2id$v=19$m=65536,t=4,p=1$ZHp4c253bEpZTzhTRzAvbA$qVt4qkeCa1NB/dJsHNNeLuYMtQFmkdCkKYqez4SpzuM', 'Admin', 'admin', NULL),
(2, 'admin@karolinska.se', '$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A', 'Anna Karlsson', 'org_admin', 'SE556677-8899'),
(3, 'chef@sahlgrenska.se', '$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A', 'Erik Lindberg', 'org_admin', 'SE556011-2233'),
(4, 'admin@sus.se', '$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A', 'Maria Svensson', 'org_admin', 'SE556445-6677'),
(5, 'lars.berg@karolinska.se', '$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A', 'Lars Berg', 'user', 'SE556677-8899'),
(6, 'eva.holm@karolinska.se', '$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A', 'Eva Holm', 'user', 'SE556677-8899'),
(7, 'johan.nyman@sahlgrenska.se', '$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A', 'Johan Nyman', 'user', 'SE556011-2233'),
(8, 'karin.lund@sus.se', '$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A', 'Karin Lund', 'user', 'SE556445-6677'),
(9, 'per.strom@akademiska.se', '$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A', 'Per Ström', 'user', 'SE556889-0011'),
(10, 'admin@textilia.se', '$argon2id$v=19$m=65536,t=4,p=3$ZGV1bi9xSWFhbG1DNFRwZg$VqivxmaDSdUglSSXjqTsZ9GXVfBFHVCPaMrWt0MYdOs', 'Anders Textil', 'org_admin', 'SE556112-3344'),
(11, 'chef@meditex.se', '$argon2id$v=19$m=65536,t=4,p=3$cS9CNzRNLndwWi9oZTdSMQ$iyhHBVajpQGhottD91U6OvtK+it0kkDD81SN31A8sco', 'Maria Lindqvist', 'org_admin', 'SE556998-7766'),
(12, 'admin@berendsen.se', '$argon2id$v=19$m=65536,t=4,p=3$UXpsay9LTjFZVjUyYkJkSw$5fVgqEQnruHreRXKK/99VSAZmSRVQxCi+fZSsm9Lc+M', 'Björn Cleansson', 'org_admin', 'SE556445-1122'),
(13, 'chef@tvattman.se', '$argon2id$v=19$m=65536,t=4,p=3$cS9CNzRNLndwWi9oZTdSMQ$iyhHBVajpQGhottD91U6OvtK+it0kkDD81SN31A8sco', 'Kerstin Tvätt', 'org_admin', 'SE556778-9900'),
(14, 'lager@textilia.se', '$argon2id$v=19$m=65536,t=4,p=3$cS9CNzRNLndwWi9oZTdSMQ$iyhHBVajpQGhottD91U6OvtK+it0kkDD81SN31A8sco', 'Erik Lagerström', 'user', 'SE556112-3344'),
(15, 'produktion@berendsen.se', '$argon2id$v=19$m=65536,t=4,p=3$cS9CNzRNLndwWi9oZTdSMQ$iyhHBVajpQGhottD91U6OvtK+it0kkDD81SN31A8sco', 'Lisa Produktsson', 'user', 'SE556445-1122');

-- Units
INSERT INTO units (id, organization_id, name, api_key, password, is_active) VALUES
(3, 'SE556677-8899', 'Sterilcentralen', 'api_karolinska_steril_001', '$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A', 1),
(4, 'SE556677-8899', 'Kirurgi A', 'api_karolinska_kirurgia_002', '$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A', 1),
(5, 'SE556677-8899', 'Kirurgi B', 'api_karolinska_kirurgib_003', '$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A', 1),
(6, 'SE556677-8899', 'Akutmottagningen', 'api_karolinska_akut_004', '$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A', 1),
(7, 'SE556677-8899', 'IVA', 'api_karolinska_iva_005', '$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A', 1),
(8, 'SE556011-2233', 'Sterilcentralen SU', 'api_sahlgrenska_steril_001', '$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A', 1),
(9, 'SE556011-2233', 'Ortopedi', 'api_sahlgrenska_ortopedi_002', '$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A', 1),
(10, 'SE556011-2233', 'Kardiologi', 'api_sahlgrenska_kardio_003', '$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A', 1),
(11, 'SE556445-6677', 'Sterilcentral Malmö', 'api_sus_steril_001', '$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A', 1),
(12, 'SE556445-6677', 'Operation Lund', 'api_sus_op_lund_002', '$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A', 1),
(13, 'SE556889-0011', 'Sterilcentralen Uppsala', 'api_akademiska_steril_001', '$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A', 1),
(14, 'SE556112-3344', 'Produktion Norrköping', 'api_textilia_prod_001', '$argon2id$v=19$m=65536,t=4,p=3$cS9CNzRNLndwWi9oZTdSMQ$iyhHBVajpQGhottD91U6OvtK+it0kkDD81SN31A8sco', 1),
(15, 'SE556112-3344', 'Lager & Distribution', 'api_textilia_lager_002', '$argon2id$v=19$m=65536,t=4,p=3$cS9CNzRNLndwWi9oZTdSMQ$iyhHBVajpQGhottD91U6OvtK+it0kkDD81SN31A8sco', 1),
(16, 'SE556998-7766', 'Tillverkning', 'api_meditex_tillv_001', '$argon2id$v=19$m=65536,t=4,p=3$cS9CNzRNLndwWi9oZTdSMQ$iyhHBVajpQGhottD91U6OvtK+it0kkDD81SN31A8sco', 1),
(17, 'SE556445-1122', 'Tvätteri Solna', 'api_berendsen_solna_001', '$argon2id$v=19$m=65536,t=4,p=3$cS9CNzRNLndwWi9oZTdSMQ$iyhHBVajpQGhottD91U6OvtK+it0kkDD81SN31A8sco', 1),
(18, 'SE556445-1122', 'Tvätteri Göteborg', 'api_berendsen_gbg_002', '$argon2id$v=19$m=65536,t=4,p=3$cS9CNzRNLndwWi9oZTdSMQ$iyhHBVajpQGhottD91U6OvtK+it0kkDD81SN31A8sco', 1),
(19, 'SE556778-9900', 'Huvudtvätteri', 'api_tvattman_main_001', '$argon2id$v=19$m=65536,t=4,p=3$cS9CNzRNLndwWi9oZTdSMQ$iyhHBVajpQGhottD91U6OvtK+it0kkDD81SN31A8sco', 1),
(20, 'SE556223-1199', 'Steriltvätteri Malmö', 'api_cleancare_malmo_001', '$argon2id$v=19$m=65536,t=4,p=3$cS9CNzRNLndwWi9oZTdSMQ$iyhHBVajpQGhottD91U6OvtK+it0kkDD81SN31A8sco', 1);

-- Organization relations
INSERT INTO organization_relations (organization_id, partner_org_id, relation_type, is_active) VALUES
('SE556112-3344', 'SE556889-0011', 'customer', 1),
('SE556112-3344', 'SE556223-1199', 'customer', 1);

-- Articles (Textilia)
INSERT INTO articles (id, organization_id, sku, name, description, data, is_used, is_active) VALUES
(4, 'SE556112-3344', 'BLU-L-BOM-VIT', 'Blus', NULL, '{"farg": "Vit", "storlek": "L", "material": "Bomull", "artikelnamn": "Blus"}', 0, 1),
(5, 'SE556112-3344', 'BYX-3XL-BOM-VIT', 'Byxa', NULL, '{"farg": "Vit", "storlek": "3XL", "material": "Bomull", "artikelnamn": "Byxa"}', 0, 1),
(6, 'SE556112-3344', 'BLU-BOM-VIT-XS', 'Blus', NULL, '{"farg": "Vit", "storlek": "XS", "material": "Bomull", "artikelnamn": "Blus"}', 0, 1),
(7, 'SE556112-3344', 'BLU-BOM-VIT-S', 'Blus', NULL, '{"farg": "Vit", "storlek": "S", "material": "Bomull", "artikelnamn": "Blus"}', 0, 1),
(8, 'SE556112-3344', 'BLU-BOM-VIT-M', 'Blus', NULL, '{"farg": "Vit", "storlek": "M", "material": "Bomull", "artikelnamn": "Blus"}', 0, 1),
(9, 'SE556112-3344', 'BLU-BOM-VIT-L', 'Blus', NULL, '{"farg": "Vit", "storlek": "L", "material": "Bomull", "artikelnamn": "Blus"}', 0, 1),
(10, 'SE556112-3344', 'BLU-BOM-VIT-XL', 'Blus', NULL, '{"farg": "Vit", "storlek": "XL", "material": "Bomull", "artikelnamn": "Blus"}', 0, 1),
(11, 'SE556112-3344', 'BLU-BOM-VIT-2XL', 'Blus', NULL, '{"farg": "Vit", "storlek": "2XL", "material": "Bomull", "artikelnamn": "Blus"}', 0, 1);

-- Events (shipment)
INSERT INTO events (id, organization_id, event_type, metadata, event_at) VALUES
(1, 'SE556112-3344', 'shipment', '{"customer": "SE556889-0011", "parentId": null, "producer": "SE556112-3344", "createdBy": {"unitId": null, "userId": 10}, "shipmentId": "SH-2026-001", "salesOrderId": "", "purchaseOrderId": ""}', '2026-01-14 21:22:07');
