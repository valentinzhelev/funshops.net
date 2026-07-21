<?php
/* =============================================================
   МОЯТ ЗАБАВЕН МАГАЗИН — конфигурация на админ панела
   ============================================================= */

/* --- Парола за вход в админ панела ---
   По подразбиране паролата е: magazin2026
   СМЕНЕТЕ Я! Генерирайте нов хеш така (в терминал):
   php -r "echo password_hash('НОВА_ПАРОЛА', PASSWORD_DEFAULT);"
   и поставете резултата по-долу.                                  */
const ADMIN_PASSWORD_HASH = '$2y$10$ImtHUYStzqQTAkMbp7r2l.05ypey9Tj73DWZszwL/GWN8p65hZIZC';
// Парола: Magazin2026!NV

/* --- Имейл за поръчки --- */
const ORDER_EMAIL_TO   = 'orders@funshops.net';
const ORDER_EMAIL_COPY = 'nedko.velikov@abv.bg'; // резервно копие
const ORDER_EMAIL_FROM = 'orders@funshops.net';
const SHOP_NAME        = 'Моят Забавен Магазин';

/* ABV SMTP — задължително за orders@funshops.net (PHP mail() не работи от jump.bg) */
const SMTP_HOST = 'appssmtp.abv.bg';
const SMTP_PORT = 465;
const SMTP_USER = 'orders@funshops.net';
const SMTP_PASS = 'Magazin2026!'; // парола на orders@funshops.net в ABV — попълнете на сървъра!

/* --- Пътища --- */
define('ADMIN_DIR', __DIR__);
define('ROOT_DIR',  dirname(__DIR__));
define('DATA_DIR',  __DIR__ . '/data');

define('PRODUCTS_FILE',     ROOT_DIR . '/products.json');
define('UVOD_FILE',         ROOT_DIR . '/uvod.json');
define('CONTENT_FILE',          ROOT_DIR . '/content.json');
define('CONTENT_DEFAULTS_FILE', ROOT_DIR . '/content.defaults.json');
define('CATEGORIES_FILE',   DATA_DIR . '/categories.json');
define('CATEGORIES_MIRROR', ROOT_DIR . '/categories.json'); // резервно копие за фронтенда
define('ORDERS_FILE',       DATA_DIR . '/orders.json');
define('VISITS_FILE',       DATA_DIR . '/visits.json');
define('SETTINGS_FILE',     DATA_DIR . '/settings.json');
define('RESERVATIONS_FILE', DATA_DIR . '/reservations.json');

define('IMAGES_DIR', ROOT_DIR . '/images');
define('VIDEOS_DIR', ROOT_DIR . '/videos');

const RESERVATION_MINUTES = 30;
const TELEGRAM_BOT_TOKEN = '';
const TELEGRAM_CHAT_ID   = '';
const NTFY_TOPIC         = '';

/* --- Разрешени файлови формати при качване --- */
const ALLOWED_IMAGE_EXT = ['png', 'jpg', 'jpeg', 'webp', 'gif'];
const ALLOWED_VIDEO_EXT = ['mp4'];
const MAX_UPLOAD_BYTES       = 60 * 1024 * 1024;  // снимки — 60 MB
const MAX_VIDEO_UPLOAD_BYTES = 100 * 1024 * 1024; // видео — 100 MB

/* Bunny CDN — оставете празно за локални снимки на jump.bg (препоръчително) */
const BUNNY_STORAGE_ZONE   = '';
const BUNNY_STORAGE_KEY    = '';
const BUNNY_STORAGE_REGION = 'de';

/* --- Часова зона --- */
date_default_timezone_set('Europe/Sofia');
