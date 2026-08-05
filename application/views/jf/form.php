<div class="card">
    <h2><?php echo $jf_row ? 'Edit JF' : 'Tambah JF'; ?></h2>

    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>
    <?php if (validation_errors()): ?>
        <div class="alert alert-danger"><?php echo validation_errors(); ?></div>
    <?php endif; ?>

    <?php echo form_open(current_url()); ?>

        <div class="form-group">
            <label>Kode JF</label>
            <input type="text" name="jf" class="form-control"
                   value="<?php echo set_value('jf', $jf_row['jf'] ?? ''); ?>" maxlength="50">
        </div>

        <div class="form-group">
            <label>Product</label>
            <input type="text" name="product" class="form-control"
                   value="<?php echo set_value('product', $jf_row['product'] ?? ''); ?>" maxlength="200">
        </div>

        <div class="form-group">
            <label>Qty</label>
            <input type="text" name="qty" class="form-control"
                   value="<?php echo set_value('qty', $jf_row['qty'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>BAPOB</label>
            <input type="text" name="bapob" class="form-control"
                   value="<?php echo set_value('bapob', $jf_row['bapob'] ?? ''); ?>" maxlength="100">
        </div>

        <div class="form-group">
            <label>Chip</label>
            <input type="text" name="chip" class="form-control"
                   value="<?php echo set_value('chip', $jf_row['chip'] ?? ''); ?>" maxlength="100">
        </div>

        <div class="form-group">
            <label>Customer</label>
            <input type="text" name="customer" class="form-control"
                   value="<?php echo set_value('customer', $jf_row['customer'] ?? ''); ?>" maxlength="100">
        </div>

        <div class="form-group">
            <label>PO</label>
            <input type="text" name="po" class="form-control"
                   value="<?php echo set_value('po', $jf_row['po'] ?? ''); ?>" maxlength="100">
        </div>

        <div class="form-group">
            <label>Kelompok Produk</label>
            <?php $kp_id = set_value('kelompok_produk_id', $jf_row['kelompok_produk_id'] ?? ''); ?>
            <select name="kelompok_produk_id" class="form-control">
                <option value="">-- Tidak ditentukan --</option>
                <?php foreach ($kelompok_produk as $kp): ?>
                    <option value="<?php echo $kp['id']; ?>" <?php echo ((string) $kp_id === (string) $kp['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($kp['nama']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($jf_row): ?>
            <div class="form-group">
                <label>Status JF</label>
                <?php $status = set_value('status_jf', $jf_row['status_jf'] ?? 'AKTIF'); ?>
                <select name="status_jf" class="form-control">
                    <option value="AKTIF" <?php echo ($status === 'AKTIF') ? 'selected' : ''; ?>>AKTIF</option>
                    <option value="FINAL" <?php echo ($status === 'FINAL') ? 'selected' : ''; ?>>FINAL</option>
                </select>
                <small class="text-muted">
                    Biasanya diubah lewat tombol "Jadikan Final" di list, bukan di sini.
                    Field ini hanya untuk koreksi manual bila diperlukan.
                </small>
            </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?php echo base_url('jf'); ?>" class="btn" style="background:#e9ecef;">Batal</a>

    <?php echo form_close(); ?>
</div>
