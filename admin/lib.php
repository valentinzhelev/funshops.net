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

/** Нов уникален ID за продукт (millisecond timestamp). */
function new_product_id() {
    return (int)round(microtime(true) * 1000);
}

/** Нормализира product ID за сравнение (големи JSON числа на 32-bit PHP). */
function product_id_key($id) {
    if ($id === null || $id === '' || $id === false) return '';
    if (is_float($id)) return sprintf('%.0f', $id);
    if (is_int($id)) return (string)$id;
    $s = trim((string)$id);
    return ($s !== '' && preg_match('/^\d+$/', $s)) ? $s : '';
}

function product_id_valid($id) {
    $k = product_id_key($id);
    return $k !== '' && $k !== '0';
}

function product_ids_match($a, $b) {
    $ka = product_id_key($a);
    $kb = product_id_key($b);
    return $ka !== '' && $ka === $kb;
}

/** Поправя продукти без валиден ID (напр. id: 0 след първо записване). */
function repair_products_invalid_ids(array &$products) {
    $changed = false;
    $used = [];
    foreach ($products as $p) {
        if (product_id_valid($p['id'] ?? 0)) $used[product_id_key($p['id'])] = true;
    }
    foreach ($products as &$p) {
        if (product_id_valid($p['id'] ?? 0)) continue;
        do {
            $p['id'] = new_product_id();
            usleep(1000);
        } while (isset($used[product_id_key($p['id'])]));
        $used[product_id_key($p['id'])] = true;
        $changed = true;
    }
    unset($p);
    return $changed;
}

/** Безопасно име на файл за качване (корен на images/). */
function safe_filename($name, $allowed_ext) {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext, true)) return null;
    return 'file_' . bin2hex(random_bytes(7)) . '.' . $ext;
}

/** Подпапка products/01 или packages/544 — само цифров номер. */
function normalize_product_folder($name) {
    $name = trim((string)$name);
    if (!preg_match('/^\d+$/', $name)) return '';
    return strlen($name) <= 2 ? str_pad($name, 2, '0', STR_PAD_LEFT) : $name;
}

function safe_media_subdir($subdir) {
    $subdir = str_replace('\\', '/', trim((string)$subdir, '/'));
    if (!preg_match('#^(products|packages)/(\d+)$#', $subdir, $m)) return '';
    $folder = normalize_product_folder($m[2]);
    if ($folder === '') return '';
    return $m[1] . '/' . $folder;
}

