<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$asset_path = '';
$page_title = 'Home';

$window = get_open_window($conn);
$courses = $conn->query("SELECT * FROM courses WHERE is_active = 1 ORDER BY name");
$announcements = get_active_announcements($conn, 6);
$hero_images = get_hero_images();
$course_count = $conn->query("SELECT COUNT(*) c FROM courses WHERE is_active = 1")->fetch_assoc()['c'];

require __DIR__ . '/includes/header.php';
?>

<section class="hero-wrap">
    <?php if (!empty($hero_images)): ?>
    <div class="hero-carousel">
        <?php foreach ($hero_images as $i => $img): ?>
            <div class="hero-slide<?php echo $i === 0 ? ' active' : ''; ?>" style="background-image: url('<?php echo sanitize($img); ?>');"></div>
        <?php endforeach; ?>
        <div class="hero-overlay"></div>
        <div class="hero-dots"></div>
    </div>
    <?php else: ?>
    <div class="hero-carousel hero-carousel--empty"></div>
    <?php endif; ?>

    <div class="hero-content">
        <p class="hero-eyebrow">Karagwe &middot; Kagera &middot; Tanzania</p>
        <h1>Skills that get you hired.</h1>
        <p class="hero-tagline">Perfect Education and Training Institute (PETI) trains students in hospitality, tourism, journalism, secretarial work, early childhood teaching, and more &middot; and helps place them in real jobs after.</p>

        <?php if ($window): ?>
            <p class="window-open">Applications OPEN: <?php echo sanitize($window['name']); ?></p>
            <a class="btn btn-primary btn-large" href="register.php">Apply Now</a>
        <?php else: ?>
            <p class="window-closed">Applications are currently closed. Check back soon.</p>
        <?php endif; ?>
        <p class="hero-login"><a href="login.php">Already applied? Log in</a></p>
    </div>
</section>

<div class="facts-strip">
    <div class="facts-strip-inner">
        <span>NACTEVET Reg. No. 128835</span>
        <span>&middot;</span>
        <span>Established 2014</span>
        <span>&middot;</span>
        <span><?php echo (int) $course_count; ?> Programmes Offered</span>
        <span>&middot;</span>
        <span>Karagwe District, Kagera Region</span>
        <span>&middot;</span>
        <span>"We Always Put You First"</span>
    </div>
</div>

<section class="why-section reveal">
    <h2>Why Choose PETI?</h2>
    <div class="why-grid">
        <div class="why-card">
            <span class="why-number">01</span>
            <h3>Job-Focused Training</h3>
            <p>Every course is built around a real career path &middot; "we look for a FIELD, then a JOB for you," not just a certificate.</p>
        </div>
        <div class="why-card">
            <span class="why-number">02</span>
            <h3>Government Accredited</h3>
            <p>Registered with NACTEVET under Cap. 9 of 1997, Reg. No. 128835 &middot; a recognised, licensed institution.</p>
        </div>
        <div class="why-card">
            <span class="why-number">03</span>
            <h3>Hands-On Campus</h3>
            <p>Modern classrooms, a computer lab, and hostel facilities right in Karagwe, Kagera.</p>
        </div>
        <div class="why-card">
            <span class="why-number">04</span>
            <h3>Simple Application</h3>
            <p>Apply, pay, and get admitted online &middot; no long queues, no lost paperwork.</p>
        </div>
    </div>
</section>

<?php if (!empty($hero_images)): ?>
<section class="campus-section reveal">
    <div class="campus-media">
        <img src="<?php echo sanitize($hero_images[0]); ?>" alt="PETI Campus">
    </div>
    <div class="campus-copy">
        <p class="hero-eyebrow">Our Campus</p>
        <h2>A place built for real learning.</h2>
        <p>Our campus in Omulurongo-Lukajange, next to Pump House in Bugene Ward, gives students a calm, focused environment to train, practice, and grow &middot; close to nature, close to community.</p>
        <a class="btn btn-secondary" href="register.php">Start Your Application</a>
    </div>
</section>
<?php endif; ?>

<?php if ($announcements->num_rows > 0): ?>
<section class="announcements-section reveal">
    <h2>Announcements</h2>
    <div class="announcement-grid">
        <?php while ($a = $announcements->fetch_assoc()): ?>
            <article class="announcement-card">
                <span class="announcement-date"><?php echo date('d M Y', strtotime($a['created_at'])); ?></span>
                <h3><?php echo sanitize($a['title']); ?></h3>
                <p><?php echo nl2br(sanitize($a['body'])); ?></p>
            </article>
        <?php endwhile; ?>
    </div>
</section>
<?php endif; ?>

<section class="courses-section reveal">
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

<section class="requirements-section reveal">
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

<section class="requirements-section reveal">
    <h2>How It Works</h2>
    <ol>
        <li>Register with your Form Four Index Number, your names, and gender, and create a password</li>
        <li>Fill in your full application details and upload a scan/photo of your Form Four certificate for verification</li>
        <li>Get your control number and pay the Tsh 10,000 application fee to the college's mobile money number shown on your dashboard</li>
        <li>Once payment is confirmed, choose the course (Advanced Certificate, 2 years) you wish to apply for right away &mdash; no waiting required</li>
        <li>The admissions office reviews your certificate and details, and will message you with the outcome, including Joining Instructions covering course fees and what to bring if you are selected</li>
    </ol>
</section>

<?php
$page_footer_extra = true;
require __DIR__ . '/includes/footer.php';
?>