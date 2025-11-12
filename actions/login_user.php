<?php
// login_customer_action.php
header('Content-Type: application/json');
session_start();

require_once '../controllers/user_controller.php'; // adjust path if needed

function send_json($payload) {
    echo json_encode($payload);
    exit;
}

// Check if POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['status' => 'error', 'message' => 'Invalid request']);
}

// Grab inputs
$email    = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Validate
if (empty($email) || empty($password)) {
    send_json(['status' => 'error', 'message' => 'Email and password are required']);
}

// Call controller
$user = login_customer_ctr($email, $password);

if ($user === false) {
    send_json(['status' => 'error', 'message' => 'Invalid email or password']);
}

// Success – session variables should already be set by loginCustomer(),
// but we'll ensure important ones are there
$_SESSION['user_id']      = $user['customer_id'];
$_SESSION['customer_id']  = $user['customer_id']; // Also set customer_id for cart compatibility
$_SESSION['user_email']   = $user['customer_email'];
$_SESSION['user_name']    = $user['customer_name'];
$_SESSION['user_role']    = $user['user_role'];
$_SESSION['user_image']   = $user['customer_image'];

send_json([
    'status'  => 'success',
    'message' => 'Login successful',
    'user'    => [
        'id'    => $user['customer_id'],
        'name'  => $user['customer_name'],
        'email' => $user['customer_email'],
        'role'  => $user['user_role']
    ]
]);
