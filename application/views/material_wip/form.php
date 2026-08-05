<div class="card">
    <h2><?php echo $material ? 'Edit Material WIP' : 'Tambah Material WIP'; ?></h2>

    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>
    <?php if (validation_errors()): ?>
        <div class="alert alert-danger"><?php echo validation_errors(); ?></div>
    <?php endif; ?>

    <?php echo form_open(current_url()); ?>

        <div class="form-group">
            <label>Kode Material</label>
            <input type="text" name="kode_material" class="form-control"
                   value="<?php echo set_value('kode_material', $material['kode_material'] ?? ''); ?>"
                   maxlength="50">
        </div>

        <div class="form-group">
            <label>Nama Material</label>
            <input type="text" name="nama_material" class="form-control"
                   value="<?php echo set_value('nama_material', $material['nama_material'] ?? ''); ?>"
                   maxlength="150">
        </div>

        <div class="form-group">
            <label>JF Asal (sisa/ex dari JF ini)</label>
            <?php $jf_asal_id = set_value('jf_asal_id', $material['jf_asal_id'] ?? ''); ?>
            <select name="jf_asal_id" class="form-control">
                <option value="">-- Pilih JF Asal --</option>
                <?php foreach ($jf_list as $jf): ?>
                    <option value="<?php echo $jf['id']; ?>" <?php echo ((string) $jf_asal_id === (string) $jf['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($jf['jf']) . ' (' . htmlspecialchars($jf['status_jf']) . ')'; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" value="1"
                       <?php echo (!$material || $material['is_active']) ? 'checked' : ''; ?>>
                Aktif
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?php echo base_url('material_wip'); ?>" class="btn" style="background:#e9ecef;">Batal</a>

    <?php echo form_close(); ?>
</div>
