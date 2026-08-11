<div class="card">
    <h2><?php echo $delivery_row ? 'Edit Delivery Record' : 'Tambah Delivery Record'; ?></h2>

    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>
    <?php if (validation_errors()): ?>
        <div class="alert alert-danger"><?php echo validation_errors(); ?></div>
    <?php endif; ?>

    <?php echo form_open(current_url()); ?>

        <div class="form-group">
            <label>No. JF</label>
            <?php $jf_id = set_value('jf_id', $delivery_row['jf_id'] ?? ''); ?>
            <select name="jf_id" class="form-control">
                <option value="">-- Pilih JF --</option>
                <?php foreach ($jf_list as $jf): ?>
                    <option value="<?php echo $jf['id']; ?>" <?php echo ((string) $jf_id === (string) $jf['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($jf['jf']); ?><?php echo $jf['product'] ? ' - ' . htmlspecialchars($jf['product']) : ''; ?>
                        (<?php echo htmlspecialchars($jf['status_jf']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Tanggal Kirim</label>
            <input type="date" name="tanggal_kirim" class="form-control"
                   value="<?php echo set_value('tanggal_kirim', $delivery_row['tanggal_kirim'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>Aktual Kirim</label>
            <input type="date" name="aktual_kirim" class="form-control"
                   value="<?php echo set_value('aktual_kirim', $delivery_row['aktual_kirim'] ?? ''); ?>">
            <small class="text-muted">Kosongkan kalau belum benar-benar terkirim.</small>
        </div>

        <div class="form-group">
            <label>No. SP</label>
            <input type="text" name="no_sp" class="form-control"
                   value="<?php echo set_value('no_sp', $delivery_row['no_sp'] ?? ''); ?>" maxlength="100">
        </div>

        <div class="form-group">
            <label>Jenis SP</label>
            <input type="text" name="jenis_sp" class="form-control"
                   value="<?php echo set_value('jenis_sp', $delivery_row['jenis_sp'] ?? ''); ?>" maxlength="50">
        </div>

        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?php echo base_url('delivery'); ?>" class="btn" style="background:#e9ecef;">Batal</a>

    <?php echo form_close(); ?>
</div>

<?php if ($delivery_row): ?>
<div class="card" style="margin-top:16px;">
    <h3>Cantolan Stok Finish Good (FG)</h3>
    <p class="text-muted">Kurangi stok FG hasil monitoring produksi (status output = Finish Good Stok) untuk
        memenuhi kiriman ini.</p>

    <div id="fg-alert-box"></div>

    <table class="table-list" id="fg-list-table">
        <thead>
            <tr>
                <th>No. JF</th>
                <th>Proses</th>
                <th>Periode</th>
                <th>Qty Pakai</th>
                <?php if (!empty($access['can_delete'])): ?><th>Aksi</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <tr><td colspan="5">Memuat...</td></tr>
        </tbody>
    </table>

    <?php if (!empty($access['can_input'])): ?>
        <div class="form-cantolan-fg" style="margin-top:8px;">
            <input type="text" class="fg-search" placeholder="Cari sumber stok FG (proses/periode)..." autocomplete="off">
            <input type="hidden" class="fg-ref-id">
            <div class="fg-search-results" style="display:none;"></div>
            <input type="number" step="any" class="fg-qty" placeholder="Qty pakai">
            <button type="button" class="btn btn-primary btn-tambah-fg">Tambah</button>
        </div>
    <?php endif; ?>
</div>

<script>
(function () {
    var baseUrl = '<?php echo base_url(); ?>';
    var deliveryId = <?php echo (int) $delivery_row['id']; ?>;
    var jfId = <?php echo (int) $delivery_row['jf_id']; ?>;
    var tbody = document.querySelector('#fg-list-table tbody');
    var canDelete = <?php echo !empty($access['can_delete']) ? 'true' : 'false'; ?>;

    function showAlert(type, msg) {
        var box = document.getElementById('fg-alert-box');
        box.innerHTML = '<div class="alert alert-' + type + '">' + msg + '</div>';
        setTimeout(function () { box.innerHTML = ''; }, 4000);
    }

    function post(url, data) {
        return fetch(baseUrl + url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(data)
        }).then(function (r) { return r.json(); });
    }

    function loadList() {
        fetch(baseUrl + 'delivery/fg_list/' + deliveryId)
            .then(function (r) { return r.json(); })
            .then(function (list) {
                tbody.innerHTML = '';
                if (!list.length) {
                    tbody.innerHTML = '<tr><td colspan="5">Belum ada cantolan FG.</td></tr>';
                    return;
                }
                list.forEach(function (row) {
                    var tr = document.createElement('tr');
                    tr.setAttribute('data-fg-id', row.id);
                    var actionTd = canDelete ? '<td><button type="button" class="btn btn-danger btn-hapus-fg">Hapus</button></td>' : '';
                    tr.innerHTML =
                        '<td>' + escapeHtml(row.jf) + '</td>' +
                        '<td>' + escapeHtml(row.proses_nama) + '</td>' +
                        '<td>' + escapeHtml(row.monitoring_periode) + '</td>' +
                        '<td>' + escapeHtml(row.qty_pakai) + '</td>' +
                        actionTd;
                    tbody.appendChild(tr);
                });
                if (canDelete) {
                    tbody.querySelectorAll('.btn-hapus-fg').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            if (!confirm('Hapus cantolan FG ini?')) { return; }
                            var tr = btn.closest('[data-fg-id]');
                            var id = tr.getAttribute('data-fg-id');
                            post('delivery/fg_delete/' + id, {}).then(function (res) {
                                if (res.success) { tr.remove(); } else { showAlert('danger', res.message || 'Gagal menghapus.'); }
                            });
                        });
                    });
                }
            });
    }

    function escapeHtml(v) {
        var d = document.createElement('div');
        d.textContent = (v === null || v === undefined) ? '' : v;
        return d.innerHTML;
    }

    loadList();

    var form = document.querySelector('.form-cantolan-fg');
    if (form) {
        var searchInp = form.querySelector('.fg-search');
        var refIdInp = form.querySelector('.fg-ref-id');
        var resultsBox = form.querySelector('.fg-search-results');
        var timer = null;

        searchInp.addEventListener('input', function () {
            refIdInp.value = '';
            clearTimeout(timer);
            var q = searchInp.value;
            timer = setTimeout(function () {
                fetch(baseUrl + 'delivery/fg_search?jf_id=' + jfId)
                    .then(function (r) { return r.json(); })
                    .then(function (list) {
                        var qLower = q.toLowerCase();
                        var filtered = list.filter(function (item) {
                            return !q || (item.proses_nama + ' ' + item.periode + ' ' + item.department_nama)
                                .toLowerCase().indexOf(qLower) !== -1;
                        });
                        resultsBox.innerHTML = '';
                        if (!filtered.length) { resultsBox.style.display = 'none'; return; }
                        filtered.forEach(function (item) {
                            var div = document.createElement('div');
                            var label = item.proses_nama + ' / ' + item.department_nama +
                                ' (' + item.periode + ') — sisa ' + item.sisa_qty;
                            div.textContent = label;
                            div.style.cursor = 'pointer';
                            div.addEventListener('click', function () {
                                refIdInp.value = item.monitoring_id;
                                searchInp.value = label;
                                resultsBox.style.display = 'none';
                            });
                            resultsBox.appendChild(div);
                        });
                        resultsBox.style.display = 'block';
                    });
            }, 250);
        });

        form.querySelector('.btn-tambah-fg').addEventListener('click', function () {
            if (!refIdInp.value) {
                showAlert('danger', 'Pilih sumber stok FG dari daftar autocomplete dulu.');
                return;
            }
            post('delivery/fg_add', {
                delivery_id: deliveryId,
                monitoring_id: refIdInp.value,
                qty_pakai: form.querySelector('.fg-qty').value
            }).then(function (res) {
                if (res.success) {
                    showAlert(res.warning ? 'danger' : 'success', res.warning || 'Cantolan FG ditambahkan.');
                    searchInp.value = '';
                    refIdInp.value = '';
                    form.querySelector('.fg-qty').value = '';
                    loadList();
                } else {
                    showAlert('danger', res.message || 'Gagal menambah cantolan FG.');
                }
            });
        });
    }
})();
</script>
<?php endif; ?>
