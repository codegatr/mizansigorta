<?php
if (!defined('MIZAN_BOOT')) { http_response_code(403); exit; }
$pageTitle  = $pageTitle  ?? setting('site_basligi', SITE_NAME);
$pageDesc   = $pageDesc   ?? setting('site_aciklamasi', '');
$pageKeys   = $pageKeys   ?? setting('site_anahtar_kelimeler', '');
$canonical  = $canonical  ?? (SITE_BASE_URL . ($_SERVER['REQUEST_URI'] ?? '/'));
$urunlerNav = db_all('SELECT id, slug, baslik, icon FROM ' . t('urunler') . ' WHERE aktif=1 AND parent_id IS NULL ORDER BY sira ASC LIMIT 12');
$cmsNav     = db_all('SELECT slug, baslik FROM ' . t('sayfalar') . ' WHERE aktif=1 AND menude_goster=1 ORDER BY menu_sirasi ASC');
$tel        = setting('telefon');
$wa         = setting('whatsapp');
?><!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($pageDesc) ?>">
<meta name="keywords"    content="<?= e($pageKeys) ?>">
<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
<meta name="googlebot" content="index, follow">
<meta name="author" content="<?= e(setting('firma_adi', SITE_NAME)) ?>">
<meta name="geo.region" content="TR">
<meta name="geo.placename" content="<?= e(setting('ofis_sehirler', 'Konya, Istanbul, Ankara, Aksaray')) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">

<!-- Open Graph -->
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($pageDesc) ?>">
<meta property="og:type" content="website">
<meta property="og:url"  content="<?= e($canonical) ?>">
<meta property="og:locale" content="tr_TR">
<meta property="og:site_name" content="<?= e(setting('site_basligi', SITE_NAME)) ?>">
<meta property="og:image" content="<?= e(SITE_BASE_URL) ?>/assets/img/logo.png">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($pageTitle) ?>">
<meta name="twitter:description" content="<?= e($pageDesc) ?>">
<meta name="twitter:image" content="<?= e(SITE_BASE_URL) ?>/assets/img/logo.png">

<!-- Google Search Console verification (admin->ayarlar->sistem'den eklenebilir) -->
<?php if ($gsc = setting('gsc_verification')): ?>
<meta name="google-site-verification" content="<?= e($gsc) ?>">
<?php endif; ?>

<meta name="theme-color" content="#0f1e37">
<link rel="icon" href="<?= asset('assets/img/favicon.svg') ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Allura&family=Pinyon+Script&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">

<!-- Schema.org InsuranceAgency JSON-LD -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "InsuranceAgency",
  "name": "<?= e(setting('firma_adi', SITE_NAME)) ?>",
  "alternateName": "Mizan Sigorta",
  "description": "<?= e(setting('site_aciklamasi', 'Mizan Sigorta — sigorta aracılık hizmetleri.')) ?>",
  "url": "<?= e(SITE_BASE_URL) ?>/",
  "logo": "<?= e(SITE_BASE_URL) ?>/assets/img/logo.png",
  "image": "<?= e(SITE_BASE_URL) ?>/assets/img/logo.png",
  "telephone": "<?= e(setting('telefon')) ?>",
  "email": "<?= e(setting('email')) ?>",
  "priceRange": "$$",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "<?= e(setting('adres', 'Konya, Türkiye')) ?>",
    "addressLocality": "Konya",
    "addressRegion": "Konya",
    "addressCountry": "TR"
  },
  "areaServed": [
    {"@type": "City", "name": "İstanbul"},
    {"@type": "City", "name": "Konya"},
    {"@type": "City", "name": "Ankara"},
    {"@type": "City", "name": "Aksaray"},
    {"@type": "Country", "name": "Türkiye"}
  ],
  "openingHoursSpecification": [{
    "@type": "OpeningHoursSpecification",
    "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
    "opens": "09:00",
    "closes": "18:00"
  }, {
    "@type": "OpeningHoursSpecification",
    "dayOfWeek": "Saturday",
    "opens": "10:00",
    "closes": "14:00"
  }],
  "sameAs": [
    <?php
    $socials = [];
    foreach (['facebook','instagram','twitter','linkedin','youtube'] as $sn) {
      if ($u = setting($sn)) $socials[] = '"' . e($u) . '"';
    }
    echo implode(",\n    ", $socials);
    ?>
  ],
  "makesOffer": [
    {"@type": "Offer", "name": "Kasko Sigortası", "url": "<?= e(SITE_BASE_URL) ?>/urun/kasko"},
    {"@type": "Offer", "name": "Trafik Sigortası", "url": "<?= e(SITE_BASE_URL) ?>/urun/trafik-zorunlu-sorumluluk"},
    {"@type": "Offer", "name": "Konut Sigortası", "url": "<?= e(SITE_BASE_URL) ?>/urun/konut-sigortasi"},
    {"@type": "Offer", "name": "DASK Zorunlu Deprem", "url": "<?= e(SITE_BASE_URL) ?>/urun/dask"},
    {"@type": "Offer", "name": "Özel Sağlık Sigortası", "url": "<?= e(SITE_BASE_URL) ?>/urun/ozel-saglik-sigortasi"},
    {"@type": "Offer", "name": "Tamamlayıcı Sağlık Sigortası", "url": "<?= e(SITE_BASE_URL) ?>/urun/tamamlayici-saglik-sigortasi"},
    {"@type": "Offer", "name": "Ferdi Kaza Sigortası", "url": "<?= e(SITE_BASE_URL) ?>/urun/ferdi-kaza-sigortasi"}
  ]
}
</script>

