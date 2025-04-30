<?php
/**
 * Support page for DebateSkills
 * This file displays the user's support tickets and allows creation of new tickets
 */

// Include the core functionality
// require_once 'includes/ticket_system.php';

// Initialize objects
session_start();

if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}

// Initialize variables to prevent undefined variable errors
$ticketsResult = [
    'success' => false,
    'tickets' => [],
    'message' => 'Ticket system not fully implemented yet.'
];

$statsResult = [
    'success' => false,
    'stats' => [
        'total' => 0,
        'open' => 0,
        'in_progress' => 0,
        'resolved' => 0
    ]
];

// Get current user
// If you have a user class, uncomment this:
// $user = new User();
// $currentUser = $user->getCurrentUser();

// For display purposes until the full system is implemented, add placeholder data
$ticketsResult = [
    'success' => true,
    'tickets' => [
        [
            'id' => 1,
            'title' => 'Unable to access practice session video recordings',
            'status' => 'open',
            'description' => 'I completed a practice session yesterday but can\'t find where to access the recording. The system says it should be available in my dashboard.',
            'created_at' => '2025-04-26 14:30:00',
            'category' => 'Technical Issue'
        ],
        [
            'id' => 2,
            'title' => 'Payment method update failed',
            'status' => 'in_progress',
            'description' => 'I tried to update my credit card information but received an error message. I want to make sure my subscription isn\'t interrupted.',
            'created_at' => '2025-04-24 10:15:00',
            'category' => 'Billing'
        ],
        [
            'id' => 3,
            'title' => 'Request for course content on parliamentary debate format',
            'status' => 'in_progress',
            'description' => 'I\'m interested in learning more about parliamentary debate format. Do you have plans to add more content on this topic?',
            'created_at' => '2025-04-22 09:45:00',
            'category' => 'Feature Request'
        ],
        [
            'id' => 4,
            'title' => 'Timer not working correctly during practice sessions',
            'status' => 'resolved',
            'description' => 'The practice timer sometimes freezes during the countdown. I\'ve tried using different browsers but the issue persists.',
            'created_at' => '2025-04-19 16:20:00',
            'category' => 'Technical Issue'
        ]
    ]
];

$statsResult = [
    'success' => true,
    'stats' => [
        'total' => 4,
        'open' => 1,
        'in_progress' => 2,
        'resolved' => 1
    ]
];

// Get all tickets for the current user
$status = isset($_GET['status']) ? $_GET['status'] : null;

// If you have ticket filtering by status, uncomment and modify this:
// if ($status && $ticketsResult['success']) {
//     $filteredTickets = [];
//     foreach ($ticketsResult['tickets'] as $ticket) {
//         if ($ticket['status'] === $status) {
//             $filteredTickets[] = $ticket;
//         }
//     }
//     $ticketsResult['tickets'] = $filteredTickets;
// }

