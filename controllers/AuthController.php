<?php
class AuthController {
    private $conn;

    public function __construct() {
        global $conn;
        $this->conn = $conn;
        error_log("AuthController initialized with connection: " . ($conn ? "success" : "failed"));
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
            error_log("Login attempt - POST data: " . print_r($_POST, true));
            
            $username = sanitize($_POST['username']);
            $password = $_POST['password'];
            
            error_log("Attempting login for username: " . $username);
            
            try {
                $stmt = $this->conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                error_log("Database query result: " . print_r($user, true));

                if ($user && password_verify($password, $user['password'])) {
                    error_log("Password verification successful");
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Regenerate session ID for security
                    session_regenerate_id(true);
                    
                    error_log("Session data set: " . print_r($_SESSION, true));
                    
                    setFlashMessage('success', 'Login successful!');
                    redirect('index.php?page=dashboard');
                } else {
                    error_log("Password verification failed");
                    setFlashMessage('error', 'Invalid username or password');
                    redirect('index.php?page=login');
                }
            } catch (PDOException $e) {
                error_log("Login error: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                setFlashMessage('error', 'An error occurred during login');
                redirect('index.php?page=login');
            }
        } else {
            error_log("Login attempt - Invalid request method or missing action");
            error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
            error_log("POST data: " . print_r($_POST, true));
        }
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
            $username = sanitize($_POST['username']);
            $email = sanitize($_POST['email']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];

            // Validate input
            if (empty($username) || empty($email) || empty($password)) {
                setFlashMessage('error', 'All fields are required');
                redirect('index.php?page=register');
                return;
            }

            if ($password !== $confirm_password) {
                setFlashMessage('error', 'Passwords do not match');
                redirect('index.php?page=register');
                return;
            }

            try {
                // Check if username exists
                $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    setFlashMessage('error', 'Username already exists');
                    redirect('index.php?page=register');
                    return;
                }

                // Check if email exists
                $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    setFlashMessage('error', 'Email already exists');
                    redirect('index.php?page=register');
                    return;
                }

                // Insert new user
                $stmt = $this->conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([
                    $username,
                    $email,
                    password_hash($password, PASSWORD_DEFAULT)
                ]);

                setFlashMessage('success', 'Registration successful! Please login.');
                redirect('index.php?page=login');
            } catch (PDOException $e) {
                error_log("Registration error: " . $e->getMessage());
                setFlashMessage('error', 'An error occurred during registration');
                redirect('index.php?page=register');
            }
        }
    }

    public function logout() {
        // Unset all session variables
        $_SESSION = array();

        // Destroy the session
        session_destroy();

        // Redirect to login page
        redirect('index.php?page=login');
    }
}

// Initialize controller and handle actions
$auth = new AuthController();

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST request received in AuthController");
    error_log("POST data: " . print_r($_POST, true));
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'login':
                $auth->login();
                break;
            case 'register':
                $auth->register();
                break;
            case 'logout':
                $auth->logout();
                break;
        }
    } else {
        error_log("No action specified in POST request");
    }
}
?>
