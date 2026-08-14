<?php
require_once __DIR__ . '/../includes/auth.php';

$destino = $_SESSION['pos_login_redirecionar'] ?? '/index.php';
unset($_SESSION['pos_login_redirecionar']);

if (!google_oauth_configurado()) {
    http_response_code(500);
    echo 'Login com Google ainda não foi configurado.';
    exit;
}

if (empty($_GET['state']) || empty($_SESSION['oauth2state']) || $_GET['state'] !== $_SESSION['oauth2state']) {
    unset($_SESSION['oauth2state']);
    http_response_code(400);
    echo 'Sessão de login inválida ou expirada. Volte e tente novamente.';
    exit;
}

unset($_SESSION['oauth2state']);

if (!empty($_GET['error'])) {
    header('Location: ../login.php');
    exit;
}

$provider = obter_provider_google();

try {
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code'] ?? '',
    ]);

    $usuarioGoogle = $provider->getResourceOwner($token)->toArray();

    $email = $usuarioGoogle['email'] ?? null;
    $googleId = $usuarioGoogle['sub'] ?? null;

    if (!$googleId || !$email) {
        throw new RuntimeException('Google não retornou os dados necessários.');
    }

    $usuarioId = criar_ou_atualizar_usuario(
        $googleId,
        $usuarioGoogle['name'] ?? $email,
        $email,
        $usuarioGoogle['picture'] ?? null
    );

    session_regenerate_id(true);
    $_SESSION['usuario_id'] = $usuarioId;
    $_SESSION['usuario_nome'] = $usuarioGoogle['name'] ?? $email;
    $_SESSION['usuario_email'] = $email;
    $_SESSION['usuario_avatar'] = $usuarioGoogle['picture'] ?? null;

    header('Location: ' . (str_starts_with($destino, '/') ? $destino : '../' . $destino));
    exit;
} catch (Exception $e) {
    http_response_code(502);
    echo 'Não foi possível concluir o login com o Google. Tente novamente.';
    exit;
}
