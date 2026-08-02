<?php
/**
 * Shared helper functions used across the system.
 */

function sanitize($value) {
    return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function set_flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash() {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function render_flash() {
    $flash = get_flash();
    if ($flash) {
        $class = $flash['type'] === 'error' ? 'alert alert-error' : 'alert alert-success';
        echo '<div class="' . $class . '">' . sanitize($flash['message']) . '</div>';
    }
}

function generate_control_number($conn) {
    do {
        $number = 'PETI' . date('y') . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $stmt = $conn->prepare('SELECT id FROM payments WHERE control_number = ?');
        $stmt->bind_param('s', $number);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
    } while ($exists);

    return $number;
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf() {
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        set_flash('error', 'Invalid form submission. Please try again.');
        return false;
    }
    return true;
}

function get_open_window($conn) {
    $result = $conn->query("SELECT * FROM admission_windows WHERE status = 'open' ORDER BY id DESC LIMIT 1");
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

/**
 * Builds a display full name from the three separate name fields.
 */
function applicant_full_name($applicant) {
    $parts = array_filter([
        $applicant['first_name'] ?? '',
        $applicant['middle_name'] ?? '',
        $applicant['last_name'] ?? '',
    ]);
    return trim(implode(' ', $parts));
}

/**
 * Validates the FORMAT of a Form Four Index Number only (no live check
 * against NECTA — that was dropped because it depends on NECTA's site
 * being reachable and its page structure staying stable, which proved
 * unreliable). The real verification now happens by an admin reviewing
 * the uploaded Form Four certificate against the typed details.
 *
 * Expected format: <centre code>/<candidate number>/<year>
 * e.g. S0001/0001/2020
 *
 * @return array{ok:bool, message:string, normalized:?string}
 */
function validate_necta_index_format($index_number) {
    $index_number = strtoupper(trim($index_number));

    if (!preg_match('/^([A-Z]{1,2}\d{3,6})\/(\d{1,6})\/((?:19|20)\d{2})$/', $index_number, $m)) {
        return ['ok' => false, 'normalized' => null,
                'message' => 'Index number format looks incorrect. Expected format like S0001/0001/2020 (centre/candidate/year).'];
    }

    $centre = strtoupper($m[1]);
    $candidate = str_pad($m[2], 4, '0', STR_PAD_LEFT);
    $year = $m[3];

    return ['ok' => true, 'normalized' => $centre . '/' . $candidate . '/' . $year, 'message' => ''];
}

/**
 * Handles an uploaded Form Four certificate file (image or PDF).
 * Saves it under /uploads/certificates/ with a random filename and
 * returns the relative path to store in the database, or an error.
 *
 * @return array{ok:bool, path:?string, message:?string}
 */
function handle_certificate_upload($file, $applicant_id) {
    if (empty($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'path' => null, 'message' => 'Please choose your Form Four certificate file.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'path' => null, 'message' => 'Upload failed. Please try again.'];
    }

    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        return ['ok' => false, 'path' => null, 'message' => 'File is too large. Maximum size is 5MB.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
    ];

    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'path' => null, 'message' => 'Only JPG, PNG, or PDF files are allowed.'];
    }

    $ext = $allowed[$mime];
    $filename = 'cert_' . (int) $applicant_id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $upload_dir = __DIR__ . '/../uploads/certificates/';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $destination = $upload_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['ok' => false, 'path' => null, 'message' => 'Could not save the uploaded file. Please try again.'];
    }

    return ['ok' => true, 'path' => 'uploads/certificates/' . $filename, 'message' => null];
}

/**
 * Fetches the college's configured mobile money payment details
 * (network name, Lipa Namba/Business Number, instructions), used to
 * show applicants exactly where to pay their application fee.
 */
function get_payment_settings($conn) {
    $result = $conn->query('SELECT * FROM payment_settings WHERE id = 1');
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

/**
 * Human readable status badge helper (used in views).
 */
function status_badge($status) {
    $map = [
        'pending'       => 'badge badge-warning',
        'paid'          => 'badge badge-success',
        'not_submitted' => 'badge badge-warning',
        'submitted'     => 'badge badge-info',
        'approved'      => 'badge badge-success',
        'rejected'      => 'badge badge-error',
        'open'          => 'badge badge-success',
        'closed'        => 'badge badge-error',
    ];
    $class = $map[$status] ?? 'badge';
    $label = ucwords(str_replace('_', ' ', $status));
    return '<span class="' . $class . '">' . $label . '</span>';
}