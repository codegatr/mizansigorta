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

// Öne çıkan yazı: ilk sayfa + filtre yoksa en yeni 1'i ayır
$featured = null;
if ($page === 1 && $q === '' && $cat === '' && count($posts) > 0) {
    $featured = array_shift($posts);
}

$kategoriler = db_all('SELECT kategori, COUNT(*) c FROM ' . t('blog') . ' WHERE aktif=1 AND kategori<>"" GROUP BY kategori ORDER BY c DESC');
$populer     = db_all('SELECT slug, baslik, kapak_gorseli, yayin_tarihi FROM ' . t('blog') . ' WHERE aktif=1 ORDER BY goruntulenme DESC LIMIT 5');
$totalAll    = (int)db_value('SELECT COUNT(*) FROM ' . t('blog') . ' WHERE aktif=1');

// Suggestion data: tum yayinda olan yazilarin minimum metaverisi (slug, baslik, kategori, ozet)
$suggestData = db_all('SELECT slug, baslik, kategori, ozet FROM ' . t('blog') . ' WHERE aktif=1 AND yayin_tarihi<=NOW() ORDER BY yayin_tarihi DESC LIMIT 200');

require MIZAN_INC . '/header.php';
?>

<style>
/* ============== HERO ============== */
.mz-blog-hero {
  position: relative; padding: 4.5rem 0 4rem;
  background: linear-gradient(135deg, var(--mz-navy) 0%, var(--mz-navy-2) 100%);
  color: #fff; overflow: hidden;
}
.mz-blog-hero::before {
  content: ''; position: absolute; inset: 0;
  background:
    radial-gradient(circle at 20% 50%, rgba(227,11,48,.14) 0%, transparent 50%),
    radial-gradient(circle at 80% 80%, rgba(227,11,48,.08) 0%, transparent 50%);
  pointer-events: none;
}

/* ============== SEARCH ============== */
.mz-blog-search { position: relative; max-width: 720px; margin: 2rem auto 0; z-index: 5; }
.mz-blog-search input {
  width: 100%; padding: 1.15rem 3.5rem 1.15rem 3.5rem;
  border: 0; border-radius: 50px;
  font-size: 1.05rem; box-shadow: 0 25px 50px rgba(15,30,55,.3);
  background: #fff; color: var(--mz-navy);
}
.mz-blog-search input:focus { outline: 3px solid rgba(227,11,48,.3); }
.mz-blog-search-icon {
  position: absolute; left: 1.25rem; top: 1.15rem;
  color: var(--mz-red); font-size: 1.25rem; pointer-events: none;
}
.mz-blog-search-clear {
  position: absolute; right: 1rem; top: 50%; transform: translateY(-50%);
  background: rgba(0,0,0,.05); border: 0; width: 32px; height: 32px; border-radius: 50%;
  display: none; align-items: center; justify-content: center; cursor: pointer; color: var(--mz-navy);
}
.mz-blog-search-clear:hover { background: rgba(0,0,0,.1); }

