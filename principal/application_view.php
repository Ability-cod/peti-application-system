<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_principal();

$asset_path = '../';
$page_title = 'Applicant Details';

$id = (int) ($_GET['id'] ?? 0);

$stmt = $conn->prepare('SELECT a.*, w.name AS window_name FROM applicants a JOIN admission_windows w ON a.window_id = w.id WHERE a.id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$applicant = $stmt->get_result()->fetch_assoc();

if (!$applicant) {
    set_flash('error', 'Applicant not found.');
    redirect('applications.php');
}

$parents = ['father' => [], 'mother' => []];
$pstmt = $conn->prepare('SELECT * FROM parents_guardians WHERE applicant_id = ?');
$pstmt->bind_param('i', $id);
$pstmt->execute();
$pres = $pstmt->get_result();
while ($row = $pres->fetch_assoc()) {
    $parents[$row['relation']] = $row;
}

$course_name = '-';
if ($applicant['course_id']) {
    $c = $conn->prepare('SELECT name FROM courses WHERE id=?');
    $c->bind_param('i', $applicant['course_id']);
    $c->execute();
    $course_name = $c->get_result()->fetch_assoc()['name'] ?? '-';
}

require __DIR__ . '/../includes/header.php';
?>

<section class="admin-section">
    <h1><?php echo sanitize(applicant_full_name($applicant) ?: $applicant['form_four_index_number']); ?></h1>
    <p><?php echo status_badge($applicant['review_status']); ?> &middot; Admission Window: <?php echo sanitize($applicant['window_name']); ?></p>

    <div class="detail-grid">
        <div>
            <h2>Personal</h2>
            <p><strong>Index Number:</strong> <?php echo sanitize($applicant['form_four_index_number']); ?></p>
            <p><strong>Gender:</strong> <?php echo sanitize(ucfirst($applicant['gender'])); ?></p>
            <p><strong>Email:</strong> <?php echo sanitize($applicant['email']); ?></p>
            <p><strong>Phone:</strong> <?php echo sanitize($applicant['phone']); ?></p>
            <p><strong>Nationality:</strong> <?php echo sanitize($applicant['nationality']); ?></p>
            <p><strong>Year Completed Form Four:</strong> <?php echo sanitize($applicant['year_completed_form_four']); ?></p>
            <p><strong>Date of Birth:</strong> <?php echo sanitize($applicant['date_of_birth']); ?></p>
            <p><strong>Place of Birth:</strong> <?php echo sanitize($applicant['birth_region'] . ', ' . $applicant['birth_district'] . ', ' . $applicant['birth_ward'] . ', ' . $applicant['birth_village_street']); ?></p>
        </div>
        <div>
            <h2>Residence</h2>
            <p><?php echo sanitize($applicant['residence_region'] . ', ' . $applicant['residence_district'] . ', ' . $applicant['residence_ward'] . ', ' . $applicant['residence_street']); ?></p>
            <p><strong>Postal Address:</strong> <?php echo sanitize($applicant['postal_address']); ?></p>
            <h2>Course</h2>
            <p><?php echo sanitize($course_name); ?></p>
        </div>
    </div>

    <?php foreach (['father' => 'Father', 'mother' => 'Mother'] as $rel => $label): ?>
        <h2><?php echo $label; ?></h2>
        <p><strong>Name:</strong> <?php echo sanitize($parents[$rel]['full_name'] ?? ''); ?> &middot;
           <strong>Phone:</strong> <?php echo sanitize($parents[$rel]['phone'] ?? ''); ?> &middot;
           <strong>Email:</strong> <?php echo sanitize($parents[$rel]['email'] ?? ''); ?></p>
        <p><strong>Occupation:</strong> <?php echo sanitize($parents[$rel]['occupation'] ?? ''); ?></p>
        <p><strong>Location:</strong> <?php echo sanitize(($parents[$rel]['region'] ?? '') . ', ' . ($parents[$rel]['district'] ?? '') . ', ' . ($parents[$rel]['ward'] ?? '') . ', ' . ($parents[$rel]['village_street'] ?? '')); ?></p>
        <p><strong>Postal Address:</strong> <?php echo sanitize($parents[$rel]['postal_address'] ?? ''); ?></p>
    <?php endforeach; ?>

    <p><a href="applications.php">&larr; Back to All Applicants</a></p>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
