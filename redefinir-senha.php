<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$hash = hash('sha256', $token);
$account = null;
$table = null;

foreach (['administradores', 'clientes'] as $t) {
    $q = $pdo->prepare("SELECT id FROM {$t} WHERE token_redefinicao_senha=? AND token_redefinicao_expira>NOW() LIMIT 1");
    $q->execute([$hash]);
    if ($account = $q->fetch()) {
        $table = $t;
        break;
    }
}

$error = null;
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $senha = (string)($_POST['senha'] ?? '');
    $confirmacao = (string)($_POST['confirmacao'] ?? '');

    if (!$account) {
        $error = 'Este link é inválido ou expirou. Solicite uma nova redefinição.';
    } elseif (strlen($senha) < 8) {
        $error = 'A senha deve ter pelo menos 8 caracteres.';
    } elseif ($senha !== $confirmacao) {
        $error = 'As senhas não coincidem.';
    } else {
        $pdo->prepare("UPDATE {$table} SET senha=?,token_redefinicao_senha=NULL,token_redefinicao_expira=NULL WHERE id=?")->execute([password_hash($senha, PASSWORD_DEFAULT), $account['id']]);
        $done = true;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1.0">
        <title>Nova senha | MesaReserva</title>
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
                    <h2>Uma nova senha,<br>
                        <span>um novo começo.</span>
                    </h2>
                    <p>Escolha uma senha segura para proteger suas reservas e continuar aproveitando o MesaReserva.</p>
                </div>
            </section>

            <section class="rec-panel">
                <div class="rec-card">
                    <div class="rec-nav">
                        <a class="rec-back" href="<?= url('login.php') ?>">
                            <span class="material-symbols-outlined">arrow_back</span>
                            Voltar ao login
                        </a>
                        <a class="rec-home" href="<?= url() ?>">
                            <span class="material-symbols-outlined">home</span>
                            Início
                        </a>
                    </div>

                    <a class="rec-brand" href="<?= url() ?>">
                        <img src="<?= url('assets/img/logo.png') ?>" alt="MesaReserva">
                    </a>

                    <?php if ($done): ?>
                        <div class="rec-head">
                            <div class="rec-icon rec-icon--success">
                                <span class="material-symbols-outlined">check_circle</span>
                            </div>
                            <h1>Senha alterada!</h1>
                            <p>Sua nova senha já está valendo. Você já pode entrar na sua conta.</p>
                        </div>
                        <a class="rec-btn rec-btn--primary" href="<?= url('login.php') ?>">
                            Ir para o login
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </a>

                    <?php elseif (!$account): ?>
                        <div class="rec-head">
                            <div class="rec-icon rec-icon--error">
                                <span class="material-symbols-outlined">link_off</span>
                            </div>
                            <h1>Link inválido ou expirado</h1>
                            <p>Por segurança, o link de redefinição vale por 1 hora e só pode ser usado uma vez. Solicite um novo para continuar.</p>
                        </div>
                        <a class="rec-btn rec-btn--primary" href="<?= url('esqueci-senha.php') ?>">
                            Solicitar novo link
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </a>
                        <a class="rec-btn rec-btn--ghost" href="<?= url('login.php') ?>">
                            Voltar para entrar
                        </a>

                    <?php else: ?>
                        <div class="rec-head">
                            <div class="rec-icon">
                                <span class="material-symbols-outlined">key</span>
                            </div>
                            <h1>Crie sua nova senha</h1>
                            <p>Escolha uma senha segura para continuar usando o MesaReserva.</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="rec-alert" role="alert">
                                <span class="material-symbols-outlined">error</span>
                                <span><?= e($error) ?></span>
                            </div>
                        <?php endif; ?>

                        <form method="post" id="resetForm">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="token" value="<?= e($token) ?>">

                            <div class="rec-field">
                                <label for="senha">Nova senha</label>
                                <div class="rec-input">
                                    <span class="material-symbols-outlined rec-input-icon">lock</span>
                                    <input id="senha" class="rec-control" type="password" name="senha" minlength="8" placeholder="Mínimo de 8 caracteres" autocomplete="new-password" autofocus required>
                                    <button class="rec-toggle" type="button" aria-label="Mostrar senha" aria-pressed="false" data-password-target="senha">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </button>
                                </div>
                            </div>

                            <div class="rec-field">
                                <label for="confirmacao">Confirme sua senha</label>
                                <div class="rec-input">
                                    <span class="material-symbols-outlined rec-input-icon">lock_clock</span>
                                    <input id="confirmacao" class="rec-control" type="password" name="confirmacao" minlength="8" placeholder="Repita a nova senha" autocomplete="new-password" required>
                                    <button class="rec-toggle" type="button" aria-label="Mostrar senha" aria-pressed="false" data-password-target="confirmacao">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </button>
                                </div>
                            </div>

                            <ul class="rec-rules" aria-live="polite">
                                <li class="rec-rule" data-rule="length">
                                    <span class="material-symbols-outlined">radio_button_unchecked</span>
                                    8 caracteres ou mais
                                </li>
                                <li class="rec-rule" data-rule="match">
                                    <span class="material-symbols-outlined">radio_button_unchecked</span>
                                    As duas senhas são iguais
                                </li>
                            </ul>

                            <button class="rec-btn rec-btn--primary" type="submit">
                                Salvar nova senha
                                <span class="material-symbols-outlined">check</span>
                            </button>
                        </form>

                        <div class="rec-foot">
                            Lembrou da senha? <a href="<?= url('login.php') ?>">Entrar</a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <?php if ($account && !$done): ?>
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

            const senha = document.getElementById('senha');
            const confirmacao = document.getElementById('confirmacao');
            const rules = {
                length: document.querySelector('[data-rule="length"]'),
                match: document.querySelector('[data-rule="match"]')
            };
            const setRule = (el, ok) => {
                el.classList.toggle('ok', ok);
                el.querySelector('.material-symbols-outlined').textContent = ok ? 'check_circle' : 'radio_button_unchecked';
            };
            const update = () => {
                setRule(rules.length, senha.value.length >= 8);
                setRule(rules.match, confirmacao.value.length > 0 && senha.value === confirmacao.value);
            };
            senha.addEventListener('input', update);
            confirmacao.addEventListener('input', update);
            update();
        })();
        </script>
        <?php endif; ?>
    </body>
</html>
