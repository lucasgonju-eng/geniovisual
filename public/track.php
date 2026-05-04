<?php
require_once dirname(__DIR__) . '/private/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://geniovisual.cloud');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('X-Robots-Tag: noindex, nofollow', true);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json_response(['ok' => false, 'error' => 'Método não permitido.'], 405);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
  json_response(['ok' => false, 'error' => 'JSON inválido.'], 400);
}

date_default_timezone_set('America/Sao_Paulo');

$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
if (strpos($ip, ',') !== false) {
  $ip = trim(explode(',', $ip)[0]);
}

$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Detectar dispositivo
$device = 'desktop';
if (preg_match('/Mobile|Android|iPhone|iPad/i', $ua)) {
  $device = preg_match('/iPad|Tablet/i', $ua) ? 'tablet' : 'mobile';
}

// Detectar navegador
$browser = 'outro';
if (preg_match('/Chrome/i', $ua) && !preg_match('/Edge|OPR/i', $ua)) $browser = 'Chrome';
elseif (preg_match('/Safari/i', $ua) && !preg_match('/Chrome/i', $ua)) $browser = 'Safari';
elseif (preg_match('/Firefox/i', $ua)) $browser = 'Firefox';
elseif (preg_match('/Edge/i', $ua)) $browser = 'Edge';
elseif (preg_match('/OPR|Opera/i', $ua)) $browser = 'Opera';

$visit = [
  'page'      => trim($data['page'] ?? '/'),
  'referrer'  => trim($data['referrer'] ?? ''),
  'event'     => trim($data['event'] ?? 'pageview'),
  'device'    => $device,
  'browser'   => $browser,
  'ip'        => $ip,
  'timestamp' => date('Y-m-d H:i:s'),
  'date'      => date('Y-m-d'),
];

append_json_record(__DIR__ . '/crm-data/analytics.json', $visit, 10000);

json_response(['ok' => true]);
