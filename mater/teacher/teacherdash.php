<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/db.php'; // Include database connection

$teacher_id = $_SESSION['teacher_id'];

// Fetch teacher details
$query = $db->prepare("SELECT name FROM teachers WHERE id = ?");
$query->execute([$teacher_id]);
$teacher = $query->fetch(PDO::FETCH_ASSOC);

// Ensure teacher data exists
if (!$teacher) {
    echo "Error: Teacher not found.";
    exit();
}

// Fetch stats
$stats_query = $db->prepare("
    SELECT 
        (SELECT COUNT(*) FROM students WHERE teacher_id = ?) AS active_students,
        (SELECT COUNT(*) FROM classes WHERE teacher_id = ? AND WEEK(class_date) = WEEK(CURDATE())) AS weekly_classes,
        (SELECT IFNULL(SUM(duration), 0) FROM classes WHERE teacher_id = ?) AS teaching_hours,
        (SELECT IFNULL(AVG(rating), 0) FROM feedback WHERE teacher_id = ?) AS avg_rating
");
$stats_query->execute([$teacher_id, $teacher_id, $teacher_id, $teacher_id]);
$stats = $stats_query->fetch(PDO::FETCH_ASSOC);

// Fetch today's classes
$classes_query = $db->prepare("
    SELECT id, title, start_time, end_time, level, student_count 
    FROM classes 
    WHERE teacher_id = ? AND DATE(class_date) = CURDATE()
");
$classes_query->execute([$teacher_id]);
$classes = $classes_query->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent activity
$activity_query = $db->prepare("
    SELECT description, 
           DATE_FORMAT(created_at, '%M %d, %Y %h:%i %p') AS formatted_date 
    FROM activity_log 
    WHERE teacher_id = :teacher_id 
    ORDER BY created_at DESC 
    LIMIT 5
");
$activity_query->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
$activity_query->execute();
$activities = $activity_query->fetchAll(PDO::FETCH_ASSOC);

// Fetch students
$students_query = $db->prepare("
    SELECT name, level, class_count, avatar 
    FROM students 
    WHERE teacher_id = ? 
    LIMIT 5
");
$students_query->execute([$teacher_id]);
$students = $students_query->fetchAll(PDO::FETCH_ASSOC);

// Fetch feedback
$feedback_query = $db->prepare("
    SELECT s.name AS student_name, f.rating, f.comment, s.avatar 
    FROM feedback f 
    JOIN students s ON f.student_id = s.id 
    WHERE f.teacher_id = ? 
    ORDER BY f.created_at DESC 
    LIMIT 3
");
$feedback_query->execute([$teacher_id]);
$feedbacks = $feedback_query->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DebateSkills - Teacher Dashboard</title>

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

        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            width: 250px;
            padding-top: 56px;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            z-index: 40;
            overflow-y: auto;
        }
        
        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: #6B7280;
            text-decoration: none;
            border-radius: 8px;
            margin: 4px 8px;
            transition: all 0.2s ease;
        }
        
        .sidebar-link:hover {
            background-color: #F3F4F6;
            color: #374151;
        }
        
        .sidebar-link.active {
            background-color: var(--primary-light);
            color: var(--primary-color);
            font-weight: 500;
        }
        
        .sidebar-link i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar-link span {
                display: none;
            }
            
            .sidebar-link i {
                margin-right: 0;
                font-size: 1.25rem;
            }
            
            .main-content {
                margin-left: 70px;
            }
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid var(--border-color);
            padding: 15px 20px;
            font-weight: 600;
        }
        
        .stats-box {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            height: 100%;
        }
        
        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stats-label {
            color: var(--gray-color);
            font-size: 0.875rem;
        }
        
        .student-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .student-item:last-child {
            border-bottom: none;
        }
        
        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 15px;
        }
        
        .student-info {
            flex-grow: 1;
        }
        
        .student-name {
            font-weight: 600;
            margin-bottom: 3px;
        }
        
        .student-details {
            font-size: 0.875rem;
            color: var(--gray-color);
        }
        
        .student-action {
            margin-left: 10px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #1D4ED8;
            border-color: #1D4ED8;
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .class-box {
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .class-time {
            font-size: 0.875rem;
            color: var(--gray-color);
            margin-bottom: 10px;
        }
        
        .class-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .class-details {
            font-size: 0.875rem;
            color: var(--gray-color);
            margin-bottom: 15px;
        }
        
        .badge-primary {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }
        
        .badge-success {
            background-color: var(--success-light);
            color: var(--success-color);
        }
        
        .badge-warning {
            background-color: var(--warning-light);
            color: var(--warning-color);
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
        
        .btn-action {
            border-radius: 8px;
            padding: 10px 16px;
            font-weight: 500;
        }
        
        .btn-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--primary-light);
            color: var(--primary-color);
            margin-left: 10px;
        }
        
        .btn-icon:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .search-input {
            border-radius: 8px;
            border: 1px solid var(--border-color);
            padding: 10px 15px;
            width: 100%;
        }
        
        .search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-chat-square-text me-2"></i>DebateSkills
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item position-relative me-3">
                        <a class="nav-link" href="#">
                            <i class="bi bi-bell"></i>
                            <span class="notification-badge">3</span>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <img src="https://randomuser.me/api/portraits/women/32.jpg" alt="Teacher" class="rounded-circle me-2" width="32" height="32">
                            <span><?= htmlspecialchars($teacher['name'], ENT_QUOTES, 'UTF-8') ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar d-none d-md-block">
        <a href="dashboard.php" class="sidebar-link active">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>
        <a href="students.php" class="sidebar-link">
            <i class="bi bi-people"></i>
            <span>Students</span>
        </a>
        <a href="classes.php" class="sidebar-link">
            <i class="bi bi-calendar3"></i>
            <span>Classes</span>
        </a>
        <a href="assignments.php" class="sidebar-link">
            <i class="bi bi-file-earmark-text"></i>
            <span>Assignments</span>
        </a>
        <a href="resources.php" class="sidebar-link">
            <i class="bi bi-folder2"></i>
            <span>Resources</span>
        </a>
        <a href="messages.php" class="sidebar-link">
            <i class="bi bi-chat-left-text"></i>
            <span>Messages</span>
        </a>
        <a href="reports.php" class="sidebar-link">
            <i class="bi bi-bar-chart"></i>
            <span>Reports</span>
        </a>
        <a href="settings.php" class="sidebar-link">
            <i class="bi bi-gear"></i>
            <span>Settings</span>
        </a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="mb-4">
            <h1 class="mb-3">Welcome, <?= htmlspecialchars($teacher['name'], ENT_QUOTES, 'UTF-8') ?>!</h1>
            <p class="text-secondary">Dashboard overview for <?= htmlspecialchars(date('l, F j, Y'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <!-- Quick Actions Row -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body d-flex flex-wrap gap-2">
                        <button class="btn btn-primary btn-action"><i class="bi bi-plus-circle me-2"></i>New Class</button>
                        <button class="btn btn-primary btn-action"><i class="bi bi-chat-dots me-2"></i>Message Students</button>
                        <button class="btn btn-primary btn-action"><i class="bi bi-file-earmark-plus me-2"></i>Create Assignment</button>
                        <button class="btn btn-outline-primary btn-action ms-auto"><i class="bi bi-file-earmark-arrow-down me-2"></i>Export Report</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-box">
                    <div class="stats-icon" style="background-color: var(--primary-light); color: var(--primary-color);">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stats-number"><?= htmlspecialchars($stats['active_students'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="stats-label">Active Students</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-box">
                    <div class="stats-icon" style="background-color: var(--purple-light); color: var(--purple-color);">
                        <i class="bi bi-calendar3-week"></i>
                    </div>
                    <div class="stats-number"><?= htmlspecialchars($stats['weekly_classes'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="stats-label">Classes This Week</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-box">
                    <div class="stats-icon" style="background-color: var(--success-light); color: var(--success-color);">
                        <i class="bi bi-clock"></i>
                    </div>
                    <div class="stats-number"><?= htmlspecialchars($stats['teaching_hours'], ENT_QUOTES, 'UTF-8') ?>h</div>
                    <div class="stats-label">Teaching Hours</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-box">
                    <div class="stats-icon" style="background-color: var(--warning-light); color: var(--warning-color);">
                        <i class="bi bi-star"></i>
                    </div>
                    <div class="stats-number"><?= htmlspecialchars(number_format($stats['avg_rating'], 1), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="stats-label">Average Rating</div>
                </div>
            </div>
        </div>

        <!-- Content Row -->
        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-7">
                <!-- Today's Classes -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Today's Classes</span>
                        <a href="classes.php" class="text-primary text-decoration-none">View All</a>
                    </div>
                    <div class="card-body">
                        <?php foreach ($classes as $class): ?>
                            <div class="class-box">
                                <div class="class-time"><?= htmlspecialchars($class['start_time'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($class['end_time'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="class-title"><?= htmlspecialchars($class['title'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="class-details"><?= htmlspecialchars($class['student_count'], ENT_QUOTES, 'UTF-8') ?> students • <?= htmlspecialchars($class['level'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="d-flex align-items-center">
                                    <span class="badge badge-primary rounded-pill px-3 py-2">Upcoming</span>
                                    <a href="start_class.php?id=<?= htmlspecialchars($class['id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-sm ms-auto">Start Class</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Recent Activity</span>
                        <a href="activity.php" class="text-primary text-decoration-none">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($activities as $activity): ?>
                                <div class="list-group-item d-flex align-items-center py-3">
                                    <div class="me-3 p-2 rounded-circle" style="background-color: var(--primary-light);">
                                        <i class="bi bi-chat-left-text text-primary"></i>
                                    </div>
                                    <div>
                                        <p class="mb-0"><?= htmlspecialchars($activity['description'], ENT_QUOTES, 'UTF-8') ?></p>
                                        <small class="text-muted"><?= htmlspecialchars($activity['formatted_date'], ENT_QUOTES, 'UTF-8') ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-5">
                <!-- Student List -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>My Students</span>
                        <a href="students.php" class="text-primary text-decoration-none">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="d-flex p-3">
                            <input type="text" class="search-input" placeholder="Search students...">
                        </div>
                        <?php foreach ($students as $student): ?>
                            <div class="student-item">
                                <img src="<?= htmlspecialchars($student['avatar'], ENT_QUOTES, 'UTF-8') ?>" alt="Student" class="student-avatar">
                                <div class="student-info">
                                    <div class="student-name"><?= htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="student-details"><?= htmlspecialchars($student['level'], ENT_QUOTES, 'UTF-8') ?> • <?= htmlspecialchars($student['class_count'], ENT_QUOTES, 'UTF-8') ?> classes</div>
                                </div>
                                <a href="#" class="btn-icon student-action"><i class="bi bi-chat"></i></a>
                                <a href="#" class="btn-icon student-action"><i class="bi bi-three-dots"></i></a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Student Feedback -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Recent Feedback</span>
                        <a href="feedback.php" class="text-primary text-decoration-none">View All</a>
                    </div>
                    <div class="card-body">
                        <?php foreach ($feedbacks as $feedback): ?>
                            <div class="mb-3 pb-3 border-bottom">
                                <div class="d-flex align-items-center mb-2">
                                    <img src="<?= htmlspecialchars($feedback['avatar'], ENT_QUOTES, 'UTF-8') ?>" alt="Student" class="rounded-circle me-2" width="32" height="32">
                                    <span class="fw-bold"><?= htmlspecialchars($feedback['student_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <div class="ms-auto text-warning">
                                        <?php for ($i = 0; $i < 5; $i++): ?>
                                            <i class="bi <?= $i < $feedback['rating'] ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p class="mb-0">"<?= htmlspecialchars($feedback['comment'], ENT_QUOTES, 'UTF-8') ?>"</p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>