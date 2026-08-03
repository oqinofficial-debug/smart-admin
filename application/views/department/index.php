<div class="card">
    <h2>Manajemen Departemen</h2>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success"><?php echo $this->session->flashdata('success'); ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>

    <?php if (!empty($access['can_input'])): ?>
        <a href="<?php echo base_url('department/add'); ?>" class="btn btn-primary">+ Tambah Departemen</a>
    <?php endif; ?>

    <table class="table-list" style="margin-top:12px;">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Departemen</th>
                <th>Jumlah Anggota</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($departments)): ?>
                <tr><td colspan="5">Belum ada departemen.</td></tr>
            <?php else: ?>
                <?php foreach ($departments as $dept): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($dept['department_code']); ?></td>
                        <td><?php echo htmlspecialchars($dept['department_name']); ?></td>
                        <td><?php echo $dept['member_count']; ?></td>
                        <td>
                            <span class="badge <?php echo $dept['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo $dept['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($access['can_edit'])): ?>
                                <a href="<?php echo base_url('department/edit/' . $dept['id']); ?>">Edit</a>
                            <?php endif; ?>
                            <?php if (!empty($access['can_delete'])): ?>
                                <?php if ($dept['member_count'] > 0): ?>
                                    <span class="text-muted" title="Masih ada anggota, tidak bisa dihapus">Hapus</span>
                                <?php else: ?>
                                    |
                                    <a href="<?php echo base_url('department/delete/' . $dept['id']); ?>"
                                       onclick="return confirm('Hapus departemen ini?');">Hapus</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
