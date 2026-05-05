<?php
if (!defined('MIZAN_BOOT')) { http_response_code(403); exit; }

$urunler = db_all('SELECT id, baslik FROM ' . t('urunler') . ' WHERE aktif=1 ORDER BY sira');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_assert_post();
    $ad   = trim((string) ($_POST['ad_soyad'] ?? ''));
    $tel  = trim((string) ($_POST['telefon'] ?? ''));
    $email= trim((string) ($_POST['email'] ?? ''));
    $urun = (int) ($_POST['urun_id'] ?? 0);
    $tarih= $_POST['olay_tarihi'] ?? null;
    $yer  = trim((string) ($_POST['olay_yeri'] ?? ''));
    $acik = trim((string) ($_POST['olay_aciklama'] ?? ''));
    $kvkk = !empty($_POST['kvkk']);
    $captcha = (int) ($_POST['captcha'] ?? 0);
    $captchaA= (int) ($_POST['captcha_a'] ?? 0);
    $captchaB= (int) ($_POST['captcha_b'] ?? 0);

    if ($ad === '') $errors[] = 'Ad soyad zorunlu.';
    if ($tel === '') $errors[] = 'Telefon zorunlu.';
    if ($acik === '') $errors[] = 'Olay açıklaması zorunlu.';
    if (!$kvkk) $errors[] = 'KVKK onayı gerekli.';
    if ($captcha !== ($captchaA + $captchaB)) $errors[] = 'Doğrulama yanlış.';
    if (!empty($_POST['website'])) $errors[] = 'Spam tespit edildi.';

    if (!$errors) {
        $no = generate_no(setting('hasar_otomatik_no', 'HSR'));
        db_exec(
            'INSERT INTO ' . t('hasarlar') . '
             (dosya_no, urun_id, ad_soyad, email, telefon, olay_tarihi, olay_yeri, olay_aciklama, kvkk_onay, ip_adresi)
             VALUES (?,?,?,?,?,?,?,?,1,?)',
            [$no, $urun ?: null, $ad, $email ?: null, $tel,
             $tarih ?: null, $yer ?: null, $acik, client_ip()]
        );
        $hasarId = db_last_id();

        // Dosya yukleme (max 5)
        if (!empty($_FILES['ekler']['name'][0])) {
            $dir = MIZAN_UPLOADS . '/hasar/' . date('Y/m');
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            $allowed = ['jpg','jpeg','png','webp','pdf'];
            $cnt = count($_FILES['ekler']['name']);
            for ($i = 0; $i < $cnt && $i < 5; $i++) {
                if ($_FILES['ekler']['error'][$i] !== UPLOAD_ERR_OK) continue;
                if ($_FILES['ekler']['size'][$i] > 5 * 1024 * 1024) continue;
                $ext = strtolower(pathinfo($_FILES['ekler']['name'][$i], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed, true)) continue;
                $name = bin2hex(random_bytes(6)) . '.' . $ext;
                $rel  = 'hasar/' . date('Y/m') . '/' . $name;
                if (move_uploaded_file($_FILES['ekler']['tmp_name'][$i], $dir . '/' . $name)) {
                    db_exec('INSERT INTO ' . t('hasar_ekleri') .
                        ' (hasar_id, dosya_yolu, dosya_adi, boyut, mime) VALUES (?,?,?,?,?)',
                        [$hasarId, $rel, basename($_FILES['ekler']['name'][$i]),
                         (int) $_FILES['ekler']['size'][$i],
                         (string) $_FILES['ekler']['type'][$i]]);
                }
            }
        }

        $opMail = setting('teklif_bildirim_email') ?: setting('email');
        $extra  = talep_bildirim_alicilari();
        if ($opMail) {
            $infoTable = '<table cellpadding="0" cellspacing="0" style="width:100%;background:#fff5f5;border-left:4px solid #e30b30;border-radius:8px;margin:16px 0">'
                . '<tr><td style="padding:12px 18px;border-bottom:1px solid #fecaca;width:140px;color:#6b7280;font-size:13px">İhbar No</td><td style="padding:12px 18px;border-bottom:1px solid #fecaca;font-weight:600">' . e($no) . '</td></tr>'
                . '<tr><td style="padding:12px 18px;border-bottom:1px solid #fecaca;color:#6b7280;font-size:13px">Ad Soyad</td><td style="padding:12px 18px;border-bottom:1px solid #fecaca;font-weight:600">' . e($ad) . '</td></tr>'
                . '<tr><td style="padding:12px 18px;border-bottom:1px solid #fecaca;color:#6b7280;font-size:13px">Telefon</td><td style="padding:12px 18px;border-bottom:1px solid #fecaca"><a href="tel:' . preg_replace('/\s+/', '', $tel) . '" style="color:#e30b30;text-decoration:none;font-weight:700">' . e($tel) . '</a></td></tr>';
            if ($email !== '') {
                $infoTable .= '<tr><td style="padding:12px 18px;border-bottom:1px solid #fecaca;color:#6b7280;font-size:13px">E-posta</td><td style="padding:12px 18px;border-bottom:1px solid #fecaca">' . e($email) . '</td></tr>';
            }
            $infoTable .= '<tr><td style="padding:12px 18px;border-bottom:1px solid #fecaca;color:#6b7280;font-size:13px">Olay Tarihi</td><td style="padding:12px 18px;border-bottom:1px solid #fecaca">' . e($tarih ?: '-') . '</td></tr>'
                . '<tr><td style="padding:12px 18px;border-bottom:1px solid #fecaca;color:#6b7280;font-size:13px">Olay Yeri</td><td style="padding:12px 18px;border-bottom:1px solid #fecaca">' . e($yer) . '</td></tr>'
                . '<tr><td style="padding:12px 18px;color:#6b7280;font-size:13px;vertical-align:top">Açıklama</td><td style="padding:12px 18px;line-height:1.6">' . nl2br(e($acik)) . '</td></tr>'
                . '</table>';

            $bodyHtml = '<h2 style="margin:0 0 8px;color:#e30b30;font-size:22px">⚠ Hasar İhbarı Geldi</h2>'
                      . '<p style="color:#6b7280;margin:0 0 16px">Web sitesinden ' . date('d.m.Y H:i') . ' tarihinde gelen hasar ihbarı. Müşteriye ivedi dönüş yapılması önerilir.</p>'
                      . $infoTable;
            $html = mail_template('Hasar İhbarı: ' . $no, $bodyHtml, [
                'badge'       => 'HASAR İHBARI',
                'badge_color' => '#e30b30',
                'preheader'   => 'Yeni hasar ihbari: ' . $ad . ' - ' . $no,
                'cta_text'    => 'Panelde Aç',
                'cta_url'     => u('/yonetim/hasarlar.php?id=' . $hasarId),
            ]);
            send_mail($opMail, 'Yeni hasar ihbarı: ' . $no, $html, '', ['bcc' => $extra['bcc']]);
        }

        safe_redirect('/hasar-ihbari?ok=1&no=' . urlencode($no));
    }
}

