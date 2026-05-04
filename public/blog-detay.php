<?php
if (!defined('MIZAN_BOOT')) { http_response_code(403); exit; }

$slug = $params[0] ?? '';
$post = db_row('SELECT * FROM ' . t('blog') . ' WHERE slug=? AND yayinda=1 AND yayin_tarihi<=NOW() LIMIT 1', [$slug]);

if (!$post) {
    http_response_code(404);
    $pageTitle = 'Yazı bulunamadı';
    require MIZAN_INC . '/header.php';
    echo '<section class="container py-5 text-center"><h1 class="display-4 text-warning">404</h1><p>Yazı bulunamadı.</p><a href="' . u('/blog') . '" class="btn btn-primary">Bloga Dön</a></section>';
    require MIZAN_INC . '/footer.php';
    exit;
}

// Goruntuleme++
db_exec('UPDATE ' . t('blog') . ' SET goruntuleme=goruntuleme+1 WHERE id=?', [(int)$post['id']]);

$pageTitle = $post['seo_baslik'] ?: ($post['baslik'] . ' - ' . SITE_NAME);
$pageDesc  = $post['seo_aciklama'] ?: mb_substr($post['ozet'] ?? '', 0, 160);

$benzer = db_all('SELECT slug, baslik, kapak, yayin_tarihi FROM ' . t('blog') . '
                  WHERE yayinda=1 AND id<>? AND (kategori=? OR kategori="")
                  ORDER BY yayin_tarihi DESC LIMIT 3', [(int)$post['id'], $post['kategori']]);

require MIZAN_INC . '/header.php';
?>

<article class="container py-5">
  <div class="row">
    <div class="col-lg-9 mx-auto">
      <nav class="mb-3 small">
        <a href="<?= u('/') ?>" class="text-decoration-none">Anasayfa</a> /
        <a href="<?= u('/blog') ?>" class="text-decoration-none">Blog</a> /
        <span class="text-muted"><?= e($post['baslik']) ?></span>
      </nav>

      <?php if (!empty($post['kategori'])): ?>
        <span class="badge bg-warning text-dark mb-3"><?= e($post['kategori']) ?></span>
      <?php endif; ?>

      <h1 class="display-6 fw-bold mb-3"><?= e($post['baslik']) ?></h1>

      <div class="d-flex gap-3 text-muted small mb-4">
        <span><i class="bi bi-calendar3"></i> <?= tr_date($post['yayin_tarihi']) ?></span>
        <span><i class="bi bi-eye"></i> <?= number_format((int)$post['goruntuleme'], 0, ',', '.') ?> okunma</span>
        <?php if (!empty($post['yazar'])): ?>
          <span><i class="bi bi-person"></i> <?= e($post['yazar']) ?></span>
        <?php endif; ?>
      </div>

      <?php if (!empty($post['kapak'])): ?>
        <img src="<?= e(asset('uploads/blog/' . $post['kapak'])) ?>" class="img-fluid rounded shadow-sm mb-4 w-100" alt="<?= e($post['baslik']) ?>" style="max-height:480px;object-fit:cover;">
      <?php endif; ?>

      <?php if (!empty($post['ozet'])): ?>
        <p class="lead text-muted"><?= e($post['ozet']) ?></p>
      <?php endif; ?>

      <div class="mz-prose">
        <?= $post['icerik'] /* HTML icerik admin panelden */ ?>
      </div>

      <hr class="my-5">

      <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <div class="d-flex gap-2">
          <span class="text-muted small me-2">Paylaş:</span>
          <a class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(SITE_BASE_URL . '/blog/' . $post['slug']) ?>"><i class="bi bi-facebook"></i></a>
          <a class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?url=<?= urlencode(SITE_BASE_URL . '/blog/' . $post['slug']) ?>&text=<?= urlencode($post['baslik']) ?>"><i class="bi bi-twitter-x"></i></a>
          <a class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener" href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode(SITE_BASE_URL . '/blog/' . $post['slug']) ?>"><i class="bi bi-linkedin"></i></a>
          <a class="btn btn-outline-success btn-sm" target="_blank" rel="noopener" href="https://wa.me/?text=<?= urlencode($post['baslik'] . ' ' . SITE_BASE_URL . '/blog/' . $post['slug']) ?>"><i class="bi bi-whatsapp"></i></a>
        </div>
        <a href="<?= u('/teklif-al') ?>" class="btn btn-warning fw-semibold"><i class="bi bi-shield-check"></i> Teklif Al</a>
      </div>
    </div>
  </div>

  <?php if ($benzer): ?>
    <div class="row mt-5">
      <div class="col-12">
        <h3 class="fw-bold mb-4">Benzer Yazılar</h3>
      </div>
      <?php foreach ($benzer as $b): ?>
        <div class="col-md-4">
          <a href="<?= u('/blog/' . $b['slug']) ?>" class="text-decoration-none text-dark">
            <article class="mz-blog-card h-100">
              <?php if (!empty($b['kapak'])): ?>
                <div class="mz-blog-img" style="background-image:url('<?= e(asset('uploads/blog/' . $b['kapak'])) ?>')"></div>
              <?php else: ?>
                <div class="mz-blog-img mz-blog-img-ph"><i class="bi bi-journal-text"></i></div>
              <?php endif; ?>
              <div class="p-3">
                <h6 class="fw-bold"><?= e($b['baslik']) ?></h6>
                <small class="text-muted"><?= tr_date($b['yayin_tarihi']) ?></small>
              </div>
            </article>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</article>

<?php require MIZAN_INC . '/footer.php';
