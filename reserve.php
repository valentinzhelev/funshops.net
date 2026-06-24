<?php
/* =============================================================
   Публичен API — резервация на продукти в количка (30 мин)
   ============================================================= */
require_once __DIR__ . '/admin/lib.php';
ensure_data_files();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

cleanup_expired_reservations();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $session = sanitize_session_id($_GET['session_id'] ?? '');
    json_response([
        'ok' => true,
        'reservations' => reservations_public($session),
        'ttl_minutes' => reservation_ttl_minutes(),
    ]);
}

if ($method !== 'POST') {
    http_response_code(405);
    json_response(['ok' => false, 'error' => 'Method not allowed']);
}

$in = read_body();
$action = (string)($in['action'] ?? '');
$session = sanitize_session_id($in['session_id'] ?? '');

if (!$session) json_error('Невалидна сесия.');

switch ($action) {
    case 'hold': {
        $pid = (int)($in['product_id'] ?? 0);
        $res = reserve_product($pid, $session);
        if (!$res['ok']) json_error($res['error'] ?? 'Грешка.');
        json_response(['ok' => true, 'ttl_minutes' => reservation_ttl_minutes()]);
    }
    case 'release': {
        release_product((int)($in['product_id'] ?? 0), $session);
        json_response(['ok' => true]);
    }
    case 'sync': {
        $ids = is_array($in['product_ids'] ?? null) ? $in['product_ids'] : [];
        sync_session_reservations($session, $ids);
        json_response(['ok' => true, 'reservations' => reservations_public($session)]);
    }
    case 'clear': {
        release_session($session);
        json_response(['ok' => true]);
    }
    default:
        json_error('Непознато действие.');
}
