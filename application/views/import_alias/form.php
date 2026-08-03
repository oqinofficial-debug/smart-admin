<div class="card">
    <h2>Edit Field Import</h2>
    <p class="text-muted">
        Field key: <code><?php echo htmlspecialchars($kolom['field_key']); ?></code>
        (tidak bisa diubah)
    </p>

    <?php if (validation_errors()): ?>
        <div class="alert alert-danger"><?php echo validation_errors(); ?></div>
    <?php endif; ?>

    <?php echo form_open(current_url()); ?>

        <div class="form-group">
            <label>Label Field</label>
            <input type="text" name="field_label" class="form-control" maxlength="100"
                   value="<?php echo set_value('field_label', $kolom['field_label']); ?>">
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_required" value="1" <?php echo $kolom['is_required'] ? 'checked' : ''; ?>>
                Wajib diisi saat import
            </label>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" value="1" <?php echo $kolom['is_active'] ? 'checked' : ''; ?>>
                Aktif (ditawarkan sebagai pilihan mapping saat import)
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?php echo base_url('import/alias'); ?>" class="btn" style="background:#e9ecef;">Batal</a>

    <?php echo form_close(); ?>
</div>
