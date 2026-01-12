<?php
/**
 * Exempel - Användning av File Picker Modal
 */

require_once __DIR__ . '/includes/config.php';
secure_session_start();
set_security_headers();
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Picker Modal - Exempel</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="assets/css/file-picker-modal.css?v=5">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            padding: 2rem;
            background: #f5f5f5;
        }
        .container { max-width: 900px; margin: 0 auto; }
        .card {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 { margin-bottom: 1rem; color: #333; }
        h2 { margin-bottom: 1rem; color: #555; font-size: 1.3rem; }
        p { margin-bottom: 1rem; line-height: 1.6; color: #666; }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn:hover { background: #0056b3; }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover { background: #545b62; }

        .example-box {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 6px;
            margin: 1rem 0;
        }
        .selected-file {
            background: #e7f3ff;
            padding: 1rem;
            border-radius: 6px;
            border-left: 4px solid #007bff;
            margin-top: 1rem;
        }
        .selected-file h3 {
            margin-bottom: 0.5rem;
            color: #0056b3;
        }
        .selected-file img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 4px;
            margin-top: 0.5rem;
        }
        code {
            background: #f4f4f4;
            padding: 0.2rem 0.4rem;
            border-radius: 3px;
            font-size: 0.9rem;
        }
        pre {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 1rem;
            border-radius: 6px;
            overflow-x: auto;
            margin: 1rem 0;
        }
        pre code {
            background: none;
            padding: 0;
            color: inherit;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗂️ File Picker Modal - Exempel</h1>

        <!-- Exempel 1: Välj valfri fil -->
        <div class="card">
            <h2>Exempel 1: Välj valfri fil</h2>
            <p>Öppna modalen och välj vilken fil som helst från databasen.</p>

            <button class="btn" id="btn-example1">Välj fil</button>

            <div id="result1"></div>
        </div>

        <!-- Exempel 2: Välj endast bilder -->
        <div class="card">
            <h2>Exempel 2: Välj endast bilder</h2>
            <p>Filtrera så att endast bilder visas.</p>

            <button class="btn" id="btn-example2">Välj bild</button>

            <div id="result2"></div>
        </div>

        <!-- Exempel 3: Välj flera filer -->
        <div class="card">
            <h2>Exempel 3: Välj flera filer</h2>
            <p>Tillåt användaren att välja flera filer samtidigt.</p>

            <button class="btn" id="btn-example3">Välj flera filer</button>

            <div id="result3"></div>
        </div>

        <!-- Exempel 4: Välj från specifik mapp -->
        <div class="card">
            <h2>Exempel 4: Välj från specifik mapp</h2>
            <p>Öppna direkt i en specifik mapp (t.ex. "avatars").</p>

            <div class="grid">
                <button class="btn-secondary btn" data-folder="images">Välj från images</button>
                <button class="btn-secondary btn" data-folder="avatars">Välj från avatars</button>
                <button class="btn-secondary btn" data-folder="gallery">Välj från gallery</button>
            </div>

            <div id="result4"></div>
        </div>

        <!-- Exempel 5: Välj mapp -->
        <div class="card">
            <h2>Exempel 5: Välj mapp</h2>
            <p>Välj en mapp istället för en fil.</p>

            <button class="btn" id="btn-example5">Välj mapp</button>

            <div id="result5"></div>
        </div>

        <!-- Kodexempel -->
        <div class="card">
            <h2>💻 Hur man använder</h2>

            <h3 style="margin-top: 1.5rem; margin-bottom: 0.5rem;">1. Inkludera CSS och JS</h3>
            <pre><code>&lt;link rel="stylesheet" href="assets/css/file-picker-modal.css"&gt;
&lt;script src="assets/js/file-picker-modal.js"&gt;&lt;/script&gt;</code></pre>

            <h3 style="margin-top: 1.5rem; margin-bottom: 0.5rem;">2. Öppna modalen med JavaScript</h3>
            <pre><code>// Välj en fil
openFilePicker({
    onSelect: function(file) {
        console.log('Vald fil:', file);
        // file innehåller: { id, original_name, mime_type, file_size, file_path, url }
    }
});

// Välj endast bilder
openFilePicker({
    type: 'image', // 'all', 'image', 'document'
    onSelect: function(file) {
        console.log('Vald bild:', file);
    }
});

// Välj flera filer
openFilePicker({
    multiple: true,
    onSelect: function(files) {
        console.log('Valda filer:', files);
        // files är en array
    }
});

// Öppna i specifik mapp
openFilePicker({
    folder: 'avatars',
    onSelect: function(file) {
        console.log('Vald fil:', file);
    }
});

// Välj mapp istället för fil
openFilePicker({
    mode: 'folder',
    onSelect: function(folder) {
        console.log('Vald mapp:', folder);
        // folder är en sträng, t.ex. 'images'
    }
});</code></pre>

            <h3 style="margin-top: 1.5rem; margin-bottom: 0.5rem;">3. Hantera vald fil eller mapp</h3>
            <pre><code>function handleFileSelect(file) {
    // Visa bilden
    if (file.mime_type.startsWith('image/')) {
        document.getElementById('preview').src = file.url;
    }

    // Sätt filens ID i ett hidden field
    document.getElementById('file_id').value = file.id;

    // Visa filnamnet
    document.getElementById('filename').textContent = file.original_name;
}</code></pre>
        </div>

        <p style="text-align: center; color: #666; margin-top: 2rem;">
            <a href="test-file-upload.php">→ Test filuppladdning</a> |
            <a href="test-image-upload.php">→ Test bilduppladdning</a> |
            <a href="test-file-browser.php">→ Gammal filbläddrare</a>
        </p>
    </div>

    <script src="assets/js/file-picker-modal.js"></script>
    <script src="assets/js/modal-usage.js"></script>
</body>
</html>
