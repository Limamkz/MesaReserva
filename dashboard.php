<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_admin();

$pageTitle = 'Dashboard';

$totalMesas = (int)$pdo->query("SELECT COUNT(*) FROM mesas")->fetchColumn();
$disponiveis = (int)$pdo->query("SELECT COUNT(*) FROM mesas WHERE status = 'disponivel'")->fetchColumn();
$reservadas = (int)$pdo->query("SELECT COUNT(*) FROM mesas WHERE status = 'reservada'")->fetchColumn();
$ocupadas = (int)$pdo->query("SELECT COUNT(*) FROM mesas WHERE status = 'ocupada'")->fetchColumn();

$consumoTotal = (float)$pdo->query("SELECT COALESCE(SUM(quantidade * valor_unitario),0) FROM consumos")->fetchColumn();

$mesas = $pdo->query(
    "SELECT m.*,
        COALESCE(SUM(c.quantidade * c.valor_unitario),0) AS total_gasto
     FROM mesas m
     LEFT JOIN consumos c ON c.mesa_id = m.id
     GROUP BY m.id
     ORDER BY m.numero"
)->fetchAll();

$reservasHoje = $pdo->query(
    "SELECT r.*, c.nome AS cliente_nome, m.numero AS mesa_numero
     FROM reservas r
     INNER JOIN clientes c ON c.id = r.cliente_id
     INNER JOIN mesas m ON m.id = r.mesa_id
     WHERE r.data_reserva = CURDATE()
       AND r.status IN ('pendente','confirmada')
     ORDER BY r.hora_reserva ASC
     LIMIT 7"
)->fetchAll();

require __DIR__ . '/partials/header.php';
?>
<div class="page-heading">
    <div>
        <h2>Visão geral do salão</h2>
        <p>Acompanhe o estado das mesas e a operação do restaurante em tempo real.</p>
    </div>
    <div class="actions">
        <a class="btn btn-light" href="<?= url('consumos/form.php') ?>"><span class="material-symbols-outlined">add_shopping_cart</span>Novo consumo</a>
        <a class="btn btn-primary" href="<?= url('reservas/form.php') ?>"><span class="material-symbols-outlined">add</span>Nova reserva</a>
    </div>
</div>

<section class="stats-grid">
    <div class="stat-card">
        <div class="stat-top"><div class="stat-icon"><span class="material-symbols-outlined">table_restaurant</span></div></div>
        <div class="stat-value"><?= $totalMesas ?></div><div class="stat-label">Total de mesas</div>
    </div>
    <div class="stat-card stat-accent-green">
        <div class="stat-top"><div class="stat-icon"><span class="material-symbols-outlined">check_circle</span></div></div>
        <div class="stat-value"><?= $disponiveis ?></div><div class="stat-label">Mesas disponíveis</div>
    </div>
    <div class="stat-card stat-accent-yellow">
        <div class="stat-top"><div class="stat-icon"><span class="material-symbols-outlined">event</span></div></div>
        <div class="stat-value"><?= $reservadas ?></div><div class="stat-label">Mesas reservadas</div>
    </div>
    <div class="stat-card stat-accent-red">
        <div class="stat-top"><div class="stat-icon"><span class="material-symbols-outlined">restaurant</span></div></div>
        <div class="stat-value"><?= $ocupadas ?></div><div class="stat-label">Mesas ocupadas</div>
    </div>
</section>

<div class="grid-2">
    <section class="card">
        <div class="card-header">
            <div><h3>Mapa das mesas</h3><span>Capacidade e consumo acumulado</span></div>
            <a href="<?= url('mesas/index.php') ?>" class="btn btn-light btn-sm">Gerenciar</a>
        </div>
        <div class="card-body">
            <div class="legend">
                <span><i class="green"></i>Disponível</span>
                <span><i class="yellow"></i>Reservada</span>
                <span><i class="red"></i>Ocupada</span>
            </div>
            <?php if (!$mesas): ?>
                <div class="empty-state"><span class="material-symbols-outlined">table_restaurant</span><p>Nenhuma mesa cadastrada.</p></div>
            <?php else: ?>
                <div class="table-map">
                    <?php foreach ($mesas as $mesa): ?>
                        <a class="table-card <?= e($mesa['status']) ?>" href="<?= url('mesas/form.php?id=' . (int)$mesa['id']) ?>">
                            <div class="table-top">
                                <div><div class="table-number">Mesa <?= e($mesa['numero']) ?></div><div class="capacity"><?= (int)$mesa['capacidade'] ?> pessoas</div></div>
                                <span class="status-badge <?= status_class($mesa['status']) ?>"><?= status_label($mesa['status']) ?></span>
                            </div>
                            <div class="table-total">Total consumido<strong><?= money($mesa['total_gasto']) ?></strong></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="card">
        <div class="card-header">
            <div><h3>Reservas de hoje</h3><span><?= date('d/m/Y') ?></span></div>
            <a href="<?= url('reservas/index.php') ?>" class="btn btn-light btn-sm">Ver todas</a>
        </div>
        <div class="card-body">
            <?php if (!$reservasHoje): ?>
                <div class="empty-state"><span class="material-symbols-outlined">event_available</span><p>Nenhuma reserva para hoje.</p></div>
            <?php else: ?>
                <div class="reservation-list">
                    <?php foreach ($reservasHoje as $r): ?>
                        <div class="reservation-row">
                            <div class="reservation-main">
                                <div class="reservation-time"><?= date('H:i', strtotime($r['hora_reserva'])) ?></div>
                                <div>
                                    <strong><?= e($r['cliente_nome']) ?></strong>
                                    <small>Mesa <?= e($r['mesa_numero']) ?> · <?= (int)$r['pessoas'] ?> pessoas</small>
                                </div>
                            </div>
                            <span class="status-badge <?= status_class($r['status']) ?>"><?= status_label($r['status']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<div style="height:20px"></div>
<section class="card">
    <div class="card-header">
        <div><h3>Atalhos</h3><span>Operações frequentes</span></div>
        <div class="kpi-inline"><strong><?= money($consumoTotal) ?></strong><span>consumo registrado</span></div>
    </div>
    <div class="card-body">
        <div class="quick-actions">
            <a class="quick-action" href="<?= url('mesas/form.php') ?>"><span class="material-symbols-outlined">add_business</span><div><strong>Cadastrar mesa</strong><small>Defina capacidade e status inicial.</small></div></a>
            <a class="quick-action" href="<?= url('clientes/form.php') ?>"><span class="material-symbols-outlined">person_add</span><div><strong>Cadastrar cliente</strong><small>Organize os dados dos clientes.</small></div></a>
            <a class="quick-action" href="<?= url('reservas/form.php') ?>"><span class="material-symbols-outlined">calendar_add_on</span><div><strong>Registrar reserva</strong><small>Escolha cliente, mesa e horário.</small></div></a>
            <a class="quick-action" href="<?= url('consumos/form.php') ?>"><span class="material-symbols-outlined">receipt_long</span><div><strong>Lançar consumo</strong><small>Atualize o gasto de cada mesa.</small></div></a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
