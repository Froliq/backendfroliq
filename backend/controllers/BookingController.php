<?php

namespace App\Controllers;

use App\Models\Booking;
use App\Models\User;
use App\Core\Controller;

class BookingController extends Controller
{
    private $bookingModel;
    private $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->bookingModel = new Booking();
        $this->userModel = new User();
    }

    // Display all bookings for admin
    public function index()
    {
        try {
            $bookings = $this->bookingModel->getAllBookings();
            $this->view('admin/bookings/index', ['bookings' => $bookings]);
        } catch (Exception $e) {
            $this->redirect('/error?message=' . urlencode('Error loading bookings'));
        }
    }

    // Display user's bookings
    public function myBookings()
    {
        $this->requireAuth();
        
        try {
            $userId = $_SESSION['user_id'];
            $bookings = $this->bookingModel->getUserBookings($userId);
            $this->view('bookings/my-bookings', ['bookings' => $bookings]);
        } catch (Exception $e) {
            $this->redirect('/error?message=' . urlencode('Error loading your bookings'));
        }
    }

    // Show booking details
    public function show($id)
    {
        try {
            $booking = $this->bookingModel->findById($id);
            
            if (!$booking) {
                $this->redirect('/404');
                return;
            }

            // Check if user owns this booking or is admin
            if (!$this->isAdmin() && $booking['user_id'] != $_SESSION['user_id']) {
                $this->redirect('/403');
                return;
            }

            $this->view('bookings/show', ['booking' => $booking]);
        } catch (Exception $e) {
            $this->redirect('/error?message=' . urlencode('Error loading booking details'));
        }
    }

    // Create new booking
    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->store();
            return;
        }

        // Get booking type and item ID from URL parameters
        $type = $_GET['type'] ?? '';
        $itemId = $_GET['item_id'] ?? '';

        if (empty($type) || empty($itemId)) {
            $this->redirect('/error?message=' . urlencode('Invalid booking parameters'));
            return;
        }

        $this->view('bookings/create', [
            'type' => $type,
            'item_id' => $itemId
        ]);
    }

    // Store new booking
    public function store()
    {
        $this->requireAuth();

        try {
            $data = [
                'user_id' => $_SESSION['user_id'],
                'booking_type' => $this->sanitize($_POST['booking_type']),
                'item_id' => (int)$_POST['item_id'],
                'booking_date' => $this->sanitize($_POST['booking_date']),
                'booking_time' => $this->sanitize($_POST['booking_time']),
                'quantity' => (int)($_POST['quantity'] ?? 1),
                'total_amount' => (float)$_POST['total_amount'],
                'special_requests' => $this->sanitize($_POST['special_requests'] ?? ''),
                'status' => 'pending'
            ];

            // Validate required fields
            if (empty($data['booking_type']) || empty($data['item_id']) || 
                empty($data['booking_date']) || $data['total_amount'] <= 0) {
                throw new Exception('Please fill all required fields');
            }

            // Validate booking date is not in the past
            if (strtotime($data['booking_date']) < strtotime('today')) {
                throw new Exception('Booking date cannot be in the past');
            }

            // Check availability based on booking type
            if (!$this->checkAvailability($data)) {
                throw new Exception('Selected time slot is not available');
            }

            $bookingId = $this->bookingModel->create($data);

            if ($bookingId) {
                $_SESSION['success'] = 'Booking created successfully!';
                $this->redirect('/bookings/' . $bookingId);
            } else {
                throw new Exception('Failed to create booking');
            }

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/bookings/create?' . http_build_query([
                'type' => $_POST['booking_type'],
                'item_id' => $_POST['item_id']
            ]));
        }
    }

    // Update booking status (admin only)
    public function updateStatus($id)
    {
        $this->requireAuth();
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/bookings');
            return;
        }

        try {
            $status = $this->sanitize($_POST['status']);
            $validStatuses = ['pending', 'confirmed', 'cancelled', 'completed'];

            if (!in_array($status, $validStatuses)) {
                throw new Exception('Invalid status');
            }

            $result = $this->bookingModel->updateStatus($id, $status);

            if ($result) {
                $_SESSION['success'] = 'Booking status updated successfully!';
            } else {
                throw new Exception('Failed to update booking status');
            }

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirect('/bookings/' . $id);
    }

    // Cancel booking
    public function cancel($id)
    {
        $this->requireAuth();

        try {
            $booking = $this->bookingModel->findById($id);

            if (!$booking) {
                $this->redirect('/404');
                return;
            }

            // Check if user owns this booking or is admin
            if (!$this->isAdmin() && $booking['user_id'] != $_SESSION['user_id']) {
                $this->redirect('/403');
                return;
            }

            // Check if booking can be cancelled
            if (in_array($booking['status'], ['cancelled', 'completed'])) {
                throw new Exception('This booking cannot be cancelled');
            }

            // Check cancellation policy (24 hours before booking)
            $bookingDateTime = strtotime($booking['booking_date'] . ' ' . $booking['booking_time']);
            if ($bookingDateTime - time() < 86400) { // 24 hours in seconds
                throw new Exception('Bookings can only be cancelled 24 hours in advance');
            }

            $result = $this->bookingModel->updateStatus($id, 'cancelled');

            if ($result) {
                $_SESSION['success'] = 'Booking cancelled successfully!';
            } else {
                throw new Exception('Failed to cancel booking');
            }

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirect('/bookings/' . $id);
    }

    // Get bookings by date range (API endpoint)
    public function getByDateRange()
    {
        $this->requireAuth();

        try {
            $startDate = $_GET['start_date'] ?? date('Y-m-d');
            $endDate = $_GET['end_date'] ?? date('Y-m-d', strtotime('+30 days'));
            $type = $_GET['type'] ?? null;

            $bookings = $this->bookingModel->getBookingsByDateRange($startDate, $endDate, $type);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $bookings
            ]);

        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    // Check availability for booking
    private function checkAvailability($data)
    {
        // This is a simplified availability check
        // In a real application, you'd check specific business rules for each type
        
        $existingBookings = $this->bookingModel->getBookingsByDateTime(
            $data['booking_date'],
            $data['booking_time'],
            $data['booking_type'],
            $data['item_id']
        );

        // Simple capacity check (this would be more complex in real application)
        $maxCapacity = $this->getMaxCapacity($data['booking_type']);
        $currentBookings = count($existingBookings);

        return ($currentBookings + $data['quantity']) <= $maxCapacity;
    }

    // Get maximum capacity based on booking type
    private function getMaxCapacity($type)
    {
        $capacities = [
            'restaurant' => 50, // 50 people per time slot
            'movie' => 100,     // 100 seats per show
            'event' => 200      // 200 people per event
        ];

        return $capacities[$type] ?? 10;
    }

    // Generate booking report (admin only)
    public function report()
    {
        $this->requireAuth();
        $this->requireAdmin();

        try {
            $startDate = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
            $endDate = $_GET['end_date'] ?? date('Y-m-t');     // Last day of current month

            $reportData = [
                'total_bookings' => $this->bookingModel->getTotalBookings($startDate, $endDate),
                'bookings_by_status' => $this->bookingModel->getBookingsByStatus($startDate, $endDate),
                'bookings_by_type' => $this->bookingModel->getBookingsByType($startDate, $endDate),
                'revenue' => $this->bookingModel->getTotalRevenue($startDate, $endDate),
                'popular_items' => $this->bookingModel->getPopularItems($startDate, $endDate)
            ];

            $this->view('admin/bookings/report', [
                'report' => $reportData,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error generating report: ' . $e->getMessage();
            $this->redirect('/admin/bookings');
        }
    }
}