<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pageTitle='Reservas';

// Pendentes sem resposta em 30 min e reservas que já passaram viram "expirada".
expirar_reservas($pdo);
$filterDate=$_GET['data']??'';
$where='';$params=[];
if($filterDate){$where='WHERE r.data_reserva = ?';$params[]=$filterDate;}
$s=$pdo->prepare(
"SELECT r.*,c.nome cliente_nome,c.telefone,m.numero mesa_numero,m.capacidade
FROM reservas r INNER JOIN clientes c ON c.id=r.cliente_id INNER JOIN mesas m ON m.id=r.mesa_id
$where ORDER BY r.data_reserva DESC,r.hora_reserva ASC"
);$s->execute($params);$reservas=$s->fetchAll();

// Separa as reservas por situação (a ordem define a ordem das seções).
$grupos = [
    'pendente'   => ['id' => 'pendentes',  'titulo' => 'Pendentes',  'card' => 'Pendentes',  'icone' => 'hourglass_top', 'accent' => 'stat-accent-yellow', 'vazio' => 'Nenhuma reserva aguardando confirmação.', 'lista' => []],
    'confirmada' => ['id' => 'confirmados', 'titulo' => 'Confirmados', 'card' => 'Confirmados', 'icone' => 'event_available', 'accent' => 'stat-accent-green', 'vazio' => 'Nenhuma reserva confirmada.', 'lista' => []],
    'cancelada'  => ['id' => 'cancelados', 'titulo' => 'Cancelados', 'card' => 'Cancelados', 'icone' => 'event_busy', 'accent' => 'stat-accent-red', 'vazio' => 'Nenhuma reserva cancelada.', 'lista' => []],
    'expirada'   => ['id' => 'expirados',  'titulo' => 'Expirados',  'card' => 'Expirados',  'icone' => 'history', 'accent' => '', 'vazio' => 'Nenhuma reserva expirada.', 'lista' => []],
    'concluida'  => ['id' => 'concluidos', 'titulo' => 'Concluídos', 'card' => null, 'icone' => 'task_alt', 'accent' => '', 'vazio' => 'Nenhuma reserva concluída.', 'lista' => []],
];
foreach ($reservas as $r) {
    $st = reserva_status($r);
    $chave = isset($grupos[$st]) ? $st : 'pendente';
    $grupos[$chave]['lista'][] = $r;
}
// Pendentes e confirmadas: as mais próximas primeiro.
foreach (['pendente', 'confirmada'] as $k) {
    usort($grupos[$k]['lista'], static fn(array $a, array $b): int =>
        strcmp($a['data_reserva'].' '.$a['hora_reserva'], $b['data_reserva'].' '.$b['hora_reserva']));
}

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

<section class="stats-grid stats-grid-4">
    <?php foreach ($grupos as $g): if ($g['card'] === null) continue; $n = count($g['lista']); ?>
        <a class="stat-card stat-link <?= e($g['accent']) ?>" href="#<?= e($g['id']) ?>">
            <div class="stat-top">
                <div class="stat-icon">
                    <span class="material-symbols-outlined"><?= e($g['icone']) ?></span>
                </div>
            </div>
            <div class="stat-value"><?= $n ?></div>
            <div class="stat-label"><?= e($g['card']) ?></div>
        </a>
    <?php endforeach; ?>
</section>

<section class="card">
    <form class="filter-bar" method="get">
        <input class="form-control" type="date" name="data" value="<?= e($filterDate) ?>">
        <button class="btn btn-light" type="submit">
            <span class="material-symbols-outlined">filter_alt</span>Filtrar</button>
        <?php if ($filterDate): ?>
            <a class="btn btn-light" href="<?= url('reservas/index.php') ?>">Limpar</a>
        <?php endif; ?>
    </form>
</section>

<?php foreach ($grupos as $chave => $g):
    // Concluídas só aparecem se existir alguma.
    if ($chave === 'concluida' && !$g['lista']) continue; ?>
