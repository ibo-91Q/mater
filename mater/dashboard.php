<?php
// Start the session
session_start();

// Check if the user is logged in, if not redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

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

// Get user information
$user_id = $_SESSION["id"];
$user_info = [];

try {
    $stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id  = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
    //exit(var_dump($user_info));
} catch(PDOException $e) {
    // Log error or handle it silently
}
// Get user's course progress
$course_progress = 0; // Default value

try {
    $stmt = $conn->prepare("SELECT AVG(progress) as avg_progress FROM course_progress WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result && $result['avg_progress'] !== null) {
        $course_progress = round($result['avg_progress']);
    }
} catch(PDOException $e) {
    // Log error or handle it silently
}

// Get number of practice debates completed this month
$practice_debates = 12; // Default value

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as debate_count FROM practice_sessions 
                          WHERE user_id = :user_id 
                          AND MONTH(date_completed) = MONTH(CURRENT_DATE()) 
                          AND YEAR(date_completed) = YEAR(CURRENT_DATE())");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $practice_debates = $result['debate_count'];
    }
} catch(PDOException $e) {
    // Log error or handle it silently
}

// Get community rating
$community_rating = 4.8; // Default value

try {
    $stmt = $conn->prepare("SELECT AVG(rating) as avg_rating FROM peer_reviews WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result && $result['avg_rating'] !== null) {
        $community_rating = round($result['avg_rating'], 1);
    }
} catch(PDOException $e) {
    // Log error or handle it silently
}

// Get upcoming events
$upcoming_events = [];

