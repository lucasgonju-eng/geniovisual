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

$name = trim($data['nome'] ?? '');
$whatsapp = trim($data['whatsapp'] ?? '');
$empresa = trim($data['empresa'] ?? 'Nao informado');
$plano = trim($data['plano'] ?? 'Nao informado');
$mensagem = trim($data['mensagem'] ?? 'Nao informado');
$subject = trim($data['subject'] ?? 'Solicitacao de proposta');

if ($name === '' || $whatsapp === '') {
  http_response_code(422);
  echo json_encode(['ok' => false, 'error' => 'Missing required fields']);
  exit;
}

$to = 'contato@geniovisual.cloud';
$body = "Nome: {$name}\n"
      . "WhatsApp: {$whatsapp}\n"
      . "Empresa: {$empresa}\n"
      . "Plano: {$plano}\n"
      . "Mensagem: {$mensagem}\n";

$headers = "MIME-Version: 1.0\r\n"
         . "Content-Type: text/plain; charset=UTF-8\r\n"
         . "From: contato@geniovisual.cloud\r\n"
         . "Reply-To: contato@geniovisual.cloud\r\n"
         . "Bcc: lucasgonju@gmail.com\r\n";

$sent = mail($to, $subject, $body, $headers);

if (!$sent) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Failed to send email']);
  exit;
}

echo json_encode(['ok' => true]);
