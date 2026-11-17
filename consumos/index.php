<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pageTitle='Consumos';

$mesas = $pdo->query(
    "SELECT m.id, m.numero, m.status, COUNT(c.id) AS lancamentos
     FROM mesas m LEFT JOIN consumos c ON c.mesa_id = m.id
     GROUP BY m.id, m.numero, m.status ORDER BY m.numero"
)->fetchAll();

// Mesa escolhida no dropdown (0 = visão geral, sem lista de lançamentos).
$mesaSel = (int)($_GET['mesa'] ?? 0);
$mesaAtual = null;
foreach ($mesas as $m) {
    if ((int)$m['id'] === $mesaSel) { $mesaAtual = $m; }
}
if (!$mesaAtual) { $mesaSel = 0; }

$filtro = $mesaSel ? 'WHERE c.mesa_id = ?' : '';
$params = $mesaSel ? [$mesaSel] : [];

$st = $pdo->prepare(
    "SELECT COUNT(*) AS qtd, COALESCE(SUM(c.quantidade*c.valor_unitario),0) AS total,
      COALESCE(SUM(CASE WHEN DATE(c.created_at)=CURDATE() THEN c.quantidade*c.valor_unitario ELSE 0 END),0) AS diario,
      COALESCE(SUM(CASE WHEN YEARWEEK(c.created_at,1)=YEARWEEK(CURDATE(),1) THEN c.quantidade*c.valor_unitario ELSE 0 END),0) AS semanal,
      COALESCE(SUM(CASE WHEN YEAR(c.created_at)=YEAR(CURDATE()) AND MONTH(c.created_at)=MONTH(CURDATE()) THEN c.quantidade*c.valor_unitario ELSE 0 END),0) AS mensal,
      COALESCE(SUM(CASE WHEN YEAR(c.created_at)=YEAR(CURDATE()) THEN c.quantidade*c.valor_unitario ELSE 0 END),0) AS anual
     FROM consumos c $filtro"
);
$st->execute($params);
$resumo = $st->fetch();

$consumos = [];
if ($mesaSel) {
    $st = $pdo->prepare(
        "SELECT c.*, (c.quantidade*c.valor_unitario) total
         FROM consumos c WHERE c.mesa_id = ? ORDER BY c.created_at DESC"
    );
    $st->execute([$mesaSel]);
    $consumos = $st->fetchAll();
}

$porMesa = [];
if (!$mesaSel) {
    $porMesa = $pdo->query(
        "SELECT m.id, m.numero, COUNT(c.id) AS lancamentos,
          COALESCE(SUM(CASE WHEN DATE(c.created_at)=CURDATE() THEN c.quantidade*c.valor_unitario ELSE 0 END),0) AS diario,
          COALESCE(SUM(CASE WHEN YEARWEEK(c.created_at,1)=YEARWEEK(CURDATE(),1) THEN c.quantidade*c.valor_unitario ELSE 0 END),0) AS semanal,
          COALESCE(SUM(CASE WHEN YEAR(c.created_at)=YEAR(CURDATE()) AND MONTH(c.created_at)=MONTH(CURDATE()) THEN c.quantidade*c.valor_unitario ELSE 0 END),0) AS mensal,
          COALESCE(SUM(CASE WHEN YEAR(c.created_at)=YEAR(CURDATE()) THEN c.quantidade*c.valor_unitario ELSE 0 END),0) AS anual
         FROM mesas m LEFT JOIN consumos c ON c.mesa_id=m.id
         GROUP BY m.id, m.numero ORDER BY m.numero"
    )->fetchAll();
}

require __DIR__.'/../partials/header.php';
?>
<div class="page-heading">
    <div>
        <h2>Consumos</h2>
        <p>Registre itens e acompanhe o valor gasto em cada mesa.</p>
    </div>
    <a class="btn btn-primary" href="<?= url('consumos/form.php' . ($mesaSel ? '?mesa=' . $mesaSel : '')) ?>">
        <span class="material-symbols-outlined">add_shopping_cart</span>Novo consumo</a>
</div>

<section class="card consumo-filter">
    <form class="filter-bar" method="get">
        <label class="consumo-filter-label" for="mesaSelect">
            <span class="material-symbols-outlined">table_restaurant</span>Mesa
        </label>
        <select class="form-control" id="mesaSelect" name="mesa" onchange="this.form.submit()">
            <option value="0">Visão geral — todas as mesas</option>
            <?php foreach ($mesas as $m): ?>
                <option value="<?= (int)$m['id'] ?>" <?= (int)$m['id'] === $mesaSel ? 'selected' : '' ?>>
                    Mesa <?= e($m['numero']) ?> — <?= (int)$m['lancamentos'] ?> <?= (int)$m['lancamentos'] === 1 ? 'lançamento' : 'lançamentos' ?>
                </option>
            <?php endforeach; ?>
        </select>
        <noscript><button class="btn btn-light" type="submit">Ver</button></noscript>
        <?php if ($mesaSel): ?>
            <a class="btn btn-light" href="<?= url('consumos/index.php') ?>">Visão geral</a>
        <?php endif; ?>
    </form>
