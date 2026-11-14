<?php
/**
 * «Бильярд Старт» — site configuration. EDIT THIS FILE before uploading.
 * Never commit real passwords to a public repository.
 */
defined('APP') or exit;

return [
    // --- MySQL (InfinityFree: Control Panel → MySQL Databases) ---
    'db_host' => 'sqlXXX.infinityfree.com',   // "MySQL Host Name"
    'db_name' => 'if0_XXXXXXXX_billiard',     // "MySQL DB Name"
    'db_user' => 'if0_XXXXXXXX',              // "MySQL User Name"
    'db_pass' => 'your-mysql-password',       // your vPanel / account password
    'db_port' => 3306,

    // --- Admin ---
    // bcrypt hash of the admin password (NOT the password itself).
    // The default below is the hash of "1111" — change it before going live.
    // Generate a new one at /admin/hash.php (see README) or with: php -r "echo password_hash('NEW_PASSWORD', PASSWORD_BCRYPT);"
    'admin_password_hash' => '$2y$12$M1hTEuAz4VT6e.r8MbmVk.5gTf0fADdBxg2OkCsS.D6.OtHoohn3K',

    // --- WhatsApp number that receives bookings (digits only) ---
    'whatsapp_number' => '996506056556',

    // --- HTTPS ---
    // Set to true after enabling the free SSL certificate in InfinityFree.
    // Turns on HSTS and Secure session cookies.
    'force_https' => false,

    // Use X-Forwarded-For for the client IP (only behind a trusted proxy). Keep false on InfinityFree.
    'trust_proxy' => false,

    // Troubleshooting: true shows PHP error details on screen and enables /check.php.
    // Turn it on only while fixing a problem, then set back to false.
    'debug' => false,
];
