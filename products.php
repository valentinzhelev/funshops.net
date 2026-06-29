<?php
/* =============================================================
   Публичен API — списък продукти (поправя липсващи ID при нужда)
   ============================================================= */
require_once __DIR__ . '/admin/lib.php';
ensure_data_files();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$products = read_products();
if (repair_products_invalid_ids($products)) {
    write_products($products);
}

foreach ($products as $i => $p) {
    if (empty($p['video'])) continue;
    $full = resolve_image_path((string)$p['video']);
    if (!$full || !is_file($full)) continue;
    $mtime = (int)filemtime($full);
    $stored = (int)($p['video_ts'] ?? 0);
    if ($mtime > $stored) {
        $products[$i]['video_ts'] = $mtime;
    } elseif ($stored > 0) {
        $products[$i]['video_ts'] = $stored;
    } else {
        $products[$i]['video_ts'] = $mtime;
    }
}

echo json_encode($products, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