$captchaA = random_int(2, 9);
$captchaB = random_int(2, 9);

$pageTitle = 'Hasar İhbarı — Online Form, 7/24 Destek | ' . setting('firma_adi', SITE_NAME);
$pageDesc  = 'Hasar ihbarınızı online iletin: oto, konut, sağlık veya iş yeri. Eksper takibi, belge süreci ve ödeme — tüm süreci biz yönetiyoruz. 7/24 hasar destek hattı.';
$pageBreadcrumbs = [
    ['name' => 'Anasayfa', 'url' => '/'],
    ['name' => 'Hasar İhbarı', 'url' => '/hasar-ihbari'],
];
require MIZAN_INC . '/header.php';
?>

<section class="mz-page-head" style="background:linear-gradient(135deg,#7f1d1d 0%, var(--mz-navy) 100%)">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
      <div>
        <h1 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill text-warning"></i> Hasar İhbarı</h1>
        <p class="mb-0 small" style="color:rgba(255,255,255,.85)">Hasar yaşadıysanız endişelenmeyin — süreç bizimle.</p>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
          <li class="breadcrumb-item"><a href="<?= u('/') ?>">Anasayfa</a></li>
          <li class="breadcrumb-item active">Hasar İhbarı</li>
        </ol></nav>
      </div>
      <div class="d-none d-md-flex flex-wrap gap-2">
        <span class="mz-trust-badge"><i class="bi bi-clock-history"></i> 7/24 Destek</span>
        <span class="mz-trust-badge"><i class="bi bi-people"></i> Uzman Eksper</span>
      </div>
    </div>
  </div>
