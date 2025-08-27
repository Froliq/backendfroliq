<?php
/**
 * API Settings Configuration File
 * Contains all API-wide settings and configurations
 */

class ApiSettings {
    
    // API Configuration
    const API_VERSION = 'v1';
    const API_NAME = 'Entertainment Hub API';
    
    // Environment settings
    private static $environment;
    private static $config = [];
    
    /**
     * Initialize API settings
     */
    public static function init() {
        self::$environment = $_ENV['ENVIRONMENT'] ?? 'development';
        self::loadConfig();
        self::setHeaders();
        self::setTimezone();
    }
    
    /**
     * Load configuration based on environment
     */
    private static function loadConfig() {
        $baseConfig = [
            // API Settings
            'api_url' => $_ENV['API_URL'] ?? 'http://localhost/api',
            'frontend_url' => $_ENV['FRONTEND_URL'] ?? 'http://localhost:3000',
            
            // Security Settings
            'jwt_secret' => $_ENV['JWT_SECRET'] ?? 'your-secret-key-change-this',
            'jwt_expiry' => 3600, // 1 hour in seconds
            'jwt_refresh_expiry' => 86400 * 7, // 7 days
            'password_min_length' => 8,
            'max_login_attempts' => 5,
            'lockout_time' => 900, // 15 minutes
            
            // Rate Limiting
            'rate_limit_requests' => 100,
            'rate_limit_window' => 3600, // 1 hour
            
            // File Upload Settings
            'max_file_size' => 5 * 1024 * 1024, // 5MB
            'allowed_image_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'upload_path' => 'uploads/',
            
            // Pagination
            'default_page_size' => 20,
            'max_page_size' => 100,
            
            // Cache Settings
            'cache_enabled' => true,
            'cache_ttl' => 3600, // 1 hour
            
            // Email Settings
            'smtp_host' => $_ENV['SMTP_HOST'] ?? 'localhost',
            'smtp_port' => $_ENV['SMTP_PORT'] ?? 587,
            'smtp_username' => $_ENV['SMTP_USER'] ?? '',
            'smtp_password' => $_ENV['SMTP_PASS'] ?? '',
            'from_email' => $_ENV['FROM_EMAIL'] ?? 'noreply@yoursite.com',
            'from_name' => $_ENV['FROM_NAME'] ?? 'Entertainment Hub',
            
            // API Keys (for external services)
            'tmdb_api_key' => $_ENV['TMDB_API_KEY'] ?? '',
            'google_places_key' => $_ENV['GOOGLE_PLACES_KEY'] ?? '',
            'stripe_secret_key' => $_ENV['STRIPE_SECRET_KEY'] ?? '',
            'stripe_publishable_key' => $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? '',
            
            // Logging
            'log_level' => 'info',
            'log_file' => 'logs/api.log',
            'log_max_size' => 10 * 1024 * 1024, // 10MB
        ];
        
        // Environment specific overrides
        switch (self::$environment) {
            case 'production':
                $envConfig = [
                    'debug' => false,
                    'log_level' => 'error',
                    'cache_ttl' => 7200, // 2 hours
                    'rate_limit_requests' => 200,
                ];
                break;
                
            case 'staging':
                $envConfig = [
                    'debug' => true,
                    'log_level' => 'warning',
                    'cache_ttl' => 1800, // 30 minutes
                ];
                break;
                
            default: // development
                $envConfig = [
                    'debug' => true,
                    'log_level' => 'debug',
                    'cache_ttl' => 300, // 5 minutes
                    'rate_limit_requests' => 1000, // More lenient for development
                ];
        }
        
        self::$config = array_merge($baseConfig, $envConfig);
    }
    
    /**
     * Set CORS and security headers
     */
    private static function setHeaders() {
        // CORS Headers
        $allowedOrigins = [
            'http://localhost:3000',
            'http://localhost:8000',
            self::get('frontend_url')
        ];
        
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
        }
        
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
        
        // Security Headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Type
        header('Content-Type: application/json; charset=UTF-8');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
    
    /**
     * Set timezone
     */
    private static function setTimezone() {
        $timezone = $_ENV['TIMEZONE'] ?? 'UTC';
        date_default_timezone_set($timezone);
    }
    
    /**
     * Get configuration value
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null) {
        return self::$config[$key] ?? $default;
    }
    
    /**
     * Set configuration value
     * @param string $key
     * @param mixed $value
     */
    public static function set($key, $value) {
        self::$config[$key] = $value;
    }
    
    /**
     * Get all configuration
     * @return array
     */
    public static function getAll() {
        return self::$config;
    }
    
    /**
     * Get environment
     * @return string
     */
    public static function getEnvironment() {
        return self::$environment;
    }
    
    /**
     * Check if in development mode
     * @return bool
     */
    public static function isDevelopment() {
        return self::$environment === 'development';
    }
    
    /**
     * Check if in production mode
     * @return bool
     */
    public static function isProduction() {
        return self::$environment === 'production';
    }
    
    /**
     * Get API response format
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @param array $meta
     * @return array
     */
    public static function getApiResponse($data = null, $message = 'Success', $statusCode = 200, $meta = []) {
        $response = [
            'success' => $statusCode >= 200 && $statusCode < 300,
            'message' => $message,
            'status_code' => $statusCode,
            'timestamp' => date('Y-m-d H:i:s'),
            'data' => $data
        ];
        
        if (!empty($meta)) {
            $response['meta'] = $meta;
        }
        
        if (self::isDevelopment()) {
            $response['debug'] = [
                'memory_usage' => memory_get_peak_usage(true),
                'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']
            ];
        }
        
        return $response;
    }
    
    /**
     * Get error response format
     * @param string $message
     * @param int $statusCode
     * @param array $errors
     * @return array
     */
    public static function getErrorResponse($message = 'Error', $statusCode = 400, $errors = []) {
        $response = [
            'success' => false,
            'message' => $message,
            'status_code' => $statusCode,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        return $response;
    }
    
    /**
     * Send JSON response
     * @param array $data
     * @param int $statusCode
     */
    public static function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }
}

// Auto-initialize settings
ApiSettings::init();

// Global helper functions
function getSetting($key, $default = null) {
    return ApiSettings::get($key, $default);
}

function sendApiResponse($data = null, $message = 'Success', $statusCode = 200, $meta = []) {
    $response = ApiSettings::getApiResponse($data, $message, $statusCode, $meta);
    ApiSettings::sendResponse($response, $statusCode);
}

function sendApiError($message = 'Error', $statusCode = 400, $errors = []) {
    $response = ApiSettings::getErrorResponse($message, $statusCode, $errors);
    ApiSettings::sendResponse($response, $statusCode);
}
?>