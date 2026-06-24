<?php
/* =============================================================
   Помощни функции — четене/запис на JSON с заключване, отговори
   ============================================================= */
require_once __DIR__ . '/config.php';

/** Сигурно четене на JSON файл с резервна стойност. */
function read_json($file, $fallback = []) {
    if (!is_string($file) || $file === '' || !is_file($file)) return $fallback;

    $data = @file_get_contents($file);
    if ($data === false) {
        $fh = @fopen($file, 'r');
        if (!$fh) return $fallback;
        $data = '';
        if (flock($fh, LOCK_SH)) {
            while (!feof($fh)) $data .= fread($fh, 8192);
            flock($fh, LOCK_UN);
        }
        fclose($fh);
    }

    if ($data === '' || $data === false) return $fallback;

    // UTF-8 BOM
    if (strncmp($data, "\xEF\xBB\xBF", 3) === 0) $data = substr($data, 3);

    $decoded = json_decode($data, true);
    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) return $fallback;
    return $decoded === null ? $fallback : $decoded;
}

/** Път до products.json — каноничен файл в root на сайта. */
function products_file_path() {
    $candidates = [];
    if (defined('PRODUCTS_FILE')) $candidates[] = PRODUCTS_FILE;
    if (defined('ROOT_DIR')) $candidates[] = ROOT_DIR . '/products.json';

    foreach ($candidates as $path) {
        $path = str_replace('\\', '/', (string)$path);
        if ($path === '') continue;
        $real = @realpath($path);
        if ($real && is_file($real)) return $real;
        if (is_file($path)) return $path;
    }

    return defined('PRODUCTS_FILE') ? PRODUCTS_FILE : (ROOT_DIR . '/products.json');
}

/** Прочита products.json (с fallback към root/products.json). */
function read_products() {
    $file = products_file_path();
    $root = defined('ROOT_DIR') ? ROOT_DIR . '/products.json' : '';
    $products = read_json($file, null);
    if (is_array($products)) return $products;
    if ($root && $file !== $root && is_file($root)) {
        $products = read_json($root, []);
        if (is_array($products)) return $products;
    }
    return [];
}

/** Записва products.json на каноничния път. */
function write_products($products) {
    return write_json(products_file_path(), $products);
}

/** Диагностика — за празен списък продукти в админ. */
function products_file_diagnostics() {
    $file = products_file_path();
    $root = defined('ROOT_DIR') ? ROOT_DIR . '/products.json' : '';
    return [
        'path'       => $file,
        'root_path'  => $root,
        'exists'     => is_file($file),
        'readable'   => is_readable($file),
        'size'       => is_file($file) ? (int)filesize($file) : 0,
        'root_exists'=> $root ? is_file($root) : false,
        'root_size'  => ($root && is_file($root)) ? (int)filesize($root) : 0,
    ];
}

/** Атомарен запис на JSON (tmp файл + rename + ексклузивно заключване). */
function write_json($file, $data) {
    $dir = dirname($file);
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $tmp  = $file . '.tmp' . getmypid();
    $fh = @fopen($tmp, 'w');
    if (!$fh) return false;
    $ok = false;
    if (flock($fh, LOCK_EX)) {
        $ok = fwrite($fh, $json) !== false;
        fflush($fh);
        flock($fh, LOCK_UN);
    }
    fclose($fh);
    if (!$ok) { @unlink($tmp); return false; }
    return @rename($tmp, $file);
}

/** JSON отговор и край. */
function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_error($message, $code = 400) {
    json_response(['ok' => false, 'error' => $message], $code);
}

/** Прочита тялото на заявката като JSON. */
function read_body() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/** Безопасно име на файл за качване (корен на images/). */
function safe_filename($name, $allowed_ext) {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext, true)) return null;
    return 'file_' . bin2hex(random_bytes(7)) . '.' . $ext;
}

/** Подпапка products/01 или packages/12 — само валидни стойности. */
function safe_media_subdir($subdir) {
    $subdir = str_replace('\\', '/', trim((string)$subdir, '/'));
    if (!preg_match('#^(products|packages)/(\d{2})$#', $subdir)) return '';
    return $subdir;
}

/** Следващ номер за снимка в папка на продукт (2.png, 3.png …). */
function next_image_filename($dir, $ext) {
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $max = 1;
    foreach (glob($dir . '/*') ?: [] as $f) {
        if (!is_file($f)) continue;
        $n = (int)pathinfo($f, PATHINFO_FILENAME);
        if ($n > $max) $max = $n;
    }
    return ($max + 1) . '.' . $ext;
}

