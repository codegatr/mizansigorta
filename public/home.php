<?php
if (!defined('MIZAN_BOOT')) { http_response_code(403); exit; }

$kategoriler = db_all('SELECT * FROM ' . t('urunler') . ' WHERE aktif=1 AND parent_id IS NULL ORDER BY sira ASC');
$tumUrunler  = db_all('SELECT id, slug, baslik FROM ' . t('urunler') . ' WHERE aktif=1 AND parent_id IS NOT NULL ORDER BY sira ASC LIMIT 200');
$blogList    = db_all('SELECT slug, baslik, ozet, kapak_gorseli, yayin_tarihi FROM ' . t('blog') . ' WHERE aktif=1 AND (yayin_tarihi IS NULL OR yayin_tarihi <= NOW()) ORDER BY yayin_tarihi DESC, id DESC LIMIT 3');
$yorumlar    = db_all('SELECT * FROM ' . t('referanslar') . " WHERE aktif=1 AND tip='yorum' ORDER BY sira ASC, id DESC LIMIT 6");
$sirketler   = db_all('SELECT * FROM ' . t('sigorta_sirketleri') . ' WHERE aktif=1 ORDER BY sira ASC LIMIT 24');

$pageTitle = setting('site_basligi', SITE_NAME);
$pageDesc  = setting('site_aciklamasi', 'Mizan Sigorta — sigorta aracılık hizmetleri. Güven ve özen ile her daim yanınızda.');
require MIZAN_INC . '/header.php';
?>

<!-- Slider Hero -->
<section class="mz-slider">
  <div class="mz-slider-track">

    <!-- Slide 1 - Hayatınız, Aracınız, İşiniz -->
    <div class="mz-slide active" data-slide="0">
      <div class="container">
        <div class="mz-slide-inner">
          <span class="mz-slide-script"><?= e(setting('site_slogan', 'Güven ve Özen İle')) ?></span>
          <h1>Hayatınıza, aracınıza ve işinize <span class="accent">tam koruma</span></h1>
          <p>12+ anlaşmalı sigorta şirketi arasından, ihtiyacınıza özel en avantajlı teminatları biz buluruz. Talebinizi iletin, müsait temsilcimiz en kısa sürede sizinle iletişime geçsin.</p>
          <div class="mz-slide-cta">
            <a href="<?= u('/teklif-al') ?>" class="btn btn-warning btn-lg fw-semibold"><i class="bi bi-headset"></i> Teklif Talebi Oluştur</a>
            <a href="<?= u('/iletisim') ?>" class="btn btn-outline-light btn-lg"><i class="bi bi-telephone"></i> Bize Ulaşın</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Slide 2 - Kasko / Trafik -->
    <div class="mz-slide" data-slide="1">
      <div class="container">
        <div class="mz-slide-inner">
          <span class="mz-slide-script">Aracınız İçin</span>
          <h1>Kasko ve Trafik Sigortası — <span class="accent">en uygun fiyat</span></h1>
          <p>Anadolu, Allianz, Türkiye Sigorta, AXA, HDI ve daha fazlası — tek bir talepte tüm şirketlerin teklifini karşılaştırın. Yenileme zamanı yaklaştığında size hatırlatma yapıyoruz.</p>
          <div class="mz-slide-cta">
            <a href="<?= u('/urun/oto-sigortalari') ?>" class="btn btn-warning btn-lg fw-semibold"><i class="bi bi-car-front-fill"></i> Oto Sigortalarını İncele</a>
            <a href="<?= u('/teklif-al?urun=oto-sigortalari') ?>" class="btn btn-outline-light btn-lg">Teklif Al</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Slide 3 - Sağlık / DASK -->
    <div class="mz-slide" data-slide="2">
      <div class="container">
        <div class="mz-slide-inner">
          <span class="mz-slide-script">Aileniz İçin</span>
          <h1>Sağlık ve DASK — <span class="accent">geleceğinizi güvenceye alın</span></h1>
          <p>Tamamlayıcı sağlık, özel sağlık ve DASK zorunlu deprem sigortası. Aile bireylerinize özel paketler, anlaşmalı özel hastanelerde fark ücretsiz tedavi ve deprem sonrası nakit destek.</p>
          <div class="mz-slide-cta">
            <a href="<?= u('/urun/saglik-sigortalari') ?>" class="btn btn-warning btn-lg fw-semibold"><i class="bi bi-heart-pulse-fill"></i> Sağlık Sigortaları</a>
            <a href="<?= u('/urun/yangin-policeleri') ?>" class="btn btn-outline-light btn-lg"><i class="bi bi-houses-fill"></i> DASK · Konut</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Slide 4 - 7/24 Hasar -->
    <div class="mz-slide" data-slide="3">
      <div class="container">
        <div class="mz-slide-inner">
          <span class="mz-slide-script">Hasar Anında</span>
          <h1>7/24 hasar desteğimiz — <span class="accent">yalnız değilsiniz</span></h1>
          <p>Hasar durumunda online ihbar formu, eksper takibi, belge süreci ve ödeme — hepsini biz yönetiyoruz. Aramamız yeterli, sürecin gerisini bize bırakın.</p>
          <div class="mz-slide-cta">
            <a href="<?= u('/hasar-ihbari') ?>" class="btn btn-warning btn-lg fw-semibold"><i class="bi bi-exclamation-triangle-fill"></i> Hasar İhbarı Yap</a>
            <?php if ($tel = setting('telefon')): ?>
              <a href="tel:<?= e(preg_replace('/\s+/', '', $tel)) ?>" class="btn btn-outline-light btn-lg"><i class="bi bi-telephone-fill"></i> <?= e($tel) ?></a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div class="mz-slider-arrows">
    <button class="mz-slider-arrow" data-slide-prev aria-label="Önceki"><i class="bi bi-chevron-left"></i></button>
    <button class="mz-slider-arrow" data-slide-next aria-label="Sonraki"><i class="bi bi-chevron-right"></i></button>
  </div>

  <div class="mz-slider-dots">
    <button class="mz-slider-dot active" data-slide-to="0" aria-label="Slide 1"></button>
    <button class="mz-slider-dot" data-slide-to="1" aria-label="Slide 2"></button>
    <button class="mz-slider-dot" data-slide-to="2" aria-label="Slide 3"></button>
    <button class="mz-slider-dot" data-slide-to="3" aria-label="Slide 4"></button>
  </div>
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
      <p class="text-light-emphasis mb-4">Sigortacılık tecrübenizi Mizan'ın gücüyle birleştirin. Güçlü altyapı, lisans desteği ve kurumsal kimlikle yan yana çalışalım.</p>
      <a href="<?= u('/temsilcimiz-olun') ?>" class="btn btn-warning"><i class="bi bi-stars"></i> Temsilci Başvurusu</a>
    </div>
  </div>
</section>

<?php require MIZAN_INC . '/footer.php';
