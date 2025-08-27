<?php
/**
 * Bookings API Endpoint
 * Handles all booking-related operations for movies, events, and restaurants
 */

require_once '../config/database.php';
require_once '../config/settings.php';
require_once '../utils/auth.php';
require_once '../utils/validation.php';

class BookingsAPI {
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
            error_log("Bookings API Error: " . $e->getMessage());
            sendApiError('Internal server error', 500);
        }
    }
    
    /**
     * Handle GET requests
     */
    private function handleGet($path) {
        AuthHelper::requireAuth();
        $pathParts = explode('/', trim($path, '/'));
        
        if (empty($pathParts[0])) {
            // GET /bookings - Get user's bookings
            $this->getUserBookings();
        } elseif (is_numeric($pathParts[0])) {
            // GET /bookings/{id} - Get specific booking
            $this->getBooking($pathParts[0]);
        } elseif ($pathParts[0] === 'history') {
            // GET /bookings/history - Get booking history
            $this->getBookingHistory();
        } elseif ($pathParts[0] === 'upcoming') {
            // GET /bookings/upcoming - Get upcoming bookings
            $this->getUpcomingBookings();
        } elseif ($pathParts[0] === 'stats') {
            // GET /bookings/stats - Get booking statistics (admin only)
            AuthHelper::requireAdmin();
            $this->getBookingStats();
        } elseif ($pathParts[0] === 'admin') {
            // GET /bookings/admin - Get all bookings (admin only)
            AuthHelper::requireAdmin();
            $this->getAllBookings();
        } else {
            sendApiError('Endpoint not found', 404);
        }
    }
    
    /**
     * Handle POST requests
     */
    private function handlePost($path) {
        AuthHelper::requireAuth();
        $data = json_decode(file_get_contents('php://input'), true);
        $pathParts = explode('/', trim($path, '/'));
        
        if (empty($pathParts[0])) {
            // POST /bookings - Create new booking
            $this->createBooking($data);
        } elseif ($pathParts[0] === 'movies') {
            // POST /bookings/movies - Book movie tickets
            $this->bookMovieTickets($data);
        } elseif ($pathParts[0] === 'events') {
            // POST /bookings/events - Book event tickets
            $this->bookEventTickets($data);
        } elseif ($pathParts[0] === 'restaurants') {
            // POST /bookings/restaurants - Book restaurant table
            $this->bookRestaurantTable($data);
        } elseif ($pathParts[0] === 'validate') {
            // POST /bookings/validate - Validate booking details
            $this->validateBooking($data);
        } else {
            sendApiError('Endpoint not found', 404);
        }
    }
    
    /**
     * Handle PUT requests
     */
    private function handlePut($path) {
        AuthHelper::requireAuth();
        $data = json_decode(file_get_contents('php://input'), true);
        $pathParts = explode('/', trim($path, '/'));
        
        if (is_numeric($pathParts[0])) {
            if (isset($pathParts[1]) && $pathParts[1] === 'cancel') {
                // PUT /bookings/{id}/cancel - Cancel booking
                $this->cancelBooking($pathParts[0]);
            } else {
                // PUT /bookings/{id} - Update booking
                $this->updateBooking($pathParts[0], $data);
            }
        } else {
            sendApiError('Booking ID required', 400);
        }
    }
    
    /**
     * Handle DELETE requests
     */
    private function handleDelete($path) {
        AuthHelper::requireAuth();
        $pathParts = explode('/', trim($path, '/'));
        
        if (is_numeric($pathParts[0])) {
            // DELETE /bookings/{id} - Delete booking (admin only)
            AuthHelper::requireAdmin();
            $this->deleteBooking($pathParts[0]);
        } else {
            sendApiError('Booking ID required', 400);
        }
    }
    
    /**
     * Get user's bookings
     */
    private function getUserBookings() {
        $userId = AuthHelper::getCurrentUserId();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        
        $status = $_GET['status'] ?? null;
        $type = $_GET['type'] ?? null; // movie, event, restaurant
        
        $whereClause = 'WHERE user_id = ?';
        $params = [$userId];
        
        if ($status && in_array($status, ['confirmed', 'cancelled', 'completed', 'pending'])) {
            $whereClause .= ' AND status = ?';
            $params[] = $status;
        }
        
        if ($type && in_array($type, ['movie', 'event', 'restaurant'])) {
            $whereClause .= ' AND booking_type = ?';
            $params[] = $type;
        }
        
        $query = "
            SELECT id, booking_type, reference_id, status, booking_date, total_amount,
                   quantity, special_requests, payment_status, payment_method,
                   booking_details, created_at, updated_at
            FROM bookings 
            $whereClause 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->executeQuery($query, $params);
        $bookings = $stmt->fetchAll();
        
        // Enrich bookings with additional details
        foreach ($bookings as &$booking) {
            $booking['booking_details'] = json_decode($booking['booking_details'] ?? '{}');
            $booking = $this->enrichBookingDetails($booking);
        }
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM bookings $whereClause";
        $countParams = array_slice($params, 0, -2);
        $countStmt = $this->db->executeQuery($countQuery, $countParams);
        $total = $countStmt->fetch()['total'];
        
        $meta = [
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => (int)$total,
                'total_pages' => ceil($total / $limit)
            ]
        ];
        
        sendApiResponse($bookings, 'Bookings retrieved successfully', 200, $meta);
    }
    
    /**
     * Get specific booking
     */
    private function getBooking($id) {
        if (!Validator::validateId($id)) {
            sendApiError('Invalid booking ID', 400);
        }
        
        $userId = AuthHelper::getCurrentUserId();
        $isAdmin = AuthHelper::isAdmin();
        
        $whereClause = $isAdmin ? 'WHERE id = ?' : 'WHERE id = ? AND user_id = ?';
        $params = $isAdmin ? [$id] : [$id, $userId];
        
        $query = "
            SELECT b.*, u.name as user_name, u.email as user_email
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.id
            $whereClause
        ";
        
        $stmt = $this->db->executeQuery($query, $params);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            sendApiError('Booking not found', 404);
        }
        
        $booking['booking_details'] = json_decode($booking['booking_details'] ?? '{}');
        $booking = $this->enrichBookingDetails($booking);
        
        sendApiResponse($booking, 'Booking retrieved successfully');
    }
    
    /**
     * Get booking history
     */
    private function getBookingHistory() {
        $userId = AuthHelper::getCurrentUserId();
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
        
        $query = "
            SELECT id, booking_type, reference_id, status, booking_date, 
                   total_amount, quantity, booking_details, created_at
            FROM bookings 
            WHERE user_id = ? AND status IN ('completed', 'cancelled')
            ORDER BY created_at DESC 
            LIMIT ?
        ";
        
        $stmt = $this->db->executeQuery($query, [$userId, $limit]);
        $bookings = $stmt->fetchAll();
        
        foreach ($bookings as &$booking) {
            $booking['booking_details'] = json_decode($booking['booking_details'] ?? '{}');
            $booking = $this->enrichBookingDetails($booking);
        }
        
        sendApiResponse($bookings, 'Booking history retrieved successfully');
    }
    
    /**
     * Get upcoming bookings
     */
    private function getUpcomingBookings() {
        $userId = AuthHelper::getCurrentUserId();
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
        
        $query = "
            SELECT id, booking_type, reference_id, status, booking_date, 
                   total_amount, quantity, booking_details, created_at
            FROM bookings 
            WHERE user_id = ? 
              AND status = 'confirmed' 
              AND booking_date >= CURDATE()
            ORDER BY booking_date ASC 
            LIMIT ?
        ";
        
        $stmt = $this->db->executeQuery($query, [$userId, $limit]);
        $bookings = $stmt->fetchAll();
        
        foreach ($bookings as &$booking) {
            $booking['booking_details'] = json_decode($booking['booking_details'] ?? '{}');
            $booking = $this->enrichBookingDetails($booking);
        }
        
        sendApiResponse($bookings, 'Upcoming bookings retrieved successfully');
    }
    
    /**
     * Get booking statistics (Admin only)
     */
    private function getBookingStats() {
        $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        // Total bookings by type
        $typeStatsQuery = "
            SELECT booking_type, COUNT(*) as count, SUM(total_amount) as revenue
            FROM bookings 
            WHERE created_at BETWEEN ? AND ?
            GROUP BY booking_type
        ";
        
        $typeStatsStmt = $this->db->executeQuery($typeStatsQuery, [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
        $typeStats = $typeStatsStmt->fetchAll();
        
        // Status distribution
        $statusStatsQuery = "
            SELECT status, COUNT(*) as count
            FROM bookings 
            WHERE created_at BETWEEN ? AND ?
            GROUP BY status
        ";
        
        $statusStatsStmt = $this->db->executeQuery($statusStatsQuery, [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
        $statusStats = $statusStatsStmt->fetchAll();
        
        // Daily bookings
        $dailyStatsQuery = "
            SELECT DATE(created_at) as date, COUNT(*) as bookings, SUM(total_amount) as revenue
            FROM bookings 
            WHERE created_at BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ";
        
        $dailyStatsStmt = $this->db->executeQuery($dailyStatsQuery, [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
        $dailyStats = $dailyStatsStmt->fetchAll();
        
        $stats = [
            'by_type' => $typeStats,
            'by_status' => $statusStats,
            'daily' => $dailyStats,
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo
            ]
        ];
        
        sendApiResponse($stats, 'Booking statistics retrieved successfully');
    }
    
    /**
     * Get all bookings (Admin only)
     */
    private function getAllBookings() {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        
        $status = $_GET['status'] ?? null;
        $type = $_GET['type'] ?? null;
        $userId = $_GET['user_id'] ?? null;
        
        $whereClause = 'WHERE 1=1';
        $params = [];
        
        if ($status && in_array($status, ['confirmed', 'cancelled', 'completed', 'pending'])) {
            $whereClause .= ' AND b.status = ?';
            $params[] = $status;
        }
        
        if ($type && in_array($type, ['movie', 'event', 'restaurant'])) {
            $whereClause .= ' AND b.booking_type = ?';
            $params[] = $type;
        }
        
        if ($userId && is_numeric($userId)) {
            $whereClause .= ' AND b.user_id = ?';
            $params[] = $userId;
        }
        
        $query = "
            SELECT b.*, u.name as user_name, u.email as user_email
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.id
            $whereClause 
            ORDER BY b.created_at DESC 
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->executeQuery($query, $params);
        $bookings = $stmt->fetchAll();
        
        foreach ($bookings as &$booking) {
            $booking['booking_details'] = json_decode($booking['booking_details'] ?? '{}');
            $booking = $this->enrichBookingDetails($booking);
        }
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM bookings b $whereClause";
        $countParams = array_slice($params, 0, -2);
        $countStmt = $this->db->executeQuery($countQuery, $countParams);
        $total = $countStmt->fetch()['total'];
        
        $meta = [
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => (int)$total,
                'total_pages' => ceil($total / $limit)
            ]
        ];
        
        sendApiResponse($bookings, 'All bookings retrieved successfully', 200, $meta);
    }
    
    /**
     * Create new booking (generic)
     */
    private function createBooking($data) {
        $validator = new Validator($data);
        $validator->required(['booking_type', 'reference_id', 'booking_date', 'total_amount', 'quantity'])
                  ->in('booking_type', ['movie', 'event', 'restaurant'])
                  ->numeric('reference_id')
                  ->datetime('booking_date')
                  ->numeric('total_amount', 0)
                  ->numeric('quantity', 1);
        
        if (!$validator->isValid()) {
            sendApiError('Validation failed', 400, $validator->getErrors());
        }
        
        switch ($data['booking_type']) {
            case 'movie':
                $this->bookMovieTickets($data);
                break;
            case 'event':
                $this->bookEventTickets($data);
                break;
            case 'restaurant':
                $this->bookRestaurantTable($data);
                break;
            default:
                sendApiError('Invalid booking type', 400);
        }
    }
    
    /**
     * Book movie tickets
     */
    private function bookMovieTickets($data) {
        $userId = AuthHelper::getCurrentUserId();
        
        $validator = new Validator($data);
        $validator->required(['showtime_id', 'seats', 'total_amount'])
                  ->numeric('showtime_id')
                  ->array('seats')
                  ->numeric('total_amount', 0);
        
        if (!$validator->isValid()) {
            sendApiError('Validation failed', 400, $validator->getErrors());
        }
        
        $this->db->beginTransaction();
        
        try {
            // Verify showtime exists and get details
            $showtimeQuery = "
                SELECT s.*, m.title as movie_title, m.poster_url
                FROM showtimes s
                JOIN movies m ON s.movie_id = m.id
                WHERE s.id = ?
            ";
            
            $showtimeStmt = $this->db->executeQuery($showtimeQuery, [$data['showtime_id']]);
            $showtime = $showtimeStmt->fetch();
            
            if (!$showtime) {
                throw new Exception('Showtime not found');
            }
            
            // Check seat availability
            $seatCount = count($data['seats']);
            if ($showtime['available_seats'] < $seatCount) {
                throw new Exception('Not enough seats available');
            }
            
            // Create booking
            $bookingQuery = "
                INSERT INTO bookings (user_id, booking_type, reference_id, status, booking_date,
                                    total_amount, quantity, booking_details, payment_status, created_at)
                VALUES (?, 'movie', ?, 'pending', ?, ?, ?, ?, 'pending', NOW())
            ";
            
            $bookingDetails = [
                'showtime_id' => $data['showtime_id'],
                'movie_title' => $showtime['movie_title'],
                'theater_name' => $showtime['theater_name'],
                'showtime' => $showtime['showtime'],
                'seats' => $data['seats'],
                'poster_url' => $showtime['poster_url']
            ];
            
            $bookingParams = [
                $userId,
                $showtime['movie_id'],
                $showtime['showtime'],
                $data['total_amount'],
                $seatCount,
                json_encode($bookingDetails)
            ];
            
            $this->db->executeQuery($bookingQuery, $bookingParams);
            $bookingId = $this->db->getLastInsertId();
            
            // Update available seats
            $updateSeatsQuery = "UPDATE showtimes SET available_seats = available_seats - ? WHERE id = ?";
            $this->db->executeQuery($updateSeatsQuery, [$seatCount, $data['showtime_id']]);
            
            $this->db->commit();
            
            sendApiResponse(['booking_id' => $bookingId], 'Movie tickets booked successfully', 201);
            
        } catch (Exception $e) {
            $this->db->rollback();
            sendApiError($e->getMessage(), 400);
        }
    }
    
    /**
     * Book event tickets
     */
    private function bookEventTickets($data) {
        $userId = AuthHelper::getCurrentUserId();
        
        $validator = new Validator($data);
        $validator->required(['event_id', 'ticket_type_id', 'quantity', 'total_amount'])
                  ->numeric('event_id')
                  ->numeric('ticket_type_id')
                  ->numeric('quantity', 1)
                  ->numeric('total_amount', 0);
        
        if (!$validator->isValid()) {
            sendApiError('Validation failed', 400, $validator->getErrors());
        }
        
        $this->db->beginTransaction();
        
        try {
            // Verify event and ticket type
            $eventQuery = "
                SELECT e.*, et.ticket_type, et.price, et.quantity_available, et.quantity_sold
                FROM events e
                JOIN event_tickets et ON e.id = et.event_id
                WHERE e.id = ? AND et.id = ?
            ";
            
            $eventStmt = $this->db->executeQuery($eventQuery, [$data['event_id'], $data['ticket_type_id']]);
            $event = $eventStmt->fetch();
            
            if (!$event) {
                throw new Exception('Event or ticket type not found');
            }
            
            // Check availability
            $availableTickets = $event['quantity_available'] - $event['quantity_sold'];
            if ($availableTickets < $data['quantity']) {
                throw new Exception('Not enough tickets available');
            }
            
            // Create booking
            $bookingQuery = "
                INSERT INTO bookings (user_id, booking_type, reference_id, status, booking_date,
                                    total_amount, quantity, booking_details, payment_status, created_at)
                VALUES (?, 'event', ?, 'pending', ?, ?, ?, ?, 'pending', NOW())
            ";
            
            $bookingDetails = [
                'event_id' => $data['event_id'],
                'event_title' => $event['title'],
                'event_date' => $event['event_date'],
                'event_time' => $event['event_time'],
                'venue_name' => $event['venue_name'],
                'location' => $event['location'],
                'ticket_type' => $event['ticket_type'],
                'ticket_type_id' => $data['ticket_type_id']
            ];
            
            $bookingParams = [
                $userId,
                $data['event_id'],
                $event['event_date'] . ' ' . ($event['event_time'] ?? '00:00:00'),
                $data['total_amount'],
                $data['quantity'],
                json_encode($bookingDetails)
            ];
            
            $this->db->executeQuery($bookingQuery, $bookingParams);
            $bookingId = $this->db->getLastInsertId();
            
            // Update ticket sales
            $updateTicketsQuery = "UPDATE event_tickets SET quantity_sold = quantity_sold + ? WHERE id = ?";
            $this->db->executeQuery($updateTicketsQuery, [$data['quantity'], $data['ticket_type_id']]);
            
            // Update event attendees
            $updateEventQuery = "UPDATE events SET current_attendees = current_attendees + ? WHERE id = ?";
            $this->db->executeQuery($updateEventQuery, [$data['quantity'], $data['event_id']]);
            
            $this->db->commit();
            
            sendApiResponse(['booking_id' => $bookingId], 'Event tickets booked successfully', 201);
            
        } catch (Exception $e) {
            $this->db->rollback();
            sendApiError($e->getMessage(), 400);
        }
    }
    
    /**
     * Book restaurant table
     */
    private function bookRestaurantTable($data) {
        $userId = AuthHelper::getCurrentUserId();
        
        $validator = new Validator($data);
        $validator->required(['restaurant_id', 'booking_date', 'booking_time', 'party_size'])
                  ->numeric('restaurant_id')
                  ->date('booking_date')
                  ->time('booking_time')
                  ->numeric('party_size', 1, 20);
        
        if (!$validator->isValid()) {
            sendApiError('Validation failed', 400, $validator->getErrors());
        }
        
        $this->db->beginTransaction();
        
        try {
            // Verify restaurant exists
            $restaurantQuery = "SELECT * FROM restaurants WHERE id = ? AND is_active = 1";
            $restaurantStmt = $this->db->executeQuery($restaurantQuery, [$data['restaurant_id']]);
            $restaurant = $restaurantStmt->fetch();
            
            if (!$restaurant) {
                throw new Exception('Restaurant not found');
            }
            
            // Check table availability (simplified - real implementation would be more complex)
            $availabilityQuery = "
                SELECT COUNT(*) as booking_count
                FROM bookings 
                WHERE booking_type = 'restaurant' 
                  AND reference_id = ? 
                  AND DATE(booking_date) = ? 
                  AND TIME(booking_date) = ?
                  AND status = 'confirmed'
            ";
            
            $availabilityStmt = $this->db->executeQuery($availabilityQuery, [
                $data['restaurant_id'],
                $data['booking_date'],
                $data['booking_time']
            ]);
            
            $bookingCount = $availabilityStmt->fetch()['booking_count'];
            
            if ($bookingCount >= 10) { // Assume max 10 tables
                throw new Exception('No tables available at requested time');
            }
            
            // Create booking
            $bookingDateTime = $data['booking_date'] . ' ' . $data['booking_time'];
            
            $bookingQuery = "
                INSERT INTO bookings (user_id, booking_type, reference_id, status, booking_date,
                                    total_amount, quantity, special_requests, booking_details, 
                                    payment_status, created_at)
                VALUES (?, 'restaurant', ?, 'confirmed', ?, 0, ?, ?, ?, 'not_required', NOW())
            ";
            
            $bookingDetails = [
                'restaurant_id' => $data['restaurant_id'],
                'restaurant_name' => $restaurant['name'],
                'restaurant_address' => $restaurant['address'],
                'party_size' => $data['party_size'],
                'booking_date' => $data['booking_date'],
                'booking_time' => $data['booking_time']
            ];
            
            $bookingParams = [
                $userId,
                $data['restaurant_id'],
                $bookingDateTime,
                $data['party_size'],
                $data['special_requests'] ?? null,
                json_encode($bookingDetails)
            ];
            
            $this->db->executeQuery($bookingQuery, $bookingParams);
            $bookingId = $this->db->getLastInsertId();
            
            $this->db->commit();
            
            sendApiResponse(['booking_id' => $bookingId], 'Restaurant table booked successfully', 201);
            
        } catch (Exception $e) {
            $this->db->rollback();
            sendApiError($e->getMessage(), 400);
        }
    }
    
    /**
     * Validate booking details
     */
    private function validateBooking($data) {
        $validator = new Validator($data);
        $validator->required(['booking_type', 'reference_id'])
                  ->in('booking_type', ['movie', 'event', 'restaurant'])
                  ->numeric('reference_id');
        
        if (!$validator->isValid()) {
            sendApiError('Validation failed', 400, $validator->getErrors());
        }
        
        $validationResult = ['valid' => false, 'message' => '', 'details' => []];
        
        switch ($data['booking_type']) {
            case 'movie':
                $validationResult = $this->validateMovieBooking($data);
                break;
            case 'event':
                $validationResult = $this->validateEventBooking($data);
                break;
            case 'restaurant':
                $validationResult = $this->validateRestaurantBooking($data);
                break;
        }
        
        sendApiResponse($validationResult, 'Booking validation completed');
    }
    
    /**
     * Cancel booking
     */
    private function cancelBooking($id) {
        if (!Validator::validateId($id)) {
            sendApiError('Invalid booking ID', 400);
        }
        
        $userId = AuthHelper::getCurrentUserId();
        $isAdmin = AuthHelper::isAdmin();
        
        $this->db->beginTransaction();
        
        try {
            // Get booking details
            $whereClause = $isAdmin ? 'WHERE id = ?' : 'WHERE id = ? AND user_id = ?';
            $params = $isAdmin ? [$id] : [$id, $userId];
            
            $bookingQuery = "SELECT * FROM bookings $whereClause";
            $bookingStmt = $this->db->executeQuery($bookingQuery, $params);
            $booking = $bookingStmt->fetch();
            
            if (!$booking) {
                throw new Exception('Booking not found');
            }
            
            if ($booking['status'] === 'cancelled') {
                throw new Exception('Booking is already cancelled');
            }
            
            // Update booking status
            $updateQuery = "UPDATE bookings SET status = 'cancelled', updated_at = NOW() WHERE id = ?";
            $this->db->executeQuery($updateQuery, [$id]);
            
            // Restore availability based on booking type
            $this->restoreAvailability($booking);
            
            $this->db->commit();
            
            sendApiResponse(null, 'Booking cancelled successfully');
            
        } catch (Exception $e) {
            $this->db->rollback();
            sendApiError($e->getMessage(), 400);
        }
    }
    
    /**
     * Update booking
     */
    private function updateBooking($id, $data) {
        if (!Validator::validateId($id)) {
            sendApiError('Invalid booking ID', 400);
        }
        
        $userId = AuthHelper::getCurrentUserId();
        $isAdmin = AuthHelper::isAdmin();
        
        // Get current booking
        $whereClause = $isAdmin ? 'WHERE id = ?' : 'WHERE id = ? AND user_id = ?';
        $params = $isAdmin ? [$id] : [$id, $userId];
        
        $bookingQuery = "SELECT * FROM bookings $whereClause";
        $bookingStmt = $this->db->executeQuery($bookingQuery, $params);
        $booking = $bookingStmt->fetch();
        
        if (!$booking) {
            sendApiError('Booking not found', 404);
        }
        
        $updateFields = [];
        $updateParams = [];
        
        $allowedFields = $isAdmin 
            ? ['status', 'payment_status', 'special_requests'] 
            : ['special_requests'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                $updateParams[] = $data[$field];
            }
        }
        
        if (empty($updateFields)) {
            sendApiError('No valid fields to update', 400);
        }
        
        $updateFields[] = "updated_at = NOW()";
        $updateParams[] = $id;
        
        $updateQuery = "UPDATE bookings SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $this->db->executeQuery($updateQuery, $updateParams);
        
        sendApiResponse(null, 'Booking updated successfully');
    }
    
    /**
     * Delete booking (Admin only)
     */
    private function deleteBooking($id) {
        if (!Validator::validateId($id)) {
            sendApiError('Invalid booking ID', 400);
        }
        
        $this->db->beginTransaction();
        
        try {
            // Get booking details before deletion
            $bookingQuery = "SELECT * FROM bookings WHERE id = ?";
            $bookingStmt = $this->db->executeQuery($bookingQuery, [$id]);
            $booking = $bookingStmt->fetch();
            
            if (!$booking) {
                throw new Exception('Booking not found');
            }
            
            // Delete booking
            $deleteQuery = "DELETE FROM bookings WHERE id = ?";
            $this->db->executeQuery($deleteQuery, [$id]);
            
            // Restore availability
            $this->restoreAvailability($booking);
            
            $this->db->commit();
            
            sendApiResponse(null, 'Booking deleted successfully');
            
        } catch (Exception $e) {
            $this->db->rollback();
            sendApiError($e->getMessage(), 400);
        }
    }
    
    /**
     * Enrich booking with additional details
     */
    private function enrichBookingDetails($booking) {
        switch ($booking['booking_type']) {
            case 'movie':
                // Get movie details
                $movieQuery = "SELECT title, poster_url FROM movies WHERE id = ?";
                $movieStmt = $this->db->executeQuery($movieQuery, [$booking['reference_id']]);
                $movie = $movieStmt->fetch();
                if ($movie) {
                    $booking['content_title'] = $movie['title'];
                    $booking['content_image'] = $movie['poster_url'];
                }
                break;
                
            case 'event':
                // Get event details
                $eventQuery = "SELECT title, image_url FROM events WHERE id = ?";
                $eventStmt = $this->db->executeQuery($eventQuery, [$booking['reference_id']]);
                $event = $eventStmt->fetch();
                if ($event) {
                    $booking['content_title'] = $event['title'];
                    $booking['content_image'] = $event['image_url'];
                }
                break;
                
            case 'restaurant':
                // Get restaurant details
                $restaurantQuery = "SELECT name, image_url FROM restaurants WHERE id = ?";
                $restaurantStmt = $this->db->executeQuery($restaurantQuery, [$booking['reference_id']]);
                $restaurant = $restaurantStmt->fetch();
                if ($restaurant) {
                    $booking['content_title'] = $restaurant['name'];
                    $booking['content_image'] = $restaurant['image_url'];
                }
                break;
        }
        
        return $booking;
    }
    
    /**
     * Restore availability when booking is cancelled/deleted
     */
    private function restoreAvailability($booking) {
        switch ($booking['booking_type']) {
            case 'movie':
                $details = json_decode($booking['booking_details'], true);
                if (isset($details['showtime_id'])) {
                    $updateQuery = "UPDATE showtimes SET available_seats = available_seats + ? WHERE id = ?";
                    $this->db->executeQuery($updateQuery, [$booking['quantity'], $details['showtime_id']]);
                }
                break;
                
            case 'event':
                $details = json_decode($booking['booking_details'], true);
                if (isset($details['event_id'], $details['ticket_type_id'])) {
                    $updateTicketsQuery = "UPDATE event_tickets SET quantity_sold = quantity_sold - ? WHERE id = ?";
                    $this->db->executeQuery($updateTicketsQuery, [$booking['quantity'], $details['ticket_type_id']]);
                    
                    $updateEventQuery = "UPDATE events SET current_attendees = current_attendees - ? WHERE id = ?";
                    $this->db->executeQuery($updateEventQuery, [$booking['quantity'], $details['event_id']]);
                }
                break;
                
            case 'restaurant':
                // Restaurant bookings don't typically need availability restoration
                // as they're time-based, but could implement table management here
                break;
        }
    }
    
    /**
     * Validate movie booking
     */
    private function validateMovieBooking($data) {
        // Implementation would check showtime availability, seat availability, etc.
        return ['valid' => true, 'message' => 'Movie booking is valid', 'details' => []];
    }
    
    /**
     * Validate event booking
     */
    private function validateEventBooking($data) {
        // Implementation would check event availability, ticket availability, etc.
        return ['valid' => true, 'message' => 'Event booking is valid', 'details' => []];
    }
    
    /**
     * Validate restaurant booking
     */
    private function validateRestaurantBooking($data) {
        // Implementation would check restaurant hours, table availability, etc.
        return ['valid' => true, 'message' => 'Restaurant booking is valid', 'details' => []];
    }
}

// Handle the request
$api = new BookingsAPI();
$api->handleRequest();
?>