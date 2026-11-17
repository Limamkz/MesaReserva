<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_login();

if (is_admin()) {
    redirect('dashboard.php');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('minhas-reservas.php');
}
verify_csrf();

expirar_reservas($pdo);

$cid = current_client_id();
$id = (int)($_POST['id'] ?? 0);

try {
    // Só a própria cliente pode cancelar, e apenas reservas ainda em aberto.
    $stmt = $pdo->prepare('SELECT id, mesa_id, data_reserva, hora_reserva, status FROM reservas WHERE id = ? AND cliente_id = ? LIMIT 1');
    $stmt->execute([$id, $cid]);
    $reserva = $stmt->fetch();

    if (!$reserva) {
        flash('error', 'Reserva não encontrada.');
    } elseif (!in_array($reserva['status'], ['pendente', 'confirmada'], true)) {
        flash('error', 'Esta reserva não pode mais ser cancelada.');
    } else {
        $pdo->prepare("UPDATE reservas SET status = 'cancelada', cancelada_por = 'cliente', cancelada_em = NOW() WHERE id = ? AND cliente_id = ?")->execute([$id, $cid]);
        update_table_status_from_reservation($pdo, (int)$reserva['mesa_id']);
        flash('success', 'Reserva cancelada. A mesa foi liberada para outros clientes.');
    }
} catch (PDOException $e) {
    flash('error', 'Não foi possível cancelar a reserva. Tente novamente.');
}

redirect('minhas-reservas.php');
