<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title><?php echo isset($title) ? $title : 'Login'; ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?php echo base_url('assets/css/style.css'); ?>">
<link rel="icon" type="image/x-icon" href="<?php echo base_url('assets/favicon/favicon.ico'); ?>">
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo base_url('assets/favicon/favicon-32x32.png'); ?>">
<link rel="icon" type="image/png" sizes="16x16" href="<?php echo base_url('assets/favicon/favicon-16x16.png'); ?>">
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo base_url('assets/favicon/apple-touch-icon.png'); ?>">
<link rel="manifest" href="<?php echo base_url('assets/favicon/site.webmanifest'); ?>">
</head>
<body>
<div class="login-wrapper">
    <div class="login-cell">
        <div class="login-box">
            <img src="<?php echo base_url('assets/favicon/android-chrome-192x192.png'); ?>" alt="Logo" class="login-logo">
            <h1><?php echo APP_NAME; ?></h1>
            <p class="subtitle">Silakan login untuk melanjutkan</p>

            <?php if (!empty($expired)): ?>
                <div class="alert alert-warning">Sesi Anda habis, silakan login kembali.</div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php echo form_open('auth/login'); ?>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control"
                           value="<?php echo set_value('username'); ?>" autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Masuk</button>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
</body>
</html>