<?php
if (!defined('MIZAN_BOOT')) { http_response_code(403); exit; }

$slug = $params[0] ?? '';
$sayfa = db_row('SELECT * FROM ' . t('sayfalar') . ' WHERE slug=? AND aktif=1 LIMIT 1', [$slug]);

if (!$sayfa) {
    http_response_code(404);
    $pageTitle = 'Sayfa bulunamadı';
    require MIZAN_INC . '/header.php';
    echo '<section class="container py-5 text-center"><h1 class="display-4 text-warning">404</h1><p>Sayfa bulunamadı.</p><a href="' . u('/') . '" class="btn btn-primary">Anasayfa</a></section>';
    require MIZAN_INC . '/footer.php';
    exit;
}

$pageTitle = $sayfa['seo_baslik'] ?: ($sayfa['baslik'] . ' - ' . SITE_NAME);
$pageDesc  = $sayfa['seo_aciklama'] ?: '';

require MIZAN_INC . '/header.php';
?>

<section class="mz-page-head py-5">
  <div class="container">
    <h1 class="display-6 fw-bold mb-2"><?= e($sayfa['baslik']) ?></h1>
  </div>
</section>

<section class="container py-5">
  <div class="row">
    <div class="col-lg-9 mx-auto">
      <?php if (!empty($sayfa['kapak_gorseli'])): ?>
        <img src="<?= e(asset('uploads/sayfa/' . $sayfa['kapak_gorseli'])) ?>" class="img-fluid rounded shadow-sm mb-4 w-100" alt="">
      <?php endif; ?>
      <div class="mz-prose">
        <?= $sayfa['icerik'] /* admin panelden gelen HTML */ ?>
      </div>
    </div>
  </div>
</section>

<?php require MIZAN_INC . '/footer.php';
