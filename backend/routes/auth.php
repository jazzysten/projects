<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$config = require __DIR__ . '/../config.php';
$secret = $config['jwt_secret'];

function getAuthorizationHeader() {
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER['Authorization']);
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_change_key_case($requestHeaders, CASE_LOWER);
        if (isset($requestHeaders['authorization'])) {
            $headers = trim($requestHeaders['authorization']);
        }
    }
    return $headers;
}

Flight::route('POST /api/login', function() use ($secret) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['username'], $data['password'])) {
        Flight::halt(400, json_encode(['success' => false, 'message' => 'Invalid input']));
    }

    $username = trim($data['username']);
    $password = $data['password'];

    try {
        $db = Flight::db();
        $stmt = $db->prepare('SELECT * FROM user WHERE name = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['pass'])) {
            Flight::halt(401, json_encode(['success' => false, 'message' => 'Invalid credentials']));
        }

        $payload = [
            'username' => $user['name'],
            'exp' => time() + 3600
        ];

        $jwt = JWT::encode($payload, $secret, 'HS256');

        echo json_encode(['success' => true, 'token' => $jwt]);

    } catch (Exception $e) {
        Flight::halt(500, json_encode(['success' => false, 'message' => $e->getMessage()]));
    }
});

Flight::route('GET /api/me', function() use ($secret) {
    $authHeader = getAuthorizationHeader();

    if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        Flight::halt(401, json_encode(['message' => 'No token provided']));
    }

    $token = $matches[1];

    try {
        $decoded = JWT::decode($token, new Key($secret, 'HS256'));
        echo json_encode(['username' => $decoded->username]);
    } catch (Exception $e) {
        Flight::halt(401, json_encode(['message' => 'Invalid token']));
    }
});

Flight::route('GET /api/jwt', function() use ($secret) {
    $authHeader = getAuthorizationHeader();

    if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        Flight::halt(401, json_encode(['authorized' => false, 'message' => 'No token provided']));
    }

    $token = $matches[1];

    try {
        $decoded = JWT::decode($token, new Key($secret, 'HS256'));
        echo json_encode([
            'authorized' => true,
            'username' => $decoded->username
        ]);
    } catch (Exception $e) {
        Flight::halt(401, json_encode(['authorized' => false, 'message' => 'Invalid token']));
    }
});