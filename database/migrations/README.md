# Migrations Guide

## Snabbstart

### 1. Skapa ny migration

```bash
# Automatiskt (genererar fil med timestamp)
php database/migrate.php create beskrivning_av_ändring

# Exempel:
php database/migrate.php create create_posts_table
php database/migrate.php create add_phone_to_users
php database/migrate.php create add_index_to_email
```

### 2. Redigera migration-filen

Öppna den genererade filen i `database/migrations/` och fyll i SQL:
- **UP-sektion**: SQL för att applicera ändringen
- **DOWN-sektion**: SQL för att återställa ändringen

### 3. Testa lokalt

```bash
# Kör migration
php database/migrate.php

# Kontrollera att det fungerade
php database/migrate.php status

# Om något gick fel, rulla tillbaka
php database/migrate.php rollback
```

### 4. Committa och pusha

```bash
git add database/migrations/YYYY_MM_DD_HHMMSS_beskrivning.sql
git commit -m "Lägg till migration: beskrivning"
git push
```

### 5. Rulla ut på produktion

**Alternativ A - Via webb (enklast):**
1. Gå till `https://admin.petersjostedt.se/migrations.php`
2. Klicka "Kör migrations"

**Alternativ B - Via SSH:**
```bash
ssh user@petersjostedt.se
cd /path/to/project
git pull
php database/migrate.php
```

---

## Vanliga exempel

### Skapa ny tabell

```sql
-- UP
CREATE TABLE posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- DOWN
DROP TABLE posts;
```

### Lägg till kolumn

```sql
-- UP
ALTER TABLE users ADD COLUMN phone VARCHAR(20);

-- DOWN
ALTER TABLE users DROP COLUMN phone;
```

### Lägg till flera kolumner

```sql
-- UP
ALTER TABLE users
ADD COLUMN bio TEXT,
ADD COLUMN avatar VARCHAR(255),
ADD COLUMN website VARCHAR(255);

-- DOWN
ALTER TABLE users
DROP COLUMN bio,
DROP COLUMN avatar,
DROP COLUMN website;
```

### Lägg till index

```sql
-- UP
CREATE INDEX idx_email ON users(email);
CREATE INDEX idx_created_at ON posts(created_at);

-- DOWN
DROP INDEX idx_email ON users;
DROP INDEX idx_created_at ON posts;
```

### Ändra kolumntyp

```sql
-- UP
ALTER TABLE users MODIFY bio TEXT;

-- DOWN
ALTER TABLE users MODIFY bio VARCHAR(255);
```

### Lägg till foreign key

```sql
-- UP
ALTER TABLE posts
ADD CONSTRAINT fk_posts_user
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- DOWN
ALTER TABLE posts DROP FOREIGN KEY fk_posts_user;
```

### Lägg till UNIQUE constraint

```sql
-- UP
ALTER TABLE users ADD UNIQUE KEY unique_email (email);

-- DOWN
ALTER TABLE users DROP INDEX unique_email;
```

---

## Best Practices

### ✅ DO

- En migration = en logisk ändring
- Testa alltid lokalt först
- Skriv alltid DOWN-sektion (för rollback)
- Använd tydliga beskrivande namn
- Committa migrations tillsammans med kod som använder dem
- Kör migrations i rätt ordning (via systemet)

### ❌ DON'T

- Ändra aldrig en migration efter att den körts på produktion
- Kör aldrig SQL direkt i phpMyAdmin/MySQL
- Skippa inte DOWN-sektionen
- Blanda inte orelaterade ändringar i samma migration
- Ta inte bort gamla migrations (historik!)

---

## Felsökning

### "Migration misslyckades"

1. Kolla felmeddelandet i admin-panelen
2. Kontrollera SQL-syntaxen i migration-filen
3. Testa SQL direkt i MySQL för att isolera problemet
4. Fixa felet i migration-filen
5. Rulla tillbaka: `php database/migrate.php rollback`
6. Kör igen: `php database/migrate.php`

### "Tabellen finns redan"

Om du kör en migration som försöker skapa en tabell som redan finns:
- Använd `CREATE TABLE IF NOT EXISTS` i UP-sektionen
- ELLER: Markera migrationen som körd manuellt i `migrations`-tabellen

### "Foreign key constraint fails"

Kontrollera att:
- Refererad tabell finns (kör migrations i rätt ordning)
- Refererad kolumn existerar
- Datatyper matchar exakt
- Tabell-engine är InnoDB (inte MyISAM)

---

## Kommandon

```bash
# Kör alla pending migrations
php database/migrate.php

# Visa status
php database/migrate.php status

# Rulla tillbaka senaste batch
php database/migrate.php rollback

# Återställ ALLA migrations (FARLIGT!)
php database/migrate.php reset

# Skapa ny migration
php database/migrate.php create beskrivning

# Hjälp
php database/migrate.php help
```

---

## Filstruktur

```
database/
├── migrations/
│   ├── 2026_01_11_090800_initial_schema.sql      ← Initial setup
│   ├── 2026_01_15_143000_create_posts_table.sql  ← Din första
│   ├── 2026_01_16_091500_add_phone_to_users.sql  ← Din andra
│   ├── TEMPLATE.sql                               ← Mall att kopiera
│   └── README.md                                  ← Denna fil
├── migrate.php                                    ← CLI-kommando
└── schema.sql                                     ← Referens (ej användas)
```

---

## Namnkonvention

Använd beskrivande namn i imperativ form:

**Bra:**
- `create_posts_table`
- `add_phone_to_users`
- `add_index_to_email`
- `remove_legacy_columns`

**Dåligt:**
- `migration1`
- `update`
- `fix`
- `new_stuff`
