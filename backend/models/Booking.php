<?php

class Booking {
    private $conn;
    private $table = 'bookings';
    
    // Booking properties
    public $id;
    public $user_id;
    public $booking_type; // 'movie', 'restaurant', 'event'
    public $movie_id;
    public $restaurant_id;
    public $event_id;
    public $booking_date;
    public $booking_time;
    public $quantity;
    public $party_size;
    public $total_amount;
    public $status; // 'pending', 'confirmed', 'cancelled', 'completed'
    public $payment_status; // 'pending', 'paid', 'refunded'
    public $special_requests;
    public $confirmation_code;
    public $created_at;
    public $updated_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create booking
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                 SET user_id = :user_id,
                     booking_type = :booking_type,
                     movie_id = :movie_id,
                     restaurant_id = :restaurant_id,
                     event_id = :event_id,
                     booking_date = :booking_date,
                     booking_time = :booking_time,
                     quantity = :quantity,
                     party_size = :party_size,
                     total_amount = :total_amount,
                     status = :status,
                     payment_status = :payment_status,
                     special_requests = :special_requests,
                     confirmation_code = :confirmation_code,
                     created_at = NOW()";
        
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $this->booking_type = htmlspecialchars(strip_tags($this->booking_type));
        $this->special_requests = htmlspecialchars(strip_tags($this->special_requests));
        $this->status = $this->status ?? 'pending';
        $this->payment_status = $this->payment_status ?? 'pending';
        
        // Generate confirmation code if not provided
        if(empty($this->confirmation_code)) {
            $this->confirmation_code = $this->generateConfirmationCode();
        }
        
