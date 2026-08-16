<?php
function require_applicant() {
    if (empty($_SESSION['user_type']) || $_SESSION['user_type'] !== 'applicant') {
        redirect(BASE_URL . 'login.php');
    }
}

function require_admin() {
    if (empty($_SESSION['user_type']) || $_SESSION['user_type'] !== 'staff' || $_SESSION['staff_role'] !== 'admin') {
        redirect(BASE_URL . 'staff-login.php');
    }
}

function require_principal() {
    if (empty($_SESSION['user_type']) || $_SESSION['user_type'] !== 'staff' || $_SESSION['staff_role'] !== 'principal') {
        redirect(BASE_URL . 'staff-login.php');
    }
}

function require_staff() {
    if (empty($_SESSION['user_type']) || $_SESSION['user_type'] !== 'staff') {
        redirect(BASE_URL . 'staff-login.php');
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