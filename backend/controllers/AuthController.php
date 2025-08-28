<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../utils/response.php';

class AuthController {
    /**
     * Handle user registration
     */
    public function register($data) {
        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            return jsonResponse(['error' => 'Missing required fields'], 400);
        }

        $user = new User();
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

        $newUser = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $hashedPassword
        ];

        if ($user->create($newUser)) {
            return jsonResponse(['message' => 'User registered successfully']);
        }
        return jsonResponse(['error' => 'Registration failed'], 400);
    }

    /**
     * Handle user login
     */
    public function login($data) {
        if (empty($data['email']) || empty($data['password'])) {
            return jsonResponse(['error' => 'Missing email or password'], 400);
        }

        $user = new User();
        $authUser = $user->findByEmail($data['email']);

        if ($authUser && password_verify($data['password'], $authUser['password'])) {
            $token = generateJWT($authUser['id']);
            return jsonResponse([
                'message' => 'Login successful',
                'token'   => $token,
                'user'    => [
                    'id' => $authUser['id'],
                    'name' => $authUser['name'],
                    'email' => $authUser['email']
                ]
            ]);
        }

        return jsonResponse(['error' => 'Invalid credentials'], 401);
    }
}
