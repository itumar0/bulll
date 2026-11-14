<?php
/** GET: list bookings (filters: date, tableId, status, q). POST: manual booking (walk-in). */
require __DIR__ . '/../../includes/bootstrap.php';

api(function () {
    require_admin_api();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'GET') {
        $rows = list_bookings([
            'date' => $_GET['date'] ?? '', 'tableId' => $_GET['tableId'] ?? '',
            'status' => $_GET['status'] ?? '', 'q' => $_GET['q'] ?? '',
        ]);
        json_response(['ok' => true, 'bookings' => array_map('booking_public_admin', $rows)]);
    }
    if ($method === 'POST') {
        $b = create_manual_booking(validate_manual_booking(read_json()));
        json_response(['ok' => true, 'id' => (int) $b['id']], 201);
    }
    header('Allow: GET, POST');
    json_error('Method not allowed', 405);
});
