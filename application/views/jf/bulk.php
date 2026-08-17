<div class="card">
    <h2>Tambah Massal JF</h2>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success"><?php echo $this->session->flashdata('success'); ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>

    <p class="text-muted">
        Tempel data langsung dari Excel (atau ketik manual satu baris per data).
        Urutan kolom: <strong>JF</strong>, Product, Qty, BAPOB, Chip, Customer, PO,
        Kelompok Produk (diisi <em>nama</em> kelompok produk, harus sudah ada di Master Data),
        Status JF (opsional, <code>AKTIF</code>/<code>FINAL</code>). Hanya kolom JF yang wajib diisi,
        kolom lain boleh dikosongkan. Kolom dipisah TAB (otomatis kalau paste dari Excel) —
        kalau ketik manual boleh pakai <code>;</code>.
    </p>
    <p class="text-muted">
        <strong>Kode JF yang sudah ada akan DI-REPLACE</strong> dengan data baru. Kolom Status JF yang
        dikosongkan pada baris replace tidak akan menimpa status yang sudah ada (mis. tidak akan
        membatalkan JF yang sudah ditandai FINAL). JF yang belum ada akan ditambahkan sebagai data baru
        (status default AKTIF kalau kolom status dikosongkan).
    </p>

    <pre style="background:#f8f9fa; border:1px solid #dee2e6; padding:10px; border-radius:4px; font-size:12px;">JF001	Produk A	1000	BAPOB-01	CHIP-01	Customer A	PO-001	Kelompok A	AKTIF
JF002	Produk B	500			Customer B	PO-002</pre>

    <?php echo form_open(current_url()); ?>

        <div class="form-group">
            <label>Tempel Data</label>
            <textarea name="data" class="form-control" rows="10" style="font-family:monospace;"
                      placeholder="JF&#9;Product&#9;Qty&#9;BAPOB&#9;Chip&#9;Customer&#9;PO&#9;Kelompok Produk&#9;Status JF"><?php echo htmlspecialchars($raw ?? ''); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Proses</button>
        <a href="<?php echo base_url('jf'); ?>" class="btn" style="background:#e9ecef;">Batal</a>

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