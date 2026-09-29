<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pageTitle='Reservas';
$filterDate=$_GET['data']??'';
$where='';$params=[];
if($filterDate){$where='WHERE r.data_reserva = ?';$params[]=$filterDate;}
$s=$pdo->prepare(
"SELECT r.*,c.nome cliente_nome,c.telefone,m.numero mesa_numero,m.capacidade
FROM reservas r INNER JOIN clientes c ON c.id=r.cliente_id INNER JOIN mesas m ON m.id=r.mesa_id
$where ORDER BY r.data_reserva DESC,r.hora_reserva ASC"
);$s->execute($params);$reservas=$s->fetchAll();
require __DIR__.'/../partials/header.php';
?>
<div class="page-heading">
    <div>
        <h2>Reservas</h2>
        <p>Organize clientes, mesas, horários e situação de cada reserva.</p>
    </div>
    <a class="btn btn-primary" href="<?= url('reservas/form.php') ?>">
        <span class="material-symbols-outlined">calendar_add_on</span>Nova reserva</a>
        </div>
        <section class="card">
            <form class="filter-bar" method="get">
                <input class="form-control" type="date" name="data" value="<?= e($filterDate) ?>">
                <button class="btn btn-light" type="submit">
                    <span class="material-symbols-outlined">filter_alt</span>Filtrar</button>
                        <?php if($filterDate): ?>
                        <a class="btn btn-light" href="<?= url('reservas/index.php') ?>">Limpar</a>
                        <?php endif; ?>
                    </form>
                    <div class="data-table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Data / hora</th>
                                    <th>Cliente</th>
                                    <th>Mesa</th>
                                    <th>Pessoas</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($reservas as $r): ?>
                                <tr>
                                    <td>
                                        <div class="cell-title">
                                            <?= date('d/m/Y',strtotime($r['data_reserva'])) ?>
                                        </div>
                                        <div class="cell-sub">
                                            <?= date('H:i',strtotime($r['hora_reserva'])) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="cell-title">
                                            <?= e($r['cliente_nome']) ?>
                                        </div>
                                        <div class="cell-sub">
                                            <?= e($r['telefone'] ?: 'Sem telefone') ?>
                                        </div>
                                    </td>
                                    <td>Mesa <?= e($r['mesa_numero']) ?>
                                        <div class="cell-sub">até <?= (int)$r['capacidade'] ?> pessoas</div>
                                    </td>
                                    <td>
                                        <?= (int)$r['pessoas'] ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= status_class($r['status']) ?>">
                                            <?= status_label($r['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="row-actions">
                                            <a class="icon-action" href="<?= url('reservas/form.php?id='.$r['id']) ?>">
                                                <span class="material-symbols-outlined">edit</span>
                                            </a>
                                            <a class="icon-action danger" data-confirm="Excluir esta reserva?" href="<?= url('reservas/delete.php?id='.$r['id']) ?>">
                                                <span class="material-symbols-outlined">delete</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if(!$reservas): ?>
                    <div class="empty-state">
                        <span class="material-symbols-outlined">event_busy</span>
                        <p>Nenhuma reserva encontrada.</p>
                    </div>
                    <?php endif; ?>
                </section>
                <?php require __DIR__.'/../partials/footer.php'; ?>
