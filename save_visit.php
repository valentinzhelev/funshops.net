<?php
/* =============================================================
   Записване на посещение — уникален посетител на ден
   Извиква се от фронтенда (app.js) при зареждане на страница.
   ============================================================= */
require_once __DIR__ . '/admin/lib.php';
ensure_data_files();

header('Content-Type: application/json; charset=utf-8');

$ip = $_SERVER['REMOTE_ADDR'] ?? '0';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$today = date('Y-m-d');
$vid = substr(hash('sha256', $ip . '|' . $ua . '|' . $today), 0, 16);

/* Бисквитка, за да не пишем при всяко зареждане в рамките на деня */
$cookieName = 'mzm_v';
if (isset($_COOKIE[$cookieName]) && $_COOKIE[$cookieName] === $vid) {
    echo json_encode(['ok' => true, 'cached' => true]);
    exit;
}
setcookie($cookieName, $vid, time() + 86400, '/');

$visits = read_json(VISITS_FILE, []);

/* Дедупликация: един уникален посетител на ден */
foreach ($visits as $v) {
    if (($v['vid'] ?? '') === $vid) {
        echo json_encode(['ok' => true, 'duplicate' => true]);
        exit;
    }
}

$visits[] = [
    'ts'   => time(),
    'date' => $today,
    'vid'  => $vid,
];

/* Подрязваме до последните 2 години записи, за да не расте безкрайно */
$cutoff = time() - (730 * 86400);
$visits = array_values(array_filter($visits, fn($v) => ($v['ts'] ?? 0) >= $cutoff));

write_json(VISITS_FILE, $visits);
echo json_encode(['ok' => true]);
