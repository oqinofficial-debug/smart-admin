<?php $akses = cek_akses('user'); ?>

<div class="card">
    <h2>Manajemen User</h2>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success"><?php echo $this->session->flashdata('success'); ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>

    <?php if ($akses && !empty($akses['can_input'])): ?>
        <p><a href="<?php echo base_url('user/create'); ?>" class="btn btn-primary">+ Tambah User</a></p>
    <?php endif; ?>

    <table class="table-list">
        <thead>
            <tr>
                <th>Username</th>
                <th>Nama Lengkap</th>
                <th>Role</th>
                <th>Status</th>
                <th>Login Terakhir</th>
                <th style="width:150px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($users)): foreach ($users as $u): ?>
                <tr>
                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                    <td><?php echo htmlspecialchars($u['fullname']); ?></td>
                    <td><span class="<?php echo role_badge_class($u['level']); ?>"><?php echo role_label($u['level']); ?></span></td>
                    <td><?php echo $u['is_active'] ? 'Aktif' : 'Nonaktif'; ?></td>
                    <td><?php echo $u['last_login'] ? format_tanggal_indo($u['last_login'], TRUE) : '-'; ?></td>
                    <td>
                        <?php if ($akses && !empty($akses['can_edit'])): ?>
                            <a href="<?php echo base_url('user/edit/' . $u['id']); ?>">Edit</a>
                        <?php endif; ?>
                        <?php if ($akses && !empty($akses['can_delete']) && $u['id'] != $current_user_id): ?>
                            &nbsp;|&nbsp;
                            <a href="<?php echo base_url('user/delete/' . $u['id']); ?>"
                               onclick="return confirm('Hapus user ini?');">Hapus</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="6">Belum ada user.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.table-list { width: 100%; border-collapse: collapse; font-size: 13px; }
.table-list th, .table-list td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #eee; }
.table-list th { background: #f7f9fb; color: #566573; font-weight: bold; }
.alert-success { background: #eafaf1; color: #1e7e4f; border: 1px solid #b7ebc6; padding: 9px 12px; border-radius: 3px; margin-bottom: 14px; font-size: 12px; }
</style>
