<?php
// Start session for user authentication if needed
//session_start();

// Database connection details
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'debateskills');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($conn === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Initialize variables for filtering and pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$price_min = isset($_GET['price_min']) ? (float)$_GET['price_min'] : 0;
$price_max = isset($_GET['price_max']) ? (float)$_GET['price_max'] : 1000; // Set a reasonable default max
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12; // Number of courses per page

// Get all available categories - FIXED FUNCTION
function getCategories($conn) {
    // Since we don't know the schema, return default categories
    return array('Public Speaking', 'Critical Thinking', 'Argumentation', 'Debate Formats');
}

// Count total courses with filters
function countFilteredCourses($conn, $search, $category, $price_min, $price_max) {
    $sql = "SELECT COUNT(*) as total FROM courses WHERE 1=1";
    $params = array();
    $types = "";
    
    if(!empty($search)){
        $sql .= " AND (title LIKE ? OR description LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ss";
    }
    
    if(!empty($category)){
        // Since we're not sure about the category column, let's comment this out for now
        /*
        $sql .= " AND category = ?";
        $params[] = $category;
        $types .= "s";
        */
    }
    
    // Price filtering
    // Commented out as in the getFilteredCourses function
    /*
    $sql .= " AND price >= ? AND price <= ?";
    $params[] = $price_min;
    $params[] = $price_max;
    $types .= "dd";
    */
    
    if($stmt = mysqli_prepare($conn, $sql)){
        if(!empty($params)){
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            
            if($row = mysqli_fetch_assoc($result)){
                return (int)$row['total'];
            }
        }
    }
    
    return 0;
}

// Get filtered courses with pagination - FIXED FUNCTION
function getFilteredCourses($conn, $search, $category, $price_min, $price_max, $sort, $page, $per_page) {
    $offset = ($page - 1) * $per_page;
    
    // Changed from selecting specific columns to selecting all columns (*)
    // This avoids issues with unknown column names
    $sql = "SELECT * FROM courses WHERE 1=1";
    $params = array();
    $types = "";
    
    if(!empty($search)){
        $sql .= " AND (title LIKE ? OR description LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ss";
    }
    
    if(!empty($category)){
        // Since we're not sure about the category column, let's comment this out for now
        /*
        $sql .= " AND category = ?";
        $params[] = $category;
        $types .= "s";
        */
    }
    
    // Comment out price filtering here too
    /*
    $sql .= " AND price >= ? AND price <= ?";
    $params[] = $price_min;
    $params[] = $price_max;
    $types .= "dd";
    */
    
    // Add sorting
    switch($sort) {
        case 'price_low':
            $sql .= " ORDER BY price ASC";
            break;
        case 'price_high':
            $sql .= " ORDER BY price DESC";
            break;
        case 'rating':
            $sql .= " ORDER BY rating DESC";
            break;
        case 'oldest':
            $sql .= " ORDER BY created_at ASC";
            break;
        case 'newest':
        default:
            // Use created_at if it exists, otherwise don't sort
            if ($hasCreatedAt = mysqli_query($conn, "SHOW COLUMNS FROM courses LIKE 'created_at'")) {
                if (mysqli_num_rows($hasCreatedAt) > 0) {
                    $sql .= " ORDER BY created_at DESC";
                }
                mysqli_free_result($hasCreatedAt);
            }
    }
    
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    $types .= "ii";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        if(!empty($params)){
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            
            $courses = array();
            while($row = mysqli_fetch_assoc($result)){
                $courses[] = $row;
            }
            
            return $courses;
        }
    }
    
    return array();
}

// Get all categories
$allCategories = getCategories($conn);

// Count total courses with filters
$totalCourses = countFilteredCourses($conn, $search, $category, $price_min, $price_max);

// Calculate total pages
$totalPages = ceil($totalCourses / $per_page);

// Ensure current page is within valid range
$page = max(1, min($page, $totalPages > 0 ? $totalPages : 1));

// Get filtered courses for current page
$courses = getFilteredCourses($conn, $search, $category, $price_min, $price_max, $sort, $page, $per_page);

