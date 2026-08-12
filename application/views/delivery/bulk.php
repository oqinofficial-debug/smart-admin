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

    <?php if (!empty($fg_previews)): ?>
        <div style="margin-top:20px;">
            <h3>Preview Auto-Alokasi Stok FG (FIFO)</h3>
            <p class="text-muted">Kandidat pengurangan stok FG dari Aktual Kirim baris di atas. Hanya preview --
                klik "Terapkan Semua Alokasi FG" untuk menyimpan. Menerapkan akan MENGGANTI cantolan FG lama
                (kalau ada) untuk tiap kiriman yang tercantum.</p>
            <table class="table-list">
                <thead>
                    <tr><th>No. JF</th><th>No. SP</th><th>Aktual Kirim</th><th>Rincian Alokasi (proses/periode: qty)</th><th>Warning</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($fg_previews as $p): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($p['jf_kode']); ?></td>
                            <td><?php echo htmlspecialchars($p['no_sp']); ?></td>
                            <td><?php echo htmlspecialchars($p['aktual_kirim']); ?></td>
                            <td>
                                <?php foreach ($p['allocations'] as $a): ?>
                                    <?php echo htmlspecialchars($a['proses_nama'] . '/' . $a['periode'] . ': ' . $a['alokasi_qty']); ?><br>
                                <?php endforeach; ?>
                            </td>
                            <td><?php echo $p['warning'] ? htmlspecialchars($p['warning']) : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="button" class="btn btn-primary" id="btn-terapkan-semua-fg" style="margin-top:8px;">
                Terapkan Semua Alokasi FG
            </button>
            <div id="bulk-fg-alert-box" style="margin-top:8px;"></div>
        </div>
        <script>
        (function () {
            var baseUrl = '<?php echo base_url(); ?>';
            var items = <?php echo json_encode(array_map(function ($p) {
                return array('delivery_id' => $p['delivery_id'], 'allocations' => $p['allocations']);
            }, $fg_previews)); ?>;
            var box = document.getElementById('bulk-fg-alert-box');

            document.getElementById('btn-terapkan-semua-fg').addEventListener('click', function () {
                if (!confirm('Terapkan semua alokasi FG di atas? Cantolan FG lama (kalau ada) akan diganti.')) { return; }
                fetch(baseUrl + 'delivery/fg_auto_confirm_bulk', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ items: JSON.stringify(items) })
                }).then(function (r) { return r.json(); })
                  .then(function (res) {
                      box.innerHTML = '<div class="alert alert-' + (res.success ? 'success' : 'danger') + '">' +
                          (res.success ? ('Alokasi diterapkan (' + res.count + ' baris cantolan FG).') : (res.message || 'Gagal.')) +
                          '</div>';
                  });
            });
        })();
        </script>
    <?php endif; ?>

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
