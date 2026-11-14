<?php
/** Revenue report for a day / week / month, with a chart, per-table breakdown and CSV export. */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/views/icons.php';
require __DIR__ . '/../includes/views/admin.php';
require_admin_page();

$type = isset(REPORT_TYPES[$_GET['type'] ?? '']) ? $_GET['type'] : 'day';
$period = report_period($type, (string) ($_GET['date'] ?? today_local()));
$data = report_summary($period);
$sum = $data['sum'];

/* ------------------------------- CSV export ------------------------------- */
if (($_GET['export'] ?? '') === 'csv') {
    send_security_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Cache-Control: no-store');
    header('Content-Disposition: attachment; filename="otchet-' . $period['type'] . '-' . $period['from'] . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows Cyrillic correctly
    fputcsv($out, ['№', 'Дата', 'Начало', 'Конец', 'Стол', 'Клиент', 'Телефон', 'Гостей', 'Часы', 'Цена за час', 'Сумма', 'Статус', 'Источник', 'Комментарий'], ';');
    foreach ($data['rows'] as $b) {
        $s = from_db($b['start_at']);
        fputcsv($out, array_map('csv_cell', [
            $b['id'], fmt_date($s), fmt_time($s), fmt_time(from_db($b['end_at'])), TABLES[$b['table_id']]['name'] ?? $b['table_id'],
            $b['name'], local_phone($b['phone']), $b['guests'], number_format($b['hours'], 2, ',', ''), $b['price'], $b['amount'],
            STATUS_LABEL[$b['status']] ?? $b['status'], $b['source'] === 'ADMIN' ? 'вручную' : 'сайт', (string) $b['comment'],
        ]), ';');
    }
    fputcsv($out, [], ';');
    fputcsv($out, ['Итого выручка (подтверждённые и завершённые)', '', '', '', '', '', '', '', number_format($sum['hours'], 2, ',', ''), '', $sum['revenue']], ';');
    fclose($out);
    exit;
}

$prevRevenue = report_previous_revenue($period);
$delta = $prevRevenue > 0 ? ($sum['revenue'] - $prevRevenue) / $prevRevenue * 100 : null;
$q = fn (array $p) => '?' . http_build_query($p + ['type' => $period['type'], 'date' => $period['from']]);
$prevLabel = ['day' => 'вчера', 'week' => 'прошлой неделе', 'month' => 'прошлом месяце'][$period['type']];

// Chart: by day for week/month, by table for a single day.
if ($period['type'] === 'day') {
    $chart = array_map(fn ($t) => ['label' => $t['name'], 'short' => str_replace('Стол ', 'С', $t['name']), 'value' => $t['revenue'], 'hint' => $t['count'] . ' брон.'], array_values($data['byTable']));
    $chartTitle = 'Выручка по столам';
} else {
    $chart = [];
    foreach ($data['byDay'] as $ymd => $d) {
        $dt = local_date($ymd);
        $chart[] = [
            'label' => (int) $dt->format('j') . ' ' . MONTHS_GEN[(int) $dt->format('n') - 1] . ', ' . WEEKDAYS_SHORT[(int) $dt->format('N') - 1],
            'short' => $period['type'] === 'week' ? WEEKDAYS_SHORT[(int) $dt->format('N') - 1] : $dt->format('j'),
            'value' => $d['revenue'], 'hint' => $d['count'] . ' брон.', 'date' => $ymd,
        ];
    }
    $chartTitle = 'Выручка по дням';
}
$max = max(1, ...array_column($chart, 'value'));
$tableMax = max(1, ...array_column($data['byTable'], 'revenue'));

admin_page_start('Отчёты', 'reports', true);
echo flash_html();
?>
<div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
  <h1 class="text-2xl font-extrabold tracking-tight">Отчёт о выручке</h1>
  <nav aria-label="Период" class="grid grid-cols-3 rounded-xl border border-white/10 bg-ink-900 p-1 sm:inline-grid">
    <?php foreach (REPORT_TYPES as $t => $label): ?>
      <a href="?type=<?= $t ?>&amp;date=<?= e($period['from']) ?>"<?= $t === $period['type'] ? ' aria-current="page"' : '' ?>
         class="rounded-lg px-4 py-2 text-center text-sm font-semibold transition <?= $t === $period['type'] ? 'bg-accent-600 text-white shadow-glow-sm' : 'text-mute hover:text-white' ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </nav>
</div>

<div class="mb-6 flex items-center gap-2">
  <a href="<?= e($q(['date' => $period['prev']])) ?>" class="<?= btn_class('outline', 'sm', 'h-11 w-11 shrink-0 px-0') ?>" aria-label="Предыдущий период">←</a>
  <div class="min-w-0 flex-1 text-center">
    <p class="truncate text-lg font-bold sm:text-xl"><?= e($period['title']) ?></p>
    <?php if (!$period['isCurrent']): ?><a href="?type=<?= $period['type'] ?>" class="text-xs text-accent-400 hover:text-accent-300">к текущему периоду</a><?php else: ?><p class="text-xs text-mute">текущий период</p><?php endif; ?>
  </div>
  <a href="<?= e($q(['date' => $period['next']])) ?>" class="<?= btn_class('outline', 'sm', 'h-11 w-11 shrink-0 px-0') ?>" aria-label="Следующий период">→</a>
</div>

<div id="live" data-live>
  <!-- KPIs -->
  <section aria-label="Итоги" class="grid grid-cols-2 gap-3 lg:grid-cols-5">
    <div class="col-span-2 rounded-2xl border border-accent-500/40 bg-gradient-to-br from-accent-950/70 to-ink-900 p-5 shadow-glow lg:col-span-1">
      <p class="text-sm text-mute">Выручка</p>
      <p class="mt-1 text-3xl font-extrabold tracking-tight tabular-nums sm:text-4xl"><?= money($sum['revenue']) ?></p>
      <p class="mt-2 text-xs <?= $delta === null ? 'text-mute' : ($delta >= 0 ? 'text-accent-300' : 'text-red-300') ?>">
        <?php if ($delta === null): ?>нет данных за прошлый период<?php else: ?><?= $delta >= 0 ? '▲' : '▼' ?> <?= number_format(abs($delta), 0, ',', ' ') ?>% к <?= $prevLabel ?> (<?= money($prevRevenue) ?>)<?php endif; ?>
      </p>
      <p class="mt-3 flex flex-wrap gap-x-4 gap-y-1 border-t border-white/10 pt-3 text-xs text-soft tabular-nums">
        <span>Столы: <b><?= money($sum['regularRevenue']) ?></b></span>
        <span>VIP: <b class="text-accent-300"><?= money($sum['vipRevenue']) ?></b></span>
      </p>
    </div>
    <?php foreach ([
        ['Часы игры', hours_label($sum['hours']), 'по подтверждённым броням'],
        ['Броней', (string) $sum['count'], $sum['online'] . ' с сайта · ' . $sum['walkins'] . ' вручную'],
        ['Средний чек', money($sum['avgCheck']), $sum['guests'] . ' гостей всего'],
        ['Загрузка столов', number_format($sum['occupancy'], 0) . '%', 'от 8 столов × 24 ч'],
    ] as [$label, $value, $sub]): ?>
      <div class="card p-4 sm:p-5">
        <p class="text-sm text-mute"><?= e($label) ?></p>
        <p class="mt-1 text-xl font-extrabold tabular-nums sm:text-2xl"><?= e($value) ?></p>
        <p class="mt-1 text-xs text-neutral-400"><?= e($sub) ?></p>
      </div>
    <?php endforeach; ?>
  </section>

  <?php if ($sum['pendingCount'] || $sum['cancelled']): ?>
    <div class="mt-3 grid gap-3 sm:grid-cols-2">
      <?php if ($sum['pendingCount']): ?>
        <a href="<?= e(base_path()) ?>admin/bookings.php?status=PENDING&amp;date=<?= $period['type'] === 'day' ? e($period['from']) : '' ?>" class="rounded-2xl border border-amber-400/30 bg-amber-400/[0.06] p-4 text-sm transition hover:border-amber-400/60">
          <span class="font-bold text-amber-200">Ожидают подтверждения: <?= $sum['pendingCount'] ?> на <?= money($sum['pendingAmount']) ?></span>
          <span class="mt-1 block text-mute">Не входят в выручку, пока вы их не подтвердите →</span>
        </a>
      <?php endif; ?>
      <?php if ($sum['cancelled']): ?>
        <div class="card p-4 text-sm"><span class="font-bold">Отменено броней: <?= $sum['cancelled'] ?></span><span class="mt-1 block text-mute">Не входят в выручку</span></div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- Chart -->
  <section class="card mt-6 p-4 sm:p-6" aria-labelledby="chart-title">
    <h2 id="chart-title" class="mb-4 text-base font-bold"><?= e($chartTitle) ?></h2>
    <ul class="flex h-48 items-end gap-1 sm:h-56 sm:gap-2" role="list">
      <?php foreach ($chart as $i => $c):
          $pct = $c['value'] / $max * 100;
          $isToday = ($c['date'] ?? '') === today_local(); ?>
        <li class="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-1" title="<?= e($c['label'] . ': ' . money($c['value']) . ', ' . $c['hint']) ?>">
          <?php if ($c['value'] > 0 && (count($chart) <= 8)): ?><span class="text-[10px] text-mute tabular-nums sm:text-xs"><?= number_format($c['value'] / 1000, $c['value'] >= 10000 ? 0 : 1, ',', '') ?>k</span><?php endif; ?>
          <span class="relative w-full flex-1"><span class="absolute inset-x-0 bottom-0 rounded-t-md <?= $c['value'] > 0 ? 'bg-gradient-to-t from-accent-700 to-accent-500' : 'bg-white/10' ?> <?= $isToday ? 'ring-2 ring-white/60' : '' ?>" data-bar-h="<?= $c['value'] > 0 ? max(3, round($pct, 1)) : 2 ?>"></span></span>
          <span class="h-4 w-full overflow-visible text-center text-[10px] whitespace-nowrap text-neutral-400 tabular-nums sm:text-xs <?= $period['type'] === 'month' && !in_array((int) $c['short'], [1, 5, 10, 15, 20, 25, 30], true) ? 'invisible' : '' ?>"><?= e($c['short']) ?></span>
          <span class="sr-only"><?= e($c['label'] . ': ' . money($c['value'])) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>

  <!-- By table -->
  <section class="card mt-6 p-4 sm:p-6" aria-labelledby="tables-title">
    <h2 id="tables-title" class="mb-4 text-base font-bold">По столам</h2>
    <ul class="grid gap-3">
      <?php foreach ($data['byTable'] as $t): ?>
        <li>
          <div class="flex items-baseline justify-between gap-3 text-sm">
            <span class="font-semibold"><?= e($t['name']) ?><?php if ($t['type'] === 'VIP'): ?> <span class="text-xs text-accent-400">VIP</span><?php endif; ?></span>
            <span class="tabular-nums"><span class="font-bold"><?= money($t['revenue']) ?></span> <span class="text-xs text-neutral-400">· <?= hours_label($t['hours']) ?> · <?= $t['count'] ?> брон.</span></span>
          </div>
          <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-white/5"><div class="h-full rounded-full bg-accent-500" data-bar-w="<?= round($t['revenue'] / $tableMax * 100, 1) ?>"></div></div>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>

  <!-- Bookings of the period -->
  <details class="card mt-6 p-4 sm:p-6">
    <summary class="-my-2 cursor-pointer py-2 text-base font-bold">Все брони периода (<?= count($data['rows']) ?>)</summary>
    <ul class="mt-4 divide-y divide-white/[0.06] text-sm">
      <?php foreach ($data['rows'] as $b): $s = from_db($b['start_at']); ?>
        <li class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 py-2.5">
          <span class="min-w-0">
            <span class="font-semibold"><?= e(TABLES[$b['table_id']]['name'] ?? '') ?></span>
            <span class="text-mute"> · <?= fmt_date($s) ?> <?= fmt_time($s) ?>–<?= fmt_time(from_db($b['end_at'])) ?> · <?= e($b['name']) ?></span>
          </span>
          <span class="flex items-center gap-2">
            <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold <?= STATUS_PILL[$b['status']] ?? '' ?>"><?= STATUS_LABEL[$b['status']] ?? '' ?></span>
            <span class="font-bold tabular-nums <?= in_array($b['status'], ['CONFIRMED', 'COMPLETED'], true) ? '' : 'text-neutral-500' ?>"><?= money($b['amount']) ?></span>
          </span>
        </li>
      <?php endforeach; ?>
      <?php if (!$data['rows']): ?><li class="py-6 text-center text-mute">Броней за этот период нет</li><?php endif; ?>
    </ul>
  </details>
</div>

<div class="mt-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
  <a href="<?= e($q(['export' => 'csv'])) ?>" class="<?= btn_class('outline', 'md') ?>">Скачать для Excel (CSV)</a>
  <p class="text-xs text-neutral-400">Выручка = часы × цена за час (стол <?= money(CLUB['price_regular']) ?>, VIP <?= money(CLUB['price_vip']) ?>). Учитываются подтверждённые и завершённые брони.</p>
</div>
<?php admin_page_end();
