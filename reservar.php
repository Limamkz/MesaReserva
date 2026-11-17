<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_login();

if (is_admin()) {
    redirect('dashboard.php');
}

expirar_reservas($pdo);
$cid = current_client_id();
if (!$cid) {
    redirect('cliente.php');
}

$error = null;

$clientStmt = $pdo->prepare('SELECT nome, telefone FROM clientes WHERE id = ? LIMIT 1');
$clientStmt->execute([$cid]);
$client = $clientStmt->fetch() ?: ['nome' => current_user_name(), 'telefone' => null];
$telefoneCadastrado = trim((string)($client['telefone'] ?? ''));

/**
 * Mesas com a situação de um dia/horário específico.
 * Uma mesa fica indisponível se já existe reserva pendente/confirmada nesse horário
 * (ou se está ocupada agora, quando a data escolhida é hoje).
 */
function mesas_para_horario(PDO $pdo, string $data, string $hora): array
{
    $stmt = $pdo->prepare(
        "SELECT m.id, m.numero, m.capacidade, m.status,
                EXISTS(SELECT 1 FROM reservas r
                       WHERE r.mesa_id = m.id AND r.data_reserva = ? AND r.hora_reserva = ?
                         AND r.status IN ('pendente','confirmada')) AS reservada
         FROM mesas m ORDER BY m.numero"
    );
    $stmt->execute([$data, $hora]);
    $hoje = $data === date('Y-m-d');
    $lista = [];
    foreach ($stmt->fetchAll() as $m) {
        $indisponivel = (int)$m['reservada'] === 1 || ($hoje && $m['status'] === 'ocupada');
        $lista[] = [
            'id' => (int)$m['id'],
            'numero' => (int)$m['numero'],
            'capacidade' => (int)$m['capacidade'],
            'reservada' => $indisponivel,
        ];
    }
    return $lista;
}

function slot_valido(string $data, string $hora): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $data);
    return $d && $d->format('Y-m-d') === $data && preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora) === 1
        && strtotime($data . ' ' . $hora) !== false;
}

// Consulta usada pela tela: devolve as mesas e se estão livres no horário escolhido.
if (($_GET['ajax'] ?? '') === 'disponibilidade') {
    header('Content-Type: application/json; charset=utf-8');
    $d = (string)($_GET['data'] ?? '');
    $h = (string)($_GET['hora'] ?? '');
    if (!slot_valido($d, $h)) {
        http_response_code(422);
        echo json_encode(['erro' => 'Data ou horário inválido.']);
        exit;
    }
    echo json_encode(['mesas' => mesas_para_horario($pdo, $d, $h)]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $mesa = (int)($_POST['mesa_id'] ?? 0);
    $data = $_POST['data_reserva'] ?? '';
    $hora = $_POST['hora_reserva'] ?? '';
    $pessoas = (int)($_POST['pessoas'] ?? 0);
    $obs = trim($_POST['observacoes'] ?? '');
    $telefoneInformado = trim($_POST['telefone'] ?? '');

    if ($telefoneCadastrado === '') {
        if ($telefoneInformado === '' || !preg_match('/^[0-9()\s+\-]{8,30}$/', $telefoneInformado)) {
            $error = 'Informe um telefone válido para realizar a reserva.';
        } else {
            $telefoneCadastrado = $telefoneInformado;
            $stmt = $pdo->prepare('UPDATE clientes SET telefone = ? WHERE id = ?');
            $stmt->execute([$telefoneCadastrado, $cid]);
        }
    }

    $cap = 0;
    $livre = false;
    if (slot_valido((string)$data, (string)$hora)) {
        foreach (mesas_para_horario($pdo, (string)$data, (string)$hora) as $m) {
            if ($m['id'] === $mesa) {
                $cap = $m['capacidade'];
                $livre = !$m['reservada'];
            }
        }
    }

    if (!$error && (!$data || !$hora || !slot_valido((string)$data, (string)$hora))) {
        $error = 'Informe a data e o horário.';
    } elseif (!$error && strtotime($data . ' ' . $hora) < time()) {
        $error = 'Escolha um horário que ainda não passou.';
    } elseif (!$error && !$cap) {
        $error = 'Escolha uma mesa para o horário selecionado.';
    } elseif (!$error && !$livre) {
        $error = 'Essa mesa já está reservada nesse horário. Escolha outra mesa.';
    } elseif (!$error && ($pessoas < 1 || $pessoas > $cap)) {
        $error = 'A mesa escolhida não comporta essa quantidade de pessoas.';
    } elseif (!$error) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservas WHERE mesa_id=? AND data_reserva=? AND hora_reserva=? AND status IN ('pendente','confirmada')");
        $stmt->execute([$mesa, $data, $hora]);

        if ((int)$stmt->fetchColumn() > 0) {
            $error = 'Esse horário já foi solicitado para a mesa escolhida.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO reservas(cliente_id,mesa_id,data_reserva,hora_reserva,pessoas,status,observacoes) VALUES(?,?,?,?,?,'pendente',?)");
            $stmt->execute([$cid, $mesa, $data, $hora, $pessoas, $obs]);
            flash('success', 'Reserva enviada! A administração tem até 30 minutos para confirmar; depois disso a solicitação expira.');
            redirect('minhas-reservas.php');
        }
    }
}

