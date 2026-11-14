<?php
/** Public booking endpoint: POST JSON → saves booking → returns wa.me link with the prepared message. */
require __DIR__ . '/../includes/bootstrap.php';

api(function () {
    require_method('POST');
    require_same_origin();

    // Anti-spam: 5 bookings per 10 minutes per IP, then a 30-minute cooldown.
    [$allowed, $retry] = rate_limit('booking:' . client_ip(), 5, 600, 1800);
    if (!$allowed) {
        json_error('Слишком много заявок. Попробуйте позже или напишите нам в WhatsApp.', 429, ['retryAfter' => $retry]);
    }

    $in = (new Validator(read_json(), ['name', 'phone', 'tableId', 'date', 'startMinutes', 'durationHours', 'guests', 'comment', 'website']))
        ->name()->phone()->tableId()->date()
        ->int('startMinutes', 0, LAST_START_MIN, 'Выберите время начала', SLOT_MIN)
        ->int('durationHours', MIN_DURATION_H, MAX_DURATION_H, 'От 1 до 5 часов')
        ->int('guests', 1, 10, 'От 1 до 10 гостей')
        ->text('comment', 500)
        ->honeypot()
        ->result();

    // Honeypot: bots fill hidden fields. Pretend success, store nothing.
    if ($in['website'] !== '') {
        json_response(['ok' => true, 'id' => 0, 'waUrl' => wa_link(cfg('whatsapp_number'))], 201);
    }

    [$start, $end] = website_interval($in['date'], $in['startMinutes'], $in['durationHours']);
    $b = create_booking([
        'tableId' => $in['tableId'], 'name' => $in['name'], 'phone' => $in['phone'], 'guests' => $in['guests'],
        'comment' => $in['comment'], 'startAt' => $start, 'endAt' => $end, 'status' => 'PENDING', 'source' => 'WEBSITE',
    ]);
    $text = booking_message($b, TABLES[$b['table_id']]['name']);
    json_response(['ok' => true, 'id' => (int) $b['id'], 'waUrl' => wa_link(cfg('whatsapp_number'), $text)], 201);
});
