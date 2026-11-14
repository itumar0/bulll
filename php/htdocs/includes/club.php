<?php
/** Public club information. Edit texts, prices and links here. */
defined('APP') or exit;

const CLUB = [
    'name'          => 'Бильярд Старт',
    'slogan'        => 'Твоя игра начинается здесь.',
    'description'   => '«Бильярд Старт» — бильярдный клуб в Караколе: 6 профессиональных столов и 2 VIP-комнаты. Онлайн-бронирование за минуту, ответ в WhatsApp.',
    'city'          => 'Каракол',
    'address'       => 'г. Каракол, ул. Токтогула, 249/1',
    'street'        => 'ул. Токтогула, 249/1',
    'map_url'       => 'https://2gis.kg/bishkek/geo/70030076631151014/78.390321,42.489646',
    'phone_display' => '+996 (506) 056-556',
    'phone_href'    => 'tel:+996506056556',
    'instagram'     => 'https://www.instagram.com/starbilliard.karakol/',
    'instagram_handle' => '@starbilliard.karakol',
    'hours_label'   => 'Круглосуточно, 24/7',
    'price_regular' => 250,
    'price_vip'     => 350,
];

// Booking rules (the club works 24/7). Minutes are counted from local midnight (Asia/Bishkek).
const SLOT_MIN = 30;
const MIN_DURATION_H = 1;
const MAX_DURATION_H = 5;
const LAST_START_MIN = 24 * 60 - SLOT_MIN;                 // 23:30
const MAX_END_MIN = LAST_START_MIN + MAX_DURATION_H * 60;   // latest end of a booking started on a date
const POLL_INTERVAL_MS = 30000;

const TABLES = [
    't1'   => ['name' => 'Стол 1', 'type' => 'REGULAR', 'capacity' => 6],
    't2'   => ['name' => 'Стол 2', 'type' => 'REGULAR', 'capacity' => 6],
    't3'   => ['name' => 'Стол 3', 'type' => 'REGULAR', 'capacity' => 6],
    't4'   => ['name' => 'Стол 4', 'type' => 'REGULAR', 'capacity' => 6],
    't5'   => ['name' => 'Стол 5', 'type' => 'REGULAR', 'capacity' => 6],
    't6'   => ['name' => 'Стол 6', 'type' => 'REGULAR', 'capacity' => 6],
    'vip1' => ['name' => 'VIP 1', 'type' => 'VIP', 'capacity' => 10],
    'vip2' => ['name' => 'VIP 2', 'type' => 'VIP', 'capacity' => 10],
];

const STATUSES = ['PENDING', 'CONFIRMED', 'COMPLETED', 'CANCELLED'];
const ACTIVE_STATUSES = ['PENDING', 'CONFIRMED'];
