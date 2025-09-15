<?php
require '../conexao.php';
header('Content-Type: application/json');

function calcularFrete($cepDestino) {
    $cepOrigem = '13761106'; // CEP de origem fixo (exemplo)
    $url = "https://viacep.com.br/ws/$cepDestino/json/";

    // Consulta a API para validar o CEP
    $response = file_get_contents($url);
    $dadosCep = json_decode($response, true);

    if (isset($dadosCep['erro'])) {
        error_log("Erro ao buscar CEP: $cepDestino");
        echo json_encode(['erro' => 'CEP não encontrado.']);
        exit();
    }

    // Simula a distância entre os CEPs (substitua por uma API real, se necessário)
    $distanciaKm = calcularDistanciaAproximada($cepOrigem, $cepDestino);

    // Calcula o valor do frete com base na distância
    $valorFrete = 5.00 + ($distanciaKm * 2); // R$5,00 fixo + R$2,00 por km

    // Calcula o prazo de entrega em horas (assumindo 30 km/h de velocidade média)
    $prazoEntregaHoras = ceil($distanciaKm / 90); // Arredonda para cima

    // Salvar no banco de dados
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO frete (cep_origem, cep_destino, valor, prazo) VALUES (:cep_origem, :cep_destino, :valor, :prazo)");
    $stmt->execute([
        ':cep_origem' => $cepOrigem,
        ':cep_destino' => $cepDestino,
        ':valor' => $valorFrete,
        ':prazo' => $prazoEntregaHoras
    ]);

    return [
        'cepDestino' => $cepDestino,
        'valorFrete' => $valorFrete,
        'prazoEntrega' => $prazoEntregaHoras,
        'endereco' => $dadosCep
    ];
}

// Função para calcular a distância aproximada entre dois CEPs
function calcularDistanciaAproximada($cepOrigem, $cepDestino) {
    // Simula a distância com base nos 3 primeiros dígitos do CEP
    $prefixoOrigem = substr($cepOrigem, 0, 3);
    $prefixoDestino = substr($cepDestino, 0, 3);

    // Diferença entre os prefixos multiplicada por 2 para simular a distância em km
    return abs($prefixoOrigem - $prefixoDestino) * 2;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica se o CEP foi enviado
    if (!isset($_POST['cepDestino']) || empty($_POST['cepDestino'])) {
        echo json_encode(['erro' => 'CEP não informado.']);
        exit();
    }

    $cepDestino = preg_replace('/[^0-9]/', '', $_POST['cepDestino']); // Remove caracteres não numéricos

    // Valida o CEP (deve ter 8 dígitos)
    if (strlen($cepDestino) !== 8) {
        echo json_encode(['erro' => 'CEP inválido.']);
        exit();
    }

    $resultado = calcularFrete($cepDestino);
    echo json_encode($resultado);
}
?>
