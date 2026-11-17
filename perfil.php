<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_login();

$pageTitle = is_admin() ? 'Perfil administrativo' : 'Meu perfil';
$userId = current_user_id();
$isClient = is_cliente();
$error = null;

$table = $isClient ? 'clientes' : 'administradores';
$table = $isClient ? 'clientes' : 'administradores';
$telefoneSelect = $isClient ? 'telefone' : 'NULL AS telefone';
$stmt = $pdo->prepare("SELECT id,nome,email,avatar,{$telefoneSelect} FROM {$table} WHERE id=? LIMIT 1");
$stmt->execute([$userId]);
$perfil = $stmt->fetch();

if (!$perfil) {
    logout_user();
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'alterar_senha') {
    verify_csrf();
    $atual = (string)($_POST['senha_atual'] ?? '');
    $nova = (string)($_POST['nova_senha'] ?? '');
    $confirmar = (string)($_POST['confirmar_senha'] ?? '');
    $check = $pdo->prepare("SELECT senha FROM {$table} WHERE id=?");
    $check->execute([$userId]); $hashAtual = (string)$check->fetchColumn();
    if (!password_verify($atual, $hashAtual)) $error = 'A senha atual está incorreta.';
    elseif (strlen($nova) < 8) $error = 'A nova senha deve ter pelo menos 8 caracteres.';
    elseif ($nova !== $confirmar) $error = 'A confirmação da nova senha não confere.';
    else {
        $pdo->prepare("UPDATE {$table} SET senha=? WHERE id=?")->execute([password_hash($nova, PASSWORD_DEFAULT), $userId]);
        flash('success', 'Senha alterada com sucesso.');
        redirect('perfil.php');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $removeAvatar = isset($_POST['remove_avatar']);

    if (mb_strlen($nome) < 3) {
        $error = 'Digite um nome válido.';
    } elseif ($isClient && $telefone !== '' && !preg_match('/^[0-9()\s+\-]{8,30}$/', $telefone)) {
        $error = 'Digite um telefone válido.';
    } else {
        $newAvatar = $perfil['avatar'];
        $uploadedPath = null;

        try {
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                    throw new RuntimeException('Não foi possível enviar a foto.');
                }

                if ((int)$_FILES['avatar']['size'] > 2 * 1024 * 1024) {
                    throw new RuntimeException('A foto deve ter no máximo 2 MB.');
                }

                $imageInfo = @getimagesize($_FILES['avatar']['tmp_name']);
                $allowedMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

                if (!$imageInfo || !isset($allowedMime[$imageInfo['mime']])) {
                    throw new RuntimeException('Use uma imagem JPG, PNG ou WEBP.');
                }

                $uploadDir = __DIR__ . '/assets/uploads/avatars';
                if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                    throw new RuntimeException('Não foi possível preparar a pasta de fotos.');
                }

                $filename = 'user_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $allowedMime[$imageInfo['mime']];
                $target = $uploadDir . '/' . $filename;

                if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
                    throw new RuntimeException('Não foi possível salvar a foto.');
                }

                $newAvatar = 'assets/uploads/avatars/' . $filename;
                $uploadedPath = $target;
            }

            if ($removeAvatar && !$uploadedPath) {
                $newAvatar = null;
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("UPDATE {$table} SET nome = ?, avatar = ? WHERE id = ?");
            $stmt->execute([$nome, $newAvatar, $userId]);

            if ($isClient && !empty($perfil['id'])) {
                $stmt = $pdo->prepare('UPDATE clientes SET nome = ?, telefone = ? WHERE id = ?');
                $stmt->execute([$nome, $telefone !== '' ? $telefone : null, $userId]);
            }

            $pdo->commit();

            if (($perfil['avatar'] ?? null) && (($removeAvatar && !$uploadedPath) || $uploadedPath)) {
                $oldFile = __DIR__ . '/' . ltrim((string)$perfil['avatar'], '/');
                if (is_file($oldFile)) {
                    @unlink($oldFile);
                }
            }

            $_SESSION['usuario_nome'] = $nome;
            $_SESSION['usuario_avatar'] = $newAvatar;
            flash('success', 'Seu perfil foi atualizado com sucesso.');
            redirect('perfil.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($uploadedPath && is_file($uploadedPath)) {
                @unlink($uploadedPath);
            }
            $error = $e instanceof RuntimeException ? $e->getMessage() : 'Não foi possível atualizar seu perfil.';
        }
    }
}

require __DIR__ . ($isClient ? '/partials/client-header.php' : '/partials/header.php');
?>

