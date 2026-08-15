<?php
require_once __DIR__ . '/includes/auth.php';

$erros = [];
$sucesso = false;

$valores = [
    'nome' => '',
    'endereco' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_login();

    $valores['nome'] = trim($_POST['nome'] ?? '');
    $valores['endereco'] = trim($_POST['endereco'] ?? '');

    if ($valores['nome'] === '') {
        $erros[] = 'Informe o nome do mercado.';
    } elseif (mercado_existe($valores['nome'])) {
        $erros[] = 'Já existe um mercado cadastrado com esse nome.';
    }

    if (!$erros) {
        inserir_mercado($valores['nome'], $valores['endereco'] ?: null);
        $sucesso = true;
        $valores = ['nome' => '', 'endereco' => ''];
    }
}

$pdo = obter_conexao();
$stmt = $pdo->query("
    SELECT
        m.id,
        m.nome,
        m.endereco,
        COUNT(pr.id) AS total_precos,
        MAX(pr.data_registro) AS ultima_atualizacao
    FROM mercados m
    LEFT JOIN precos pr ON pr.mercado_id = m.id
    GROUP BY m.id, m.nome, m.endereco
    ORDER BY m.nome ASC
");
$mercados = $stmt->fetchAll();

$tituloPagina = 'Mercados';
$paginaAtiva = 'mercados';
require __DIR__ . '/includes/header.php';
?>

<section class="cabecalho-secao">
    <h1>🏬 Mercados</h1>
    <p class="subtitulo">Mercados já cadastrados na comparação de preços.</p>
</section>

<?php if ($sucesso): ?>
    <div class="alerta alerta--sucesso" role="status">
        <span aria-hidden="true">✅</span>
        <div>
            <strong>Mercado cadastrado com sucesso!</strong>
            <p>Agora já pode registrar preços encontrados nele.</p>
        </div>
    </div>
<?php endif; ?>

<?php if ($erros): ?>
    <div class="alerta alerta--erro" role="alert">
        <span aria-hidden="true">⚠️</span>
        <div>
            <strong>Corrija os campos abaixo:</strong>
            <ul>
                <?php foreach ($erros as $erro): ?>
                    <li><?= htmlspecialchars($erro) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<?php if (usuario_logado()): ?>
    <form method="post" class="form-cadastro">
        <fieldset class="grupo-campos">
            <legend>🏬 Novo mercado</legend>

            <label class="campo">
                <span>Nome do mercado</span>
                <input
                    type="text"
                    name="nome"
                    id="nome-mercado"
                    placeholder="Ex: Supermercado Bom Preço"
                    value="<?= htmlspecialchars($valores['nome']) ?>"
                    required
                >
            </label>

            <label class="campo">
                <span>Endereço (opcional)</span>
                <input
                    type="text"
                    name="endereco"
                    id="endereco-mercado"
                    placeholder="Ex: Av. Principal, 123"
                    value="<?= htmlspecialchars($valores['endereco']) ?>"
                >
            </label>

            <div class="campo-localizacao">
                <button
                    type="button"
                    id="botao-localizacao"
                    class="botao botao--secundario botao--bloco"
                    data-campo-nome="nome-mercado"
                    data-campo-destino="endereco-mercado"
                >
                    📍 Usar minha localização
                </button>
                <p id="status-localizacao" class="dica-campo" role="status"></p>
                <div id="mapa-localizacao" class="mapa-localizacao" hidden></div>
                <p id="dica-mapa" class="dica-campo" hidden>Toque no mercado no mapa para preencher automaticamente, ou em outro ponto para usar aquele endereço.</p>
            </div>

            <button type="submit" class="botao botao--primario botao--bloco">
                💾 Salvar mercado
            </button>
        </fieldset>
    </form>
<?php else: ?>
    <div class="alerta alerta--sucesso">
        <span aria-hidden="true">🔐</span>
        <div>
            <strong>Quer cadastrar um novo mercado?</strong>
            <p>Faça login para adicionar.</p>
        </div>
        <a href="login.php?redirecionar=%2Fmercados.php" class="botao botao--pequeno">Entrar com Google</a>
    </div>
<?php endif; ?>

<?php if (!$mercados): ?>
    <div class="estado-vazio">
        <span class="estado-vazio__icone" aria-hidden="true">🏬</span>
        <p>Nenhum mercado cadastrado ainda.</p>
    </div>
<?php else: ?>
    <h2 class="titulo-secao">Mercados cadastrados</h2>
    <section class="grade-mercados">
        <?php foreach ($mercados as $mercado): ?>
            <article class="cartao-mercado">
                <h2><?= htmlspecialchars($mercado['nome']) ?></h2>
                <?php if ($mercado['endereco']): ?>
                    <p class="cartao-mercado__endereco">📍 <?= htmlspecialchars($mercado['endereco']) ?></p>
                <?php endif; ?>
                <div class="cartao-mercado__rodape">
                    <span><?= (int) $mercado['total_precos'] ?> preço<?= (int) $mercado['total_precos'] === 1 ? '' : 's' ?> registrado<?= (int) $mercado['total_precos'] === 1 ? '' : 's' ?></span>
                    <?php if ($mercado['ultima_atualizacao']): ?>
                        <span>· Última consulta em <?= formatar_data($mercado['ultima_atualizacao']) ?></span>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