// Get ticket statistics
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DebateSkills - Support Tickets</title>

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

        .page-title {
            font-weight: 700;
            font-size: 24px;
            margin-bottom: 24px;
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

        .ticket-card {
            background-color: white;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 20px;
            margin-bottom: 16px;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .ticket-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .ticket-title {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 0;
        }

        .ticket-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-open {
            background-color: var(--warning-light);
            color: var(--warning-color);
        }

        .status-in_progress {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }

        .status-resolved {
            background-color: var(--success-light);
            color: var(--success-color);
        }

        .ticket-description {
            margin-bottom: 16px;
            color: #6B7280;
            font-size: 14px;
        }

        .ticket-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: var(--gray-color);
        }

        .ticket-meta .ticket-date {
            display: flex;
            align-items: center;
        }

        .ticket-meta .ticket-date i {
            margin-right: 4px;
        }

        .ticket-meta .ticket-category {
            display: flex;
            align-items: center;
        }

        .ticket-meta .ticket-category i {
            margin-right: 4px;
        }

        .btn-create-ticket {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
            border-radius: 8px;
            padding: 10px 18px;
            border: none;
            display: flex;
            align-items: center;
        }

        .btn-create-ticket:hover {
            background-color: #1D4ED8;
            color: white;
        }

        .btn-create-ticket i {
            margin-right: 8px;
        }

        .ticket-filter {
            display: flex;
            align-items: center;
            margin-bottom: 24px;
        }

        .filter-label {
            margin-right: 12px;
            font-weight: 500;
        }

        .filter-option {
            background-color: white;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 6px 16px;
            margin-right: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            color: var(--dark-color);
        }

        .filter-option:hover, .filter-option.active {
            background-color: var(--primary-light);
            color: var(--primary-color);
            border-color: var(--primary-color);
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

        .support-card {
            background-color: white;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 24px;
            margin-bottom: 24px;
        }

        .support-card-title {
            font-weight: 600;
            font-size: 18px;
            margin-bottom: 16px;
        }

        .faq-item {
            border-bottom: 1px solid var(--border-color);
            padding: 16px 0;
        }

        .faq-item:last-child {
            border-bottom: none;
        }

        .faq-question {
            font-weight: 500;
            font-size: 16px;
            margin-bottom: 8px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .faq-answer {
            color: #6B7280;
            font-size: 14px;
            display: none;
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
                        <a class="nav-link" href="dashboard.php">
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
                        <a class="nav-link active" href="community.php">
                            <i class="bi bi-headset me-1"></i> Support
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <div class="notification-icon me-3">
                        <i class="bi bi-bell fs-5"></i>
                        <span class="notification-badge">3</span>
                    </div>
                    <img src="assets/img/default-avatar.jpg" alt="User Avatar" class="user-avatar">
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Left Column: Tickets List -->
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="page-title mb-0">Support Tickets</h1>
                    <button class="btn-create-ticket" data-bs-toggle="modal" data-bs-target="#createTicketModal">
                        <i class="bi bi-plus-circle"></i> Create Ticket
                    </button>
                </div>

                <div class="row mb-4">
                    <div class="col-md-8">
                        <input type="text" class="search-input" id="searchTicket" placeholder="Search tickets...">
                    </div>
                </div>

                <div class="ticket-filter mb-4">
                    <span class="filter-label">Status:</span>
                    <a href="community.php" class="filter-option <?php echo !isset($_GET['status']) ? 'active' : ''; ?>">All</a>
                    <a href="community.php?status=open" class="filter-option <?php echo isset($_GET['status']) && $_GET['status'] === 'open' ? 'active' : ''; ?>">Open</a>
                    <a href="community.php?status=in_progress" class="filter-option <?php echo isset($_GET['status']) && $_GET['status'] === 'in_progress' ? 'active' : ''; ?>">In Progress</a>
                    <a href="community.php?status=resolved" class="filter-option <?php echo isset($_GET['status']) && $_GET['status'] === 'resolved' ? 'active' : ''; ?>">Resolved</a>
                </div>

                <!-- Tickets List -->
                <?php if (isset($ticketsResult['success']) && $ticketsResult['success'] && count($ticketsResult['tickets']) > 0): ?>
                    <?php foreach($ticketsResult['tickets'] as $ticketItem): ?>
                        <a href="view_ticket.php?id=<?php echo $ticketItem['id']; ?>" class="text-decoration-none">
                            <div class="ticket-card">
                                <div class="ticket-header">
                                    <h3 class="ticket-title"><?php echo htmlspecialchars($ticketItem['title']); ?></h3>
                                    <span class="ticket-status status-<?php echo htmlspecialchars($ticketItem['status']); ?>">
                                        <?php 
                                        $status = $ticketItem['status'];
                                        echo ucfirst(str_replace('_', ' ', $status)); 
                                        ?>
                                    </span>
                                </div>
                                <p class="ticket-description"><?php echo htmlspecialchars(substr($ticketItem['description'], 0, 150)) . (strlen($ticketItem['description']) > 150 ? '...' : ''); ?></p>
                                <div class="ticket-meta">
                                    <div class="ticket-date">
                                        <i class="bi bi-calendar"></i> Created on <?php echo date('M d, Y', strtotime($ticketItem['created_at'])); ?>
                                    </div>
                                    <div class="ticket-category">
                                        <i class="bi bi-tag"></i> <?php echo htmlspecialchars($ticketItem['category']); ?>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <?php if (isset($ticketsResult['success']) && !$ticketsResult['success']): ?>
                            <?php echo htmlspecialchars($ticketsResult['message']); ?>
                        <?php else: ?>
                            You don't have any tickets yet. Click "Create Ticket" to submit a new support request.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Column: Help Resources -->
            <div class="col-lg-4">
                <?php if (isset($statsResult['success']) && $statsResult['success']): ?>
                <div class="support-card">
                    <h3 class="support-card-title">Your Tickets</h3>
                    <div class="d-flex justify-content-between mb-3">
                        <div class="text-center">
                            <div class="fs-4 fw-bold"><?php echo $statsResult['stats']['total']; ?></div>
                            <div class="text-muted small">Total</div>
                        </div>
                        <div class="text-center">
                            <div class="fs-4 fw-bold text-warning"><?php echo $statsResult['stats']['open']; ?></div>
                            <div class="text-muted small">Open</div>
                        </div>
                        <div class="text-center">
                            <div class="fs-4 fw-bold text-primary"><?php echo $statsResult['stats']['in_progress']; ?></div>
                            <div class="text-muted small">In Progress</div>
                        </div>
                        <div class="text-center">
                            <div class="fs-4 fw-bold text-success"><?php echo $statsResult['stats']['resolved']; ?></div>
                            <div class="text-muted small">Resolved</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="support-card">
                    <h3 class="support-card-title">Frequently Asked Questions</h3>
                    <div class="faq-item">
                        <div class="faq-question">
                            <span>How do I reset my password?</span>
                            <i class="bi bi-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            Click on "Forgot Password" on the login page. Enter your email address, and we'll send you instructions on how to reset your password.
                        </div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question">
                            <span>How can I cancel my subscription?</span>
                            <i class="bi bi-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            Go to Account Settings > Billing and click on "Cancel Subscription". Note that you'll continue to have access until the end of your current billing period.
                        </div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question">
                            <span>Can I download course materials?</span>
                            <i class="bi bi-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            Yes, most course materials can be downloaded for offline use. Look for the download icon next to the course content.
                        </div>
                    </div>
                </div>

                <div class="support-card">
                    <h3 class="support-card-title">Contact Support</h3>
                    <p>Our support team is available Monday through Friday, 9 AM to 5 PM EST.</p>
                    <div class="mb-3">
                        <i class="bi bi-envelope me-2"></i> support@debateskills.com
                    </div>
                    <div>
                        <i class="bi bi-telephone me-2"></i> +1 (800) 123-4567
                    </div>
                </div>

                <div class="support-card">
                    <h3 class="support-card-title">Help Resources</h3>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="#" class="text-decoration-none text-primary">
                                <i class="bi bi-file-text me-2"></i> User Guide
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="#" class="text-decoration-none text-primary">
                                <i class="bi bi-play-circle me-2"></i> Video Tutorials
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-decoration-none text-primary">
                                <i class="bi bi-book me-2"></i> Knowledge Base
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Ticket Modal -->
    <div class="modal fade" id="createTicketModal" tabindex="-1" aria-labelledby="createTicketModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createTicketModalLabel">Create Support Ticket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="community.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="create_ticket" value="1">
                        <div class="mb-3">
                            <label for="ticketTitle" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="ticketTitle" name="ticket_title" placeholder="Brief description of your issue" required>
                        </div>
                        <div class="mb-3">
                            <label for="ticketCategory" class="form-label">Category</label>
                            <select class="form-select" id="ticketCategory" name="ticket_category" required>
                                <option value="Technical Issue">Technical Issue</option>
                                <option value="Billing">Billing</option>
                                <option value="Account">Account</option>
                                <option value="Course Content">Course Content</option>
                                <option value="Feature Request">Feature Request</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="ticketPriority" class="form-label">Priority</label>
                            <select class="form-select" id="ticketPriority" name="ticket_priority" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="ticketDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="ticketDescription" name="ticket_description" rows="5" placeholder="Please provide details about your issue" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="ticketAttachment" class="form-label">Attachments (optional)</label>
                            <input type="file" class="form-control" id="ticketAttachment" name="ticket_attachment">
                            <div class="form-text">Maximum file size: 5MB</div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-submit">Submit Ticket</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // FAQ accordion functionality
            const faqQuestions = document.querySelectorAll('.faq-question');
            faqQuestions.forEach(question => {
                question.addEventListener('click', function() {
                    const answer = this.nextElementSibling;
                    const icon = this.querySelector('i');
                    
                    if (answer.style.display === 'none' || !answer.style.display) {
                        answer.style.display = 'block';
                        icon.classList.replace('bi-chevron-down', 'bi-chevron-up');
                    } else {
                        answer.style.display = 'none';
                        icon.classList.replace('bi-chevron-up', 'bi-chevron-down');
                    }
                });
                
                // Hide answers initially
                question.nextElementSibling.style.display = 'none';
            });

            // Search functionality
            const searchInput = document.getElementById('searchTicket');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const searchText = this.value.toLowerCase();
                    const ticketCards = document.querySelectorAll('.ticket-card');
                    
                    ticketCards.forEach(card => {
                        const title = card.querySelector('.ticket-title').textContent.toLowerCase();
                        const description = card.querySelector('.ticket-description').textContent.toLowerCase();
                        const category = card.querySelector('.ticket-category').textContent.toLowerCase();
                        
                        if (title.includes(searchText) || description.includes(searchText) || category.includes(searchText)) {
                            card.parentElement.style.display = 'block';
                        } else {
                            card.parentElement.style.display = 'none';
                        }
                    });
                });
            }

            // Form submission
            const ticketForm = document.querySelector('form[action="community.php"]');
            if (ticketForm) {
                ticketForm.addEventListener('submit', function(e) {
                    // You can add client-side validation here if needed
                    
                    // For now, since the backend isn't fully implemented, let's simulate a success message
                    e.preventDefault();
                    alert('The ticket system is currently in development. Your request has been recorded.');
                    document.getElementById('createTicketModal').querySelector('.btn-close').click();
                });
            }
        });
    </script>
</body>
</html>