<?php

class Restaurant {
    private $conn;
    private $table = 'restaurants';
    
    // Restaurant properties
    public $id;
    public $name;
    public $description;
    public $cuisine_type;
    public $address;
    public $city;
    public $phone;
    public $email;
    public $website;
    public $image_url;
    public $rating;
    public $price_range;
    public $opening_hours;
    public $capacity;
    public $status;
    public $created_at;
    public $updated_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create restaurant
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                 SET name = :name, 
                     description = :description, 
                     cuisine_type = :cuisine_type, 
                     address = :address, 
                     city = :city,
                     phone = :phone,
                     email = :email,
                     website = :website,
                     image_url = :image_url,
                     rating = :rating,
                     price_range = :price_range,
                     opening_hours = :opening_hours,
                     capacity = :capacity,
                     status = :status,
                     created_at = NOW()";
        
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->cuisine_type = htmlspecialchars(strip_tags($this->cuisine_type));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->city = htmlspecialchars(strip_tags($this->city));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->website = htmlspecialchars(strip_tags($this->website));
        $this->image_url = htmlspecialchars(strip_tags($this->image_url));
        $this->status = $this->status ?? 'active';
        
        // Bind data
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':cuisine_type', $this->cuisine_type);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':city', $this->city);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':website', $this->website);
        $stmt->bindParam(':image_url', $this->image_url);
        $stmt->bindParam(':rating', $this->rating);
        $stmt->bindParam(':price_range', $this->price_range);
        $stmt->bindParam(':opening_hours', $this->opening_hours);
        $stmt->bindParam(':capacity', $this->capacity);
        $stmt->bindParam(':status', $this->status);
        
        return $stmt->execute();
    }
    
    // Read restaurant by ID
    public function readOne() {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id AND status = 'active' LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->cuisine_type = $row['cuisine_type'];
            $this->address = $row['address'];
            $this->city = $row['city'];
            $this->phone = $row['phone'];
            $this->email = $row['email'];
            $this->website = $row['website'];
            $this->image_url = $row['image_url'];
            $this->rating = $row['rating'];
            $this->price_range = $row['price_range'];
            $this->opening_hours = $row['opening_hours'];
            $this->capacity = $row['capacity'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }
    
    // Get all active restaurants
    public function readAll() {
        $query = "SELECT * FROM " . $this->table . " WHERE status = 'active' ORDER BY rating DESC, created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Get restaurants by cuisine type
    public function getByCuisine($cuisine_type) {
        $query = "SELECT * FROM " . $this->table . " 
                 WHERE cuisine_type LIKE :cuisine_type AND status = 'active' 
                 ORDER BY rating DESC";
        
        $stmt = $this->conn->prepare($query);
        $cuisine_type = "%{$cuisine_type}%";
        $stmt->bindParam(':cuisine_type', $cuisine_type);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Get restaurants by city
    public function getByCity($city) {
        $query = "SELECT * FROM " . $this->table . " 
                 WHERE city LIKE :city AND status = 'active' 
                 ORDER BY rating DESC";
        
        $stmt = $this->conn->prepare($query);
        $city = "%{$city}%";
        $stmt->bindParam(':city', $city);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Search restaurants
    public function search($keywords) {
        $query = "SELECT * FROM " . $this->table . " 
                 WHERE (name LIKE :keywords OR description LIKE :keywords OR cuisine_type LIKE :keywords OR city LIKE :keywords) 
                 AND status = 'active' 
                 ORDER BY rating DESC";
        
        $stmt = $this->conn->prepare($query);
        $keywords = "%{$keywords}%";
        $stmt->bindParam(':keywords', $keywords);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Get restaurants by price range
    public function getByPriceRange($price_range) {
        $query = "SELECT * FROM " . $this->table . " 
                 WHERE price_range = :price_range AND status = 'active' 
                 ORDER BY rating DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':price_range', $price_range);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Update restaurant
    public function update() {
        $query = "UPDATE " . $this->table . " 
                 SET name = :name, 
                     description = :description, 
                     cuisine_type = :cuisine_type, 
                     address = :address, 
                     city = :city,
                     phone = :phone,
                     email = :email,
                     website = :website,
                     image_url = :image_url,
                     rating = :rating,
                     price_range = :price_range,
                     opening_hours = :opening_hours,
                     capacity = :capacity,
                     status = :status,
                     updated_at = NOW()
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->cuisine_type = htmlspecialchars(strip_tags($this->cuisine_type));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->city = htmlspecialchars(strip_tags($this->city));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->website = htmlspecialchars(strip_tags($this->website));
        $this->image_url = htmlspecialchars(strip_tags($this->image_url));
        
        // Bind data
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':cuisine_type', $this->cuisine_type);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':city', $this->city);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':website', $this->website);
        $stmt->bindParam(':image_url', $this->image_url);
        $stmt->bindParam(':rating', $this->rating);
        $stmt->bindParam(':price_range', $this->price_range);
        $stmt->bindParam(':opening_hours', $this->opening_hours);
        $stmt->bindParam(':capacity', $this->capacity);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    // Delete restaurant (soft delete)
    public function delete() {
        $query = "UPDATE " . $this->table . " SET status = 'inactive' WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    // Check availability for booking
    public function checkAvailability($date, $time, $party_size) {
        $query = "SELECT 
                    r.capacity,
                    COALESCE(SUM(b.party_size), 0) as booked_capacity
                  FROM " . $this->table . " r
                  LEFT JOIN bookings b ON r.id = b.restaurant_id 
                    AND DATE(b.booking_date) = :date 
                    AND TIME(b.booking_time) = :time
                    AND b.status IN ('confirmed', 'pending')
                  WHERE r.id = :restaurant_id AND r.status = 'active'
                  GROUP BY r.id, r.capacity";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':restaurant_id', $this->id);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':time', $time);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($result) {
            $available_capacity = $result['capacity'] - $result['booked_capacity'];
            return $available_capacity >= $party_size;
        }
        
        return false;
    }
}