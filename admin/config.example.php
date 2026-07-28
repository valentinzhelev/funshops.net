<?php
/* =============================================================
   МОЯТ ЗАБАВЕН МАГАЗИН — конфигурация на админ панела
   Копирайте на сървъра като admin/config.php (не се deploy-ва от Git)
   ============================================================= */

const ADMIN_PASSWORD_HASH = '$2y$10$ImtHUYStzqQTAkMbp7r2l.05ypey9Tj73DWZszwL/GWN8p65hZIZC';
// Парола: Magazin2026!NV

const ORDER_EMAIL_TO   = 'orders@funshops.net';
const ORDER_EMAIL_COPY = 'nedko.velikov@abv.bg';
const ORDER_EMAIL_FROM = 'orders@funshops.net';
const SHOP_NAME        = 'Моят Забавен Магазин';

/* ABV SMTP — appssmtp.abv.bg:465, потребител orders@funshops.net + парола от ABV */
const SMTP_HOST = 'appssmtp.abv.bg';
const SMTP_PORT = 465;
const SMTP_USER = 'orders@funshops.net';
const SMTP_PASS = '';

/* Резервация в количка (минути) */
const RESERVATION_MINUTES = 30;

/* Известия на телефон — попълнете едно от двете (или и двете) */
const TELEGRAM_BOT_TOKEN = '';  /* @BotFather → нов бот → токен */
const TELEGRAM_CHAT_ID   = '';  /* вашият chat id (число) */
const NTFY_TOPIC         = '';  /* ntfy.sh — абонирайте се в приложението */

define('ADMIN_DIR', __DIR__);
define('ROOT_DIR',  dirname(__DIR__));
define('DATA_DIR',  __DIR__ . '/data');

define('PRODUCTS_FILE',     ROOT_DIR . '/products.json');
define('UVOD_FILE',         ROOT_DIR . '/uvod.json');
define('CONTENT_FILE',          ROOT_DIR . '/content.json');
define('CONTENT_DEFAULTS_FILE', ROOT_DIR . '/content.defaults.json');
define('CATEGORIES_FILE',   DATA_DIR . '/categories.json');
define('CATEGORIES_MIRROR', ROOT_DIR . '/categories.json');
define('ORDERS_FILE',        DATA_DIR . '/orders.json');
define('VISITS_FILE',        DATA_DIR . '/visits.json');
define('SETTINGS_FILE',      DATA_DIR . '/settings.json');
define('RESERVATIONS_FILE',  DATA_DIR . '/reservations.json');

define('IMAGES_DIR', ROOT_DIR . '/images');
define('VIDEOS_DIR', ROOT_DIR . '/videos');

const ALLOWED_IMAGE_EXT = ['png', 'jpg', 'jpeg', 'webp', 'gif'];
const ALLOWED_VIDEO_EXT = ['mp4', 'm4v', 'mov', 'webm', 'avi', 'mkv', '3gp'];
const MAX_UPLOAD_BYTES       = 60 * 1024 * 1024;   // снимки — 60 MB
const MAX_VIDEO_UPLOAD_BYTES = 200 * 1024 * 1024; // видео — 200 MB

/* Bunny — оставете празно за локални снимки на jump.bg */
const BUNNY_STORAGE_ZONE   = '';
const BUNNY_STORAGE_KEY    = '';
const BUNNY_STORAGE_REGION = 'de';

date_default_timezone_set('Europe/Sofia');
