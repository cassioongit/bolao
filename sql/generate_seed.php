<?php
/**
 * Gerador do seed da Copa 2026 (seleções + 104 jogos).
 * Rode na linha de comando:  php sql/generate_seed.php > sql/seed.sql
 *
 * Fonte do calendário de grupos: ESPN (horários em ET/EDT = UTC-4 em junho).
 * Convertemos tudo para UTC. O mata-mata entra como placeholders (o admin
 * define as seleções depois em /admin/jogos.php).
 */

// ---- Seleções: [nome, sigla, bandeira, grupo] ----
$teams = [
    ['México', 'MEX', '🇲🇽', 'A'],
    ['África do Sul', 'RSA', '🇿🇦', 'A'],
    ['Coreia do Sul', 'KOR', '🇰🇷', 'A'],
    ['Tchéquia', 'CZE', '🇨🇿', 'A'],
    ['Canadá', 'CAN', '🇨🇦', 'B'],
    ['Bósnia e Herzegovina', 'BIH', '🇧🇦', 'B'],
    ['Catar', 'QAT', '🇶🇦', 'B'],
    ['Suíça', 'SUI', '🇨🇭', 'B'],
    ['Brasil', 'BRA', '🇧🇷', 'C'],
    ['Marrocos', 'MAR', '🇲🇦', 'C'],
    ['Haiti', 'HAI', '🇭🇹', 'C'],
    ['Escócia', 'SCO', '🏴󠁧󠁢󠁳󠁣󠁴󠁿', 'C'],
    ['Estados Unidos', 'USA', '🇺🇸', 'D'],
    ['Paraguai', 'PAR', '🇵🇾', 'D'],
    ['Austrália', 'AUS', '🇦🇺', 'D'],
    ['Turquia', 'TUR', '🇹🇷', 'D'],
    ['Alemanha', 'GER', '🇩🇪', 'E'],
    ['Curaçao', 'CUW', '🇨🇼', 'E'],
    ['Costa do Marfim', 'CIV', '🇨🇮', 'E'],
    ['Equador', 'ECU', '🇪🇨', 'E'],
    ['Países Baixos', 'NED', '🇳🇱', 'F'],
    ['Japão', 'JPN', '🇯🇵', 'F'],
    ['Suécia', 'SWE', '🇸🇪', 'F'],
    ['Tunísia', 'TUN', '🇹🇳', 'F'],
    ['Bélgica', 'BEL', '🇧🇪', 'G'],
    ['Egito', 'EGY', '🇪🇬', 'G'],
    ['Irã', 'IRN', '🇮🇷', 'G'],
    ['Nova Zelândia', 'NZL', '🇳🇿', 'G'],
    ['Espanha', 'ESP', '🇪🇸', 'H'],
    ['Cabo Verde', 'CPV', '🇨🇻', 'H'],
    ['Arábia Saudita', 'KSA', '🇸🇦', 'H'],
    ['Uruguai', 'URU', '🇺🇾', 'H'],
    ['França', 'FRA', '🇫🇷', 'I'],
    ['Senegal', 'SEN', '🇸🇳', 'I'],
    ['Iraque', 'IRQ', '🇮🇶', 'I'],
    ['Noruega', 'NOR', '🇳🇴', 'I'],
    ['Argentina', 'ARG', '🇦🇷', 'J'],
    ['Argélia', 'ALG', '🇩🇿', 'J'],
    ['Áustria', 'AUT', '🇦🇹', 'J'],
    ['Jordânia', 'JOR', '🇯🇴', 'J'],
    ['Portugal', 'POR', '🇵🇹', 'K'],
    ['Rep. Dem. do Congo', 'COD', '🇨🇩', 'K'],
    ['Uzbequistão', 'UZB', '🇺🇿', 'K'],
    ['Colômbia', 'COL', '🇨🇴', 'K'],
    ['Inglaterra', 'ENG', '🏴󠁧󠁢󠁥󠁮󠁧󠁿', 'L'],
    ['Croácia', 'CRO', '🇭🇷', 'L'],
    ['Gana', 'GHA', '🇬🇭', 'L'],
    ['Panamá', 'PAN', '🇵🇦', 'L'],
];