/** products/01 от път images/products/01/1.mp4 */
function product_video_subdir($path) {
    $path = ltrim(str_replace('\\', '/', (string)$path), '/');
    if (preg_match('#^images/(products|packages)/(\d+)/#', $path, $m)) {
        return $m[1] . '/' . normalize_product_folder($m[2]);
    }
    return '';
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

/** Макс. размер при качване (байтове) — отделен лимит за видео. */
function max_upload_bytes_for($kind) {
    if ($kind === 'video' && defined('MAX_VIDEO_UPLOAD_BYTES')) return (int)MAX_VIDEO_UPLOAD_BYTES;
    return defined('MAX_UPLOAD_BYTES') ? (int)MAX_UPLOAD_BYTES : 60 * 1024 * 1024;
}

function video_mime_for_ext($ext) {
    $ext = strtolower((string)$ext);
    return $ext === 'webm' ? 'video/webm' : 'video/mp4';
}

/** Валидира път до видео на продукт (images/products|packages/NN/1.ext). */
function safe_product_video_path($path) {
    $path = ltrim(str_replace('\\', '/', (string)$path), '/');
    $extPattern = implode('|', array_map('preg_quote', ALLOWED_VIDEO_EXT));
    if (!preg_match('#^images/(products|packages)/(\d+)/1\.(' . $extPattern . ')$#i', $path, $m)) return null;
    return 'images/' . $m[1] . '/' . normalize_product_folder($m[2]) . '/1.' . strtolower($m[3]);
}

/** Потоково обслужване на видео с Range (нужно за HTML5 плейъра). */
function stream_video_file($fullPath, $mime) {
    $size = (int)filesize($fullPath);
    if ($size <= 0) {
        http_response_code(404);
        exit;
    }

    $start = 0;
    $end = $size - 1;
    $httpStatus = 200;

    if (isset($_SERVER['HTTP_RANGE'])) {
        if (preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $m)) {
            if ($m[1] !== '') $start = (int)$m[1];
            if ($m[2] !== '') $end = (int)$m[2];
            if ($end >= $size) $end = $size - 1;
            if ($start > $end || $start >= $size) {
                http_response_code(416);
                header('Content-Range: bytes */' . $size);
                exit;
            }
            $httpStatus = 206;
        }
    }

    $length = $end - $start + 1;

    if (function_exists('apache_setenv')) {
        @apache_setenv('no-gzip', '1');
    }
    @ini_set('zlib.output_compression', '0');

    http_response_code($httpStatus);
    header('Content-Type: ' . $mime);
    header('Accept-Ranges: bytes');
    header('Content-Length: ' . $length);
    if ($httpStatus === 206) {
        header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
    }
    $mtime = (int)@filemtime($fullPath);
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
    header('ETag: W/"' . $mtime . '-' . $size . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('X-LiteSpeed-Cache-Control: no-cache');

    $fh = fopen($fullPath, 'rb');
    if (!$fh) {
        http_response_code(500);
        exit;
    }
    if ($start > 0) fseek($fh, $start);

    $remaining = $length;
    while ($remaining > 0 && !feof($fh)) {
        $read = fread($fh, min(8192, $remaining));
        if ($read === false) break;
        echo $read;
        $remaining -= strlen($read);
        if (connection_aborted()) break;
    }
    fclose($fh);
    exit;
}

/** Изтрива старо видео в папката на продукта преди ново качване. */
function clear_product_folder_videos($subdir) {
    $subdir = safe_media_subdir($subdir);
    if (!$subdir) return;
    $storageDir = 'images/' . $subdir;
    if (bunny_storage_enabled()) {
        foreach (ALLOWED_VIDEO_EXT as $ext) {
            bunny_delete($storageDir . '/1.' . $ext);
        }
        return;
    }
    $dir = IMAGES_DIR . '/' . $subdir;
    if (!is_dir($dir)) return;
    foreach (glob($dir . '/1.*') ?: [] as $f) {
        if (is_file($f)) @unlink($f);
    }
    foreach (glob($dir . '/_upload_*') ?: [] as $f) {
        if (is_file($f)) @unlink($f);
    }
    foreach (glob($dir . '/_convert_*') ?: [] as $f) {
        if (is_file($f)) @unlink($f);
    }
}

/** Изтрива файл от images/products|packages (локално или CDN). */
function delete_media_asset($path) {
    $path = safe_image_asset_path($path);
    if (!$path) return false;
    if (bunny_storage_enabled()) return bunny_delete($path);
    $full = resolve_image_path($path);
    if (!$full) return false;
    return @unlink($full);
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
    if (!preg_match('#^images/(products|packages)/(\d+)/[A-Za-z0-9._-]+$#', $path)) return null;
    return $path;
}

function bunny_storage_enabled() {
    if (!defined('BUNNY_STORAGE_ZONE') || !defined('BUNNY_STORAGE_KEY')) return false;
    $zone = trim((string)BUNNY_STORAGE_ZONE);
    $key  = trim((string)BUNNY_STORAGE_KEY);
    return $zone !== '' && $key !== '';
}

function media_storage_label() {
    return bunny_storage_enabled() ? 'Bunny CDN' : 'сървър (jump.bg)';
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
    $rows = read_json(RESERVATIONS_FILE, []);
    $dirty = false;
    foreach ($rows as &$r) {
        $k = product_id_key($r['product_id'] ?? '');
        if ($k === '' || $k === '0') continue;
        if (($r['product_id'] ?? '') !== $k) {
            $r['product_id'] = $k;
            $dirty = true;
        }
    }
    unset($r);
    if ($dirty) write_reservations($rows);
    return $rows;
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
        if (product_ids_match($r['product_id'] ?? 0, $productId)) return $r;
    }
    return null;
}

function reserve_product($productId, $sessionId) {
    $sessionId = sanitize_session_id($sessionId);
    if (!product_id_valid($productId) || !$sessionId) return ['ok' => false, 'error' => 'Невалидни данни.'];

    $products = read_products();
    $product = null;
    foreach ($products as $p) {
        if (product_ids_match($p['id'] ?? 0, $productId)) { $product = $p; break; }
    }
    if (!$product || empty($product['available'])) return ['ok' => false, 'error' => 'Продуктът не е наличен.'];

    $storedId = product_id_key($product['id']);
    $rows = read_reservations();
    $existing = find_reservation($storedId, $rows);
    if ($existing && ($existing['session_id'] ?? '') !== $sessionId) {
        return ['ok' => false, 'error' => 'Резервиран от друг клиент.'];
    }

    $expires = time() + reservation_ttl_seconds();
    $found = false;
    foreach ($rows as $i => $r) {
        if (product_ids_match($r['product_id'] ?? 0, $storedId)) {
            $rows[$i] = ['product_id' => $storedId, 'session_id' => $sessionId, 'expires_at' => $expires, 'updated_at' => time()];
            $found = true;
            break;
        }
    }
    if (!$found) $rows[] = ['product_id' => $storedId, 'session_id' => $sessionId, 'expires_at' => $expires, 'updated_at' => time()];
    if (!write_reservations($rows)) return ['ok' => false, 'error' => 'Неуспешен запис на резервация. Проверете правата на admin/data/.'];
    return ['ok' => true];
}

