<?php

function carregar_env(string $caminho): void
{
    if (!is_readable($caminho)) {
        return;
    }

    foreach (file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linha) {
        $linha = trim($linha);

        if ($linha === '' || str_starts_with($linha, '#')) {
            continue;
        }

        [$chave, $valor] = array_pad(explode('=', $linha, 2), 2, '');
        $chave = trim($chave);
        $valor = trim($valor);

        if ($chave === '') {
            continue;
        }

        putenv("{$chave}={$valor}");
        $_ENV[$chave] = $valor;
    }
}

$arquivoProducao = __DIR__ . '/../.env';
$arquivoDesenvolvimento = __DIR__ . '/../.env.dev';

carregar_env(is_readable($arquivoProducao) ? $arquivoProducao : $arquivoDesenvolvimento);
