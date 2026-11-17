<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$pageTitle = 'Administradores';
$admins = $pdo->query(
    "SELECT id, nome, email, ativo, email_verificado_em, created_at
     FROM administradores
     ORDER BY nome"
)->fetchAll();

require __DIR__ . '/../partials/header.php';
?>
<div class="page-heading">
    <div>
        <span class="section-kicker">USUÁRIOS</span>
        <h2>Administradores</h2>
        <p>Veja quais administradores possuem acesso à gestão do MesaReserva.</p>
    </div>
</div>

<?php
$totalAdmins = count($admins);
$adminsAtivos = count(array_filter($admins, static fn(array $a): bool => (int)$a['ativo'] === 1));
$adminsVerificados = count(array_filter($admins, static fn(array $a): bool => !empty($a['email_verificado_em'])));
?>
<section class="stats-grid stats-grid-3">
    <div class="stat-card">
        <div class="stat-top">
            <div class="stat-icon">
                <span class="material-symbols-outlined">admin_panel_settings</span>
            </div>
        </div>
        <div class="stat-value"><?= $totalAdmins ?></div>
        <div class="stat-label"><?= $totalAdmins === 1 ? 'Administrador cadastrado' : 'Administradores cadastrados' ?></div>
    </div>
    <div class="stat-card stat-accent-green">
        <div class="stat-top">
            <div class="stat-icon">
                <span class="material-symbols-outlined">verified_user</span>
            </div>
        </div>
        <div class="stat-value"><?= $adminsAtivos ?></div>
        <div class="stat-label"><?= $adminsAtivos === 1 ? 'Conta ativa' : 'Contas ativas' ?></div>
    </div>
    <div class="stat-card stat-accent-yellow">
        <div class="stat-top">
            <div class="stat-icon">
                <span class="material-symbols-outlined">mark_email_read</span>
            </div>
        </div>
        <div class="stat-value"><?= $adminsVerificados ?></div>
        <div class="stat-label"><?= $adminsVerificados === 1 ? 'E-mail verificado' : 'E-mails verificados' ?></div>
    </div>
</section>

<section class="card">
    <div class="card-header">
        <div>
            <h3>Equipe administrativa</h3>
            <span>Contas com acesso ao painel de gestão</span>
        </div>
    </div>
    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Administrador</th>
                    <th>E-mail</th>
                    <th>Status</th>
                    <th>Verificação</th>
                    <th>Cadastro</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td>
                            <div class="cell-title"><?= e($admin['nome']) ?></div>
                            <div class="cell-sub">ID #<?= (int)$admin['id'] ?></div>
                        </td>
                        <td><?= e($admin['email']) ?></td>
                        <td>
                            <span class="status-badge <?= (int)$admin['ativo'] === 1 ? 'status-confirmada' : 'status-cancelada' ?>">
                                <?= (int)$admin['ativo'] === 1 ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </td>
                        <td>
                            <?= $admin['email_verificado_em'] ? 'Verificado' : 'Pendente' ?>
                        </td>
                        <td><?= date('d/m/Y', strtotime($admin['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$admins): ?>
        <div class="empty-state">
            <span class="material-symbols-outlined">admin_panel_settings</span>
            <p>Nenhum administrador cadastrado.</p>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
