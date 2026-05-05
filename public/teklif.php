<?php
if (!defined('MIZAN_BOOT')) { http_response_code(403); exit; }

$urunler = db_all('SELECT * FROM ' . t('urunler') . ' WHERE aktif=1 ORDER BY sira ASC');
$onSecili = $_GET['urun'] ?? '';
$onTel    = $_GET['tel']  ?? '';

$errors = [];
$success = false;
$teklifNo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_assert_post();

    $tip       = trim((string) ($_POST['tip'] ?? 'bireysel'));
    $urun_slug = trim((string) ($_POST['urun'] ?? ''));
    $ad        = trim((string) ($_POST['ad_soyad'] ?? ''));
    $firma     = trim((string) ($_POST['firma_adi'] ?? ''));
    $email     = trim((string) ($_POST['email'] ?? ''));
    $tel       = trim((string) ($_POST['telefon'] ?? ''));
    $il        = trim((string) ($_POST['il'] ?? ''));
    $ilce      = trim((string) ($_POST['ilce'] ?? ''));
    $aciklama  = trim((string) ($_POST['aciklama'] ?? ''));
    $kvkk      = !empty($_POST['kvkk']);
    $captcha   = (int) ($_POST['captcha'] ?? 0);
    $captchaA  = (int) ($_POST['captcha_a'] ?? 0);
    $captchaB  = (int) ($_POST['captcha_b'] ?? 0);

    // Urun-spesifik detaylar
    $detay = [];
    foreach ($_POST as $k => $v) {
        if (str_starts_with($k, 'd_')) {
            $detay[substr($k, 2)] = is_string($v) ? trim($v) : $v;
        }
    }

    if ($ad === '' || mb_strlen($ad) < 3) $errors[] = 'Ad soyad en az 3 karakter olmalı.';
    if ($tel === '' || strlen(preg_replace('/\D+/', '', $tel)) < 10) $errors[] = 'Geçerli bir telefon girin.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli bir e-posta girin.';
    if ($urun_slug === '') $errors[] = 'Sigorta türü seçilmedi.';
    if (!$kvkk) $errors[] = 'KVKK aydınlatma metnini onaylamalısınız.';
    if ($captcha !== ($captchaA + $captchaB)) $errors[] = 'Doğrulama yanlış. Lütfen toplama işlemini doğru girin.';

    $urun = $urun_slug !== ''
        ? db_row('SELECT id, baslik FROM ' . t('urunler') . ' WHERE slug=? AND aktif=1', [$urun_slug])
        : null;
    if (!$urun) $errors[] = 'Geçersiz sigorta türü.';

    // Honeypot
    if (!empty($_POST['website'])) $errors[] = 'Spam tespit edildi.';

    if (!$errors) {
        $no = generate_no(setting('teklif_otomatik_no', 'TKL'));
        db_exec(
            'INSERT INTO ' . t('teklifler') .
            ' (teklif_no, urun_id, kaynak, durum, ad_soyad, firma_adi, email, telefon, il, ilce,
               aciklama, urun_detay_json, kvkk_onay, ip_adresi, user_agent)
              VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $no, (int) $urun['id'], 'web', 'yeni', $ad,
                $tip === 'kurumsal' ? $firma : null,
                $email !== '' ? $email : null, $tel,
                $il !== '' ? $il : null, $ilce !== '' ? $ilce : null,
                $aciklama !== '' ? $aciklama : null,
                $detay ? json_encode($detay, JSON_UNESCAPED_UNICODE) : null,
                1, client_ip(),
                substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255)
            ]
        );
        $teklifId = db_last_id();
        $teklifNo = $no;
        $success = true;

        // Operatore bildirim (panel + email) + talep merkezi BCC
        $opMail = setting('teklif_bildirim_email');
        $extra  = talep_bildirim_alicilari();
        if ($opMail) {
            $infoTable = '<table cellpadding="0" cellspacing="0" style="width:100%;background:#f8fafc;border-radius:8px;margin:16px 0">'
                . '<tr><td style="padding:12px 18px;border-bottom:1px solid #e5e7eb;width:140px;color:#6b7280;font-size:13px">Teklif No</td><td style="padding:12px 18px;border-bottom:1px solid #e5e7eb;font-weight:600">#' . e($no) . '</td></tr>'
                . '<tr><td style="padding:12px 18px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px">Ürün</td><td style="padding:12px 18px;border-bottom:1px solid #e5e7eb">' . e($urun['baslik']) . '</td></tr>'
                . '<tr><td style="padding:12px 18px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px">Ad Soyad</td><td style="padding:12px 18px;border-bottom:1px solid #e5e7eb;font-weight:600">' . e($ad) . '</td></tr>'
                . '<tr><td style="padding:12px 18px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px">Telefon</td><td style="padding:12px 18px;border-bottom:1px solid #e5e7eb"><a href="tel:' . preg_replace('/\s+/', '', $tel) . '" style="color:#e30b30;text-decoration:none;font-weight:600">' . e($tel) . '</a></td></tr>';
            if ($email !== '') {
                $infoTable .= '<tr><td style="padding:12px 18px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px">E-posta</td><td style="padding:12px 18px;border-bottom:1px solid #e5e7eb"><a href="mailto:' . e($email) . '" style="color:#0d1b2a;text-decoration:none">' . e($email) . '</a></td></tr>';
            }
            if ($il !== '' || $ilce !== '') {
                $infoTable .= '<tr><td style="padding:12px 18px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px">İl / İlçe</td><td style="padding:12px 18px;border-bottom:1px solid #e5e7eb">' . e(trim($il . ' / ' . $ilce, ' /')) . '</td></tr>';
            }
            if ($aciklama !== '') {
                $infoTable .= '<tr><td style="padding:12px 18px;color:#6b7280;font-size:13px;vertical-align:top">Açıklama</td><td style="padding:12px 18px;line-height:1.6">' . nl2br(e($aciklama)) . '</td></tr>';
            }
            $infoTable .= '</table>';

            $bodyHtml = '<h2 style="margin:0 0 8px;color:#0d1b2a;font-size:22px">Yeni Teklif Talebi</h2>'
                      . '<p style="color:#6b7280;margin:0 0 16px">Web sitesinden ' . date('d.m.Y H:i') . ' tarihinde gelen yeni teklif talebi:</p>'
                      . $infoTable;
            $html = mail_template('Yeni Teklif: ' . $no, $bodyHtml, [
                'badge'       => 'YENİ TEKLİF',
                'badge_color' => '#f4d35e',
                'preheader'   => 'Yeni teklif talebi: ' . $ad . ' - ' . $urun['baslik'],
                'cta_text'    => 'Panelde Aç',
                'cta_url'     => u('/yonetim/teklif-detay.php?id=' . $teklifId),
            ]);
            send_mail($opMail, 'Yeni teklif: ' . $no, $html, '', ['bcc' => $extra['bcc']]);
        }
        // Musteriye otomatik tesekkur
        if ($email !== '') {
            $bodyHtml = '<h2 style="margin:0 0 12px;color:#0d1b2a;font-size:22px">Merhaba ' . e($ad) . ',</h2>'
                      . '<p style="font-size:16px;color:#1f2937;margin-bottom:18px">' . e($urun['baslik']) . ' için teklif talebiniz başarıyla iletildi. Uzman ekibimiz en kısa sürede sizinle iletişime geçecek.</p>'
                      . '<table cellpadding="0" cellspacing="0" style="background:#f8fafc;border-radius:8px;margin:18px 0;width:100%"><tr><td style="padding:18px 22px"><div style="font-size:12px;color:#6b7280;letter-spacing:.5px;text-transform:uppercase;font-weight:600;margin-bottom:6px">Teklif Numaranız</div><div style="font-size:22px;font-weight:800;color:#0d1b2a;font-family:Georgia,serif">' . e($no) . '</div></td></tr></table>'
                      . '<p style="color:#374151">Talebinizi anlaşmalı 12+ sigorta şirketi arasında karşılaştırıyor, size en uygun paketi sunuyoruz. Sürecin tamamı boyunca KVKK kapsamında bilgileriniz koruma altındadır.</p>'
                      . '<p style="color:#1f2937;margin-bottom:0"><strong>Teşekkür ederiz.</strong><br><span style="color:#6b7280">' . e(setting('firma_adi', SITE_NAME)) . '</span></p>';
            $html = mail_template('Teklif Talebiniz Alındı', $bodyHtml, [
                'badge'       => 'TALEBİNİZ ALINDI',
                'badge_color' => '#22c55e',
                'preheader'   => 'Teklif talebiniz alindi - Teklif No: ' . $no,
            ]);
            send_mail($email, 'Teklif talebiniz alındı - ' . $no, $html);
        }

        // Tesekkur sayfasina yonlendir (PRG)
        safe_redirect('/teklif-tesekkur?no=' . urlencode($no));
    }
}

