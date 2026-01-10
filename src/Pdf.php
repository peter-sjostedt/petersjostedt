<?php

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Pdf - Wrapper för DOMPDF
 * 
 * Enkel klass för att skapa PDF från HTML.
 * 
 * Användning:
 *   $pdf = new Pdf();
 *   $pdf->loadHtml('<h1>Hej!</h1>');
 *   $pdf->stream('dokument.pdf');
 */
class Pdf
{
    private Dompdf $dompdf;
    
    public function __construct()
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', __DIR__ . '/../public_html');
        
        $this->dompdf = new Dompdf($options);
    }
    
    /**
     * Ladda HTML-innehåll
     */
    public function loadHtml(string $html): self
    {
        $this->dompdf->loadHtml($html, 'UTF-8');
        return $this;
    }
    
    /**
     * Ladda HTML från fil
     */
    public function loadHtmlFile(string $path): self
    {
        $html = file_get_contents($path);
        return $this->loadHtml($html);
    }
    
    /**
     * Sätt pappersstorlek
     * @param string $size 'A4', 'letter', etc.
     * @param string $orientation 'portrait' eller 'landscape'
     */
    public function setPaper(string $size = 'A4', string $orientation = 'portrait'): self
    {
        $this->dompdf->setPaper($size, $orientation);
        return $this;
    }
    
    /**
     * Rendera PDF (måste köras innan output)
     */
    public function render(): self
    {
        $this->dompdf->render();
        return $this;
    }
    
    /**
     * Skicka PDF till webbläsaren (visa eller ladda ner)
     * @param string $filename Filnamn
     * @param bool $download true = ladda ner, false = visa i webbläsare
     */
    public function stream(string $filename = 'dokument.pdf', bool $download = false): void
    {
        $this->dompdf->stream($filename, [
            'Attachment' => $download
        ]);
    }
    
    /**
     * Hämta PDF som sträng (för att spara eller skicka)
     */
    public function output(): string
    {
        return $this->dompdf->output();
    }
    
    /**
     * Spara PDF till fil
     */
    public function save(string $path): bool
    {
        $output = $this->dompdf->output();
        return file_put_contents($path, $output) !== false;
    }
    
    /**
     * Skicka PDF som e-postbilaga
     */
    public function sendByEmail(string $to, string $subject, string $body, string $filename = 'dokument.pdf'): bool
    {
        $pdfContent = $this->output();
        
        $mailer = new Mailer();
        return $mailer->sendWithAttachment($to, $subject, $body, $pdfContent, $filename);
    }
}