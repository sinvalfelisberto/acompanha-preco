</main>

<div id="modal-confirmacao" class="modal-overlay" hidden>
    <div class="modal-caixa" role="alertdialog" aria-modal="true" aria-labelledby="modal-titulo">
        <span class="modal-icone" aria-hidden="true">🗑️</span>
        <h2 id="modal-titulo">Confirmar exclusão</h2>
        <p id="modal-mensagem"></p>
        <div class="modal-acoes">
            <button type="button" id="modal-cancelar" class="botao botao--secundario botao--bloco">Cancelar</button>
            <button type="button" id="modal-confirmar" class="botao botao--bloco botao--perigo">🗑️ Excluir</button>
        </div>
    </div>
</div>

<?php if ($paginaAtiva === 'mercados'): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<?php endif; ?>
<?php if ($paginaAtiva === 'inicio'): ?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPGmkzjNSjY=" crossorigin="anonymous"></script>
<?php endif; ?>
<script src="assets/js/app.js"></script>
</body>
</html>
