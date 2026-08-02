<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_applicant();

$asset_path = '../';
$page_title = 'My Dashboard';

$applicant = current_applicant($conn);
if (!$applicant) {
    redirect('../logout.php');
}

$payment_stmt = $conn->prepare('SELECT * FROM payments WHERE applicant_id = ? ORDER BY id DESC LIMIT 1');
$payment_stmt->bind_param('i', $applicant['id']);
$payment_stmt->execute();
$payment = $payment_stmt->get_result()->fetch_assoc();

$payment_settings = get_payment_settings($conn);

$unread_stmt = $conn->prepare('SELECT COUNT(*) AS c FROM messages WHERE applicant_id = ? AND is_read = 0');
$unread_stmt->bind_param('i', $applicant['id']);
$unread_stmt->execute();
$unread_count = $unread_stmt->get_result()->fetch_assoc()['c'];

$course_name = null;
if ($applicant['course_id']) {
    $c = $conn->prepare('SELECT name FROM courses WHERE id = ?');
    $c->bind_param('i', $applicant['course_id']);
    $c->execute();
    $course_name = $c->get_result()->fetch_assoc()['name'] ?? null;
}

require __DIR__ . '/../includes/header.php';
?>

<section class="dashboard">
    <h1>Welcome, <?php echo sanitize(applicant_full_name($applicant) ?: $applicant['form_four_index_number']); ?></h1>

    <div class="status-cards">
        <div class="status-card">
            <h3>1. Application Details</h3>
            <p><?php echo status_badge($applicant['profile_status']); ?></p>
            <a class="btn btn-secondary" href="application_form.php">
                <?php echo $applicant['profile_status'] === 'submitted' ? 'View / Edit Details' : 'Complete Application Details'; ?>
            </a>
        </div>

        <div class="status-card">
            <h3>2. Payment</h3>
            <p><?php echo status_badge($applicant['payment_status']); ?></p>
            <?php if ($applicant['profile_status'] !== 'submitted'): ?>
                <p class="muted">Your control number will be generated once you complete your application details.</p>
            <?php elseif ($applicant['payment_status'] === 'pending' && $payment): ?>
                <p>Control Number: <strong><?php echo sanitize($payment['control_number']); ?></strong></p>
                <p>Amount: Tsh <?php echo number_format($payment['amount'], 0); ?></p>
                <?php if ($payment_settings): ?>
                    <p>
                        Pay via <strong><?php echo sanitize($payment_settings['network_name']); ?></strong>,
                        Lipa Namba: <strong><?php echo sanitize($payment_settings['lipa_number']); ?></strong>.
                        Use your control number above as the Reference/Kumbukumbu.
                    </p>
                    <?php if (!empty($payment_settings['instructions'])): ?>
                        <p class="muted"><?php echo nl2br(sanitize($payment_settings['instructions'])); ?></p>
                    <?php endif; ?>
                <?php endif; ?>
                <p class="muted">Your status will change to PAID once the college confirms your payment.</p>
            <?php elseif ($applicant['payment_status'] === 'paid'): ?>
                <p>Payment confirmed. You can now select your course.</p>
            <?php endif; ?>
        </div>

        <div class="status-card">
            <h3>3. Course Selection</h3>
            <?php if ($course_name): ?>
                <p>Selected Course: <strong><?php echo sanitize($course_name); ?></strong></p>
            <?php elseif ($applicant['payment_status'] === 'paid'): ?>
                <a class="btn btn-secondary" href="course_selection.php">Choose Your Course</a>
            <?php else: ?>
                <p class="muted">Available after your payment is confirmed.</p>
            <?php endif; ?>
        </div>

        <div class="status-card">
            <h3>Admissions Decision</h3>
            <p><?php echo status_badge($applicant['review_status']); ?></p>
            <?php if ($applicant['review_status'] === 'approved'): ?>
                <p>Congratulations! Your application has been approved.</p>
            <?php elseif ($applicant['review_status'] === 'rejected'): ?>
                <p>Your application was not successful in this admission window. Please check your messages for guidance on the next window.</p>
            <?php else: ?>
                <p class="muted">The admissions office will review your application and message you with the outcome.</p>
            <?php endif; ?>
        </div>
    </div>

    <p><a href="messages.php">View Messages <?php echo $unread_count > 0 ? '(' . $unread_count . ' unread)' : ''; ?></a></p>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>