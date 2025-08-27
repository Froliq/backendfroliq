<?php

namespace App\Controllers;

use App\Models\Event;
use App\Models\Booking;
use App\Core\Controller;

class EventController extends Controller
{
    private $eventModel;
    private $bookingModel;

    public function __construct()
    {
        parent::__construct();
        $this->eventModel = new Event();
        $this->bookingModel = new Booking();
    }

    // Display all events
    public function index()
    {
        try {
            $page = (int)($_GET['page'] ?? 1);
            $limit = 12;
            $offset = ($page - 1) * $limit;

            // Get filters
            $filters = [
                'category' => $_GET['category'] ?? '',
                'date_from' => $_GET['date_from'] ?? '',
                'date_to' => $_GET['date_to'] ?? '',
                'search' => $_GET['search'] ?? '',
                'price_min' => $_GET['price_min'] ?? '',
                'price_max' => $_GET['price_max'] ?? ''
            ];

            $events = $this->eventModel->getFilteredEvents($filters, $limit, $offset);
            $totalEvents = $this->eventModel->getFilteredEventsCount($filters);
            $categories = $this->eventModel->getAllCategories();

            $pagination = [
                'current_page' => $page,
                'total_pages' => ceil($totalEvents / $limit),
                'total_items' => $totalEvents
            ];

            $this->view('events/index', [
                'events' => $events,
                'categories' => $categories,
                'filters' => $filters,
                'pagination' => $pagination
            ]);

        } catch (Exception $e) {
            $this->redirect('/error?message=' . urlencode('Error loading events'));
        }
    }

    // Show single event
    public function show($id)
    {
        try {
            $event = $this->eventModel->findById($id);

            if (!$event || !$event['is_active']) {
                $this->redirect('/404');
                return;
            }

            // Get available time slots
            $timeSlots = $this->eventModel->getEventTimeSlots($id);
            
            // Get reviews/ratings if needed
            $reviews = $this->eventModel->getEventReviews($id, 5); // Get latest 5 reviews

            $this->view('events/show', [
                'event' => $event,
                'time_slots' => $timeSlots,
                'reviews' => $reviews
            ]);

        } catch (Exception $e) {
            $this->redirect('/error?message=' . urlencode('Error loading event details'));
        }
    }

    // Admin: Show all events for management
    public function manage()
    {
        $this->requireAuth();
        $this->requireAdmin();

        try {
            $page = (int)($_GET['page'] ?? 1);
            $limit = 20;
            $offset = ($page - 1) * $limit;

            $events = $this->eventModel->getAllEvents($limit, $offset);
            $totalEvents = $this->eventModel->getTotalEventsCount();

            $pagination = [
                'current_page' => $page,
                'total_pages' => ceil($totalEvents / $limit),
                'total_items' => $totalEvents
            ];

            $this->view('admin/events/manage', [
                'events' => $events,
                'pagination' => $pagination
            ]);

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error loading events: ' . $e->getMessage();
            $this->redirect('/admin/dashboard');
        }
    }

    // Admin: Show create event form
    public function create()
    {
        $this->requireAuth();
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->store();
            return;
        }

