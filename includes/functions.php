<?php

require_once __DIR__ . '/db.php';

/**
 * Lista produtos com o menor preço mais recente encontrado e em quantos mercados há registro.
 */
function listar_produtos(?string $busca = null, ?string $categoria = null): array
{
    $pdo = obter_conexao();

    $sql = "
        SELECT
            p.id,
            p.nome,
            p.marca,
            p.categoria,
            p.unidade,
            ultimos.mercados_comparados,
            ultimos.menor_preco,
            ultimos.mercado_menor_preco,
            ultimos.ultima_atualizacao
        FROM produtos p
        INNER JOIN (
            SELECT
                r.produto_id,
                COUNT(*) AS mercados_comparados,
                MIN(r.preco) AS menor_preco,
                SUBSTRING_INDEX(GROUP_CONCAT(r.mercado_nome ORDER BY r.preco ASC), ',', 1) AS mercado_menor_preco,
                MAX(r.data_registro) AS ultima_atualizacao
            FROM (
                SELECT
                    pr.produto_id,
                    pr.mercado_id,
                    pr.preco,
                    pr.data_registro,
                    m.nome AS mercado_nome
                FROM precos pr
                INNER JOIN mercados m ON m.id = pr.mercado_id
                WHERE pr.id = (
                    SELECT p2.id
                    FROM precos p2
                    WHERE p2.produto_id = pr.produto_id AND p2.mercado_id = pr.mercado_id
                    ORDER BY p2.data_registro DESC, p2.id DESC
                    LIMIT 1
                )
            ) r
            GROUP BY r.produto_id
        ) ultimos ON ultimos.produto_id = p.id
        WHERE 1 = 1
    ";

    $parametros = [];

    if ($busca !== null && $busca !== '') {
        $sql .= " AND (p.nome LIKE :busca_nome OR p.marca LIKE :busca_marca) ";
        $parametros['busca_nome'] = '%' . $busca . '%';
        $parametros['busca_marca'] = '%' . $busca . '%';
    }

    if ($categoria !== null && $categoria !== '') {
        $sql .= " AND p.categoria = :categoria ";
        $parametros['categoria'] = $categoria;
    }

    $sql .= " ORDER BY p.nome ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);

    return $stmt->fetchAll();
}

