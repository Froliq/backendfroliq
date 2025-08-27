<?php

namespace App\Controllers;

use App\Models\Restaurant;
use App\Models\Booking;
use App\Core\Controller;

class RestaurantController extends Controller
{
    private $restaurantModel;
    private $bookingModel;

    public function __construct()
    {
        parent::__construct();
        $this->restaurantModel = new Restaurant();
        $this->bookingModel = new Booking();
    }

    // Display all restaurants
    public function index()
    {
        try {
            $page = (int)($_GET['page'] ?? 1);
            $limit = 12;
            $offset = ($page - 1) * $limit;

            // Get filters
            $filters = [
                'cuisine' => $_GET['cuisine'] ?? '',
                'location' => $_GET['location'] ?? '',
                'price_range' => $_GET['price_range'] ?? '',
                'rating' => $_GET['rating'] ?? '',
                'search' => $_GET['search'] ?? ''
            ];

            $restaurants = $this->restaurantModel->getFilteredRestaurants($filters, $limit, $offset);
            $totalRestaurants = $this->restaurantModel->getFilteredRestaurantsCount($filters);
            $cuisines = $this->restaurantModel->getAllCuisines();
            $locations = $this->restaurantModel->getAllLocations();

            $pagination = [
                'current_page' => $page,
                'total_pages' => ceil($totalRestaurants / $limit),
                'total_items' => $totalRestaurants
            ];

            $this->view('restaurants/index', [
                'restaurants' => $restaurants,
                'cuisines' => $cuisines,
                'locations' => $locations,
                'filters' => $filters,
                'pagination' => $pagination
            ]);

        } catch (Exception $e) {
            $this->redirect('/error?message=' . urlencode('Error loading restaurants'));
        }
    }

    // Show single restaurant with menu and availability
    public function show($id)
    {
        try {
            $restaurant = $this->restaurantModel->findById($id);

            if (!$restaurant || !$restaurant['is_active']) {
                $this->redirect('/404');
                return;
            }

            // Get restaurant menu items
            $menuItems = $this->restaurantModel->getMenuItems($id);
            
            // Get available time slots for today and tomorrow
            $availableSlots = $this->restaurantModel->getAvailableTimeSlots($id, 2);
            
            // Get restaurant reviews
            $reviews = $this->restaurantModel->getRestaurantReviews($id, 5);

            $this->view('restaurants/show', [
                'restaurant' => $restaurant,
                'menu_items' => $menuItems,
                'available_slots' => $availableSlots,
                'reviews' => $reviews
            ]);

        } catch (Exception $e) {
            $this->redirect('/error?message=' . urlencode('Error loading restaurant details'));
        }
    }

    // Admin: Manage restaurants
    public function manage()
    {
        $this->requireAuth();
        $this->requireAdmin();

        try {
            $page = (int)($_GET['page'] ?? 1);
            $limit = 20;
            $offset = ($page - 1) * $limit;

            $restaurants = $this->restaurantModel->getAllRestaurants($limit, $offset);
            $totalRestaurants = $this->restaurantModel->getTotalRestaurantsCount();

            $pagination = [
                'current_page' => $page,
                'total_pages' => ceil($totalRestaurants / $limit),
                'total_items' => $totalRestaurants
            ];

            $this->view('admin/restaurants/manage', [
                'restaurants' => $restaurants,
                'pagination' => $pagination
            ]);

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error loading restaurants: ' . $e->getMessage();
            $this->redirect('/admin/dashboard');
        }
    }

    // Admin: Show create restaurant form
    public function create()
    {
        $this->requireAuth();
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->store();
            return;
        }

