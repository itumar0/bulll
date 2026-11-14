<?php
/** Upload the club logo (classic form, works without JavaScript). */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/views/icons.php';
require __DIR__ . '/../includes/views/admin.php';
require_admin_page();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    // A file larger than post_max_size empties $_POST/$_FILES entirely.
    if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        flash('Файл слишком большой (максимум 2 МБ)', true);
        redirect('admin/logo.php');
    }
    if (!form_post_is_valid()) {
        flash('Сессия устарела. Обновите страницу и попробуйте снова.', true);
        redirect('admin/logo.php');
    }
    try {
        if (($_POST['action'] ?? '') === 'reset') {
            reset_logo();
            flash('Вернули стандартный логотип.');
        } else {
            save_logo(is_array($_FILES['logo'] ?? null) ? $_FILES['logo'] : []);
            flash('Логотип обновлён. Он уже показывается на сайте.');
        }
    } catch (BookingError $e) {
        flash($e->getMessage(), true);
    } catch (Throwable $e) {
        log_error('admin/logo', $e);
        flash('Не удалось сохранить логотип. Попробуйте ещё раз.', true);
    }
    redirect('admin/logo.php');
}

admin_page_start('Логотип');
?>
<h1 class="mb-2 text-3xl font-extrabold tracking-tight">Логотип</h1>
<p class="mb-6 text-sm text-mute">Показывается в шапке сайта, на первом экране, во вкладке браузера и на иконке телефона.</p>
<?= flash_html() ?>

<div class="card mb-6 flex items-center gap-5 p-5">
  <img src="<?= e(logo_url('logo-512')) ?>" alt="Текущий логотип" width="112" height="112" class="h-28 w-28 rounded-full ring-1 ring-white/10">
  <div class="text-sm">
    <p class="font-bold"><?= has_custom_logo() ? 'Загруженный логотип' : 'Стандартный логотип' ?></p>
    <p class="mt-1 text-mute">На сайте логотип показывается в круге — лучше всего подходит квадратная картинка с логотипом по центру.</p>
  </div>
</div>

<form method="post" enctype="multipart/form-data" class="card grid gap-4 p-5 sm:p-6">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <input type="hidden" name="MAX_FILE_SIZE" value="<?= LOGO_MAX_BYTES ?>">
  <label for="logo" class="label">Новый логотип</label>
  <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp" required class="field file:mr-3 file:rounded-lg file:border-0 file:bg-accent-600 file:px-3 file:py-1.5 file:text-white">
  <p class="text-xs text-neutral-400">PNG, JPG или WebP, до 2 МБ, от 64×64 до 4000×4000 пикселей. Прозрачный фон сохраняется.</p>
  <button type="submit" class="<?= btn_class('primary', 'md', 'w-full') ?>">Загрузить</button>
</form>

<?php if (has_custom_logo()): ?>
<form method="post" class="mt-4 text-center">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <input type="hidden" name="action" value="reset">
  <button type="submit" class="text-sm text-mute underline-offset-4 hover:text-white hover:underline">Вернуть стандартный логотип</button>
</form>
<?php endif; ?>
<?php admin_page_end();
