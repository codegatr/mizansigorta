<?php
if (!defined('MIZAN_BOOT')) { http_response_code(403); exit; }

$pageTitle = 'Blog - ' . SITE_NAME;
$pageDesc  = 'Sigorta dünyasından bilgilendirici yazılar, ipuçları ve rehberler.';

$page = max(1, (int)($_GET['s'] ?? 1));
$per  = 9;
$q    = trim((string)($_GET['q'] ?? ''));
$cat  = trim((string)($_GET['kategori'] ?? ''));

$where  = "aktif=1 AND yayin_tarihi<=NOW()";
$params = [];
if ($q !== '')   { $where .= " AND (baslik LIKE ? OR ozet LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($cat !== '') { $where .= " AND kategori = ?"; $params[] = $cat; }

$total = (int)db_value('SELECT COUNT(*) FROM ' . t('blog') . " WHERE $where", $params);
$pag   = paginate($total, $per, $page);

$posts = db_all('SELECT * FROM ' . t('blog') . " WHERE $where ORDER BY yayin_tarihi DESC LIMIT $per OFFSET " . $pag['offset'], $params);

// Öne çıkan: ilk sayfa + filtre yoksa en yeni yazıyı feature olarak ayır
$featured = null;
if ($page === 1 && $q === '' && $cat === '' && count($posts) > 0) {
    $featured = array_shift($posts);
}

$kategoriler = db_all('SELECT kategori, COUNT(*) c FROM ' . t('blog') . ' WHERE aktif=1 AND kategori<>"" GROUP BY kategori ORDER BY c DESC');
$populer     = db_all('SELECT slug, baslik, kapak_gorseli, yayin_tarihi FROM ' . t('blog') . ' WHERE aktif=1 ORDER BY goruntulenme DESC LIMIT 5');
$totalAll    = (int)db_value('SELECT COUNT(*) FROM ' . t('blog') . ' WHERE aktif=1');

require MIZAN_INC . '/header.php';
?>

<section class="mz-page-head">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
      <div>
        <h1 class="fw-bold mb-1">Blog</h1>
        <p class="mb-0 small" style="color:rgba(255,255,255,.8)">Sigorta dünyasından <strong><?= $totalAll ?> bilgilendirici yazı</strong></p>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
          <li class="breadcrumb-item"><a href="<?= u('/') ?>">Anasayfa</a></li>
          <li class="breadcrumb-item active">Blog</li>
        </ol></nav>
      </div>
      <div class="d-none d-md-block">
        <i class="bi bi-journal-bookmark-fill" style="font-size:2.5rem;color:rgba(238,39,55,.5)"></i>
      </div>
    </div>
  </div>
</section>

<!-- Search bar + kategori chips -->
<section style="background:#fff;border-bottom:1px solid var(--mz-border)">
  <div class="container py-3">
    <div class="row g-2 align-items-center">
      <div class="col-md-6 col-lg-5">
        <form method="get" class="input-group">
          <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" name="q" class="form-control border-start-0" placeholder="Blog yazılarında ara..." value="<?= e($q) ?>">
          <?php if ($cat !== ''): ?><input type="hidden" name="kategori" value="<?= e($cat) ?>"><?php endif; ?>
          <button class="btn btn-warning fw-semibold">Ara</button>
        </form>
      </div>
      <div class="col-md-6 col-lg-7">
        <div class="d-flex flex-wrap gap-1 justify-content-md-end">
          <a href="<?= u('/blog') ?>" class="badge <?= $cat === '' && $q === '' ? 'bg-danger text-white' : 'bg-light text-dark border' ?> text-decoration-none px-3 py-2">Tümü</a>
          <?php foreach (array_slice($kategoriler, 0, 6) as $k): ?>
            <a href="?kategori=<?= urlencode($k['kategori']) ?>"
               class="badge <?= $cat === $k['kategori'] ? 'bg-danger text-white' : 'bg-light text-dark border' ?> text-decoration-none px-3 py-2">
              <?= e($k['kategori']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php if ($q !== '' || $cat !== ''): ?>
      <div class="mt-2 small text-muted">
        <i class="bi bi-funnel"></i> Filtre:
        <?php if ($q !== ''): ?><span class="badge bg-secondary me-1">"<?= e($q) ?>"</span><?php endif; ?>
        <?php if ($cat !== ''): ?><span class="badge bg-secondary me-1"><?= e($cat) ?></span><?php endif; ?>
        · <strong><?= $total ?></strong> sonuç ·
        <a href="<?= u('/blog') ?>" class="text-decoration-none">Filtreleri temizle <i class="bi bi-x-circle"></i></a>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="container py-5">
  <div class="row g-4">
    <div class="col-lg-8">
      <?php if (!$posts && !$featured): ?>
        <div class="text-center py-5" style="background:#fff;border-radius:12px;border:1px solid var(--mz-border)">
          <i class="bi bi-search display-3 text-muted d-block mb-3"></i>
          <h5 class="fw-bold">Sonuç bulunamadı</h5>
          <p class="text-muted mb-3">Aramanızla eşleşen yazı yok. Farklı bir kelime deneyin veya filtreleri temizleyin.</p>
          <a href="<?= u('/blog') ?>" class="btn btn-outline-primary"><i class="bi bi-arrow-left"></i> Tüm yazılar</a>
        </div>
      <?php else: ?>
        <!-- Öne çıkan yazı -->
        <?php if ($featured): ?>
          <article class="mz-blog-featured mb-5">
            <a href="<?= u('/blog/' . $featured['slug']) ?>" class="text-decoration-none">
              <div class="row g-0">
                <div class="col-md-6">
                  <?php if (!empty($featured['kapak_gorseli'])): ?>
                    <img src="<?= e(asset('uploads/blog/' . $featured['kapak_gorseli'])) ?>" alt="<?= e($featured['baslik']) ?>" class="mz-blog-featured-img">
                  <?php else: ?>
                    <div class="mz-blog-featured-img mz-blog-img-ph"><i class="bi bi-journal-text"></i></div>
                  <?php endif; ?>
                </div>
                <div class="col-md-6 d-flex flex-column justify-content-center p-4 p-md-5">
                  <div>
                    <span class="badge bg-warning text-dark mb-2"><i class="bi bi-star-fill"></i> Öne Çıkan</span>
                    <?php if (!empty($featured['kategori'])): ?>
                      <span class="badge bg-light text-dark border ms-1"><?= e($featured['kategori']) ?></span>
                    <?php endif; ?>
                  </div>
                  <h2 class="fw-bold mt-3 mb-2" style="color:var(--mz-navy);font-size:1.6rem;line-height:1.3"><?= e($featured['baslik']) ?></h2>
                  <p class="text-muted mb-3" style="font-size:.95rem;line-height:1.6"><?= e(mb_substr($featured['ozet'] ?? '', 0, 180)) ?>…</p>
                  <div class="d-flex justify-content-between align-items-center mt-auto">
                    <small class="text-muted"><i class="bi bi-calendar3"></i> <?= tr_date($featured['yayin_tarihi']) ?>
                      <?php if (!empty($featured['goruntulenme'])): ?>
                        <span class="ms-2"><i class="bi bi-eye"></i> <?= (int)$featured['goruntulenme'] ?></span>
                      <?php endif; ?>
                    </small>
                    <span class="text-warning fw-semibold small">Devamını oku <i class="bi bi-arrow-right"></i></span>
                  </div>
                </div>
              </div>
            </a>
          </article>
        <?php endif; ?>

        <!-- Diger yazilar grid -->
        <?php if ($posts): ?>
          <div class="row g-4">
            <?php foreach ($posts as $p): ?>
              <div class="col-md-6">
                <article class="mz-blog-card h-100">
                  <a href="<?= u('/blog/' . $p['slug']) ?>" class="text-decoration-none d-flex flex-column h-100">
                    <?php if (!empty($p['kapak_gorseli'])): ?>
                      <img src="<?= e(asset('uploads/blog/' . $p['kapak_gorseli'])) ?>" alt="<?= e($p['baslik']) ?>" class="mz-blog-img">
                    <?php else: ?>
                      <div class="mz-blog-img mz-blog-img-ph"><i class="bi bi-journal-text"></i></div>
                    <?php endif; ?>
                    <div class="body">
                      <?php if (!empty($p['kategori'])): ?>
                        <span class="badge bg-warning text-dark mb-2 align-self-start"><?= e($p['kategori']) ?></span>
                      <?php endif; ?>
                      <h5 class="title fw-bold mb-2" style="line-height:1.35"><?= e($p['baslik']) ?></h5>
                      <p class="small text-muted mb-3" style="line-height:1.55;flex:1"><?= e(mb_substr($p['ozet'] ?? '', 0, 110)) ?>…</p>
                      <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                        <small class="text-muted"><i class="bi bi-calendar3"></i> <?= tr_date($p['yayin_tarihi']) ?></small>
                        <span class="small fw-semibold" style="color:var(--mz-red)">Oku <i class="bi bi-arrow-right"></i></span>
                      </div>
                    </div>
                  </a>
                </article>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($pag['pages'] > 1): ?>
          <nav class="mt-5" aria-label="Sayfalama">
            <ul class="pagination justify-content-center">
              <?php if ($page > 1): ?>
                <li class="page-item"><a class="page-link" href="?s=<?= $page - 1 ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?><?= $cat !== '' ? '&kategori=' . urlencode($cat) : '' ?>"><i class="bi bi-chevron-left"></i></a></li>
              <?php endif; ?>
              <?php for ($i = 1; $i <= $pag['pages']; $i++): ?>
                <li class="page-item <?= $i === $pag['page'] ? 'active' : '' ?>">
                  <a class="page-link" href="?s=<?= $i ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?><?= $cat !== '' ? '&kategori=' . urlencode($cat) : '' ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>
              <?php if ($page < $pag['pages']): ?>
                <li class="page-item"><a class="page-link" href="?s=<?= $page + 1 ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?><?= $cat !== '' ? '&kategori=' . urlencode($cat) : '' ?>"><i class="bi bi-chevron-right"></i></a></li>
              <?php endif; ?>
            </ul>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <aside class="col-lg-4">
      <?php if ($kategoriler): ?>
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
          <h6 class="fw-bold mb-3" style="color:var(--mz-navy)"><i class="bi bi-tags-fill text-warning"></i> Kategoriler</h6>
          <div class="d-flex flex-column gap-1">
            <?php foreach ($kategoriler as $k): ?>
              <a href="?kategori=<?= urlencode($k['kategori']) ?>" class="d-flex justify-content-between align-items-center text-decoration-none p-2 rounded <?= $cat === $k['kategori'] ? 'bg-warning bg-opacity-25' : '' ?>" style="transition:background .15s">
                <span class="<?= $cat === $k['kategori'] ? 'fw-bold text-dark' : 'text-dark' ?>"><i class="bi bi-chevron-right small text-muted"></i> <?= e($k['kategori']) ?></span>
                <span class="badge bg-light text-dark border"><?= (int)$k['c'] ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($populer): ?>
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
          <h6 class="fw-bold mb-3" style="color:var(--mz-navy)"><i class="bi bi-fire text-warning"></i> En Çok Okunanlar</h6>
          <?php foreach ($populer as $i => $pp): ?>
            <a href="<?= u('/blog/' . $pp['slug']) ?>" class="d-flex gap-3 align-items-start text-decoration-none mb-3 pb-3 <?= $i < count($populer) - 1 ? 'border-bottom' : '' ?>">
              <div style="position:relative;flex-shrink:0">
                <?php if (!empty($pp['kapak_gorseli'])): ?>
                  <img src="<?= e(asset('uploads/blog/' . $pp['kapak_gorseli'])) ?>" width="64" height="64" class="rounded" style="object-fit:cover" alt="">
                <?php else: ?>
                  <div class="rounded d-flex align-items-center justify-content-center" style="width:64px;height:64px;background:linear-gradient(135deg,var(--mz-navy),var(--mz-dark-2));color:#fff;font-size:1.5rem"><i class="bi bi-journal-text"></i></div>
                <?php endif; ?>
                <span class="position-absolute top-0 start-0 translate-middle badge rounded-pill bg-danger" style="font-size:.65rem"><?= $i + 1 ?></span>
              </div>
              <div class="flex-grow-1">
                <span class="fw-semibold text-dark small d-block" style="line-height:1.35"><?= e($pp['baslik']) ?></span>
                <small class="text-muted"><i class="bi bi-calendar3"></i> <?= tr_date($pp['yayin_tarihi']) ?></small>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- CTA -->
      <div class="mz-side-card text-center">
        <i class="bi bi-headset" style="font-size:2.5rem;color:var(--mz-red)"></i>
        <h6 class="fw-bold mt-2 mb-1">Teklif Talebi Oluşturun</h6>
        <p class="small mb-3" style="opacity:.85">Müsait temsilcimiz sizi arayarak en uygun çözümü sunsun.</p>
        <a href="<?= u('/teklif-al') ?>" class="btn btn-warning btn-sm fw-semibold w-100">Teklif Talebi Oluştur <i class="bi bi-arrow-right"></i></a>
      </div>
    </aside>
  </div>
</section>

<style>
.mz-blog-featured { background:#fff; border:1px solid var(--mz-border); border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,.04); transition:all .25s; }
.mz-blog-featured:hover { transform:translateY(-3px); box-shadow:0 14px 36px rgba(0,0,0,.1); }
.mz-blog-featured-img { width:100%; height:100%; min-height:280px; object-fit:cover; display:block; }
.mz-blog-featured .mz-blog-img-ph { min-height:280px; }
@media (max-width: 767.98px) { .mz-blog-featured-img { min-height:200px; } }
</style>

<?php require MIZAN_INC . '/footer.php';
