<div class="card">
    <h2>Tambah Massal Delivery Record</h2>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success"><?php echo $this->session->flashdata('success'); ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>

    <p class="text-muted">
        Copy-paste data langsung dari Excel (atau ketik manual satu baris per data).
        Urutan kolom: <strong>No. JF</strong>, <strong>Tanggal Kirim</strong>, Aktual Kirim / Qty (opsional,
        angka, kosongkan kalau belum benar-benar terkirim), <strong>No. SP</strong>, Jenis SP (opsional).
        Kolom dipisah TAB (otomatis kalau paste dari Excel) — kalau ketik manual boleh pakai <code>;</code>.
        Format tanggal fleksibel: <code>DD/MM/YYYY</code>, <code>YYYY-MM-DD</code>, dst.
    </p>
    <p class="text-muted">
        No. JF harus sudah terdaftar di Master JF (tidak dibuatkan otomatis dari sini).
        <strong>Pasangan No. JF + No. SP yang sudah ada akan DI-REPLACE</strong> (tanggal kirim, aktual
        kirim/qty &amp; jenis SP ditimpa dengan data baru). Kalau pasangannya belum ada, jadi data baru.
    </p>

    <pre style="background:#f8f9fa; border:1px solid #dee2e6; padding:10px; border-radius:4px; font-size:12px;">JF001	01/08/2026	500	SP-1001	Reguler
JF002	05/08/2026		SP-1002	Ekspress</pre>

    <?php echo form_open(current_url()); ?>

        <div class="form-group">
            <label>Tempel Data</label>
            <textarea name="data" class="form-control" rows="10" style="font-family:monospace;"
                      placeholder="No. JF&#9;Tanggal Kirim&#9;Aktual Kirim (Qty)&#9;No. SP&#9;Jenis SP"><?php echo htmlspecialchars($raw ?? ''); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Proses</button>
        <a href="<?php echo base_url('delivery'); ?>" class="btn" style="background:#e9ecef;">Batal</a>

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
