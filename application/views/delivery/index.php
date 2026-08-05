<div class="card">
    <h2>Delivery Record</h2>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success"><?php echo $this->session->flashdata('success'); ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>

    <?php if (!empty($access['can_input'])): ?>
        <a href="<?php echo base_url('delivery/add'); ?>" class="btn btn-primary">+ Tambah Delivery Record</a>
    <?php endif; ?>

    <table class="table-list" style="margin-top:12px;">
        <thead>
            <tr>
                <th>No. JF</th>
                <th>Tanggal Kirim</th>
                <th>Aktual Kirim</th>
                <th>No. SP</th>
                <th>Jenis SP</th>
                <th>Status JF</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($deliveries)): ?>
                <tr><td colspan="7">Belum ada data kiriman.</td></tr>
            <?php else: ?>
                <?php foreach ($deliveries as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['jf_kode']); ?></td>
                        <td><?php echo htmlspecialchars($row['tanggal_kirim']); ?></td>
                        <td><?php echo $row['aktual_kirim'] ? htmlspecialchars($row['aktual_kirim']) : '-'; ?></td>
                        <td><?php echo htmlspecialchars($row['no_sp']); ?></td>
                        <td><?php echo htmlspecialchars($row['jenis_sp'] ?? '-'); ?></td>
                        <td>
                            <span class="badge <?php echo ($row['status_jf'] === 'AKTIF') ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo htmlspecialchars($row['status_jf']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($access['can_edit'])): ?>
                                <a href="<?php echo base_url('delivery/edit/' . $row['id']); ?>">Edit</a>
                            <?php endif; ?>
                            <?php if (!empty($access['can_delete'])): ?>
                                |
                                <a href="<?php echo base_url('delivery/delete/' . $row['id']); ?>"
                                   onclick="return confirm('Hapus delivery record ini?');">
                                    Hapus
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
