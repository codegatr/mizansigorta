<?php
if (!defined('MIZAN_BOOT')) { http_response_code(403); exit; }

$urunlerOne = db_all('SELECT * FROM ' . t('urunler') . " WHERE aktif=1 AND one_cikan=1 ORDER BY sira ASC LIMIT 6");
$urunlerHepsi = db_all('SELECT * FROM ' . t('urunler') . " WHERE aktif=1 ORDER BY sira ASC LIMIT 12");
$blogList = db_all('SELECT slug, baslik, ozet, kapak_gorseli, yayin_tarihi FROM ' . t('blog') . ' WHERE aktif=1 AND (yayin_tarihi IS NULL OR yayin_tarihi <= NOW()) ORDER BY yayin_tarihi DESC, id DESC LIMIT 3');
$yorumlar = db_all('SELECT * FROM ' . t('referanslar') . " WHERE aktif=1 AND tip='yorum' ORDER BY sira ASC, id DESC LIMIT 6");
$sirketler = db_all('SELECT * FROM ' . t('sigorta_sirketleri') . ' WHERE aktif=1 ORDER BY sira ASC LIMIT 24');

$pageTitle = setting('site_basligi', SITE_NAME);
$pageDesc  = setting('site_aciklamasi');
require MIZAN_INC . '/header.php';
?>

