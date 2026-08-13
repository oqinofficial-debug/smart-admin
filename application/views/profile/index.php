<div class="card">
    <h2>Akun Saya</h2>
    <p style="font-size:12px; color:#8492a6; margin:0 0 16px 0;">
        Ganti username dan/atau password login Anda sendiri di sini. Password saat ini wajib
        diisi setiap kali menyimpan perubahan, sebagai konfirmasi bahwa benar Anda pemilik akun.
    </p>

    <?php if (validation_errors()): ?>
        <div class="alert alert-danger"><?php echo validation_errors(); ?></div>
    <?php endif; ?>

    <?php echo form_open(current_url()); ?>

        <div class="form-group">
            <label>Nama Lengkap <span class="field-lock-note">Tidak bisa diedit</span></label>
            <input type="text" class="form-control field-locked" value="<?php echo htmlspecialchars($user_data['fullname']); ?>" disabled>
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
        <a href="<?php echo base_url('dashboard'); ?>" class="btn btn-secondary">Batal</a>

    <?php echo form_close(); ?>
</div>