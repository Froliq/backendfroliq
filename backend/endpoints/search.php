<?php
/**
 * Search API Endpoint
 * Handles unified search across movies, events, and restaurants
 */

require_once '../config/database.php';
require_once '../config/settings.php';
require_once '../utils/validation.php';

class SearchAPI {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = getDatabase();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * Handle API requests
     */
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = $_GET['path'] ?? '';
        
        try {
            switch ($method) {
                case 'GET':
                    $this->handleGet($path);
                    break;
                default:
                    sendApiError('Method not allowed', 405);
            }
        } catch (Exception $e) {
            error_log("Search API Error: " . $e->getMessage());
            sendApiError('Internal server error', 500);
        }
    }
    
    /**
     * Handle GET requests
     */
    private function handleGet($path) {
        $pathParts = explode('/', trim($path, '/'));
        
        if (empty($pathParts[0])) {
            // GET /search - Universal search
            $this->universalSearch();
        } elseif ($pathParts[0] === 'movies') {
            // GET /search/movies - Search only movies
            $this->searchMovies();
        } elseif ($pathParts[0] === 'events') {
            // GET /search/events - Search only events
            $this->searchEvents();
        } elseif ($pathParts[0] === 'restaurants') {
            // GET /search/restaurants - Search only restaurants
            $this->searchRestaurants();
        } elseif ($pathParts[0] === 'suggestions') {
            // GET /search/suggestions - Get search suggestions
            $this->getSearchSuggestions();
        } elseif ($pathParts[0] === 'popular') {
            // GET /search/popular - Get popular search terms
            $this->getPopularSearches();
        } elseif ($pathParts[0] === 'filters') {
            // GET /search/filters - Get available filter options
            $this->getFilterOptions();
        } else {
            sendApiError('Endpoint not found', 404);
        }
    }
    
    /**
     * Universal search across all content types
     */
    private function universalSearch() {
        $query = $_GET['q'] ?? '';
        
        if (strlen($query) < 2) {
            sendApiError('Search query must be at least 2 characters', 400);
        }
        
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $category = $_GET['category'] ?? 'all'; // all, movies, events, restaurants
        
        $results = [];
        
        if ($category === 'all' || $category === 'movies') {
            $results['movies'] = $this->searchInMovies($query, $limit);
        }
        
        if ($category === 'all' || $category === 'events') {
            $results['events'] = $this->searchInEvents($query, $limit);
        }
        
        if ($category === 'all' || $category === 'restaurants') {
            $results['restaurants'] = $this->searchInRestaurants($query, $limit);
        }
        
        // Log search query for analytics
        $this->logSearchQuery($query, $category);
        
        // Calculate result counts
        $meta = [
            'query' => $query,
            'category' => $category,
            'counts' => [
                'movies' => count($results['movies'] ?? []),
                'events' => count($results['events'] ?? []),
                'restaurants' => count($results['restaurants'] ?? []),
                'total' => array_sum(array_map('count', $results))
            ]
        ];
        
        sendApiResponse($results, 'Search completed successfully', 200, $meta);
    }
    
    /**
     * Search only movies
     */
    private function searchMovies() {
        $query = $_GET['q'] ?? '';
        
        if (strlen($query) < 2) {
            sendApiError('Search query must be at least 2 characters', 400);
        }
        
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $results = $this->searchInMovies($query, $limit);
        
        $this->logSearchQuery($query, 'movies');
        
        sendApiResponse($results, 'Movies search completed successfully');
    }
    
    /**
     * Search only events
     */
    private function searchEvents() {
        $query = $_GET['q'] ?? '';
        
        if (strlen($query) < 2) {
            sendApiError('Search query must be at least 2 characters', 400);
        }
        
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $results = $this->searchInEvents($query, $limit);
        
        $this->logSearchQuery($query, 'events');
        
        sendApiResponse($results, 'Events search completed successfully');
    }
    
    /**
     * Search only restaurants
     */
    private function searchRestaurants() {
        $query = $_GET['q'] ?? '';
        
        if (strlen($query) < 2) {
            sendApiError('Search query must be at least 2 characters', 400);
        }
        
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $results = $this->searchInRestaurants($query, $limit);
        
        $this->logSearchQuery($query, 'restaurants');
        
        sendApiResponse($results, 'Restaurants search completed successfully');
    }
    
    /**
     * Search in movies table
     */
    private function searchInMovies($query, $limit = 20) {
        $searchQuery = "
            SELECT id, title, description, category, rating, duration, 
                   release_date, poster_url, price, 'movie' as content_type
            FROM movies 
            WHERE title LIKE ? 
               OR description LIKE ? 
               OR category LIKE ?
               OR cast_members LIKE ?
               OR director LIKE ?
            ORDER BY 
                CASE 
                    WHEN title LIKE ? THEN 1
                    WHEN description LIKE ? THEN 2
                    ELSE 3
                END,
                title ASC
            LIMIT ?
        ";
        
        $searchTerm = "%$query%";
        $exactTerm = "$query%";
        
        $params = [
            $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm,
            $exactTerm, $exactTerm, $limit
        ];
        
        $stmt = $this->db->executeQuery($searchQuery, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Search in events table
     */
    private function searchInEvents($query, $limit = 20) {
        $searchQuery = "
            SELECT id, title, description, category, event_date, event_time,
                   location, venue_name, price, image_url, 'event' as content_type,
                   max_attendees, current_attendees
            FROM events 
            WHERE event_date >= CURDATE() 
              AND (title LIKE ? 
                   OR description LIKE ? 
                   OR category LIKE ?
                   OR location LIKE ?
                   OR venue_name LIKE ?
                   OR organizer_name LIKE ?
                   OR tags LIKE ?)
            ORDER BY 
                CASE 
                    WHEN title LIKE ? THEN 1
                    WHEN description LIKE ? THEN 2
                    ELSE 3
                END,
                event_date ASC
            LIMIT ?
        ";
        
        $searchTerm = "%$query%";
        $exactTerm = "$query%";
        
        $params = [
            $searchTerm, $searchTerm, $searchTerm, $searchTerm, 
            $searchTerm, $searchTerm, $searchTerm,
            $exactTerm, $exactTerm, $limit
        ];
        
        $stmt = $this->db->executeQuery($searchQuery, $params);
        $results = $stmt->fetchAll();
        
        // Add availability info
        foreach ($results as &$event) {
            $event['available_spots'] = $event['max_attendees'] - $event['current_attendees'];
            $event['is_full'] = $event['current_attendees'] >= $event['max_attendees'];
        }
        
        return $results;
    }
    
    /**
     * Search in restaurants table
     */
    private function searchInRestaurants($query, $limit = 20) {
        $searchQuery = "
            SELECT id, name as title, description, cuisine_type as category, 
                   address, area, average_rating, review_count, price_range as price, 
                   image_url, features, 'restaurant' as content_type
            FROM restaurants 
            WHERE is_active = 1 
              AND (name LIKE ? 
                   OR description LIKE ? 
                   OR cuisine_type LIKE ?
                   OR address LIKE ?
                   OR area LIKE ?)
            ORDER BY 
                CASE 
                    WHEN name LIKE ? THEN 1
                    WHEN cuisine_type LIKE ? THEN 2
                    ELSE 3
                END,
                average_rating DESC
            LIMIT ?
        ";
        
        $searchTerm = "%$query%";
        $exactTerm = "$query%";
        
        $params = [
            $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm,
            $exactTerm, $exactTerm, $limit
        ];
        
        $stmt = $this->db->executeQuery($searchQuery, $params);
        $results = $stmt->fetchAll();
        
        // Parse JSON fields
        foreach ($results as &$restaurant) {
            $restaurant['features'] = json_decode($restaurant['features'] ?? '[]');
        }
        
        return $results;
    }
    
    /**
     * Get search suggestions (autocomplete)
     */
    private function getSearchSuggestions() {
        $query = $_GET['q'] ?? '';
        
        if (strlen($query) < 1) {
            sendApiResponse([], 'Query too short for suggestions');
        }
        
        $limit = min(10, max(1, (int)($_GET['limit'] ?? 5)));
        $suggestions = [];
        
        // Get suggestions from movies
        $movieQuery = "
            SELECT DISTINCT title as suggestion, 'movie' as type
            FROM movies 
            WHERE title LIKE ? 
            ORDER BY title ASC 
            LIMIT ?
        ";
        
        $movieStmt = $this->db->executeQuery($movieQuery, ["$query%", $limit]);
        $movieSuggestions = $movieStmt->fetchAll();
        $suggestions = array_merge($suggestions, $movieSuggestions);
        
        // Get suggestions from events
        $eventQuery = "
            SELECT DISTINCT title as suggestion, 'event' as type
            FROM events 
            WHERE title LIKE ? AND event_date >= CURDATE()
            ORDER BY title ASC 
            LIMIT ?
        ";
        
        $eventStmt = $this->db->executeQuery($eventQuery, ["$query%", $limit]);
        $eventSuggestions = $eventStmt->fetchAll();
        $suggestions = array_merge($suggestions, $eventSuggestions);
        
        // Get suggestions from restaurants
        $restaurantQuery = "
            SELECT DISTINCT name as suggestion, 'restaurant' as type
            FROM restaurants 
            WHERE name LIKE ? AND is_active = 1
            ORDER BY name ASC 
            LIMIT ?
        ";
        
        $restaurantStmt = $this->db->executeQuery($restaurantQuery, ["$query%", $limit]);
        $restaurantSuggestions = $restaurantStmt->fetchAll();
        $suggestions = array_merge($suggestions, $restaurantSuggestions);
        
        // Sort and limit final results
        usort($suggestions, function($a, $b) {
            return strcmp($a['suggestion'], $b['suggestion']);
        });
        
        $suggestions = array_slice($suggestions, 0, $limit);
        
        sendApiResponse($suggestions, 'Search suggestions retrieved successfully');
    }
    
    /**
     * Get popular search terms
     */
    private function getPopularSearches() {
        $limit = min(20, max(1, (int)($_GET['limit'] ?? 10)));
        $days = min(30, max(1, (int)($_GET['days'] ?? 7)));
        
        $query = "
            SELECT search_query, COUNT(*) as search_count
            FROM search_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
              AND CHAR_LENGTH(search_query) >= 2
            GROUP BY search_query
            ORDER BY search_count DESC, search_query ASC
            LIMIT ?
        ";
        
        $stmt = $this->db->executeQuery($query, [$days, $limit]);
        $popular = $stmt->fetchAll();
        
        sendApiResponse($popular, 'Popular searches retrieved successfully');
    }
    
    /**
     * Get available filter options
     */
    private function getFilterOptions() {
        $filters = [];
        
        // Movie categories
        $movieCategoriesQuery = "SELECT DISTINCT category FROM movies WHERE category IS NOT NULL ORDER BY category";
        $movieCategoriesStmt = $this->db->executeQuery($movieCategoriesQuery);
        $filters['movie_categories'] = $movieCategoriesStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Event categories
        $eventCategoriesQuery = "SELECT DISTINCT category FROM events WHERE category IS NOT NULL ORDER BY category";
        $eventCategoriesStmt = $this->db->executeQuery($eventCategoriesQuery);
        $filters['event_categories'] = $eventCategoriesStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Restaurant cuisines
        $cuisineQuery = "SELECT DISTINCT cuisine_type FROM restaurants WHERE cuisine_type IS NOT NULL AND is_active = 1 ORDER BY cuisine_type";
        $cuisineStmt = $this->db->executeQuery($cuisineQuery);
        $filters['cuisines'] = $cuisineStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Locations (from events and restaurants)
        $locationQuery = "
            SELECT DISTINCT location as loc FROM events WHERE location IS NOT NULL
            UNION
            SELECT DISTINCT area as loc FROM restaurants WHERE area IS NOT NULL AND is_active = 1
            ORDER BY loc
        ";
        $locationStmt = $this->db->executeQuery($locationQuery);
        $filters['locations'] = $locationStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Price ranges
        $filters['price_ranges'] = [
            ['label' => 'Free', 'min' => 0, 'max' => 0],
            ['label' => 'Under $10', 'min' => 0, 'max' => 10],
            ['label' => '$10 - $25', 'min' => 10, 'max' => 25],
            ['label' => '$25 - $50', 'min' => 25, 'max' => 50],
            ['label' => '$50+', 'min' => 50, 'max' => null]
        ];
        
        // Date ranges for events
        $filters['date_ranges'] = [
            ['label' => 'Today', 'value' => date('Y-m-d')],
            ['label' => 'This Week', 'start' => date('Y-m-d'), 'end' => date('Y-m-d', strtotime('+7 days'))],
            ['label' => 'This Month', 'start' => date('Y-m-d'), 'end' => date('Y-m-d', strtotime('+30 days'))],
            ['label' => 'Next 3 Months', 'start' => date('Y-m-d'), 'end' => date('Y-m-d', strtotime('+90 days'))]
        ];
        
        sendApiResponse($filters, 'Filter options retrieved successfully');
    }
    
    /**
     * Log search query for analytics
     */
    private function logSearchQuery($query, $category) {
        try {
            $logQuery = "
                INSERT INTO search_logs (search_query, category, user_agent, ip_address, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ";
            
            $params = [
                $query,
                $category,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null
            ];
            
            $this->db->executeQuery($logQuery, $params);
        } catch (Exception $e) {
            // Log error but don't fail the search
            error_log("Failed to log search query: " . $e->getMessage());
        }
    }
}

// Handle the request
$api = new SearchAPI();
$api->handleRequest();
?>