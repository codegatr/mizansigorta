<?php
if (!defined('MIZAN_BOOT')) { http_response_code(403); exit; }
$no = $_GET['no'] ?? '';
$pageTitle = 'Teklif talebiniz alındı - ' . SITE_NAME;
require MIZAN_INC . '/header.php';
?>
<section class="py-5">
  <div class="container text-center py-5">
    <div class="display-1 text-success mb-3"><i class="bi bi-check-circle-fill"></i></div>
    <h1 class="fw-bold">Teklif talebiniz başarıyla alındı!</h1>
    <?php if ($no): ?>
      <p class="lead">Teklif No: <strong class="text-warning"><?= e($no) ?></strong></p>
    <?php endif; ?>
    <p class="text-muted mb-4">Uzman ekibimiz en kısa sürede sizinle iletişime geçecek.</p>
    <a class="btn btn-primary me-2" href="<?= u('/') ?>">Anasayfaya Dön</a>
    <a class="btn btn-outline-primary" href="<?= u('/iletisim') ?>">İletişim</a>
  </div>
</section>
<?php require MIZAN_INC . '/footer.php';
