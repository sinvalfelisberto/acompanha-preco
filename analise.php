<?php
require_once __DIR__ . '/includes/auth.php';

exigir_login();

$periodosValidos = [30, 60, 90, 180, 365];

$dias = $_POST['dias'] ?? $_GET['dias'] ?? '90';
$dias = ($dias === 'todos' || !in_array((int) $dias, $periodosValidos, true)) ? null : (int) $dias;

$categoria = trim($_POST['categoria'] ?? $_GET['categoria'] ?? '');

$precosAtuais = listar_precos_atuais($dias, $categoria ?: null);
$categorias = listar_categorias();

// Produtos que têm pelo menos um preço dentro do filtro — só eles podem entrar na cesta.
$produtosDisponiveis = [];
foreach ($precosAtuais as $linha) {
    $produtoId = (int) $linha['produto_id'];

    if (!isset($produtosDisponiveis[$produtoId])) {
        $produtosDisponiveis[$produtoId] = [
            'id' => $produtoId,
            'nome' => $linha['produto_nome'],
            'marca' => $linha['marca'],
            'categoria' => $linha['categoria'],
            'unidade' => $linha['unidade'],
            'mercados' => 0,
            'menor_preco' => (float) $linha['preco'],
        ];
    }

    $produtosDisponiveis[$produtoId]['mercados']++;
    $produtosDisponiveis[$produtoId]['menor_preco'] = min(
        $produtosDisponiveis[$produtoId]['menor_preco'],
        (float) $linha['preco']
    );
}

// Na primeira visita a cesta já vem completa, para a análise aparecer pronta.
$cestaEnviada = $_SERVER['REQUEST_METHOD'] === 'POST';
$selecionados = array_map('intval', (array) ($_POST['produtos'] ?? []));
$quantidadesEnviadas = (array) ($_POST['quantidade'] ?? []);

$quantidades = [];
foreach ($produtosDisponiveis as $produtoId => $produto) {
    if ($cestaEnviada && !in_array($produtoId, $selecionados, true)) {
        continue;
    }

    $quantidade = $cestaEnviada ? (float) str_replace(',', '.', (string) ($quantidadesEnviadas[$produtoId] ?? 1)) : 1.0;
    $quantidades[$produtoId] = $quantidade > 0 ? $quantidade : 1.0;
}

$analise = analisar_compra_mensal($precosAtuais, $quantidades);

$tituloPagina = 'Compra do mês';
$paginaAtiva = 'analise';
require __DIR__ . '/includes/header.php';
?>

<section class="cabecalho-secao">
    <h1>📊 Compra do mês</h1>
    <p class="subtitulo">Com base nos preços já cadastrados, veja em qual mercado vale mais a pena fazer a compra.</p>
</section>

<form method="post" class="form-analise" id="form-analise">
    <fieldset class="grupo-campos">
        <legend>🧺 Cesta e filtros</legend>

        <div class="campo-linha">
            <label class="campo">
                <span>Considerar preços dos últimos</span>
                <select name="dias" id="analise-dias">
                    <?php foreach ($periodosValidos as $periodo): ?>
                        <option value="<?= $periodo ?>" <?= $dias === $periodo ? 'selected' : '' ?>><?= $periodo ?> dias</option>
                    <?php endforeach; ?>
                    <option value="todos" <?= $dias === null ? 'selected' : '' ?>>Todo o histórico</option>
                </select>
            </label>

            <label class="campo">
                <span>Categoria</span>
                <select name="categoria" id="analise-categoria">
                    <option value="">Todas as categorias</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= $categoria === $cat ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <?php if (!$produtosDisponiveis): ?>
            <p class="dica-campo">Nenhum produto com preço nesse período. Aumente o período ou cadastre novos preços.</p>
        <?php else: ?>
            <label class="campo">
                <span>Itens da cesta (<?= count($quantidades) ?> de <?= count($produtosDisponiveis) ?> selecionados)</span>
                <input type="search" id="filtro-cesta" placeholder="Filtrar produto na lista..." autocomplete="off">
            </label>

            <div class="cesta-acoes">
                <button type="button" class="botao botao--secundario botao--pequeno" id="cesta-marcar-todos">Marcar todos</button>
                <button type="button" class="botao botao--secundario botao--pequeno" id="cesta-desmarcar-todos">Desmarcar todos</button>
            </div>

            <ul class="lista-cesta" id="lista-cesta">
                <?php foreach ($produtosDisponiveis as $produtoId => $produto): ?>
                    <li class="item-cesta" data-nome="<?= htmlspecialchars(mb_strtolower($produto['nome'] . ' ' . (string) $produto['marca']), ENT_QUOTES) ?>">
                        <label class="item-cesta__escolha">
                            <input
                                type="checkbox"
                                name="produtos[]"
                                value="<?= $produtoId ?>"
                                <?= isset($quantidades[$produtoId]) ? 'checked' : '' ?>
                            >
                            <span class="item-cesta__nome">
                                <?= htmlspecialchars($produto['nome']) ?>
                                <?php if ($produto['marca']): ?>
                                    <small><?= htmlspecialchars($produto['marca']) ?></small>
                                <?php endif; ?>
                                <small><?= (int) $produto['mercados'] ?> mercado<?= (int) $produto['mercados'] === 1 ? '' : 's' ?> · a partir de <?= formatar_preco($produto['menor_preco']) ?></small>
                            </span>
                        </label>

                        <input
                            type="number"
                            class="item-cesta__quantidade"
                            name="quantidade[<?= $produtoId ?>]"
                            value="<?= htmlspecialchars(str_replace(',', '.', formatar_quantidade($quantidades[$produtoId] ?? 1.0))) ?>"
                            min="1"
                            step="1"
                            inputmode="numeric"
                            aria-label="Quantidade de <?= htmlspecialchars($produto['nome']) ?>"
                        >
                    </li>
                <?php endforeach; ?>
            </ul>

            <button type="submit" class="botao botao--primario botao--bloco">🔎 Analisar cesta</button>
        <?php endif; ?>
    </fieldset>