        $categories = $this->eventModel->getAllCategories();
        $this->view('admin/events/create', ['categories' => $categories]);
    }

    // Admin: Store new event
    public function store()
    {
        $this->requireAuth();
        $this->requireAdmin();

        try {
            // Handle image upload
            $imagePath = $this->handleImageUpload($_FILES['image'] ?? null);

            $data = [
                'title' => $this->sanitize($_POST['title']),
                'description' => $this->sanitize($_POST['description']),
                'category' => $this->sanitize($_POST['category']),
                'venue' => $this->sanitize($_POST['venue']),
                'address' => $this->sanitize($_POST['address']),
                'event_date' => $this->sanitize($_POST['event_date']),
                'start_time' => $this->sanitize($_POST['start_time']),
                'end_time' => $this->sanitize($_POST['end_time']),
                'price' => (float)$_POST['price'],
                'max_capacity' => (int)$_POST['max_capacity'],
                'image_path' => $imagePath,
                'organizer_name' => $this->sanitize($_POST['organizer_name']),
                'organizer_contact' => $this->sanitize($_POST['organizer_contact']),
                'is_active' => 1
            ];

            // Validation
            $this->validateEventData($data);

            $eventId = $this->eventModel->create($data);

            if ($eventId) {
                $_SESSION['success'] = 'Event created successfully!';
                $this->redirect('/admin/events/' . $eventId);
            } else {
                throw new Exception('Failed to create event');
            }

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/admin/events/create');
        }
    }

    // Admin: Show edit event form
    public function edit($id)
    {
        $this->requireAuth();
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update($id);
            return;
        }

        try {
            $event = $this->eventModel->findById($id);

            if (!$event) {
                $this->redirect('/404');
                return;
            }

            $categories = $this->eventModel->getAllCategories();

            $this->view('admin/events/edit', [
                'event' => $event,
                'categories' => $categories
            ]);

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error loading event: ' . $e->getMessage();
            $this->redirect('/admin/events/manage');
        }
    }

    // Admin: Update event
    public function update($id)
    {
        $this->requireAuth();
        $this->requireAdmin();

        try {
            $event = $this->eventModel->findById($id);

            if (!$event) {
                $this->redirect('/404');
                return;
            }

            // Handle image upload (optional for updates)
            $imagePath = $event['image_path']; // Keep existing image by default
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $imagePath = $this->handleImageUpload($_FILES['image']);
                // Delete old image if new one is uploaded
                if ($event['image_path'] && file_exists('uploads/' . $event['image_path'])) {
                    unlink('uploads/' . $event['image_path']);
                }
            }

            $data = [
                'title' => $this->sanitize($_POST['title']),
                'description' => $this->sanitize($_POST['description']),
                'category' => $this->sanitize($_POST['category']),
                'venue' => $this->sanitize($_POST['venue']),
                'address' => $this->sanitize($_POST['address']),
                'event_date' => $this->sanitize($_POST['event_date']),
                'start_time' => $this->sanitize($_POST['start_time']),
                'end_time' => $this->sanitize($_POST['end_time']),
                'price' => (float)$_POST['price'],
                'max_capacity' => (int)$_POST['max_capacity'],
                'image_path' => $imagePath,
                'organizer_name' => $this->sanitize($_POST['organizer_name']),
                'organizer_contact' => $this->sanitize($_POST['organizer_contact']),
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];

            // Validation
            $this->validateEventData($data);

            $result = $this->eventModel->update($id, $data);

            if ($result) {
                $_SESSION['success'] = 'Event updated successfully!';
                $this->redirect('/admin/events/' . $id);
            } else {
                throw new Exception('Failed to update event');
            }

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/admin/events/' . $id . '/edit');
        }
    }

    // Admin: Delete event
    public function delete($id)
    {
        $this->requireAuth();
        $this->requireAdmin();

        try {
            $event = $this->eventModel->findById($id);

            if (!$event) {
                $this->redirect('/404');
                return;
            }

            // Check if event has bookings
            $bookingCount = $this->bookingModel->getEventBookingCount($id);
            if ($bookingCount > 0) {
                throw new Exception('Cannot delete event with existing bookings. Deactivate instead.');
            }

            $result = $this->eventModel->delete($id);

            if ($result) {
                // Delete associated image
                if ($event['image_path'] && file_exists('uploads/' . $event['image_path'])) {
                    unlink('uploads/' . $event['image_path']);
                }

                $_SESSION['success'] = 'Event deleted successfully!';
            } else {
                throw new Exception('Failed to delete event');
            }

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirect('/admin/events/manage');
    }

    // Toggle event active status
    public function toggleStatus($id)
    {
        $this->requireAuth();
        $this->requireAdmin();

        try {
            $event = $this->eventModel->findById($id);

            if (!$event) {
                $this->redirect('/404');
                return;
            }

            $newStatus = $event['is_active'] ? 0 : 1;
            $result = $this->eventModel->updateStatus($id, $newStatus);

            if ($result) {
                $statusText = $newStatus ? 'activated' : 'deactivated';
                $_SESSION['success'] = "Event {$statusText} successfully!";
            } else {
                throw new Exception('Failed to update event status');
            }

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirect('/admin/events/manage');
    }

    // Book event
    public function book($id)
    {
        $this->requireAuth();

        try {
            $event = $this->eventModel->findById($id);

            if (!$event || !$event['is_active']) {
                $this->redirect('/404');
                return;
            }

            // Check if event is in the future
            $eventDateTime = strtotime($event['event_date'] . ' ' . $event['start_time']);
            if ($eventDateTime <= time()) {
                throw new Exception('Cannot book past events');
            }

            $this->redirect('/bookings/create?type=event&item_id=' . $id);

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/events/' . $id);
        }
    }

    // Get events by category (API endpoint)
    public function getByCategory($category)
    {
        try {
            $limit = (int)($_GET['limit'] ?? 10);
            $events = $this->eventModel->getEventsByCategory($category, $limit);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $events
            ]);

        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    // Search events (API endpoint)
    public function search()
    {
        try {
            $query = $_GET['q'] ?? '';
            $limit = (int)($_GET['limit'] ?? 10);

            if (strlen($query) < 2) {
                throw new Exception('Search query must be at least 2 characters');
            }

            $events = $this->eventModel->searchEvents($query, $limit);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $events
            ]);

        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    // Validate event data
    private function validateEventData($data)
    {
        if (empty($data['title'])) {
            throw new Exception('Event title is required');
        }

        if (empty($data['description'])) {
            throw new Exception('Event description is required');
        }

        if (empty($data['venue'])) {
            throw new Exception('Venue is required');
        }

        if (empty($data['event_date'])) {
            throw new Exception('Event date is required');
        }

        if (strtotime($data['event_date']) < strtotime('today')) {
            throw new Exception('Event date cannot be in the past');
        }

        if (empty($data['start_time']) || empty($data['end_time'])) {
            throw new Exception('Start and end times are required');
        }

        if (strtotime($data['end_time']) <= strtotime($data['start_time'])) {
            throw new Exception('End time must be after start time');
        }

        if ($data['price'] < 0) {
            throw new Exception('Price cannot be negative');
        }

        if ($data['max_capacity'] <= 0) {
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

        $uploadDir = 'uploads/events/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'event_' . time() . '_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Failed to upload image');
        }

        return 'events/' . $filename;
    }
}