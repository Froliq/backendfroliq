<?php

namespace App\Controllers;

use App\Models\Movie;
use App\Models\Booking;
use App\Core\Controller;

class MovieController extends Controller
{
    private $movieModel;
    private $bookingModel;

    public function __construct()
    {
        parent::__construct();
        $this->movieModel = new Movie();
        $this->bookingModel = new Booking();
    }

    // Display all movies
    public function index()
    {
        try {
            $page = (int)($_GET['page'] ?? 1);
            $limit = 12;
            $offset = ($page - 1) * $limit;

            // Get filters
            $filters = [
                'genre' => $_GET['genre'] ?? '',
                'language' => $_GET['language'] ?? '',
                'rating' => $_GET['rating'] ?? '',
                'search' => $_GET['search'] ?? '',
                'status' => 'now_showing'
            ];

            $movies = $this->movieModel->getFilteredMovies($filters, $limit, $offset);
            $totalMovies = $this->movieModel->getFilteredMoviesCount($filters);
            $genres = $this->movieModel->getAllGenres();
            $languages = $this->movieModel->getAllLanguages();

            $pagination = [
                'current_page' => $page,
                'total_pages' => ceil($totalMovies / $limit),
                'total_items' => $totalMovies
            ];

            $this->view('movies/index', [
                'movies' => $movies,
                'genres' => $genres,
                'languages' => $languages,
                'filters' => $filters,
                'pagination' => $pagination
            ]);

        } catch (Exception $e) {
            $this->redirect('/error?message=' . urlencode('Error loading movies'));
        }
    }

    // Show single movie with showtimes
    public function show($id)
    {
        try {
            $movie = $this->movieModel->findById($id);

            if (!$movie || $movie['status'] !== 'now_showing') {
                $this->redirect('/404');
                return;
            }

            // Get movie showtimes for next 7 days
            $showtimes = $this->movieModel->getMovieShowtimes($id, 7);
            
            // Get movie reviews
            $reviews = $this->movieModel->getMovieReviews($id, 5);

            $this->view('movies/show', [
                'movie' => $movie,
                'showtimes' => $showtimes,
                'reviews' => $reviews
            ]);

        } catch (Exception $e) {
            $this->redirect('/error?message=' . urlencode('Error loading movie details'));
        }
    }

    // Show coming soon movies
    public function comingSoon()
    {
        try {
            $page = (int)($_GET['page'] ?? 1);
            $limit = 12;
            $offset = ($page - 1) * $limit;

            $filters = [
                'genre' => $_GET['genre'] ?? '',
                'language' => $_GET['language'] ?? '',
                'search' => $_GET['search'] ?? '',
                'status' => 'coming_soon'
            ];

            $movies = $this->movieModel->getFilteredMovies($filters, $limit, $offset);
            $totalMovies = $this->movieModel->getFilteredMoviesCount($filters);
            $genres = $this->movieModel->getAllGenres();
            $languages = $this->movieModel->getAllLanguages();

            $pagination = [
                'current_page' => $page,
                'total_pages' => ceil($totalMovies / $limit),
                'total_items' => $totalMovies
            ];

            $this->view('movies/coming-soon', [
                'movies' => $movies,
                'genres' => $genres,
                'languages' => $languages,
                'filters' => $filters,
                'pagination' => $pagination
            ]);

        } catch (Exception $e) {
            $this->redirect('/error?message=' . urlencode('Error loading coming soon movies'));
        }
    }

    // Admin: Manage movies
    public function manage()
    {
        $this->requireAuth();
        $this->requireAdmin();

        try {
            $page = (int)($_GET['page'] ?? 1);
            $limit = 20;
            $offset = ($page - 1) * $limit;

            $movies = $this->movieModel->getAllMovies($limit, $offset);
            $totalMovies = $this->movieModel->getTotalMoviesCount();

            $pagination = [
                'current_page' => $page,
                'total_pages' => ceil($totalMovies / $limit),
                'total_items' => $totalMovies
            ];

            $this->view('admin/movies/manage', [
                'movies' => $movies,
                'pagination' => $pagination
            ]);

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error loading movies: ' . $e->getMessage();
            $this->redirect('/admin/dashboard');
        }
    }

    // Admin: Show create movie form
    public function create()
    {
        $this->requireAuth();
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->store();
            return;
        }

        $genres = $this->movieModel->getAllGenres();
        $languages = $this->movieModel->getAllLanguages();
        
