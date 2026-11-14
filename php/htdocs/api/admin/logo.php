<?php
/**
 * Upload / reset the club logo (admin API).
 *   POST multipart/form-data, field "logo" (PNG/JPG/WebP, ≤ 2 MB)  → upload
 *   POST ?action=reset                                              → back to the default logo
 * Requires the admin session and the X-CSRF-Token header.
 */
require __DIR__ . '/../../includes/bootstrap.php';

api(function () {
    require_method('POST');
    require_admin_api();
    if (($_GET['action'] ?? '') === 'reset') {
        reset_logo();
    } else {
        if (!isset($_FILES['logo']) || !is_array($_FILES['logo']) || is_array($_FILES['logo']['name'] ?? null)) {
            json_error('Выберите файл с логотипом', 422);
        }
        save_logo($_FILES['logo']);
    }
    json_response(['ok' => true, 'custom' => has_custom_logo(), 'logo' => logo_url('logo-512'), 'logoSmall' => logo_url('logo-96')]);
});
