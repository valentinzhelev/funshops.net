<?php
/* =============================================================
   Помощни функции — четене/запис на JSON с заключване, отговори
   ============================================================= */
require_once __DIR__ . '/config.php';

/** Сигурно четене на JSON файл с резервна стойност. */
function read_json($file, $fallback = []) {
    if (!file_exists($file)) return $fallback;
    $fh = @fopen($file, 'r');
    if (!$fh) return $fallback;
    $data = '';
    if (flock($fh, LOCK_SH)) {
        while (!feof($fh)) $data .= fread($fh, 8192);
        flock($fh, LOCK_UN);
    }
    fclose($fh);
    $decoded = json_decode($data, true);
    return $decoded === null ? $fallback : $decoded;
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

/** Безопасно име на файл за качване. */
function safe_filename($name, $allowed_ext) {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext, true)) return null;
    return 'file_' . bin2hex(random_bytes(7)) . '.' . $ext;
}

/** Гарантира съществуване на дата файловете със стойности по подразбиране. */
function ensure_data_files() {
    if (!is_dir(DATA_DIR)) @mkdir(DATA_DIR, 0775, true);
    if (!file_exists(ORDERS_FILE))   write_json(ORDERS_FILE, []);
    if (!file_exists(VISITS_FILE))   write_json(VISITS_FILE, []);
    if (!file_exists(CATEGORIES_FILE)) write_json(CATEGORIES_FILE, []);
    if (!file_exists(SETTINGS_FILE)) write_json(SETTINGS_FILE, default_settings());
}

function default_settings() {
    return [
        'delivery' => "Доставка с куриер (Спиди / Еконт) за България — 1–3 работни дни.\nМеждународна доставка чрез Български пощи — 7–20 работни дни.",
        'payment'  => "Плащане при доставка (наложен платеж) или по банков път след уговорка.",
        'general'  => "Моят Забавен Магазин — ръчно изработени шишета с български фолклорен мотив.",
        'email_notifications' => true
    ];
}
