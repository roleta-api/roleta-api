<?php

header('Content-Type: application/json');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Endpoint raiz
if ($uri === '/' || $uri === '') {
    echo json_encode([
        'status' => 'ok',
        'mensagem' => 'API Roleta ativa',
        'versao' => 'v1'
    ]);
    exit;
}

// /api/roletas
if ($uri === '/api/roletas') {
    echo json_encode([
        'status' => 'ok',
        'provedores' => []
    ]);
    exit;
}

// Fallback
http_response_code(404);
echo json_encode([
    'erro' => 'Endpoint não encontrado'
]);
