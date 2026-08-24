<?php
/**
 * Database connection settings.
 * Default values match a standard XAMPP local setup.
 * Change these if your MySQL user/password/host differ.
 */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'peti_system');

define('BASE_URL', 'http://localhost/peti-system/');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
