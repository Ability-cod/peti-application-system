<?php
/**
 * Serves an applicant's uploaded Form Four certificate ONLY to:
 *   - the applicant themselves (logged in as that applicant), or
 *   - logged-in staff (admin or principal), for review purposes.
 *
 * This exists because certificates were previously linked directly by
 * their file path (e.g. uploads/certificates/cert_12_ab34ef.jpg), which
 * meant anyone with that URL — even without logging in — could view a
 * student's personal document. Routing all access through this script,
 * combined with uploads/certificates/.htaccess blocking direct access,
 * closes that gap: the file itself is no longer reachable except
 * through this authentication check.
 */
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$applicant_id = (int) ($_GET['applicant_id'] ?? 0);

$is_self = !empty($_SESSION['user_type']) && $_SESSION['user_type'] === 'applicant' && (int) $_SESSION['applicant_id'] === $applicant_id;
$is_staff = !empty($_SESSION['user_type']) && $_SESSION['user_type'] === 'staff';

if (!$is_self && !$is_staff) {
    http_response_code(403);
    die('Access denied. Please log in to view this file.');
}

$stmt = $conn->prepare('SELECT certificate_path FROM applicants WHERE id = ?');
$stmt->bind_param('i', $applicant_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row || empty($row['certificate_path'])) {
    http_response_code(404);
    die('Certificate not found.');
}

$full_path = __DIR__ . '/' . $row['certificate_path'];

if (!is_file($full_path)) {
    http_response_code(404);
    die('Certificate file is missing.');
}

$ext = strtolower(pathinfo($full_path, PATHINFO_EXTENSION));
$mime_map = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf'];
$mime = $mime_map[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="certificate.' . $ext . '"');
header('X-Content-Type-Options: nosniff');
readfile($full_path);