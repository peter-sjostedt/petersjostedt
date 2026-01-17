-- Drop all tables in correct order (respecting foreign keys)
-- Run this manually in MySQL before running migrations

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS rfid_event;
DROP TABLE IF EXISTS rfids_events;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS event;
DROP TABLE IF EXISTS event_types;
DROP TABLE IF EXISTS rfids;
DROP TABLE IF EXISTS article_mappings;
DROP TABLE IF EXISTS articles;
DROP TABLE IF EXISTS organization_relations;
DROP TABLE IF EXISTS units;
DROP TABLE IF EXISTS files;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS logs;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS organizations;
DROP TABLE IF EXISTS migrations;

SET FOREIGN_KEY_CHECKS = 1;
