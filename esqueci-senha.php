<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$error = null;
$success = false;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Informe um e-mail válido.';
    } else {
        try {
            $user = null;
            $table = null;
            foreach (['administradores', 'clientes'] as $t) {
                $q = $pdo->prepare("SELECT id,nome,email FROM {$t} WHERE email=? AND ativo=1 AND senha IS NOT NULL LIMIT 1");
                $q->execute([$email]);
                if ($user = $q->fetch()) {
                    $table = $t;
                    break;
                }
            }
            if ($user) {
                $token = bin2hex(random_bytes(32));
                $hash = hash('sha256', $token);
                $pdo->prepare("UPDATE {$table} SET token_redefinicao_senha=?,token_redefinicao_expira=DATE_ADD(NOW(),INTERVAL 1 HOUR) WHERE id=?")->execute([$hash, $user['id']]);
                $link = rtrim(verification_base_url(), '/') . '/redefinir-senha.php?token=' . urlencode($token);
                send_smtp_mail($user['email'], 'Redefinição de senha — MesaReserva', "Olá, {$user['nome']}!\n\nPara criar uma nova senha, acesse o link válido por 1 hora:\n\n{$link}\n\nSe não solicitou, ignore este e-mail.\nMesaReserva");
            }
            $success = true;
        } catch (Throwable $e) {
            error_log('MesaReserva password reset: ' . $e->getMessage());
            $error = 'Não foi possível concluir o envio. Confira as configurações SMTP e tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1.0">
        <title>Recuperar senha | MesaReserva</title>
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
                    <h2>Recupere o acesso<br>
                        <span>à sua conta.</span>
                    </h2>
                    <p>Enviaremos um link seguro para o seu e-mail para você criar uma nova senha em poucos instantes.</p>
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

                    <?php if ($success): ?>
                        <div class="rec-head">
                            <div class="rec-icon rec-icon--success">
                                <span class="material-symbols-outlined">mark_email_read</span>
                            </div>
                            <h1>Confira seu e-mail</h1>
                            <p>Se <strong><?= e($email) ?></strong> estiver cadastrado, enviaremos as instruções para redefinir a senha.</p>
                        </div>

                        <div class="rec-info">
                            <span class="material-symbols-outlined">schedule</span>
                            <p>O link é válido por <strong>1 hora</strong>. Não encontrou a mensagem? Olhe também a caixa de spam.</p>
                        </div>

                        <a class="rec-btn rec-btn--primary" href="<?= url('login.php') ?>">
                            Voltar para entrar
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </a>
                        <a class="rec-btn rec-btn--ghost" href="<?= url('esqueci-senha.php') ?>">
                            Usar outro e-mail
                        </a>
                    <?php else: ?>
                        <div class="rec-head">
                            <div class="rec-icon">
                                <span class="material-symbols-outlined">lock_reset</span>
                            </div>
                            <h1>Esqueceu sua senha?</h1>
                            <p>Informe o e-mail da sua conta para receber o link de redefinição.</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="rec-alert" role="alert">
                                <span class="material-symbols-outlined">error</span>
                                <span><?= e($error) ?></span>
                            </div>
                        <?php endif; ?>

                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <div class="rec-field">
                                <label for="email">E-mail cadastrado</label>
                                <div class="rec-input">
                                    <span class="material-symbols-outlined rec-input-icon">mail</span>
                                    <input id="email" class="rec-control" type="email" name="email" value="<?= e($email) ?>" placeholder="seu@email.com" autocomplete="email" autofocus required>
                                </div>
                            </div>
                            <button class="rec-btn rec-btn--primary" type="submit">
                                Enviar link de redefinição
                                <span class="material-symbols-outlined">send</span>
                            </button>
                        </form>

                        <div class="rec-foot">
                            Lembrou da senha? <a href="<?= url('login.php') ?>">Entrar</a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </body>
</html>
