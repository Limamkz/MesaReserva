<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

// Atualiza reservas cujo horário já passou antes de exibir os dados.
$pdo->exec("UPDATE reservas SET status='expirada' WHERE status IN ('pendente','confirmada') AND TIMESTAMP(data_reserva, hora_reserva) < NOW();");
require_admin();

$pageTitle = 'Dashboard';

$totalMesas = (int)$pdo->query("SELECT COUNT(*) FROM mesas")->fetchColumn();
$disponiveis = (int)$pdo->query("SELECT COUNT(*) FROM mesas m WHERE m.status <> 'ocupada' AND NOT EXISTS (SELECT 1 FROM reservas r WHERE r.mesa_id=m.id AND r.data_reserva=CURDATE() AND r.status='confirmada' AND TIMESTAMP(r.data_reserva,r.hora_reserva)>=NOW())")->fetchColumn();
$reservadas = (int)$pdo->query("SELECT COUNT(*) FROM mesas m WHERE m.status <> 'ocupada' AND EXISTS (SELECT 1 FROM reservas r WHERE r.mesa_id=m.id AND r.data_reserva=CURDATE() AND r.status='confirmada' AND TIMESTAMP(r.data_reserva,r.hora_reserva)>=NOW())")->fetchColumn();
$ocupadas = (int)$pdo->query("SELECT COUNT(*) FROM mesas WHERE status = 'ocupada'")->fetchColumn();

$consumoTotal = (float)$pdo->query("SELECT COALESCE(SUM(quantidade * valor_unitario),0) FROM consumos")->fetchColumn();

$mesas = $pdo->query(
"SELECT m.*,
CASE WHEN m.status='ocupada' THEN 'ocupada'
     WHEN EXISTS (SELECT 1 FROM reservas r WHERE r.mesa_id=m.id AND r.data_reserva=CURDATE() AND r.status='confirmada' AND TIMESTAMP(r.data_reserva,r.hora_reserva)>=NOW()) THEN 'reservada'
     ELSE 'disponivel' END AS status_dia,
COALESCE((SELECT SUM(c.quantidade*c.valor_unitario) FROM consumos c WHERE c.mesa_id=m.id AND DATE(c.created_at)=CURDATE()),0) AS total_gasto
FROM mesas m
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

$periodo = $_GET['periodo'] ?? 'mensal';
$periodo = in_array($periodo, ['diario','semanal','mensal','anual'], true) ? $periodo : 'mensal';
$range = match ($periodo) {
    'diario' => "DATE(c.created_at) >= CURDATE() - INTERVAL 6 DAY",
    'semanal' => "YEARWEEK(c.created_at, 1) >= YEARWEEK(CURDATE() - INTERVAL 7 WEEK, 1)",
    'anual' => "YEAR(c.created_at) >= YEAR(CURDATE()) - 4",
    default => "c.created_at >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 11 MONTH)"
};
// Compara o valor total gasto em cada mesa dentro do período selecionado.
$chartStmt = $pdo->query("SELECT CONCAT('Mesa ', m.numero) AS rotulo,
        COALESCE(SUM(c.quantidade * c.valor_unitario), 0) AS total
    FROM mesas m
    LEFT JOIN consumos c ON c.mesa_id = m.id AND {$range}
    GROUP BY m.id, m.numero
    ORDER BY m.numero");
$chartRows = $chartStmt->fetchAll();
$chartLabels = array_column($chartRows, 'rotulo');
$chartValues = array_map('floatval', array_column($chartRows, 'total'));

if (isset($_GET['ajax']) && $_GET['ajax'] === 'grafico') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['labels' => $chartLabels, 'values' => $chartValues], JSON_UNESCAPED_UNICODE);
    exit;
}

