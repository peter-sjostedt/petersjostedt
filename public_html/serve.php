<?php
/**
 * Filservering - Endpoint för säker filåtkomst
 *
 * Användning:
 * - Visa fil: /serve.php?id=47
 * - Ladda ner: /serve.php?id=47&download=1
 */

require_once __DIR__ . '/includes/config.php';

$fileId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$download = isset($_GET['download']);

if ($download) {
    FileServe::download($fileId);
} else {
    FileServe::serve($fileId);
}
