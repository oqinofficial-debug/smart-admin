<div class="card">
    <h2>Pilih Sheet</h2>
    <p class="text-muted">
        File yang Anda upload berisi lebih dari 1 sheet. Pilih sheet mana yang
        berisi data laporan produksi yang mau diimport.
    </p>

    <?php echo form_open('import/select-sheet'); ?>
        <div class="form-group">
            <?php foreach ($sheets as $s): ?>
                <label style="display:block;font-weight:normal;">
                    <input type="radio" name="sheet_name" value="<?php echo htmlspecialchars($s['name']); ?>" required>
                    <?php echo htmlspecialchars($s['name']); ?>
                </label>
            <?php endforeach; ?>
        </div>
        <button type="submit" class="btn btn-primary">Lanjut ke Preview</button>
        <a href="<?php echo base_url('import'); ?>" class="btn btn-secondary">Batal</a>
    <?php echo form_close(); ?>
</div>
