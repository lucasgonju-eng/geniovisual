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
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body style="margin:0;padding:0;background-color:#050505;font-family:'Inter','Segoe UI',Arial,sans-serif;color:#f7f7f7;">

  <!-- Wrapper -->
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#050505;">
    <tr><td style="padding:48px 16px;" align="center">

      <!-- Container principal -->
      <table role="presentation" width="580" cellspacing="0" cellpadding="0" style="max-width:580px;width:100%;">

        <!-- Barra gradiente topo -->
        <tr><td style="height:3px;background:linear-gradient(90deg,#7c3aed,#3b82f6,#00e5ff);border-radius:12px 12px 0 0;"></td></tr>

        <!-- Header -->
        <tr><td style="padding:40px 40px 32px;background-color:#0d0d0d;text-align:center;">
          <p style="margin:0;font-family:'Space Grotesk','Inter',sans-serif;font-size:24px;font-weight:700;letter-spacing:4px;color:#fff;">
            GENIO VISUAL
          </p>
          <p style="margin:6px 0 0;font-size:11px;letter-spacing:3px;color:#555;text-transform:uppercase;">
            OOH Premium &bull; Goiania/GO
          </p>
        </td></tr>

        <!-- Linha separadora gradiente sutil -->
        <tr><td style="height:1px;background:linear-gradient(90deg,transparent,#7c3aed44,#3b82f644,#00e5ff44,transparent);"></td></tr>

        <!-- Corpo -->
        <tr><td style="padding:44px 40px 36px;background-color:#0d0d0d;">

          <!-- Saudacao -->
          <p style="margin:0 0 24px;font-family:'Space Grotesk','Inter',sans-serif;font-size:26px;font-weight:700;color:#fff;line-height:1.3;">
            Ola, {$firstName}!
          </p>

          <p style="margin:0 0 16px;font-size:15px;line-height:1.8;color:#b3b3b3;">
            Recebemos sua solicitacao de proposta e ficamos muito felizes com seu interesse em anunciar no maior painel de LED de Goiania.
          </p>

          <p style="margin:0 0 32px;font-size:15px;line-height:1.8;color:#b3b3b3;">
            Nossa equipe ja esta preparando a melhor proposta para voce. Em breve entraremos em contato com todos os detalhes.
          </p>

          <!-- Card resumo -->
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 32px;">
            <tr><td style="padding:24px 28px;background-color:#111;border:1px solid #1a1a1a;border-radius:12px;">
              <p style="margin:0 0 16px;font-family:'Space Grotesk','Inter',sans-serif;font-size:11px;font-weight:600;letter-spacing:2px;color:#7c3aed;text-transform:uppercase;">
                Seu pedido
              </p>
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                <tr>
                  <td style="padding:8px 0;border-bottom:1px solid #1a1a1a;color:#666;font-size:13px;width:90px;">Plano</td>
                  <td style="padding:8px 0;border-bottom:1px solid #1a1a1a;color:#fff;font-size:14px;font-weight:600;">{$plano}</td>
                </tr>
                <tr>
                  <td style="padding:8px 0;border-bottom:1px solid #1a1a1a;color:#666;font-size:13px;">Empresa</td>
                  <td style="padding:8px 0;border-bottom:1px solid #1a1a1a;color:#fff;font-size:14px;">{$empresa}</td>
                </tr>
                <tr>
                  <td style="padding:8px 0;color:#666;font-size:13px;">E-mail</td>
                  <td style="padding:8px 0;color:#ccc;font-size:14px;">{$email}</td>
                </tr>
              </table>
            </td></tr>
          </table>

          <!-- Separador -->
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 32px;">
            <tr><td style="height:1px;background:linear-gradient(90deg,transparent,#1a1a1a,transparent);"></td></tr>
          </table>

          <!-- WhatsApp CTA -->
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
            <tr><td style="text-align:center;padding:0 0 8px;">
              <p style="margin:0 0 8px;font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:600;color:#fff;">
                Tem pressa?
              </p>
              <p style="margin:0 0 24px;font-size:14px;color:#888;">
                Fale direto com nossa equipe pelo WhatsApp.
              </p>

              <!-- Botao gradiente -->
              <table role="presentation" cellspacing="0" cellpadding="0" align="center">
                <tr><td style="border-radius:12px;background:linear-gradient(90deg,#7c3aed,#3b82f6);">
                  <a href="{$waLink}" target="_blank" style="display:inline-block;padding:16px 40px;font-family:'Space Grotesk','Inter',sans-serif;font-size:15px;font-weight:600;color:#fff;text-decoration:none;letter-spacing:0.5px;">
                    Chamar no WhatsApp
                  </a>
                </td></tr>
              </table>

              <p style="margin:20px 0 0;font-size:13px;color:#555;">
                +55 21 99595-2526
              </p>
            </td></tr>
          </table>

        </td></tr>

        <!-- Linha separadora gradiente sutil -->
        <tr><td style="height:1px;background:linear-gradient(90deg,transparent,#7c3aed44,#3b82f644,#00e5ff44,transparent);"></td></tr>

        <!-- Footer -->
        <tr><td style="padding:28px 40px;background-color:#0d0d0d;text-align:center;border-radius:0 0 12px 12px;">
          <p style="margin:0 0 4px;font-size:12px;color:#444;">
            Genio Visual &bull; Paineis de LED em Goiania/GO
          </p>
          <p style="margin:0;">
            <a href="https://geniovisual.cloud" style="font-size:12px;color:#3b82f6;text-decoration:none;">geniovisual.cloud</a>
          </p>
        </td></tr>

        <!-- Barra gradiente inferior -->
        <tr><td style="height:3px;background:linear-gradient(90deg,#7c3aed,#3b82f6,#00e5ff);border-radius:0 0 12px 12px;"></td></tr>

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