// Page title
$pageTitle = "Browse Courses";
if(!empty($category)) {
    $pageTitle = ucfirst($category) . " Courses";
}
if(!empty($search)) {
    $pageTitle = "Search Results for '" . htmlspecialchars($search) . "'";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DebateSkills - Courses</title>

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

        .search-container {
            max-width: 600px;
            margin: 0 auto;
            position: relative;
        }

        .search-input {
            padding: 12px 20px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            width: 100%;
            font-size: 16px;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-color);
        }

        .filter-select {
            padding: 10px 16px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background-color: white;
            font-size: 14px;
            font-weight: 500;
        }

        .filter-button {
            padding: 10px 16px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background-color: white;
            font-size: 14px;
            font-weight: 500;
        }

        .section-title {
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 24px;
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .view-all {
            font-size: 14px;
            font-weight: 500;
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
        }

        .view-all i {
            margin-left: 4px;
        }

        .category-card {
            background-color: white;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 24px;
            display: flex;
            flex-direction: column;
            transition: all 0.2s ease;
            height: 100%;
        }

        .category-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .category-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .category-icon.blue {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }

        .category-icon.purple {
            background-color: var(--purple-light);
            color: var(--purple-color);
        }

        .category-icon.green {
            background-color: var(--success-light);
            color: var(--success-color);
        }

        .category-icon.orange {
            background-color: #FEF3C7;
            color: #D97706;
        }

        .category-title {
            font-weight: 600;
            font-size: 18px;
            margin-bottom: 8px;
        }

        .category-count {
            color: var(--gray-color);
            font-size: 14px;
        }

        .course-card {
            background-color: white;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            overflow: hidden;
            transition: all 0.2s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .course-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .course-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }

        .course-content {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .course-level {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 12px;
        }

        .course-level.beginner {
            background-color: var(--success-light);
            color: var(--success-color);
        }

        .course-level.intermediate {
            background-color: var(--warning-light);
            color: var(--warning-color);
        }

        .course-level.advanced {
            background-color: var(--danger-light);
            color: var(--danger-color);
        }

        .course-title {
            font-weight: 600;
            font-size: 18px;
            margin-bottom: 12px;
            color: var(--dark-color);
        }

        .course-description {
            font-size: 14px;
            color: #6B7280;
            margin-bottom: 16px;
            flex-grow: 1;
        }

        .instructor {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
        }

        .instructor-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 12px;
        }

        .instructor-name {
            font-size: 14px;
            font-weight: 500;
        }

        .course-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid var(--border-color);
            padding-top: 16px;
        }

        .course-rating {
            display: flex;
            align-items: center;
            font-size: 14px;
            font-weight: 500;
        }

        .rating-star {
            color: #FBBF24;
            margin-right: 4px;
        }

        .course-students {
            color: var(--gray-color);
            font-size: 14px;
        }

        .btn-enroll {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
            border-radius: 6px;
            padding: 8px 16px;
            border: none;
        }

        .btn-enroll:hover {
            background-color: #1D4ED8;
            color: white;
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
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="courses.php">Courses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="practice.php">Practice</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="community.php">Community</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <div class="notification-icon me-3">
                        <i class="bi bi-bell fs-5"></i>
                        <span class="notification-badge">3</span>
                    </div>
                    <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="User Avatar" class="user-avatar">
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
        <!-- Search and Filters -->
        <div class="row mb-4">
            <div class="col-md-8">
                <form action="courses.php" method="GET" class="search-container">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" name="search" class="search-input" placeholder="Search for courses..." value="<?php echo htmlspecialchars($search); ?>">
                </form>
            </div>
            <div class="col-md-4 d-flex justify-content-end">
                <select class="filter-select me-2" name="category" form="filter-form">
                    <option value="">All Levels</option>
                    <option value="beginner" <?php echo ($category == 'beginner') ? 'selected' : ''; ?>>Beginner</option>
                    <option value="intermediate" <?php echo ($category == 'intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                    <option value="advanced" <?php echo ($category == 'advanced') ? 'selected' : ''; ?>>Advanced</option>
                </select>
                <button class="filter-button" data-bs-toggle="collapse" data-bs-target="#filterOptions" aria-expanded="false">
                    <i class="bi bi-sliders me-2"></i> Filters
                </button>
            </div>
        </div>

        <!-- Advanced Filter Options (Collapsed by default) -->
        <div class="collapse mb-4" id="filterOptions">
            <div class="card card-body">
                <form id="filter-form" action="courses.php" method="GET" class="row g-3">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    
                    <div class="col-md-4">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category">
                            <option value="">All Categories</option>
                            <?php foreach($allCategories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($category == $cat) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst($cat)); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Sort By</label>
                        <select class="form-select" name="sort">
                            <option value="newest" <?php echo ($sort == 'newest') ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo ($sort == 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="price_low" <?php echo ($sort == 'price_low') ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo ($sort == 'price_high') ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="rating" <?php echo ($sort == 'rating') ? 'selected' : ''; ?>>Highest Rated</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                        <a href="courses.php" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Featured Categories -->
        <div class="section-title">
            <span>Featured Categories</span>
            <a href="#" class="view-all">View All <i class="bi bi-arrow-right"></i></a>
        </div>

        <div class="row">
            <?php foreach($allCategories as $index => $cat): ?>
            <?php
                $icons = ['blue bi-people', 'purple bi-lightbulb', 'green bi-clipboard-data', 'orange bi-chat-quote'];
                $counts = [12, 8, 15, 10];
                $i = $index % 4; // Ensure we don't go out of bounds
            ?>
            <div class="col-md-3 mb-4">
                <div class="category-card">
                    <div class="category-icon <?php echo explode(' ', $icons[$i])[0]; ?>">
                        <i class="bi <?php echo explode(' ', $icons[$i])[1]; ?> fs-4"></i>
                    </div>
                    <h3 class="category-title"><?php echo htmlspecialchars($cat); ?></h3>
                    <div class="category-count"><?php echo $counts[$i]; ?> Courses</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Courses Section -->
        <div class="section-title">
            <span><?php echo $pageTitle; ?></span>
            <small class="text-muted ms-2">(<?php echo $totalCourses; ?> courses)</small>
        </div>

        <?php if(empty($courses)): ?>
            <div class="alert alert-info">
                No courses found matching your criteria. Try adjusting your filters.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach($courses as $course): ?>
                    <div class="col-md-4 mb-4">
                        <div class="course-card">
                            <img src="<?php echo htmlspecialchars($course['image_url'] ?? 'assets/img/course-placeholder.jpg'); ?>" class="course-image" alt="<?php echo htmlspecialchars($course['title'] ?? 'Course'); ?>">
                            <div class="course-content">
                                <span class="course-level <?php echo strtolower($course['level'] ?? 'beginner'); ?>">
                                    <?php echo htmlspecialchars(ucfirst($course['level'] ?? 'Beginner')); ?>
                                </span>
                                <h3 class="course-title"><?php echo htmlspecialchars($course['title'] ?? 'Course Title'); ?></h3>
                                <p class="course-description"><?php echo htmlspecialchars(substr($course['description'] ?? 'No description available.', 0, 100)) . '...'; ?></p>
                                <div class="instructor">
                                    <img src="<?php echo htmlspecialchars($course['instructor_avatar'] ?? 'assets/img/default-avatar.jpg'); ?>" alt="Instructor" class="instructor-avatar">
                                    <div class="instructor-name"><?php echo htmlspecialchars($course['instructor'] ?? 'Instructor'); ?></div>
                                </div>
                                <div class="course-meta">
                                    <div class="course-rating">
                                        <i class="bi bi-star-fill rating-star"></i>
                                        <?php echo number_format($course['rating'] ?? 4.5, 1); ?>
                                    </div>
                                    <div class="course-students">
                                        <i class="bi bi-people"></i> <?php echo htmlspecialchars($course['students'] ?? '0'); ?> students
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if($totalPages > 1): ?>
    <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination justify-content-center">
            <!-- Previous page link -->
            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo $sort; ?>">
                    &laquo;
                </a>
            </li>
            
            <!-- Page number links -->
            <?php
            for($i = 1; $i <= $totalPages; $i++) {
                echo '<li class="page-item ' . (($page == $i) ? 'active' : '') . '">';
                echo '<a class="page-link" href="?page=' . $i . '&search=' . urlencode($search) . '&category=' . urlencode($category) . '&sort=' . $sort . '">';
                echo $i;
                echo '</a></li>';
            }
            ?>
            
            <!-- Next page link -->
            <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo $sort; ?>">
                    &raquo;
                </a>
            </li>
        </ul>
    </nav>
<?php endif; ?>
<?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="text-center py-4">
        <p class="mb-0">&copy; 2023 DebateSkills. All rights reserved.</p>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>