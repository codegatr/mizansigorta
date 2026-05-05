<?php
if (!defined('MIZAN_BOOT')) { http_response_code(403); exit; }

$kategoriler = db_all('SELECT * FROM ' . t('urunler') . ' WHERE aktif=1 AND parent_id IS NULL ORDER BY sira ASC');
$tumUrunler  = db_all('SELECT id, slug, baslik FROM ' . t('urunler') . ' WHERE aktif=1 AND parent_id IS NOT NULL ORDER BY sira ASC LIMIT 200');
$blogList    = db_all('SELECT slug, baslik, ozet, kapak_gorseli, yayin_tarihi FROM ' . t('blog') . ' WHERE aktif=1 AND (yayin_tarihi IS NULL OR yayin_tarihi <= NOW()) ORDER BY yayin_tarihi DESC, id DESC LIMIT 3');
$yorumlar    = db_all('SELECT * FROM ' . t('referanslar') . " WHERE aktif=1 AND tip='yorum' ORDER BY sira ASC, id DESC LIMIT 6");
$sirketler   = db_all('SELECT * FROM ' . t('sigorta_sirketleri') . ' WHERE aktif=1 ORDER BY sira ASC LIMIT 24');
$slaytlar    = db_all('SELECT * FROM ' . t('slaytlar') . ' WHERE aktif=1 ORDER BY sira ASC, id ASC');

$pageTitle = 'Mizan Sigorta — Konya, İstanbul, Ankara, Aksaray | Kasko, Trafik, Konut, DASK, Sağlık Sigortası';
$pageDesc  = setting('site_aciklamasi') ?:
    'Mizan Sigorta — T.C. Hazine Bakanlığı SBM lisanslı sigorta aracılık şirketi. Kasko, trafik, konut, DASK, özel sağlık, tamamlayıcı sağlık, ferdi kaza, işyeri ve hayat sigortalarında 12+ anlaşmalı şirket arasında en uygun primi karşılaştırın. Online teklif, 7/24 hasar desteği, KVKK uyumlu güvenli süreç. Konya merkez · İstanbul · Ankara · Aksaray şubeleri ile sigortanızda Güven ve Özen.';
$pageKeys  = setting('site_anahtar_kelimeler') ?:
    'mizan sigorta, mizan sigorta konya, mizan sigorta istanbul, mizan sigorta ankara, mizan sigorta aksaray, sigorta acentesi, sigorta aracılık, kasko, trafik sigortası, konut sigortası, dask, dask sigortası, özel sağlık sigortası, tamamlayıcı sağlık sigortası, tss, ferdi kaza sigortası, işyeri sigortası, hayat sigortası, seyahat sağlık sigortası, online sigorta teklifi, sigorta hesaplama, en uygun kasko, en ucuz trafik sigortası, deprem sigortası, ev sigortası, araç sigortası, hasar ihbarı, sigorta şirketi, türkiye sigorta, hdı sigorta, allianz, axa sigorta, anadolu sigorta, ak sigorta, mapfre sigorta';

// Anasayfaya breadcrumb (SEO icin)
$pageBreadcrumbs = [
    ['name' => 'Ana Sayfa', 'url' => SITE_BASE_URL . '/']
];
require MIZAN_INC . '/header.php';
?>