try {
    $stmt = $conn->prepare("SELECT event_title, event_time FROM events 
                          WHERE event_time > CURRENT_TIMESTAMP 
                          ORDER BY event_time ASC LIMIT 2");
    $stmt->execute();
    $upcoming_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Log error or handle it silently
}

// Get recommended courses
$recommended_courses = [];

try {
    $stmt = $conn->prepare("SELECT c.title, c.difficulty, c.duration 
                          FROM courses c
                          JOIN user_interests ui ON c.category_id = ui.category_id
                          WHERE ui.user_id = :user_id
                          LIMIT 2");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $recommended_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Log error or handle it silently
}

// Close connection
unset($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DebateSkills - Dashboard</title>

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
            --warning-color: #F59E0B;
            --warning-light: #FFFBEB;
            --danger-color: #EF4444;
            --danger-light: #FEF2F2;
            --purple-color: #8B5CF6;
            --purple-light: #F5F3FF;
            --dark-color: #1F2937;
            --gray-color: #9CA3AF;
            --light-gray: #F9FAFB;
            --border-color: #E5E7EB;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #F9FAFB;
            color: #1F2937;
        }

        .navbar {
            background-color: #FFFFFF;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color);
        }

        .nav-link {
            color: #4B5563;
            font-weight: 500;
            padding: 0.75rem 1rem;
        }

        .nav-link.active {
            color: var(--primary-color);
        }

        .card {
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
        }

        .progress {
            height: 10px;
            border-radius: 5px;
        }

        .progress-bar {
            background-color: var(--primary-color);
        }

        .stats-card {
            padding: 24px;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            height: 100%;
        }

        .stats-card-blue {
            background-color: var(--primary-light);
        }

        .stats-card-green {
            background-color: var(--success-light);
        }

        .stats-card-purple {
            background-color: var(--purple-light);
        }

        .stats-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 8px;
            line-height: 1;
        }

        .stats-value.blue {
            color: var(--primary-color);
        }

        .stats-value.green {
            color: var(--success-color);
        }

        .stats-value.purple {
            color: var(--purple-color);
        }

        .stats-label {
            font-weight: 600;
            margin-bottom: 16px;
            color: var(--dark-color);
        }

        .stats-description {
            color: var(--gray-color);
            font-size: 0.875rem;
        }

        .stats-icon {
            position: absolute;
            top: 20px;
            right: 24px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .event-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
        }

        .event-card:last-child {
            border-bottom: none;
        }

        .event-info h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .event-time {
            color: var(--gray-color);
            font-size: 0.875rem;
        }

        .btn-join {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
            border-radius: 6px;
            padding: 8px 16px;
            border: none;
        }

        .btn-join:hover {
            background-color: #1D4ED8;
            color: white;
        }

        .recommended-course {
            display: flex;
            align-items: center;
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
        }

        .recommended-course:last-child {
            border-bottom: none;
        }

        .course-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
            flex-shrink: 0;
        }

        .course-icon.blue {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }

        .course-icon.green {
            background-color: var(--success-light);
            color: var(--success-color);
        }

        .course-info h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .course-meta {
            color: var(--gray-color);
            font-size: 0.875rem;
        }

        .quick-action {
            background-color: white;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            transition: all 0.2s ease;
            border: 1px solid var(--border-color);
            cursor: pointer;
        }

        .quick-action:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .action-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .action-icon.blue {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }

        .action-icon.green {
            background-color: var(--success-light);
            color: var(--success-color);
        }

        .action-icon.purple {
            background-color: var(--purple-light);
            color: var(--purple-color);
        }

        .action-icon.orange {
            background-color: #FEF3C7;
            color: #D97706;
        }

        .section-title {
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 24px;
            margin-top: 40px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .notification-icon {
            position: relative;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background-color: var(--danger-color);
            color: white;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dropdown-menu {
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
            padding: 0.5rem;
        }

        .dropdown-item {
            border-radius: 6px;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .dropdown-item:hover {
            background-color: var(--primary-light);
        }

        .dropdown-divider {
            margin: 0.5rem 0;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">DebateSkills</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-house-door me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="courses.php">
                            <i class="bi bi-journal-text me-1"></i> Courses
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="practice.php">
                            <i class="bi bi-mic me-1"></i> Practice
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="community.php">
                            <i class="bi bi-people me-1"></i> Community
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <div class="notification-icon me-3">
                        <i class="bi bi-bell fs-5"></i>
                        <span class="notification-badge">0</span>
                    </div>
                    <div class="dropdown">
                        <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="User Avatar" class="user-avatar dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <?php print_r($user_info); ?>
                            <li><span class="dropdown-item fw-bold"><?php echo htmlspecialchars($user_info['first_name'] . ' ' . $user_info['last_name']); ?></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-4">
                <div class="stats-card stats-card-blue">
                    <div class="stats-icon">
                        <i class="bi bi-mortarboard fs-4 text-primary"></i>
                    </div>
                    <h2 class="stats-label">Course Progress</h2>
                    <div class="stats-value blue"><?php echo $course_progress; ?>%</div>
                    <div class="progress mb-3">
                        <div class="progress-bar" role="progressbar" style="width: <?php echo $course_progress; ?>%" aria-valuenow="<?php echo $course_progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stats-card stats-card-green">
                    <div class="stats-icon">
                        <i class="bi bi-chat-square-text fs-4 text-success"></i>
                    </div>
                    <h2 class="stats-label">Practice Debates</h2>
                    <div class="stats-value green"><?php echo $practice_debates; ?></div>
                    <div class="stats-description">Completed this month</div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stats-card stats-card-purple">
                    <div class="stats-icon">
                        <i class="bi bi-star fs-4 text-purple"></i>
                    </div>
                    <h2 class="stats-label">Community Rating</h2>
                    <div class="stats-value purple"><?php echo $community_rating; ?></div>
                    <div class="stats-description">Based on peer reviews</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Upcoming Events -->
            <div class="col-lg-6">
                <h2 class="section-title">Upcoming Events</h2>
                <div class="card">
                    <?php if (count($upcoming_events) > 0): ?>
                        <?php foreach ($upcoming_events as $event): ?>
                            <div class="event-card">
                                <div class="event-info">
                                    <h3><?php echo htmlspecialchars($event['event_title']); ?></h3>
                                    <div class="event-time"><?php echo date('l, g:i A', strtotime($event['event_time'])); ?></div>
                                </div>
                                <button class="btn btn-join">Join</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="event-card">
                            <div class="event-info">
                                <h3>Mock Debate: Climate Change</h3>
                                <div class="event-time">Tomorrow, 2:00 PM</div>
                            </div>
                            <button class="btn btn-join">Join</button>
                        </div>
                        <div class="event-card">
                            <div class="event-info">
                                <h3>Workshop: Advanced Arguments</h3>
                                <div class="event-time">Friday, 3:30 PM</div>
                            </div>
                            <button class="btn btn-join">Join</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recommended Courses -->
            <div class="col-lg-6">
                <h2 class="section-title">Recommended Courses</h2>
                <div class="card">
                    <?php if (count($recommended_courses) > 0): ?>
                        <?php foreach ($recommended_courses as $course): ?>
                            <div class="recommended-course">
                                <div class="course-icon blue">
                                    <i class="bi bi-people"></i>
                                </div>
                                <div class="course-info">
                                    <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                                    <div class="course-meta"><?php echo htmlspecialchars($course['difficulty']); ?> • <?php echo htmlspecialchars($course['duration']); ?> hours</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="recommended-course">
                            <div class="course-icon blue">
                                <i class="bi bi-people"></i>
                            </div>
                            <div class="course-info">
                                <h3>Public Speaking Mastery</h3>
                                <div class="course-meta">Intermediate • 8 hours</div>
                            </div>
                        </div>
                        <div class="recommended-course">
                            <div class="course-icon green">
                                <i class="bi bi-diagram-3"></i>
                            </div>
                            <div class="course-info">
                                <h3>Logical Fallacies</h3>
                                <div class="course-meta">Advanced • 6 hours</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <h2 class="section-title">Quick Actions</h2>
        <div class="row">
            <div class="col-md-3 mb-4">
                <a href="practice.php" class="text-decoration-none">
                    <div class="quick-action">
                        <div class="action-icon blue">
                            <i class="bi bi-mic"></i>
                        </div>
                        <h3>Start Practice</h3>
                    </div>
                </a>
            </div>
            <div class="col-md-3 mb-4">
                <a href="feedback.php" class="text-decoration-none">
                    <div class="quick-action">
                        <div class="action-icon green">
                            <i class="bi bi-chat-quote"></i>
                        </div>
                        <h3>Get Feedback</h3>
                    </div>
                </a>
            </div>
            <div class="col-md-3 mb-4">
                <a href="community.php" class="text-decoration-none">
                    <div class="quick-action">
                        <div class="action-icon purple">
                            <i class="bi bi-people"></i>
                        </div>
                        <h3>Join Discussion</h3>
                    </div>
                </a>
            </div>
            <div class="col-md-3 mb-4">
                <a href="resources.php" class="text-decoration-none">
                    <div class="quick-action">
                        <div class="action-icon orange">
                            <i class="bi bi-folder"></i>
                        </div>
                        <h3>Resources</h3>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>