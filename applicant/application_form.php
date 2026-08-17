<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_applicant();

$asset_path = '../';
$page_title = 'Application Form';

$applicant = current_applicant($conn);
if (!$applicant) {
    redirect('../logout.php');
}

if ($applicant['profile_status'] === 'submitted' && $applicant['payment_status'] === 'paid' && !$applicant['course_id']) {
    redirect('course_selection.php');
}

// Fetch existing guardian record if any
$gstmt = $conn->prepare('SELECT * FROM guardian WHERE applicant_id = ?');
$gstmt->bind_param('i', $applicant['id']);
$gstmt->execute();
$guardian = $gstmt->get_result()->fetch_assoc();
if (!$guardian) {
    $guardian = ['full_name' => '', 'address' => '', 'contact' => ''];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        redirect('application_form.php');
    }

    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $nationality = sanitize($_POST['nationality'] ?? '');
    $marital_status = sanitize($_POST['marital_status'] ?? '');
    $residence_region = sanitize($_POST['residence_region'] ?? '');
    $residence_district = sanitize($_POST['residence_district'] ?? '');
    $residence_ward = sanitize($_POST['residence_ward'] ?? '');
    $residence_street = sanitize($_POST['residence_street'] ?? '');
    $postal_address = sanitize($_POST['postal_address'] ?? '');
    $date_of_birth = sanitize($_POST['date_of_birth'] ?? '');
    $birth_region = sanitize($_POST['birth_region'] ?? '');
    $birth_district = sanitize($_POST['birth_district'] ?? '');
    $birth_ward = sanitize($_POST['birth_ward'] ?? '');
    $birth_village_street = sanitize($_POST['birth_village_street'] ?? '');
    $guardian_name = sanitize($_POST['guardian_name'] ?? '');
    $guardian_address = sanitize($_POST['guardian_address'] ?? '');
    $guardian_contact = sanitize($_POST['guardian_contact'] ?? '');

    $required = [$email, $phone, $nationality, $marital_status, $residence_region, $residence_district, $residence_ward, $residence_street, $postal_address, $date_of_birth, $birth_region, $birth_district, $birth_ward, $birth_village_street, $guardian_name, $guardian_address, $guardian_contact];
    foreach ($required as $field) {
        if ($field === '') {
            set_flash('error', 'Please fill in all required fields.');
            redirect('application_form.php');
        }
    }

    if (!in_array($marital_status, ['married', 'not_married'], true)) {
        set_flash('error', 'Please select a valid marital status.');
        redirect('application_form.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('error', 'Please enter a valid email address.');
        redirect('application_form.php');
    }

    $certificate_path = $applicant['certificate_path'];
    if (empty($certificate_path)) {
        $upload = handle_certificate_upload($_FILES['certificate'] ?? null, $applicant['id']);
        if (!$upload['ok']) {
            set_flash('error', $upload['message']);
            redirect('application_form.php');
        }
        $certificate_path = $upload['path'];
    }

    $stmt = $conn->prepare('UPDATE applicants SET email=?, phone=?, nationality=?, marital_status=?, residence_region=?, residence_district=?, residence_ward=?, residence_street=?, postal_address=?, date_of_birth=?, birth_region=?, birth_district=?, birth_ward=?, birth_village_street=?, certificate_path=?, profile_status="submitted" WHERE id=?');
    $stmt->bind_param('sssssssssssssssi',
        $email, $phone, $nationality, $marital_status,
        $residence_region, $residence_district, $residence_ward, $residence_street, $postal_address,
        $date_of_birth,
        $birth_region, $birth_district, $birth_ward, $birth_village_street,
        $certificate_path,
        $applicant['id']
    );
    $stmt->execute();
    $stmt->close();

    // Save guardian (single record, insert or update)
    $check = $conn->prepare('SELECT id FROM guardian WHERE applicant_id = ?');
    $check->bind_param('i', $applicant['id']);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;
    $check->close();

    if ($exists) {
        $up = $conn->prepare('UPDATE guardian SET full_name=?, address=?, contact=? WHERE applicant_id=?');
        $up->bind_param('sssi', $guardian_name, $guardian_address, $guardian_contact, $applicant['id']);
        $up->execute();
    } else {
        $ins = $conn->prepare('INSERT INTO guardian (applicant_id, full_name, address, contact) VALUES (?, ?, ?, ?)');
        $ins->bind_param('isss', $applicant['id'], $guardian_name, $guardian_address, $guardian_contact);
        $ins->execute();
    }

    $has_payment = $conn->query('SELECT id FROM payments WHERE applicant_id = ' . (int) $applicant['id'])->num_rows > 0;
    if (!$has_payment) {
        $control_number = generate_control_number($conn);
        $pay_stmt = $conn->prepare('INSERT INTO payments (applicant_id, control_number, amount, status) VALUES (?, ?, 10000.00, "pending")');
        $pay_stmt->bind_param('is', $applicant['id'], $control_number);
        $pay_stmt->execute();
        $pay_stmt->close();

        $payment_settings = get_payment_settings($conn);
        $pay_instructions = $payment_settings
            ? 'Pay via ' . $payment_settings['network_name'] . ', Lipa Namba: ' . $payment_settings['lipa_number'] . ', using this control number as your Reference.'
            : 'Pay Tsh 10,000 using this control number.';
        set_flash('success', 'Your details and certificate have been saved. Your control number is ' . $control_number . '. ' . $pay_instructions . ' Then check back here.');
    } else {
        set_flash('success', 'Your details have been updated.');
    }
    redirect('dashboard.php');
}

