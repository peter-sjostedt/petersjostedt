-- Migration: Add supplier and laundry organization types
-- Created: 2026-01-14
-- Description: Extends org_type enum to include suppliers (textile producers) and laundries

-- ==================================================
-- UP: Kör denna SQL för att applicera migrationen
-- ==================================================

ALTER TABLE organizations
MODIFY COLUMN org_type ENUM('system', 'customer', 'supplier', 'laundry') DEFAULT 'customer'
COMMENT 'system=Hospitex, customer=sjukhus/vård, supplier=textilproducent, laundry=tvätteri';

-- ==================================================
-- DOWN: Kör denna SQL för att rulla tillbaka
-- ==================================================

-- OBS: Kräver att inga organisationer har supplier eller laundry som typ
ALTER TABLE organizations
MODIFY COLUMN org_type ENUM('system', 'customer') DEFAULT 'customer';
