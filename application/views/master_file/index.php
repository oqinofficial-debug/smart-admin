<div class="card">
    <h2>Master File — Nama Laporan per Departemen</h2>
    <p class="text-muted">
        Daftar identitas laporan yang bisa dipilih tiap departemen saat Import Data.
        Nama laporan inilah yang jadi acuan: kalau file diimport ulang dengan nama
        laporan dan periode yang sama, data lama bisa langsung di-replace.
    </p>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success"><?php echo $this->session->flashdata('success'); ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>

    <form method="get" style="margin-bottom:16px;">
        <label style="font-weight:normal;">Filter Departemen:</label>
        <select name="department_id" onchange="this.form.submit()" style="max-width:280px; display:inline-block;">
            <option value="">-- Semua Departemen --</option>
            <?php foreach ($departments as $dept): ?>
                <option value="<?php echo (int) $dept['id']; ?>"
                    <?php echo ($filter_department === (int) $dept['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($dept['department_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if (!empty($access['can_input'])): ?>
        <a href="<?php echo base_url('master-file/add'); ?>" class="btn btn-primary">
            + Tambah Nama Laporan
        </a>
    <?php endif; ?>

    <table class="table-list" style="margin-top:12px;">
        <thead>
            <tr><th>Departemen</th><th>Kode</th><th>Nama Laporan</th><th>Status</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="5">Belum ada data.</td></tr>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['department_name'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($item['kode']); ?></td>
                        <td><?php echo htmlspecialchars($item['nama']); ?></td>
                        <td>
                            <span class="badge <?php echo $item['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo $item['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($access['can_edit'])): ?>
                                <a href="<?php echo base_url('master-file/edit/' . $item['id']); ?>">Edit</a>
                            <?php endif; ?>
                            <?php if (!empty($access['can_delete'])): ?>
                                |
                                <a href="<?php echo base_url('master-file/delete/' . $item['id']); ?>"
                                   onclick="return confirm('Hapus nama laporan ini? Laporan yang sudah pernah dipakai untuk import tidak akan bisa dihapus.');">Hapus</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
