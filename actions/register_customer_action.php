<?php
error_reporting(E_ALL); // Report all errors, warnings, notices
ini_set('display_errors', 1); 


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Only POST requests allowed.']);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function respond($status, $message, $extra = []) {
    $payload = array_merge(['status' => $status, 'message' => $message], $extra);
    echo json_encode($payload);
    exit;
}

$raw = function($k) {
    return isset($_POST[$k]) ? trim($_POST[$k]) : '';
};

$name = htmlspecialchars($raw('name'));
$email = filter_var($raw('email'), FILTER_SANITIZE_EMAIL);
$password = $raw('password');
$confirmPassword = $raw('confirmPassword');
$country = htmlspecialchars($raw('country'));
$city = htmlspecialchars($raw('city'));

$fullPhone = htmlspecialchars($raw('full_phone_e164'));
$countryCode = htmlspecialchars($raw('country_code'));
$phoneLocal = htmlspecialchars($raw('phone_number'));
$role = isset($_POST['role']) ? (int) $_POST['role'] : 2;

if ($name === '' || $email === '' || $password === '' || $confirmPassword === '' || $country === '' || $city === '') {
    respond('error', 'Please fill all required fields (name, email, password, country, city).');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond('error', 'Invalid email address.');
}

if ($password !== $confirmPassword) {
    respond('error', 'Passwords do not match.');
}

if (!preg_match('/^(?=.*[A-Z])(?=(.*\d){3,})(?=.*[@#$%^&+=!]).{8,}$/', $password)) {
    respond('error', 'Password does not meet complexity requirements.');
}

if ($fullPhone === '' || !preg_match('/^\+\d{7,15}$/', $fullPhone)) {
    respond('error', 'Invalid or missing phone number.');
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$contact = $fullPhone ?: ($countryCode ? ($countryCode . $phoneLocal) : $phoneLocal);

$controllerPath = __DIR__ . '/../controllers/customer_controller.php';
if (!file_exists($controllerPath)) {
    respond('error', 'Server configuration error: customer controller not found.');
}

require_once $controllerPath;

if (!function_exists('register_customer_ctr')) {
    respond('error', 'Server error: registration handler not available. Please ensure controller defines register_customer_ctr().');
}

try {
    // Expect register_customer_ctr to return true/false or user id on success
    $result = register_customer_ctr($name, $email, $hashedPassword, $country, $city, $contact, $role);

    if ($result === false) {
        respond('error', 'Registration failed. Email may already be in use or data invalid.');
    }

    // success: reply with JSON (and optionally provide redirect URL)
    header("Location: ../view/login.php");
    exit;

} catch (Throwable $e) {
    respond('error', 'Exception: ' . $e->getMessage());
}
?>
