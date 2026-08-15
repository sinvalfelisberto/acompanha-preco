<?php
/** @var array $produtos */
?>
<section class="secao-resumo">
    <p class="texto-resumo" id="texto-resumo">
        <?php if ($produtos): ?>
            <strong><?= count($produtos) ?></strong> produto<?= count($produtos) === 1 ? '' : 's' ?> encontrado<?= count($produtos) === 1 ? '' : 's' ?>
        <?php else: ?>
            Nenhum produto encontrado
        <?php endif; ?>
    </p>
</section>

<?php if (!$produtos): ?>
    <div class="estado-vazio">
        <span class="estado-vazio__icone" aria-hidden="true">🛍️</span>
        <p>Nenhum produto por aqui ainda.</p>
        <a href="adicionar.php" class="botao botao--primario">Cadastrar o primeiro preço</a>
    </div>
<?php else: ?>
    <section class="grade-produtos" id="grade-produtos">
        <?php foreach ($produtos as $produto): ?>
            <a
                href="produto.php?id=<?= (int) $produto['id'] ?>"
                class="cartao-produto"
                data-nome="<?= htmlspecialchars(mb_strtolower($produto['nome']), ENT_QUOTES) ?>"
                data-marca="<?= htmlspecialchars(mb_strtolower((string) $produto['marca']), ENT_QUOTES) ?>"
                data-categoria="<?= htmlspecialchars((string) $produto['categoria'], ENT_QUOTES) ?>"
            >
                <div class="cartao-produto__topo">
                    <h2 class="cartao-produto__nome"><?= htmlspecialchars($produto['nome']) ?></h2>
                    <?php if ($produto['marca']): ?>
                        <p class="cartao-produto__marca"><?= htmlspecialchars($produto['marca']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="cartao-produto__preco">
                    <span class="selo-menor-preco">🏆 Melhor preço</span>
                    <span class="valor"><?= formatar_preco((float) $produto['menor_preco']) ?></span>
                    <span class="unidade">/ <?= htmlspecialchars($produto['unidade']) ?></span>
                </div>

                <div class="cartao-produto__rodape">
                    <span>🏬 <?= htmlspecialchars($produto['mercado_menor_preco']) ?></span>
                    <span class="separador">·</span>
                    <span><?= (int) $produto['mercados_comparados'] ?> mercado<?= (int) $produto['mercados_comparados'] === 1 ? '' : 's' ?> comparado<?= (int) $produto['mercados_comparados'] === 1 ? '' : 's' ?></span>
                </div>

                <?php if ($produto['categoria']): ?>
                    <span class="etiqueta-categoria"><?= htmlspecialchars($produto['categoria']) ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </section>

    <div class="estado-vazio" id="sem-resultados-filtro" hidden>
        <span class="estado-vazio__icone" aria-hidden="true">🔎</span>
        <p>Nenhum produto encontrado para esse filtro.</p>
    </div>
<?php endif; ?>
