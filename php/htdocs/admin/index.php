<?php
/** Dashboard: 8 tables, green = free, red = busy, yellow = booking within the hour. */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/views/icons.php';
require __DIR__ . '/../includes/views/admin.php';
require_admin_page();

$tables = table_statuses(true);
$now = now_utc();
$theme = [
    'free' => ['border-emerald-500/30 bg-emerald-500/[0.06]', 'bg-emerald-400', 'Свободен', 'text-emerald-300'],
    'busy' => ['border-red-500/40 bg-red-600/[0.10]', 'bg-red-500 animate-pulse-dot', 'Занят', 'text-red-300'],
    'soon' => ['border-amber-400/40 bg-amber-400/[0.07]', 'bg-amber-400 animate-pulse-dot', 'Скоро бронь', 'text-amber-200'],
];
$count = array_count_values(array_column($tables, 'state'));

admin_page_start('Столы', 'index', true);
echo flash_html();
?>
<div id="live" data-live>
  <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-extrabold tracking-tight">Столы сейчас</h1>
    <p class="flex gap-4 text-sm" aria-live="polite">
      <span class="text-emerald-300">● свободно <?= (int) ($count['free'] ?? 0) ?></span>
      <span class="text-red-300">● занято <?= (int) ($count['busy'] ?? 0) ?></span>
      <span class="text-amber-200">● скоро <?= (int) ($count['soon'] ?? 0) ?></span>
    </p>
  </div>
  <ul class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <?php foreach ($tables as $t):
        [$card, $dot, $label, $text] = $theme[$t['state']];
        $cur = $t['current'] ? get_booking($t['current']['id']) : null;
        ?>
      <li class="flex flex-col rounded-2xl border p-4 <?= $card ?>">
        <div class="flex items-center justify-between">
          <h2 class="flex items-center gap-2 text-xl font-extrabold"><?= $t['type'] === 'VIP' ? icon('crown', 'h-4 w-4 text-accent-500') : '' ?> <?= e($t['name']) ?></h2>
          <span class="flex items-center gap-1.5 text-xs font-bold <?= $text ?>"><span class="h-2 w-2 rounded-full <?= $dot ?>" aria-hidden="true"></span><?= $label ?></span>
        </div>
        <?php if ($cur): ?>
          <div class="mt-4 space-y-1 text-sm">
            <p class="font-bold"><?= e($cur['name']) ?> <span class="font-normal text-mute">· <?= (int) $cur['guests'] ?> чел.</span></p>
            <p class="text-soft tabular-nums"><a href="tel:<?= e($cur['phone']) ?>" class="hover:underline"><?= e(pretty_phone($cur['phone'])) ?></a></p>
            <p class="text-mute tabular-nums"><?= fmt_time(from_db($cur['start_at'])) ?> – <?= fmt_time(from_db($cur['end_at'])) ?> · №<?= (int) $cur['id'] ?></p>
            <p class="pt-1 text-lg font-extrabold text-red-300 tabular-nums">осталось <?= fmt_remaining((new DateTimeImmutable($t['busyUntil']))->getTimestamp() - $now->getTimestamp()) ?></p>
          </div>
        <?php else: ?>
          <p class="mt-4 text-sm text-mute">Стол свободен</p>
        <?php endif; ?>
        <?php if ($t['next']):
            $ns = new DateTimeImmutable($t['next']['startAt']); ?>
          <p class="mt-3 rounded-lg bg-black/25 px-3 py-2 text-xs <?= $t['state'] === 'soon' ? 'text-amber-200' : 'text-mute' ?>">
            Следующая: <b class="tabular-nums"><?= fmt_time($ns) ?></b> — <?= e($t['next']['name']) ?>
            <?php if ($t['state'] === 'soon'): ?> (через <?= fmt_remaining($ns->getTimestamp() - $now->getTimestamp()) ?>)<?php endif; ?>
          </p>
        <?php endif; ?>
        <?php if ($cur): ?><div class="mt-4 border-t border-white/10 pt-3"><?= booking_actions($cur, true) ?></div><?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
  <p class="mt-4 text-xs text-neutral-400">Обновляется автоматически каждые 30 секунд.</p>
</div>
<?php admin_page_end();
