<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$asset_path = '';
$page_title = 'Staff Login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        redirect('staff-login.php');
    }

    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare('SELECT * FROM users WHERE username = ? AND is_active = 1');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        set_flash('error', 'Invalid username or password.');
        redirect('staff-login.php');
    }

    $user = $result->fetch_assoc();

    if (!password_verify($password, $user['password'])) {
        set_flash('error', 'Invalid username or password.');
        redirect('staff-login.php');
    }

    $_SESSION['staff_id'] = $user['id'];
    $_SESSION['staff_role'] = $user['role'];
    $_SESSION['staff_name'] = $user['full_name'];
    $_SESSION['user_type'] = 'staff';

    if ($user['role'] === 'admin') {
        redirect('admin/dashboard.php');
    } else {
        redirect('principal/dashboard.php');
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="form-section">
    <h1>Staff Login</h1>
    <p>For staff login only.</p>
    <form method="POST" class="app-form">
        <?php echo csrf_field(); ?>
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <button type="submit" class="btn btn-primary">Log In</button>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
