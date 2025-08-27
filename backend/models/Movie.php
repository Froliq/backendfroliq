<?php

class Movie {
    private $conn;
    private $table = 'movies';
    
    // Movie properties
    public $id;
    public $title;
    public $description;
    public $duration;
    public $genre;
    public $rating;
    public $release_date;
    public $poster_url;
    public $trailer_url;
    public $price;
    public $status;
    public $created_at;
    public $updated_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create movie
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                 SET title = :title, 
                     description = :description, 
                     duration = :duration, 
                     genre = :genre, 
                     rating = :rating,
                     release_date = :release_date,
                     poster_url = :poster_url,
                     trailer_url = :trailer_url,
                     price = :price,
                     status = :status,
                     created_at = NOW()";
        
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->genre = htmlspecialchars(strip_tags($this->genre));
        $this->rating = htmlspecialchars(strip_tags($this->rating));
        $this->poster_url = htmlspecialchars(strip_tags($this->poster_url));
        $this->trailer_url = htmlspecialchars(strip_tags($this->trailer_url));
        $this->status = $this->status ?? 'active';
        
        // Bind data
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':duration', $this->duration);
        $stmt->bindParam(':genre', $this->genre);
        $stmt->bindParam(':rating', $this->rating);
        $stmt->bindParam(':release_date', $this->release_date);
        $stmt->bindParam(':poster_url', $this->poster_url);
        $stmt->bindParam(':trailer_url', $this->trailer_url);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':status', $this->status);
        
        return $stmt->execute();
    }
    
    // Read movie by ID
    public function readOne() {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id AND status = 'active' LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->title = $row['title'];
            $this->description = $row['description'];
            $this->duration = $row['duration'];
            $this->genre = $row['genre'];
            $this->rating = $row['rating'];
            $this->release_date = $row['release_date'];
            $this->poster_url = $row['poster_url'];
            $this->trailer_url = $row['trailer_url'];
            $this->price = $row['price'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }
    
    // Get all active movies
    public function readAll() {
        $query = "SELECT * FROM " . $this->table . " WHERE status = 'active' ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Get movies by genre
    public function getByGenre($genre) {
        $query = "SELECT * FROM " . $this->table . " WHERE genre LIKE :genre AND status = 'active' ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $genre = "%{$genre}%";
        $stmt->bindParam(':genre', $genre);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Search movies
    public function search($keywords) {
        $query = "SELECT * FROM " . $this->table . " 
                 WHERE (title LIKE :keywords OR description LIKE :keywords OR genre LIKE :keywords) 
                 AND status = 'active' 
                 ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $keywords = "%{$keywords}%";
        $stmt->bindParam(':keywords', $keywords);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Update movie
    public function update() {
        $query = "UPDATE " . $this->table . " 
                 SET title = :title, 
                     description = :description, 
                     duration = :duration, 
                     genre = :genre, 
                     rating = :rating,
                     release_date = :release_date,
                     poster_url = :poster_url,
                     trailer_url = :trailer_url,
                     price = :price,
                     status = :status,
                     updated_at = NOW()
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->genre = htmlspecialchars(strip_tags($this->genre));
        $this->rating = htmlspecialchars(strip_tags($this->rating));
        $this->poster_url = htmlspecialchars(strip_tags($this->poster_url));
        $this->trailer_url = htmlspecialchars(strip_tags($this->trailer_url));
        
        // Bind data
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':duration', $this->duration);
        $stmt->bindParam(':genre', $this->genre);
        $stmt->bindParam(':rating', $this->rating);
        $stmt->bindParam(':release_date', $this->release_date);
        $stmt->bindParam(':poster_url', $this->poster_url);
        $stmt->bindParam(':trailer_url', $this->trailer_url);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    // Delete movie (soft delete)
    public function delete() {
        $query = "UPDATE " . $this->table . " SET status = 'inactive' WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    // Get upcoming movies
    public function getUpcoming() {
        $query = "SELECT * FROM " . $this->table . " 
                 WHERE release_date > NOW() AND status = 'active' 
                 ORDER BY release_date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Get now showing movies
    public function getNowShowing() {
        $query = "SELECT * FROM " . $this->table . " 
                 WHERE release_date <= NOW() AND status = 'active' 
                 ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
}