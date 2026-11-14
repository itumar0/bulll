<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/views/icons.php';
send_security_headers();
header('Content-Type: text/html; charset=utf-8');

$wa = (string) cfg('whatsapp_number');
try {
    $statuses = table_statuses(false);
} catch (Throwable $e) {
    log_error('index', $e);
    $statuses = [];
}

function status_label(array $t): string
{
    if ($t['state'] === 'busy' && $t['busyUntil']) return 'Занят до ' . fmt_time(new DateTimeImmutable($t['busyUntil']));
    if ($t['state'] === 'soon' && $t['nextStart']) return 'Бронь с ' . fmt_time(new DateTimeImmutable($t['nextStart']));
    return 'Свободен';
}
$badge = [
    'free' => ['ring' => 'bg-emerald-400/10 border-emerald-400/20 text-emerald-300', 'dot' => 'bg-emerald-400'],
    'busy' => ['ring' => 'bg-red-500/10 border-red-500/25 text-red-300', 'dot' => 'bg-red-500 animate-pulse-dot'],
    'soon' => ['ring' => 'bg-amber-400/10 border-amber-400/25 text-amber-200', 'dot' => 'bg-amber-400 animate-pulse-dot'],
];
$freeCount = count(array_filter($statuses, fn ($t) => $t['state'] !== 'busy'));
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'SportsActivityLocation',
    'name' => CLUB['name'],
    'description' => CLUB['description'],
    'url' => site_url(),
    'telephone' => '+996506056556',
    'address' => ['@type' => 'PostalAddress', 'streetAddress' => CLUB['street'], 'addressLocality' => CLUB['city'], 'addressCountry' => 'KG'],
    'openingHours' => 'Mo-Su 00:00-24:00',
    'sameAs' => [CLUB['instagram'], CLUB['map_url']],
];

require __DIR__ . '/includes/views/head.php';
?>
<body class="min-h-dvh bg-ink-950 text-white"
      data-wa="<?= e($wa) ?>"
      data-today="<?= e(today_local()) ?>"
      data-base="<?= e(base_path()) ?>">
<a href="#main" class="sr-only z-[60] rounded-lg bg-accent-600 px-4 py-2 focus:not-sr-only focus:fixed focus:top-3 focus:left-3">Перейти к содержимому</a>

<header class="fixed inset-x-0 top-0 z-50 border-b border-white/[0.06] bg-ink-950/75 backdrop-blur-xl">
  <div class="container-x flex h-16 items-center justify-between">
    <?= logo_html() ?>
    <nav aria-label="Основная навигация" class="hidden md:block">
      <ul class="flex items-center gap-1">
        <?php foreach (['#tables' => 'Столы', '#prices' => 'Цены', '#booking' => 'Бронь', '#contact' => 'Связь', '#contacts' => 'Контакты'] as $href => $label): ?>
          <li><a href="<?= $href ?>" class="rounded-lg px-3 py-2 text-sm font-medium text-mute transition-colors hover:text-white"><?= e($label) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <div class="flex items-center gap-1">
      <a href="<?= e(CLUB['instagram']) ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram клуба" class="hidden rounded-lg p-2 text-mute transition-colors hover:text-white sm:inline-flex"><?= icon('instagram') ?></a>
      <a href="#booking" class="<?= btn_class('primary', 'sm') ?>">Забронировать</a>
    </div>
  </div>
</header>

