<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$asset_path = '../';
$page_title = 'Admission Windows';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        redirect('windows.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = sanitize($_POST['name'] ?? '');
        if ($name === '') {
            set_flash('error', 'Please provide a name for the admission window.');
            redirect('windows.php');
        }
        $stmt = $conn->prepare('INSERT INTO admission_windows (name, status, created_by) VALUES (?, "closed", ?)');
        $stmt->bind_param('si', $name, $_SESSION['staff_id']);
        $stmt->execute();
        set_flash('success', 'Admission window created (closed by default). Open it when ready.');
    } elseif ($action === 'open') {
        $id = (int) ($_POST['window_id'] ?? 0);
        // Close any currently open windows first (only one open window at a time)
        $conn->query("UPDATE admission_windows SET status='closed', closed_at=NOW() WHERE status='open'");
        $stmt = $conn->prepare("UPDATE admission_windows SET status='open', opened_at=NOW() WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        set_flash('success', 'Admission window opened.');
    } elseif ($action === 'close') {
        $id = (int) ($_POST['window_id'] ?? 0);
        $stmt = $conn->prepare("UPDATE admission_windows SET status='closed', closed_at=NOW() WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        set_flash('success', 'Admission window closed.');
    }

    redirect('windows.php');
}

$windows = $conn->query('SELECT w.*, (SELECT COUNT(*) FROM applicants a WHERE a.window_id = w.id) AS applicant_count FROM admission_windows w ORDER BY w.id DESC');

require __DIR__ . '/../includes/header.php';
?>

<section class="admin-section">
    <h1>Admission Windows</h1>
    <p>Only one window can be open at a time. Opening a new window automatically closes any other open window.</p>

    <form method="POST" class="inline-form">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="create">
        <input type="text" name="name" placeholder="e.g. 2026 Intake - Round 2" required>
        <button type="submit" class="btn btn-primary">Create Window</button>
    </form>

    <table class="data-table">
        <thead><tr><th>Name</th><th>Status</th><th>Applicants</th><th>Opened</th><th>Closed</th><th>Action</th></tr></thead>
        <tbody>
        <?php while ($w = $windows->fetch_assoc()): ?>
            <tr>
                <td><?php echo sanitize($w['name']); ?></td>
                <td><?php echo status_badge($w['status']); ?></td>
                <td><?php echo $w['applicant_count']; ?></td>
                <td><?php echo $w['opened_at'] ? date('d M Y H:i', strtotime($w['opened_at'])) : '-'; ?></td>
                <td><?php echo $w['closed_at'] ? date('d M Y H:i', strtotime($w['closed_at'])) : '-'; ?></td>
                <td>
                    <?php if ($w['status'] === 'closed'): ?>
                        <form method="POST" class="inline-form">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="open">
                            <input type="hidden" name="window_id" value="<?php echo $w['id']; ?>">
                            <button type="submit" class="btn btn-small btn-primary">Open</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" class="inline-form">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="close">
                            <input type="hidden" name="window_id" value="<?php echo $w['id']; ?>">
                            <button type="submit" class="btn btn-small btn-secondary">Close</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