function listar_categorias(): array
{
    $pdo = obter_conexao();
    $stmt = $pdo->query('SELECT nome FROM categorias ORDER BY nome ASC');

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Lista categorias com a quantidade de produtos que as usam atualmente.
 */
function listar_categorias_com_uso(): array
{
    $pdo = obter_conexao();
    $stmt = $pdo->query('
        SELECT c.id, c.nome, COUNT(p.id) AS total_produtos
        FROM categorias c
        LEFT JOIN produtos p ON p.categoria = c.nome
        GROUP BY c.id, c.nome
        ORDER BY c.nome ASC
    ');

    return $stmt->fetchAll();
}

function obter_categoria(int $id): ?array
{
    $pdo = obter_conexao();
    $stmt = $pdo->prepare('SELECT * FROM categorias WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $categoria = $stmt->fetch();

    return $categoria ?: null;
}

function categoria_existe(string $nome, ?int $exceto_id = null): bool
{
    $pdo = obter_conexao();
    $sql = 'SELECT COUNT(*) FROM categorias WHERE LOWER(nome) = LOWER(:nome)';
    $parametros = ['nome' => $nome];

    if ($exceto_id !== null) {
        $sql .= ' AND id <> :exceto_id';
        $parametros['exceto_id'] = $exceto_id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);

    return (bool) $stmt->fetchColumn();
}

function inserir_categoria(string $nome): int
{
    $pdo = obter_conexao();
    $stmt = $pdo->prepare('INSERT INTO categorias (nome) VALUES (:nome)');
    $stmt->execute(['nome' => $nome]);

    return (int) $pdo->lastInsertId();
}

function atualizar_categoria(int $id, string $nomeNovo): void
{
    $pdo = obter_conexao();
    $categoria = obter_categoria($id);

    if (!$categoria) {
        return;
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('UPDATE categorias SET nome = :nome WHERE id = :id');
    $stmt->execute(['id' => $id, 'nome' => $nomeNovo]);

    $stmt = $pdo->prepare('UPDATE produtos SET categoria = :nome_novo WHERE categoria = :nome_antigo');
    $stmt->execute(['nome_novo' => $nomeNovo, 'nome_antigo' => $categoria['nome']]);

    $pdo->commit();
}

function excluir_categoria(int $id): void
{
    $pdo = obter_conexao();
    $stmt = $pdo->prepare('DELETE FROM categorias WHERE id = :id');
    $stmt->execute(['id' => $id]);
}

function obter_produto(int $id): ?array
{
    $pdo = obter_conexao();
    $stmt = $pdo->prepare('SELECT * FROM produtos WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $produto = $stmt->fetch();

    return $produto ?: null;
}

/**
 * Retorna o preço mais recente de cada mercado para um produto, do mais barato ao mais caro.
 */
function obter_precos_produto(int $produtoId): array
{
    $pdo = obter_conexao();
    $stmt = $pdo->prepare("
        SELECT pr.id, pr.mercado_id, m.nome AS mercado_nome, m.endereco, pr.preco, pr.data_registro, pr.observacao
        FROM precos pr
        INNER JOIN mercados m ON m.id = pr.mercado_id
        WHERE pr.produto_id = :produto_id
        AND pr.id = (
            SELECT p2.id
            FROM precos p2
            WHERE p2.produto_id = pr.produto_id AND p2.mercado_id = pr.mercado_id
            ORDER BY p2.data_registro DESC, p2.id DESC
            LIMIT 1
        )
        ORDER BY pr.preco ASC
    ");
    $stmt->execute(['produto_id' => $produtoId]);

    return $stmt->fetchAll();
}

/**
 * Retorna todo o histórico de preços de um produto (todas as datas e mercados).
 */
function obter_historico_produto(int $produtoId): array
{
    $pdo = obter_conexao();
    $stmt = $pdo->prepare("
        SELECT pr.id, pr.mercado_id, m.nome AS mercado_nome, pr.preco, pr.data_registro, pr.observacao
        FROM precos pr
        INNER JOIN mercados m ON m.id = pr.mercado_id
        WHERE pr.produto_id = :produto_id
        ORDER BY pr.data_registro DESC, pr.id DESC
    ");
    $stmt->execute(['produto_id' => $produtoId]);

    return $stmt->fetchAll();
}

function listar_mercados(): array
{
    $pdo = obter_conexao();
    $stmt = $pdo->query('SELECT * FROM mercados ORDER BY nome ASC');

    return $stmt->fetchAll();
}

function buscar_produtos_autocomplete(string $termo): array
{
    $pdo = obter_conexao();
    $stmt = $pdo->prepare("
        SELECT id, nome, marca, categoria, unidade
        FROM produtos
        WHERE nome LIKE :termo
        ORDER BY nome ASC
        LIMIT 10
    ");
    $stmt->execute(['termo' => '%' . $termo . '%']);

    return $stmt->fetchAll();
}

function criar_ou_obter_produto(string $nome, ?string $marca, ?string $categoria, string $unidade): int
{
    $pdo = obter_conexao();

    $stmt = $pdo->prepare('SELECT id FROM produtos WHERE nome = :nome LIMIT 1');
    $stmt->execute(['nome' => $nome]);
    $existente = $stmt->fetchColumn();

    if ($existente) {
        return (int) $existente;
    }

    $stmt = $pdo->prepare('
        INSERT INTO produtos (nome, marca, categoria, unidade)
        VALUES (:nome, :marca, :categoria, :unidade)
    ');
    $stmt->execute([
        'nome' => $nome,
        'marca' => $marca ?: null,
        'categoria' => $categoria ?: null,
        'unidade' => $unidade ?: 'un',
    ]);

    return (int) $pdo->lastInsertId();
}

function criar_ou_obter_mercado(string $nome, ?string $endereco): int
{
    $pdo = obter_conexao();

    $stmt = $pdo->prepare('SELECT id FROM mercados WHERE nome = :nome LIMIT 1');
    $stmt->execute(['nome' => $nome]);
    $existente = $stmt->fetchColumn();

    if ($existente) {
        return (int) $existente;
    }

    $stmt = $pdo->prepare('
        INSERT INTO mercados (nome, endereco)
        VALUES (:nome, :endereco)
    ');
    $stmt->execute([
        'nome' => $nome,
        'endereco' => $endereco ?: null,
    ]);

    return (int) $pdo->lastInsertId();
}

function mercado_existe(string $nome, ?int $exceto_id = null): bool
{
    $pdo = obter_conexao();
    $sql = 'SELECT COUNT(*) FROM mercados WHERE LOWER(nome) = LOWER(:nome)';
    $parametros = ['nome' => $nome];

    if ($exceto_id !== null) {
        $sql .= ' AND id <> :exceto_id';
        $parametros['exceto_id'] = $exceto_id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);

    return (bool) $stmt->fetchColumn();
}

function obter_mercado(int $id): ?array
{
    $pdo = obter_conexao();
    $stmt = $pdo->prepare('SELECT * FROM mercados WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $mercado = $stmt->fetch();

    return $mercado ?: null;
}

function atualizar_mercado(int $id, string $nome, ?string $endereco): void
{
    $pdo = obter_conexao();
    $stmt = $pdo->prepare('UPDATE mercados SET nome = :nome, endereco = :endereco WHERE id = :id');
    $stmt->execute([
        'id' => $id,
        'nome' => $nome,
        'endereco' => $endereco ?: null,
    ]);
}

function inserir_mercado(string $nome, ?string $endereco): int
{
    $pdo = obter_conexao();
    $stmt = $pdo->prepare('INSERT INTO mercados (nome, endereco) VALUES (:nome, :endereco)');
    $stmt->execute([
        'nome' => $nome,
        'endereco' => $endereco ?: null,
    ]);

    return (int) $pdo->lastInsertId();
}

function inserir_preco(int $produtoId, int $mercadoId, float $preco, string $data, ?string $observacao): void
{
    $pdo = obter_conexao();
    $stmt = $pdo->prepare('
        INSERT INTO precos (produto_id, mercado_id, preco, data_registro, observacao)
        VALUES (:produto_id, :mercado_id, :preco, :data_registro, :observacao)
        ON DUPLICATE KEY UPDATE observacao = VALUES(observacao)
    ');
    $stmt->execute([
        'produto_id' => $produtoId,
        'mercado_id' => $mercadoId,
        'preco' => $preco,
        'data_registro' => $data,
        'observacao' => $observacao ?: null,
    ]);
}

function obter_preco(int $id): ?array
{
    $pdo = obter_conexao();
    $stmt = $pdo->prepare('
        SELECT pr.*, p.nome AS produto_nome, m.nome AS mercado_nome
        FROM precos pr
        INNER JOIN produtos p ON p.id = pr.produto_id
        INNER JOIN mercados m ON m.id = pr.mercado_id
        WHERE pr.id = :id
    ');
    $stmt->execute(['id' => $id]);
    $preco = $stmt->fetch();

    return $preco ?: null;
}

function atualizar_preco(int $id, int $mercadoId, float $preco, string $data, ?string $observacao): void
{
    $pdo = obter_conexao();
    $stmt = $pdo->prepare('
        UPDATE precos
        SET mercado_id = :mercado_id, preco = :preco, data_registro = :data_registro, observacao = :observacao
        WHERE id = :id
    ');
    $stmt->execute([
        'id' => $id,
        'mercado_id' => $mercadoId,
        'preco' => $preco,
        'data_registro' => $data,
        'observacao' => $observacao ?: null,
    ]);
}

function excluir_preco(int $id): void
{
    $pdo = obter_conexao();
    $stmt = $pdo->prepare('DELETE FROM precos WHERE id = :id');
    $stmt->execute(['id' => $id]);
}

function excluir_mercado(int $id): void
{
    $pdo = obter_conexao();
    $stmt = $pdo->prepare('DELETE FROM mercados WHERE id = :id');
    $stmt->execute(['id' => $id]);
}

function criar_ou_atualizar_usuario(string $googleId, string $nome, string $email, ?string $avatarUrl): int
{
    $pdo = obter_conexao();
    $stmt = $pdo->prepare('
        INSERT INTO usuarios (google_id, nome, email, avatar_url)
        VALUES (:google_id, :nome, :email, :avatar_url)
        ON DUPLICATE KEY UPDATE
            nome = VALUES(nome),
            email = VALUES(email),
            avatar_url = VALUES(avatar_url),
            ultimo_login = CURRENT_TIMESTAMP
    ');
    $stmt->execute([
        'google_id' => $googleId,
        'nome' => $nome,
        'email' => $email,
        'avatar_url' => $avatarUrl,
    ]);

    if ($pdo->lastInsertId()) {
        return (int) $pdo->lastInsertId();
    }

    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE google_id = :google_id');
    $stmt->execute(['google_id' => $googleId]);

    return (int) $stmt->fetchColumn();
}

function formatar_preco(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function formatar_data(string $data): string
{
    $timestamp = strtotime($data);

    return $timestamp ? date('d/m/Y', $timestamp) : $data;
}
