<?php
declare(strict_types=1);

function load_app_config(): array
{
    $config = [
        'app_url' => getenv('GENIO_APP_URL') ?: 'https://geniovisual.cloud',
        'contact_email' => getenv('GENIO_CONTACT_EMAIL') ?: 'contato@geniovisual.cloud',
        'lead_recipient_email' => getenv('GENIO_LEAD_RECIPIENT_EMAIL') ?: '',
        'admin_user' => getenv('GENIO_ADMIN_USER') ?: '',
        'admin_password_hash' => getenv('GENIO_ADMIN_PASSWORD_HASH') ?: '',
        'admin_password' => getenv('GENIO_ADMIN_PASSWORD') ?: '',
    ];

    $localConfigPath = __DIR__ . '/app-config.local.php';
    if (is_file($localConfigPath)) {
        $localConfig = require $localConfigPath;
        if (is_array($localConfig)) {
            $config = array_merge($config, $localConfig);
        }
    }

    if (($config['admin_password_hash'] ?? '') === '' && ($config['admin_password'] ?? '') !== '') {
        $config['admin_password_hash'] = password_hash((string) $config['admin_password'], PASSWORD_DEFAULT);
    }

    return $config;
}

function app_config(?string $key = null, mixed $default = null): mixed
{
    static $config;

    if ($config === null) {
        $config = load_app_config();
    }

    if ($key === null) {
        return $config;
    }

    return $config[$key] ?? $default;
}

function admin_is_configured(): bool
{
    return app_config('admin_user', '') !== '' && app_config('admin_password_hash', '') !== '';
}

function json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function append_json_record(string $filePath, array $record, int $maxItems = 0): void
{
    $directory = dirname($filePath);
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }

    $handle = fopen($filePath, 'c+');
    if ($handle === false) {
        throw new RuntimeException('Não foi possível abrir o arquivo de dados.');
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            throw new RuntimeException('Não foi possível bloquear o arquivo de dados.');
        }

        $contents = stream_get_contents($handle);
        $items = $contents ? json_decode($contents, true) : [];
        if (!is_array($items)) {
            $items = [];
        }

        $items[] = $record;

        if ($maxItems > 0 && count($items) > $maxItems) {
            $items = array_slice($items, -$maxItems);
        }

        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($handle);
        flock($handle, LOCK_UN);
    } finally {
        fclose($handle);
    }
}
