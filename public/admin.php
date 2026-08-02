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

session_set_cookie_params([
  'httponly' => true,
  'samesite' => 'Strict',
  'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();
date_default_timezone_set('America/Sao_Paulo');
header('X-Robots-Tag: noindex, nofollow', true);

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$adminUser = (string) app_config('admin_user', '');
$adminPassHash = (string) app_config('admin_password_hash', '');
$adminConfigured = admin_is_configured();
$loginError = '';
$painelErrors = [];
$painelForm = null;
$painelCeilingConfirmation = '';
$segmentErrors = [];
$segmentForm = null;
$pricingErrors = [];
$pricingForm = null;

$isValidCsrf = static function (): bool {
  return hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '');
};

// --- Logout ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout']) && !empty($_SESSION['admin_logged'])) {
  if (!$isValidCsrf()) {
    http_response_code(403);
    exit('Sessão expirada. Recarregue a página e tente novamente.');
  }

  $_SESSION = [];
  session_destroy();
  header('Location: admin.php');
  exit;
}

// --- Login ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
  if (!$isValidCsrf()) {
    $loginError = 'Sessão expirada. Atualize a página e tente novamente.';
  } elseif (!$adminConfigured) {
    $loginError = 'Admin não configurado. Defina as credenciais em `preview/private/app-config.local.php` ou em variáveis de ambiente.';
  } elseif (hash_equals($adminUser, (string) ($_POST['user'] ?? '')) && password_verify((string) ($_POST['pass'] ?? ''), $adminPassHash)) {
    session_regenerate_id(true);
    $_SESSION['admin_logged'] = true;
    header('Location: admin.php');
    exit;
  } else {
    $loginError = 'Usuário ou senha inválidos.';
  }
}

// --- Ação: excluir lead ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && !empty($_SESSION['admin_logged'])) {
  if (!$isValidCsrf()) {
    http_response_code(403);
    exit('Sessão expirada. Recarregue a página e tente novamente.');
  }

  $idToDelete = trim((string) ($_POST['delete'] ?? ''));
  $crmFile = __DIR__ . '/crm-data/leads.json';
  if ($idToDelete !== '' && file_exists($crmFile)) {
    $leads = json_decode(file_get_contents($crmFile), true) ?: [];
    $leads = array_values(array_filter($leads, fn($l) => ($l['id'] ?? '') !== $idToDelete));
    file_put_contents($crmFile, json_encode($leads, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
  }
  header('Location: admin.php?tab=crm');
  exit;
}

// --- Ação: atualizar configuração operacional do painel ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_painel']) && !empty($_SESSION['admin_logged'])) {
  if (!$isValidCsrf()) {
    http_response_code(403);
    exit('Sessão expirada. Recarregue a página e tente novamente.');
  }

  $painelForm = [
    'anunciantes_regulares' => trim((string) ($_POST['anunciantes_regulares'] ?? '')),
    'anunciantes_com_exclusividade' => trim((string) ($_POST['anunciantes_com_exclusividade'] ?? '')),
    'einstein_intercalado' => isset($_POST['einstein_intercalado']),
    'duracao_segundos' => trim((string) ($_POST['duracao_segundos'] ?? '')),
    'horas_por_dia' => trim((string) ($_POST['horas_por_dia'] ?? '')),
    'vagas_totais' => trim((string) ($_POST['vagas_totais'] ?? '')),
  ];

  $validation = painel_validate_admin_config([
    ...$painelForm,
    'atualizado_em' => date('Y-m-d'),
  ]);
  $painelErrors = $validation['errors'];

  if ($painelErrors === [] && is_array($validation['config'])) {
    $currentConfig = painel_read_config();
    $proposedConfig = $validation['config'];
    $proposedConfig['segmentos'] = $currentConfig['segmentos'];
    $proposedConfig['planos'] = $currentConfig['planos'];
    $proposedConfig['preco_minimo'] = $currentConfig['preco_minimo'];
    $ceilingWarning = painel_ceiling_increase_warning($currentConfig, $proposedConfig);
    $ceilingConfirmed = (string) ($_POST['confirm_teto'] ?? '') === '1';

    if ($ceilingWarning !== null && !$ceilingConfirmed) {
      $painelCeilingConfirmation = $ceilingWarning;
    } else {
      try {
        painel_write_config($proposedConfig);
        $_SESSION['painel_flash'] = 'Configuração do painel atualizada.';
        header('Location: admin.php?tab=painel');
        exit;
      } catch (Throwable $error) {
        $painelErrors[] = 'Não foi possível salvar a configuração privada.';
      }
    }
  }
}

// --- Ação: atualizar segmentos e disponibilidade ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_segmentos']) && !empty($_SESSION['admin_logged'])) {
  if (!$isValidCsrf()) {
    http_response_code(403);
    exit('Sessão expirada. Recarregue a página e tente novamente.');
  }

  $currentConfig = painel_read_config();
  $slugs = is_array($_POST['segmento_slug'] ?? null) ? $_POST['segmento_slug'] : [];
  $names = is_array($_POST['segmento_nome'] ?? null) ? $_POST['segmento_nome'] : [];
  $occupiedValues = is_array($_POST['segmentos_ocupados'] ?? null) ? $_POST['segmentos_ocupados'] : [];
  $occupiedSet = array_fill_keys(array_map('strval', $occupiedValues), true);
  $segments = [];

  foreach ($names as $index => $rawName) {
    $name = trim((string) $rawName);
    $slug = painel_slugify((string) ($slugs[$index] ?? $name));
    if ($name === '' || $slug === '') {
      continue;
    }
    $segments[] = [
      'slug' => $slug,
      'nome' => $name,
      'ocupado' => isset($occupiedSet[$slug]),
    ];
  }

  $newCategory = trim((string) ($_POST['nova_categoria'] ?? ''));
  if ($newCategory !== '') {
    $newSlug = painel_slugify($newCategory);
    $existingSlugs = array_column(painel_normalize_segments($segments), 'slug');
    if ($newSlug === '' || in_array($newSlug, $existingSlugs, true)) {
      $segmentErrors[] = 'A nova categoria é inválida ou já existe.';
    } else {
      $segments[] = ['slug' => $newSlug, 'nome' => $newCategory, 'ocupado' => false];
    }
  }

  $segmentValidation = painel_validate_segments_config(
    $segments,
    (int) $currentConfig['anunciantes_com_exclusividade']
  );
  $segmentForm = $segmentValidation['segments'];
  $segmentErrors = [...$segmentErrors, ...$segmentValidation['errors']];

  if ($segmentErrors === []) {
    try {
      $currentConfig['segmentos'] = $segmentValidation['segments'];
      $currentConfig['atualizado_em'] = date('Y-m-d');
      painel_write_config($currentConfig);
      $_SESSION['segmentos_flash'] = 'Registro de segmentos atualizado.';
      header('Location: admin.php?tab=segmentos');
      exit;
    } catch (Throwable $error) {
      $segmentErrors[] = 'Não foi possível salvar o registro de segmentos.';
    }
  }
}