/** Проверка дали път е в images/ (вкл. подпапки). */
function resolve_image_path($path) {
    $path = ltrim(str_replace('\\', '/', (string)$path), '/');
    if (!preg_match('#^images/[A-Za-z0-9._/-]+$#', $path)) return null;
    $full = realpath(ROOT_DIR . '/' . $path);
    $base = realpath(IMAGES_DIR);
    if (!$full || !$base || strpos($full, $base) !== 0 || !is_file($full)) return null;
    return $full;
}

/** Валидира път до файл в images/products|packages (без локален файл). */
function safe_image_asset_path($path) {
    $path = ltrim(str_replace('\\', '/', (string)$path), '/');
    if (!preg_match('#^images/(products|packages)/(\d{2})/[A-Za-z0-9._-]+$#', $path)) return null;
    return $path;
}

function bunny_storage_enabled() {
    return BUNNY_STORAGE_ZONE !== '' && BUNNY_STORAGE_KEY !== '';
}

function bunny_storage_host() {
    $region = trim((string)BUNNY_STORAGE_REGION);
    return $region ? $region . '.storage.bunnycdn.com' : 'storage.bunnycdn.com';
}

function bunny_storage_url($remotePath) {
    $remotePath = ltrim(str_replace('\\', '/', (string)$remotePath), '/');
    return 'https://' . bunny_storage_host() . '/' . rawurlencode(BUNNY_STORAGE_ZONE) . '/' . $remotePath;
}

/** @return array{0:int,1:string} */
function bunny_request($method, $remotePath, $body = null) {
    $url = bunny_storage_url($remotePath);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['AccessKey: ' . BUNNY_STORAGE_KEY],
        CURLOPT_TIMEOUT => 120,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($resp === false) {
        $resp = curl_error($ch);
        $code = 0;
    }
    curl_close($ch);
    return [$code, (string)$resp];
}

function bunny_list($dirPath) {
    $dirPath = rtrim(ltrim(str_replace('\\', '/', (string)$dirPath), '/'), '/') . '/';
    [$code, $resp] = bunny_request('GET', $dirPath);
    if ($code !== 200) return [];
    $data = json_decode($resp, true);
    return is_array($data) ? $data : [];
}

function bunny_next_image_filename($storageDir, $ext) {
    $max = 1;
    foreach (bunny_list($storageDir) as $item) {
        if (!empty($item['IsDirectory'])) continue;
        $n = (int)pathinfo($item['ObjectName'] ?? '', PATHINFO_FILENAME);
        if ($n > $max) $max = $n;
    }
    return ($max + 1) . '.' . $ext;
}

function bunny_upload($remotePath, $tmpFile) {
    if (!preg_match('#^images/#', $remotePath)) return false;
    $body = @file_get_contents($tmpFile);
    if ($body === false) return false;
    [$code] = bunny_request('PUT', $remotePath, $body);
    return in_array($code, [200, 201], true);
}

function bunny_delete($remotePath) {
    if (!preg_match('#^images/#', $remotePath)) return false;
    [$code] = bunny_request('DELETE', $remotePath);
    return in_array($code, [200, 404], true);
}

/** Гарантира съществуване на дата файловете със стойности по подразбиране. */
function ensure_data_files() {
    if (!is_dir(DATA_DIR)) @mkdir(DATA_DIR, 0775, true);
    if (!file_exists(ORDERS_FILE))       write_json(ORDERS_FILE, []);
    if (!file_exists(VISITS_FILE))       write_json(VISITS_FILE, []);
    if (!file_exists(RESERVATIONS_FILE)) write_json(RESERVATIONS_FILE, []);
    if (!file_exists(CATEGORIES_FILE)) write_json(CATEGORIES_FILE, []);
    if (!file_exists(SETTINGS_FILE)) write_json(SETTINGS_FILE, default_settings());
    if (!file_exists(CONTENT_FILE)) write_content(default_content());
    if (!file_exists(UVOD_FILE)) write_json(UVOD_FILE, ['content' => default_uvod_html()]);
}

function reservation_ttl_seconds() {
    $min = defined('RESERVATION_MINUTES') ? (int)RESERVATION_MINUTES : 30;
    return max(5, $min) * 60;
}

function reservation_ttl_minutes() {
    return (int)(reservation_ttl_seconds() / 60);
}

function read_reservations() {
    cleanup_expired_reservations();
    return read_json(RESERVATIONS_FILE, []);
}

function write_reservations($rows) {
    return write_json(RESERVATIONS_FILE, array_values($rows));
}

