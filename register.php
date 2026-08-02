<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$asset_path = '';
$page_title = 'Apply Now';

$window = get_open_window($conn);

if (!$window) {
    set_flash('error', 'Applications are currently closed. Please wait for the next admission window to open.');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        redirect('register.php');
    }

    $index_number_raw = $_POST['form_four_index_number'] ?? '';
    $first_name = sanitize($_POST['first_name'] ?? '');
    $middle_name = sanitize($_POST['middle_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $gender = sanitize($_POST['gender'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($index_number_raw === '' || $first_name === '' || $last_name === '' || $gender === '' || $password === '') {
        set_flash('error', 'Please fill in all required fields.');
        redirect('register.php');
    }

    // Validate the index number FORMAT only (centre/candidate/year).
    // We no longer live-check it against NECTA's site — that depended on
    // NECTA's servers being reachable and their page layout staying the
    // same, which proved unreliable. Genuine verification now happens
    // when the admin reviews the uploaded Form Four certificate.
    $format_check = validate_necta_index_format($index_number_raw);
    if (!$format_check['ok']) {
        set_flash('error', $format_check['message']);
        redirect('register.php');
    }
    $index_number = $format_check['normalized'];

    if (!in_array($gender, ['male', 'female'], true)) {
        set_flash('error', 'Please select a valid gender.');
        redirect('register.php');
    }

    if (strlen($password) < 6) {
        set_flash('error', 'Password must be at least 6 characters long.');
        redirect('register.php');
    }

    if ($password !== $confirm_password) {
        set_flash('error', 'Passwords do not match.');
        redirect('register.php');
    }

    $check = $conn->prepare('SELECT id FROM applicants WHERE form_four_index_number = ?');
    $check->bind_param('s', $index_number);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        set_flash('error', 'An application already exists for this Form Four Index Number. Please log in instead.');
        redirect('login.php');
    }
    $check->close();

    // Year is embedded in the index number itself (centre/candidate/year).
    $year_completed_form_four = explode('/', $index_number)[2];
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare('INSERT INTO applicants
        (form_four_index_number, password, window_id, first_name, middle_name, last_name, gender, year_completed_form_four)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ssisssss',
        $index_number, $hash, $window['id'],
        $first_name, $middle_name, $last_name, $gender,
        $year_completed_form_four
    );
    $stmt->execute();
    $applicant_id = $stmt->insert_id;
    $stmt->close();

    $_SESSION['applicant_id'] = $applicant_id;
    $_SESSION['user_type'] = 'applicant';

    set_flash('success', 'Registration successful! Please complete your application details and upload your Form Four certificate next.');
    redirect('applicant/application_form.php');
}

require __DIR__ . '/includes/header.php';
?>

<section class="form-section">
    <h1>Apply to PETI</h1>
    <p>Step 1 of 3: Register with your Form Four Index Number exactly as it appears on your certificate, and create a password. You'll be asked to upload a scan of your certificate next, for verification by our admissions team.</p>

    <form method="POST" class="app-form">
        <?php echo csrf_field(); ?>

        <label for="form_four_index_number">Form Four Index Number</label>
        <input type="text" id="form_four_index_number" name="form_four_index_number" required placeholder="e.g. S0001/0001/2020">

        <label for="first_name">First Name</label>
        <input type="text" id="first_name" name="first_name" required>

        <label for="middle_name">Middle Name</label>
        <input type="text" id="middle_name" name="middle_name">

        <label for="last_name">Last Name / Surname</label>
        <input type="text" id="last_name" name="last_name" required>

        <p class="muted">Enter your names exactly as they appear on your Form Four certificate.</p>

        <label for="gender">Gender</label>
        <select id="gender" name="gender" required>
            <option value="">-- Select --</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
        </select>

        <label for="password">Create Password</label>
        <input type="password" id="password" name="password" required minlength="6">

        <label for="confirm_password">Confirm Password</label>
        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">

        <button type="submit" class="btn btn-primary">Register &amp; Continue</button>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>