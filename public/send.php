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

$logoUrl = "https://geniovisual.cloud/assets/logo-onqBbdQx.png";

$htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body style="margin:0;padding:0;background-color:#f4f4f5;font-family:'Inter','Segoe UI',Arial,sans-serif;">

  <!-- Fundo branco -->
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4f4f5;">
    <tr><td style="padding:40px 16px;" align="center">

      <!-- Logo no topo, fora do card -->
      <table role="presentation" width="580" cellspacing="0" cellpadding="0" style="max-width:580px;width:100%;">
        <tr><td align="center" style="padding:0 0 28px;">
          <img src="{$logoUrl}" alt="Genio Visual" width="120" style="display:block;width:120px;height:auto;" />
        </td></tr>
      </table>

      <!-- Card escuro arredondado -->
      <table role="presentation" width="580" cellspacing="0" cellpadding="0" style="max-width:580px;width:100%;border-radius:24px;overflow:hidden;">

        <!-- Barra gradiente topo com efeito de movimento -->
        <tr><td style="height:4px;background:linear-gradient(90deg,#7c3aed,#3b82f6,#00e5ff,#3b82f6,#7c3aed);background-size:200% 100%;border-radius:24px 24px 0 0;"></td></tr>

        <!-- Header com fundo sutil de particulas -->
        <tr><td style="padding:36px 40px 28px;background-color:#0a0a0a;background-image:radial-gradient(circle at 15% 50%,rgba(124,58,237,0.08) 0%,transparent 50%),radial-gradient(circle at 85% 30%,rgba(0,229,255,0.06) 0%,transparent 50%);text-align:center;">
          <p style="margin:0;font-family:'Space Grotesk',sans-serif;font-size:13px;font-weight:600;letter-spacing:5px;color:#7c3aed;text-transform:uppercase;">
            Genio Visual
          </p>
          <p style="margin:4px 0 0;font-size:11px;letter-spacing:2px;color:#555;text-transform:uppercase;">
            OOH Premium &bull; Goiania/GO
          </p>
        </td></tr>

        <!-- Corpo principal -->
        <tr><td style="padding:40px 40px 20px;background-color:#0a0a0a;">

          <!-- Saudacao com gradiente lateral decorativo -->
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 28px;">
            <tr>
              <td style="width:4px;background:linear-gradient(180deg,#7c3aed,#3b82f6,#00e5ff);border-radius:4px;"></td>
              <td style="padding:0 0 0 20px;">
                <p style="margin:0;font-family:'Space Grotesk',sans-serif;font-size:28px;font-weight:700;color:#ffffff;line-height:1.2;">
                  Ola, {$firstName}!
                </p>
                <p style="margin:6px 0 0;font-size:14px;color:#888;">
                  Que bom ter voce por aqui.
                </p>
              </td>
            </tr>
          </table>

          <p style="margin:0 0 14px;font-size:15px;line-height:1.8;color:#b0b0b0;">
            Recebemos sua solicitacao de proposta e estamos muito felizes com o seu interesse em anunciar no maior painel de LED de Goiania.
          </p>

          <p style="margin:0 0 32px;font-size:15px;line-height:1.8;color:#b0b0b0;">
            Nossa equipe ja esta preparando a melhor proposta para voce. Em breve entraremos em contato com todos os detalhes.
          </p>

        </td></tr>

        <!-- Card resumo dentro do card principal -->
        <tr><td style="padding:0 40px 32px;background-color:#0a0a0a;">
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-radius:16px;overflow:hidden;">
            <!-- Borda gradiente do card interno -->
            <tr><td style="height:2px;background:linear-gradient(90deg,#7c3aed,#3b82f6,#00e5ff);"></td></tr>
            <tr><td style="padding:24px 28px;background-color:#111111;">
              <p style="margin:0 0 16px;font-family:'Space Grotesk',sans-serif;font-size:11px;font-weight:600;letter-spacing:2px;color:#00e5ff;text-transform:uppercase;">
                Resumo do pedido
              </p>
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                <tr>
                  <td style="padding:10px 0;border-bottom:1px solid #1c1c1c;color:#666;font-size:13px;width:90px;">Plano</td>
                  <td style="padding:10px 0;border-bottom:1px solid #1c1c1c;color:#fff;font-size:14px;font-weight:600;">{$plano}</td>
                </tr>
                <tr>
                  <td style="padding:10px 0;border-bottom:1px solid #1c1c1c;color:#666;font-size:13px;">Empresa</td>
                  <td style="padding:10px 0;border-bottom:1px solid #1c1c1c;color:#fff;font-size:14px;">{$empresa}</td>
                </tr>
                <tr>
                  <td style="padding:10px 0;color:#666;font-size:13px;">E-mail</td>
                  <td style="padding:10px 0;color:#ccc;font-size:14px;">{$email}</td>
                </tr>
              </table>
            </td></tr>
          </table>
        </td></tr>

        <!-- Secao WhatsApp com fundo gradiente sutil -->
        <tr><td style="padding:0 40px 40px;background-color:#0a0a0a;">
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-radius:16px;overflow:hidden;">
            <tr><td style="padding:32px;background-image:radial-gradient(circle at 30% 50%,rgba(124,58,237,0.1) 0%,transparent 60%),radial-gradient(circle at 70% 50%,rgba(0,229,255,0.08) 0%,transparent 60%);background-color:#0f0f0f;text-align:center;">

              <p style="margin:0 0 6px;font-family:'Space Grotesk',sans-serif;font-size:20px;font-weight:700;color:#fff;">
                Tem pressa?
              </p>
              <p style="margin:0 0 24px;font-size:14px;color:#888;line-height:1.6;">
                Fale direto com nossa equipe agora mesmo.
              </p>

              <!-- Botao com gradiente -->
              <table role="presentation" cellspacing="0" cellpadding="0" align="center">
                <tr><td style="border-radius:14px;background:linear-gradient(135deg,#7c3aed,#3b82f6,#00e5ff);background-size:200% 200%;">
                  <a href="{$waLink}" target="_blank" style="display:inline-block;padding:16px 44px;font-family:'Space Grotesk',sans-serif;font-size:15px;font-weight:700;color:#fff;text-decoration:none;letter-spacing:0.5px;">
                    Chamar no WhatsApp
                  </a>
                </td></tr>
              </table>

              <p style="margin:16px 0 0;font-size:13px;color:#555;letter-spacing:1px;">
                +55 21 99595-2526
              </p>

            </td></tr>
          </table>
        </td></tr>

        <!-- Footer dentro do card -->
        <tr><td style="padding:24px 40px;background-color:#080808;text-align:center;border-radius:0 0 24px 24px;">
          <p style="margin:0 0 4px;font-size:12px;color:#444;">
            Genio Visual &bull; Paineis de LED em Goiania/GO
          </p>
          <p style="margin:0;">
            <a href="https://geniovisual.cloud" style="font-size:12px;color:#3b82f6;text-decoration:none;">geniovisual.cloud</a>
          </p>
        </td></tr>

      </table>

      <!-- Texto legal fora do card -->
      <table role="presentation" width="580" cellspacing="0" cellpadding="0" style="max-width:580px;width:100%;">
        <tr><td style="padding:24px 0 0;text-align:center;">
          <p style="margin:0;font-size:11px;color:#999;">
            Voce recebeu este e-mail porque solicitou uma proposta em geniovisual.cloud
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
