<div class="card">
    <h2>Master Material RAW</h2>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success"><?php echo $this->session->flashdata('success'); ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>

    <?php if (!empty($access['can_input'])): ?>
        <a href="<?php echo base_url('material_raw/add'); ?>" class="btn btn-primary">+ Tambah Material RAW</a>
    <?php endif; ?>

    <table class="table-list" style="margin-top:12px;">
        <thead>
            <tr>
                <th>Kode Material</th>
                <th>Nama Material</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($materials)): ?>
                <tr><td colspan="4">Belum ada material RAW.</td></tr>
            <?php else: ?>
                <?php foreach ($materials as $m): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($m['kode_material']); ?></td>
                        <td><?php echo htmlspecialchars($m['nama_material']); ?></td>
                        <td>
                            <span class="badge <?php echo $m['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo $m['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($access['can_edit'])): ?>
                                <a href="<?php echo base_url('material_raw/edit/' . $m['id']); ?>">Edit</a>
                            <?php endif; ?>
                            <?php if (!empty($access['can_delete'])): ?>
                                |
                                <a href="<?php echo base_url('material_raw/delete/' . $m['id']); ?>"
                                   onclick="return confirm('Hapus material ini?');">Hapus</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
