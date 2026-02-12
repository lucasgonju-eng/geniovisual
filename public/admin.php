<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

// --- Credenciais ---
define('ADMIN_USER', 'admin');
define('ADMIN_PASS_HASH', password_hash('Gqlk1110', PASSWORD_DEFAULT));

// --- Logout ---
if (isset($_GET['logout'])) {
  session_destroy();
  header('Location: admin.php');
  exit;
}

// --- Login ---
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
  if ($_POST['user'] === ADMIN_USER && password_verify($_POST['pass'], ADMIN_PASS_HASH)) {
    $_SESSION['admin_logged'] = true;
    header('Location: admin.php');
    exit;
  } else {
    $loginError = 'Usuário ou senha inválidos.';
  }
}

// --- Ação: excluir lead ---
if (isset($_GET['delete']) && !empty($_SESSION['admin_logged'])) {
  $idToDelete = $_GET['delete'];
  $crmFile = __DIR__ . '/crm-data/leads.json';
  if (file_exists($crmFile)) {
    $leads = json_decode(file_get_contents($crmFile), true) ?: [];
    $leads = array_values(array_filter($leads, fn($l) => $l['id'] !== $idToDelete));
    file_put_contents($crmFile, json_encode($leads, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
  }
  header('Location: admin.php');
  exit;
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
    <form method="POST">
      <input type="hidden" name="login" value="1">
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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $tab === 'analytics' ? 'Analytics' : 'CRM' ?> • Gênio Visual</title>
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
        <a href="admin.php?logout=1" class="bg-zinc-800 hover:bg-zinc-700 text-zinc-300 px-4 py-2 rounded-lg text-sm transition">
          Sair
        </a>
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

  <?php else: ?>
    <!-- ===================== ABA CRM ===================== -->

    <!-- Barra de busca -->
    <form method="GET" class="mb-8">
      <input type="hidden" name="tab" value="crm">
      <div class="flex gap-3">
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
          class="flex-1 bg-zinc-900 border border-zinc-700 rounded-lg px-4 py-3 text-white placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-cyan-500"
          placeholder="Buscar por nome, e-mail, WhatsApp, empresa ou plano...">
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
              <th class="py-3 px-4">Plano</th>
              <th class="py-3 px-4">Mensagem</th>
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
                <td class="py-4 px-4">
                  <span class="bg-blue-600/20 text-blue-400 px-2 py-1 rounded text-xs font-medium">
                    <?= htmlspecialchars($lead['plano'] ?? '') ?>
                  </span>
                </td>
                <td class="py-4 px-4 text-sm text-zinc-400 max-w-[200px] truncate" title="<?= htmlspecialchars($lead['mensagem'] ?? '') ?>">
                  <?= htmlspecialchars($lead['mensagem'] ?? '') ?>
                </td>
                <td class="py-4 px-4 text-sm text-zinc-400 whitespace-nowrap"><?= htmlspecialchars($lead['data_hora'] ?? '') ?></td>
                <td class="py-4 px-4 text-sm text-zinc-500"><?= htmlspecialchars($lead['ip'] ?? '') ?></td>
                <td class="py-4 px-4">
                  <a href="admin.php?tab=crm&delete=<?= urlencode($lead['id'] ?? '') ?>"
                     onclick="return confirm('Excluir este lead?')"
                     class="text-red-400 hover:text-red-300 text-sm transition">
                    Excluir
                  </a>
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
              <p><span class="text-zinc-500">Mensagem:</span>
                <span class="text-zinc-400 ml-1"><?= htmlspecialchars($lead['mensagem'] ?? '') ?></span>
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
              <a href="admin.php?tab=crm&delete=<?= urlencode($lead['id'] ?? '') ?>"
                 onclick="return confirm('Excluir este lead?')"
                 class="text-red-400 hover:text-red-300 text-sm transition px-3 py-2.5">
                Excluir
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  <?php endif; ?>

  </main>
</body>
</html>