function cleanup_expired_reservations() {
    if (!defined('RESERVATIONS_FILE')) return;
    $now = time();
    $rows = read_json(RESERVATIONS_FILE, []);
    $next = array_values(array_filter($rows, fn($r) => ($r['expires_at'] ?? 0) > $now));
    if (count($next) !== count($rows)) write_reservations($next);
}

function find_reservation($productId, $rows = null) {
    $rows = $rows ?? read_reservations();
    foreach ($rows as $r) {
        if ((int)($r['product_id'] ?? 0) === (int)$productId) return $r;
    }
    return null;
}

function reserve_product($productId, $sessionId) {
    $productId = (int)$productId;
    $sessionId = sanitize_session_id($sessionId);
    if (!$productId || !$sessionId) return ['ok' => false, 'error' => 'Невалидни данни.'];

    $products = read_products();
    $product = null;
    foreach ($products as $p) {
        if ((int)$p['id'] === $productId) { $product = $p; break; }
    }
    if (!$product || empty($product['available'])) return ['ok' => false, 'error' => 'Продуктът не е наличен.'];

    $rows = read_reservations();
    $existing = find_reservation($productId, $rows);
    if ($existing && ($existing['session_id'] ?? '') !== $sessionId) {
        return ['ok' => false, 'error' => 'Резервиран от друг клиент.'];
    }

    $expires = time() + reservation_ttl_seconds();
    $found = false;
    foreach ($rows as $i => $r) {
        if ((int)($r['product_id'] ?? 0) === $productId) {
            $rows[$i] = ['product_id' => $productId, 'session_id' => $sessionId, 'expires_at' => $expires, 'updated_at' => time()];
            $found = true;
            break;
        }
    }
    if (!$found) $rows[] = ['product_id' => $productId, 'session_id' => $sessionId, 'expires_at' => $expires, 'updated_at' => time()];
    write_reservations($rows);
    return ['ok' => true];
}

function sync_session_reservations($sessionId, array $productIds) {
    $sessionId = sanitize_session_id($sessionId);
    if (!$sessionId) return;
    $want = array_values(array_unique(array_filter(array_map('intval', $productIds), fn($id) => $id > 0)));
    $rows = read_reservations();

    foreach ($rows as $r) {
        if (($r['session_id'] ?? '') === $sessionId && !in_array((int)($r['product_id'] ?? 0), $want, true)) {
            release_product((int)$r['product_id'], $sessionId);
        }
    }
    foreach ($want as $pid) {
        reserve_product($pid, $sessionId);
    }
}

function clear_reservations_for_products(array $productIds) {
    $ids = array_map('intval', $productIds);
    $rows = read_reservations();
    $rows = array_values(array_filter($rows, fn($r) => !in_array((int)($r['product_id'] ?? 0), $ids, true)));
    write_reservations($rows);
}

function reservations_public($sessionId = '') {
    $sessionId = sanitize_session_id($sessionId);
    $out = [];
    foreach (read_reservations() as $r) {
        $out[] = [
            'product_id' => (int)($r['product_id'] ?? 0),
            'expires_at' => (int)($r['expires_at'] ?? 0),
            'mine'       => $sessionId !== '' && ($r['session_id'] ?? '') === $sessionId,
        ];
    }
    return $out;
}

function sanitize_session_id($id) {
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$id);
    return strlen($id) >= 8 ? $id : '';
}

function order_product_ids($order) {
    $ids = [];
    foreach ($order['products'] ?? [] as $p) {
        $id = (int)($p['id'] ?? 0);
        if ($id > 0) $ids[] = $id;
    }
    return array_values(array_unique($ids));
}

function set_products_availability(array $productIds, $available) {
    if (!$productIds) return;
    $products = read_products();
    foreach ($products as $i => $p) {
        if (in_array((int)($p['id'] ?? 0), $productIds, true)) {
            $products[$i]['available'] = (bool)$available;
        }
    }
    write_products($products);
}

function send_push_notification($title, $message) {
    $title = trim((string)$title);
    $message = trim((string)$message);
    if ($title === '' && $message === '') return;

    if (defined('TELEGRAM_BOT_TOKEN') && TELEGRAM_BOT_TOKEN !== ''
        && defined('TELEGRAM_CHAT_ID') && TELEGRAM_CHAT_ID !== '') {
        $text = $title . "\n\n" . $message;
        $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage';
        $payload = http_build_query([
            'chat_id' => TELEGRAM_CHAT_ID,
            'text' => $text,
            'parse_mode' => 'HTML',
        ]);
        $ctx = stream_context_create(['http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $payload,
            'timeout' => 8,
        ]]);
        @file_get_contents($url, false, $ctx);
    }

    if (defined('NTFY_TOPIC') && NTFY_TOPIC !== '') {
        $ctx = stream_context_create(['http' => [
            'method' => 'POST',
            'header' => "Title: " . $title . "\r\nPriority: high\r\nTags: bell\r\n",
            'content' => $message,
            'timeout' => 8,
        ]]);
        @file_get_contents('https://ntfy.sh/' . rawurlencode(NTFY_TOPIC), false, $ctx);
    }
}

