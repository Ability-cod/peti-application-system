<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        redirect('application_view.php?id=' . $id);
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'approve' || $action === 'reject') {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $stmt = $conn->prepare('UPDATE applicants SET review_status=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?');
        $stmt->bind_param('sii', $status, $_SESSION['staff_id'], $id);
        $stmt->execute();
        set_flash('success', 'Application ' . $status . '.');
        redirect('application_view.php?id=' . $id);
    } elseif ($action === 'send_message') {
        $subject = sanitize($_POST['subject'] ?? '');
        $body = sanitize($_POST['body'] ?? '');
        if ($subject === '' || $body === '') {
            set_flash('error', 'Subject and message body are required.');
            redirect('application_view.php?id=' . $id);
        }
        $stmt = $conn->prepare('INSERT INTO messages (applicant_id, sender_id, subject, body) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('iiss', $id, $_SESSION['staff_id'], $subject, $body);
        $stmt->execute();
        set_flash('success', 'Message sent to applicant.');
        redirect('application_view.php?id=' . $id);
    }
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
    <h1><?php echo sanitize(applicant_full_name($applicant)); ?></h1>
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
            <p><strong>Form Four Certificate:</strong>
                <?php if (!empty($applicant['certificate_path'])): ?>
                    <a href="../<?php echo sanitize($applicant['certificate_path']); ?>" target="_blank">View uploaded certificate</a> —
                    please check the names and index number on it match what's shown above.
                <?php else: ?>
                    <span class="muted">Not uploaded yet.</span>
                <?php endif; ?>
            </p>
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

    <?php if ($applicant['review_status'] === 'pending'): ?>
        <div class="action-row">
            <form method="POST" class="inline-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="btn btn-primary">Approve</button>
            </form>
            <form method="POST" class="inline-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="reject">
                <button type="submit" class="btn btn-danger">Reject</button>
            </form>
        </div>
    <?php endif; ?>

    <h2>Send Message to Applicant</h2>
    <?php if ($applicant['review_status'] === 'rejected'): ?>
        <p class="muted">Tip: let them know they're welcome to try again when the next admission window opens.</p>
    <?php endif; ?>
    <form method="POST" class="app-form">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="send_message">
        <label>Subject</label>
        <input type="text" name="subject" required>
        <label>Message</label>
        <textarea name="body" rows="4" required></textarea>
        <button type="submit" class="btn btn-secondary">Send Message</button>
    </form>

    <p><a href="applications.php">&larr; Back to Applications</a></p>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>