<main id="main">
  <!-- Hero -->
  <section aria-labelledby="hero-title" class="relative isolate overflow-hidden pt-28 pb-20 sm:pt-36 sm:pb-28">
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10">
      <div class="absolute -top-40 right-[-20%] h-[38rem] w-[38rem] rounded-full bg-accent-600/25 blur-[120px]"></div>
      <div class="absolute bottom-[-30%] left-[-20%] h-[28rem] w-[28rem] rounded-full bg-accent-700/15 blur-[120px]"></div>
      <div class="grain absolute inset-0"></div>
      <div class="absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-accent-500/40 to-transparent"></div>
    </div>
    <div class="container-x grid items-center gap-12 lg:grid-cols-[1.2fr_0.8fr]">
      <div>
        <p class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.03] px-4 py-1.5 text-xs font-semibold text-soft">
          <span class="h-1.5 w-1.5 rounded-full bg-accent-500 shadow-glow-sm" aria-hidden="true"></span>
          <?= e(CLUB['city']) ?> · <?= e(CLUB['hours_label']) ?>
        </p>
        <h1 id="hero-title" class="text-[3.4rem] leading-[1.02] font-extrabold tracking-[-0.04em] sm:text-8xl lg:text-[7.5rem]">
          Бильярд<br>
          <span class="hero-gradient bg-gradient-to-r from-accent-500 to-accent-600 bg-clip-text text-transparent">СТАРТ</span>
        </h1>
        <p class="mt-6 max-w-xl text-lg leading-relaxed text-soft sm:text-xl"><?= e(CLUB['slogan']) ?></p>
        <p class="mt-2 max-w-xl text-base text-mute">Профессиональные столы, VIP-комнаты для компаний и бронь онлайн за минуту.</p>
        <div class="mt-9 flex flex-col gap-3 sm:flex-row">
          <a href="#booking" class="<?= btn_class('primary', 'lg') ?>">Забронировать <?= icon('arrow') ?></a>
          <a href="<?= e(wa_link($wa, 'Здравствуйте! Хочу забронировать стол 🎱')) ?>" target="_blank" rel="noopener noreferrer" class="<?= btn_class('whatsapp', 'lg') ?>" aria-label="Написать в WhatsApp (откроется в новой вкладке)">
            <?= icon('whatsapp', 'h-5 w-5 text-[#25D366]') ?> Написать в WhatsApp
          </a>
        </div>
        <dl class="mt-12 grid max-w-md grid-cols-3 gap-6 border-t border-white/[0.08] pt-8">
          <?php foreach ([['6', 'профессиональных столов'], ['2', 'VIP-комнаты'], ['24/7', 'без выходных']] as [$v, $l]): ?>
            <div><dt class="sr-only"><?= e($l) ?></dt><dd class="text-3xl font-extrabold tracking-tight"><?= e($v) ?></dd><dd class="mt-1 text-xs leading-snug text-mute"><?= e($l) ?></dd></div>
          <?php endforeach; ?>
        </dl>
      </div>
      <div aria-hidden="true" class="relative mx-auto hidden aspect-square w-full max-w-sm lg:block">
        <div class="absolute inset-8 rounded-full bg-accent-600/30 blur-3xl"></div>
        <svg viewBox="0 0 200 200" class="ball-float relative h-full w-full">
          <defs>
            <radialGradient id="ball" cx="35%" cy="30%" r="75%"><stop offset="0%" stop-color="#3a3a3a"/><stop offset="45%" stop-color="#141414"/><stop offset="100%" stop-color="#000"/></radialGradient>
            <radialGradient id="rim" cx="50%" cy="50%" r="50%"><stop offset="80%" stop-color="transparent"/><stop offset="100%" stop-color="#22C55E" stop-opacity="0.55"/></radialGradient>
          </defs>
          <circle cx="100" cy="100" r="96" fill="url(#ball)"/>
          <circle cx="100" cy="100" r="96" fill="url(#rim)"/>
          <circle cx="88" cy="84" r="36" fill="#fff"/>
          <text x="88" y="98" text-anchor="middle" font-size="40" font-weight="800" fill="#0A0A0A" font-family="Manrope, sans-serif">8</text>
          <ellipse cx="62" cy="48" rx="22" ry="11" fill="#fff" opacity="0.12" transform="rotate(-30 62 48)"/>
        </svg>
      </div>
    </div>
  </section>

  <!-- Why us -->
  <section aria-labelledby="why-title" class="pt-20 sm:pt-28">
    <div class="container-x">
      <?= section_heading('why-title', 'Почему «Бильярд Старт»', 'Всё для хорошей игры', 'Удобные столы, отдельные VIP-комнаты и бронь без звонков — приходите и играйте.') ?>
      <ul class="grid gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-4">
        <?php foreach ([
            ['target', 'Профессиональные столы', '6 столов для игры с друзьями — свободные места видно на сайте в реальном времени.'],
            ['crown', 'VIP-комнаты', '2 отдельные комнаты до 10 гостей — для компании, дня рождения или встречи с коллегами.'],
            ['bolt', 'Бронь за минуту', 'Выберите стол и время онлайн — занятые слоты сразу видно, подтверждение приходит в WhatsApp.'],
            ['clock', 'Работаем 24/7', 'Круглосуточно и без выходных — приходите днём, вечером или ночью.'],
        ] as $i => [$ic, $title, $text]): ?>
          <li class="reveal card flex h-full gap-4 p-5 transition-colors hover:border-accent-500/30 sm:block sm:p-6" data-delay="<?= $i ?>">
            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-accent-600/15 text-accent-500 shadow-glow-sm sm:mb-5"><?= icon($ic) ?></span>
            <div><h3 class="text-lg font-bold"><?= e($title) ?></h3><p class="mt-1.5 text-sm leading-relaxed text-mute sm:mt-2"><?= e($text) ?></p></div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

  <!-- Tables -->
  <section id="tables" aria-labelledby="tables-title" class="py-20 sm:py-28">
    <div class="container-x">
      <?= section_heading('tables-title', 'Наши столы', '6 столов и 2 VIP-комнаты', 'Статус каждого стола обновляется в реальном времени. Нажмите на свободный стол, чтобы забронировать его.') ?>
      <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-mute" aria-live="polite" id="free-count">
          <?php if ($statuses): ?>Сейчас свободно: <span class="font-bold text-white"><?= $freeCount ?></span> из <?= count($statuses) ?><?php else: ?>Загружаем статусы…<?php endif; ?>
        </p>
        <p class="flex items-center gap-2 text-xs text-neutral-400"><span class="h-1.5 w-1.5 animate-pulse-dot rounded-full bg-accent-500" aria-hidden="true"></span> Обновляется автоматически</p>
      </div>
      <ul class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4" id="tables-list">
        <?php foreach (TABLES as $id => $t):
            $s = null;
            foreach ($statuses as $row) if ($row['id'] === $id) $s = $row;
            $state = $s['state'] ?? 'free';
            $vip = $t['type'] === 'VIP';
            ?>
          <li>
            <button type="button" data-pick-table="<?= e($id) ?>" class="group card relative flex h-full w-full flex-col overflow-hidden p-4 text-left transition-all duration-300 hover:-translate-y-0.5 hover:border-accent-500/40 hover:shadow-glow sm:p-6 <?= $vip ? 'bg-gradient-to-br from-ink-900 to-accent-950/40' : '' ?>">
              <?php if ($vip): ?><span aria-hidden="true" class="absolute -top-10 -right-10 h-28 w-28 rounded-full bg-accent-600/20 blur-2xl"></span><?php endif; ?>
              <span class="mb-6 flex items-start justify-between gap-2">
                <span class="text-xs font-bold tracking-[0.18em] text-neutral-400 uppercase"><?= $vip ? 'VIP-комната' : 'Бильярдный стол' ?></span>
                <?php if ($vip): ?><?= icon('crown', 'h-4 w-4 text-accent-500') ?><?php endif; ?>
              </span>
              <span class="text-2xl font-extrabold tracking-tight sm:text-3xl"><?= e($t['name']) ?></span>
              <span class="mt-1 flex items-center gap-1.5 text-xs text-mute"><?= icon('users', 'h-3.5 w-3.5') ?> до <?= (int) $t['capacity'] ?> гостей</span>
              <span class="mt-0.5 text-xs text-mute"><?= number_format($vip ? CLUB['price_vip'] : CLUB['price_regular'], 0, '', ' ') ?> сом/час</span>
              <span class="mt-5 whitespace-nowrap" data-status-for="<?= e($id) ?>">
                <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold <?= $badge[$state]['ring'] ?>">
                  <span class="h-2 w-2 rounded-full <?= $badge[$state]['dot'] ?>" aria-hidden="true"></span><?= e($s ? status_label($s) : 'Свободен') ?>
                </span>
              </span>
              <span class="mt-4 text-xs font-semibold text-accent-400 transition-colors group-hover:text-accent-300">Забронировать<span class="sr-only"> <?= e($t['name']) ?></span> <span aria-hidden="true">→</span></span>
            </button>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

  <!-- Prices -->
  <section id="prices" aria-labelledby="prices-title" class="pb-20 sm:pb-28">
    <div class="container-x">
      <?= section_heading('prices-title', 'Цены', 'Простые и честные цены', 'Платите только за время игры. Забронируйте онлайн — стол будет ждать вас.') ?>
      <div class="grid gap-4 md:grid-cols-2">
        <?php foreach ([
            ['Стол', CLUB['price_regular'], '6 столов · до 6 гостей', ['Профессиональный стол', 'До 6 гостей за столом', 'Бронь от 1 до 5 часов'], false],
            ['VIP-комната', CLUB['price_vip'], '2 комнаты · до 10 гостей', ['Отдельная комната для компании', 'До 10 гостей', 'Бронь от 1 до 5 часов'], true],
        ] as [$name, $price, $note, $features, $vip]): ?>
          <div class="reveal relative overflow-hidden rounded-3xl border p-7 sm:p-9 <?= $vip ? 'border-accent-500/40 bg-gradient-to-br from-accent-950/60 via-ink-900 to-ink-900 shadow-glow' : 'border-white/[0.07] bg-ink-900' ?>">
            <?php if ($vip): ?>
              <span aria-hidden="true" class="absolute -top-16 -right-16 h-48 w-48 rounded-full bg-accent-600/25 blur-3xl"></span>
              <span class="absolute top-6 right-6 inline-flex items-center gap-1.5 rounded-full bg-accent-600 px-3 py-1 text-xs font-bold"><?= icon('crown', 'h-3.5 w-3.5') ?> Для компаний</span>
            <?php endif; ?>
            <h3 class="text-xl font-bold"><?= e($name) ?></h3>
            <p class="mt-1 text-sm text-mute"><?= e($note) ?></p>
            <p class="mt-6 flex items-baseline gap-2"><span class="text-5xl font-extrabold tracking-tight tabular-nums"><?= number_format($price, 0, '', ' ') ?></span><span class="text-mute">сом / час</span></p>
            <ul class="mt-6 space-y-3">
              <?php foreach ($features as $f): ?><li class="flex items-center gap-3 text-sm text-soft"><?= icon('check', 'h-4 w-4 shrink-0 text-accent-400') ?> <?= e($f) ?></li><?php endforeach; ?>
            </ul>
            <a href="#booking" class="<?= btn_class($vip ? 'primary' : 'outline', 'lg', 'mt-8 w-full') ?>">Забронировать <?= $vip ? 'VIP' : 'стол' ?></a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Booking -->
  <section id="booking" aria-labelledby="booking-title" class="relative py-20 sm:py-28">
    <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/10 to-transparent"></div>
    <div class="container-x">
      <?= section_heading('booking-title', 'Онлайн-бронирование', 'Забронируйте стол за минуту', 'Выберите стол, дату и время — занятые слоты недоступны. После брони откроется WhatsApp с готовым сообщением.') ?>

      <div id="booking-success" class="card hidden flex-col items-center p-8 text-center sm:p-12" role="status">
        <span class="grid h-16 w-16 place-items-center rounded-full bg-accent-600 shadow-glow"><?= icon('check', 'h-8 w-8') ?></span>
        <h3 class="mt-6 text-2xl font-extrabold sm:text-3xl">Бронь №<span id="success-id"></span> создана</h3>
        <p class="mt-3 max-w-md text-mute">Открываем WhatsApp с готовым сообщением — отправьте его, чтобы мы подтвердили бронь.</p>
        <a id="success-wa" href="#" class="mt-8 inline-flex h-14 items-center gap-2.5 rounded-2xl bg-[#25D366] px-7 font-bold text-ink-950 transition hover:brightness-110"><?= icon('whatsapp') ?> Открыть WhatsApp</a>
        <button type="button" id="booking-again" class="mt-4 text-sm text-mute underline-offset-4 hover:text-white hover:underline">Создать ещё одну бронь</button>
      </div>

      <form id="booking-form" novalidate class="card relative grid gap-8 p-5 sm:p-8 lg:grid-cols-2 lg:gap-10">
        <div aria-hidden="true" class="absolute -left-[9999px] h-px w-px overflow-hidden">
          <label for="bf-website">Ваш сайт</label>
          <input id="bf-website" name="website" tabindex="-1" autocomplete="off">
        </div>

        <div class="grid content-start gap-6">
          <div class="grid gap-5 sm:grid-cols-2">
            <div>
              <label for="bf-name" class="label">Имя</label>
              <input id="bf-name" name="name" class="field" autocomplete="given-name" placeholder="Как к вам обращаться" maxlength="60" required>
              <p class="field-error hidden" data-error-for="name" role="alert"></p>
            </div>
            <div>
              <label for="bf-phone" class="label">Телефон</label>
              <input id="bf-phone" name="phone" class="field tabular-nums" type="tel" inputmode="tel" autocomplete="tel" placeholder="+996 (___) ___-___" value="+996 " data-phone-mask required>
              <p class="field-error hidden" data-error-for="phone" role="alert"></p>
            </div>
          </div>

          <fieldset>
            <legend class="label">Стол</legend>
            <div role="radiogroup" aria-label="Выбор стола" class="grid grid-cols-6 gap-2" id="bf-tables">
              <?php foreach (TABLES as $id => $t): ?>
                <button type="button" role="radio" aria-checked="<?= $id === 't1' ? 'true' : 'false' ?>" data-table="<?= e($id) ?>" data-capacity="<?= (int) $t['capacity'] ?>" class="chip <?= $t['type'] === 'VIP' ? 'col-span-3' : 'col-span-2' ?>"><?= e($t['name']) ?></button>
              <?php endforeach; ?>
            </div>
          </fieldset>

          <div class="grid grid-cols-2 gap-5">
            <div>
              <label for="bf-date" class="label">Дата</label>
              <input id="bf-date" name="date" type="date" class="field" required>
              <p class="mt-2 text-xs text-neutral-400" id="bf-date-human"></p>
              <p class="field-error hidden" data-error-for="date" role="alert"></p>
            </div>
            <div>
              <label for="bf-guests" class="label">Гостей</label>
              <div class="flex h-[54px] items-center rounded-xl border border-ink-700 bg-ink-900">
                <button type="button" class="h-full w-12 text-xl text-soft hover:text-white disabled:opacity-30" data-guests="-1" aria-label="Меньше гостей">−</button>
                <input id="bf-guests" name="guests" type="number" inputmode="numeric" min="1" max="6" value="2" class="number-plain h-full w-full min-w-0 bg-transparent text-center font-bold tabular-nums outline-none">
                <button type="button" class="h-full w-12 text-xl text-soft hover:text-white disabled:opacity-30" data-guests="1" aria-label="Больше гостей">+</button>
              </div>
              <p class="mt-2 text-xs text-neutral-400">максимум <span id="bf-capacity">6</span></p>
              <p class="field-error hidden" data-error-for="guests" role="alert"></p>
            </div>
          </div>

          <div>
            <label for="bf-comment" class="label">Комментарий <span class="font-normal text-neutral-400">— необязательно</span></label>
            <textarea id="bf-comment" name="comment" class="field min-h-24 resize-y" maxlength="500" placeholder="День рождения, нужен кий определённого веса…"></textarea>
            <p class="field-error hidden" data-error-for="comment" role="alert"></p>
          </div>
        </div>

        <div class="grid content-start gap-6">
          <fieldset>
            <legend class="label">Длительность</legend>
            <div role="radiogroup" aria-label="Длительность" class="grid grid-cols-5 gap-2" id="bf-durations">
              <?php for ($h = MIN_DURATION_H; $h <= MAX_DURATION_H; $h++): ?>
                <button type="button" role="radio" aria-checked="<?= $h === 2 ? 'true' : 'false' ?>" data-duration="<?= $h ?>" class="chip"><?= $h ?> ч</button>
              <?php endfor; ?>
            </div>
            <p class="field-error hidden" data-error-for="durationHours" role="alert"></p>
          </fieldset>

          <fieldset>
            <legend class="label flex w-full items-center justify-between" id="bf-start-legend" tabindex="-1">
              <span>Время начала</span>
              <span class="text-xs font-normal text-neutral-400" aria-live="polite">свободно слотов: <span id="bf-free">—</span></span>
            </legend>
            <div role="radiogroup" aria-label="Время начала" class="grid grid-cols-5 gap-1.5 sm:grid-cols-6 sm:gap-2" id="bf-slots"></div>
            <p class="field-error hidden" data-error-for="startMinutes" role="alert"></p>
            <p class="mt-3 hidden text-sm text-amber-200" id="bf-no-slots">На эту дату нет свободного времени для выбранного стола и длительности. Попробуйте другой стол или уменьшите длительность.</p>
          </fieldset>

          <div class="rounded-2xl border border-white/[0.07] bg-ink-950/60 p-5">
            <p class="text-sm text-mute">Ваша бронь</p>
            <p class="mt-1 text-lg font-bold" id="bf-summary-title">—</p>
            <p class="mt-0.5 text-2xl font-extrabold tabular-nums text-accent-500" id="bf-summary-time">—:— – —:—</p>
          </div>

          <p id="bf-form-error" role="alert" class="hidden rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200"></p>

          <button type="submit" class="<?= btn_class('primary', 'lg', 'w-full') ?>" id="bf-submit">Забронировать и отправить в WhatsApp</button>
          <p class="-mt-3 text-center text-xs text-neutral-400">Нажимая кнопку, вы соглашаетесь на обработку персональных данных для связи по брони.</p>
        </div>
      </form>
    </div>
  </section>

  <!-- Quick contact -->
  <section id="contact" aria-labelledby="contact-title" class="py-20 sm:py-28">
    <div class="container-x grid gap-10 lg:grid-cols-[0.8fr_1.2fr] lg:gap-16">
      <?= section_heading('contact-title', 'Быстрая связь', 'Есть вопрос? Напишите нам', 'Ответим в WhatsApp в течение нескольких минут: цены, турниры, корпоративы и дни рождения.') ?>
      <form id="quick-form" novalidate class="card grid gap-5 p-5 sm:p-8">
        <div class="grid gap-5 sm:grid-cols-2">
          <div>
            <label for="qc-name" class="label">Имя</label>
            <input id="qc-name" class="field" autocomplete="given-name" maxlength="60" placeholder="Ваше имя">
            <p class="field-error hidden" data-error-for="name" role="alert"></p>
          </div>
          <div>
            <label for="qc-phone" class="label">Телефон</label>
            <input id="qc-phone" class="field tabular-nums" type="tel" inputmode="tel" autocomplete="tel" value="+996 " data-phone-mask>
            <p class="field-error hidden" data-error-for="phone" role="alert"></p>
          </div>
        </div>
        <div>
          <label for="qc-message" class="label">Сообщение</label>
          <textarea id="qc-message" class="field min-h-32 resize-y" maxlength="1000" placeholder="Вопрос о ценах, турнирах, корпоративе…"></textarea>
          <p class="field-error hidden" data-error-for="message" role="alert"></p>
        </div>
        <button type="submit" class="inline-flex h-14 items-center justify-center gap-2.5 rounded-2xl bg-[#25D366] px-7 font-bold text-ink-950 transition hover:brightness-110 active:scale-[0.98]"><?= icon('whatsapp') ?> Отправить в WhatsApp</button>
      </form>
    </div>
  </section>

  <!-- Contacts -->
  <section id="contacts" aria-labelledby="contacts-title" class="pt-10 pb-20 sm:pb-28">
    <div class="container-x">
      <?= section_heading('contacts-title', 'Контакты', 'Ждём вас в клубе', 'Подписывайтесь на наш Instagram, чтобы не пропустить турниры и акции. Маршрут до клуба — в 2ГИС.') ?>
      <ul class="grid gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3">
        <?php foreach ([
            ['pin', 'Адрес', CLUB['address'], null, CLUB['map_url'], 'Открыть в 2ГИС'],
            ['clock', 'Часы работы', CLUB['hours_label'], 'Без выходных и перерывов', null, null],
            ['phone', 'Телефон', CLUB['phone_display'], null, CLUB['phone_href'], 'Позвонить'],
            ['whatsapp', 'WhatsApp', '+' . $wa, null, wa_link($wa), 'Написать'],
            ['instagram', 'Instagram', CLUB['instagram_handle'], 'Фото зала, турниры и акции', CLUB['instagram'], 'Подписаться'],
            ['map', '2ГИС', 'Маршрут до клуба', 'Отзывы и как добраться', CLUB['map_url'], 'Построить маршрут'],
        ] as [$ic, $title, $value, $sub, $href, $cta]): ?>
          <li class="reveal card flex gap-4 p-5 transition-colors hover:border-accent-500/30 sm:flex-col sm:gap-0 sm:p-6">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-accent-600/15 text-accent-500 sm:mb-5"><?= icon($ic) ?></span>
            <div class="flex min-w-0 flex-1 flex-col">
              <h3 class="text-sm font-medium text-mute"><?= e($title) ?></h3>
              <p class="mt-1 text-lg leading-snug font-bold break-words"><?= e($value) ?></p>
              <?php if ($sub): ?><p class="mt-1 text-sm text-neutral-400"><?= e($sub) ?></p><?php endif; ?>
              <?php if ($href): ?>
                <a href="<?= e($href) ?>"<?= str_starts_with($href, 'http') ? ' target="_blank" rel="noopener noreferrer"' : '' ?> class="mt-auto pt-3 text-sm font-semibold text-accent-400 hover:text-accent-300 sm:pt-4"><?= e($cta) ?> →</a>
              <?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>
