<?php
$jsonFile = __DIR__ . '/../sys/produtos.json';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (file_exists($jsonFile)) {
        readfile($jsonFile);
    } else {
        echo '[]';
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (is_array($data)) {
        // Se for array simples, salva como campo 'produtos'
        if (array_keys($data) === range(0, count($data) - 1)) {
            $data = [ 'produtos' => $data, 'adicionais' => [] ];
        } else {
            // Se for objeto, garante que tem os campos
            if (!isset($data['produtos'])) $data['produtos'] = [];
            if (!isset($data['adicionais'])) $data['adicionais'] = [];
        }
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $result = @file_put_contents($jsonFile, $json);
        if ($result === false) {
            // Log de erro em caso de falha
            $logFile = __DIR__ . '/../storage/logs/produtos_error.log';
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Erro ao salvar produtos.json\n", FILE_APPEND);
            http_response_code(500);
            echo json_encode(['error' => 'Falha ao salvar produtos.json']);
            exit;
        }
        echo json_encode(['success' => true]);
        exit;
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'JSON inválido']);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Método não permitido']);
