<?php
/**
 * Run this ONCE in your browser (e.g. http://localhost/peti-system/install/seed.php)
 * right after importing database/peti_schema.sql.
 * It creates the default admin and principal accounts with properly
 * hashed passwords. Delete this file (or rename it) after running it.
 *
 * DEFAULT LOGINS CREATED:
 *   Admin:     username = admin      password = Admin@2026
 *   Principal: username = principal  password = Principal@2026
 *
 * Change these passwords immediately after first login in a real deployment.
 */

require_once __DIR__ . '/../config/db.php';

$accounts = [
    ['username' => 'admin', 'password' => 'Admin@2026', 'full_name' => 'System Administrator', 'role' => 'admin'],
    ['username' => 'principal', 'password' => 'Principal@2026', 'full_name' => 'Mkuu wa Chuo', 'role' => 'principal'],
];

$created = [];
$skipped = [];

foreach ($accounts as $account) {
    $check = $conn->prepare('SELECT id FROM users WHERE username = ?');
    $check->bind_param('s', $account['username']);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $skipped[] = $account['username'];
        continue;
    }
    $check->close();

    $hash = password_hash($account['password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('ssss', $account['username'], $hash, $account['full_name'], $account['role']);
    $stmt->execute();
    $stmt->close();
    $created[] = $account['username'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>PETI - Seed Default Accounts</title></head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 40px auto;">
<h2>PETI Default Account Setup</h2>
<?php if ($created): ?>
    <p style="color:green;">Created accounts: <?php echo implode(', ', $created); ?></p>
<?php endif; ?>
<?php if ($skipped): ?>
    <p style="color:#856404;">Already existed, skipped: <?php echo implode(', ', $skipped); ?></p>
<?php endif; ?>
<p><strong>Default logins:</strong></p>
<ul>
    <li>Admin &mdash; username: <code>admin</code> / password: <code>Admin@2026</code></li>
    <li>Principal &mdash; username: <code>principal</code> / password: <code>Principal@2026</code></li>
</ul>
<p style="color:red;"><strong>Important:</strong> Delete or rename this file (install/seed.php) now that setup is complete, and change these default passwords after logging in.</p>
<p><a href="../staff-login.php">Go to Staff Login</a></p>
</body>
</html>
