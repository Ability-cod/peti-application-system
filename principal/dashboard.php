<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_principal();

$asset_path = '../';
$page_title = 'Principal Dashboard';

$total_applicants = $conn->query('SELECT COUNT(*) c FROM applicants')->fetch_assoc()['c'];
$approved = $conn->query("SELECT COUNT(*) c FROM applicants WHERE review_status = 'approved'")->fetch_assoc()['c'];
$rejected = $conn->query("SELECT COUNT(*) c FROM applicants WHERE review_status = 'rejected'")->fetch_assoc()['c'];
$pending = $conn->query("SELECT COUNT(*) c FROM applicants WHERE profile_status = 'submitted' AND review_status = 'pending'")->fetch_assoc()['c'];
$pending_payments = $conn->query("SELECT COUNT(*) c FROM payments WHERE status = 'pending'")->fetch_assoc()['c'];
$window = get_open_window($conn);

$by_course = $conn->query("SELECT c.name, COUNT(a.id) AS cnt FROM courses c LEFT JOIN applicants a ON a.course_id = c.id GROUP BY c.id ORDER BY c.name");

require __DIR__ . '/../includes/header.php';
?>

<section class="dashboard">
    <h1>Principal Dashboard</h1>
    <p>Welcome, <?php echo sanitize($_SESSION['staff_name']); ?>.</p>

    <?php if ($window): ?>
        <p class="window-open">Current admission window OPEN: <strong><?php echo sanitize($window['name']); ?></strong></p>
    <?php else: ?>
        <p class="window-closed">No admission window is currently open.</p>
    <?php endif; ?>

    <div class="stat-grid">
        <div class="stat-card"><span class="stat-number"><?php echo $total_applicants; ?></span><span>Total Applicants</span></div>
        <div class="stat-card"><span class="stat-number"><?php echo $pending; ?></span><span>Awaiting Review</span></div>
        <div class="stat-card"><span class="stat-number"><?php echo $approved; ?></span><span>Approved</span></div>
        <div class="stat-card"><span class="stat-number"><?php echo $rejected; ?></span><span>Rejected</span></div>
        <div class="stat-card"><span class="stat-number"><?php echo $pending_payments; ?></span><span>Pending Payments</span></div>
    </div>

    <h2>Enrolled by Course</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Course</th>
                <th>Students</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $by_course->fetch_assoc()): ?>
                <tr>
                    <td><?php echo sanitize($row['name']); ?></td>
                    <td><?php echo $row['cnt']; ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Principal anaweza kufanya kila kitu kama Admin -->
    <nav class="admin-nav" style="margin-top: 2rem;">
        <a class="btn btn-secondary" href="../admin/windows.php">Manage Admission Windows</a>
        <a class="btn btn-secondary" href="../admin/courses.php">Manage Courses</a>
        <a class="btn btn-secondary" href="../admin/payments.php">Confirm Payments</a>
        <a class="btn btn-secondary" href="../admin/applications.php">Review Applications</a>
        <a class="btn btn-secondary" href="../admin/announcements.php">Manage Announcements</a>
    </nav>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>