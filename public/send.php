<?php
$bootstrapPaths = [
  dirname(__DIR__) . '/private/bootstrap.php',
  __DIR__ . '/private/bootstrap.php',
];

$bootstrapLoaded = false;
foreach ($bootstrapPaths as $bootstrapPath) {
  if (is_file($bootstrapPath)) {
    require_once $bootstrapPath;
    $bootstrapLoaded = true;
    break;
  }
}

if (!$bootstrapLoaded) {
  http_response_code(500);
  exit('Bootstrap não encontrado.');
}

require_once __DIR__ . '/lib/painel.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow', true);
date_default_timezone_set('America/Sao_Paulo');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json_response(['ok' => false, 'error' => 'Método não permitido.'], 405);
}

$contentType = strtolower(trim(explode(';', (string) ($_SERVER['CONTENT_TYPE'] ?? ''))[0]));
if ($contentType !== 'application/json') {
  json_response(['ok' => false, 'error' => 'Content-Type deve ser application/json.'], 415);
}

$maxBodyBytes = 16 * 1024;
$contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > $maxBodyBytes) {
  json_response(['ok' => false, 'error' => 'Corpo da requisição muito grande.'], 413);
}

$raw = file_get_contents('php://input');
if ($raw === false || strlen($raw) > $maxBodyBytes) {
  json_response(['ok' => false, 'error' => 'Corpo da requisição muito grande.'], 413);
}

$data = json_decode($raw, true);

if (!is_array($data)) {
  json_response(['ok' => false, 'error' => 'Corpo da requisição inválido.'], 400);
}

if (trim((string) ($data['website'] ?? '')) !== '') {
  json_response(['ok' => true, 'message' => 'Proposta enviada com sucesso.']);
}

$readField = static function (
  array $payload,
  string $key,
  int $maxLength,
  string $default = '',
  bool $singleLine = true
): string {
  $value = trim((string) ($payload[$key] ?? $default));
  if ($singleLine) {
    $value = str_replace(["\r", "\n"], ' ', $value);
  }
  if (mb_strlen($value, 'UTF-8') > $maxLength) {
    json_response(['ok' => false, 'error' => "Campo {$key} excede o limite permitido."], 422);
  }
  return $value;
};

$name = $readField($data, 'nome', 120);
$email = $readField($data, 'email', 255);
$whatsappRaw = $readField($data, 'whatsapp', 255);
$whatsapp = preg_replace('/\D+/', '', $whatsappRaw) ?? '';
$empresa = $readField($data, 'empresa', 120, 'Não informado');
$segmento = $readField($data, 'segmento', 120);
$segmentoSlug = painel_slugify($readField($data, 'segmento_slug', 80, $segmento));
$plano = $readField($data, 'plano', 60, 'Não informado');
$mensagem = $readField($data, 'mensagem', 2000, 'Não informado', false);
$subjectBase = $readField($data, 'subject', 255, 'Solicitação de proposta');

$utmSource = $readField($data, 'utm_source', 255);
$utmMedium = $readField($data, 'utm_medium', 255);
$utmCampaign = $readField($data, 'utm_campaign', 255);
$utmContent = $readField($data, 'utm_content', 255);
$utmTerm = $readField($data, 'utm_term', 255);
$gclid = $readField($data, 'gclid', 255);
$fbclid = $readField($data, 'fbclid', 255);
$liFatId = $readField($data, 'li_fat_id', 255);
$landingPath = $readField($data, 'landing_path', 255);
$referrer = $readField($data, 'referrer', 255);
$pageUrl = $readField($data, 'page_url', 255);
$consent = ($data['consent'] ?? false) === true;
$consentText = $readField($data, 'consent_text', 255);
$consentAt = $readField($data, 'consent_at', 255);

if ($name === '' || $email === '' || $whatsapp === '' || $segmento === '') {
  json_response(['ok' => false, 'error' => 'Preencha nome, e-mail, WhatsApp e segmento.'], 422);
}