<div class="pf-page">
    <div class="page-heading">
        <div>
            <span class="section-kicker">MINHA CONTA</span>
            <h2><?= $isClient ? 'Meu perfil de cliente' : 'Perfil administrativo' ?></h2>
            <p>Gerencie seus dados de acesso e as informações da sua conta.</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <span class="material-symbols-outlined">error</span>
            <span><?= e($error) ?></span>
            <button class="alert-close" onclick="this.parentElement.remove()">×</button>
        </div>
    <?php endif; ?>

    <section class="pf-hero">
        <div class="pf-avatar-wrap">
            <?php if (!empty($perfil['avatar'])): ?>
                <img id="pfAvatar" src="<?= url($perfil['avatar']) ?>" alt="Foto de <?= e($perfil['nome']) ?>" class="pf-avatar">
            <?php else: ?>
                <div id="pfAvatar" class="pf-avatar pf-avatar--initial"><?= e(mb_strtoupper(mb_substr($perfil['nome'], 0, 1))) ?></div>
            <?php endif; ?>
            <label class="pf-avatar-btn" for="avatar" title="Trocar foto">
                <span class="material-symbols-outlined">photo_camera</span>
            </label>
            <input id="avatar" class="sr-only" type="file" name="avatar" form="perfilForm" accept="image/jpeg,image/png,image/webp">
        </div>

        <div class="pf-hero-info">
            <span class="pf-chip">
                <span class="material-symbols-outlined"><?= $isClient ? 'person' : 'admin_panel_settings' ?></span>
                <?= $isClient ? 'Cliente' : 'Administrador' ?>
            </span>
            <strong class="pf-name"><?= e($perfil['nome']) ?></strong>
            <span class="pf-email"><span class="material-symbols-outlined">mail</span><?= e($perfil['email']) ?></span>
        </div>

        <div class="pf-hero-actions">
            <label class="pf-hero-btn" for="avatar">
                <span class="material-symbols-outlined">upload</span>
                Trocar foto
            </label>
            <?php if (!empty($perfil['avatar'])): ?>
                <label class="pf-hero-check">
                    <input type="checkbox" name="remove_avatar" form="perfilForm">
                    Remover foto atual
                </label>
            <?php endif; ?>
            <small>JPG, PNG ou WEBP · até 2 MB</small>
        </div>
    </section>

    <div class="pf-grid">
        <section class="pf-card">
            <header class="pf-card-head">
                <span class="pf-card-icon"><span class="material-symbols-outlined">badge</span></span>
                <div>
                    <h3>Dados pessoais</h3>
                    <p>Atualize as informações que aparecem na sua conta.</p>
                </div>
            </header>
            <form method="post" enctype="multipart/form-data" id="perfilForm" class="pf-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div class="pf-field pf-field--full">
                    <label for="pfNome">Nome completo *</label>
                    <div class="pf-input">
                        <span class="material-symbols-outlined pf-input-icon">person</span>
                        <input id="pfNome" class="pf-control" name="nome" value="<?= e($perfil['nome']) ?>" autocomplete="name" required>
                    </div>
                </div>

                <div class="pf-field pf-field--full">
                    <label>E-mail</label>
                    <div class="pf-input pf-input--readonly">
                        <span class="material-symbols-outlined pf-input-icon">mail</span>
                        <input class="pf-control" type="email" value="<?= e($perfil['email']) ?>" readonly>
                        <span class="material-symbols-outlined pf-lock" title="O e-mail não pode ser alterado">lock</span>
                    </div>
                </div>

                <div class="pf-field<?= $isClient ? '' : ' pf-field--full' ?>">
                    <label>Tipo de conta</label>
                    <div class="pf-input pf-input--readonly">
                        <span class="material-symbols-outlined pf-input-icon"><?= $isClient ? 'person' : 'admin_panel_settings' ?></span>
                        <input class="pf-control" value="<?= $isClient ? 'Cliente' : 'Administrador do restaurante' ?>" readonly>
                        <span class="material-symbols-outlined pf-lock">lock</span>
                    </div>
                </div>

                <?php if ($isClient): ?>
                    <div class="pf-field">
                        <label for="pfTelefone">Telefone</label>
                        <div class="pf-input">
                            <span class="material-symbols-outlined pf-input-icon">call</span>
                            <input id="pfTelefone" class="pf-control" name="telefone" type="tel" value="<?= e($perfil['telefone'] ?? '') ?>" placeholder="(12) 99999-9999" autocomplete="tel">
                        </div>
                        <small class="pf-help">Seu telefone será usado para confirmar e organizar suas reservas.</small>
                    </div>
                <?php endif; ?>

                <div class="pf-actions">
                    <a class="pf-btn pf-btn--ghost" href="<?= url($isClient ? 'cliente.php' : 'dashboard.php') ?>">Voltar</a>
                    <button class="pf-btn pf-btn--primary" type="submit">
                        <span class="material-symbols-outlined">save</span>
                        Salvar alterações
                    </button>
                </div>
            </form>
        </section>

        <section class="pf-card">
            <header class="pf-card-head">
                <span class="pf-card-icon"><span class="material-symbols-outlined">shield_lock</span></span>
                <div>
                    <h3>Segurança da conta</h3>
                    <p>Altere sua senha de acesso.</p>
                </div>
            </header>
            <form method="post" class="pf-form" id="pfSenhaForm">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="acao" value="alterar_senha">

                <div class="pf-field pf-field--full">
                    <label for="pfAtual">Senha atual</label>
                    <div class="pf-input">
                        <span class="material-symbols-outlined pf-input-icon">lock</span>
                        <input id="pfAtual" class="pf-control" type="password" name="senha_atual" autocomplete="current-password" required>
                        <button class="pf-toggle" type="button" aria-label="Mostrar senha" aria-pressed="false" data-password-target="pfAtual"><span class="material-symbols-outlined">visibility</span></button>
                    </div>
                </div>

                <div class="pf-field">
                    <label for="pfNova">Nova senha</label>
                    <div class="pf-input">
                        <span class="material-symbols-outlined pf-input-icon">key</span>
                        <input id="pfNova" class="pf-control" type="password" name="nova_senha" minlength="8" autocomplete="new-password" required>
                        <button class="pf-toggle" type="button" aria-label="Mostrar senha" aria-pressed="false" data-password-target="pfNova"><span class="material-symbols-outlined">visibility</span></button>
                    </div>
                </div>

                <div class="pf-field">
                    <label for="pfConfirmar">Confirmar nova senha</label>
                    <div class="pf-input">
                        <span class="material-symbols-outlined pf-input-icon">lock_clock</span>
                        <input id="pfConfirmar" class="pf-control" type="password" name="confirmar_senha" minlength="8" autocomplete="new-password" required>
                        <button class="pf-toggle" type="button" aria-label="Mostrar senha" aria-pressed="false" data-password-target="pfConfirmar"><span class="material-symbols-outlined">visibility</span></button>
                    </div>
                </div>

                <div class="pf-rules pf-field--full">
                    <span class="pf-rule" data-rule="length"><span class="material-symbols-outlined">radio_button_unchecked</span>8 caracteres ou mais</span>
                    <span class="pf-rule" data-rule="match"><span class="material-symbols-outlined">radio_button_unchecked</span>As senhas são iguais</span>
                </div>

                <div class="pf-actions">
                    <button class="pf-btn pf-btn--primary" type="submit">
                        <span class="material-symbols-outlined">lock_reset</span>
                        Alterar senha
                    </button>
                </div>
            </form>
        </section>
    </div>

    <section class="pf-danger">
        <div class="pf-danger-text">
            <span class="pf-card-icon pf-card-icon--danger"><span class="material-symbols-outlined">delete_forever</span></span>
            <div>
                <h3>Excluir conta</h3>
                <p>Remova seu acesso ao MesaReserva. Se houver reservas vinculadas, seus dados pessoais serão anonimizados para preservar o histórico das reservas.</p>
            </div>
        </div>
        <form method="post" action="<?= url('excluir-conta.php') ?>" class="pf-danger-form" onsubmit="return confirm('Tem certeza de que deseja excluir sua conta? Esta ação não poderá ser desfeita.');">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="pf-field">
                <label for="pfExcluirSenha">Confirme sua senha</label>
                <div class="pf-input">
                    <span class="material-symbols-outlined pf-input-icon">lock</span>
                    <input id="pfExcluirSenha" class="pf-control" type="password" name="senha_atual" autocomplete="current-password" required>
                </div>
            </div>
            <button class="pf-btn pf-btn--danger" type="submit">
                <span class="material-symbols-outlined">delete_forever</span>
                Excluir minha conta
            </button>
        </form>
    </section>
