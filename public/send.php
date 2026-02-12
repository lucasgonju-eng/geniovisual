<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
  exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
  exit;
}

$name     = trim($data['nome'] ?? '');
$email    = trim($data['email'] ?? '');
$whatsapp = trim($data['whatsapp'] ?? '');
$empresa  = trim($data['empresa'] ?? 'Nao informado');
$plano    = trim($data['plano'] ?? 'Nao informado');
$mensagem = trim($data['mensagem'] ?? 'Nao informado');
$subjectBase = trim($data['subject'] ?? 'Solicitacao de proposta');
$subject = "{$subjectBase} (WhatsApp: {$whatsapp})";

if ($name === '' || $email === '' || $whatsapp === '') {
  http_response_code(422);
  echo json_encode(['ok' => false, 'error' => 'Missing required fields']);
  exit;
}

// --- CRM: salvar lead em arquivo JSON ---
$crmDir = __DIR__ . '/crm-data';
if (!is_dir($crmDir)) {
  mkdir($crmDir, 0755, true);
}

$crmFile = $crmDir . '/leads.json';
$leads = [];
if (file_exists($crmFile)) {
  $content = file_get_contents($crmFile);
  $leads = json_decode($content, true) ?: [];
}

$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
// pegar apenas o primeiro IP se houver vários
if (strpos($ip, ',') !== false) {
  $ip = trim(explode(',', $ip)[0]);
}

$lead = [
  'id'        => uniqid('lead_'),
  'nome'      => $name,
  'email'     => $email,
  'whatsapp'  => $whatsapp,
  'empresa'   => $empresa,
  'plano'     => $plano,
  'mensagem'  => $mensagem,
  'ip'        => $ip,
  'data_hora' => date('Y-m-d H:i:s'),
  'timezone'  => 'America/Sao_Paulo',
];

$leads[] = $lead;
file_put_contents($crmFile, json_encode($leads, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// --- Enviar e-mail ---
$to = 'lucasgonju@gmail.com';
$from = 'contato@geniovisual.cloud';

$body = "=== Nova solicitação de proposta ===\n\n"
      . "Nome: {$name}\n"
      . "E-mail: {$email}\n"
      . "WhatsApp: {$whatsapp}\n"
      . "Empresa: {$empresa}\n"
      . "Plano: {$plano}\n"
      . "Mensagem: {$mensagem}\n"
      . "IP: {$ip}\n"
      . "Data/Hora: {$lead['data_hora']}\n";

$headers = "MIME-Version: 1.0\r\n"
         . "Content-Type: text/plain; charset=UTF-8\r\n"
         . "From: Genio Visual <{$from}>\r\n"
         . "Reply-To: {$email}\r\n"
         . "X-Lead-WhatsApp: {$whatsapp}\r\n";

mail($to, $subject, $body, $headers);

echo json_encode(['ok' => true]);
