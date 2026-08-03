<div class="card">
    <h2><?php echo $item ? 'Edit' : 'Tambah'; ?> <?php echo htmlspecialchars($label); ?></h2>

    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>
    <?php if (validation_errors()): ?>
        <div class="alert alert-danger"><?php echo validation_errors(); ?></div>
    <?php endif; ?>

    <?php echo form_open(current_url()); ?>

        <div class="form-group">
            <label>Kode</label>
            <input type="text" name="kode" class="form-control"
                   value="<?php echo set_value('kode', $item['kode'] ?? ''); ?>" maxlength="50">
        </div>

        <div class="form-group">
            <label>Nama</label>
            <input type="text" name="nama" class="form-control"
                   value="<?php echo set_value('nama', $item['nama'] ?? ''); ?>" maxlength="200">
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" value="1"
                       <?php echo (!$item || $item['is_active']) ? 'checked' : ''; ?>>
                Aktif
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?php echo base_url('masterdata/index/' . $type); ?>" class="btn" style="background:#e9ecef;">Batal</a>

    <?php echo form_close(); ?>
</div>
