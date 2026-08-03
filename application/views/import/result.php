<div class="card">
    <h2>Hasil Import</h2>

    <?php if ($success_count > 0): ?>
        <div class="alert alert-success">
            <?php echo $success_count; ?> dari <?php echo $total_rows; ?> baris berhasil disimpan.
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php echo count($errors); ?> baris gagal diimport. Perbaiki data di file Excel lalu upload ulang
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

    <?php if ($success_count === 0 && empty($errors)): ?>
        <div class="alert alert-warning">Tidak ada baris data yang diproses.</div>
    <?php endif; ?>

    <a href="<?php echo base_url('import'); ?>" class="btn btn-primary">Import File Lain</a>
</div>
