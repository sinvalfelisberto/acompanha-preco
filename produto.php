<?php
require_once __DIR__ . '/includes/auth.php';

exigir_login();

$id = (int) ($_GET['id'] ?? 0);
$produto = obter_produto($id);

if (!$produto) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir_preco') {
    excluir_preco((int) ($_POST['preco_id'] ?? 0));

    header('Location: produto.php?id=' . $id . '&excluido=1');
    exit;
}

$precos = obter_precos_produto($id);
$historico = obter_historico_produto($id);

$menorPreco = $precos ? (float) $precos[0]['preco'] : null;
$maiorPreco = $precos ? (float) end($precos)['preco'] : null;
$economia = ($menorPreco !== null && $maiorPreco !== null && $maiorPreco > 0)
    ? (($maiorPreco - $menorPreco) / $maiorPreco) * 100
    : 0;

$tituloPagina = $produto['nome'];
$paginaAtiva = '';
require __DIR__ . '/includes/header.php';
?>

<a href="index.php" class="link-voltar">← Voltar para a lista</a>

<?php if (!empty($_GET['excluido'])): ?>
    <div class="alerta alerta--sucesso" role="status">
        <span aria-hidden="true">✅</span>
        <div>
            <strong>Preço excluído com sucesso!</strong>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($_GET['editado'])): ?>
    <div class="alerta alerta--sucesso" role="status">
        <span aria-hidden="true">✅</span>
        <div>
            <strong>Preço atualizado com sucesso!</strong>
        </div>
    </div>
<?php endif; ?>

<section class="cabecalho-produto">
    <h1><?= htmlspecialchars($produto['nome']) ?></h1>
    <div class="cabecalho-produto__meta">
        <?php if ($produto['marca']): ?><span>🏷️ <?= htmlspecialchars($produto['marca']) ?></span><?php endif; ?>
        <?php if ($produto['categoria']): ?><span>📂 <?= htmlspecialchars($produto['categoria']) ?></span><?php endif; ?>
        <span>📏 Unidade: <?= htmlspecialchars($produto['unidade']) ?></span>
    </div>

    <?php if ($economia > 0.5): ?>
        <div class="faixa-economia">
            💡 Economize até <strong><?= number_format($economia, 0) ?>%</strong> escolhendo o mercado certo!
        </div>
    <?php endif; ?>

    <a href="adicionar.php?produto_id=<?= (int) $produto['id'] ?>" class="botao botao--primario botao--bloco">
        ➕ Registrar novo preço para este produto
    </a>
</section>

<section class="secao-comparacao">
    <h2 class="titulo-secao">Comparação entre mercados</h2>

    <?php if (!$precos): ?>
        <div class="estado-vazio">
            <span class="estado-vazio__icone" aria-hidden="true">📭</span>
            <p>Ainda não há preços registrados para este produto.</p>
        </div>
    <?php else: ?>
        <ul class="lista-comparacao">
            <?php foreach ($precos as $indice => $item): ?>
                <li class="item-comparacao <?= $indice === 0 ? 'item-comparacao--melhor' : '' ?>">
                    <div class="item-comparacao__info">
                        <span class="item-comparacao__mercado">
                            <?php if ($indice === 0): ?><span class="selo-medalha" aria-hidden="true">🏆</span><?php endif; ?>
                            <?= htmlspecialchars($item['mercado_nome']) ?>
                        </span>
                        <?php if ($item['endereco']): ?>
                            <span class="item-comparacao__endereco"><?= htmlspecialchars($item['endereco']) ?></span>
                        <?php endif; ?>
                        <span class="item-comparacao__data">Atualizado em <?= formatar_data($item['data_registro']) ?></span>
                    </div>
                    <div class="item-comparacao__preco">
                        <?= formatar_preco((float) $item['preco']) ?>
                    </div>
                    <?php if (usuario_logado()): ?>
                        <div class="item-comparacao__acoes">
                            <a href="adicionar.php?preco_id=<?= (int) $item['id'] ?>" class="botao-editar" aria-label="Editar este preço">✏️</a>
                            <form method="post" class="form-excluir" data-confirm="Excluir esse preço de <?= htmlspecialchars($item['mercado_nome']) ?>?">
                                <input type="hidden" name="acao" value="excluir_preco">
                                <input type="hidden" name="preco_id" value="<?= (int) $item['id'] ?>">
                                <button type="submit" class="botao-excluir" aria-label="Excluir este preço">🗑️</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<?php if (count($historico) > count($precos)): ?>
    <section class="secao-historico">
        <h2 class="titulo-secao">Histórico completo</h2>
        <div class="tabela-scroll">
            <table class="tabela-historico">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Mercado</th>
                        <th>Preço</th>
                        <?php if (usuario_logado()): ?><th></th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historico as $registro): ?>
                        <tr>
                            <td><?= formatar_data($registro['data_registro']) ?></td>
                            <td><?= htmlspecialchars($registro['mercado_nome']) ?></td>
                            <td><?= formatar_preco((float) $registro['preco']) ?></td>
                            <?php if (usuario_logado()): ?>
                                <td>
                                    <div class="item-comparacao__acoes">
                                        <a href="adicionar.php?preco_id=<?= (int) $registro['id'] ?>" class="botao-editar" aria-label="Editar este preço">✏️</a>
                                        <form method="post" class="form-excluir" data-confirm="Excluir esse preço de <?= htmlspecialchars($registro['mercado_nome']) ?>?">
                                            <input type="hidden" name="acao" value="excluir_preco">
                                            <input type="hidden" name="preco_id" value="<?= (int) $registro['id'] ?>">
                                            <button type="submit" class="botao-excluir" aria-label="Excluir este preço">🗑️</button>
                                        </form>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