if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
  json_response(['ok' => false, 'error' => 'Informe um e-mail válido.'], 422);
}

if (strlen($whatsapp) < 10 || strlen($whatsapp) > 13) {
  json_response(['ok' => false, 'error' => 'Informe um WhatsApp válido.'], 422);
}

$panelConfig = painel_read_config();
$registeredSegment = painel_find_segment($panelConfig, $segmentoSlug);
$registeredPlan = painel_find_plan($panelConfig, $plano);
$planHasExclusivity = $registeredPlan !== null
  ? (bool) $registeredPlan['exclusividade']
  : mb_strtolower(trim($plano), 'UTF-8') !== 'mensal';
$listaEspera = !empty($registeredSegment['ocupado'])
  && $planHasExclusivity;
if ($registeredSegment !== null) {
  $segmento = (string) $registeredSegment['nome'];
  $segmentoSlug = (string) $registeredSegment['slug'];
}
if ($registeredPlan !== null) {
  $plano = (string) $registeredPlan['nome'];
}
$precoVigente = $registeredPlan !== null ? (int) $registeredPlan['preco_efetivo'] : null;
$precoCheio = $registeredPlan !== null ? (int) $registeredPlan['preco'] : null;
$emCampanha = $registeredPlan !== null && (bool) $registeredPlan['em_campanha'];
$campanhaRotulo = $emCampanha ? (string) ($registeredPlan['rotulo'] ?? '') : '';
$campanhaValidade = $emCampanha ? (string) ($registeredPlan['validade'] ?? '') : '';
$precoVigenteLabel = $precoVigente !== null
  ? 'R$ ' . number_format($precoVigente, 0, ',', '.') . '/mês'
  : 'Sob consulta';

$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
if (strpos($ip, ',') !== false) {
  $ip = trim(explode(',', $ip)[0]);
}

$crmDataDir = getenv('GENIO_CRM_DATA_DIR') ?: __DIR__ . '/crm-data';
if (!is_dir($crmDataDir) && !mkdir($crmDataDir, 0750, true) && !is_dir($crmDataDir)) {
  json_response(['ok' => false, 'error' => 'Não foi possível processar a solicitação.'], 500);
}

$truncateIp = static function (string $value): string {
  if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
    $parts = explode('.', $value);
    return "{$parts[0]}.{$parts[1]}.{$parts[2]}.x";
  }
  if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
    return implode(':', array_slice(explode(':', $value), 0, 4)) . '::';
  }
  return 'desconhecido';
};

$writeErrorLog = static function (string $type) use ($crmDataDir, $ip, $truncateIp): void {
  $entry = json_encode([
    'timestamp' => date(DATE_ATOM),
    'type' => $type,
    'ip' => $truncateIp((string) $ip),
  ], JSON_UNESCAPED_UNICODE);
  @file_put_contents($crmDataDir . '/errors.log', $entry . PHP_EOL, FILE_APPEND | LOCK_EX);
};

$leadsFile = $crmDataDir . '/leads.json';
$isDuplicate = static function (string $file, string $candidateEmail, string $candidateWhatsApp): bool {
  if (!is_file($file)) {
    return false;
  }
  $handle = fopen($file, 'r');
  if ($handle === false) {
    return false;
  }
  try {
    if (!flock($handle, LOCK_SH)) {
      return false;
    }
    $contents = stream_get_contents($handle);
    $leads = $contents ? json_decode($contents, true) : [];
    if (!is_array($leads)) {
      return false;
    }
    $cutoff = time() - 60;
    foreach (array_reverse($leads) as $existingLead) {
      $createdAt = strtotime((string) ($existingLead['data_hora'] ?? '')) ?: 0;
      if ($createdAt < $cutoff) {
        continue;
      }
      if (
        strcasecmp((string) ($existingLead['email'] ?? ''), $candidateEmail) === 0
        && (string) ($existingLead['whatsapp'] ?? '') === $candidateWhatsApp
      ) {
        return true;
      }
    }
    return false;
  } finally {
    flock($handle, LOCK_UN);
    fclose($handle);
  }
};

