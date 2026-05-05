<?php
/**
 * Mizan Sigorta - Bootstrap (tum giris noktalari icin)
 * includes/bootstrap.php
 */

declare(strict_types=1);

if (!defined('MIZAN_BOOT')) define('MIZAN_BOOT', true);

require __DIR__ . '/../config/config.php';

// SITE_VERSION'i her zaman manifest.json'dan oku (config.php static kalir, manifest dinamik)
// Bu sayede config.php'deki eski versiyon, gercek surumun gosterilmesini engellemez.
if (defined('MIZAN_ROOT')) {
    $manifestPath = MIZAN_ROOT . '/manifest.json';
    if (is_file($manifestPath)) {
        $_mz_manifest = json_decode((string)@file_get_contents($manifestPath), true);
        if (is_array($_mz_manifest) && !empty($_mz_manifest['version'])) {
            // PHP'de define() bir kere yapilir, redefine edilemez. Bu yuzden farkli sabit + helper kullaniyoruz.
            if (!defined('MIZAN_RUNTIME_VERSION')) {
                define('MIZAN_RUNTIME_VERSION', (string)$_mz_manifest['version']);
            }
        }
        unset($_mz_manifest);
    }
}
if (!defined('MIZAN_RUNTIME_VERSION')) {
    define('MIZAN_RUNTIME_VERSION', defined('SITE_VERSION') ? SITE_VERSION : '?');
}

require __DIR__ . '/db.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/csrf.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/mail.php';
require __DIR__ . '/upload.php';
require __DIR__ . '/svg_illustrations.php';

// Kritik calisma klasorleri yoksa olustur (root tasima sonrasi vs.)
foreach (['backups', 'uploads', 'uploads/sirket', 'uploads/blog', 'uploads/referans', 'uploads/sayfa'] as $d) {
    $abs = MIZAN_ROOT . '/' . $d;
    if (!is_dir($abs)) @mkdir($abs, 0755, true);
}

start_session();
