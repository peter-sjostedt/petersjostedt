<?php
/**
 * Language - Språkhantering
 * 
 * Användning:
 *   echo t('welcome');
 *   echo t('welcome_user', ['name' => 'Peter']);
 */

/**
 * Global hjälpfunktion för översättning
 */
function t(string $key, array $params = []): string {
    return Language::getInstance()->t($key, $params);
}

class Language
{
    private static ?Language $instance = null;
    
    private string $currentLang;
    private string $defaultLang = 'sv';
    private string $cookieName = 'lang';
    private int $cookieLifetime = 31536000; // 1 år
    
    private array $translations = [];
    private array $languages = [];
    
    private string $configPath;
    
    /**
     * Singleton - hämta instans
     */
    public static function getInstance(): Language
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Privat constructor
     */
    private function __construct()
    {
        // Sökväg till config (justera efter din struktur)
        $this->configPath = dirname(__DIR__) . '/config/';
        
        // Ladda språk och översättningar
        $this->languages = $this->loadFile('languages.php');
        $this->translations = $this->loadFile('translations.php');
        
        // Bestäm aktivt språk
        $this->currentLang = $this->detectLanguage();
    }
    
    /**
     * Översätt nyckel
     */
    public function t(string $key, array $params = []): string
    {
        // Hämta översättning
        $text = $this->translations[$key][$this->currentLang] 
             ?? $this->translations[$key][$this->defaultLang] 
             ?? $key;
        
        // Ersätt placeholders
        foreach ($params as $name => $value) {
            $text = str_replace('{' . $name . '}', $value, $text);
        }
        
        return $text;
    }
    
    /**
     * Byt språk
     */
    public function setLanguage(string $lang): bool
    {
        if (!isset($this->languages[$lang])) {
            return false;
        }
        
        $this->currentLang = $lang;
        $this->setCookie($lang);
        
        return true;
    }
    
    /**
     * Hämta aktivt språk
     */
    public function getLanguage(): string
    {
        return $this->currentLang;
    }
    
    /**
     * Hämta alla tillgängliga språk
     */
    public function getLanguages(): array
    {
        return $this->languages;
    }
    
    /**
     * Hämta alla översättningar för aktivt språk (för JS)
     */
    public function all(): array
    {
        $result = [];
        
        foreach ($this->translations as $key => $values) {
            $result[$key] = $values[$this->currentLang] 
                         ?? $values[$this->defaultLang] 
                         ?? $key;
        }
        
        return $result;
    }
    
    /**
     * Hämta alla översättningar (alla språk)
     */
    public function getAllTranslations(): array
    {
        return $this->translations;
    }
    
    /**
     * Spara översättningar till fil
     */
    public function saveTranslations(array $translations): bool
    {
        $this->translations = $translations;
        
        $content = "<?php\n/**\n * Översättningar\n * Genererad: " . date('Y-m-d H:i:s') . "\n */\n\nreturn ";
        $content .= var_export($translations, true) . ";\n";
        
        return file_put_contents($this->configPath . 'translations.php', $content) !== false;
    }
    
    /**
     * Lägg till nytt språk
     */
    public function addLanguage(string $code, string $name, string $flag = '', string $locale = ''): bool
    {
        if (isset($this->languages[$code])) {
            return false;
        }
        
        $this->languages[$code] = [
            'name' => $name,
            'flag' => $flag,
            'locale' => $locale ?: $code
        ];
        
        // Spara till fil
        $content = "<?php\n/**\n * Tillgängliga språk\n */\n\nreturn ";
        $content .= var_export($this->languages, true) . ";\n";
        
        return file_put_contents($this->configPath . 'languages.php', $content) !== false;
    }
    
    /**
     * Detektera språk från cookie eller webbläsare
     */
    private function detectLanguage(): string
    {
        // 1. Kolla cookie
        if (isset($_COOKIE[$this->cookieName])) {
            $lang = $_COOKIE[$this->cookieName];
            if (isset($this->languages[$lang])) {
                return $lang;
            }
        }
        
        // 2. Kolla webbläsarens språk
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            if (isset($this->languages[$browserLang])) {
                return $browserLang;
            }
        }
        
        // 3. Använd standard
        return $this->defaultLang;
    }
    
    /**
     * Sätt cookie
     */
    private function setCookie(string $lang): void
    {
        setcookie(
            $this->cookieName,
            $lang,
            [
                'expires' => time() + $this->cookieLifetime,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => false, // Tillgänglig i JS
                'samesite' => 'Lax'
            ]
        );
    }
    
    /**
     * Ladda config-fil
     */
    private function loadFile(string $filename): array
    {
        $path = $this->configPath . $filename;
        
        if (file_exists($path)) {
            return require $path;
        }
        
        return [];
    }
}