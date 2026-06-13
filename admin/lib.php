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

/** Гарантира съществуване на дата файловете със стойности по подразбиране. */
function ensure_data_files() {
    if (!is_dir(DATA_DIR)) @mkdir(DATA_DIR, 0775, true);
    if (!file_exists(ORDERS_FILE))   write_json(ORDERS_FILE, []);
    if (!file_exists(VISITS_FILE))   write_json(VISITS_FILE, []);
    if (!file_exists(CATEGORIES_FILE)) write_json(CATEGORIES_FILE, []);
    if (!file_exists(SETTINGS_FILE)) write_json(SETTINGS_FILE, default_settings());
    if (!file_exists(CONTENT_FILE)) write_content(default_content());
    if (!file_exists(UVOD_FILE)) write_json(UVOD_FILE, ['content' => default_uvod_html()]);
}

function default_settings() {
    return [
        'delivery' => "Доставка с куриер (Спиди / Еконт) за България — 1–3 работни дни.\nМеждународна доставка чрез Български пощи — 7–20 работни дни.",
        'payment'  => "Плащане при доставка (наложен платеж) или по банков път след уговорка.",
        'general'  => "Моят Забавен Магазин — ръчно изработени шишета с български фолклорен мотив.",
        'email_notifications' => true
    ];
}

function default_content() {
    return [
        'contacts' => [
            'phone'      => '+359 899 518 271',
            'phone_link' => '+359899518271',
            'email'      => 'nedko.velikov@abv.bg',
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
    return <<<'HTML'
<p class="lead-line reveal">Изработвам подаръчни шишета с български фолклорен мотив.</p>
<p class="reveal">Подходящи за много поводи:</p>
<div class="occasions">
    <span class="chip red reveal">Рожден ден</span>
    <span class="chip green reveal d1">Имен ден</span>
    <span class="chip amber reveal d2">Семеен подарък</span>
    <span class="chip red reveal d3">Украса за механи и заведения</span>
</div>
<div class="divider" style="margin:30px 0"><span>✦</span></div>
<p class="reveal">Изработени са изцяло чисто — без лепила или други смоли. Единствено точно измерване и прецизно сглобяване.</p>
<p class="reveal">Поставени в шишето, фигурите са здраво стегнати и заклинени. След това се измиват и пълнят с алкохол (ракия). Не плават, не мърдат, а след измиване и закисване е невъзможно да се разглобят.</p>
<p class="reveal">Дървото придава цвят на ракията — дори да се сипе бяла ракия (за предпочитане), след време тя придобива характерен жълт цвят.</p>
<p class="reveal">Шишетата се продават сухи и неизмити, за да може да се правят корекции. Но по ваше желание могат да са измити и подготвени. След получаване се измиват с вода от прашинки и се закисват с вода или ракия.</p>
<p class="reveal">Шишето се изработва изцяло ръчно. Отнема много време за цялата изработка, така че моля да оцените този труд. Цената за всяко е различна спрямо трудността на изработка.</p>
<p class="reveal">За въпроси относно наличности, допълнителни снимки или цени — моля, изпратете съобщение. Разгледайте снимките и ако харесате нещо, пишете.</p>
<p class="lead-line reveal" style="margin-top:26px">Очаквам вашите поръчки и благодаря за отделеното време!</p>
<div style="text-align:center; margin-top:34px" class="reveal">
    <a href="products.html" class="btn btn-amber">Разгледай продуктите</a>
    <a href="contacts.html" class="btn btn-ghost">Свържи се с мен</a>
</div>
HTML;
}