if ($isDuplicate($leadsFile, $email, $whatsapp)) {
  json_response(['ok' => true, 'message' => 'Proposta enviada com sucesso.']);
}

$consumeRateLimit = static function (string $file, string $clientIp): bool {
  $handle = fopen($file, 'c+');
  if ($handle === false) {
    throw new RuntimeException('Não foi possível abrir o controle de envios.');
  }
  try {
    if (!flock($handle, LOCK_EX)) {
      throw new RuntimeException('Não foi possível bloquear o controle de envios.');
    }
    $contents = stream_get_contents($handle);
    $records = $contents ? json_decode($contents, true) : [];
    if (!is_array($records)) {
      $records = [];
    }
    $now = time();
    $cutoff = $now - 3600;
    $key = hash('sha256', $clientIp);
    foreach ($records as $recordKey => $timestamps) {
      $records[$recordKey] = array_values(array_filter(
        is_array($timestamps) ? $timestamps : [],
        static fn($timestamp): bool => is_int($timestamp) && $timestamp >= $cutoff
      ));
      if ($records[$recordKey] === []) {
        unset($records[$recordKey]);
      }
    }
    $attempts = $records[$key] ?? [];
    $allowed = count($attempts) < 5;
    if ($allowed) {
      $attempts[] = $now;
      $records[$key] = $attempts;
    }
    rewind($handle);
    ftruncate($handle, 0);
    fwrite($handle, json_encode($records, JSON_PRETTY_PRINT));
    fflush($handle);
    return $allowed;
  } finally {
    flock($handle, LOCK_UN);
    fclose($handle);
  }
};

try {
  $rateLimitAllowed = $consumeRateLimit($crmDataDir . '/ratelimit.json', (string) $ip);
} catch (Throwable $error) {
  $writeErrorLog('rate_limit_storage_failed');
  json_response(['ok' => false, 'error' => 'Não foi possível processar a solicitação.'], 500);
}

if (!$rateLimitAllowed) {
  $writeErrorLog('rate_limit_exceeded');
  json_response(['ok' => false, 'error' => 'Limite de envios excedido. Tente novamente mais tarde.'], 429);
}

$waitlistSubject = $listaEspera ? '[LISTA DE ESPERA] ' : '';
$subject = "{$waitlistSubject}{$subjectBase} (WhatsApp: {$whatsapp})";
$leadId = uniqid('lead_');
$lead = [
  'id'        => $leadId,
  'nome'      => $name,
  'email'     => $email,
  'whatsapp'  => $whatsapp,
  'empresa'   => $empresa,
  'segmento'  => $segmento,
  'segmento_slug' => $segmentoSlug,
  'lista_espera' => $listaEspera,
  'plano'     => $plano,
  'preco_vigente' => $precoVigente,
  'preco_cheio' => $precoCheio,
  'em_campanha' => $emCampanha,
  'campanha_rotulo' => $campanhaRotulo,
  'campanha_validade' => $campanhaValidade,
  'mensagem'  => $mensagem,
  'utm_source' => $utmSource,
  'utm_medium' => $utmMedium,
  'utm_campaign' => $utmCampaign,
  'utm_content' => $utmContent,
  'utm_term' => $utmTerm,
  'gclid' => $gclid,
  'fbclid' => $fbclid,
  'li_fat_id' => $liFatId,
  'landing_path' => $landingPath,
  'referrer' => $referrer,
  'page_url' => $pageUrl,
  'consent' => $consent,
  'consent_text' => $consentText,
  'consent_at' => $consentAt,
  'ip'        => $ip,
  'data_hora' => date('Y-m-d H:i:s'),
  'timezone'  => 'America/Sao_Paulo',
  'email_status' => 'pending',
];

