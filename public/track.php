<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false]);
  exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(['ok' => false]);
  exit;
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

$dir = __DIR__ . '/crm-data';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$file = $dir . '/analytics.json';
$visits = [];
if (file_exists($file)) {
  $visits = json_decode(file_get_contents($file), true) ?: [];
}

$visits[] = $visit;

// Manter apenas últimos 90 dias
$cutoff = date('Y-m-d', strtotime('-90 days'));
$visits = array_values(array_filter($visits, fn($v) => ($v['date'] ?? '') >= $cutoff));

file_put_contents($file, json_encode($visits, JSON_UNESCAPED_UNICODE));

echo json_encode(['ok' => true]);
