<?php
/**
 * Restaurants API Endpoint
 * Handles all restaurant-related operations
 */

require_once '../config/database.php';
require_once '../config/settings.php';
require_once '../utils/auth.php';
require_once '../utils/validation.php';

class RestaurantsAPI {
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
            error_log("Restaurants API Error: " . $e->getMessage());
            sendApiError('Internal server error', 500);
        }
    }
    
    /**
     * Handle GET requests
     */
    private function handleGet($path) {
        $pathParts = explode('/', trim($path, '/'));
        
        if (empty($pathParts[0])) {
            // GET /restaurants - Get all restaurants
            $this->getAllRestaurants();
        } elseif (is_numeric($pathParts[0])) {
            // GET /restaurants/{id} - Get specific restaurant
            $this->getRestaurant($pathParts[0]);
        } elseif ($pathParts[0] === 'search') {
            // GET /restaurants/search - Search restaurants
            $this->searchRestaurants();
        } elseif ($pathParts[0] === 'cuisines') {
            // GET /restaurants/cuisines - Get cuisine types
            $this->getCuisines();
        } elseif ($pathParts[0] === 'featured') {
            // GET /restaurants/featured - Get featured restaurants
            $this->getFeaturedRestaurants();
        } elseif ($pathParts[0] === 'nearby') {
            // GET /restaurants/nearby - Get nearby restaurants
            $this->getNearbyRestaurants();
        } elseif ($pathParts[0] === 'menu') {
            if (isset($pathParts[1]) && is_numeric($pathParts[1])) {
                // GET /restaurants/menu/{restaurant_id} - Get restaurant menu
                $this->getRestaurantMenu($pathParts[1]);
            } else {
                sendApiError('Restaurant ID required for menu', 400);
            }
        } elseif ($pathParts[0] === 'reviews') {
            if (isset($pathParts[1]) && is_numeric($pathParts[1])) {
                // GET /restaurants/reviews/{restaurant_id} - Get restaurant reviews
                $this->getRestaurantReviews($pathParts[1]);
            } else {
                sendApiError('Restaurant ID required for reviews', 400);
            }
        } elseif ($pathParts[0] === 'availability') {
            if (isset($pathParts[1]) && is_numeric($pathParts[1])) {
                // GET /restaurants/availability/{restaurant_id} - Get table availability
                $this->getTableAvailability($pathParts[1]);
            } else {
                sendApiError('Restaurant ID required for availability', 400);
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
        $pathParts = explode('/', trim($path, '/'));
        
        if (empty($pathParts[0])) {
            // POST /restaurants - Add new restaurant (admin only)
            AuthHelper::requireAdmin();
            $this->addRestaurant($data);
        } elseif ($pathParts[0] === 'menu') {
            // POST /restaurants/menu - Add menu item
            AuthHelper::requireAuth();
            $this->addMenuItem($data);
        } elseif ($pathParts[0] === 'reviews') {
            // POST /restaurants/reviews - Add review
            AuthHelper::requireAuth();
            $this->addReview($data);
        } else {
            sendApiError('Endpoint not found', 404);
        }
    }
    
    /**
     * Handle PUT requests
     */
    private function handlePut($path) {
        $data = json_decode(file_get_contents('php://input'), true);
        $pathParts = explode('/', trim($path, '/'));
        
        if (is_numeric($pathParts[0])) {
            // PUT /restaurants/{id} - Update restaurant (admin only)
            AuthHelper::requireAdmin();
            $this->updateRestaurant($pathParts[0], $data);
        } else {
            sendApiError('Restaurant ID required', 400);
        }
    }
    
    /**
     * Handle DELETE requests
     */
    private function handleDelete($path) {
        AuthHelper::requireAdmin();
        $pathParts = explode('/', trim($path, '/'));
        
        if (is_numeric($pathParts[0])) {
            // DELETE /restaurants/{id} - Delete restaurant
            $this->deleteRestaurant($pathParts[0]);
        } else {
            sendApiError('Restaurant ID required', 400);
        }
    }
    
    /**
     * Get all restaurants with pagination and filters
     */
    private function getAllRestaurants() {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(getSetting('max_page_size'), max(1, (int)($_GET['limit'] ?? getSetting('default_page_size'))));
        $offset = ($page - 1) * $limit;
        
        $cuisine = $_GET['cuisine'] ?? null;
        $location = $_GET['location'] ?? null;
        $minRating = $_GET['min_rating'] ?? null;
        $maxPrice = $_GET['max_price'] ?? null;
        $features = $_GET['features'] ?? null; // delivery, takeout, dine_in
        $sort = $_GET['sort'] ?? 'name';
        $order = strtoupper($_GET['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        
        // Build query
        $whereClause = 'WHERE is_active = 1';
        $params = [];
        
        if ($cuisine) {
            $whereClause .= ' AND cuisine_type = ?';
            $params[] = $cuisine;
        }
        
        if ($location) {
            $whereClause .= ' AND (address LIKE ? OR area LIKE ?)';
            $locationPattern = "%$location%";
            $params[] = $locationPattern;
            $params[] = $locationPattern;
        }
        
        if ($minRating) {
            $whereClause .= ' AND average_rating >= ?';
            $params[] = $minRating;
        }
        
        if ($maxPrice) {
            $whereClause .= ' AND price_range <= ?';
            $params[] = $maxPrice;
        }
        
        if ($features) {
            $featuresArray = explode(',', $features);
            foreach ($featuresArray as $feature) {
                $feature = trim($feature);
                if (in_array($feature, ['delivery', 'takeout', 'dine_in'])) {
                    $whereClause .= " AND features LIKE ?";
                    $params[] = "%$feature%";
                }
            }
        }
        
        // Validate sort field
        $allowedSortFields = ['name', 'average_rating', 'price_range', 'created_at'];
        if (!in_array($sort, $allowedSortFields)) {
            $sort = 'name';
        }
        
        $query = "
            SELECT id, name, description, cuisine_type, address, area, phone,
                   average_rating, review_count, price_range, image_url,
                   features, opening_hours, is_featured, created_at
            FROM restaurants 
            $whereClause 
            ORDER BY $sort $order 
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->executeQuery($query, $params);
        $restaurants = $stmt->fetchAll();
        
        // Parse JSON fields
        foreach ($restaurants as &$restaurant) {
            $restaurant['features'] = json_decode($restaurant['features'] ?? '[]');
            $restaurant['opening_hours'] = json_decode($restaurant['opening_hours'] ?? '{}');
        }
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM restaurants $whereClause";
        $countParams = array_slice($params, 0, -2);
        $countStmt = $this->db->executeQuery($countQuery, $countParams);
        $total = $countStmt->fetch()['total'];
        
        $meta = [
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => (int)$total,
                'total_pages' => ceil($total / $limit)
            ],
            'filters' => compact('cuisine', 'location', 'minRating', 'maxPrice', 'features', 'sort', 'order')
        ];
        
        sendApiResponse($restaurants, 'Restaurants retrieved successfully', 200, $meta);
    }
    
    /**
     * Get specific restaurant by ID
     */
    private function getRestaurant($id) {
        if (!Validator::validateId($id)) {
            sendApiError('Invalid restaurant ID', 400);
        }
        
        $query = "
            SELECT id, name, description, cuisine_type, address, area, phone, email,
                   website, average_rating, review_count, price_range, image_url,
                   gallery_images, features, amenities, opening_hours, special_offers,
                   owner_name, is_featured, is_active, created_at, updated_at
            FROM restaurants 
            WHERE id = ? AND is_active = 1
        ";
        
        $stmt = $this->db->executeQuery($query, [$id]);
        $restaurant = $stmt->fetch();
        
        if (!$restaurant) {
            sendApiError('Restaurant not found', 404);
        }
        
        // Parse JSON fields
        $restaurant['gallery_images'] = json_decode($restaurant['gallery_images'] ?? '[]');
        $restaurant['features'] = json_decode($restaurant['features'] ?? '[]');
        $restaurant['amenities'] = json_decode($restaurant['amenities'] ?? '[]');
        $restaurant['opening_hours'] = json_decode($restaurant['opening_hours'] ?? '{}');
        $restaurant['special_offers'] = json_decode($restaurant['special_offers'] ?? '[]');
        
        sendApiResponse($restaurant, 'Restaurant retrieved successfully');
    }
    
    /**
     * Search restaurants
     */
    private function searchRestaurants() {
        $query = $_GET['q'] ?? '';
        
        if (strlen($query) < 2) {
            sendApiError('Search query must be at least 2 characters', 400);
        }
        
        $searchQuery = "
            SELECT id, name, description, cuisine_type, address, area,
                   average_rating, review_count, price_range, image_url,
                   features, is_featured
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
            LIMIT 50
        ";
        
        $searchTerm = "%$query%";
        $exactTerm = "$query%";
        
        $params = [
            $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm,
            $exactTerm, $exactTerm
        ];
        
        $stmt = $this->db->executeQuery($searchQuery, $params);
        $restaurants = $stmt->fetchAll();
        
        // Parse JSON fields
        foreach ($restaurants as &$restaurant) {
            $restaurant['features'] = json_decode($restaurant['features'] ?? '[]');
        }
        
        sendApiResponse($restaurants, 'Search completed successfully');
    }
    
    /**
     * Get cuisine types
     */
    private function getCuisines() {
        $query = "
            SELECT cuisine_type, COUNT(*) as restaurant_count 
            FROM restaurants 
            WHERE cuisine_type IS NOT NULL AND is_active = 1
            GROUP BY cuisine_type 
            ORDER BY cuisine_type
        ";
        
        $stmt = $this->db->executeQuery($query);
        $cuisines = $stmt->fetchAll();
        
        sendApiResponse($cuisines, 'Cuisines retrieved successfully');
    }
    
    /**
     * Get featured restaurants
     */
    private function getFeaturedRestaurants() {
        $limit = min(20, max(1, (int)($_GET['limit'] ?? 6)));
        
        $query = "
            SELECT id, name, description, cuisine_type, address, area,
                   average_rating, review_count, price_range, image_url, features
            FROM restaurants
            WHERE is_featured = 1 AND is_active = 1
            ORDER BY average_rating DESC, review_count DESC
            LIMIT ?
        ";
        
        $stmt = $this->db->executeQuery($query, [$limit]);
        $restaurants = $stmt->fetchAll();
        
        // Parse JSON fields
        foreach ($restaurants as &$restaurant) {
            $restaurant['features'] = json_decode($restaurant['features'] ?? '[]');
        }
        
        sendApiResponse($restaurants, 'Featured restaurants retrieved successfully');
    }
    
    /**
     * Get nearby restaurants (placeholder - would need geolocation in real implementation)
     */
    private function getNearbyRestaurants() {
        $area = $_GET['area'] ?? '';
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
        
        if (empty($area)) {
            sendApiError('Area parameter required', 400);
        }
        
        $query = "
            SELECT id, name, description, cuisine_type, address, area,
                   average_rating, review_count, price_range, image_url, features
            FROM restaurants
            WHERE area LIKE ? AND is_active = 1
            ORDER BY average_rating DESC
            LIMIT ?
        ";
        
        $areaPattern = "%$area%";
        $stmt = $this->db->executeQuery($query, [$areaPattern, $limit]);
        $restaurants = $stmt->fetchAll();
        
        // Parse JSON fields
        foreach ($restaurants as &$restaurant) {
            $restaurant['features'] = json_decode($restaurant['features'] ?? '[]');
        }
        
        sendApiResponse($restaurants, 'Nearby restaurants retrieved successfully');
    }
    
    /**
     * Get restaurant menu
     */
    private function getRestaurantMenu($restaurantId) {
        if (!Validator::validateId($restaurantId)) {
            sendApiError('Invalid restaurant ID', 400);
        }
        
        $query = "
            SELECT id, restaurant_id, category, item_name, description, price,
                   image_url, is_vegetarian, is_vegan, is_spicy, allergens,
                   preparation_time, is_available, created_at
            FROM menu_items
            WHERE restaurant_id = ? AND is_available = 1
            ORDER BY category ASC, item_name ASC
        ";
        
        $stmt = $this->db->executeQuery($query, [$restaurantId]);
        $menuItems = $stmt->fetchAll();
        
        // Group by category
        $menu = [];
        foreach ($menuItems as $item) {
            $category = $item['category'];
            if (!isset($menu[$category])) {
                $menu[$category] = [];
            }
            
            // Parse JSON fields
            $item['allergens'] = json_decode($item['allergens'] ?? '[]');
            
            $menu[$category][] = $item;
        }
        
        sendApiResponse($menu, 'Restaurant menu retrieved successfully');
    }
    
    /**
     * Get restaurant reviews
     */
    private function getRestaurantReviews($restaurantId) {
        if (!Validator::validateId($restaurantId)) {
            sendApiError('Invalid restaurant ID', 400);
        }
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        
        $query = "
            SELECT r.id, r.rating, r.review_text, r.created_at,
                   u.name as reviewer_name
            FROM reviews r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.restaurant_id = ?
            ORDER BY r.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $this->db->executeQuery($query, [$restaurantId, $limit, $offset]);
        $reviews = $stmt->fetchAll();
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM reviews WHERE restaurant_id = ?";
        $countStmt = $this->db->executeQuery($countQuery, [$restaurantId]);
        $total = $countStmt->fetch()['total'];
        
        $meta = [
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => (int)$total,
                'total_pages' => ceil($total / $limit)
            ]
        ];
        
        sendApiResponse($reviews, 'Restaurant reviews retrieved successfully', 200, $meta);
    }
    
    /**
     * Get table availability (placeholder)
     */
    private function getTableAvailability($restaurantId) {
        if (!Validator::validateId($restaurantId)) {
            sendApiError('Invalid restaurant ID', 400);
        }
        
        $date = $_GET['date'] ?? date('Y-m-d');
        $partySize = max(1, (int)($_GET['party_size'] ?? 2));
        
        // This is a simplified version - real implementation would be more complex
        $query = "
            SELECT time_slot, available_tables
            FROM restaurant_availability
            WHERE restaurant_id = ? AND date = ? AND min_party_size <= ?
            ORDER BY time_slot ASC
        ";
        
        $stmt = $this->db->executeQuery($query, [$restaurantId, $date, $partySize]);
        $availability = $stmt->fetchAll();
        
        sendApiResponse($availability, 'Table availability retrieved successfully');
    }
    
    /**
     * Add new restaurant (Admin only)
     */
    private function addRestaurant($data) {
        $validator = new Validator($data);
        
        $validator->required(['name', 'cuisine_type', 'address', 'phone', 'price_range'])
                  ->string('name', 1, 255)
                  ->string('cuisine_type', 1, 100)
                  ->string('address', 1, 500)
                  ->string('phone', 10, 15)
                  ->numeric('price_range', 1, 5);
        
        if (!$validator->isValid()) {
            sendApiError('Validation failed', 400, $validator->getErrors());
        }
        
        $query = "
            INSERT INTO restaurants (name, description, cuisine_type, address, area, phone, email,
                                   website, price_range, image_url, gallery_images, features,
                                   amenities, opening_hours, special_offers, owner_name,
                                   is_featured, is_active, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
        ";
        
        $params = [
            $data['name'],
            $data['description'] ?? null,
            $data['cuisine_type'],
            $data['address'],
            $data['area'] ?? null,
            $data['phone'],
            $data['email'] ?? null,
            $data['website'] ?? null,
            $data['price_range'],
            $data['image_url'] ?? null,
            json_encode($data['gallery_images'] ?? []),
            json_encode($data['features'] ?? []),
            json_encode($data['amenities'] ?? []),
            json_encode($data['opening_hours'] ?? []),
            json_encode($data['special_offers'] ?? []),
            $data['owner_name'] ?? null,
            $data['is_featured'] ?? 0
        ];
        
        $this->db->executeQuery($query, $params);
        $restaurantId = $this->db->getLastInsertId();
        
        sendApiResponse(['id' => $restaurantId], 'Restaurant added successfully', 201);
    }
    
    /**
     * Update restaurant (Admin only)
     */
    private function updateRestaurant($id, $data) {
        if (!Validator::validateId($id)) {
            sendApiError('Invalid restaurant ID', 400);
        }
        
        // Check if restaurant exists
        $checkQuery = "SELECT id FROM restaurants WHERE id = ?";
        $checkStmt = $this->db->executeQuery($checkQuery, [$id]);
        if (!$checkStmt->fetch()) {
            sendApiError('Restaurant not found', 404);
        }
        
        $updateFields = [];
        $params = [];
        
        $allowedFields = ['name', 'description', 'cuisine_type', 'address', 'area', 'phone',
                         'email', 'website', 'price_range', 'image_url', 'gallery_images',
                         'features', 'amenities', 'opening_hours', 'special_offers',
                         'owner_name', 'is_featured', 'is_active'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                if (in_array($field, ['gallery_images', 'features', 'amenities', 'opening_hours', 'special_offers'])) {
                    $params[] = json_encode($data[$field]);
                } else {
                    $params[] = $data[$field];
                }
            }
        }
        
        if (empty($updateFields)) {
            sendApiError('No valid fields to update', 400);
        }
        
        $updateFields[] = "updated_at = NOW()";
        $params[] = $id;
        
        $query = "UPDATE restaurants SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $this->db->executeQuery($query, $params);
        
        sendApiResponse(null, 'Restaurant updated successfully');
    }
    
    /**
     * Delete restaurant (Admin only)
     */
    private function deleteRestaurant($id) {
        if (!Validator::validateId($id)) {
            sendApiError('Invalid restaurant ID', 400);
        }
        
        // Soft delete - just set is_active to 0
        $query = "UPDATE restaurants SET is_active = 0, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->executeQuery($query, [$id]);
        
        if ($stmt->rowCount() === 0) {
            sendApiError('Restaurant not found', 404);
        }
        
        sendApiResponse(null, 'Restaurant deleted successfully');
    }
    
    /**
     * Add menu item
     */
    private function addMenuItem($data) {
        $validator = new Validator($data);
        
        $validator->required(['restaurant_id', 'category', 'item_name', 'price'])
                  ->numeric('restaurant_id')
                  ->string('category', 1, 100)
                  ->string('item_name', 1, 255)
                  ->numeric('price', 0);
        
        if (!$validator->isValid()) {
            sendApiError('Validation failed', 400, $validator->getErrors());
        }
        
        // Verify restaurant exists
        $restaurantCheck = "SELECT id FROM restaurants WHERE id = ? AND is_active = 1";
        $restaurantStmt = $this->db->executeQuery($restaurantCheck, [$data['restaurant_id']]);
        if (!$restaurantStmt->fetch()) {
            sendApiError('Restaurant not found', 404);
        }
        
        $query = "
            INSERT INTO menu_items (restaurant_id, category, item_name, description, price,
                                  image_url, is_vegetarian, is_vegan, is_spicy, allergens,
                                  preparation_time, is_available, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
        ";
        
        $params = [
            $data['restaurant_id'],
            $data['category'],
            $data['item_name'],
            $data['description'] ?? null,
            $data['price'],
            $data['image_url'] ?? null,
            $data['is_vegetarian'] ?? 0,
            $data['is_vegan'] ?? 0,
            $data['is_spicy'] ?? 0,
            json_encode($data['allergens'] ?? []),
            $data['preparation_time'] ?? null
        ];
        
        $this->db->executeQuery($query, $params);
        $menuItemId = $this->db->getLastInsertId();
        
        sendApiResponse(['id' => $menuItemId], 'Menu item added successfully', 201);
    }
    
    /**
     * Add review
     */
    private function addReview($data) {
        $userId = AuthHelper::getCurrentUserId();
        
        $validator = new Validator($data);
        $validator->required(['restaurant_id', 'rating'])
                  ->numeric('restaurant_id')
                  ->numeric('rating', 1, 5);
        
        if (!$validator->isValid()) {
            sendApiError('Validation failed', 400, $validator->getErrors());
        }
        
        // Check if user already reviewed this restaurant
        $existingReview = "SELECT id FROM reviews WHERE user_id = ? AND restaurant_id = ?";
        $existingStmt = $this->db->executeQuery($existingReview, [$userId, $data['restaurant_id']]);
        if ($existingStmt->fetch()) {
            sendApiError('You have already reviewed this restaurant', 400);
        }
        
        // Verify restaurant exists
        $restaurantCheck = "SELECT id FROM restaurants WHERE id = ? AND is_active = 1";
        $restaurantStmt = $this->db->executeQuery($restaurantCheck, [$data['restaurant_id']]);
        if (!$restaurantStmt->fetch()) {
            sendApiError('Restaurant not found', 404);
        }
        
        $this->db->beginTransaction();
        
        try {
            // Add review
            $query = "
                INSERT INTO reviews (user_id, restaurant_id, rating, review_text, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ";
            
            $params = [
                $userId,
                $data['restaurant_id'],
                $data['rating'],
                $data['review_text'] ?? null
            ];
            
            $this->db->executeQuery($query, $params);
            $reviewId = $this->db->getLastInsertId();
            
            // Update restaurant average rating and review count
            $updateRatingQuery = "
                UPDATE restaurants 
                SET average_rating = (
                    SELECT AVG(rating) FROM reviews WHERE restaurant_id = ?
                ),
                review_count = (
                    SELECT COUNT(*) FROM reviews WHERE restaurant_id = ?
                ),
                updated_at = NOW()
                WHERE id = ?
            ";
            
            $this->db->executeQuery($updateRatingQuery, [
                $data['restaurant_id'], 
                $data['restaurant_id'], 
                $data['restaurant_id']
            ]);
            
            $this->db->commit();
            
            sendApiResponse(['id' => $reviewId], 'Review added successfully', 201);
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}

// Handle the request
$api = new RestaurantsAPI();
$api->handleRequest();
?>