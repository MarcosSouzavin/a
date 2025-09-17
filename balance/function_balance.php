<?php
function buscarSaldo(PDO $pdo, $usuario_id) {
    $stmt = $pdo->prepare("SELECT saldo FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['saldo'] : 0;
}

function adicionarSaldo(PDO $pdo, $usuario_id, $valor, $descricao = "Crédito manual") {
    $pdo->prepare("UPDATE usuarios SET saldo = saldo + ? WHERE id = ?")->execute([$valor, $usuario_id]);

    $pdo->prepare("INSERT INTO saldos_usuarios (usuario_id, tipo, valor, descricao)
                   VALUES (?, 'entrada', ?, ?)")
        ->execute([$usuario_id, $valor, $descricao]);
}

function descontarSaldo(PDO $pdo, $usuario_id, $valor, $descricao = "Compra") {
    $valor = floatval($valor);
    $saldoAtual = buscarSaldo($pdo, $usuario_id);
    file_put_contents(__DIR__ . '/../storage/logs/descontar_saldo.log', date('[Y-m-d H:i:s]') . " [descontarSaldo] Saldo atual: $saldoAtual, Valor a descontar: $valor\n", FILE_APPEND);
    if ($saldoAtual < $valor) {
        file_put_contents(__DIR__ . '/../storage/logs/descontar_saldo.log', date('[Y-m-d H:i:s]') . " [descontarSaldo] Saldo insuficiente!\n", FILE_APPEND);
        throw new Exception("Saldo insuficiente.");
    }

    // Atualiza o saldo no banco de dados
    $stmt = $pdo->prepare("UPDATE usuarios SET saldo = saldo - ? WHERE id = ?");
    $stmt->execute([$valor, $usuario_id]);

    // Registra a transação
    $pdo->prepare("INSERT INTO saldos_usuarios (usuario_id, tipo, valor, descricao)
                   VALUES (?, 'saida', ?, ?)")->execute([$usuario_id, $valor, $descricao]);

    $saldoDepois = buscarSaldo($pdo, $usuario_id);
    file_put_contents(__DIR__ . '/../storage/logs/descontar_saldo.log', date('[Y-m-d H:i:s]') . " [descontarSaldo] Saldo depois: $saldoDepois\n", FILE_APPEND);
    return true;
}