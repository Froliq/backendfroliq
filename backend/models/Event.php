<?php

class Event {
    private $conn;
    private $table = 'events';
    
    // Event properties
    public $id;
    public $title;
    public $description;
    public $category;
    public $venue;
    public $address;
    public $city;
    public $event_date;
    public $event_time;
    public $duration;
    public $capacity;
    public $price;
    public $organizer_name;
    public $organizer_email;
    public $organizer_phone;
    public $image_url;
    public $status;
    public $created_at;
    public $updated_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create event
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                 SET title = :title, 
                     description = :description, 
                     category = :category, 
                     venue = :venue, 
                     address = :address,
                     city = :city,
                     event_date = :event_date,
                     event_time = :event_time,
                     duration = :duration,
                     capacity = :capacity,
                     price = :price,
                     organizer_name = :organizer_name,
                     organizer_email = :organizer_email,
                     organizer_phone = :organizer_phone,
                     image_url = :image_url,
                     status = :status,
                     created_at = NOW()";
        
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->category = htmlspecialchars(strip_tags($this->category));
        $this->venue = htmlspecialchars(strip_tags($this->venue));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->city = htmlspecialchars(strip_tags($this->city));
        $this->organizer_name = htmlspecialchars(strip_tags($this->organizer_name));
        $this->organizer_email = htmlspecialchars(strip_tags($this->organizer_email));
        $this->organizer_phone = htmlspecialchars(strip_tags($this->organizer_phone));
        $this->image_url = htmlspecialchars(strip_tags($this->image_url));
        $this->status = $this->status ?? 'active';
        
