<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . ($path ? '/' . ltrim($path, '/') : '');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function money(float|int|string $value): string
{
    return 'R$ ' . number_format((float)$value, 2, ',', '.');
}

function status_label(string $status): string
{
    return match ($status) {
        'disponivel' => 'Disponível',
        'reservada' => 'Reservada',
        'ocupada' => 'Ocupada',
        'pendente' => 'Pendente',
        'confirmada' => 'Confirmada',
        'cancelada' => 'Cancelada',
        'concluida' => 'Concluída',
        default => ucfirst($status),
    };
}

function status_class(string $status): string
{
    return match ($status) {
        'disponivel' => 'status-disponivel',
        'reservada', 'confirmada' => 'status-reservada',
        'ocupada' => 'status-ocupada',
        'pendente' => 'status-pendente',
        'cancelada' => 'status-cancelada',
        'concluida' => 'status-concluida',
        default => 'status-default',
    };
}

function update_table_status_from_reservation(PDO $pdo, int $mesaId): void
{
    $stmt = $pdo->prepare(
        "SELECT status
         FROM reservas
         WHERE mesa_id = ?
           AND status = 'confirmada'
           AND data_reserva >= CURDATE()
         ORDER BY data_reserva ASC, hora_reserva ASC
         LIMIT 1"
    );
    $stmt->execute([$mesaId]);
    $reservation = $stmt->fetch();

    if ($reservation) {
        $pdo->prepare("UPDATE mesas SET status = 'reservada' WHERE id = ? AND status <> 'ocupada'")->execute([$mesaId]);
    } else {
        $pdo->prepare("UPDATE mesas SET status = 'disponivel' WHERE id = ? AND status = 'reservada'")->execute([$mesaId]);
    }
}


function verification_token(): string { return bin2hex(random_bytes(32)); }
function verification_hash(string $token): string { return hash('sha256', $token); }
function send_verification_email(string $nome, string $email, string $token): bool {
    $link = url('verificar-email.php?token=' . urlencode($token));
    $subject = 'Confirme seu e-mail — MesaReserva';
    $message = "Olá, {$nome}!\n\nSua conta no MesaReserva foi criada com sucesso. Para ativá-la, confirme seu e-mail acessando:\n\n{$link}\n\nEste link é válido por 24 horas. Se você não criou esta conta, ignore esta mensagem.\n\nMesaReserva\nSua mesa, seu horário, sua reserva.";
    $headers = "MIME-Version: 1.0\r\n" . "Content-Type: text/plain; charset=UTF-8\r\n" . "From: MesaReserva <no-reply@mesareserva.local>\r\n";
    return @mail($email, '=?UTF-8?B?'.base64_encode($subject).'?=', $message, $headers);
}
function available_tables_count(PDO $pdo): int { return (int)$pdo->query("SELECT COUNT(*) FROM mesas WHERE status='disponivel'")->fetchColumn(); }
