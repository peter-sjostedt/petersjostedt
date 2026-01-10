<?php
/**
 * LanguageScanner - Skannar projekt efter hårdkodade strängar
 */

class LanguageScanner
{
    private array $results = [];
    private array $ignoredPatterns = [
        '/^[a-z_]+$/',              // snake_case (variabler)
        '/^[a-zA-Z]+:$/',           // Labels (case:)
        '/^\d+$/',                   // Bara siffror
        '/^#[a-fA-F0-9]{3,6}$/',    // Hex-färger
        '/^[\.\/#]/',                // Sökvägar, CSS-selektorer
        '/^(https?:|mailto:)/',      // URLs
        '/^(GET|POST|PUT|DELETE)$/', // HTTP-metoder
        '/^(SELECT|INSERT|UPDATE|DELETE|FROM|WHERE)/i', // SQL
        '/^(true|false|null)$/i',    // Boolean/null
        '/^(px|em|rem|%|vh|vw)$/',   // CSS-enheter
        '/^[<>=!]+$/',               // Operatorer
        '/^\s*$/',                   // Whitespace
        '/^(div|span|button|input|form|table|tr|td|th|ul|li|a|p|h[1-6])$/i', // HTML-taggar
        '/^(click|submit|change|load|error|success)$/i', // Event-namn
        '/^(id|class|type|name|value|href|src|alt)$/i',  // HTML-attribut
        '/^(utf-8|iso-8859-1)$/i',   // Encodings
        '/^(application\/json|text\/html|text\/csv)/i', // MIME-typer
    ];
    
    private array $extensions = ['php', 'js'];
    private array $skipDirs = ['vendor', 'node_modules', '.git', 'logs'];
    private int $minLength = 2;
    private int $maxLength = 500;

    /**
     * Skanna en katalog
     */
    public function scan(string $directory): array
    {
        $this->results = [];
        $this->scanDirectory($directory);
        return $this->results;
    }

    /**
     * Rekursiv katalogskanning
     */
    private function scanDirectory(string $dir): void
    {
        $items = scandir($dir);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            if (in_array($item, $this->skipDirs)) continue;
            
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            
            if (is_dir($path)) {
                $this->scanDirectory($path);
            } else {
                $ext = pathinfo($path, PATHINFO_EXTENSION);
                if (in_array($ext, $this->extensions)) {
                    $this->scanFile($path);
                }
            }
        }
    }

    /**
     * Skanna en fil
     */
    private function scanFile(string $filepath): void
    {
        $content = file_get_contents($filepath);
        $lines = explode("\n", $content);
        $ext = pathinfo($filepath, PATHINFO_EXTENSION);
        
        foreach ($lines as $lineNum => $line) {
            $strings = $this->extractStrings($line, $ext);
            
            foreach ($strings as $string) {
                if ($this->isTranslatable($string)) {
                    $key = $this->generateKey($string);
                    
                    if (!isset($this->results[$key])) {
                        $this->results[$key] = [
                            'text' => $string,
                            'suggested_key' => $key,
                            'locations' => []
                        ];
                    }
                    
                    $this->results[$key]['locations'][] = [
                        'file' => $filepath,
                        'line' => $lineNum + 1
                    ];
                }
            }
        }
    }

    /**
     * Extrahera strängar från en rad
     */
    private function extractStrings(string $line, string $ext): array
    {
        $strings = [];
        
        // Hoppa över kommentarer
        $trimmed = trim($line);
        if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '#') || str_starts_with($trimmed, '*')) {
            return [];
        }
        
        // Matcha strängar inom citattecken
        // Dubbla citattecken
        preg_match_all('/"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"/', $line, $doubleQuotes);
        // Enkla citattecken
        preg_match_all("/(?<![a-zA-Z])'([^'\\\\]*(?:\\\\.[^'\\\\]*)*)'/", $line, $singleQuotes);
        
        $strings = array_merge($doubleQuotes[1] ?? [], $singleQuotes[1] ?? []);
        
        // PHP: Kolla även HTML utanför PHP-taggar
        if ($ext === 'php') {
            // HTML-innehåll mellan taggar (förenklad)
            preg_match_all('/>([^<>{$]+)</', $line, $htmlContent);
            if (!empty($htmlContent[1])) {
                foreach ($htmlContent[1] as $text) {
                    $text = trim($text);
                    if (!empty($text) && !preg_match('/^\s*$/', $text)) {
                        $strings[] = $text;
                    }
                }
            }
        }
        
        return $strings;
    }

    /**
     * Kontrollera om sträng ska översättas
     */
    private function isTranslatable(string $string): bool
    {
        $string = trim($string);
        
        // Längdkontroll
        if (strlen($string) < $this->minLength || strlen($string) > $this->maxLength) {
            return false;
        }
        
        // Måste innehålla minst en bokstav
        if (!preg_match('/[a-zA-ZåäöÅÄÖ]/', $string)) {
            return false;
        }
        
        // Kolla ignorerade mönster
        foreach ($this->ignoredPatterns as $pattern) {
            if (preg_match($pattern, $string)) {
                return false;
            }
        }
        
        // Hoppa över om det ser ut som en variabel/funktion
        if (preg_match('/^[a-z][a-zA-Z0-9_]*$/', $string)) {
            return false;
        }
        
        // Hoppa över filsökvägar
        if (preg_match('/\.(php|js|css|html|jpg|png|gif|svg)$/i', $string)) {
            return false;
        }
        
        // Hoppa över CSS-klasser och ID:n
        if (preg_match('/^[\w\-]+$/', $string) && strlen($string) < 30) {
            return false;
        }
        
        return true;
    }

    /**
     * Generera en nyckel från texten
     */
    private function generateKey(string $text): string
    {
        $key = mb_strtolower($text);
        $key = preg_replace('/[^a-z0-9åäö\s]/u', '', $key);
        $key = preg_replace('/\s+/', '_', trim($key));
        $key = substr($key, 0, 40);
        $key = rtrim($key, '_');
        
        return $key ?: 'text_' . substr(md5($text), 0, 8);
    }

    /**
     * Exportera resultat som CSV
     */
    public function exportCSV(string $filename): bool
    {
        $handle = fopen($filename, 'w');
        if (!$handle) return false;
        
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['key', 'sv', 'en', 'original_text', 'file', 'line'], ';');
        
        foreach ($this->results as $item) {
            $location = $item['locations'][0] ?? ['file' => '', 'line' => ''];
            fputcsv($handle, [
                $item['suggested_key'],
                $item['text'],  // Svenska (original)
                '',             // Engelska (tom)
                $item['text'],
                $location['file'],
                $location['line']
            ], ';');
        }
        
        fclose($handle);
        return true;
    }

    /**
     * Exportera som nedladdning
     */
    public function downloadCSV(string $filename = 'scan_results.csv'): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['key', 'sv', 'en', 'original_text', 'file', 'line'], ';');
        
        foreach ($this->results as $item) {
            $location = $item['locations'][0] ?? ['file' => '', 'line' => ''];
            fputcsv($output, [
                $item['suggested_key'],
                $item['text'],
                '',
                $item['text'],
                $location['file'],
                $location['line']
            ], ';');
        }
        
        fclose($output);
        exit;
    }

    /**
     * Hämta resultat
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Antal hittade strängar
     */
    public function count(): int
    {
        return count($this->results);
    }
}