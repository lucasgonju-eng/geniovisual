<?php
declare(strict_types=1);

const PAINEL_PRECO_MINIMO_ABSOLUTO = 2500;

function painel_default_segments(): array
{
    return [
        ['slug' => 'automotivo', 'nome' => 'Automotivo', 'ocupado' => false],
        ['slug' => 'imobiliario', 'nome' => 'Imobiliário', 'ocupado' => false],
        ['slug' => 'saude-odontologia', 'nome' => 'Saúde e Odontologia', 'ocupado' => false],
        ['slug' => 'educacao', 'nome' => 'Educação', 'ocupado' => false],
        ['slug' => 'alimentacao', 'nome' => 'Alimentação e Restaurantes', 'ocupado' => false],
        ['slug' => 'varejo-moda', 'nome' => 'Varejo e Moda', 'ocupado' => false],
        ['slug' => 'beleza-estetica', 'nome' => 'Beleza e Estética', 'ocupado' => false],
        ['slug' => 'academias', 'nome' => 'Academias e Fitness', 'ocupado' => false],
        ['slug' => 'financeiro', 'nome' => 'Serviços Financeiros', 'ocupado' => false],
        ['slug' => 'construcao', 'nome' => 'Construção e Reforma', 'ocupado' => false],
        ['slug' => 'tecnologia', 'nome' => 'Tecnologia', 'ocupado' => false],
        ['slug' => 'advocacia-contabilidade', 'nome' => 'Advocacia e Contabilidade', 'ocupado' => false],
        ['slug' => 'pet', 'nome' => 'Pet', 'ocupado' => false],
    ];
}

function painel_slugify(string $value): string
{
    $value = mb_strtolower(trim($value), 'UTF-8');
    $value = strtr($value, [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c',
    ]);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-');
}

function painel_normalize_segments(mixed $candidate): array
{
    if (!is_array($candidate) || $candidate === []) {
        return [];
    }

    $segments = [];
    $seen = [];
    foreach (array_slice($candidate, 0, 50) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $name = trim((string) ($item['nome'] ?? ''));
        if ($name === '' || mb_strlen($name, 'UTF-8') > 80) {
            continue;
        }
        $slug = painel_slugify((string) ($item['slug'] ?? $name));
        if ($slug === '' || isset($seen[$slug])) {
            continue;
        }
        $seen[$slug] = true;
        $segments[] = [
            'slug' => $slug,
            'nome' => $name,
            'ocupado' => ($item['ocupado'] ?? false) === true
                || in_array($item['ocupado'] ?? null, [1, '1', 'on'], true),
        ];
    }

    return $segments;
}

function painel_plan_definitions(): array
{
    return [
        ['slug' => 'mensal', 'nome' => 'Mensal', 'meses' => 1, 'exclusividade' => false, 'destaque' => false],
        ['slug' => 'trimestral', 'nome' => 'Trimestral', 'meses' => 3, 'exclusividade' => true, 'destaque' => false],
        ['slug' => 'semestral', 'nome' => 'Semestral', 'meses' => 6, 'exclusividade' => true, 'destaque' => false],
        ['slug' => 'anual', 'nome' => 'Anual', 'meses' => 12, 'exclusividade' => true, 'destaque' => true],
    ];
}

