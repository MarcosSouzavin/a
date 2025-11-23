<?php
    $host = 'localhost';
    $dbname = 'u557720587_2025_php01';
    $user = 'u557720587_2025_php01';
    $password = 'Mtec@php1';



try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

?>