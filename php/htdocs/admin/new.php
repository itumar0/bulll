<?php
/** Manual booking (walk-in guests or a booking taken by phone). Confirmed immediately. */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/views/icons.php';
require __DIR__ . '/../includes/views/admin.php';
require_admin_page();

$v = ['tableId' => (string) ($_GET['table'] ?? 't1'), 'name' => 'Гость', 'phone' => '+996 ', 'guests' => '2', 'when' => 'now',
      'date' => today_local(), 'startMinutes' => '1080', 'durationHours' => '1', 'comment' => ''];
$errors = [];
$formError = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    foreach ($v as $k => $_) if (isset($_POST[$k]) && is_string($_POST[$k])) $v[$k] = $_POST[$k];
    if (!form_post_is_valid()) {
        $formError = 'Сессия устарела. Обновите страницу и попробуйте снова.';
    } else {
        try {
            $raw = [
                'tableId' => $v['tableId'], 'name' => $v['name'], 'phone' => $v['phone'],
                'guests' => $v['guests'], 'durationHours' => $v['durationHours'], 'comment' => $v['comment'],
            ];
            if ($v['when'] === 'now') $raw['startNow'] = true;
            else { $raw['date'] = $v['date']; $raw['startMinutes'] = $v['startMinutes']; }
            $b = create_manual_booking(validate_manual_booking($raw));
            flash('Бронь №' . $b['id'] . ' создана: ' . TABLES[$b['table_id']]['name'] . ', ' . fmt_time(from_db($b['start_at'])) . ' – ' . fmt_time(from_db($b['end_at'])) . '.');
            redirect('admin/');
        } catch (ValidationError $e) {
            $errors = $e->fields;
            $formError = 'Проверьте поля формы';
        } catch (BookingError $e) {
            $formError = $e->getMessage();
        }
    }
}
if (!isset(TABLES[$v['tableId']])) $v['tableId'] = 't1';
$err = fn (string $k) => isset($errors[$k]) ? '<p class="field-error" role="alert">' . e($errors[$k]) . '</p>' : '';

admin_page_start('Новая бронь', 'new');
?>
<h1 class="mb-1 text-2xl font-extrabold tracking-tight">Новая бронь вручную</h1>
<p class="mb-6 text-sm text-mute">Для гостей, которые пришли без брони или позвонили. Бронь сразу подтверждена.</p>
<?php if ($formError): ?><p role="alert" class="mb-4 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200"><?= e($formError) ?></p><?php endif; ?>

<form method="post" class="card grid gap-4 p-5 sm:p-6" novalidate>
  <?= csrf_field() ?>
  <div class="grid grid-cols-2 gap-3">
    <div>
      <label for="nb-table" class="label">Стол</label>
      <select id="nb-table" name="tableId" class="field">
        <?php foreach (TABLES as $id => $t): ?><option value="<?= e($id) ?>"<?= $v['tableId'] === $id ? ' selected' : '' ?>><?= e($t['name']) ?> (до <?= (int) $t['capacity'] ?>)</option><?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="nb-guests" class="label">Гостей</label>
      <input id="nb-guests" name="guests" type="number" min="1" max="10" class="field" value="<?= e($v['guests']) ?>">
      <?= $err('guests') ?>
    </div>
    <div>
      <label for="nb-name" class="label">Имя</label>
      <input id="nb-name" name="name" class="field" maxlength="60" value="<?= e($v['name']) ?>" required>
      <?= $err('name') ?>
    </div>
    <div>
      <label for="nb-phone" class="label">Телефон</label>
      <input id="nb-phone" name="phone" type="tel" class="field tabular-nums" value="<?= e($v['phone']) ?>" data-phone-mask required>
      <?= $err('phone') ?>
    </div>
  </div>

  <fieldset class="grid gap-3">
    <legend class="label">Начало</legend>
    <div class="grid grid-cols-2 gap-2">
      <label class="chip cursor-pointer has-checked:border-accent-500 has-checked:bg-accent-600/15 has-checked:text-white"><input type="radio" name="when" value="now" class="sr-only"<?= $v['when'] === 'now' ? ' checked' : '' ?>> Сейчас</label>
      <label class="chip cursor-pointer has-checked:border-accent-500 has-checked:bg-accent-600/15 has-checked:text-white"><input type="radio" name="when" value="later" class="sr-only"<?= $v['when'] !== 'now' ? ' checked' : '' ?>> Выбрать время</label>
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label for="nb-date" class="label text-xs">Дата (если «выбрать время»)</label>
        <input id="nb-date" name="date" type="date" class="field" value="<?= e($v['date']) ?>">
        <?= $err('date') ?>
      </div>
      <div>
        <label for="nb-time" class="label text-xs">Время</label>
        <select id="nb-time" name="startMinutes" class="field">
          <?php for ($m = 0; $m <= LAST_START_MIN; $m += SLOT_MIN): ?><option value="<?= $m ?>"<?= (string) $m === $v['startMinutes'] ? ' selected' : '' ?>><?= sprintf('%02d:%02d', intdiv($m, 60), $m % 60) ?></option><?php endfor; ?>
        </select>
        <?= $err('startMinutes') ?>
      </div>
    </div>
  </fieldset>

  <div>
    <label for="nb-dur" class="label">Длительность</label>
    <select id="nb-dur" name="durationHours" class="field">
      <?php foreach ([0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, 5] as $d): ?><option value="<?= $d ?>"<?= (string) $d === $v['durationHours'] ? ' selected' : '' ?>><?= $d < 1 ? '30 мин' : str_replace('.5', ',5', (string) $d) . ' ч' ?></option><?php endforeach; ?>
    </select>
    <?= $err('durationHours') ?>
  </div>
  <div>
    <label for="nb-comment" class="label">Комментарий</label>
    <input id="nb-comment" name="comment" class="field" maxlength="500" value="<?= e($v['comment']) ?>">
    <?= $err('comment') ?>
  </div>
  <button type="submit" class="<?= btn_class('primary', 'md', 'w-full') ?>">Создать бронь</button>
</form>
<?php admin_page_end();
