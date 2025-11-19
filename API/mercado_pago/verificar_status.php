<?php
require __DIR__ . "/../conexao.php"; // sua conexão mysqli ou PDO

$pref = $_GET["id"] ?? null;

if (!$pref) {
    echo json_encode(["status" => "erro"]);
    exit;
}

$stmt = $pdo->prepare("SELECT status FROM pagamentos_pix WHERE preference_id = ?");
$stmt->execute([$pref]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(["status" => "pending"]);
    exit;
}

echo json_encode(["status" => $row["status"]]);
