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
        $scalars = ['repartiment_count', 'repartiment_freq', 'neteja_count', 'neteja_freq', 'advance_months'];
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
        $task    = $_POST['task'];   // 'repartiment' or 'neteja'
        $start   = $_POST['start'];  // 'YYYY-MM-DD'
        $months  = (int)($_POST['months'] ?? getTornsConfig()['advance_months']);
        $end     = date('Y-m-d', strtotime($start . ' +' . $months . ' months'));
        generateTorns($task, $start, $end);
        echo json_encode(getUpcomingTorns($months));
        break;

    case 'getUpcoming':
        $months = (int)(getTornsConfig()['advance_months'] ?? 2);
        echo json_encode(getUpcomingTorns($months));
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

    case 'getUfs':
        $rs   = $db->Execute('SELECT id, name FROM aixada_uf WHERE active = 1 ORDER BY id');
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
    $cfg['excluded']      = [];
    $cfg['no_responsible'] = [];
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
    $rs  = $db->Execute('SELECT id, name FROM aixada_uf WHERE active = 1 ORDER BY id');
    $ufs = [];
    while ($row = $rs->fetch_assoc()) {
        if (!in_array((int)$row['id'], $excluded)) {
            $ufs[] = (int)$row['id'];
        }
    }
    return $ufs;
}

function getRotationStart(string $task, array $eligible): int
{
    $db  = DBWrap::get_instance();
    $rs  = $db->Execute('SELECT ufTorn FROM aixada_torns WHERE task_type = :1q ORDER BY dataTorn DESC, ufTorn DESC LIMIT 1', $task);
    if ($row = $rs->fetch_assoc()) {
        $last = (int)$row['ufTorn'];
        $pos  = array_search($last, $eligible);
        if ($pos !== false) {
            return ($pos + 1) % count($eligible);
        }
    }
    return 0;
}

function pickUfs(int $count, int &$rotIdx, array $eligible, array $incompatible): array
{
    $n       = count($eligible);
    $picked  = [];
    $tried   = 0;

    while (count($picked) < $count && $tried < $n * 2) {
        $candidate = $eligible[$rotIdx % $n];
        $rotIdx    = ($rotIdx + 1) % $n;
        $tried++;

        $conflict = false;
        foreach ($picked as $already) {
            $a = min($candidate, $already);
            $b = max($candidate, $already);
            foreach ($incompatible as $pair) {
                if ($pair[0] === $a && $pair[1] === $b) {
                    $conflict = true;
                    break 2;
                }
            }
        }
        if (!$conflict) {
            $picked[] = $candidate;
        }
    }

    return $picked;
}

function generateTorns(string $task, string $start, string $end): void
{
    $db  = DBWrap::get_instance();
    $cfg = getTornsConfig();

    $count        = (int)($cfg[$task . '_count'] ?? ($task === 'repartiment' ? 6 : 3));
    $freq_weeks   = (int)($cfg[$task . '_freq']  ?? ($task === 'repartiment' ? 1 : 2));
    $excluded     = $cfg['excluded']      ?? [];
    $no_resp      = $cfg['no_responsible'] ?? [];
    $incompatible = array_map(fn($p) => [(int)$p[0], (int)$p[1]], $cfg['incompatible'] ?? []);

    $eligible = getEligibleUfs($excluded);
    if (empty($eligible)) return;

    $db->Execute('DELETE FROM aixada_torns WHERE task_type = :1q AND dataTorn >= :2q AND dataTorn <= :3q',
                 $task, $start, $end);

    $rotIdx  = getRotationStart($task, $eligible);
    $current = strtotime($start);
    $endTs   = strtotime($end);

    while ($current <= $endTs) {
        $date   = date('Y-m-d', $current);
        $picked = pickUfs($count, $rotIdx, $eligible, $incompatible);

        $responsable = null;
        if ($task === 'repartiment') {
            foreach ($picked as $uf) {
                if (!in_array($uf, $no_resp)) {
                    $responsable = $uf;
                    break;
                }
            }
        }

        foreach ($picked as $uf) {
            $is_resp = ($uf === $responsable) ? 1 : 0;
            $db->Execute('INSERT INTO aixada_torns (dataTorn, ufTorn, task_type, is_responsible) VALUES (:1q, :2q, :3q, :4q)',
                         $date, $uf, $task, $is_resp);
        }

        $current = strtotime($date . ' +' . $freq_weeks . ' weeks');
    }
}

function getUpcomingTorns(int $months): array
{
    $db    = DBWrap::get_instance();
    $today = date('Y-m-d');
    $end   = date('Y-m-d', strtotime('+' . $months . ' months'));

    $rs = $db->Execute(
        'SELECT t.dataTorn, t.ufTorn, t.task_type, t.is_responsible, u.name
         FROM aixada_torns t
         JOIN aixada_uf u ON u.id = t.ufTorn
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
            'is_responsible' => (int)$row['is_responsible'],
        ];
    }

    return array_values($weeks);
}
