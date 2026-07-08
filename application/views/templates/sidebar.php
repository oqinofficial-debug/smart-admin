<div class="app-sidebar">
    <ul>
        <?php if (!empty($menus)): foreach ($menus as $menu): ?>
            <li>
                <a href="<?php echo base_url($menu['menu_url']); ?>"
                   class="<?php echo (uri_string() == $menu['menu_url']) ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($menu['menu_name']); ?>
                </a>
            </li>
        <?php endforeach; endif; ?>
    </ul>
</div>
<div class="app-content">
