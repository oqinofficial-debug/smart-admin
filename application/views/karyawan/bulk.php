<div class="card">
    <h2>Tambah Massal Karyawan</h2>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success"><?php echo $this->session->flashdata('success'); ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>

    <p class="text-muted">
        Copy-paste data langsung dari Excel (atau ketik manual satu baris per data).
        Urutan kolom: <strong>NIK</strong>, <strong>Nama</strong>, <strong>Status Kepegawaian</strong>
        (<code>HARIAN</code> atau <code>BORONG</code>), <strong>Aktif</strong> (opsional, isi
        <code>1</code>/<code>0</code> atau <code>ya</code>/<code>tidak</code>, kalau kosong dianggap Aktif).
        Kolom dipisah TAB (otomatis kalau paste dari Excel) — kalau ketik manual boleh pakai <code>;</code>.
    </p>
    <p class="text-muted">
        <strong>NIK yang sudah ada akan DI-REPLACE</strong> (nama, status kepegawaian &amp; status aktif
        ditimpa dengan data baru). NIK yang belum ada akan ditambahkan sebagai karyawan baru.
    </p>

    <pre style="background:#f8f9fa; border:1px solid #dee2e6; padding:10px; border-radius:4px; font-size:12px;">198501012010001	Budi Santoso	HARIAN	1
198602022011002	Siti Aminah	BORONG	1</pre>

    <?php echo form_open(current_url()); ?>

        <div class="form-group">
            <label>Tempel Data</label>
            <textarea name="data" class="form-control" rows="10" style="font-family:monospace;"
                      placeholder="NIK&#9;Nama&#9;Status Kepegawaian&#9;Aktif"><?php echo htmlspecialchars($raw ?? ''); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Proses</button>
        <a href="<?php echo base_url('karyawan'); ?>" class="btn" style="background:#e9ecef;">Batal</a>

    <?php echo form_close(); ?>

    <?php if ($result !== null && !empty($result['errors'])): ?>
        <div style="margin-top:20px;">
            <h3>Baris Gagal (<?php echo count($result['errors']); ?>)</h3>
            <table class="table-list">
                <thead>
                    <tr><th style="width:80px;">Baris</th><th>Keterangan</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($result['errors'] as $err): ?>
                        <tr>
                            <td><?php echo (int) $err['line']; ?></td>
                            <td><?php echo htmlspecialchars($err['message']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
