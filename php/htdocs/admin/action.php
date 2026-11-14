<?php
/** Handles booking actions from admin forms: confirm, cancel, complete (early finish), extend. */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/views/icons.php';
require __DIR__ . '/../includes/views/admin.php';
require_admin_page();

$return = safe_return((string) ($_POST['return'] ?? 'admin/'));
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect('admin/');
if (!form_post_is_valid()) {
    flash('Сессия устарела. Обновите страницу и попробуйте снова.', true);
    redirect($return);
}
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$action = (string) ($_POST['action'] ?? '');
$labels = ['confirm' => 'подтверждена', 'cancel' => 'отменена', 'complete' => 'завершена', 'extend' => 'продлена'];
try {
    if (!$id || !isset($labels[$action])) throw new BookingError('Некорректный запрос');
    if ($action === 'extend') {
        $minutes = filter_var($_POST['minutes'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 30, 'max_range' => 300]]);
        if (!$minutes || $minutes % 30 !== 0) throw new BookingError('Шаг продления — 30 минут');
        $b = extend_booking($id, $minutes);
        flash("Бронь №{$id} продлена до " . fmt_time(from_db($b['end_at'])) . '.');
    } else {
        set_booking_status($id, $action);
        flash("Бронь №{$id} {$labels[$action]}.");
    }
} catch (BookingError $e) {
    flash($e->getMessage(), true);
} catch (Throwable $e) {
    log_error('admin/action', $e);
    flash('Не удалось выполнить действие. Попробуйте ещё раз.', true);
}
redirect($return);
