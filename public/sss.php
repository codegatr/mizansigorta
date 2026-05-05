<?php
if (!defined('MIZAN_BOOT')) { http_response_code(403); exit; }

$pageTitle = 'Sıkça Sorulan Sorular - ' . SITE_NAME;
$pageDesc  = 'Sigorta ürünlerimiz ve süreçlerimizle ilgili sıkça sorulan sorular ve cevapları. Kasko, trafik, DASK, sağlık ve daha fazlası.';

$kategoriFilter = $_GET['k'] ?? '';
$qFilter = trim($_GET['q'] ?? '');

$where = "aktif=1";
$params = [];
if ($kategoriFilter !== '') {
    $where .= " AND kategori = ?";
    $params[] = $kategoriFilter;
}

// Tum sorulari yukle (JS ile filtreleyecegiz; sayisi az 35 civari)
$sorular     = db_all('SELECT id, kategori, soru, cevap, sira FROM ' . t('sss') . " WHERE aktif=1 ORDER BY kategori, sira ASC, id ASC");
$kategoriler = db_all('SELECT DISTINCT kategori FROM ' . t('sss') . ' WHERE aktif=1 AND kategori<>"" ORDER BY kategori');

// Kategoriye gore grupla (server-side render kategorili gosterim icin)
$gruplu = [];
foreach ($sorular as $s) {
    $k = $s['kategori'] ?: 'Diğer';
    $gruplu[$k][] = $s;
}

require MIZAN_INC . '/header.php';
?>

<style>
.mz-sss-hero {
  position: relative; padding: 5rem 0 4rem;
  background: linear-gradient(135deg, var(--mz-navy) 0%, var(--mz-navy-2) 100%);
  color: #fff; overflow: hidden;
}
.mz-sss-hero::before {
  content: ''; position: absolute; inset: 0;
  background:
    radial-gradient(circle at 20% 50%, rgba(227,11,48,.12) 0%, transparent 50%),
    radial-gradient(circle at 80% 80%, rgba(227,11,48,.08) 0%, transparent 50%);
  pointer-events: none;
}
.mz-sss-search {
  position: relative; max-width: 720px; margin: 2rem auto 0; z-index: 5;
}
.mz-sss-search input {
  width: 100%; padding: 1.15rem 3.5rem 1.15rem 3.5rem;
  border: 0; border-radius: 50px;
  font-size: 1.05rem; box-shadow: 0 25px 50px rgba(15,30,55,.3);
  background: #fff; color: var(--mz-navy);
}
.mz-sss-search input:focus { outline: 3px solid rgba(227,11,48,.3); }
.mz-sss-search-icon {
  position: absolute; left: 1.25rem; top: 50%; transform: translateY(-50%);
  color: var(--mz-red); font-size: 1.25rem;
}
.mz-sss-search-clear {
  position: absolute; right: 1rem; top: 50%; transform: translateY(-50%);
  background: rgba(0,0,0,.05); border: 0; width: 32px; height: 32px; border-radius: 50%;
  display: none; align-items: center; justify-content: center; cursor: pointer; color: var(--mz-navy);
}
.mz-sss-search-clear:hover { background: rgba(0,0,0,.1); }