// ---- Jogos de grupos: [num, 'Jun D', 'h:mm AP', casa, visitante, grupo, sede] ----
// Times em ET (EDT, UTC-4). Nomes batem com a coluna sigla das seleções? Não:
// aqui usamos o NOME EM PT para casar via mapa abaixo.
$ptByEn = [
    'Mexico'=>'México','South Africa'=>'África do Sul','South Korea'=>'Coreia do Sul','Czechia'=>'Tchéquia',
    'Canada'=>'Canadá','Bosnia and Herzegovina'=>'Bósnia e Herzegovina','Qatar'=>'Catar','Switzerland'=>'Suíça',
    'Brazil'=>'Brasil','Morocco'=>'Marrocos','Haiti'=>'Haiti','Scotland'=>'Escócia',
    'United States'=>'Estados Unidos','Paraguay'=>'Paraguai','Australia'=>'Austrália','Türkiye'=>'Turquia',
    'Germany'=>'Alemanha','Curaçao'=>'Curaçao','Ivory Coast'=>'Costa do Marfim','Ecuador'=>'Equador',
    'Netherlands'=>'Países Baixos','Japan'=>'Japão','Sweden'=>'Suécia','Tunisia'=>'Tunísia',
    'Belgium'=>'Bélgica','Egypt'=>'Egito','Iran'=>'Irã','New Zealand'=>'Nova Zelândia',
    'Spain'=>'Espanha','Cape Verde'=>'Cabo Verde','Saudi Arabia'=>'Arábia Saudita','Uruguay'=>'Uruguai',
    'France'=>'França','Senegal'=>'Senegal','Iraq'=>'Iraque','Norway'=>'Noruega',
    'Argentina'=>'Argentina','Algeria'=>'Argélia','Austria'=>'Áustria','Jordan'=>'Jordânia',
    'Portugal'=>'Portugal','DR Congo'=>'Rep. Dem. do Congo','Uzbekistan'=>'Uzbequistão','Colombia'=>'Colômbia',
    'England'=>'Inglaterra','Croatia'=>'Croácia','Ghana'=>'Gana','Panama'=>'Panamá',
];