require __DIR__ . '/../includes/header.php';
?>

<section class="form-section">
    <h1>Application Form</h1>
    <p>Step 2 of 3: Fill in your personal, birth, and guardian details, and upload a scan/photo of your Form Four certificate.</p>

    <form method="POST" class="app-form" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>

        <h2>Personal Details</h2>
        <p class="muted">Name: <strong><?php echo sanitize(applicant_full_name($applicant)); ?></strong> &middot;
           Gender: <strong><?php echo sanitize(ucfirst($applicant['gender'])); ?></strong> &middot;
           Index Number: <strong><?php echo sanitize($applicant['form_four_index_number']); ?></strong> &middot;
           Year Completed Form Four: <strong><?php echo sanitize($applicant['year_completed_form_four']); ?></strong>
           (captured at registration)</p>

        <label for="email">Active Email Address</label>
        <input type="email" name="email" id="email" required value="<?php echo sanitize($applicant['email']); ?>">

        <label for="phone">Phone Number</label>
        <input type="text" name="phone" id="phone" required value="<?php echo sanitize($applicant['phone']); ?>">

        <label for="nationality">Nationality</label>
        <input type="text" name="nationality" id="nationality" required value="<?php echo sanitize($applicant['nationality']); ?>">

        <label for="marital_status">Marital Status</label>
        <select id="marital_status" name="marital_status" required>
            <option value="">-- Select --</option>
            <option value="married" <?php echo $applicant['marital_status'] === 'married' ? 'selected' : ''; ?>>Married</option>
            <option value="not_married" <?php echo $applicant['marital_status'] === 'not_married' ? 'selected' : ''; ?>>Not Married</option>
        </select>

        <h2>Form Four Certificate</h2>
        <?php if (!empty($applicant['certificate_path'])): ?>
            <p class="muted">A certificate is already on file. Upload a new one below to replace it, or leave blank to keep the current one.</p>
            <p><a href="../serve_certificate.php?applicant_id=<?php echo $applicant['id']; ?>" target="_blank">View currently uploaded certificate</a></p>
            <input type="file" name="certificate" accept=".jpg,.jpeg,.png,.pdf">
        <?php else: ?>
            <label for="certificate">Upload Form Four Certificate (JPG, PNG, or PDF, max 5MB)</label>
            <input type="file" id="certificate" name="certificate" accept=".jpg,.jpeg,.png,.pdf" required>
        <?php endif; ?>

        <h2>Residential Address</h2>
        <div class="grid-2">
            <div><label>Region</label><input type="text" name="residence_region" required value="<?php echo sanitize($applicant['residence_region']); ?>"></div>
            <div><label>District</label><input type="text" name="residence_district" required value="<?php echo sanitize($applicant['residence_district']); ?>"></div>
            <div><label>Ward (Kata)</label><input type="text" name="residence_ward" required value="<?php echo sanitize($applicant['residence_ward']); ?>"></div>
            <div><label>Street/Mtaa</label><input type="text" name="residence_street" required value="<?php echo sanitize($applicant['residence_street']); ?>"></div>
        </div>
        <label for="postal_address">Postal Address (P.O. Box)</label>
        <input type="text" name="postal_address" id="postal_address" required value="<?php echo sanitize($applicant['postal_address']); ?>">

        <h2>Birth Details</h2>
        <label for="date_of_birth">Date of Birth (as per birth certificate)</label>
        <input type="date" name="date_of_birth" id="date_of_birth" required value="<?php echo sanitize($applicant['date_of_birth']); ?>">
        <div class="grid-2">
            <div><label>Region</label><input type="text" name="birth_region" required value="<?php echo sanitize($applicant['birth_region']); ?>"></div>
            <div><label>District</label><input type="text" name="birth_district" required value="<?php echo sanitize($applicant['birth_district']); ?>"></div>
            <div><label>Ward</label><input type="text" name="birth_ward" required value="<?php echo sanitize($applicant['birth_ward']); ?>"></div>
            <div><label>Village/Street</label><input type="text" name="birth_village_street" required value="<?php echo sanitize($applicant['birth_village_street']); ?>"></div>
        </div>

        <h2>Guardian / Mdhamini Details</h2>
        <label>Full Name</label>
        <input type="text" name="guardian_name" required value="<?php echo sanitize($guardian['full_name']); ?>">
        <label>Address</label>
        <input type="text" name="guardian_address" required value="<?php echo sanitize($guardian['address']); ?>">
        <label>Contact (Phone/Email)</label>
        <input type="text" name="guardian_contact" required value="<?php echo sanitize($guardian['contact']); ?>">

        <button type="submit" class="btn btn-primary">Submit Application</button>
    </form>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>