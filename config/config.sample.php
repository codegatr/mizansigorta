<?php
/**
 * Mizan Sigorta - Yapilandirma ORNEK dosyasi
 * Bu dosyayi `config.php` olarak kopyalayip kendi degerlerinizi girin.
 */

declare(strict_types=1);

if (!defined('MIZAN_BOOT')) {
    http_response_code(403);
    exit('Forbidden');
}

const DB_HOST    = 'localhost';
const DB_NAME    = 'DEGISTIRIN_db';
const DB_USER    = 'DEGISTIRIN_user';
const DB_PASS    = 'DEGISTIRIN_password';
const DB_CHARSET = 'utf8mb4';
const DB_PREFIX  = 'mz_';

const SITE_VERSION   = '1.1.32';
const SITE_BASE_URL  = 'https://mizansigorta.com.tr';
const SITE_NAME      = 'Mizan Sigorta';
const SITE_TIMEZONE  = 'Europe/Istanbul';
const SITE_LOCALE    = 'tr_TR';

const SESSION_NAME       = 'mz_sess';
const SESSION_LIFETIME   = 7200;
const ADMIN_LOGIN_LOCK   = 5;
const ADMIN_LOCK_MINUTES = 15;
const CSRF_TOKEN_NAME    = '_token';

define('MIZAN_ROOT',     dirname(__DIR__));
define('MIZAN_INC',      MIZAN_ROOT . '/includes');
define('MIZAN_PUBLIC',   MIZAN_ROOT . '/public');
define('MIZAN_ADMIN',    MIZAN_ROOT . '/yonetim');
define('MIZAN_ASSETS',   MIZAN_ROOT . '/assets');
define('MIZAN_UPLOADS',  MIZAN_ROOT . '/uploads');
define('MIZAN_BACKUPS',  MIZAN_ROOT . '/backups');

const UPDATE_REPO        = 'codegatr/mizansigorta';
const UPDATE_MANIFEST    = 'manifest.json';
const UPDATE_USE_API     = true;
const UPDATE_ZIP_TIMEOUT = 120;

const MIZAN_DEBUG = false;

date_default_timezone_set(SITE_TIMEZONE);
if (MIZAN_DEBUG) {
    error_reporting(E_ALL); ini_set('display_errors','1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
    ini_set('display_errors','0');
}
