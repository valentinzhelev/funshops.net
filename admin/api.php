<?php
/* =============================================================
   АДМИН API — всички действия минават оттук (?action=...)
   GET = четене, POST = запис (със CSRF защита)
   ============================================================= */
require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/auth.php';

ensure_data_files();
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

/* Всички API заявки изискват вход */
admin_require(true);

/* CSRF за всички записи */
if ($method === 'POST') {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf'] ?? '');
    if (!csrf_check($token)) json_error('Невалиден CSRF токен. Презаредете страницата.', 419);
}

switch ($action) {

    /* ---------------------- СТАТИСТИКА ---------------------- */
    case 'stats': {
        $visits = read_json(VISITS_FILE, []);
        $orders = read_json(ORDERS_FILE, []);
        $products = read_json(PRODUCTS_FILE, []);

        // Дневни посещения (date => count)
        $vDaily = [];
        foreach ($visits as $v) {
            $d = $v['date'] ?? date('Y-m-d', $v['ts'] ?? time());
            $vDaily[$d] = ($vDaily[$d] ?? 0) + 1;
        }

        // Дневни поръчки + разбивка по статус
        $oDaily = [];
        $kpi = ['total' => 0, 'pending' => 0, 'fulfilled' => 0, 'cancelled' => 0,
                'revenue_fulfilled' => 0, 'revenue_all' => 0];
        foreach ($orders as $o) {
            $d = date('Y-m-d', $o['created_at'] ?? time());
            if (!isset($oDaily[$d])) $oDaily[$d] = ['total'=>0,'pending'=>0,'fulfilled'=>0,'cancelled'=>0,'revenue'=>0];
            $st = $o['status'] ?? 'pending';
            $oDaily[$d]['total']++;
            $oDaily[$d][$st] = ($oDaily[$d][$st] ?? 0) + 1;
            $oDaily[$d]['revenue'] += (float)($o['total'] ?? 0);
            $kpi['total']++;
            $kpi[$st] = ($kpi[$st] ?? 0) + 1;
            $kpi['revenue_all'] += (float)($o['total'] ?? 0);
            if ($st === 'fulfilled') $kpi['revenue_fulfilled'] += (float)($o['total'] ?? 0);
        }

        // KPI за посещения
        $today = date('Y-m-d');
        $vToday = $vDaily[$today] ?? 0;
        $vWeek = 0; $vMonth = 0; $vYear = 0;
        for ($i = 0; $i < 7; $i++)   $vWeek  += $vDaily[date('Y-m-d', strtotime("-$i day"))] ?? 0;
        for ($i = 0; $i < 30; $i++)  $vMonth += $vDaily[date('Y-m-d', strtotime("-$i day"))] ?? 0;
        for ($i = 0; $i < 365; $i++) $vYear  += $vDaily[date('Y-m-d', strtotime("-$i day"))] ?? 0;

        $avail = 0;
        foreach ($products as $p) if (!empty($p['available'])) $avail++;

        json_response([
            'ok' => true,
            'visits_daily' => $vDaily,
            'orders_daily' => $oDaily,
            'kpi' => $kpi,
            'visits_kpi' => ['today'=>$vToday, 'week'=>$vWeek, 'month'=>$vMonth, 'year'=>$vYear],
            'products' => ['total' => count($products), 'available' => $avail],
        ]);
    }

    /* ---------------------- ПРОДУКТИ ---------------------- */
    case 'products_list':
        json_response(['ok' => true, 'products' => read_json(PRODUCTS_FILE, [])]);

    case 'product_save': {
        if ($method !== 'POST') json_error('POST only');
        $in = read_body();
        $products = read_json(PRODUCTS_FILE, []);

        $tags = $in['tags'] ?? [];
        if (is_string($tags)) $tags = array_values(array_filter(array_map('trim', explode(',', $tags))));

        $item = [
            'id'          => (int)($in['id'] ?? round(microtime(true) * 1000)),
            'name'        => trim((string)($in['name'] ?? '')),
            'price'       => is_numeric($in['price'] ?? null) ? 0 + $in['price'] : 0,
            'images'      => array_values(array_filter((array)($in['images'] ?? []))),
            'video'       => $in['video'] ?? '',
            'description' => (string)($in['description'] ?? ''),
            'tags'        => $tags,
            'category'    => (string)($in['category'] ?? ''),
            'available'   => array_key_exists('available', $in) ? (bool)$in['available'] : true,
        ];
        if ($item['name'] === '') json_error('Името е задължително.');

        $found = false;
        foreach ($products as $i => $p) {
            if ((int)$p['id'] === $item['id']) { $products[$i] = $item; $found = true; break; }
        }
        if (!$found) $products[] = $item;

        write_json(PRODUCTS_FILE, $products);
        json_response(['ok' => true, 'product' => $item, 'created' => !$found]);
    }

    case 'product_delete': {
        if ($method !== 'POST') json_error('POST only');
        $id = (int)(read_body()['id'] ?? 0);
        $products = read_json(PRODUCTS_FILE, []);
        $products = array_values(array_filter($products, fn($p) => (int)$p['id'] !== $id));
        write_json(PRODUCTS_FILE, $products);
        json_response(['ok' => true]);
    }

    case 'product_toggle': {
        if ($method !== 'POST') json_error('POST only');
        $id = (int)(read_body()['id'] ?? 0);
        $products = read_json(PRODUCTS_FILE, []);
        foreach ($products as $i => $p) {
            if ((int)$p['id'] === $id) { $products[$i]['available'] = empty($p['available']); break; }
        }
        write_json(PRODUCTS_FILE, $products);
        json_response(['ok' => true]);
    }

    case 'product_duplicate': {
        if ($method !== 'POST') json_error('POST only');
        $id = (int)(read_body()['id'] ?? 0);
        $products = read_json(PRODUCTS_FILE, []);
        foreach ($products as $p) {
            if ((int)$p['id'] === $id) {
                $copy = $p;
                $copy['id'] = (int)round(microtime(true) * 1000);
                $copy['name'] = $p['name'] . ' (копие)';
                $products[] = $copy;
                write_json(PRODUCTS_FILE, $products);
                json_response(['ok' => true, 'product' => $copy]);
            }
        }
        json_error('Продуктът не е намерен.', 404);
    }

    /* ---------------------- КАТЕГОРИИ ---------------------- */
    case 'categories_list': {
        $cats = read_json(CATEGORIES_FILE, []);
        if (!$cats) $cats = read_json(CATEGORIES_MIRROR, []);
        $products = read_json(PRODUCTS_FILE, []);
        $counts = [];
        foreach ($products as $p) if (!empty($p['available'])) {
            $c = $p['category'] ?? '';
            $counts[$c] = ($counts[$c] ?? 0) + 1;
        }
        json_response(['ok' => true, 'categories' => $cats, 'counts' => $counts]);
    }

    case 'category_add': {
        if ($method !== 'POST') json_error('POST only');
        $name = trim((string)(read_body()['name'] ?? ''));
        if ($name === '') json_error('Празно име на категория.');
        $cats = read_json(CATEGORIES_FILE, []);
        if (!in_array($name, $cats, true)) $cats[] = $name;
        write_json(CATEGORIES_FILE, $cats);
        write_json(CATEGORIES_MIRROR, $cats);
        json_response(['ok' => true, 'categories' => $cats]);
    }

    case 'category_delete': {
        if ($method !== 'POST') json_error('POST only');
        $name = (string)(read_body()['name'] ?? '');
        $cats = read_json(CATEGORIES_FILE, []);
        $cats = array_values(array_filter($cats, fn($c) => $c !== $name));
        write_json(CATEGORIES_FILE, $cats);
        write_json(CATEGORIES_MIRROR, $cats);
        json_response(['ok' => true, 'categories' => $cats]);
    }

    case 'category_rename': {
        if ($method !== 'POST') json_error('POST only');
        $b = read_body();
        $from = (string)($b['from'] ?? ''); $to = trim((string)($b['to'] ?? ''));
        if ($to === '') json_error('Празно име.');
        $cats = read_json(CATEGORIES_FILE, []);
        foreach ($cats as $i => $c) if ($c === $from) $cats[$i] = $to;
        write_json(CATEGORIES_FILE, $cats);
        write_json(CATEGORIES_MIRROR, $cats);
        // обновяваме продуктите
        $products = read_json(PRODUCTS_FILE, []);
        foreach ($products as $i => $p) if (($p['category'] ?? '') === $from) $products[$i]['category'] = $to;
        write_json(PRODUCTS_FILE, $products);
        json_response(['ok' => true, 'categories' => $cats]);
    }

    /* ---------------------- УВОД ---------------------- */
    case 'uvod_get':
        json_response(['ok' => true, 'content' => (read_json(UVOD_FILE, ['content' => '']))['content'] ?? '']);

    case 'uvod_save': {
        if ($method !== 'POST') json_error('POST only');
        $content = (string)(read_body()['content'] ?? '');
        write_json(UVOD_FILE, ['content' => $content]);
        json_response(['ok' => true]);
    }

    /* ---------------------- ПОРЪЧКИ ---------------------- */
    case 'orders_list': {
        $orders = read_json(ORDERS_FILE, []);
        usort($orders, fn($a, $b) => ($b['created_at'] ?? 0) <=> ($a['created_at'] ?? 0));
        json_response(['ok' => true, 'orders' => $orders]);
    }

    case 'order_status': {
        if ($method !== 'POST') json_error('POST only');
        $b = read_body();
        $id = (int)($b['id'] ?? 0); $status = (string)($b['status'] ?? '');
        if (!in_array($status, ['pending','fulfilled','cancelled'], true)) json_error('Невалиден статус.');
        $orders = read_json(ORDERS_FILE, []);
        foreach ($orders as $i => $o) if ((int)$o['id'] === $id) $orders[$i]['status'] = $status;
        write_json(ORDERS_FILE, $orders);
        json_response(['ok' => true]);
    }

    case 'order_delete': {
        if ($method !== 'POST') json_error('POST only');
        $id = (int)(read_body()['id'] ?? 0);
        $orders = read_json(ORDERS_FILE, []);
        $orders = array_values(array_filter($orders, fn($o) => (int)$o['id'] !== $id));
        write_json(ORDERS_FILE, $orders);
        json_response(['ok' => true]);
    }

    /* ---------------------- НАСТРОЙКИ ---------------------- */
    case 'settings_get':
        json_response(['ok' => true, 'settings' => read_json(SETTINGS_FILE, default_settings())]);

    case 'settings_save': {
        if ($method !== 'POST') json_error('POST only');
        $in = read_body();
        $cur = read_json(SETTINGS_FILE, default_settings());
        foreach (['delivery','payment','general'] as $k)
            if (array_key_exists($k, $in)) $cur[$k] = (string)$in[$k];
        if (array_key_exists('email_notifications', $in)) $cur['email_notifications'] = (bool)$in['email_notifications'];
        write_json(SETTINGS_FILE, $cur);
        json_response(['ok' => true, 'settings' => $cur]);
    }

    case 'clear_cache': {
        if ($method !== 'POST') json_error('POST only');
        // Просто връщаме нов "версионен" печат; фронтендът ползва ?nocache=time()
        json_response(['ok' => true, 'version' => time()]);
    }

    /* ---------------------- КАЧВАНЕ НА ФАЙЛОВЕ ---------------------- */
    case 'upload': {
        if ($method !== 'POST') json_error('POST only');
        $kind = $_GET['kind'] ?? 'image';
        $allowed = $kind === 'video' ? ALLOWED_VIDEO_EXT : ALLOWED_IMAGE_EXT;
        $destDir = $kind === 'video' ? VIDEOS_DIR : IMAGES_DIR;
        $prefix  = $kind === 'video' ? 'videos/' : 'images/';
        if (!is_dir($destDir)) @mkdir($destDir, 0775, true);

        $saved = [];
        $files = $_FILES['files'] ?? null;
        if (!$files) json_error('Няма файлове.');

        $names = is_array($files['name']) ? $files['name'] : [$files['name']];
        $tmps  = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
        $sizes = is_array($files['size']) ? $files['size'] : [$files['size']];

        foreach ($names as $i => $orig) {
            if (!is_uploaded_file($tmps[$i])) continue;
            if ($sizes[$i] > MAX_UPLOAD_BYTES) json_error('Файлът е твърде голям (макс. 60MB).');
            $fname = safe_filename($orig, $allowed);
            if (!$fname) json_error('Недопустим формат: ' . htmlspecialchars($orig));
            if (move_uploaded_file($tmps[$i], $destDir . '/' . $fname)) {
                $saved[] = $prefix . $fname;
            }
        }
        if (!$saved) json_error('Качването неуспешно.');
        json_response(['ok' => true, 'paths' => $saved]);
    }

    case 'delete_asset': {
        if ($method !== 'POST') json_error('POST only');
        $path = (string)(read_body()['path'] ?? '');
        // Само в рамките на images/ или videos/
        $path = ltrim($path, '/');
        if (!preg_match('#^(images|videos)/[A-Za-z0-9._-]+$#', $path)) json_error('Невалиден път.');
        $full = ROOT_DIR . '/' . $path;
        if (is_file($full)) @unlink($full);
        json_response(['ok' => true]);
    }

    default:
        json_error('Непознато действие: ' . htmlspecialchars($action), 404);
}
