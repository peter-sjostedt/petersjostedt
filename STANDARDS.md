# Kodstandard och Designmönster

Detta dokument beskriver de standarder och mönster som ska följas vid utveckling i detta projekt.

## Innehåll
1. [Systemarkitektur](#systemarkitektur)
2. [Filstruktur](#filstruktur)
3. [Databasmigrationer](#databasmigrationer)
4. [PHP-sidor](#php-sidor)
5. [UI/UX-mönster](#uiux-mönster)
6. [JavaScript](#javascript)
7. [Översättningar](#översättningar)
8. [CSS](#css)

---

## Systemarkitektur

Systemet är uppdelat i två huvudkategorier:

### Stamdata (Master Data)
Referensdata som används av händelser. Definieras en gång, används många gånger.

| Data | Tabell | Beskrivning |
|------|--------|-------------|
| **Artiklar** | `articles` | SKU-definitioner med dynamiskt schema per organisation |
| **Relationer** | `organization_relations` | Kunder och leverantörer |
| **Organisationer** | `organizations` | Företag/enheter i systemet |
| **Enheter** | `units` | Platser/avdelningar inom en organisation |

### Händelser (Events)
Saker som sker. Varje händelse sparas i `events`-tabellen med `event_type` och `metadata` (JSON).

| Händelsetyp | event_type | Beskrivning |
|-------------|------------|-------------|
| **Försändelse** | `shipment` | Skicka varor till kund |
| **Inleverans** | `delivery` | Förvänta varor från leverantör |
| **RFID-registrering** | `rfid_register` | Aktivera ny RFID-tagg |
| **RFID-koppling** | `rfid_link` | Knyt RFID till SKU (repetitiv, sker dagligen) |
| **Inventering** | `inventory` | Räkna lager |

### Händelsetyper

**Engångshändelser:**
- RFID-registrering (en gång per tagg)
- Försändelse (en gång per leverans)
- Inleverans (en gång per mottagning)
- Inventering (en gång per tillfälle)

**Repetitiva händelser:**
- RFID-koppling (samma typ av händelse sker dagligen med olika taggar)

### RFID-flöde

1. **Artikel skapas** (Stamdata) - SKU definieras i Partner Portal
2. **QR-kod genereras** - För artikeln, innehåller SKU
3. **RFID skannas** - Via RFID-läsare + app
4. **Koppling sker** - API skapar `rfid_link`-event i bakgrunden
5. **Event sparas** - I `events`-tabellen med referens till artikel

```
Partner Portal          RFID Scanner App          API/Backend
     │                        │                        │
     │  1. Skapa artikel      │                        │
     │  2. Generera QR        │                        │
     │ ──────────────────────>│                        │
     │                        │  3. Skanna QR + RFID   │
     │                        │ ──────────────────────>│
     │                        │                        │  4. Skapa event
     │                        │  5. Bekräftelse        │
     │                        │ <──────────────────────│
```

---

## Filstruktur

```
petersjostedt/
├── config/
│   └── translations.php      # Alla översättningar
├── database/
│   └── migrations/           # SQL-migrationer
│       └── TEMPLATE.sql      # Mall för nya migrationer
├── public_html/
│   ├── admin/                # Admin-panel (system admin)
│   ├── partner/              # Partner Portal (org_admin)
│   │   ├── css/
│   │   ├── js/
│   │   ├── includes/
│   │   └── modals/
│   ├── assets/               # Delade resurser
│   │   ├── css/
│   │   │   └── modal.css
│   │   └── js/
│   │       └── modal.js
│   └── includes/
│       └── config.php
└── src/                      # PHP-klasser
```

---

## Databasmigrationer

**VIKTIGT:** Använd alltid `database/migrations/TEMPLATE.sql` som mall.

### Namngivning

**Tabeller använder plural:**
- `users`, `organizations`, `articles`, `events`, `files`
- `sales_orders`, `purchase_orders`, `shipments`, `deliveries`

**Kopplingstabeller använder plural + plural:**
- `rfid_events` (rfid till events)
- `organization_relations` (organisation till relation)

**PHP-klasser använder singular:**
- `User`, `Organization`, `Article`, `Event`
- `SalesOrder`, `Shipment`, `Delivery`

### Filnamngivning
```
YYYY_MM_DD_HHMMSS_beskrivning.sql
```
Exempel: `2026_01_14_120000_create_events_rfid_events.sql`

### Format
```sql
-- Migration: YYYY_MM_DD_HHMMSS_beskrivning.sql
-- Description: Kort beskrivning av vad migrationen gör
-- Created: YYYY-MM-DD HH:MM:SS

-- ==================================================
-- UP: Kör denna SQL för att applicera migrationen
-- ==================================================

CREATE TABLE exempel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    -- kolumner här
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================
-- DOWN: Kör denna SQL för att rulla tillbaka
-- ==================================================

-- DROP TABLE exempel;
```

---

## PHP-sidor

### Sidstruktur (partner-sidor)

Alla sidor följer samma grundstruktur:

```php
<?php
/**
 * Partner Portal - [Sidnamn]
 */

require_once __DIR__ . '/../includes/config.php';

Session::start();

// 1. Hantera språkbyte
if (isset($_GET['set_lang'])) {
    Language::getInstance()->setLanguage($_GET['set_lang']);
    // ... redirect
}

// 2. Kräv inloggning
if (!Session::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// 3. System admin redirectas till admin
if (Session::isSystemAdmin()) {
    header('Location: ../admin/index.php');
    exit;
}

// 4. Kräv org_admin roll
if (!Session::isOrgAdmin()) {
    Session::flash('error', t('error.unauthorized'));
    header('Location: login.php');
    exit;
}

// 5. Hämta grunddata
$userData = Session::getUserData();
$organizationId = Session::getOrganizationId();
$db = Database::getInstance()->getPdo();

// 6. Hantera POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        // fel
    } else {
        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'create': // ...
            case 'update': // ...
            case 'delete': // ...
        }
    }
}

// 7. Hämta data för visning
// ...

// 8. HTML-output
?>
<!DOCTYPE html>
<html lang="<?= Language::getInstance()->getLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <meta name="csrf-token" content="<?= Session::generateCsrfToken() ?>">
    <link rel="stylesheet" href="css/partner.css">
    <link rel="stylesheet" href="../assets/css/modal.css">
    <script src="../assets/js/modal.js"></script>
    <script src="js/sidebar.js" defer></script>
    <script src="js/modals.js" defer></script>
</head>
<body>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main">
        <!-- innehåll -->
    </main>

    <!-- Modal overlay -->
    <div id="modal-overlay" class="hidden">
        <div class="modal-container">
            <div id="modal-content"></div>
        </div>
    </div>
</body>
</html>
```

---

## UI/UX-mönster

### Lista + Modal (CRUD)

**Standard för alla hanteringssidor** (artiklar, leveranser, användare, etc.):

1. **Listavy** - Tabell med alla poster
2. **Skapa** - Modal med formulär (knapp i page-header)
3. **Redigera** - Modal med formulär (ikon-knapp i tabellrad)
4. **Radera** - Bekräftelse-modal (ikon-knapp i tabellrad)
5. **Visa detaljer** - Modal (ikon-knapp i tabellrad)

### Page Header
```html
<div class="page-header">
    <h1><?= t('partner.xxx.heading') ?></h1>
    <div class="page-actions">
        <div class="search-box">
            <input type="text" id="table-search" placeholder="<?= t('common.search') ?>...">
            <button type="button" class="search-clear">&times;</button>
        </div>
        <button type="button" class="btn btn-primary" id="createBtn">
            <?= t('partner.xxx.action.create') ?>
        </button>
    </div>
</div>
```

### Tabellstruktur
```html
<div class="card">
    <table id="xxx-table">
        <thead>
            <tr>
                <th><?= t('partner.xxx.table.column1') ?></th>
                <th><?= t('partner.xxx.table.column2') ?></th>
                <th></th> <!-- Actions kolumn, ingen rubrik -->
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
            <tr>
                <td colspan="X" class="text-muted text-center">
                    <?= t('partner.xxx.list.empty') ?>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($items as $item): ?>
            <tr>
                <td>...</td>
                <td class="actions">
                    <button class="btn btn-icon" data-xxx-view='...' title="<?= t('common.view') ?>">👁️</button>
                    <button class="btn btn-icon" data-xxx-edit='...' title="<?= t('common.edit') ?>">✏️</button>
                    <form method="POST" style="display:inline;" data-confirm="...">
                        <!-- delete form -->
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
```

### Ikon-knappar
| Åtgärd | Emoji | Klass |
|--------|-------|-------|
| Visa | 👁️ | `btn btn-icon` |
| Redigera | ✏️ | `btn btn-icon` |
| Radera | 🗑️ | `btn btn-icon btn-icon-danger` |
| QR-kod | 📱 | `btn btn-icon` |

---

## JavaScript

### Filstruktur (per sida)
```
js/
├── sidebar.js      # Meny-collapse, språkväljare (delas)
├── modals.js       # Modal-hantering (delas)
├── articles.js     # Artikel-specifik logik
├── shipments.js    # Leverans-specifik logik
└── ...
```

### Modal-mönster
```javascript
document.addEventListener('DOMContentLoaded', function() {
    // Create button
    const createBtn = document.getElementById('createXxxBtn');
    if (createBtn) {
        createBtn.addEventListener('click', openCreateModal);
    }

    // Edit buttons
    document.querySelectorAll('[data-xxx-edit]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const data = JSON.parse(this.getAttribute('data-xxx-edit'));
            openEditModal(data);
        });
    });

    // Table search
    initTableSearch('table-search', '#xxx-table');
});

function openCreateModal() {
    const csrfField = document.querySelector('meta[name="csrf-token"]').content;
    const labels = JSON.parse(document.querySelector('meta[name="xxx-labels"]').content);

    const content = `
        <form method="POST" id="xxxForm">
            <input type="hidden" name="csrf_token" value="${csrfField}">
            <input type="hidden" name="action" value="create">
            <!-- formulärfält -->
        </form>
    `;

    Modal.custom('info', labels.modal_create, content, {
        html: true,
        width: '500px',
        buttons: [
            { text: labels.cancel, class: 'cancel', value: false },
            { text: labels.create, class: 'primary', value: 'submit' }
        ]
    });

    // Bind submit
    setTimeout(() => {
        const primaryBtn = document.querySelector('.modal-footer .modal-btn.primary');
        if (primaryBtn) {
            const newBtn = primaryBtn.cloneNode(true);
            primaryBtn.parentNode.replaceChild(newBtn, primaryBtn);
            newBtn.addEventListener('click', () => {
                const form = document.getElementById('xxxForm');
                if (form && form.checkValidity()) {
                    form.submit();
                } else if (form) {
                    form.reportValidity();
                }
            });
        }
    }, 100);
}
```

### Meta-taggar för data
```html
<meta name="xxx-labels" content='<?= json_encode([...]) ?>'>
<meta name="xxx-data" content='<?= htmlspecialchars(json_encode([...]), ENT_QUOTES) ?>'>
```

---

## Översättningar

### Fil
`config/translations.php`

### Namnkonvention
```
[område].[sida].[sektion].[nyckel]
```

Exempel:
- `partner.articles.form.sku` - Formulärfält
- `partner.articles.table.status` - Tabellrubrik
- `partner.articles.action.create` - Knapptext
- `partner.articles.message.created` - Bekräftelsemeddelande
- `partner.articles.error.sku_exists` - Felmeddelande
- `partner.articles.modal.create.title` - Modal-titel
- `partner.articles.list.empty` - Text för tom lista

### Gemensamma
- `common.*` - Återanvändbara ord (Spara, Avbryt, Radera, etc.)
- `error.*` - Generella fel
- `field.*` - Generella formulärfält

### Format
```php
'nyckel' => ['sv' => 'Svenska', 'en' => 'English'],
```

### Placeholders
```php
'partner.xxx.message.created' => [
    'sv' => 'Posten {id} har skapats',
    'en' => 'Record {id} has been created'
],
```

---

## CSS

### Filer
- `public_html/partner/css/partner.css` - Partner Portal (ljust tema)
- `public_html/admin/css/admin.css` - Admin Panel (mörkt tema)
- `public_html/assets/css/modal.css` - Delad modal-styling

### Klasser

#### Layout
- `.sidebar` - Vänster navigation
- `.main` - Huvudinnehåll (margin-left: 250px)
- `.page-header` - Rubrik + actions
- `.page-actions` - Knappar i header

#### Komponenter
- `.card` - Vit box med padding och border
- `.stats` - Grid med statistik-kort
- `.stat-card` - Enskilt statistik-kort

#### Tabeller
- `.actions` - Kolumn för knappar
- `.row-inactive` - Grå rad för inaktiva poster
- `.truncate` - Klipp lång text

#### Knappar
- `.btn` - Standardknapp
- `.btn-primary` - Primär (blå)
- `.btn-secondary` - Sekundär (grå)
- `.btn-danger` - Varning (röd)
- `.btn-small` - Mindre storlek
- `.btn-icon` - Ikon-knapp (32x32)
- `.btn-icon-danger` - Röd hover

#### Formulär
- `.form-group` - Wrapper för label + input
- `.form-row` - Grid-rad för flera fält
- `.checkbox-group` - Checkbox med label

#### Meddelanden
- `.message` - Bas för meddelanden
- `.message.success` - Grön
- `.message.error` - Röd

#### Badges
- `.badge` - Liten etikett
- `.badge-new`, `.badge-used`, `.badge-inactive`
- `.badge-success`, `.badge-danger`

---

## Checklista för ny sida

1. [ ] Skapa PHP-fil med standardstruktur
2. [ ] Skapa JS-fil för modal-hantering
3. [ ] Lägg till översättningar i translations.php
4. [ ] Lägg till länk i sidebar.php
5. [ ] Testa CRUD-flöde
6. [ ] Testa på svenska och engelska
