<?php
require_once 'includes/header.php';

$message = '';

// Hantera formulärinlämning
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifiera CSRF-token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = '<p class="error">Ogiltig förfrågan. Ladda om sidan och försök igen.</p>';
        log_security_event('CSRF_FAILURE', 'Kontaktformulär');
    }
    // Rate limiting - max 5 försök per 5 minuter
    elseif (!rate_limit('contact_form')) {
        $message = '<p class="error">För många försök. Vänta en stund och försök igen.</p>';
        log_security_event('RATE_LIMIT', 'Kontaktformulär');
    }
    else {
        $name = sanitize_input($_POST['name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $subject = sanitize_input($_POST['subject'] ?? '');
        $body = sanitize_input($_POST['message'] ?? '');

        // Validering
        if (empty($name) || empty($email) || empty($body)) {
            $message = '<p class="error">Vänligen fyll i alla obligatoriska fält.</p>';
        } elseif (!validate_email($email)) {
            $message = '<p class="error">Vänligen ange en giltig e-postadress.</p>';
        } else {
            // Skicka e-post till kontaktadressen från inställningarna
            $settings = Settings::getInstance();
            $siteEmail = $settings->get('site_email', '');

            if ($siteEmail) {
                $mailer = Mailer::getInstance();
                $emailSubject = $subject ?: 'Nytt meddelande från kontaktformuläret';
                $emailBody = "<h2>Nytt kontaktformulär</h2>";
                $emailBody .= "<p><strong>Från:</strong> " . htmlspecialchars($name) . "</p>";
                $emailBody .= "<p><strong>E-post:</strong> " . htmlspecialchars($email) . "</p>";
                if ($subject) {
                    $emailBody .= "<p><strong>Ämne:</strong> " . htmlspecialchars($subject) . "</p>";
                }
                $emailBody .= "<p><strong>Meddelande:</strong></p>";
                $emailBody .= "<p>" . nl2br(htmlspecialchars($body)) . "</p>";

                if ($mailer->send($siteEmail, $emailSubject, $emailBody, $email)) {
                    $message = '<p class="success">Tack för ditt meddelande! Vi återkommer så snart som möjligt.</p>';
                    log_security_event('CONTACT_FORM', "Från: {$name} ({$email})");
                } else {
                    $message = '<p class="error">Ett fel uppstod när meddelandet skulle skickas. Försök igen senare.</p>';
                }
            } else {
                $message = '<p class="success">Tack för ditt meddelande! Vi återkommer så snart som möjligt.</p>';
                log_security_event('CONTACT_FORM', "Från: {$name} ({$email})");
            }
        }
    }
}
?>

<main>
    <section class="content">
        <h1>Kontakta oss</h1>

        <?php echo $message; ?>

        <form method="POST" action="">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label for="name">Namn *</label>
                <input type="text" id="name" name="name" required>
            </div>

            <div class="form-group">
                <label for="email">E-post *</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="subject">Ämne</label>
                <input type="text" id="subject" name="subject">
            </div>

            <div class="form-group">
                <label for="message">Meddelande *</label>
                <textarea id="message" name="message" required></textarea>
            </div>

            <button type="submit" class="btn">Skicka</button>
        </form>
    </section>
</main>

<style>
    .error { color: #dc3545; background: #f8d7da; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
    .success { color: #155724; background: #d4edda; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
</style>

<?php
require_once 'includes/footer.php';
?>