</div>

<script>
(() => {
    // Mostrar/ocultar senha
    document.querySelectorAll('.pf-page [data-password-target]').forEach((toggle) => {
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

    // Requisitos da nova senha
    const nova = document.getElementById('pfNova');
    const conf = document.getElementById('pfConfirmar');
    const rLength = document.querySelector('.pf-rule[data-rule="length"]');
    const rMatch = document.querySelector('.pf-rule[data-rule="match"]');
    const setRule = (el, ok) => {
        el.classList.toggle('ok', ok);
        el.querySelector('.material-symbols-outlined').textContent = ok ? 'check_circle' : 'radio_button_unchecked';
    };
    const update = () => {
        setRule(rLength, nova.value.length >= 8);
        setRule(rMatch, conf.value.length > 0 && nova.value === conf.value);
    };
    nova.addEventListener('input', update);
    conf.addEventListener('input', update);
    update();

    // Pré-visualização da foto escolhida
    const fileInput = document.getElementById('avatar');
    fileInput.addEventListener('change', () => {
        const file = fileInput.files && fileInput.files[0];
        if (!file) return;
        const url = URL.createObjectURL(file);
        let avatar = document.getElementById('pfAvatar');
        if (avatar.tagName !== 'IMG') {
            const img = document.createElement('img');
            img.id = 'pfAvatar';
            img.className = 'pf-avatar';
            img.alt = 'Nova foto de perfil';
            avatar.replaceWith(img);
            avatar = img;
        }
        avatar.src = url;
    });
})();
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>
