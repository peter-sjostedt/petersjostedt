-- Migration: Add organization support to users table
-- Created: 2026-01-13 16:00:00
-- Description: Adds organization_id and org_admin role to enable organization admins

-- ==================================================
-- UP: Kör denna SQL för att applicera migrationen
-- ==================================================

-- Add organization_id to users table
ALTER TABLE users
ADD COLUMN organization_id VARCHAR(20) NULL AFTER role,
ADD CONSTRAINT fk_users_organization FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

-- Update role enum to include org_admin
ALTER TABLE users
MODIFY COLUMN role ENUM('admin', 'org_admin', 'user') NOT NULL DEFAULT 'user';

-- Add index for organization queries
ALTER TABLE users
ADD INDEX idx_organization_id (organization_id);

-- Add comment to clarify role usage
ALTER TABLE users
MODIFY COLUMN role ENUM('admin', 'org_admin', 'user') NOT NULL DEFAULT 'user' COMMENT 'admin=system admin, org_admin=organization admin, user=basic user';

-- ==================================================
-- DOWN: Kör denna SQL för att rulla tillbaka
-- ==================================================

-- Remove foreign key constraint
ALTER TABLE users
DROP FOREIGN KEY fk_users_organization;

-- Remove organization_id column
ALTER TABLE users
DROP COLUMN organization_id;

-- Revert role enum to original
ALTER TABLE users
MODIFY COLUMN role ENUM('admin', 'user') NOT NULL DEFAULT 'user';

-- Remove index
ALTER TABLE users
DROP INDEX idx_organization_id;
