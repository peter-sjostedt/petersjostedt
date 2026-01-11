<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Mailer - Wrapper för PHPMailer
 *
 * Singleton-klass för att skicka e-post via SMTP.
 *
 * Användning:
 *   $mailer = Mailer::getInstance();
 *   $mailer->send('mottagare@example.com', 'Ämne', '<p>HTML-innehåll</p>');
 */
class Mailer
{
    private static ?Mailer $instance = null;
    private array $config;
    private ?string $lastError = null;

    /**
     * Privat konstruktor - använd getInstance()
     */
    private function __construct()
    {
        $this->config = require __DIR__ . '/../config/mail.php';
    }

    /**
     * Hämta instansen (singleton)
     */
    public static function getInstance(): Mailer
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Skicka e-post
     */
    public function send(string $to, string $subject, string $htmlBody, ?string $replyTo = null, ?string $textBody = null): bool
    {
        $mail = new PHPMailer(true);
        
        try {
            // SMTP-konfiguration
            $mail->isSMTP();
            $mail->Host = $this->config['host'];
            $mail->Port = $this->config['port'];
            $mail->SMTPSecure = $this->config['encryption'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['username'];
            $mail->Password = $this->config['password'];
            $mail->SMTPDebug = $this->config['debug'];
            
            // Teckenuppsättning
            $mail->CharSet = $this->config['charset'];
            
            // Avsändare och mottagare
            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $mail->addAddress($to);

            // Reply-To om angivet
            if ($replyTo) {
                $mail->addReplyTo($replyTo);
            }

            // Innehåll
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $textBody ?? strip_tags($htmlBody);
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            $this->lastError = $mail->ErrorInfo;
            
            // Logga felet
            if (class_exists('Logger')) {
                $logger = new Logger();
                $logger->log('mail_error', "Kunde inte skicka till {$to}: {$this->lastError}");
            }
            
            return false;
        }
    }
    
    /**
     * Skicka till flera mottagare
     */
    public function sendToMany(array $recipients, string $subject, string $htmlBody, ?string $textBody = null): array
    {
        $results = ['success' => [], 'failed' => []];
        
        foreach ($recipients as $email) {
            if ($this->send($email, $subject, $htmlBody, $textBody)) {
                $results['success'][] = $email;
            } else {
                $results['failed'][] = ['email' => $email, 'error' => $this->lastError];
            }
        }
        
        return $results;
    }
    
    /**
     * Skicka med anpassad avsändare
     */
    public function sendFrom(string $fromEmail, string $fromName, string $to, string $subject, string $htmlBody): bool
    {
        $mail = new PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->Host = $this->config['host'];
            $mail->Port = $this->config['port'];
            $mail->SMTPSecure = $this->config['encryption'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['username'];
            $mail->Password = $this->config['password'];
            $mail->CharSet = $this->config['charset'];
            
            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $mail->addReplyTo($fromEmail, $fromName);
            $mail->addAddress($to);
            
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody);
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            $this->lastError = $mail->ErrorInfo;
            return false;
        }
    }
    
    /**
     * Skicka med bilaga
     */
    public function sendWithAttachment(string $to, string $subject, string $htmlBody, string $attachmentContent, string $filename): bool
    {
        $mail = new PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->Host = $this->config['host'];
            $mail->Port = $this->config['port'];
            $mail->SMTPSecure = $this->config['encryption'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['username'];
            $mail->Password = $this->config['password'];
            $mail->CharSet = $this->config['charset'];
            
            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $mail->addAddress($to);
            
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody);
            
            // Lägg till bilaga från sträng
            $mail->addStringAttachment($attachmentContent, $filename);
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            $this->lastError = $mail->ErrorInfo;
            return false;
        }
    }
    
    /**
     * Hämta senaste felmeddelande
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Förhindra kloning av singleton
     */
    private function __clone() {}

    /**
     * Förhindra unserialisering av singleton
     */
    public function __wakeup()
    {
        throw new Exception('Kan inte unserialisera singleton');
    }
}