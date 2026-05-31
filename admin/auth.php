<?php
/* =============================================================
   Сесийна автентикация за админ панела
   ============================================================= */
require_once __DIR__ . '/config.php';

function admin_session_start() {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function admin_is_logged_in() {
    admin_session_start();
    return !empty($_SESSION['admin_ok']);
}

function admin_login($password) {
    admin_session_start();
    if (password_verify($password, ADMIN_PASSWORD_HASH)) {
        session_regenerate_id(true);
        $_SESSION['admin_ok'] = true;
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
        return true;
    }
    return false;
}

function admin_logout() {
    admin_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Изисква вход; пренасочва уеб заявки към login, връща JSON за API. */
function admin_require($asApi = false) {
    if (!admin_is_logged_in()) {
        if ($asApi) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Нямате достъп. Моля, влезте отново.']);
            exit;
        }
        header('Location: login.php');
        exit;
    }
}

function csrf_token() {
    admin_session_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

function csrf_check($token) {
    admin_session_start();
    return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$token);
}