</section>

<!-- Acil arama bandı -->
<?php if ($sitTel = setting('telefon')): ?>
<section style="background:linear-gradient(90deg,#7f1d1d,#991b1b);color:#fff;padding:1.25rem 0">
  <div class="container">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;border-radius:50%;background:#fff;color:#991b1b;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;animation:pulse 2s infinite">
          <i class="bi bi-telephone-fill"></i>
        </div>
        <div>
          <small style="display:block;text-transform:uppercase;letter-spacing:.1em;opacity:.85">Acil hasar mı yaşadınız?</small>
          <strong style="font-size:1.15rem">7/24 hasar destek hattımızı arayın</strong>
        </div>
      </div>
      <a href="tel:<?= e(preg_replace('/\s+/','',$sitTel)) ?>" class="btn btn-warning btn-lg fw-bold px-4">
        <i class="bi bi-telephone-fill"></i> <?= e($sitTel) ?>
      </a>
    </div>
  </div>
</section>
<style>
@keyframes pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(255,255,255,.5); }
  50% { box-shadow: 0 0 0 12px rgba(255,255,255,0); }
}
</style>
<?php endif; ?>

<!-- Hasar Rehberi: hepiyi.com.tr/hasar-islemleri tarzi yonlendirmeler -->
<section class="container py-5">
  <div class="text-center mb-4">
    <span class="mz-trust-badge mb-2"><i class="bi bi-compass"></i> Hasar Rehberi</span>
    <h2 class="fw-bold" style="color:var(--mz-navy)">Hasar anında ne yapmalısınız?</h2>
    <p class="text-muted">Adım adım rehberler, gerekli belgeler ve doğrudan hasar hatları</p>
  </div>

  <div class="row g-3">
    <div class="col-md-6 col-lg-4">
      <a href="<?= u('/sayfa/hasar-anlik-rehber') ?>" class="mz-hasar-rehber-card">
        <div class="mz-hasar-rehber-icon"><i class="bi bi-exclamation-circle-fill"></i></div>
        <h6 class="fw-bold" style="color:var(--mz-navy)">Hasar anında ne yapmalısınız?</h6>
        <p class="small text-muted mb-2">Trafik kazası, yangın, sel — anlık adımlar ve hayati öneriler.</p>
        <span class="small fw-semibold" style="color:var(--mz-red)">Rehberi oku <i class="bi bi-arrow-right"></i></span>
      </a>
    </div>
    <div class="col-md-6 col-lg-4">
      <a href="<?= u('/sayfa/kaza-tespit-tutanagi') ?>" class="mz-hasar-rehber-card">
        <div class="mz-hasar-rehber-icon" style="background:linear-gradient(135deg,#0d1b2a,#1b263b)"><i class="bi bi-clipboard-data-fill"></i></div>
        <h6 class="fw-bold" style="color:var(--mz-navy)">Kaza tespit tutanağı</h6>
        <p class="small text-muted mb-2">Anlaşmalı tutanak nedir, nasıl doldurulur, dikkat edilmesi gerekenler.</p>
        <span class="small fw-semibold" style="color:var(--mz-red)">Detaylar <i class="bi bi-arrow-right"></i></span>
      </a>
    </div>
    <div class="col-md-6 col-lg-4">
      <a href="https://www.sbm.org.tr/tr/mobil-kaza-tutanagi-mkt" target="_blank" rel="noopener" class="mz-hasar-rehber-card">
        <div class="mz-hasar-rehber-icon" style="background:linear-gradient(135deg,#198754,#157347)"><i class="bi bi-phone-fill"></i></div>
        <h6 class="fw-bold" style="color:var(--mz-navy)">Mobil kaza uygulaması <i class="bi bi-box-arrow-up-right small"></i></h6>
        <p class="small text-muted mb-2">SBM Mobil Kaza Tutanağı (MKT) uygulaması — telefonla anlaşmalı tutanak.</p>
        <span class="small fw-semibold" style="color:var(--mz-red)">SBM sayfasına git <i class="bi bi-arrow-right"></i></span>
      </a>
    </div>
    <div class="col-md-6 col-lg-4">
      <a href="<?= u('/sayfa/deger-kaybi-basvurusu') ?>" class="mz-hasar-rehber-card">
        <div class="mz-hasar-rehber-icon" style="background:linear-gradient(135deg,#ffc107,#fd7e14)"><i class="bi bi-cash-coin"></i></div>
        <h6 class="fw-bold" style="color:var(--mz-navy)">Değer kaybı başvurusu</h6>
        <p class="small text-muted mb-2">Kazaya karışan aracınızın değer kaybı için gerekli evraklar ve süreç.</p>
        <span class="small fw-semibold" style="color:var(--mz-red)">Belgeleri gör <i class="bi bi-arrow-right"></i></span>
      </a>
    </div>
    <div class="col-md-6 col-lg-4">
      <a href="<?= u('/sayfa/anlasmali-saglik-kurumlari') ?>" class="mz-hasar-rehber-card">
        <div class="mz-hasar-rehber-icon" style="background:linear-gradient(135deg,#0d6efd,#0b5ed7)"><i class="bi bi-hospital-fill"></i></div>
        <h6 class="fw-bold" style="color:var(--mz-navy)">Anlaşmalı sağlık kurumları</h6>
        <p class="small text-muted mb-2">Sağlık tazminat sürecinde geçerli özel hastaneler ve klinikler.</p>
        <span class="small fw-semibold" style="color:var(--mz-red)">Listeyi görüntüle <i class="bi bi-arrow-right"></i></span>
      </a>
    </div>
    <div class="col-md-6 col-lg-4">
      <a href="<?= u('/iletisim') ?>" class="mz-hasar-rehber-card">
        <div class="mz-hasar-rehber-icon" style="background:linear-gradient(135deg,#6610f2,#6f42c1)"><i class="bi bi-headset"></i></div>
        <h6 class="fw-bold" style="color:var(--mz-navy)">Hasar iletişim</h6>
        <p class="small text-muted mb-2">Doğrudan hasar uzmanlarımızla iletişim — telefon, e-posta, WhatsApp.</p>
        <span class="small fw-semibold" style="color:var(--mz-red)">İletişim kanalları <i class="bi bi-arrow-right"></i></span>
      </a>
    </div>
  </div>