        // Bind data
        $stmt->bindParam(':category', $category);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Get events by city
    public function getByCity($city) {
        $query = "SELECT * FROM " . $this->table . " 
                 WHERE city LIKE :city AND status = 'active' AND event_date >= CURDATE() 
                 ORDER BY event_date ASC";
        
        $stmt = $this->conn->prepare($query);
        $city = "%{$city}%";
        $stmt->bindParam(':city', $city);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Search events
    public function search($keywords) {
        $query = "SELECT * FROM " . $this->table . " 
                 WHERE (title LIKE :keywords OR description LIKE :keywords OR category LIKE :keywords OR venue LIKE :keywords) 
                 AND status = 'active' AND event_date >= CURDATE() 
                 ORDER BY event_date ASC";
        
        $stmt = $this->conn->prepare($query);
        $keywords = "%{$keywords}%";
        $stmt->bindParam(':keywords', $keywords);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Get events by date range
    public function getByDateRange($start_date, $end_date) {
        $query = "SELECT * FROM " . $this->table . " 
                 WHERE event_date BETWEEN :start_date AND :end_date 
                 AND status = 'active' 
                 ORDER BY event_date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Update event
    public function update() {
        $query = "UPDATE " . $this->table . " 
                 SET title = :title, 
                     description = :description, 
                     category = :category, 
                     venue = :venue, 
                     address = :address,
                     city = :city,
                     event_date = :event_date,
                     event_time = :event_time,
                     duration = :duration,
                     capacity = :capacity,
                     price = :price,
                     organizer_name = :organizer_name,
                     organizer_email = :organizer_email,
                     organizer_phone = :organizer_phone,
                     image_url = :image_url,
                     status = :status,
                     updated_at = NOW()
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->category = htmlspecialchars(strip_tags($this->category));
        $this->venue = htmlspecialchars(strip_tags($this->venue));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->city = htmlspecialchars(strip_tags($this->city));
        $this->organizer_name = htmlspecialchars(strip_tags($this->organizer_name));
        $this->organizer_email = htmlspecialchars(strip_tags($this->organizer_email));
        $this->organizer_phone = htmlspecialchars(strip_tags($this->organizer_phone));
        $this->image_url = htmlspecialchars(strip_tags($this->image_url));
        
        // Bind data
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':category', $this->category);
        $stmt->bindParam(':venue', $this->venue);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':city', $this->city);
        $stmt->bindParam(':event_date', $this->event_date);
        $stmt->bindParam(':event_time', $this->event_time);
        $stmt->bindParam(':duration', $this->duration);
        $stmt->bindParam(':capacity', $this->capacity);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':organizer_name', $this->organizer_name);
        $stmt->bindParam(':organizer_email', $this->organizer_email);
        $stmt->bindParam(':organizer_phone', $this->organizer_phone);
        $stmt->bindParam(':image_url', $this->image_url);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    // Delete event (soft delete)
    public function delete() {
        $query = "UPDATE " . $this->table . " SET status = 'cancelled' WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    // Check event capacity
    public function checkCapacity() {
        $query = "SELECT 
                    e.capacity,
                    COALESCE(SUM(b.quantity), 0) as total_booked
                  FROM " . $this->table . " e
                  LEFT JOIN bookings b ON e.id = b.event_id 
                    AND b.status IN ('confirmed', 'pending')
                  WHERE e.id = :event_id AND e.status = 'active'
                  GROUP BY e.id, e.capacity";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':event_id', $this->id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($result) {
            return [
                'capacity' => $result['capacity'],
                'booked' => $result['total_booked'],
                'available' => $result['capacity'] - $result['total_booked']
            ];
        }
        
        return false;
    }
    
    // Get upcoming events
    public function getUpcoming($limit = 10) {
        $query = "SELECT * FROM " . $this->table . " 
                 WHERE event_date >= CURDATE() AND status = 'active' 
                 ORDER BY event_date ASC, event_time ASC
                 LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Get popular events (based on bookings)
    public function getPopular($limit = 10) {
        $query = "SELECT e.*, COUNT(b.id) as booking_count
                  FROM " . $this->table . " e
                  LEFT JOIN bookings b ON e.id = b.event_id
                  WHERE e.event_date >= CURDATE() AND e.status = 'active'
                  GROUP BY e.id
                  ORDER BY booking_count DESC, e.event_date ASC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }
}title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':category', $this->category);
        $stmt->bindParam(':venue', $this->venue);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':city', $this->city);
        $stmt->bindParam(':event_date', $this->event_date);
        $stmt->bindParam(':event_time', $this->event_time);
        $stmt->bindParam(':duration', $this->duration);
        $stmt->bindParam(':capacity', $this->capacity);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':organizer_name', $this->organizer_name);
        $stmt->bindParam(':organizer_email', $this->organizer_email);
        $stmt->bindParam(':organizer_phone', $this->organizer_phone);
        $stmt->bindParam(':image_url', $this->image_url);
        $stmt->bindParam(':status', $this->status);
        
        return $stmt->execute();
    }
    
    // Read event by ID
    public function readOne() {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id AND status = 'active' LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->title = $row['title'];
            $this->description = $row['description'];
            $this->category = $row['category'];
            $this->venue = $row['venue'];
            $this->address = $row['address'];
            $this->city = $row['city'];
            $this->event_date = $row['event_date'];
            $this->event_time = $row['event_time'];
            $this->duration = $row['duration'];
            $this->capacity = $row['capacity'];
            $this->price = $row['price'];
            $this->organizer_name = $row['organizer_name'];
            $this->organizer_email = $row['organizer_email'];
            $this->organizer_phone = $row['organizer_phone'];
            $this->image_url = $row['image_url'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }
    
    // Get all active events
    public function readAll() {
        $query = "SELECT * FROM " . $this->table . " 
                 WHERE status = 'active' AND event_date >= CURDATE() 
                 ORDER BY event_date ASC, event_time ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Get events by category
    public function getByCategory($category) {
        $query = "SELECT * FROM " . $this->table . " 
                 WHERE category LIKE :category AND status = 'active' AND event_date >= CURDATE() 
                 ORDER BY event_date ASC";
        
        $stmt = $this->conn->prepare($query);
        $category = "%{$category}%";
        $stmt->bindParam(':