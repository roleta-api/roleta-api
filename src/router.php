<?php

header('Content-Type: application/json');

$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

// Rota raiz
if ($uri === '') {
    echo json_encode([
        'status' => 'ok',
        'mensagem' => 'API Roleta ativa',
        'versao' => 'v1'
    ]);
    exit;
}

// /api/roletas
if ($uri === 'api/roletas') {
    $storagePath = __DIR__ . '/../storage';
    $provedores = [];

    if (is_dir($storagePath)) {
        foreach (scandir($storagePath) as $dir) {
            if ($dir !== '.' && $dir !== '..' && is_dir($storagePath . '/' . $dir)) {
                $provedores[] = $dir;
            }
        }
    }

    echo json_encode([
        'status' => 'ok',
        'provedores' => $provedores
    ]);
    exit;
}

// /api/roletas/{provedor}
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

// Fallback
http_response_code(404);
echo json_encode(['erro' => 'Endpoint não encontrado']);
