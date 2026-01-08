<?php
/**
 * Säkerhetsfunktioner
 */

// Starta session säkert
function secure_session_start() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.use_strict_mode', 1);
        session_start();
    }

    // Regenerera session-ID regelbundet
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } elseif (time() - $_SESSION['created'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

// Generera CSRF-token
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verifiera CSRF-token
function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// CSRF-fält för formulär
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
}

// Sanera input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Validera e-post
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Rate limiting (enkel version)
function rate_limit($key, $max_attempts = 5, $window_seconds = 300) {
    $cache_key = 'rate_limit_' . md5($key . $_SERVER['REMOTE_ADDR']);

    if (!isset($_SESSION[$cache_key])) {
        $_SESSION[$cache_key] = ['count' => 0, 'start' => time()];
    }

    $data = &$_SESSION[$cache_key];

    // Återställ om tidsfönstret har passerat
    if (time() - $data['start'] > $window_seconds) {
        $data = ['count' => 0, 'start' => time()];
    }

    $data['count']++;

    return $data['count'] <= $max_attempts;
}

// Logga säkerhetshändelser
function log_security_event($event, $details = '') {
    $log_file = __DIR__ . '/../../logs/security.log';
    $log_dir = dirname($log_file);

    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    $log_entry = "[{$timestamp}] [{$ip}] [{$event}] {$details} | UA: {$user_agent}" . PHP_EOL;

    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// Säkerhetsheaders (anropas tidigt)
function set_security_headers() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline';");
}

// Kontrollera om request är HTTPS
function is_https() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || $_SERVER['SERVER_PORT'] == 443;
}

// Tvinga HTTPS (för produktion)
function force_https() {
    if (!is_https() && $_SERVER['HTTP_HOST'] !== 'localhost') {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
        exit;
    }
}

// Validera lösenordsstyrka
function validate_password_strength($password) {
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = 'Lösenordet måste vara minst 8 tecken';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Lösenordet måste innehålla minst en stor bokstav';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Lösenordet måste innehålla minst en liten bokstav';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Lösenordet måste innehålla minst en siffra';
    }

    return $errors;
}

// Hasha lösenord säkert
function hash_password($password) {
    return password_hash($password, PASSWORD_ARGON2ID);
}

// Verifiera lösenord
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}