        // Bind data
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':booking_type', $this->booking_type);
        $stmt->bindParam(':movie_id', $this->movie_id);
        $stmt->bindParam(':restaurant_id', $this->restaurant_id);
        $stmt->bindParam(':event_id', $this->event_id);
        $stmt->bindParam(':booking_date', $this->booking_date);
        $stmt->bindParam(':booking_time', $this->booking_time);
        $stmt->bindParam(':quantity', $this->quantity);
        $stmt->bindParam(':party_size', $this->party_size);
        $stmt->bindParam(':total_amount', $this->total_amount);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':payment_status', $this->payment_status);
        $stmt->bindParam(':special_requests', $this->special_requests);
        $stmt->bindParam(':confirmation_code', $this->confirmation_code);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    // Read booking by ID
    public function readOne() {
        $query = "SELECT b.*,
                         u.full_name as user_name, u.email as user_email,
                         m.title as movie_title,
                         r.name as restaurant_name,
                         e.title as event_title
                  FROM " . $this->table . " b
                  LEFT JOIN users u ON b.user_id = u.id
                  LEFT JOIN movies m ON b.movie_id = m.id
                  LEFT JOIN restaurants r ON b.restaurant_id = r.id
                  LEFT JOIN events e ON b.event_id = e.id
                  WHERE b.id = :id LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->user_id = $row['user_id'];
            $this->booking_type = $row['booking_type'];
            $this->movie_id = $row['movie_id'];
            $this->restaurant_id = $row['restaurant_id'];
            $this->event_id = $row['event_id'];
            $this->booking_date = $row['booking_date'];
            $this->booking_time = $row['booking_time'];
            $this->quantity = $row['quantity'];
            $this->party_size = $row['party_size'];
            $this->total_amount = $row['total_amount'];
            $this->status = $row['status'];
            $this->payment_status = $row['payment_status'];
            $this->special_requests = $row['special_requests'];
            $this->confirmation_code = $row['confirmation_code'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            return $row; // Return full row with joined data
        }
        return false;
    }
    
    // Get bookings by user
    public function getByUser($user_id) {
        $query = "SELECT b.*,
                         m.title as movie_title, m.poster_url,
                         r.name as restaurant_name, r.image_url as restaurant_image,
                         e.title as event_title, e.image_url as event_image
                  FROM " . $this->table . " b
                  LEFT JOIN movies m ON b.movie_id = m.id
                  LEFT JOIN restaurants r ON b.restaurant_id = r.id
                  LEFT JOIN events e ON b.event_id = e.id
                  WHERE b.user_id = :user_id
                  ORDER BY b.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Get booking by confirmation code
    public function getByConfirmationCode($confirmation_code) {
        $query = "SELECT b.*,
                         u.full_name as user_name, u.email as user_email, u.phone as user_phone,
                         m.title as movie_title,
                         r.name as restaurant_name, r.address as restaurant_address,
                         e.title as event_title, e.venue as event_venue
                  FROM " . $this->table . " b
                  LEFT JOIN users u ON b.user_id = u.id
                  LEFT JOIN movies m ON b.movie_id = m.id
                  LEFT JOIN restaurants r ON b.restaurant_id = r.id
                  LEFT JOIN events e ON b.event_id = e.id
                  WHERE b.confirmation_code = :confirmation_code LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':confirmation_code', $confirmation_code);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Update booking
    public function update() {
        $query = "UPDATE " . $this->table . " 
                 SET booking_date = :booking_date,
                     booking_time = :booking_time,
                     quantity = :quantity,
                     party_size = :party_size,
                     total_amount = :total_amount,
                     status = :status,
                     payment_status = :payment_status,
                     special_requests = :special_requests,
                     updated_at = NOW()
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $this->special_requests = htmlspecialchars(strip_tags($this->special_requests));
        
        // Bind data
        $stmt->bindParam(':booking_date', $this->booking_date);
        $stmt->bindParam(':booking_time', $this->booking_time);
        $stmt->bindParam(':quantity', $this->quantity);
        $stmt->bindParam(':party_size', $this->party_size);
        $stmt->bindParam(':total_amount', $this->total_amount);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':payment_status', $this->payment_status);
        $stmt->bindParam(':special_requests', $this->special_requests);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    // Update booking status
    public function updateStatus($status) {
        $query = "UPDATE " . $this->table . " SET status = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    // Update payment status
    public function updatePaymentStatus($payment_status) {
        $query = "UPDATE " . $this->table . " SET payment_status = :payment_status, updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':payment_status', $payment_status);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    // Cancel booking
    public function cancel() {
        $query = "UPDATE " . $this->table . " 
                 SET status = 'cancelled', updated_at = NOW() 
                 WHERE id = :id AND status IN ('pending', 'confirmed')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    // Get upcoming bookings for user
    public function getUpcomingByUser($user_id) {
        $query = "SELECT b.*,
                         m.title as movie_title,
                         r.name as restaurant_name,
                         e.title as event_title
                  FROM " . $this->table . " b
                  LEFT JOIN movies m ON b.movie_id = m.id
                  LEFT JOIN restaurants r ON b.restaurant_id = r.id
                  LEFT JOIN events e ON b.event_id = e.id
                  WHERE b.user_id = :user_id 
                    AND b.booking_date >= CURDATE()
                    AND b.status IN ('pending', 'confirmed')
                  ORDER BY b.booking_date ASC, b.booking_time ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Get booking history for user
    public function getHistoryByUser($user_id) {
        $query = "SELECT b.*,
                         m.title as movie_title,
                         r.name as restaurant_name,
                         e.title as event_title
                  FROM " . $this->table . " b
                  LEFT JOIN movies m ON b.movie_id = m.id
                  LEFT JOIN restaurants r ON b.restaurant_id = r.id
                  LEFT JOIN events e ON b.event_id = e.id
                  WHERE b.user_id = :user_id 
                    AND (b.booking_date < CURDATE() OR b.status IN ('cancelled', 'completed'))
                  ORDER BY b.booking_date DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Get all bookings (admin)
    public function readAll($limit = 100, $offset = 0) {
        $query = "SELECT b.*,
                         u.full_name as user_name, u.email as user_email,
                         m.title as movie_title,
                         r.name as restaurant_name,
                         e.title as event_title
                  FROM " . $this->table . " b
                  LEFT JOIN users u ON b.user_id = u.id
                  LEFT JOIN movies m ON b.movie_id = m.id
                  LEFT JOIN restaurants r ON b.restaurant_id = r.id
                  LEFT JOIN events e ON b.event_id = e.id
                  ORDER BY b.created_at DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Generate confirmation code
    private function generateConfirmationCode() {
        return strtoupper(substr(uniqid(), 0, 8));
    }
    
    // Get booking statistics
    public function getStats($start_date = null, $end_date = null) {
        $where_clause = "";
        $params = [];
        
        if($start_date && $end_date) {
            $where_clause = "WHERE booking_date BETWEEN :start_date AND :end_date";
            $params = [':start_date' => $start_date, ':end_date' => $end_date];
        }
        
        $query = "SELECT 
                    booking_type,
                    status,
                    COUNT(*) as count,
                    SUM(total_amount) as total_revenue
                  FROM " . $this->table . " 
                  {$where_clause}
                  GROUP BY booking_type, status";
        
        $stmt = $this->conn->prepare($query);
        
        foreach($params as $key => $value) {
            $stmt->bindParam($key, $value);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}