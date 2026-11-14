# «Бильярд Старт» — сайт бильярдного клуба

Сайт бильярдного клуба с онлайн-бронированием столов, отправкой заявок в WhatsApp, live-статусами столов и защищённой админ-панелью.

**Стек:** Next.js 15 (App Router) · TypeScript · Tailwind CSS v4 · Prisma + PostgreSQL · Zod · framer-motion · jose (JWT) · bcryptjs

---

## Возможности

**Публичная часть (mobile-first, на русском)**
- Hero-блок с кнопками «Забронировать» и «Написать в WhatsApp».
- «Наши столы»: 6 столов и 2 VIP-комнаты с живым статусом («Свободен», «Занят до 21:30», «Бронь с 20:00»). Обновляется каждые 30 секунд без перезагрузки.
- Форма бронирования: имя, телефон с маской `+996 (XXX) XXX-XXX`, стол, дата, время начала (слоты по 30 минут), длительность 1–5 ч, гости, комментарий. Занятые и прошедшие слоты выбрать нельзя.
- После сохранения брони пользователя перенаправляет на `https://wa.me/<WHATSAPP_NUMBER>?text=…` с готовым текстом брони.
- Форма быстрой связи сразу открывает WhatsApp.
- Контакты, часы работы, футер, SEO, Open Graph, JSON-LD, favicon, robots и sitemap.

**Админ-панель `/admin`**
- Вход по паролю. В `.env` хранится только bcrypt-хэш пароля.
- Дашборд из 8 карточек: зелёная — свободен, красная — занят, жёлтая — бронь в ближайший час. На карточке видно, кто занял стол, телефон и сколько осталось времени.
- Таблица всех броней: фильтр по дате, столу и статусу, поиск по имени или телефону.
- Таймлайн дня по всем 8 столам с линией текущего времени.
- Действия: подтвердить, отменить, завершить досрочно, продлить (+30 мин или +1 ч), создать бронь вручную (например, для гостей без брони, «начать сейчас»), «Написать клиенту в WhatsApp».

**Логика времени**
- Часовой пояс — `Asia/Bishkek` (UTC+6, без перехода на летнее время). В БД время хранится в UTC.
- Клуб работает с 12:00 до 02:00. Брони после полуночи относятся к «рабочему дню» предыдущей даты.
- Статус стола вычисляется по текущему времени: пока идёт бронь (`startAt ≤ now < endAt`), стол «Занят». Когда время брони вышло, стол сам становится «Свободен».
- Двойные брони исключены: пересечение проверяется на сервере в транзакции уровня `SERIALIZABLE` (PostgreSQL), конфликты автоматически повторяются. Защита работает и при нескольких серверах: в тесте из 20 одновременных запросов на один слот через два процесса прошёл ровно один. Интервалы полуоткрытые, поэтому брони встык допустимы.

---

## Быстрый старт (локально)

Нужны Node.js 20 или новее и PostgreSQL. Проще всего поднять PostgreSQL через Docker:

```bash
npm install
```

```bash
docker compose up -d
```

```bash
cp .env.example .env
```

Сгенерируйте хэш пароля и вставьте строку `ADMIN_PASSWORD_HASH=...` в `.env`:

```bash
npm run hash-password -- 1111
```

Сгенерируйте `JWT_SECRET` и вставьте его в `.env`:

```bash
node -e "console.log(require('crypto').randomBytes(48).toString('base64url'))"
```

Создайте таблицы (миграция сразу добавляет 8 столов) и, по желанию, тестовые брони:

```bash
npx prisma migrate deploy
```

```bash
npm run db:seed
```

```bash
npm run dev
```

Сайт откроется на http://localhost:3000, админка — на http://localhost:3000/admin (пароль `1111`, если вы сгенерировали хэш для него).

