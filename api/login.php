<?php

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/config/users.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);

    exit;
}

$username = trim($_POST['username'] ?? '');
$pin = $_POST['pin'] ?? '';

if (
    $username === '' ||
    !is_string($pin) ||
    !preg_match('/^\d{4}$/', $pin)
) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid username or PIN'
    ]);

    exit;
}

foreach (getUsers() as $user) {

    if (
        ($user['username'] ?? null) === $username &&
        ($user['active'] ?? false) &&
        isset($user['password']) &&
        password_verify($pin, $user['password'])
    ) {

        session_regenerate_id(true);

        $_SESSION['logged_in'] = true;
        $_SESSION['logged_in_as'] = $user['username'];

        echo json_encode([
            'success' => true,
            'redirect' => '../index.php'
        ]);

        exit;
    }
}

http_response_code(401);

echo json_encode([
    'success' => false,
    'message' => 'Incorrect PIN'
]);

exit;