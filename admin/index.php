<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/lib.php';
admin_require(false);
ensure_data_files();
$csrf = csrf_token();
$assetVer = max(
    (int)@filemtime(__DIR__ . '/admin.js'),
    (int)@filemtime(__DIR__ . '/admin.css'),
    (int)@filemtime(__DIR__ . '/content-schema.js')
);
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ панел — Моят Забавен Магазин</title>
    <link rel="icon" type="image/png" href="../images/logo_bottle.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Yeseva+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css?v=<?= $assetVer ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="layout">
    <!-- Сайдбар -->
    <aside class="sidebar" id="sidebar">
        <div class="side-brand">
            <img class="side-logo" src="../images/logo_full.png" alt="Моят Забавен Магазин">
            <span class="side-tag">Админ панел</span>
        </div>

        <nav class="side-nav">
            <a href="#dashboard" class="nav-item active" data-view="dashboard"><span class="ni" data-i="grid"></span>Табло</a>
            <a href="#products" class="nav-item" data-view="products"><span class="ni" data-i="box"></span>Продукти</a>
            <a href="#categories" class="nav-item" data-view="categories"><span class="ni" data-i="tag"></span>Категории</a>
            <a href="#orders" class="nav-item" data-view="orders"><span class="ni" data-i="cart"></span>Поръчки <span class="nav-badge" id="ordBadge" hidden>0</span></a>
            <a href="#settings" class="nav-item" data-view="settings"><span class="ni" data-i="cog"></span>Настройки</a>
        </nav>

        <div class="side-foot">
            <a href="../index.html" target="_blank" class="btn btn-outline btn-block"><span class="ni" data-i="globe"></span>Виж магазина</a>
            <a href="logout.php" class="btn btn-danger btn-block">Изход</a>
        </div>
    </aside>

    <div class="scrim" id="sideScrim"></div>

    <!-- Основна част -->
    <main class="main">
        <header class="topbar">
            <button class="icon-btn menu-btn" id="menuBtn" aria-label="Меню"><span class="ni" data-i="menu"></span></button>
            <h1 id="viewTitle">Табло</h1>
            <div class="topbar-right">
                <span class="today" id="todayLabel"></span>
            </div>
        </header>

        <div class="content" id="content"><div class="loader">Зареждане…</div></div>
    </main>
</div>

<!-- Модал -->
<div class="modal-scrim" id="modalScrim">
    <div class="modal" id="modal" role="dialog" aria-modal="true">
        <div class="modal-head"><h2 id="modalTitle"></h2><button class="icon-btn" id="modalClose"><span class="ni" data-i="x"></span></button></div>
        <div class="modal-body" id="modalBody"></div>
    </div>
</div>

<!-- Toast -->
<div class="toast-wrap" id="toastWrap"></div>

<script src="../config.js"></script>
<script>window.CSRF = <?= json_encode($csrf) ?>;</script>
<script src="content-schema.js?v=<?= $assetVer ?>"></script>
<script src="admin.js?v=<?= $assetVer ?>"></script>
</body>
</html>
