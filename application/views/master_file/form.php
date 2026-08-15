<div class="card">
    <h2><?php echo $item ? 'Edit' : 'Tambah'; ?> Nama Laporan</h2>

    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>
    <?php if (validation_errors()): ?>
        <div class="alert alert-danger"><?php echo validation_errors(); ?></div>
    <?php endif; ?>

    <?php echo form_open(current_url()); ?>

        <div class="form-group">
            <label>Departemen</label>
            <select name="department_id" class="form-control" required>
                <option value="">-- Pilih Departemen --</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo (int) $dept['id']; ?>"
                        <?php echo (set_value('department_id', $item['department_id'] ?? '') == $dept['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($dept['department_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Kode</label>
            <input type="text" name="kode" class="form-control"
                   value="<?php echo set_value('kode', $item['kode'] ?? ''); ?>" maxlength="50">
            <p class="text-muted" style="margin-top:4px;">Cukup unik di dalam departemen yang sama, mis. "HARIAN-CETAK".</p>
        </div>

        <div class="form-group">
            <label>Nama Laporan</label>
            <input type="text" name="nama" class="form-control"
                   value="<?php echo set_value('nama', $item['nama'] ?? ''); ?>" maxlength="200">
            <p class="text-muted" style="margin-top:4px;">Ditampilkan ke user di dropdown "Nama Laporan" saat Import Data, mis. "Laporan Produksi Harian - Cetak".</p>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" value="1"
                       <?php echo (!$item || $item['is_active']) ? 'checked' : ''; ?>>
                Aktif (muncul di dropdown Import Data)
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?php echo base_url('master-file'); ?>" class="btn" style="background:#e9ecef;">Batal</a>

    <?php echo form_close(); ?>
</div>
