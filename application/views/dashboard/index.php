<?php
/**
 * Dashboard: tombol akses cepat ke menu-menu sesuai hak akses user
 * (sumber data sama dengan sidebar, yaitu $menus dari Menu_model::get_menu_for_level()).
 *
 * $menus adalah array flat berisi menu yang can_view = TRUE untuk user ini,
 * masing-masing punya: id, parent_id, menu_code, menu_name, menu_url, menu_icon, sort_order, dst.
 * Di sini kita susun ulang jadi pohon (induk -> anak) supaya menu dengan sub-menu
 * tampil terkelompok, sama seperti strukturnya di database (mst_menu.parent_id).
 */

$all_menus = !empty($menus) ? $menus : array();

// Kelompokkan per parent_id supaya gampang cari anak dari suatu menu
$children_of = array();
foreach ($all_menus as $m) {
    $children_of[$m['parent_id']][] = $m;
}

$top_level = isset($children_of[0]) ? $children_of[0] : array();

// Pisahkan menu level atas jadi dua: yang langsung punya link (tombol tunggal)
// dan yang punya sub-menu (jadi kelompok kartu tersendiri). Menu "dashboard"
// itu sendiri tidak perlu ditampilkan sebagai tombol karena kita sedang di sini.
$direct_items = array();
$grouped_items = array();

foreach ($top_level as $item) {
    if ($item['menu_code'] === 'dashboard') {
        continue;
    }

    $kids = isset($children_of[$item['id']]) ? $children_of[$item['id']] : array();

    if (!empty($kids)) {
        $grouped_items[] = array('menu' => $item, 'children' => $kids);
    } elseif (!empty($item['menu_url'])) {
        $direct_items[] = $item;
    }
}

$palette = array('tile-blue', 'tile-green', 'tile-orange', 'tile-purple', 'tile-red', 'tile-teal');
$tile_i = 0;

/**
 * Render satu tombol menu. Dipakai berulang di bawah supaya markup-nya konsisten.
 * Dibungkus function_exists() sesuai konvensi helper lain di project ini,
 * jaga-jaga kalau view ini pernah di-load lebih dari sekali dalam satu request.
 */
if (!function_exists('render_menu_tile')) {
    function render_menu_tile($item, $palette, &$tile_i)
    {
        $color = $palette[$tile_i % count($palette)];
        $tile_i++;
        $initial = strtoupper(substr(trim($item['menu_name']), 0, 1));
        ?>
        <a class="menu-tile <?php echo $color; ?>" href="<?php echo base_url($item['menu_url']); ?>">
            <span class="menu-tile-icon"><?php echo htmlspecialchars($initial); ?></span>
            <span class="menu-tile-label"><?php echo htmlspecialchars($item['menu_name']); ?></span>
        </a>
        <?php
    }
}
?>

<?php $u = current_user(); ?>
<div class="card">
    <h2>Selamat datang, <?php echo htmlspecialchars($u['fullname']); ?></h2>
    <p class="text-muted">
        Berikut menu-menu yang bisa Anda akses sesuai hak akses akun Anda saat ini.
    </p>
</div>

<?php if (empty($direct_items) && empty($grouped_items)): ?>

    <div class="card">
        <p class="text-muted">
            Belum ada menu yang bisa Anda akses. Silakan hubungi administrator untuk pengaturan hak akses.
        </p>
    </div>

<?php else: ?>

    <?php if (!empty($direct_items)): ?>
        <div class="card">
            <h2>Menu Utama</h2>
            <div class="tile-grid">
                <?php foreach ($direct_items as $item): ?>
                    <?php render_menu_tile($item, $palette, $tile_i); ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php foreach ($grouped_items as $group): ?>
        <div class="card">
            <h2><?php echo htmlspecialchars($group['menu']['menu_name']); ?></h2>
            <div class="tile-grid">
                <?php foreach ($group['children'] as $kid): ?>
                    <?php render_menu_tile($kid, $palette, $tile_i); ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

<?php endif; ?>
