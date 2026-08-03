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
            <label>
                <input type="checkbox" name="is_active" value="1"
                       <?php echo !empty($user_data['is_active']) ? 'checked' : ''; ?>>
                Akun aktif
            </label>
        </div>

        <?php if ($mode === 'edit'): ?>
        <div class="form-group">
            <label>
                <input type="checkbox" name="can_view_all_departments" value="1"
                       <?php echo normalize_bool($user_data['can_view_all_departments'] ?? false) ? 'checked' : ''; ?>>
                Bisa lihat semua departemen (bypass filter departemen)
            </label>
        </div>
        <?php endif; ?>

        <?php if ($mode === 'edit' && !empty($all_menus)): ?>
        <div class="form-group">
            <label>Akses per Modul</label>
            <p style="font-size:12px; color:#8492a6; margin:0 0 8px 0;">
                Tidak ada role default — setiap modul wajib diatur sendiri. Pilih "Tidak Ada Akses"
                kalau modul ini harus disembunyikan sepenuhnya dari user ini.
            </p>
            <table class="table-list">
                <thead>
                    <tr><th>Modul</th><th style="width:200px;">Role di Modul Ini</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($all_menus as $menu): ?>
                        <?php $eff = isset($module_access[$menu['id']]) ? $module_access[$menu['id']] : 0; ?>
                        <tr>
                            <td><?php echo htmlspecialchars($menu['menu_name']); ?></td>
                            <td>
                                <select name="access_<?php echo $menu['id']; ?>" class="form-control">
                                    <option value="0" <?php echo ($eff == 0) ? 'selected' : ''; ?>>Tidak Ada Akses</option>
                                    <option value="1" <?php echo ($eff == 1) ? 'selected' : ''; ?>>Viewer</option>
                                    <option value="2" <?php echo ($eff == 2) ? 'selected' : ''; ?>>Inputer</option>
                                    <option value="3" <?php echo ($eff == 3) ? 'selected' : ''; ?>>Master</option>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if ($mode === 'edit' && !empty($all_departments)): ?>
        <div class="form-group">
            <label>Departemen</label>
            <p style="font-size:12px; color:#8492a6; margin:0 0 8px 0;">
                Tentukan keanggotaan user di tiap departemen. "Anggota (Primary)" jadi departemen
                aktif default saat user login — maksimal satu.
            </p>
            <table class="table-list">
                <thead>
                    <tr><th>Departemen</th><th style="width:200px;">Keanggotaan</th></tr>
                </thead>
                <tbody>
                    <?php
                        $current_dept_map = array();
                        foreach ($user_departments as $ud) {
                            $current_dept_map[$ud['department_id']] = $ud['is_primary'] ? 2 : 1;
                        }
                    ?>
                    <?php foreach ($all_departments as $dept): ?>
                        <?php $current = isset($current_dept_map[$dept['id']]) ? $current_dept_map[$dept['id']] : 0; ?>
                        <tr>
                            <td><?php echo htmlspecialchars($dept['department_name']); ?></td>
                            <td>
                                <select name="membership_<?php echo $dept['id']; ?>" class="form-control">
                                    <option value="0" <?php echo ($current == 0) ? 'selected' : ''; ?>>Tidak Ikut</option>
                                    <option value="1" <?php echo ($current == 1) ? 'selected' : ''; ?>>Anggota</option>
                                    <option value="2" <?php echo ($current == 2) ? 'selected' : ''; ?>>Anggota (Primary)</option>
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