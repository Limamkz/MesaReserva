<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$id=(int)($_GET['id']??0);
if($id){
 $s=$pdo->prepare("SELECT mesa_id FROM reservas WHERE id=?");$s->execute([$id]);$mesa=(int)$s->fetchColumn();
 $s=$pdo->prepare("DELETE FROM reservas WHERE id=?");$s->execute([$id]);
 if($mesa) update_table_status_from_reservation($pdo,$mesa);
 flash('success','Reserva excluída com sucesso.');
}
redirect('reservas/index.php');