/* ============== SUGGESTIONS ============== */
.mz-blog-suggestions {
  position: absolute; top: calc(100% + 8px); left: 0; right: 0;
  background: #fff; border-radius: 16px; box-shadow: 0 30px 60px rgba(15,30,55,.25);
  padding: .5rem; max-height: 420px; overflow-y: auto;
  display: none; z-index: 100; text-align: left;
}
.mz-blog-suggest-item {
  display: flex; align-items: flex-start; gap: .75rem; padding: .75rem 1rem; border-radius: 10px;
  cursor: pointer; text-decoration: none; color: var(--mz-navy);
  transition: background .12s;
}
.mz-blog-suggest-item:hover, .mz-blog-suggest-item.active { background: rgba(227,11,48,.07); color: var(--mz-navy); }
.mz-blog-suggest-cat {
  font-size: .7rem; text-transform: uppercase; letter-spacing: .05em; opacity: .65;
  margin-bottom: .15rem;
}
.mz-blog-suggest-text { font-weight: 600; line-height: 1.35; }
.mz-blog-suggest-excerpt { font-size: .82rem; opacity: .7; margin-top: .15rem; line-height: 1.4; }
.mz-blog-suggest-empty { padding: 1.5rem; text-align: center; color: #888; font-size: .9rem; }
.mz-blog-suggest-footer { padding: .65rem 1rem; border-top: 1px solid rgba(0,0,0,.06); font-size: .8rem; color: #888; display: flex; justify-content: space-between; align-items: center; }
.mz-blog-suggest-kbd { background: #f1f5f9; padding: 1px 6px; border-radius: 4px; font-family: monospace; font-size: .7rem; }

/* ============== CHIPS ============== */
.mz-blog-cat-chips { display: flex; flex-wrap: wrap; gap: .5rem; justify-content: center; margin: 1.5rem 0 0; position: relative; z-index: 5; }
.mz-blog-chip {
  padding: .4rem 1.05rem; border-radius: 50px;
  background: rgba(255,255,255,.1); color: #fff;
  border: 1px solid rgba(255,255,255,.25); font-size: .88rem;
  text-decoration: none; transition: all .15s;
}
.mz-blog-chip:hover { background: rgba(255,255,255,.2); color: #fff; }
.mz-blog-chip.active { background: var(--mz-red); border-color: var(--mz-red); color: #fff; box-shadow: 0 8px 20px rgba(227,11,48,.35); }

.mz-blog-stat { display: inline-flex; align-items: center; gap: .35rem; color: rgba(255,255,255,.85); font-size: .85rem; }

mark { background: rgba(244, 211, 94, 0.55); color: inherit; padding: 0 2px; border-radius: 3px; }

/* ============== FEATURED ============== */
.mz-blog-featured { background:#fff; border:1px solid var(--mz-border); border-radius:16px; overflow:hidden; box-shadow:0 8px 30px rgba(0,0,0,.06); transition:all .25s; }
.mz-blog-featured:hover { transform:translateY(-3px); box-shadow:0 16px 44px rgba(0,0,0,.12); }
.mz-blog-featured-img { width:100%; height:100%; min-height:300px; object-fit:cover; display:block; }
.mz-blog-featured .mz-blog-img-ph { min-height:300px; }
@media (max-width: 767.98px) { .mz-blog-featured-img { min-height:200px; } }

/* ============== EMPTY ============== */
.mz-blog-empty {
  text-align: center; padding: 4rem 1.5rem;
  background: #fff; border-radius: 16px; border: 1px solid var(--mz-border);
}
.mz-blog-empty i { font-size: 4rem; color: #cbd5e1; }
</style>

<!-- ============== HERO ============== -->
<section class="mz-blog-hero">
  <div class="container text-center position-relative">
    <span class="mz-script mz-script-md mz-script-red d-block mb-1" style="color:#f4d35e !important">Sigorta Rehberi</span>
    <h1 class="display-4 fw-bold mb-2">Blog</h1>
    <p class="lead text-white-50 mx-auto mb-0" style="max-width:600px">
      Kasko, konut, sağlık, işyeri sigortaları ve daha fazlası — tarafsız, anlaşılır içerikler
    </p>

    <!-- Live arama (suggestion'li) -->
    <div class="mz-blog-search">
      <i class="bi bi-search mz-blog-search-icon"></i>
      <input type="text" id="blogSearch" placeholder="Aramaya başlayın... (örn. kasko fiyatı, DASK, hasar süreci)" autocomplete="off" value="<?= e($q) ?>">
      <button type="button" class="mz-blog-search-clear" id="blogSearchClear" title="Temizle"><i class="bi bi-x-lg"></i></button>
      <div class="mz-blog-suggestions" id="blogSuggestions"></div>
    </div>

    <!-- Kategori chip filtreleri -->
    <?php if ($kategoriler): ?>
      <div class="mz-blog-cat-chips">
        <a href="<?= u('/blog') ?>" class="mz-blog-chip <?= $cat === '' ? 'active' : '' ?>">Tümü</a>
        <?php foreach (array_slice($kategoriler, 0, 8) as $k): ?>
          <a href="?kategori=<?= urlencode($k['kategori']) ?>" class="mz-blog-chip <?= $cat === $k['kategori'] ? 'active' : '' ?>"><?= e($k['kategori']) ?></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="mt-3">
      <span class="mz-blog-stat"><i class="bi bi-collection"></i> <?= $totalAll ?> yazı</span>
      <span class="mz-blog-stat ms-3"><i class="bi bi-tags"></i> <?= count($kategoriler) ?> kategori</span>
    </div>
  </div>
</section>

<!-- ============== ICERIK ============== -->
<section class="container py-5">
  <?php if ($q !== '' || $cat !== ''): ?>
    <div class="mb-4 small text-muted">
      <i class="bi bi-funnel"></i> Filtre:
      <?php if ($q !== ''): ?><span class="badge bg-secondary me-1">"<?= e($q) ?>"</span><?php endif; ?>
      <?php if ($cat !== ''): ?><span class="badge bg-secondary me-1"><?= e($cat) ?></span><?php endif; ?>
      · <strong><?= $total ?></strong> sonuç ·
      <a href="<?= u('/blog') ?>" class="text-decoration-none">Filtreleri temizle <i class="bi bi-x-circle"></i></a>
    </div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-8">
      <?php if (!$posts && !$featured): ?>
        <div class="mz-blog-empty">
          <i class="bi bi-search d-block mb-3"></i>
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

<script>
(function () {
  'use strict';

  // PHP'den gelen suggestion verisi (slug, baslik, kategori, ozet)
  const POSTS = <?= json_encode(array_map(function($r){
      return [
          'slug'  => $r['slug'],
          'title' => $r['baslik'],
          'cat'   => $r['kategori'] ?? '',
          'excerpt' => mb_substr((string)($r['ozet'] ?? ''), 0, 110),
      ];
  }, $suggestData), JSON_UNESCAPED_UNICODE) ?>;
  const BLOG_BASE = '<?= u('/blog/') ?>';

  const searchInput = document.getElementById('blogSearch');
  const searchClear = document.getElementById('blogSearchClear');
  const suggestions = document.getElementById('blogSuggestions');

  function escapeHtml(s) { return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }

  function highlight(text, q) {
    if (!q) return escapeHtml(text);
    const re = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
    return escapeHtml(text).replace(re, '<mark>$1</mark>');
  }

  function turkishLower(s) {
    return s.toLocaleLowerCase('tr-TR').replace(/i̇/g, 'i');
  }

  let activeIdx = -1;
  let currentMatches = [];

  function renderSuggestions() {
    const qRaw = searchInput.value.trim();
    const q = turkishLower(qRaw);
    if (q.length < 2) {
      suggestions.style.display = 'none';
      currentMatches = [];
      activeIdx = -1;
      return;
    }

    // Eslesen yazilar (baslik veya ozet'te), max 6
    currentMatches = [];
    for (const p of POSTS) {
      if (currentMatches.length >= 6) break;
      const haystack = turkishLower(p.title + ' ' + p.excerpt + ' ' + p.cat);
      if (haystack.indexOf(q) !== -1) {
        currentMatches.push(p);
      }
    }
    activeIdx = -1;

    if (currentMatches.length === 0) {
      suggestions.innerHTML = '<div class="mz-blog-suggest-empty"><i class="bi bi-info-circle"></i> "' + escapeHtml(qRaw) + '" için eşleşen yazı bulunamadı</div>';
    } else {
      let html = currentMatches.map(p =>
        '<a href="' + BLOG_BASE + encodeURIComponent(p.slug) + '" class="mz-blog-suggest-item">'
        + '<i class="bi bi-journal-text text-warning fs-5"></i>'
        + '<div class="flex-grow-1">'
        +   (p.cat ? '<div class="mz-blog-suggest-cat">' + escapeHtml(p.cat) + '</div>' : '')
        +   '<div class="mz-blog-suggest-text">' + highlight(p.title, qRaw) + '</div>'
        +   (p.excerpt ? '<div class="mz-blog-suggest-excerpt">' + highlight(p.excerpt, qRaw) + '…</div>' : '')
        + '</div>'
        + '</a>'
      ).join('');
      html += '<div class="mz-blog-suggest-footer">'
           + '<span><span class="mz-blog-suggest-kbd">↑↓</span> gez · <span class="mz-blog-suggest-kbd">↵</span> aç</span>'
           + '<span>' + currentMatches.length + ' sonuç</span>'
           + '</div>';
      suggestions.innerHTML = html;
    }
    suggestions.style.display = 'block';
  }

  function updateActive() {
    suggestions.querySelectorAll('.mz-blog-suggest-item').forEach((el, idx) => {
      el.classList.toggle('active', idx === activeIdx);
    });
    const active = suggestions.querySelector('.mz-blog-suggest-item.active');
    if (active) active.scrollIntoView({ block: 'nearest' });
  }

  searchInput.addEventListener('input', () => {
    searchClear.style.display = searchInput.value ? 'flex' : 'none';
    renderSuggestions();
  });

  searchInput.addEventListener('keydown', (e) => {
    if (suggestions.style.display !== 'block' || currentMatches.length === 0) {
      if (e.key === 'Enter') {
        // Enter ile direkt arama sayfasina git
        e.preventDefault();
        if (searchInput.value.trim()) {
          window.location = '<?= u('/blog') ?>?q=' + encodeURIComponent(searchInput.value.trim());
        }
      }
      return;
    }
    if (e.key === 'ArrowDown') { e.preventDefault(); activeIdx = (activeIdx + 1) % currentMatches.length; updateActive(); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); activeIdx = (activeIdx - 1 + currentMatches.length) % currentMatches.length; updateActive(); }
    else if (e.key === 'Enter') {
      e.preventDefault();
      if (activeIdx >= 0 && currentMatches[activeIdx]) {
        window.location = BLOG_BASE + encodeURIComponent(currentMatches[activeIdx].slug);
      } else if (searchInput.value.trim()) {
        window.location = '<?= u('/blog') ?>?q=' + encodeURIComponent(searchInput.value.trim());
      }
    }
    else if (e.key === 'Escape') { suggestions.style.display = 'none'; }
  });

  searchClear.addEventListener('click', () => {
    searchInput.value = '';
    searchClear.style.display = 'none';
    suggestions.style.display = 'none';
    searchInput.focus();
  });

  // Disari tiklayinca kapat
  document.addEventListener('click', (e) => {
    if (!searchInput.parentElement.contains(e.target)) {
      suggestions.style.display = 'none';
    }
  });

  // URL'de q varsa input'u doldur ve clear butonunu goster
  if (searchInput.value) searchClear.style.display = 'flex';
})();
</script>

<?php require MIZAN_INC . '/footer.php';
