<?php
/* Потоково обслужване на видео с правилен MIME и Range поддръжка. */
require_once __DIR__ . '/admin/lib.php';

$rel = safe_product_video_path($_GET['f'] ?? '');
if (!$rel) {
    http_response_code(404);
    exit;
}

$ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
$mime = video_mime_for_ext($ext);

$full = resolve_image_path($rel);
if (!$full) {
    http_response_code(404);
    exit;
}

stream_video_file($full, $mime);