<section class="card reservation-group" id="<?= e($g['id']) ?>">
    <div class="card-header">
        <div>
            <h3><?= e($g['titulo']) ?></h3>
            <span><?= count($g['lista']) ?> <?= count($g['lista']) === 1 ? 'reserva' : 'reservas' ?></span>
        </div>
    </div>
    <?php if (!$g['lista']): ?>
        <div class="empty-state">
            <span class="material-symbols-outlined"><?= e($g['icone']) ?></span>
            <p><?= e($g['vazio']) ?></p>
        </div>
    <?php else: ?>
    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Data / hora</th>
                    <th>Cliente</th>
                    <th>Mesa</th>
                    <th>Pessoas</th>
                    <th>Status</th>
                    <?php if ($chave === 'cancelada'): ?><th>Cancelado por</th><?php endif; ?>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($g['lista'] as $r): ?>
                <tr>
                    <td>
                        <div class="cell-title"><?= date('d/m/Y', strtotime($r['data_reserva'])) ?></div>
                        <div class="cell-sub"><?= date('H:i', strtotime($r['hora_reserva'])) ?></div>
                    </td>
                    <td>
                        <div class="cell-title"><?= e($r['cliente_nome']) ?></div>
                        <div class="cell-sub"><?= e($r['telefone'] ?: 'Sem telefone') ?></div>
                    </td>
                    <td>Mesa <?= e($r['mesa_numero']) ?>
                        <div class="cell-sub">até <?= (int)$r['capacidade'] ?> pessoas</div>
                    </td>
                    <td><?= (int)$r['pessoas'] ?></td>
                    <td>
                        <?php if ($r['status'] === 'expirada'): ?>
                            <span class="status-badge status-expirada reservation-expired-label">Expirado</span>
                        <?php else: ?>
                            <form class="reservation-status-form" method="post" action="<?= url('reservas/atualizar-status.php') ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <select class="form-control reservation-status-select <?= e(status_class($r['status'])) ?>"
                                        name="status" aria-label="Alterar status da reserva"
                                        onchange="this.form.submit()"
                                        <?= in_array($r['status'], ['cancelada','concluida'], true) ? 'disabled' : '' ?>>
                                    <option value="pendente" <?= $r['status']==='pendente'?'selected':'' ?>>Pendente</option>
                                    <option value="confirmada" <?= $r['status']==='confirmada'?'selected':'' ?>><?= $r['status']==='confirmada'?'Confirmada':'Confirmar reserva' ?></option>
                                    <option value="cancelada" <?= $r['status']==='cancelada'?'selected':'' ?>><?= $r['status']==='cancelada'?'Cancelada':'Recusar reserva' ?></option>
                                    <option value="concluida" <?= $r['status']==='concluida'?'selected':'' ?>><?= $r['status']==='concluida'?'Concluída':'Marcar como concluída' ?></option>
                                </select>
                            </form>
                        <?php endif; ?>
                    </td>
                    <?php if ($chave === 'cancelada'): ?>
                    <td>
                        <span class="cancel-by cancel-by-<?= e($r['cancelada_por'] ?? 'nd') ?>"><?= e(cancelada_por_label($r['cancelada_por'] ?? null)) ?></span>
                        <?php if (!empty($r['cancelada_em'])): ?>
                            <div class="cell-sub"><?= date('d/m/Y H:i', strtotime($r['cancelada_em'])) ?></div>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <td>
                        <form method="post" action="<?= url('reservas/delete.php') ?>" onsubmit="return confirm('Tem certeza que deseja excluir esta reserva? Esta ação não pode ser desfeita.');">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button type="submit" class="btn btn-light btn-sm reservation-delete-btn" aria-label="Excluir reserva <?= (int)$r['id'] ?>">
                                <span class="material-symbols-outlined">delete</span>Excluir
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>
<?php endforeach; ?>
<style>
.status-expirada { color:#7b4d18; background:#f8ead1; }
.reservation-status-form { min-width: 175px; }
.reservation-delete-btn { display:inline-flex; align-items:center; justify-content:center; gap:5px; min-height:34px; padding:7px 12px; color:#b42318 !important; background:#fff !important; border:1px solid #f0c7c4 !important; border-radius:8px; font-weight:700; white-space:nowrap; }
.reservation-delete-btn:hover { background:#fff0ef !important; border-color:#e8b4b0 !important; }
.reservation-delete-btn .material-symbols-outlined { font-size:18px; }
.reservation-status-select { width: 148px; min-width: 148px; padding: 6px 25px 6px 8px; font-size: 12px; }
.reservation-status-select:disabled { opacity: .8; cursor: not-allowed; }
.cancel-by { display:inline-block; padding:4px 10px; border-radius:999px; font-size:12px; font-weight:700; background:#eee8df; color:#7a6f64; }
.cancel-by-cliente { background:#e8eefc; color:#34508f; }
.cancel-by-admin { background:#f7e4e1; color:#a6423a; }
.reservation-expired-label { display: inline-flex; align-items: center; min-height: 36px; white-space: nowrap; }
</style>
<?php require __DIR__.'/../partials/footer.php'; ?>
