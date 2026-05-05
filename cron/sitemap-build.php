<?php
/**
 * Statik sitemap.xml olusturucu
 *
 * Cron ornegi (her gun 03:00):
 *   0 3 * * * php /path/to/cron/sitemap-build.php
 *
 * Veya manuel: yonetim/sitemap-yenile.php sayfasindan tetiklenir.
 * Cikti: site root /sitemap.xml dosyasi (statik)
 */

define('MIZAN_BOOT', 1);
require __DIR__ . '/../includes/bootstrap.php';

$base = rtrim(SITE_BASE_URL, '/');
$now  = date('c');

$urls = [
    ['loc' => $base . '/',              'pri' => '1.0', 'cf' => 'daily',   'lm' => $now],
    ['loc' => $base . '/teklif-al',     'pri' => '0.9', 'cf' => 'monthly', 'lm' => $now],
    ['loc' => $base . '/hasar-ihbari',  'pri' => '0.9', 'cf' => 'monthly', 'lm' => $now],
    ['loc' => $base . '/iletisim',      'pri' => '0.7', 'cf' => 'monthly', 'lm' => $now],
    ['loc' => $base . '/sss',           'pri' => '0.6', 'cf' => 'monthly', 'lm' => $now],
    ['loc' => $base . '/blog',          'pri' => '0.8', 'cf' => 'weekly',  'lm' => $now],
];

foreach (db_all('SELECT slug, guncelleme_tarihi FROM ' . t('urunler') . ' WHERE aktif=1') as $r) {
    $urls[] = ['loc' => $base . '/urun/' . $r['slug'], 'pri' => '0.8', 'cf' => 'monthly',
               'lm' => date('c', strtotime((string)($r['guncelleme_tarihi'] ?? $now)))];
}
foreach (db_all('SELECT slug, guncelleme_tarihi FROM ' . t('sayfalar') . ' WHERE aktif=1') as $r) {
    $urls[] = ['loc' => $base . '/sayfa/' . $r['slug'], 'pri' => '0.5', 'cf' => 'monthly',
               'lm' => date('c', strtotime((string)($r['guncelleme_tarihi'] ?? $now)))];
}
foreach (db_all('SELECT slug, guncelleme_tarihi, yayin_tarihi FROM ' . t('blog') . ' WHERE aktif=1 AND yayin_tarihi<=NOW()') as $r) {
    $urls[] = ['loc' => $base . '/blog/' . $r['slug'], 'pri' => '0.6', 'cf' => 'weekly',
               'lm' => date('c', strtotime((string)($r['guncelleme_tarihi'] ?: $r['yayin_tarihi'])))];
}

$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    $xml .= "  <url>\n";
    $xml .= "    <loc>" . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
    $xml .= "    <lastmod>" . $u['lm'] . "</lastmod>\n";
    $xml .= "    <changefreq>" . $u['cf'] . "</changefreq>\n";
    $xml .= "    <priority>" . $u['pri'] . "</priority>\n";
    $xml .= "  </url>\n";
}
$xml .= '</urlset>';

$target = MIZAN_ROOT . '/sitemap.xml';
$bytes  = file_put_contents($target, $xml);

if (PHP_SAPI === 'cli') {
    echo "Sitemap yenilendi: " . count($urls) . " URL, " . $bytes . " bayt -> $target\n";
} else {
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Sitemap yenilendi: " . count($urls) . " URL, " . $bytes . " bayt\n";
    echo "Konum: $target\n";
    echo "URL: $base/sitemap.xml\n";
}
