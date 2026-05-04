<?php
if (!defined('MIZAN_BOOT')) { http_response_code(403); exit; }
header('Content-Type: text/plain; charset=UTF-8');
$base = rtrim(SITE_BASE_URL, '/');
?>
User-agent: *
Allow: /
Disallow: /yonetim/
Disallow: /config/
Disallow: /includes/
Disallow: /cron/
Disallow: /backups/
Disallow: /uploads/sayfa/
Crawl-delay: 2

Sitemap: <?= $base ?>/sitemap.xml
