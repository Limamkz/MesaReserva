<?php
declare(strict_types=1);

/*
 * Carga dados demonstrativos realistas do MesaReserva.
 * Execute uma única vez em: http://localhost/MesaReserva/database/popular-banco.php
 * Contas de demonstração: senha Mesa@2026 (troque antes de publicar o sistema).
 */
require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('America/Sao_Paulo');

$senha = '$2y$12$hN9VNCwEgjK4taiAKX.RjuqEoN/D9iLKNOFMio0P3.4a3LXzNduNm';
$admins = [
    ['Mariana Costa', 'mariana.costa@mesareserva.test', '12913994838'],
    ['Rafael Almeida', 'rafael.almeida@mesareserva.test', '12945226626'],
    ['Camila Oliveira', 'camila.oliveira@mesareserva.test', '11945476647'],
];
$clientes = [
    ['Ana Beatriz Souza','ana.souza@example.com','11919951177'],
    ['Lucas Ferreira','lucas.ferreira@example.com','19967935805'],
    ['Mariana Ribeiro','mariana.ribeiro@example.com','11988902939'],
    ['Gabriel Martins','gabriel.martins@example.com','19975378716'],
    ['Isabela Santos','isabela.santos@example.com','12931065943'],
    ['Pedro Henrique Lima','pedro.lima@example.com','19998169879'],
    ['Julia Carvalho','julia.carvalho@example.com','12948533709'],
    ['Matheus Gomes','matheus.gomes@example.com','19941789756'],
    ['Beatriz Rodrigues','beatriz.rodrigues@example.com','12930642153'],
    ['Felipe Nascimento','felipe.nascimento@example.com','19922652433'],
    ['Larissa Araujo','larissa.araujo@example.com','12931759306'],
    ['Bruno Barbosa','bruno.barbosa@example.com','12929735844'],
    ['Camila Mendes','camila.mendes@example.com','19937055959'],
    ['Thiago Cardoso','thiago.cardoso@example.com','11922332193'],
    ['Manuela Teixeira','manuela.teixeira@example.com','19937226735'],
    ['João Pedro Rocha','joao.rocha@example.com','19966713322'],
    ['Leticia Dias','leticia.dias@example.com','11989737272'],
    ['Enzo Moreira','enzo.moreira@example.com','12937337448'],
    ['Amanda Lopes','amanda.lopes@example.com','19993946019'],
    ['Vitor Hugo Freitas','vitor.freitas@example.com','11943576608'],
    ['Sofia Monteiro','sofia.monteiro@example.com','19912387291'],
    ['Daniel Mendes','daniel.mendes@example.com','11940808139'],
    ['Eduarda Correia','eduarda.correia@example.com','11979418365'],
    ['Leonardo Batista','leonardo.batista@example.com','19910484837'],
    ['Helena Campos','helena.campos@example.com','11952383932'],
    ['Caio Fernandes','caio.fernandes@example.com','19993193816'],
    ['Giovanna Melo','giovanna.melo@example.com','19929869322'],
    ['André Luiz Pinto','andre.pinto@example.com','11939395753'],
    ['Clara Barbosa','clara.barbosa@example.com','12964331856'],
    ['Samuel Costa','samuel.costa@example.com','12961949010'],
];
$mesas = [
    [1,2,'Mesa para casal, próxima à janela'],[2,2,'Ambiente reservado para duas pessoas'],
    [3,4,'Próxima ao salão principal'],[4,4,'Ideal para famílias pequenas'],
    [5,4,'Ao lado do jardim interno'],[6,4,'Ambiente tranquilo'],
    [7,6,'Mesa ampla para grupos'],[8,6,'Próxima ao bar'],
    [9,6,'Boa opção para confraternizações'],[10,8,'Mesa para grupos maiores'],
    [11,8,'Espaço para eventos pequenos'],[12,10,'Mesa maior, ideal para famílias'],
];
$pdo->exec("CREATE TABLE IF NOT EXISTS mesareserva_demo_seed (id TINYINT UNSIGNED PRIMARY KEY, executado_em DATETIME NOT NULL) ENGINE=InnoDB");
if ($pdo->query("SELECT COUNT(*) FROM mesareserva_demo_seed WHERE id=1")->fetchColumn()) {
    exit("Os dados demonstrativos já foram inseridos anteriormente. Nenhuma alteração foi feita.\n");
}
$pdo->beginTransaction();
try {
    $adminInsert = $pdo->prepare("INSERT INTO administradores (nome,email,senha,ativo,email_verificado_em,created_at) VALUES (?,?,?,1,NOW(),NOW()) ON DUPLICATE KEY UPDATE nome=VALUES(nome), ativo=1, email_verificado_em=COALESCE(email_verificado_em,NOW())");
    foreach ($admins as [$nome,$email,$telefone]) $adminInsert->execute([$nome,$email,$senha]);

    $clientInsert = $pdo->prepare("INSERT INTO clientes (nome,telefone,email,senha,ativo,email_verificado_em,created_at) VALUES (?,?,?,?,1,NOW(),NOW()) ON DUPLICATE KEY UPDATE nome=VALUES(nome), telefone=VALUES(telefone), senha=VALUES(senha), ativo=1, email_verificado_em=COALESCE(email_verificado_em,NOW())");
    foreach ($clientes as [$nome,$email,$telefone]) $clientInsert->execute([$nome,$telefone,$email,$senha]);

    $adminIds = $pdo->query("SELECT id FROM administradores WHERE email IN ('mariana.costa@mesareserva.test','rafael.almeida@mesareserva.test','camila.oliveira@mesareserva.test')")->fetchAll(PDO::FETCH_COLUMN);
    $clientIds = [];
    $getClient = $pdo->prepare("SELECT id FROM clientes WHERE email=?");
    foreach ($clientes as $c) { $getClient->execute([$c[1]]); $clientIds[]=(int)$getClient->fetchColumn(); }

    $tableInsert=$pdo->prepare("INSERT INTO mesas(numero,capacidade,status,observacoes) VALUES(?,?, 'disponivel',?) ON DUPLICATE KEY UPDATE capacidade=VALUES(capacidade),observacoes=VALUES(observacoes)");
    foreach($mesas as [$numero,$capacidade,$obs]) $tableInsert->execute([$numero,$capacidade,$obs]);
    $tableIds=$pdo->query("SELECT numero,id FROM mesas WHERE numero BETWEEN 1 AND 12 ORDER BY numero")->fetchAll(PDO::FETCH_KEY_PAIR);
    $tableIds=array_values($tableIds);

    // Reservas históricas, próximas e futuras, todas ligadas a clientes e mesas reais.
    $reservationInsert=$pdo->prepare("INSERT INTO reservas(cliente_id,mesa_id,data_reserva,hora_reserva,pessoas,status,observacoes,created_at) VALUES(?,?,?,?,?,?,?,?)");
    $today=new DateTimeImmutable('today');
    for($i=0;$i<105;$i++){
        $offset = $i < 75 ? -random_int(1,330) : random_int(0,35);
        $day=$today->modify(($offset>=0?'+':'').$offset.' days');
        $hour=random_int(18,21); $minute=[0,15,30,45][random_int(0,3)];
        $status=$offset<0 ? (['concluida','concluida','concluida','cancelada'][random_int(0,3)]) : (['pendente','confirmada','confirmada'][random_int(0,2)]);
        $client=$clientIds[$i%count($clientIds)];
        $table=$tableIds[$i%count($tableIds)];
        $created=$day->modify('-'.random_int(1,20).' days')->format('Y-m-d H:i:s');
        $reservationInsert->execute([$client,$table,$day->format('Y-m-d'),sprintf('%02d:%02d:00',$hour,$minute),random_int(1, min(8,4+($i%5))),$status,'Reserva demonstrativa para '.$hour.'h'.$minute,$created]);
    }

    // Consumos distribuídos pelos últimos dias, semanas, meses e cinco anos.
    // Cada mesa recebe lançamentos em todos os recortes do gráfico.
    $itens=[
        ['Risoto de parmesão',48.90],['Filé ao molho madeira',69.90],['Salmão grelhado',72.00],
        ['Lasanha da casa',46.50],['Hambúrguer artesanal',39.90],['Água mineral',6.00],
        ['Suco natural',12.00],['Refrigerante',8.50],['Brownie com sorvete',19.90],
        ['Entrada de bruschettas',28.00],['Pizza margherita',54.90],['Café espresso',7.00],
    ];
    $consumoInsert=$pdo->prepare("INSERT INTO consumos(mesa_id,descricao,quantidade,valor_unitario,created_at) VALUES(?,?,?,?,?)");
    $dates=[];
    for($d=0;$d<7;$d++) $dates[]=$today->modify("-$d days")->setTime(random_int(11,21),random_int(0,59));
    for($w=1;$w<=8;$w++) $dates[]=$today->modify("-".($w*7+random_int(0,5))." days")->setTime(random_int(11,21),random_int(0,59));
    for($m=1;$m<=12;$m++) $dates[]=$today->modify("-".($m*30+random_int(0,20))." days")->setTime(random_int(11,21),random_int(0,59));
    for($y=1;$y<=5;$y++) for($j=0;$j<3;$j++) $dates[]=$today->modify("-".($y*365-random_int(0,100))." days")->setTime(random_int(11,21),random_int(0,59));
    foreach($tableIds as $ti=>$tableId){
        foreach($dates as $di=>$date){
            $item=$itens[($ti+$di)%count($itens)];
            $qty=random_int(1,3);
            $consumoInsert->execute([$tableId,$item[0],$qty,$item[1],$date->format('Y-m-d H:i:s')]);
        }
        // Mais de um lançamento recente por mesa para reforçar o movimento diário.
        for($k=0;$k<4;$k++){
            $item=$itens[($ti*2+$k)%count($itens)];
            $consumoInsert->execute([$tableId,$item[0],random_int(1,3),$item[1],$today->setTime(random_int(11,21),random_int(0,59))->format('Y-m-d H:i:s')]);
        }
    }

    $questions=[
        ['Dúvida sobre reservas','Com quanto tempo de antecedência posso reservar uma mesa?','Recomendamos reservar com antecedência, principalmente aos finais de semana.'],
        ['Alteração de reserva','Consigo alterar o horário depois de solicitar a reserva?','Sim. Entre em contato com a equipe para verificarmos a disponibilidade.'],
        ['Comemorações','Vocês possuem alguma opção para comemorar aniversário?','Podemos organizar detalhes especiais mediante solicitação prévia.'],
        ['Acessibilidade','O restaurante possui acesso para pessoas com mobilidade reduzida?','Sim, temos acesso facilitado e nossa equipe pode auxiliar na chegada.'],
        ['Cardápio','Há opções vegetarianas no cardápio?','Sim, contamos com opções vegetarianas. Consulte a equipe sobre os pratos do dia.'],
        ['Crianças','Crianças são contabilizadas no número de pessoas da reserva?','Sim, informe todos os participantes para prepararmos a mesa adequadamente.'],
        ['Atraso','Por quanto tempo minha mesa fica aguardando em caso de atraso?','Entre em contato se houver imprevisto para que a equipe possa orientar.'],
        ['Estacionamento','O restaurante possui estacionamento ou convênio?','Nossa equipe informa as opções de estacionamento disponíveis nas proximidades.'],
        ['Cancelamento','Como faço para cancelar uma reserva?','Você pode solicitar o cancelamento pela área Minhas reservas.'],
        ['Pagamento','Quais formas de pagamento são aceitas?','Aceitamos cartões, Pix e dinheiro.'],
        ['Alérgenos','É possível informar alergias alimentares antes da visita?','Sim. Inclua a informação nas observações da reserva e avise nossa equipe.'],
        ['Ambiente','É possível escolher uma mesa próxima à janela?','Você pode registrar sua preferência nas observações; a confirmação depende da disponibilidade.'],
        ['Pet friendly','Posso levar meu animal de estimação?','Consulte a equipe antes da visita para confirmar as condições de atendimento.'],
        ['Grupos','Como faço uma reserva para um grupo grande?','Envie a quantidade de pessoas e a data desejada para avaliarmos a melhor acomodação.'],
        ['Comprovante','Receberei uma confirmação da minha reserva?','Sim, a situação da solicitação pode ser acompanhada na sua conta.'],
    ];
    $qInsert=$pdo->prepare("INSERT INTO perguntas(cliente_id,assunto,mensagem,resposta,status,created_at,respondida_em) VALUES(?,?,?,?,?,?,?)");
    foreach($questions as $i=>$q){
        $date=$today->modify('-'.random_int(0,160).' days')->setTime(random_int(9,20),random_int(0,59));
        $answered=($i%4!==0);
        $qInsert->execute([$clientIds[$i%count($clientIds)],$q[0],$q[1],$answered?$q[2]:null,$answered?'respondida':'aberta',$date->format('Y-m-d H:i:s'),$answered?$date->modify('+'.random_int(1,8).' hours')->format('Y-m-d H:i:s'):null]);
    }

    $reviews=[
        'Ambiente muito agradável e atendimento atencioso. Voltaremos com certeza!',
        'Comida excelente, pratos bem servidos e tudo chegou quentinho.',
        'Uma ótima experiência do começo ao fim. Recomendo!',
        'Fomos muito bem recebidos e a reserva estava organizada.',
        'Cardápio variado, sobremesa deliciosa e equipe muito simpática.',
        'Lugar aconchegante para reunir a família. Gostamos bastante.',
        'Atendimento rápido e comida de ótima qualidade.',
        'Tudo muito bem preparado. Foi uma noite especial.',
        'Excelente custo-benefício e ambiente muito bonito.',
        'A equipe foi prestativa e cuidou de todos os detalhes.',
        'Pratos saborosos e apresentação caprichada. Nota máxima!',
        'Experiência muito boa, com certeza pretendemos voltar.',
        'Reserva feita sem complicação e atendimento impecável.',
        'Gostamos muito do ambiente e da cordialidade dos funcionários.',
        'Comida deliciosa e ótimo atendimento. Superou as expectativas.',
        'Tudo organizado e a equipe foi muito atenciosa.',
        'Uma das melhores experiências que tivemos recentemente.',
        'Ótima opção para jantar com amigos. Recomendamos!',
        'Ambiente tranquilo, pratos excelentes e serviço cuidadoso.',
        'Foi tudo ótimo, desde a chegada até a sobremesa.',
        'Atendimento acolhedor e comida feita com muito capricho.',
        'Gostei bastante da experiência e voltaria tranquilamente.',
        'Excelente recepção e pratos muito saborosos.',
        'Tudo perfeito para nosso almoço em família.',
    ];
    $reviewInsert=$pdo->prepare("INSERT INTO avaliacoes(cliente_id,nota,comentario,aprovado,created_at) VALUES(?,?,?,1,?)");
    foreach($reviews as $i=>$comment){
        $date=$today->modify('-'.random_int(0,180).' days')->setTime(random_int(12,22),random_int(0,59));
        $reviewInsert->execute([$clientIds[($i*3)%count($clientIds)],random_int(4,5),$comment,$date->format('Y-m-d H:i:s')]);
    }

    $pdo->prepare("INSERT INTO mesareserva_demo_seed(id,executado_em) VALUES(1,NOW())")->execute();
    $pdo->commit();
    echo "Banco populado com sucesso!\n";
    echo "Administradores adicionados: 3\nClientes adicionados/atualizados: ".count($clientes)."\nMesas garantidas: 12\nReservas criadas: 105\nLançamentos de consumo criados: ".(count($dates)+4)*count($tableIds)."\nPerguntas de atendimento: ".count($questions)."\nAvaliações: ".count($reviews)."\n";
    echo "Senha padrão de todas as contas de demonstração: Mesa@2026\n";
    echo "E-mails dos administradores: mariana.costa@mesareserva.test, rafael.almeida@mesareserva.test, camila.oliveira@mesareserva.test\n";
} catch(Throwable $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    throw $e;
}
