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
                <th>Customer</th>
                <th>Status JF</th>
                <th>Department</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($jf_list)): ?>
                <tr><td colspan="6">Belum ada data monitoring untuk periode ini.</td></tr>
            <?php else: ?>
                <?php foreach ($jf_list as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['jf']); ?></td>
                        <td><?php echo htmlspecialchars($row['product']); ?></td>
                        <td><?php echo htmlspecialchars($row['customer']); ?></td>
                        <td>
                            <span class="badge <?php echo ($row['status_jf'] === 'AKTIF') ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo htmlspecialchars($row['status_jf']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($row['department_nama']); ?></td>
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
