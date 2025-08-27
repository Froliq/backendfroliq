<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Booking;
use App\Core\Controller;

class UserController extends Controller
{
    private $userModel;
    private $bookingModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->bookingModel = new Booking();
    }

    // Show login form
    public function login()
    {
        // Redirect if already logged in
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processLogin();
            return;
        }

        $this->view('auth/login');
    }

    // Process login
    public function processLogin()
    {
        try {
            $email = $this->sanitize($_POST['email']);
            $password = $_POST['password'];

            // Validation
            if (empty($email) || empty($password)) {
                throw new Exception('Email and password are required');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format');
            }

            // Authenticate user
            $user = $this->userModel->authenticate($email, $password);

            if ($user) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_role'] = $user['role'];

                // Update last login
                $this->userModel->updateLastLogin($user['id']);

                $_SESSION['success'] = 'Welcome back, ' . $user['first_name'] . '!';

                // Redirect based on role
                $redirectUrl = $user['role'] === 'admin' ? '/admin/dashboard' : '/dashboard';
                $this->redirect($redirectUrl);
            } else {
                throw new Exception('Invalid email or password');
            }

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/login');
        }
    }

    // Show registration form
    public function register()
    {
        // Redirect if already logged in
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processRegistration();
            return;
        }

        $this->view('auth/register');
    }

    // Process registration
    public function processRegistration()
    {
        try {
            $data = [
                'first_name' => $this->sanitize($_POST['first_name']),
                'last_name' => $this->sanitize($_POST['last_name']),
                'email' => $this->sanitize($_POST['email']),
                'phone' => $this->sanitize($_POST['phone']),
                'password' => $_POST['password'],
                'confirm_password' => $_POST['confirm_password']
            ];

            // Validation
            $this->validateRegistrationData($data);

            // Check if email already exists
            if ($this->userModel->emailExists($data['email'])) {
                throw new Exception('Email address is already registered');
            }

            // Hash password
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['confirm_password']);

            // Set default role
            $data['role'] = 'customer';

            // Create user
            $userId = $this->userModel->create($data);

            if ($userId) {
                $_SESSION['success'] = 'Registration successful! Please login to continue.';
                $this->redirect('/login');
            } else {
                throw new Exception('Registration failed. Please try again.');
            }

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/register');
        }
    }

    // User dashboard
    public function dashboard()
    {
        $this->requireAuth();

        try {
            $userId = $_SESSION['user_id'];
            
            // Get user's recent bookings
            $recentBookings = $this->bookingModel->getUserBookings($userId, 5);
            
            // Get booking statistics
            $bookingStats = [
                'total' => $this->bookingModel->getUserBookingsCount($userId),
                'pending' => $this->bookingModel->getUserBookingsCount($userId, 'pending'),
                'confirmed' => $this->bookingModel->getUserBookingsCount($userId, 'confirmed'),
                'completed' => $this->bookingModel->getUserBookingsCount($userId, 'completed')
            ];

            $this->view('user/dashboard', [
                'recent_bookings' => $recentBookings,
                'booking_stats' => $bookingStats
            ]);

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error loading dashboard: ' . $e->getMessage();
            $this->redirect('/');
        }
    }

    // User profile
    public function profile()
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->updateProfile();
            return;
        }

        try {
            $user = $this->userModel->findById($_SESSION['user_id']);

            if (!$user) {
                $this->logout();
                return;
            }

            $this->view('user/profile', ['user' => $user]);

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error loading profile: ' . $e->getMessage();
            $this->redirect('/dashboard');
        }
    }

    // Update profile
    public function updateProfile()
    {
        $this->requireAuth();

        try {
            $userId = $_SESSION['user_id'];
            
            $data = [
                'first_name' => $this->sanitize($_POST['first_name']),
                'last_name' => $this->sanitize($_POST['last_name']),
                'phone' => $this->sanitize($_POST['phone']),
                'date_of_birth' => $this->sanitize($_POST['date_of_birth'] ?? ''),
                'address' => $this->sanitize($_POST['address'] ?? '')
            ];

            // Validation
            if (empty($data['first_name']) || empty($data['last_name'])) {
                throw new Exception('First name and last name are required');
            }

            if (empty($data['phone'])) {
                throw new Exception('Phone number is required');
            }

            if (!preg_match('/^[\d\s\+\-\(\)]+$/', $data['phone'])) {
                throw new Exception('Invalid phone number format');
            }

            // Validate date of birth if provided
            if (!empty($data['date_of_birth']) && strtotime($data['date_of_birth']) > strtotime('-13 years')) {
                throw new Exception('You must be at least 13 years old');
            }

            $result = $this->userModel->updateProfile($userId, $data);

            if ($result) {
                // Update session name
                $_SESSION['user_name'] = $data['first_name'] . ' ' . $data['last_name'];
                $_SESSION['success'] = 'Profile updated successfully!';
            } else {
                throw new Exception('Failed to update profile');
            }

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirect('/profile');
    }

    // Change password
    public function changePassword()
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processPasswordChange();
            return;
        }

        $this->view('user/change-password');
    }

    // Process password change
    public function processPasswordChange()
    {
        $this->requireAuth();

        try {
            $userId = $_SESSION['user_id'];
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];

            // Validation
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                throw new Exception('All password fields are required');
            }

            if ($newPassword !== $confirmPassword) {
                throw new Exception('New passwords do not match');
            }

            if (strlen($newPassword) < 8) {
                throw new Exception('New password must be at least 8 characters long');
            }

            // Verify current password
            $user = $this->userModel->findById($userId);
            if (!password_verify($currentPassword, $user['password'])) {
                throw new Exception('Current password is incorrect');
            }

            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $result = $this->userModel->updatePassword($userId, $hashedPassword);

            if ($result) {
                $_SESSION['success'] = 'Password changed successfully!';
                $this->redirect('/profile');
            } else {
                throw new Exception('Failed to change password');
            }

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/change-password');
        }
    }

    // Logout
    public function logout()
    {
        // Destroy session
        session_destroy();
        
        // Start new session for flash message
        session_start();
        $_SESSION['success'] = 'You have been logged out successfully';
        
        $this->redirect('/');
    }

    // Admin: Manage users
    public function manage()
    {
        $this->requireAuth();
        $this->requireAdmin();

        try {
            $page = (int)($_GET['page'] ?? 1);
            $limit = 20;
            $offset = ($page - 1) * $limit;

            $search = $_GET['search'] ?? '';
            $role = $_GET['role'] ?? '';

            $users = $this->userModel->getFilteredUsers($search, $role, $limit, $offset);
            $totalUsers = $this->userModel->getFilteredUsersCount($search, $role);

            $pagination = [
                'current_page' => $page,
                'total_pages' => ceil($totalUsers / $limit),
                'total_items' => $totalUsers
            ];

            $this->view('admin/users/manage', [
                'users' => $users,
                'pagination' => $pagination,
                'search' => $search,
                'role' => $role
            ]);

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error loading users: ' . $e->getMessage();
            $this->redirect('/admin/dashboard');
        }
    }

    // Admin: View user details
    public function show($id)
    {
        $this->requireAuth();
        
        // Users can only view their own profile, admins can view any
        if (!$this->isAdmin() && $id != $_SESSION['user_id']) {
            $this->redirect('/403');
            return;
        }

        try {
            $user = $this->userModel->findById($id);

            if (!$user) {
                $this->redirect('/404');
                return;
            }

            // Get user's booking history
            $bookings = $this->bookingModel->getUserBookings($id, 10);
            
            // Get user statistics
            $userStats = [
                'total_bookings' => $this->bookingModel->getUserBookingsCount($id),
                'total_spent' => $this->bookingModel->getUserTotalSpent($id),
                'member_since' => $user['created_at']
            ];

            $viewPath = $this->isAdmin() ? 'admin/users/show' : 'user/profile';
            
            $this->view($viewPath, [
                'user' => $user,
                'bookings' => $bookings,
                'user_stats' => $userStats
            ]);

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error loading user details: ' . $e->getMessage();
            $this->redirect($this->isAdmin() ? '/admin/users' : '/dashboard');
        }
    }

    // Admin: Toggle user status
    public function toggleStatus($id)
    {
        $this->requireAuth();
        $this->requireAdmin();

        try {
            // Prevent admin from deactivating themselves
            if ($id == $_SESSION['user_id']) {
                throw new Exception('You cannot deactivate your own account');
            }

            $user = $this->userModel->findById($id);

            if (!$user) {
                $this->redirect('/404');
                return;
            }

            $newStatus = $user['is_active'] ? 0 : 1;
            $result = $this->userModel->updateStatus($id, $newStatus);

            if ($result) {
                $statusText = $newStatus ? 'activated' : 'deactivated';
                $_SESSION['success'] = "User {$statusText} successfully!";
            } else {
                throw new Exception('Failed to update user status');
            }

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirect('/admin/users');
    }

    // Admin: Change user role
    public function changeRole($id)
    {
        $this->requireAuth();
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/users');
            return;
        }

        try {
            // Prevent admin from changing their own role
            if ($id == $_SESSION['user_id']) {
                throw new Exception('You cannot change your own role');
            }

            $user = $this->userModel->findById($id);

            if (!$user) {
                $this->redirect('/404');
                return;
            }

            $newRole = $this->sanitize($_POST['role']);
            $validRoles = ['customer', 'admin'];

            if (!in_array($newRole, $validRoles)) {
                throw new Exception('Invalid role');
            }

            $result = $this->userModel->updateRole($id, $newRole);

            if ($result) {
                $_SESSION['success'] = 'User role updated successfully!';
            } else {
                throw new Exception('Failed to update user role');
            }

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirect('/admin/users/' . $id);
    }

    // Forgot password
    public function forgotPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processForgotPassword();
            return;
        }

        $this->view('auth/forgot-password');
    }

    // Process forgot password
    public function processForgotPassword()
    {
        try {
            $email = $this->sanitize($_POST['email']);

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Valid email address is required');
            }

            $user = $this->userModel->findByEmail($email);

            if ($user) {
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $this->userModel->createPasswordResetToken($user['id'], $token, $expiry);

                // In a real application, send email with reset link
                // For demo purposes, we'll just show success message
                $_SESSION['success'] = 'Password reset instructions have been sent to your email.';
            } else {
                // Don't reveal if email exists or not for security
                $_SESSION['success'] = 'If the email exists, password reset instructions have been sent.';
            }

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirect('/forgot-password');
    }

    // Validate registration data
    private function validateRegistrationData($data)
    {
        if (empty($data['first_name']) || empty($data['last_name'])) {
            throw new Exception('First name and last name are required');
        }

        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Valid email address is required');
        }

        if (empty($data['phone'])) {
            throw new Exception('Phone number is required');
        }

        if (!preg_match('/^[\d\s\+\-\(\)]+$/', $data['phone'])) {
            throw new Exception('Invalid phone number format');
        }

        if (empty($data['password']) || strlen($data['password']) < 8) {
            throw new Exception('Password must be at least 8 characters long');
        }

        if ($data['password'] !== $data['confirm_password']) {
            throw new Exception('Passwords do not match');
        }

        // Password strength validation
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $data['password'])) {
            throw new Exception('Password must contain at least one lowercase letter, one uppercase letter, and one number');
        }
    }
}