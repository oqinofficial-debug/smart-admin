<div class="card">
    <h2>Import Data Laporan Produksi</h2>
    <p class="text-muted">
        Upload file berisi data laporan produksi (format .xlsx, .xls, .csv, atau .txt).
        Kolom di file akan dicocokkan otomatis ke field tujuan berdasarkan alias yang
        terdaftar, dan bisa dikoreksi manual sebelum data disimpan.
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
                <label>Nama Laporan</label>
                <select name="nama_laporan_id" class="form-control" required>
                    <option value="" selected disabled>-- Pilih Nama Laporan --</option>
                    <?php $current_dept = null; ?>
                    <?php foreach ($nama_laporan_options as $opt): ?>
                        <?php if ($current_dept !== $opt['department_id']): ?>
                            <?php if ($current_dept !== null): ?></optgroup><?php endif; ?>
                            <optgroup label="<?php echo htmlspecialchars($opt['department_name'] ?: '-'); ?>">
                            <?php $current_dept = $opt['department_id']; ?>
                        <?php endif; ?>
                        <option value="<?php echo (int) $opt['id']; ?>">
                            <?php echo htmlspecialchars($opt['nama']); ?>
                        </option>
                    <?php endforeach; ?>
                    <?php if ($current_dept !== null): ?></optgroup><?php endif; ?>
                </select>
                <p class="text-muted" style="margin-top:4px;">
                    Wajib dipilih. Inilah identitas laporan yang jadi acuan: kalau file
                    diimport ulang dengan nama laporan &amp; periode yang sama, data lama
                    bisa langsung di-replace. Belum ada di daftar?
                    <a href="<?php echo base_url('master-file'); ?>">Kelola di Master File</a>.
                </p>
                <?php if (empty($nama_laporan_options)): ?>
                    <div class="alert alert-warning" style="margin-top:8px;">
                        Belum ada nama laporan aktif untuk departemen Anda. Hubungi admin untuk
                        menambahkannya di menu Master File terlebih dahulu.
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>File (.xlsx, .xls, .csv, .txt)</label>
                <input type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required>
                <p class="text-muted" style="margin-top:4px;">
                    Untuk file xlsx/xls dengan lebih dari 1 sheet, Anda akan diminta memilih sheet mana yang mau diimport setelah upload.
                </p>
            </div>

            <div class="form-group">
                <label>Cakupan Data yang Diimport</label>

                <label style="display:block;font-weight:normal;">
                    <input type="radio" name="import_mode" value="periode" checked onchange="toggleImportMode()"> Satu periode bulanan tertentu (YYYY-MM)
                </label>
                <label style="display:block;font-weight:normal;">
                    <input type="radio" name="import_mode" value="range" onchange="toggleImportMode()"> Rentang tanggal custom (bisa lintas periode)
                </label>
                <label style="display:block;font-weight:normal;">
                    <input type="radio" name="import_mode" value="all" onchange="toggleImportMode()"> Semua data di file (tanpa filter periode/tanggal)
                </label>
            </div>

            <div class="form-group" id="mode-periode">
                <label>Periode (Tahun-Bulan)</label>
                <input type="month" name="periode" value="<?php echo htmlspecialchars($default_periode); ?>">
                <p class="text-muted" style="margin-top:4px;">
                    Sudah diisi otomatis dengan periode kemarin (H-1), silakan ganti kalau perlu.
                </p>
                <label style="display:block;font-weight:normal;margin-top:6px;">
                    <input type="checkbox" name="replace_periode" value="1">
                    Timpa data periode ini (hapus SATU periode utuh dari hasil import
                    sebelumnya untuk laporan yang sama, sebelum import ulang)
                </label>
                <p class="text-muted" style="margin-top:4px;">
                    Baris di file yang tanggalnya di luar periode ini akan otomatis dilewati (tidak dianggap error).
                    Data yang diinput manual (bukan hasil import) tidak akan ikut terhapus.
                </p>
            </div>

            <div class="form-group" id="mode-range" style="display:none;">
                <label>Dari Tanggal</label>
                <input type="date" name="tanggal_mulai" value="<?php echo htmlspecialchars($default_tanggal); ?>">
                <label style="margin-top:6px;">Sampai Tanggal</label>
                <input type="date" name="tanggal_selesai" value="<?php echo htmlspecialchars($default_tanggal); ?>">
                <label style="display:block;font-weight:normal;margin-top:6px;">
                    <input type="checkbox" name="replace_range" value="1">
                    Timpa data rentang tanggal ini (hapus PER TANGGAL dari hasil import
                    sebelumnya untuk laporan yang sama, sesuai rentang di atas -- bisa
                    lintas periode/bulan)
                </label>
                <p class="text-muted" style="margin-top:4px;">
                    Beda dari mode periode: yang dihapus hanya tanggal-tanggal di dalam
                    rentang ini, bukan satu bulan utuh, jadi rentangnya boleh memotong
                    atau mencakup lebih dari satu periode. Baris di file yang tanggalnya
                    di luar rentang ini akan otomatis dilewati (tidak dianggap error).
                </p>
            </div>

            <button type="submit" class="btn btn-primary">Upload &amp; Lihat Preview</button>
        <?php echo form_close(); ?>

        <script>
        function toggleImportMode() {
            var mode = document.querySelector('input[name="import_mode"]:checked').value;
            document.getElementById('mode-periode').style.display = (mode === 'periode') ? 'block' : 'none';
            document.getElementById('mode-range').style.display = (mode === 'range') ? 'block' : 'none';
        }
        </script>
    <?php else: ?>
        <div class="alert alert-warning">Anda tidak memiliki hak untuk mengimport data.</div>
    <?php endif; ?>

    <?php if (!empty($access['can_edit'])): ?>
        <p style="margin-top:16px;">
            <a href="<?php echo base_url('import/alias'); ?>">&#9881; Kelola Alias Kolom</a>
        </p>
    <?php endif; ?>
