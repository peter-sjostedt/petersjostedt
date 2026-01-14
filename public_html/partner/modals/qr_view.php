<?php
/**
 * Modal - Visa QR-kod för artikel
 */

require_once __DIR__ . '/../../includes/config.php';

Session::start();

if (!Session::isLoggedIn() || !Session::isOrgAdmin()) {
    echo '<div class="modal-body"><p>' . t('error.unauthorized') . '</p></div>';
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$orgId = Session::getOrganizationId();

if (!$id || !$orgId) {
    echo '<div class="modal-body"><p>ID saknas</p></div>';
    exit;
}

$db = Database::getInstance()->getPdo();

// Hämta artikel
$stmt = $db->prepare("SELECT * FROM articles WHERE id = ? AND organization_id = ?");
$stmt->execute([$id, $orgId]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$article) {
    echo '<div class="modal-body"><p>Artikeln hittades inte</p></div>';
    exit;
}

// Hämta organisation
$orgModel = new Organization();
$org = $orgModel->findById($orgId);

// Hämta article_schema för sortering
$articleSchema = [];
if (!empty($org['article_schema'])) {
    if (is_string($org['article_schema'])) {
        $articleSchema = json_decode($org['article_schema'], true) ?: [];
    } else {
        $articleSchema = $org['article_schema'];
    }
    usort($articleSchema, fn($a, $b) => ((int)($a['sort_order'] ?? 0)) - ((int)($b['sort_order'] ?? 0)));
}

function fieldKey(string $label): string
{
    $key = mb_strtolower($label);
    $key = str_replace(['å', 'ä', 'ö'], ['a', 'a', 'o'], $key);
    $key = preg_replace('/[^a-z0-9]/', '_', $key);
    $key = preg_replace('/_+/', '_', $key);
    return trim($key, '_');
}

// QR-data
$qrData = [
    'type' => 'sku',
    'org_id' => $orgId,
    'sku' => $article['sku']
];

// Text för visning
$title = 'SKU: ' . $article['sku'];
$lines = [
    'Org: ' . $orgId . ' (' . ($org['name'] ?? '-') . ')'
];

$articleData = json_decode($article['data'] ?? '{}', true) ?: [];
if (!empty($articleData) && !empty($articleSchema)) {
    $sortedValues = [];
    foreach ($articleSchema as $field) {
        $key = fieldKey($field['label']);
        if (!empty($articleData[$key])) {
            $sortedValues[] = $articleData[$key];
        }
    }
    if (!empty($sortedValues)) {
        $lines[] = implode(' | ', $sortedValues);
    }
}
$subtitle = implode("\n", $lines);
$filename = 'SKU_' . $article['sku'];

$qrJson = json_encode($qrData, JSON_UNESCAPED_UNICODE);
?>

<div class="modal-header">
    <h2>QR-kod</h2>
    <button class="modal-close" data-modal-close>&times;</button>
</div>

<div class="modal-body" style="text-align: center;">
    <div id="qr-code" style="display: inline-block; padding: 20px; background: white;"></div>

    <p style="margin-top: 1rem; font-size: 1.1rem;">
        <strong><?= htmlspecialchars($title) ?></strong>
    </p>

    <?php if (!empty($subtitle)): ?>
    <p class="text-muted" style="white-space: pre-line;"><?= htmlspecialchars($subtitle) ?></p>
    <?php endif; ?>
</div>

<div class="modal-footer">
    <button type="button" class="btn" data-modal-close>Stäng</button>
    <button type="button" class="btn btn-primary" data-download-qr>Ladda ner</button>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function() {
    const qrData = <?= $qrJson ?>;
    const title = <?= json_encode($title) ?>;
    const subtitle = <?= json_encode($subtitle) ?>;
    const filename = <?= json_encode($filename) ?>;

    // Skapa QR-kod
    const qrContainer = document.getElementById('qr-code');
    const qr = new QRCode(qrContainer, {
        text: JSON.stringify(qrData),
        width: 200,
        height: 200,
        colorDark: '#000000',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M
    });

    // Ladda ner funktion
    window.downloadQR = function() {
        const canvas = qrContainer.querySelector('canvas');
        if (!canvas) return;

        // Beräkna storlek
        const padding = 20;
        const subtitleLines = subtitle ? subtitle.split('\n') : [];
        const textHeight = 40 + (subtitleLines.length * 16);

        // Skapa ny canvas med text
        const newCanvas = document.createElement('canvas');
        newCanvas.width = canvas.width + padding * 2;
        newCanvas.height = canvas.height + padding * 2 + textHeight;

        const ctx = newCanvas.getContext('2d');

        // Vit bakgrund
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, newCanvas.width, newCanvas.height);

        // QR-kod
        ctx.drawImage(canvas, padding, padding);

        // Titel
        ctx.fillStyle = '#000000';
        ctx.font = 'bold 16px Arial';
        ctx.textAlign = 'center';
        ctx.fillText(title, newCanvas.width / 2, canvas.height + padding + 25);

        // Subtitle (flera rader)
        if (subtitle) {
            ctx.font = '12px Arial';
            ctx.fillStyle = '#666666';
            subtitleLines.forEach((line, i) => {
                ctx.fillText(line, newCanvas.width / 2, canvas.height + padding + 45 + (i * 16));
            });
        }

        // Ladda ner
        const link = document.createElement('a');
        link.download = filename.replace(/[^a-zA-Z0-9-_]/g, '_') + '.png';
        link.href = newCanvas.toDataURL('image/png');
        link.click();
    };
})();
</script>