<!-- Slider Hero (yonetim panelden duzenlenebilir) -->
<section class="mz-slider">
  <div class="mz-slider-track">
    <?php
    // Eger DB'de hic aktif slayt yoksa, varsayilan 4 slayt seti kullan (kurulum oncesi guvenlik)
    $slaytKaynak = $slaytlar;
    if (!$slaytKaynak) {
        $slaytKaynak = [
            ['baslik' => 'Hayatınıza, aracınıza ve işinize tam koruma', 'accent_kelime' => 'tam koruma', 'ust_metin' => setting('site_slogan', 'Güven ve Özen İle'),
             'aciklama' => '12+ anlaşmalı sigorta şirketi arasından, ihtiyacınıza özel en avantajlı teminatları biz buluruz. Talebinizi iletin, müsait temsilcimiz en kısa sürede sizinle iletişime geçsin.',
             'buton1_metin' => 'Teklif Talebi Oluştur', 'buton1_link' => '/teklif-al', 'buton1_ikon' => 'bi-headset',
             'buton2_metin' => 'Bize Ulaşın', 'buton2_link' => '/iletisim', 'buton2_ikon' => 'bi-telephone',
             'gorsel_tip' => 'svg_kalkan', 'gorsel_url' => null],
        ];
    }
    $slaytTotal = count($slaytKaynak);
    ?>

    <?php foreach ($slaytKaynak as $idx => $sl):
        $isActive   = ($idx === 0);
        $accent     = trim((string)($sl['accent_kelime'] ?? ''));
        $baslik     = (string)($sl['baslik'] ?? '');
        // accent kelimeyi h1 icinde sari renkli span ile vurgula (ilk eslesme)
        $baslikRender = e($baslik);
        if ($accent !== '' && stripos($baslik, $accent) !== false) {
            // Case-preserving replace (ilk eslesme)
            $pos = stripos($baslik, $accent);
            $baslikRender = e(substr($baslik, 0, $pos))
                          . '<span class="accent">' . e(substr($baslik, $pos, strlen($accent))) . '</span>'
                          . e(substr($baslik, $pos + strlen($accent)));
        }

        $tip      = (string)($sl['gorsel_tip'] ?? 'yok');
        $customUrl = trim((string)($sl['gorsel_url'] ?? ''));

        // Buton2 link telefon ise setting'den oku
        $b2link = (string)($sl['buton2_link'] ?? '');
        if ($b2link === 'tel:' || $b2link === 'tel') {
            $tel = (string) setting('telefon', '');
            $b2link = $tel ? 'tel:' . preg_replace('/\s+/', '', $tel) : '';
            $b2text = $tel ?: (string)($sl['buton2_metin'] ?? '');
        } else {
            $b2text = (string)($sl['buton2_metin'] ?? '');
        }
    ?>
      <div class="mz-slide<?= $isActive ? ' active' : '' ?><?= ($tip === 'custom_url' && $customUrl !== '') ? ' has-full-bg' : '' ?>" data-slide="<?= $idx ?>">
        <div class="mz-slide-decor d1"></div>
        <div class="mz-slide-decor d2"></div>
        <?php if ($tip === 'custom_url' && $customUrl !== ''): ?>
          <div class="mz-slide-bg mz-slide-bg-full" aria-hidden="true">
            <img src="<?= e($customUrl) ?>" alt="" loading="lazy">
          </div>
        <?php elseif ($tip !== 'yok'): ?>
          <div class="mz-slide-bg" aria-hidden="true">
            <?= mz_svg_illustration($tip) ?>
          </div>
        <?php endif; ?>
        <div class="container">
          <div class="mz-slide-inner">
            <?php if (!empty($sl['ust_metin'])): ?>
              <span class="mz-slide-script"><?= e($sl['ust_metin']) ?></span>
            <?php endif; ?>
            <h1><?= $baslikRender /* HTML icerir, escape edilmis */ ?></h1>
            <?php if (!empty($sl['aciklama'])): ?>
              <p><?= e($sl['aciklama']) ?></p>
            <?php endif; ?>
            <div class="mz-slide-cta">
              <?php if (!empty($sl['buton1_link']) && !empty($sl['buton1_metin'])): ?>
                <a href="<?= e(u($sl['buton1_link'])) ?>" class="btn btn-warning btn-lg fw-semibold">
                  <?php if (!empty($sl['buton1_ikon'])): ?><i class="bi <?= e($sl['buton1_ikon']) ?>"></i> <?php endif; ?>
                  <?= e($sl['buton1_metin']) ?>
                </a>
              <?php endif; ?>
              <?php if ($b2link !== '' && $b2text !== ''): ?>
                <a href="<?= e(strpos($b2link, 'tel:') === 0 ? $b2link : u($b2link)) ?>" class="btn btn-outline-light btn-lg">
                  <?php if (!empty($sl['buton2_ikon'])): ?><i class="bi <?= e($sl['buton2_ikon']) ?>"></i> <?php endif; ?>
                  <?= e($b2text) ?>
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

  </div>

  <?php if ($slaytTotal > 1): ?>
    <div class="mz-slider-arrows">
      <button class="mz-slider-arrow" data-slide-prev aria-label="Önceki"><i class="bi bi-chevron-left"></i></button>
      <button class="mz-slider-arrow" data-slide-next aria-label="Sonraki"><i class="bi bi-chevron-right"></i></button>
    </div>

    <div class="mz-slider-dots">
      <?php for ($i = 0; $i < $slaytTotal; $i++): ?>
        <button class="mz-slider-dot<?= $i === 0 ? ' active' : '' ?>" data-slide-to="<?= $i ?>" aria-label="Slide <?= $i + 1 ?>"></button>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</section>


