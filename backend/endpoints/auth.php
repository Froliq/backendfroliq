<?php
require_once __DIR__ . '/../controllers/AuthController.php';

header("Content-Type: application/json");
$method = $_SERVER['REQUEST_METHOD'];
$authController = new AuthController();

switch ($method) {
    case 'POST':
        $path = $_GET['action'] ?? '';

        $input = json_decode(file_get_contents("php://input"), true);

        if ($path === 'register') {
            echo $authController->register($input);
        } elseif ($path === 'login') {
            echo $authController->login($input);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Invalid endpoint']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
