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
  <div class="mz-copy text-center small">
    © <?= date('Y') ?> <?= e(setting('firma_adi', SITE_NAME)) ?> — Tüm hakları saklıdır.
    &nbsp;|&nbsp; v<?= e(SITE_VERSION) ?> &nbsp;|&nbsp; <span class="text-light-emphasis">Yazılım: <a href="https://codega.com.tr" target="_blank" rel="noopener">CODEGA</a></span>
  </div>
</footer>

<button type="button" class="mz-fab" title="Hızlı Teklif" data-bs-toggle="modal" data-bs-target="#teklifWizard" style="border:0;cursor:pointer"><i class="bi bi-lightning-charge-fill"></i></button>

<?php require __DIR__ . '/teklif_wizard.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('assets/js/main.js') ?>"></script>
</body>
</html>
