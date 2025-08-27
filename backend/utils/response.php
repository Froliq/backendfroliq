<?php
/**
 * Standard API Response Utility
 * Provides consistent response formatting for all API endpoints
 */

class Response {
    
    /**
     * Send a successful response
     * 
     * @param mixed $data The data to return
     * @param string $message Optional success message
     * @param int $status HTTP status code (default: 200)
     */
    public static function success($data = null, $message = 'Success', $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        
        $response = [
            'success' => true,
            'status' => $status,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send an error response
     * 
     * @param string $message Error message
     * @param int $status HTTP status code (default: 400)
     * @param mixed $errors Optional detailed errors array
     */
    public static function error($message = 'An error occurred', $status = 400, $errors = null) {
        http_response_code($status);
        header('Content-Type: application/json');
        
        $response = [
            'success' => false,
            'status' => $status,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send a validation error response
     * 
     * @param array $errors Validation errors
     * @param string $message Optional custom message
     */
    public static function validationError($errors, $message = 'Validation failed') {
        self::error($message, 422, $errors);
    }
    
    /**
     * Send an unauthorized response
     * 
     * @param string $message Optional custom message
     */
    public static function unauthorized($message = 'Unauthorized access') {
        self::error($message, 401);
    }
    
    /**
     * Send a not found response
     * 
     * @param string $message Optional custom message
     */
    public static function notFound($message = 'Resource not found') {
        self::error($message, 404);
    }
    
    /**
     * Send a forbidden response
     * 
     * @param string $message Optional custom message
     */
    public static function forbidden($message = 'Access forbidden') {
        self::error($message, 403);
    }
    
    /**
     * Send an internal server error response
     * 
     * @param string $message Optional custom message
     */
    public static function serverError($message = 'Internal server error') {
        self::error($message, 500);
    }
    
    /**
     * Send a paginated response
     * 
     * @param array $data The paginated data
     * @param int $total Total number of records
     * @param int $page Current page
     * @param int $limit Records per page
     * @param string $message Optional success message
     */
    public static function paginated($data, $total, $page, $limit, $message = 'Success') {
        $totalPages = ceil($total / $limit);
        
        $response = [
            'success' => true,
            'status' => 200,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'current_page' => (int)$page,
                'per_page' => (int)$limit,
                'total' => (int)$total,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_previous' => $page > 1
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Set CORS headers
     */
    public static function setCorsHeaders() {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}

// Helper functions for quick responses
function success($data = null, $message = 'Success', $status = 200) {
    Response::success($data, $message, $status);
}

function error($message = 'An error occurred', $status = 400, $errors = null) {
    Response::error($message, $status, $errors);
}

function validationError($errors, $message = 'Validation failed') {
    Response::validationError($errors, $message);
}

function unauthorized($message = 'Unauthorized access') {
    Response::unauthorized($message);
}

function notFound($message = 'Resource not found') {
    Response::notFound($message);
}

function forbidden($message = 'Access forbidden') {
    Response::forbidden($message);
}

function serverError($message = 'Internal server error') {
    Response::serverError($message);
}

function paginated($data, $total, $page, $limit, $message = 'Success') {
    Response::paginated($data, $total, $page, $limit, $message);
}
?>