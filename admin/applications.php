<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$asset_path = '../';
$page_title = 'Review Applications';

$filter = $_GET['status'] ?? 'pending';
$allowed_filters = ['pending', 'approved', 'rejected', 'all'];
if (!in_array($filter, $allowed_filters, true)) {
    $filter = 'pending';
}

$sql = "SELECT a.*, w.name AS window_name FROM applicants a JOIN admission_windows w ON a.window_id = w.id WHERE a.profile_status = 'submitted'";
if ($filter !== 'all') {
    $sql .= " AND a.review_status = '" . $conn->real_escape_string($filter) . "'";
}
$sql .= " ORDER BY a.created_at DESC";
$applications = $conn->query($sql);

require __DIR__ . '/../includes/header.php';
?>

<section class="admin-section">
    <h1>Review Applications</h1>

    <div class="filter-tabs">
        <a href="?status=pending" class="<?php echo $filter === 'pending' ? 'active' : ''; ?>">Pending</a>
        <a href="?status=approved" class="<?php echo $filter === 'approved' ? 'active' : ''; ?>">Approved</a>
        <a href="?status=rejected" class="<?php echo $filter === 'rejected' ? 'active' : ''; ?>">Rejected</a>
        <a href="?status=all" class="<?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
    </div>

    <table class="data-table">
        <thead><tr><th>Index No.</th><th>Name</th><th>Window</th><th>Submitted</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php while ($app = $applications->fetch_assoc()): ?>
            <tr>
                <td><?php echo sanitize($app['form_four_index_number']); ?></td>
                <td><?php echo sanitize(applicant_full_name($app)); ?></td>
                <td><?php echo sanitize($app['window_name']); ?></td>
                <td><?php echo date('d M Y', strtotime($app['created_at'])); ?></td>
                <td><?php echo status_badge($app['review_status']); ?></td>
                <td><a class="btn btn-small btn-secondary" href="application_view.php?id=<?php echo $app['id']; ?>">View</a></td>
            </tr>
        <?php endwhile; ?>
        <?php if ($applications->num_rows === 0): ?>
            <tr><td colspan="6">No applications found for this filter.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