require __DIR__ . '/partials/header.php';
?>
<div class="page-heading">
    <div>
        <h2>Visão geral do salão</h2>
        <p>Acompanhe o estado das mesas e a operação do restaurante em tempo real.</p>
    </div>
    <div class="actions">
        <a class="btn btn-light" href="<?= url('consumos/form.php') ?>">
            <span class="material-symbols-outlined">add_shopping_cart</span>Novo consumo</a>
                <a class="btn btn-primary" href="<?= url('reservas/form.php') ?>">
                    <span class="material-symbols-outlined">add</span>Nova reserva</a>
                    </div>
                </div>
                <section class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-top">
                            <div class="stat-icon">
                                <span class="material-symbols-outlined">table_restaurant</span>
                            </div>
                        </div>
                        <div class="stat-value">
                            <?= $totalMesas ?>
                        </div>
                        <div class="stat-label">Total de mesas</div>
                    </div>
                    <div class="stat-card stat-accent-green">
                        <div class="stat-top">
                            <div class="stat-icon">
                                <span class="material-symbols-outlined">check_circle</span>
                            </div>
                        </div>
                        <div class="stat-value">
                            <?= $disponiveis ?>
                        </div>
                        <div class="stat-label">Mesas disponíveis</div>
                    </div>
                    <div class="stat-card stat-accent-yellow">
                        <div class="stat-top">
                            <div class="stat-icon">
                                <span class="material-symbols-outlined">event</span>
                            </div>
                        </div>
                        <div class="stat-value">
                            <?= $reservadas ?>
                        </div>
                        <div class="stat-label">Mesas reservadas</div>
                    </div>
                    <div class="stat-card stat-accent-red">
                        <div class="stat-top">
                            <div class="stat-icon">
                                <span class="material-symbols-outlined">restaurant</span>
                            </div>
                        </div>
                        <div class="stat-value">
                            <?= $ocupadas ?>
                        </div>
                        <div class="stat-label">Mesas ocupadas</div>
                    </div>
                </section>
                <div class="grid-2">
                    <section class="card map-card">
                        <div class="card-header">
                            <div>
                                <h3>Mapa das mesas</h3>
                                <span>Capacidade e consumo de hoje</span>
                            </div>
                            <a href="<?= url('mesas/index.php') ?>" class="btn btn-light btn-sm">Gerenciar</a>
                        </div>
                        <div class="card-body">
                            <div class="legend">
                                <span>
                                    <i class="green">
                                    </i>Disponível</span>
                                    <span>
                                        <i class="yellow">
                                        </i>Reservada</span>
                                        <span>
                                            <i class="red">
                                            </i>Ocupada</span>
                                        </div>
                                        <?php if (!$mesas): ?>
                                        <div class="empty-state">
                                            <span class="material-symbols-outlined">table_restaurant</span>
                                            <p>Nenhuma mesa cadastrada.</p>
                                        </div>
                                        <?php else: ?>
                                        <div class="table-map">
                                            <?php foreach ($mesas as $mesa): ?>
                                            <a class="table-card <?= e($mesa['status_dia']) ?>" href="<?= url('mesas/form.php?id=' . (int)$mesa['id']) ?>">
    <span class="status-badge <?= status_class($mesa['status_dia']) ?>"><?= status_label($mesa['status_dia']) ?></span>
    <div class="table-number">Mesa <?= e($mesa['numero']) ?></div>
    <div class="capacity">
        <span class="material-symbols-outlined">group</span>
        <?= (int)$mesa['capacidade'] ?> pessoas
    </div>
    <div class="table-total">
        Consumo de hoje
        <strong><?= money($mesa['total_gasto']) ?></strong>
    </div>
</a>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </section>
                                <section class="card">
                                    <div class="card-header">
                                        <div>
                                            <h3>Reservas de hoje</h3>
                                            <span>
                                                <?= date('d/m/Y') ?>
                                            </span>
                                        </div>
                                        <a href="<?= url('reservas/index.php') ?>" class="btn btn-light btn-sm">Ver todas</a>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!$reservasHoje): ?>
                                        <div class="empty-state">
                                            <span class="material-symbols-outlined">event_available</span>
                                            <p>Nenhuma reserva para hoje.</p>
                                        </div>
                                        <?php else: ?>
                                        <div class="reservation-list">
                                            <?php foreach ($reservasHoje as $r): ?>
                                            <div class="reservation-row">
                                                <div class="reservation-main">
                                                    <div class="reservation-time">
                                                        <?= date('H:i', strtotime($r['hora_reserva'])) ?>
                                                    </div>
                                                    <div>
                                                        <strong>
                                                            <?= e($r['cliente_nome']) ?>
                                                        </strong>
                                                        <small>Mesa <?= e($r['mesa_numero']) ?> · <?= (int)$r['pessoas'] ?> pessoas</small>
                                                    </div>
                                                </div>
                                                <span class="status-badge <?= status_class(reserva_status($r)) ?> res-<?= e(reserva_status($r)) ?>">
                                                    <?= status_label(reserva_status($r)) ?>
                                                </span>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </section>
                            </div>
                            <?php