</section>

<!-- 4 adim sureci -->
<section class="container py-5">
  <div class="text-center mb-4">
    <span class="mz-trust-badge mb-2"><i class="bi bi-list-check"></i> Süreç</span>
    <h2 class="fw-bold" style="color:var(--mz-navy)">Hasar süreci nasıl ilerler?</h2>
    <p class="text-muted">İhbarınızdan tazminat ödemesine 4 adım</p>
  </div>
  <div class="row g-3">
    <?php
    $steps = [
      ['1', 'bi-clipboard-check', 'İhbar Alımı', 'Formu doldurun veya bizi arayın. Dosya numaranızı SMS/e-posta ile alacaksınız.'],
      ['2', 'bi-person-badge', 'Eksper Atanması', 'Sigorta şirketi bağımsız eksper atar. Sizinle iletişime geçer ve gerekirse hasar yerine gelir.'],
      ['3', 'bi-file-earmark-text', 'Belge Toplama', 'Eksper raporu, fatura, fotoğraf gibi gerekli belgeler bizimle toplanır. Süreci sizin yerinize takip ederiz.'],
      ['4', 'bi-cash-stack', 'Ödeme', 'Onay sonrası tazminat hesabınıza yatırılır. Ortalama süreç: kasko 7-15 gün, konut 15-30 gün.'],
    ];
    foreach ($steps as $s): ?>
      <div class="col-sm-6 col-lg-3">
        <div style="background:#fff;border:1px solid var(--mz-border);border-radius:14px;padding:1.5rem;height:100%;position:relative;transition:all .2s" onmouseover="this.style.borderColor='var(--mz-red)';this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 30px rgba(0,0,0,.08)'" onmouseout="this.style.borderColor='var(--mz-border)';this.style.transform='';this.style.boxShadow=''">
          <span style="position:absolute;top:-14px;left:1.5rem;width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--mz-red),var(--mz-red-2));color:#fff;font-weight:800;font-size:.9rem;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 16px rgba(238,39,55,.35)"><?= $s[0] ?></span>
          <i class="bi <?= $s[1] ?>" style="font-size:1.6rem;color:var(--mz-red);display:block;margin-top:.5rem"></i>
          <h6 class="fw-bold mt-3 mb-1" style="color:var(--mz-navy)"><?= $s[2] ?></h6>
          <p class="small text-muted mb-0"><?= $s[3] ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="container pb-5">
  <div class="row g-4">
    <div class="col-lg-8">
      <?php if (!empty($_GET['ok'])): ?>
        <div class="alert alert-success d-flex gap-3 align-items-start">
          <i class="bi bi-check-circle-fill fs-3"></i>
          <div>
            <strong class="d-block mb-1">Hasar ihbarınız başarıyla alındı!</strong>
            <span class="small">Dosya numaranız: <strong class="text-warning"><?= e($_GET['no'] ?? '') ?></strong></span>
            <p class="small text-muted mb-0 mt-2">Bu numarayı saklayın. Eksper en kısa sürede sizinle iletişime geçecek. SMS ve e-posta ile bilgilendirileceksiniz.</p>
          </div>
        </div>
      <?php endif; ?>
      <?php if ($errors): ?>
        <div class="alert alert-danger d-flex gap-3 align-items-start">
          <i class="bi bi-exclamation-triangle-fill fs-4"></i>
          <div>
            <strong class="d-block mb-1">Lütfen aşağıdaki hataları düzeltin:</strong>
            <ul class="mb-0 small"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
          </div>
        </div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data" class="mz-form-card" novalidate>
        <?= csrf_field() ?>
        <input type="text" name="website" style="display:none">

        <h5 class="fw-bold mb-3"><i class="bi bi-person-fill text-warning"></i> Sizin Bilgileriniz</h5>
        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Ad Soyad <span class="text-danger">*</span></label>
            <div class="input-group"><span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
              <input type="text" name="ad_soyad" autocomplete="name" class="form-control" required placeholder="Adınız ve soyadınız">
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Telefon <span class="text-danger">*</span></label>
            <div class="input-group"><span class="input-group-text bg-light"><i class="bi bi-telephone"></i></span>
              <input type="tel" name="telefon" inputmode="tel" autocomplete="tel" class="form-control" required placeholder="0 5xx xxx xx xx">
            </div>
          </div>
          <div class="col-12">
            <label class="form-label small fw-semibold">E-posta <small class="text-muted fw-normal">(opsiyonel — bilgilendirme için)</small></label>
            <div class="input-group"><span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
              <input type="email" name="email" inputmode="email" autocomplete="email" class="form-control" placeholder="ornek@email.com">
            </div>
          </div>
        </div>

        <h5 class="fw-bold mb-3"><i class="bi bi-info-square-fill text-warning"></i> Hasar Bilgileri</h5>
        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Sigorta Türü</label>
            <select name="urun_id" class="form-select">
              <option value="">Seçiniz (bilmiyorsanız boş bırakın)</option>
              <?php foreach ($urunler as $u): ?>
                <option value="<?= (int) $u['id'] ?>"><?= e($u['baslik']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Olay Tarihi</label>
            <input type="date" name="olay_tarihi" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label small fw-semibold">Olay Yeri</label>
            <div class="input-group"><span class="input-group-text bg-light"><i class="bi bi-geo-alt"></i></span>
              <input type="text" name="olay_yeri" class="form-control" placeholder="Şehir, ilçe, mahalle / işyeri adresi">
            </div>
          </div>
          <div class="col-12">
            <label class="form-label small fw-semibold">Olay Açıklaması <span class="text-danger">*</span></label>
            <textarea name="olay_aciklama" class="form-control" rows="5" required placeholder="Olayı sakince anlatın: ne oldu, ne zaman, kim/ne hasar gördü, taraflar var mıydı, polis/jandarma çağrıldı mı vb."></textarea>
            <div class="form-text small"><i class="bi bi-info-circle"></i> Detaylı yazmanız sürecin hızlanmasına yardımcı olur.</div>
          </div>
          <div class="col-12">
            <label class="form-label small fw-semibold">Belge / Fotoğraf <small class="text-muted fw-normal">(max 5 dosya, her biri 5 MB)</small></label>
            <input type="file" name="ekler[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.webp,.pdf">
            <div class="form-text small"><i class="bi bi-info-circle"></i> Hasar fotoğrafları, kaza tutanağı, polis raporu, fatura — varsa eklemeniz değerlendirmeyi hızlandırır.</div>
          </div>
        </div>

        <div class="p-3 rounded mb-3" style="background:rgba(13,27,42,.04);border:1px dashed rgba(13,27,42,.15)">
          <div class="row g-3 align-items-center">
            <div class="col-md-5">
              <label class="form-label small fw-semibold mb-1">Doğrulama: <?= $captchaA ?> + <?= $captchaB ?> = ?</label>
              <input type="number" name="captcha" class="form-control form-control-sm" required placeholder="Sonuç">
              <input type="hidden" name="captcha_a" value="<?= $captchaA ?>">
              <input type="hidden" name="captcha_b" value="<?= $captchaB ?>">
            </div>
            <div class="col-md-7">
              <div class="form-check">
                <input type="checkbox" name="kvkk" id="hkvkk" class="form-check-input" required>
                <label class="form-check-label small" for="hkvkk"><a href="<?= u('/sayfa/kvkk') ?>" target="_blank">KVKK Aydınlatma Metni</a>'ni okudum, onaylıyorum. <span class="text-danger">*</span></label>
              </div>
            </div>
          </div>
        </div>

        <button class="btn btn-warning btn-lg w-100 fw-semibold py-3"><i class="bi bi-send-fill"></i> Hasar İhbarını Gönder</button>
        <p class="text-center small text-muted mt-3 mb-0">
          <i class="bi bi-shield-check"></i> Bilgileriniz KVKK kapsamında korunmaktadır · Sadece hasar süreci için kullanılır
        </p>
      </form>
    </div>

    <aside class="col-lg-4">
      <!-- Onemli bilgi -->
      <div class="card border-0 shadow-sm mb-3" style="background:#fff8e6;border-left:4px solid var(--mz-warning, #ffc107) !important">
        <div class="card-body">
          <h6 class="fw-bold mb-2"><i class="bi bi-info-circle-fill text-warning"></i> Hasar İhbarı İpuçları</h6>
          <ul class="small mb-0">
            <li class="mb-1"><strong>İlk 5 gün</strong> içinde ihbar etmeniz hak kaybınızı önler.</li>
            <li class="mb-1">Trafik kazası ise <strong>polis veya jandarma tutanağı</strong> mutlaka tutturun.</li>
            <li class="mb-1">Yangın/sel hasarı için itfaiye/AFAD raporu önemlidir.</li>
            <li class="mb-1">Hasar mahallini fotoğraflayın, eksiltmeden saklayın.</li>
            <li class="mb-0">Süreç boyunca aramalarımıza yanıt vermeniz hızlı sonuç sağlar.</li>
          </ul>
        </div>
      </div>

      <!-- 7/24 Hat -->
      <?php if ($sitTel): ?>
      <div class="mz-side-card text-center mb-3">
        <i class="bi bi-headset-vr" style="font-size:2rem;color:var(--mz-red)"></i>
        <h6 class="fw-bold mt-2 mb-1">7/24 Hasar Hattı</h6>
        <p class="small mb-3" style="opacity:.85">Acil durumlarda doğrudan arayın</p>
        <a href="tel:<?= e(preg_replace('/\s+/','',$sitTel)) ?>" class="btn btn-warning fw-bold w-100">
          <i class="bi bi-telephone-fill"></i> <?= e($sitTel) ?>
        </a>
      </div>
      <?php endif; ?>

      <!-- WhatsApp ek kanal -->
      <?php if ($wa = setting('whatsapp')): ?>
      <div class="card border-0 shadow-sm">
        <div class="card-body text-center">
          <i class="bi bi-whatsapp" style="font-size:2rem;color:#25D366"></i>
          <h6 class="fw-bold mt-2 mb-1">WhatsApp ile İhbar</h6>
          <p class="small text-muted mb-3">Hasar fotoğraflarını hızlıca gönderin</p>
          <a href="https://wa.me/<?= e($wa) ?>?text=<?= rawurlencode('Hasar ihbarı yapmak istiyorum.') ?>" target="_blank" class="btn fw-semibold w-100" style="background:#25D366;color:#fff">
            <i class="bi bi-whatsapp"></i> WhatsApp'tan Yaz
          </a>
        </div>
      </div>
      <?php endif; ?>
    </aside>
  </div>
</section>

<?php require MIZAN_INC . '/footer.php';
