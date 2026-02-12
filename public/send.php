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

// --- Enviar e-mail interno (para a equipe) ---
$to = 'lucasgonju@gmail.com';
$from = 'contato@geniovisual.cloud';
$whatsappGenio = '5521995952526';

$bodyInterno = "=== Nova solicitação de proposta ===\n\n"
      . "Nome: {$name}\n"
      . "E-mail: {$email}\n"
      . "WhatsApp: {$whatsapp}\n"
      . "Empresa: {$empresa}\n"
      . "Plano: {$plano}\n"
      . "Mensagem: {$mensagem}\n"
      . "IP: {$ip}\n"
      . "Data/Hora: {$lead['data_hora']}\n";

$headersInterno = "MIME-Version: 1.0\r\n"
         . "Content-Type: text/plain; charset=UTF-8\r\n"
         . "From: Genio Visual <{$from}>\r\n"
         . "Reply-To: {$email}\r\n"
         . "X-Lead-WhatsApp: {$whatsapp}\r\n";

mail($to, $subject, $bodyInterno, $headersInterno);

// --- Enviar e-mail HTML para o cliente ---
$firstName = explode(' ', $name)[0];
$waLink = "https://wa.me/{$whatsappGenio}?text=" . rawurlencode("Olá! Sou {$name}, acabei de solicitar uma proposta pelo site.");

$htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background-color:#0a0a0a;font-family:'Segoe UI',Arial,sans-serif;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#0a0a0a;padding:40px 0;">
    <tr><td align="center">
      <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width:600px;width:100%;">

        <!-- Logo / Header -->
        <tr><td style="padding:30px 40px 20px;text-align:center;border-bottom:1px solid #1a1a2e;">
          <h1 style="margin:0;font-size:28px;letter-spacing:3px;">
            <span style="background:linear-gradient(135deg,#00e5ff,#536dfe);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">GÊNIO VISUAL</span>
          </h1>
          <p style="margin:4px 0 0;color:#666;font-size:12px;letter-spacing:2px;">OOH Premium • Goiânia/GO</p>
        </td></tr>

        <!-- Corpo -->
        <tr><td style="padding:40px;background-color:#111;border-left:1px solid #1a1a2e;border-right:1px solid #1a1a2e;">
          <h2 style="margin:0 0 20px;color:#fff;font-size:22px;">Olá, {$firstName}! 👋</h2>
          <p style="color:#ccc;font-size:15px;line-height:1.7;margin:0 0 20px;">
            Recebemos sua solicitação de proposta e ficamos muito felizes com seu interesse!
          </p>
          <p style="color:#ccc;font-size:15px;line-height:1.7;margin:0 0 20px;">
            Nossa equipe já está analisando o seu pedido e <strong style="color:#00e5ff;">entraremos em contato em breve</strong> com todos os detalhes.
          </p>

          <!-- Resumo -->
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#0a0a1a;border:1px solid #1a1a2e;border-radius:12px;margin:24px 0;">
            <tr><td style="padding:20px;">
              <p style="margin:0 0 8px;color:#666;font-size:12px;text-transform:uppercase;letter-spacing:1px;">Resumo do pedido</p>
              <p style="margin:0 0 6px;color:#ccc;font-size:14px;">📋 <strong style="color:#fff;">Plano:</strong> {$plano}</p>
              <p style="margin:0;color:#ccc;font-size:14px;">🏢 <strong style="color:#fff;">Empresa:</strong> {$empresa}</p>
            </td></tr>
          </table>

          <!-- Destaque WhatsApp -->
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:30px 0 10px;">
            <tr><td style="padding:24px;background:linear-gradient(135deg,#0a2a1a,#0a1a2a);border:1px solid #1a3a2a;border-radius:12px;text-align:center;">
              <p style="margin:0 0 12px;color:#ccc;font-size:15px;">
                Tem pressa? Fale direto com a gente! 🚀
              </p>
              <a href="{$waLink}" target="_blank" style="display:inline-block;background-color:#25D366;color:#fff;font-weight:bold;font-size:15px;text-decoration:none;padding:14px 32px;border-radius:50px;">
                💬 Chamar no WhatsApp
              </a>
              <p style="margin:12px 0 0;color:#888;font-size:13px;">
                +55 21 99595-2526
              </p>
            </td></tr>
          </table>
        </td></tr>

        <!-- Footer -->
        <tr><td style="padding:24px 40px;text-align:center;border-top:1px solid #1a1a2e;">
          <p style="margin:0 0 4px;color:#555;font-size:12px;">Gênio Visual • Painéis de LED em Goiânia/GO</p>
          <p style="margin:0;color:#444;font-size:11px;">
            <a href="https://geniovisual.cloud" style="color:#536dfe;text-decoration:none;">geniovisual.cloud</a>
          </p>
        </td></tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

$headersCliente = "MIME-Version: 1.0\r\n"
  . "Content-Type: text/html; charset=UTF-8\r\n"
  . "From: Genio Visual <{$from}>\r\n"
  . "Reply-To: {$from}\r\n";

$subjectCliente = "Recebemos sua solicitação, {$firstName}! ✨ - Gênio Visual";

mail($email, $subjectCliente, $htmlBody, $headersCliente);

echo json_encode(['ok' => true]);
