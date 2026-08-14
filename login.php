<?php
require_once __DIR__ . '/includes/auth.php';

if (usuario_logado()) {
    header('Location: index.php');
    exit;
}

if (!google_oauth_configurado()) {
    http_response_code(500);
    echo 'Login com Google ainda não foi configurado. Defina GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET e GOOGLE_REDIRECT_URI no arquivo .env.';
    exit;
}

$redirecionar = $_GET['redirecionar'] ?? 'index.php';
$_SESSION['pos_login_redirecionar'] = $redirecionar;

$provider = obter_provider_google();

$urlAutorizacao = $provider->getAuthorizationUrl([
    'scope' => ['email', 'profile'],
]);

$_SESSION['oauth2state'] = $provider->getState();

header('Location: ' . $urlAutorizacao);
exit;
