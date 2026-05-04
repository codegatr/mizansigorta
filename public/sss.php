<?php
if (!defined('MIZAN_BOOT')) { http_response_code(403); exit; }

$pageTitle = 'Sıkça Sorulan Sorular - ' . SITE_NAME;
$pageDesc  = 'Sigorta ile ilgili merak edilen sorular ve cevapları.';

$kategoriFilter = $_GET['k'] ?? '';
$where = "aktif=1";
$params = [];
if ($kategoriFilter !== '') {
    $where .= " AND kategori = ?";
    $params[] = $kategoriFilter;
}

$kategoriler = db_all('SELECT DISTINCT kategori FROM ' . t('sss') . ' WHERE aktif=1 AND kategori<>"" ORDER BY kategori');
$sorular     = db_all('SELECT * FROM ' . t('sss') . " WHERE $where ORDER BY sira ASC, id ASC", $params);

require MIZAN_INC . '/header.php';
?>

<section class="mz-page-head py-5">
  <div class="container">
    <h1 class="display-6 fw-bold mb-2">Sıkça Sorulan Sorular</h1>
    <p class="lead text-white-50 mb-0">Sigorta ürünlerimiz ve süreçlerimizle ilgili merak ettikleriniz.</p>
  </div>
</section>

<section class="container py-5">
  <?php if ($kategoriler): ?>
    <div class="d-flex flex-wrap gap-2 mb-4">
      <a href="<?= u('/sss') ?>" class="btn btn-sm <?= $kategoriFilter === '' ? 'btn-primary' : 'btn-outline-primary' ?>">Tümü</a>
      <?php foreach ($kategoriler as $k): ?>
        <a href="<?= u('/sss?k=' . urlencode($k['kategori'])) ?>"
           class="btn btn-sm <?= $kategoriFilter === $k['kategori'] ? 'btn-primary' : 'btn-outline-primary' ?>">
          <?= e($k['kategori']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="row">
    <div class="col-lg-8 mx-auto">
      <?php if (!$sorular): ?>
        <div class="alert alert-info text-center">Bu kategoride henüz soru bulunmuyor.</div>
      <?php else: ?>
        <div class="accordion" id="sssAcc">
          <?php foreach ($sorular as $i => $s): ?>
            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button <?= $i === 0 ? '' : 'collapsed' ?>" type="button"
                        data-bs-toggle="collapse" data-bs-target="#sss<?= (int)$s['id'] ?>">
                  <i class="bi bi-question-circle me-2 text-warning"></i>
                  <?= e($s['soru']) ?>
                </button>
              </h2>
              <div id="sss<?= (int)$s['id'] ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#sssAcc">
                <div class="accordion-body"><?= nl2br(e($s['cevap'])) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="text-center mt-5 p-4 mz-cta-box">
        <h4 class="fw-bold mb-2">Sorunuzun cevabını bulamadınız mı?</h4>
        <p class="text-muted">Bize ulaşın, en kısa sürede size dönüş yapalım.</p>
        <a href="<?= u('/iletisim') ?>" class="btn btn-primary"><i class="bi bi-chat-dots"></i> Bize Sorun</a>
      </div>
    </div>
  </div>
</section>

<?php require MIZAN_INC . '/footer.php';