        $this->view('admin/movies/create', [
            'genres' => $genres,
            'languages' => $languages
        ]);
    }

    // Admin: Store new movie
    public function store()
    {
        $this->requireAuth();
        $this->requireAdmin();

        try {
            // Handle poster upload
            $posterPath = $this->handleImageUpload($_FILES['poster'] ?? null, 'posters');

            $data = [
                'title' => $this->sanitize($_POST['title']),
                'description' => $this->sanitize($_POST['description']),
                'genre' => $this->sanitize($_POST['genre']),
                'language' => $this->sanitize($_POST['language']),
                'duration' => (int)$_POST['duration'],
                'rating' => $this->sanitize($_POST['rating']),
                'director' => $this->sanitize($_POST['director']),
                'cast' => $this->sanitize($_POST['cast']),
                'release_date' => $this->sanitize($_POST['release_date']),
                'trailer_url' => $this->sanitize($_POST['trailer_url'] ?? ''),
                'poster_path' => $posterPath,
                'status' => $this->sanitize($_POST['status']),
                'ticket_price' => (float)$_POST['ticket_price']
            ];

            // Validation
            $this->validateMovieData($data);

            $movieId = $this->movieModel->create($data);

            if ($movieId) {
                $_SESSION['success'] = 'Movie created successfully!';
                $this->redirect('/admin/movies/' . $movieId);
            } else {
                throw new Exception('Failed to create movie');
            }

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/admin/movies/create');
        }
    }

    // Admin: Show edit movie form
    public function edit($id)
    {
        $this->requireAuth();
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update($id);
            return;
        }

        try {
            $movie = $this->movieModel->findById($id);

            if (!$movie) {
                $this->redirect('/404');
                return;
            }

            $genres = $this->movieModel->getAllGenres();
            $languages = $this->movieModel->getAllLanguages();

            $this->view('admin/movies/edit', [
                'movie' => $movie,
                'genres' => $genres,
                'languages' => $languages
            ]);

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error loading movie: ' . $e->getMessage();
            $this->redirect('/admin/movies/manage');
        }
    }

    // Admin: Update movie
    public function update($id)
    {
        $this->requireAuth();
        $this->requireAdmin();

        try {
            $movie = $this->movieModel->findById($id);

            if (!$movie) {
                $this->redirect('/404');
                return;
            }

            // Handle poster upload (optional for updates)
            $posterPath = $movie['poster_path']; // Keep existing poster by default
            if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
                $posterPath = $this->handleImageUpload($_FILES['poster'], 'posters');
                // Delete old poster if new one is uploaded
                if ($movie['poster_path'] && file_exists('uploads/' . $movie['poster_path'])) {
                    unlink('uploads/' . $movie['poster_path']);
                }
            }

            $data = [
                'title' => $this->sanitize($_POST['title']),
                'description' => $this->sanitize($_POST['description']),
                'genre' => $this->sanitize($_POST['genre']),
                'language' => $this->sanitize($_POST['language']),
                'duration' => (int)$_POST['duration'],
                'rating' => $this->sanitize($_POST['rating']),
                'director' => $this->sanitize($_POST['director']),
                'cast' => $this->sanitize($_POST['cast']),
                'release_date' => $this->sanitize($_POST['release_date']),
                'trailer_url' => $this->sanitize($_POST['trailer_url'] ?? ''),
                'poster_path' => $posterPath,
                'status' => $this->sanitize($_POST['status']),
                'ticket_price' => (float)$_POST['ticket_price']
            ];

            // Validation
            $this->validateMovieData($data);

            $result = $this->movieModel->update($id, $data);

            if ($result) {
                $_SESSION['success'] = 'Movie updated successfully!';
                $this->redirect('/admin/movies/' . $id);
            } else {
                throw new Exception('Failed to update movie');
            }

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/admin/movies/' . $id . '/edit');
        }
    }

    // Admin: Delete movie
    public function delete($id)
    {
        $this->requireAuth();
        $this->requireAdmin();

        try {
            $movie = $this->movieModel->findById($id);

            if (!$movie) {
                $this->redirect('/404');
                return;
            }

            // Check if movie has bookings
            $bookingCount = $this->bookingModel->getMovieBookingCount($id);
            if ($bookingCount > 0) {
                throw new Exception('Cannot delete movie with existing bookings. Change status instead.');
            }

            $result = $this->movieModel->delete($id);

            if ($result) {
                // Delete associated poster
                if ($movie['poster_path'] && file_exists('uploads/' . $movie['poster_path'])) {
                    unlink('uploads/' . $movie['poster_path']);
                }

                $_SESSION['success'] = 'Movie deleted successfully!';
            } else {
                throw new Exception('Failed to delete movie');
            }

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirect('/admin/movies/manage');
    }

    // Admin: Manage movie showtimes
    public function showtimes($id)
    {
        $this->requireAuth();
        $this->requireAdmin();

        try {
            $movie = $this->movieModel->findById($id);

            if (!$movie) {
                $this->redirect('/404');
                return;
            }

            $showtimes = $this->movieModel->getMovieShowtimes($id, 30); // Next 30 days

            $this->view('admin/movies/showtimes', [
                'movie' => $movie,
                'showtimes' => $showtimes
            ]);

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error loading showtimes: ' . $e->getMessage();
            $this->redirect('/admin/movies/manage');
        }
    }

    // Admin: Add showtime
    public function addShowtime($id)
    {
        $this->requireAuth();
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = [
                    'movie_id' => $id,
                    'show_date' => $this->sanitize($_POST['show_date']),
                    'show_time' => $this->sanitize($_POST['show_time']),
                    'hall_number' => (int)$_POST['hall_number'],
                    'available_seats' => (int)$_POST['available_seats']
                ];

                // Validation
                if (empty($data['show_date']) || empty($data['show_time'])) {
                    throw new Exception('Date and time are required');
                }

                if (strtotime($data['show_date'] . ' ' . $data['show_time']) <= time()) {
                    throw new Exception('Showtime must be in the future');
                }

                if ($data['hall_number'] <= 0 || $data['available_seats'] <= 0) {
                    throw new Exception('Hall number and seats must be positive numbers');
                }

                $result = $this->movieModel->addShowtime($data);

                if ($result) {
                    $_SESSION['success'] = 'Showtime added successfully!';
                } else {
                    throw new Exception('Failed to add showtime');
                }

            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
            }
        }

        $this->redirect('/admin/movies/' . $id . '/showtimes');
    }

    // Book movie ticket
    public function book($id)
    {
        $this->requireAuth();

        try {
            $movie = $this->movieModel->findById($id);

            if (!$movie || $movie['status'] !== 'now_showing') {
                $this->redirect('/404');
                return;
            }

            $this->redirect('/bookings/create?type=movie&item_id=' . $id);

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/movies/' . $id);
        }
    }

    // Get movies by genre (API endpoint)
    public function getByGenre($genre)
    {
        try {
            $limit = (int)($_GET['limit'] ?? 10);
            $movies = $this->movieModel->getMoviesByGenre($genre, $limit);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $movies
            ]);

        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    // Search movies (API endpoint)
    public function search()
    {
        try {
            $query = $_GET['q'] ?? '';
            $limit = (int)($_GET['limit'] ?? 10);

            if (strlen($query) < 2) {
                throw new Exception('Search query must be at least 2 characters');
            }

            $movies = $this->movieModel->searchMovies($query, $limit);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $movies
            ]);

        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    // Get available showtimes for a specific date
    public function getShowtimes($id)
    {
        try {
            $date = $_GET['date'] ?? date('Y-m-d');
            $showtimes = $this->movieModel->getMovieShowtimesByDate($id, $date);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $showtimes
            ]);

        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    // Validate movie data
    private function validateMovieData($data)
    {
        if (empty($data['title'])) {
            throw new Exception('Movie title is required');
        }

        if (empty($data['description'])) {
            throw new Exception('Movie description is required');
        }

        if (empty($data['genre'])) {
            throw new Exception('Genre is required');
        }

        if (empty($data['language'])) {
            throw new Exception('Language is required');
        }

        if ($data['duration'] <= 0) {
            throw new Exception('Duration must be greater than 0');
        }

        if (empty($data['rating'])) {
            throw new Exception('Rating is required');
        }

        if (empty($data['director'])) {
            throw new Exception('Director is required');
        }

        if (empty($data['release_date'])) {
            throw new Exception('Release date is required');
        }

        if (!in_array($data['status'], ['now_showing', 'coming_soon', 'ended'])) {
            throw new Exception('Invalid status');
        }

        if ($data['ticket_price'] < 0) {
            throw new Exception('Ticket price cannot be negative');
        }

        // Validate trailer URL if provided
        if (!empty($data['trailer_url']) && !filter_var($data['trailer_url'], FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid trailer URL format');
        }
    }

    // Handle image upload
    private function handleImageUpload($file, $subfolder = 'movies')
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

        $uploadDir = "uploads/{$subfolder}/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'movie_' . time() . '_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Failed to upload image');
        }

        return "{$subfolder}/" . $filename;
    }
}