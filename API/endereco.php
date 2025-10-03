<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $endereco = trim($data['endereco'] ?? '');

    if (empty($endereco)) {
        echo json_encode(['erro' => 'Endereço obrigatório']);
        exit;
    }

    $_SESSION['endereco'] = $endereco;
    $_SESSION['frete'] = 5.00;

    echo json_encode(['frete' => 5.00, 'endereco' => $endereco]);
} else {
    echo json_encode(['erro' => 'Método não permitido']);
}
?>
