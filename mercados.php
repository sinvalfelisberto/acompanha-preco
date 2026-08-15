<?php
require_once __DIR__ . '/includes/auth.php';

exigir_login();

$erros = [];
$sucesso = false;
$editado = false;

$mercadoEditando = null;
if (!empty($_GET['editar'])) {
    $mercadoEditando = obter_mercado((int) $_GET['editar']);
}

$valores = [
    'nome' => $mercadoEditando['nome'] ?? '',
    'endereco' => $mercadoEditando['endereco'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir_mercado') {
    excluir_mercado((int) ($_POST['mercado_id'] ?? 0));

    header('Location: mercados.php?excluido=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'criar_mercado') {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'editar_mercado') {
    $mercadoId = (int) ($_POST['mercado_id'] ?? 0);
    $mercadoEditando = obter_mercado($mercadoId);

    $valores['nome'] = trim($_POST['nome'] ?? '');
    $valores['endereco'] = trim($_POST['endereco'] ?? '');

    if (!$mercadoEditando) {
        $erros[] = 'Mercado não encontrado.';
    } elseif ($valores['nome'] === '') {
        $erros[] = 'Informe o nome do mercado.';
    } elseif (mercado_existe($valores['nome'], $mercadoId)) {
        $erros[] = 'Já existe outro mercado cadastrado com esse nome.';
    }

    if (!$erros) {
        atualizar_mercado($mercadoId, $valores['nome'], $valores['endereco'] ?: null);
        header('Location: mercados.php?editado=1');
        exit;
    }
}

$editado = !empty($_GET['editado']);

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

<?php if (!empty($_GET['excluido'])): ?>
    <div class="alerta alerta--sucesso" role="status">
        <span aria-hidden="true">✅</span>
        <div>
            <strong>Mercado excluído com sucesso!</strong>
        </div>
    </div>
<?php endif; ?>

<?php if ($editado): ?>
    <div class="alerta alerta--sucesso" role="status">
        <span aria-hidden="true">✅</span>
        <div>
            <strong>Mercado atualizado com sucesso!</strong>
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

<form method="post" class="form-cadastro" id="form-cadastro">
        <input type="hidden" name="acao" value="<?= $mercadoEditando ? 'editar_mercado' : 'criar_mercado' ?>">
        <?php if ($mercadoEditando): ?>
            <input type="hidden" name="mercado_id" value="<?= (int) $mercadoEditando['id'] ?>">
        <?php endif; ?>
        <fieldset class="grupo-campos">
            <legend><?= $mercadoEditando ? '✏️ Editar mercado' : '🏬 Novo mercado' ?></legend>

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
                <?= $mercadoEditando ? '💾 Salvar alterações' : '💾 Salvar mercado' ?>
            </button>

            <?php if ($mercadoEditando): ?>
                <a href="mercados.php" class="botao botao--secundario botao--bloco">Cancelar edição</a>
            <?php endif; ?>
        </fieldset>
    </form>

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

                <div class="cartao-mercado__acoes">
                    <a href="mercados.php?editar=<?= (int) $mercado['id'] ?>#form-cadastro" class="botao-editar">✏️ Editar</a>

                    <?php
                        $totalPrecos = (int) $mercado['total_precos'];
                        $avisoExclusao = $totalPrecos > 0
                            ? "Excluir \"{$mercado['nome']}\"? Isso também vai apagar os {$totalPrecos} preço(s) registrados nele. Essa ação não pode ser desfeita."
                            : "Excluir \"{$mercado['nome']}\"? Essa ação não pode ser desfeita.";
                    ?>
                    <form method="post" class="form-excluir" data-confirm="<?= htmlspecialchars($avisoExclusao) ?>">
                        <input type="hidden" name="acao" value="excluir_mercado">
                        <input type="hidden" name="mercado_id" value="<?= (int) $mercado['id'] ?>">
                        <button type="submit" class="botao-excluir">🗑️ Excluir</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
