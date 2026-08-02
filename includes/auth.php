<?php
/**
 * Session guards. Include this AFTER session_start() and db.php
 * on every protected page.
 *
 * Session shape:
 *   Applicant: $_SESSION['applicant_id'], $_SESSION['user_type'] = 'applicant'
 *   Staff:     $_SESSION['staff_id'], $_SESSION['staff_role'] = 'admin' | 'principal',
 *              $_SESSION['user_type'] = 'staff'
 */

function require_applicant() {
    if (empty($_SESSION['user_type']) || $_SESSION['user_type'] !== 'applicant') {
        redirect('/login.php');
    }
}

function require_admin() {
    if (empty($_SESSION['user_type']) || $_SESSION['user_type'] !== 'staff' || $_SESSION['staff_role'] !== 'admin') {
        redirect('/staff-login.php');
    }
}

function require_principal() {
    if (empty($_SESSION['user_type']) || $_SESSION['user_type'] !== 'staff' || $_SESSION['staff_role'] !== 'principal') {
        redirect('/staff-login.php');
    }
}

function require_staff() {
    if (empty($_SESSION['user_type']) || $_SESSION['user_type'] !== 'staff') {
        redirect('/staff-login.php');
    }
}

function current_applicant($conn) {
    if (empty($_SESSION['applicant_id'])) {
        return null;
    }
    $stmt = $conn->prepare('SELECT * FROM applicants WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['applicant_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}
