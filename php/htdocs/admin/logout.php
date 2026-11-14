<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/views/icons.php';
require __DIR__ . '/../includes/views/admin.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && is_admin() && form_post_is_valid()) {
    admin_logout();
}
redirect('admin/login.php');