try {
  append_json_record($leadsFile, $lead, 5000);
} catch (Throwable $error) {
  $writeErrorLog('lead_write_failed');
  json_response(['ok' => false, 'error' => 'Não foi possível processar a solicitação.'], 500);
}

// --- Enviar e-mail interno (para a equipe) ---
$to = app_config('lead_recipient_email', '') ?: app_config('contact_email', 'contato@geniovisual.com.br');
$from = app_config('contact_email', 'contato@geniovisual.com.br');
$appUrl = rtrim((string) app_config('app_url', 'https://geniovisual.com.br'), '/');
$whatsappGenio = '5562995077995';

$bodyInterno = "=== Nova solicitação de proposta ===\n\n"
      . "Nome: {$name}\n"
      . "E-mail: {$email}\n"
      . "WhatsApp: {$whatsapp}\n"
      . "Empresa: {$empresa}\n"
      . "Segmento: {$segmento}\n"
      . "Lista de espera: " . ($listaEspera ? 'SIM' : 'NÃO') . "\n"
      . "Plano: {$plano}\n"
      . "Preço vigente: {$precoVigenteLabel}\n"
      . ($emCampanha ? "Campanha: {$campanhaRotulo} (até {$campanhaValidade})\n" : '')
      . "Mensagem: {$mensagem}\n"
      . "Origem: {$utmSource} / {$utmMedium} / {$utmCampaign}\n"
      . "IP: {$ip}\n"
      . "Data/Hora: {$lead['data_hora']}\n";

$headersInterno = "MIME-Version: 1.0\r\n"
         . "Content-Type: text/plain; charset=UTF-8\r\n"
         . "From: Gênio Visual <{$from}>\r\n"
         . "Reply-To: {$email}\r\n"
         . "X-Lead-WhatsApp: {$whatsapp}\r\n";

$mailDisabled = getenv('GENIO_DISABLE_MAIL') === '1';
$internalMailSent = $to !== '' && !$mailDisabled
  ? @mail($to, $subject, $bodyInterno, $headersInterno)
  : false;

// --- Enviar e-mail HTML para o cliente ---
$firstName = explode(' ', $name)[0];
$waLink = "https://wa.me/{$whatsappGenio}?text=" . rawurlencode("Olá! Sou {$name}, acabei de solicitar uma proposta pelo site.");

