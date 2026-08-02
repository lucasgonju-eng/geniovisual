<?php
declare(strict_types=1);

function painel_default_config(): array
{
    return [
        'anunciantes_regulares' => 9,
        'einstein_intercalado' => true,
        'duracao_segundos' => 10,
        'horas_por_dia' => 18,
        'vagas_totais' => 15,
        'atualizado_em' => '2026-08-02',
    ];
}

function painel_config_path(): string
{
    $override = getenv('GENIO_PAINEL_CONFIG_PATH');
    if (is_string($override) && $override !== '') {
        return $override;
    }

    return dirname(__DIR__, 2) . '/private/painel-config.json';
}

function painel_normalize_config(array $candidate): array
{
    $defaults = painel_default_config();

    $anunciantes = filter_var(
        $candidate['anunciantes_regulares'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 0]]
    );
    $duracao = filter_var(
        $candidate['duracao_segundos'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 5, 'max_range' => 60]]
    );
    $horas = filter_var(
        $candidate['horas_por_dia'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1, 'max_range' => 24]]
    );
    $vagas = filter_var(
        $candidate['vagas_totais'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 0]]
    );

    $config = [
        'anunciantes_regulares' => $anunciantes === false
            ? $defaults['anunciantes_regulares']
            : $anunciantes,
        'einstein_intercalado' => is_bool($candidate['einstein_intercalado'] ?? null)
            ? $candidate['einstein_intercalado']
            : $defaults['einstein_intercalado'],
        'duracao_segundos' => $duracao === false ? $defaults['duracao_segundos'] : $duracao,
        'horas_por_dia' => $horas === false ? $defaults['horas_por_dia'] : $horas,
        'vagas_totais' => $vagas === false ? $defaults['vagas_totais'] : $vagas,
        'atualizado_em' => preg_match(
            '/^\d{4}-\d{2}-\d{2}$/',
            (string) ($candidate['atualizado_em'] ?? '')
        ) ? (string) $candidate['atualizado_em'] : $defaults['atualizado_em'],
    ];

    if ($config['vagas_totais'] < $config['anunciantes_regulares']) {
        $config['vagas_totais'] = $config['anunciantes_regulares'];
    }

    return $config;
}

function painel_read_config(): array
{
    $path = painel_config_path();
    if (!is_file($path)) {
        return painel_default_config();
    }

    $contents = file_get_contents($path);
    if ($contents === false) {
        return painel_default_config();
    }

    $decoded = json_decode($contents, true);
    return is_array($decoded)
        ? painel_normalize_config($decoded)
        : painel_default_config();
}

function painel_calculate_status(array $config): array
{
    $normalized = painel_normalize_config($config);
    $anunciantes = $normalized['anunciantes_regulares'];
    $duracao = $normalized['duracao_segundos'];
    $slots = $normalized['einstein_intercalado']
        ? $anunciantes * 2
        : $anunciantes;
    $ciclo = $slots * $duracao;
    $aparicoesHora = $ciclo > 0 ? 3600 / $ciclo : 0;
    $aparicoesDia = $aparicoesHora * $normalized['horas_por_dia'];

    return [
        'anunciantes' => $anunciantes,
        'vagas_totais' => $normalized['vagas_totais'],
        'vagas_restantes' => max(0, $normalized['vagas_totais'] - $anunciantes),
        'aparicoes_hora' => (int) round($aparicoesHora),
        'aparicoes_dia' => (int) round($aparicoesDia),
        'aparicoes_mes' => (int) round($aparicoesDia * 30),
        'tela_dia_minutos' => (int) round(($aparicoesDia * $duracao) / 60),
        'ciclo_segundos' => (int) round($ciclo),
        'duracao_segundos' => $duracao,
    ];
}

function painel_write_config(array $config): void
{
    $path = painel_config_path();
    $directory = dirname($path);
    if (!is_dir($directory)) {
        throw new RuntimeException('Diretório privado não encontrado.');
    }

    $normalized = painel_normalize_config($config);
    $encoded = json_encode(
        $normalized,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    if ($encoded === false) {
        throw new RuntimeException('Não foi possível serializar a configuração.');
    }

    $temporary = tempnam($directory, 'painel-');
    if ($temporary === false) {
        throw new RuntimeException('Não foi possível criar o arquivo temporário.');
    }

    try {
        if (file_put_contents($temporary, $encoded . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('Não foi possível gravar a configuração.');
        }
        chmod($temporary, 0640);
        if (!rename($temporary, $path)) {
            throw new RuntimeException('Não foi possível publicar a configuração.');
        }
        chmod($path, 0640);
    } finally {
        if (is_file($temporary)) {
            unlink($temporary);
        }
    }
}
