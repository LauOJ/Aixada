<?php

define('DS', DIRECTORY_SEPARATOR);
define('__ROOT__', dirname(__DIR__, 2) . DS);
require_once(__ROOT__ . 'local_config/config.php');
require_once(__ROOT__ . 'php/inc/database.php');
require_once(__ROOT__ . 'php/utilities/general.php');

validate_session();

$db = DBWrap::get_instance();

switch ($_POST['oper'] ?? '') {

    case 'getConfig':
        echo json_encode(getTornsConfig());
        break;

    case 'saveConfig':
        $scalars = ['repartiment_count', 'repartiment_freq', 'neteja_count', 'neteja_freq', 'repartiment_day'];
        foreach ($scalars as $key) {
            if (isset($_POST[$key])) {
                $db->Execute('INSERT INTO aixada_torns_config (setting, value) VALUES (:1q, :2q)
                              ON DUPLICATE KEY UPDATE value = :2q', $key, (int)$_POST[$key]);
            }
        }
        $db->Execute('DELETE FROM aixada_torns_restriction WHERE type = :1q', 'excluded');
        foreach (json_decode($_POST['excluded'] ?? '[]') as $uf_id) {
            $db->Execute('INSERT IGNORE INTO aixada_torns_restriction VALUES (:1q, :2q)', 'excluded', (int)$uf_id);
        }
        $db->Execute('DELETE FROM aixada_torns_restriction WHERE type = :1q', 'no_responsible');
        foreach (json_decode($_POST['no_responsible'] ?? '[]') as $uf_id) {
            $db->Execute('INSERT IGNORE INTO aixada_torns_restriction VALUES (:1q, :2q)', 'no_responsible', (int)$uf_id);
        }
        $db->Execute('DELETE FROM aixada_torns_restriction WHERE type = :1q', 'nova');
        foreach (json_decode($_POST['nova'] ?? '[]') as $uf_id) {
            $db->Execute('INSERT IGNORE INTO aixada_torns_restriction VALUES (:1q, :2q)', 'nova', (int)$uf_id);
        }
        $db->Execute('DELETE FROM aixada_torns_incompatible');
        foreach (json_decode($_POST['incompatible'] ?? '[]') as $pair) {
            $a = min((int)$pair[0], (int)$pair[1]);
            $b = max((int)$pair[0], (int)$pair[1]);
            if ($a !== $b) {
                $db->Execute('INSERT IGNORE INTO aixada_torns_incompatible VALUES (:1q, :2q)', $a, $b);
            }
        }
        echo 'ok';
        break;

    case 'generateTorns':
        $task  = $_POST['task'];
        $start = $_POST['start'];
        $end   = $_POST['end'];
        generateTorns($task, $start, $end);
        $cfgMonths = (int)(getTornsConfig()['advance_months'] ?? 2);
        $endMonths = (int)ceil((strtotime($end) - strtotime(date('Y-m-d'))) / (30 * 24 * 3600));
        echo json_encode(getUpcomingTorns(max($cfgMonths, $endMonths)));
        break;

    case 'getUpcoming':
        echo json_encode(getUpcomingTorns(12));
        break;

    // Només lectura: compta les assignacions (UF > 0) que un "generar" esborraria
    // en aquest període i tipus. Serveix perquè la pantalla només avisi si cal.
    case 'countTornsInRange':
        $task  = $_POST['task'];
        $start = $_POST['start'];
        $end   = $_POST['end'];
        $rs = $db->Execute(
            'SELECT COUNT(*) AS n FROM aixada_torns
             WHERE task_type = :1q AND ufTorn > 0 AND dataTorn >= :2q AND dataTorn <= :3q',
            $task, $start, $end
        );
        $row = $rs->fetch_assoc();
        echo json_encode((int)$row['n']);
        break;

    case 'addTorn':
        $date = $_POST['date'];
        $uf   = (int)$_POST['uf'];
        $task = $_POST['task'];
        $db->Execute('INSERT IGNORE INTO aixada_torns (dataTorn, ufTorn, task_type, is_responsible) VALUES (:1q, :2q, :3q, 0)',
                     $date, $uf, $task);
        echo 'ok';
        break;

    case 'updateTorn':
        $date     = $_POST['date'];
        $old_uf   = (int)$_POST['old_uf'];
        $new_uf   = (int)$_POST['new_uf'];
        $task     = $_POST['task'];
        $db->Execute('UPDATE aixada_torns SET ufTorn = :1q
                      WHERE dataTorn = :2q AND ufTorn = :3q AND task_type = :4q LIMIT 1',
                     $new_uf, $date, $old_uf, $task);
        echo 'ok';
        break;

    case 'setResponsable':
        $date = $_POST['date'];
        $uf   = (int)$_POST['uf'];
        $db->Execute('UPDATE aixada_torns SET is_responsible = 0
                      WHERE dataTorn = :1q AND task_type = :2q', $date, 'repartiment');
        $db->Execute('UPDATE aixada_torns SET is_responsible = 1
                      WHERE dataTorn = :1q AND ufTorn = :2q AND task_type = :3q',
                     $date, $uf, 'repartiment');
        echo 'ok';
        break;

    case 'deleteTorn':
        $date = $_POST['date'];
        $uf   = (int)$_POST['uf'];
        $task = $_POST['task'];
        $db->Execute('DELETE FROM aixada_torns WHERE dataTorn = :1q AND ufTorn = :2q AND task_type = :3q LIMIT 1',
                     $date, $uf, $task);
        echo 'ok';
        break;

    // La setmana passa a autorepartiment: buidem el grup de repartiment i hi
    // deixem una fila sentinella (UF 0) que la vista mostra com a "Autorepartiment".
    case 'setAutorepartiment':
        $date = $_POST['date'];
        $db->Execute("DELETE FROM aixada_torns WHERE dataTorn = :1q AND task_type = 'repartiment'", $date);
        $db->Execute("INSERT IGNORE INTO aixada_torns (dataTorn, ufTorn, task_type, is_responsible) VALUES (:1q, 0, 'repartiment', 0)", $date);
        echo 'ok';
        break;

    case 'unsetAutorepartiment':
        $date = $_POST['date'];
        $db->Execute("DELETE FROM aixada_torns WHERE dataTorn = :1q AND ufTorn = 0 AND task_type = 'repartiment'", $date);
        echo 'ok';
        break;

    case 'getUfs':
        $rs   = $db->Execute(
            'SELECT DISTINCT u.id, u.name FROM aixada_uf u
             INNER JOIN aixada_member m ON m.uf_id = u.id AND m.active = 1
             WHERE u.active = 1
             ORDER BY u.id'
        );
        $ufs  = [];
        while ($row = $rs->fetch_assoc()) {
            $ufs[] = $row;
        }
        echo json_encode($ufs);
        break;

    default:
        http_response_code(400);
        echo 'unknown operation';
}


// ─── helpers ─────────────────────────────────────────────────────────────────

function getTornsConfig(): array
{
    $db  = DBWrap::get_instance();
    $cfg = [];

    $rs = $db->Execute('SELECT setting, value FROM aixada_torns_config');
    while ($row = $rs->fetch_assoc()) {
        $cfg[$row['setting']] = $row['value'];
    }

    $rs = $db->Execute("SELECT type, uf_id FROM aixada_torns_restriction");
    $cfg['excluded']       = [];
    $cfg['no_responsible'] = [];
    $cfg['nova']           = [];
    while ($row = $rs->fetch_assoc()) {
        $cfg[$row['type']][] = (int)$row['uf_id'];
    }

    $rs = $db->Execute('SELECT uf_id_1, uf_id_2 FROM aixada_torns_incompatible');
    $cfg['incompatible'] = [];
    while ($row = $rs->fetch_assoc()) {
        $cfg['incompatible'][] = [(int)$row['uf_id_1'], (int)$row['uf_id_2']];
    }

    return $cfg;
}

function getEligibleUfs(array $excluded): array
{
    $db  = DBWrap::get_instance();
    $rs  = $db->Execute(
        'SELECT DISTINCT u.id FROM aixada_uf u
         INNER JOIN aixada_member m ON m.uf_id = u.id AND m.active = 1
         WHERE u.active = 1
         ORDER BY u.id'
    );
    $ufs = [];
    while ($row = $rs->fetch_assoc()) {
        if (!in_array((int)$row['id'], $excluded)) {
            $ufs[] = (int)$row['id'];
        }
    }
    return $ufs;
}

function getLastPeriodUfs(string $task, string $before): array
{
    $db = DBWrap::get_instance();
    // Get all UFs from the most recent period before $before
    $rs = $db->Execute(
        'SELECT ufTorn FROM aixada_torns WHERE task_type = :1q AND dataTorn < :2q
         ORDER BY dataTorn DESC LIMIT 20',
        $task, $before
    );
    if (!($row = $rs->fetch_assoc())) return [];
    // Find all UFs from that same date
    $rs2 = $db->Execute(
        'SELECT ufTorn FROM aixada_torns WHERE task_type = :1q AND dataTorn =
         (SELECT MAX(dataTorn) FROM aixada_torns WHERE task_type = :1q AND dataTorn < :2q)',
        $task, $before
    );
    $ufs = [];
    while ($r = $rs2->fetch_assoc()) {
        $ufs[] = (int)$r['ufTorn'];
    }
    return $ufs;
}

function getRecentAssignmentCount(string $task, string $since): array
{
    $db = DBWrap::get_instance();
    $rs = $db->Execute(
        'SELECT ufTorn, COUNT(*) as cnt FROM aixada_torns
         WHERE task_type = :1q AND dataTorn >= :2q
         GROUP BY ufTorn',
        $task, $since
    );
    $counts = [];
    while ($row = $rs->fetch_assoc()) {
        $counts[(int)$row['ufTorn']] = (int)$row['cnt'];
    }
    return $counts;
}

function getRotationStart(string $task, array $eligible, string $startDate): int
{
    // Find the last UF assigned before startDate and continue from there
    $db = DBWrap::get_instance();
    $rs = $db->Execute(
        'SELECT ufTorn FROM aixada_torns WHERE task_type = :1q AND dataTorn < :2q
         ORDER BY dataTorn DESC, ufTorn ASC LIMIT 1',
        $task, $startDate
    );
    if ($row = $rs->fetch_assoc()) {
        $last = (int)$row['ufTorn'];
        $pos  = array_search($last, $eligible);
        if ($pos !== false) {
            return ($pos + 1) % count($eligible);
        }
    }
    return 0;
}

function getLastResponsable(string $before): ?int
{
    $db = DBWrap::get_instance();
    $rs = $db->Execute(
        'SELECT ufTorn FROM aixada_torns
         WHERE task_type = :1q AND is_responsible = 1 AND dataTorn < :2q
         ORDER BY dataTorn DESC LIMIT 1',
        'repartiment', $before
    );
    if ($row = $rs->fetch_assoc()) {
        return (int)$row['ufTorn'];
    }
    return null;
}

// Quantes vegades ha estat responsable cada UF des de $since (per rotar el càrrec).
function getResponsableCount(string $since): array
{
    $db = DBWrap::get_instance();
    $rs = $db->Execute(
        "SELECT ufTorn, COUNT(*) AS cnt FROM aixada_torns
         WHERE task_type = 'repartiment' AND is_responsible = 1 AND dataTorn >= :1q
         GROUP BY ufTorn",
        $since
    );
    $counts = [];
    while ($row = $rs->fetch_assoc()) {
        $counts[(int)$row['ufTorn']] = (int)$row['cnt'];
    }
    return $counts;
}

function pickUfs(int $count, array $eligible, array $incompatible, array $lastPeriod, array $recentCount = [], int $maxRecent = 2, array $nova = [], int $maxNova = 3, int $minNova = 0): array
{
    $isNovaFn = fn($uf) => in_array($uf, $nova);

    // Comprova si $cand xoca amb alguna parella incompatible dins de $group.
    $incompatWith = function ($cand, array $group) use ($incompatible) {
        foreach ($group as $already) {
            $a = min($cand, $already);
            $b = max($cand, $already);
            foreach ($incompatible as $pair) {
                if ($pair[0] === $a && $pair[1] === $b) return true;
            }
        }
        return false;
    };

    // Ordre de prioritat: qui ha repartit MENYS últimament (asc); a igualtat, VETERANES
    // abans que noves (prioritzar veteranes pendents); i desempat ALEATORI. La clau
    // aleatòria evita dependre de l'estabilitat de sort (PHP 7.4).
    $order = [];
    foreach ($eligible as $uf) {
        $order[] = [$recentCount[$uf] ?? 0, $isNovaFn($uf) ? 1 : 0, random_int(0, PHP_INT_MAX), $uf];
    }
    usort($order, fn($a, $b) => [$a[0], $a[1], $a[2]] <=> [$b[0], $b[1], $b[2]]);
    $order = array_map(fn($x) => $x[3], $order);

    $picked   = [];
    $deferred = []; // consec/capped: només com a última opció, en ordre de prioritat
    $novaNow  = 0;

    foreach ($order as $cand) {
        if (count($picked) >= $count) break;
        if ($incompatWith($cand, $picked)) continue;
        $isNova = $isNovaFn($cand);
        if ($isNova && $novaNow >= $maxNova) continue; // límit de noves: es queda per una altra setmana
        if (in_array($cand, $lastPeriod) || ($recentCount[$cand] ?? 0) >= $maxRecent) {
            $deferred[] = $cand;
            continue;
        }
        $picked[] = $cand;
        if ($isNova) $novaNow++;
    }

    // Si falten places, omplim amb els diferits (respectant incompat i límit de noves).
    foreach ($deferred as $cand) {
        if (count($picked) >= $count) break;
        if ($incompatWith($cand, $picked)) continue;
        $isNova = $isNovaFn($cand);
        if ($isNova && $novaNow >= $maxNova) continue;
        $picked[] = $cand;
        if ($isNova) $novaNow++;
    }

    // Garantir un mínim de famílies noves: si en falten, canviem veteranes (les que han
    // repartit MÉS) per noves disponibles encara no escollides.
    if ($minNova > 0 && $novaNow < $minNova) {
        $availNovas = array_values(array_filter($order, fn($u) => $isNovaFn($u) && !in_array($u, $picked)));
        $vets = array_values(array_filter($picked, fn($u) => !$isNovaFn($u)));
        usort($vets, fn($a, $b) => ($recentCount[$b] ?? 0) <=> ($recentCount[$a] ?? 0));
        foreach ($availNovas as $novaUf) {
            if ($novaNow >= $minNova || $novaNow >= $maxNova || empty($vets)) break;
            $vet   = array_shift($vets);
            $group = array_values(array_filter($picked, fn($u) => $u !== $vet));
            if ($incompatWith($novaUf, $group)) continue;
            $picked = $group;
            $picked[] = $novaUf;
            $novaNow++;
        }
    }

    return array_slice($picked, 0, $count);
}

function generateTorns(string $task, string $start, string $end): void
{
    $db  = DBWrap::get_instance();
    $cfg = getTornsConfig();

    $count        = (int)($cfg[$task . '_count'] ?? ($task === 'repartiment' ? 6 : 3));
    $freq_weeks   = (int)($cfg[$task . '_freq']  ?? ($task === 'repartiment' ? 1 : 2));
    $excluded     = $cfg['excluded']       ?? [];
    $no_resp      = $cfg['no_responsible'] ?? [];
    $nova         = $cfg['nova']           ?? [];
    $incompatible = array_map(fn($p) => [(int)$p[0], (int)$p[1]], $cfg['incompatible'] ?? []);

    // Límit i mínim de famílies noves per torn (per defecte: repartiment 2/1, neteja 1/0).
    $novaMax = (int)($cfg[$task . '_nova_max'] ?? ($task === 'repartiment' ? 2 : 1));
    $novaMin = (int)($cfg[$task . '_nova_min'] ?? ($task === 'repartiment' ? 1 : 0));
    // Neteja: excloure l'agost i limitar el nombre de torns per mes.
    $excludeAugust = ($task === 'neteja') && ((int)($cfg['neteja_exclude_august'] ?? 1) === 1);
    $maxPerMonth   = ($task === 'neteja') ? (int)($cfg['neteja_max_per_month'] ?? 2) : 0;

    $eligible = getEligibleUfs($excluded);
    if (empty($eligible)) return;

    $since       = date('Y-m-d', strtotime('-2 months'));
    $recentCount = getRecentAssignmentCount($task, $since);
    // Rotació del càrrec de responsable: comptador de vegades que cadascú ho ha sigut.
    $respCount   = ($task === 'repartiment') ? getResponsableCount($since) : [];
    $monthCount  = []; // torns de neteja assignats per mes (Y-m => n)

    // Snap repartiment start to the configured day of week.
    // DELETE from the original start so old off-day data is also removed.
    $deleteFrom = $start;
    if ($task === 'repartiment') {
        $repDay = (int)($cfg['repartiment_day'] ?? 4);
        $dow    = (int)date('w', strtotime($start));
        if ($dow !== $repDay) {
            $diff  = ($repDay - $dow + 7) % 7;
            $start = date('Y-m-d', strtotime($start . ' +' . $diff . ' days'));
        }
    }

    // Conservem les setmanes d'autorepartiment (fila sentinella UF 0) en regenerar.
    $autoDates = [];
    if ($task === 'repartiment') {
        $rsA = $db->Execute("SELECT dataTorn FROM aixada_torns WHERE task_type = 'repartiment' AND ufTorn = 0 AND dataTorn >= :1q AND dataTorn <= :2q",
                            $deleteFrom, $end);
        while ($rA = $rsA->fetch_assoc()) { $autoDates[] = $rA['dataTorn']; }
    }

    $db->Execute('DELETE FROM aixada_torns WHERE task_type = :1q AND dataTorn >= :2q AND dataTorn <= :3q',
                 $task, $deleteFrom, $end);

    $lastPicked      = getLastPeriodUfs($task, $start);
    $current         = strtotime($start);
    $endTs           = strtotime($end);

    while ($current <= $endTs) {
        $date   = date('Y-m-d', $current);

        // Setmana d'autorepartiment: la deixem buida (només la sentinella).
        if ($task === 'repartiment' && in_array($date, $autoDates)) {
            $db->Execute("INSERT IGNORE INTO aixada_torns (dataTorn, ufTorn, task_type, is_responsible) VALUES (:1q, 0, 'repartiment', 0)", $date);
            $lastPicked = [];
            $current    = strtotime($date . ' +' . $freq_weeks . ' weeks');
            continue;
        }

        // Neteja: saltar l'agost i no superar el màxim de torns per mes.
        if ($task === 'neteja') {
            $ym = date('Y-m', $current);
            if (($excludeAugust && (int)date('n', $current) === 8)
                || ($monthCount[$ym] ?? 0) >= $maxPerMonth) {
                $current = strtotime($date . ' +' . $freq_weeks . ' weeks');
                continue;
            }
        }

        $picked = pickUfs($count, $eligible, $incompatible, $lastPicked, $recentCount, 2, $nova, $novaMax, $novaMin);

        $responsable = null;
        if ($task === 'repartiment') {
            // Rotació: entre les del grup que poden ser responsables, tria qui ho ha
            // estat MENYS vegades (desempat aleatori). Així totes ho són abans de repetir.
            $respCandidates = array_values(array_filter($picked, fn($uf) => !in_array($uf, $no_resp)));
            if (!empty($respCandidates)) {
                $ranked = array_map(fn($uf) => [$respCount[$uf] ?? 0, random_int(0, PHP_INT_MAX), $uf], $respCandidates);
                usort($ranked, fn($a, $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);
                $responsable = $ranked[0][2];
                $respCount[$responsable] = ($respCount[$responsable] ?? 0) + 1;
            }
        }

        foreach ($picked as $uf) {
            $is_resp = ($uf === $responsable) ? 1 : 0;
            $db->Execute('INSERT INTO aixada_torns (dataTorn, ufTorn, task_type, is_responsible) VALUES (:1q, :2q, :3q, :4q)',
                         $date, $uf, $task, $is_resp);
            // Comptador dinàmic: cada assignació compta perquè el límit (maxRecent)
            // s'apliqui de veritat i el repartiment quedi equilibrat dins la generació.
            $recentCount[$uf] = ($recentCount[$uf] ?? 0) + 1;
        }

        if ($task === 'neteja') {
            $ym = date('Y-m', $current);
            $monthCount[$ym] = ($monthCount[$ym] ?? 0) + 1;
        }

        $lastPicked = $picked;
        $current    = strtotime($date . ' +' . $freq_weeks . ' weeks');
    }
}

function getUpcomingTorns(int $months): array
{
    $db    = DBWrap::get_instance();
    $today = date('Y-m-d');
    $end   = date('Y-m-d', strtotime('+' . $months . ' months'));

    $rs = $db->Execute(
        'SELECT t.dataTorn, t.ufTorn, t.task_type, t.is_responsible, u.name,
                (SELECT GROUP_CONCAT(
                    CASE
                        WHEN NULLIF(TRIM(m.phone1),\'\') IS NOT NULL AND NULLIF(TRIM(m.phone2),\'\') IS NOT NULL
                            THEN CONCAT(TRIM(m.phone1), \' / \', TRIM(m.phone2))
                        WHEN NULLIF(TRIM(m.phone1),\'\') IS NOT NULL THEN TRIM(m.phone1)
                        WHEN NULLIF(TRIM(m.phone2),\'\') IS NOT NULL THEN TRIM(m.phone2)
                    END
                    SEPARATOR \', \')
                 FROM aixada_member m WHERE m.uf_id = u.id AND m.active = 1) AS phone
         FROM aixada_torns t
         LEFT JOIN aixada_uf u ON u.id = t.ufTorn
         WHERE t.dataTorn >= :1q AND t.dataTorn <= :2q
         ORDER BY t.dataTorn, t.task_type, t.is_responsible DESC, t.ufTorn',
        $today, $end
    );

    $weeks = [];
    while ($row = $rs->fetch_assoc()) {
        $monday = date('Y-m-d', strtotime('monday this week', strtotime($row['dataTorn'])));
        $sunday = date('Y-m-d', strtotime('sunday this week', strtotime($row['dataTorn'])));
        $key    = $monday;

        if (!isset($weeks[$key])) {
            $weeks[$key] = [
                'week_start'  => $monday,
                'week_end'    => $sunday,
                'repartiment' => [],
                'neteja'      => [],
            ];
        }
        $weeks[$key][$row['task_type']][] = [
            'date'           => $row['dataTorn'],
            'uf_id'          => (int)$row['ufTorn'],
            'name'           => $row['name'],
            'phone'          => $row['phone'] ?? '',
            'is_responsible' => (int)$row['is_responsible'],
        ];
    }

    return array_values($weeks);
}
