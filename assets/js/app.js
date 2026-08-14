(function () {
    'use strict';

    /* ---------- Alternância de tema claro/escuro ---------- */

    var raiz = document.documentElement;
    var botaoTema = document.getElementById('botao-tema');

    function temaAtual() {
        return raiz.getAttribute('data-tema') === 'escuro' ? 'escuro' : 'claro';
    }

    function atualizarIconeTema() {
        if (!botaoTema) return;
        var icone = botaoTema.querySelector('.botao-tema__icone');
        var escuro = temaAtual() === 'escuro';
        icone.textContent = escuro ? '☀️' : '🌙';
        botaoTema.setAttribute('aria-label', escuro ? 'Mudar para tema claro' : 'Mudar para tema escuro');
    }

    if (!raiz.hasAttribute('data-tema')) {
        var prefereEscuro = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        raiz.setAttribute('data-tema', prefereEscuro ? 'escuro' : 'claro');
    }
    atualizarIconeTema();

    if (botaoTema) {
        botaoTema.addEventListener('click', function () {
            var novoTema = temaAtual() === 'escuro' ? 'claro' : 'escuro';
            raiz.setAttribute('data-tema', novoTema);
            localStorage.setItem('tema', novoTema);
            atualizarIconeTema();
        });
    }

    /* ---------- Preenchimento automático ao escolher produto existente ---------- */

    var campoProduto = document.getElementById('produto_nome');
    var scriptDados = document.getElementById('dados-produtos');

    if (campoProduto && scriptDados) {
        var produtos = [];
        try {
            produtos = JSON.parse(scriptDados.textContent || '[]');
        } catch (erro) {
            produtos = [];
        }

        var campoMarca = document.getElementById('marca');
        var campoCategoria = document.getElementById('categoria');
        var campoUnidade = document.getElementById('unidade');

        campoProduto.addEventListener('input', function () {
            var nomeDigitado = campoProduto.value.trim().toLowerCase();
            var encontrado = produtos.find(function (p) {
                return p.nome.trim().toLowerCase() === nomeDigitado;
            });

            if (encontrado) {
                if (campoMarca && encontrado.marca) campoMarca.value = encontrado.marca;
                if (campoCategoria && encontrado.categoria) campoCategoria.value = encontrado.categoria;
                if (campoUnidade && encontrado.unidade) campoUnidade.value = encontrado.unidade;
            }
        });
    }

    /* ---------- Máscara simples para o campo de preço ---------- */

    var campoPreco = document.getElementById('preco');
    if (campoPreco) {
        campoPreco.addEventListener('input', function () {
            var valor = campoPreco.value.replace(/[^0-9,]/g, '');
            var partes = valor.split(',');
            if (partes.length > 2) {
                valor = partes[0] + ',' + partes.slice(1).join('');
            }
            campoPreco.value = valor;
        });
    }
})();