$pageTitle = 'Reservar mesa';
require __DIR__ . '/partials/client-header.php';
?>

<div class="reserva-hero">
    <div class="reserva-hero-text">
        <span class="eyebrow">RESERVA ONLINE</span>
        <h1>Reserve sua mesa</h1>
        <p>Escolha o dia e o horário e veja na hora quais mesas estão livres.</p>
    </div>
    <ol class="reserva-steps" id="reservaSteps">
        <li class="is-active" data-step="1"><span>1</span>Data e horário</li>
        <li data-step="2"><span>2</span>Mesa</li>
        <li data-step="3"><span>3</span>Confirmar</li>
    </ol>
</div>

<?php if ($telefoneCadastrado === ''): ?>
    <div class="profile-required-note">
        <span class="material-symbols-outlined">phone</span>
        <div>
            <strong>Telefone necessário para a reserva</strong>
            <span>Você ainda não possui um telefone cadastrado. Informe-o abaixo ou <a href="<?= url('perfil.php') ?>">adicione pelo seu perfil</a>.</span>
        </div>
    </div>
<?php endif; ?>

<section class="card form-card">
    <div class="card-body">
        <?php if ($error): ?>
            <div class="login-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="form-grid">
                <?php if ($telefoneCadastrado === ''): ?>
                    <div class="form-group full">
                        <label>Telefone para contato *</label>
                        <input class="form-control" type="tel" name="telefone" placeholder="(12) 99999-9999" autocomplete="tel" required value="<?= e($_POST['telefone'] ?? '') ?>">
                        <small class="field-help">Esse número será salvo no seu cadastro para as próximas reservas.</small>
                    </div>
                <?php else: ?>
                    <div class="form-group full">
                        <label>Telefone para contato</label>
                        <div class="saved-contact">
                            <span class="material-symbols-outlined">phone</span>
                            <span><?= e($telefoneCadastrado) ?></span>
                            <a href="<?= url('perfil.php') ?>">Alterar</a>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Data *</label>
                    <input class="form-control" type="date" name="data_reserva" min="<?= date('Y-m-d') ?>" value="<?= e($_POST['data_reserva'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="form-group">
                    <label>Horário *</label>
                    <input class="form-control" type="time" name="hora_reserva" value="<?= e($_POST['hora_reserva'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Pessoas *</label>
                    <input class="form-control" type="number" name="pessoas" min="1" max="50" value="<?= e($_POST['pessoas'] ?? '2') ?>" required>
                </div>
                <div class="form-group full">
                    <label>Mesa *</label>
                    <div class="mesa-picker" id="mesaPicker" data-selected="<?= (int)($_POST['mesa_id'] ?? 0) ?>">
                        <div class="mesa-picker-hint" id="mesaHint">
                            <span class="material-symbols-outlined">schedule</span>
                            Escolha a data e o horário para ver quais mesas estão livres.
                        </div>
                        <div class="mesa-summary" id="mesaSummary" hidden></div>
                        <div class="mesa-options" id="mesaOptions"></div>
                    </div>
                </div>
                <div class="form-group full">
                    <label>Observações</label>
                    <textarea class="form-control" name="observacoes" placeholder="Ex.: aniversário, necessidade de acessibilidade, preferência..."><?= e($_POST['observacoes'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="form-actions">
                <a class="btn btn-light" href="<?= url('cliente.php') ?>">Voltar</a>
                <button class="btn btn-primary" type="submit" id="submitReserva" disabled>Solicitar reserva <span class="material-symbols-outlined">arrow_forward</span></button>
            </div>
        </form>
    </div>
</section>

<script>
(function () {
    const data = document.querySelector('[name="data_reserva"]');
    const hora = document.querySelector('[name="hora_reserva"]');
    const pessoas = document.querySelector('[name="pessoas"]');
    const picker = document.getElementById('mesaPicker');
    const options = document.getElementById('mesaOptions');
    const hint = document.getElementById('mesaHint');
    const summary = document.getElementById('mesaSummary');
    const submit = document.getElementById('submitReserva');
    const steps = document.querySelectorAll('#reservaSteps li');
    let selected = parseInt(picker.dataset.selected, 10) || 0;
    let mesas = [];
    let req = 0;

    function setStep(n) {
        steps.forEach(li => {
            const s = parseInt(li.dataset.step, 10);
            li.classList.toggle('is-active', s === n);
            li.classList.toggle('is-done', s < n);
        });
    }

    function showHint(text, icon) {
        options.innerHTML = '';
        summary.hidden = true;
        hint.hidden = false;
        hint.innerHTML = '<span class="material-symbols-outlined"></span>';
        hint.querySelector('span').textContent = icon || 'schedule';
        hint.appendChild(document.createTextNode(text));
        submit.disabled = true;
        setStep(1);
    }

    function render() {
        const qtd = parseInt(pessoas.value, 10) || 1;
        options.innerHTML = '';
        hint.hidden = true;

        let livres = 0;
        mesas.forEach(m => {
            const pequena = m.capacidade < qtd;
            const indisponivel = m.reservada || pequena;
            if (!indisponivel) livres++;

            const label = document.createElement('label');
            label.className = 'mesa-option ' + (m.reservada ? 'is-reservada' : (pequena ? 'is-pequena' : 'is-livre'));

            const input = document.createElement('input');
            input.type = 'radio';
            input.name = 'mesa_id';
            input.value = m.id;
            input.required = true;
            input.disabled = indisponivel;
            input.checked = !indisponivel && m.id === selected;
            input.addEventListener('change', () => {
                selected = m.id;
                submit.disabled = false;
                setStep(3);
            });

            const num = document.createElement('span');
            num.className = 'mesa-num';
            num.textContent = 'Mesa ' + m.numero;
            const cap = document.createElement('span');
            cap.className = 'mesa-cap';
            cap.textContent = 'até ' + m.capacidade + (m.capacidade === 1 ? ' pessoa' : ' pessoas');
            const state = document.createElement('span');
            state.className = 'mesa-state';
            state.textContent = m.reservada ? 'Reservada neste horário' : (pequena ? 'Não comporta ' + qtd : 'Livre');

            label.append(input, num, cap, state);
            options.appendChild(label);
        });

        const [y, mo, d] = data.value.split('-');
        summary.hidden = false;
        summary.textContent = livres + ' de ' + mesas.length + (mesas.length === 1 ? ' mesa' : ' mesas') +
            ' ' + (livres === 1 ? 'livre' : 'livres') + ' em ' + d + '/' + mo + '/' + y + ' às ' + hora.value + '.';
        summary.classList.toggle('is-empty', livres === 0);

        const checked = options.querySelector('input:checked');
        submit.disabled = !checked;
        if (!checked) selected = 0;
        setStep(checked ? 3 : 2);
    }

    function load() {
        if (!data.value || !hora.value) {
            showHint('Escolha a data e o horário para ver quais mesas estão livres.');
            return;
        }
        const id = ++req;
        showHint('Verificando as mesas...', 'progress_activity');
        fetch('<?= url('reservar.php') ?>?ajax=disponibilidade&data=' + encodeURIComponent(data.value) +
              '&hora=' + encodeURIComponent(hora.value), { headers: { 'Accept': 'application/json' } })
            .then(r => r.ok ? r.json() : Promise.reject())
            .then(json => { if (id === req) { mesas = json.mesas || []; render(); } })
            .catch(() => { if (id === req) showHint('Não foi possível verificar as mesas. Tente novamente.', 'error'); });
    }

    data.addEventListener('change', load);
    hora.addEventListener('change', load);
    pessoas.addEventListener('input', () => { if (mesas.length && hora.value) render(); });

    if (data.value && hora.value) load(); else showHint('Escolha a data e o horário para ver quais mesas estão livres.');
})();
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
