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
$crmFile = __DIR__ . '/crm-data/leads.json';
$leads = [];
if (file_exists($crmFile)) {
  $leads = json_decode(file_get_contents($crmFile), true) ?: [];
}
// Ordenar do mais recente para o mais antigo
$leads = array_reverse($leads);

// Busca
$search = trim($_GET['q'] ?? '');
if ($search !== '') {
  $searchLower = mb_strtolower($search);
  $leads = array_filter($leads, function($l) use ($searchLower) {
    return str_contains(mb_strtolower($l['nome'] ?? ''), $searchLower)
        || str_contains(mb_strtolower($l['email'] ?? ''), $searchLower)
        || str_contains(mb_strtolower($l['whatsapp'] ?? ''), $searchLower)
        || str_contains(mb_strtolower($l['empresa'] ?? ''), $searchLower)
        || str_contains(mb_strtolower($l['plano'] ?? ''), $searchLower);
  });
}

function formatWhatsApp($number) {
  $clean = preg_replace('/\D/', '', $number);
  if (strlen($clean) <= 11) {
    $clean = '55' . $clean;
  }
  return $clean;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CRM • Gênio Visual</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>body{background:#0a0a0a;}</style>
</head>
<body class="min-h-screen text-white">
  <!-- Header -->
  <header class="border-b border-zinc-800 bg-zinc-900/80 backdrop-blur sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">
          Gênio Visual CRM
        </h1>
        <p class="text-zinc-500 text-xs"><?= count($leads) ?> lead(s) encontrado(s)</p>
      </div>
      <div class="flex items-center gap-4">
        <a href="/" class="text-zinc-400 hover:text-white text-sm transition">← Voltar ao site</a>
        <a href="admin.php?logout=1" class="bg-zinc-800 hover:bg-zinc-700 text-zinc-300 px-4 py-2 rounded-lg text-sm transition">
          Sair
        </a>
      </div>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-4 py-8">
    <!-- Barra de busca -->
    <form method="GET" class="mb-8">
      <div class="flex gap-3">
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
          class="flex-1 bg-zinc-900 border border-zinc-700 rounded-lg px-4 py-3 text-white placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-cyan-500"
          placeholder="Buscar por nome, e-mail, WhatsApp, empresa ou plano...">
        <button type="submit"
          class="bg-cyan-600 hover:bg-cyan-500 text-white px-6 py-3 rounded-lg font-medium transition">
          Buscar
        </button>
        <?php if ($search !== ''): ?>
          <a href="admin.php" class="bg-zinc-800 hover:bg-zinc-700 text-zinc-300 px-4 py-3 rounded-lg transition flex items-center">
            Limpar
          </a>
        <?php endif; ?>
      </div>
    </form>

    <?php if (empty($leads)): ?>
      <div class="text-center py-20">
        <p class="text-zinc-500 text-lg">Nenhum lead encontrado.</p>
      </div>
    <?php else: ?>
      <!-- Cards em mobile, tabela em desktop -->
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
            <?php foreach ($leads as $i => $lead): ?>
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
                  <a href="admin.php?delete=<?= urlencode($lead['id'] ?? '') ?>"
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
        <?php foreach ($leads as $i => $lead): ?>
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
              <a href="admin.php?delete=<?= urlencode($lead['id'] ?? '') ?>"
                 onclick="return confirm('Excluir este lead?')"
                 class="text-red-400 hover:text-red-300 text-sm transition px-3 py-2.5">
                Excluir
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
</body>
</html>
