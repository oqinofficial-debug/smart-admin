<?php $u = current_user(); ?>

<div class="card">
    <h2>Selamat datang, <?php echo htmlspecialchars($u['fullname']); ?></h2>
    <p>Anda login sebagai <strong><?php echo role_label($u['level']); ?></strong>.
       Menu yang tersedia di sebelah kiri menyesuaikan hak akses Anda.</p>
</div>

<div class="card">
    <h2>Akses Cepat</h2>
    <?php if (!empty($menus)): ?>
        <table style="width:100%; border-collapse: collapse;">
            <?php foreach ($menus as $menu): ?>
                <tr>
                    <td style="padding:8px 0; border-bottom:1px solid #eee;">
                        <a href="<?php echo base_url($menu['menu_url']); ?>">
                            <?php echo htmlspecialchars($menu['menu_name']); ?>
                        </a>
                    </td>
                    <td style="padding:8px 0; border-bottom:1px solid #eee; text-align:right; font-size:12px; color:#8492a6;">
                        <?php
                            $hak = array();
                            if ($menu['can_view'])   $hak[] = 'Lihat';
                            if ($menu['can_input'])  $hak[] = 'Input';
                            if ($menu['can_edit'])   $hak[] = 'Edit';
                            if ($menu['can_delete']) $hak[] = 'Hapus';
                            echo implode(' / ', $hak);
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Belum ada menu yang bisa diakses. Hubungi administrator.</p>
    <?php endif; ?>
</div>
