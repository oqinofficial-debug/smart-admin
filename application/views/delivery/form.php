<div class="card">
    <h2><?php echo $delivery_row ? 'Edit Delivery Record' : 'Tambah Delivery Record'; ?></h2>

    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>
    <?php if (validation_errors()): ?>
        <div class="alert alert-danger"><?php echo validation_errors(); ?></div>
    <?php endif; ?>

    <?php echo form_open(current_url()); ?>

        <div class="form-group">
            <label>No. JF</label>
            <?php $jf_id = set_value('jf_id', $delivery_row['jf_id'] ?? ''); ?>
            <select name="jf_id" class="form-control">
                <option value="">-- Pilih JF --</option>
                <?php foreach ($jf_list as $jf): ?>
                    <option value="<?php echo $jf['id']; ?>" <?php echo ((string) $jf_id === (string) $jf['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($jf['jf']); ?><?php echo $jf['product'] ? ' - ' . htmlspecialchars($jf['product']) : ''; ?>
                        (<?php echo htmlspecialchars($jf['status_jf']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Tanggal Kirim</label>
            <input type="date" name="tanggal_kirim" class="form-control"
                   value="<?php echo set_value('tanggal_kirim', $delivery_row['tanggal_kirim'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>Aktual Kirim</label>
            <input type="date" name="aktual_kirim" class="form-control"
                   value="<?php echo set_value('aktual_kirim', $delivery_row['aktual_kirim'] ?? ''); ?>">
            <small class="text-muted">Kosongkan kalau belum benar-benar terkirim.</small>
        </div>

        <div class="form-group">
            <label>No. SP</label>
            <input type="text" name="no_sp" class="form-control"
                   value="<?php echo set_value('no_sp', $delivery_row['no_sp'] ?? ''); ?>" maxlength="100">
        </div>

        <div class="form-group">
            <label>Jenis SP</label>
            <input type="text" name="jenis_sp" class="form-control"
                   value="<?php echo set_value('jenis_sp', $delivery_row['jenis_sp'] ?? ''); ?>" maxlength="50">
        </div>

        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?php echo base_url('delivery'); ?>" class="btn" style="background:#e9ecef;">Batal</a>

    <?php echo form_close(); ?>
</div>
