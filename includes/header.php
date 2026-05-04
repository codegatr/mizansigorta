<?php
if (!defined('MIZAN_BOOT')) { http_response_code(403); exit; }
$pageTitle  = $pageTitle  ?? setting('site_basligi', SITE_NAME);
$pageDesc   = $pageDesc   ?? setting('site_aciklamasi', '');
$pageKeys   = $pageKeys   ?? setting('site_anahtar_kelimeler', '');
$canonical  = $canonical  ?? (SITE_BASE_URL . ($_SERVER['REQUEST_URI'] ?? '/'));
$urunlerNav = db_all('SELECT slug, baslik FROM ' . t('urunler') . " WHERE aktif=1 ORDER BY sira ASC LIMIT 12");
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
<link rel="canonical" href="<?= e($canonical) ?>">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($pageDesc) ?>">
<meta property="og:type" content="website">
<meta property="og:url"  content="<?= e($canonical) ?>">
<meta name="theme-color" content="#0d1b2a">
<link rel="icon" href="<?= asset('assets/img/favicon.svg') ?>" type="image/svg+xml">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
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
          <ul class="dropdown-menu mz-mega">
            <?php foreach ($urunlerNav as $u): ?>
              <li><a class="dropdown-item" href="<?= u('/urun/' . $u['slug']) ?>"><?= e($u['baslik']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </li>
        <li class="nav-item"><a class="nav-link" href="<?= u('/teklif-al') ?>">Teklif Al</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= u('/hasar-ihbari') ?>">Hasar İhbarı</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= u('/blog') ?>">Blog</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= u('/sss') ?>">S.S.S.</a></li>
        <?php foreach ($cmsNav as $c): ?>
          <li class="nav-item"><a class="nav-link" href="<?= u('/sayfa/' . $c['slug']) ?>"><?= e($c['baslik']) ?></a></li>
        <?php endforeach; ?>
        <li class="nav-item"><a class="nav-link" href="<?= u('/iletisim') ?>">İletişim</a></li>
      </ul>
      <div class="d-flex gap-2">
        <?php if ($wa): ?>
          <a class="btn btn-outline-light btn-sm" target="_blank" rel="noopener" href="https://wa.me/<?= e($wa) ?>"><i class="bi bi-whatsapp"></i> WhatsApp</a>
        <?php endif; ?>
        <a class="btn btn-warning btn-sm fw-semibold" href="<?= u('/teklif-al') ?>"><i class="bi bi-shield-check"></i> Hızlı Teklif</a>
      </div>
    </div>
  </div>
</nav>

<main class="mz-main">
