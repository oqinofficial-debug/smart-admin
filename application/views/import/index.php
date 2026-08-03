<div class="card">
    <h2>Import Data Laporan Produksi</h2>
    <p class="text-muted">
        Upload file Excel (.xlsx) berisi data laporan produksi. Kolom di file Excel
        akan dicocokkan otomatis ke field tujuan berdasarkan alias yang terdaftar,
        dan bisa dikoreksi manual sebelum data disimpan.
    </p>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success"><?php echo $this->session->flashdata('success'); ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>

    <?php if (!empty($access['can_input'])): ?>
        <?php echo form_open_multipart('import/preview'); ?>
            <div class="form-group">
                <label>File Excel (.xlsx)</label>
                <input type="file" name="file" accept=".xlsx" required>
            </div>
            <button type="submit" class="btn btn-primary">Upload &amp; Lihat Preview</button>
        <?php echo form_close(); ?>
    <?php else: ?>
        <div class="alert alert-warning">Anda tidak memiliki hak untuk mengimport data.</div>
    <?php endif; ?>

    <?php if (!empty($access['can_edit'])): ?>
        <p style="margin-top:16px;">
            <a href="<?php echo base_url('import/alias'); ?>">&#9881; Kelola Alias Kolom</a>
        </p>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Field yang Bisa Diisi</h2>
    <p class="text-muted">
        Baris pertama file Excel harus berisi nama kolom (header). Nama kolom boleh
        berbeda dari nama field di bawah, selama sudah terdaftar sebagai alias
        (lihat &quot;Kelola Alias Kolom&quot;).
    </p>
    <table class="table-list">
        <thead>
            <tr>
                <th>Field Tujuan</th>
                <th>Wajib</th>
                <th>Alias Kolom Excel yang Diterima</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($fields as $field_key => $info): ?>
                <tr>
                    <td><?php echo htmlspecialchars($info['label']); ?></td>
                    <td>
                        <?php if ($info['required']): ?>
                            <span class="badge badge-master">Wajib</span>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars(implode(', ', $info['aliases'])); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
