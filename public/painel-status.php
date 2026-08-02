<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/painel.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow', true);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(
        ['ok' => false, 'error' => 'Método não permitido.'],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

echo json_encode(
    painel_calculate_status(painel_read_config()),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
