<?php
/**
 * Mizan Sigorta - Admin Layout (head + sidebar)
 * yonetim/_layout.php
 *
 * Kullanim:
 *   define('MZ_ADMIN', true);
 *   $adminTitle = 'Sayfa Basligi';
 *   require __DIR__ . '/_layout.php';
 *   require __DIR__ . '/_helpers.php';
 *   ... sayfa icerigi ...
 *   require __DIR__ . '/_footer.php';
 */

if (!defined('MZ_ADMIN')) { http_response_code(403); exit; }

if (!defined('MIZAN_BOOT')) define('MIZAN_BOOT', true);
require __DIR__ . '/../includes/bootstrap.php';

// Bakim modu admin alani disinda etkili
check_maintenance(true);

// Login sayfasi haric login zorunlu
$current = basename($_SERVER['SCRIPT_NAME']);
if ($current !== 'login.php') {
    require_login();
}

$me        = user_id() ? db_row('SELECT * FROM ' . t('kullanicilar') . ' WHERE id=?', [user_id()]) : null;
$adminTitle = $adminTitle ?? 'Yönetim Paneli';
$flash     = flash_get();

// Bildirim sayilari (sidebar rozetleri)
$cnt_yeni_teklif = (int)db_value("SELECT COUNT(*) FROM " . t('teklifler') . " WHERE durum='yeni'");
$cnt_yeni_mesaj  = (int)db_value("SELECT COUNT(*) FROM " . t('iletisim_mesajlari') . " WHERE okundu=0");
$cnt_yeni_hasar  = (int)db_value("SELECT COUNT(*) FROM " . t('hasarlar') . " WHERE durum='yeni'");
?><!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($adminTitle) ?> - Mizan Sigorta Yönetim</title>
<link rel="icon" href="<?= asset('assets/img/favicon.svg') ?>" type="image/svg+xml">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
<meta name="theme-color" content="#0d1b2a">
</head>
<body class="mz-admin">

<?php if ($current !== 'login.php'): ?>

