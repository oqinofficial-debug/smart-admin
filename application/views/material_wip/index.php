<div class="card">
    <h2>Master Material WIP</h2>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success"><?php echo $this->session->flashdata('success'); ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>

    <?php if (!empty($access['can_input'])): ?>
        <a href="<?php echo base_url('material_wip/add'); ?>" class="btn btn-primary">+ Tambah Material WIP</a>
    <?php endif; ?>

    <table class="table-list" style="margin-top:12px;">
        <thead>
            <tr>
                <th>Kode Material</th>
                <th>Nama Material</th>
                <th>JF Asal</th>
                <th>Status Asal JF</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($materials)): ?>
                <tr><td colspan="6">Belum ada material WIP.</td></tr>
            <?php else: ?>
                <?php foreach ($materials as $m): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($m['kode_material']); ?></td>
                        <td><?php echo htmlspecialchars($m['nama_material']); ?></td>
                        <td><?php echo htmlspecialchars($m['jf_asal']); ?></td>
                        <td>
                            <span class="badge <?php echo ($m['status_asal_jf'] === 'AKTIF') ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo htmlspecialchars($m['status_asal_jf']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php echo $m['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo $m['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($access['can_edit'])): ?>
                                <a href="<?php echo base_url('material_wip/edit/' . $m['id']); ?>">Edit</a>
                            <?php endif; ?>
                            <?php if (!empty($access['can_delete'])): ?>
                                |
                                <a href="<?php echo base_url('material_wip/delete/' . $m['id']); ?>"
                                   onclick="return confirm('Hapus material ini?');">Hapus</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
