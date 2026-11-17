<?php
declare(strict_types=1); require_once __DIR__.'/includes/functions.php';
if(is_logged_in()){ redirect(is_admin()?'dashboard.php':'cliente.php'); }
$error=null; $tipo=$_POST['tipo']??'cliente';
if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$email=trim($_POST['email']??'');$senha=$_POST['senha']??'';$tipo=$_POST['tipo']??'cliente';if(!in_array($tipo,['admin','cliente'],true)||!filter_var($email,FILTER_VALIDATE_EMAIL)||!$senha){$error='Preencha todos os campos corretamente.';}else{$table=$tipo==='admin'?'administradores':'clientes';$s=$pdo->prepare("SELECT id,nome,email,senha,avatar,ativo,email_verificado_em FROM {$table} WHERE email=? LIMIT 1");$s->execute([$email]);$u=$s->fetch();if($u&&(int)$u['ativo']===1&&!empty($u['senha'])&&password_verify($senha,$u['senha'])){if(empty($u['email_verificado_em'])){$error='Seu e-mail ainda não foi verificado. Confira sua caixa de entrada.';}else{$u['tipo']=$tipo;login_user($u);redirect($tipo==='admin'?'dashboard.php':'cliente.php');}}else{$error='E-mail, senha ou tipo de acesso inválidos.';}}}
?>
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1.0">
        <title>Entrar | MesaReserva</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,300..600,0..1,0..200&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="<?= url('assets/css/style.css?v=20261115pro1') ?>">
    </head>
    <body class="auth-page recovery-page">
        <div class="login-page">
            <section class="login-visual">
                <div class="login-copy">
                    <span class="eyebrow gold">RESTAURANTE MESARESERVA</span>
                    <h2>Sua mesa,<br>
                        <span>do seu jeito.</span>
                    </h2>
                    <p>Reserve com praticidade, acompanhe seus pedidos de reserva e fale diretamente com nossa equipe.</p>
                </div>
            </section>

            <section class="rec-panel">
                <div class="rec-card">
                    <div class="rec-nav">
                        <a class="rec-back" href="<?= url() ?>" onclick="if (window.history.length > 1) { window.history.back(); return false; }">
                            <span class="material-symbols-outlined">arrow_back</span>
                            Voltar
                        </a>
                        <a class="rec-home" href="<?= url() ?>">
                            <span class="material-symbols-outlined">home</span>
                            Início
                        </a>
                    </div>

                    <a class="rec-brand" href="<?= url() ?>">
                        <img src="<?= url('assets/img/logo.png') ?>" alt="MesaReserva">
                    </a>

                    <div class="rec-head rec-head--compact">
                        <h1>Bem-vindo</h1>
                        <p>Entre para continuar sua experiência.</p>
                    </div>

                    <?php $flashMessage = get_flash(); if ($flashMessage): ?>
                        <div class="rec-alert rec-alert--<?= $flashMessage['type'] === 'success' ? 'success' : 'error' ?>" role="status">
                            <span class="material-symbols-outlined"><?= $flashMessage['type'] === 'success' ? 'check_circle' : 'error' ?></span>
                            <span><?= e($flashMessage['message']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="rec-alert" role="alert">
                            <span class="material-symbols-outlined">error</span>
                            <span><?= e($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                        <div class="rec-seg" role="radiogroup" aria-label="Tipo de acesso">
                            <label class="rec-seg-option">
                                <input type="radio" name="tipo" value="cliente" <?= $tipo === 'cliente' ? 'checked' : '' ?>>
                                <span class="rec-seg-body">
                                    <span class="material-symbols-outlined">person</span>
                                    <span class="rec-seg-text"><b>Cliente</b><small>Reservar uma mesa</small></span>
                                </span>
                            </label>
                            <label class="rec-seg-option">
                                <input type="radio" name="tipo" value="admin" <?= $tipo === 'admin' ? 'checked' : '' ?>>
                                <span class="rec-seg-body">
                                    <span class="material-symbols-outlined">admin_panel_settings</span>
                                    <span class="rec-seg-text"><b>Administrador</b><small>Gerenciar restaurante</small></span>
                                </span>
                            </label>
                        </div>

                        <div class="rec-field">
                            <label for="loginEmail">E-mail</label>
                            <div class="rec-input">
                                <span class="material-symbols-outlined rec-input-icon">mail</span>
                                <input id="loginEmail" class="rec-control" type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="seu@email.com" autocomplete="email" required>
                            </div>
                        </div>

                        <div class="rec-field">
                            <div class="rec-label-row">
                                <label for="loginPassword">Senha</label>
                                <a href="<?= url('esqueci-senha.php') ?>">Esqueceu a senha?</a>
                            </div>
                            <div class="rec-input">
                                <span class="material-symbols-outlined rec-input-icon">lock</span>
                                <input id="loginPassword" class="rec-control" type="password" name="senha" placeholder="Sua senha" autocomplete="current-password" required>
                                <button class="rec-toggle" type="button" aria-label="Mostrar senha" aria-pressed="false" data-password-target="loginPassword">
                                    <span class="material-symbols-outlined">visibility</span>
                                </button>
                            </div>
                        </div>

                        <button class="rec-btn rec-btn--primary" type="submit">
                            Entrar
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </button>
                    </form>

                    <div class="rec-foot">
                        Ainda não tem conta? <a href="<?= url('criar-conta.php') ?>">Criar conta</a>
                    </div>
                </div>
            </section>
        </div>
<?php require __DIR__ . '/partials/footer.php'; ?>
<script>
(() => {
    document.querySelectorAll('[data-password-target]').forEach((toggle) => {
        const input = document.getElementById(toggle.dataset.passwordTarget);
        if (!input) return;
        toggle.addEventListener('click', () => {
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
            toggle.setAttribute('aria-label', show ? 'Ocultar senha' : 'Mostrar senha');
            toggle.querySelector('.material-symbols-outlined').textContent = show ? 'visibility_off' : 'visibility';
        });
    });
})();
</script>
</body>
</html>
