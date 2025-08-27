<?php
/**
 * Events API Endpoint
 * Handles all event-related operations
 */

require_once '../config/database.php';
require_once '../config/settings.php';
require_once '../utils/auth.php';
require_once '../utils/validation.php';

class EventsAPI {
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
            error_log("Events API Error: " . $e->getMessage());
            sendApiError('Internal server error', 500);
        }
    }
    
    /**
     * Handle GET requests
     */
    private function handleGet($path) {
        $pathParts = explode('/', trim($path, '/'));
        
        if (empty($pathParts[0])) {
            // GET /events - Get all events
            $this->getAllEvents();
        } elseif (is_numeric($pathParts[0])) {
            // GET /events/{id} - Get specific event
            $this->getEvent($pathParts[0]);
        } elseif ($pathParts[0] === 'search') {
            // GET /events/search - Search events
            $this->searchEvents();
        } elseif ($pathParts[0] === 'categories') {
            // GET /events/categories - Get event categories
            $this->getCategories();
        } elseif ($pathParts[0] === 'upcoming') {
            // GET /events/upcoming - Get upcoming events
            $this->getUpcomingEvents();
        } elseif ($pathParts[0] === 'featured') {
            // GET /events/featured - Get featured events
            $this->getFeaturedEvents();
        } elseif ($pathParts[0] === 'by-location') {
            // GET /events/by-location - Get events by location
            $this->getEventsByLocation();
        } elseif ($pathParts[0] === 'tickets') {
            if (isset($pathParts[1]) && is_numeric($pathParts[1])) {
                // GET /events/tickets/{event_id} - Get ticket types for event
                $this->getEventTickets($pathParts[1]);
            } else {
                sendApiError('Event ID required for tickets', 400);
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
        
        if ($path === 'tickets') {
            // POST /events/tickets - Add ticket type
            AuthHelper::requireAuth();
            $this->addTicketType($data);
        } else {
            // POST /events - Add new event (admin only)
            AuthHelper::requireAdmin();
            $this->addEvent($data);
        }
    }
    
    /**
     * Handle PUT requests
     */
    private function handlePut($path) {
        $data = json_decode(file_get_contents('php://input'), true);
        $pathParts = explode('/', trim($path, '/'));
        
        if (is_numeric($pathParts[0])) {
            // PUT /events/{id} - Update event (admin only)
            AuthHelper::requireAdmin();
            $this->updateEvent($pathParts[0], $data);
        } else {
            sendApiError('Event ID required', 400);
        }
    }
    
    /**
     * Handle DELETE requests
     */
    private function handleDelete($path) {
        AuthHelper::requireAdmin();
        $pathParts = explode('/', trim($path, '/'));
        
        if (is_numeric($pathParts[0])) {
            // DELETE /events/{id} - Delete event
            $this->deleteEvent($pathParts[0]);
        } else {
            sendApiError('Event ID required', 400);
        }
    }
    
    /**
     * Get all events with pagination and filters
     */
    private function getAllEvents() {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(getSetting('max_page_size'), max(1, (int)($_GET['limit'] ?? getSetting('default_page_size'))));
        $offset = ($page - 1) * $limit;
        
        $category = $_GET['category'] ?? null;
        $location = $_GET['location'] ?? null;
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;
        $minPrice = $_GET['min_price'] ?? null;
        $maxPrice = $_GET['max_price'] ?? null;
        $sort = $_GET['sort'] ?? 'event_date';
        $order = strtoupper($_GET['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        
        // Build query
        $whereClause = 'WHERE 1=1';
        $params = [];
        
        if ($category) {
            $whereClause .= ' AND category = ?';
            $params[] = $category;
        }
        
        if ($location) {
            $whereClause .= ' AND (location LIKE ? OR venue_name LIKE ?)';
            $locationPattern = "%$location%";
            $params[] = $locationPattern;
            $params[] = $locationPattern;
        }
        
        if ($dateFrom) {
            $whereClause .= ' AND event_date >= ?';
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $whereClause .= ' AND event_date <= ?';
            $params[] = $dateTo;
        }
        
        if ($minPrice !== null) {
            $whereClause .= ' AND price >= ?';
            $params[] = $minPrice;
        }
        
        if ($maxPrice !== null) {
            $whereClause .= ' AND price <= ?';
            $params[] = $maxPrice;
        }
        
        // Validate sort field
        $allowedSortFields = ['title', 'event_date', 'price', 'location', 'created_at'];
        if (!in_array($sort, $allowedSortFields)) {
            $sort = 'event_date';
        }
        
        $query = "
            SELECT id, title, description, category, event_date, event_time,
                   location, venue_name, price, image_url, is_featured,
                   max_attendees, current_attendees, created_at, updated_at
            FROM events 
            $whereClause 
            ORDER BY $sort $order 
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->executeQuery($query, $params);
        $events = $stmt->fetchAll();
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM events $whereClause";
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
            'filters' => compact('category', 'location', 'dateFrom', 'dateTo', 'minPrice', 'maxPrice', 'sort', 'order')
        ];
        
        sendApiResponse($events, 'Events retrieved successfully', 200, $meta);
    }
    
    /**
     * Get specific event by ID
     */
    private function getEvent($id) {
        if (!Validator::validateId($id)) {
            sendApiError('Invalid event ID', 400);
        }
        
        $query = "
            SELECT id, title, description, category, event_date, event_time,
                   location, venue_name, venue_address, price, image_url,
                   organizer_name, organizer_contact, is_featured,
                   max_attendees, current_attendees, tags, requirements,
                   created_at, updated_at
            FROM events 
            WHERE id = ?
        ";
        
        $stmt = $this->db->executeQuery($query, [$id]);
        $event = $stmt->fetch();
        
        if (!$event) {
            sendApiError('Event not found', 404);
        }
        
        // Parse JSON fields
        $event['tags'] = json_decode($event['tags'] ?? '[]');
        $event['requirements'] = json_decode($event['requirements'] ?? '[]');
        
        // Calculate availability
        $event['available_spots'] = $event['max_attendees'] - $event['current_attendees'];
        $event['is_full'] = $event['current_attendees'] >= $event['max_attendees'];
        
        sendApiResponse($event, 'Event retrieved successfully');
    }
    
    /**
     * Search events
     */
    private function searchEvents() {
        $query = $_GET['q'] ?? '';
        
        if (strlen($query) < 2) {
            sendApiError('Search query must be at least 2 characters', 400);
        }
        
        $searchQuery = "
            SELECT id, title, description, category, event_date, event_time,
                   location, venue_name, price, image_url, is_featured
            FROM events 
            WHERE title LIKE ? 
               OR description LIKE ? 
               OR category LIKE ?
               OR location LIKE ?
               OR venue_name LIKE ?
               OR organizer_name LIKE ?
               OR tags LIKE ?
            ORDER BY 
                CASE 
                    WHEN title LIKE ? THEN 1
                    WHEN description LIKE ? THEN 2
                    ELSE 3
                END,
                event_date ASC
            LIMIT 50
        ";
        
        $searchTerm = "%$query%";
        $exactTerm = "$query%";
        
        $params = [
            $searchTerm, $searchTerm, $searchTerm, $searchTerm, 
            $searchTerm, $searchTerm, $searchTerm,
            $exactTerm, $exactTerm
        ];
        
        $stmt = $this->db->executeQuery($searchQuery, $params);
        $events = $stmt->fetchAll();
        
        sendApiResponse($events, 'Search completed successfully');
    }
    
    /**
     * Get event categories
     */
    private function getCategories() {
        $query = "SELECT DISTINCT category FROM events WHERE category IS NOT NULL ORDER BY category";
        $stmt = $this->db->executeQuery($query);
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        sendApiResponse($categories, 'Categories retrieved successfully');
    }
    
    /**
     * Get upcoming events
     */
    private function getUpcomingEvents() {
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
        $days = min(365, max(1, (int)($_GET['days'] ?? 30)));
        
        $query = "
            SELECT id, title, description, category, event_date, event_time,
                   location, venue_name, price, image_url, is_featured,
                   max_attendees, current_attendees
            FROM events
            WHERE event_date >= CURDATE() 
              AND event_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
            ORDER BY event_date ASC, event_time ASC
            LIMIT ?
        ";
        
        $stmt = $this->db->executeQuery($query, [$days, $limit]);
        $events = $stmt->fetchAll();
        
        sendApiResponse($events, 'Upcoming events retrieved successfully');
    }
    
    /**
     * Get featured events
     */
    private function getFeaturedEvents() {
        $limit = min(20, max(1, (int)($_GET['limit'] ?? 6)));
        
        $query = "
            SELECT id, title, description, category, event_date, event_time,
                   location, venue_name, price, image_url,
                   max_attendees, current_attendees
            FROM events
            WHERE is_featured = 1 AND event_date >= CURDATE()
            ORDER BY event_date ASC
            LIMIT ?
        ";
        
        $stmt = $this->db->executeQuery($query, [$limit]);
        $events = $stmt->fetchAll();
        
        sendApiResponse($events, 'Featured events retrieved successfully');
    }
    
    /**
     * Get events by location
     */
    private function getEventsByLocation() {
        $location = $_GET['location'] ?? null;
        if (!$location) {
            sendApiError('Location parameter required', 400);
        }
        
        $radius = min(100, max(1, (int)($_GET['radius'] ?? 10))); // km
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        
        $query = "
            SELECT id, title, description, category, event_date, event_time,
                   location, venue_name, price, image_url,
                   max_attendees, current_attendees
            FROM events
            WHERE location LIKE ? OR venue_name LIKE ?
              AND event_date >= CURDATE()
            ORDER BY event_date ASC
            LIMIT ?
        ";
        
        $locationPattern = "%$location%";
        $stmt = $this->db->executeQuery($query, [$locationPattern, $locationPattern, $limit]);
        $events = $stmt->fetchAll();
        
        sendApiResponse($events, 'Events by location retrieved successfully');
    }
    
    /**
     * Get ticket types for an event
     */
    private function getEventTickets($eventId) {
        if (!Validator::validateId($eventId)) {
            sendApiError('Invalid event ID', 400);
        }
        
        $query = "
            SELECT id, event_id, ticket_type, price, quantity_available,
                   quantity_sold, description, benefits, created_at
            FROM event_tickets
            WHERE event_id = ?
            ORDER BY price ASC
        ";
        
        $stmt = $this->db->executeQuery($query, [$eventId]);
        $tickets = $stmt->fetchAll();
        
        // Parse JSON benefits
        foreach ($tickets as &$ticket) {
            $ticket['benefits'] = json_decode($ticket['benefits'] ?? '[]');
            $ticket['quantity_remaining'] = $ticket['quantity_available'] - $ticket['quantity_sold'];
        }
        
        sendApiResponse($tickets, 'Event tickets retrieved successfully');
    }
    
    /**
     * Add new event (Admin only)
     */
    private function addEvent($data) {
        $validator = new Validator($data);
        
        $validator->required(['title', 'description', 'category', 'event_date', 'location', 'max_attendees', 'price'])
                  ->string('title', 1, 255)
                  ->string('description', 1, 2000)
                  ->string('category', 1, 100)
                  ->date('event_date')
                  ->string('location', 1, 255)
                  ->numeric('max_attendees', 1)
                  ->numeric('price', 0);
        
        if (!$validator->isValid()) {
            sendApiError('Validation failed', 400, $validator->getErrors());
        }
        
        $query = "
            INSERT INTO events (title, description, category, event_date, event_time,
                              location, venue_name, venue_address, price, image_url,
                              organizer_name, organizer_contact, is_featured,
                              max_attendees, current_attendees, tags, requirements, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, NOW())
        ";
        
        $params = [
            $data['title'],
            $data['description'],
            $data['category'],
            $data['event_date'],
            $data['event_time'] ?? null,
            $data['location'],
            $data['venue_name'] ?? null,
            $data['venue_address'] ?? null,
            $data['price'],
            $data['image_url'] ?? null,
            $data['organizer_name'] ?? null,
            $data['organizer_contact'] ?? null,
            $data['is_featured'] ?? 0,
            $data['max_attendees'],
            json_encode($data['tags'] ?? []),
            json_encode($data['requirements'] ?? [])
        ];
        
        $this->db->executeQuery($query, $params);
        $eventId = $this->db->getLastInsertId();
        
        sendApiResponse(['id' => $eventId], 'Event added successfully', 201);
    }
    
    /**
     * Update event (Admin only)
     */
    private function updateEvent($id, $data) {
        if (!Validator::validateId($id)) {
            sendApiError('Invalid event ID', 400);
        }
        
        // Check if event exists
        $checkQuery = "SELECT id FROM events WHERE id = ?";
        $checkStmt = $this->db->executeQuery($checkQuery, [$id]);
        if (!$checkStmt->fetch()) {
            sendApiError('Event not found', 404);
        }
        
        $updateFields = [];
        $params = [];
        
        $allowedFields = ['title', 'description', 'category', 'event_date', 'event_time',
                         'location', 'venue_name', 'venue_address', 'price', 'image_url',
                         'organizer_name', 'organizer_contact', 'is_featured',
                         'max_attendees', 'tags', 'requirements'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                if (in_array($field, ['tags', 'requirements'])) {
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
        
        $query = "UPDATE events SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $this->db->executeQuery($query, $params);
        
        sendApiResponse(null, 'Event updated successfully');
    }
    
    /**
     * Delete event (Admin only)
     */
    private function deleteEvent($id) {
        if (!Validator::validateId($id)) {
            sendApiError('Invalid event ID', 400);
        }
        
        $query = "DELETE FROM events WHERE id = ?";
        $stmt = $this->db->executeQuery($query, [$id]);
        
        if ($stmt->rowCount() === 0) {
            sendApiError('Event not found', 404);
        }
        
        sendApiResponse(null, 'Event deleted successfully');
    }
    
    /**
     * Add ticket type for event
     */
    private function addTicketType($data) {
        $validator = new Validator($data);
        
        $validator->required(['event_id', 'ticket_type', 'price', 'quantity_available'])
                  ->numeric('event_id')
                  ->string('ticket_type', 1, 100)
                  ->numeric('price', 0)
                  ->numeric('quantity_available', 1);
        
        if (!$validator->isValid()) {
            sendApiError('Validation failed', 400, $validator->getErrors());
        }
        
        // Verify event exists
        $eventCheck = "SELECT id FROM events WHERE id = ?";
        $eventStmt = $this->db->executeQuery($eventCheck, [$data['event_id']]);
        if (!$eventStmt->fetch()) {
            sendApiError('Event not found', 404);
        }
        
        $query = "
            INSERT INTO event_tickets (event_id, ticket_type, price, quantity_available,
                                     quantity_sold, description, benefits, created_at)
            VALUES (?, ?, ?, ?, 0, ?, ?, NOW())
        ";
        
        $params = [
            $data['event_id'],
            $data['ticket_type'],
            $data['price'],
            $data['quantity_available'],
            $data['description'] ?? null,
            json_encode($data['benefits'] ?? [])
        ];
        
        $this->db->executeQuery($query, $params);
        $ticketId = $this->db->getLastInsertId();
        
        sendApiResponse(['id' => $ticketId], 'Ticket type added successfully', 201);
    }
}

// Handle the request
$api = new EventsAPI();
$api->handleRequest();
?>