// --- Ação: atualizar preços e campanhas ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_precos']) && !empty($_SESSION['admin_logged'])) {
  if (!$isValidCsrf()) {
    http_response_code(403);
    exit('Sessão expirada. Recarregue a página e tente novamente.');
  }

  $postedPlans = is_array($_POST['planos'] ?? null) ? $_POST['planos'] : [];
  $pricingForm = [];
  foreach (painel_plan_definitions() as $definition) {
    $slug = $definition['slug'];
    $row = is_array($postedPlans[$slug] ?? null) ? $postedPlans[$slug] : [];
    $plan = [
      ...$definition,
      'preco' => trim((string) ($row['preco'] ?? '')),
    ];
    $promotionalPrice = trim((string) ($row['preco_promocional'] ?? ''));
    $campaignLabel = trim((string) ($row['rotulo'] ?? ''));
    $validUntil = trim((string) ($row['validade'] ?? ''));
    if ($promotionalPrice !== '' || $campaignLabel !== '' || $validUntil !== '') {
      $plan['campanha'] = [
        'preco_promocional' => $promotionalPrice,
        'rotulo' => $campaignLabel,
        'validade' => $validUntil,
      ];
    }
    $pricingForm[] = $plan;
  }

  $minimumPriceForm = trim((string) ($_POST['preco_minimo'] ?? ''));
  $pricingValidation = painel_validate_pricing_config($pricingForm, $minimumPriceForm);
  $pricingErrors = $pricingValidation['errors'];
  if ($pricingErrors === [] && is_array($pricingValidation['planos'])) {
    try {
      $currentConfig = painel_read_config();
      $currentConfig['planos'] = $pricingValidation['planos'];
      $currentConfig['preco_minimo'] = $pricingValidation['preco_minimo'];
      $currentConfig['atualizado_em'] = date('Y-m-d');
      painel_write_config($currentConfig);
      $_SESSION['precos_flash'] = 'Tabela de preços atualizada.';
      header('Location: admin.php?tab=precos');
      exit;
    } catch (Throwable $error) {
      $pricingErrors[] = 'Não foi possível salvar a tabela de preços.';
    }
  }
}

