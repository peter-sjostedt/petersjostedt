<?php
/**
 * 400 - Felaktig begäran
 */
http_response_code(400);
require_once __DIR__ . '/../public_html/includes/config.php';

$requestUri = $_SERVER['REDIRECT_URL'] ?? $_SERVER['REQUEST_URI'] ?? t('common.unknown');
$referer = $_SERVER['HTTP_REFERER'] ?? t('common.unknown');
?>
<!DOCTYPE html>
<html lang="<?= Language::getInstance()->current() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('error.400.title') ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            max-width: 600px;
            margin: 100px auto;
            padding: 2rem;
            text-align: center;
            background: #f5f5f5;
        }
        h1 {
            color: #e94560;
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        p {
            color: #333;
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        .info {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            margin: 2rem 0;
            text-align: left;
            word-break: break-all;
        }
        a {
            display: inline-block;
            background: #16213e;
            color: white;
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 1rem;
        }
        a:hover {
            background: #0f3460;
        }
    </style>
</head>
<body>
    <h1>400</h1>
    <h2><?= t('error.400.heading') ?></h2>
    <p><?= t('error.400.message') ?></p>
    <div class="info">
        <p><strong><?= t('error.requested_url') ?>:</strong> <?= htmlspecialchars($requestUri) ?></p>
        <p><strong><?= t('error.came_from') ?>:</strong> <?= htmlspecialchars($referer) ?></p>
    </div>
    <a href="/"><?= t('error.back_home') ?></a>
</body>
</html>