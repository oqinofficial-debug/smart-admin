<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title><?php echo isset($title) ? $title : APP_NAME; ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?php echo base_url('assets/css/style.css'); ?>">
</head>
<body>

<div class="app-header">
    <div class="brand"><?php echo APP_NAME; ?></div>
    <div class="user-info">
        <?php $u = current_user(); ?>
        <span class="<?php echo role_badge_class($u['level']); ?>"><?php echo role_label($u['level']); ?></span>
        &nbsp;<?php echo htmlspecialchars($u['fullname']); ?>
        <a href="<?php echo base_url('auth/logout'); ?>">Logout</a>
    </div>
    <div style="clear:both;"></div>
</div>
