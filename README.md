# Peter Sjöstedt - Webbplats

Säker och modern PHP-webbplats med admin-panel, flerspråksstöd och omfattande säkerhetsfunktioner.

## 📋 Innehållsförteckning

- [Översikt](#översikt)
- [Funktioner](#funktioner)
- [Krav](#krav)
- [Installation](#installation)
- [Konfiguration](#konfiguration)
- [Säkerhet](#säkerhet)
- [Cron-jobb](#cron-jobb)
- [Struktur](#struktur)
- [Underhåll](#underhåll)

## 🎯 Översikt

Detta projekt är en säkerhetsfokuserad PHP-webbapplikation med:
- **Publik webbplats** (`petersjostedt.se`)
- **Admin-panel** (`admin.petersjostedt.se`)
- **Flerspråksstöd** (Svenska/Engelska)
- **Omfattande säkerhet** (CSP, CSRF, XSS-skydd, rate limiting)
- **Automatiska backuper** (daglig/veckovis/månatlig rotation)
- **Avancerad loggning** (databas + fil, rotation, komprimering)

## ✨ Funktioner

### Säkerhet
- ✅ Content Security Policy (CSP) - ingen inline JavaScript
- ✅ CSRF-skydd på alla formulär
- ✅ XSS-skydd via htmlspecialchars()
- ✅ Prepared statements mot SQL-injection
- ✅ Rate limiting mot brute force
- ✅ Session-säkerhet (database-backed, fingerprinting)
- ✅ Lösenordshashing med PASSWORD_ARGON2ID

### Admin-panel
- ✅ Användarhantering (skapa, redigera, radera)
- ✅ Loggviewer med filtrering och sökning
- ✅ Session-hantering (se aktiva sessioner, logga ut användare)
- ✅ Systeminställningar
- ✅ Databasbackup med restore-funktion
- ✅ Flerspråkigt gränssnitt

### Loggning
- ✅ Dual logging (databas + fil)
- ✅ 5 loggningsnivåer (DEBUG, INFO, WARNING, ERROR, SECURITY)
- ✅ Automatisk rotation och komprimering
- ✅ Separata loggfiler per typ
- ✅ 90 dagars retention i databas

### Backup
- ✅ Automatiska dagliga backuper via cron
- ✅ Tre nivåer: daglig (7 dagar), veckovis (4 veckor), månatlig (12 månader)
- ✅ Gzip-komprimering (80-90% storleksreduktion)
- ✅ Backup-verifiering
- ✅ Återställning via admin-panel

## 🔧 Krav

### Server
- **PHP**: 8.1 eller högre
- **MySQL/MariaDB**: 5.7+ / 10.3+
- **Apache/Nginx**: med mod_rewrite
- **Diskutrymme**: Minst 500MB (mer för backuper)

### PHP-extensions
- `pdo_mysql` - Databasanslutning
- `mbstring` - Stränghantering
- `gzip` - Komprimering av backuper
- `curl` - HTTP-förfrågningar (för e-post)

### Verktyg
- `mysqldump` - För databasbackuper
- `mysql` - För återställning av backuper
- `cron` - För automatiska jobb

## 📦 Installation

### 1. Ladda upp filer

```bash
# Via FTP/SFTP eller Git
git clone <repository-url> /path/to/project
```

### 2. Skapa databas

```sql
CREATE DATABASE petersjostedt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'dbuser'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON petersjostedt.* TO 'dbuser'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Importera databasschema

```bash
mysql -u dbuser -p petersjostedt < database/schema.sql
```

### 4. Konfigurera webbserver

#### Apache Virtual Hosts

**Publik sida** (`petersjostedt.se`):
```apache
<VirtualHost *:80>
    ServerName petersjostedt.se
    ServerAlias www.petersjostedt.se
    DocumentRoot /path/to/project/public_html

    <Directory /path/to/project/public_html>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/petersjostedt-error.log
    CustomLog ${APACHE_LOG_DIR}/petersjostedt-access.log combined
</VirtualHost>
```

**Admin-panel** (`admin.petersjostedt.se`):
```apache
<VirtualHost *:80>
    ServerName admin.petersjostedt.se
    DocumentRoot /path/to/project/admin.petersjostedt.se

    <Directory /path/to/project/admin.petersjostedt.se>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/admin-petersjostedt-error.log
    CustomLog ${APACHE_LOG_DIR}/admin-petersjostedt-access.log combined
</VirtualHost>
```

#### SSL-certifikat (Rekommenderat)

```bash
# Installera Certbot
sudo apt-get install certbot python3-certbot-apache

# Skaffa certifikat
sudo certbot --apache -d petersjostedt.se -d www.petersjostedt.se
sudo certbot --apache -d admin.petersjostedt.se
```

### 5. Sätt filrättigheter

```bash
cd /path/to/project

# Ge skrivbehörighet till nödvändiga mappar
chmod 755 logs backups sessions
chmod 644 logs/.htaccess backups/.htaccess

# Skydda känsliga filer
chmod 600 config/database.php config/app.php config/mail.php
```

## ⚙️ Konfiguration

### 1. Databaskonfiguration

Kopiera exempel och redigera:
```bash
cp config/database.example.php config/database.php
nano config/database.php
```

```php
<?php
return [
    'host' => 'localhost',
    'name' => 'petersjostedt',
    'user' => 'dbuser',
    'pass' => 'strong_password',
    'charset' => 'utf8mb4'
];
```

### 2. Applikationsinställningar

```bash
cp config/app.example.php config/app.php
nano config/app.php
```

```php
<?php
return [
    'name' => 'Peter Sjöstedt',
    'url' => 'https://petersjostedt.se',
    'environment' => 'production', // 'development' eller 'production'
    'timezone' => 'Europe/Stockholm'
];
```

### 3. E-postkonfiguration

```bash
cp config/mail.example.php config/mail.php
nano config/mail.php
```

```php
<?php
return [
    'smtp_host' => 'mail.petersjostedt.se',
    'smtp_port' => 587,
    'smtp_secure' => 'tls', // 'tls' eller 'ssl'
    'smtp_user' => 'noreply@petersjostedt.se',
    'smtp_password' => 'email_password',
    'from_email' => 'noreply@petersjostedt.se',
    'from_name' => 'Peter Sjöstedt'
];
```

### 4. Skapa admin-användare

```bash
php public_html/setup-admin.php
```

Följ instruktionerna och ta bort filen efteråt:
```bash
rm public_html/setup-admin.php
```

## 🔒 Säkerhet

### Kritiska filer att ALDRIG committa

Dessa filer är exkluderade i `.gitignore`:
- `config/database.php` - Databasuppgifter
- `config/app.php` - Applikationskonfiguration
- `config/mail.php` - E-postuppgifter
- `.env` - Miljövariabler
- `logs/*.log` - Loggfiler
- `backups/*.sql.gz` - Databasbackuper
- `sessions/*` - Sessionsfiler

### Säkerhetskontroller

1. **Verifiera .htaccess-filer**:
   ```bash
   # Dessa mappar ska INTE vara åtkomliga via webben
   curl https://petersjostedt.se/logs/ # Ska ge 403 Forbidden
   curl https://petersjostedt.se/backups/ # Ska ge 403 Forbidden
   curl https://petersjostedt.se/config/ # Ska ge 403 Forbidden
   ```

2. **Kontrollera CSP-headers**:
   ```bash
   curl -I https://petersjostedt.se | grep -i content-security-policy
   ```

3. **Testa rate limiting**:
   - Försök logga in med fel lösenord 5 gånger
   - Ska ge "För många försök"-meddelande

## ⏰ Cron-jobb

Lägg till dessa i crontab (`crontab -e`):

```cron
# Databasbackup - kl 03:00 varje natt
0 3 * * * cd /path/to/project && php cron/backup-database.php >> /path/to/project/logs/cron-backup.log 2>&1

# Loggrotation - kl 02:00 varje natt
0 2 * * * cd /path/to/project && php cron/rotate-logs.php >> /path/to/project/logs/cron-rotate.log 2>&1
```

### Verifiera cron-jobb

```bash
# Testa manuellt
php /path/to/project/cron/backup-database.php
php /path/to/project/cron/rotate-logs.php

# Kontrollera loggfiler
tail -f /path/to/project/logs/cron-backup.log
tail -f /path/to/project/logs/cron-rotate.log
```

## 📁 Struktur

```
petersjostedt/
├── admin.petersjostedt.se/     # Admin-panel
│   ├── backup.php              # Backup-hantering
│   ├── index.php               # Dashboard
│   ├── login.php               # Admin-login
│   ├── logout.php              # Logout
│   ├── logs.php                # Loggviewer
│   ├── sessions.php            # Session-hantering
│   ├── settings.php            # Systeminställningar
│   ├── users.php               # Användarhantering
│   ├── css/                    # Admin-stilar
│   ├── js/                     # Admin-JavaScript
│   └── includes/               # Admin-komponenter
│       └── sidebar.php         # Admin-meny
├── public_html/                # Publik webbplats
│   ├── index.php               # Startsida
│   ├── test-logging.php        # Test loggning
│   ├── test-backup.php         # Test backup
│   ├── assets/                 # CSS, JS, bilder
│   └── includes/               # Komponenter
│       ├── config.php          # Huvudkonfiguration
│       └── security.php        # Säkerhetsfunktioner
├── src/                        # Kärnklasser
│   ├── Backup.php              # Backup-hantering
│   ├── Database.php            # Databasanslutning
│   ├── Language.php            # Språkhantering
│   ├── Logger.php              # Loggning
│   ├── Mailer.php              # E-post
│   ├── Session.php             # Session-hantering
│   ├── Settings.php            # Inställningar
│   └── User.php                # Användarhantering
├── config/                     # Konfigurationsfiler (GIT-IGNORERADE)
│   ├── app.php                 # Applikationsinställningar
│   ├── database.php            # Databasuppgifter
│   ├── mail.php                # E-postinställningar
│   ├── languages.php           # Språkdefinitioner
│   └── translations.php        # Översättningar
├── cron/                       # Cron-jobb
│   ├── backup-database.php     # Automatisk backup
│   └── rotate-logs.php         # Loggrotation
├── logs/                       # Loggfiler (GIT-IGNORERADE)
│   ├── .htaccess               # Blockera HTTP-åtkomst
│   ├── app-YYYY-MM-DD.log      # Applikationsloggar
│   ├── error-YYYY-MM-DD.log    # Fellogar
│   ├── security-YYYY-MM-DD.log # Säkerhetsloggar
│   └── debug-YYYY-MM-DD.log    # Debug-loggar
├── backups/                    # Databasbackuper (GIT-IGNORERADE)
│   ├── .htaccess               # Blockera HTTP-åtkomst
│   ├── daily/                  # Dagliga backuper (7 dagar)
│   ├── weekly/                 # Veckovisa backuper (4 veckor)
│   └── monthly/                # Månatliga backuper (12 månader)
├── sessions/                   # Sessionsfiler (GIT-IGNORERADE)
├── errors/                     # Felsidor
│   ├── 404.php                 # Sidan hittades inte
│   └── 500.php                 # Serverfel
├── .gitignore                  # Git-ignorerade filer
└── README.md                   # Denna fil
```

## 🔧 Underhåll

### Dagliga uppgifter (automatiska)
- ✅ Databasbackup (03:00)
- ✅ Loggrotation (02:00)

### Veckovisa kontroller
- 📊 Granska säkerhetsloggar i admin-panelen
- 💾 Verifiera att backuper skapas korrekt
- 🔍 Kontrollera diskutrymme

### Månatliga uppgifter
- 📦 Ladda ner backup off-site (rekommenderat)
- 🔄 Uppdatera PHP-beroenden om nödvändigt
- 🔐 Granska användarkonton och sessioner

### Felsökning

#### Loggfiler hittas inte
```bash
# Kontrollera att logs-mappen finns och har rätt rättigheter
ls -la logs/
chmod 755 logs/
```

#### Backup misslyckas
```bash
# Verifiera att mysqldump finns
which mysqldump

# Testa backup manuellt
php cron/backup-database.php
```

#### Sessioner fungerar inte
```bash
# Kontrollera sessions-mappen
ls -la sessions/
chmod 755 sessions/
```

#### E-post skickas inte
```bash
# Testa e-postkonfiguration
php public_html/test-mail.php
```

### Uppdatera från Git

```bash
cd /path/to/project

# Backup först!
php cron/backup-database.php

# Hämta uppdateringar
git pull origin main

# Kör eventuella databas-migreringar
# (om sådana finns)

# Rensa cache om nödvändigt
rm -rf sessions/*
```

## 📝 Licens

Privat projekt - Alla rättigheter förbehållna.

## 👤 Författare

Peter Sjöstedt

---

**Senast uppdaterad**: 2026-01-11
