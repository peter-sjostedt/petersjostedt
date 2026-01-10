<?php
require_once 'includes/config.php';

$action = $_GET['action'] ?? 'form';

if ($action === 'view') {
    // Visa PDF i webbläsaren
    $pdf = new Pdf();
    $pdf->loadHtml(getTestHtml())
        ->setPaper('A4', 'portrait')
        ->render()
        ->stream('test.pdf', false);
    exit;
}

if ($action === 'download') {
    // Ladda ner PDF
    $pdf = new Pdf();
    $pdf->loadHtml(getTestHtml())
        ->setPaper('A4', 'portrait')
        ->render()
        ->stream('test.pdf', true);
    exit;
}

if ($action === 'email') {
    // Skicka PDF via e-post
    $pdf = new Pdf();
    $pdf->loadHtml(getTestHtml())
        ->setPaper('A4', 'portrait')
        ->render();
    
    $success = $pdf->sendByEmail(
        'peter.sjostedt@gmail.com',
        'Test PDF från ramverket',
        '<h1>Hej!</h1><p>Här kommer en test-PDF som bilaga.</p>',
        'test.pdf'
    );
    
    $message = $success ? 'PDF skickad till peter.sjostedt@gmail.com!' : 'Kunde inte skicka e-post.';
}

function getTestHtml(): string {
    $date = date('Y-m-d H:i');
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #333;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        .info-box {
            background: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #bdc3c7;
            padding: 10px;
            text-align: left;
        }
        th {
            background: #3498db;
            color: white;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #7f8c8d;
            font-size: 10pt;
        }
    </style>
</head>
<body>
    <h1>Test PDF-dokument</h1>
    
    <div class="info-box">
        <strong>Genererad:</strong> {$date}<br>
        <strong>System:</strong> PHP Framework av Peter Sjöstedt
    </div>
    
    <h2>Exempeltabell</h2>
    <table>
        <tr>
            <th>Namn</th>
            <th>E-post</th>
            <th>Roll</th>
        </tr>
        <tr>
            <td>Anna Andersson</td>
            <td>anna@example.com</td>
            <td>Admin</td>
        </tr>
        <tr>
            <td>Erik Eriksson</td>
            <td>erik@example.com</td>
            <td>Användare</td>
        </tr>
        <tr>
            <td>Maria Månsson</td>
            <td>maria@example.com</td>
            <td>Användare</td>
        </tr>
    </table>
    
    <h2>Svenska tecken</h2>
    <p>Kontroll av ÅÄÖ och åäö – fungerar det?</p>
    
    <div class="footer">
        Skapad med DOMPDF • petersjostedt.se
    </div>
</body>
</html>
HTML;
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Testa PDF</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div style="max-width: 600px; margin: 50px auto; padding: 20px;">
        <h1>Testa PDF-generering</h1>
        
        <?php if (!empty($message)): ?>
        <div style="padding: 15px; background: <?= $success ? '#d4edda' : '#f8d7da' ?>; border-radius: 5px; margin-bottom: 20px;">
            <?= $message ?>
        </div>
        <?php endif; ?>
        
        <p>Klicka på knapparna för att testa PDF-funktionen:</p>
        
        <div style="display: flex; gap: 10px; margin: 30px 0;">
            <a href="?action=view" class="button" style="padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px;">
                Visa PDF
            </a>
            <a href="?action=download" class="button" style="padding: 10px 20px; background: #27ae60; color: white; text-decoration: none; border-radius: 5px;">
                Ladda ner PDF
            </a>
            <a href="?action=email" class="button" style="padding: 10px 20px; background: #9b59b6; color: white; text-decoration: none; border-radius: 5px;">
                Skicka via e-post
            </a>
        </div>
        
        <h2>Vad testas?</h2>
        <ul>
            <li>HTML till PDF-konvertering</li>
            <li>CSS-styling (färger, tabeller, boxar)</li>
            <li>Svenska tecken (ÅÄÖ)</li>
            <li>Visa i webbläsare vs ladda ner</li>
            <li>Skicka PDF som e-postbilaga</li>
        </ul>
    </div>
</body>
</html>