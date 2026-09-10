<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$id=(int)($_GET['id']??0);
if($id){try{$s=$pdo->prepare("DELETE FROM clientes WHERE id=?");$s->execute([$id]);flash('success','Cliente excluído com sucesso.');}catch(PDOException $e){flash('error','Não é possível excluir cliente com reservas vinculadas.');}}
redirect('clientes/index.php');
