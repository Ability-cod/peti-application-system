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

// Once details have already been submitted and payment is confirmed,
// send the applicant straight to course selection instead of letting
// them re-enter this form.
if ($applicant['profile_status'] === 'submitted' && $applicant['payment_status'] === 'paid' && !$applicant['course_id']) {
    redirect('course_selection.php');
}

// Fetch existing parent/guardian records if any
$parents = ['father' => [], 'mother' => []];
$pstmt = $conn->prepare('SELECT * FROM parents_guardians WHERE applicant_id = ?');
$pstmt->bind_param('i', $applicant['id']);
$pstmt->execute();
$pres = $pstmt->get_result();
while ($row = $pres->fetch_assoc()) {
    $parents[$row['relation']] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        redirect('application_form.php');
    }

    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $nationality = sanitize($_POST['nationality'] ?? '');
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

    $required = [$email, $phone, $nationality, $residence_region, $residence_district, $residence_ward, $residence_street, $postal_address, $date_of_birth, $birth_region, $birth_district, $birth_ward, $birth_village_street];
    foreach ($required as $field) {
        if ($field === '') {
            set_flash('error', 'Please fill in all required fields.');
            redirect('application_form.php');
        }
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('error', 'Please enter a valid email address.');
        redirect('application_form.php');
    }

    // Certificate upload is required the first time this form is
    // submitted (i.e. if no certificate is on file yet).
    $certificate_path = $applicant['certificate_path'];
    if (empty($certificate_path)) {
        $upload = handle_certificate_upload($_FILES['certificate'] ?? null, $applicant['id']);
        if (!$upload['ok']) {
            set_flash('error', $upload['message']);
            redirect('application_form.php');
        }
        $certificate_path = $upload['path'];
    }

    $stmt = $conn->prepare('UPDATE applicants SET email=?, phone=?, nationality=?, residence_region=?, residence_district=?, residence_ward=?, residence_street=?, postal_address=?, date_of_birth=?, birth_region=?, birth_district=?, birth_ward=?, birth_village_street=?, certificate_path=?, profile_status="submitted" WHERE id=?');
    $stmt->bind_param('ssssssssssssssi',
        $email, $phone, $nationality,
        $residence_region, $residence_district, $residence_ward, $residence_street, $postal_address,
        $date_of_birth,
        $birth_region, $birth_district, $birth_ward, $birth_village_street,
        $certificate_path,
        $applicant['id']
    );
    $stmt->execute();
    $stmt->close();

    // Save parents/guardians (father + mother)
    foreach (['father', 'mother'] as $relation) {
        $p_name = sanitize($_POST[$relation . '_name'] ?? '');
        $p_phone = sanitize($_POST[$relation . '_phone'] ?? '');
        $p_email = sanitize($_POST[$relation . '_email'] ?? '');
        $p_occupation = sanitize($_POST[$relation . '_occupation'] ?? '');
        $p_region = sanitize($_POST[$relation . '_region'] ?? '');
        $p_district = sanitize($_POST[$relation . '_district'] ?? '');
        $p_ward = sanitize($_POST[$relation . '_ward'] ?? '');
        $p_village = sanitize($_POST[$relation . '_village_street'] ?? '');
        $p_postal = sanitize($_POST[$relation . '_postal_address'] ?? '');

        $check = $conn->prepare('SELECT id FROM parents_guardians WHERE applicant_id = ? AND relation = ?');
        $check->bind_param('is', $applicant['id'], $relation);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();

        if ($exists) {
            $up = $conn->prepare('UPDATE parents_guardians SET full_name=?, phone=?, email=?, occupation=?, region=?, district=?, ward=?, village_street=?, postal_address=? WHERE applicant_id=? AND relation=?');
            $up->bind_param('sssssssssis', $p_name, $p_phone, $p_email, $p_occupation, $p_region, $p_district, $p_ward, $p_village, $p_postal, $applicant['id'], $relation);
            $up->execute();
        } else {
            $ins = $conn->prepare('INSERT INTO parents_guardians (applicant_id, relation, full_name, phone, email, occupation, region, district, ward, village_street, postal_address) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
            $ins->bind_param('issssssssss', $applicant['id'], $relation, $p_name, $p_phone, $p_email, $p_occupation, $p_region, $p_district, $p_ward, $p_village, $p_postal);
            $ins->execute();
        }
    }

    // Generate a control number now that details are complete, but only
    // the first time this is submitted.
    $has_payment = $conn->query('SELECT id FROM payments WHERE applicant_id = ' . (int) $applicant['id'])->num_rows > 0;
    if (!$has_payment) {
        $control_number = generate_control_number($conn);
        $pay_stmt = $conn->prepare('INSERT INTO payments (applicant_id, control_number, amount, status) VALUES (?, ?, 5000.00, "pending")');
        $pay_stmt->bind_param('is', $applicant['id'], $control_number);
        $pay_stmt->execute();
        $pay_stmt->close();
        $payment_settings = get_payment_settings($conn);
        $pay_instructions = $payment_settings
            ? 'Pay via ' . $payment_settings['network_name'] . ', Lipa Namba: ' . $payment_settings['lipa_number'] . ', using this control number as your Reference.'
            : 'Pay Tsh 5,000 using this control number.';
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
    <p>Step 2 of 3: Fill in your personal, birth, and parent/guardian details, and upload a scan/photo of your Form Four certificate.</p>

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

        <h2>Form Four Certificate</h2>
        <?php if (!empty($applicant['certificate_path'])): ?>
            <p class="muted">A certificate is already on file. You can upload a new one below to replace it, or leave this blank to keep the current one.</p>
            <p><a href="../<?php echo sanitize($applicant['certificate_path']); ?>" target="_blank">View currently uploaded certificate</a></p>
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

        <?php foreach (['father' => 'Father', 'mother' => 'Mother'] as $rel => $label): ?>
            <h2><?php echo $label; ?>'s Details</h2>
            <label>Full Name</label>
            <input type="text" name="<?php echo $rel; ?>_name" required value="<?php echo sanitize($parents[$rel]['full_name'] ?? ''); ?>">
            <div class="grid-2">
                <div><label>Phone</label><input type="text" name="<?php echo $rel; ?>_phone" required value="<?php echo sanitize($parents[$rel]['phone'] ?? ''); ?>"></div>
                <div><label>Email</label><input type="email" name="<?php echo $rel; ?>_email" required value="<?php echo sanitize($parents[$rel]['email'] ?? ''); ?>"></div>
            </div>
            <label>Occupation</label>
            <input type="text" name="<?php echo $rel; ?>_occupation" required value="<?php echo sanitize($parents[$rel]['occupation'] ?? ''); ?>">
            <div class="grid-2">
                <div><label>Region</label><input type="text" name="<?php echo $rel; ?>_region" required value="<?php echo sanitize($parents[$rel]['region'] ?? ''); ?>"></div>
                <div><label>District</label><input type="text" name="<?php echo $rel; ?>_district" required value="<?php echo sanitize($parents[$rel]['district'] ?? ''); ?>"></div>
                <div><label>Ward</label><input type="text" name="<?php echo $rel; ?>_ward" required value="<?php echo sanitize($parents[$rel]['ward'] ?? ''); ?>"></div>
                <div><label>Village/Street</label><input type="text" name="<?php echo $rel; ?>_village_street" required value="<?php echo sanitize($parents[$rel]['village_street'] ?? ''); ?>"></div>
            </div>
            <label>Postal Address</label>
            <input type="text" name="<?php echo $rel; ?>_postal_address" required value="<?php echo sanitize($parents[$rel]['postal_address'] ?? ''); ?>">
        <?php endforeach; ?>

        <button type="submit" class="btn btn-primary">Submit Application</button>
    </form>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>