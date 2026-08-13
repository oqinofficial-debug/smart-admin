<div class="card">
    <h2>Production Monitoring Report</h2>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success"><?php echo $this->session->flashdata('success'); ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>

    <form method="get" action="<?php echo base_url('monitoring-produksi'); ?>" style="margin-bottom:12px;">
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
                <th>Qty</th>
                <?php foreach ($departments as $dept): ?>
                    <th title="Kode: <?php echo htmlspecialchars($dept['department_code']); ?>">
                        <?php echo htmlspecialchars($dept['department_name']); ?>
                    </th>
                <?php endforeach; ?>
                <th>Kirim Bulan Ini</th>
                <th>Total Kirim s/d Periode Ini</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($jf_matrix)): ?>
                <tr><td colspan="<?php echo 6 + count($departments); ?>">Belum ada data monitoring untuk periode ini.</td></tr>
            <?php else: ?>
                <?php foreach ($jf_matrix as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['jf']); ?></td>
                        <td><?php echo htmlspecialchars($row['product']); ?></td>
                        <td><?php echo htmlspecialchars($row['qty']); ?></td>
                        <?php foreach ($departments as $dept): ?>
                            <td style="text-align:center;">
                                <?php if (in_array((int) $dept['id'], $row['department_ids_jalan'], true)): ?>
                                    <span class="badge badge-active" title="Jatah jalan di <?php echo htmlspecialchars($dept['department_name']); ?> periode ini">&#10003;</span>
                                <?php else: ?>
                                    &ndash;
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <td style="text-align:right;">
                            <?php echo ($row['delivery_bulan_ini'] > 0) ? htmlspecialchars($row['delivery_bulan_ini']) : '-'; ?>
                        </td>
                        <td style="text-align:right;"><?php echo htmlspecialchars($row['total_kirim_s_d_periode']); ?></td>
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