<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$id=(int)($_GET['id']??0);$cliente=['nome'=>'','telefone'=>'','email'=>''];
if($id){$s=$pdo->prepare("SELECT * FROM clientes WHERE id=?");$s->execute([$id]);$cliente=$s->fetch();if(!$cliente){flash('error','Cliente não encontrado.');redirect('clientes/index.php');}}
$pageTitle=$id?'Editar cliente':'Novo cliente';
require __DIR__.'/../partials/header.php';
?>
<div class="page-heading">
    <div>
        <h2>
            <?= e($pageTitle) ?>
        </h2>
        <p>Preencha os dados de contato do cliente.</p>
    </div>
</div>
<section class="card form-card">
    <div class="card-body">
        <form method="post" action="<?= url('clientes/save.php') ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="form-grid">
                <div class="form-group full">
                    <label>Nome completo *</label>
                    <input class="form-control" name="nome" value="<?= e($cliente['nome']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Telefone</label>
                    <input class="form-control" name="telefone" value="<?= e($cliente['telefone']) ?>" placeholder="(11) 99999-9999">
                </div>
                <div class="form-group">
                    <label>E-mail</label>
                    <input class="form-control" type="email" name="email" value="<?= e($cliente['email']) ?>" placeholder="cliente@email.com">
                </div>
            </div>
            <div class="form-actions">
                <a class="btn btn-light" href="<?= url('clientes/index.php') ?>">Cancelar</a>
                <button class="btn btn-primary">
                    <span class="material-symbols-outlined">save</span>Salvar cliente</button>
                    </div>
                </form>
            </div>
        </section>
        <?php require __DIR__.'/../partials/footer.php'; ?>
