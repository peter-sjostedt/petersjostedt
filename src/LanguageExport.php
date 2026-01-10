<?php
/**
 * LanguageExport - Export/Import av översättningar
 * 
 * Användning:
 *   $export = new LanguageExport();
 *   $export->exportCSV('translations.csv');
 *   $export->importCSV('translations.csv');
 */

class LanguageExport
{
    private Language $lang;
    
    public function __construct()
    {
        $this->lang = Language::getInstance();
    }
    
    /**
     * Exportera till CSV-fil
     */
    public function exportCSV(string $filename): bool
    {
        $translations = $this->lang->getAllTranslations();
        $languages = $this->lang->getLanguages();
        
        $handle = fopen($filename, 'w');
        if (!$handle) {
            return false;
        }
        
        // BOM för Excel UTF-8
        fwrite($handle, "\xEF\xBB\xBF");
        
        // Header-rad
        $header = ['key'];
        foreach (array_keys($languages) as $langCode) {
            $header[] = $langCode;
        }
        fputcsv($handle, $header, ';');
        
        // Data-rader
        foreach ($translations as $key => $values) {
            $row = [$key];
            foreach (array_keys($languages) as $langCode) {
                $row[] = $values[$langCode] ?? '';
            }
            fputcsv($handle, $row, ';');
        }
        
        fclose($handle);
        return true;
    }
    
    /**
     * Exportera och skicka som nedladdning
     */
    public function downloadCSV(string $filename = 'translations.csv'): void
    {
        $translations = $this->lang->getAllTranslations();
        $languages = $this->lang->getLanguages();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // BOM för Excel UTF-8
        fwrite($output, "\xEF\xBB\xBF");
        
        // Header
        $header = ['key'];
        foreach (array_keys($languages) as $langCode) {
            $header[] = $langCode;
        }
        fputcsv($output, $header, ';');
        
        // Data
        foreach ($translations as $key => $values) {
            $row = [$key];
            foreach (array_keys($languages) as $langCode) {
                $row[] = $values[$langCode] ?? '';
            }
            fputcsv($output, $row, ';');
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Importera från CSV-fil
     */
    public function importCSV(string $filename): array
    {
        $result = [
            'success' => false,
            'added' => 0,
            'updated' => 0,
            'languages_added' => [],
            'errors' => []
        ];
        
        if (!file_exists($filename)) {
            $result['errors'][] = 'Filen hittades inte';
            return $result;
        }
        
        $handle = fopen($filename, 'r');
        if (!$handle) {
            $result['errors'][] = 'Kunde inte öppna filen';
            return $result;
        }
        
        // Läs header
        $header = fgetcsv($handle, 0, ';');
        if (!$header || $header[0] !== 'key') {
            $result['errors'][] = 'Ogiltig CSV-format';
            fclose($handle);
            return $result;
        }
        
        // Ta bort BOM om det finns
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        
        // Hämta språkkoder från header
        $langCodes = array_slice($header, 1);
        
        // Kolla om nya språk behöver läggas till
        $existingLanguages = $this->lang->getLanguages();
        foreach ($langCodes as $code) {
            if (!isset($existingLanguages[$code])) {
                $this->lang->addLanguage($code, $code, '', $code);
                $result['languages_added'][] = $code;
            }
        }
        
        // Läs översättningar
        $translations = $this->lang->getAllTranslations();
        $lineNumber = 1;
        
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $lineNumber++;
            
            if (count($row) < 2) {
                $result['errors'][] = "Rad {$lineNumber}: För få kolumner";
                continue;
            }
            
            $key = trim($row[0]);
            if (empty($key)) {
                continue;
            }
            
            $isNew = !isset($translations[$key]);
            $translations[$key] = [];
            
            foreach ($langCodes as $i => $langCode) {
                $translations[$key][$langCode] = $row[$i + 1] ?? '';
            }
            
            if ($isNew) {
                $result['added']++;
            } else {
                $result['updated']++;
            }
        }
        
        fclose($handle);
        
        // Spara
        if ($this->lang->saveTranslations($translations)) {
            $result['success'] = true;
        } else {
            $result['errors'][] = 'Kunde inte spara översättningar';
        }
        
        return $result;
    }
    
    /**
     * Importera från uppladdad fil ($_FILES)
     */
    public function importUploadedCSV(array $file): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'errors' => ['Uppladdningsfel: ' . $file['error']]
            ];
        }
        
        return $this->importCSV($file['tmp_name']);
    }
}