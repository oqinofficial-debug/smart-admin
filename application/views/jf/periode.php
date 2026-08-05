<div class="card">
    <h2>JF Aktif per Periode</h2>
    <p class="text-muted">
        Daftar JF berstatus AKTIF yang terdeteksi muncul pada periode (bulan) yang dipilih,
        berdasarkan data laporan produksi yang sudah diimport/diinput di periode tersebut.
    </p>

    <a href="<?php echo base_url('jf'); ?>" class="btn btn-secondary" style="margin-bottom:12px;display:inline-block;">&larr; Kembali ke Manajemen JF</a>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success"><?php echo $this->session->flashdata('success'); ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>

    <?php echo form_open('jf/periode', array('method' => 'get')); ?>
        <div class="form-group" style="display:flex;align-items:flex-end;gap:8px;">
            <div>
                <label>Periode (Tahun-Bulan)</label>
                <input type="month" name="periode" value="<?php echo htmlspecialchars($periode); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Tampilkan</button>
        </div>
    <?php echo form_close(); ?>

    <table class="table-list" style="margin-top:12px;">
        <thead>
            <tr>
                <th>JF</th>
                <th>Product</th>
                <th>Customer</th>
                <th>PO</th>
                <th>Status</th>
                <th>Pertama Terlihat</th>
                <th>Terakhir Terlihat</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($jfs)): ?>
                <tr><td colspan="7">Tidak ada JF aktif pada periode <?php echo htmlspecialchars($periode); ?>.</td></tr>
            <?php else: ?>
                <?php foreach ($jfs as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['jf']); ?></td>
                        <td><?php echo htmlspecialchars($row['product'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($row['customer'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($row['po'] ?? '-'); ?></td>
                        <td>
                            <span class="badge badge-active">
                                <?php echo htmlspecialchars($row['status_jf']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($row['first_seen_at']); ?></td>
                        <td><?php echo htmlspecialchars($row['last_seen_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