</div>

<?php if (!empty($riwayat_import)): ?>
<div class="card">
    <h2>Riwayat Import Terakhir</h2>
    <table class="table-list">
        <thead>
            <tr>
                <th>Waktu</th>
                <th>File</th>
                <th>Nama Laporan</th>
                <th>Mode</th>
                <th>Periode</th>
                <th>Sukses</th>
                <th>Gagal</th>
                <th>Dilewati</th>
                <th>Oleh</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($riwayat_import as $b): ?>
                <tr>
                    <td><?php echo htmlspecialchars($b['created_at']); ?></td>
                    <td><?php echo htmlspecialchars($b['nama_file']); ?> <span class="text-muted">(.<?php echo htmlspecialchars($b['format_file']); ?><?php echo $b['sheet_name'] ? ' - ' . htmlspecialchars($b['sheet_name']) : ''; ?>)</span></td>
                    <td><?php echo htmlspecialchars($b['nama_laporan'] ?: '-'); ?></td>
                    <td>
                        <?php echo htmlspecialchars($b['mode']); ?>
                        <?php echo !empty($b['replace_periode']) ? ' (timpa periode)' : ''; ?>
                        <?php echo !empty($b['replace_range']) ? ' (timpa rentang)' : ''; ?>
                    </td>
                    <td><?php echo htmlspecialchars($b['periode'] ?: ($b['tanggal_mulai'] ? $b['tanggal_mulai'] . ' s/d ' . $b['tanggal_selesai'] : '-')); ?></td>
                    <td><?php echo (int) $b['sukses']; ?></td>
                    <td><?php echo (int) $b['gagal']; ?></td>
                    <td><?php echo (int) $b['dilewati']; ?></td>
                    <td><?php echo htmlspecialchars($b['nama_user'] ?: '-'); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="card">
    <h2>Field yang Bisa Diisi</h2>
    <p class="text-muted">
        Baris pertama file harus berisi nama kolom (header). Nama kolom boleh
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
