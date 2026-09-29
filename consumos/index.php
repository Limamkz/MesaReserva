<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pageTitle='Consumos';
$consumos=$pdo->query(
"SELECT c.*,m.numero mesa_numero,(c.quantidade*c.valor_unitario) total
FROM consumos c INNER JOIN mesas m ON m.id=c.mesa_id
ORDER BY c.created_at DESC"
)->fetchAll();
$total=(float)$pdo->query("SELECT COALESCE(SUM(quantidade*valor_unitario),0) FROM consumos")->fetchColumn();
require __DIR__.'/../partials/header.php';
?>
<div class="page-heading">
    <div>
        <h2>Consumos</h2>
        <p>Registre itens e acompanhe o valor gasto em cada mesa.</p>
    </div>
    <a class="btn btn-primary" href="<?= url('consumos/form.php') ?>">
        <span class="material-symbols-outlined">add_shopping_cart</span>Novo consumo</a>
        </div>
        <section class="stats-grid" style="grid-template-columns:1fr 1fr">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon">
                        <span class="material-symbols-outlined">receipt_long</span>
                    </div>
                </div>
                <div class="stat-value">
                    <?= count($consumos) ?>
                </div>
                <div class="stat-label">Lançamentos registrados</div>
            </div>
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon">
                        <span class="material-symbols-outlined">payments</span>
                    </div>
                </div>
                <div class="stat-value">
                    <?= money($total) ?>
                </div>
                <div class="stat-label">Valor total registrado</div>
            </div>
        </section>
        <section class="card">
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Mesa</th>
                            <th>Descrição</th>
                            <th>Qtd.</th>
                            <th>Unitário</th>
                            <th>Total</th>
                            <th>Data</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($consumos as $c): ?>
                        <tr>
                            <td>
                                <span class="cell-title">Mesa <?= e($c['mesa_numero']) ?>
                                </span>
                            </td>
                            <td>
                                <?= e($c['descricao']) ?>
                            </td>
                            <td>
                                <?= e($c['quantidade']) ?>
                            </td>
                            <td>
                                <?= money($c['valor_unitario']) ?>
                            </td>
                            <td>
                                <strong>
                                    <?= money($c['total']) ?>
                                </strong>
                            </td>
                            <td>
                                <?= date('d/m/Y H:i',strtotime($c['created_at'])) ?>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <a class="icon-action" href="<?= url('consumos/form.php?id='.$c['id']) ?>">
                                        <span class="material-symbols-outlined">edit</span>
                                    </a>
                                    <a class="icon-action danger" data-confirm="Excluir este consumo?" href="<?= url('consumos/delete.php?id='.$c['id']) ?>">
                                        <span class="material-symbols-outlined">delete</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if(!$consumos): ?>
            <div class="empty-state">
                <span class="material-symbols-outlined">receipt_long</span>
                <p>Nenhum consumo registrado.</p>
            </div>
            <?php endif; ?>
        </section>
        <?php require __DIR__.'/../partials/footer.php'; ?>