<script>
(function(){
  'use strict';
  const track = document.querySelector('.mz-slider-track');
  if (!track) return;
  const slides = track.querySelectorAll('.mz-slide');
  const dots = document.querySelectorAll('.mz-slider-dot');
  let current = 0;
  let timer = null;

  function show(idx) {
    slides.forEach((s, i) => s.classList.toggle('active', i === idx));
    dots.forEach((d, i) => d.classList.toggle('active', i === idx));
    current = idx;
  }
  function next() { show((current + 1) % slides.length); }
  function prev() { show((current - 1 + slides.length) % slides.length); }
  function start() { stop(); timer = setInterval(next, 6000); }
  function stop()  { if (timer) { clearInterval(timer); timer = null; } }

  document.querySelector('[data-slide-next]')?.addEventListener('click', () => { next(); start(); });
  document.querySelector('[data-slide-prev]')?.addEventListener('click', () => { prev(); start(); });
  dots.forEach(d => d.addEventListener('click', e => { show(parseInt(e.target.dataset.slideTo, 10)); start(); }));

  // Hover'da durdur
  document.querySelector('.mz-slider')?.addEventListener('mouseenter', stop);
  document.querySelector('.mz-slider')?.addEventListener('mouseleave', start);

  // Klavye gezinme
  document.addEventListener('keydown', e => {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
    if (e.key === 'ArrowRight') { next(); start(); }
    if (e.key === 'ArrowLeft') { prev(); start(); }
  });

  start();
})();
</script>

<!-- 9 Kategori Kartlari -->
<section class="mz-band">
  <div class="container">
    <div class="text-center mb-5">
      <span class="mz-trust-badge mb-2"><i class="bi bi-collection"></i> 9 ana kategori, 35+ ürün</span>
      <h2 class="fw-bold display-6">Sigorta Ürünleri</h2>
      <p class="lead text-muted">Bireysel ve kurumsal her ihtiyaca özel çözüm</p>
    </div>
    <div class="row g-3">
      <?php foreach ($kategoriler as $k): ?>
        <div class="col-md-6 col-lg-4">
          <a href="<?= u('/urun/' . $k['slug']) ?>" class="mz-prod-card">
            <div class="mz-prod-icon"><i class="bi <?= e($k['icon'] ?: 'bi-shield') ?>"></i></div>
            <h5><?= e($k['baslik']) ?></h5>
            <p><?= e($k['kisa_aciklama']) ?></p>
            <span class="badge bg-warning text-white mt-2"><?= (int)db_value('SELECT COUNT(*) FROM ' . t('urunler') . ' WHERE parent_id=? AND aktif=1', [$k['id']]) ?> ürün</span>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Anlasmali Sirketler -->
