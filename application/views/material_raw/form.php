<div class="card">
    <h2><?php echo $material ? 'Edit Material RAW' : 'Tambah Material RAW'; ?></h2>

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
            <label>
                <input type="checkbox" name="is_active" value="1"
                       <?php echo (!$material || $material['is_active']) ? 'checked' : ''; ?>>
                Aktif
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?php echo base_url('material_raw'); ?>" class="btn" style="background:#e9ecef;">Batal</a>

    <?php echo form_close(); ?>
</div>
