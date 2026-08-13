<div class="card">
    <h2>Detail Monitoring — <?php echo htmlspecialchars($jf ? $jf['jf'] : '-'); ?> (<?php echo htmlspecialchars($periode); ?>)</h2>

    <p>
        <a href="<?php echo base_url('monitoring-produksi?periode=' . $periode); ?>">&laquo; Kembali ke daftar</a>
    </p>

    <div id="alert-box"></div>

    <?php if (!empty($summary)): ?>
        <div class="card" style="margin-bottom:16px;border:1px solid #ddd;background:#fafafa;">
            <h3>Ringkasan Produksi — Pemakaian Material s/d Kirim</h3>

            <h4>Total Realisasi (semua proses)</h4>
            <table class="table-list">
                <thead>
                    <tr>
                        <th>Input Qty</th><th>QC Sampling</th><th>Waste</th>
                        <th>Dead</th><th>Error</th><th>Good Qty</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo htmlspecialchars($summary['total_realisasi']['input_qty']); ?></td>
                        <td><?php echo htmlspecialchars($summary['total_realisasi']['qc_sampling']); ?></td>
                        <td><?php echo htmlspecialchars($summary['total_realisasi']['waste']); ?></td>
                        <td><?php echo htmlspecialchars($summary['total_realisasi']['dead']); ?></td>
                        <td><?php echo htmlspecialchars($summary['total_realisasi']['error']); ?></td>
                        <td><?php echo htmlspecialchars($summary['total_realisasi']['good_qty']); ?></td>
                    </tr>
                </tbody>
            </table>

            <h4 style="margin-top:16px;">Pemakaian RAW</h4>
            <table class="table-list">
                <thead>
                    <tr><th>Kode</th><th>Material</th><th>Total Qty Pakai</th><th>Satuan</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($summary['raw_usage'])): ?>
                        <tr><td colspan="4">Belum ada pemakaian RAW.</td></tr>
                    <?php else: ?>
                        <?php foreach ($summary['raw_usage'] as $ru): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ru['kode_material']); ?></td>
                                <td><?php echo htmlspecialchars($ru['nama_material']); ?></td>
                                <td><?php echo htmlspecialchars($ru['total_qty']); ?></td>
                                <td><?php echo htmlspecialchars($ru['satuan']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <h4 style="margin-top:16px;">Alur WIP</h4>
            <table class="table-list">
                <thead>
                    <tr>
                        <th>Dihasilkan (status WIP Stok)</th>
                        <th>Dipakai proses ini (dari sumber manapun)</th>
                        <th>Sudah dipakai proses lain</th>
                        <th>Sisa Stok WIP</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo htmlspecialchars($summary['wip']['dihasilkan']); ?></td>
                        <td><?php echo htmlspecialchars($summary['wip']['masuk']); ?></td>
                        <td><?php echo htmlspecialchars($summary['wip']['keluar']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($summary['wip']['sisa']); ?>
                            <?php if ($summary['wip']['sisa'] < 0): ?>
                                <span class="badge badge-inactive">Minus — cek cantolan</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <h4 style="margin-top:16px;">Alur FG (Finish Good)</h4>
            <table class="table-list">
                <thead>
                    <tr>
                        <th>Dihasilkan (status FG Stok)</th>
                        <th>Dipakai bahan proses lain</th>
                        <th>Dikirim ke customer</th>
                        <th>Sisa Stok FG</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo htmlspecialchars($summary['fg']['dihasilkan']); ?></td>
                        <td><?php echo htmlspecialchars($summary['fg']['dipakai_proses_lain']); ?></td>
                        <td><?php echo htmlspecialchars($summary['fg']['dikirim']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($summary['fg']['sisa']); ?>
                            <?php if ($summary['fg']['sisa'] < 0): ?>
                                <span class="badge badge-inactive">Minus — cek cantolan/kirim</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p style="margin-top:8px;font-size:0.85em;color:#666;">
                Sisa Stok WIP/FG di sini sudah menggabungkan pemakaian dari modul Monitoring Produksi
                (cantolan bahan proses lain) dan modul Delivery (alokasi WIP antar-proses / kirim ke customer),
                supaya angkanya konsisten dengan yang dipakai saat validasi di kedua modul tersebut.
            </p>
        </div>
    <?php endif; ?>

    <?php foreach ($rows as $row): ?>
        <div class="card" style="margin-bottom:16px;border:1px solid #ddd;" data-monitoring-id="<?php echo $row['id']; ?>">
            <h3>
                <?php echo htmlspecialchars($row['department_nama']); ?> —
                <?php echo htmlspecialchars($row['proses_nama']); ?>
                <?php if (!$row['is_match']): ?>
                    <span class="badge badge-inactive">Realisasi ≠ Agregat</span>
                <?php endif; ?>
            </h3>

            <table class="table-list">
                <thead>
                    <tr>
                        <th>Kolom</th>
                        <th>Hasil Import (agg)</th>
                        <th>
                            Realisasi
                            <?php if (!empty($access['can_edit'])): ?>
                                <span class="field-lock-note" style="background:rgba(63,185,80,0.15); color:#3fb950; border-color:rgba(63,185,80,0.4);">✎ Bisa diedit</span>
                            <?php else: ?>
                                <span class="field-lock-note">Tidak bisa diedit</span>
                            <?php endif; ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array(
                        'input_qty'    => 'Input Qty',
                        'qc_sampling'  => 'QC Sampling',
                        'waste'        => 'Waste',
                        'dead'         => 'Dead',
                        'error'        => 'Error',
                        'good_qty'     => 'Good Qty',
                    ) as $col => $label): ?>
                        <tr>
                            <td><?php echo $label; ?></td>
                            <td><?php echo htmlspecialchars($row['agg_' . $col]); ?></td>
                            <td>
                                <input type="number" step="any"
                                       class="realisasi-input<?php echo empty($access['can_edit']) ? ' field-locked' : ''; ?>"
                                       data-col="<?php echo $col; ?>"
                                       value="<?php echo htmlspecialchars($row['realisasi_' . $col]); ?>"
                                       <?php echo empty($access['can_edit']) ? 'disabled' : ''; ?>>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (!empty($access['can_edit'])): ?>
                <button type="button" class="btn btn-primary btn-simpan-realisasi">Simpan Realisasi</button>
            <?php endif; ?>

            <div style="margin-top:12px;">
                <label>
                    Status Output:
                    <?php if (empty($access['can_edit'])): ?>
                        <span class="field-lock-note">Tidak bisa diedit</span>
                    <?php endif; ?>
                </label>
                <select class="status-output-select<?php echo empty($access['can_edit']) ? ' field-locked' : ''; ?>"
                        <?php echo empty($access['can_edit']) ? 'disabled' : ''; ?>>
                    <option value="" <?php echo empty($row['status_output']) ? 'selected' : ''; ?>>- Belum ditentukan -</option>
                    <option value="PROSES_SELANJUTNYA" <?php echo ($row['status_output'] === 'PROSES_SELANJUTNYA') ? 'selected' : ''; ?>>Proses Selanjutnya</option>
                    <option value="WIP_STOK" <?php echo ($row['status_output'] === 'WIP_STOK') ? 'selected' : ''; ?>>WIP Stok</option>
                    <option value="FINISH_GOOD_STOK" <?php echo ($row['status_output'] === 'FINISH_GOOD_STOK') ? 'selected' : ''; ?>>Finish Good Stok</option>
                </select>
            </div>

            <h4 style="margin-top:16px;">Cantolan Bahan (RAW/WIP/FG)</h4>
            <table class="table-list pemakaian-list">
                <thead>
                    <tr>
                        <th>Jenis</th>
                        <th>Material / Sumber</th>
                        <th>Qty Pakai</th>
                        <th>Satuan</th>
                        <th>Keterangan</th>
                        <?php if (!empty($access['can_delete'])): ?><th>Aksi</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $pemakaian = $this->Pemakaian_material_model->get_by_monitoring($row['id']); ?>
                    <?php if (empty($pemakaian)): ?>
                        <tr><td colspan="6">Belum ada cantolan bahan.</td></tr>
                    <?php else: ?>
                        <?php foreach ($pemakaian as $p): ?>
                            <tr data-pemakaian-id="<?php echo $p['id']; ?>">
                                <td><?php echo htmlspecialchars($p['jenis_material']); ?></td>
                                <td>
                                    <?php if ($p['jenis_material'] === 'RAW'): ?>
                                        <?php echo htmlspecialchars($p['raw_kode'] . ' - ' . $p['raw_nama']); ?>
                                    <?php else: ?>
                                        <?php // WIP maupun FG sama-sama merujuk baris trx_monitoring_produksi lain via sumber_monitoring_id ?>
                                        <?php echo htmlspecialchars($p['sumber_jf'] . ' / ' . $p['sumber_proses_nama'] . ' / ' . $p['sumber_department_nama'] . ' (' . $p['sumber_periode'] . ')'); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($p['qty_pakai']); ?></td>
                                <td><?php echo htmlspecialchars($p['satuan']); ?></td>
                                <td><?php echo htmlspecialchars($p['keterangan']); ?></td>
                                <?php if (!empty($access['can_delete'])): ?>
                                    <td><button type="button" class="btn btn-danger btn-hapus-pemakaian">Hapus</button></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if (!empty($access['can_input'])): ?>
                <div class="form-cantolan-bahan" style="margin-top:8px;">
                    <label>Jenis:</label>
                    <select class="cantolan-jenis">
                        <option value="RAW">RAW</option>
                        <option value="WIP">WIP</option>
                        <option value="FG">FG (stok Finish Good proses lain)</option>
                    </select>
                    <input type="text" class="cantolan-search" placeholder="Cari material/sumber..." autocomplete="off">
                    <input type="hidden" class="cantolan-ref-id">
                    <div class="cantolan-search-results" style="display:none;"></div>
                    <input type="number" step="any" class="cantolan-qty" placeholder="Qty pakai">
                    <input type="text" class="cantolan-satuan" placeholder="Satuan">
                    <input type="text" class="cantolan-keterangan" placeholder="Keterangan (opsional)">
                    <button type="button" class="btn btn-primary btn-tambah-cantolan">Tambah</button>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<script>
(function () {
    var baseUrl = '<?php echo base_url(); ?>';

    function showAlert(type, msg) {
        var box = document.getElementById('alert-box');
        box.innerHTML = '<div class="alert alert-' + type + '">' + msg + '</div>';
        setTimeout(function () { box.innerHTML = ''; }, 4000);
    }

    function post(url, data) {
        var body = new URLSearchParams(data);
        return fetch(baseUrl + url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body
        }).then(function (r) { return r.json(); });
    }

    // Simpan realisasi
    document.querySelectorAll('.btn-simpan-realisasi').forEach(function (btn) {
        btn.addEventListener('click', function (force) {
            var card = btn.closest('[data-monitoring-id]');
            var monitoringId = card.getAttribute('data-monitoring-id');
            var data = { monitoring_id: monitoringId };
            card.querySelectorAll('.realisasi-input').forEach(function (inp) {
                data['realisasi_' + inp.getAttribute('data-col')] = inp.value;
            });
            if (force === true) { data.force = '1'; }

            post('monitoring-produksi/realisasi_update', data).then(function (res) {
                if (res.success) {
                    showAlert('success', 'Realisasi tersimpan.');
                } else if (res.warning_keras) {
                    if (confirm(res.message + '\n\nLanjutkan simpan?')) {
                        btn.click.call(btn, true);
                        // re-trigger with force
                        var d2 = Object.assign({}, data, { force: '1' });
                        post('monitoring-produksi/realisasi_update', d2).then(function (res2) {
                            showAlert(res2.success ? 'success' : 'danger', res2.success ? 'Realisasi tersimpan.' : res2.message);
                        });
                    }
                } else {
                    showAlert('danger', res.message || 'Gagal menyimpan realisasi.');
                }
            });
        });
    });

    // Status output
    document.querySelectorAll('.status-output-select').forEach(function (sel) {
        sel.addEventListener('change', function () {
            var card = sel.closest('[data-monitoring-id]');
            var monitoringId = card.getAttribute('data-monitoring-id');
            post('monitoring-produksi/status_output_set', { monitoring_id: monitoringId, status: sel.value })
                .then(function (res) {
                    showAlert(res.success ? 'success' : 'danger', res.success ? 'Status output diperbarui.' : (res.message || 'Gagal.'));
                });
        });
    });

    // Hapus cantolan
    document.querySelectorAll('.btn-hapus-pemakaian').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!confirm('Hapus cantolan bahan ini?')) { return; }
            var tr = btn.closest('[data-pemakaian-id]');
            var id = tr.getAttribute('data-pemakaian-id');
            post('monitoring-produksi/pemakaian_delete/' + id, {}).then(function (res) {
                if (res.success) { tr.remove(); } else { showAlert('danger', res.message || 'Gagal menghapus.'); }
            });
        });
    });

    // Autocomplete + tambah cantolan bahan
    document.querySelectorAll('.form-cantolan-bahan').forEach(function (form) {
        var card = form.closest('[data-monitoring-id]');
        var monitoringId = card.getAttribute('data-monitoring-id');
        var jenisSel = form.querySelector('.cantolan-jenis');
        var searchInp = form.querySelector('.cantolan-search');
        var refIdInp = form.querySelector('.cantolan-ref-id');
        var resultsBox = form.querySelector('.cantolan-search-results');
        var timer = null;

        searchInp.addEventListener('input', function () {
            refIdInp.value = '';
            clearTimeout(timer);
            var q = searchInp.value;
            timer = setTimeout(function () {
                var endpoint = 'monitoring-produksi/search_wip';
                if (jenisSel.value === 'RAW') { endpoint = 'monitoring-produksi/search_raw'; }
                if (jenisSel.value === 'FG') { endpoint = 'monitoring-produksi/search_fg'; }
                fetch(baseUrl + endpoint + '?q=' + encodeURIComponent(q))
                    .then(function (r) { return r.json(); })
                    .then(function (list) {
                        resultsBox.innerHTML = '';
                        if (!list.length) { resultsBox.style.display = 'none'; return; }
                        list.forEach(function (item) {
                            var div = document.createElement('div');
                            var id, label;
                            if (jenisSel.value === 'RAW') {
                                id = item.id; label = item.kode_material + ' - ' + item.nama_material;
                            } else {
                                id = item.monitoring_id;
                                label = item.jf + ' / ' + item.proses_nama + ' / ' + item.department_nama +
                                    ' (' + item.periode + ') — sisa ' + item.sisa_qty;
                            }
                            div.textContent = label;
                            div.style.cursor = 'pointer';
                            div.addEventListener('click', function () {
                                refIdInp.value = id;
                                searchInp.value = label;
                                resultsBox.style.display = 'none';
                            });
                            resultsBox.appendChild(div);
                        });
                        resultsBox.style.display = 'block';
                    });
            }, 250);
        });

        form.querySelector('.btn-tambah-cantolan').addEventListener('click', function () {
            if (!refIdInp.value) {
                showAlert('danger', 'Pilih material/sumber dari daftar autocomplete dulu.');
                return;
            }
            var data = {
                monitoring_id: monitoringId,
                jenis_material: jenisSel.value,
                qty_pakai: form.querySelector('.cantolan-qty').value,
                satuan: form.querySelector('.cantolan-satuan').value,
                keterangan: form.querySelector('.cantolan-keterangan').value
            };
            if (jenisSel.value === 'RAW') {
                data.material_raw_id = refIdInp.value;
            } else {
                data.sumber_monitoring_id = refIdInp.value;
            }
            post('monitoring-produksi/pemakaian_add', data).then(function (res) {
                if (res.success) {
                    showAlert(res.warning ? 'danger' : 'success', res.warning || 'Cantolan bahan ditambahkan. Muat ulang halaman untuk lihat daftar.');
                    setTimeout(function () { location.reload(); }, 1200);
                } else {
                    showAlert('danger', res.message || 'Gagal menambah cantolan.');
                }
            });
        });
    });
})();
</script>