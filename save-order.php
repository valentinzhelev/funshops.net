<?php
/* =============================================================
   Приемане на поръчка — запис в orders.json + имейл известие
   Извиква се от order.html (POST с JSON тяло).
   ============================================================= */
require_once __DIR__ . '/admin/lib.php';
ensure_data_files();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$in = read_body();
$name = trim($in['name'] ?? '');
if ($name === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Липсва име.']);
    exit;
}

$products = is_array($in['products'] ?? null) ? $in['products'] : [];
$total = 0;
foreach ($products as $p) { $total += (float)($p['price'] ?? 0); }

$order = [
    'id'        => (int)($in['id'] ?? round(microtime(true) * 1000)),
    'name'      => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
    'phone'     => htmlspecialchars(trim($in['phone'] ?? ''), ENT_QUOTES, 'UTF-8'),
    'email'     => htmlspecialchars(trim($in['email'] ?? ''), ENT_QUOTES, 'UTF-8'),
    'delivery'  => htmlspecialchars(trim($in['delivery'] ?? ''), ENT_QUOTES, 'UTF-8'),
    'city'      => htmlspecialchars(trim($in['city'] ?? ''), ENT_QUOTES, 'UTF-8'),
    'address'   => htmlspecialchars(trim($in['address'] ?? ''), ENT_QUOTES, 'UTF-8'),
    'comment'   => htmlspecialchars(trim($in['comment'] ?? ''), ENT_QUOTES, 'UTF-8'),
    'products'  => array_map(function ($p) {
        return [
            'id'    => $p['id'] ?? null,
            'title' => htmlspecialchars((string)($p['title'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'price' => (float)($p['price'] ?? 0),
        ];
    }, $products),
    'total'     => round($total, 2),
    'status'    => 'pending', // pending | fulfilled | cancelled
    'date'      => $in['date'] ?? date('d.m.Y H:i'),
    'created_at'=> time(),
];

$orders = read_json(ORDERS_FILE, []);
$orders[] = $order;
write_json(ORDERS_FILE, $orders);

/* Имейл известие (best-effort) */
$settings = read_json(SETTINGS_FILE, default_settings());
if (!empty($settings['email_notifications'])) {
    $lines = [];
    foreach ($order['products'] as $p) $lines[] = "  • {$p['title']} — {$p['price']} €";
    $body =
        "Нова поръчка от " . SHOP_NAME . "\n\n" .
        "Име: {$order['name']}\n" .
        "Телефон: {$order['phone']}\n" .
        "Имейл: {$order['email']}\n" .
        "Доставка: {$order['delivery']}\n" .
        "Град: {$order['city']}\n" .
        "Адрес: {$order['address']}\n" .
        "Коментар: {$order['comment']}\n\n" .
        "Продукти:\n" . implode("\n", $lines) . "\n\n" .
        "Общо: {$order['total']} €\n" .
        "Дата: {$order['date']}\n";
    $subject = '=?UTF-8?B?' . base64_encode('Нова поръчка — ' . SHOP_NAME) . '?=';
    $headers = "From: " . ORDER_EMAIL_FROM . "\r\n" .
               "Content-Type: text/plain; charset=UTF-8\r\n";
    if (!empty($order['email']) && filter_var($in['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
        $headers .= "Reply-To: " . ($in['email']) . "\r\n";
    }
    @mail(ORDER_EMAIL_TO, $subject, $body, $headers);
}

echo json_encode(['ok' => true, 'id' => $order['id']]);
