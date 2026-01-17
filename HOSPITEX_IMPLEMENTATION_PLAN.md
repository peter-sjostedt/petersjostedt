# Hospitex RFID Tracking System - Implementation Plan

## Översikt

Detta dokument beskriver hur vi ska integrera Hospitex RFID Tracking System som den **primära applikationen** på petersjostedt.se domänen, med en subdomain-baserad arkitektur.

### Viktiga beslut

- ✅ Subdomain-struktur (INTE folder-baserad)
- ✅ Capabilities-baserad behörighetsmodell (flexibel, organisationer kan både skicka OCH ta emot)
- ✅ API för mobil- och desktop-applikationer
- ✅ Behåll befintlig admin-panel funktionalitet (files, backups, migrations, etc.)
- ❌ Inga ändringar i Laragon vhost-konfiguration

---

## Fas 1: Databas - Skapa Hospitex-tabeller

### 1.1 Skapa ny migration-fil

**Fil**: `admin.petersjostedt.se/migrations/003_hospitex_schema.sql`

Denna migration skapar:

#### Organisationstabeller
```sql
CREATE TABLE organizations (
    id VARCHAR(20) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    org_type ENUM('system', 'partner') NOT NULL DEFAULT 'partner',
    capabilities JSON COMMENT '{"can_produce": bool, "can_receive": bool, "can_scan": bool, "can_map": bool}',
    contact_info JSON COMMENT '{"address": "", "phone": "", "email": ""}',
    custom_fields JSON COMMENT 'Anpassningsbara fält per organisation',
    api_key VARCHAR(64) UNIQUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_org_type (org_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Artikeltabeller
```sql
CREATE TABLE articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id VARCHAR(20) NOT NULL,
    sku VARCHAR(100) NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    data JSON COMMENT 'Dynamiska fält per artikel',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_org_sku (organization_id, sku),
    INDEX idx_sku (sku),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE article_mappings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_org_id VARCHAR(20) NOT NULL,
    customer_article_id INT NOT NULL,
    producer_org_id VARCHAR(20) NOT NULL,
    producer_article_id INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (customer_org_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (producer_org_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (producer_article_id) REFERENCES articles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_mapping (customer_article_id, producer_article_id),
    INDEX idx_customer_org (customer_org_id),
    INDEX idx_producer_org (producer_org_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### RFID-tabeller
```sql
CREATE TABLE rfids (
    id INT AUTO_INCREMENT PRIMARY KEY,
    epc VARCHAR(100) NOT NULL UNIQUE COMMENT 'RFID tag ID',
    article_id INT,
    organization_id VARCHAR(20) NOT NULL,
    first_event_id INT COMMENT 'Första händelsen där taggen skannas',
    latest_event_id INT COMMENT 'Senaste händelsen',
    status ENUM('registered', 'tagged', 'shipped', 'received', 'inactive') DEFAULT 'registered',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE SET NULL,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    INDEX idx_epc (epc),
    INDEX idx_article (article_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Händelsetabeller
```sql
CREATE TABLE event_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE COMMENT 'tagged, shipped, received, order_created, etc.',
    label VARCHAR(100) NOT NULL,
    description TEXT,
    priority INT DEFAULT 50 COMMENT 'Högre = viktigare, används för sortering',
    is_system BOOLEAN DEFAULT FALSE COMMENT 'Systemhändelser kan inte raderas',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type_id INT NOT NULL,
    organization_id VARCHAR(20) NOT NULL,
    user_id INT COMMENT 'Användare som skapade händelsen',
    related_order_id INT COMMENT 'Koppling till order om relevant',
    metadata JSON COMMENT 'Flexibel data: order_number, po_number, notes, etc.',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_type_id) REFERENCES event_types(id) ON DELETE RESTRICT,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    INDEX idx_event_type (event_type_id),
    INDEX idx_organization (organization_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE event_rfids (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    rfid_id INT NOT NULL,
    scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (rfid_id) REFERENCES rfids(id) ON DELETE CASCADE,
    UNIQUE KEY unique_event_rfid (event_id, rfid_id),
    INDEX idx_event (event_id),
    INDEX idx_rfid (rfid_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Ordertabeller
```sql
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(100) NOT NULL,
    producer_org_id VARCHAR(20) NOT NULL,
    customer_org_id VARCHAR(20) NOT NULL,
    status ENUM('new', 'active', 'received', 'closed') DEFAULT 'new',
    po_number VARCHAR(100) COMMENT 'Purchase Order nummer från kund',
    delivery_date DATE,
    metadata JSON COMMENT 'Flexibel orderdata',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (producer_org_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_org_id) REFERENCES organizations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_order_number (producer_org_id, order_number),
    INDEX idx_status (status),
    INDEX idx_producer (producer_org_id),
    INDEX idx_customer (customer_org_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    article_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    delivered_quantity INT DEFAULT 0,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE RESTRICT,
    UNIQUE KEY unique_order_article (order_id, article_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Scanner-sessioner
```sql
CREATE TABLE scanner_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id VARCHAR(20) NOT NULL,
    user_id INT NOT NULL,
    session_type ENUM('tagging', 'receiving', 'inventory') NOT NULL,
    article_id INT COMMENT 'Artikel som taggas (vid tagging)',
    order_id INT COMMENT 'Order som tas emot (vid receiving)',
    status ENUM('active', 'paused', 'completed', 'cancelled') DEFAULT 'active',
    scanned_count INT DEFAULT 0,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE SET NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 1.2 Modifiera befintlig users-tabell

```sql
ALTER TABLE users
    ADD COLUMN organization_id VARCHAR(20) AFTER id,
    ADD FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    ADD INDEX idx_organization (organization_id);

-- Skapa system-organisation för befintliga admin-användare
INSERT INTO organizations (id, name, org_type, capabilities)
VALUES ('SYSTEM', 'Hospitex System', 'system', '{"can_produce": true, "can_receive": true, "can_scan": true, "can_map": true}');

-- Uppdatera befintliga admin-användare att tillhöra SYSTEM-organisationen
UPDATE users SET organization_id = 'SYSTEM' WHERE role = 'admin';
```

### 1.3 Initiera event_types

```sql
INSERT INTO event_types (code, label, description, priority, is_system) VALUES
('tagged', 'Taggad', 'RFID-tagg kopplad till artikel', 70, TRUE),
('shipped', 'Skickad', 'Artikel skickad från producent', 60, TRUE),
('received', 'Mottagen', 'Artikel mottagen av kund', 50, TRUE),
('order_created', 'Order skapad', 'Ny order registrerad', 40, TRUE),
('order_activated', 'Order aktiverad', 'RFID-taggar tillagda i order', 45, TRUE),
('order_closed', 'Order stängd', 'Order slutförd', 30, TRUE),
('inventory', 'Inventering', 'Lagersaldo-skanning', 20, FALSE);
```

---

## Fas 2: Filstruktur för subdomäner

### 2.1 Skapa subdomain-mapper

```
c:\laragon\www\petersjostedt\
│
├── admin.petersjostedt.se\          # Befintlig admin-panel (system admin)
│   ├── index.php                     # Dashboard
│   ├── users.php                     # Användarhantering
│   ├── organizations.php             # [NY] Organisationshantering
│   ├── event_types.php               # [NY] Händelsetyper
│   ├── files.php                     # Befintlig filhantering
│   ├── settings.php                  # Befintliga inställningar
│   ├── logs.php                      # Befintliga loggar
│   ├── sessions.php                  # Befintliga sessioner
│   ├── backup.php                    # Befintlig backup
│   ├── migrations.php                # Befintliga migrationer
│   ├── includes\
│   ├── css\
│   └── js\
│
├── partner.petersjostedt.se\         # [NY] Partner-portal
│   ├── index.php                     # Dashboard med capabilities-baserad vy
│   ├── articles.php                  # Artikelhantering
│   ├── orders.php                    # Orderhantering (skicka/ta emot baserat på capabilities)
│   ├── mappings.php                  # Artikelmappningar
│   ├── scanner.php                   # RFID-skanner-gränssnitt
│   ├── reports.php                   # Rapporter och statistik
│   ├── settings.php                  # Organisationsinställningar
│   ├── import.php                    # CSV-import
│   ├── export.php                    # CSV-export
│   ├── includes\
│   │   ├── auth.php                  # Partner-autentisering
│   │   ├── header.php                # Partner-navigation
│   │   └── footer.php
│   ├── css\
│   │   └── partner.css
│   └── js\
│       ├── articles.js
│       ├── orders.js
│       ├── scanner.js
│       └── mappings.js
│
├── api.petersjostedt.se\             # [NY] REST API
│   ├── index.php                     # API-router
│   ├── auth.php                      # JWT-autentisering
│   ├── v1\
│   │   ├── articles.php              # Artikel-endpoints
│   │   ├── orders.php                # Order-endpoints
│   │   ├── rfids.php                 # RFID-endpoints
│   │   ├── events.php                # Event-endpoints
│   │   ├── mappings.php              # Mapping-endpoints
│   │   ├── scanner.php               # Scanner-session endpoints
│   │   └── organizations.php         # Organisation-endpoints (admin only)
│   └── includes\
│       ├── ApiAuth.php               # JWT-hantering
│       └── ApiResponse.php           # Standardiserade svar
│
├── config\                           # Delad konfiguration
│   ├── config.php                    # Befintlig huvudconfig
│   ├── database.php                  # Befintlig DB-config
│   ├── languages.php                 # Befintlig språkconfig
│   ├── translations.php              # Befintliga + nya översättningar
│   └── routes.php                    # [UPPDATERA] Lägg till Hospitex-rutter
│
├── src\                              # Delade PHP-klasser
│   ├── Language.php                  # Befintlig
│   ├── Database.php                  # [NY] PDO wrapper
│   ├── Auth.php                      # [NY] Autentisering
│   ├── Organization.php              # [NY] Organisationslogik
│   ├── Article.php                   # [NY] Artikellogik
│   ├── Order.php                     # [NY] Orderlogik
│   ├── RFID.php                      # [NY] RFID-logik
│   ├── Event.php                     # [NY] Händelselogik
│   ├── Scanner.php                   # [NY] Scanner-session logik
│   ├── Mapper.php                    # [NY] Artikelmappning
│   ├── Permissions.php               # [NY] Capability-baserade permissions
│   ├── Import.php                    # [NY] CSV-import
│   ├── Export.php                    # [NY] CSV-export
│   └── Logger.php                    # [NY] Händelseloggning
│
└── public_html\                      # Befintlig publik site (om den ska vara kvar)
```

### 2.2 .htaccess för API (api.petersjostedt.se)

```apache
# api.petersjostedt.se/.htaccess
RewriteEngine On

# CORS headers för mobil/desktop-appar
Header set Access-Control-Allow-Origin "*"
Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
Header set Access-Control-Allow-Headers "Content-Type, Authorization"

# Hantera OPTIONS preflight
RewriteCond %{REQUEST_METHOD} OPTIONS
RewriteRule ^(.*)$ $1 [R=200,L]

# Route all API requests to index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?path=$1 [QSA,L]
```

---

## Fas 3: Kopiering och anpassning från hospitex-referensen

### 3.1 Filer att kopiera DIREKT (minimal anpassning)

Från `hospitex/httpd.www/`:

**Modaler** (använder liknande system som vi redan har):
- `modals/article_form.php` → `partner.petersjostedt.se/modals/`
- `modals/order_form.php` → `partner.petersjostedt.se/modals/`
- `modals/order_details.php` → `partner.petersjostedt.se/modals/`
- `modals/mapping_form.php` → `partner.petersjostedt.se/modals/`
- `modals/qr_view.php` → `partner.petersjostedt.se/modals/`

**CSS** (baserat på samma struktur):
- `assets/css/tables.css` → `partner.petersjostedt.se/css/`
- `assets/css/modals.css` → Jämför med vår befintliga `admin.petersjostedt.se/css/modals.css`
- `assets/css/search.css` → `partner.petersjostedt.se/css/`

**JavaScript**:
- `assets/js/modals.js` → `partner.petersjostedt.se/js/`
- `assets/js/orders.js` → `partner.petersjostedt.se/js/`
- `assets/js/search.js` → `partner.petersjostedt.se/js/`

### 3.2 Filer att ANPASSA kraftigt

**Lib-klasser** → Konvertera till nya `src/` klasser:

| Hospitex-fil | Ny fil | Anpassningar |
|--------------|--------|--------------|
| `lib/Api.php` | `src/Auth.php` | Omskriv till JWT + API-nyckel |
| `lib/Permissions.php` | `src/Permissions.php` | Byt från producer/customer till capabilities |
| `lib/Logger.php` | `src/Logger.php` | Integrera med vårt befintliga loggsystem |
| `lib/Import.php` | `src/Import.php` | Behåll CSV-import logik |
| `lib/Export.php` | `src/Export.php` | Behåll CSV-export logik |

**API-endpoints** → Skapa REST API med standardiserade svar:

| Hospitex API | Ny API v1 | Ändringar |
|--------------|-----------|-----------|
| `api/articles.php` | `api/v1/articles.php` | JWT-auth, JSON-svar, capabilities-check |
| `api/orders.php` | `api/v1/orders.php` | JWT-auth, JSON-svar, capabilities-check |
| `api/mappings.php` | `api/v1/mappings.php` | JWT-auth, JSON-svar |
| `api/organizations.php` | `api/v1/organizations.php` | Endast system admin |
| `api/scan.php` | `api/v1/scanner.php` | Session-baserad skanning |

### 3.3 Filer att BYGGA NYA (inspiration från hospitex)

**Partner-portal sidor**:
- `partner.petersjostedt.se/index.php` - Dashboard med capabilities-baserad vy
- `partner.petersjostedt.se/articles.php` - Artikelhantering (basera på hospitex/producer/articles.php)
- `partner.petersjostedt.se/orders.php` - Unified ordervy (både skickade och mottagna)
- `partner.petersjostedt.se/scanner.php` - RFID-scanner (basera på hospitex/scanner/index.php)

**Admin-panel tillägg**:
- `admin.petersjostedt.se/organizations.php` - Organisationshantering (basera på hospitex/admin/organizations.php)
- `admin.petersjostedt.se/event_types.php` - Händelsetyper (basera på hospitex/admin/)

---

## Fas 4: Implementation steg-för-steg

### Steg 1: Databas och grundstruktur (1-2 dagar)
1. ✅ Skapa migration `003_hospitex_schema.sql`
2. ✅ Kör migration via befintlig admin-panel
3. ✅ Skapa `src/Database.php` wrapper-klass
4. ✅ Skapa tomma subdomain-mappar
5. ✅ Kopiera `includes/db.php` logik till ny `src/Database.php`

### Steg 2: Autentisering och organisation (2-3 dagar)
6. ✅ Skapa `src/Organization.php` klass
7. ✅ Skapa `src/Permissions.php` med capability-logik
8. ✅ Skapa `src/Auth.php` för session-baserad auth
9. ✅ Skapa `admin.petersjostedt.se/organizations.php`
10. ✅ Skapa `admin.petersjostedt.se/modals/organization_form.php`
11. ✅ Testa skapa test-organisationer via admin-panel

### Steg 3: Artikelsystem (3-4 dagar)
12. ✅ Skapa `src/Article.php` klass
13. ✅ Skapa `partner.petersjostedt.se/articles.php`
14. ✅ Kopiera och anpassa `modals/article_form.php`
15. ✅ Skapa `src/Import.php` och `src/Export.php`
16. ✅ Implementera CSV import/export för artiklar
17. ✅ Testa artikelhantering

### Steg 4: RFID och händelser (3-4 dagar)
18. ✅ Skapa `src/RFID.php` klass
19. ✅ Skapa `src/Event.php` klass
20. ✅ Skapa `src/Scanner.php` för scanner-sessioner
21. ✅ Skapa `admin.petersjostedt.se/event_types.php`
22. ✅ Skapa `partner.petersjostedt.se/scanner.php`
23. ✅ Testa RFID-taggning workflow

### Steg 5: Orderhantering (4-5 dagar)
24. ✅ Skapa `src/Order.php` klass
25. ✅ Skapa `partner.petersjostedt.se/orders.php` (unified view)
26. ✅ Kopiera och anpassa `modals/order_form.php`
27. ✅ Kopiera och anpassa `modals/order_details.php`
28. ✅ Implementera order-status workflow (NEW → ACTIVE → RECEIVED → CLOSED)
29. ✅ Testa hela orderflödet mellan två organisationer

### Steg 6: Artikelmappningar (2-3 dagar)
30. ✅ Skapa `src/Mapper.php` klass
31. ✅ Skapa `partner.petersjostedt.se/mappings.php`
32. ✅ Kopiera och anpassa `modals/mapping_form.php`
33. ✅ Testa mappningar mellan kund- och producentartiklar

### Steg 7: REST API (5-6 dagar)
34. ✅ Skapa `api.petersjostedt.se/index.php` router
35. ✅ Skapa `api/includes/ApiAuth.php` (JWT-hantering)
36. ✅ Skapa `api/includes/ApiResponse.php`
37. ✅ Implementera alla `api/v1/` endpoints
38. ✅ Skapa API-dokumentation
39. ✅ Testa med Postman/Insomnia

### Steg 8: Dashboard och rapporter (3-4 dagar)
40. ✅ Skapa `partner.petersjostedt.se/index.php` dashboard
41. ✅ Skapa `partner.petersjostedt.se/reports.php`
42. ✅ Uppdatera `admin.petersjostedt.se/index.php` med Hospitex-statistik
43. ✅ Implementera grafer och översikter

### Steg 9: Språkhantering (2-3 dagar)
44. ✅ Lägg till alla Hospitex-översättningar i `config/translations.php`
45. ✅ Byt alla hard-coded texter till `t()` funktioner
46. ✅ Testa både svenska och engelska

### Steg 10: Test och säkerhet (3-4 dagar)
47. ✅ Säkerhetsaudit (SQL injection, XSS, CSRF)
48. ✅ Testa alla capabilities-kombinationer
49. ✅ Testa orderflöden mellan organisationer
50. ✅ Testa RFID-skanning med testdata
51. ✅ Performance-tester med stora dataset

---

## Fas 5: Översättningar som behövs

Lägg till i `config/translations.php`:

```php
// Organisationer
'org.type.system' => ['sv' => 'System', 'en' => 'System'],
'org.type.partner' => ['sv' => 'Partner', 'en' => 'Partner'],
'org.capability.produce' => ['sv' => 'Kan producera', 'en' => 'Can produce'],
'org.capability.receive' => ['sv' => 'Kan ta emot', 'en' => 'Can receive'],
'org.capability.scan' => ['sv' => 'Kan skanna RFID', 'en' => 'Can scan RFID'],
'org.capability.map' => ['sv' => 'Kan mappa artiklar', 'en' => 'Can map articles'],

// Artiklar
'article.sku' => ['sv' => 'Artikelnummer (SKU)', 'en' => 'Article number (SKU)'],
'article.name' => ['sv' => 'Artikelnamn', 'en' => 'Article name'],
'article.create' => ['sv' => 'Skapa artikel', 'en' => 'Create article'],
'article.edit' => ['sv' => 'Redigera artikel', 'en' => 'Edit article'],

// RFID
'rfid.epc' => ['sv' => 'RFID-tagg (EPC)', 'en' => 'RFID tag (EPC)'],
'rfid.scan' => ['sv' => 'Skanna RFID', 'en' => 'Scan RFID'],
'rfid.status.registered' => ['sv' => 'Registrerad', 'en' => 'Registered'],
'rfid.status.tagged' => ['sv' => 'Taggad', 'en' => 'Tagged'],
'rfid.status.shipped' => ['sv' => 'Skickad', 'en' => 'Shipped'],
'rfid.status.received' => ['sv' => 'Mottagen', 'en' => 'Received'],

// Ordrar
'order.number' => ['sv' => 'Ordernummer', 'en' => 'Order number'],
'order.status.new' => ['sv' => 'Ny', 'en' => 'New'],
'order.status.active' => ['sv' => 'Aktiv', 'en' => 'Active'],
'order.status.received' => ['sv' => 'Mottagen', 'en' => 'Received'],
'order.status.closed' => ['sv' => 'Stängd', 'en' => 'Closed'],
'order.create' => ['sv' => 'Skapa order', 'en' => 'Create order'],
'order.add_rfid' => ['sv' => 'Lägg till RFID', 'en' => 'Add RFID'],

// Mappningar
'mapping.create' => ['sv' => 'Skapa mappning', 'en' => 'Create mapping'],
'mapping.customer_article' => ['sv' => 'Kundartikel', 'en' => 'Customer article'],
'mapping.producer_article' => ['sv' => 'Producentartikel', 'en' => 'Producer article'],

// Scanner
'scanner.session.start' => ['sv' => 'Starta skanning', 'en' => 'Start scanning'],
'scanner.session.type.tagging' => ['sv' => 'Taggning', 'en' => 'Tagging'],
'scanner.session.type.receiving' => ['sv' => 'Mottagning', 'en' => 'Receiving'],
'scanner.session.type.inventory' => ['sv' => 'Inventering', 'en' => 'Inventory'],
'scanner.scanned_count' => ['sv' => 'Antal skannade', 'en' => 'Scanned count'],

// Events
'event.type.tagged' => ['sv' => 'Taggad', 'en' => 'Tagged'],
'event.type.shipped' => ['sv' => 'Skickad', 'en' => 'Shipped'],
'event.type.received' => ['sv' => 'Mottagen', 'en' => 'Received'],
'event.type.order_created' => ['sv' => 'Order skapad', 'en' => 'Order created'],

// API
'api.auth.invalid_key' => ['sv' => 'Ogiltig API-nyckel', 'en' => 'Invalid API key'],
'api.auth.invalid_token' => ['sv' => 'Ogiltig JWT-token', 'en' => 'Invalid JWT token'],
'api.permission_denied' => ['sv' => 'Åtkomst nekad', 'en' => 'Access denied'],
```

**Uppskattning**: ~200-300 nya översättningsnycklar totalt

---

## Fas 6: Capabilities-baserad behörighetsmodell

### Hur capabilities fungerar

Varje organisation har en JSON-kolumn med capabilities:

```json
{
  "can_produce": true,
  "can_receive": true,
  "can_scan": true,
  "can_map": true
}
```

### Permissions-klass exempel

```php
// src/Permissions.php
class Permissions {
    private $org;

    public function __construct($organization) {
        $this->org = $organization;
    }

    public function canProduce() {
        return $this->org['capabilities']['can_produce'] ?? false;
    }

    public function canReceive() {
        return $this->org['capabilities']['can_receive'] ?? false;
    }

    public function canScan() {
        return $this->org['capabilities']['can_scan'] ?? false;
    }

    public function canMap() {
        return $this->org['capabilities']['can_map'] ?? false;
    }

    public function canCreateOrder() {
        return $this->canProduce(); // Endast producenter skapar ordrar
    }

    public function canReceiveOrder() {
        return $this->canReceive(); // Endast mottagare tar emot ordrar
    }

    public function canViewOrders() {
        return $this->canProduce() || $this->canReceive();
    }
}
```

### Användning i vyer

```php
// partner.petersjostedt.se/index.php
$perms = new Permissions($_SESSION['organization']);

if ($perms->canProduce()) {
    echo '<a href="orders.php?view=outgoing">Utgående ordrar</a>';
}

if ($perms->canReceive()) {
    echo '<a href="orders.php?view=incoming">Inkommande ordrar</a>';
}

if ($perms->canScan()) {
    echo '<a href="scanner.php">RFID-skanner</a>';
}
```

---

## Fas 7: API-autentisering med JWT

### Flöde för mobil-/desktop-app

1. **Inloggning** → `POST /api/auth.php`
   ```json
   {
     "api_key": "org_abc123...",
     "username": "user@example.com",
     "password": "password"
   }
   ```

2. **Svar** → JWT-token
   ```json
   {
     "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
     "expires_in": 3600,
     "organization": {
       "id": "ORG001",
       "name": "Example Hospital",
       "capabilities": {...}
     }
   }
   ```

3. **Använd token** → Alla API-requests
   ```
   Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
   ```

### ApiAuth.php exempel

```php
// api/includes/ApiAuth.php
class ApiAuth {
    public static function validateToken() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(['error' => 'Missing token']);
            exit;
        }

        $token = $matches[1];
        // Validera JWT med secret från config
        $decoded = JWT::decode($token, JWT_SECRET);

        return $decoded; // Innehåller user_id, org_id, capabilities
    }
}
```

---

## Fas 8: Testdata och simulering

### Skapa testorganisationer

```sql
-- Producent med alla capabilities
INSERT INTO organizations (id, name, org_type, capabilities) VALUES
('PROD001', 'MedSupply AB', 'partner', '{"can_produce": true, "can_receive": false, "can_scan": true, "can_map": false}');

-- Kund med mottagning och mappning
INSERT INTO organizations (id, name, org_type, capabilities) VALUES
('CUST001', 'Karolinska Sjukhuset', 'partner', '{"can_produce": false, "can_receive": true, "can_scan": true, "can_map": true}');

-- Hybridorganisation (både producent och kund)
INSERT INTO organizations (id, name, org_type, capabilities) VALUES
('HYBRID001', 'Regional Sterilcentral', 'partner', '{"can_produce": true, "can_receive": true, "can_scan": true, "can_map": true}');
```

### Skapa testanvändare

```sql
INSERT INTO users (organization_id, username, email, password, role) VALUES
('PROD001', 'producent', 'prod@medsupply.se', '$2y$10$...', 'user'),
('CUST001', 'mottagare', 'recv@karolinska.se', '$2y$10$...', 'user'),
('HYBRID001', 'hybrid', 'admin@sterilcentral.se', '$2y$10$...', 'user');
```

---

## Sammanfattning: Viktigaste besluten

| Beslut | Motivering |
|--------|------------|
| **Subdomain-struktur** | Bättre separation, säkerhet, deployment |
| **Capabilities istället för fasta roller** | Flexibilitet - organisationer kan både skicka OCH ta emot |
| **JWT för API** | Stöd för mobil/desktop-appar, stateless auth |
| **Behåll befintlig admin-panel** | Utöka med Hospitex-funktioner, kassera inte |
| **Ingen Laragon vhost-ändring** | Fungerade inte bra tidigare, använd mappar i stället |
| **Multi-tenant design** | En databas, data isoleras via organization_id |
| **Event sourcing** | Oföränderlig revisionslogg för spårbarhet |

---

## Nästa steg - Diskutera med din vanliga Claude

1. **Börja med Fas 1** - Skapa databasen först
2. **Bygg API-et tidigt** (Fas 7) om mobil/desktop-appar är prioritet
3. **Eller bygg partner-portalen först** om webgränssnitt är prioritet
4. **Testa löpande** med testorganisationer och RFID-data

**Frågor att diskutera**:
- Ska vi behålla public_html för en landningssida eller göra partner-portalen till default?
- Behöver vi stöd för flera språk från start eller börja med svenska?
- Hur ska RFID-skannrar integreras (hårdvara, API, simulering)?
- Ska vi bygga en dedikerad scanner-subdomain eller hålla det i partner-portalen?

---

**Total uppskattad utvecklingstid**: 25-35 dagar för fullständigt system

---

# Hospitex RFID-Tracking - Designbeslut

## Organisationer och enheter

**Princip:** En organisation har alltid minst en unit (avdelning/enhet). Skapas ingen manuellt skapas en automatiskt.

**Fördelar:**
- Events pekar alltid på en unit - inga NULL-checks
- Enhetliga queries
- Framtidssäkert om organisationen växer

```
Litet företag:
  Organization: "Tvätteri AB"
    └── Unit: "Tvätteri AB"

Stort företag:
  Organization: "Sjukhusgruppen"
    ├── Unit: "Sahlgrenska"
    ├── Unit: "Östra"
    └── Unit: "Mölndal"
```

---

## Förberedelse vs Händelse

**Förberedelse (template):**
- Skapas i admin
- Har QR-kod
- Definierar event_type, unit, target_unit
- Kan vara engångs (order) eller återanvändbar (tvätt-QR)
- Ingen RFID-koppling
- Är inte en händelse i sig

**Händelse (event):**
- Skapas vid skanning
- Har alltid RFID involverad
- Kopplas till förberedelse
- Har `event_at` (när det skedde)
- RFID:s kopplas via `rfids_events`

**Exempel - återanvändbar:**
```
Förberedelse: "Till tvätt" (avdelning 3 → tvätteri)

Måndag 08:15  → Event #101, 45 RFID
Tisdag 08:20  → Event #102, 52 RFID
Onsdag 08:10  → Event #103, 38 RFID
```

**Exempel - engångs:**
```
Förberedelse: Sändning ORD-2025-001

Event #201: Avsändare skannar (skickad)
Event #202: Mottagare skannar (mottagen) + ägarbyte

Förberedelsen förbrukad.
```

---

## Händelsetyper

| Event type | Beskrivning | Uppdaterar på rfid |
|------------|-------------|-------------------|
| `tagged` | RFID kopplas till artikel | article_id, owner_org_id |
| `transferred` | Förflyttning | location_unit_id, owner_org_id (vid första leverans) |
| `washed` | Tvättcykel | wash_count +1 |
| `repaired` | Lagning | metadata |
| `inventory` | Inventering | last_event |
| `scrapped` | Kassering | status = scrapped |

---

## Perspektiv på händelser

Samma fysiska händelse ses olika beroende på perspektiv:

```
Fysisk verklighet: 50 plagg flyttas från Tvätteri till Sjukhus

Tvätteriet ser:    "Levererat rent till Sahlgrenska"
Sjukhuset ser:     "Mottaget rent från Tvätteri AB"
```

Lösning: Modellera verkligheten (from_unit, to_unit), presentera perspektivet i gränssnitt.

---

## Transfer med dubbel skanning

```
Avsändare skannar → Event med RFID-lista A
Mottagare skannar → Event med RFID-lista B

Avvikelse: A - B = saknas vid mottagning
```

---

## Ägande vs fysisk plats

```
owner_org_id     = Vem som äger (ändras sällan, typiskt bara vid första leverans)
location_unit_id = Var plagget är just nu (ändras ofta)
```

**Ägarbyte:**
- Sker vid första leverans från producent
- Använder article_mappings för att översätta till mottagarens SKU
- Sker typiskt bara en gång under livscykeln

**Beslut om kassering/reparation:**
- Alltid ägarens beslut

---

## Inventering

**Snabb fråga "vad finns på unit X":**
```sql
SELECT * FROM rfids WHERE location_unit_id = X
```

**Avstämning:**
```
Förväntat:  SELECT epc FROM rfids WHERE location_unit_id = X
Skannat:    RFID:s i inventeringseventet

Saknas:     Förväntat - Skannat
Oväntat:    Skannat - Förväntat
```

---

## RFID = Plagg (1:1)

I första version: ingen hantering av taggbyten. En RFID representerar ett plagg.

---

## Tvätträknare

`wash_count` på rfids-tabellen, räknas upp vid varje `washed`-event.

---

## Databasstruktur

### event_templates (förberedelser)

```sql
event_templates:
  id
  qr_code
  event_type
  unit_id
  target_unit_id
  reusable            -- true/false
  is_active           -- false när engångs är förbrukad
  metadata
  created_at
```

### events

```sql
events:
  id
  template_id         -- koppling till förberedelse
  event_type
  unit_id
  target_unit_id
  event_at            -- när händelsen inträffade
  metadata
  created_at
```

### rfids_events

```sql
rfids_events:
  event_id
  rfid
```

### rfids

```sql
rfids:
  epc
  article_id
  owner_org_id
  location_unit_id
  status              -- active, lost, scrapped
  wash_count
  first_event_id
  first_event_at      -- denormaliserat för snabba queries
  last_event_id
  last_event_at       -- denormaliserat för inventering/rapporter
```

### units (tillägg)

```sql
units:
  ...
  last_inventory_at
  last_inventory_event_id
```

---

## Avgränsningar (första version)

- Byte av RFID-tagg hanteras ej
- Butik (retur etc.) prioriteras ej
- Fokus på: producent, sjukvård/hotell/restaurang, tvätteri

---

## Branschoberoende

Flödet är i princip samma oavsett bransch:
- Producent → Användare → Tvätteri → Användare (loop)
- Kassering avslutar livscykeln
- Tvätteriet äger ofta plaggen, leasar ut till användare
