<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$asset_path = '../';
$page_title = 'Announcements';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        redirect('announcements.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = sanitize($_POST['title'] ?? '');
        $body = sanitize($_POST['body'] ?? '');

        if ($title === '' || $body === '') {
            set_flash('error', 'Title and body are required.');
            redirect('announcements.php');
        }

        $stmt = $conn->prepare('INSERT INTO announcements (title, body, created_by) VALUES (?, ?, ?)');
        $stmt->bind_param('ssi', $title, $body, $_SESSION['staff_id']);
        $stmt->execute();
        set_flash('success', 'Announcement published.');
    } elseif ($action === 'toggle') {
        $id = (int) ($_POST['announcement_id'] ?? 0);
        $conn->query('UPDATE announcements SET is_active = 1 - is_active WHERE id = ' . $id);
        set_flash('success', 'Announcement visibility updated.');
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['announcement_id'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM announcements WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        set_flash('success', 'Announcement deleted.');
    }

    redirect('announcements.php');
}

$announcements = $conn->query('SELECT * FROM announcements ORDER BY created_at DESC');

require __DIR__ . '/../includes/header.php';
?>

<section class="admin-section">
    <h1>Announcements</h1>
    <p>These appear on the public homepage for anyone visiting the site, even before they log in.</p>

    <form method="POST" class="app-form">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="create">
        <label>Title</label>
        <input type="text" name="title" required maxlength="200">
        <label>Announcement</label>
        <textarea name="body" rows="4" required></textarea>
        <button type="submit" class="btn btn-primary">Publish Announcement</button>
    </form>

    <table class="data-table">
        <thead><tr><th>Title</th><th>Announcement</th><th>Posted</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php while ($a = $announcements->fetch_assoc()): ?>
            <tr>
                <td><?php echo sanitize($a['title']); ?></td>
                <td><?php echo sanitize(mb_strimwidth($a['body'], 0, 80, '...')); ?></td>
                <td><?php echo date('d M Y', strtotime($a['created_at'])); ?></td>
                <td><?php echo $a['is_active'] ? status_badge('approved') : status_badge('rejected'); ?></td>
                <td>
                    <form method="POST" class="inline-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="announcement_id" value="<?php echo $a['id']; ?>">
                        <button type="submit" class="btn btn-small btn-secondary"><?php echo $a['is_active'] ? 'Hide' : 'Show'; ?></button>
                    </form>
                    <form method="POST" class="inline-form" onsubmit="return confirm('Delete this announcement permanently?');">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="announcement_id" value="<?php echo $a['id']; ?>">
                        <button type="submit" class="btn btn-small btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        <?php if ($announcements->num_rows === 0): ?>
            <tr><td colspan="5">No announcements yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>