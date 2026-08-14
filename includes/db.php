<?php

require_once __DIR__ . '/env.php';

function obter_conexao(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $nome = getenv('DB_NAME') ?: '';
    $usuario = getenv('DB_USER') ?: '';
    $senha = getenv('DB_PASS') ?: '';

    $dsn = "mysql:host={$host};port={$port};dbname={$nome};charset=utf8mb4";

    $pdo = new PDO($dsn, $usuario, $senha, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
