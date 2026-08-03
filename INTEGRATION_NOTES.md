# Integrasi Fitur Departemen

File yang saya buat baru (tinggal copy ke lokasi yang sesuai):

```
application/models/Department_model.php
application/controllers/Department.php
application/views/department/index.php
application/views/department/form.php
```

Langkah:

1. **Jalankan migrasi dulu**: `2026_07_13_add_department.sql` (lewat pgAdmin/psql).
2. **Tambahkan fungsi `normalize_bool()`** ke `application/helpers/app_helper.php` yang
   sudah ada — isinya di `app_helper_addition.php`. Helper ini sudah di-autoload lewat
   `application/config/autoload.php`, jadi tidak perlu ubah autoload.
3. **Copy 4 file di atas** ke lokasi yang sesuai di project.
4. **Beri akses ke modul `department`** lewat `User.php::edit()` untuk user yang perlu
   (default level 0 = tidak ada akses, sesuai prinsip project ini).
5. **Patch `User.php::edit()`** — lihat di bawah.

---

## Patch: `application/controllers/User.php`

Di `__construct()`, tambahkan:

```php
$this->load->model('Department_model');
```

Di method `edit($id)`, tambahkan sebelum load view (untuk menampilkan checklist departemen):

```php
$data['all_departments']  = $this->Department_model->get_all();
$data['user_departments'] = $this->Department_model->get_user_departments($id);
// $data['user_departments'] dipakai view untuk tahu departemen mana yang
// sudah dicentang dan mana yang primary.
```

Di bagian yang menangani POST (setelah logic simpan `mst_user_menu_access` yang sudah
ada), tambahkan:

```php
$department_ids = $this->input->post('departments') ?: [];       // array of int
$primary_id     = $this->input->post('primary_department');       // int atau null

$this->Department_model->set_user_departments($id, $department_ids, $primary_id);
```

Tidak perlu validasi rumit — kalau `primary_department` yang dikirim ternyata tidak ada
di `departments` yang dicentang, `Department_model::set_user_departments()` otomatis
tidak menandai siapa pun sebagai primary (aman, tidak fatal).

---

## Patch: view edit user (mis. `application/views/user/edit.php`)

Tambahkan section baru, polanya mengikuti matrix akses menu yang sudah ada di form ini.
Contoh markup (sesuaikan class/struktur ke tema project):

```php
<h4>Departemen</h4>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Ikut?</th>
            <th>Departemen</th>
            <th>Primary</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Kumpulkan department_id yang sudah diikuti user, untuk cek checkbox.
        $current_ids = array_column($user_departments, 'department_id');
        $primary_id  = null;
        foreach ($user_departments as $ud) {
            if ($ud['is_primary']) { $primary_id = $ud['department_id']; }
        }
        ?>
        <?php foreach ($all_departments as $dept): ?>
            <tr>
                <td>
                    <input type="checkbox" name="departments[]" value="<?= $dept['id'] ?>"
                        <?= in_array($dept['id'], $current_ids) ? 'checked' : '' ?>>
                </td>
                <td><?= html_escape($dept['department_name']) ?></td>
                <td>
                    <input type="radio" name="primary_department" value="<?= $dept['id'] ?>"
                        <?= $primary_id == $dept['id'] ? 'checked' : '' ?>>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<p class="text-muted">
    "Primary" menentukan departemen aktif default saat user login. User tetap bisa
    switch departemen aktif kapan saja (lihat bagian "departemen aktif" di bawah).
</p>
```

**Catatan:** tidak ada validasi JS di sini yang otomatis un-check radio kalau checkbox-nya
di-uncheck (supaya tidak perlu JS modern yang mungkin tidak jalan di Chrome versi lama).
Kalau user uncheck departemen yang jadi primary tapi radio-nya tetap terkirim,
`set_user_departments()` di model akan mengabaikannya karena department_id itu sudah
tidak ada di array `departments[]` yang dikirim.

---

## Belum termasuk di sini (perlu dibahas terpisah kalau mau lanjut)

- **Mekanisme "departemen aktif"**: dropdown switch di header + penyimpanan ke session
  (`departemen_aktif_id` di `session->userdata`), diisi awal dari `is_primary` saat login.
  Ini perlu sentuh `Auth.php` (saat login) dan `MY_Controller.php` (baca session).
- **Helper filter departemen** untuk dipakai modul data di masa depan, misalnya
  `apply_department_filter($this->db, $user_id)` yang otomatis nambah `WHERE is_public
  = TRUE OR department_id IN (...)`, dengan pengecualian kalau `can_view_all_departments`
  = TRUE.
- Kolom `department_id` + `is_public` di tabel data modul — ditambahkan nanti per modul
  saat modulnya dibuat, bukan sekarang.