<aside class="mz-admin-sidebar" id="adminSidebar">
  <div class="mz-admin-brand">
    <img src="<?= asset('assets/img/logo-light.png') ?>" alt="Mizan Sigorta">
    <span class="mz-brand-sub">v<?= e(SITE_VERSION) ?></span>
  </div>

  <nav class="mz-admin-nav">
    <div class="mz-admin-section">Genel</div>
    <a href="index.php"               class="<?= $current === 'index.php' ? 'active' : '' ?>"><i class="bi bi-speedometer2"></i> Gösterge Paneli</a>

    <div class="mz-admin-section">Satış</div>
    <a href="teklifler.php"           class="<?= str_starts_with($current, 'teklif') ? 'active' : '' ?>">
      <i class="bi bi-file-earmark-text"></i> Teklifler
      <?php if ($cnt_yeni_teklif): ?><span class="badge bg-warning text-dark ms-auto"><?= $cnt_yeni_teklif ?></span><?php endif; ?>
    </a>
    <a href="musteriler.php"          class="<?= $current === 'musteriler.php' ? 'active' : '' ?>"><i class="bi bi-people"></i> Müşteriler</a>
    <a href="policeler.php"           class="<?= $current === 'policeler.php' ? 'active' : '' ?>"><i class="bi bi-shield-check"></i> Poliçeler</a>
    <a href="hasarlar.php"            class="<?= $current === 'hasarlar.php' ? 'active' : '' ?>">
      <i class="bi bi-exclamation-triangle"></i> Hasar Dosyaları
      <?php if ($cnt_yeni_hasar): ?><span class="badge bg-danger ms-auto"><?= $cnt_yeni_hasar ?></span><?php endif; ?>
    </a>

    <div class="mz-admin-section">Hatırlatma Motoru</div>
    <a href="hatirlatma-kurallari.php" class="<?= $current === 'hatirlatma-kurallari.php' ? 'active' : '' ?>"><i class="bi bi-bell"></i> Hatırlatma Kuralları</a>
    <a href="hatirlatma-log.php"       class="<?= $current === 'hatirlatma-log.php' ? 'active' : '' ?>"><i class="bi bi-list-check"></i> Gönderim Logu</a>

    <div class="mz-admin-section">İçerik</div>
    <a href="urun-yonetimi.php"        class="<?= $current === 'urun-yonetimi.php' ? 'active' : '' ?>"><i class="bi bi-box"></i> Sigorta Ürünleri</a>
    <a href="sayfa-yonetimi.php"       class="<?= $current === 'sayfa-yonetimi.php' ? 'active' : '' ?>"><i class="bi bi-file-text"></i> CMS Sayfalar</a>
    <a href="blog.php"                 class="<?= $current === 'blog.php' ? 'active' : '' ?>"><i class="bi bi-journal-text"></i> Blog</a>
    <a href="sss.php"                  class="<?= $current === 'sss.php' ? 'active' : '' ?>"><i class="bi bi-question-circle"></i> S.S.S.</a>
    <a href="referanslar.php"          class="<?= $current === 'referanslar.php' ? 'active' : '' ?>"><i class="bi bi-chat-quote"></i> Referanslar</a>
    <a href="sigorta-sirketleri.php"   class="<?= $current === 'sigorta-sirketleri.php' ? 'active' : '' ?>"><i class="bi bi-buildings"></i> Sigorta Şirketleri</a>

    <div class="mz-admin-section">İletişim</div>
    <a href="iletisim-mesajlari.php"   class="<?= $current === 'iletisim-mesajlari.php' ? 'active' : '' ?>">
      <i class="bi bi-envelope"></i> İletişim Mesajları
      <?php if ($cnt_yeni_mesaj): ?><span class="badge bg-warning text-dark ms-auto"><?= $cnt_yeni_mesaj ?></span><?php endif; ?>
    </a>

    <?php if (is_admin()): ?>
    <div class="mz-admin-section">Sistem</div>
    <a href="kullanicilar.php"         class="<?= $current === 'kullanicilar.php' ? 'active' : '' ?>"><i class="bi bi-person-gear"></i> Kullanıcılar</a>
    <a href="ayarlar.php"              class="<?= $current === 'ayarlar.php' ? 'active' : '' ?>"><i class="bi bi-gear"></i> Ayarlar</a>
    <a href="audit-log.php"            class="<?= $current === 'audit-log.php' ? 'active' : '' ?>"><i class="bi bi-clipboard-data"></i> İşlem Kaydı</a>
    <?php if (is_superadmin()): ?>
    <a href="update.php"               class="<?= $current === 'update.php' ? 'active' : '' ?>"><i class="bi bi-cloud-arrow-down"></i> Güncelleme</a>
    <?php endif; ?>
    <?php endif; ?>

    <div class="mz-admin-section"></div>
    <a href="logout.php" class="text-warning"><i class="bi bi-box-arrow-right"></i> Çıkış Yap</a>
  </nav>
</aside>

<div class="mz-admin-main">
  <header class="mz-admin-topbar">
    <button class="btn btn-sm btn-outline-light d-lg-none me-2" id="sidebarToggle" type="button"><i class="bi bi-list"></i></button>
    <h5 class="mb-0 fw-bold"><?= e($adminTitle) ?></h5>
    <div class="ms-auto d-flex align-items-center gap-3">
      <a class="text-white-50" href="<?= u('/') ?>" target="_blank" title="Siteyi Görüntüle"><i class="bi bi-globe"></i></a>
      <span class="text-white-50 small d-none d-md-inline"><i class="bi bi-clock"></i> <?= date('d.m.Y H:i') ?></span>
      <div class="dropdown">
        <a class="text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" href="#">
          <i class="bi bi-person-circle"></i> <?= e($me['ad_soyad'] ?? 'Kullanıcı') ?>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><span class="dropdown-item-text small text-muted"><?= e($me['email'] ?? '') ?></span></li>
          <li><span class="dropdown-item-text small"><span class="badge bg-warning text-dark"><?= e($me['rol'] ?? '') ?></span></span></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="profil.php"><i class="bi bi-person"></i> Profilim</a></li>
          <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Çıkış</a></li>
        </ul>
      </div>
    </div>
  </header>

  <main class="mz-admin-content">
    <?php if ($flash['type'] ?? ''): ?>
      <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show">
        <?= e($flash['msg']) ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

<?php else: ?>
  <main class="mz-admin-login">
<?php endif; ?>
