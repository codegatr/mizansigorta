<?php
/**
 * Mizan Sigorta - Hizli Teklif Sihirbazi (Modal Popup)
 *
 * Public sayfalarda navbar'daki "Hizli Teklif" butonu bu modal'i acar.
 * 3 adim: 1) Sigorta turu, 2) Musteri tipi+kimlik, 3) Iletisim+KVKK
 * AJAX ile /api/teklif-wizard endpoint'ine gonderir, sayfayi yenilemeden teklif olusturur.
 */
if (!defined('MIZAN_BOOT')) return;

// Kategorileri ve alt urunleri yukle (lazy yapmak yerine sayfa yuklenirken DB'den cek)
$wizKategoriler = db_all('SELECT id, slug, baslik, icon FROM ' . t('urunler') . ' WHERE aktif=1 AND parent_id IS NULL ORDER BY sira ASC');
$wizAltUrunler = [];
foreach ($wizKategoriler as $k) {
    $wizAltUrunler[$k['id']] = db_all('SELECT slug, baslik FROM ' . t('urunler') . ' WHERE aktif=1 AND parent_id=? ORDER BY sira ASC', [(int)$k['id']]);
}
?>

<!-- ============== HIZLI TEKLIF WIZARD MODAL ============== -->
<div class="modal fade" id="teklifWizard" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg overflow-hidden" style="border-radius:16px">

      <!-- Header -->
      <div class="modal-header border-0 text-white" style="background:linear-gradient(135deg,var(--mz-navy),var(--mz-navy-2));padding:1.5rem">
        <div class="d-flex align-items-center gap-3">
          <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,var(--mz-red),var(--mz-red-2));display:inline-flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0">
            <i class="bi bi-headset"></i>
          </div>
          <div>
            <span class="mz-script mz-script-md mz-script-red d-block" style="line-height:1">Teklif Talebi</span>
            <h5 class="modal-title fw-bold mb-0 mt-1" id="wizTitle">Müsait temsilcimiz sizi arasın</h5>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
      </div>

      <!-- Adım göstergesi -->
      <div class="px-4 pt-3">
        <div class="mz-steps mb-0">
          <div class="mz-step on" data-s="1"></div>
          <div class="mz-step" data-s="2"></div>
          <div class="mz-step" data-s="3"></div>
        </div>
        <div class="d-flex justify-content-between small text-muted mt-1">
          <span class="wiz-step-label">1. Sigorta türü</span>
          <span class="wiz-step-num">Adım 1 / 3</span>
        </div>
      </div>

      <!-- Body -->
      <div class="modal-body p-4">

        <!-- ====== ADIM 1: Kategori → Alt ürün ====== -->
        <div class="wiz-step" data-step="1">
          <h5 class="fw-bold mb-2">Hangi sigorta türü için talep oluşturuyorsunuz?</h5>
          <p class="text-muted small mb-3">Önce kategoriyi, sonra alt ürünü seçin.</p>

          <div class="wiz-pane wiz-pane-cats">
            <div class="row g-2">
              <?php foreach ($wizKategoriler as $k): ?>
                <div class="col-6 col-md-4">
                  <button type="button" class="wiz-cat-btn" data-cat-id="<?= (int)$k['id'] ?>" data-cat-slug="<?= e($k['slug']) ?>" data-cat-baslik="<?= e($k['baslik']) ?>">
                    <i class="bi <?= e($k['icon'] ?: 'bi-shield') ?>"></i>
                    <span><?= e($k['baslik']) ?></span>
                  </button>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="wiz-pane wiz-pane-prods d-none">
            <div class="d-flex align-items-center gap-2 mb-3 small">
              <button type="button" class="btn btn-link btn-sm p-0" id="wizBackToCats"><i class="bi bi-arrow-left"></i> Kategoriye Dön</button>
              <span class="text-muted">/</span>
              <strong id="wizSelCatLabel"></strong>
            </div>
            <div class="row g-2" id="wizProdList"></div>
          </div>
        </div>

        <!-- ====== ADIM 2: Müşteri tipi + Kimlik ====== -->
        <div class="wiz-step d-none" data-step="2">
          <h5 class="fw-bold mb-3">Kim için teklif?</h5>

          <div class="btn-group w-100 mb-3" role="group">
            <input type="radio" name="wizTip" id="wTip1" value="bireysel" class="btn-check" checked>
            <label class="btn btn-outline-warning" for="wTip1"><i class="bi bi-person"></i> Bireysel</label>
            <input type="radio" name="wizTip" id="wTip2" value="kurumsal" class="btn-check">
            <label class="btn btn-outline-warning" for="wTip2"><i class="bi bi-building"></i> Kurumsal</label>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold mb-1"><span class="wiz-name-label">Ad Soyad</span> *</label>
            <input type="text" id="wizAd" class="form-control" placeholder="Adınız ve soyadınız" required>
          </div>

          <div class="mb-3 wiz-firma d-none">
            <label class="form-label small fw-semibold mb-1">Firma Adı *</label>
            <input type="text" id="wizFirma" class="form-control" placeholder="Şirket ünvanı">
          </div>

          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label small fw-semibold mb-1">İl</label>
              <input type="text" id="wizIl" class="form-control" placeholder="İstanbul">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold mb-1">İlçe</label>
              <input type="text" id="wizIlce" class="form-control" placeholder="Ataşehir">
            </div>
          </div>

          <div class="alert alert-light border mt-3 mb-0 small d-flex gap-2 align-items-start">
            <i class="bi bi-info-circle text-warning fs-5"></i>
            <div>Seçiminiz: <strong id="wizSelectedProductLabel" class="text-warning">—</strong></div>
          </div>
        </div>

        <!-- ====== ADIM 3: İletişim + KVKK ====== -->
        <div class="wiz-step d-none" data-step="3">
          <h5 class="fw-bold mb-3">Sizinle nasıl iletişime geçelim?</h5>

          <div class="mb-3">
            <label class="form-label small fw-semibold mb-1">Cep Telefonu *</label>
            <div class="input-group">
              <span class="input-group-text bg-warning text-white border-warning fw-bold">+90</span>
              <input type="tel" id="wizTel" class="form-control" placeholder="5XX XXX XX XX" required pattern="[0-9 ]+">
            </div>
            <small class="text-muted">Sizi en kısa sürede bu numaradan arayacağız</small>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold mb-1">E-posta <span class="text-danger">*</span></label>
            <input type="email" id="wizEmail" class="form-control" placeholder="ornek@email.com" required>
            <small class="text-muted">Teklif sonucu ve süreç güncellemeleri bu adrese gönderilecek</small>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold mb-1">Mesajınız <span class="text-muted">(opsiyonel)</span></label>
            <textarea id="wizMesaj" class="form-control" rows="2" placeholder="Aracın markası/modeli, evin alanı, kuruluşunuzun büyüklüğü gibi detaylar..."></textarea>
          </div>

          <!-- ====== Sigorta Detaylari (opsiyonel - hizli donus icin) ====== -->
          <details class="mb-3 wiz-detay-box">
            <summary class="wiz-detay-summary">
              <i class="bi bi-lightning-charge-fill text-warning"></i>
              <span>Hızlı dönüş için sigorta detayları</span>
              <small class="text-muted ms-1">(opsiyonel)</small>
            </summary>
            <div class="wiz-detay-icerik mt-3">
              <p class="small text-muted mb-3"><i class="bi bi-info-circle"></i> Bu bilgileri doldurursanız <strong>aramada size daha hızlı dönüş</strong> yapabiliriz.</p>

              <!-- Dogum tarihi (Tüm ürünler) -->
              <div class="mb-3">
                <label class="form-label small fw-semibold mb-1">Doğum Tarihi <small class="text-muted">(opsiyonel)</small></label>
                <div class="row g-2">
                  <div class="col-4">
                    <select id="wizDogumGun" class="form-select form-select-sm">
                      <option value="">Gün</option>
                      <?php for ($g = 1; $g <= 31; $g++): ?><option value="<?= $g ?>"><?= str_pad($g, 2, '0', STR_PAD_LEFT) ?></option><?php endfor; ?>
                    </select>
                  </div>
                  <div class="col-4">
                    <select id="wizDogumAy" class="form-select form-select-sm">
                      <option value="">Ay</option>
                      <?php
                      $aylar = ['', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
                      for ($a = 1; $a <= 12; $a++): ?><option value="<?= $a ?>"><?= $aylar[$a] ?></option><?php endfor; ?>
                    </select>
                  </div>
                  <div class="col-4">
                    <select id="wizDogumYil" class="form-select form-select-sm">
                      <option value="">Yıl</option>
                      <?php $thisYear = (int)date('Y');
                      for ($y = $thisYear - 18; $y >= $thisYear - 90; $y--): ?><option value="<?= $y ?>"><?= $y ?></option><?php endfor; ?>
                    </select>
                  </div>
                </div>
              </div>

              <!-- TCKN (Tüm ürünler) -->
              <div class="mb-3">
                <label class="form-label small fw-semibold mb-1">T.C. Kimlik No <small class="text-muted">(opsiyonel)</small></label>
                <input type="text" id="wizTckn" class="form-control form-control-sm" placeholder="11 haneli TC Kimlik No" maxlength="11" inputmode="numeric" pattern="[0-9]{11}">
                <small class="text-muted">Sigorta poliçesi düzenlemek için gerekli — yalnızca aracılık sürecinde kullanılır</small>
              </div>

              <!-- Arac alanlari (sadece arac kategorisi) -->
              <div id="wizDetayArac" class="d-none">
                <hr class="my-3">
                <div class="small fw-bold text-uppercase text-muted mb-2" style="letter-spacing:.5px"><i class="bi bi-car-front"></i> Araç Bilgileri</div>
                <div class="row g-2 mb-2">
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold mb-1">Araç Plakası</label>
                    <input type="text" id="wizPlaka" class="form-control form-control-sm" placeholder="34 ABC 123" style="text-transform:uppercase" maxlength="20">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold mb-1">Ruhsat Seri No</label>
                    <input type="text" id="wizRuhsat" class="form-control form-control-sm" placeholder="AB123456 veya yeni belge no" style="text-transform:uppercase" maxlength="60">
                  </div>
                </div>
                <small class="text-muted">Plakaya ve ruhsat bilgisine göre 12+ şirketten en uygun primi anında karşılaştırırız.</small>
              </div>

              <!-- Konut alanlari (konut/dask kategorisi) -->
              <div id="wizDetayKonut" class="d-none">
                <hr class="my-3">
                <div class="small fw-bold text-uppercase text-muted mb-2" style="letter-spacing:.5px"><i class="bi bi-house"></i> Konut Bilgileri</div>
                <div class="row g-2 mb-2">
                  <div class="col-md-7">
                    <label class="form-label small fw-semibold mb-1">UAVT Numarası</label>
                    <input type="text" id="wizUavt" class="form-control form-control-sm" placeholder="10 haneli (e-Devlet'ten alınır)" maxlength="10" inputmode="numeric">
                  </div>
                  <div class="col-md-5">
                    <label class="form-label small fw-semibold mb-1">Brüt m²</label>
                    <input type="number" id="wizMetrekare" class="form-control form-control-sm" placeholder="120" min="20" max="5000">
                  </div>
                </div>
                <small class="text-muted">UAVT no e-Devlet → "Yapı Kayıt" → "Adres Kayıt" sayfasından alınabilir.</small>
              </div>
            </div>
          </details>

          <div class="form-check small">
            <input type="checkbox" id="wizKvkk" class="form-check-input" required>
            <label for="wizKvkk" class="form-check-label">
              <a href="<?= u('/sayfa/kvkk') ?>" target="_blank">KVKK Aydınlatma Metni</a>'ni okudum, kişisel verilerimin teklif sürecinde işlenmesine rıza gösteriyorum. <span class="text-danger">*</span>
            </label>
          </div>
        </div>

        <!-- ====== Başarı ekranı ====== -->
        <div class="wiz-step d-none" data-step="ok">
          <div class="text-center py-4">
            <div class="d-inline-flex align-items-center justify-content-center mb-3" style="width:90px;height:90px;border-radius:50%;background:rgba(34,197,94,.12);color:#16a34a;font-size:3rem">
              <i class="bi bi-check-circle-fill"></i>
            </div>
            <h3 class="fw-bold">Talebiniz alındı!</h3>
            <p class="text-muted lead">Müsait temsilcimiz <b id="wizThankPhone" class="text-warning"></b> numaranızdan en kısa sürede sizinle iletişime geçecek.</p>
            <p class="small text-muted mb-0">Çalışma saatlerimiz dışında iletilen talepler bir sonraki iş günü değerlendirilir.</p>
            <p class="mz-script mz-script-md mz-script-red mt-3 mb-0">Güven ve Özen İle</p>
          </div>
        </div>

        <!-- ====== Hata ekranı ====== -->
        <div class="wiz-step d-none" data-step="err">
          <div class="text-center py-4">
            <div class="d-inline-flex align-items-center justify-content-center mb-3" style="width:90px;height:90px;border-radius:50%;background:rgba(220,38,38,.12);color:#dc2626;font-size:3rem">
              <i class="bi bi-x-circle-fill"></i>
            </div>
            <h4 class="fw-bold">Bir sorun oluştu</h4>
            <p class="text-muted" id="wizErrMsg"></p>
            <button class="btn btn-outline-secondary" onclick="wizBackToForm()">Tekrar dene</button>
          </div>
        </div>

      </div>

      <!-- Footer -->
      <div class="modal-footer border-0 bg-light px-4 d-flex justify-content-between align-items-center">
        <button type="button" class="btn btn-link wiz-prev d-none text-decoration-none" onclick="wizPrev()"><i class="bi bi-arrow-left"></i> Geri</button>
        <span></span>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-warning fw-semibold wiz-next" onclick="wizNext()" disabled>İleri <i class="bi bi-arrow-right"></i></button>
          <button type="button" class="btn btn-warning fw-semibold wiz-submit d-none" onclick="wizSubmit()"><i class="bi bi-send"></i> Talebi Gönder</button>
        </div>
      </div>

    </div>
  </div>
</div>

<style>
.wiz-cat-btn {
  width: 100%; padding: 1rem .75rem; border: 2px solid #e5e7eb; border-radius: 12px;
  background: #fff; transition: all .15s; cursor: pointer; display: flex; flex-direction: column;
  align-items: center; gap: .5rem; text-align: center;
}
.wiz-cat-btn i { font-size: 1.75rem; color: var(--mz-red); }
.wiz-cat-btn span { font-weight: 600; color: var(--mz-navy); font-size: .9rem; line-height: 1.15; }
.wiz-cat-btn:hover { border-color: var(--mz-red); transform: translateY(-2px); box-shadow: 0 8px 16px rgba(227,11,48,.12); }
.wiz-cat-btn.active { border-color: var(--mz-red); background: rgba(227,11,48,.04); }

.wiz-prod-btn {
  width: 100%; padding: .8rem 1rem; border: 2px solid #e5e7eb; border-radius: 10px;
  background: #fff; transition: all .15s; cursor: pointer; display: flex; align-items: center; gap: .75rem;
  text-align: left; font-size: .92rem; color: var(--mz-navy);
}
.wiz-prod-btn:hover { border-color: var(--mz-red); background: rgba(227,11,48,.04); }
.wiz-prod-btn.active { border-color: var(--mz-red); background: rgba(227,11,48,.08); font-weight: 600; }

/* Sigorta detaylari (opsiyonel) - hizli donus icin */
.wiz-detay-box { border: 1.5px dashed #fbbf24; border-radius: 12px; padding: 12px 16px; background: rgba(251,191,36,.04); transition: all .2s; }
.wiz-detay-box[open] { border-style: solid; background: rgba(251,191,36,.06); border-color: #f59e0b; }
.wiz-detay-summary { cursor: pointer; font-weight: 600; color: var(--mz-navy); font-size: 14px; display: flex; align-items: center; gap: 8px; user-select: none; list-style: none; }
.wiz-detay-summary::-webkit-details-marker { display: none; }
.wiz-detay-summary::after { content: '+'; margin-left: auto; font-size: 20px; color: #f59e0b; font-weight: 800; transition: transform .2s; }
.wiz-detay-box[open] .wiz-detay-summary::after { transform: rotate(45deg); }
.wiz-detay-icerik { padding-top: 8px; border-top: 1px dashed #fbbf24; }
.wiz-prod-btn .check { margin-left: auto; color: var(--mz-red); opacity: 0; transition: opacity .15s; }
.wiz-prod-btn.active .check { opacity: 1; }
</style>

<script>
(function () {
  'use strict';

  const altUrunler = <?= json_encode($wizAltUrunler, JSON_UNESCAPED_UNICODE) ?>;

  const state = {
    step: 1,
    urun_slug: null,
    urun_baslik: null,
    cat_id: null,
    cat_slug: null,
    cat_baslik: null,
    tip: 'bireysel',
    ad: '',
    firma: '',
    il: '',
    ilce: '',
    tel: '',
    email: '',
    mesaj: '',
    kvkk: false,
    // Sigorta detaylari (opsiyonel)
    dogum_gun: '',
    dogum_ay: '',
    dogum_yil: '',
    tckn: '',
    plaka: '',
    ruhsat: '',
    uavt: '',
    metrekare: '',
  };

  const stepLabels = {
    1: '1. Sigorta türü',
    2: '2. Bilgileriniz',
    3: '3. İletişim'
  };

  function showStep(n) {
    state.step = n;
    document.querySelectorAll('.wiz-step').forEach(s => s.classList.add('d-none'));
    const el = document.querySelector('.wiz-step[data-step="' + n + '"]');
    if (el) el.classList.remove('d-none');

    // Step indicator
    document.querySelectorAll('.mz-step').forEach((s, i) => {
      s.classList.remove('on', 'done');
      const sNum = parseInt(s.getAttribute('data-s'), 10);
      if (typeof n === 'number') {
        if (sNum < n) s.classList.add('done');
        else if (sNum === n) s.classList.add('on');
      }
    });

    // Step labels
    if (typeof n === 'number') {
      document.querySelector('.wiz-step-label').textContent = stepLabels[n] || '';
      document.querySelector('.wiz-step-num').textContent = 'Adım ' + n + ' / 3';
    }

    // Buttons
    const prev = document.querySelector('.wiz-prev');
    const next = document.querySelector('.wiz-next');
    const submit = document.querySelector('.wiz-submit');

    if (n === 'ok' || n === 'err') {
      prev.classList.add('d-none');
      next.classList.add('d-none');
      submit.classList.add('d-none');
      return;
    }

    prev.classList.toggle('d-none', n === 1);
    if (n === 3) {
      next.classList.add('d-none');
      submit.classList.remove('d-none');
      // Kategori slug'ina gore detay alanlarini ac
      const aracBox = document.getElementById('wizDetayArac');
      const konutBox = document.getElementById('wizDetayKonut');
      const slug = (state.cat_slug || '').toLowerCase();
      // Arac kategorisi: kasko, trafik, arac
      const isArac = /arac|kasko|trafik|otomobil|tasit/.test(slug);
      // Konut kategorisi: konut, dask, ev
      const isKonut = /konut|dask|ev|deprem/.test(slug);
      if (aracBox) aracBox.classList.toggle('d-none', !isArac);
      if (konutBox) konutBox.classList.toggle('d-none', !isKonut);
    } else {
      next.classList.remove('d-none');
      submit.classList.add('d-none');
    }
    validateStep();
  }

  function validateStep() {
    const next = document.querySelector('.wiz-next');
    const submit = document.querySelector('.wiz-submit');
    let ok = false;
    if (state.step === 1) ok = !!state.urun_slug;
    else if (state.step === 2) {
      const ad = document.getElementById('wizAd').value.trim();
      const firma = document.getElementById('wizFirma').value.trim();
      ok = ad.length >= 3 && (state.tip === 'bireysel' || firma.length >= 2);
    } else if (state.step === 3) {
      const tel = document.getElementById('wizTel').value.replace(/\s/g, '');
      const email = document.getElementById('wizEmail').value.trim();
      const kvkk = document.getElementById('wizKvkk').checked;
      const emailOk = email.length > 4 && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
      ok = tel.length >= 10 && emailOk && kvkk;
    }
    next.disabled = !ok;
    submit.disabled = !ok;
  }

  // ===== Step 1: Kategori seçimi =====
  document.querySelectorAll('.wiz-cat-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = parseInt(btn.dataset.catId, 10);
      state.cat_id = id;
      state.cat_slug = btn.dataset.catSlug || '';
      state.cat_baslik = btn.dataset.catBaslik;
      // Alt ürünleri göster
      document.querySelector('.wiz-pane-cats').classList.add('d-none');
      const prods = document.querySelector('.wiz-pane-prods');
      prods.classList.remove('d-none');
      document.getElementById('wizSelCatLabel').textContent = btn.dataset.catBaslik;
      // Render alt ürünler
      const list = altUrunler[id] || [];
      const html = list.map(p =>
        '<div class="col-md-6"><button type="button" class="wiz-prod-btn" data-prod-slug="' + p.slug + '" data-prod-baslik="' + p.baslik.replace(/"/g, '&quot;') + '">'
        + '<i class="bi bi-shield-check text-warning"></i><span>' + p.baslik + '</span><i class="bi bi-check-circle-fill check"></i>'
        + '</button></div>'
      ).join('');
      const ana = '<div class="col-12 mb-2"><button type="button" class="wiz-prod-btn" data-prod-slug="' + btn.dataset.catSlug + '" data-prod-baslik="' + btn.dataset.catBaslik.replace(/"/g, '&quot;') + ' (Genel)">'
        + '<i class="bi bi-collection text-warning"></i><span>' + btn.dataset.catBaslik + ' — Genel teklif</span><i class="bi bi-check-circle-fill check"></i>'
        + '</button></div>';
      prods.querySelector('#wizProdList').innerHTML = ana + html;
      // Hook prod buttons
      prods.querySelectorAll('.wiz-prod-btn').forEach(pb => {
        pb.addEventListener('click', () => {
          prods.querySelectorAll('.wiz-prod-btn').forEach(x => x.classList.remove('active'));
          pb.classList.add('active');
          state.urun_slug = pb.dataset.prodSlug;
          state.urun_baslik = pb.dataset.prodBaslik;
          validateStep();
        });
      });
    });
  });

  // Kategoriye dön
  document.getElementById('wizBackToCats').addEventListener('click', () => {
    document.querySelector('.wiz-pane-prods').classList.add('d-none');
    document.querySelector('.wiz-pane-cats').classList.remove('d-none');
    state.urun_slug = null;
    state.urun_baslik = null;
    validateStep();
  });

  // ===== Step 2: Bireysel/Kurumsal =====
  document.querySelectorAll('input[name="wizTip"]').forEach(r => {
    r.addEventListener('change', () => {
      state.tip = r.value;
      const firma = document.querySelector('.wiz-firma');
      const lbl = document.querySelector('.wiz-name-label');
      if (state.tip === 'kurumsal') {
        firma.classList.remove('d-none');
        lbl.textContent = 'Yetkili Kişi';
      } else {
        firma.classList.add('d-none');
        lbl.textContent = 'Ad Soyad';
      }
      validateStep();
    });
  });

  ['wizAd', 'wizFirma', 'wizIl', 'wizIlce', 'wizTel', 'wizEmail', 'wizMesaj'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', validateStep);
  });
  document.getElementById('wizKvkk').addEventListener('change', validateStep);

  // ===== Navigation =====
  window.wizNext = function () {
    if (state.step === 1) {
      // Show selected product label on step 2
      document.getElementById('wizSelectedProductLabel').textContent = state.urun_baslik;
      showStep(2);
    } else if (state.step === 2) {
      state.ad = document.getElementById('wizAd').value.trim();
      state.firma = document.getElementById('wizFirma').value.trim();
      state.il = document.getElementById('wizIl').value.trim();
      state.ilce = document.getElementById('wizIlce').value.trim();
      showStep(3);
    }
  };

  window.wizPrev = function () {
    if (state.step === 3) showStep(2);
    else if (state.step === 2) showStep(1);
  };

  window.wizBackToForm = function () { showStep(3); };

  window.wizSubmit = function () {
    state.tel = document.getElementById('wizTel').value.trim();
    state.email = document.getElementById('wizEmail').value.trim();
    state.mesaj = document.getElementById('wizMesaj').value.trim();
    state.kvkk = document.getElementById('wizKvkk').checked;

    // Sigorta detaylari (opsiyonel - kullanici doldurdu ise alir)
    state.dogum_gun = (document.getElementById('wizDogumGun')||{}).value || '';
    state.dogum_ay  = (document.getElementById('wizDogumAy') ||{}).value || '';
    state.dogum_yil = (document.getElementById('wizDogumYil')||{}).value || '';
    state.tckn      = (document.getElementById('wizTckn')    ||{}).value.trim() || '';
    state.plaka     = (document.getElementById('wizPlaka')   ||{}).value.trim().toUpperCase() || '';
    state.ruhsat    = (document.getElementById('wizRuhsat')  ||{}).value.trim().toUpperCase() || '';
    state.uavt      = (document.getElementById('wizUavt')    ||{}).value.trim() || '';
    state.metrekare = (document.getElementById('wizMetrekare')||{}).value.trim() || '';

    const submit = document.querySelector('.wiz-submit');
    submit.disabled = true;
    submit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Gönderiliyor...';

    fetch('<?= u("/api/teklif-wizard") ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(state)
    })
    .then(r => r.json())
    .then(d => {
      if (d.ok) {
        document.getElementById('wizThankPhone').textContent = state.tel;
        showStep('ok');
      } else {
        document.getElementById('wizErrMsg').textContent = d.error || 'Bilinmeyen hata oluştu, lütfen tekrar deneyin.';
        showStep('err');
      }
    })
    .catch(e => {
      document.getElementById('wizErrMsg').textContent = 'Bağlantı hatası: ' + e.message;
      showStep('err');
    })
    .finally(() => {
      submit.disabled = false;
      submit.innerHTML = '<i class="bi bi-send"></i> Teklifi Gönder';
    });
  };

  // Reset on close
  document.getElementById('teklifWizard').addEventListener('hidden.bs.modal', () => {
    state.step = 1;
    state.urun_slug = state.urun_baslik = state.cat_id = state.cat_slug = state.cat_baslik = null;
    state.tip = 'bireysel';
    state.ad = state.firma = state.il = state.ilce = state.tel = state.email = state.mesaj = '';
    state.kvkk = false;
    state.dogum_gun = state.dogum_ay = state.dogum_yil = state.tckn = '';
    state.plaka = state.ruhsat = state.uavt = state.metrekare = '';
    document.querySelectorAll('.wiz-prod-btn').forEach(b => b.classList.remove('active'));
    ['wizAd', 'wizFirma', 'wizIl', 'wizIlce', 'wizTel', 'wizEmail', 'wizMesaj',
     'wizTckn', 'wizPlaka', 'wizRuhsat', 'wizUavt', 'wizMetrekare'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.value = '';
    });
    ['wizDogumGun', 'wizDogumAy', 'wizDogumYil'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.selectedIndex = 0;
    });
    document.getElementById('wizKvkk').checked = false;
    document.querySelector('.wiz-pane-prods').classList.add('d-none');
    document.querySelector('.wiz-pane-cats').classList.remove('d-none');
    document.getElementById('wTip1').checked = true;
    document.querySelector('.wiz-firma').classList.add('d-none');
    // Detay box'larini gizle ve <details>'i kapat
    const dArac = document.getElementById('wizDetayArac');
    const dKonut = document.getElementById('wizDetayKonut');
    if (dArac) dArac.classList.add('d-none');
    if (dKonut) dKonut.classList.add('d-none');
    document.querySelectorAll('.wiz-detay-box').forEach(d => d.removeAttribute('open'));
    showStep(1);
  });
})();
</script>
