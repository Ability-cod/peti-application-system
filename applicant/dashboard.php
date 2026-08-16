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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_payment_proof') {
    if (!verify_csrf()) {
        redirect('dashboard.php');
    }

    if (!$payment || $payment['status'] !== 'pending') {
        set_flash('error', 'There is no pending payment to update.');
        redirect('dashboard.php');
    }

    $payer_phone = sanitize($_POST['payer_phone'] ?? '');
    $payer_network = sanitize($_POST['payer_network'] ?? '');
    $transaction_id = sanitize($_POST['transaction_id'] ?? '');

    if ($payer_phone === '' || $payer_network === '' || $transaction_id === '') {
        set_flash('error', 'Please fill in your phone number, network, and Transaction ID.');
        redirect('dashboard.php');
    }

    $upd = $conn->prepare('UPDATE payments SET payer_phone=?, payer_network=?, transaction_id=? WHERE id=?');
    $upd->bind_param('sssi', $payer_phone, $payer_network, $transaction_id, $payment['id']);
    $upd->execute();

    set_flash('success', 'Thank you! Your payment proof has been submitted. The college will confirm it shortly.');
    redirect('dashboard.php');
}

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
                        Number: <strong><?php echo sanitize($payment_settings['lipa_number']); ?></strong>.
                    </p>
                    <?php if (!empty($payment_settings['instructions'])): ?>
                        <p class="muted"><?php echo nl2br(sanitize($payment_settings['instructions'])); ?></p>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (!empty($payment['transaction_id'])): ?>
                    <p class="muted">You already submitted Transaction ID <strong><?php echo sanitize($payment['transaction_id']); ?></strong>. Awaiting confirmation from the college.</p>
                <?php else: ?>
                    <p class="muted">Already paid? Submit your Transaction ID below so the college can confirm it faster.</p>
                    <form method="POST" class="app-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="submit_payment_proof">
                        <label for="payer_phone">Phone Number You Paid From</label>
                        <input type="text" id="payer_phone" name="payer_phone" required placeholder="e.g. 07XXXXXXXX">
                        <label for="payer_network">Network You Used</label>
                        <select id="payer_network" name="payer_network" required>
                            <option value="">-- Select --</option>
                            <option value="M-Pesa (Vodacom)">M-Pesa (Vodacom)</option>
                            <option value="Tigo Pesa/Mixx by Yas">Tigo Pesa/Mixx by Yas</option>
                            <option value="Airtel Money">Airtel Money</option>
                            <option value="HaloPesa">HaloPesa</option>
                        </select>
                        <label for="transaction_id">Transaction ID (from your payment confirmation SMS)</label>
                        <input type="text" id="transaction_id" name="transaction_id" required placeholder="e.g. QFI5X2Y8ZQ">
                        <button type="submit" class="btn btn-secondary">Submit Payment Proof</button>
                    </form>
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