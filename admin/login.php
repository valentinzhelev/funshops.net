<?php
require_once __DIR__ . '/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (admin_login($_POST['password'] ?? '')) {
        header('Location: index.php');
        exit;
    }
    $error = 'Грешна парола. Опитайте отново.';
}
if (admin_is_logged_in()) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход — Админ панел</title>
    <link rel="icon" type="image/png" href="../logo_bottle.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Yeseva+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body class="login-body">
    <div class="login-card">
        <img class="login-logo" src="../logo_full_ink.png" alt="Моят Забавен Магазин">
        <h1>Админ панел</h1>

        <?php if ($error): ?><div class="login-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="post" autocomplete="off">
            <label for="password">Парола</label>
            <input id="password" name="password" type="password" required autofocus placeholder="••••••••">
            <button type="submit" class="btn btn-primary btn-block">Вход</button>
        </form>
        <a href="../index.html" class="muted small back-link">← Към сайта</a>
    </div>
</body>
</html>
