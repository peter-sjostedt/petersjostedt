<?php
/**
 * Översättningar - Organiserad struktur
 *
 * Format: 'nyckel' => ['sv' => 'Svenska', 'en' => 'English']
 * Placeholders: {name}, {count} osv. ersätts vid anrop
 */

return [
    // === COMMON - Återanvändbara ord ===
    'common.save' => ['sv' => 'Spara', 'en' => 'Save'],
    'common.cancel' => ['sv' => 'Avbryt', 'en' => 'Cancel'],
    'common.close' => ['sv' => 'Stäng', 'en' => 'Close'],
    'common.delete' => ['sv' => 'Radera', 'en' => 'Delete'],
    'common.edit' => ['sv' => 'Redigera', 'en' => 'Edit'],
    'common.add' => ['sv' => 'Lägg till', 'en' => 'Add'],
    'common.create' => ['sv' => 'Skapa', 'en' => 'Create'],
    'common.update' => ['sv' => 'Uppdatera', 'en' => 'Update'],
    'common.created' => ['sv' => 'Skapad', 'en' => 'Created'],
    'common.search' => ['sv' => 'Sök', 'en' => 'Search'],
    'common.loading' => ['sv' => 'Laddar...', 'en' => 'Loading...'],
    'common.please_wait' => ['sv' => 'Vänligen vänta...', 'en' => 'Please wait...'],
    'common.actions' => ['sv' => 'Åtgärder', 'en' => 'Actions'],
    'common.unknown' => ['sv' => 'Okänd', 'en' => 'Unknown'],
    'common.yes' => ['sv' => 'Ja', 'en' => 'Yes'],
    'common.no' => ['sv' => 'Nej', 'en' => 'No'],
    'common.ok' => ['sv' => 'OK', 'en' => 'OK'],
    'common.login' => ['sv' => 'Logga in', 'en' => 'Log in'],
    'common.logout' => ['sv' => 'Logga ut', 'en' => 'Log out'],
    'common.welcome' => ['sv' => 'Välkommen', 'en' => 'Welcome'],
    'common.welcome_user' => ['sv' => 'Välkommen {name}!', 'en' => 'Welcome {name}!'],

    // === FIELD - Formulärfält ===
    'field.email' => ['sv' => 'E-post', 'en' => 'Email'],
    'field.password' => ['sv' => 'Lösenord', 'en' => 'Password'],
    'field.password_min_8' => ['sv' => 'Lösenord (minst 8 tecken)', 'en' => 'Password (min 8 characters)'],
    'field.new_password_min_8' => ['sv' => 'Nytt lösenord (minst 8 tecken)', 'en' => 'New password (min 8 characters)'],
    'field.name' => ['sv' => 'Namn', 'en' => 'Name'],
    'field.role' => ['sv' => 'Roll', 'en' => 'Role'],

    // === ERROR - Felmeddelanden ===
    'error.generic' => ['sv' => 'Ett fel uppstod', 'en' => 'An error occurred'],
    'error.not_found' => ['sv' => 'Sidan hittades inte', 'en' => 'Page not found'],
    'error.unauthorized' => ['sv' => 'Ingen behörighet', 'en' => 'Unauthorized'],
    'error.invalid_request' => ['sv' => 'Ogiltig förfrågan. Försök igen.', 'en' => 'Invalid request. Please try again.'],
    'error.too_many_attempts' => ['sv' => 'För många misslyckade försök. Vänta 15 minuter.', 'en' => 'Too many failed attempts. Wait 15 minutes.'],
    'error.invalid_credentials_or_no_admin' => ['sv' => 'Felaktiga inloggningsuppgifter eller saknar admin-behörighet.', 'en' => 'Invalid credentials or missing admin privileges.'],
    'error.all_fields_required' => ['sv' => 'Alla fält måste fyllas i.', 'en' => 'All fields are required.'],
    'error.password_min_length' => ['sv' => 'Lösenordet måste vara minst 8 tecken.', 'en' => 'Password must be at least 8 characters.'],

    // === CONFIRM - Bekräftelsedialoger ===
    'confirm.are_you_sure' => ['sv' => 'Är du säker?', 'en' => 'Are you sure?'],
    'confirm.delete_item' => ['sv' => 'Vill du verkligen radera detta?', 'en' => 'Do you really want to delete this?'],

    // === PAGINATION ===
    'pagination.previous' => ['sv' => 'Föregående', 'en' => 'Previous'],
    'pagination.next' => ['sv' => 'Nästa', 'en' => 'Next'],

    // === FILTER ===
    'filter.all' => ['sv' => 'Alla', 'en' => 'All'],
    'filter.security' => ['sv' => 'Säkerhet', 'en' => 'Security'],
    'filter.logins' => ['sv' => 'Inloggningar', 'en' => 'Logins'],
    'filter.errors' => ['sv' => 'Fel', 'en' => 'Errors'],

    // === ADMIN - Navigation ===
    'admin.nav.dashboard' => ['sv' => 'Dashboard', 'en' => 'Dashboard'],
    'admin.nav.users' => ['sv' => 'Användare', 'en' => 'Users'],
    'admin.nav.settings' => ['sv' => 'Inställningar', 'en' => 'Settings'],
    'admin.nav.logs' => ['sv' => 'Loggar', 'en' => 'Logs'],
    'admin.nav.sessions' => ['sv' => 'Sessioner', 'en' => 'Sessions'],
    'admin.nav.view_site' => ['sv' => 'Visa sidan', 'en' => 'View site'],

    // === ADMIN - Titles ===
    'admin.title.prefix' => ['sv' => 'Admin', 'en' => 'Admin'],
    'admin.title.panel' => ['sv' => 'Admin Panel', 'en' => 'Admin Panel'],
    'admin.title.login' => ['sv' => 'Admin Login', 'en' => 'Admin Login'],
    'admin.title.back_to_site' => ['sv' => 'Tillbaka till sidan', 'en' => 'Back to site'],

    // === ADMIN - Dashboard ===
    'admin.dashboard.logged_in_as' => ['sv' => 'Inloggad som:', 'en' => 'Logged in as:'],
    'admin.dashboard.admin' => ['sv' => 'Admin', 'en' => 'Admin'],
    'admin.dashboard.events_24h' => ['sv' => 'Händelser (24h)', 'en' => 'Events (24h)'],
    'admin.dashboard.active_users_24h' => ['sv' => 'Aktiva användare (24h)', 'en' => 'Active users (24h)'],
    'admin.dashboard.failed_logins_24h' => ['sv' => 'Misslyckade inloggningar (24h)', 'en' => 'Failed logins (24h)'],
    'admin.dashboard.recent_activity' => ['sv' => 'Senaste aktivitet', 'en' => 'Recent activity'],

    // === ADMIN - Users ===
    'admin.users.created' => ['sv' => 'Användare skapad!', 'en' => 'User created!'],
    'admin.users.deleted' => ['sv' => 'Användare borttagen!', 'en' => 'User deleted!'],
    'admin.users.updated' => ['sv' => 'Användare uppdaterad!', 'en' => 'User updated!'],
    'admin.users.create_new' => ['sv' => 'Skapa ny användare', 'en' => 'Create new user'],
    'admin.users.edit' => ['sv' => 'Redigera användare', 'en' => 'Edit user'],
    'admin.users.change_password' => ['sv' => 'Byt lösenord', 'en' => 'Change password'],
    'admin.users.all_users' => ['sv' => 'Alla användare ({count})', 'en' => 'All users ({count})'],
    'admin.users.cannot_delete_self' => ['sv' => 'Du kan inte ta bort dig själv.', 'en' => 'You cannot delete yourself.'],
    'admin.users.create_failed' => ['sv' => 'Kunde inte skapa användare. E-posten kanske redan finns.', 'en' => 'Could not create user. Email may already exist.'],
    'admin.users.update_failed' => ['sv' => 'Kunde inte uppdatera användare.', 'en' => 'Could not update user.'],
    'admin.users.delete_failed' => ['sv' => 'Kunde inte ta bort användare.', 'en' => 'Could not delete user.'],
    'admin.users.password_updated' => ['sv' => 'Lösenord uppdaterat!', 'en' => 'Password updated!'],
    'admin.users.password_update_failed' => ['sv' => 'Kunde inte uppdatera lösenord.', 'en' => 'Could not update password.'],
    'admin.users.role_user' => ['sv' => 'Användare', 'en' => 'User'],
    'admin.users.role_admin' => ['sv' => 'Admin', 'en' => 'Admin'],

    // === ADMIN - Settings ===
    'admin.settings.saved' => ['sv' => 'Inställningar sparade!', 'en' => 'Settings saved!'],
    'admin.settings.save_failed' => ['sv' => 'Kunde inte spara inställningar.', 'en' => 'Could not save settings.'],
    'admin.settings.save' => ['sv' => 'Spara inställningar', 'en' => 'Save settings'],
    'admin.settings.system_settings' => ['sv' => 'Systeminställningar', 'en' => 'System settings'],
    'admin.settings.general' => ['sv' => 'Allmänt', 'en' => 'General'],
    'admin.settings.security' => ['sv' => 'Säkerhet', 'en' => 'Security'],
    'admin.settings.site_name' => ['sv' => 'Sidans namn', 'en' => 'Site name'],
    'admin.settings.description' => ['sv' => 'Beskrivning', 'en' => 'Description'],
    'admin.settings.contact_email' => ['sv' => 'Kontakt-epost', 'en' => 'Contact email'],
    'admin.settings.maintenance_mode' => ['sv' => 'Underhållsläge', 'en' => 'Maintenance mode'],
    'admin.settings.maintenance_mode_desc' => ['sv' => 'Underhållsläge (stänger sidan för besökare)', 'en' => 'Maintenance mode (closes site for visitors)'],
    'admin.settings.allow_registration' => ['sv' => 'Tillåt registrering', 'en' => 'Allow registration'],
    'admin.settings.allow_registration_desc' => ['sv' => 'Tillåt registrering av nya användare', 'en' => 'Allow registration of new users'],
    'admin.settings.items_per_page' => ['sv' => 'Poster per sida', 'en' => 'Items per page'],
    'admin.settings.session_lifetime' => ['sv' => 'Session-livstid (sekunder)', 'en' => 'Session lifetime (seconds)'],
    'admin.settings.session_lifetime_help' => ['sv' => '86400 = 24 timmar, 3600 = 1 timme', 'en' => '86400 = 24 hours, 3600 = 1 hour'],
    'admin.settings.max_login_attempts' => ['sv' => 'Max inloggningsförsök', 'en' => 'Max login attempts'],
    'admin.settings.max_login_attempts_help' => ['sv' => 'Antal misslyckade försök innan IP blockeras', 'en' => 'Number of failed attempts before IP is blocked'],
    'admin.settings.lockout_time' => ['sv' => 'Spärrtid (minuter)', 'en' => 'Lockout time (minutes)'],
    'admin.settings.lockout_time_help' => ['sv' => 'Hur länge en IP är blockerad efter för många försök', 'en' => 'How long an IP is blocked after too many attempts'],
    'admin.settings.system_info' => ['sv' => 'Systeminformation', 'en' => 'System information'],
    'admin.settings.php_version' => ['sv' => 'PHP-version', 'en' => 'PHP version'],
    'admin.settings.server' => ['sv' => 'Server', 'en' => 'Server'],
    'admin.settings.environment' => ['sv' => 'Miljö', 'en' => 'Environment'],
    'admin.settings.database' => ['sv' => 'Databas', 'en' => 'Database'],
    'admin.settings.timezone' => ['sv' => 'Tidzon', 'en' => 'Timezone'],

    // === ADMIN - Sessions ===
    'admin.sessions.active' => ['sv' => 'Aktiva sessioner', 'en' => 'Active sessions'],
    'admin.sessions.unique_users' => ['sv' => 'Unika användare', 'en' => 'Unique users'],
    'admin.sessions.unique_ips' => ['sv' => 'Unika IP-adresser', 'en' => 'Unique IP addresses'],
    'admin.sessions.expired_to_clean' => ['sv' => 'Utgångna (att rensa)', 'en' => 'Expired (to clean)'],
    'admin.sessions.clean_expired' => ['sv' => 'Rensa {count} utgångna sessioner', 'en' => 'Clean {count} expired sessions'],
    'admin.sessions.expires' => ['sv' => 'Utgår', 'en' => 'Expires'],
    'admin.sessions.no_active' => ['sv' => 'Inga aktiva sessioner', 'en' => 'No active sessions'],
    'admin.sessions.your_session' => ['sv' => 'Din session', 'en' => 'Your session'],
    'admin.sessions.hours_left' => ['sv' => '{hours}h kvar', 'en' => '{hours}h left'],
    'admin.sessions.confirm_terminate' => ['sv' => 'Avsluta denna session?', 'en' => 'Terminate this session?'],
    'admin.sessions.terminate' => ['sv' => 'Avsluta', 'en' => 'Terminate'],
    'admin.sessions.terminated' => ['sv' => 'Session avslutad!', 'en' => 'Session terminated!'],
    'admin.sessions.terminated_count' => ['sv' => '{count} sessioner avslutade!', 'en' => '{count} sessions terminated!'],
    'admin.sessions.expired_cleaned' => ['sv' => '{count} utgångna sessioner rensade!', 'en' => '{count} expired sessions cleaned!'],

    // === ADMIN - Logs ===
    'admin.logs.total' => ['sv' => 'Totalt', 'en' => 'Total'],
    'admin.logs.last_24h' => ['sv' => 'Senaste 24h', 'en' => 'Last 24h'],
    'admin.logs.failed_logins' => ['sv' => 'Misslyckade inloggningar', 'en' => 'Failed logins'],
    'admin.logs.unique_ips_24h' => ['sv' => 'Unika IP:er (24h)', 'en' => 'Unique IPs (24h)'],
    'admin.logs.no_results' => ['sv' => 'Inga loggar hittades', 'en' => 'No logs found'],
    'admin.logs.time' => ['sv' => 'Tid', 'en' => 'Time'],
    'admin.logs.user' => ['sv' => 'Användare', 'en' => 'User'],
    'admin.logs.event' => ['sv' => 'Händelse', 'en' => 'Event'],
    'admin.logs.ip_address' => ['sv' => 'IP-adress', 'en' => 'IP address'],
    'admin.logs.search_placeholder' => ['sv' => 'Sök...', 'en' => 'Search...'],

    // === ADMIN - Backup ===
    'admin.nav.backup' => ['sv' => 'Backuper', 'en' => 'Backups'],
    'admin.backup.title' => ['sv' => 'Databasbackuper', 'en' => 'Database Backups'],
    'admin.backup.heading' => ['sv' => 'Databasbackuper', 'en' => 'Database Backups'],
    'admin.backup.description' => ['sv' => 'Hantera och återställ databasbackuper', 'en' => 'Manage and restore database backups'],

    // Stats
    'admin.backup.stats.heading' => ['sv' => 'Statistik', 'en' => 'Statistics'],
    'admin.backup.stats.total' => ['sv' => 'Totalt', 'en' => 'Total'],
    'admin.backup.stats.daily' => ['sv' => 'Dagliga', 'en' => 'Daily'],
    'admin.backup.stats.weekly' => ['sv' => 'Veckovisa', 'en' => 'Weekly'],
    'admin.backup.stats.monthly' => ['sv' => 'Månatliga', 'en' => 'Monthly'],
    'admin.backup.stats.total_size' => ['sv' => 'Total storlek', 'en' => 'Total size'],
    'admin.backup.stats.oldest' => ['sv' => 'Äldsta backup', 'en' => 'Oldest backup'],
    'admin.backup.stats.newest' => ['sv' => 'Senaste backup', 'en' => 'Newest backup'],

    // Create
    'admin.backup.create.heading' => ['sv' => 'Skapa ny backup', 'en' => 'Create new backup'],
    'admin.backup.create.type' => ['sv' => 'Typ av backup', 'en' => 'Backup type'],
    'admin.backup.create.button' => ['sv' => 'Skapa backup', 'en' => 'Create backup'],
    'admin.backup.create.confirm' => ['sv' => 'Skapa en ny databasbackup?', 'en' => 'Create a new database backup?'],

    // Types
    'admin.backup.type.daily' => ['sv' => 'Daglig', 'en' => 'Daily'],
    'admin.backup.type.weekly' => ['sv' => 'Veckovis', 'en' => 'Weekly'],
    'admin.backup.type.monthly' => ['sv' => 'Månatlig', 'en' => 'Monthly'],

    // List
    'admin.backup.list.heading' => ['sv' => 'Befintliga backuper', 'en' => 'Existing backups'],
    'admin.backup.list.empty' => ['sv' => 'Inga backuper hittades', 'en' => 'No backups found'],
    'admin.backup.list.filename' => ['sv' => 'Filnamn', 'en' => 'Filename'],
    'admin.backup.list.type' => ['sv' => 'Typ', 'en' => 'Type'],
    'admin.backup.list.size' => ['sv' => 'Storlek', 'en' => 'Size'],
    'admin.backup.list.created' => ['sv' => 'Skapad', 'en' => 'Created'],
    'admin.backup.list.age' => ['sv' => 'Ålder', 'en' => 'Age'],
    'admin.backup.list.actions' => ['sv' => 'Åtgärder', 'en' => 'Actions'],
    'admin.backup.list.days_ago' => ['sv' => '{days} dagar sedan', 'en' => '{days} days ago'],

    // Restore
    'admin.backup.restore.button' => ['sv' => 'Återställ', 'en' => 'Restore'],
    'admin.backup.restore.confirm' => ['sv' => 'VARNING: Detta kommer att ersätta all data i databasen! Är du säker?', 'en' => 'WARNING: This will replace all data in the database! Are you sure?'],

    // Delete
    'admin.backup.delete.button' => ['sv' => 'Radera', 'en' => 'Delete'],
    'admin.backup.delete.confirm' => ['sv' => 'Radera denna backup permanent?', 'en' => 'Delete this backup permanently?'],

    // Rotate
    'admin.backup.rotate.button' => ['sv' => 'Rotera backuper', 'en' => 'Rotate backups'],
    'admin.backup.rotate.confirm' => ['sv' => 'Köra backup-rotation nu?', 'en' => 'Run backup rotation now?'],

    // Success messages
    'admin.backup.success.created' => ['sv' => 'Backup skapad: {filename} ({size})', 'en' => 'Backup created: {filename} ({size})'],
    'admin.backup.success.restored' => ['sv' => 'Databasen har återställts från {filename}', 'en' => 'Database has been restored from {filename}'],
    'admin.backup.success.deleted' => ['sv' => 'Backup raderad: {filename}', 'en' => 'Backup deleted: {filename}'],
    'admin.backup.success.rotated' => ['sv' => 'Rotation utförd: {promoted} befordrade, {deleted} raderade', 'en' => 'Rotation completed: {promoted} promoted, {deleted} deleted'],

    // Error messages
    'admin.backup.error.invalid_type' => ['sv' => 'Ogiltig backup-typ', 'en' => 'Invalid backup type'],
    'admin.backup.error.create_failed' => ['sv' => 'Kunde inte skapa backup: {error}', 'en' => 'Could not create backup: {error}'],
    'admin.backup.error.restore_failed' => ['sv' => 'Kunde inte återställa backup: {error}', 'en' => 'Could not restore backup: {error}'],
    'admin.backup.error.delete_failed' => ['sv' => 'Kunde inte radera backup', 'en' => 'Could not delete backup'],
    'admin.backup.error.no_file_selected' => ['sv' => 'Ingen fil vald', 'en' => 'No file selected'],
    'admin.backup.error.file_not_found' => ['sv' => 'Filen hittades inte', 'en' => 'File not found'],

    // Instructions
    'admin.backup.instructions.heading' => ['sv' => 'Instruktioner', 'en' => 'Instructions'],
    'admin.backup.instructions.cron.heading' => ['sv' => 'Automatiska backuper', 'en' => 'Automatic backups'],
    'admin.backup.instructions.cron.description' => ['sv' => 'Lägg till detta cron-jobb för automatiska dagliga backuper kl 03:00:', 'en' => 'Add this cron job for automatic daily backups at 03:00:'],
    'admin.backup.instructions.types.heading' => ['sv' => 'Backup-typer', 'en' => 'Backup types'],
    'admin.backup.instructions.types.daily' => ['sv' => 'Behålls i 7 dagar', 'en' => 'Kept for 7 days'],
    'admin.backup.instructions.types.weekly' => ['sv' => 'Behålls i 4 veckor', 'en' => 'Kept for 4 weeks'],
    'admin.backup.instructions.types.monthly' => ['sv' => 'Behålls i 12 månader', 'en' => 'Kept for 12 months'],
    'admin.backup.instructions.restore.heading' => ['sv' => 'Återställning', 'en' => 'Restoration'],
    'admin.backup.instructions.restore.warning' => ['sv' => 'VARNING: Återställning ersätter ALL data i databasen. Skapa alltid en backup först innan du återställer!', 'en' => 'WARNING: Restoration replaces ALL data in the database. Always create a backup first before restoring!'],

    // === ERROR - Felsidor ===
    'error.back_home' => ['sv' => 'Tillbaka till startsidan', 'en' => 'Back to homepage'],
    'error.requested_url' => ['sv' => 'Begärd URL', 'en' => 'Requested URL'],
    'error.came_from' => ['sv' => 'Kom från', 'en' => 'Came from'],
    'error.400.title' => ['sv' => '400 - Felaktig begäran', 'en' => '400 - Bad Request'],
    'error.400.heading' => ['sv' => 'Felaktig begäran', 'en' => 'Bad Request'],
    'error.400.message' => ['sv' => 'Servern kunde inte förstå din begäran.', 'en' => 'The server could not understand your request.'],
    'error.401.title' => ['sv' => '401 - Auktorisering krävs', 'en' => '401 - Authorization Required'],
    'error.401.heading' => ['sv' => 'Auktorisering krävs', 'en' => 'Authorization Required'],
    'error.401.message' => ['sv' => 'Du måste logga in för att se den här sidan.', 'en' => 'You must log in to view this page.'],
    'error.403.title' => ['sv' => '403 - Förbjudet', 'en' => '403 - Forbidden'],
    'error.403.heading' => ['sv' => 'Förbjudet', 'en' => 'Forbidden'],
    'error.403.message' => ['sv' => 'Du har inte behörighet att se den här sidan.', 'en' => 'You do not have permission to view this page.'],
    'error.404.title' => ['sv' => '404 - Sidan hittades inte', 'en' => '404 - Page Not Found'],
    'error.404.heading' => ['sv' => 'Sidan hittades inte', 'en' => 'Page Not Found'],
    'error.404.message' => ['sv' => 'Sidan du letar efter finns inte eller har flyttats.', 'en' => 'The page you are looking for does not exist or has been moved.'],
    'error.500.title' => ['sv' => '500 - Internt serverfel', 'en' => '500 - Internal Server Error'],
    'error.500.heading' => ['sv' => 'Internt serverfel', 'en' => 'Internal Server Error'],
    'error.500.message' => ['sv' => 'Något gick fel på servern. Försök igen senare.', 'en' => 'Something went wrong on the server. Please try again later.'],

    // === ADMIN - Migrations ===
    'admin.nav.migrations' => ['sv' => 'Migrations', 'en' => 'Migrations'],
    'admin.migrations.title' => ['sv' => 'Databasmigrations', 'en' => 'Database Migrations'],
    'admin.migrations.heading' => ['sv' => 'Databasmigrations', 'en' => 'Database Migrations'],
    'admin.migrations.description' => ['sv' => 'Hantera databasändringar med migrations-systemet', 'en' => 'Manage database changes with the migration system'],

    // Summary
    'admin.migrations.summary.heading' => ['sv' => 'Sammanfattning', 'en' => 'Summary'],
    'admin.migrations.summary.total' => ['sv' => 'Totalt antal migrations', 'en' => 'Total migrations'],
    'admin.migrations.summary.executed' => ['sv' => 'Körda', 'en' => 'Executed'],
    'admin.migrations.summary.pending' => ['sv' => 'Väntande', 'en' => 'Pending'],

    // Actions
    'admin.migrations.actions.heading' => ['sv' => 'Kör migrations', 'en' => 'Run Migrations'],
    'admin.migrations.actions.pending_info' => ['sv' => 'Det finns {count} väntande migrations som behöver köras.', 'en' => 'There are {count} pending migrations that need to be run.'],
    'admin.migrations.actions.migrate_confirm' => ['sv' => 'Är du säker på att du vill köra alla väntande migrations? Detta kommer att ändra databasens schema.', 'en' => 'Are you sure you want to run all pending migrations? This will modify the database schema.'],
    'admin.migrations.actions.run_button' => ['sv' => 'Kör alla migrations', 'en' => 'Run All Migrations'],

    // Rollback
    'admin.migrations.rollback.heading' => ['sv' => 'Återställ migrations', 'en' => 'Rollback Migrations'],
    'admin.migrations.rollback.warning' => ['sv' => 'VARNING: Detta återställer den senaste batchen av migrations. Data kan gå förlorad!', 'en' => 'WARNING: This will rollback the latest batch of migrations. Data may be lost!'],
    'admin.migrations.rollback.confirm' => ['sv' => 'Är du SÄKER på att du vill återställa migrations? Detta kan inte ångras!', 'en' => 'Are you SURE you want to rollback migrations? This cannot be undone!'],
    'admin.migrations.rollback.button' => ['sv' => 'Återställ senaste batch', 'en' => 'Rollback Latest Batch'],

    // List
    'admin.migrations.list.heading' => ['sv' => 'Alla migrations', 'en' => 'All Migrations'],
    'admin.migrations.list.empty' => ['sv' => 'Inga migrations hittades', 'en' => 'No migrations found'],
    'admin.migrations.list.status' => ['sv' => 'Status', 'en' => 'Status'],
    'admin.migrations.list.migration' => ['sv' => 'Migration', 'en' => 'Migration'],
    'admin.migrations.list.batch' => ['sv' => 'Batch', 'en' => 'Batch'],
    'admin.migrations.list.executed_at' => ['sv' => 'Körd', 'en' => 'Executed At'],
    'admin.migrations.list.executed' => ['sv' => 'Körd', 'en' => 'Executed'],
    'admin.migrations.list.pending' => ['sv' => 'Väntande', 'en' => 'Pending'],

    // Success messages
    'admin.migrations.success.migrated' => ['sv' => '{count} migrations kördes framgångsrikt!', 'en' => '{count} migrations executed successfully!'],
    'admin.migrations.success.rolled_back' => ['sv' => '{count} migrations återställdes!', 'en' => '{count} migrations rolled back!'],

    // Error messages
    'admin.migrations.error.failed' => ['sv' => 'Migrations misslyckades. {count} fel uppstod.', 'en' => 'Migrations failed. {count} errors occurred.'],
    'admin.migrations.error.rollback_failed' => ['sv' => 'Återställning misslyckades', 'en' => 'Rollback failed'],

    // Info
    'admin.migrations.info.heading' => ['sv' => 'Information', 'en' => 'Information'],
    'admin.migrations.info.what.heading' => ['sv' => 'Vad är migrations?', 'en' => 'What are migrations?'],
    'admin.migrations.info.what.description' => ['sv' => 'Migrations är ett sätt att versionshantera databasens schema. Varje migration innehåller SQL för att applicera en ändring och för att återställa den.', 'en' => 'Migrations are a way to version control your database schema. Each migration contains SQL to apply a change and to rollback it.'],
    'admin.migrations.info.when.heading' => ['sv' => 'När ska jag köra migrations?', 'en' => 'When should I run migrations?'],
    'admin.migrations.info.when.first_setup' => ['sv' => 'Vid första installation för att skapa tabellerna', 'en' => 'On first installation to create the tables'],
    'admin.migrations.info.when.after_update' => ['sv' => 'Efter att ha uppdaterat koden från Git', 'en' => 'After updating the code from Git'],
    'admin.migrations.info.when.new_features' => ['sv' => 'När nya funktioner kräver databasändringar', 'en' => 'When new features require database changes'],
    'admin.migrations.info.cli.heading' => ['sv' => 'CLI-kommando', 'en' => 'CLI Command'],
    'admin.migrations.info.cli.description' => ['sv' => 'Du kan också köra migrations via terminalen:', 'en' => 'You can also run migrations via terminal:'],

    ];

