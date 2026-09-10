<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$pageTitle = $pageTitle ?? APP_NAME;
$flash = get_flash();
$currentPath = $_SERVER['REQUEST_URI'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,300..600,0..1,0..200&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css?v=20260910b') ?>">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <a href="<?= url('dashboard.php') ?>" class="brand-link">
                <img src="<?= url('assets/img/logo.png') ?>" alt="MesaReserva">
            </a>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-label">GESTÃO</div>
            <a class="nav-item <?= str_contains($currentPath, '/dashboard.php') ? 'active' : '' ?>" href="<?= url('dashboard.php') ?>">
                <span class="material-symbols-outlined">dashboard</span>
                <span>Dashboard</span>
            </a>
            <a class="nav-item <?= str_contains($currentPath, '/mesas/') ? 'active' : '' ?>" href="<?= url('mesas/index.php') ?>">
                <span class="material-symbols-outlined">table_restaurant</span>
                <span>Mesas</span>
            </a>
            <a class="nav-item <?= str_contains($currentPath, '/reservas/') ? 'active' : '' ?>" href="<?= url('reservas/index.php') ?>">
                <span class="material-symbols-outlined">calendar_month</span>
                <span>Reservas</span>
            </a>
            <a class="nav-item <?= str_contains($currentPath, '/clientes/') ? 'active' : '' ?>" href="<?= url('clientes/index.php') ?>">
                <span class="material-symbols-outlined">groups</span>
                <span>Clientes</span>
            </a>
            <a class="nav-item <?= str_contains($currentPath, '/consumos/') ? 'active' : '' ?>" href="<?= url('consumos/index.php') ?>">
                <span class="material-symbols-outlined">receipt_long</span>
                <span>Consumos</span>
            </a>

            <div class="nav-label nav-label-space">SISTEMA</div>
            <a class="nav-item <?= str_contains($currentPath, '/perguntas/') ? 'active' : '' ?>" href="<?= url('perguntas/index.php') ?>">
                <span class="material-symbols-outlined">support_agent</span>
                <span>Atendimento</span>
            </a>
            <a class="nav-item <?= str_contains($currentPath, '/avaliacoes/') ? 'active' : '' ?>" href="<?= url('avaliacoes/index.php') ?>">
                <span class="material-symbols-outlined">star</span>
                <span>Avaliações</span>
            </a>
            <div class="nav-label nav-label-space">SITE</div>
            <a class="nav-item" href="<?= url('index.php') ?>">
                <span class="material-symbols-outlined">public</span>
                <span>Ver site</span>
            </a>
            <a class="nav-item" href="<?= url('privacidade.php') ?>">
                <span class="material-symbols-outlined">privacy_tip</span>
                <span>Privacidade</span>
            </a>
            <a class="nav-item" href="<?= url('logout.php') ?>">
                <span class="material-symbols-outlined">logout</span>
                <span>Sair</span>
            </a>
        </nav>

        <div class="sidebar-bottom">
            <div class="user-mini">
                <div class="user-avatar"><?= e(mb_strtoupper(mb_substr(current_user_name(), 0, 1))) ?></div>
                <div>
                    <strong><?= e(current_user_name()) ?></strong>
                    <span>Administrador</span>
                </div>
            </div>
        </div>
    </aside>

    <div class="main-area">
        <header class="topbar">
            <button class="icon-button mobile-menu" id="mobileMenu" aria-label="Abrir menu">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <div class="topbar-title">
                <span class="eyebrow">RESTAURANTE MESARESERVA</span>
                <h1><?= e($pageTitle) ?></h1>
            </div>
            <div class="topbar-actions">
                <div class="date-pill">
                    <span class="material-symbols-outlined">calendar_today</span>
                    <?= date('d/m/Y') ?>
                </div>
                <a class="profile-chip" href="<?= url('logout.php') ?>" title="Sair">
                    <span class="material-symbols-outlined">logout</span>
                </a>
            </div>
        </header>

        <main class="content">
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>">
                    <span class="material-symbols-outlined"><?= $flash['type'] === 'success' ? 'check_circle' : 'error' ?></span>
                    <span><?= e($flash['message']) ?></span>
                    <button class="alert-close" onclick="this.parentElement.remove()">×</button>
                </div>
            <?php endif; ?>
