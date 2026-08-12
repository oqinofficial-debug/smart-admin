<div class="card">
    <h2>Master Data — <?php echo htmlspecialchars($label); ?></h2>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success"><?php echo $this->session->flashdata('success'); ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>

    <div style="margin-bottom:16px;">
        <?php foreach ($types as $key => $conf): ?>
            <a href="<?php echo base_url('masterdata/index/' . $key); ?>"
               class="btn <?php echo ($key === $type) ? 'btn-primary' : ''; ?>"
               style="<?php echo ($key === $type) ? '' : 'background:#e9ecef; color:#2c3e50;'; ?> margin-right:6px; margin-bottom:6px;">
                <?php echo htmlspecialchars($conf['label']); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($access['can_input'])): ?>
        <a href="<?php echo base_url('masterdata/add/' . $type); ?>" class="btn btn-primary">
            + Tambah <?php echo htmlspecialchars($label); ?>
        </a>
        <a href="<?php echo base_url('masterdata/bulk/' . $type); ?>" class="btn" style="background:#e9ecef; color:#2c3e50; margin-left:6px;">
            Tambah Massal (Copy-Paste)
        </a>
    <?php endif; ?>

    <table class="table-list" style="margin-top:12px;">
        <thead>
            <tr><th>Kode</th><th>Nama</th><th>Status</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="4">Belum ada data.</td></tr>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['kode']); ?></td>
                        <td><?php echo htmlspecialchars($item['nama']); ?></td>
                        <td>
                            <span class="badge <?php echo $item['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo $item['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($access['can_edit'])): ?>
                                <a href="<?php echo base_url('masterdata/edit/' . $type . '/' . $item['id']); ?>">Edit</a>
                            <?php endif; ?>
                            <?php if (!empty($access['can_delete'])): ?>
                                |
                                <a href="<?php echo base_url('masterdata/delete/' . $type . '/' . $item['id']); ?>"
                                   onclick="return confirm('Hapus data ini?');">Hapus</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