function notify_new_order($order) {
    $lines = [];
    foreach ($order['products'] ?? [] as $p) {
        $lines[] = '• ' . ($p['title'] ?? '') . ' — ' . ($p['price'] ?? '') . ' €';
    }
    $body = "Име: {$order['name']}\nТел: {$order['phone']}\nИмейл: {$order['email']}\n\n"
        . implode("\n", $lines) . "\n\nОбщо: {$order['total']} €";
    send_push_notification('Нова поръчка — ' . SHOP_NAME, $body);
}

function default_settings() {
    return [
        'delivery' => "Доставка с куриер (Спиди / Еконт) за България — 1–3 работни дни.\nМеждународна доставка чрез Български пощи — 7–20 работни дни.",
        'payment'  => "Плащане при доставка (наложен платеж) или по банков път след уговорка.",
        'general'  => "Моят Забавен Магазин — ръчно изработени бутилки с български фолклорен мотив.",
        'email_notifications' => true
    ];
}

function default_content() {
    return [
        'contacts' => [
            'phone'      => '+359 899 518 271',
            'phone_link' => '+359899518271',
            'email'      => 'orders@funshops.net',
            'person'     => 'Недко Димитров',
            'address'    => 'Стара Загора, България',
            'hours'      => 'Пон – Нед: 09:00 – 18:00 · Онлайн 24/7',
        ],
    ];
}

function read_content() {
    return read_json(CONTENT_FILE, default_content());
}

function write_content($data) {
    return write_json(CONTENT_FILE, $data);
}

function default_uvod_html() {
    $j = read_json(UVOD_FILE, null);
    if (is_array($j) && !empty($j['content'])) return $j['content'];
    return <<<'HTML'
<p class="lead-line reveal"><strong>Здравейте!</strong></p>
<p class="reveal">Изработвам подаръчни бутилки с български фолклорен мотив.</p>
<p class="reveal">Подходящи за много поводи:</p>
<div class="occasions">
    <span class="chip red reveal">Рожден ден</span>
    <span class="chip green reveal d1">Имен ден</span>
    <span class="chip amber reveal d2">Семеен подарък</span>
    <span class="chip red reveal d3">Украса за механи и заведения</span>
</div>
<div class="divider" style="margin:30px 0"><span>✦</span></div>
<p class="reveal">Изработени са изцяло чисто — без лепила или други смоли. Единствено точно измерване и прецизно сглобяване.</p>
<p class="reveal">Поставени в бутилката, фигурите са здраво стегнати и заклинени. След това се измиват и пълнят с алкохол (ракия). Не плават, не мърдат, а след измиване и закисване е невъзможно да се разглобят.</p>
<p class="reveal">Дървото придава цвят на ракията — дори да се сипе бяла ракия (за предпочитане), след време тя придобива характерен жълт цвят.</p>
<p class="reveal">Бутилките се продават сухи и неизмити, за да може да се правят корекции. Но по ваше желание могат да са измити и подготвени. След получаване се измиват с вода от прашинки и се закисват с вода или ракия.</p>
<p class="reveal">Бутилката се изработва изцяло ръчно. Отнема много време за цялата изработка, така че моля да оцените този труд. Цената за всяко е различна спрямо изработката.</p>
<p class="reveal">Изработвам и индивидуални бутилки по ваше желание и надписи, но с текст съобразен с размера на масичката и предварително уточнен с мен по телефона или съобщение.</p>
<p class="reveal">За въпроси относно наличности, допълнителни снимки или цени — моля, изпратете съобщение. Разгледайте снимките и ако харесате нещо, пишете.</p>
<p class="lead-line reveal" style="margin-top:26px">Очаквам вашите поръчки и благодаря за отделеното време!</p>
<div style="text-align:center; margin-top:34px" class="reveal">
    <a href="products.html" class="btn btn-amber">Разгледай продуктите</a>
    <a href="contacts.html" class="btn btn-ghost">Свържи се с мен</a>
</div>
HTML;
}
