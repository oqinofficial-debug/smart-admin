<div class="card">
    <h2><?php echo ($mode === 'create') ? 'Tambah User' : 'Edit User'; ?></h2>

    <?php if (validation_errors()): ?>
        <div class="alert alert-danger"><?php echo validation_errors(); ?></div>
    <?php endif; ?>

    <?php echo form_open(current_url()); ?>

        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" class="form-control"
                   value="<?php echo set_value('username', $user_data['username'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>Nama Lengkap</label>
            <input type="text" name="fullname" class="form-control"
                   value="<?php echo set_value('fullname', $user_data['fullname'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>Password <?php echo ($mode === 'edit') ? '(kosongkan kalau tidak ingin ganti)' : ''; ?></label>
            <input type="password" name="password" class="form-control">
        </div>

        <div class="form-group">
            <label>Role Global (default)</label>
            <select name="level" class="form-control">
                <?php $current_level = set_value('level', $user_data['level'] ?? ROLE_VIEWER); ?>
                <option value="1" <?php echo ($current_level == 1) ? 'selected' : ''; ?>>Viewer</option>
                <option value="2" <?php echo ($current_level == 2) ? 'selected' : ''; ?>>Inputer</option>
                <option value="3" <?php echo ($current_level == 3) ? 'selected' : ''; ?>>Master</option>
            </select>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" value="1"
                       <?php echo !empty($user_data['is_active']) ? 'checked' : ''; ?>>
                Akun aktif
            </label>
        </div>

        <?php if ($mode === 'edit' && !empty($all_menus)): ?>
        <div class="form-group">
            <label>Role Khusus per Modul (opsional)</label>
            <p style="font-size:12px; color:#8492a6; margin:0 0 8px 0;">
                Biarkan "Ikut Role Global" kalau user ini pakai role default di atas untuk modul tsb.
                Pilih role lain kalau user butuh akses berbeda khusus di modul tertentu.
            </p>
            <table class="table-list">
                <thead>
                    <tr><th>Modul</th><th style="width:200px;">Role di Modul Ini</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($all_menus as $menu): ?>
                        <?php
                            $eff = isset($module_access[$menu['id']]) ? $module_access[$menu['id']] : $current_level;
                            $is_override = $eff != $current_level;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($menu['menu_name']); ?></td>
                            <td>
                                <select name="access_<?php echo $menu['id']; ?>" class="form-control">
                                    <option value="" <?php echo !$is_override ? 'selected' : ''; ?>>Ikut Role Global</option>
                                    <option value="1" <?php echo ($is_override && $eff == 1) ? 'selected' : ''; ?>>Viewer</option>
                                    <option value="2" <?php echo ($is_override && $eff == 2) ? 'selected' : ''; ?>>Inputer</option>
                                    <option value="3" <?php echo ($is_override && $eff == 3) ? 'selected' : ''; ?>>Master</option>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?php echo base_url('user'); ?>" class="btn" style="background:#e9ecef;">Batal</a>

    <?php echo form_close(); ?>
</div>