function painel_normalize_plans(mixed $candidate): array
{
    if (!is_array($candidate) || $candidate === []) {
        return [];
    }

    $plans = [];
    $seen = [];
    foreach (array_slice($candidate, 0, 12) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $name = trim((string) ($item['nome'] ?? ''));
        $slug = painel_slugify((string) ($item['slug'] ?? $name));
        $months = filter_var(
            $item['meses'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 120]]
        );
        $price = filter_var(
            $item['preco'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        if (
            $name === ''
            || mb_strlen($name, 'UTF-8') > 60
            || $slug === ''
            || isset($seen[$slug])
            || $months === false
            || $price === false
        ) {
            continue;
        }

        $campaign = null;
        if (is_array($item['campanha'] ?? null)) {
            $campaignData = $item['campanha'];
            $promotionalPrice = filter_var(
                $campaignData['preco_promocional'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );
            $label = trim((string) ($campaignData['rotulo'] ?? ''));
            $validUntil = trim((string) ($campaignData['validade'] ?? ''));
            if ($promotionalPrice !== false || $label !== '' || $validUntil !== '') {
                $campaign = [
                    'preco_promocional' => $promotionalPrice === false ? 0 : $promotionalPrice,
                    'rotulo' => mb_substr($label, 0, 120, 'UTF-8'),
                    'validade' => $validUntil,
                ];
            }
        }

        $seen[$slug] = true;
        $plan = [
            'slug' => $slug,
            'nome' => $name,
            'meses' => $months,
            'preco' => $price,
            'exclusividade' => ($item['exclusividade'] ?? false) === true
                || in_array($item['exclusividade'] ?? null, [1, '1', 'on'], true),
            'destaque' => ($item['destaque'] ?? false) === true
                || in_array($item['destaque'] ?? null, [1, '1', 'on'], true),
        ];
        if ($campaign !== null) {
            $plan['campanha'] = $campaign;
        }
        $plans[] = $plan;
    }

    return $plans;
}

function painel_default_config(): array
{
    return [
        'anunciantes_regulares' => 3,
        'anunciantes_com_exclusividade' => 3,
        'einstein_intercalado' => true,
        'duracao_segundos' => 10,
        'horas_por_dia' => 18,
        'vagas_totais' => 12,
        'segmentos' => [],
        'planos' => [],
        'preco_minimo' => PAINEL_PRECO_MINIMO_ABSOLUTO,
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
    $exclusiveCandidate = array_key_exists('anunciantes_com_exclusividade', $candidate)
        ? $candidate['anunciantes_com_exclusividade']
        : ($anunciantes === false ? $defaults['anunciantes_com_exclusividade'] : $anunciantes);
    $exclusiveAdvertisers = filter_var(
        $exclusiveCandidate,
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
    $minimumPrice = filter_var(
        $candidate['preco_minimo'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => PAINEL_PRECO_MINIMO_ABSOLUTO]]
    );

    $config = [
        'anunciantes_regulares' => $anunciantes === false
            ? $defaults['anunciantes_regulares']
            : $anunciantes,
        'anunciantes_com_exclusividade' => $exclusiveAdvertisers === false
            ? $defaults['anunciantes_com_exclusividade']
            : $exclusiveAdvertisers,
        'einstein_intercalado' => is_bool($candidate['einstein_intercalado'] ?? null)
            ? $candidate['einstein_intercalado']
            : $defaults['einstein_intercalado'],
        'duracao_segundos' => $duracao === false ? $defaults['duracao_segundos'] : $duracao,
        'horas_por_dia' => $horas === false ? $defaults['horas_por_dia'] : $horas,
        'vagas_totais' => $vagas === false ? $defaults['vagas_totais'] : $vagas,
        'segmentos' => painel_normalize_segments($candidate['segmentos'] ?? []),
        'planos' => painel_normalize_plans($candidate['planos'] ?? []),
        'preco_minimo' => $minimumPrice === false ? $defaults['preco_minimo'] : $minimumPrice,
        'atualizado_em' => preg_match(
            '/^\d{4}-\d{2}-\d{2}$/',
            (string) ($candidate['atualizado_em'] ?? '')
        ) ? (string) $candidate['atualizado_em'] : $defaults['atualizado_em'],
    ];

    if ($config['vagas_totais'] < $config['anunciantes_regulares']) {
        $config['vagas_totais'] = $config['anunciantes_regulares'];
    }
    if ($config['anunciantes_com_exclusividade'] > $config['anunciantes_regulares']) {
        $config['anunciantes_com_exclusividade'] = $config['anunciantes_regulares'];
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
    $exclusiveAdvertisers = filter_var(
        $candidate['anunciantes_com_exclusividade'] ?? null,
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
    if ($exclusiveAdvertisers === false) {
        $errors[] = 'Anunciantes com exclusividade deve ser um inteiro maior ou igual a zero.';
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
    if (
        $anunciantes !== false
        && $exclusiveAdvertisers !== false
        && $exclusiveAdvertisers > $anunciantes
    ) {
        $errors[] = 'Não é permitido salvar: anunciantes com exclusividade não pode ser maior que anunciantes regulares.';
    }

    return [
        'errors' => $errors,
        'config' => $errors === [] ? [
            'anunciantes_regulares' => $anunciantes,
            'anunciantes_com_exclusividade' => $exclusiveAdvertisers,
            'einstein_intercalado' => ($candidate['einstein_intercalado'] ?? false) === true,
            'duracao_segundos' => $duracao,
            'horas_por_dia' => $horas,
            'vagas_totais' => $vagas,
            'atualizado_em' => (string) ($candidate['atualizado_em'] ?? date('Y-m-d')),
        ] : null,
    ];
}

function painel_validate_pricing_config(array $plans, mixed $minimumPrice): array
{
    $errors = [];
    $minimum = filter_var(
        $minimumPrice,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => PAINEL_PRECO_MINIMO_ABSOLUTO]]
    );
    if ($minimum === false) {
        $errors[] = 'O preço mínimo não pode ser inferior a R$ '
            . number_format(PAINEL_PRECO_MINIMO_ABSOLUTO, 0, ',', '.') . '.';
        $minimum = PAINEL_PRECO_MINIMO_ABSOLUTO;
    }

    $normalized = painel_normalize_plans($plans);
    if ($normalized === []) {
        $errors[] = 'Informe ao menos um plano com preço válido.';
    }
    if (count($normalized) !== count($plans)) {
        $errors[] = 'Há plano com nome, duração ou preço inválido.';
    }

    foreach ($normalized as $plan) {
        $planName = $plan['nome'];
        if ($plan['preco'] < $minimum) {
            $errors[] = "O preço do plano {$planName} não pode ser inferior ao piso de R$ "
                . number_format($minimum, 0, ',', '.') . '.';
        }
        if (!isset($plan['campanha'])) {
            continue;
        }

        $campaign = $plan['campanha'];
        $promotionalPrice = $campaign['preco_promocional'];
        $validUntil = $campaign['validade'];
        $label = $campaign['rotulo'];
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $validUntil);
        $validDate = $date !== false && $date->format('Y-m-d') === $validUntil;

        if ($promotionalPrice < $minimum) {
            $errors[] = "O preço promocional do plano {$planName} não pode ser inferior ao piso de R$ "
                . number_format($minimum, 0, ',', '.') . '.';
        }
        if ($promotionalPrice >= $plan['preco']) {
            $errors[] = "O preço promocional do plano {$planName} deve ser menor que o preço cheio.";
        }
        if ($label === '') {
            $errors[] = "Informe o rótulo da campanha do plano {$planName}.";
        }
        if ($validUntil === '' || !$validDate) {
            $errors[] = "Informe uma validade válida para a campanha do plano {$planName}.";
        }
    }

    return [
        'errors' => array_values(array_unique($errors)),
        'planos' => $errors === [] ? $normalized : null,
        'preco_minimo' => $errors === [] ? $minimum : null,
    ];
}

function painel_validate_segments_config(array $segments, int $exclusiveAdvertisers): array
{
    $normalized = painel_normalize_segments($segments);
    $occupied = count(array_filter(
        $normalized,
        static fn(array $segment): bool => $segment['ocupado']
    ));
    $errors = [];
    if ($occupied > $exclusiveAdvertisers) {
        $errors[] = "Não é permitido salvar: existem {$occupied} segmentos ocupados para {$exclusiveAdvertisers} anunciantes com exclusividade.";
    }

    return [
        'segments' => $normalized,
        'occupied' => $occupied,
        'consistent' => $normalized !== [] && $occupied === $exclusiveAdvertisers,
        'errors' => $errors,
    ];
}

function painel_segments_warning(int $exclusiveAdvertisers, int $occupied): ?string
{
    if ($occupied === $exclusiveAdvertisers) {
        return null;
    }

    $advertiserLabel = $exclusiveAdvertisers === 1
        ? 'anunciante com exclusividade'
        : 'anunciantes com exclusividade';
    $segmentLabel = $occupied === 1 ? 'segmento marcado' : 'segmentos marcados';
    return "Você tem {$exclusiveAdvertisers} {$advertiserLabel} e {$occupied} {$segmentLabel}. Ou algum anunciante com exclusividade está sem segmento atribuído, ou dois dividem a mesma categoria — o que contraria a exclusividade vendida.";
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
    $segments = $normalized['segmentos'];
    $occupiedSegments = count(array_filter(
        $segments,
        static fn(array $segment): bool => $segment['ocupado']
    ));
    $pricing = painel_calculate_pricing($normalized);

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
        'segmentos' => $segments,
        'segmentos_livres' => max(0, count($segments) - $occupiedSegments),
        'segmentos_consistente' => $segments !== []
            && $occupiedSegments === $normalized['anunciantes_com_exclusividade'],
        'planos' => $pricing['planos'],
        'preco_a_partir_de' => $pricing['preco_a_partir_de'],
    ];
}

function painel_calculate_pricing(array $config, ?string $today = null): array
{
    $normalized = painel_normalize_config($config);
    $today = $today ?? date('Y-m-d');
    $plans = [];
    $effectivePrices = [];

    foreach ($normalized['planos'] as $plan) {
        if ($plan['preco'] < $normalized['preco_minimo']) {
            continue;
        }
        $campaign = $plan['campanha'] ?? null;
        $campaignDate = is_array($campaign)
            ? DateTimeImmutable::createFromFormat('!Y-m-d', $campaign['validade'])
            : false;
        $campaignDateValid = $campaignDate !== false
            && $campaignDate->format('Y-m-d') === $campaign['validade'];
        $campaignActive = is_array($campaign)
            && $campaignDateValid
            && $campaign['rotulo'] !== ''
            && $campaign['validade'] >= $today
            && $campaign['preco_promocional'] >= $normalized['preco_minimo']
            && $campaign['preco_promocional'] < $plan['preco'];
        $effectivePrice = $campaignActive
            ? $campaign['preco_promocional']
            : $plan['preco'];
        $effectivePrices[] = $effectivePrice;
        $plans[] = [
            'slug' => $plan['slug'],
            'nome' => $plan['nome'],
            'meses' => $plan['meses'],
            'preco' => $plan['preco'],
            'preco_efetivo' => $effectivePrice,
            'em_campanha' => $campaignActive,
            'rotulo' => $campaignActive ? $campaign['rotulo'] : null,
            'validade' => $campaignActive ? $campaign['validade'] : null,
            'exclusividade' => $plan['exclusividade'],
            'destaque' => $plan['destaque'],
        ];
    }

    return [
        'planos' => $plans,
        'preco_a_partir_de' => $effectivePrices === [] ? null : min($effectivePrices),
    ];
}

function painel_find_plan(array $config, string $nameOrSlug): ?array
{
    $slug = painel_slugify($nameOrSlug);
    foreach (painel_calculate_pricing($config)['planos'] as $plan) {
        if ($plan['slug'] === $slug) {
            return $plan;
        }
    }
    return null;
}

function painel_find_segment(array $config, string $slug): ?array
{
    $slug = painel_slugify($slug);
    foreach (painel_normalize_config($config)['segmentos'] as $segment) {
        if ($segment['slug'] === $slug) {
            return $segment;
        }
    }
    return null;
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

function promo_config_path(): string
{
    $override = getenv('GENIO_PROMO_CONFIG_PATH');
    if (is_string($override) && $override !== '') {
        return $override;
    }

    return dirname(__DIR__, 2) . '/private/promocao.json';
}

function promo_default(): array
{
    return [
        'ativa' => false,
        'rotulo' => 'Promoção Relâmpago — 5 primeiros',
        'descricao' => '3 meses pelo preço de 2 e, no PIX à vista, mais 20% de desconto. Um anunciante por segmento, frequência mínima garantida em contrato. Direto com o proprietário, sem agência e sem comissão.',
        'preco_total' => 5760.0,
        'equivalente_mensal' => 1920.0,
        'forma_pagamento' => 'PIX à vista (100% antecipado)',
        'limite_vagas' => 5,
        'vagas_restantes' => 5,
        'validade' => '',
        'mensagem_whatsapp' => 'Vi o anúncio relâmpago do painel da T-15 e quero a condição dos 5 primeiros - R$ 5.760 no PIX',
        'atualizado_em' => '',
    ];
}

function promo_normalize(array $candidate): array
{
    $defaults = promo_default();
    $normalizeFloat = static function (mixed $value, float $fallback): float {
        if (!is_numeric($value)) {
            return $fallback;
        }
        $number = (float) $value;
        return is_finite($number) ? max(0.0, $number) : $fallback;
    };
    $normalizeInteger = static function (mixed $value, int $fallback): int {
        if ($value === null || $value === '') {
            return $fallback;
        }
        return max(0, (int) $value);
    };
    $normalizeString = static function (mixed $value, string $fallback, int $limit): string {
        if ($value === null) {
            return $fallback;
        }
        return mb_substr(trim((string) $value), 0, $limit, 'UTF-8');
    };

    $limit = $normalizeInteger(
        $candidate['limite_vagas'] ?? null,
        $defaults['limite_vagas']
    );
    $remaining = $normalizeInteger(
        $candidate['vagas_restantes'] ?? null,
        $defaults['vagas_restantes']
    );
    $validUntil = trim((string) ($candidate['validade'] ?? ''));
    $validDate = DateTimeImmutable::createFromFormat('!Y-m-d', $validUntil);
    if (
        $validUntil === ''
        || $validDate === false
        || $validDate->format('Y-m-d') !== $validUntil
    ) {
        $validUntil = '';
    }

    return [
        'ativa' => ($candidate['ativa'] ?? false) === true
            || in_array($candidate['ativa'] ?? null, [1, '1', 'on'], true),
        'rotulo' => $normalizeString($candidate['rotulo'] ?? null, $defaults['rotulo'], 120),
        'descricao' => $normalizeString($candidate['descricao'] ?? null, $defaults['descricao'], 400),
        'preco_total' => $normalizeFloat(
            $candidate['preco_total'] ?? null,
            $defaults['preco_total']
        ),
        'equivalente_mensal' => $normalizeFloat(
            $candidate['equivalente_mensal'] ?? null,
            $defaults['equivalente_mensal']
        ),
        'forma_pagamento' => $normalizeString(
            $candidate['forma_pagamento'] ?? null,
            $defaults['forma_pagamento'],
            80
        ),
        'limite_vagas' => $limit,
        'vagas_restantes' => min($remaining, $limit),
        'validade' => $validUntil,
        'mensagem_whatsapp' => $normalizeString(
            $candidate['mensagem_whatsapp'] ?? null,
            $defaults['mensagem_whatsapp'],
            300
        ),
        'atualizado_em' => $normalizeString(
            $candidate['atualizado_em'] ?? null,
            $defaults['atualizado_em'],
            30
        ),
    ];
}

function promo_read(): array
{
    $path = promo_config_path();
    if (!is_file($path)) {
        return promo_default();
    }

    $contents = file_get_contents($path);
    if ($contents === false) {
        return promo_default();
    }

    $decoded = json_decode($contents, true);
    return is_array($decoded) ? promo_normalize($decoded) : promo_default();
}

function promo_write(array $promotion): void
{
    $path = promo_config_path();
    $directory = dirname($path);
    if (!is_dir($directory)) {
        throw new RuntimeException('Diretório privado não encontrado.');
    }

    $encoded = json_encode(
        promo_normalize($promotion),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    if ($encoded === false) {
        throw new RuntimeException('Não foi possível serializar a promoção.');
    }

    $temporary = tempnam($directory, 'promo-');
    if ($temporary === false) {
        throw new RuntimeException('Não foi possível criar o arquivo temporário.');
    }

    try {
        if (file_put_contents($temporary, $encoded . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('Não foi possível gravar a promoção.');
        }
        chmod($temporary, 0640);
        if (!rename($temporary, $path)) {
            throw new RuntimeException('Não foi possível publicar a promoção.');
        }
        chmod($path, 0640);
    } finally {
        if (is_file($temporary)) {
            unlink($temporary);
        }
    }
}

function promo_public_view(array $promotion, ?string $today = null): ?array
{
    $normalized = promo_normalize($promotion);
    $today = $today ?? date('Y-m-d');
    if (
        !$normalized['ativa']
        || $normalized['vagas_restantes'] <= 0
        || ($normalized['validade'] !== '' && $normalized['validade'] < $today)
    ) {
        return null;
    }

    return [
        'rotulo' => $normalized['rotulo'],
        'descricao' => $normalized['descricao'],
        'preco_total' => $normalized['preco_total'],
        'equivalente_mensal' => $normalized['equivalente_mensal'],
        'forma_pagamento' => $normalized['forma_pagamento'],
        'vagas_restantes' => $normalized['vagas_restantes'],
        'validade' => $normalized['validade'],
        'mensagem_whatsapp' => $normalized['mensagem_whatsapp'],
    ];
}