.mz-sss-suggestions {
  position: absolute; top: calc(100% + 8px); left: 0; right: 0;
  background: #fff; border-radius: 16px; box-shadow: 0 30px 60px rgba(15,30,55,.25);
  padding: .5rem; max-height: 380px; overflow-y: auto;
  display: none; z-index: 100; text-align: left;
}
.mz-sss-suggest-item {
  display: flex; align-items: flex-start; gap: .75rem; padding: .75rem 1rem; border-radius: 10px;
  cursor: pointer; text-decoration: none; color: var(--mz-navy);
  transition: background .12s;
}
.mz-sss-suggest-item:hover, .mz-sss-suggest-item.active { background: rgba(227,11,48,.07); color: var(--mz-navy); }
.mz-sss-suggest-cat {
  font-size: .7rem; text-transform: uppercase; letter-spacing: .05em; opacity: .65;
  margin-bottom: .15rem;
}
.mz-sss-suggest-text { font-weight: 500; }
.mz-sss-suggest-empty { padding: 1.5rem; text-align: center; color: #888; font-size: .9rem; }

.mz-sss-cat-chips { display: flex; flex-wrap: wrap; gap: .5rem; justify-content: center; margin: 1.5rem 0 0; position: relative; z-index: 5; }
.mz-sss-chip {
  padding: .4rem 1rem; border-radius: 50px;
  background: rgba(255,255,255,.1); color: #fff;
  border: 1px solid rgba(255,255,255,.25); font-size: .88rem;
  text-decoration: none; transition: all .15s;
}
.mz-sss-chip:hover { background: rgba(255,255,255,.2); color: #fff; }
.mz-sss-chip.active { background: var(--mz-red); border-color: var(--mz-red); color: #fff; }

.mz-sss-stat { display: inline-flex; align-items: center; gap: .35rem; color: rgba(255,255,255,.85); font-size: .85rem; }

.mz-sss-cat-section { margin-bottom: 2.5rem; }
.mz-sss-cat-title {
  display: flex; align-items: center; gap: .75rem;
  margin-bottom: 1rem; padding-bottom: .5rem;
  border-bottom: 2px solid var(--mz-red-soft);
  color: var(--mz-navy);
}
.mz-sss-cat-title i { color: var(--mz-red); }
.mz-sss-cat-count {
  margin-left: auto; font-size: .8rem; font-weight: 500;
  background: var(--mz-red-soft); color: var(--mz-red);
  padding: .15rem .55rem; border-radius: 50px;
}

.mz-sss-item {
  background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
  margin-bottom: .5rem; overflow: hidden;
  transition: all .2s;
}
.mz-sss-item:hover { border-color: var(--mz-red-soft); box-shadow: 0 8px 20px rgba(15,30,55,.05); }
.mz-sss-item.open { border-color: var(--mz-red); box-shadow: 0 12px 30px rgba(227,11,48,.08); }
.mz-sss-q-btn {
  width: 100%; padding: 1rem 1.25rem;
  background: transparent; border: 0; text-align: left;
  display: flex; align-items: flex-start; gap: .85rem;
  font-weight: 600; color: var(--mz-navy); cursor: pointer;
  transition: all .2s; line-height: 1.4;
}
.mz-sss-q-btn:hover { color: var(--mz-red); }
.mz-sss-q-icon {
  width: 28px; height: 28px; border-radius: 50%;
  background: var(--mz-red-soft); color: var(--mz-red);
  display: inline-flex; align-items: center; justify-content: center;
  font-size: .85rem; flex-shrink: 0; transition: all .2s;
}
.mz-sss-item.open .mz-sss-q-icon { background: var(--mz-red); color: #fff; }
.mz-sss-q-arrow { margin-left: auto; transition: transform .25s; flex-shrink: 0; color: var(--mz-red); }
.mz-sss-item.open .mz-sss-q-arrow { transform: rotate(180deg); }
.mz-sss-a { padding: 0 1.25rem 1.25rem 4rem; color: #475569; line-height: 1.7; display: none; }
.mz-sss-item.open .mz-sss-a { display: block; }

.mz-sss-noresults {
  text-align: center; padding: 3rem 1rem;
  background: #f8fafc; border-radius: 16px; border: 2px dashed #cbd5e1;
}
.mz-sss-noresults i { font-size: 3rem; color: #cbd5e1; }

mark { background: rgba(244, 211, 94, 0.5); color: inherit; padding: 0 2px; border-radius: 3px; }
</style>

<!-- ==== HERO ==== -->
<section class="mz-sss-hero">
  <div class="container text-center position-relative">
    <span class="mz-script mz-script-md mz-script-red d-block mb-1" style="color:#f4d35e !important">Yardım</span>
    <h1 class="display-4 fw-bold mb-2">Sıkça Sorulan Sorular</h1>
    <p class="lead text-white-50 mx-auto" style="max-width:600px">Sigorta ürünlerimiz ve süreçlerimizle ilgili merak ettiklerinizin yanıtları</p>

    <!-- Live arama -->
    <div class="mz-sss-search">
      <i class="bi bi-search mz-sss-search-icon"></i>
      <input type="text" id="sssSearch" placeholder="Sorunuzu yazmaya başlayın... (örn. kasko, DASK, hasar)" autocomplete="off">
      <button type="button" class="mz-sss-search-clear" id="sssSearchClear" title="Temizle"><i class="bi bi-x-lg"></i></button>
      <div class="mz-sss-suggestions" id="sssSuggestions"></div>
    </div>

    <!-- Kategori chip filtreleri -->
    <div class="mz-sss-cat-chips">
      <a href="#" class="mz-sss-chip <?= $kategoriFilter === '' ? 'active' : '' ?>" data-cat="">Tümü</a>
      <?php foreach ($kategoriler as $k): ?>
        <a href="#" class="mz-sss-chip <?= $kategoriFilter === $k['kategori'] ? 'active' : '' ?>" data-cat="<?= e($k['kategori']) ?>"><?= e($k['kategori']) ?></a>
      <?php endforeach; ?>
    </div>

    <div class="mt-3">
      <span class="mz-sss-stat"><i class="bi bi-collection"></i> <span id="sssTotal"><?= count($sorular) ?></span> soru</span>
      <span class="mz-sss-stat ms-3"><i class="bi bi-tags"></i> <?= count($kategoriler) ?> kategori</span>
    </div>
  </div>
</section>

<!-- ==== Sorular Listesi ==== -->
<section class="container py-5" id="sssSection">

  <?php if (!$sorular): ?>
    <div class="alert alert-info text-center">Henüz SSS eklenmemiş.</div>
  <?php else: ?>

    <div id="sssList">
      <?php foreach ($gruplu as $kat => $list): ?>
        <div class="mz-sss-cat-section" data-cat="<?= e($kat) ?>">
          <h3 class="mz-sss-cat-title h5 fw-bold">
            <i class="bi bi-bookmark-fill"></i>
            <?= e($kat) ?>
            <span class="mz-sss-cat-count"><?= count($list) ?> soru</span>
          </h3>

          <?php foreach ($list as $s): ?>
            <div class="mz-sss-item" data-q="<?= e(mb_strtolower($s['soru'] . ' ' . strip_tags($s['cevap']))) ?>" data-cat="<?= e($kat) ?>">
              <button type="button" class="mz-sss-q-btn" onclick="this.parentElement.classList.toggle('open')">
                <span class="mz-sss-q-icon"><i class="bi bi-question-lg"></i></span>
                <span class="mz-sss-q-text"><?= e($s['soru']) ?></span>
                <i class="bi bi-chevron-down mz-sss-q-arrow"></i>
              </button>
              <div class="mz-sss-a"><?= $s['cevap'] /* HTML safe — admin tarafindan yazilir */ ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="mz-sss-noresults" id="sssNoResults" style="display:none">
      <i class="bi bi-search"></i>
      <h4 class="mt-3 fw-bold">Aradığınız soru bulunamadı</h4>
      <p class="text-muted">Aramanızı genişletin veya bize doğrudan ulaşın.</p>
      <a href="<?= u('/iletisim') ?>" class="btn btn-warning fw-semibold mt-2"><i class="bi bi-chat-dots"></i> Soru Sor</a>
    </div>

  <?php endif; ?>
</section>

<!-- ==== CTA ==== -->
<section class="mz-band bg-light">
  <div class="container text-center">
    <span class="mz-script mz-script-md mz-script-red d-block">Cevap bulamadın mı?</span>
    <h2 class="fw-bold display-6 mt-2 mb-3">Yetkililerimize sor</h2>
    <p class="text-muted lead mb-4">7/24 destek hattımız ve uzman ekibimizle her sorunu çözüyoruz.</p>
    <div class="d-flex gap-2 justify-content-center flex-wrap">
      <button type="button" class="btn btn-warning fw-semibold" data-bs-toggle="modal" data-bs-target="#teklifWizard"><i class="bi bi-headset"></i> Teklif Talebi</button>
      <a href="<?= u('/iletisim') ?>" class="btn btn-outline-warning fw-semibold"><i class="bi bi-envelope"></i> Mesaj Gönder</a>
      <?php if ($v = setting('telefon')): ?>
        <a href="tel:<?= e(preg_replace('/\s+/', '', $v)) ?>" class="btn btn-outline-secondary fw-semibold"><i class="bi bi-telephone"></i> <?= e($v) ?></a>
      <?php endif; ?>
    </div>
  </div>
</section>

<script>
(function () {
  'use strict';

  const searchInput = document.getElementById('sssSearch');
  const searchClear = document.getElementById('sssSearchClear');
  const suggestions = document.getElementById('sssSuggestions');
  const list = document.getElementById('sssList');
  const noResults = document.getElementById('sssNoResults');
  const totalEl = document.getElementById('sssTotal');
  const chips = document.querySelectorAll('.mz-sss-chip');
  const items = document.querySelectorAll('.mz-sss-item');
  const sections = document.querySelectorAll('.mz-sss-cat-section');

  let activeCat = '<?= e($kategoriFilter) ?>';

  function escapeHtml(s) { return s.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }

  function highlight(text, q) {
    if (!q) return escapeHtml(text);
    const re = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
    return escapeHtml(text).replace(re, '<mark>$1</mark>');
  }

  function turkishLower(s) {
    return s.toLocaleLowerCase('tr-TR')
      .replace(/i̇/g, 'i'); // dotted-i normalize
  }

  function applyFilter() {
    const q = turkishLower(searchInput.value.trim());
    let visibleCount = 0;
    const sectionVisible = {};

    items.forEach(item => {
      const text = item.dataset.q;
      const cat = item.dataset.cat;
      const matchCat = !activeCat || cat === activeCat;
      const matchQ = !q || text.indexOf(q) !== -1;
      const visible = matchCat && matchQ;
      item.style.display = visible ? '' : 'none';
      if (visible) {
        visibleCount++;
        sectionVisible[cat] = true;
        // q text'ini bul ve highlight et
        const txtEl = item.querySelector('.mz-sss-q-text');
        if (txtEl) {
          const original = txtEl.textContent;
          if (q) txtEl.innerHTML = highlight(original, searchInput.value.trim());
        }
      }
    });

    sections.forEach(sec => {
      sec.style.display = sectionVisible[sec.dataset.cat] ? '' : 'none';
    });

    totalEl.textContent = visibleCount;
    noResults.style.display = visibleCount === 0 ? '' : 'none';
    list.style.display = visibleCount === 0 ? 'none' : '';
  }

  function showSuggestions() {
    const q = turkishLower(searchInput.value.trim());
    if (q.length < 2) { suggestions.style.display = 'none'; return; }

    // Suggestion: ilk 6 eslesen soru (kategori + soru)
    const matches = [];
    items.forEach(item => {
      if (matches.length >= 6) return;
      const text = item.dataset.q;
      if (text.indexOf(q) !== -1) {
        const txt = item.querySelector('.mz-sss-q-text').textContent;
        matches.push({ id: item, cat: item.dataset.cat, text: txt });
      }
    });

    if (matches.length === 0) {
      suggestions.innerHTML = '<div class="mz-sss-suggest-empty"><i class="bi bi-info-circle"></i> Eşleşen soru bulunamadı</div>';
    } else {
      suggestions.innerHTML = matches.map(m =>
        `<a href="#" class="mz-sss-suggest-item">
          <i class="bi bi-question-circle text-warning fs-5"></i>
          <div class="flex-grow-1">
            <div class="mz-sss-suggest-cat">${escapeHtml(m.cat)}</div>
            <div class="mz-sss-suggest-text">${highlight(m.text, searchInput.value.trim())}</div>
          </div>
        </a>`
      ).join('');

      // Click handler — sora git ve aç
      suggestions.querySelectorAll('.mz-sss-suggest-item').forEach((el, idx) => {
        el.addEventListener('click', (e) => {
          e.preventDefault();
          const target = matches[idx].id;
          // Filter'ı temizle, kategoriyi sıfırla
          searchInput.value = '';
          activeCat = '';
          chips.forEach(c => c.classList.toggle('active', !c.dataset.cat));
          applyFilter();
          // Açıp scroll
          target.classList.add('open');
          target.scrollIntoView({ behavior: 'smooth', block: 'center' });
          suggestions.style.display = 'none';
        });
      });
    }
    suggestions.style.display = 'block';
  }

  searchInput.addEventListener('input', () => {
    searchClear.style.display = searchInput.value ? 'flex' : 'none';
    showSuggestions();
    applyFilter();
  });

  searchClear.addEventListener('click', () => {
    searchInput.value = '';
    searchClear.style.display = 'none';
    suggestions.style.display = 'none';
    applyFilter();
    searchInput.focus();
  });

  searchInput.addEventListener('focus', () => {
    if (searchInput.value.trim().length >= 2) showSuggestions();
  });

  document.addEventListener('click', (e) => {
    if (!suggestions.contains(e.target) && e.target !== searchInput) {
      suggestions.style.display = 'none';
    }
  });

  searchInput.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      suggestions.style.display = 'none';
      searchInput.blur();
    }
  });

  // Kategori chip filtreleri
  chips.forEach(chip => {
    chip.addEventListener('click', (e) => {
      e.preventDefault();
      activeCat = chip.dataset.cat || '';
      chips.forEach(c => c.classList.toggle('active', c === chip));
      applyFilter();
    });
  });

  applyFilter();
})();
</script>

<?php require MIZAN_INC . '/footer.php';
