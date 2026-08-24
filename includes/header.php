<?php
$page_title = $page_title ?? 'PETI Application System';
$base = isset($asset_path) ? $asset_path : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo sanitize($page_title); ?> | PETI</title>
<link rel="stylesheet" href="<?php echo $base; ?>assets/css/style.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?php echo $base; ?>index.php">
            <img src="<?php echo $base; ?>assets/img/logo.png" alt="PETI - Perfect Education and Training Institute" class="brand-logo">
        </a>
        <nav class="main-nav">
            <?php if (!empty($_SESSION['user_type']) && $_SESSION['user_type'] === 'applicant'): ?>
                <a href="<?php echo $base; ?>applicant/dashboard.php">Dashboard</a>
                <a href="<?php echo $base; ?>logout.php">Logout</a>
            <?php elseif (!empty($_SESSION['user_type']) && $_SESSION['user_type'] === 'staff' && $_SESSION['staff_role'] === 'admin'): ?>
                <a href="<?php echo $base; ?>admin/dashboard.php">Admin Dashboard</a>
                <a href="<?php echo $base; ?>logout.php">Logout</a>
            <?php elseif (!empty($_SESSION['user_type']) && $_SESSION['user_type'] === 'staff' && $_SESSION['staff_role'] === 'principal'): ?>
                <a href="<?php echo $base; ?>principal/dashboard.php">Principal Dashboard</a>
                <a href="<?php echo $base; ?>logout.php">Logout</a>
            <?php else: ?>
                <a href="<?php echo $base; ?>login.php">Applicant Login</a>
                <a href="<?php echo $base; ?>staff-login.php">Staff Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container page-content">
<?php render_flash(); ?>