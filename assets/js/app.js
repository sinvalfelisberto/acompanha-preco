(function () {
    'use strict';

    /* ---------- Alternância de tema claro/escuro ---------- */

    var raiz = document.documentElement;
    var botaoTema = document.getElementById('botao-tema');
    var iconeTema = document.getElementById('botao-tema-icone');
    var textoTema = document.getElementById('botao-tema-texto');

    function temaAtual() {
        return raiz.getAttribute('data-tema') === 'escuro' ? 'escuro' : 'claro';
    }

    function atualizarIconeTema() {
        if (!botaoTema) return;
        var escuro = temaAtual() === 'escuro';
        if (iconeTema) iconeTema.textContent = escuro ? '☀️' : '🌙';
        if (textoTema) textoTema.textContent = escuro ? 'Tema claro' : 'Tema escuro';
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

    /* ---------- Saudação (calculada no dispositivo do usuário) ---------- */

    var saudacaoTexto = document.getElementById('saudacao-texto');
    if (saudacaoTexto) {
        var horaLocal = new Date().getHours();
        var saudacao = horaLocal < 12 ? 'Bom dia,' : (horaLocal < 18 ? 'Boa tarde,' : 'Boa noite,');
        saudacaoTexto.textContent = saudacao;
    }

    /* ---------- Menu hambúrguer (painel lateral) ---------- */

    var botaoMenu = document.getElementById('botao-menu');
    var menuLateral = document.getElementById('menu-lateral');
    var menuOverlay = document.getElementById('menu-overlay');
    var botaoFecharMenu = document.getElementById('botao-fechar-menu');

    if (botaoMenu && menuLateral && menuOverlay) {
        var abrirMenu = function () {
            menuLateral.classList.add('aberto');
            menuOverlay.classList.add('aberto');
            menuLateral.setAttribute('aria-hidden', 'false');
            botaoMenu.setAttribute('aria-expanded', 'true');
            if (botaoFecharMenu) botaoFecharMenu.focus();
            document.body.style.overflow = 'hidden';
        };

        var fecharMenu = function () {
            menuLateral.classList.remove('aberto');
            menuOverlay.classList.remove('aberto');
            menuLateral.setAttribute('aria-hidden', 'true');
            botaoMenu.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
            botaoMenu.focus();
        };

        botaoMenu.addEventListener('click', abrirMenu);
        if (botaoFecharMenu) botaoFecharMenu.addEventListener('click', fecharMenu);
        menuOverlay.addEventListener('click', fecharMenu);

        document.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape' && menuLateral.classList.contains('aberto')) {
                fecharMenu();
            }
        });
    }

    /* ---------- Modal de confirmação para exclusão de preços e mercados ---------- */

    var modalOverlay = document.getElementById('modal-confirmacao');
    var formsExcluir = document.querySelectorAll('.form-excluir[data-confirm]');

    if (modalOverlay && formsExcluir.length) {
        var modalMensagem = document.getElementById('modal-mensagem');
        var modalCancelar = document.getElementById('modal-cancelar');
        var modalConfirmar = document.getElementById('modal-confirmar');
        var formPendente = null;

        var fecharModal = function () {
            modalOverlay.hidden = true;
            formPendente = null;
        };

        var abrirModal = function (form) {
            formPendente = form;
            modalMensagem.textContent = form.dataset.confirm;
            modalOverlay.hidden = false;
            modalConfirmar.focus();
        };

        formsExcluir.forEach(function (form) {
            form.addEventListener('submit', function (evento) {
                evento.preventDefault();
                abrirModal(form);
            });
        });

        modalCancelar.addEventListener('click', fecharModal);

        modalConfirmar.addEventListener('click', function () {
            var form = formPendente;
            modalOverlay.hidden = true;
            formPendente = null;
            if (form) form.submit();
        });

        modalOverlay.addEventListener('click', function (evento) {
            if (evento.target === modalOverlay) fecharModal();
        });

        document.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape' && !modalOverlay.hidden) fecharModal();
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

    /* ---------- Mapa de localização com mercados próximos (Leaflet + OpenStreetMap) ---------- */

    var botaoLocalizacao = document.getElementById('botao-localizacao');

    if (botaoLocalizacao && typeof L !== 'undefined') {
        var statusLocalizacao = document.getElementById('status-localizacao');
        var campoDestino = document.getElementById(botaoLocalizacao.dataset.campoDestino);
        var campoNome = document.getElementById(botaoLocalizacao.dataset.campoNome);
        var containerMapa = document.getElementById('mapa-localizacao');
        var dicaMapa = document.getElementById('dica-mapa');
        var mapaLeaflet = null;
        var marcadorSelecionado = null;

        var definirStatus = function (texto) {
            if (statusLocalizacao) statusLocalizacao.textContent = texto;
        };

        var preencherCampos = function (nome, endereco) {
            if (endereco && campoDestino) campoDestino.value = endereco;
            if (nome && campoNome) campoNome.value = nome;
        };

        var buscarEnderecoPorCoordenada = function (lat, lng, nomeConhecido) {
            var url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + lat
                + '&lon=' + lng + '&accept-language=pt-BR&namedetails=1&zoom=18';

            definirStatus('🔎 Buscando endereço...');

            fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(function (resposta) { return resposta.json(); })
                .then(function (dados) {
                    if (!dados || !dados.display_name) {
                        definirStatus('Não foi possível encontrar o endereço dessa localização.');
                        return;
                    }

                    var nomeEncontrado = nomeConhecido
                        || dados.name
                        || (dados.namedetails && dados.namedetails.name)
                        || (dados.address && (dados.address.shop || dados.address.supermarket || dados.address.marketplace));

                    preencherCampos(nomeEncontrado, dados.display_name);
                    definirStatus(nomeEncontrado ? '✅ Nome e endereço preenchidos.' : '✅ Endereço preenchido. Preencha o nome manualmente.');
                })
                .catch(function () {
                    definirStatus('Erro ao consultar o serviço de endereços. Tente novamente.');
                });
        };

        var iconePin = function (emoji) {
            return L.divIcon({
                html: '<span class="marcador-mapa">' + emoji + '</span>',
                className: '',
                iconSize: [28, 28],
                iconAnchor: [14, 26]
            });
        };

        var moverMarcadorSelecionado = function (lat, lng) {
            if (marcadorSelecionado) {
                marcadorSelecionado.setLatLng([lat, lng]);
            } else {
                marcadorSelecionado = L.marker([lat, lng], { icon: iconePin('📍') }).addTo(mapaLeaflet);
            }
        };

        var construirEnderecoDeTags = function (tags) {
            var partes = [];
            var rua = tags['addr:street'] || '';
            if (rua && tags['addr:housenumber']) rua += ', ' + tags['addr:housenumber'];
            if (rua) partes.push(rua);
            if (tags['addr:suburb']) partes.push(tags['addr:suburb']);
            if (tags['addr:city']) partes.push(tags['addr:city']);
            if (tags['addr:postcode']) partes.push(tags['addr:postcode']);
            return partes.length ? partes.join(', ') : null;
        };

        var carregarMercadosProximos = function (lat, lng) {
            var consulta = '[out:json][timeout:15];('
                + 'nwr["shop"~"supermarket|convenience|grocery|greengrocer|department_store"](around:400,' + lat + ',' + lng + ');'
                + 'nwr["amenity"="marketplace"](around:400,' + lat + ',' + lng + ');'
                + ');out center;';

            fetch('https://overpass-api.de/api/interpreter', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'data=' + encodeURIComponent(consulta)
            })
                .then(function (resposta) { return resposta.json(); })
                .then(function (dados) {
                    (dados.elements || []).forEach(function (elemento) {
                        if (!elemento.tags || !elemento.tags.name) return;

                        var pontoLat = elemento.lat || (elemento.center && elemento.center.lat);
                        var pontoLng = elemento.lon || (elemento.center && elemento.center.lon);
                        if (!pontoLat || !pontoLng) return;

                        var enderecoTags = construirEnderecoDeTags(elemento.tags);
                        var marcador = L.marker([pontoLat, pontoLng], { icon: iconePin('🏬') }).addTo(mapaLeaflet);

                        var popupDiv = document.createElement('div');
                        popupDiv.className = 'popup-mercado-mapa';

                        var titulo = document.createElement('strong');
                        titulo.textContent = elemento.tags.name;
                        popupDiv.appendChild(titulo);

                        var botao = document.createElement('button');
                        botao.type = 'button';
                        botao.textContent = 'Usar este mercado';
                        botao.addEventListener('click', function () {
                            if (enderecoTags) {
                                preencherCampos(elemento.tags.name, enderecoTags);
                                definirStatus('✅ Nome e endereço preenchidos a partir do mapa.');
                            } else {
                                buscarEnderecoPorCoordenada(pontoLat, pontoLng, elemento.tags.name);
                            }
                            mapaLeaflet.closePopup();
                        });
                        popupDiv.appendChild(botao);

                        marcador.bindPopup(popupDiv);
                    });
                })
                .catch(function () {
                    /* falha silenciosa: usuário ainda pode tocar no mapa manualmente */
                });
        };

        var abrirMapa = function (lat, lng) {
            containerMapa.hidden = false;
            dicaMapa.hidden = false;

            if (!mapaLeaflet) {
                mapaLeaflet = L.map(containerMapa).setView([lat, lng], 17);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© colaboradores do OpenStreetMap',
                    maxZoom: 19
                }).addTo(mapaLeaflet);

                mapaLeaflet.on('click', function (evento) {
                    moverMarcadorSelecionado(evento.latlng.lat, evento.latlng.lng);
                    buscarEnderecoPorCoordenada(evento.latlng.lat, evento.latlng.lng);
                });
            } else {
                mapaLeaflet.setView([lat, lng], 17);
                mapaLeaflet.invalidateSize();
            }

            moverMarcadorSelecionado(lat, lng);
            carregarMercadosProximos(lat, lng);
        };

        botaoLocalizacao.addEventListener('click', function () {
            if (!navigator.geolocation) {
                definirStatus('Seu navegador não suporta localização automática.');
                return;
            }

            botaoLocalizacao.disabled = true;
            definirStatus('📡 Obtendo sua localização...');

            navigator.geolocation.getCurrentPosition(
                function (posicao) {
                    var lat = posicao.coords.latitude;
                    var lng = posicao.coords.longitude;

                    botaoLocalizacao.disabled = false;
                    abrirMapa(lat, lng);
                    buscarEnderecoPorCoordenada(lat, lng);
                },
                function (erro) {
                    botaoLocalizacao.disabled = false;
                    if (erro.code === erro.PERMISSION_DENIED) {
                        definirStatus('Permissão de localização negada. Você pode digitar o endereço manualmente.');
                    } else {
                        definirStatus('Não foi possível obter sua localização. Tente novamente.');
                    }
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
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

/* ---------- Busca instantânea de produtos (jQuery) ---------- */

if (window.jQuery) {
    jQuery(function ($) {
        var $form = $('#form-busca');
        var $busca = $('#busca-input');
        var $categoria = $('#categoria-select');
        var $resultados = $('#resultados-produtos');

        if (!$form.length || !$resultados.length) {
            return;
        }

        var temporizadorBusca = null;

        function buscar() {
            var parametros = {
                ajax: 1,
                busca: $busca.val(),
                categoria: $categoria.val()
            };

            $resultados.attr('aria-busy', 'true');

            $.get('index.php', parametros)
                .done(function (html) {
                    $resultados.html(html);

                    var urlParams = $.param({ busca: parametros.busca, categoria: parametros.categoria });
                    var novaUrl = window.location.pathname + (urlParams ? '?' + urlParams : '');
                    window.history.replaceState(null, '', novaUrl);
                })
                .always(function () {
                    $resultados.removeAttr('aria-busy');
                });
        }

        $busca.on('input', function () {
            window.clearTimeout(temporizadorBusca);
            temporizadorBusca = window.setTimeout(buscar, 300);
        });

        $categoria.on('change', function () {
            window.clearTimeout(temporizadorBusca);
            buscar();
        });

        $form.on('submit', function (evento) {
            evento.preventDefault();
            window.clearTimeout(temporizadorBusca);
            buscar();
        });
    });
}
