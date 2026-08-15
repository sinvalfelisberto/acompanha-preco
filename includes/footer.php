</main>

<nav class="nav-mobile" aria-label="Navegação principal">
    <a href="index.php" class="<?= $paginaAtiva === 'inicio' ? 'ativo' : '' ?>">
        <span aria-hidden="true">🏠</span>
        <span>Início</span>
    </a>
    <a href="adicionar.php" class="nav-mobile__destaque <?= $paginaAtiva === 'adicionar' ? 'ativo' : '' ?>">
        <span aria-hidden="true">➕</span>
        <span>Adicionar</span>
    </a>
    <a href="mercados.php" class="<?= $paginaAtiva === 'mercados' ? 'ativo' : '' ?>">
        <span aria-hidden="true">🏬</span>
        <span>Mercados</span>
    </a>
</nav>

<?php if ($paginaAtiva === 'mercados'): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<?php endif; ?>
<script src="assets/js/app.js"></script>
</body>
</html>
