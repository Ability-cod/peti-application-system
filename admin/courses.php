<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$asset_path = '../';
$page_title = 'Manage Courses';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        redirect('courses.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = sanitize($_POST['name'] ?? '');
        $code = strtoupper(sanitize($_POST['code'] ?? ''));
        $description = sanitize($_POST['description'] ?? '');

        if ($name === '' || $code === '') {
            set_flash('error', 'Course name and code are required.');
            redirect('courses.php');
        }

        $stmt = $conn->prepare('INSERT INTO courses (name, code, description) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $name, $code, $description);
        $stmt->execute();
        set_flash('success', 'Course added.');
    } elseif ($action === 'toggle') {
        $id = (int) ($_POST['course_id'] ?? 0);
        $conn->query("UPDATE courses SET is_active = 1 - is_active WHERE id = " . $id);
        set_flash('success', 'Course status updated.');
    }

    redirect('courses.php');
}

$courses = $conn->query('SELECT * FROM courses ORDER BY name');

require __DIR__ . '/../includes/header.php';
?>

<section class="admin-section">
    <h1>Manage Courses</h1>

    <form method="POST" class="app-form">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="create">
        <label>Course Name</label>
        <input type="text" name="name" required>
        <label>Course Code</label>
        <input type="text" name="code" required>
        <label>Description</label>
        <textarea name="description" rows="2"></textarea>
        <button type="submit" class="btn btn-primary">Add Course</button>
    </form>

    <table class="data-table">
        <thead><tr><th>Name</th><th>Code</th><th>Description</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php while ($c = $courses->fetch_assoc()): ?>
            <tr>
                <td><?php echo sanitize($c['name']); ?></td>
                <td><?php echo sanitize($c['code']); ?></td>
                <td><?php echo sanitize($c['description']); ?></td>
                <td><?php echo $c['is_active'] ? status_badge('approved') : status_badge('rejected'); ?></td>
                <td>
                    <form method="POST" class="inline-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="course_id" value="<?php echo $c['id']; ?>">
                        <button type="submit" class="btn btn-small btn-secondary"><?php echo $c['is_active'] ? 'Deactivate' : 'Activate'; ?></button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
