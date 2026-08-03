<div class="card">
    <h2><?php echo $karyawan ? 'Edit Karyawan' : 'Tambah Karyawan'; ?></h2>

    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>
    <?php if (validation_errors()): ?>
        <div class="alert alert-danger"><?php echo validation_errors(); ?></div>
    <?php endif; ?>

    <?php echo form_open(current_url()); ?>

        <div class="form-group">
            <label>NIK</label>
            <input type="text" name="nik" class="form-control"
                   value="<?php echo set_value('nik', $karyawan['nik'] ?? ''); ?>" maxlength="50">
        </div>

        <div class="form-group">
            <label>Nama</label>
            <input type="text" name="nama" class="form-control"
                   value="<?php echo set_value('nama', $karyawan['nama'] ?? ''); ?>" maxlength="100">
        </div>

        <div class="form-group">
            <label>Status Kepegawaian</label>
            <?php $status = set_value('status_kepegawaian', $karyawan['status_kepegawaian'] ?? ''); ?>
            <select name="status_kepegawaian" class="form-control">
                <option value="">-- Pilih Status --</option>
                <option value="HARIAN" <?php echo ($status === 'HARIAN') ? 'selected' : ''; ?>>Harian</option>
                <option value="BORONG" <?php echo ($status === 'BORONG') ? 'selected' : ''; ?>>Borong</option>
            </select>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" value="1"
                       <?php echo (!$karyawan || $karyawan['is_active']) ? 'checked' : ''; ?>>
                Aktif
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?php echo base_url('karyawan'); ?>" class="btn" style="background:#e9ecef;">Batal</a>

    <?php echo form_close(); ?>
</div>
