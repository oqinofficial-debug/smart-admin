<div class="card">
    <h2>Preview Import</h2>
    <p class="text-muted">
        Ditemukan <strong><?php echo count($header_row); ?></strong> kolom dan
        <strong><?php echo $total_rows; ?></strong> baris data. Periksa pemetaan kolom
        di bawah ini sebelum data disimpan. Kolom yang tidak dipetakan ke field manapun
        akan diabaikan.
    </p>

    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>

    <?php echo form_open('import/process'); ?>

        <h3 style="font-size:14px; margin-top:0;">Pemetaan Kolom</h3>
        <table class="table-list">
            <thead>
                <tr>
                    <th>Kolom Excel</th>
                    <th>Header di File</th>
                    <th>Dipetakan ke Field</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($header_row as $col_letter => $header_text): ?>
                    <?php $mapped = isset($auto_mapping[$col_letter]) ? $auto_mapping[$col_letter] : null; ?>
                    <tr>
                        <td><?php echo htmlspecialchars($col_letter); ?></td>
                        <td>
                            <?php echo htmlspecialchars($header_text); ?>
                            <?php if (!$mapped): ?>
                                <br><span class="text-muted" style="font-size:11px;">tidak ada alias yang cocok</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <select name="mapping[<?php echo htmlspecialchars($col_letter); ?>]" class="form-control">
                                <option value="">-- Abaikan kolom ini --</option>
                                <?php foreach ($fields as $field_key => $info): ?>
                                    <option value="<?php echo htmlspecialchars($field_key); ?>"
                                        <?php echo ($mapped === $field_key) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($info['label']); ?><?php echo $info['required'] ? ' (wajib)' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3 style="font-size:14px;">Contoh Data (5 baris pertama)</h3>
        <div style="overflow-x:auto;">
        <table class="table-list">
            <thead>
                <tr>
                    <?php foreach ($header_row as $col_letter => $header_text): ?>
                        <th><?php echo htmlspecialchars($header_text ?: $col_letter); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($preview_rows as $row): ?>
                    <tr>
                        <?php foreach ($header_row as $col_letter => $header_text): ?>
                            <td><?php echo htmlspecialchars(isset($row[$col_letter]) ? $row[$col_letter] : ''); ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <button type="submit" class="btn btn-primary">Konfirmasi &amp; Simpan <?php echo $total_rows; ?> Baris</button>
        <a href="<?php echo base_url('import'); ?>" class="btn" style="background:#e9ecef;">Batal</a>

    <?php echo form_close(); ?>
</div>
