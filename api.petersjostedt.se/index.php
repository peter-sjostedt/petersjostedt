<?php
header('Content-Type: application/json; charset=utf-8');

// CORS-inställningar (justera efter behov)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Hantera preflight-request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Enkel routing
$request_uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Grundläggande API-svar
$response = [
    'status' => 'ok',
    'message' => 'Välkommen till API:et',
    'version' => '1.0.0',
    'timestamp' => date('c')
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
