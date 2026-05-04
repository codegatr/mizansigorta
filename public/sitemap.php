<?php
if (!defined('MIZAN_BOOT')) { http_response_code(403); exit; }

header('Content-Type: application/xml; charset=UTF-8');
$base = rtrim(SITE_BASE_URL, '/');

$urls = [
    ['loc' => $base . '/',              'pri' => '1.0', 'cf' => 'daily'],
    ['loc' => $base . '/teklif-al',     'pri' => '0.9', 'cf' => 'monthly'],
    ['loc' => $base . '/hasar-ihbari',  'pri' => '0.7', 'cf' => 'monthly'],
    ['loc' => $base . '/iletisim',      'pri' => '0.7', 'cf' => 'monthly'],
    ['loc' => $base . '/sss',           'pri' => '0.6', 'cf' => 'monthly'],
    ['loc' => $base . '/blog',          'pri' => '0.8', 'cf' => 'weekly'],
];

foreach (db_all('SELECT slug, guncelleme_tarihi FROM ' . t('urunler') . ' WHERE aktif=1') as $r) {
    $urls[] = ['loc' => $base . '/urun/' . $r['slug'], 'pri' => '0.8', 'cf' => 'monthly', 'lm' => $r['guncelleme_tarihi'] ?? null];
}
foreach (db_all('SELECT slug, guncelleme_tarihi FROM ' . t('sayfalar') . ' WHERE aktif=1') as $r) {
    $urls[] = ['loc' => $base . '/sayfa/' . $r['slug'], 'pri' => '0.5', 'cf' => 'monthly', 'lm' => $r['guncelleme_tarihi'] ?? null];
}
foreach (db_all('SELECT slug, guncelleme_tarihi, yayin_tarihi FROM ' . t('blog') . ' WHERE yayinda=1 AND yayin_tarihi<=NOW()') as $r) {
    $urls[] = ['loc' => $base . '/blog/' . $r['slug'], 'pri' => '0.6', 'cf' => 'weekly', 'lm' => $r['guncelleme_tarihi'] ?: $r['yayin_tarihi']];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
    if (!empty($u['lm'])) {
        echo "    <lastmod>" . date('c', strtotime((string)$u['lm'])) . "</lastmod>\n";
    }
    echo "    <changefreq>" . $u['cf'] . "</changefreq>\n";
    echo "    <priority>" . $u['pri'] . "</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>';