$logoUrl = "{$appUrl}/painel-anuncie.png";
$escapeHtml = static fn(string $value): string => htmlspecialchars(
  $value,
  ENT_QUOTES | ENT_SUBSTITUTE,
  'UTF-8'
);
$safeFirstName = $escapeHtml($firstName);
$safePlano = $escapeHtml($plano);
$safePrecoVigente = $escapeHtml($precoVigenteLabel);
$safeEmpresa = $escapeHtml($empresa);
$safeSegmento = $escapeHtml($segmento);
$safeEmail = $escapeHtml($email);
$safeWaLink = $escapeHtml($waLink);
$safeLogoUrl = $escapeHtml($logoUrl);
$safeAppUrl = $escapeHtml($appUrl);

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
          <img src="{$safeLogoUrl}" alt="Gênio Visual" width="120" style="display:block;width:120px;height:auto;" />
        </td></tr>
      </table>

      <!-- Card escuro arredondado -->
      <table role="presentation" width="580" cellspacing="0" cellpadding="0" style="max-width:580px;width:100%;border-radius:24px;overflow:hidden;">

        <!-- Barra gradiente topo com efeito de movimento -->
        <tr><td style="height:4px;background:linear-gradient(90deg,#7c3aed,#3b82f6,#00e5ff,#3b82f6,#7c3aed);background-size:200% 100%;border-radius:24px 24px 0 0;"></td></tr>

        <!-- Header com fundo sutil de particulas -->
        <tr><td style="padding:36px 40px 28px;background-color:#0a0a0a;text-align:center;">
          <p style="margin:0;font-family:'Space Grotesk',sans-serif;font-size:13px;font-weight:600;letter-spacing:5px;color:#7c3aed;text-transform:uppercase;">
            Gênio Visual
          </p>
          <p style="margin:4px 0 0;font-size:11px;letter-spacing:2px;color:#555;text-transform:uppercase;">
            OOH Premium &bull; Goiânia/GO
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
                  Olá, {$safeFirstName}!
                </p>
                <p style="margin:6px 0 0;font-size:14px;color:#888;">
                  Que bom ter você por aqui.
                </p>
              </td>
            </tr>
          </table>

          <p style="margin:0 0 14px;font-size:15px;line-height:1.8;color:#b0b0b0;">
            Recebemos sua solicitação de proposta para anunciar no painel de LED da Gênio Visual, em Goiânia.
          </p>

          <p style="margin:0 0 32px;font-size:15px;line-height:1.8;color:#b0b0b0;">
            Nossa equipe já está preparando a proposta para você. Em breve entraremos em contato com os detalhes.
          </p>

        </td></tr>

        <!-- Card resumo dentro do card principal -->
        <tr><td style="padding:0 40px 32px;background-color:#0a0a0a;">
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-radius:16px;overflow:hidden;">
            <!-- Borda gradiente do card interno -->
            <tr><td style="height:2px;background:linear-gradient(90deg,#7c3aed,#3b82f6,#00e5ff);"></td></tr>
            <tr><td style="padding:24px 28px;background-color:#0a0a0a;">
              <p style="margin:0 0 16px;font-family:'Space Grotesk',sans-serif;font-size:11px;font-weight:600;letter-spacing:2px;color:#00e5ff;text-transform:uppercase;">
                Resumo do pedido
              </p>
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                <tr>
                  <td style="padding:10px 0;border-bottom:1px solid #1c1c1c;color:#666;font-size:13px;width:90px;">Plano</td>
                  <td style="padding:10px 0;border-bottom:1px solid #1c1c1c;color:#fff;font-size:14px;font-weight:600;">{$safePlano}</td>
                </tr>
                <tr>
                  <td style="padding:10px 0;border-bottom:1px solid #1c1c1c;color:#666;font-size:13px;">Preço</td>
                  <td style="padding:10px 0;border-bottom:1px solid #1c1c1c;color:#fff;font-size:14px;font-weight:600;">{$safePrecoVigente}</td>
                </tr>
                <tr>
                  <td style="padding:10px 0;border-bottom:1px solid #1c1c1c;color:#666;font-size:13px;">Empresa</td>
                  <td style="padding:10px 0;border-bottom:1px solid #1c1c1c;color:#fff;font-size:14px;">{$safeEmpresa}</td>
                </tr>
                <tr>
                  <td style="padding:10px 0;border-bottom:1px solid #1c1c1c;color:#666;font-size:13px;">Segmento</td>
                  <td style="padding:10px 0;border-bottom:1px solid #1c1c1c;color:#fff;font-size:14px;">{$safeSegmento}</td>
                </tr>
                <tr>
                  <td style="padding:10px 0;color:#666;font-size:13px;">E-mail</td>
                  <td style="padding:10px 0;color:#ccc;font-size:14px;">{$safeEmail}</td>
                </tr>
              </table>
            </td></tr>
          </table>
        </td></tr>

        <!-- Secao WhatsApp com fundo gradiente sutil -->
        <tr><td style="padding:0 40px 40px;background-color:#0a0a0a;">
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-radius:16px;overflow:hidden;">
            <tr><td style="padding:32px;background-color:#0a0a0a;text-align:center;">

              <p style="margin:0 0 6px;font-family:'Space Grotesk',sans-serif;font-size:20px;font-weight:700;color:#fff;">
                Tem pressa?
              </p>
              <p style="margin:0 0 24px;font-size:14px;color:#888;line-height:1.6;">
                Fale direto com nossa equipe agora mesmo.
              </p>

              <!-- Botao com gradiente -->
              <table role="presentation" cellspacing="0" cellpadding="0" align="center">
                <tr><td style="border-radius:14px;background:linear-gradient(135deg,#7c3aed,#3b82f6,#00e5ff);background-size:200% 200%;">
                  <a href="{$safeWaLink}" target="_blank" style="display:inline-block;padding:16px 44px;font-family:'Space Grotesk',sans-serif;font-size:15px;font-weight:700;color:#fff;text-decoration:none;letter-spacing:0.5px;">
                    Chamar no WhatsApp
                  </a>
                </td></tr>
              </table>

              <p style="margin:16px 0 0;font-size:13px;color:#555;letter-spacing:1px;">
                (62) 99507-7995
              </p>

            </td></tr>
          </table>
        </td></tr>

        <!-- Footer dentro do card -->
        <tr><td style="padding:24px 40px;background-color:#0a0a0a;text-align:center;border-radius:0 0 24px 24px;">
          <p style="margin:0 0 4px;font-size:12px;color:#444;">
            Gênio Visual &bull; Painéis de LED em Goiânia/GO
          </p>
          <p style="margin:0;">
            <a href="{$safeAppUrl}" style="font-size:12px;color:#3b82f6;text-decoration:none;">geniovisual.com.br</a>
          </p>
        </td></tr>

      </table>

      <!-- Texto legal fora do card -->
      <table role="presentation" width="580" cellspacing="0" cellpadding="0" style="max-width:580px;width:100%;">
        <tr><td style="padding:24px 0 0;text-align:center;">
          <p style="margin:0;font-size:11px;color:#999;">
            Você recebeu este e-mail porque solicitou uma proposta em geniovisual.com.br
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
  . "From: Gênio Visual <{$from}>\r\n"
  . "Reply-To: {$from}\r\n";

