<?php
/** All bookings with filters (date, table, status) and search by name / phone. */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/views/icons.php';
require __DIR__ . '/../includes/views/admin.php';
require_admin_page();

$f = [
    'date' => array_key_exists('date', $_GET) ? (string) $_GET['date'] : today_local(),
    'tableId' => (string) ($_GET['tableId'] ?? ''),
    'status' => (string) ($_GET['status'] ?? ''),
    'q' => mb_substr((string) ($_GET['q'] ?? ''), 0, 60),
];
$error = null;
try {
    $rows = list_bookings($f);
} catch (BookingError $e) {
    $rows = [];
    $error = $e->getMessage();
}

admin_page_start('Брони', 'bookings', true);
echo flash_html();
?>
<h1 class="mb-5 text-2xl font-extrabold tracking-tight">Брони</h1>
<form method="get" class="mb-4 grid grid-cols-2 gap-2 lg:grid-cols-[auto_auto_auto_1fr_auto_auto]">
  <label class="sr-only" for="f-date">Дата</label>
  <input id="f-date" name="date" type="date" class="field min-w-0 px-3 py-2.5" value="<?= e($f['date']) ?>">
  <label class="sr-only" for="f-table">Стол</label>
  <select id="f-table" name="tableId" class="field py-2.5">
    <option value="">Все столы</option>
    <?php foreach (TABLES as $id => $t): ?><option value="<?= e($id) ?>"<?= $f['tableId'] === $id ? ' selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?>
  </select>
  <label class="sr-only" for="f-status">Статус</label>
  <select id="f-status" name="status" class="field col-span-2 py-2.5 sm:col-span-1">
    <option value="">Все статусы</option>
    <?php foreach (STATUS_LABEL as $s => $l): ?><option value="<?= $s ?>"<?= $f['status'] === $s ? ' selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
  </select>
  <label class="sr-only" for="f-q">Поиск</label>
  <input id="f-q" name="q" type="search" class="field col-span-2 py-2.5 lg:col-span-1" placeholder="Поиск по имени или телефону" value="<?= e($f['q']) ?>" maxlength="60">
  <button type="submit" class="<?= btn_class('primary', 'md') ?>">Найти</button>
  <a href="?date=" class="inline-flex h-12 items-center justify-center rounded-xl px-4 text-sm text-mute hover:bg-white/5 hover:text-white">Все даты</a>
</form>
<?php if ($error): ?><p role="alert" class="mb-4 text-sm text-red-300"><?= e($error) ?></p><?php endif; ?>

<div id="live" data-live>
  <p class="mb-3 text-sm text-mute">Найдено: <?= count($rows) ?><?= $f['date'] !== '' ? ' · ' . e(date('d.m.Y', strtotime($f['date']))) : '' ?></p>
  <div class="overflow-hidden rounded-2xl border border-white/[0.07]">
    <div class="hidden grid-cols-[60px_80px_1.3fr_110px_120px_50px_120px_2fr] gap-3 bg-ink-900 px-4 py-3 text-xs font-bold tracking-wider text-neutral-400 uppercase lg:grid">
      <span>№</span><span>Стол</span><span>Клиент</span><span>Дата</span><span>Время</span><span>Гост.</span><span>Статус</span><span>Действия</span>
    </div>
    <ul class="divide-y divide-white/[0.06]">
      <?php foreach ($rows as $b): $s = from_db($b['start_at']); $en = from_db($b['end_at']); ?>
        <li id="b-<?= (int) $b['id'] ?>" class="px-4 py-4 text-sm target:bg-accent-500/10">
          <!-- phone -->
          <div class="lg:hidden">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="text-base font-bold"><?= e(TABLES[$b['table_id']]['name'] ?? $b['table_id']) ?> · <span class="tabular-nums"><?= fmt_time($s) ?>–<?= fmt_time($en) ?></span></p>
                <p class="mt-0.5 text-xs text-neutral-400 tabular-nums">№<?= (int) $b['id'] ?> · <?= fmt_date($s) ?> · <?= (int) $b['guests'] ?> гост.<?= $b['source'] === 'ADMIN' ? ' · вручную' : '' ?></p>
              </div>
              <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold <?= STATUS_PILL[$b['status']] ?? '' ?>"><?= STATUS_LABEL[$b['status']] ?? e($b['status']) ?></span>
            </div>
            <div class="mt-2 flex items-center justify-between gap-3">
              <span class="min-w-0 truncate font-semibold"><?= e($b['name']) ?></span>
              <a href="tel:<?= e($b['phone']) ?>" class="inline-flex min-h-10 shrink-0 items-center gap-1.5 rounded-lg px-2 text-mute tabular-nums hover:bg-white/5 hover:text-white"><?= icon('phone', 'h-4 w-4') ?><?= e(pretty_phone($b['phone'])) ?></a>
            </div>
            <?php if ($b['comment']): ?><p class="mt-1 text-xs text-neutral-400 italic">«<?= e($b['comment']) ?>»</p><?php endif; ?>
            <div class="mt-3"><?= booking_actions($b) ?></div>
          </div>
          <!-- desktop -->
          <div class="hidden grid-cols-[60px_80px_1.3fr_110px_120px_50px_120px_2fr] items-center gap-3 lg:grid">
            <span class="font-mono text-neutral-400">#<?= (int) $b['id'] ?></span>
            <span class="font-bold"><?= e(TABLES[$b['table_id']]['name'] ?? $b['table_id']) ?></span>
            <span>
              <span class="block font-semibold"><?= e($b['name']) ?></span>
              <a href="tel:<?= e($b['phone']) ?>" class="block text-mute tabular-nums hover:underline"><?= e(pretty_phone($b['phone'])) ?></a>
              <?php if ($b['comment']): ?><span class="mt-1 block text-xs text-neutral-400 italic">«<?= e($b['comment']) ?>»</span><?php endif; ?>
            </span>
            <span class="text-soft tabular-nums"><?= fmt_date($s) ?></span>
            <span class="font-semibold tabular-nums"><?= fmt_time($s) ?> – <?= fmt_time($en) ?></span>
            <span class="tabular-nums"><?= (int) $b['guests'] ?></span>
            <span>
              <span class="inline-block rounded-full px-2.5 py-1 text-xs font-semibold <?= STATUS_PILL[$b['status']] ?? '' ?>"><?= STATUS_LABEL[$b['status']] ?? e($b['status']) ?></span>
              <?php if ($b['source'] === 'ADMIN'): ?><span class="ml-1 text-[10px] text-neutral-400">вручную</span><?php endif; ?>
            </span>
            <?= booking_actions($b) ?>
          </div>
        </li>
      <?php endforeach; ?>
      <?php if (!$rows): ?><li class="px-4 py-12 text-center text-mute">Броней не найдено</li><?php endif; ?>
    </ul>
  </div>
</div>
<?php admin_page_end();
