<?php
/**
 * Movies API Endpoint
 * Handles all movie-related operations
 */

require_once '../config/database.php';
require_once '../config/settings.php';
require_once '../utils/auth.php';
require_once '../utils/validation.php';

class MoviesAPI {
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
                case 'POST':
                    $this->handlePost($path);
                    break;
                case 'PUT':
                    $this->handlePut($path);
                    break;
                case 'DELETE':
                    $this->handleDelete($path);
                    break;
                default:
                    sendApiError('Method not allowed', 405);
            }
        } catch (Exception $e) {
            error_log("Movies API Error: " . $e->getMessage());
            sendApiError('Internal server error', 500);
        }
    }
    
    /**
     * Handle GET requests
     */
    private function handleGet($path) {
        $pathParts = explode('/', trim($path, '/'));
        
        if (empty($pathParts[0])) {
            // GET /movies - Get all movies with pagination and filters
            $this->getAllMovies();
        } elseif (is_numeric($pathParts[0])) {
            // GET /movies/{id} - Get specific movie
            $this->getMovie($pathParts[0]);
        } elseif ($pathParts[0] === 'search') {
            // GET /movies/search - Search movies
            $this->searchMovies();
        } elseif ($pathParts[0] === 'categories') {
            // GET /movies/categories - Get movie categories
            $this->getCategories();
        } elseif ($pathParts[0] === 'trending') {
            // GET /movies/trending - Get trending movies
            $this->getTrendingMovies();
        } elseif ($pathParts[0] === 'showtimes') {
            if (isset($pathParts[1]) && is_numeric($pathParts[1])) {
                // GET /movies/showtimes/{movie_id} - Get showtimes for a movie
                $this->getMovieShowtimes($pathParts[1]);
            } else {
                sendApiError('Movie ID required for showtimes', 400);
            }
        } else {
            sendApiError('Endpoint not found', 404);
        }
    }
    
    /**
     * Handle POST requests
     */
    private function handlePost($path) {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if ($path === 'showtimes') {
            // POST /movies/showtimes - Add new showtime
            $this->addShowtime($data);
        } else {
            // POST /movies - Add new movie (admin only)
            AuthHelper::requireAdmin();
            $this->addMovie($data);
        }
    }
    
    /**
     * Handle PUT requests
     */
    private function handlePut($path) {
        AuthHelper::requireAdmin();
        $data = json_decode(file_get_contents('php://input'), true);
        $pathParts = explode('/', trim($path, '/'));
        
        if (is_numeric($pathParts[0])) {
            // PUT /movies/{id} - Update movie
            $this->updateMovie($pathParts[0], $data);
        } else {
            sendApiError('Movie ID required', 400);
        }
    }
    
    /**
     * Handle DELETE requests
     */
    private function handleDelete($path) {
        AuthHelper::requireAdmin();
        $pathParts = explode('/', trim($path, '/'));
        
        if (is_numeric($pathParts[0])) {
            // DELETE /movies/{id} - Delete movie
            $this->deleteMovie($pathParts[0]);
        } else {
            sendApiError('Movie ID required', 400);
        }
    }
    
    /**
     * Get all movies with pagination and filters
     */
    private function getAllMovies() {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(getSetting('max_page_size'), max(1, (int)($_GET['limit'] ?? getSetting('default_page_size'))));
        $offset = ($page - 1) * $limit;
        
        $category = $_GET['category'] ?? null;
        $rating = $_GET['rating'] ?? null;
        $year = $_GET['year'] ?? null;
        $sort = $_GET['sort'] ?? 'title';
        $order = strtoupper($_GET['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        
        // Build query
        $whereClause = 'WHERE 1=1';
        $params = [];
        
        if ($category) {
            $whereClause .= ' AND category = ?';
            $params[] = $category;
        }
        
        if ($rating) {
            $whereClause .= ' AND rating >= ?';
            $params[] = $rating;
        }
        
        if ($year) {
            $whereClause .= ' AND YEAR(release_date) = ?';
            $params[] = $year;
        }
        
        // Validate sort field
        $allowedSortFields = ['title', 'release_date', 'rating', 'duration', 'created_at'];
        if (!in_array($sort, $allowedSortFields)) {
            $sort = 'title';
        }
        
        $query = "
            SELECT id, title, description, category, rating, duration, 
                   release_date, poster_url, trailer_url, price, 
                   created_at, updated_at
            FROM movies 
            $whereClause 
            ORDER BY $sort $order 
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->executeQuery($query, $params);
        $movies = $stmt->fetchAll();
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM movies $whereClause";
        $countParams = array_slice($params, 0, -2); // Remove limit and offset
        $countStmt = $this->db->executeQuery($countQuery, $countParams);
        $total = $countStmt->fetch()['total'];
        
        $meta = [
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => (int)$total,
                'total_pages' => ceil($total / $limit)
            ],
            'filters' => compact('category', 'rating', 'year', 'sort', 'order')
        ];
        
        sendApiResponse($movies, 'Movies retrieved successfully', 200, $meta);
    }
    
    /**
     * Get specific movie by ID
     */
    private function getMovie($id) {
        if (!Validator::validateId($id)) {
            sendApiError('Invalid movie ID', 400);
        }
        
        $query = "
            SELECT id, title, description, category, rating, duration, 
                   release_date, poster_url, trailer_url, price,
                   cast_members, director, language, created_at, updated_at
            FROM movies 
            WHERE id = ?
        ";
        
        $stmt = $this->db->executeQuery($query, [$id]);
        $movie = $stmt->fetch();
        
        if (!$movie) {
            sendApiError('Movie not found', 404);
        }
        
        // Parse JSON fields
        $movie['cast_members'] = json_decode($movie['cast_members'] ?? '[]');
        
        sendApiResponse($movie, 'Movie retrieved successfully');
    }
    
    /**
     * Search movies
     */
    private function searchMovies() {
        $query = $_GET['q'] ?? '';
        
        if (strlen($query) < 2) {
            sendApiError('Search query must be at least 2 characters', 400);
        }
        
        $searchQuery = "
            SELECT id, title, description, category, rating, 
                   release_date, poster_url, price
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
            LIMIT 50
        ";
        
        $searchTerm = "%$query%";
        $exactTerm = "$query%";
        
        $params = [
            $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm,
            $exactTerm, $exactTerm
        ];
        
        $stmt = $this->db->executeQuery($searchQuery, $params);
        $movies = $stmt->fetchAll();
        
        sendApiResponse($movies, 'Search completed successfully');
    }
    
    /**
     * Get movie categories
     */
    private function getCategories() {
        $query = "SELECT DISTINCT category FROM movies WHERE category IS NOT NULL ORDER BY category";
        $stmt = $this->db->executeQuery($query);
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        sendApiResponse($categories, 'Categories retrieved successfully');
    }
    
    /**
     * Get trending movies
     */
    private function getTrendingMovies() {
        $limit = min(20, max(1, (int)($_GET['limit'] ?? 10)));
        
        $query = "
            SELECT m.id, m.title, m.poster_url, m.rating, m.category, m.price,
                   COUNT(b.id) as booking_count
            FROM movies m
            LEFT JOIN bookings b ON m.id = b.movie_id 
                AND b.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY m.id
            ORDER BY booking_count DESC, m.rating DESC
            LIMIT ?
        ";
        
        $stmt = $this->db->executeQuery($query, [$limit]);
        $movies = $stmt->fetchAll();
        
        sendApiResponse($movies, 'Trending movies retrieved successfully');
    }
    
    /**
     * Get movie showtimes
     */
    private function getMovieShowtimes($movieId) {
        if (!Validator::validateId($movieId)) {
            sendApiError('Invalid movie ID', 400);
        }
        
        $date = $_GET['date'] ?? date('Y-m-d');
        
        $query = "
            SELECT s.id, s.showtime, s.theater_name, s.theater_location,
                   s.available_seats, s.total_seats, s.price
            FROM showtimes s
            WHERE s.movie_id = ? AND DATE(s.showtime) = ?
            ORDER BY s.showtime ASC
        ";
        
        $stmt = $this->db->executeQuery($query, [$movieId, $date]);
        $showtimes = $stmt->fetchAll();
        
        sendApiResponse($showtimes, 'Showtimes retrieved successfully');
    }
    
    /**
     * Add new movie (Admin only)
     */
    private function addMovie($data) {
        $validator = new Validator($data);
        
        $validator->required(['title', 'description', 'category', 'duration', 'release_date', 'price'])
                  ->string('title', 1, 255)
                  ->string('description', 1, 1000)
                  ->string('category', 1, 100)
                  ->numeric('duration', 1, 999)
                  ->date('release_date')
                  ->numeric('price', 0);
        
        if (!$validator->isValid()) {
            sendApiError('Validation failed', 400, $validator->getErrors());
        }
        
        $query = "
            INSERT INTO movies (title, description, category, rating, duration, 
                              release_date, poster_url, trailer_url, price, cast_members, 
                              director, language, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ";
        
        $params = [
            $data['title'],
            $data['description'],
            $data['category'],
            $data['rating'] ?? null,
            $data['duration'],
            $data['release_date'],
            $data['poster_url'] ?? null,
            $data['trailer_url'] ?? null,
            $data['price'],
            json_encode($data['cast_members'] ?? []),
            $data['director'] ?? null,
            $data['language'] ?? 'English'
        ];
        
        $this->db->executeQuery($query, $params);
        $movieId = $this->db->getLastInsertId();
        
        sendApiResponse(['id' => $movieId], 'Movie added successfully', 201);
    }
    
    /**
     * Update movie (Admin only)
     */
    private function updateMovie($id, $data) {
        if (!Validator::validateId($id)) {
            sendApiError('Invalid movie ID', 400);
        }
        
        // Check if movie exists
        $checkQuery = "SELECT id FROM movies WHERE id = ?";
        $checkStmt = $this->db->executeQuery($checkQuery, [$id]);
        if (!$checkStmt->fetch()) {
            sendApiError('Movie not found', 404);
        }
        
        $updateFields = [];
        $params = [];
        
        $allowedFields = ['title', 'description', 'category', 'rating', 'duration', 
                         'release_date', 'poster_url', 'trailer_url', 'price', 
                         'cast_members', 'director', 'language'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $field === 'cast_members' ? json_encode($data[$field]) : $data[$field];
            }
        }
        
        if (empty($updateFields)) {
            sendApiError('No valid fields to update', 400);
        }
        
        $updateFields[] = "updated_at = NOW()";
        $params[] = $id;
        
        $query = "UPDATE movies SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $this->db->executeQuery($query, $params);
        
        sendApiResponse(null, 'Movie updated successfully');
    }
    
    /**
     * Delete movie (Admin only)
     */
    private function deleteMovie($id) {
        if (!Validator::validateId($id)) {
            sendApiError('Invalid movie ID', 400);
        }
        
        $query = "DELETE FROM movies WHERE id = ?";
        $stmt = $this->db->executeQuery($query, [$id]);
        
        if ($stmt->rowCount() === 0) {
            sendApiError('Movie not found', 404);
        }
        
        sendApiResponse(null, 'Movie deleted successfully');
    }
    
    /**
     * Add showtime
     */
    private function addShowtime($data) {
        AuthHelper::requireAuth();
        
        $validator = new Validator($data);
        $validator->required(['movie_id', 'showtime', 'theater_name', 'total_seats', 'price'])
                  ->numeric('movie_id')
                  ->datetime('showtime')
                  ->string('theater_name', 1, 255)
                  ->numeric('total_seats', 1)
                  ->numeric('price', 0);
        
        if (!$validator->isValid()) {
            sendApiError('Validation failed', 400, $validator->getErrors());
        }
        
        $query = "
            INSERT INTO showtimes (movie_id, showtime, theater_name, theater_location,
                                 total_seats, available_seats, price, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ";
        
        $params = [
            $data['movie_id'],
            $data['showtime'],
            $data['theater_name'],
            $data['theater_location'] ?? null,
            $data['total_seats'],
            $data['total_seats'], // Initially all seats are available
            $data['price']
        ];
        
        $this->db->executeQuery($query, $params);
        $showtimeId = $this->db->getLastInsertId();
        
        sendApiResponse(['id' => $showtimeId], 'Showtime added successfully', 201);
    }
}

// Handle the request
$api = new MoviesAPI();
$api->handleRequest();
?>