<div class="card">
    <h2>Manajemen Karyawan</h2>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success"><?php echo $this->session->flashdata('success'); ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>

    <?php if (!empty($access['can_input'])): ?>
        <a href="<?php echo base_url('karyawan/add'); ?>" class="btn btn-primary">+ Tambah Karyawan</a>
        <a href="<?php echo base_url('karyawan/bulk'); ?>" class="btn" style="background:#e9ecef; color:#2c3e50; margin-left:6px;">
            Tambah Massal
        </a>
    <?php endif; ?>

    <table class="table-list" style="margin-top:12px;">
        <thead>
            <tr>
                <th>NIK</th>
                <th>Nama</th>
                <th>Status Kepegawaian</th>
                <th>Status Aktif</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($karyawans)): ?>
                <tr><td colspan="5">Belum ada data karyawan.</td></tr>
            <?php else: ?>
                <?php foreach ($karyawans as $k): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($k['nik']); ?></td>
                        <td><?php echo htmlspecialchars($k['nama']); ?></td>
                        <td><?php echo htmlspecialchars($k['status_kepegawaian']); ?></td>
                        <td>
                            <span class="badge <?php echo $k['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo $k['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($access['can_edit'])): ?>
                                <a href="<?php echo base_url('karyawan/edit/' . $k['id']); ?>">Edit</a>
                            <?php endif; ?>
                            <?php if (!empty($access['can_delete'])): ?>
                                |
                                <a href="<?php echo base_url('karyawan/delete/' . $k['id']); ?>"
                                   onclick="return confirm('Hapus karyawan ini?');">Hapus</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>