<?php
if (!defined('MIZAN_BOOT')) { http_response_code(403); exit; }
$no = $_GET['no'] ?? '';
$pageTitle = 'Teklif talebiniz alındı - ' . SITE_NAME;
require MIZAN_INC . '/header.php';
?>

<style>
.mz-thx-hero {
  position: relative; padding: 5rem 0 4rem;
  background: linear-gradient(135deg, var(--mz-navy) 0%, var(--mz-navy-2) 100%);
  color: #fff; overflow: hidden; text-align: center;
}
.mz-thx-hero::before {
  content: ''; position: absolute; inset: 0;
  background:
    radial-gradient(circle at 30% 30%, rgba(34,197,94,.18) 0%, transparent 50%),
    radial-gradient(circle at 70% 70%, rgba(227,11,48,.08) 0%, transparent 50%);
  pointer-events: none;
}
.mz-thx-check {
  width: 110px; height: 110px; border-radius: 50%;
  background: linear-gradient(135deg, #22c55e, #15803d);
  display: inline-flex; align-items: center; justify-content: center;
  margin-bottom: 1.5rem; box-shadow: 0 25px 50px rgba(34,197,94,.4);
  animation: thxPop .5s cubic-bezier(.34,1.56,.64,1);
}
.mz-thx-check i { color: #fff; font-size: 4rem; }
@keyframes thxPop {
  0% { transform: scale(0); opacity: 0; }
  60% { transform: scale(1.15); }
  100% { transform: scale(1); opacity: 1; }
}
.mz-thx-no {
  display: inline-block; padding: .85rem 1.75rem; border-radius: 12px;
  background: rgba(255,255,255,.08); border: 1px dashed rgba(255,255,255,.3);
  font-family: monospace; font-size: 1.25rem; letter-spacing: 1px;
  color: #f4d35e; margin: .5rem 0 1.5rem;
}
.mz-thx-step {
  background: #fff; border: 1px solid var(--mz-border); border-radius: 14px;
  padding: 1.5rem; height: 100%; transition: all .2s; position: relative;
}
.mz-thx-step:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(0,0,0,.08); }
.mz-thx-step-num {
  position: absolute; top: -16px; left: 1.5rem;
  width: 36px; height: 36px; border-radius: 50%;
  background: linear-gradient(135deg, var(--mz-red), var(--mz-red-2));
  color: #fff; font-weight: 800; font-size: .95rem;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 8px 20px rgba(227,11,48,.35);
}
.mz-thx-step h6 { color: var(--mz-navy); font-weight: 700; margin: 1rem 0 .5rem; }
.mz-thx-step .icon { font-size: 1.6rem; color: var(--mz-red); }
</style>

<section class="mz-thx-hero">
  <div class="container position-relative">
    <div class="mz-thx-check"><i class="bi bi-check-lg"></i></div>
    <span class="mz-script mz-script-md mz-script-red d-block mb-1" style="color:#f4d35e !important">Talebiniz Alındı</span>
    <h1 class="display-5 fw-bold mb-2">Teşekkür ederiz!</h1>
    <p class="lead text-white-50 mx-auto mb-2" style="max-width:560px">
      Teklif talebiniz başarıyla iletildi. Müsait temsilcimiz en kısa sürede sizinle iletişime geçecek.
    </p>
    <?php if ($no): ?>
      <div class="mz-thx-no">
        <i class="bi bi-hash"></i> <?= e($no) ?>
      </div>
      <p class="small text-white-50 mb-0">Bu numarayı saklayabilir, herhangi bir görüşmede referans olarak kullanabilirsiniz.</p>
    <?php endif; ?>
  </div>
</section>

<section class="container py-5">
  <div class="text-center mb-4">
    <span class="mz-script mz-script-md mz-script-red">Süreç</span>
    <h2 class="fw-bold" style="color:var(--mz-navy)">Bundan sonra ne olacak?</h2>
    <p class="text-muted">Teklif talebinizin yolculuğu</p>
  </div>

  <div class="row g-4">
    <div class="col-md-4">
      <div class="mz-thx-step">
        <span class="mz-thx-step-num">1</span>
        <i class="bi bi-headset icon"></i>
        <h6>Temsilci ataması</h6>
        <p class="small text-muted mb-0">Talebiniz uzmanlık alanına göre müsait temsilcimize otomatik atanır. Çalışma saatleri içinde 30 dakika içinde, dışında bir sonraki iş günü.</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="mz-thx-step">
        <span class="mz-thx-step-num">2</span>
        <i class="bi bi-bar-chart-line icon"></i>
        <h6>Karşılaştırmalı teklif</h6>
        <p class="small text-muted mb-0">Temsilciniz sizi arar, ihtiyaç analizi yapar ve 12+ anlaşmalı sigorta şirketinden karşılaştırmalı teklifleri hazırlar.</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="mz-thx-step">
        <span class="mz-thx-step-num">3</span>
        <i class="bi bi-shield-check icon"></i>
        <h6>Onayla, poliçen hazır</h6>
        <p class="small text-muted mb-0">Sizin için en uygun teklifi seçtiğinizde poliçeniz hızlıca düzenlenir. KVKK uyumlu, şeffaf, bağlayıcılığı yok.</p>
      </div>
    </div>
  </div>

  <div class="text-center mt-5 d-flex flex-wrap gap-2 justify-content-center">
    <a class="btn btn-warning fw-semibold px-4" href="<?= u('/') ?>"><i class="bi bi-house"></i> Anasayfaya Dön</a>
    <a class="btn btn-outline-secondary px-4" href="<?= u('/blog') ?>"><i class="bi bi-journal-text"></i> Blog Yazıları</a>
    <a class="btn btn-outline-secondary px-4" href="<?= u('/iletisim') ?>"><i class="bi bi-telephone"></i> İletişim</a>
  </div>

  <div class="text-center mt-4 small text-muted">
    <i class="bi bi-shield-lock"></i> Bilgileriniz KVKK kapsamında korunmaktadır · Üçüncü taraflarla paylaşılmaz
  </div>
</section>

<?php require MIZAN_INC . '/footer.php';
