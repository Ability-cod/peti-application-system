<?php
// Expects $page_title to be set before including this file.
$page_title = $page_title ?? 'PETI Application System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo sanitize($page_title); ?> | PETI</title>
<link rel="stylesheet" href="<?php echo (isset($asset_path) ? $asset_path : '') . 'assets/css/style.css'; ?>">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?php echo (isset($asset_path) ? $asset_path : ''); ?>index.php">
            PETI <span>Perfect Education and Training Institute</span>
        </a>
        <nav class="main-nav">
            <?php if (!empty($_SESSION['user_type']) && $_SESSION['user_type'] === 'applicant'): ?>
                <a href="<?php echo (isset($asset_path) ? $asset_path : ''); ?>applicant/dashboard.php">Dashboard</a>
                <a href="<?php echo (isset($asset_path) ? $asset_path : ''); ?>logout.php">Logout</a>
            <?php elseif (!empty($_SESSION['user_type']) && $_SESSION['user_type'] === 'staff' && $_SESSION['staff_role'] === 'admin'): ?>
                <a href="<?php echo (isset($asset_path) ? $asset_path : ''); ?>admin/dashboard.php">Admin Dashboard</a>
                <a href="<?php echo (isset($asset_path) ? $asset_path : ''); ?>logout.php">Logout</a>
            <?php elseif (!empty($_SESSION['user_type']) && $_SESSION['user_type'] === 'staff' && $_SESSION['staff_role'] === 'principal'): ?>
                <a href="<?php echo (isset($asset_path) ? $asset_path : ''); ?>principal/dashboard.php">Principal Dashboard</a>
                <a href="<?php echo (isset($asset_path) ? $asset_path : ''); ?>logout.php">Logout</a>
            <?php else: ?>
                <a href="<?php echo (isset($asset_path) ? $asset_path : ''); ?>login.php">Applicant Login</a>
                <a href="<?php echo (isset($asset_path) ? $asset_path : ''); ?>staff-login.php">Staff Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container page-content">
<?php render_flash(); ?>
