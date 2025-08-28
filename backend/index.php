<!-- <?php

//header("Access-Control-Allow-Origin: *");
//header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
//header("Access-Control-Allow-Headers: Content-Type, Authorization");
//echo "PHP server is working!";

?> -->

<?php
/**
 * Main API Entry Point for Froliq Entertainment Platform
 * Routes all API requests to appropriate handlers
 */

// Error reporting (adjust for production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Start session
session_start();

// Load environment variables
require_once __DIR__ . '/vendor/autoload.php';

// Use Dotenv if available
if (class_exists('Dotenv\Dotenv')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Include configuration files
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/settings.php';
require_once __DIR__ . '/config/middleware.php';

// Include utility files
require_once __DIR__ . '/utils/auth.php';
require_once __DIR__ . '/utils/response.php';
require_once __DIR__ . '/utils/validation.php';

// Include API route handlers
require_once __DIR__ . '/api/routes/auth.php';
require_once __DIR__ . '/api/routes/users.php';
require_once __DIR__ . '/api/routes/movies.php';
require_once __DIR__ . '/api/routes/events.php';
require_once __DIR__ . '/api/routes/restaurants.php';
require_once __DIR__ . '/api/routes/venues.php';
require_once __DIR__ . '/api/routes/bookings.php';
require_once __DIR__ . '/api/routes/reviews.php';
require_once __DIR__ . '/api/routes/payments.php';
require_once __DIR__ . '/api/routes/search.php';
require_once __DIR__ . '/api/routes/admin.php';

/**
 * Simple router class
 */
class Router {
    private $routes = [];
    
    public function addRoute($method, $pattern, $handler) {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler
        ];
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove base path if API is in subdirectory
        $basePath = '/api';
        if (strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }
        
        // Find matching route
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            // Convert route pattern to regex
            $pattern = preg_replace('/\{([^}]+)\}/', '(?P<$1>[^/]+)', $route['pattern']);
            $pattern = '#^' . $pattern . '$#';
            
            if (preg_match($pattern, $uri, $matches)) {
                // Extract parameters
                $params = [];
                foreach ($matches as $key => $value) {
                    if (!is_numeric($key)) {
                        $params[$key] = $value;
                    }
                }
                
                // Call the handler
                call_user_func($route['handler'], $params);
                return;
            }
        }
        
        // No route found
        http_response_code(404);
        sendApiError('Endpoint not found', 404);
    }
}

// Create router instance
$router = new Router();

// Health check endpoint
$router->addRoute('GET', '/health', function() {
    sendApiResponse([
        'status' => 'OK',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => ApiSettings::API_VERSION,
        'environment' => ApiSettings::getEnvironment()
    ]);
});

// API Information endpoint
$router->addRoute('GET', '/', function() {
    sendApiResponse([
        'name' => ApiSettings::API_NAME,
        'version' => ApiSettings::API_VERSION,
        'endpoints' => [
            'auth' => '/auth',
            'users' => '/users',
            'movies' => '/movies',
            'events' => '/events',
            'restaurants' => '/restaurants',
            'venues' => '/venues',
            'bookings' => '/bookings',
            'reviews' => '/reviews',
            'payments' => '/payments',
            'search' => '/search',
            'admin' => '/admin'
        ]
    ]);
});

// Authentication routes
$router->addRoute('POST', '/auth/register', 'handleRegister');
$router->addRoute('POST', '/auth/login', 'handleLogin');
$router->addRoute('POST', '/auth/logout', 'handleLogout');
$router->addRoute('POST', '/auth/refresh', 'handleRefreshToken');
$router->addRoute('POST', '/auth/forgot-password', 'handleForgotPassword');
$router->addRoute('POST', '/auth/reset-password', 'handleResetPassword');
$router->addRoute('POST', '/auth/verify-email', 'handleVerifyEmail');

// User routes
$router->addRoute('GET', '/users/profile', 'handleGetProfile');
$router->addRoute('PUT', '/users/profile', 'handleUpdateProfile');
$router->addRoute('GET', '/users/{id}', 'handleGetUser');
$router->addRoute('DELETE', '/users/{id}', 'handleDeleteUser');

