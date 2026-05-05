<?php
// includes/_404_template.php
// MIZAN_BOOT zaten tanimli olmali (bootstrap'ten gelir)
if (!defined('MIZAN_BOOT')) { http_response_code(403); exit; }
?>
<style>
.mz-404-hero {
  position: relative; padding: 4rem 0;
  background: linear-gradient(135deg, var(--mz-navy) 0%, var(--mz-navy-2) 100%);
  color: #fff; overflow: hidden; text-align: center;
}
.mz-404-hero::before {
  content: ''; position: absolute; inset: 0;
  background: radial-gradient(circle at 50% 30%, rgba(227,11,48,.18) 0%, transparent 50%);
  pointer-events: none;
}
.mz-404-num {
  font-size: 8rem; font-weight: 900; line-height: 1;
  background: linear-gradient(135deg, #fff 30%, var(--mz-red) 70%);
  -webkit-background-clip: text; background-clip: text;
  -webkit-text-fill-color: transparent;
  letter-spacing: -.05em; margin-bottom: .5rem;
}
@media (max-width: 575.98px) { .mz-404-num { font-size: 5rem; } }
.mz-404-shortcut {
  background: #fff; border: 1px solid var(--mz-border); border-radius: 12px;
  padding: 1.5rem; text-align: left; transition: all .2s;
  text-decoration: none; color: inherit; height: 100%; display: block;
}
.mz-404-shortcut:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(0,0,0,.08); border-color: var(--mz-red); color: inherit; }
.mz-404-shortcut i { font-size: 2rem; color: var(--mz-red); }
.mz-404-shortcut h6 { color: var(--mz-navy); font-weight: 700; margin: .75rem 0 .25rem; }
</style>

<section class="mz-404-hero">
  <div class="container position-relative">
    <div class="mz-404-num">404</div>
    <span class="mz-script mz-script-md mz-script-red d-block mb-1" style="color:#f4d35e !important">Hop!</span>
    <h1 class="fw-bold mb-2" style="font-size:1.75rem">Aradığınız sayfa bulunamadı</h1>
    <p class="lead text-white-50 mx-auto mb-4" style="max-width:520px">
      Bağlantı eski olabilir veya yazım hatası olabilir. Aşağıdan size yardımcı olabilecek bir sayfaya geçebilirsiniz.
    </p>
    <a class="btn btn-warning fw-semibold px-4" href="<?= u('/') ?>"><i class="bi bi-house"></i> Anasayfaya Dön</a>
  </div>
</section>

<section class="container py-5">
  <div class="text-center mb-4">
    <h2 class="fw-bold" style="color:var(--mz-navy);font-size:1.5rem">Belki şunlardan birini arıyordunuz?</h2>
  </div>
  <div class="row g-3">
    <div class="col-sm-6 col-lg-3">
      <a href="<?= u('/teklif-al') ?>" class="mz-404-shortcut">
        <i class="bi bi-headset"></i>
        <h6>Teklif Talebi</h6>
        <p class="small text-muted mb-0">Müsait temsilcimiz sizi arasın</p>
      </a>
    </div>
    <div class="col-sm-6 col-lg-3">
      <a href="<?= u('/sss') ?>" class="mz-404-shortcut">
        <i class="bi bi-question-circle"></i>
        <h6>S.S.S.</h6>
        <p class="small text-muted mb-0">Sıkça sorulan soruların yanıtları</p>
      </a>
    </div>
    <div class="col-sm-6 col-lg-3">
      <a href="<?= u('/blog') ?>" class="mz-404-shortcut">
        <i class="bi bi-journal-text"></i>
        <h6>Blog</h6>
        <p class="small text-muted mb-0">Sigorta dünyasından yazılar</p>
      </a>
    </div>
    <div class="col-sm-6 col-lg-3">
      <a href="<?= u('/iletisim') ?>" class="mz-404-shortcut">
        <i class="bi bi-telephone"></i>
        <h6>İletişim</h6>
        <p class="small text-muted mb-0">Bizimle direkt iletişime geçin</p>
      </a>
    </div>
  </div>
</section>
