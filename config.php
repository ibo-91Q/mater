<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'debateskills');
define('DB_USER', 'root');
define('DB_PASSWORD', '');

// Establish database connection
function connectDB() {
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASSWORD);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Session handling
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Authentication functions
function isLoggedIn() {
    startSecureSession();
    return isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("location: login.php");
        exit;
    }
}

function getUserInfo($user_id) {
    $conn = connectDB();
    try {
        $stmt = $conn->prepare("SELECT id, first_name, last_name, email FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return false;
    } finally {
        unset($conn);
    }
}

// Utility functions
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generateRandomToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Error and notification handling
function setFlashMessage($type, $message) {
    startSecureSession();
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    startSecureSession();
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// Display flash message
function displayFlashMessage() {
    $message = getFlashMessage();
    if ($message) {
        $type = $message['type'];
        $text = $message['message'];
        echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>";
        echo $text;
        echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
        echo "</div>";
    }
}
?>
