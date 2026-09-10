<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_admin();
if($_SERVER['REQUEST_METHOD']!=='POST')redirect('clientes/index.php');verify_csrf();
$id=(int)($_POST['id']??0);$nome=trim($_POST['nome']??'');$telefone=trim($_POST['telefone']??'');$email=trim($_POST['email']??'');
if(!$nome){flash('error','Informe o nome do cliente.');redirect('clientes/form.php'.($id?'?id='.$id:''));}
try{
 if($id){$s=$pdo->prepare("UPDATE clientes SET nome=?,telefone=?,email=? WHERE id=?");$s->execute([$nome,$telefone,$email,$id]);flash('success','Cliente atualizado com sucesso.');}
 else{$s=$pdo->prepare("INSERT INTO clientes(nome,telefone,email) VALUES(?,?,?)");$s->execute([$nome,$telefone,$email]);flash('success','Cliente cadastrado com sucesso.');}
}catch(PDOException $e){flash('error','Não foi possível salvar o cliente.');}
redirect('clientes/index.php');