<!-- Sitemap referansı -->
<link rel="sitemap" type="application/xml" href="<?= e(SITE_BASE_URL) ?>/sitemap.xml">
</head>
<body class="mz-public">

<div class="mz-topbar">
  <div class="container d-flex flex-wrap justify-content-between align-items-center small">
    <div class="d-flex gap-3">
      <span><i class="bi bi-clock"></i> <?= e(setting('calisma_saatleri', 'Pzt-Cum 09:00-18:00')) ?></span>
      <?php if ($tel): ?><a class="text-white-50" href="tel:<?= e(preg_replace('/\s+/', '', $tel)) ?>"><i class="bi bi-telephone-fill"></i> <?= e($tel) ?></a><?php endif; ?>
    </div>
    <div class="d-flex gap-2">
      <?php foreach (['facebook','instagram','linkedin','twitter','youtube'] as $sn): $u = setting($sn); if ($u): ?>
        <a class="text-white-50" target="_blank" rel="noopener" href="<?= e($u) ?>" aria-label="<?= e($sn) ?>"><i class="bi bi-<?= e($sn === 'twitter' ? 'twitter-x' : $sn) ?>"></i></a>
      <?php endif; endforeach; ?>
    </div>
  </div>
</div>

<nav class="navbar navbar-expand-lg mz-navbar sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= u('/') ?>">
      <img src="<?= asset('assets/img/logo-light.png') ?>" alt="<?= e(setting('site_basligi', SITE_NAME)) ?>">
    </a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navMain" aria-label="Menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="<?= u('/') ?>">Anasayfa</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">Sigorta Ürünleri</a>
          <ul class="dropdown-menu mz-mega-clean p-3 border-0 shadow-lg" style="min-width:560px;border-radius:14px">
            <li>
              <div class="row g-2">
                <?php foreach ($urunlerNav as $kat): ?>
                  <div class="col-md-6">
                    <a class="mz-mega-item" href="<?= u('/urun/' . $kat['slug']) ?>">
                      <span class="mz-mega-icon"><i class="bi <?= e($kat['icon'] ?: 'bi-shield-check') ?>"></i></span>
                      <span class="mz-mega-text"><?= e($kat['baslik']) ?></span>
                      <i class="bi bi-arrow-right ms-auto text-muted"></i>
                    </a>
                  </div>
                <?php endforeach; ?>
              </div>
              <hr class="my-2">
              <div class="d-flex justify-content-between align-items-center px-2">
                <small class="text-muted"><i class="bi bi-info-circle"></i> 35+ ürün, 12+ şirket</small>
                <button class="btn btn-sm btn-warning fw-semibold" data-bs-toggle="modal" data-bs-target="#teklifWizard"><i class="bi bi-headset"></i> Teklif Talebi</button>
              </div>
            </li>
          </ul>
        </li>
        <li class="nav-item"><a class="nav-link" href="<?= u('/hasar-ihbari') ?>">Hasar İhbarı</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= u('/sss') ?>">S.S.S.</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= u('/blog') ?>">Blog</a></li>
        <?php foreach ($cmsNav as $c): ?>
          <li class="nav-item"><a class="nav-link" href="<?= u('/sayfa/' . $c['slug']) ?>"><?= e($c['baslik']) ?></a></li>
        <?php endforeach; ?>
        <li class="nav-item"><a class="nav-link" href="<?= u('/iletisim') ?>">İletişim</a></li>
        <li class="nav-item"><a class="nav-link fw-semibold" href="<?= u('/temsilcimiz-olun') ?>" style="color:var(--mz-red) !important"><i class="bi bi-stars"></i> Temsilcimiz Olun</a></li>
      </ul>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-warning btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#teklifWizard"><i class="bi bi-headset"></i> Teklif Talebi</button>
      </div>
    </div>
  </div>
</nav>

<main class="mz-main">
