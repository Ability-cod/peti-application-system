<?php
/**
 * Run this ONCE in your browser (e.g.
 * http://localhost/peti-system/install/seed_test_applicant.php)
 * to create a ready-to-test applicant whose Tsh 5,000 payment is
 * already marked as PAID. This lets you test the rest of the flow
 * (application form -> admin review -> approval -> course selection)
 * without having to generate and manually confirm a control number
 * every time.
 *
 * This test applicant is created directly in the database and does
 * NOT go through NECTA index number verification (that check only
 * runs on the public register.php form).
 *
 * TEST LOGIN CREATED:
 *   Index Number: TEST0001
 *   Password:     Test@1234
 *
 * Delete or rename this file after you're done testing, especially
 * before deploying to a real server.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$test_index_number = 'TEST0001';
$test_password = 'Test@1234';

// Make sure there's an admission window to attach this applicant to.
// Prefer the currently open one; otherwise use the most recently created.
$window_result = $conn->query("SELECT * FROM admission_windows WHERE status = 'open' ORDER BY id DESC LIMIT 1");
if ($window_result->num_rows === 0) {
    $window_result = $conn->query("SELECT * FROM admission_windows ORDER BY id DESC LIMIT 1");
}

if ($window_result->num_rows === 0) {
    die('No admission window exists yet. Import database/peti_schema.sql first (it seeds one), or create a window in the admin panel, then re-run this script.');
}

$window = $window_result->fetch_assoc();

$check = $conn->prepare('SELECT id FROM applicants WHERE form_four_index_number = ?');
$check->bind_param('s', $test_index_number);
$check->execute();
$already_exists = $check->get_result()->num_rows > 0;
$check->close();

if (!$already_exists) {
    $hash = password_hash($test_password, PASSWORD_DEFAULT);
    $first_name = 'Test';
    $middle_name = '';
    $last_name = 'Applicant';
    $gender = 'male';
    $year_completed_form_four = date('Y') - 1;

    $stmt = $conn->prepare('INSERT INTO applicants
        (form_four_index_number, password, window_id, first_name, middle_name, last_name, gender, year_completed_form_four, payment_status, necta_verified)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, "paid", 0)');
    $stmt->bind_param('ssisssss', $test_index_number, $hash, $window['id'], $first_name, $middle_name, $last_name, $gender, $year_completed_form_four);
    $stmt->execute();
    $applicant_id = $stmt->insert_id;
    $stmt->close();

    // Create a matching payment record already marked as paid, so the
    // Admin > Payments screen stays consistent with reality.
    $control_number = generate_control_number($conn);
    $stmt = $conn->prepare('INSERT INTO payments (applicant_id, control_number, amount, status, channel, confirmed_at) VALUES (?, ?, 5000.00, "paid", "Test Seed", NOW())');
    $stmt->bind_param('is', $applicant_id, $control_number);
    $stmt->execute();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>PETI - Seed Test Applicant</title></head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 40px auto;">
<h2>PETI Test Applicant Setup</h2>
<?php if ($already_exists): ?>
    <p style="color:#856404;">A test applicant with index number <?php echo htmlspecialchars($test_index_number); ?> already exists &mdash; nothing changed.</p>
<?php else: ?>
    <p style="color:green;">Test applicant created with payment already marked PAID, under admission window "<?php echo htmlspecialchars($window['name']); ?>".</p>
<?php endif; ?>
<p><strong>Test login:</strong></p>
<ul>
    <li>Index Number: <code><?php echo htmlspecialchars($test_index_number); ?></code></li>
    <li>Password: <code><?php echo htmlspecialchars($test_password); ?></code></li>
</ul>
<p>Log in with these credentials and you can go straight to filling the full application form, since payment is already confirmed.</p>
<p style="color:red;"><strong>Remember:</strong> delete or rename this file when you're done testing.</p>
<p><a href="../login.php">Go to Applicant Login</a></p>
</body>
</html>