function release_product($productId, $sessionId) {
    $sessionId = sanitize_session_id($sessionId);
    if (!product_id_valid($productId) || !$sessionId) return;

    $rows = read_reservations();
    $before = count($rows);
    $rows = array_values(array_filter($rows, fn($r) =>
        !(product_ids_match($r['product_id'] ?? 0, $productId) && ($r['session_id'] ?? '') === $sessionId)
    ));
    if (count($rows) !== $before) write_reservations($rows);
}

function release_session($sessionId) {
    $sessionId = sanitize_session_id($sessionId);
    if (!$sessionId) return;

    $rows = read_reservations();
    $before = count($rows);
    $rows = array_values(array_filter($rows, fn($r) => ($r['session_id'] ?? '') !== $sessionId));
    if (count($rows) !== $before) write_reservations($rows);
}

function sync_session_reservations($sessionId, array $productIds) {
    $sessionId = sanitize_session_id($sessionId);
    if (!$sessionId) return;
    $want = [];
    foreach ($productIds as $id) {
        $k = product_id_key($id);
        if (product_id_valid($id)) $want[$k] = $id;
    }
    $rows = read_reservations();

    foreach ($rows as $r) {
        $rk = product_id_key($r['product_id'] ?? 0);
        if (($r['session_id'] ?? '') === $sessionId && !isset($want[$rk])) {
            release_product($r['product_id'], $sessionId);
        }
    }
    foreach ($want as $pid) {
        reserve_product($pid, $sessionId);
    }
}

function clear_reservations_for_products(array $productIds) {
    $keys = [];
    foreach ($productIds as $id) {
        $k = product_id_key($id);
        if (product_id_valid($id)) $keys[$k] = true;
    }
    $rows = read_reservations();
    $rows = array_values(array_filter($rows, fn($r) => !isset($keys[product_id_key($r['product_id'] ?? 0)])));
    write_reservations($rows);
}

function reservations_public($sessionId = '') {
    $sessionId = sanitize_session_id($sessionId);
    $out = [];
    foreach (read_reservations() as $r) {
        $pid = product_id_key($r['product_id'] ?? '');
        if ($pid === '' || $pid === '0') continue;
        $out[] = [
            'product_id' => $pid,
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
        if (product_id_valid($p['id'] ?? 0)) $ids[] = $p['id'];
    }
    return array_values(array_unique($ids, SORT_REGULAR));
}

function set_products_availability(array $productIds, $available) {
    if (!$productIds) return;
    $products = read_products();
    foreach ($products as $i => $p) {
        foreach ($productIds as $pid) {
            if (product_ids_match($p['id'] ?? 0, $pid)) {
                $products[$i]['available'] = (bool)$available;
                break;
            }
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
    static $defaults = null;
    if ($defaults !== null) return $defaults;
    $file = defined('CONTENT_DEFAULTS_FILE') ? CONTENT_DEFAULTS_FILE : (ROOT_DIR . '/content.defaults.json');
    $defaults = read_json($file, [
        'contacts' => [
            'phone'      => '+359 899 518 271',
            'phone_link' => '+359899518271',
            'email'      => 'orders@funshops.net',
            'person'     => 'Недко Димитров',
            'address'    => 'Стара Загора, България',
            'hours'      => 'Пон – Нед: 09:00 – 18:00 · Онлайн 24/7',
        ],
    ]);
    return $defaults;
}

function deep_merge_content($base, $override) {
    if (!is_array($base)) return $override;
    if (!is_array($override)) return $base;
    foreach ($override as $k => $v) {
        if (is_array($v) && isset($base[$k]) && is_array($base[$k])) {
            $base[$k] = deep_merge_content($base[$k], $v);
        } else {
            $base[$k] = $v;
        }
    }
    return $base;
}

function sanitize_content_string($s) {
    $s = strip_tags((string)$s);
    $s = html_entity_decode($s, ENT_QUOTES, 'UTF-8');
    return trim(preg_replace("/\r\n?/", "\n", $s));
}

function sanitize_content_tree($data) {
    if (!is_array($data)) return sanitize_content_string($data);
    $out = [];
    foreach ($data as $k => $v) {
        if (is_array($v)) $out[$k] = sanitize_content_tree($v);
        else $out[$k] = sanitize_content_string($v);
    }
    return $out;
}

function read_content() {
    $saved = read_json(CONTENT_FILE, []);
    return deep_merge_content(default_content(), is_array($saved) ? $saved : []);
}

function write_content($data) {
    return write_json(CONTENT_FILE, sanitize_content_tree($data));
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
