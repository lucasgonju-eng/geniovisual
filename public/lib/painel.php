<?php
declare(strict_types=1);

function painel_default_config(): array
{
    return [
        'anunciantes_regulares' => 3,
        'einstein_intercalado' => true,
        'duracao_segundos' => 10,
        'horas_por_dia' => 18,
        'vagas_totais' => 12,
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

function painel_validate_admin_config(array $candidate): array
{
    $errors = [];
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

    if ($anunciantes === false) {
        $errors[] = 'Anunciantes regulares deve ser um inteiro maior ou igual a zero.';
    }
    if ($duracao === false) {
        $errors[] = 'A duração deve ser um inteiro entre 5 e 60 segundos.';
    }
    if ($horas === false) {
        $errors[] = 'As horas por dia devem ser um inteiro entre 1 e 24.';
    }
    if ($vagas === false) {
        $errors[] = 'Vagas totais deve ser um inteiro maior ou igual a zero.';
    }
    if ($anunciantes !== false && $vagas !== false && $anunciantes > $vagas) {
        $errors[] = 'Não é permitido salvar: anunciantes regulares não pode ser maior que vagas totais.';
    }

    return [
        'errors' => $errors,
        'config' => $errors === [] ? [
            'anunciantes_regulares' => $anunciantes,
            'einstein_intercalado' => ($candidate['einstein_intercalado'] ?? false) === true,
            'duracao_segundos' => $duracao,
            'horas_por_dia' => $horas,
            'vagas_totais' => $vagas,
            'atualizado_em' => (string) ($candidate['atualizado_em'] ?? date('Y-m-d')),
        ] : null,
    ];
}

function painel_ceiling_increase_warning(array $current, array $proposed): ?string
{
    $current = painel_normalize_config($current);
    $proposed = painel_normalize_config($proposed);
    if ($proposed['vagas_totais'] <= $current['vagas_totais']) {
        return null;
    }

    $currentFloor = painel_calculate_status($current)['aparicoes_hora_min'];
    $proposedFloor = painel_calculate_status($proposed)['aparicoes_hora_min'];

    return "Aumentar o teto reduz a frequência garantida de todos os contratos vigentes. Piso atual: {$currentFloor}/hora. Piso após a mudança: {$proposedFloor}/hora.";
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
    $slotsMax = $normalized['einstein_intercalado']
        ? $normalized['vagas_totais'] * 2
        : $normalized['vagas_totais'];
    $cicloMax = $slotsMax * $duracao;
    $aparicoesHoraMin = $cicloMax > 0 ? 3600 / $cicloMax : 0;
    $aparicoesDiaMin = $aparicoesHoraMin * $normalized['horas_por_dia'];

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
        'aparicoes_hora_min' => (int) round($aparicoesHoraMin),
        'aparicoes_dia_min' => (int) round($aparicoesDiaMin),
        'aparicoes_mes_min' => (int) round($aparicoesDiaMin * 30),
        'tela_dia_min_minutos' => (int) round(($aparicoesDiaMin * $duracao) / 60),
        'ciclo_max_segundos' => (int) round($cicloMax),
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