// --- Se não logado, exibir tela de login ---
if (empty($_SESSION['admin_logged'])):
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin • Gênio Visual</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>body{background:#0a0a0a;}</style>
</head>
<body class="min-h-screen flex items-center justify-center">
  <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-8 w-full max-w-sm shadow-xl">
    <h1 class="text-2xl font-bold text-white text-center mb-6">Painel Admin</h1>
    <?php if ($loginError): ?>
      <p class="text-red-400 text-sm text-center mb-4"><?= htmlspecialchars($loginError) ?></p>
    <?php endif; ?>
    <?php if (!$adminConfigured): ?>
      <p class="text-zinc-400 text-sm text-center mb-4">
        Configure o arquivo <code class="text-cyan-400">preview/private/app-config.local.php</code> para liberar o acesso.
      </p>
    <?php endif; ?>
    <form method="POST">
      <input type="hidden" name="login" value="1">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
      <div class="mb-4">
        <label class="block text-zinc-400 text-sm mb-1">Usuário</label>
        <input type="text" name="user" required
          class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-cyan-500"
          placeholder="admin">
      </div>
      <div class="mb-6">
        <label class="block text-zinc-400 text-sm mb-1">Senha</label>
        <input type="password" name="pass" required
          class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-cyan-500"
          placeholder="••••••••">
      </div>
      <button type="submit"
        <?= !$adminConfigured ? 'disabled' : '' ?>
        class="w-full bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-semibold py-3 rounded-lg hover:opacity-90 transition">
        Entrar
      </button>
    </form>
  </div>
</body>
</html>
<?php exit; endif; ?>

<?php
// --- Dashboard (logado) ---
$tab = $_GET['tab'] ?? 'crm';
if (!in_array($tab, ['crm', 'analytics', 'painel', 'segmentos', 'precos'], true)) {
  $tab = 'crm';
}

$painelConfig = painel_read_config();
$painelStatus = painel_calculate_status($painelConfig);
$painelInput = $painelForm ?? $painelConfig;
$painelFlash = (string) ($_SESSION['painel_flash'] ?? '');
unset($_SESSION['painel_flash']);
$segmentDisplay = $segmentForm ?? (
  $painelConfig['segmentos'] !== []
    ? $painelConfig['segmentos']
    : painel_default_segments()
);
$occupiedSegments = count(array_filter(
  $painelConfig['segmentos'],
  static fn(array $segment): bool => $segment['ocupado']
));
$segmentWarning = painel_segments_warning(
  (int) $painelConfig['anunciantes_com_exclusividade'],
  $occupiedSegments
);
$segmentFlash = (string) ($_SESSION['segmentos_flash'] ?? '');
unset($_SESSION['segmentos_flash']);
$storedPlansBySlug = [];
foreach ($painelConfig['planos'] as $storedPlan) {
  $storedPlansBySlug[$storedPlan['slug']] = $storedPlan;
}
$pricingDisplay = $pricingForm ?? array_map(
  static fn(array $definition): array => $storedPlansBySlug[$definition['slug']] ?? [
    ...$definition,
    'preco' => '',
  ],
  painel_plan_definitions()
);
$minimumPriceInput = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_precos'])
  ? (string) ($_POST['preco_minimo'] ?? '')
  : (string) $painelConfig['preco_minimo'];
$pricingFlash = (string) ($_SESSION['precos_flash'] ?? '');
unset($_SESSION['precos_flash']);

// CRM data
$crmFile = __DIR__ . '/crm-data/leads.json';
$leads = [];
if (file_exists($crmFile)) {
  $leads = json_decode(file_get_contents($crmFile), true) ?: [];
}
$leads = array_reverse($leads);

$search = trim($_GET['q'] ?? '');
$filteredLeads = $leads;
if ($search !== '') {
  $searchLower = mb_strtolower($search);
  $filteredLeads = array_filter($leads, function($l) use ($searchLower) {
    return str_contains(mb_strtolower($l['nome'] ?? ''), $searchLower)
        || str_contains(mb_strtolower($l['email'] ?? ''), $searchLower)
        || str_contains(mb_strtolower($l['whatsapp'] ?? ''), $searchLower)
        || str_contains(mb_strtolower($l['empresa'] ?? ''), $searchLower)
        || str_contains(mb_strtolower($l['segmento'] ?? ''), $searchLower)
        || str_contains(mb_strtolower($l['plano'] ?? ''), $searchLower);
  });
}

// Analytics data
$analyticsFile = __DIR__ . '/crm-data/analytics.json';
$visits = [];
if (file_exists($analyticsFile)) {
  $visits = json_decode(file_get_contents($analyticsFile), true) ?: [];
}

// Metricas
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$last7 = date('Y-m-d', strtotime('-7 days'));
$last30 = date('Y-m-d', strtotime('-30 days'));

$visitsToday = array_filter($visits, fn($v) => ($v['date'] ?? '') === $today);
$visitsYesterday = array_filter($visits, fn($v) => ($v['date'] ?? '') === $yesterday);
$visits7d = array_filter($visits, fn($v) => ($v['date'] ?? '') >= $last7);
$visits30d = array_filter($visits, fn($v) => ($v['date'] ?? '') >= $last30);

// Pageviews por dia (ultimos 14 dias)
$dailyViews = [];
for ($i = 13; $i >= 0; $i--) {
  $d = date('Y-m-d', strtotime("-{$i} days"));
  $dailyViews[$d] = 0;
}
foreach ($visits as $v) {
  $d = $v['date'] ?? '';
  if (isset($dailyViews[$d])) $dailyViews[$d]++;
}

// Dispositivos (30 dias)
$devices = ['mobile' => 0, 'desktop' => 0, 'tablet' => 0];
foreach ($visits30d as $v) {
  $dev = $v['device'] ?? 'desktop';
  $devices[$dev] = ($devices[$dev] ?? 0) + 1;
}
$totalDevices = array_sum($devices) ?: 1;

// Navegadores (30 dias)
$browsers = [];
foreach ($visits30d as $v) {
  $b = $v['browser'] ?? 'outro';
  $browsers[$b] = ($browsers[$b] ?? 0) + 1;
}
arsort($browsers);

// Top referrers (30 dias)
$referrers = [];
foreach ($visits30d as $v) {
  $ref = $v['referrer'] ?? '';
  if ($ref === '' || $ref === '-') $ref = 'Direto';
  else {
    $parsed = parse_url($ref);
    $ref = $parsed['host'] ?? $ref;
  }
  $referrers[$ref] = ($referrers[$ref] ?? 0) + 1;
}
arsort($referrers);
$referrers = array_slice($referrers, 0, 10, true);

// Top paginas (30 dias)
$pages = [];
foreach ($visits30d as $v) {
  $p = $v['page'] ?? '/';
  $pages[$p] = ($pages[$p] ?? 0) + 1;
}
arsort($pages);
$pages = array_slice($pages, 0, 10, true);

// Leads por dia (14 dias)
$dailyLeads = [];
for ($i = 13; $i >= 0; $i--) {
  $d = date('Y-m-d', strtotime("-{$i} days"));
  $dailyLeads[$d] = 0;
}
foreach ($leads as $l) {
  $d = substr($l['data_hora'] ?? '', 0, 10);
  if (isset($dailyLeads[$d])) $dailyLeads[$d]++;
}

// IPs unicos hoje e 30 dias
$uniqueIpsToday = count(array_unique(array_column(array_values($visitsToday), 'ip')));
$uniqueIps30d = count(array_unique(array_column(array_values($visits30d), 'ip')));

// Max do grafico
$maxDaily = max(1, max(array_values($dailyViews)));

function formatWhatsApp($number) {
  $clean = preg_replace('/\D/', '', $number);
  if (strlen($clean) <= 11) $clean = '55' . $clean;
  return $clean;
}

function formatLeadOrigin($lead) {
  $parts = array_values(array_filter([
    trim((string) ($lead['utm_source'] ?? '')),
    trim((string) ($lead['utm_campaign'] ?? '')),
  ], fn($value) => $value !== ''));

  return $parts ? implode(' / ', $parts) : 'direto/sem origem';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars(['crm' => 'CRM', 'analytics' => 'Analytics', 'painel' => 'Painel', 'segmentos' => 'Segmentos', 'precos' => 'Preços'][$tab]) ?> • Gênio Visual</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>body{background:#0a0a0a;}</style>
</head>
<body class="min-h-screen text-white">
  <!-- Header -->
  <header class="border-b border-zinc-800 bg-zinc-900/80 backdrop-blur sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">
          Gênio Visual Admin
        </h1>
      </div>
      <div class="flex items-center gap-4">
        <a href="/" class="text-zinc-400 hover:text-white text-sm transition">← Site</a>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
          <button type="submit" name="logout" value="1" class="bg-zinc-800 hover:bg-zinc-700 text-zinc-300 px-4 py-2 rounded-lg text-sm transition">
            Sair
          </button>
        </form>
      </div>
    </div>
    <!-- Tabs -->
    <div class="max-w-7xl mx-auto px-4 flex gap-1">
      <a href="admin.php?tab=crm"
         class="px-5 py-3 text-sm font-medium transition border-b-2 <?= $tab === 'crm' ? 'border-cyan-500 text-cyan-400' : 'border-transparent text-zinc-500 hover:text-zinc-300' ?>">
        CRM
        <span class="ml-1.5 bg-zinc-800 text-zinc-400 text-xs px-2 py-0.5 rounded-full"><?= count($leads) ?></span>
      </a>
      <a href="admin.php?tab=analytics"
         class="px-5 py-3 text-sm font-medium transition border-b-2 <?= $tab === 'analytics' ? 'border-cyan-500 text-cyan-400' : 'border-transparent text-zinc-500 hover:text-zinc-300' ?>">
        Analytics
      </a>
      <a href="admin.php?tab=painel"
         class="px-5 py-3 text-sm font-medium transition border-b-2 <?= $tab === 'painel' ? 'border-cyan-500 text-cyan-400' : 'border-transparent text-zinc-500 hover:text-zinc-300' ?>">
        Painel
      </a>
      <a href="admin.php?tab=segmentos"
         class="px-5 py-3 text-sm font-medium transition border-b-2 <?= $tab === 'segmentos' ? 'border-cyan-500 text-cyan-400' : 'border-transparent text-zinc-500 hover:text-zinc-300' ?>">
        Segmentos
      </a>
      <a href="admin.php?tab=precos"
         class="px-5 py-3 text-sm font-medium transition border-b-2 <?= $tab === 'precos' ? 'border-cyan-500 text-cyan-400' : 'border-transparent text-zinc-500 hover:text-zinc-300' ?>">
        Preços
      </a>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-4 py-8">

  <?php if ($tab === 'analytics'): ?>
    <!-- ===================== ABA ANALYTICS ===================== -->

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
        <p class="text-zinc-500 text-xs uppercase tracking-wider mb-1">Visitas hoje</p>
        <p class="text-3xl font-bold text-white"><?= count($visitsToday) ?></p>
        <p class="text-zinc-600 text-xs mt-1"><?= $uniqueIpsToday ?> visitantes únicos</p>
      </div>
      <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
        <p class="text-zinc-500 text-xs uppercase tracking-wider mb-1">Ontem</p>
        <p class="text-3xl font-bold text-white"><?= count($visitsYesterday) ?></p>
      </div>
      <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
        <p class="text-zinc-500 text-xs uppercase tracking-wider mb-1">Últimos 7 dias</p>
        <p class="text-3xl font-bold text-white"><?= count($visits7d) ?></p>
      </div>
      <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
        <p class="text-zinc-500 text-xs uppercase tracking-wider mb-1">Últimos 30 dias</p>
        <p class="text-3xl font-bold text-white"><?= count($visits30d) ?></p>
        <p class="text-zinc-600 text-xs mt-1"><?= $uniqueIps30d ?> visitantes únicos</p>
      </div>
    </div>

    <!-- Gráfico de visitas e leads (14 dias) -->
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6 mb-8">
      <h3 class="text-sm font-semibold text-zinc-300 mb-4">Visitas por dia (14 dias)</h3>
      <div class="flex items-end gap-1.5 h-40">
        <?php foreach ($dailyViews as $date => $count): ?>
          <?php $pct = ($count / $maxDaily) * 100; ?>
          <div class="flex-1 flex flex-col items-center gap-1 group relative">
            <div class="w-full rounded-t-md transition-all bg-gradient-to-t from-cyan-600 to-blue-500 hover:from-cyan-500 hover:to-blue-400"
                 style="height:<?= max(2, $pct) ?>%;min-height:2px;"></div>
            <span class="text-[9px] text-zinc-600"><?= substr($date, 8) ?></span>
            <!-- Tooltip -->
            <div class="absolute -top-8 bg-zinc-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition pointer-events-none whitespace-nowrap">
              <?= date('d/m', strtotime($date)) ?>: <?= $count ?> visitas
              <?php if (($dailyLeads[$date] ?? 0) > 0): ?> · <?= $dailyLeads[$date] ?> leads<?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6 mb-8">
      <!-- Dispositivos -->
      <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6">
        <h3 class="text-sm font-semibold text-zinc-300 mb-4">Dispositivos (30d)</h3>
        <div class="space-y-3">
          <?php
          $deviceLabels = ['mobile' => 'Mobile', 'desktop' => 'Desktop', 'tablet' => 'Tablet'];
          $deviceColors = ['mobile' => 'bg-cyan-500', 'desktop' => 'bg-blue-500', 'tablet' => 'bg-purple-500'];
          foreach ($devices as $dev => $cnt):
            $pct = round(($cnt / $totalDevices) * 100);
          ?>
            <div>
              <div class="flex justify-between text-sm mb-1">
                <span class="text-zinc-400"><?= $deviceLabels[$dev] ?? $dev ?></span>
                <span class="text-zinc-500"><?= $cnt ?> (<?= $pct ?>%)</span>
              </div>
              <div class="h-2 bg-zinc-800 rounded-full overflow-hidden">
                <div class="h-full <?= $deviceColors[$dev] ?? 'bg-zinc-600' ?> rounded-full transition-all" style="width:<?= $pct ?>%"></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Navegadores -->
      <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6">
        <h3 class="text-sm font-semibold text-zinc-300 mb-4">Navegadores (30d)</h3>
        <div class="space-y-2">
          <?php
          $totalBrowsers = array_sum($browsers) ?: 1;
          foreach ($browsers as $b => $cnt):
            $pct = round(($cnt / $totalBrowsers) * 100);
          ?>
            <div class="flex items-center justify-between text-sm">
              <span class="text-zinc-400"><?= htmlspecialchars($b) ?></span>
              <div class="flex items-center gap-2">
                <div class="w-16 h-1.5 bg-zinc-800 rounded-full overflow-hidden">
                  <div class="h-full bg-blue-500 rounded-full" style="width:<?= $pct ?>%"></div>
                </div>
                <span class="text-zinc-500 text-xs w-12 text-right"><?= $cnt ?></span>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (empty($browsers)): ?>
            <p class="text-zinc-600 text-sm">Sem dados ainda.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Top Referrers -->
      <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6">
        <h3 class="text-sm font-semibold text-zinc-300 mb-4">Origem do tráfego (30d)</h3>
        <div class="space-y-2">
          <?php
          $totalRef = array_sum($referrers) ?: 1;
          foreach ($referrers as $ref => $cnt):
            $pct = round(($cnt / $totalRef) * 100);
          ?>
            <div class="flex items-center justify-between text-sm">
              <span class="text-zinc-400 truncate max-w-[150px]"><?= htmlspecialchars($ref) ?></span>
              <div class="flex items-center gap-2">
                <div class="w-16 h-1.5 bg-zinc-800 rounded-full overflow-hidden">
                  <div class="h-full bg-purple-500 rounded-full" style="width:<?= $pct ?>%"></div>
                </div>
                <span class="text-zinc-500 text-xs w-12 text-right"><?= $cnt ?></span>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (empty($referrers)): ?>
            <p class="text-zinc-600 text-sm">Sem dados ainda.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Conversão -->
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6 mb-8">
      <h3 class="text-sm font-semibold text-zinc-300 mb-4">Conversão (30 dias)</h3>
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
          <p class="text-zinc-500 text-xs">Total de visitas</p>
          <p class="text-2xl font-bold"><?= count($visits30d) ?></p>
        </div>
        <div>
          <p class="text-zinc-500 text-xs">Total de leads</p>
          <p class="text-2xl font-bold text-cyan-400"><?= count($leads) ?></p>
        </div>
        <div>
          <p class="text-zinc-500 text-xs">Taxa de conversão</p>
          <p class="text-2xl font-bold text-green-400">
            <?= count($visits30d) > 0 ? number_format((count($leads) / count($visits30d)) * 100, 1) : '0.0' ?>%
          </p>
        </div>
        <div>
          <p class="text-zinc-500 text-xs">Leads hoje</p>
          <p class="text-2xl font-bold">
            <?php
            $leadsToday = count(array_filter($leads, fn($l) => substr($l['data_hora'] ?? '', 0, 10) === $today));
            echo $leadsToday;
            ?>
          </p>
        </div>
      </div>
    </div>

    <!-- Links externos -->
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6">
      <h3 class="text-sm font-semibold text-zinc-300 mb-4">Ferramentas do Google</h3>
      <p class="text-zinc-500 text-sm mb-4">Acesse os painéis completos para análises avançadas:</p>
      <div class="flex flex-wrap gap-3">
        <a href="https://tagmanager.google.com/" target="_blank" rel="noopener"
           class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 px-5 py-3 rounded-lg text-sm transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
          Google Tag Manager
        </a>
        <a href="https://analytics.google.com/" target="_blank" rel="noopener"
           class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 px-5 py-3 rounded-lg text-sm transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
          Google Analytics
        </a>
      </div>

      <!-- Guia de configuração GA4 -->
      <div class="mt-6 p-4 bg-zinc-800/50 border border-zinc-700 rounded-lg">
        <h4 class="text-sm font-semibold text-cyan-400 mb-2">Como configurar o Google Analytics 4 via GTM:</h4>
        <ol class="text-zinc-400 text-sm space-y-1.5 list-decimal list-inside">
          <li>Acesse <a href="https://analytics.google.com/" target="_blank" class="text-cyan-400 hover:underline">analytics.google.com</a> e crie uma propriedade GA4</li>
          <li>ID de medição: <strong class="text-cyan-400">G-K1LSSMV8LQ</strong> (já configurado)</li>
          <li>Container GTM: <strong class="text-cyan-400">GTM-WQLBZW9R</strong> (já instalado no site)</li>
          <li>No GTM: <strong class="text-zinc-300">Tags → Nova → Tag do Google → ID: G-K1LSSMV8LQ</strong></li>
          <li>Acionador: <strong class="text-zinc-300">"All Pages"</strong> → Salvar → Enviar</li>
        </ol>
        <p class="text-green-400 text-xs mt-3">✓ GTM e GA4 configurados. Os dados aparecem em Relatórios → Tempo real no GA4.</p>
      </div>
    </div>

  <?php elseif ($tab === 'painel'): ?>
    <!-- ===================== ABA PAINEL ===================== -->
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
      <section class="bg-zinc-900 border border-zinc-800 rounded-xl p-6">
        <h2 class="text-xl font-semibold mb-2">Configuração operacional</h2>
        <p class="text-zinc-500 text-sm mb-6">
          Use sempre o menor número de horas operadas em um dia da semana.
        </p>

        <?php if ($painelFlash !== ''): ?>
          <p class="mb-5 rounded-lg border border-green-700 bg-green-950/40 px-4 py-3 text-sm text-green-300">
            <?= htmlspecialchars($painelFlash) ?>
          </p>
        <?php endif; ?>

        <?php if ($painelErrors !== []): ?>
          <div class="mb-5 rounded-lg border border-red-800 bg-red-950/40 px-4 py-3 text-sm text-red-300">
            <ul class="list-disc space-y-1 pl-5">
              <?php foreach ($painelErrors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="POST" action="admin.php?tab=painel" class="space-y-5" id="painel-form">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
          <input type="hidden" name="save_painel" value="1">

          <?php if ($painelCeilingConfirmation !== ''): ?>
            <div class="rounded-lg border border-amber-700 bg-amber-950/40 px-4 py-3 text-sm text-amber-200">
              <p><?= htmlspecialchars($painelCeilingConfirmation) ?></p>
              <button type="submit" name="confirm_teto" value="1"
                class="mt-3 rounded-lg bg-amber-600 px-4 py-2 font-semibold text-white hover:bg-amber-500">
                Confirmar aumento do teto
              </button>
            </div>
          <?php endif; ?>

          <div class="grid gap-4 sm:grid-cols-2">
            <label class="block">
              <span class="block text-sm text-zinc-400 mb-1.5">Anunciantes regulares</span>
              <input type="number" name="anunciantes_regulares" min="0" step="1" required
                value="<?= htmlspecialchars((string) $painelInput['anunciantes_regulares']) ?>"
                class="painel-input w-full bg-zinc-800 border border-zinc-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-cyan-500">
            </label>

            <label class="block">
              <span class="block text-sm text-zinc-400 mb-1.5">Anunciantes com exclusividade</span>
              <input type="number" name="anunciantes_com_exclusividade" min="0" step="1" required
                value="<?= htmlspecialchars((string) $painelInput['anunciantes_com_exclusividade']) ?>"
                class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-cyan-500">
              <span class="mt-1.5 block text-xs text-zinc-500">Anunciantes no plano mensal não travam segmento.</span>
            </label>

            <label class="block">
              <span class="block text-sm text-zinc-400 mb-1.5">Vagas totais</span>
              <input type="number" name="vagas_totais" min="0" step="1" required
                value="<?= htmlspecialchars((string) $painelInput['vagas_totais']) ?>"
                class="painel-input w-full bg-zinc-800 border border-zinc-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-cyan-500">
            </label>

            <label class="block">
              <span class="block text-sm text-zinc-400 mb-1.5">Duração de cada inserção (s)</span>
              <input type="number" name="duracao_segundos" min="5" max="60" step="1" required
                value="<?= htmlspecialchars((string) $painelInput['duracao_segundos']) ?>"
                class="painel-input w-full bg-zinc-800 border border-zinc-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-cyan-500">
            </label>

            <label class="block">
              <span class="block text-sm text-zinc-400 mb-1.5">Horas por dia</span>
              <input type="number" name="horas_por_dia" min="1" max="24" step="1" required
                value="<?= htmlspecialchars((string) $painelInput['horas_por_dia']) ?>"
                class="painel-input w-full bg-zinc-800 border border-zinc-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-cyan-500">
            </label>
          </div>

          <label class="flex items-center gap-3 rounded-lg border border-zinc-800 bg-zinc-950/50 px-4 py-3">
            <input type="checkbox" name="einstein_intercalado" value="1"
              <?= !empty($painelInput['einstein_intercalado']) ? 'checked' : '' ?>
              class="painel-input h-4 w-4 accent-cyan-500">
            <span class="text-sm text-zinc-300">Colégio Einstein intercalado entre anunciantes</span>
          </label>

          <p class="text-xs text-zinc-600">
            Última atualização: <?= htmlspecialchars((string) $painelConfig['atualizado_em']) ?>
          </p>

          <button type="submit"
            class="w-full bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-semibold py-3 rounded-lg hover:opacity-90 transition">
            Salvar configuração
          </button>
        </form>
      </section>

      <section class="bg-zinc-900 border border-zinc-800 rounded-xl p-6">
        <h2 class="text-xl font-semibold mb-2">Pré-visualização pública</h2>
        <p class="text-zinc-500 text-sm mb-6">Estes valores são recalculados antes de salvar.</p>

        <div class="grid grid-cols-2 gap-3 mb-4">
          <div class="rounded-xl border border-zinc-800 bg-zinc-950 p-4">
            <p class="text-3xl font-bold text-cyan-400" id="preview-anunciantes"><?= $painelStatus['anunciantes'] ?></p>
            <p class="text-xs text-zinc-500">anunciantes hoje</p>
          </div>
          <div class="rounded-xl border border-zinc-800 bg-zinc-950 p-4">
            <p class="text-3xl font-bold text-cyan-400" id="preview-vagas"><?= $painelStatus['vagas_restantes'] ?></p>
            <p class="text-xs text-zinc-500">vagas restantes</p>
          </div>
        </div>

        <div class="space-y-3">
          <div class="rounded-xl border border-cyan-900 bg-cyan-950/20 p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-cyan-400">Entrega de hoje</p>
            <p class="mt-1 font-semibold text-white">
              <span id="preview-hora"><?= $painelStatus['aparicoes_hora'] ?></span> aparições por hora —
              <span id="preview-tempo"><?= $painelStatus['tela_dia_minutos'] ?> minutos</span> por dia.
            </p>
            <p class="mt-1 text-sm text-zinc-500">
              Com <span id="preview-anunciantes-frase"><?= $painelStatus['anunciantes'] ?></span> anunciantes regulares.
            </p>
          </div>
          <div class="rounded-xl border border-amber-800 bg-amber-950/20 p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-amber-400">Piso garantido no teto</p>
            <p class="mt-1 font-semibold text-white">
              No mínimo <span id="preview-hora-min"><?= $painelStatus['aparicoes_hora_min'] ?></span> aparições por hora —
              <span id="preview-tempo-min"><?= $painelStatus['tela_dia_min_minutos'] ?> minutos</span> por dia.
            </p>
            <p class="mt-1 text-sm text-zinc-500">
              Cenário com <span id="preview-teto"><?= $painelStatus['vagas_totais'] ?></span> anunciantes.
            </p>
          </div>
          <div class="rounded-xl border border-zinc-800 bg-zinc-950 p-4 text-sm text-zinc-400">
            <div class="grid grid-cols-2 gap-3">
              <p><strong class="block text-white" id="preview-dia"><?= $painelStatus['aparicoes_dia'] ?></strong> aparições/dia</p>
              <p><strong class="block text-white" id="preview-mes"><?= $painelStatus['aparicoes_mes'] ?></strong> aparições/mês</p>
              <p><strong class="block text-white" id="preview-ciclo"><?= $painelStatus['ciclo_segundos'] ?>s</strong> ciclo completo</p>
              <p><strong class="block text-white" id="preview-duracao"><?= $painelStatus['duracao_segundos'] ?>s</strong> por inserção</p>
            </div>
          </div>
        </div>
      </section>
    </div>

    <script>
      (() => {
        const form = document.getElementById('painel-form');
        if (!form) return;
        const field = (name) => form.elements.namedItem(name);
        const setText = (id, value) => {
          const element = document.getElementById(id);
          if (element) element.textContent = String(value);
        };
        const durationLabel = (minutes) => {
          if (minutes === 60) return '1 hora';
          if (minutes > 60 && minutes % 60 === 0) return `${minutes / 60} horas`;
          return `${minutes} minutos`;
        };
        const refresh = () => {
          const advertisers = Math.max(0, Number(field('anunciantes_regulares').value) || 0);
          const totalVacancies = Math.max(0, Number(field('vagas_totais').value) || 0);
          const duration = Math.max(0, Number(field('duracao_segundos').value) || 0);
          const hours = Math.max(0, Number(field('horas_por_dia').value) || 0);
          const interleaved = field('einstein_intercalado').checked;
          const slots = interleaved ? advertisers * 2 : advertisers;
          const cycle = slots * duration;
          const perHour = cycle > 0 ? 3600 / cycle : 0;
          const perDay = perHour * hours;
          const minutes = Math.round((perDay * duration) / 60);
          const maxSlots = interleaved ? totalVacancies * 2 : totalVacancies;
          const maxCycle = maxSlots * duration;
          const floorPerHour = maxCycle > 0 ? 3600 / maxCycle : 0;
          const floorPerDay = floorPerHour * hours;
          const floorMinutes = Math.round((floorPerDay * duration) / 60);

          setText('preview-anunciantes', advertisers);
          setText('preview-anunciantes-frase', advertisers);
          setText('preview-vagas', Math.max(0, totalVacancies - advertisers));
          setText('preview-hora', Math.round(perHour));
          setText('preview-hora-min', Math.round(floorPerHour));
          setText('preview-dia', Math.round(perDay));
          setText('preview-mes', Math.round(perDay * 30));
          setText('preview-tempo', durationLabel(minutes));
          setText('preview-tempo-min', durationLabel(floorMinutes));
          setText('preview-teto', totalVacancies);
          setText('preview-ciclo', `${Math.round(cycle)}s`);
          setText('preview-duracao', `${Math.round(duration)}s`);
        };
        form.querySelectorAll('.painel-input').forEach((input) => {
          input.addEventListener('input', refresh);
          input.addEventListener('change', refresh);
        });
        refresh();
      })();
    </script>

  <?php elseif ($tab === 'precos'): ?>
    <!-- ===================== ABA PREÇOS ===================== -->
    <section class="mx-auto max-w-6xl bg-zinc-900 border border-zinc-800 rounded-xl p-6">
      <div class="mb-6">
        <h2 class="text-xl font-semibold">Preços e campanhas</h2>
        <p class="mt-1 text-sm text-zinc-500">
          Alterações salvas entram no site e nas mensagens sem novo deploy. Campanhas expiram automaticamente na data informada.
        </p>
      </div>

      <?php if ($pricingFlash !== ''): ?>
        <p class="mb-5 rounded-lg border border-green-700 bg-green-950/40 px-4 py-3 text-sm text-green-300">
          <?= htmlspecialchars($pricingFlash) ?>
        </p>
      <?php endif; ?>

      <?php if ($pricingErrors !== []): ?>
        <div class="mb-5 rounded-lg border border-red-800 bg-red-950/40 px-4 py-3 text-sm text-red-300">
          <ul class="list-disc space-y-1 pl-5">
            <?php foreach ($pricingErrors as $error): ?>
              <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" action="admin.php?tab=precos" class="space-y-5">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="save_precos" value="1">

        <label class="block max-w-sm">
          <span class="mb-1.5 block text-sm text-zinc-400">Piso mínimo permitido</span>
          <input type="number" name="preco_minimo"
            min="<?= PAINEL_PRECO_MINIMO_ABSOLUTO ?>" step="1" required
            value="<?= htmlspecialchars($minimumPriceInput) ?>"
            class="w-full rounded-lg border border-zinc-700 bg-zinc-800 px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-cyan-500">
          <span class="mt-1.5 block text-xs text-zinc-500">
            Nenhum preço cheio ou promocional pode ficar abaixo deste piso.
          </span>
        </label>

        <div class="grid gap-5 lg:grid-cols-2">
          <?php foreach ($pricingDisplay as $plan): ?>
            <?php
              $slug = (string) $plan['slug'];
              $campaign = is_array($plan['campanha'] ?? null) ? $plan['campanha'] : [];
              $currentPublicPlan = painel_find_plan($painelConfig, $slug);
            ?>
            <fieldset class="rounded-xl border border-zinc-800 bg-zinc-950/50 p-5">
              <legend class="px-2 font-semibold text-white">
                <?= htmlspecialchars((string) $plan['nome']) ?>
                <?php if (!empty($plan['destaque'])): ?>
                  <span class="ml-2 text-xs text-cyan-400">Mais vendido</span>
                <?php endif; ?>
              </legend>
              <p class="mb-4 text-xs text-zinc-500">
                <?= (int) $plan['meses'] ?> mês(es) ·
                <?= !empty($plan['exclusividade']) ? 'com exclusividade' : 'sem exclusividade' ?>
              </p>

              <?php if ($currentPublicPlan !== null): ?>
                <p class="mb-4 rounded-lg border border-zinc-800 bg-zinc-900 px-3 py-2 text-xs text-zinc-400">
                  Publicado agora:
                  <strong class="text-white">
                    R$ <?= number_format((int) $currentPublicPlan['preco_efetivo'], 0, ',', '.') ?>/mês
                  </strong>
                  <?= $currentPublicPlan['em_campanha'] ? ' · campanha vigente' : ' · preço cheio' ?>
                </p>
              <?php endif; ?>

              <div class="space-y-4">
                <label class="block">
                  <span class="mb-1.5 block text-sm text-zinc-400">Preço mensal cheio</span>
                  <input type="number" name="planos[<?= htmlspecialchars($slug) ?>][preco]"
                    min="<?= PAINEL_PRECO_MINIMO_ABSOLUTO ?>" step="1" required
                    value="<?= htmlspecialchars((string) ($plan['preco'] ?? '')) ?>"
                    class="w-full rounded-lg border border-zinc-700 bg-zinc-800 px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-cyan-500">
                </label>

                <div class="border-t border-zinc-800 pt-4">
                  <p class="mb-3 text-sm font-medium text-amber-300">Campanha opcional</p>
                  <div class="grid gap-3 sm:grid-cols-2">
                    <label class="block">
                      <span class="mb-1.5 block text-xs text-zinc-500">Preço promocional</span>
                      <input type="number" name="planos[<?= htmlspecialchars($slug) ?>][preco_promocional]"
                        min="<?= PAINEL_PRECO_MINIMO_ABSOLUTO ?>" step="1"
                        value="<?= htmlspecialchars((string) ($campaign['preco_promocional'] ?? '')) ?>"
                        class="w-full rounded-lg border border-zinc-700 bg-zinc-800 px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </label>
                    <label class="block">
                      <span class="mb-1.5 block text-xs text-zinc-500">Validade obrigatória</span>
                      <input type="date" name="planos[<?= htmlspecialchars($slug) ?>][validade]"
                        value="<?= htmlspecialchars((string) ($campaign['validade'] ?? '')) ?>"
                        class="w-full rounded-lg border border-zinc-700 bg-zinc-800 px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </label>
                  </div>
                  <label class="mt-3 block">
                    <span class="mb-1.5 block text-xs text-zinc-500">Rótulo da campanha</span>
                    <input type="text" name="planos[<?= htmlspecialchars($slug) ?>][rotulo]"
                      maxlength="120"
                      value="<?= htmlspecialchars((string) ($campaign['rotulo'] ?? '')) ?>"
                      placeholder="Motivo real da oferta"
                      class="w-full rounded-lg border border-zinc-700 bg-zinc-800 px-3 py-2 text-white placeholder:text-zinc-600 focus:outline-none focus:ring-2 focus:ring-amber-500">
                  </label>
                </div>
              </div>
            </fieldset>
          <?php endforeach; ?>
        </div>

        <button type="submit"
          class="w-full rounded-lg bg-gradient-to-r from-cyan-500 to-blue-600 py-3 font-semibold text-white hover:opacity-90">
          Publicar tabela de preços
        </button>
      </form>
    </section>

  <?php elseif ($tab === 'segmentos'): ?>
    <!-- ===================== ABA SEGMENTOS ===================== -->
    <section class="mx-auto max-w-5xl bg-zinc-900 border border-zinc-800 rounded-xl p-6">
      <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
          <h2 class="text-xl font-semibold">Registro de segmentos</h2>
          <p class="mt-1 text-sm text-zinc-500">
            Marque apenas categorias com contrato ativo. Nenhum nome de cliente será publicado.
          </p>
        </div>
        <div class="rounded-lg border border-zinc-700 bg-zinc-950 px-4 py-2 text-sm">
          <strong class="text-white"><?= $occupiedSegments ?></strong>
          <span class="text-zinc-500"> ocupados para </span>
          <strong class="text-white"><?= (int) $painelConfig['anunciantes_com_exclusividade'] ?></strong>
          <span class="text-zinc-500"> anunciantes com exclusividade</span>
        </div>
      </div>

      <?php if ($segmentWarning !== null): ?>
        <div class="mb-5 rounded-lg border border-amber-700 bg-amber-950/40 px-4 py-3 text-sm text-amber-200">
          <?= htmlspecialchars($segmentWarning) ?>
        </div>
      <?php else: ?>
        <div class="mb-5 rounded-lg border border-green-700 bg-green-950/40 px-4 py-3 text-sm text-green-300">
          Registro coerente: cada anunciante regular possui um segmento marcado.
        </div>
      <?php endif; ?>

      <?php if ($painelConfig['segmentos'] === []): ?>
        <p class="mb-5 rounded-lg border border-cyan-800 bg-cyan-950/30 px-4 py-3 text-sm text-cyan-200">
          A configuração ainda não possui categorias. As categorias padrão estão prontas abaixo; salve para inicializar o registro privado.
        </p>
      <?php endif; ?>

      <?php if ($segmentFlash !== ''): ?>
        <p class="mb-5 rounded-lg border border-green-700 bg-green-950/40 px-4 py-3 text-sm text-green-300">
          <?= htmlspecialchars($segmentFlash) ?>
        </p>
      <?php endif; ?>

      <?php if ($segmentErrors !== []): ?>
        <div class="mb-5 rounded-lg border border-red-800 bg-red-950/40 px-4 py-3 text-sm text-red-300">
          <ul class="list-disc space-y-1 pl-5">
            <?php foreach ($segmentErrors as $error): ?>
              <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" action="admin.php?tab=segmentos" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="save_segmentos" value="1">

        <?php foreach ($segmentDisplay as $segment): ?>
          <?php
            $segmentSlug = (string) $segment['slug'];
            $waitlistLeads = array_values(array_filter(
              $leads,
              static function(array $lead) use ($segmentSlug): bool {
                if (($lead['lista_espera'] ?? false) !== true) {
                  return false;
                }
                $leadSlug = (string) ($lead['segmento_slug'] ?? painel_slugify((string) ($lead['segmento'] ?? '')));
                return $leadSlug === $segmentSlug;
              }
            ));
          ?>
          <div class="rounded-xl border border-zinc-800 bg-zinc-950/50 p-4">
            <div class="grid items-center gap-3 sm:grid-cols-[auto_minmax(0,1fr)_auto]">
              <label class="flex items-center gap-3">
                <input type="checkbox" name="segmentos_ocupados[]" value="<?= htmlspecialchars($segmentSlug) ?>"
                  <?= !empty($segment['ocupado']) ? 'checked' : '' ?>
                  class="h-5 w-5 accent-cyan-500">
                <span class="text-xs uppercase tracking-wide text-zinc-500">Ocupado</span>
              </label>
              <input type="hidden" name="segmento_slug[]" value="<?= htmlspecialchars($segmentSlug) ?>">
              <input type="text" name="segmento_nome[]" value="<?= htmlspecialchars((string) $segment['nome']) ?>"
                maxlength="80" required
                class="w-full rounded-lg border border-zinc-700 bg-zinc-800 px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-cyan-500">
              <span class="text-xs text-zinc-500"><?= count($waitlistLeads) ?> na espera</span>
            </div>

            <?php if ($waitlistLeads !== []): ?>
              <details class="mt-3 border-t border-zinc-800 pt-3">
                <summary class="cursor-pointer text-sm text-amber-300">Ver lista de espera</summary>
                <ul class="mt-2 space-y-1 text-sm text-zinc-400">
                  <?php foreach ($waitlistLeads as $lead): ?>
                    <li>
                      <?= htmlspecialchars((string) ($lead['nome'] ?? 'Sem nome')) ?>
                      · <?= htmlspecialchars((string) ($lead['email'] ?? '')) ?>
                      · <?= htmlspecialchars((string) ($lead['data_hora'] ?? '')) ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </details>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>

        <div class="rounded-xl border border-dashed border-zinc-700 p-4">
          <label for="nova_categoria" class="mb-2 block text-sm font-medium text-zinc-300">
            Acrescentar categoria
          </label>
          <input id="nova_categoria" type="text" name="nova_categoria" maxlength="80"
            placeholder="Nome da nova categoria"
            class="w-full rounded-lg border border-zinc-700 bg-zinc-800 px-4 py-3 text-white placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-cyan-500">
        </div>

        <button type="submit"
          class="w-full rounded-lg bg-gradient-to-r from-cyan-500 to-blue-600 py-3 font-semibold text-white hover:opacity-90">
          Salvar registro de segmentos
        </button>
      </form>
    </section>

  <?php else: ?>
    <!-- ===================== ABA CRM ===================== -->

    <!-- Barra de busca -->
    <form method="GET" class="mb-8">
      <input type="hidden" name="tab" value="crm">
      <div class="flex gap-3">
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
          class="flex-1 bg-zinc-900 border border-zinc-700 rounded-lg px-4 py-3 text-white placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-cyan-500"
          placeholder="Buscar por nome, e-mail, WhatsApp, empresa, segmento ou plano...">
        <button type="submit"
          class="bg-cyan-600 hover:bg-cyan-500 text-white px-6 py-3 rounded-lg font-medium transition">
          Buscar
        </button>
        <?php if ($search !== ''): ?>
          <a href="admin.php?tab=crm" class="bg-zinc-800 hover:bg-zinc-700 text-zinc-300 px-4 py-3 rounded-lg transition flex items-center">
            Limpar
          </a>
        <?php endif; ?>
      </div>
    </form>

    <!-- Contador -->
    <p class="text-zinc-500 text-xs mb-4"><?= count($filteredLeads) ?> lead(s) encontrado(s)</p>

    <?php if (empty($filteredLeads)): ?>
      <div class="text-center py-20">
        <p class="text-zinc-500 text-lg">Nenhum lead encontrado.</p>
      </div>
    <?php else: ?>
      <!-- Desktop: tabela -->
      <div class="hidden lg:block overflow-x-auto">
        <table class="w-full border-collapse">
          <thead>
            <tr class="border-b border-zinc-800 text-left text-zinc-400 text-sm">
              <th class="py-3 px-4">#</th>
              <th class="py-3 px-4">Nome</th>
              <th class="py-3 px-4">E-mail</th>
              <th class="py-3 px-4">WhatsApp</th>
              <th class="py-3 px-4">Empresa</th>
              <th class="py-3 px-4">Segmento</th>
              <th class="py-3 px-4">Status</th>
              <th class="py-3 px-4">Plano</th>
              <th class="py-3 px-4">Mensagem</th>
              <th class="py-3 px-4">Origem</th>
              <th class="py-3 px-4">Data/Hora</th>
              <th class="py-3 px-4">IP</th>
              <th class="py-3 px-4">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($filteredLeads as $i => $lead): ?>
              <tr class="border-b border-zinc-800/50 hover:bg-zinc-900/50 transition">
                <td class="py-4 px-4 text-zinc-500 text-sm"><?= $i + 1 ?></td>
                <td class="py-4 px-4 font-medium"><?= htmlspecialchars($lead['nome'] ?? '') ?></td>
                <td class="py-4 px-4">
                  <a href="mailto:<?= htmlspecialchars($lead['email'] ?? '') ?>" class="text-cyan-400 hover:underline text-sm">
                    <?= htmlspecialchars($lead['email'] ?? '') ?>
                  </a>
                </td>
                <td class="py-4 px-4">
                  <a href="https://wa.me/<?= formatWhatsApp($lead['whatsapp'] ?? '') ?>"
                     target="_blank" rel="noopener"
                     class="inline-flex items-center gap-1.5 bg-green-600/20 text-green-400 px-3 py-1.5 rounded-full text-sm hover:bg-green-600/30 transition">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492a.5.5 0 00.611.611l4.458-1.495A11.952 11.952 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-2.319 0-4.47-.644-6.326-1.758l-.442-.269-2.646.887.887-2.646-.269-.442A9.956 9.956 0 012 12C2 6.486 6.486 2 12 2s10 4.486 10 10-4.486 10-10 10z"/></svg>
                    <?= htmlspecialchars($lead['whatsapp'] ?? '') ?>
                  </a>
                </td>
                <td class="py-4 px-4 text-sm text-zinc-300"><?= htmlspecialchars($lead['empresa'] ?? '') ?></td>
                <td class="py-4 px-4 text-sm text-zinc-300"><?= htmlspecialchars($lead['segmento'] ?? '') ?></td>
                <td class="py-4 px-4">
                  <?php if (($lead['lista_espera'] ?? false) === true): ?>
                    <span class="rounded bg-amber-600/20 px-2 py-1 text-xs font-medium text-amber-300">Lista de espera</span>
                  <?php else: ?>
                    <span class="text-xs text-zinc-500">Proposta</span>
                  <?php endif; ?>
                </td>
                <td class="py-4 px-4">
                  <span class="bg-blue-600/20 text-blue-400 px-2 py-1 rounded text-xs font-medium">
                    <?= htmlspecialchars($lead['plano'] ?? '') ?>
                  </span>
                </td>
                <td class="py-4 px-4 text-sm text-zinc-400 max-w-[200px] truncate" title="<?= htmlspecialchars($lead['mensagem'] ?? '') ?>">
                  <?= htmlspecialchars($lead['mensagem'] ?? '') ?>
                </td>
                <td class="py-4 px-4 text-sm text-zinc-400 whitespace-nowrap">
                  <?= htmlspecialchars(formatLeadOrigin($lead)) ?>
                </td>
                <td class="py-4 px-4 text-sm text-zinc-400 whitespace-nowrap"><?= htmlspecialchars($lead['data_hora'] ?? '') ?></td>
                <td class="py-4 px-4 text-sm text-zinc-500"><?= htmlspecialchars($lead['ip'] ?? '') ?></td>
                <td class="py-4 px-4">
                  <form method="POST" onsubmit="return confirm('Excluir este lead?')" class="inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <button type="submit" name="delete" value="<?= htmlspecialchars($lead['id'] ?? '') ?>" class="text-red-400 hover:text-red-300 text-sm transition">
                      Excluir
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Mobile: cards -->
      <div class="lg:hidden space-y-4">
        <?php foreach ($filteredLeads as $i => $lead): ?>
          <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 space-y-3">
            <div class="flex items-start justify-between">
              <div>
                <h3 class="font-semibold text-lg"><?= htmlspecialchars($lead['nome'] ?? '') ?></h3>
                <p class="text-zinc-500 text-xs"><?= htmlspecialchars($lead['data_hora'] ?? '') ?></p>
              </div>
              <span class="bg-blue-600/20 text-blue-400 px-2 py-1 rounded text-xs font-medium">
                <?= htmlspecialchars($lead['plano'] ?? '') ?>
              </span>
            </div>
            <div class="space-y-2 text-sm">
              <p><span class="text-zinc-500">E-mail:</span>
                <a href="mailto:<?= htmlspecialchars($lead['email'] ?? '') ?>" class="text-cyan-400 hover:underline ml-1">
                  <?= htmlspecialchars($lead['email'] ?? '') ?>
                </a>
              </p>
              <p><span class="text-zinc-500">Empresa:</span>
                <span class="text-zinc-300 ml-1"><?= htmlspecialchars($lead['empresa'] ?? '') ?></span>
              </p>
              <p><span class="text-zinc-500">Segmento:</span>
                <span class="text-zinc-300 ml-1"><?= htmlspecialchars($lead['segmento'] ?? '') ?></span>
              </p>
              <?php if (($lead['lista_espera'] ?? false) === true): ?>
                <p><span class="rounded bg-amber-600/20 px-2 py-1 text-xs font-medium text-amber-300">Lista de espera</span></p>
              <?php endif; ?>
              <p><span class="text-zinc-500">Mensagem:</span>
                <span class="text-zinc-400 ml-1"><?= htmlspecialchars($lead['mensagem'] ?? '') ?></span>
              </p>
              <p><span class="text-zinc-500">Origem:</span>
                <span class="text-zinc-400 ml-1"><?= htmlspecialchars(formatLeadOrigin($lead)) ?></span>
              </p>
              <p><span class="text-zinc-500">IP:</span>
                <span class="text-zinc-500 ml-1"><?= htmlspecialchars($lead['ip'] ?? '') ?></span>
              </p>
            </div>
            <div class="flex items-center gap-3 pt-2">
              <a href="https://wa.me/<?= formatWhatsApp($lead['whatsapp'] ?? '') ?>"
                 target="_blank" rel="noopener"
                 class="flex-1 inline-flex items-center justify-center gap-2 bg-green-600/20 text-green-400 px-4 py-2.5 rounded-lg text-sm hover:bg-green-600/30 transition font-medium">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492a.5.5 0 00.611.611l4.458-1.495A11.952 11.952 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-2.319 0-4.47-.644-6.326-1.758l-.442-.269-2.646.887.887-2.646-.269-.442A9.956 9.956 0 012 12C2 6.486 6.486 2 12 2s10 4.486 10 10-4.486 10-10 10z"/></svg>
                WhatsApp
              </a>
              <form method="POST" onsubmit="return confirm('Excluir este lead?')">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <button type="submit" name="delete" value="<?= htmlspecialchars($lead['id'] ?? '') ?>" class="text-red-400 hover:text-red-300 text-sm transition px-3 py-2.5">
                  Excluir
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  <?php endif; ?>

  </main>
</body>
</html>