</section>

<section class="stats-grid" style="grid-template-columns:1fr 1fr">
    <div class="stat-card">
        <div class="stat-top"><div class="stat-icon"><span class="material-symbols-outlined">receipt_long</span></div></div>
        <div class="stat-value"><?= (int)$resumo['qtd'] ?></div>
        <div class="stat-label">Lançamentos<?= $mesaAtual ? ' da Mesa ' . e($mesaAtual['numero']) : ' registrados' ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><div class="stat-icon"><span class="material-symbols-outlined">payments</span></div></div>
        <div class="stat-value"><?= money($resumo['total']) ?></div>
        <div class="stat-label">Valor total<?= $mesaAtual ? ' da Mesa ' . e($mesaAtual['numero']) : ' registrado' ?></div>
    </div>
</section>
<section class="stats-grid" style="grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:20px">
    <div class="stat-card"><div class="stat-top"><div class="stat-icon"><span class="material-symbols-outlined">today</span></div></div><div class="stat-value"><?= money($resumo['diario']) ?></div><div class="stat-label">Consumo diário</div></div>
    <div class="stat-card"><div class="stat-top"><div class="stat-icon"><span class="material-symbols-outlined">date_range</span></div></div><div class="stat-value"><?= money($resumo['semanal']) ?></div><div class="stat-label">Consumo semanal</div></div>
    <div class="stat-card"><div class="stat-top"><div class="stat-icon"><span class="material-symbols-outlined">calendar_month</span></div></div><div class="stat-value"><?= money($resumo['mensal']) ?></div><div class="stat-label">Consumo mensal</div></div>
    <div class="stat-card"><div class="stat-top"><div class="stat-icon"><span class="material-symbols-outlined">event</span></div></div><div class="stat-value"><?= money($resumo['anual']) ?></div><div class="stat-label">Consumo anual</div></div>
</section>

<?php if (!$mesaSel): ?>
<section class="card">
    <div class="card-header"><div><h3>Comparativo de consumo por mesa</h3><span>Escolha uma mesa para ver os lançamentos dela</span></div></div>
    <div class="data-table-wrap"><table class="data-table">
        <thead><tr><th>Mesa</th><th>Hoje</th><th>Esta semana</th><th>Este mês</th><th>Este ano</th><th>Lançamentos</th></tr></thead>
        <tbody><?php foreach ($porMesa as $pm): ?><tr>
            <td><strong>Mesa <?= e($pm['numero']) ?></strong></td>
            <td><?= money($pm['diario']) ?></td><td><?= money($pm['semanal']) ?></td><td><?= money($pm['mensal']) ?></td><td><?= money($pm['anual']) ?></td>
            <td>
                <a class="btn btn-light btn-sm" href="<?= url('consumos/index.php?mesa=' . (int)$pm['id']) ?>">
                    <?= (int)$pm['lancamentos'] ?> <?= (int)$pm['lancamentos'] === 1 ? 'lançamento' : 'lançamentos' ?>
                    <span class="material-symbols-outlined">arrow_forward</span>
                </a>
            </td>
        </tr><?php endforeach; ?></tbody>
    </table></div>
</section>
<?php else: ?>
<section class="card">
    <div class="card-header"><div><h3>Lançamentos — Mesa <?= e($mesaAtual['numero']) ?></h3><span>Histórico completo desta mesa</span></div></div>
    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>Descrição</th><th>Qtd.</th><th>Unitário</th><th>Total</th><th>Data</th><th>Ações</th></tr>
            </thead>
            <tbody>
                <?php foreach ($consumos as $c): ?>
                <tr>
                    <td><?= e($c['descricao']) ?></td>
                    <td><?= e($c['quantidade']) ?></td>
                    <td><?= money($c['valor_unitario']) ?></td>
                    <td><strong><?= money($c['total']) ?></strong></td>
                    <td><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
                    <td>
                        <div class="row-actions">
                            <a class="icon-action" href="<?= url('consumos/form.php?id=' . $c['id']) ?>">
                                <span class="material-symbols-outlined">edit</span>
                            </a>
                            <a class="icon-action danger" data-confirm="Excluir este consumo?" href="<?= url('consumos/delete.php?id=' . $c['id']) ?>">
                                <span class="material-symbols-outlined">delete</span>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$consumos): ?>
    <div class="empty-state">
        <span class="material-symbols-outlined">receipt_long</span>
        <p>Nenhum consumo lançado para a Mesa <?= e($mesaAtual['numero']) ?>.</p>
    </div>
    <?php endif; ?>
</section>
<?php endif; ?>
<style>
.consumo-filter { margin-bottom: 20px; }
.consumo-filter .filter-bar { align-items: center; }
.consumo-filter-label { display: inline-flex; align-items: center; gap: 6px; font-weight: 700; color: #4b3b2c; }
.consumo-filter-label .material-symbols-outlined { color: #a8792c; }
.consumo-filter select.form-control { max-width: 320px; }
</style>
<?php require __DIR__.'/../partials/footer.php'; ?>