$captchaA = random_int(2, 9);
$captchaB = random_int(2, 9);

$pageTitle = 'Teklif Talebi - ' . setting('firma_adi', SITE_NAME);
require MIZAN_INC . '/header.php';
?>

<section class="mz-page-head">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
      <div>
        <h1 class="fw-bold mb-1">Teklif Talebi Oluşturun</h1>
        <p class="mb-0 small" style="color:rgba(255,255,255,.8)">Bilgilerinizi alalım, müsait temsilcimiz sizi arayarak en uygun teklifi sunsun.</p>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
          <li class="breadcrumb-item"><a href="<?= u('/') ?>">Anasayfa</a></li>
          <li class="breadcrumb-item active">Teklif Talebi</li>
        </ol></nav>
      </div>
      <div class="d-none d-md-flex flex-wrap gap-2">
        <span class="mz-trust-badge"><i class="bi bi-patch-check"></i> Lisanslı Acente</span>
        <span class="mz-trust-badge"><i class="bi bi-shield-lock"></i> KVKK Uyumlu</span>
      </div>
    </div>
  </div>
</section>

<section class="container py-5">
  <div class="row g-4">
    <div class="col-lg-8">
      <?php if ($errors): ?>
        <div class="alert alert-danger d-flex gap-3 align-items-start mb-4">
          <i class="bi bi-exclamation-triangle-fill fs-4"></i>
          <div>
            <strong class="d-block mb-1">Lütfen aşağıdaki hataları düzeltin:</strong>
            <ul class="mb-0 small"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
          </div>
        </div>
      <?php endif; ?>

      <!-- Adım göstergesi -->
      <div class="mz-form-stepper mb-4">
        <div class="mz-step-item active" data-step="1"><span class="mz-step-num">1</span><span class="mz-step-lbl">Müşteri Tipi</span></div>
        <div class="mz-step-item" data-step="2"><span class="mz-step-num">2</span><span class="mz-step-lbl">Sigorta Türü</span></div>
        <div class="mz-step-item" data-step="3"><span class="mz-step-num">3</span><span class="mz-step-lbl">İletişim</span></div>
        <div class="mz-step-item" data-step="4"><span class="mz-step-num">4</span><span class="mz-step-lbl">Onay</span></div>
      </div>

      <form method="post" class="mz-form-card" novalidate>
        <?= csrf_field() ?>
        <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">

        <!-- 1. Müşteri Tipi -->
        <div class="mb-4">
          <h5 class="fw-bold mb-3"><span class="mz-form-num">1</span> Müşteri Tipi</h5>
          <div class="row g-2">
            <div class="col-md-6">
              <input type="radio" class="btn-check" name="tip" id="tipB" value="bireysel" checked>
              <label class="btn w-100 mz-tip-card text-start" for="tipB">
                <i class="bi bi-person-fill"></i>
                <span class="mz-tip-title">Bireysel</span>
                <span class="mz-tip-desc">Kasko, konut, sağlık, hayat vb. kişisel sigortalar</span>
              </label>
            </div>
            <div class="col-md-6">
              <input type="radio" class="btn-check" name="tip" id="tipK" value="kurumsal">
              <label class="btn w-100 mz-tip-card text-start" for="tipK">
                <i class="bi bi-building-fill"></i>
                <span class="mz-tip-title">Kurumsal</span>
                <span class="mz-tip-desc">İşyeri, filo, sorumluluk, grup sağlık vb. ticari sigortalar</span>
              </label>
            </div>
          </div>
        </div>

        <!-- 2. Sigorta Türü -->
        <div class="mb-4">
          <h5 class="fw-bold mb-3"><span class="mz-form-num">2</span> Sigorta Türü</h5>
          <p class="small text-muted mb-3"><i class="bi bi-info-circle"></i> Ana kategori seçin — alt ürün seçimini temsilcimizle birlikte belirleyeceksiniz.</p>
          <div class="row g-2">
            <?php foreach ($urunler as $u): ?>
              <div class="col-6 col-md-4">
                <input type="radio" class="btn-check mz-prod-radio" name="urun" id="u_<?= e($u['slug']) ?>" value="<?= e($u['slug']) ?>"
                  <?= $onSecili === $u['slug'] ? 'checked' : '' ?>>
                <label class="btn w-100 mz-prod-btn text-start" for="u_<?= e($u['slug']) ?>">
                  <i class="bi <?= e($u['icon'] ?: 'bi-shield-check') ?>"></i>
                  <span><?= e($u['baslik']) ?></span>
                </label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- 3. İletişim Bilgileri -->
        <div class="mb-4">
          <h5 class="fw-bold mb-3"><span class="mz-form-num">3</span> İletişim Bilgileri</h5>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Ad Soyad <span class="text-danger">*</span></label>
              <div class="input-group"><span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                <input type="text" name="ad_soyad" class="form-control" value="<?= e($_POST['ad_soyad'] ?? '') ?>" required placeholder="Adınız ve soyadınız">
              </div>
            </div>
            <div class="col-md-6 mz-firma-field" style="display:none">
              <label class="form-label small fw-semibold">Firma Adı</label>
              <div class="input-group"><span class="input-group-text bg-light"><i class="bi bi-building"></i></span>
                <input type="text" name="firma_adi" class="form-control" value="<?= e($_POST['firma_adi'] ?? '') ?>" placeholder="Şirket ünvanınız">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Telefon <span class="text-danger">*</span></label>
              <div class="input-group"><span class="input-group-text bg-light"><i class="bi bi-telephone"></i></span>
                <input type="tel" name="telefon" class="form-control" value="<?= e($onTel ?: ($_POST['telefon'] ?? '')) ?>" placeholder="0 5xx xxx xx xx" required>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">E-posta <small class="text-muted fw-normal">(opsiyonel)</small></label>
              <div class="input-group"><span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? '') ?>" placeholder="ornek@email.com">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">İl</label>
              <input type="text" name="il" class="form-control" value="<?= e($_POST['il'] ?? '') ?>" placeholder="Konya, İstanbul, Ankara...">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">İlçe</label>
              <input type="text" name="ilce" class="form-control" value="<?= e($_POST['ilce'] ?? '') ?>" placeholder="Selçuklu, Kadıköy...">
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold">Ek Açıklama / Talep <small class="text-muted fw-normal">(opsiyonel)</small></label>
              <textarea name="aciklama" class="form-control" rows="3" placeholder="Aracınızın model yılı, evinizin metrekaresi, çalıştığınız sektör gibi detayları yazabilirsiniz."><?= e($_POST['aciklama'] ?? '') ?></textarea>
            </div>
          </div>
        </div>

        <!-- 4. Doğrulama + KVKK -->
        <div class="mb-3 p-3 rounded" style="background:rgba(13,27,42,.04);border:1px dashed rgba(13,27,42,.15)">
          <h6 class="fw-bold mb-3"><span class="mz-form-num">4</span> Doğrulama ve Onay</h6>
          <div class="row g-3 align-items-center mb-3">
            <div class="col-md-4">
              <label class="form-label small fw-semibold mb-1"><?= $captchaA ?> + <?= $captchaB ?> = ?</label>
              <input type="number" name="captcha" class="form-control" required placeholder="Sonuç">
              <input type="hidden" name="captcha_a" value="<?= $captchaA ?>">
              <input type="hidden" name="captcha_b" value="<?= $captchaB ?>">
            </div>
            <div class="col-md-8">
              <div class="form-check">
                <input type="checkbox" name="kvkk" id="kvkk" class="form-check-input" required>
                <label class="form-check-label small" for="kvkk">
                  <a href="<?= u('/sayfa/kvkk') ?>" target="_blank">KVKK Aydınlatma Metni</a>'ni okudum, kişisel verilerimin teklif sürecinde işlenmesine onay veriyorum.
                </label>
              </div>
            </div>
          </div>
        </div>

        <button class="btn btn-warning btn-lg w-100 fw-semibold py-3"><i class="bi bi-send-fill"></i> Teklif Talebimi Gönder</button>
        <p class="text-center small text-muted mt-3 mb-0">
          <i class="bi bi-shield-check"></i> Bilgileriniz KVKK kapsamında korunmaktadır · Üçüncü taraflarla paylaşılmaz
        </p>
      </form>
    </div>

    <aside class="col-lg-4">
      <!-- Nasıl çalışır -->
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
          <h6 class="fw-bold mb-3" style="color:var(--mz-navy)"><i class="bi bi-list-check text-warning"></i> Nasıl çalışır?</h6>
          <div class="mz-howto-step">
            <span class="mz-howto-num">1</span>
            <div><strong class="d-block">Talebinizi iletin</strong><span class="small text-muted">Form üzerinden temel bilgilerinizi paylaşın.</span></div>
          </div>
          <div class="mz-howto-step">
            <span class="mz-howto-num">2</span>
            <div><strong class="d-block">Temsilcimiz arar</strong><span class="small text-muted">Müsait temsilcimiz size ulaşır, ihtiyaç analizini yapar.</span></div>
          </div>
          <div class="mz-howto-step">
            <span class="mz-howto-num">3</span>
            <div><strong class="d-block">Karşılaştırmalı teklif</strong><span class="small text-muted">12+ sigorta şirketinden size en uygun teklif sunulur.</span></div>
          </div>
          <div class="mz-howto-step">
            <span class="mz-howto-num">4</span>
            <div><strong class="d-block">Onayınızla poliçe</strong><span class="small text-muted">Tercih ettiğiniz teklifle poliçeniz hazırlanır.</span></div>
          </div>
        </div>
      </div>

      <!-- Bize Ulaşın -->
      <div class="mz-side-card mb-3">
        <h6 class="fw-bold mb-3"><i class="bi bi-headset"></i> Doğrudan Görüşelim</h6>
        <p class="small mb-2" style="opacity:.85">Acil durumlarda doğrudan arayabilirsiniz:</p>
        <a href="tel:<?= e(preg_replace('/\s+/', '', setting('telefon', '02163152674'))) ?>" class="btn btn-warning fw-semibold w-100 mb-2">
          <i class="bi bi-telephone-fill"></i> <?= e(setting('telefon', '0216 315 26 74')) ?>
        </a>
        <p class="small mb-0" style="opacity:.75">
          <i class="bi bi-clock"></i> <?= e(setting('calisma_saatleri', 'Pzt-Cum 09:00-18:00, Cmt 10:00-14:00')) ?>
        </p>
      </div>

      <!-- Güvende misiniz -->
      <div class="card border-0 shadow-sm mb-3" style="background:#fff8e6;border-left:4px solid var(--mz-warning,#ffc107) !important">
        <div class="card-body">
          <h6 class="fw-bold mb-2"><i class="bi bi-shield-lock-fill text-warning"></i> Verileriniz güvende</h6>
          <p class="small mb-0">
            Bilgileriniz <strong>SSL ile şifrelenmiş</strong> kanaldan iletilir, KVKK kapsamında yalnızca teklif sürecinde kullanılır.
            Üçüncü taraflarla pazarlama amacıyla paylaşılmaz.
          </p>
        </div>
      </div>

      <!-- Anlaşmalı Şirketler -->
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <h6 class="fw-bold mb-3"><i class="bi bi-buildings text-warning"></i> Anlaşmalı Şirketler</h6>
          <p class="small text-muted mb-2">12+ sigorta şirketinden karşılaştırmalı teklif:</p>
          <div class="d-flex flex-wrap gap-1">
            <?php
            $sirketKisa = db_all('SELECT ad FROM ' . t('sigorta_sirketleri') . ' WHERE aktif=1 ORDER BY sira ASC LIMIT 12');
            foreach ($sirketKisa as $sk): ?>
              <span class="badge bg-light text-dark fw-normal border"><?= e($sk['ad']) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </aside>
  </div>