</main>

<footer class="border-t border-white/[0.06] py-10 pb-28 md:pb-10">
  <div class="container-x flex flex-col items-start justify-between gap-6 sm:flex-row sm:items-center">
    <div>
      <?= logo_html() ?>
      <p class="mt-3 text-sm text-neutral-400">© <?= date('Y') ?> <?= e(CLUB['name']) ?>. <?= e(CLUB['address']) ?> · <?= e(CLUB['hours_label']) ?></p>
    </div>
    <div class="flex items-center gap-2 text-sm text-mute">
      <a href="<?= e(CLUB['instagram']) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-lg p-2 hover:text-white"><?= icon('instagram') ?> Instagram</a>
      <a href="<?= e(CLUB['map_url']) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-lg p-2 hover:text-white"><?= icon('map') ?> 2ГИС</a>
    </div>
  </div>
</footer>

<div id="mobile-cta" class="fixed inset-x-0 bottom-0 z-40 translate-y-full border-t border-white/10 bg-ink-950/90 p-3 pb-safe backdrop-blur-xl transition-transform duration-300 md:hidden" aria-hidden="true">
  <div class="flex gap-2">
    <a href="#booking" tabindex="-1" class="flex h-12 flex-1 items-center justify-center rounded-xl bg-accent-600 font-semibold shadow-glow active:scale-[0.98]">Забронировать стол</a>
    <a href="<?= e(wa_link($wa, 'Здравствуйте! Хочу забронировать стол 🎱')) ?>" target="_blank" rel="noopener noreferrer" tabindex="-1" aria-label="Написать в WhatsApp" class="grid h-12 w-12 place-items-center rounded-xl bg-[#25D366] text-ink-950 active:scale-[0.98]"><?= icon('whatsapp') ?></a>
  </div>
</div>

<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<script src="<?= e(asset('site.js')) ?>" defer></script>
</body>
</html>
