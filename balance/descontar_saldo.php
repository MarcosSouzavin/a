<?php
session_start();
require_once '../conexao.php';
require_once 'function_balance.php';


// Log simples para depuração
$logFile = __DIR__ . '/../storage/logs/descontar_saldo.log';
file_put_contents($logFile, date('[Y-m-d H:i:s]') . " Sessão: " . json_encode($_SESSION) . "\n", FILE_APPEND);

if (!isset($_SESSION['usuario_id'])) {
    file_put_contents($logFile, date('[Y-m-d H:i:s]') . " Falha: Usuário não autenticado\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$data = json_decode(file_get_contents('php://input'), true);


file_put_contents($logFile, date('[Y-m-d H:i:s]') . " Dados recebidos: " . json_encode($data) . "\n", FILE_APPEND);
if (!isset($data['valor']) || $data['valor'] <= 0) {
    file_put_contents($logFile, date('[Y-m-d H:i:s]') . " Falha: Valor inválido\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Valor inválido.']);
    exit;
}

// Log do saldo antes
$saldoAntes = buscarSaldo($pdo, $usuario_id);
file_put_contents($logFile, date('[Y-m-d H:i:s]') . " Saldo antes: $saldoAntes\n", FILE_APPEND);

try {
    $valor = floatval($data['valor']);
    if ($valor <= 0) throw new Exception('Valor inválido para desconto');
    descontarSaldo($pdo, $usuario_id, $valor, $data['descricao'] ?? 'Compra');
    $saldoDepois = buscarSaldo($pdo, $usuario_id);
    file_put_contents($logFile, date('[Y-m-d H:i:s]') . " Sucesso: Saldo descontado\n", FILE_APPEND);
    file_put_contents($logFile, date('[Y-m-d H:i:s]') . " Saldo depois: $saldoDepois\n", FILE_APPEND);
    echo json_encode(['success' => true, 'saldo_antes' => $saldoAntes, 'saldo_depois' => $saldoDepois]);
} catch (Exception $e) {
    file_put_contents($logFile, date('[Y-m-d H:i:s]') . " Erro: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'saldo_antes' => $saldoAntes]);
}