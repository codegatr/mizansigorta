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

<section class="mz-hero">
  <div class="container position-relative py-5">
    <div class="row align-items-center g-5 py-4">
      <div class="col-lg-6 text-white">
        <span class="mz-script mz-script-lg mz-script-red d-inline-block mb-3"><?= e(setting('site_slogan', 'Güven ve Özen İle')) ?></span>
        <h1 class="display-4 fw-bold lh-1 mb-3">Hayatınıza, aracınıza ve işinize <span class="text-warning">tam koruma</span></h1>
        <p class="lead mb-4">Anlaşmalı sigorta şirketleri arasından sizin için en uygun teminatları karşılaştırır, en avantajlı teklifi sunarız. Online teklif almak ücretsizdir ve sizi bağlamaz.</p>
        <div class="d-flex flex-wrap gap-2 mb-4">
          <a href="<?= u('/teklif-al') ?>" class="btn btn-warning btn-lg fw-semibold"><i class="bi bi-shield-check"></i> Hızlı Teklif Al</a>
          <a href="<?= u('/iletisim') ?>" class="btn btn-outline-light btn-lg"><i class="bi bi-headset"></i> Bize Ulaşın</a>
        </div>
        <div class="d-flex flex-wrap gap-2 mb-4">
          <span class="mz-trust-badge"><i class="bi bi-shield-check"></i> KVKK Uyumlu</span>
          <span class="mz-trust-badge"><i class="bi bi-patch-check"></i> Lisanslı Acente</span>
          <span class="mz-trust-badge"><i class="bi bi-clock-history"></i> 7/24 Hasar Desteği</span>
        </div>
        <ul class="mz-hero-stats list-unstyled d-flex flex-wrap gap-4 mt-4 mb-0">
          <li><strong class="text-warning fs-3 d-block">7/24</strong><span>Hasar Desteği</span></li>
          <li><strong class="text-warning fs-3 d-block">12+</strong><span>Sigorta Şirketi</span></li>
          <li><strong class="text-warning fs-3 d-block">4</strong><span>Şube · İST · KON · ANK · AKS</span></li>
        </ul>
      </div>
      <div class="col-lg-6">
        <div class="mz-hero-quote">
          <div class="mz-hero-quote-head">
            <span class="mz-hero-quote-icon"><i class="bi bi-lightning-charge-fill"></i></span>
            <div>
              <div class="mz-hero-quote-title">60 saniyede teklif alın</div>
              <div class="mz-hero-quote-sub">Bilgilerinizi girin, en uygun teklifi sunalım</div>
            </div>
          </div>
          <form action="<?= u('/teklif-al') ?>" method="get" class="row g-3">
            <div class="col-12">
              <label class="form-label small fw-semibold text-secondary mb-1">Sigorta türü</label>
              <select name="urun" class="form-select" required>
                <option value="">Sigorta türü seçin</option>
                <?php foreach ($kategoriler as $k): ?>
                  <optgroup label="<?= e($k['baslik']) ?>">
                    <?php $alt = db_all('SELECT slug, baslik FROM ' . t('urunler') . ' WHERE aktif=1 AND parent_id=? ORDER BY sira', [$k['id']]); ?>
                    <?php foreach ($alt as $a): ?>
                      <option value="<?= e($a['slug']) ?>"><?= e($a['baslik']) ?></option>
                    <?php endforeach; ?>
                  </optgroup>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold text-secondary mb-1">Cep telefonu</label>
              <input type="tel" name="tel" class="form-control" placeholder="0XXX XXX XX XX" pattern="[0-9 +]+" required>
            </div>
            <div class="col-12 d-grid mt-2">
              <button class="btn btn-warning fw-semibold">Teklif Al <i class="bi bi-arrow-right"></i></button>
            </div>
            <div class="col-12 small text-muted text-center mb-0 mt-1">
              <i class="bi bi-shield-check"></i> KVKK kapsamında bilgileriniz yalnızca teklif sürecinde kullanılır.
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

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

<!-- Neden Mizan -->
<section class="mz-band bg-light">
  <div class="container">
    <div class="row g-4 align-items-center">
      <div class="col-lg-5">
        <span class="mz-script mz-script-md mz-script-red d-inline-block mb-2">Neden Mizan?</span>
        <h2 class="fw-bold mb-3">Önem verdiklerinizi <span class="text-warning">güvence altına</span> alıyoruz.</h2>
        <p class="text-muted">Mizan Sigorta Aracılık Hizmetleri olarak, sizi ve değer verdiğiniz her şeyi korumak için yıllardır çalışıyoruz. İşyerinizden konutunuza, sağlığınızdan aracınıza kadar her ihtiyacınıza özel çözümler sunarız.</p>
        <a href="<?= u('/teklif-al') ?>" class="btn btn-warning fw-semibold mt-2"><i class="bi bi-shield-check"></i> Hemen Teklif Al</a>
      </div>
      <div class="col-lg-7">
        <div class="row g-3">
          <div class="col-md-6"><div class="mz-feat"><i class="bi bi-shield-check"></i><div><h6>Lisanslı Acentelik</h6><p>SBM ve Hazine Müsteşarlığı kayıtlı, levhalı sigorta acentesi.</p></div></div></div>
          <div class="col-md-6"><div class="mz-feat"><i class="bi bi-graph-up-arrow"></i><div><h6>En Uygun Teklif</h6><p>12+ anlaşmalı şirket arasından otomatik karşılaştırma.</p></div></div></div>
          <div class="col-md-6"><div class="mz-feat"><i class="bi bi-headset"></i><div><h6>7/24 Hasar Desteği</h6><p>Hasar bildiriminizi gece-gündüz takip ediyoruz.</p></div></div></div>
          <div class="col-md-6"><div class="mz-feat"><i class="bi bi-people"></i><div><h6>Uzman Kadro</h6><p>Yıllarca tecrübeyle hizmet veren uzman ekip.</p></div></div></div>
          <div class="col-md-6"><div class="mz-feat"><i class="bi bi-geo-alt-fill"></i><div><h6>4 Şehirde Şube</h6><p>İstanbul, Konya, Ankara, Aksaray fiziki ofislerimiz.</p></div></div></div>
          <div class="col-md-6"><div class="mz-feat"><i class="bi bi-shield-lock"></i><div><h6>KVKK Uyumlu</h6><p>Verileriniz yalnızca teklif sürecinde kullanılır.</p></div></div></div>
        </div>
      </div>
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
        <div class="col-6 col-md-3 col-lg-2">
          <?php if ($s['logo']): ?>
            <div class="mz-partner-logo" title="<?= e($s['ad']) ?>"><img src="<?= u('uploads/sirket/' . rawurlencode($s['logo'])) ?>" alt="<?= e($s['ad']) ?>"></div>
          <?php else: ?>
            <div class="mz-partner-text"><?= e($s['ad']) ?></div>
          <?php endif; ?>
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
