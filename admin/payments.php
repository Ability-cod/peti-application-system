<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$asset_path = '../';
$page_title = 'Confirm Payments';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        redirect('payments.php');
    }

    $payment_id = (int) ($_POST['payment_id'] ?? 0);
    $channel = sanitize($_POST['channel'] ?? '');

    $stmt = $conn->prepare('SELECT * FROM payments WHERE id = ? AND status = "pending"');
    $stmt->bind_param('i', $payment_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();

    if (!$payment) {
        set_flash('error', 'Payment record not found or already confirmed.');
        redirect('payments.php');
    }

    $upd = $conn->prepare('UPDATE payments SET status="paid", channel=?, confirmed_by=?, confirmed_at=NOW() WHERE id=?');
    $upd->bind_param('sii', $channel, $_SESSION['staff_id'], $payment_id);
    $upd->execute();

    $upd2 = $conn->prepare('UPDATE applicants SET payment_status="paid" WHERE id=?');
    $upd2->bind_param('i', $payment['applicant_id']);
    $upd2->execute();

    set_flash('success', 'Payment confirmed. Applicant can now complete their application form.');
    redirect('payments.php');
}

$search = sanitize($_GET['q'] ?? '');
$sql = "SELECT p.*, a.form_four_index_number, a.first_name, a.middle_name, a.last_name FROM payments p JOIN applicants a ON p.applicant_id = a.id WHERE p.status = 'pending'";
if ($search !== '') {
    $sql .= " AND (p.control_number LIKE '%" . $conn->real_escape_string($search) . "%' OR a.form_four_index_number LIKE '%" . $conn->real_escape_string($search) . "%')";
}
$sql .= " ORDER BY p.created_at DESC";
$pending_payments = $conn->query($sql);

require __DIR__ . '/../includes/header.php';
?>

<section class="admin-section">
    <h1>Confirm Payments</h1>
    <p>Check your bank/mobile money statement for received control numbers, then confirm them here to unlock the applicant's full application form.</p>

    <form method="GET" class="inline-form">
        <input type="text" name="q" placeholder="Search control number or index number" value="<?php echo sanitize($search); ?>">
        <button type="submit" class="btn btn-secondary">Search</button>
    </form>

    <table class="data-table">
        <thead><tr><th>Control Number</th><th>Applicant</th><th>Amount</th><th>Requested</th><th>Confirm</th></tr></thead>
        <tbody>
        <?php while ($p = $pending_payments->fetch_assoc()): ?>
            <tr>
                <td><?php echo sanitize($p['control_number']); ?></td>
                <td><?php echo sanitize($p['form_four_index_number']); ?> &middot; <?php echo sanitize(applicant_full_name($p)); ?></td>
                <td>Tsh <?php echo number_format($p['amount'], 0); ?></td>
                <td><?php echo date('d M Y H:i', strtotime($p['created_at'])); ?></td>
                <td>
                    <form method="POST" class="inline-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="payment_id" value="<?php echo $p['id']; ?>">
                        <select name="channel" required>
                            <option value="">Channel used</option>
                            <option value="CRDB Bank">CRDB Bank</option>
                            <option value="M-Pesa">M-Pesa</option>
                            <option value="HaloPesa">HaloPesa</option>
                            <option value="Mixx by Yas">Mixx by Yas</option>
                            <option value="Airtel Money">Airtel Money</option>
                            <option value="Other">Other</option>
                        </select>
                        <button type="submit" class="btn btn-small btn-primary">Mark as Paid</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        <?php if ($pending_payments->num_rows === 0): ?>
            <tr><td colspan="5">No pending payments found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