$periodos = [
    'diario' => ['curto' => 'Diário', 'descricao' => 'Últimos 7 dias'],
    'semanal' => ['curto' => 'Semanal', 'descricao' => 'Últimas 8 semanas'],
    'mensal' => ['curto' => 'Mensal', 'descricao' => 'Últimos 12 meses'],
    'anual' => ['curto' => 'Anual', 'descricao' => 'Últimos 5 anos'],
];
$chartTotal = array_sum($chartValues);
$chartMedia = count($chartValues) > 0 ? $chartTotal / count($chartValues) : 0;
$chartTopIndex = $chartTotal > 0 ? (int)array_search(max($chartValues), $chartValues, true) : null;
$chartTopLabel = $chartTopIndex !== null ? $chartLabels[$chartTopIndex] : '—';
?>
<section class="card dashboard-chart-card" id="chartCard">
    <div class="card-header chart-head">
        <div class="chart-title">
            <span class="chart-icon">
                <span class="material-symbols-outlined">bar_chart</span>
            </span>
            <div>
                <h3>Desempenho de consumo</h3>
                <span>Valor gasto em cada mesa no período selecionado</span>
            </div>
        </div>
        <div class="chart-periods" role="tablist" aria-label="Período do gráfico">
            <?php foreach ($periodos as $chave => $info): ?>
            <a
                class="chart-period <?= $periodo === $chave ? 'is-active' : '' ?>"
                href="?periodo=<?= e($chave) ?>"
                data-periodo="<?= e($chave) ?>"
                data-descricao="<?= e($info['descricao']) ?>"
                role="tab"
                aria-selected="<?= $periodo === $chave ? 'true' : 'false' ?>"
            ><?= e($info['curto']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="chart-kpis">
        <div class="chart-kpi">
            <span>Total no período</span>
            <strong id="kpiTotal"><?= money($chartTotal) ?></strong>
        </div>
        <div class="chart-kpi">
            <span>Média por mesa</span>
            <strong id="kpiMedia"><?= money($chartMedia) ?></strong>
        </div>
        <div class="chart-kpi">
            <span>Maior consumo</span>
            <strong id="kpiTop"><?= e($chartTopLabel) ?></strong>
        </div>
        <div class="chart-kpi chart-kpi-range">
            <span>Período</span>
            <strong id="kpiPeriodo"><?= e($periodos[$periodo]['descricao']) ?></strong>
        </div>
    </div>
    <div class="card-body">
        <div class="chart-canvas">
            <canvas id="consumptionChart" aria-label="Gráfico de valor gasto por mesa"></canvas>
            <div class="chart-empty" id="chartEmpty" <?= $chartTotal > 0 ? 'hidden' : '' ?>>
                <span class="material-symbols-outlined">insights</span>
                <strong>Sem consumo neste período</strong>
                <small>Os lançamentos de consumo aparecem aqui assim que forem registrados.</small>
            </div>
        </div>
    </div>
</section>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('consumptionChart');
    const card = document.getElementById('chartCard');
    const emptyState = document.getElementById('chartEmpty');
    const tabs = document.querySelectorAll('.chart-period');

    if (!canvas || typeof Chart === 'undefined') {
        return;
    }

    const COR_BASE = '#e3cb9d';
    const COR_DESTAQUE = '#a8792c';
    const COR_HOVER = '#c49a55';

    Chart.defaults.font.family = "'DM Sans', system-ui, sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = '#8a7d6f';

    function formatarMoeda(valor) {
        return 'R$ ' + Number(valor).toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function formatarEixo(valor) {
        if (valor >= 1000) {
            return 'R$ ' + (valor / 1000).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) + ' mil';
        }
        return 'R$ ' + Number(valor).toLocaleString('pt-BR');
    }

    function indiceMaior(valores) {
        let indice = -1;
        let maior = 0;
        valores.forEach(function (valor, i) {
            if (valor > maior) {
                maior = valor;
                indice = i;
            }
        });
        return indice;
    }

    function coresDasBarras(valores) {
        const destaque = indiceMaior(valores);
        return valores.map(function (_, i) {
            return i === destaque ? COR_DESTAQUE : COR_BASE;
        });
    }

    const valoresIniciais = <?= json_encode($chartValues) ?>;

    const chart = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                label: 'Valor gasto',
                data: valoresIniciais,
                backgroundColor: coresDasBarras(valoresIniciais),
                hoverBackgroundColor: COR_HOVER,
                borderRadius: { topLeft: 8, topRight: 8, bottomLeft: 0, bottomRight: 0 },
                borderSkipped: false,
                maxBarThickness: 44,
                categoryPercentage: 0.7,
                barPercentage: 0.9
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 350, easing: 'easeOutQuart' },
            interaction: { intersect: false, mode: 'index' },
            layout: { padding: { top: 6, right: 4 } },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#2a1e15',
                    titleColor: '#e8d3aa',
                    bodyColor: '#ffffff',
                    padding: 12,
                    cornerRadius: 10,
                    displayColors: false,
                    titleFont: { weight: '700', size: 12 },
                    bodyFont: { size: 13 },
                    callbacks: {
                        label: function (item) {
                            return formatarMoeda(item.parsed.y);
                        },
                        afterLabel: function (item) {
                            const total = item.dataset.data.reduce(function (soma, v) { return soma + v; }, 0);
                            if (total <= 0) {
                                return '';
                            }
                            return Math.round((item.parsed.y / total) * 100) + '% do total do período';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { color: '#e5dbcf' },
                    ticks: { autoSkip: false, maxRotation: 0, padding: 8 }
                },
                y: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: { color: '#efe7db', drawTicks: false },
                    ticks: { padding: 10, maxTicksLimit: 6, callback: formatarEixo }
                }
            }
        }
    });

    function atualizarResumo(rotulos, valores, descricao) {
        const total = valores.reduce(function (soma, v) { return soma + v; }, 0);
        const media = valores.length ? total / valores.length : 0;
        const topo = indiceMaior(valores);
        document.getElementById('kpiTotal').textContent = formatarMoeda(total);
        document.getElementById('kpiMedia').textContent = formatarMoeda(media);
        document.getElementById('kpiTop').textContent = topo >= 0 ? rotulos[topo] : '—';
        document.getElementById('kpiPeriodo').textContent = descricao;
        emptyState.hidden = total > 0;
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', async function (event) {
            event.preventDefault();

            if (tab.classList.contains('is-active') || card.classList.contains('is-loading')) {
                return;
            }

            const anterior = document.querySelector('.chart-period.is-active');
            card.classList.add('is-loading');

            try {
                const url = new URL(window.location.href);
                url.searchParams.set('periodo', tab.dataset.periodo);
                url.searchParams.set('ajax', 'grafico');

                const resposta = await fetch(url.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (!resposta.ok) {
                    throw new Error('Falha ao carregar o gráfico');
                }

                const resultado = await resposta.json();

                chart.data.labels = resultado.labels;
                chart.data.datasets[0].data = resultado.values;
                chart.data.datasets[0].backgroundColor = coresDasBarras(resultado.values);
                chart.update();

                atualizarResumo(resultado.labels, resultado.values, tab.dataset.descricao);

                tabs.forEach(function (item) {
                    item.classList.remove('is-active');
                    item.setAttribute('aria-selected', 'false');
                });
                tab.classList.add('is-active');
                tab.setAttribute('aria-selected', 'true');

                const atual = new URL(window.location.href);
                atual.searchParams.set('periodo', tab.dataset.periodo);
                atual.searchParams.delete('ajax');
                window.history.replaceState(null, '', atual.toString());
            } catch (erro) {
                console.error(erro);
                if (anterior) {
                    anterior.classList.add('is-active');
                }
            } finally {
                card.classList.remove('is-loading');
            }
        });
    });
});
</script>
<div style="height:20px">
                            </div>
                            <section class="card">
                                <div class="card-header">
                                    <div>
                                        <h3>Atalhos</h3>
                                        <span>Operações frequentes</span>
                                    </div>
                                    <div class="kpi-inline">
                                        <strong>
                                            <?= money($consumoTotal) ?>
                                        </strong>
                                        <span>consumo registrado</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="quick-actions">
                                        <a class="quick-action" href="<?= url('mesas/form.php') ?>">
                                            <span class="material-symbols-outlined">add_business</span>
                                            <div>
                                                <strong>Cadastrar mesa</strong>
                                                <small>Defina capacidade e status inicial.</small>
                                            </div>
                                        </a>
                                        <a class="quick-action" href="<?= url('clientes/form.php') ?>">
                                            <span class="material-symbols-outlined">person_add</span>
                                            <div>
                                                <strong>Cadastrar cliente</strong>
                                                <small>Organize os dados dos clientes.</small>
                                            </div>
                                        </a>
                                        <a class="quick-action" href="<?= url('reservas/form.php') ?>">
                                            <span class="material-symbols-outlined">calendar_add_on</span>
                                            <div>
                                                <strong>Registrar reserva</strong>
                                                <small>Escolha cliente, mesa e horário.</small>
                                            </div>
                                        </a>
                                        <a class="quick-action" href="<?= url('consumos/form.php') ?>">
                                            <span class="material-symbols-outlined">receipt_long</span>
                                            <div>
                                                <strong>Lançar consumo</strong>
                                                <small>Atualize o gasto de cada mesa.</small>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </section>
                            <?php require __DIR__ . '/partials/footer.php'; ?>