$g = [
    [1,'Jun 11','1:00 PM','Mexico','South Africa','A','Cidade do México'],
    [2,'Jun 11','10:00 PM','South Korea','Czechia','A','Zapopan'],
    [3,'Jun 12','3:00 PM','Canada','Bosnia and Herzegovina','B','Toronto'],
    [4,'Jun 12','9:00 PM','United States','Paraguay','D','Inglewood'],
    [5,'Jun 13','3:00 PM','Qatar','Switzerland','B','Santa Clara'],
    [6,'Jun 13','6:00 PM','Brazil','Morocco','C','East Rutherford'],
    [7,'Jun 13','9:00 PM','Haiti','Scotland','C','Foxborough'],
    [8,'Jun 14','12:00 AM','Australia','Türkiye','D','Vancouver'],
    [9,'Jun 14','1:00 PM','Germany','Curaçao','E','Houston'],
    [10,'Jun 14','4:00 PM','Netherlands','Japan','F','Arlington'],
    [11,'Jun 14','7:00 PM','Ivory Coast','Ecuador','E','Filadélfia'],
    [12,'Jun 14','10:00 PM','Sweden','Tunisia','F','Guadalupe'],
    [13,'Jun 15','1:00 PM','Spain','Cape Verde','H','Atlanta'],
    [14,'Jun 15','6:00 PM','Belgium','Egypt','G','Seattle'],
    [15,'Jun 15','6:00 PM','Saudi Arabia','Uruguay','H','Miami Gardens'],
    [16,'Jun 16','12:00 AM','Iran','New Zealand','G','Inglewood'],
    [17,'Jun 16','3:00 PM','France','Senegal','I','East Rutherford'],
    [18,'Jun 16','6:00 PM','Iraq','Norway','I','Foxborough'],
    [19,'Jun 16','9:00 PM','Argentina','Algeria','J','Kansas City'],
    [20,'Jun 17','12:00 AM','Austria','Jordan','J','Santa Clara'],
    [21,'Jun 17','1:00 PM','Portugal','DR Congo','K','Houston'],
    [22,'Jun 17','4:00 PM','England','Croatia','L','Arlington'],
    [23,'Jun 17','7:00 PM','Ghana','Panama','L','Toronto'],
    [24,'Jun 17','10:00 PM','Uzbekistan','Colombia','K','Cidade do México'],
    [25,'Jun 18','12:00 PM','Czechia','South Africa','A','Atlanta'],
    [26,'Jun 18','3:00 PM','Switzerland','Bosnia and Herzegovina','B','Inglewood'],
    [27,'Jun 18','6:00 PM','Canada','Qatar','B','Vancouver'],
    [28,'Jun 18','11:00 PM','Mexico','South Korea','A','Zapopan'],
    [29,'Jun 19','3:00 PM','United States','Australia','D','Seattle'],
    [30,'Jun 19','6:00 PM','Scotland','Morocco','C','Foxborough'],
    [31,'Jun 19','9:00 PM','Brazil','Haiti','C','Filadélfia'],
    [32,'Jun 20','12:00 AM','Türkiye','Paraguay','D','Santa Clara'],
    [33,'Jun 20','1:00 PM','Netherlands','Sweden','F','Houston'],
    [34,'Jun 20','4:00 PM','Germany','Ivory Coast','E','Toronto'],
    [35,'Jun 20','8:00 PM','Ecuador','Curaçao','E','Kansas City'],
    [36,'Jun 21','12:00 AM','Tunisia','Japan','F','Guadalupe'],
    [37,'Jun 21','12:00 PM','Spain','Saudi Arabia','H','Atlanta'],
    [38,'Jun 21','3:00 PM','Belgium','Iran','G','Inglewood'],
    [39,'Jun 21','6:00 PM','Uruguay','Cape Verde','H','Miami Gardens'],
    [40,'Jun 21','9:00 PM','New Zealand','Egypt','G','Vancouver'],
    [41,'Jun 22','1:00 PM','Argentina','Austria','J','Arlington'],
    [42,'Jun 22','5:00 PM','France','Iraq','I','Filadélfia'],
    [43,'Jun 22','8:00 PM','Norway','Senegal','I','East Rutherford'],
    [44,'Jun 22','11:00 PM','Jordan','Algeria','J','Santa Clara'],
    [45,'Jun 23','1:00 PM','Portugal','Uzbekistan','K','Houston'],
    [46,'Jun 23','4:00 PM','England','Ghana','L','Foxborough'],
    [47,'Jun 23','7:00 PM','Panama','Croatia','L','Toronto'],
    [48,'Jun 23','10:00 PM','Colombia','DR Congo','K','Zapopan'],
    [49,'Jun 24','3:00 PM','Switzerland','Canada','B','Vancouver'],
    [50,'Jun 24','3:00 PM','Bosnia and Herzegovina','Qatar','B','Seattle'],
    [51,'Jun 24','6:00 PM','Scotland','Brazil','C','Miami Gardens'],
    [52,'Jun 24','6:00 PM','Morocco','Haiti','C','Atlanta'],
    [53,'Jun 24','9:00 PM','Czechia','Mexico','A','Cidade do México'],
    [54,'Jun 24','9:00 PM','South Africa','South Korea','A','Guadalupe'],
    [55,'Jun 25','4:00 PM','Ecuador','Germany','E','East Rutherford'],
    [56,'Jun 25','4:00 PM','Curaçao','Ivory Coast','E','Filadélfia'],
    [57,'Jun 25','7:00 PM','Japan','Sweden','F','Arlington'],
    [58,'Jun 25','7:00 PM','Tunisia','Netherlands','F','Kansas City'],
    [59,'Jun 25','10:00 PM','Türkiye','United States','D','Inglewood'],
    [60,'Jun 25','10:00 PM','Paraguay','Australia','D','Santa Clara'],
    [61,'Jun 26','3:00 PM','Norway','France','I','Foxborough'],
    [62,'Jun 26','3:00 PM','Senegal','Iraq','I','Toronto'],
    [63,'Jun 26','8:00 PM','Cape Verde','Saudi Arabia','H','Houston'],
    [64,'Jun 26','8:00 PM','Uruguay','Spain','H','Zapopan'],
    [65,'Jun 26','11:00 PM','Egypt','Iran','G','Seattle'],
    [66,'Jun 26','11:00 PM','New Zealand','Belgium','G','Vancouver'],
    [67,'Jun 27','5:00 PM','Panama','England','L','East Rutherford'],
    [68,'Jun 27','5:00 PM','Croatia','Ghana','L','Filadélfia'],
    [69,'Jun 27','7:30 PM','Colombia','Portugal','K','Miami Gardens'],
    [70,'Jun 27','7:30 PM','DR Congo','Uzbekistan','K','Atlanta'],
    [71,'Jun 27','10:00 PM','Algeria','Austria','J','Kansas City'],
    [72,'Jun 27','10:00 PM','Jordan','Argentina','J','Arlington'],
];

// Converte 'Jun 11' + '1:00 PM' (ET/EDT) -> 'YYYY-MM-DD HH:MM:SS' em UTC
function to_utc(string $date, string $time): string {
    $dt = DateTime::createFromFormat('M j Y g:i A', "$date 2026 $time", new DateTimeZone('America/New_York'));
    if (!$dt) { fwrite(STDERR, "Falha ao converter: $date $time\n"); exit(1); }
    $dt->setTimezone(new DateTimeZone('UTC'));
    return $dt->format('Y-m-d H:i:s');
}

function q(string $s): string { return "'" . str_replace("'", "''", $s) . "'"; }

