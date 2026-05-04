<?php
define('MZ_ADMIN', true);
if (!defined('MIZAN_BOOT')) define('MIZAN_BOOT', true);
require __DIR__ . '/../includes/bootstrap.php';
user_logout();
flash_set('info', 'Oturumunuz kapatıldı.');
header('Location: login.php');
exit;