</section>

<style>
.mz-form-stepper { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; padding:1rem 1.25rem; background:#fff; border-radius:12px; border:1px solid var(--mz-border); }
.mz-step-item { display:flex; align-items:center; gap:.5rem; flex:1; min-width:120px; }
.mz-step-num { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:50%; background:#e5e7eb; color:#6b7280; font-weight:700; font-size:.85rem; flex-shrink:0; transition:all .2s; }
.mz-step-lbl { font-size:.78rem; color:#6b7280; font-weight:500; }
.mz-step-item.active .mz-step-num { background:linear-gradient(135deg,var(--mz-red),var(--mz-red-2)); color:#fff; }
.mz-step-item.active .mz-step-lbl { color:var(--mz-navy); font-weight:600; }
.mz-form-num { display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; border-radius:50%; background:linear-gradient(135deg,var(--mz-red),var(--mz-red-2)); color:#fff; font-size:.9rem; font-weight:700; margin-right:.4rem; }
.mz-tip-card { padding:1.25rem; border:2px solid var(--mz-border); border-radius:10px; transition:all .2s; background:#fff; display:block; }
.mz-tip-card i { font-size:1.6rem; color:var(--mz-navy); margin-bottom:.5rem; display:block; }
.mz-tip-card .mz-tip-title { display:block; font-weight:700; font-size:1.05rem; color:var(--mz-navy); }
.mz-tip-card .mz-tip-desc { display:block; font-size:.78rem; color:#6b7280; margin-top:.25rem; line-height:1.4; }
.btn-check:checked + .mz-tip-card { border-color:var(--mz-red); background:rgba(238,39,55,.04); box-shadow:0 4px 12px rgba(238,39,55,.12); }
.btn-check:checked + .mz-tip-card i { color:var(--mz-red); }
.mz-howto-step { display:flex; gap:.75rem; align-items:flex-start; padding:.6rem 0; border-bottom:1px dashed var(--mz-border); }
.mz-howto-step:last-child { border-bottom:none; padding-bottom:0; }
.mz-howto-num { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:50%; background:rgba(238,39,55,.1); color:var(--mz-red); font-weight:700; font-size:.85rem; flex-shrink:0; }
</style>

<script>
(function () {
  var tipRadios = document.querySelectorAll('input[name="tip"]');
  var firmaField = document.querySelector('.mz-firma-field');
  tipRadios.forEach(function (r) {
    r.addEventListener('change', function (e) {
      firmaField.style.display = e.target.value === 'kurumsal' ? '' : 'none';
    });
  });

  // Stepper highlight - hangi alana focus var
  var stepMap = { ad_soyad: 3, firma_adi: 3, telefon: 3, email: 3, il: 3, ilce: 3, aciklama: 3, captcha: 4, kvkk: 4 };
  var prodRadios = document.querySelectorAll('input[name="urun"]');
  var tipRadiosAll = document.querySelectorAll('input[name="tip"]');
  function setStep(n) {
    document.querySelectorAll('.mz-step-item').forEach(function (s) {
      s.classList.toggle('active', parseInt(s.dataset.step, 10) <= n);
    });
  }
  document.querySelectorAll('input,textarea,select').forEach(function (el) {
    el.addEventListener('focus', function () {
      var s = stepMap[el.name];
      if (s) setStep(s);
    });
  });
  tipRadiosAll.forEach(function (r) { r.addEventListener('change', function () { setStep(2); }); });
  prodRadios.forEach(function (r) { r.addEventListener('change', function () { setStep(3); }); });
})();
</script>

<?php require MIZAN_INC . '/footer.php';
