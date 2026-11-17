<?php declare(strict_types=1); require_once __DIR__.'/includes/functions.php';
expirar_reservas($pdo); require_login(); if(is_admin()) redirect('dashboard.php'); $cid=current_client_id();$s=$pdo->prepare("SELECT r.*,m.numero mesa_numero,m.capacidade FROM reservas r INNER JOIN mesas m ON m.id=r.mesa_id WHERE r.cliente_id=? ORDER BY r.data_reserva DESC,r.hora_reserva DESC");$s->execute([$cid]);$reservas=$s->fetchAll();$pageTitle='Minhas reservas';require __DIR__.'/partials/client-header.php'; ?>
<div class="page-heading">
    <div>
        <span class="section-kicker">MEUS MOMENTOS</span>
        <h2>Minhas reservas</h2>
        <p>Acompanhe suas solicitações e horários.</p>
    </div>
    <a class="btn btn-primary" href="<?= url('reservar.php') ?>">Nova reserva</a>
</div>
<section class="card">
    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Horário</th>
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
                        <?= date('d/m/Y',strtotime($r['data_reserva'])) ?>
                    </td>
                    <td>
                        <?= date('H:i',strtotime($r['hora_reserva'])) ?>
                    </td>
                    <td>Mesa <?= e($r['mesa_numero']) ?>
                    </td>
                    <td>
                        <?= (int)$r['pessoas'] ?>
                    </td>
                    <td>
                        <span class="status-badge <?= status_class(reserva_status($r)) ?> res-<?= e(reserva_status($r)) ?>">
                            <?= status_label(reserva_status($r)) ?>
                        </span>
                        <?php if (reserva_status($r) === 'pendente'): ?>
                            <div class="cell-sub">Aguardando confirmação</div>
                        <?php endif; ?>
                        <?php if (reserva_status($r) === 'cancelada' && cancelada_por_label($r['cancelada_por'] ?? null, true) !== ''): ?>
                            <div class="cell-sub"><?= e(cancelada_por_label($r['cancelada_por'], true)) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (in_array(reserva_status($r), ['pendente', 'confirmada'], true)): ?>
                            <form method="post" action="<?= url('cancelar-reserva.php') ?>" onsubmit="return confirm('Deseja cancelar esta reserva? A mesa será liberada para outros clientes.');">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button type="submit" class="btn btn-light btn-sm reservation-cancel-btn">
                                    <span class="material-symbols-outlined">event_busy</span>Cancelar
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="cell-sub">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if(!$reservas): ?>
    <div class="empty-state">
        <span class="material-symbols-outlined">event_busy</span>
        <p>Nenhuma reserva cadastrada.</p>
    </div>
    <?php endif; ?>
</section>
<style>
.reservation-cancel-btn { display:inline-flex; align-items:center; gap:5px; color:#b42318 !important; background:#fff !important; border:1px solid #f0c7c4 !important; border-radius:8px; font-weight:700; white-space:nowrap; }
.reservation-cancel-btn:hover { background:#fff0ef !important; border-color:#e8b4b0 !important; }
.reservation-cancel-btn .material-symbols-outlined { font-size:18px; }
</style>
<?php require __DIR__.'/partials/footer.php'; ?>
