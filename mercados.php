<?php
require_once __DIR__ . '/includes/functions.php';

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

<?php if (!$mercados): ?>
    <div class="estado-vazio">
        <span class="estado-vazio__icone" aria-hidden="true">🏬</span>
        <p>Nenhum mercado cadastrado ainda.</p>
        <a href="adicionar.php" class="botao botao--primario">Adicionar um preço</a>
    </div>
<?php else: ?>
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
