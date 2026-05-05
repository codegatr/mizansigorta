<?php if (!defined('MIZAN_BOOT')) { http_response_code(403); exit; }
$footerUrunler = $footerUrunler ?? db_all('SELECT slug, baslik FROM ' . t('urunler') . " WHERE aktif=1 AND parent_id IS NULL ORDER BY sira ASC LIMIT 9");
$footerCms     = $footerCms     ?? db_all('SELECT slug, baslik FROM ' . t('sayfalar') . ' WHERE aktif=1 ORDER BY menu_sirasi ASC LIMIT 8');
?>
</main>

<footer class="mz-footer">
  <div class="container py-5">
    <div class="row g-4">
      <div class="col-md-4">
        <img src="<?= asset('assets/img/logo-light.png') ?>" alt="Mizan Sigorta" class="mz-footer-logo">
        <p class="fst-italic small mb-3" style="color:#fff;opacity:.85;font-family:'Brush Script MT',cursive;font-size:1.15rem">Güven ve Özen İle</p>
        <p class="small text-light-emphasis">
          Müşteri memnuniyetini önceliklendiren çözüm ortağınız.
          Anlaşmalı sigorta şirketleri ile en uygun teminat ve fiyatları sunuyoruz.
        </p>
        <div class="d-flex gap-2 mt-3">
          <?php foreach (['facebook','instagram','linkedin','twitter','youtube'] as $sn):
              $url = setting($sn); if ($url): ?>
            <a class="mz-social" target="_blank" rel="noopener" href="<?= e($url) ?>"><i class="bi bi-<?= e($sn === 'twitter' ? 'twitter-x' : $sn) ?>"></i></a>
          <?php endif; endforeach; ?>
        </div>
      </div>
      <div class="col-md-3">
        <h6 class="text-uppercase mb-3">Sigorta Ürünleri</h6>
        <ul class="list-unstyled small">
          <?php foreach ($footerUrunler as $u): ?>
            <li><a href="<?= u('/urun/' . $u['slug']) ?>"><?= e($u['baslik']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="col-md-2">
        <h6 class="text-uppercase mb-3">Kurumsal</h6>
        <ul class="list-unstyled small">
          <?php foreach ($footerCms as $c): ?>
            <li><a href="<?= u('/sayfa/' . $c['slug']) ?>"><?= e($c['baslik']) ?></a></li>
          <?php endforeach; ?>
          <li><a href="<?= u('/blog') ?>">Blog</a></li>
          <li><a href="<?= u('/sss') ?>">S.S.S.</a></li>
          <li><a href="<?= u('/iletisim') ?>">İletişim</a></li>
          <li class="mt-2"><a href="<?= u('/temsilcimiz-olun') ?>" style="color:var(--mz-red);font-weight:600"><i class="bi bi-stars"></i> Temsilcimiz Olun</a></li>
        </ul>
      </div>
      <div class="col-md-3">
        <h6 class="text-uppercase mb-3">İletişim</h6>
        <ul class="list-unstyled small">
          <?php if ($v = setting('telefon')): ?><li><i class="bi bi-telephone-fill text-warning"></i> <a href="tel:<?= e(preg_replace('/\s+/', '', $v)) ?>"><?= e($v) ?></a></li><?php endif; ?>
          <?php if ($v = setting('email')): ?><li><i class="bi bi-envelope-fill text-warning"></i> <a href="mailto:<?= e($v) ?>"><?= e($v) ?></a></li><?php endif; ?>
          <?php if ($v = setting('whatsapp')): ?><li><i class="bi bi-whatsapp text-warning"></i> <a target="_blank" rel="noopener" href="https://wa.me/<?= e($v) ?>">WhatsApp</a></li><?php endif; ?>
          <?php if ($v = setting('adres')): ?><li class="mt-2"><i class="bi bi-geo-alt-fill text-warning"></i> <?= nl2br(e($v)) ?></li><?php endif; ?>
          <?php if ($sehirler = setting('ofis_sehirler')): ?>
            <li class="mt-2"><i class="bi bi-pin-map-fill text-warning"></i>
              <?php $list = array_filter(array_map('trim', explode(',', (string)$sehirler))); ?>
              <?= e(implode(' • ', $list)) ?>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
  <div class="mz-legal-bar text-center small py-2" style="background:#0a1428;color:#94a3b8;border-top:1px solid rgba(255,255,255,.05)">
    <div class="container d-flex flex-wrap justify-content-center gap-3 gap-md-4">
      <a href="<?= u('/sayfa/kvkk') ?>" style="color:#cbd5e1;text-decoration:none">KVKK Aydınlatma</a>
      <a href="<?= u('/sayfa/gizlilik-politikasi') ?>" style="color:#cbd5e1;text-decoration:none">Gizlilik</a>
      <a href="<?= u('/sayfa/cerez-politikasi') ?>" style="color:#cbd5e1;text-decoration:none">Çerezler</a>
      <a href="<?= u('/sayfa/kullanim-sartlari') ?>" style="color:#cbd5e1;text-decoration:none">Kullanım Şartları</a>
      <a href="<?= u('/sayfa/uye-aydinlatma') ?>" style="color:#cbd5e1;text-decoration:none">Üye Aydınlatma</a>
      <a href="<?= u('/sayfa/acik-riza') ?>" style="color:#cbd5e1;text-decoration:none">Açık Rıza</a>
    </div>
  </div>
  <div class="mz-copy text-center small">
    © <?= date('Y') ?> <?= e(setting('firma_adi', SITE_NAME)) ?> — Tüm hakları saklıdır.
    &nbsp;|&nbsp; v<?= e(MIZAN_RUNTIME_VERSION) ?> &nbsp;|&nbsp; <span class="text-light-emphasis">Yazılım: <a href="https://codega.com.tr" target="_blank" rel="noopener">CODEGA</a></span>
  </div>
</footer>

<?php require __DIR__ . '/teklif_wizard.php'; ?>

<!-- Çerez onay banner'ı -->
<div id="mzCookieBanner" class="mz-cookie-banner" style="display:none">
  <div class="mz-cookie-content">
    <div class="d-flex align-items-start gap-3">
      <i class="bi bi-cookie text-warning fs-3 flex-shrink-0"></i>
      <div class="flex-grow-1">
        <strong class="d-block mb-1">Çerez Kullanımı Hakkında</strong>
        <p class="small mb-2 mb-md-0" style="color:#cbd5e1">
          Web sitemizde deneyiminizi iyileştirmek için çerezler kullanıyoruz. Detaylı bilgi için
          <a href="<?= u('/sayfa/cerez-politikasi') ?>" style="color:#f4d35e;text-decoration:underline">Çerez Politikamızı</a> inceleyebilirsiniz.
        </p>
      </div>
    </div>
    <div class="d-flex gap-2 mt-3 flex-wrap">
      <button type="button" class="btn btn-warning btn-sm fw-semibold" id="mzCookieAcceptAll"><i class="bi bi-check2"></i> Tümünü Kabul Et</button>
      <button type="button" class="btn btn-outline-light btn-sm" id="mzCookieAcceptEssential">Sadece Zorunlu</button>
      <a href="<?= u('/sayfa/cerez-politikasi') ?>" class="btn btn-link btn-sm text-decoration-none" style="color:#cbd5e1">Detaylar →</a>
    </div>
  </div>
</div>

<style>
.mz-cookie-banner {
  position: fixed; bottom: 16px; left: 16px; right: 16px;
  max-width: 520px; margin-left: auto; z-index: 1050;
  background: #0f1e37; color: #fff;
  border-radius: 14px; padding: 1.25rem;
  border: 1px solid rgba(244, 211, 94, .35);
  box-shadow: 0 25px 60px rgba(0, 0, 0, .4);
}
.mz-cookie-content { font-size: .92rem; line-height: 1.4; }
@media (max-width: 576px) { .mz-cookie-banner { right: 10px; left: 10px; bottom: 10px; padding: 1rem; } }
</style>

<script>
(function () {
  'use strict';
  const KEY = 'mz_cookie_consent';
  const stored = localStorage.getItem(KEY);
  const banner = document.getElementById('mzCookieBanner');
  if (!banner) return;
  if (!stored) {
    banner.style.display = 'block';
  }
  function setConsent(level) {
    localStorage.setItem(KEY, JSON.stringify({ level: level, ts: Date.now() }));
    banner.style.display = 'none';
  }
  document.getElementById('mzCookieAcceptAll').addEventListener('click', () => setConsent('all'));
  document.getElementById('mzCookieAcceptEssential').addEventListener('click', () => setConsent('essential'));
})();
</script>

<!-- Mobil Alt Tab Bar (sadece mobil/tablette gorunur) -->
<nav class="mz-mobile-tabbar" aria-label="Mobil hızlı erişim">
  <a href="<?= u('/') ?>" class="mz-mtab" data-route="/">
    <i class="bi bi-house-fill"></i>
    <span>Anasayfa</span>
  </a>
  <a href="<?= u('/teklif-al') ?>" class="mz-mtab" data-route="/teklif-al">
    <i class="bi bi-file-earmark-text-fill"></i>
    <span>Teklif Al</span>
  </a>
  <?php if ($tel = setting('telefon')): ?>
    <a href="tel:<?= e(preg_replace('/\s+/', '', $tel)) ?>" class="mz-mtab mz-mtab-cta" aria-label="Hemen ara">
      <span class="mz-mtab-cta-circle"><i class="bi bi-telephone-fill"></i></span>
    </a>
  <?php endif; ?>
  <a href="<?= u('/hasar-ihbari') ?>" class="mz-mtab" data-route="/hasar-ihbari">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <span>Hasar</span>
  </a>
  <a href="<?= u('/iletisim') ?>" class="mz-mtab" data-route="/iletisim">
    <i class="bi bi-chat-left-text-fill"></i>
    <span>İletişim</span>
  </a>
</nav>
<script>
// Aktif tab'i isaretle
(function(){
  var path = window.location.pathname.replace(/\/$/, '') || '/';
  document.querySelectorAll('.mz-mtab[data-route]').forEach(function(t){
    var route = t.getAttribute('data-route');
    if (route === '/' && path === '/') t.classList.add('active');
    else if (route !== '/' && path.indexOf(route) === 0) t.classList.add('active');
  });
})();
</script>

<!-- Mizan Kampanya Pop-up (mz_kampanyalar tablosundan, tarih araliginda olan) -->
<?php @require __DIR__ . '/kampanya_popup.php'; ?>

<!-- Scroll to Top -->
<button type="button" id="mzScrollTop" class="mz-scroll-top" aria-label="Yukarı çık" title="Yukarı çık">
  <i class="bi bi-arrow-up"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('assets/js/main.js') ?>"></script>
</body>
</html>
