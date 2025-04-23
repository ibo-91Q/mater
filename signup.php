<?php
// Start session
session_start();

// Show all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Redirect if already logged in
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    //  delete session variables
    unset($_SESSION["loggedin"]);
    // header("location: dashboard.php");
    exit;
}

// Database credentials
$host = "localhost";
$dbname = "debateskills";
$username = "root";
$password = "";

// Connect to database
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}


// Initialize variables
$first_name = $last_name = $email = $password = "";
$first_name_err = $last_name_err = $email_err = $password_err = $terms_err = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // First name
    if (empty(trim($_POST["firstName"]))) {
        $first_name_err = "Please enter your first name.";
    } else {
        $first_name = trim($_POST["firstName"]);
    }
    // Last name
    if (empty(trim($_POST["lastName"]))) {
        $last_name_err = "Please enter your last name.";
    } else {
        $last_name = trim($_POST["lastName"]);
    }

    // Email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } else {
        $sql = "SELECT * FROM users WHERE email = :email";
                $stmt = $conn->prepare($sql);
        $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
        $param_email = trim($_POST["email"]);
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                $email_err = "This email is already taken.";
            } else {
                $email = $param_email;
            }
        } else {
            echo "Error checking email.";
        }
    }

    // Password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["password"])) < 8) {
        $password_err = "Password must be at least 8 characters.";
    } else {
        $password = trim($_POST["password"]);
        if (!preg_match('/[0-9]/', $password) || !preg_match('/[^a-zA-Z0-9]/', $password)) {
            $password_err = "Password must include a number and a symbol.";
        }
    }

    // Terms agreement
    if (!isset($_POST["termsAgree"])) {
        $terms_err = "You must agree to the terms.";
    }


// If no errors, insert user
if (empty($first_name_err) && empty($last_name_err) && empty($email_err) && empty($password_err) && empty($terms_err)) {
    try {
        $sql = "INSERT INTO users (first_name, last_name, email, password_hash) VALUES (:first_name, :last_name, :email, :password_hash)";
        $stmt = $conn->prepare($sql);

        $stmt->bindParam(":first_name", $first_name);
        $stmt->bindParam(":last_name", $last_name);
        $stmt->bindParam(":email", $email);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt->bindParam(":password_hash", $hashed_password);

        if ($stmt->execute()) {
            header("Location: login.php?registered=true");
            
            exit;
        } else {
            echo "Something went wrong. Please try again.";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

    unset($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DebateSkills - Sign Up</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-color: #2563EB;
            --primary-light: #EFF6FF;
            --success-color: #10B981;
            --success-light: #ECFDF5;
            --dark-color: #1F2937;
            --gray-color: #9CA3AF;
            --light-gray: #F9FAFB;
            --border-color: #E5E7EB;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #F9FAFB;
            color: #1F2937;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .signup-container {
            width: 100%;
            max-width: 1000px;
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .logo {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .logo i {
            margin-right: 8px;
        }

        .help-link {
            color: var(--gray-color);
            text-decoration: none;
            font-size: 0.875rem;
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
        }

        .left-panel {
            padding: 40px;
            background-color: var(--primary-light);
        }

        .illustration {
            width: 100%;
            height: auto;
        }

        .panel-title {
            font-weight: 700;
            font-size: 1.75rem;
            margin-bottom: 1rem;
            color: var(--dark-color);
        }

        .panel-text {
            color: #6B7280;
            margin-bottom: 2rem;
            line-height: 1.5;
        }

        .form-container {
            padding: 40px;
        }

        .create-account {
            font-weight: 700;
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }

        .welcome-text {
            color: #6B7280;
            margin-bottom: 2rem;
        }

        .form-label {
            font-weight: 500;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .form-control {
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-check {
            margin-bottom: 1.5rem;
        }

        .btn-sign-up {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            padding: 12px 0;
            border-radius: 8px;
            width: 100%;
            border: none;
            margin-bottom: 1.5rem;
        }

        .btn-sign-up:hover {
            background-color: #1D4ED8;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
        }

        .divider::before, .divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background-color: var(--border-color);
        }

        .divider-text {
            padding: 0 1rem;
            color: var(--gray-color);
            font-size: 0.875rem;
        }

        .social-signup-button {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px 0;
            border-radius: 8px;
            width: 100%;
            border: 1px solid var(--border-color);
            background-color: white;
            font-weight: 500;
            color: var(--dark-color);
            margin-bottom: 1rem;
        }

        .social-icon {
            margin-right: 12px;
        }

        .google-icon {
            color: #EA4335;
        }

        .microsoft-icon {
            color: #00a4ef;
        }

        .already-account {
            text-align: center;
            color: #6B7280;
            font-size: 0.875rem;
        }

        .login-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .login-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <a href="#" class="help-link">Need Help?</a>
    
    <div class="signup-container">
        <div class="row g-0">
            <!-- Left Panel -->
            <div class="col-md-5 left-panel">
                <div class="logo">
                    <i class="bi bi-chat-square-text"></i> DebateSkills
                </div>
                <img src="https://static.vecteezy.com/system/resources/previews/006/846/802/original/business-colleagues-debate-cartoon-illustration-people-sitting-at-table-and-discussing-ideas-brainstorming-business-meeting-teamwork-cooperation-collaboration-vector.jpg" alt="Debate Illustration" class="illustration">
                <h1 class="panel-title mt-4">Join Our Debating Community</h1>
                <p class="panel-text">Create an account to access courses, practice with other debaters, and track your progress as you enhance your skills.</p>
            </div>

            <!-- Right Panel (Sign Up Form) -->
            <div class="col-md-7 form-container">
                <h1 class="create-account">Create Account</h1>
                <p class="welcome-text">Start your journey to becoming a better debater</p>

                <form method="POST" action="signup.php">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="firstName" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="firstName" name="firstName" placeholder="Enter your first name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="lastName" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="lastName" name="lastName" placeholder="Enter your last name">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email">
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Create a password">
                        <div class="form-text">Must be at least 8 characters long and include a number and symbol</div>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="termsAgree" name="termsAgree">
                        <label class="form-check-label" for="termsAgree">
                            I agree to the <a href="#" class="login-link">Terms of Service</a> and <a href="#" class="login-link">Privacy Policy</a>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-sign-up">Create Account</button>

                    <div class="divider">
                        <span class="divider-text">Or sign up with</span>
                    </div>

                    <button type="button" class="social-signup-button mb-3">
                        <svg class="social-icon google-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" />
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                        </svg>
                        Continue with Google
                    </button>
                    
                    <button type="button" class="social-signup-button">
                        <svg class="social-icon microsoft-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 23 23" fill="currentColor">
                            <path d="M11 11H0V0h11zm12 0H12V0h11zm-12 1h11v11H11zm-1 0H0v11h10z" />
                        </svg>
                        Continue with Microsoft
                    </button>

                    <p class="already-account mt-4">
                        Already have an account? <a href="login.html" class="login-link">Sign in</a>
                    </p>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>