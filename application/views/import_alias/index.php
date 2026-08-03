<div class="card">
    <h2>Kelola Alias Kolom Import</h2>
    <p class="text-muted">
        Tiap field tujuan bisa punya beberapa alias -- nama kolom di file Excel yang
        akan dikenali otomatis sebagai field tersebut saat import. Field <code>field_key</code>
        sendiri tidak bisa diganti karena dipakai langsung di kode program, tapi label,
        status wajib/aktif, dan daftar alias-nya bisa diatur bebas.
    </p>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success"><?php echo $this->session->flashdata('success'); ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>

    <a href="<?php echo base_url('import'); ?>">&larr; Kembali ke Import Data</a>
</div>

<?php foreach ($kolom as $k): ?>
    <div class="card">
        <h2 style="font-size:15px;">
            <?php echo htmlspecialchars($k['field_label']); ?>
            <span class="text-muted" style="font-weight:normal; font-size:12px;">(<?php echo htmlspecialchars($k['field_key']); ?>)</span>

            <?php if ($k['is_required']): ?>
                <span class="badge badge-master">Wajib</span>
            <?php endif; ?>
            <?php if (!$k['is_active']): ?>
                <span class="badge badge-inactive">Nonaktif</span>
            <?php endif; ?>

            <a href="<?php echo base_url('import/alias/edit/' . $k['id']); ?>" style="float:right; font-size:12px;">Edit Field</a>
        </h2>

        <table class="table-list" style="margin-top:8px;">
            <thead>
                <tr>
                    <th>Alias</th>
                    <th style="width:80px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($k['aliases'])): ?>
                    <tr><td colspan="2" class="text-muted">Belum ada alias, hanya nama field ini sendiri yang dikenali.</td></tr>
                <?php else: ?>
                    <?php foreach ($k['aliases'] as $a): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($a['alias_text']); ?></td>
                            <td>
                                <a href="<?php echo base_url('import/alias/delete/' . $a['id']); ?>"
                                   onclick="return confirm('Hapus alias ini?');">Hapus</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php echo form_open('import/alias/add'); ?>
            <input type="hidden" name="kolom_id" value="<?php echo $k['id']; ?>">
            <div style="display:flex; gap:8px; align-items:flex-end;">
                <div class="form-group" style="flex:1; margin-bottom:0;">
                    <label>Tambah alias baru</label>
                    <input type="text" name="alias_text" class="form-control" maxlength="100"
                           placeholder="mis. Tgl Produksi" required>
                </div>
                <button type="submit" class="btn btn-primary">Tambah</button>
            </div>
        <?php echo form_close(); ?>
    </div>
<?php endforeach; ?>
