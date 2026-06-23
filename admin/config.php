<?php
/* =============================================================
   МОЯТ ЗАБАВЕН МАГАЗИН — конфигурация на админ панела
   ============================================================= */

/* --- Парола за вход в админ панела ---
   По подразбиране паролата е: magazin2026
   СМЕНЕТЕ Я! Генерирайте нов хеш така (в терминал):
   php -r "echo password_hash('НОВА_ПАРОЛА', PASSWORD_DEFAULT);"
   и поставете резултата по-долу.                                  */
const ADMIN_PASSWORD_HASH = '$2y$10$MCVWh3li/o4dY9IpW9Vu.uva0olWbxWLOSriEx84lqtb0h1Umc0cO';

/* --- Имейл за поръчки --- */
const ORDER_EMAIL_TO   = 'orders@funshops.net';
const ORDER_EMAIL_FROM = 'orders@funshops.net';
const SHOP_NAME        = 'Моят Забавен Магазин';

/* --- Пътища --- */
define('ADMIN_DIR', __DIR__);
define('ROOT_DIR',  dirname(__DIR__));
define('DATA_DIR',  __DIR__ . '/data');

define('PRODUCTS_FILE',     ROOT_DIR . '/products.json');
define('UVOD_FILE',         ROOT_DIR . '/uvod.json');
define('CONTENT_FILE',      ROOT_DIR . '/content.json');
define('CATEGORIES_FILE',   DATA_DIR . '/categories.json');
define('CATEGORIES_MIRROR', ROOT_DIR . '/categories.json'); // резервно копие за фронтенда
define('ORDERS_FILE',       DATA_DIR . '/orders.json');
define('VISITS_FILE',       DATA_DIR . '/visits.json');
define('SETTINGS_FILE',     DATA_DIR . '/settings.json');

define('IMAGES_DIR', ROOT_DIR . '/images');
define('VIDEOS_DIR', ROOT_DIR . '/videos');

/* --- Разрешени файлови формати при качване --- */
const ALLOWED_IMAGE_EXT = ['png', 'jpg', 'jpeg', 'webp', 'gif'];
const ALLOWED_VIDEO_EXT = ['mp4', 'webm', 'mov'];
const MAX_UPLOAD_BYTES   = 60 * 1024 * 1024; // 60 MB

/* --- Часова зона --- */
date_default_timezone_set('Europe/Sofia');
