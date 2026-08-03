<div class="card">
    <h2><?php echo $department ? 'Edit Departemen' : 'Tambah Departemen'; ?></h2>

    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>
    <?php if (validation_errors()): ?>
        <div class="alert alert-danger"><?php echo validation_errors(); ?></div>
    <?php endif; ?>

    <?php echo form_open(current_url()); ?>

        <div class="form-group">
            <label>Kode Departemen</label>
            <input type="text" name="department_code" class="form-control"
                   value="<?php echo set_value('department_code', $department['department_code'] ?? ''); ?>"
                   maxlength="50">
        </div>

        <div class="form-group">
            <label>Nama Departemen</label>
            <input type="text" name="department_name" class="form-control"
                   value="<?php echo set_value('department_name', $department['department_name'] ?? ''); ?>"
                   maxlength="100">
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" value="1"
                       <?php echo (!$department || $department['is_active']) ? 'checked' : ''; ?>>
                Aktif
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?php echo base_url('department'); ?>" class="btn" style="background:#e9ecef;">Batal</a>

    <?php echo form_close(); ?>
</div>