</form>

<?php if ($analise['total_itens'] === 0): ?>
    <div class="estado-vazio">
        <span class="estado-vazio__icone" aria-hidden="true">🧺</span>
        <p>Selecione pelo menos um produto para analisar.</p>
    </div>
<?php else: ?>
    <?php $melhor = $analise['melhor_mercado']; ?>

    <section class="destaque-analise">
        <span class="destaque-analise__selo">🏆 Melhor mercado para a compra do mês</span>
        <h2><?= htmlspecialchars($melhor['mercado_nome']) ?></h2>
        <p class="destaque-analise__valor"><?= formatar_preco($melhor['total_estimado']) ?></p>
        <p class="destaque-analise__detalhe">
            <?= $analise['total_itens'] ?> ite<?= $analise['total_itens'] === 1 ? 'm' : 'ns' ?> na cesta ·
            <?= $analise['total_mercados'] ?> mercado<?= $analise['total_mercados'] === 1 ? '' : 's' ?> comparado<?= $analise['total_mercados'] === 1 ? '' : 's' ?> ·
            tem <?= number_format($melhor['cobertura'], 0) ?>% da cesta
        </p>

        <?php if (!$melhor['completo']): ?>
            <p class="destaque-analise__aviso">
                ⚠️ Esse mercado não tem preço cadastrado para <?= count($melhor['itens_faltantes']) ?> ite<?= count($melhor['itens_faltantes']) === 1 ? 'm' : 'ns' ?>
                (<?= htmlspecialchars(implode(', ', array_slice($melhor['itens_faltantes'], 0, 5))) ?><?= count($melhor['itens_faltantes']) > 5 ? '…' : '' ?>).
                O valor acima inclui esses itens pelo menor preço encontrado em outro mercado.
            </p>
        <?php endif; ?>

        <?php if ($analise['economia_ranking'] > 0.01): ?>
            <p class="destaque-analise__economia">
                💰 Economia de <strong><?= formatar_preco($analise['economia_ranking']) ?></strong>
                em relação ao mercado mais caro (<?= htmlspecialchars($analise['pior_mercado']['mercado_nome']) ?>).
            </p>
        <?php endif; ?>
    </section>

    <section class="secao-ranking">
        <h2 class="titulo-secao">Ranking dos mercados</h2>

        <p class="texto-resumo">
            O total é o da cesta inteira: o que o mercado não tem entra pelo menor preço encontrado em outro lugar.
            Mercados que cobrem menos de <?= number_format($analise['cobertura_minima'], 0) ?>% da cesta ficam fora da
            recomendação — com poucos itens cadastrados, o total deles seria estimativa demais.
        </p>

        <ul class="lista-comparacao">
            <?php foreach ($analise['ranking'] as $indice => $mercado): ?>
                <li class="item-comparacao <?= $indice === 0 ? 'item-comparacao--melhor' : '' ?> <?= $mercado['recomendavel'] ? '' : 'item-comparacao--fraco' ?>">
                    <div class="item-comparacao__info">
                        <span class="item-comparacao__mercado">
                            <?php if ($indice === 0): ?><span class="selo-medalha" aria-hidden="true">🏆</span><?php endif; ?>
                            <?= htmlspecialchars($mercado['mercado_nome']) ?>
                        </span>
                        <span class="item-comparacao__data">
                            <?= $mercado['itens_cobertos'] ?> de <?= $analise['total_itens'] ?> itens
                            (<?= number_format($mercado['cobertura'], 0) ?>% da cesta)
                        </span>
                        <?php if (!$mercado['recomendavel']): ?>
                            <span class="etiqueta-aviso">Poucos itens cadastrados para comparar</span>
                        <?php endif; ?>
                        <?php if (!$mercado['completo']): ?>
                            <span class="item-comparacao__endereco">
                                + <?= formatar_preco($mercado['complemento']) ?> estimados nos itens que faltam
                            </span>
                        <?php endif; ?>
                        <?php if ($mercado['acima_do_melhor'] > 0.05): ?>
                            <span class="item-comparacao__endereco">
                                <?= number_format($mercado['acima_do_melhor'], 1, ',', '.') ?>% acima do melhor preço nos itens que tem
                            </span>
                        <?php else: ?>
                            <span class="item-comparacao__endereco">Tem o melhor preço em todos os itens que vende</span>
                        <?php endif; ?>
                    </div>
                    <div class="item-comparacao__preco">
                        <?= formatar_preco($mercado['total_estimado']) ?>
                        <?php if (!$mercado['completo']): ?>
                            <small class="item-comparacao__nota">estimado</small>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="secao-divisao">
        <h2 class="titulo-secao">E se dividir a compra?</h2>

        <p class="texto-resumo">
            Comprando cada item no mercado mais barato, a cesta sai por
            <strong><?= formatar_preco($analise['total_otimo']) ?></strong>
            em <?= count($analise['compra_dividida']) ?> mercado<?= count($analise['compra_dividida']) === 1 ? '' : 's' ?>.
            <?php if ($analise['economia_dividindo'] > 0.01): ?>
                São <strong><?= formatar_preco($analise['economia_dividindo']) ?></strong> a menos que comprar tudo em
                <?= htmlspecialchars($melhor['mercado_nome']) ?> — avalie se compensa o deslocamento.
            <?php else: ?>
                Ou seja: não compensa dividir, <?= htmlspecialchars($melhor['mercado_nome']) ?> já resolve a compra inteira.
            <?php endif; ?>
        </p>

        <div class="grade-mercados">
            <?php foreach ($analise['compra_dividida'] as $grupo): ?>
                <article class="cartao-mercado">
                    <h2>🏬 <?= htmlspecialchars($grupo['mercado_nome']) ?></h2>
                    <p class="cartao-mercado__endereco"><?= count($grupo['itens']) ?> ite<?= count($grupo['itens']) === 1 ? 'm' : 'ns' ?> · <?= formatar_preco($grupo['total']) ?></p>
                    <ul class="lista-itens-mercado">
                        <?php foreach ($grupo['itens'] as $item): ?>
                            <li>
                                <a href="produto.php?id=<?= (int) $item['produto_id'] ?>">
                                    <?= htmlspecialchars(formatar_quantidade($item['quantidade'])) ?>× <?= htmlspecialchars($item['nome']) ?>
                                </a>
                                <span><?= formatar_preco($item['melhor_preco'] * $item['quantidade']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="secao-itens-analise">
        <h2 class="titulo-secao">Itens da cesta</h2>
        <div class="tabela-scroll">
            <table class="tabela-historico">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Qtd.</th>
                        <th>Melhor preço</th>
                        <th>Mercado</th>
                        <th>Diferença</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($analise['itens'] as $item): ?>
                        <?php $diferenca = $item['pior_preco'] - $item['melhor_preco']; ?>
                        <tr>
                            <td>
                                <a href="produto.php?id=<?= (int) $item['produto_id'] ?>"><?= htmlspecialchars($item['nome']) ?></a>
                            </td>
                            <td><?= htmlspecialchars(formatar_quantidade($item['quantidade'])) ?></td>
                            <td><?= formatar_preco($item['melhor_preco']) ?></td>
                            <td><?= htmlspecialchars($analise['mercados'][$item['melhor_mercado_id']] ?? '—') ?></td>
                            <td>
                                <?php if ($diferenca > 0.001): ?>
                                    <span class="diferenca-alta">+<?= formatar_preco($diferenca) ?> no mais caro</span>
                                <?php else: ?>
                                    <span class="diferenca-neutra">preço único</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
