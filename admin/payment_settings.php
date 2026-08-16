<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$asset_path = '../';
$page_title = 'Payment Settings';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        redirect('payment_settings.php');
    }

    $network_name = sanitize($_POST['network_name'] ?? '');
    $lipa_number = sanitize($_POST['lipa_number'] ?? '');
    $instructions = sanitize($_POST['instructions'] ?? '');

    if ($network_name === '' || $lipa_number === '') {
        set_flash('error', 'Network name and phone number are required.');
        redirect('payment_settings.php');
    }

    $exists = $conn->query('SELECT id FROM payment_settings WHERE id = 1')->num_rows > 0;

    if ($exists) {
        $stmt = $conn->prepare('UPDATE payment_settings SET network_name=?, lipa_number=?, instructions=? WHERE id=1');
        $stmt->bind_param('sss', $network_name, $lipa_number, $instructions);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare('INSERT INTO payment_settings (id, network_name, lipa_number, instructions) VALUES (1, ?, ?, ?)');
        $stmt->bind_param('sss', $network_name, $lipa_number, $instructions);
        $stmt->execute();
    }

    set_flash('success', 'Payment settings updated. Applicants will now see the new details.');
    redirect('payment_settings.php');
}

$settings = get_payment_settings($conn);

require __DIR__ . '/../includes/header.php';
?>

<section class="admin-section">
    <h1>Payment Settings</h1>
    <p>This is the mobile money number applicants are told to pay their Tsh 10,000 application fee to. Because Tanzania's mobile networks are interoperable, students on Tigo Pesa, Airtel Money, or HaloPesa can send directly to a Vodacom M-Pesa number too &mdash; explain that in the instructions box below.</p>

    <form method="POST" class="app-form">
        <?php echo csrf_field(); ?>

        <label for="network_name">Receiving Network (e.g. M-Pesa (Vodacom))</label>
        <input type="text" id="network_name" name="network_name" required value="<?php echo sanitize($settings['network_name'] ?? ''); ?>">

        <label for="lipa_number">Phone Number to Receive Payment</label>
        <input type="text" id="lipa_number" name="lipa_number" required value="<?php echo sanitize($settings['lipa_number'] ?? ''); ?>">

        <label for="instructions">Additional Instructions (optional, shown to every applicant)</label>
        <textarea id="instructions" name="instructions" rows="5" placeholder="e.g. Tigo Pesa, Airtel Money, and HaloPesa users can also send directly to this number using their own network's 'Tuma Pesa Mtandao Mtambuka' option."><?php echo sanitize($settings['instructions'] ?? ''); ?></textarea>

        <button type="submit" class="btn btn-primary">Save Payment Settings</button>
    </form>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>