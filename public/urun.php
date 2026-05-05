<?php
if (!defined('MIZAN_BOOT')) { http_response_code(403); exit; }

$slug = $params[0] ?? '';
$urun = db_row('SELECT * FROM ' . t('urunler') . ' WHERE slug=? AND aktif=1', [$slug]);
if (!$urun) {
    http_response_code(404);
    require MIZAN_INC . '/header.php';
    echo '<section class="container py-5 text-center"><h1 class="display-4">404</h1><p class="lead">Ürün bulunamadı.</p><a href="' . u('/') . '" class="btn btn-warning">Anasayfaya Dön</a></section>';
    require MIZAN_INC . '/footer.php';
    exit;
}

$isCategory = $urun['parent_id'] === null;
$pageTitle = ($urun['seo_baslik'] ?: $urun['baslik']) . ' - ' . setting('firma_adi', SITE_NAME);
$pageDesc  = $urun['seo_aciklama'] ?: $urun['kisa_aciklama'];

// Eger kategori ise alt urunleri yukle
$altUrunler = $isCategory
    ? db_all('SELECT * FROM ' . t('urunler') . ' WHERE aktif=1 AND parent_id=? ORDER BY sira ASC', [(int)$urun['id']])
    : [];

// Eger alt urun ise parent ve kardes urunleri yukle
$parent = null;
$kardesler = [];
if (!$isCategory) {
    $parent = db_row('SELECT id, slug, baslik, icon FROM ' . t('urunler') . ' WHERE id=?', [(int)$urun['parent_id']]);
    $kardesler = db_all('SELECT slug, baslik, icon FROM ' . t('urunler') . ' WHERE aktif=1 AND parent_id=? AND id<>? ORDER BY sira ASC', [(int)$urun['parent_id'], (int)$urun['id']]);
}

require MIZAN_INC . '/header.php';
?>

<section class="mz-page-head">
  <div class="container">
    <h1 class="fw-bold mb-1"><i class="bi <?= e($urun['icon'] ?: 'bi-shield-check') ?>"></i> <?= e($urun['baslik']) ?></h1>
    <nav><ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= u('/') ?>">Anasayfa</a></li>
      <?php if ($parent): ?>
        <li class="breadcrumb-item"><a href="<?= u('/urun/' . $parent['slug']) ?>"><?= e($parent['baslik']) ?></a></li>
      <?php endif; ?>
      <li class="breadcrumb-item active"><?= e($urun['baslik']) ?></li>
    </ol></nav>
  </div>
</section>

<section class="mz-band">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-8">
        <p class="lead text-muted"><?= e($urun['kisa_aciklama']) ?></p>

        <?php if ($urun['aciklama']): ?>
          <div class="mz-prose"><?= $urun['aciklama'] ?></div>
        <?php endif; ?>

        <?php if ($isCategory && $altUrunler): ?>
          <h3 class="fw-bold mb-3 mt-4"><i class="bi bi-collection text-warning"></i> <?= e($urun['baslik']) ?> Çeşitleri</h3>
          <div class="row g-3">
            <?php foreach ($altUrunler as $alt): ?>
              <div class="col-md-6">
                <a href="<?= u('/urun/' . $alt['slug']) ?>" class="mz-prod-card text-start" style="display:flex;gap:1rem;align-items:start;padding:1.25rem;text-align:left">
                  <div class="mz-prod-icon" style="width:50px;height:50px;font-size:1.35rem;flex-shrink:0;margin:0"><i class="bi <?= e($alt['icon'] ?: 'bi-shield') ?>"></i></div>
                  <div>
                    <h6 class="mb-1 fw-bold" style="color:var(--mz-navy)"><?= e($alt['baslik']) ?></h6>
                    <p class="small text-muted mb-0"><?= e($alt['kisa_aciklama']) ?></p>
                  </div>
                </a>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if (!$isCategory && !$urun['aciklama']): ?>
          <div class="alert alert-light border">
            <p class="mb-2"><b><?= e($urun['baslik']) ?></b> için detaylı bilgi ve teminat içerikleri hakkında bilgi almak ister misiniz?</p>
            <p class="mb-0">Anlaşmalı 12+ sigorta şirketi arasından sizin için en uygun teklifi karşılaştırıyoruz. Sürecimiz ücretsiz ve sizi bağlamaz.</p>
          </div>
        <?php endif; ?>

        <div class="mz-cta-box d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4">
          <div>
            <h5 class="mb-1 text-warning fw-bold"><?= e($urun['baslik']) ?> için teklif talep edin</h5>
            <p class="mb-0 small text-light-emphasis">Bilgilerinizi alalım, müsait temsilcimiz sizinle iletişime geçsin.</p>
          </div>
          <a href="<?= u('/teklif-al?urun=' . $urun['slug']) ?>" class="btn btn-warning fw-semibold"><i class="bi bi-headset"></i> Teklif Talebi Oluştur</a>
        </div>
      </div>

      <aside class="col-lg-4">
        <?php if ($parent): ?>
          <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
              <small class="text-muted">Kategori</small>
              <h6 class="fw-bold mb-0"><a href="<?= u('/urun/' . $parent['slug']) ?>" class="text-decoration-none" style="color:var(--mz-navy)"><i class="bi <?= e($parent['icon']) ?>"></i> <?= e($parent['baslik']) ?></a></h6>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($kardesler): ?>
          <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
              <h6 class="fw-bold mb-3"><i class="bi bi-collection text-warning"></i> Aynı Kategoride</h6>
              <div class="list-group list-group-flush">
                <?php foreach ($kardesler as $k): ?>
                  <a href="<?= u('/urun/' . $k['slug']) ?>" class="list-group-item list-group-item-action border-0 px-0 py-2"><i class="bi <?= e($k['icon'] ?: 'bi-shield') ?> text-warning me-2"></i><?= e($k['baslik']) ?></a>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <div class="mz-side-card">
          <h5><i class="bi bi-headset"></i> Yardıma mı ihtiyacınız var?</h5>
          <p class="mb-3">Uzmanlarımıza danışın, doğru teminatı birlikte seçelim.</p>
          <?php if ($v = setting('telefon')): ?>
            <a href="tel:<?= e(preg_replace('/\s+/', '', $v)) ?>" class="btn btn-warning btn-sm w-100 mb-2"><i class="bi bi-telephone-fill"></i> <?= e($v) ?></a>
          <?php endif; ?>
          <?php if ($v = setting('whatsapp')): ?>
            <a href="https://wa.me/<?= e($v) ?>" target="_blank" class="btn btn-success btn-sm w-100"><i class="bi bi-whatsapp"></i> WhatsApp ile yaz</a>
          <?php endif; ?>
        </div>
      </aside>
    </div>
  </div>
</section>

<?php require MIZAN_INC . '/footer.php';
