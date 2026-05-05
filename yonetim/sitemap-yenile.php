<?php
define('MZ_ADMIN', true);
$adminTitle = 'Sitemap Yenile';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_helpers.php';
require_role('superadmin', 'admin');

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['yenile'])) {
    csrf_assert_post();

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
    $bytes  = @file_put_contents($target, $xml);

    if ($bytes !== false) {
        audit_log('sitemap_yenile', 'sistem', 0);
        $msg = "Sitemap basariyla yenilendi: " . count($urls) . " URL, " . number_format($bytes) . " bayt.";
    } else {
        $err = "sitemap.xml dosyasi yazilamadi. Dosya izinlerini kontrol edin (yazma hakki gerekli).";
    }
}

$exists  = file_exists(MIZAN_ROOT . '/sitemap.xml');
$mtime   = $exists ? filemtime(MIZAN_ROOT . '/sitemap.xml') : 0;
$urlBase = rtrim(SITE_BASE_URL, '/');
?>

<div class="container-fluid py-3">
  <h1 class="h4 mb-3"><i class="bi bi-diagram-3"></i> Sitemap Yenile</h1>

  <?php if ($msg): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle"></i> <?= e($msg) ?></div>
  <?php endif; ?>
  <?php if ($err): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= e($err) ?></div>
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <h5 class="fw-bold mb-3"><i class="bi bi-info-circle text-primary"></i> Statik sitemap.xml</h5>
          <p class="small text-muted">
            Google ve diger arama motorlari sitenizin haritasini bu dosyadan okur. Yeni urun, sayfa veya blog yazisi ekledigimizde
            <strong>"Yenile"</strong> butonuna tiklayarak sitemap'i guncel veriye gore yeniden olusturabilirsiniz.
          </p>

          <hr>

          <h6 class="fw-bold mb-2">Mevcut Durum</h6>
          <ul class="list-unstyled small">
            <li><strong>Statik dosya:</strong>
              <?php if ($exists): ?>
                <span class="badge bg-success">Mevcut</span>
                <code><?= e($urlBase) ?>/sitemap.xml</code>
                <span class="text-muted">(son guncelleme: <?= date('d.m.Y H:i', $mtime) ?>)</span>
              <?php else: ?>
                <span class="badge bg-warning text-dark">Yok</span>
                <span class="text-muted">Henuz olusturulmamis, asagidaki butona tiklayin</span>
              <?php endif; ?>
            </li>
            <li class="mt-2"><strong>Dinamik route:</strong>
              <span class="badge bg-success">Aktif</span>
              <code><?= e($urlBase) ?>/sitemap.php</code>
              <span class="text-muted">(her erisimde anlik uretilir)</span>
            </li>
          </ul>

          <form method="post" class="mt-4">
            <?= csrf_field() ?>
            <button type="submit" name="yenile" value="1" class="btn btn-primary fw-semibold">
              <i class="bi bi-arrow-clockwise"></i> Sitemap'i Yenile
            </button>
            <a href="<?= e($urlBase) ?>/sitemap.xml" target="_blank" class="btn btn-outline-secondary">
              <i class="bi bi-eye"></i> Mevcut sitemap.xml goruntule
            </a>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <h6 class="fw-bold mb-3"><i class="bi bi-google text-primary"></i> Google Search Console</h6>
          <p class="small text-muted mb-3">
            Sitemap'i Google Search Console'a bildirmek icin:
          </p>
          <ol class="small">
            <li>search.google.com/search-console adresine gidin</li>
            <li>Mizan Sigorta'yi mulk olarak ekleyin (varsa atlayin)</li>
            <li>Sol menude <strong>"Site Haritalari"</strong> bolumune girin</li>
            <li>Yeni site haritasi olarak <code>sitemap.xml</code> yazip <strong>"Gonder"</strong> tiklayin</li>
            <li>Birkac saat icinde durum <strong>"Basarili"</strong> olarak gorunecek</li>
          </ol>
          <hr>
          <p class="small text-muted mb-2"><strong>Dogrulama kodu eklemek icin:</strong></p>
          <a href="<?= u('yonetim/ayarlar.php#seo') ?>" class="btn btn-sm btn-outline-primary w-100">
            <i class="bi bi-gear"></i> Ayarlar > SEO sekmesi
          </a>
        </div>
      </div>

      <div class="card border-0 shadow-sm mt-3">
        <div class="card-body">
          <h6 class="fw-bold mb-2"><i class="bi bi-clock-history text-warning"></i> Otomatik Yenileme (Cron)</h6>
          <p class="small text-muted mb-2">
            DirectAdmin > Cron Jobs'tan, gunde bir kez otomatik yenileme icin:
          </p>
          <code class="small d-block p-2 bg-light rounded" style="font-size:.7rem">
            0 3 * * * php <?= e(MIZAN_ROOT) ?>/cron/sitemap-build.php
          </code>
          <p class="small text-muted mt-2 mb-0">Her gece saat 03:00'de yeniden uretilir.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
