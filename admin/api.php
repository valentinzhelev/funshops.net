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
        $products = read_products();

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
    case 'products_list': {
        $products = read_products();
        if (repair_products_invalid_ids($products)) write_products($products);
        $payload = ['ok' => true, 'products' => $products];
        if (!$products) $payload['meta'] = products_file_diagnostics();
        json_response($payload);
    }

    case 'product_save': {
        if ($method !== 'POST') json_error('POST only');
        $in = read_body();
        $products = read_products();
        if (repair_products_invalid_ids($products)) write_products($products);

        $tags = $in['tags'] ?? [];
        if (is_string($tags)) $tags = array_values(array_filter(array_map('trim', explode(',', $tags))));

        $priceRaw = str_replace(',', '.', trim((string)($in['price'] ?? '')));

        $requestedId = array_key_exists('id', $in) ? $in['id'] : 0;
        $productId = product_id_valid($requestedId) ? (0 + product_id_key($requestedId)) : new_product_id();

        $item = [
            'id'          => $productId,
            'name'        => trim((string)($in['name'] ?? '')),
            'price'       => is_numeric($priceRaw) ? 0 + $priceRaw : 0,
            'images'      => array_values(array_filter((array)($in['images'] ?? []))),
            'video'       => !empty($in['video']) ? (string)$in['video'] : null,
            'video_ts'    => !empty($in['video']) ? max(0, (int)($in['video_ts'] ?? time())) : null,
            'description' => (string)($in['description'] ?? ''),
            'tags'        => $tags,
            'category'    => (string)($in['category'] ?? ''),
            'available'   => array_key_exists('available', $in) ? (bool)$in['available'] : true,
        ];
        if ($item['name'] === '') json_error('Името е задължително.');

        $oldVideo = null;
        foreach ($products as $p) {
            if (product_ids_match($p['id'] ?? 0, $item['id'])) {
                $oldVideo = !empty($p['video']) ? (string)$p['video'] : null;
                break;
            }
        }
        if ($oldVideo && empty($item['video'])) {
            clear_product_folder_videos(product_video_subdir($oldVideo));
        } elseif ($oldVideo && $oldVideo !== $item['video']) {
            delete_media_asset($oldVideo);
        }

        $found = false;
        foreach ($products as $i => $p) {
            if (product_ids_match($p['id'] ?? 0, $item['id'])) { $products[$i] = $item; $found = true; break; }
        }
        if (!$found) $products[] = $item;

        write_products($products);
        json_response(['ok' => true, 'product' => $item, 'created' => !$found]);
    }

    case 'product_delete': {
        if ($method !== 'POST') json_error('POST only');
        $id = (int)(read_body()['id'] ?? 0);
        $products = read_products();
        $products = array_values(array_filter($products, fn($p) => (int)$p['id'] !== $id));
        write_products($products);
        json_response(['ok' => true]);
    }

    case 'product_toggle': {
        if ($method !== 'POST') json_error('POST only');
        $id = (int)(read_body()['id'] ?? 0);
        $products = read_products();
        foreach ($products as $i => $p) {
            if ((int)$p['id'] === $id) { $products[$i]['available'] = empty($p['available']); break; }
        }
        write_products($products);
        json_response(['ok' => true]);
    }

    case 'product_duplicate': {
        if ($method !== 'POST') json_error('POST only');
        $id = (int)(read_body()['id'] ?? 0);
        $products = read_products();
        foreach ($products as $p) {
            if ((int)$p['id'] === $id) {
                $copy = $p;
                $copy['id'] = (int)round(microtime(true) * 1000);
                $copy['name'] = $p['name'] . ' (копие)';
                $products[] = $copy;
                write_products($products);
                json_response(['ok' => true, 'product' => $copy]);
            }
        }
        json_error('Продуктът не е намерен.', 404);
    }

    /* ---------------------- КАТЕГОРИИ ---------------------- */
    case 'categories_list': {
        $cats = read_json(CATEGORIES_FILE, []);
        if (!$cats) $cats = read_json(CATEGORIES_MIRROR, []);
        $products = read_products();
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
        $products = read_products();
        foreach ($products as $i => $p) if (($p['category'] ?? '') === $from) $products[$i]['category'] = $to;
        write_products($products);
        json_response(['ok' => true, 'categories' => $cats]);
    }

    /* ---------------------- УВОД ---------------------- */
    case 'uvod_get':
        if (!file_exists(UVOD_FILE)) {
            write_json(UVOD_FILE, ['content' => default_uvod_html()]);
        }
        json_response(['ok' => true, 'content' => (read_json(UVOD_FILE, ['content' => '']))['content'] ?? '']);

    case 'uvod_save': {
        if ($method !== 'POST') json_error('POST only');
        $content = (string)(read_body()['content'] ?? '');
        write_json(UVOD_FILE, ['content' => $content]);
        json_response(['ok' => true]);
    }

    /* ---------------------- КОНТАКТИ / САЙТ ---------------------- */
    case 'content_get':
        try {
            json_response(['ok' => true, 'content' => read_content()]);
        } catch (Throwable $e) {
            json_error('Грешка при зареждане на текстовете: ' . $e->getMessage(), 500);
        }

    case 'content_save': {
        if ($method !== 'POST') json_error('POST only');
        $in = read_body();
        unset($in['csrf']);
        $cur = read_content();
        if (isset($in['contacts']) && is_array($in['contacts'])) {
            $cur['contacts'] = deep_merge_content($cur['contacts'] ?? [], sanitize_content_tree($in['contacts']));
            unset($in['contacts']);
        }
        $sections = ['site', 'home', 'uvod', 'products', 'delivery', 'contacts_page', 'cart', 'order', 'legal', 'shop'];
        foreach ($sections as $sec) {
            if (isset($in[$sec]) && is_array($in[$sec])) {
                $cur[$sec] = deep_merge_content($cur[$sec] ?? [], sanitize_content_tree($in[$sec]));
                unset($in[$sec]);
            }
        }
        write_content($cur);
        json_response(['ok' => true, 'content' => $cur]);
    }

    /* ---------------------- ПОРЪЧКИ ---------------------- */
    case 'orders_list': {
        $orders = read_json(ORDERS_FILE, []);
        usort($orders, fn($a, $b) => ($b['created_at'] ?? 0) <=> ($a['created_at'] ?? 0));
        json_response(['ok' => true, 'orders' => $orders]);
    }

    case 'orders_poll': {
        $orders = read_json(ORDERS_FILE, []);
        usort($orders, fn($a, $b) => ($b['created_at'] ?? 0) <=> ($a['created_at'] ?? 0));
        $pending = 0;
        $latestId = 0;
        foreach ($orders as $o) {
            if (($o['status'] ?? 'pending') === 'pending') $pending++;
            $latestId = max($latestId, (int)($o['id'] ?? 0));
        }
        json_response(['ok' => true, 'pending' => $pending, 'total' => count($orders), 'latest_id' => $latestId, 'orders' => $orders]);
    }

    case 'order_status': {
        if ($method !== 'POST') json_error('POST only');
        $b = read_body();
        $id = (int)($b['id'] ?? 0);
        $status = (string)($b['status'] ?? '');
        if (!in_array($status, ['pending','fulfilled','cancelled'], true)) json_error('Невалиден статус.');
        $orders = read_json(ORDERS_FILE, []);
        $target = null;
        foreach ($orders as $i => $o) {
            if ((int)$o['id'] === $id) {
                $old = $o['status'] ?? 'pending';
                $orders[$i]['status'] = $status;
                $target = $orders[$i];
                if ($status === 'cancelled' && $old !== 'cancelled') {
                    set_products_availability(order_product_ids($target), true);
                    clear_reservations_for_products(order_product_ids($target));
                }
                if ($status === 'pending' && $old === 'cancelled') {
                    set_products_availability(order_product_ids($target), false);
                }
                break;
            }
        }
        write_json(ORDERS_FILE, $orders);
        json_response(['ok' => true]);
    }

    case 'order_delete': {
        if ($method !== 'POST') json_error('POST only');
        $id = (int)(read_body()['id'] ?? 0);
        $orders = read_json(ORDERS_FILE, []);
        $removed = null;
        $orders = array_values(array_filter($orders, function ($o) use ($id, &$removed) {
            if ((int)$o['id'] === $id) { $removed = $o; return false; }
            return true;
        }));
        if ($removed && in_array($removed['status'] ?? 'pending', ['pending', 'cancelled'], true)) {
            set_products_availability(order_product_ids($removed), true);
            clear_reservations_for_products(order_product_ids($removed));
        }
        write_json(ORDERS_FILE, $orders);
        json_response(['ok' => true]);
    }

    /* ---------------------- НАСТРОЙКИ ---------------------- */
    case 'settings_get':
        json_response([
            'ok' => true,
            'settings' => read_json(SETTINGS_FILE, default_settings()),
            'media_storage' => media_storage_label(),
            'bunny_enabled' => bunny_storage_enabled(),
        ]);

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
        $useBunny = bunny_storage_enabled();
        if ($useBunny && !function_exists('curl_init')) json_error('Сървърът няма cURL — нужен е за качване в CDN.');
        $kind = $_GET['kind'] ?? 'image';
        $allowed = $kind === 'video' ? ALLOWED_VIDEO_EXT : ALLOWED_IMAGE_EXT;
        $subdir = safe_media_subdir($_GET['subdir'] ?? '');

        if ($subdir) {
            $destDir = IMAGES_DIR . '/' . $subdir;
            $prefix  = 'images/' . $subdir . '/';
            $storageDir = 'images/' . $subdir;
        } else {
            if ($useBunny) json_error('Качване извън продукт/опаковка — използвайте редактора на продукт.');
            $destDir = $kind === 'video' ? VIDEOS_DIR : IMAGES_DIR;
            $prefix  = $kind === 'video' ? 'videos/' : 'images/';
        }
        if (!$useBunny && !is_dir($destDir)) @mkdir($destDir, 0775, true);

        $saved = [];
        $files = $_FILES['files'] ?? null;
        if (!$files) json_error('Няма файлове.');

        $names = is_array($files['name']) ? $files['name'] : [$files['name']];
        $tmps  = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
        $sizes = is_array($files['size']) ? $files['size'] : [$files['size']];

        foreach ($names as $i => $orig) {
            if (!is_uploaded_file($tmps[$i])) continue;
            $maxBytes = max_upload_bytes_for($kind);
            if ($sizes[$i] > $maxBytes) {
                if ($kind === 'video') json_error('Файлът е твърде голям за качване на сървъра.');
                $maxMb = (int)round($maxBytes / (1024 * 1024));
                json_error('Файлът е твърде голям (макс. ' . $maxMb . 'MB).');
            }
            $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) json_error('Недопустим формат: ' . htmlspecialchars($orig));

            if ($subdir && $kind === 'video') {
                if (!is_dir($destDir) && !@mkdir($destDir, 0775, true)) {
                    json_error('Папката ' . $prefix . ' не може да се създаде — проверете правата на images/.');
                }
                clear_product_folder_videos($subdir);
                $fname = '1.' . $ext;
            } elseif ($subdir) {
                $fname = $useBunny
                    ? bunny_next_image_filename($storageDir, $ext)
                    : next_image_filename($destDir, $ext);
            } else {
                $fname = safe_filename($orig, $allowed);
                if (!$fname) json_error('Недопустим формат: ' . htmlspecialchars($orig));
            }

            $relPath = $prefix . $fname;
            $destFile = $destDir . '/' . $fname;
            if ($kind === 'video' && !$useBunny && is_file($destFile)) {
                @unlink($destFile);
            }
            $ok = $useBunny
                ? bunny_upload($relPath, $tmps[$i])
                : move_uploaded_file($tmps[$i], $destFile);
            if ($ok) $saved[] = $relPath;
        }
        if (!$saved) {
            if ($useBunny) json_error('Качването в CDN неуспешно — проверете BUNNY_STORAGE_KEY.');
            if ($subdir && !is_writable($destDir)) json_error('Папката ' . $prefix . ' не може да се записва — проверете правата на images/ на сървъра.');
            json_error('Качването неуспешно.');
        }
        json_response(['ok' => true, 'paths' => $saved, 'uploaded_at' => time()]);
    }

    case 'reservations_list': {
        $rows = read_reservations();
        $names = [];
        foreach (read_products() as $p) $names[(int)$p['id']] = (string)($p['name'] ?? '');
        $out = [];
        foreach ($rows as $r) {
            $pid = (int)($r['product_id'] ?? 0);
            $exp = (int)($r['expires_at'] ?? 0);
            $out[] = [
                'product_id'   => $pid,
                'product_name' => $names[$pid] ?? ('#' . $pid),
                'expires_at'   => $exp,
                'minutes_left' => max(0, (int)ceil(($exp - time()) / 60)),
            ];
        }
        json_response(['ok' => true, 'reservations' => $out, 'count' => count($out)]);
    }

    case 'reservations_clear': {
        if ($method !== 'POST') json_error('POST only');
        write_reservations([]);
        json_response(['ok' => true, 'cleared' => true]);
    }

    case 'delete_asset': {
        if ($method !== 'POST') json_error('POST only');
        $path = safe_image_asset_path(read_body()['path'] ?? '');
        if (!$path) json_error('Невалиден път.');
        if (bunny_storage_enabled()) {
            if (!bunny_delete($path)) json_error('Изтриването от CDN неуспешно.');
            json_response(['ok' => true]);
        }
        $full = resolve_image_path($path);
        if (!$full) json_error('Файлът не е намерен.');
        @unlink($full);
        json_response(['ok' => true]);
    }

    default:
        json_error('Непознато действие: ' . htmlspecialchars($action), 404);
}
