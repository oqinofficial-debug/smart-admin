<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title><?php echo isset($title) ? $title : APP_NAME; ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?php echo base_url('assets/css/style.css'); ?>">
<link rel="icon" type="image/x-icon" href="<?php echo base_url('assets/favicon/favicon.ico'); ?>">
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo base_url('assets/favicon/favicon-32x32.png'); ?>">
<link rel="icon" type="image/png" sizes="16x16" href="<?php echo base_url('assets/favicon/favicon-16x16.png'); ?>">
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo base_url('assets/favicon/apple-touch-icon.png'); ?>">
<link rel="manifest" href="<?php echo base_url('assets/favicon/site.webmanifest'); ?>">
</head>
<body>

<div class="app-header">
    <div class="brand"><?php echo APP_NAME; ?></div>
    <div class="user-info">
        <?php $u = current_user(); ?>
        Selamat datang, <?php echo htmlspecialchars($u['fullname']); ?>
        &nbsp;<a href="<?php echo base_url('auth/logout'); ?>">Logout</a>
    </div>
    <div style="clear:both;"></div>
</div>