<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pageTitle = 'Clientes';
$clientes = $pdo->query(
    "SELECT c.id, c.nome, c.telefone, c.email, c.created_at,
            u.id AS usuario_id, u.ativo,
            (SELECT COUNT(*) FROM reservas r WHERE r.cliente_id = c.id) AS reservas_count
     FROM clientes c
     LEFT JOIN usuarios u ON u.cliente_id = c.id
     ORDER BY c.nome"
)->fetchAll();
require __DIR__ . '/../partials/header.php';
?>
<div class="page-heading">
    <div>
        <h2>Clientes</h2>
        <p>Consulte os dados de contato e gerencie o acesso às contas.</p>
    </div>
</div>
<section class="card">
    <div class="data-table-wrap">
        <table class="data-table">
            <thead><tr><th>Cliente</th><th>Contato</th><th>Reservas</th><th>Conta</th><th>Ações</th></tr></thead>
            <tbody>
            <?php foreach ($clientes as $c): ?>
                <tr>
                    <td><div class="cell-title"><?= e($c['nome']) ?></div><div class="cell-sub">ID #<?= (int)$c['id'] ?></div></td>
                    <td><div><?= e($c['email'] ?: 'Sem e-mail') ?></div><div class="cell-sub"><?= e($c['telefone'] ?: 'Sem telefone') ?></div></td>
                    <td><?= (int)$c['reservas_count'] ?></td>
                    <td>
                        <?php if ($c['usuario_id']): ?>
                            <span class="status-badge <?= (int)$c['ativo'] ? 'status-disponivel' : 'status-cancelada' ?>"><?= (int)$c['ativo'] ? 'Ativa' : 'Inativa' ?></span>
                            <form method="post" action="<?= url('clientes/status.php') ?>" style="margin-top:8px">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="usuario_id" value="<?= (int)$c['usuario_id'] ?>">
                                <select class="form-control" name="ativo" onchange="this.form.submit()" aria-label="Alterar estado da conta">
                                    <option value="1" <?= (int)$c['ativo'] ? 'selected' : '' ?>>Ativa</option>
                                    <option value="0" <?= !(int)$c['ativo'] ? 'selected' : '' ?>>Inativa</option>
                                </select>
                            </form>
                        <?php else: ?><span class="cell-sub">Sem conta vinculada</span><?php endif; ?>
                    </td>
                    <td><div class="row-actions">
                        <a class="icon-action danger" data-confirm="Excluir este cliente? Reservas vinculadas podem impedir a exclusão." href="<?= url('clientes/delete.php?id=' . (int)$c['id']) ?>" title="Excluir cliente"><span class="material-symbols-outlined">delete</span></a>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$clientes): ?><div class="empty-state"><span class="material-symbols-outlined">groups</span><p>Nenhum cliente cadastrado.</p></div><?php endif; ?>
</section>
<?php require __DIR__ . '/../partials/footer.php'; ?>
