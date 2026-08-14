# Compara Preços

Aplicação web para acompanhar e comparar preços de produtos entre diferentes mercados. Cadastre um produto, o preço encontrado e o mercado da consulta — o app mostra automaticamente qual mercado tem o melhor preço para cada item.

Feita em PHP puro (sem framework), HTML e CSS, com visual moderno, responsivo (mobile e desktop) e temas claro/escuro.

🔗 Em produção: https://felisberto.com.br/acompanha-preco/

## Funcionalidades

- Listagem de produtos com o menor preço em destaque e o mercado correspondente
- Busca por nome/marca e filtro por categoria
- Comparação completa entre mercados para cada produto, com % de economia e histórico de preços
- Cadastro de produto + preço + mercado, com autocomplete para evitar duplicidade
- Listagem de mercados cadastrados
- Login com Google (OAuth 2.0) — necessário para cadastrar preços
- Tema claro/escuro (segue a preferência do sistema, com alternância manual salva no navegador)

## Stack

- PHP 8+ com PDO (MySQL/MariaDB)
- HTML, CSS e JavaScript puros (sem build step)
- [league/oauth2-google](https://github.com/thephpleague/oauth2-google) para o login com Google

## Estrutura do banco

- `produtos` — nome, marca, categoria, unidade
- `mercados` — nome, endereço
- `precos` — preço, data da consulta, ligado a um produto e a um mercado
- `usuarios` — dados de quem faz login com Google (google_id, nome, email, avatar)

## Como rodar localmente

### Pré-requisitos

- PHP 8+ com as extensões `pdo_mysql` habilitadas
- [Composer](https://getcomposer.org/)
- Um banco MySQL/MariaDB acessível

### Passo a passo

```bash
git clone <url-do-repositorio>
cd acompanha-preco
composer install
cp .env.example .env.dev
```

Edite o `.env.dev` com os dados do seu banco:

```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=nome_do_banco
DB_USER=usuario
DB_PASS=senha
```

Crie as tabelas `produtos`, `mercados`, `precos` e `usuarios` no banco (veja a estrutura em [Estrutura do banco](#estrutura-do-banco)).

Suba o servidor embutido do PHP:

```bash
php -S 127.0.0.1:8099
```

Acesse http://127.0.0.1:8099.

> A aplicação carrega `.env` (produção) se ele existir; caso contrário, usa `.env.dev`. Isso permite manter configurações separadas por ambiente sem trocar código.

### Configurando o login com Google

1. Crie um projeto e credenciais OAuth em [console.cloud.google.com/apis/credentials](https://console.cloud.google.com/apis/credentials), tipo **Web application**.
2. Em **Origens JavaScript autorizadas**, informe apenas o domínio (sem caminho e sem barra final), ex: `http://localhost:8099`.
3. Em **URIs de redirecionamento autorizados**, informe a URL completa do callback, ex: `http://localhost:8099/auth/callback.php`.
4. Preencha no `.env`/`.env.dev`:

```
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost:8099/auth/callback.php
```

Sem essas variáveis configuradas, o app funciona normalmente para visualizar preços — apenas o cadastro de novos preços fica bloqueado atrás do login.

## Variáveis de ambiente

Veja todas as variáveis disponíveis em [.env.example](.env.example).
