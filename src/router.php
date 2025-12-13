<?php

header('Content-Type: application/json');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($uri === '/' || $uri === '/api') {
    echo json_encode([
        'status' => 'ok',
        'mensagem' => 'API Roleta ativa',
        'versao' => 'v1'
    ]);
    exit;
}

http_response_code(404);
echo json_encode(['erro' => 'Endpoint não encontrado']);
