<?php
/** 24-hour timeline for all 8 tables. */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/views/icons.php';
require __DIR__ . '/../includes/views/admin.php';
require_admin_page();

$date = (string) ($_GET['date'] ?? today_local());
if (!is_valid_date($date)) $date = today_local();
$rows = array_filter(list_bookings(['date' => $date]), fn ($b) => $b['status'] !== 'CANCELLED');
$dayStart = local_to_utc($date, 0)->getTimestamp();
$nowMin = (int) floor((now_utc()->getTimestamp() - $dayStart) / 60);
$prev = (new DateTimeImmutable($date))->modify('-1 day')->format('Y-m-d');
$next = (new DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d');
$bar = [
    'PENDING' => 'bg-amber-400/25 border-amber-400/60 text-amber-100',
    'CONFIRMED' => 'bg-accent-600/40 border-accent-500/70 text-white',
    'COMPLETED' => 'bg-white/10 border-white/20 text-mute',
];

admin_page_start('Таймлайн', 'timeline', true);
echo flash_html();
?>
<div class="mb-4 flex flex-wrap items-center gap-2">
  <h1 class="mr-2 text-2xl font-extrabold tracking-tight">Таймлайн</h1>
  <a href="?date=<?= e($prev) ?>" class="<?= btn_class('ghost', 'sm') ?>" aria-label="Предыдущий день">←</a>
  <form method="get" class="flex items-center gap-2">
    <label class="sr-only" for="tl-date">Дата</label>
    <input id="tl-date" name="date" type="date" class="field w-auto py-2" value="<?= e($date) ?>" data-autosubmit>
    <noscript><button class="<?= btn_class('outline', 'sm') ?>">Показать</button></noscript>
  </form>
  <a href="?date=<?= e($next) ?>" class="<?= btn_class('ghost', 'sm') ?>" aria-label="Следующий день">→</a>
  <a href="?date=<?= e(today_local()) ?>" class="<?= btn_class('ghost', 'sm') ?>">Сегодня</a>
  <span class="ml-auto flex items-center gap-3 text-xs text-mute">
    <span class="flex items-center gap-1"><i class="h-3 w-3 rounded bg-accent-600/60"></i> подтверждена</span>
    <span class="flex items-center gap-1"><i class="h-3 w-3 rounded bg-amber-400/50"></i> ожидает</span>
    <span class="flex items-center gap-1"><i class="h-3 w-3 rounded bg-white/20"></i> завершена</span>
  </span>
</div>

<div id="live" data-live>
  <div class="overflow-x-auto rounded-2xl border border-white/[0.07] bg-ink-900">
    <div class="relative min-w-[1440px] p-4">
      <div class="relative ml-20 h-6 text-[11px] text-neutral-400">
        <?php for ($h = 0; $h <= 24; $h++): ?>
          <span class="absolute -translate-x-1/2 tabular-nums" data-tl-left="<?= $h * 60 ?>"><?= sprintf('%02d:00', $h % 24) ?></span>
        <?php endfor; ?>
      </div>
      <div class="relative">
        <?php foreach (TABLES as $id => $t): ?>
          <div class="flex h-12 items-center border-t border-white/[0.05]">
            <span class="sticky left-0 z-10 w-20 shrink-0 self-stretch bg-ink-900 pl-1 text-sm leading-[3rem] font-bold"><?= e($t['name']) ?></span>
            <div class="relative h-9 flex-1">
              <?php for ($h = 0; $h <= 24; $h++): ?><span class="absolute inset-y-0 w-px bg-white/[0.05]" data-tl-left="<?= $h * 60 ?>" aria-hidden="true"></span><?php endfor; ?>
              <?php foreach ($rows as $b):
                  if ($b['table_id'] !== $id) continue;
                  $s = max(0, (int) floor((from_db($b['start_at'])->getTimestamp() - $dayStart) / 60));
                  $en = min(1440, (int) ceil((from_db($b['end_at'])->getTimestamp() - $dayStart) / 60));
                  if ($en <= $s) continue; ?>
                <a href="<?= e(base_path()) ?>admin/bookings.php?date=<?= e($date) ?>#b-<?= (int) $b['id'] ?>"
                   data-tl-left="<?= $s ?>" data-tl-width="<?= $en - $s ?>"
                   class="absolute inset-y-0 overflow-hidden rounded-md border px-2 text-[11px] leading-tight font-semibold whitespace-nowrap transition hover:brightness-125 <?= $bar[$b['status']] ?? '' ?>"
                   title="<?= e($b['name'] . ' · ' . fmt_time(from_db($b['start_at'])) . '–' . fmt_time(from_db($b['end_at'])) . ' · ' . (STATUS_LABEL[$b['status']] ?? '')) ?>">
                  <span class="block truncate"><?= e($b['name']) ?></span>
                  <span class="block truncate tabular-nums opacity-75"><?= fmt_time(from_db($b['start_at'])) ?>–<?= fmt_time(from_db($b['end_at'])) ?></span>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if ($nowMin >= 0 && $nowMin <= 1440): ?>
          <div class="pointer-events-none absolute inset-y-0 right-0 left-20" aria-hidden="true">
            <div class="absolute inset-y-0 w-0.5 bg-red-500" data-tl-left="<?= $nowMin ?>"></div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <p class="mt-3 text-xs text-neutral-400">Нажмите на бронь, чтобы открыть её в списке с кнопками действий.</p>
</div>
<?php admin_page_end();
