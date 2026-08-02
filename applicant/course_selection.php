<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_applicant();

$asset_path = '../';
$page_title = 'Course Selection';

$applicant = current_applicant($conn);
if (!$applicant) {
    redirect('../logout.php');
}

if ($applicant['profile_status'] !== 'submitted') {
    set_flash('error', 'Please complete your application details first.');
    redirect('application_form.php');
}

if ($applicant['payment_status'] !== 'paid') {
    set_flash('error', 'Course selection is only available after your Tsh 5,000 payment has been confirmed.');
    redirect('dashboard.php');
}

if ($applicant['course_id']) {
    set_flash('success', 'You have already selected a course.');
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        redirect('course_selection.php');
    }

    $course_id = (int) ($_POST['course_id'] ?? 0);

    $check = $conn->prepare('SELECT id FROM courses WHERE id = ? AND is_active = 1');
    $check->bind_param('i', $course_id);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        set_flash('error', 'Please select a valid course.');
        redirect('course_selection.php');
    }

    $stmt = $conn->prepare('UPDATE applicants SET course_id = ?, course_selected_at = NOW() WHERE id = ?');
    $stmt->bind_param('ii', $course_id, $applicant['id']);
    $stmt->execute();

    set_flash('success', 'Your application is complete! The admissions office will review it and message you with the outcome.');
    redirect('dashboard.php');
}

$courses = $conn->query('SELECT * FROM courses WHERE is_active = 1 ORDER BY name');

require __DIR__ . '/../includes/header.php';
?>

<section class="form-section">
    <h1>Choose Your Course</h1>
    <p>Step 3 of 3: Your payment has been confirmed. Select the course you wish to apply for. The admissions office will review your full application and message you with the outcome.</p>

    <form method="POST" class="app-form">
        <?php echo csrf_field(); ?>
        <?php while ($course = $courses->fetch_assoc()): ?>
            <label class="radio-option">
                <input type="radio" name="course_id" value="<?php echo $course['id']; ?>" required>
                <strong><?php echo sanitize($course['name']); ?></strong> &mdash; <?php echo sanitize($course['description']); ?>
            </label>
        <?php endwhile; ?>
        <button type="submit" class="btn btn-primary">Confirm Course Selection</button>
    </form>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
