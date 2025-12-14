<?php

header('Content-Type: application/json');

$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

/*
|--------------------------------------------------------------------------
| ROTA RAIZ
|--------------------------------------------------------------------------
*/
if ($uri === '') {
    echo json_encode([
        'status' => 'ok',
        'mensagem' => 'API Roleta ativa',
        'versao' => 'v1'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| POST /api/roletas/{provedor}/{roleta}/push
|--------------------------------------------------------------------------
*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    preg_match('#^api/roletas/([a-z0-9_-]+)/([a-z0-9_-]+)/push$#', $uri, $matches)
) {
    $provedor = $matches[1];
    $roleta   = $matches[2];

    $arquivo = __DIR__ . "/../storage/$provedor/$roleta.json";

    if (!file_exists($arquivo)) {
        http_response_code(404);
        echo json_encode(['erro' => 'Roleta não encontrada']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['numero']) || !is_numeric($input['numero'])) {
        http_response_code(400);
        echo json_encode(['erro' => 'Número inválido']);
        exit;
    }

    $numero = (int)$input['numero'];

    // Definir cor
    if ($numero === 0) {
        $cor = 'zero';
    } else {
        $vermelhos = [1,3,5,7,9,12,14,16,18,19,21,23,25,27,30,32,34,36];
        $cor = in_array($numero, $vermelhos) ? 'vermelho' : 'preto';
    }

    // Ler dados atuais
    $dados = json_decode(file_get_contents($arquivo), true) ?? [];

    $historico = $dados['historico'] ?? [];
    array_unshift($historico, $numero);

    // Limitar histórico a 100
    $historico = array_slice($historico, 0, 100);

    // Recalcular estatísticas
    $estatisticas = ['vermelho' => 0, 'preto' => 0, 'zero' => 0];

    foreach ($historico as $n) {
        if ($n === 0) {
            $estatisticas['zero']++;
        } elseif (in_array($n, $vermelhos)) {
            $estatisticas['vermelho']++;
        } else {
            $estatisticas['preto']++;
        }
    }

    $total = count($historico);

    $percentuais = [
        'vermelho' => $total ? round(($estatisticas['vermelho'] / $total) * 100) : 0,
        'preto'    => $total ? round(($estatisticas['preto'] / $total) * 100) : 0,
        'zero'     => $total ? round(($estatisticas['zero'] / $total) * 100) : 0
    ];

    $dadosAtualizados = [
        'provedor'      => $provedor,
        'roleta'        => $roleta,
        'ultimo_numero' => $numero,
        'cor'           => $cor,
        'timestamp'     => date('Y-m-d H:i:s'),
        'historico'     => $historico,
        'estatisticas'  => array_merge($estatisticas, ['total' => $total]),
        'percentuais'   => $percentuais
    ];

    file_put_contents(
        $arquivo,
        json_encode($dadosAtualizados, JSON_PRETTY_PRINT)
    );

    echo json_encode([
        'status' => 'ok',
        'mensagem' => 'Número registrado com sucesso',
        'dados' => $dadosAtualizados
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| GET /api/roletas/{provedor}/{roleta}
|--------------------------------------------------------------------------
*/
if (preg_match('#^api/roletas/([a-z0-9_-]+)/([a-z0-9_-]+)$#', $uri, $matches)) {
    $provedor = $matches[1];
    $roleta   = $matches[2];

    $arquivo = __DIR__ . "/../storage/$provedor/$roleta.json";

    if (!file_exists($arquivo)) {
        http_response_code(404);
        echo json_encode(['erro' => 'Roleta não encontrada']);
        exit;
    }

    echo file_get_contents($arquivo);
    exit;
}

/*
|--------------------------------------------------------------------------
| GET /api/roletas/{provedor}
|--------------------------------------------------------------------------
*/
if (preg_match('#^api/roletas/([a-z0-9_-]+)$#', $uri, $matches)) {
    $provedor = $matches[1];
    $provedorPath = __DIR__ . '/../storage/' . $provedor;

    if (!is_dir($provedorPath)) {
        http_response_code(404);
        echo json_encode(['erro' => 'Provedor não encontrado']);
        exit;
    }

    $roletas = [];

    foreach (scandir($provedorPath) as $file) {
        if (str_ends_with($file, '.json')) {
            $roletas[] = basename($file, '.json');
        }
    }

    echo json_encode([
        'provedor' => $provedor,
        'roletas' => $roletas
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| GET /api/roletas
|--------------------------------------------------------------------------
*/
if ($uri === 'api/roletas') {
    $storagePath = __DIR__ . '/../storage';
    $provedores = [];

    foreach (scandir($storagePath) as $dir) {
        if ($dir !== '.' && $dir !== '..' && is_dir($storagePath . '/' . $dir)) {
            $provedores[] = $dir;
        }
    }

    echo json_encode([
        'status' => 'ok',
        'provedores' => $provedores
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| FALLBACK 404
|--------------------------------------------------------------------------
*/
http_response_code(404);
echo json_encode(['erro' => 'Endpoint não encontrado']);
