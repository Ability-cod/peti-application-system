<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$asset_path = '';
$page_title = 'Applicant Login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        redirect('login.php');
    }

    $index_number = strtoupper(sanitize($_POST['form_four_index_number'] ?? ''));
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare('SELECT * FROM applicants WHERE form_four_index_number = ?');
    $stmt->bind_param('s', $index_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        set_flash('error', 'No application found for that Form Four Index Number.');
        redirect('login.php');
    }

    $applicant = $result->fetch_assoc();

    if (!password_verify($password, $applicant['password'])) {
        set_flash('error', 'Incorrect password.');
        redirect('login.php');
    }

    $_SESSION['applicant_id'] = $applicant['id'];
    $_SESSION['user_type'] = 'applicant';

    redirect('applicant/dashboard.php');
}

require __DIR__ . '/includes/header.php';
?>

<section class="form-section">
    <h1>Applicant Login</h1>
    <form method="POST" class="app-form">
        <?php echo csrf_field(); ?>
        <label for="form_four_index_number">Form Four Index Number</label>
        <input type="text" id="form_four_index_number" name="form_four_index_number" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <button type="submit" class="btn btn-primary">Log In</button>
    </form>
    <p>Not applied yet? <a href="register.php">Apply now</a></p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
