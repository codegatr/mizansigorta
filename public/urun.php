<?php
if (!defined('MIZAN_BOOT')) { http_response_code(403); exit; }

$slug = $params[0] ?? '';
$urun = db_row('SELECT * FROM ' . t('urunler') . ' WHERE slug=? AND aktif=1', [$slug]);
if (!$urun) {
    http_response_code(404);
    require MIZAN_INC . '/header.php';
    echo '<section class="container py-5"><h1>Ürün bulunamadı.</h1></section>';
    require MIZAN_INC . '/footer.php';
    exit;
}

$diger = db_all('SELECT slug,baslik,icon FROM ' . t('urunler') . ' WHERE aktif=1 AND id<>? ORDER BY sira ASC LIMIT 6', [(int) $urun['id']]);

$pageTitle = ($urun['seo_baslik'] ?: $urun['baslik']) . ' - ' . setting('firma_adi', SITE_NAME);
$pageDesc  = $urun['seo_aciklama'] ?: $urun['kisa_aciklama'];
require MIZAN_INC . '/header.php';
?>

<section class="mz-page-head">
  <div class="container py-4">
    <h1 class="fw-bold mb-1"><i class="bi bi-<?= e($urun['icon'] ?: 'shield-check') ?>"></i> <?= e($urun['baslik']) ?></h1>
    <nav><ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= u('/') ?>">Anasayfa</a></li>
      <li class="breadcrumb-item active"><?= e($urun['baslik']) ?></li>
    </ol></nav>
  </div>
</section>

<section class="container py-5">
  <div class="row g-4">
    <div class="col-lg-8">
      <p class="lead text-muted"><?= e($urun['kisa_aciklama']) ?></p>
      <div class="mz-prose">
        <?= $urun['aciklama'] ?: '<p>Bu ürün için hazırlanan teminatlar ve avantajlı paketler hakkında bilgi almak için <a href="' . u('/teklif-al?urun=' . $urun['slug']) . '">hızlı teklif</a> formumuzu doldurabilir veya bizi arayabilirsiniz.</p>' ?>
      </div>

      <div class="mz-cta-box d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4">
        <div>
          <h5 class="mb-1"><?= e($urun['baslik']) ?> için anında teklif alın</h5>
          <p class="mb-0 text-muted small">Bilgileriniz yalnızca teklif amacıyla kullanılır.</p>
        </div>
        <a class="btn btn-warning btn-lg" href="<?= u('/teklif-al?urun=' . $urun['slug']) ?>">Teklif Al <i class="bi bi-arrow-right"></i></a>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="mz-side-card">
        <h6 class="fw-bold mb-3">Diğer Ürünler</h6>
        <ul class="list-unstyled mb-0">
          <?php foreach ($diger as $d): ?>
            <li class="mb-2"><a href="<?= u('/urun/' . $d['slug']) ?>" class="d-flex align-items-center gap-2 text-decoration-none"><i class="bi bi-<?= e($d['icon'] ?: 'shield-check') ?> text-warning"></i> <?= e($d['baslik']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php if ($tel = setting('telefon')): ?>
      <div class="mz-side-card mt-3 text-center">
        <h6 class="fw-bold"><i class="bi bi-telephone-fill text-warning"></i> Hemen arayın</h6>
        <a href="tel:<?= e(preg_replace('/\s+/','',$tel)) ?>" class="d-block fs-4 fw-bold"><?= e($tel) ?></a>
        <small class="text-muted"><?= e(setting('calisma_saatleri')) ?></small>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require MIZAN_INC . '/footer.php';