// Movie routes
$router->addRoute('GET', '/movies', 'handleGetMovies');
$router->addRoute('GET', '/movies/{id}', 'handleGetMovie');
$router->addRoute('GET', '/movies/{id}/showtimes', 'handleGetMovieShowtimes');
$router->addRoute('POST', '/movies', 'handleCreateMovie');
$router->addRoute('PUT', '/movies/{id}', 'handleUpdateMovie');
$router->addRoute('DELETE', '/movies/{id}', 'handleDeleteMovie');

// Event routes
$router->addRoute('GET', '/events', 'handleGetEvents');
$router->addRoute('GET', '/events/{id}', 'handleGetEvent');
$router->addRoute('GET', '/events/{id}/tickets', 'handleGetEventTickets');
$router->addRoute('POST', '/events', 'handleCreateEvent');
$router->addRoute('PUT', '/events/{id}', 'handleUpdateEvent');
$router->addRoute('DELETE', '/events/{id}', 'handleDeleteEvent');

// Restaurant routes
$router->addRoute('GET', '/restaurants', 'handleGetRestaurants');
$router->addRoute('GET', '/restaurants/{id}', 'handleGetRestaurant');
$router->addRoute('GET', '/restaurants/{id}/tables', 'handleGetRestaurantTables');
$router->addRoute('POST', '/restaurants', 'handleCreateRestaurant');
$router->addRoute('PUT', '/restaurants/{id}', 'handleUpdateRestaurant');
$router->addRoute('DELETE', '/restaurants/{id}', 'handleDeleteRestaurant');

// Venue routes
$router->addRoute('GET', '/venues', 'handleGetVenues');
$router->addRoute('GET', '/venues/{id}', 'handleGetVenue');
$router->addRoute('POST', '/venues', 'handleCreateVenue');
$router->addRoute('PUT', '/venues/{id}', 'handleUpdateVenue');
$router->addRoute('DELETE', '/venues/{id}', 'handleDeleteVenue');

// Booking routes
$router->addRoute('GET', '/bookings', 'handleGetBookings');
$router->addRoute('GET', '/bookings/{id}', 'handleGetBooking');
$router->addRoute('POST', '/bookings', 'handleCreateBooking');
$router->addRoute('PUT', '/bookings/{id}', 'handleUpdateBooking');
$router->addRoute('DELETE', '/bookings/{id}', 'handleCancelBooking');

// Review routes
$router->addRoute('GET', '/reviews', 'handleGetReviews');
$router->addRoute('POST', '/reviews', 'handleCreateReview');
$router->addRoute('PUT', '/reviews/{id}', 'handleUpdateReview');
$router->addRoute('DELETE', '/reviews/{id}', 'handleDeleteReview');

// Payment routes
$router->addRoute('POST', '/payments/create-intent', 'handleCreatePaymentIntent');
$router->addRoute('POST', '/payments/confirm', 'handleConfirmPayment');
$router->addRoute('POST', '/payments/webhook', 'handlePaymentWebhook');

// Search routes
$router->addRoute('GET', '/search', 'handleSearch');
$router->addRoute('GET', '/search/movies', 'handleSearchMovies');
$router->addRoute('GET', '/search/events', 'handleSearchEvents');
$router->addRoute('GET', '/search/restaurants', 'handleSearchRestaurants');

// Admin routes
$router->addRoute('GET', '/admin/dashboard', 'handleAdminDashboard');
$router->addRoute('GET', '/admin/users', 'handleAdminUsers');
$router->addRoute('GET', '/admin/bookings', 'handleAdminBookings');
$router->addRoute('GET', '/admin/analytics', 'handleAdminAnalytics');

// Handle CORS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Handle the request
try {
    $router->handleRequest();
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    
    if (ApiSettings::isDevelopment()) {
        sendApiError($e->getMessage(), 500, [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    } else {
        sendApiError('Internal server error', 500);
    }
}
?>