> ⚠️ **Знак `$` в хэше.** Next.js подставляет переменные вида `$VAR` внутри `.env`, поэтому каждый `$` в bcrypt-хэше нужно экранировать: `\$`. Скрипт `hash-password` сразу выводит строку в нужном формате. В панели Vercel хэш вставляется **без** экранирования. Также поддерживается хэш в base64.

### Переменные окружения

| Переменная | Описание |
|---|---|
| `DATABASE_URL` | Строка подключения PostgreSQL (на Neon — pooled) |
| `DIRECT_URL` | Прямое подключение для миграций (на Neon — адрес без `-pooler`) |
| `WHATSAPP_NUMBER` | Номер, на который уходят брони, только цифры: `996506056556` |
| `ADMIN_PASSWORD_HASH` | bcrypt-хэш пароля админа, `$` экранированы |
| `JWT_SECRET` | Секрет для подписи сессии, не короче 32 символов |
| `SITE_URL` | Публичный URL сайта (для SEO и Open Graph) |

### Скрипты

| Команда | Что делает |
|---|---|
| `npm run dev` | Dev-сервер |
| `npm run build` / `npm start` | Production-сборка и запуск |
| `npm run lint` / `npm run typecheck` | ESLint / TypeScript |
| `npm run db:migrate` | Новая миграция (dev) |
| `npm run db:deploy` | Применение миграций (prod) |
| `npm run db:seed` | Seed: 8 столов и тестовые брони |
| `npm run db:studio` | Prisma Studio |
| `npm run hash-password -- <пароль>` | bcrypt-хэш для `.env` |

---

## Структура проекта

```
prisma/
  schema.prisma          модели PoolTable и Booking (PostgreSQL)
  migrations/            SQL-миграции (init создаёт 8 столов)
  seed.ts                демо-брони для разработки
vercel.json              настройки сборки Vercel
docker-compose.yml       локальный PostgreSQL
scripts/hash-password.mjs
src/
  middleware.ts          CSP (nonce), security-заголовки, защита /admin и /api/admin, проверка Origin
  app/
    layout.tsx           шрифт, SEO-метаданные, JSON-LD
    page.tsx             главная страница
    opengraph-image.tsx  генерируемая OG-картинка
    icon.svg, robots.ts, sitemap.ts
    admin/               login/ и дашборд
    api/
      tables/status      GET   live-статусы (без персональных данных)
      availability       GET   занятые интервалы на дату (без персональных данных)
      bookings           POST  создание брони
      admin/login|logout|overview|bookings|bookings/[id]
  lib/
    config.ts            клуб, часы работы, столы, статусы
    time.ts              хелперы Asia/Bishkek (общие для клиента и сервера)
    phone.ts             маска и нормализация +996
    validation.ts        Zod-схемы и санитизация
    booking-service.ts   пересечения, создание, продление, статусы
    auth.ts              JWT-сессия (edge-совместимо)
    rate-limit.ts        rate limiting с блокировкой
    env.ts, prisma.ts, http.ts, whatsapp.ts, types.ts
  components/
    site/                Header, Hero, TablesSection, BookingForm, QuickContact, Contacts, Footer
    admin/               AdminDashboard, TablesGrid, BookingsList, Timeline, BookingActions, NewBookingModal
    ui/                  Button, Reveal, StatusBadge, SectionHeading, icons
  hooks/usePolling.ts    polling раз в 30 с (пауза в фоновой вкладке)
```

Название, адрес, телефон, цены и часы работы меняются в `src/lib/config.ts`.

---

## Безопасность

