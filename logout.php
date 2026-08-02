<?php
session_start();
$was_staff = !empty($_SESSION['user_type']) && $_SESSION['user_type'] === 'staff';
$_SESSION = [];
session_destroy();
header('Location: ' . ($was_staff ? 'staff-login.php' : 'login.php'));
exit;