<section class="mz-hero">
  <div class="mz-hero-overlay"></div>
  <div class="container position-relative py-5">
    <div class="row align-items-center g-5 py-4">
      <div class="col-lg-7 text-white">
        <span class="badge bg-warning text-dark fw-semibold mb-3"><?= e(setting('site_slogan', 'Güven ve Özen İle')) ?></span>
        <h1 class="display-4 fw-bold lh-1 mb-3">Hayatınıza, aracınıza ve işinize <span class="text-warning">tam koruma</span></h1>
        <p class="lead mb-4">Anlaşmalı sigorta şirketleri arasından sizin için en uygun teminatları karşılaştırır, en avantajlı teklifi sunarız. Online teklif almak ücretsiz ve sizi bağlamaz.</p>
        <div class="d-flex flex-wrap gap-2">
          <a href="<?= u('/teklif-al') ?>" class="btn btn-warning btn-lg fw-semibold"><i class="bi bi-shield-check"></i> Hızlı Teklif Al</a>
          <a href="<?= u('/iletisim') ?>" class="btn btn-outline-light btn-lg"><i class="bi bi-headset"></i> Bize Ulaşın</a>
        </div>
        <ul class="mz-hero-stats list-unstyled d-flex flex-wrap gap-4 mt-5">
          <li><strong class="text-warning fs-3 d-block">7/24</strong><span>Hasar Desteği</span></li>
          <li><strong class="text-warning fs-3 d-block">12+</strong><span>Sigorta Şirketi</span></li>
          <li><strong class="text-warning fs-3 d-block">%100</strong><span>Müşteri Odaklı</span></li>
        </ul>
      </div>
      <div class="col-lg-5">
        <div class="mz-quick-card shadow-lg">
          <h5 class="fw-bold mb-3"><i class="bi bi-lightning-charge text-warning"></i> 60 saniyede teklif</h5>
          <form action="<?= u('/teklif-al') ?>" method="get" class="row g-2">
            <div class="col-12">
              <select name="urun" class="form-select form-select-lg" required>
                <option value="">Sigorta türü seçin</option>
                <?php foreach ($urunlerHepsi as $u): ?>
                  <option value="<?= e($u['slug']) ?>"><?= e($u['baslik']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <input type="tel" name="tel" class="form-control form-control-lg" placeholder="Cep telefonu" pattern="[0-9 +]+" required>
            </div>
            <div class="col-12 d-grid">
              <button class="btn btn-warning btn-lg fw-semibold">Teklif Al <i class="bi bi-arrow-right"></i></button>
            </div>
            <small class="text-muted text-center mt-2">KVKK kapsamında bilgileriniz yalnızca teklif sürecinde kullanılır.</small>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="fw-bold">Sigorta Ürünlerimiz</h2>
      <p class="text-muted">Aşağıdaki kategorilerden teklif alabilir, ihtiyacınıza özel paketleri inceleyebilirsiniz.</p>
    </div>
    <div class="row g-4">
      <?php foreach ($urunlerHepsi as $u): ?>
        <div class="col-6 col-md-4 col-lg-3">
          <a href="<?= u('/urun/' . $u['slug']) ?>" class="mz-prod-card text-decoration-none">
            <div class="mz-prod-icon"><i class="bi bi-<?= e($u['icon'] ?: 'shield-check') ?>"></i></div>
            <h6 class="mb-1"><?= e($u['baslik']) ?></h6>
            <p class="small text-muted mb-0"><?= e($u['kisa_aciklama']) ?></p>
            <span class="mz-prod-go">Teklif al <i class="bi bi-arrow-right"></i></span>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="mz-band py-5">
  <div class="container">
    <div class="row g-4 align-items-stretch">
      <div class="col-md-3 d-flex"><div class="mz-feat"><i class="bi bi-currency-exchange"></i><h6>En Uygun Fiyat</h6><p>12+ şirketten karşılaştırma.</p></div></div>
      <div class="col-md-3 d-flex"><div class="mz-feat"><i class="bi bi-headset"></i><h6>7/24 Destek</h6><p>Hasar anında yanınızda.</p></div></div>
      <div class="col-md-3 d-flex"><div class="mz-feat"><i class="bi bi-bell-fill"></i><h6>Yenileme Takibi</h6><p>Poliçeniz boşa düşmesin.</p></div></div>
      <div class="col-md-3 d-flex"><div class="mz-feat"><i class="bi bi-shield-lock-fill"></i><h6>KVKK Güvencesi</h6><p>Verileriniz emin ellerde.</p></div></div>
    </div>
  </div>
</section>

<?php if ($yorumlar): ?>
<section class="py-5">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="fw-bold">Müşterilerimiz Ne Diyor?</h2>
    </div>
    <div class="row g-4">
      <?php foreach ($yorumlar as $y): ?>
        <div class="col-md-6 col-lg-4">
          <div class="mz-testimonial">
            <div class="mz-stars">
              <?php for ($i = 0; $i < (int) ($y['puan'] ?: 5); $i++): ?><i class="bi bi-star-fill"></i><?php endfor; ?>
            </div>
            <p class="mb-3">“<?= e($y['mesaj']) ?>”</p>
            <strong class="d-block"><?= e($y['ad']) ?></strong>
            <small class="text-muted"><?= e($y['unvan']) ?></small>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($sirketler): ?>
<section class="py-5 bg-light">
  <div class="container">
    <div class="text-center mb-4">
      <h3 class="fw-bold">Anlaşmalı Sigorta Şirketleri</h3>
    </div>
    <div class="row row-cols-3 row-cols-md-6 g-3">
      <?php foreach ($sirketler as $s): ?>
        <div class="col text-center">
          <?php if ($s['logo']): ?>
            <img src="<?= u('uploads/' . $s['logo']) ?>" alt="<?= e($s['ad']) ?>" class="img-fluid mz-partner-logo">
          <?php else: ?>
            <div class="mz-partner-text"><?= e($s['ad']) ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($blogList): ?>
<section class="py-5">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-4">
      <h3 class="fw-bold mb-0">Blog & Bilgi</h3>
      <a href="<?= u('/blog') ?>" class="btn btn-link">Tümünü Gör <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="row g-4">
      <?php foreach ($blogList as $b): ?>
        <div class="col-md-4">
          <a href="<?= u('/blog/' . $b['slug']) ?>" class="mz-blog-card text-decoration-none">
            <div class="mz-blog-img" style="background-image:url('<?= e($b['kapak_gorseli'] ? u('uploads/' . $b['kapak_gorseli']) : '') ?>')"></div>
            <div class="mz-blog-body">
              <small class="text-muted"><?= tr_date($b['yayin_tarihi']) ?></small>
              <h6 class="fw-bold mt-1 mb-2"><?= e($b['baslik']) ?></h6>
              <p class="small text-muted mb-0"><?= e(mb_strimwidth((string) $b['ozet'], 0, 120, '…')) ?></p>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="mz-cta py-5 text-center text-white">
  <div class="container">
    <h2 class="fw-bold mb-3">Hangi sigorta en uygun? <span class="text-warning">5 dakikada öğrenin.</span></h2>
    <p class="lead mb-4">Tek formla 12+ sigorta şirketinden teklifleri karşılaştırıyoruz.</p>
    <a class="btn btn-warning btn-lg fw-semibold px-4" href="<?= u('/teklif-al') ?>"><i class="bi bi-shield-check"></i> Hemen Teklif Al</a>
  </div>
</section>

<?php require MIZAN_INC . '/footer.php';
