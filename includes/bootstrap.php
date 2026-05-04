<?php
/**
 * Mizan Sigorta - Bootstrap (tum giris noktalari icin)
 * includes/bootstrap.php
 */

declare(strict_types=1);

if (!defined('MIZAN_BOOT')) define('MIZAN_BOOT', true);

require __DIR__ . '/../config/config.php';
require __DIR__ . '/db.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/csrf.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/mail.php';

start_session();
