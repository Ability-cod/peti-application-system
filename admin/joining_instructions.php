<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$asset_path = '../';
$page_title = 'Joining Instructions';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        redirect('joining_instructions.php');
    }

    $file = $_FILES['joining_pdf'] ?? null;

    if (empty($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        set_flash('error', 'Please choose a PDF file to upload.');
        redirect('joining_instructions.php');
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        set_flash('error', 'Upload failed. Please try again.');
        redirect('joining_instructions.php');
    }

    $max_size = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $max_size) {
        set_flash('error', 'File is too large. Maximum size is 10MB.');
        redirect('joining_instructions.php');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if ($mime !== 'application/pdf') {
        set_flash('error', 'Only PDF files are allowed.');
        redirect('joining_instructions.php');
    }

    $destination = __DIR__ . '/../' . joining_instructions_path();
    $upload_dir = dirname($destination);
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        set_flash('error', 'Could not save the uploaded file. Please try again.');
        redirect('joining_instructions.php');
    }

    set_flash('success', 'Joining Instructions PDF updated. It will now be attached automatically whenever an applicant is approved.');
    redirect('joining_instructions.php');
}

require __DIR__ . '/../includes/header.php';
?>

<section class="admin-section">
    <h1>Joining Instructions PDF</h1>
    <p>This single PDF is automatically attached to the congratulations message sent to every applicant the moment you approve their application. Upload or replace it here.</p>

    <?php if (joining_instructions_exists()): ?>
        <p><strong>Current file:</strong> <a href="../<?php echo sanitize(joining_instructions_path()); ?>" target="_blank">View current Joining Instructions PDF</a></p>
    <?php else: ?>
        <p class="muted">No Joining Instructions PDF has been uploaded yet. Approved applicants will still get the congratulations message, just without an attachment, until you upload one.</p>
    <?php endif; ?>

    <form method="POST" class="app-form" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <label for="joining_pdf">Upload Joining Instructions (PDF, max 10MB)</label>
        <input type="file" id="joining_pdf" name="joining_pdf" accept=".pdf" required>
        <button type="submit" class="btn btn-primary">Save</button>
    </form>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>