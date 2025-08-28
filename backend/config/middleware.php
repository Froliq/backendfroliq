<?php
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../utils/response.php';

/**
 * Middleware to protect routes with JWT authentication
 */
function authMiddleware() {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Authorization header missing']);
        exit;
    }

    $authHeader = $headers['Authorization'];
    $token = str_replace('Bearer ', '', $authHeader);

    $decoded = verifyJWT($token);
    if (!$decoded) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid or expired token']);
        exit;
    }

    // Return decoded token (contains user id etc.)
    return $decoded;
}
