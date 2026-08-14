<?php

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../vendor/autoload.php';

use League\OAuth2\Client\Provider\Google;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function google_oauth_configurado(): bool
{
    return (bool) (getenv('GOOGLE_CLIENT_ID') && getenv('GOOGLE_CLIENT_SECRET') && getenv('GOOGLE_REDIRECT_URI'));
}

function obter_provider_google(): Google
{
    return new Google([
        'clientId' => getenv('GOOGLE_CLIENT_ID'),
        'clientSecret' => getenv('GOOGLE_CLIENT_SECRET'),
        'redirectUri' => getenv('GOOGLE_REDIRECT_URI'),
    ]);
}

function usuario_logado(): bool
{
    return !empty($_SESSION['usuario_id']);
}

function obter_usuario_atual(): ?array
{
    if (!usuario_logado()) {
        return null;
    }

    return [
        'id' => $_SESSION['usuario_id'],
        'nome' => $_SESSION['usuario_nome'] ?? '',
        'email' => $_SESSION['usuario_email'] ?? '',
        'avatar' => $_SESSION['usuario_avatar'] ?? null,
    ];
}

function exigir_login(): void
{
    if (usuario_logado()) {
        return;
    }

    $destino = $_SERVER['REQUEST_URI'] ?? 'index.php';
    header('Location: login.php?redirecionar=' . urlencode($destino));
    exit;
}
