<div class="card">
    <h2>Kelengkapan Setor</h2>
    <p style="color:#666;margin-top:-8px;">
        JF yang biasanya melapor tapi belum ada laporan di periode ini, dibandingkan dengan histori periode-periode sebelumnya.
        JF yang baru pertama kali muncul (belum punya histori) tidak ikut ditampilkan di sini karena sistem belum bisa membedakan
        "belum setor" dari "memang belum pernah lewat sini".
    </p>

    <form method="get" action="<?php echo base_url('kelengkapan-setor'); ?>" style="margin-bottom:12px;">
        <label for="periode">Periode:</label>
        <input type="month" id="periode" name="periode"
               value="<?php echo htmlspecialchars($periode); ?>">
        <button type="submit" class="btn btn-primary">Tampilkan</button>
    </form>

    <table class="table-list">
        <thead>
            <tr>
                <th>No. JF</th>
                <th>Produk</th>
                <th>Belum Setor (Department / Proses)</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($report)): ?>
                <tr><td colspan="4">Tidak ada outstanding -- semua JF yang punya histori sudah lengkap setor periode ini.</td></tr>
            <?php else: ?>
                <?php foreach ($report as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['jf']); ?></td>
                        <td><?php echo htmlspecialchars($row['product']); ?></td>
                        <td>
                            <?php foreach ($row['belum_input'] as $b): ?>
                                <span class="badge badge-inactive" style="margin:2px;display:inline-block;">
                                    <?php echo htmlspecialchars($b['department_nama']); ?> &mdash; <?php echo htmlspecialchars($b['proses_nama']); ?>
                                </span>
                            <?php endforeach; ?>
                        </td>
                        <td>
                            <a href="<?php echo base_url('monitoring-produksi/detail/' . $row['jf_id'] . '/' . $periode); ?>">
                                Detail
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
