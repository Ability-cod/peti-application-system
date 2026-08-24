<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$asset_path = '../';
$page_title = 'Admin Dashboard';

$total_applicants = $conn->query('SELECT COUNT(*) c FROM applicants')->fetch_assoc()['c'];
$pending_payments = $conn->query("SELECT COUNT(*) c FROM payments WHERE status = 'pending'")->fetch_assoc()['c'];
$awaiting_review = $conn->query("SELECT COUNT(*) c FROM applicants WHERE profile_status = 'submitted' AND review_status = 'pending'")->fetch_assoc()['c'];
$approved = $conn->query("SELECT COUNT(*) c FROM applicants WHERE review_status = 'approved'")->fetch_assoc()['c'];
$rejected = $conn->query("SELECT COUNT(*) c FROM applicants WHERE review_status = 'rejected'")->fetch_assoc()['c'];
$window = get_open_window($conn);

require __DIR__ . '/../includes/header.php';
?>

<section class="dashboard">
    <h1>Admin Dashboard</h1>
    <p>Welcome, <?php echo sanitize($_SESSION['staff_name']); ?>.</p>

    <?php if ($window): ?>
        <p class="window-open">Current admission window OPEN: <strong><?php echo sanitize($window['name']); ?></strong></p>
    <?php else: ?>
        <p class="window-closed">No admission window is currently open.</p>
    <?php endif; ?>

    <div class="stat-grid">
        <div class="stat-card"><span class="stat-number"><?php echo $total_applicants; ?></span><span>Total Applicants</span></div>
        <div class="stat-card"><span class="stat-number"><?php echo $pending_payments; ?></span><span>Pending Payments</span></div>
        <div class="stat-card"><span class="stat-number"><?php echo $awaiting_review; ?></span><span>Awaiting Review</span></div>
        <div class="stat-card"><span class="stat-number"><?php echo $approved; ?></span><span>Approved</span></div>
        <div class="stat-card"><span class="stat-number"><?php echo $rejected; ?></span><span>Rejected</span></div>
    </div>

    <nav class="admin-nav">
        <a class="btn btn-secondary" href="windows.php">Manage Admission Windows</a>
        <a class="btn btn-secondary" href="courses.php">Manage Courses</a>
        <a class="btn btn-secondary" href="payments.php">Confirm Payments</a>
        <a class="btn btn-secondary" href="applications.php">Review Applications</a>
        <a class="btn btn-secondary" href="announcements.php">Manage Announcements</a>
    </nav>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>