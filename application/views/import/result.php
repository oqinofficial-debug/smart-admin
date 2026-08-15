<div class="card">
    <h2>Hasil Import</h2>

    <p class="text-muted">
        Nama Laporan: <strong><?php echo htmlspecialchars($nama_laporan['nama'] ?? '-'); ?></strong>
        <?php if (!empty($nama_laporan['department_name'])): ?>
            (<?php echo htmlspecialchars($nama_laporan['department_name']); ?>)
        <?php endif; ?>
    </p>

    <?php if (!empty($deleted_count)): ?>
        <div class="alert alert-info">
            <?php echo (int) $deleted_count; ?> baris data lama (laporan &amp; cakupan yang sama) ditimpa/dihapus sebelum data baru disimpan.
        </div>
    <?php endif; ?>

    <?php if ($success_count > 0): ?>
        <div class="alert alert-success">
            <?php echo $success_count; ?> dari <?php echo $total_rows; ?> baris berhasil disimpan.
        </div>
    <?php endif; ?>

    <?php if (!empty($skipped_count)): ?>
        <div class="alert alert-info">
            <?php echo $skipped_count; ?> baris dilewati karena tanggalnya di luar
            <?php if ($period_filter['mode'] === 'periode'): ?>
                periode <strong><?php echo htmlspecialchars($period_filter['periode']); ?></strong>
            <?php else: ?>
                rentang tanggal <strong><?php echo htmlspecialchars($period_filter['tanggal_mulai']); ?></strong> s/d <strong><?php echo htmlspecialchars($period_filter['tanggal_selesai']); ?></strong>
            <?php endif; ?>
            yang dipilih (bukan error, cuma tidak termasuk cakupan yang diminta).
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php echo count($errors); ?> baris gagal diimport. Perbaiki data di file lalu upload ulang
            khusus baris yang gagal.
        </div>

        <table class="table-list">
            <thead>
                <tr>
                    <th style="width:80px;">Baris ke-</th>
                    <th>Keterangan Error</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($errors as $err): ?>
                    <tr>
                        <td><?php echo (int) $err['row']; ?></td>
                        <td><?php echo htmlspecialchars($err['message']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if ($success_count === 0 && empty($errors) && empty($skipped_count)): ?>
        <div class="alert alert-warning">Tidak ada baris data yang diproses.</div>
    <?php endif; ?>

    <a href="<?php echo base_url('import'); ?>" class="btn btn-primary">Import File Lain</a>
</div>