$subjectCliente = "Recebemos sua solicitação, {$firstName}! - Gênio Visual";

$clientMailSent = !$mailDisabled
  ? @mail($email, $subjectCliente, $htmlBody, $headersCliente)
  : false;

if (!$internalMailSent) {
  $writeErrorLog('mail_internal_failed');
}
if (!$clientMailSent) {
  $writeErrorLog('mail_client_failed');
}

$emailStatus = $internalMailSent && $clientMailSent
  ? 'sent'
  : ($internalMailSent || $clientMailSent ? 'partial' : 'failed');

$updateEmailStatus = static function (string $file, string $id, string $status): void {
  $handle = fopen($file, 'c+');
  if ($handle === false) {
    throw new RuntimeException('Não foi possível atualizar o lead.');
  }
  try {
    if (!flock($handle, LOCK_EX)) {
      throw new RuntimeException('Não foi possível bloquear o arquivo de leads.');
    }
    $contents = stream_get_contents($handle);
    $leads = $contents ? json_decode($contents, true) : [];
    if (!is_array($leads)) {
      throw new RuntimeException('Arquivo de leads inválido.');
    }
    foreach ($leads as &$storedLead) {
      if (($storedLead['id'] ?? '') === $id) {
        $storedLead['email_status'] = $status;
        break;
      }
    }
    unset($storedLead);
    rewind($handle);
    ftruncate($handle, 0);
    fwrite($handle, json_encode($leads, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($handle);
  } finally {
    flock($handle, LOCK_UN);
    fclose($handle);
  }
};

try {
  $updateEmailStatus($leadsFile, $leadId, $emailStatus);
} catch (Throwable $error) {
  $writeErrorLog('email_status_update_failed');
}

json_response(['ok' => true, 'message' => 'Proposta enviada com sucesso.']);
