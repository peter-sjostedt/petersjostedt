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
];