// ---- Mata-mata: [num, fase, 'YYYY-MM-DD HH:MM:SS' UTC, ph_casa, ph_visitante, sede] ----
// Datas aproximadas (UTC). O admin ajusta seleções e horários depois.
$ko = [];
$mk = function($num,$fase,$dia,$hora,$ca,$cv,$sede) { return [$num,$fase,"2026-$dia $hora:00",$ca,$cv,$sede]; };
// R32 (16 jogos): 28/06 a 03/07
$r32 = [
    [73,'06-28','1A','2C'],[74,'06-28','1C','3A/B/F'],[75,'06-29','1F','2A'],[76,'06-29','2B','2F'],
    [77,'06-29','1I','3C/E/H'],[78,'06-30','1E','3A/B/C/D'],[79,'06-30','1A2','2E'],[80,'06-30','2I','2L'],
    [81,'07-01','1L','3E/H/I/J'],[82,'07-01','1D','3B/E/F/I'],[83,'07-02','1G','3A/H/I/J'],[84,'07-02','2D','2G'],
    [85,'07-02','1B','3D/E/I/L'],[86,'07-03','1H','2J'],[87,'07-03','1J','2H'],[88,'07-03','1K','3D/E/I/L'],
];
$i=0; foreach ($r32 as $r){ $h = ['17','20','23'][$i%3]; $ko[] = [$r[0],'r32',"2026-$r[1] $h:00:00",$r[2],$r[3],'A definir']; $i++; }
// R16 (8): 04-07/07
$r16 = [[89,'07-04'],[90,'07-04'],[91,'07-05'],[92,'07-05'],[93,'07-06'],[94,'07-06'],[95,'07-07'],[96,'07-07']];
foreach ($r16 as $k=>$r){ $ko[] = [$r[0],'r16',"2026-$r[1] ".(['19','23'][$k%2]).":00:00",'Venc. '.(73+$k*2),'Venc. '.(74+$k*2),'A definir']; }
// QF (4): 09-11/07
$qf = [[97,'07-09'],[98,'07-09'],[99,'07-10'],[100,'07-11']];
foreach ($qf as $k=>$r){ $ko[] = [$r[0],'qf',"2026-$r[1] 21:00:00",'Venc. '.(89+$k*2),'Venc. '.(90+$k*2),'A definir']; }
// SF (2): 14-15/07
$ko[] = [101,'sf','2026-07-14 22:00:00','Venc. 97','Venc. 98','A definir'];
$ko[] = [102,'sf','2026-07-15 22:00:00','Venc. 99','Venc. 100','A definir'];
// 3º lugar: 18/07
$ko[] = [103,'terceiro','2026-07-18 20:00:00','Perd. 101','Perd. 102','A definir'];
// Final: 19/07
$ko[] = [104,'final','2026-07-19 19:00:00','Venc. 101','Venc. 102','East Rutherford'];

// =================== SAÍDA SQL ===================
$out = "-- Seed gerado por generate_seed.php — Copa 2026\nSET NAMES utf8mb4;\n\n";
$out .= "DELETE FROM matches; DELETE FROM teams;\n";
$out .= "ALTER TABLE teams AUTO_INCREMENT = 1; ALTER TABLE matches AUTO_INCREMENT = 1;\n\n";

// teams
$idByNome = [];
$out .= "INSERT INTO teams (id, nome, sigla, bandeira, grupo) VALUES\n";
$rows = [];
foreach ($teams as $i => $t) {
    $id = $i + 1;
    $idByNome[$t[0]] = $id;
    $rows[] = "($id, " . q($t[0]) . ", " . q($t[1]) . ", " . q($t[2]) . ", " . q($t[3]) . ")";
}
$out .= implode(",\n", $rows) . ";\n\n";

// matches — grupos
$out .= "INSERT INTO matches (numero, fase, grupo, home_team_id, away_team_id, kickoff_utc, sede) VALUES\n";
$rows = [];
foreach ($g as $m) {
    [$num,$date,$time,$enHome,$enAway,$grp,$sede] = $m;
    $hp = $ptByEn[$enHome] ?? null; $ap = $ptByEn[$enAway] ?? null;
    if (!$hp || !$ap || !isset($idByNome[$hp]) || !isset($idByNome[$ap])) {
        fwrite(STDERR, "Seleção não mapeada no jogo $num: $enHome / $enAway\n"); exit(1);
    }
    $utc = to_utc($date, $time);
    $rows[] = "($num, 'grupos', " . q($grp) . ", " . $idByNome[$hp] . ", " . $idByNome[$ap] . ", " . q($utc) . ", " . q($sede) . ")";
}
$out .= implode(",\n", $rows) . ";\n\n";

// matches — mata-mata (placeholders)
$out .= "INSERT INTO matches (numero, fase, home_placeholder, away_placeholder, kickoff_utc, sede) VALUES\n";
$rows = [];
foreach ($ko as $m) {
    [$num,$fase,$utc,$ph,$pa,$sede] = $m;
    $rows[] = "($num, " . q($fase) . ", " . q($ph) . ", " . q($pa) . ", " . q($utc) . ", " . q($sede) . ")";
}
$out .= implode(",\n", $rows) . ";\n";

echo $out;
