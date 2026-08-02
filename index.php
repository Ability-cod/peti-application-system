<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$asset_path = '';
$page_title = 'Home';

$window = get_open_window($conn);

$courses = $conn->query("SELECT * FROM courses WHERE is_active = 1 ORDER BY name");

require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <h1>Perfect Education and Training Institute (PETI)</h1>
    <p>Karagwe District, Kagera Region &mdash; Registered with NACTEVET</p>

    <?php if ($window): ?>
        <p class="window-open">Applications are currently <strong>OPEN</strong> for: <?php echo sanitize($window['name']); ?></p>
        <a class="btn btn-primary" href="register.php">Apply Now</a>
    <?php else: ?>
        <p class="window-closed">Applications are currently <strong>CLOSED</strong>. Please check back when a new admission window opens.</p>
    <?php endif; ?>

    <p><a href="login.php">Already applied? Log in here</a></p>
</section>

<section class="courses-section">
    <h2>Courses Offered</h2>
    <div class="course-grid">
        <?php while ($course = $courses->fetch_assoc()): ?>
            <div class="course-card">
                <h3><?php echo sanitize($course['name']); ?></h3>
                <p><?php echo sanitize($course['description']); ?></p>
            </div>
        <?php endwhile; ?>
    </div>
</section>

<section class="requirements-section">
    <h2>What You Need to Apply</h2>
    <ul>
        <li>Form Four Index Number (used as your login username) and year you completed Form Four</li>
        <li>An active email address and phone number</li>
        <li>Your residential address (region, district, ward, street) and postal address</li>
        <li>Your date and place of birth</li>
        <li>Full names and contact details of your father and mother</li>
        <li>Tsh 5,000 application fee, paid via a control number generated after you complete your application details</li>
    </ul>
</section>

<section class="requirements-section">
    <h2>How It Works</h2>
    <ol>
        <li>Register with your Form Four Index Number (verified against NECTA) and create a password</li>
        <li>Fill in your full application details: personal, residence, birth, and parents'/guardians' information</li>
        <li>Get your control number and pay the Tsh 5,000 application fee</li>
        <li>Once payment is confirmed, choose the course you wish to apply for</li>
        <li>The admissions office reviews applications and will message you with the outcome</li>
    </ol>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
