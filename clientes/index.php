<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pageTitle='Clientes';
$clientes=$pdo->query(
"SELECT c.*, COUNT(r.id) reservas_count
FROM clientes c LEFT JOIN reservas r ON r.cliente_id=c.id
GROUP BY c.id ORDER BY c.nome"
)->fetchAll();
require __DIR__.'/../partials/header.php';
?>
<div class="page-heading">
    <div>
        <h2>Clientes</h2>
        <p>Cadastro e histórico básico dos clientes do restaurante.</p>
    </div>
    <a class="btn btn-primary" href="<?= url('clientes/form.php') ?>">
        <span class="material-symbols-outlined">person_add</span>Novo cliente</a>
        </div>
        <section class="card">
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Contato</th>
                            <th>Reservas</th>
                            <th>Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($clientes as $c): ?>
                        <tr>
                            <td>
                                <div class="cell-title">
                                    <?= e($c['nome']) ?>
                                </div>
                                <div class="cell-sub">ID #<?= (int)$c['id'] ?>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <?= e($c['telefone'] ?: '—') ?>
                                </div>
                                <div class="cell-sub">
                                    <?= e($c['email'] ?: 'Sem e-mail') ?>
                                </div>
                            </td>
                            <td>
                                <?= (int)$c['reservas_count'] ?>
                            </td>
                            <td>
                                <?= date('d/m/Y',strtotime($c['created_at'])) ?>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <a class="icon-action" href="<?= url('clientes/form.php?id='.$c['id']) ?>">
                                        <span class="material-symbols-outlined">edit</span>
                                    </a>
                                    <a class="icon-action danger" data-confirm="Excluir este cliente?" href="<?= url('clientes/delete.php?id='.$c['id']) ?>">
                                        <span class="material-symbols-outlined">delete</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if(!$clientes): ?>
            <div class="empty-state">
                <span class="material-symbols-outlined">groups</span>
                <p>Nenhum cliente cadastrado.</p>
            </div>
            <?php endif; ?>
        </section>
        <?php require __DIR__.'/../partials/footer.php'; ?>
