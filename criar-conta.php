<?php
declare(strict_types=1); require_once __DIR__.'/includes/functions.php';
if(is_logged_in()) redirect(is_admin()?'dashboard.php':'cliente.php');
$error=null; $tipo=$_POST['tipo']??'cliente';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $conf = $_POST['confirmacao'] ?? '';
    $tipo = $_POST['tipo'] ?? 'cliente';
    $chave = $_POST['chave_admin'] ?? '';

    if (empty($_POST['aceita_privacidade'])) $error = 'Você precisa aceitar a Política de Privacidade para criar sua conta.';
    elseif (mb_strlen($nome) < 3) $error = 'Digite um nome válido.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Digite um e-mail válido.';
    elseif (strlen($senha) < 8) $error = 'A senha precisa ter pelo menos 8 caracteres.';
    elseif ($senha !== $conf) $error = 'As senhas não coincidem.';
    elseif (!in_array($tipo, ['admin', 'cliente'], true)) $error = 'Tipo de acesso inválido.';
    elseif ($tipo === 'admin' && $chave !== '@MesaReserva_231009') $error = 'A chave administrativa informada está incorreta.';
    else {
        try {
            $check = $pdo->prepare('SELECT id FROM clientes WHERE email = ? UNION SELECT id FROM administradores WHERE email = ? LIMIT 1');
            $check->execute([$email, $email]);
            if ($check->fetch()) throw new RuntimeException('Este e-mail já está cadastrado.');

            // Até a confirmação, nome, e-mail e senha ficam somente em arquivo temporário protegido.
            $token = verification_token();
            $registration = [
                'nome' => $nome,
                'email' => $email,
                'senha' => password_hash($senha, PASSWORD_DEFAULT),
                'tipo' => $tipo,
                'expires_at' => time() + 86400,
            ];
            save_pending_registration($token, $registration);
            $_SESSION['verification_email'] = $email;
            $_SESSION['verification_type'] = $tipo;
            $_SESSION['verification_token'] = $token;
            try {
                send_verification_email($nome, $email, $token);
                $_SESSION['verification_sent'] = true;
            } catch (Throwable $mailError) {
                $_SESSION['verification_sent'] = false;
                $_SESSION['verification_mail_error'] = $mailError->getMessage();
            }
            redirect('verificar-email.php?created=1');
        } catch (Throwable $e) {
            $error = $e instanceof RuntimeException ? $e->getMessage() : 'Não foi possível criar a conta.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1.0">
        <title>Criar conta | MesaReserva</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,300..600,0..1,0..200&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="<?= url('assets/css/style.css?v=20261115pro1') ?>">
    </head>
    <body class="auth-page recovery-page signup-auth-page">
        <div class="login-page">
            <section class="login-visual">
                <div class="login-copy">
                    <span class="eyebrow gold">UMA CONTA PARA CADA EXPERIÊNCIA</span>
                    <h2>Entre para o<br>
                        <span>seu próximo momento.</span>
                    </h2>
                    <p>Clientes podem reservar e acompanhar suas solicitações. Administradores têm acesso à gestão completa do restaurante.</p>
                </div>
            </section>

            <section class="rec-panel">
                <div class="rec-card rec-card--wide">
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
                        <h1>Criar conta</h1>
                        <p>Escolha como você deseja acessar.</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="rec-alert" role="alert">
                            <span class="material-symbols-outlined">error</span>
                            <span><?= e($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                        <div class="rec-seg" role="radiogroup" aria-label="Tipo de conta">
                            <label class="rec-seg-option">
                                <input type="radio" name="tipo" value="cliente" <?= $tipo === 'cliente' ? 'checked' : '' ?>>
                                <span class="rec-seg-body">
                                    <span class="material-symbols-outlined">person</span>
                                    <span class="rec-seg-text"><b>Cliente</b><small>Reservar mesas</small></span>
                                </span>
                            </label>
                            <label class="rec-seg-option">
                                <input type="radio" name="tipo" value="admin" <?= $tipo === 'admin' ? 'checked' : '' ?>>
                                <span class="rec-seg-body">
                                    <span class="material-symbols-outlined">admin_panel_settings</span>
                                    <span class="rec-seg-text"><b>Administrador</b><small>Gestão do restaurante</small></span>
                                </span>
                            </label>
                        </div>

                        <div class="rec-field">
                            <label for="nome">Nome completo</label>
                            <div class="rec-input">
                                <span class="material-symbols-outlined rec-input-icon">badge</span>
                                <input id="nome" class="rec-control" name="nome" value="<?= e($_POST['nome'] ?? '') ?>" placeholder="Seu nome" autocomplete="name" required>
                            </div>
                        </div>

                        <div class="rec-field">
                            <label for="email">E-mail</label>
                            <div class="rec-input">
                                <span class="material-symbols-outlined rec-input-icon">mail</span>
                                <input id="email" class="rec-control" type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="seu@email.com" autocomplete="email" required>
                            </div>
                        </div>

                        <div class="rec-row">
                            <div class="rec-field">
                                <label for="senha">Senha</label>
                                <div class="rec-input">
                                    <span class="material-symbols-outlined rec-input-icon">lock</span>
                                    <input id="senha" class="rec-control" type="password" name="senha" minlength="8" placeholder="Sua senha" autocomplete="new-password" required>
                                    <button class="rec-toggle" type="button" aria-label="Mostrar senha" aria-pressed="false" data-password-target="senha">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </button>
                                </div>
                            </div>
                            <div class="rec-field">
                                <label for="confirmacao">Confirmar senha</label>
                                <div class="rec-input">
                                    <span class="material-symbols-outlined rec-input-icon">lock_clock</span>
                                    <input id="confirmacao" class="rec-control" type="password" name="confirmacao" minlength="8" placeholder="Repita a senha" autocomplete="new-password" required>
                                    <button class="rec-toggle" type="button" aria-label="Mostrar senha" aria-pressed="false" data-password-target="confirmacao">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="rec-rules rec-rules--inline" id="passwordRules">
                            <span class="rec-rules-title">Sua senha precisa ter:</span>
                            <span class="rec-rule" data-rule="length"><span class="material-symbols-outlined">radio_button_unchecked</span>8 caracteres ou mais</span>
                            <span class="rec-rule" data-rule="letter"><span class="material-symbols-outlined">radio_button_unchecked</span>Uma letra</span>
                            <span class="rec-rule" data-rule="number"><span class="material-symbols-outlined">radio_button_unchecked</span>Um número</span>
                        </div>
                        <div id="passwordMatch" class="rec-match"></div>

                        <div class="rec-admin" id="adminKey">
                            <label for="chaveAdminInput"><span class="material-symbols-outlined">key</span>Chave de cadastro administrativo</label>
                            <div class="rec-input">
                                <span class="material-symbols-outlined rec-input-icon">vpn_key</span>
                                <input id="chaveAdminInput" class="rec-control" type="password" name="chave_admin" placeholder="Digite a chave fornecida pelo restaurante" autocomplete="off">
                                <button class="rec-toggle" type="button" aria-label="Mostrar senha" aria-pressed="false" data-password-target="chaveAdminInput">
                                    <span class="material-symbols-outlined">visibility</span>
                                </button>
                            </div>
                            <small>Necessária somente para criar uma conta de administrador.</small>
                        </div>

                        <label class="rec-check" for="aceita-privacidade">
                            <input id="aceita-privacidade" type="checkbox" name="aceita_privacidade" value="1" required <?= !empty($_POST['aceita_privacidade']) ? 'checked' : '' ?>>
                            <span>Li e aceito as <a href="<?= url('privacidade.php') ?>">Políticas de Privacidade</a>.</span>
                        </label>

                        <button class="rec-btn rec-btn--primary" type="submit">
                            Criar conta
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </button>
                    </form>

                    <div class="rec-foot">
                        Já tem uma conta? <a href="<?= url('login.php') ?>">Entrar</a>
                    </div>
                </div>
            </section>
        </div>

<script>
document.querySelectorAll('[data-password-target]').forEach((toggleButton) => {
    const target = document.getElementById(toggleButton.dataset.passwordTarget);
    if (!target) return;
    toggleButton.addEventListener('click', () => {
        const visible = target.type === 'password';
        target.type = visible ? 'text' : 'password';
        toggleButton.setAttribute('aria-pressed', visible ? 'true' : 'false');
        toggleButton.setAttribute('aria-label', visible ? 'Ocultar senha' : 'Mostrar senha');
        toggleButton.querySelector('.material-symbols-outlined').textContent = visible ? 'visibility_off' : 'visibility';
    });
});

const radios = document.querySelectorAll('input[name=tipo]');
const key = document.getElementById('adminKey');
const senha = document.getElementById('senha');
const confirmacao = document.getElementById('confirmacao');
const match = document.getElementById('passwordMatch');

function toggle() {
    key.style.display = document.querySelector('input[name=tipo]:checked').value === 'admin' ? 'flex' : 'none';
}
radios.forEach((radio) => radio.addEventListener('change', toggle));
toggle();

function rule(name, ok) {
    const element = document.querySelector('[data-rule="' + name + '"]');
    if (!element) return;
    element.classList.toggle('ok', ok);
    element.querySelector('.material-symbols-outlined').textContent = ok ? 'check_circle' : 'radio_button_unchecked';
}

function validatePassword() {
    const value = senha.value;
    rule('length', value.length >= 8);
    rule('letter', /[A-Za-zÀ-ÿ]/.test(value));
    rule('number', /\d/.test(value));
}

function validateMatch() {
    if (!confirmacao.value) {
        match.textContent = '';
        match.className = 'rec-match';
        return;
    }
    const ok = senha.value === confirmacao.value;
    match.textContent = ok ? 'As senhas coincidem.' : 'As senhas ainda não coincidem.';
    match.className = 'rec-match ' + (ok ? 'ok' : 'error');
}

senha.addEventListener('input', () => {
    validatePassword();
    validateMatch();
});
confirmacao.addEventListener('input', validateMatch);

document.querySelector('form').addEventListener('submit', (event) => {
    const strong = senha.value.length >= 8
        && /[A-Za-zÀ-ÿ]/.test(senha.value)
        && /\d/.test(senha.value)
        && senha.value === confirmacao.value;
    if (!strong) {
        event.preventDefault();
        validatePassword();
        validateMatch();
        senha.focus();
    }
});
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
