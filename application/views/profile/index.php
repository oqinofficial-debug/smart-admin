<div class="card">
    <h2>Akun Saya</h2>
    <p style="font-size:12px; color:#8492a6; margin:0 0 16px 0;">
        Ganti username dan/atau password login Anda sendiri di sini. Password saat ini wajib
        diisi setiap kali menyimpan perubahan, sebagai konfirmasi bahwa benar Anda pemilik akun.
    </p>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success"><?php echo $this->session->flashdata('success'); ?></div>
    <?php endif; ?>
    <?php if (validation_errors()): ?>
        <div class="alert alert-danger"><?php echo validation_errors(); ?></div>
    <?php endif; ?>

    <?php echo form_open(current_url()); ?>

        <div class="form-group">
            <label>Nama Lengkap</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['fullname']); ?>" disabled>
        </div>

        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" class="form-control"
                   value="<?php echo set_value('username', $user_data['username']); ?>">
        </div>

        <hr style="border:none; border-top:1px solid #30363d; margin:18px 0;">

        <div class="form-group">
            <label>Password Baru <span style="font-weight:normal; color:#8492a6;">(kosongkan kalau tidak ingin ganti password)</span></label>
            <input type="password" name="new_password" class="form-control" autocomplete="new-password">
        </div>

        <div class="form-group">
            <label>Konfirmasi Password Baru</label>
            <input type="password" name="confirm_password" class="form-control" autocomplete="new-password">
        </div>

        <hr style="border:none; border-top:1px solid #30363d; margin:18px 0;">

        <div class="form-group">
            <label>Password Saat Ini <span style="font-weight:normal; color:#8492a6;">(wajib diisi untuk menyimpan)</span></label>
            <input type="password" name="current_password" class="form-control" autocomplete="current-password">
        </div>

        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>

    <?php echo form_close(); ?>
</div>

<style>
.alert-success { background: #eafaf1; color: #1e7e4f; border: 1px solid #b7ebc6; padding: 9px 12px; border-radius: 3px; margin-bottom: 14px; font-size: 12px; }
</style>
