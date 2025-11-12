<?php


// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(){
    // Check for either user_id or customer_id for backwards compatibility
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['customer_id'])) {
        return false;
    } else {
        return true;
    }
}


function isAdmin(){
    if (isLoggedIn()) {
        return $_SESSION['user_role'] == 1;
    }
}


if (!function_exists('ensure_session')) {
    function ensure_session()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}


if (!function_exists('s')) {
    function s($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('respond_json')) {
    function respond_json($payload, $httpCode = 200)
    {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }
}

if (!function_exists('redirect')) {
    function redirect($url)
    {
        header('Location: ' . $url);
        exit;
    }
}


if (!function_exists('csrf_token')) {
    function csrf_token()
    {
        ensure_session();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}


if (!function_exists('verify_csrf')) {
    function verify_csrf($token)
    {
        ensure_session();
        if (empty($_SESSION['csrf_token'])) return false;
        return hash_equals($_SESSION['csrf_token'], (string)$token);
    }
}


if (!function_exists('post')) {
    function post($key, $default = '')
    {
        return isset($_POST[$key]) ? s($_POST[$key]) : $default;
    }
}
