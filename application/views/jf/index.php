<div class="card">
    <h2>Manajemen JF</h2>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success"><?php echo $this->session->flashdata('success'); ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>

    <?php if (!empty($access['can_input'])): ?>
        <a href="<?php echo base_url('jf/add'); ?>" class="btn btn-primary">+ Tambah JF</a>
    <?php endif; ?>
    <a href="<?php echo base_url('jf/periode'); ?>" class="btn btn-secondary" style="margin-left:8px;">JF Aktif per Periode</a>

    <table class="table-list" style="margin-top:12px;">
        <thead>
            <tr>
                <th>JF</th>
                <th>Product</th>
                <th>Qty</th>
                <th>Customer</th>
                <th>PO</th>
                <th>Kelompok Produk</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($jfs)): ?>
                <tr><td colspan="8">Belum ada JF.</td></tr>
            <?php else: ?>
                <?php foreach ($jfs as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['jf']); ?></td>
                        <td><?php echo htmlspecialchars($row['product'] ?? '-'); ?></td>
                        <td><?php echo $row['qty'] !== null ? htmlspecialchars($row['qty']) : '-'; ?></td>
                        <td><?php echo htmlspecialchars($row['customer'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($row['po'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($row['kelompok_produk_nama'] ?? '-'); ?></td>
                        <td>
                            <span class="badge <?php echo ($row['status_jf'] === 'AKTIF') ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo htmlspecialchars($row['status_jf']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($access['can_edit'])): ?>
                                <a href="<?php echo base_url('jf/edit/' . $row['id']); ?>">Edit</a>
                            <?php endif; ?>
                            <?php if (!empty($access['can_edit']) && $row['status_jf'] === 'AKTIF'): ?>
                                |
                                <a href="<?php echo base_url('jf/final/' . $row['id']); ?>"
                                   onclick="return confirm('Tandai JF ini FINAL? JF yang sudah final tidak akan dianggap aktif lagi.');">
                                    Jadikan Final
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($access['can_delete'])): ?>
                                |
                                <a href="<?php echo base_url('jf/delete/' . $row['id']); ?>"
                                   onclick="return confirm('Hapus JF ini? Tidak bisa dihapus kalau masih dipakai di laporan produksi.');">
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
