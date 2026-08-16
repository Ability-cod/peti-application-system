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

<section class="requirements-section">
    <h2>What You Need to Apply</h2>
    <p>Most of our courses are 2-year Advanced Certificate programmes. Course fees (Tsh 850,000 over 2 years, plus other charges) are separate from the application fee below, and only apply once you have been admitted &mdash; full details are sent in your Joining Instructions if you are selected.</p>
    <ul>
        <li>Your Form Four Index Number (used as your login username) in the format centre/candidate/year, e.g. S0001/0001/2020</li>
        <li>Your full names (first, middle, last) and gender, exactly as they appear on your certificate</li>
        <li>A scan or clear photo of your Form Four certificate (JPG, PNG, or PDF, max 5MB) for verification by our admissions team</li>
        <li>An active email address and phone number</li>
        <li>Your marital status, residential address (region, district, ward, street), and postal address</li>
        <li>Your date and place of birth</li>
        <li>Full name, address, and contact details of your guardian/mdhamini</li>
        <li>Tsh 10,000 application fee, paid via a control number generated after you complete your application details</li>
    </ul>
</section>

<section class="requirements-section">
    <h2>How It Works</h2>
    <ol>
        <li>Register with your Form Four Index Number, your names, and gender, and create a password</li>
        <li>Fill in your full application details and upload a scan/photo of your Form Four certificate for verification</li>
        <li>Get your control number and pay the Tsh 10,000 application fee to the college's mobile money number shown on your dashboard</li>
        <li>Once payment is confirmed, choose the course (Advanced Certificate, 2 years) you wish to apply for right away &mdash; no waiting required</li>
        <li>The admissions office reviews your certificate and details, and will message you with the outcome, including Joining Instructions covering course fees and what to bring if you are selected</li>
    </ol>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