<?php if ($sirketler): ?>
<section class="mz-band">
  <div class="container">
    <div class="text-center mb-4">
      <span class="mz-trust-badge mb-2"><i class="bi bi-buildings"></i> Anlaşmalı Şirketler</span>
      <h3 class="fw-bold">12+ sigorta şirketi tek bir yerde</h3>
      <p class="text-muted">Türkiye'nin önde gelen sigorta şirketleriyle çalışıyoruz</p>
    </div>
    <div class="row g-3">
      <?php foreach ($sirketler as $s): ?>
        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
          <?php $logoUrl = sirket_logo_url($s['logo']); ?>
          <div class="mz-partner-circle" title="<?= e($s['ad']) ?>">
            <?php if ($logoUrl): ?>
              <img src="<?= e($logoUrl) ?>" alt="<?= e($s['ad']) ?>" loading="lazy">
            <?php else: ?>
              <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,var(--mz-navy),var(--mz-dark-2));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.5rem">
                <?= e(mb_strtoupper(mb_substr($s['ad'], 0, 1))) ?>
              </div>
            <?php endif; ?>
            <span class="mz-partner-name"><?= e($s['ad']) ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Musteri Yorumlari -->
<?php if ($yorumlar): ?>
<section class="mz-band bg-light">
  <div class="container">
    <div class="text-center mb-5">
      <span class="mz-trust-badge mb-2"><i class="bi bi-chat-quote"></i> Müşterilerimiz</span>
      <h3 class="fw-bold">Bizi tercih edenler</h3>
    </div>
    <div class="row g-3">
      <?php foreach ($yorumlar as $y): ?>
        <div class="col-md-6 col-lg-4">
          <div class="mz-testimonial">
            <div class="stars"><?php for ($i = 1; $i <= 5; $i++): ?><i class="bi bi-star<?= $i <= (int)$y['puan'] ? '-fill' : '' ?>"></i><?php endfor; ?></div>
            <p>"<?= e($y['mesaj']) ?>"</p>
            <div><span class="name"><?= e($y['ad']) ?></span><?php if ($y['unvan']): ?><div class="role"><?= e($y['unvan']) ?></div><?php endif; ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Blog -->
<?php if ($blogList): ?>
<section class="mz-band">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-4">
      <div>
        <span class="mz-trust-badge mb-2"><i class="bi bi-newspaper"></i> Bilgi Bankası</span>
        <h3 class="fw-bold mb-0">Blog & Sigorta Rehberi</h3>
      </div>
      <a href="<?= u('/blog') ?>" class="btn btn-outline-secondary btn-sm">Tüm Yazılar <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="row g-3">
      <?php foreach ($blogList as $b): ?>
        <div class="col-md-4">
          <article class="mz-blog-card">
            <?php if ($b['kapak_gorseli']): ?>
              <img src="<?= u('uploads/blog/' . rawurlencode($b['kapak_gorseli'])) ?>" alt="" class="mz-blog-img">
            <?php else: ?>
              <div class="mz-blog-img-ph"><i class="bi bi-newspaper"></i></div>
            <?php endif; ?>
            <div class="body">
              <small class="text-muted"><?= tr_date($b['yayin_tarihi']) ?></small>
              <h6 class="mt-1 mb-2"><a href="<?= u('/blog/' . $b['slug']) ?>" class="title"><?= e($b['baslik']) ?></a></h6>
              <p class="small text-muted flex-grow-1"><?= e(mb_substr($b['ozet'] ?: '', 0, 100)) ?>…</p>
              <a href="<?= u('/blog/' . $b['slug']) ?>" class="btn btn-sm btn-outline-warning mt-auto">Devamını Oku <i class="bi bi-arrow-right"></i></a>
            </div>
          </article>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Temsilci CTA -->
<section class="mz-cta">
  <div class="container">
    <div class="mz-cta-box">
      <span class="mz-script mz-script-lg mz-script-red d-block mb-2">Bize Katılın</span>
      <h3>Mizan Sigorta Temsilcisi Olun</h3>
      <p class="mb-4">Sigortacılık tecrübenizi Mizan'ın gücüyle birleştirin. Güçlü altyapı, lisans desteği ve kurumsal kimlikle yan yana çalışalım.</p>
      <a href="<?= u('/temsilcimiz-olun') ?>" class="btn btn-warning"><i class="bi bi-stars"></i> Temsilci Başvurusu</a>
    </div>
  </div>
</section>

<?php require MIZAN_INC . '/footer.php';