- **Сессия админа** хранится в cookie `httpOnly`, `secure` (в production), `sameSite=strict` с JWT HS256, срок жизни 8 часов.
- **Middleware** защищает все `/admin/*` и `/api/admin/*`. Без сессии страницы перенаправляют на логин, API отвечает 401. Страница `/admin` дополнительно проверяет сессию на сервере.
- **Brute-force:** не больше 5 попыток входа за 15 минут с одного IP, затем вход блокируется на 15 минут.
- **Спам броней:** не больше 5 заявок за 10 минут с одного IP, затем пауза 30 минут. Есть скрытое honeypot-поле: бот получает «успех», но бронь не сохраняется.
- **CSRF:** помимо `SameSite=strict`, мутирующие запросы к `/api/*` принимаются только с Origin того же сайта.
- **Валидация:** каждый вход проверяется через Zod (`.strict()`), строки санитизируются (управляющие символы и `<>` удаляются), размер тела запроса ограничен. Вывод экранирует React.
- **SQL-инъекции** исключены: доступ к БД только через Prisma, сырых запросов нет.
- **Заголовки:** CSP с nonce и `strict-dynamic`, `X-Frame-Options: DENY`, HSTS (в production), `Referrer-Policy`, `X-Content-Type-Options`, `Permissions-Policy`, COOP.
- **Логи:** Prisma логирует только ошибки, без запросов. Обработчики пишут в лог только тип ошибки, без тел запросов и персональных данных.
- **Публичные API** (статусы и занятость) не отдают имена и телефоны.
- **Секреты** хранятся только в `.env`. Файл в `.gitignore`, шаблон — `.env.example`.

> Rate limiter хранит счётчики в памяти процесса. На VPS с одним процессом это работает точно. На Vercel у каждого экземпляра функции свои счётчики, поэтому ограничение действует приблизительно. Для строгого лимита замените хранилище в `src/lib/rate-limit.ts` на Upstash Redis, интерфейс останется тем же.

---

## Деплой

### Vercel + Neon (бесплатно)

1. Создайте базу на https://neon.tech и скопируйте две строки подключения: pooled (с `-pooler` в адресе) и direct.
2. На https://vercel.com: **Add New → Project →** импортируйте репозиторий. Команды сборки уже заданы в `vercel.json`: `prisma migrate deploy && npm run build`.
3. В **Settings → Environment Variables** добавьте `DATABASE_URL` (pooled), `DIRECT_URL` (direct), `WHATSAPP_NUMBER`, `ADMIN_PASSWORD_HASH` (без `\$`), `JWT_SECRET` и `SITE_URL` (`https://<проект>.vercel.app`).
4. Нажмите **Deploy**. Миграция при сборке создаст таблицы и 8 столов.

После каждого пуша в основную ветку Vercel пересобирает сайт автоматически.

### VPS

```bash
git clone <repo> && cd <repo>
```

```bash
npm ci
```

Создайте `.env` для production: PostgreSQL на этом же сервере или Neon, ваш `SITE_URL`. Затем:

```bash
npm run db:deploy
```

```bash
npm run build
```

Запуск через pm2:

```bash
npm i -g pm2 && pm2 start npm --name start -- start -- -p 3000 && pm2 save
```

Nginx как reverse proxy. HTTPS обязателен: cookie сессии в production помечена `Secure`.

```nginx
server {
  server_name start.kg;
  location / {
    proxy_pass http://127.0.0.1:3000;
    proxy_set_header Host $host;
    # Перезаписываем, а не дополняем, чтобы клиент не мог подменить IP для rate limit
    proxy_set_header X-Forwarded-For $remote_addr;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-Proto $scheme;
  }
  listen 443 ssl; # сертификат — certbot --nginx
}
```

## Проверено

- `npm run build`, `npm run lint`, `npm run typecheck` проходят без ошибок.
- PostgreSQL: 20 одновременных запросов на один слот через 2 процесса → создана ровно 1 бронь.
- API: пересечение броней → 409, бронь встык → 201, невалидные данные → 422, запрос без Origin → 403, honeypot срабатывает, rate limit броней и входа срабатывает, подделанный JWT → 401.
- E2E (Playwright): бронь через форму создаётся, происходит редирект в WhatsApp с правильным текстом, занятый слот становится недоступен.
- Lighthouse: mobile — Performance 92, Accessibility 100, Best Practices 100, SEO 100; desktop — 100 во всех категориях.