        $cuisines = $this->restaurantModel->getAllCuisines();
        $this->view('admin/restaurants/create', ['cuisines' => $cuisines]);
    }

    // Admin: Store new restaurant
    public function store()
    {
        $this->requireAuth();
        $this->requireAdmin();

        try {
            // Handle image upload
            $imagePath = $this->handleImageUpload($_FILES['image'] ?? null);

            $data = [
                'name' => $this->sanitize($_POST['name']),
                'description' => $this->sanitize($_POST['description']),
                'cuisine_type' => $this->sanitize($_POST['cuisine_type']),
                'address' => $this->sanitize($_POST['address']),
                'phone' => $this->sanitize($_POST['phone']),
                'email' => $this->sanitize($_POST['email'] ?? ''),
                'opening_hours' => $this->sanitize($_POST['opening_hours']),
                'price_range' => $this->sanitize($_POST['price_range']),
                'capacity' => (int)$_POST['capacity'],
                'image_path' => $imagePath,
                'features' => $this->sanitize($_POST['features'] ?? ''),
                'is_active' => 1
            ];

            // Validation
            $this->validateRestaurantData($data);

            $restaurantId = $this->restaurantModel->create($data);

            if ($restaurantId) {
                $_SESSION['success'] = 'Restaurant created successfully!';
                $this->redirect('/admin/restaurants/' . $restaurantId);
            } else {
                throw new Exception('Failed to create restaurant');
            }

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/admin/restaurants/create');
        }
    }

    // Admin: Show edit restaurant form
    public function edit($id)
    {
        $this->requireAuth();
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update($id);
            return;
        }

        try {
            $restaurant = $this->restaurantModel->findById($id);

            if (!$restaurant) {
                $this->redirect('/404');
                return;
            }

            $cuisines = $this->restaurantModel->getAllCuisines();

            $this->view('admin/restaurants/edit', [
                'restaurant' => $restaurant,
                'cuisines' => $cuisines
            ]);

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error loading restaurant: ' . $e->getMessage();
            $this->redirect('/admin/restaurants/manage');
        }
    }

    // Admin: Update restaurant
    public function update($id)
    {
        $this->requireAuth();
        $this->requireAdmin();

        try {
            $restaurant = $this->restaurantModel->findById($id);

            if (!$restaurant) {
                $this->redirect('/404');
                return;
            }

            // Handle image upload (optional for updates)
            $imagePath = $restaurant['image_path']; // Keep existing image by default
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $imagePath = $this->handleImageUpload($_FILES['image']);
                // Delete old image if new one is uploaded
                if ($restaurant['image_path'] && file_exists('uploads/' . $restaurant['image_path'])) {
                    unlink('uploads/' . $restaurant['image_path']);
                }
            }

            $data = [
                'name' => $this->sanitize($_POST['name']),
                'description' => $this->sanitize($_POST['description']),
                'cuisine_type' => $this->sanitize($_POST['cuisine_type']),
                'address' => $this->sanitize($_POST['address']),
                'phone' => $this->sanitize($_POST['phone']),
                'email' => $this->sanitize($_POST['email'] ?? ''),
                'opening_hours' => $this->sanitize($_POST['opening_hours']),
                'price_range' => $this->sanitize($_POST['price_range']),
                'capacity' => (int)$_POST['capacity'],
                'image_path' => $imagePath,
                'features' => $this->sanitize($_POST['features'] ?? ''),
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];

            // Validation
            $this->validateRestaurantData($data);

            $result = $this->restaurantModel->update($id, $data);

            if ($result) {
                $_SESSION['success'] = 'Restaurant updated successfully!';
                $this->redirect('/admin/restaurants/' . $id);
            } else {
                throw new Exception('Failed to update restaurant');
            }

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/admin/restaurants/' . $id . '/edit');
        }
    }

    // Admin: Delete restaurant
    public function delete($id)
    {
        $this->requireAuth();
        $this->requireAdmin();

        try {
            $restaurant = $this->restaurantModel->findById($id);

            if (!$restaurant) {
                $this->redirect('/404');
                return;
            }

            // Check if restaurant has bookings
            $bookingCount = $this->bookingModel->getRestaurantBookingCount($id);
            if ($bookingCount > 0) {
                throw new Exception('Cannot delete restaurant with existing bookings. Deactivate instead.');
            }

            $result = $this->restaurantModel->delete($id);

            if ($result) {
                // Delete associated image
                if ($restaurant['image_path'] && file_exists('uploads/' . $restaurant['image_path'])) {
                    unlink('uploads/' . $restaurant['image_path']);
                }

                $_SESSION['success'] = 'Restaurant deleted successfully!';
            } else {
                throw new Exception('Failed to delete restaurant');
            }

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirect('/admin/restaurants/manage');
    }

    // Toggle restaurant active status
    public function toggleStatus($id)
    {
        $this->requireAuth();
        $this->requireAdmin();

        try {
            $restaurant = $this->restaurantModel->findById($id);

            if (!$restaurant) {
                $this->redirect('/404');
                return;
            }

            $newStatus = $restaurant['is_active'] ? 0 : 1;
            $result = $this->restaurantModel->updateStatus($id, $newStatus);

            if ($result) {
                $statusText = $newStatus ? 'activated' : 'deactivated';
                $_SESSION['success'] = "Restaurant {$statusText} successfully!";
            } else {
                throw new Exception('Failed to update restaurant status');
            }

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirect('/admin/restaurants/manage');
    }

    // Admin: Manage restaurant menu
    public function menu($id)
    {
        $this->requireAuth();
        $this->requireAdmin();

        try {
            $restaurant = $this->restaurantModel->findById($id);

            if (!$restaurant) {
                $this->redirect('/404');
                return;
            }

            $menuItems = $this->restaurantModel->getMenuItems($id);

            $this->view('admin/restaurants/menu', [
                'restaurant' => $restaurant,
                'menu_items' => $menuItems
            ]);

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error loading menu: ' . $e->getMessage();
            $this->redirect('/admin/restaurants/manage');
        }
    }

    // Admin: Add menu item
    public function addMenuItem($id)
    {
        $this->requireAuth();
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = [
                    'restaurant_id' => $id,
                    'name' => $this->sanitize($_POST['name']),
                    'description' => $this->sanitize($_POST['description']),
                    'category' => $this->sanitize($_POST['category']),
                    'price' => (float)$_POST['price'],
                    'is_vegetarian' => isset($_POST['is_vegetarian']) ? 1 : 0,
                    'is_available' => 1
                ];

                // Validation
                if (empty($data['name']) || empty($data['category']) || $data['price'] <= 0) {
                    throw new Exception('Name, category, and valid price are required');
                }

                $result = $this->restaurantModel->addMenuItem($data);

                if ($result) {
                    $_SESSION['success'] = 'Menu item added successfully!';
                } else {
                    throw new Exception('Failed to add menu item');
                }

            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
            }
        }

        $this->redirect('/admin/restaurants/' . $id . '/menu');
    }

    // Book restaurant table
    public function book($id)
    {
        $this->requireAuth();

        try {
            $restaurant = $this->restaurantModel->findById($id);

            if (!$restaurant || !$restaurant['is_active']) {
                $this->redirect('/404');
                return;
            }

            $this->redirect('/bookings/create?type=restaurant&item_id=' . $id);

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/restaurants/' . $id);
        }
    }

    // Get restaurants by cuisine (API endpoint)
    public function getByCuisine($cuisine)
    {
        try {
            $limit = (int)($_GET['limit'] ?? 10);
            $restaurants = $this->restaurantModel->getRestaurantsByCuisine($cuisine, $limit);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $restaurants
            ]);

        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    // Search restaurants (API endpoint)
    public function search()
    {
        try {
            $query = $_GET['q'] ?? '';
            $limit = (int)($_GET['limit'] ?? 10);

            if (strlen($query) < 2) {
                throw new Exception('Search query must be at least 2 characters');
            }

            $restaurants = $this->restaurantModel->searchRestaurants($query, $limit);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $restaurants
            ]);

        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    // Get available time slots for a specific date
    public function getAvailableSlots($id)
    {
        try {
            $date = $_GET['date'] ?? date('Y-m-d');
            $partySize = (int)($_GET['party_size'] ?? 2);

            // Validate date is not in the past
            if (strtotime($date) < strtotime('today')) {
                throw new Exception('Cannot check availability for past dates');
            }

            $availableSlots = $this->restaurantModel->getAvailableTimeSlotsForDate($id, $date, $partySize);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $availableSlots
            ]);

        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    // Get menu items for a restaurant
    public function getMenu($id)
    {
        try {
            $category = $_GET['category'] ?? '';
            $menuItems = $this->restaurantModel->getMenuItems($id, $category);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $menuItems
            ]);

        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    // Show restaurant reviews
    public function reviews($id)
    {
        try {
            $restaurant = $this->restaurantModel->findById($id);

            if (!$restaurant) {
                $this->redirect('/404');
                return;
            }

            $page = (int)($_GET['page'] ?? 1);
            $limit = 10;
            $offset = ($page - 1) * $limit;

            $reviews = $this->restaurantModel->getRestaurantReviews($id, $limit, $offset);
            $totalReviews = $this->restaurantModel->getTotalReviewsCount($id);

            $pagination = [
                'current_page' => $page,
                'total_pages' => ceil($totalReviews / $limit),
                'total_items' => $totalReviews
            ];

            $this->view('restaurants/reviews', [
                'restaurant' => $restaurant,
                'reviews' => $reviews,
                'pagination' => $pagination
            ]);

        } catch (Exception $e) {
            $this->redirect('/error?message=' . urlencode('Error loading reviews'));
        }
    }

    // Submit restaurant review
    public function submitReview($id)
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/restaurants/' . $id);
            return;
        }

        try {
            $restaurant = $this->restaurantModel->findById($id);

            if (!$restaurant) {
                $this->redirect('/404');
                return;
            }

            // Check if user has a completed booking for this restaurant
            $hasBooking = $this->bookingModel->userHasCompletedBooking($_SESSION['user_id'], 'restaurant', $id);
            if (!$hasBooking) {
                throw new Exception('You can only review restaurants you have visited');
            }

            // Check if user has already reviewed this restaurant
            $existingReview = $this->restaurantModel->getUserReview($_SESSION['user_id'], $id);
            if ($existingReview) {
                throw new Exception('You have already reviewed this restaurant');
            }

            $data = [
                'restaurant_id' => $id,
                'user_id' => $_SESSION['user_id'],
                'rating' => (int)$_POST['rating'],
                'review_text' => $this->sanitize($_POST['review_text']),
                'visit_date' => $this->sanitize($_POST['visit_date'])
            ];

            // Validation
            if ($data['rating'] < 1 || $data['rating'] > 5) {
                throw new Exception('Rating must be between 1 and 5');
            }

            if (empty($data['review_text']) || strlen($data['review_text']) < 10) {
                throw new Exception('Review must be at least 10 characters long');
            }

            if (empty($data['visit_date']) || strtotime($data['visit_date']) > time()) {
                throw new Exception('Valid visit date is required');
            }

            $result = $this->restaurantModel->addReview($data);

            if ($result) {
                $_SESSION['success'] = 'Review submitted successfully!';
            } else {
                throw new Exception('Failed to submit review');
            }

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirect('/restaurants/' . $id . '#reviews');
    }

    // Validate restaurant data
    private function validateRestaurantData($data)
    {
        if (empty($data['name'])) {
            throw new Exception('Restaurant name is required');
        }

        if (empty($data['description'])) {
            throw new Exception('Restaurant description is required');
        }

        if (empty($data['cuisine_type'])) {
            throw new Exception('Cuisine type is required');
        }

        if (empty($data['address'])) {
            throw new Exception('Address is required');
        }

        if (empty($data['phone'])) {
            throw new Exception('Phone number is required');
        }

        // Validate phone number format
        if (!preg_match('/^[\d\s\+\-\(\)]+$/', $data['phone'])) {
            throw new Exception('Invalid phone number format');
        }

        // Validate email if provided
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }

        if (empty($data['opening_hours'])) {
            throw new Exception('Opening hours are required');
        }

        if (!in_array($data['price_range'], ['

            , '$', '$

            , '$$'])) {
            throw new Exception('Invalid price range');
        }

        if ($data['capacity'] <= 0) {
            throw new Exception('Capacity must be greater than 0');
        }
    }

    // Handle image upload
    private function handleImageUpload($file)
    {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Only JPEG, PNG and GIF images are allowed');
        }

        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            throw new Exception('Image size cannot exceed 5MB');
        }

        $uploadDir = 'uploads/restaurants/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'restaurant_' . time() . '_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Failed to upload image');
        }

        return 'restaurants/' . $filename;
    }
}