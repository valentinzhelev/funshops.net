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

echo json_encode($products, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
