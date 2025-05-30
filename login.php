<?php
// Start session
session_start();

// Database connection parameters
$host = "localhost";
$dbname = "debateskills";
$username = "root";
$password = "";

// Establish database connection
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Initialize variables
$email = $password = "";
$email_err = $password_err = $login_err = "";

// Process form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate email
    if(empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Check input errors before processing login
    if(empty($email_err) && empty($password_err)) {
         
$sql = "SELECT user_id AS id, email, password_hash AS password FROM users WHERE email = :email";
        
        if($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
            
            // Set parameters
            $param_email = $email;
            
            // Attempt to execute the prepared statement
            if($stmt->execute()) {
                // Check if email exists
                if($stmt->rowCount() == 1) {
                    if($row = $stmt->fetch()) {
                        $id = $row["id"];
                        $email = $row["email"];
                        $hashed_password = $row["password"];
                        
                        // Verify password
                        if(password_verify($password, $hashed_password)) {
                            // Password is correct, start a new session
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["email"] = $email;
                            
                            // Redirect user to dashboard page
                            header("location: dashboard.php");
                        } else {
                            // Password is not valid
                            $login_err = "Invalid email or password.";
                        }
                    }
                } else {
                    // Email doesn't exist
                    $login_err = "Invalid email or password.";
                }
            } else {
                $login_err = "Oops! Something went wrong. Please try again later.";
            }
            
            // Close statement
            unset($stmt);
        }
    }
    
    // Close connection
    unset($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DebateSkills - Login</title>

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

        .login-container {
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

        .welcome-back {
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

        .forgot-password {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        .btn-sign-in {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            padding: 12px 0;
            border-radius: 8px;
            width: 100%;
            border: none;
            margin-bottom: 1.5rem;
        }

        .btn-sign-in:hover {
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

        .social-login-button {
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

        .form-tab {
            display: inline-block;
            padding: 0.5rem 1rem;
            margin-right: 1rem;
            font-weight: 500;
            color: var(--gray-color);
            border-bottom: 2px solid transparent;
            cursor: pointer;
        }

        .form-tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        .no-account {
            text-align: center;
            color: #6B7280;
            font-size: 0.875rem;
            margin-top: 1.5rem;
        }

        .signup-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .signup-link:hover {
            text-decoration: underline;
        }

        .error-text {
            color: var(--danger-color);
            font-size: 0.875rem;
            margin-top: -1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <a href="#" class="help-link">Need Help?</a>
    
    <div class="login-container">
        <div class="row g-0">
            <!-- Left Panel -->
            <div class="col-md-5 left-panel">
                <div class="logo">
                    <i class="bi bi-chat-square-text"></i> DebateSkills
                </div>
                <img src="https://static.vecteezy.com/system/resources/previews/006/846/802/original/business-colleagues-debate-cartoon-illustration-people-sitting-at-table-and-discussing-ideas-brainstorming-business-meeting-teamwork-cooperation-collaboration-vector.jpg" alt="Debate Illustration" class="illustration">
                <h1 class="panel-title mt-4">Master the Art of Debate</h1>
                <p class="panel-text">Join our community of debaters and enhance your skills through structured learning and practice.</p>
            </div>

            <!-- Right Panel (Sign In Form) -->
            <div class="col-md-7 form-container">
                <div class="mb-4">
                    <span class="form-tab active">Sign In</span>
                    <a href="signup.php" class="form-tab">Sign Up</a>
                </div>

                <h1 class="welcome-back">Welcome Back</h1>
                <p class="welcome-text">Sign in to your account to continue learning</p>

                <?php 
                if(!empty($login_err)) {
                    echo '<div class="alert alert-danger">' . $login_err . '</div>';
                }        
                ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" id="email" name="email" placeholder="Enter your email" value="<?php echo $email; ?>">
                        <?php if(!empty($email_err)) { echo '<div class="error-text">' . $email_err . '</div>'; } ?>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" id="password" name="password" placeholder="Enter your password">
                        <?php if(!empty($password_err)) { echo '<div class="error-text">' . $password_err . '</div>'; } ?>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        <a href="#" class="forgot-password">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn-sign-in">Sign In</button>

                    <div class="divider">
                        <span class="divider-text">Or continue with</span>
                    </div>

                    <button type="button" class="social-login-button mb-3">
                        <svg class="social-icon google-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" />
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                        </svg>
                        Google
                    </button>
                    
                    <button type="button" class="social-login-button">
                        <svg class="social-icon microsoft-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 23 23" fill="currentColor">
                            <path d="M11 11H0V0h11zm12 0H12V0h11zm-12 1h11v11H11zm-1 0H0v11h10z" />
                        </svg>
                        Microsoft
                    </button>

                    <p class="no-account">
                        Don't have an account? <a href="signup.php" class="signup-link">Sign up</a>
                    </p>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>