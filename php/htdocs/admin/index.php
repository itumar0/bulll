<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/views/icons.php';
require __DIR__ . '/../includes/views/admin.php';
require_admin_page();

admin_page_start('Админ-панель');
?>
<h1 class="mb-6 text-3xl font-extrabold tracking-tight">Админ-панель</h1>
<?= flash_html() ?>
<ul class="grid gap-3">
  <li>
    <a href="<?= e(base_path()) ?>admin/logo.php" class="card flex items-center gap-4 p-5 transition-colors hover:border-accent-500/40">
      <img src="<?= e(logo_url('logo-96')) ?>" alt="" width="48" height="48" class="h-12 w-12 rounded-full">
      <span><span class="block font-bold">Логотип</span><span class="block text-sm text-mute">Загрузить новый логотип или вернуть стандартный</span></span>
    </a>
  </li>
</ul>
<?php admin_page_end();
