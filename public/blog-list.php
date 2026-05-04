<?php
if (!defined('MIZAN_BOOT')) { http_response_code(403); exit; }

$pageTitle = 'Blog - ' . SITE_NAME;
$pageDesc  = 'Sigorta dünyasından haberler, ipuçları ve rehberler.';

$page = max(1, (int)($_GET['s'] ?? 1));
$per  = 9;
$q    = trim((string)($_GET['q'] ?? ''));
$cat  = trim((string)($_GET['kategori'] ?? ''));

$where  = "yayinda=1 AND yayin_tarihi<=NOW()";
$params = [];
if ($q !== '')   { $where .= " AND (baslik LIKE ? OR ozet LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($cat !== '') { $where .= " AND kategori = ?"; $params[] = $cat; }

$total = (int)db_value('SELECT COUNT(*) FROM ' . t('blog') . " WHERE $where", $params);
$pag   = paginate($total, $per, $page);

$posts = db_all('SELECT * FROM ' . t('blog') . " WHERE $where ORDER BY yayin_tarihi DESC LIMIT $per OFFSET " . $pag['offset'], $params);

$kategoriler = db_all('SELECT kategori, COUNT(*) c FROM ' . t('blog') . ' WHERE yayinda=1 AND kategori<>"" GROUP BY kategori ORDER BY c DESC');
$populer     = db_all('SELECT slug, baslik, kapak FROM ' . t('blog') . ' WHERE yayinda=1 ORDER BY goruntuleme DESC LIMIT 5');

require MIZAN_INC . '/header.php';
?>

<section class="mz-page-head py-5">
  <div class="container">
    <h1 class="display-6 fw-bold mb-2">Blog</h1>
    <p class="lead text-white-50 mb-0">Sigorta dünyasından bilgilendirici yazılar.</p>
  </div>
</section>

<section class="container py-5">
  <div class="row g-4">
    <div class="col-lg-8">
      <?php if (!$posts): ?>
        <div class="alert alert-info text-center py-5">
          <i class="bi bi-journal display-4 d-block mb-3"></i>
          Aramanızla eşleşen yazı bulunamadı.
        </div>
      <?php else: ?>
        <div class="row g-4">
          <?php foreach ($posts as $p): ?>
            <div class="col-md-6">
              <article class="mz-blog-card h-100">
                <a href="<?= u('/blog/' . $p['slug']) ?>" class="text-decoration-none text-dark">
                  <?php if (!empty($p['kapak'])): ?>
                    <div class="mz-blog-img" style="background-image:url('<?= e(asset('uploads/blog/' . $p['kapak'])) ?>')"></div>
                  <?php else: ?>
                    <div class="mz-blog-img mz-blog-img-ph"><i class="bi bi-journal-text"></i></div>
                  <?php endif; ?>
                  <div class="p-3">
                    <?php if (!empty($p['kategori'])): ?>
                      <span class="badge bg-warning text-dark mb-2"><?= e($p['kategori']) ?></span>
                    <?php endif; ?>
                    <h5 class="fw-bold mb-2"><?= e($p['baslik']) ?></h5>
                    <p class="text-muted small mb-2"><?= e(mb_substr($p['ozet'] ?? '', 0, 120)) ?>…</p>
                    <small class="text-muted"><i class="bi bi-calendar3"></i> <?= tr_date($p['yayin_tarihi']) ?></small>
                  </div>
                </a>
              </article>
            </div>
          <?php endforeach; ?>
        </div>

        <?php if ($pag['pages'] > 1): ?>
          <nav class="mt-4">
            <ul class="pagination justify-content-center">
              <?php for ($i = 1; $i <= $pag['pages']; $i++): ?>
                <li class="page-item <?= $i === $pag['page'] ? 'active' : '' ?>">
                  <a class="page-link" href="?s=<?= $i ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?><?= $cat !== '' ? '&kategori=' . urlencode($cat) : '' ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>
            </ul>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <aside class="col-lg-4">
      <div class="mz-side-card mb-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-search text-warning"></i> Arama</h5>
        <form method="get" class="d-flex gap-2">
          <input type="text" name="q" class="form-control" placeholder="Yazı ara..." value="<?= e($q) ?>">
          <button class="btn btn-primary"><i class="bi bi-search"></i></button>
        </form>
      </div>

      <?php if ($kategoriler): ?>
      <div class="mz-side-card mb-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-tags text-warning"></i> Kategoriler</h5>
        <ul class="list-unstyled mb-0">
          <?php foreach ($kategoriler as $k): ?>
            <li class="d-flex justify-content-between border-bottom py-2">
              <a class="text-decoration-none text-dark" href="?kategori=<?= urlencode($k['kategori']) ?>"><?= e($k['kategori']) ?></a>
              <span class="badge bg-light text-dark"><?= (int)$k['c'] ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <?php if ($populer): ?>
      <div class="mz-side-card mb-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-fire text-warning"></i> En Çok Okunanlar</h5>
        <?php foreach ($populer as $pp): ?>
          <a href="<?= u('/blog/' . $pp['slug']) ?>" class="d-flex gap-2 align-items-center text-decoration-none text-dark mb-3">
            <?php if (!empty($pp['kapak'])): ?>
              <img src="<?= e(asset('uploads/blog/' . $pp['kapak'])) ?>" width="60" height="60" class="rounded object-fit-cover" alt="">
            <?php else: ?>
              <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:60px;height:60px;"><i class="bi bi-journal-text text-muted"></i></div>
            <?php endif; ?>
            <span class="small fw-semibold"><?= e($pp['baslik']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div class="mz-cta-box text-center">
        <i class="bi bi-shield-check display-5 text-warning"></i>
        <h6 class="fw-bold mt-2">Hızlı Teklif Alın</h6>
        <p class="small text-muted">Dakikalar içinde size özel fiyat.</p>
        <a href="<?= u('/teklif-al') ?>" class="btn btn-primary btn-sm w-100">Teklif Al</a>
      </div>
    </aside>
  </div>
</section>

<?php require MIZAN_INC . '/footer.php';
