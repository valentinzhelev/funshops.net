<?php
/* Страница на продукт — PHP fallback (същото съдържание като product.html) */
$file = __DIR__ . '/product.html';
if (!is_file($file)) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="bg"><head><meta charset="UTF-8"><title>404</title></head><body><h1>404 — product.html липсва</h1><p>Направете Deploy HEAD Commit от cPanel Git.</p></body></html>';
    exit;
}
header('Content-Type: text/html; charset=utf-8');
readfile($file);
