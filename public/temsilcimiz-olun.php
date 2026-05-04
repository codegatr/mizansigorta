<?php
if (!defined('MIZAN_BOOT')) { http_response_code(403); exit; }

$err = ''; $ok = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check((string)($_POST[CSRF_TOKEN_NAME] ?? ''))) {
        $err = 'Güvenlik doğrulaması geçersiz. Sayfayı yenileyip tekrar deneyin.';
    } else {
        $ad      = trim((string)($_POST['ad_soyad'] ?? ''));
        $email   = strtolower(trim((string)($_POST['email'] ?? '')));
        $tel     = normalize_phone((string)($_POST['telefon'] ?? ''));
        $il      = trim((string)($_POST['il'] ?? ''));
        $ilce    = trim((string)($_POST['ilce'] ?? ''));
        $firma   = trim((string)($_POST['firma_adi'] ?? ''));
        $tecrube = (int)($_POST['tecrube_yili'] ?? 0);
        $mevcut  = trim((string)($_POST['mevcut_acentelik'] ?? ''));
        $levha   = trim((string)($_POST['levha_no'] ?? ''));
        $aciklama = trim((string)($_POST['aciklama'] ?? ''));
        $kvkk    = isset($_POST['kvkk']) ? 1 : 0;

        // Math captcha
        $captchaOk = ((int)($_POST['cap_input'] ?? -1) === (int)($_POST['cap_correct'] ?? -2));

        if (!$ad || !$email || !$tel) {
            $err = 'Ad soyad, e-posta ve telefon zorunlu.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $err = 'Geçerli bir e-posta adresi giriniz.';
        } elseif (!$kvkk) {
            $err = 'KVKK Aydınlatma Metnini onaylayın.';
        } elseif (!$captchaOk) {
            $err = 'Doğrulama yanıtı hatalı.';
        } else {
            db_exec(
                'INSERT INTO ' . t('bayi_basvurulari') . ' (ad_soyad,firma_adi,email,telefon,il,ilce,tecrube_yili,mevcut_acentelik,levha_no,aciklama,kvkk_onay,ip_adresi,olusturma_tarihi) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW())',
                [$ad, $firma, $email, $tel, $il, $ilce, $tecrube, $mevcut, $levha, $aciklama, $kvkk, client_ip()]
            );

            // Bilgilendirme e-postasi (admin'e)
            $adminMail = setting('email');
            if ($adminMail) {
                $govde = '<h3>Yeni Temsilci Başvurusu</h3>'
                       . '<p><b>Ad Soyad:</b> ' . e($ad) . '</p>'
                       . ($firma ? '<p><b>Firma:</b> ' . e($firma) . '</p>' : '')
                       . '<p><b>E-posta:</b> ' . e($email) . '</p>'
                       . '<p><b>Telefon:</b> ' . e($tel) . '</p>'
                       . '<p><b>Şehir:</b> ' . e($il . ($ilce ? ' / ' . $ilce : '')) . '</p>'
                       . '<p><b>Tecrübe:</b> ' . (int)$tecrube . ' yıl</p>'
                       . ($mevcut ? '<p><b>Mevcut Acentelik:</b> ' . e($mevcut) . '</p>' : '')
                       . ($levha ? '<p><b>Levha No:</b> ' . e($levha) . '</p>' : '')
                       . ($aciklama ? '<p><b>Açıklama:</b><br>' . nl2br(e($aciklama)) . '</p>' : '')
                       . '<hr><p>Yönetim panelinden başvuruyu inceleyebilirsiniz.</p>';
                send_mail($adminMail, 'Mizan Sigorta', 'Yeni Temsilci Başvurusu — ' . $ad, mail_template('Temsilci Başvurusu', $govde));
            }

            // Basvuru sahibine teyit
            send_mail($email, $ad, 'Başvurunuz alındı — Mizan Sigorta',
                mail_template('Başvurunuz alındı',
                    '<p>Sayın <b>' . e($ad) . '</b>,</p>'
                  . '<p>Mizan Sigorta temsilciliği için başvurunuzu aldık. Yetkili ekibimiz başvurunuzu inceleyerek en kısa sürede sizinle iletişime geçecek.</p>'
                  . '<p>İlginiz için teşekkür ederiz.</p>'
                  . '<p style="font-style:italic">Güven ve Özen İle<br><b>Mizan Sigorta</b></p>'));

            $ok = true;
        }
    }
}

$pageTitle = 'Temsilcimiz Olun — ' . SITE_NAME;
$pageDesc  = 'Mizan Sigorta temsilciliği için başvuru formu. Sigortacılık tecrübenizi bizim gücümüzle birleştirin.';
require MIZAN_INC . '/header.php';
?>

<section class="mz-page-head">
  <div class="container">
    <span class="mz-script mz-script-md mz-script-red d-inline-block mb-1">Bize Katılın</span>
    <h1 class="display-5">Mizan Sigorta Temsilcisi Olun</h1>
    <nav><ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= u('/') ?>">Anasayfa</a></li>
      <li class="breadcrumb-item active">Temsilcimiz Olun</li>
    </ol></nav>
  </div>
</section>

<section class="mz-band">
  <div class="container">

    <?php if ($ok): ?>
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="card border-0 shadow-sm text-center p-5">
            <div class="mb-3"><i class="bi bi-check-circle-fill text-success" style="font-size:4rem"></i></div>
            <h2 class="fw-bold">Başvurunuz alındı!</h2>
            <p class="text-muted lead">Yetkili ekibimiz başvurunuzu inceleyerek en kısa sürede sizinle iletişime geçecek.</p>
            <p class="mz-script mz-script-md mz-script-red mb-3">Güven ve Özen İle</p>
            <div><a href="<?= u('/') ?>" class="btn btn-warning"><i class="bi bi-house"></i> Anasayfaya Dön</a></div>
          </div>
        </div>
      </div>
    <?php else: ?>

    <div class="row g-4">
      <!-- Sol: Avantajlar -->
      <div class="col-lg-5">
        <h3 class="fw-bold mb-3">Neden Mizan ile yan yana?</h3>
        <p class="text-muted mb-4">Mizan ailesine katılarak, sigortacılık deneyiminizi 4 şehirde faaliyet gösteren güçlü bir markanın çatısı altında değerlendirebilirsiniz.</p>

        <div class="d-flex gap-3 mb-3">
          <div class="mz-prod-icon" style="width:48px;height:48px;font-size:1.25rem"><i class="bi bi-buildings"></i></div>
          <div>
            <h6 class="fw-bold mb-1">Kurumsal Altyapı</h6>
            <p class="text-muted small mb-0">12+ sigorta şirketiyle anlaşmalı, lisanslı ve levhalı.</p>
          </div>
        </div>
        <div class="d-flex gap-3 mb-3">
          <div class="mz-prod-icon" style="width:48px;height:48px;font-size:1.25rem"><i class="bi bi-graph-up-arrow"></i></div>
          <div>
            <h6 class="fw-bold mb-1">Geniş Ürün Yelpazesi</h6>
            <p class="text-muted small mb-0">9 ana kategoride 35+ sigorta ürünü, kurumsal ve bireysel.</p>
          </div>
        </div>
        <div class="d-flex gap-3 mb-3">
          <div class="mz-prod-icon" style="width:48px;height:48px;font-size:1.25rem"><i class="bi bi-headset"></i></div>
          <div>
            <h6 class="fw-bold mb-1">7/24 Operasyon Desteği</h6>
            <p class="text-muted small mb-0">Hasar takip, teklif desteği ve teknik altyapı sürekli yanınızda.</p>
          </div>
        </div>
        <div class="d-flex gap-3 mb-4">
          <div class="mz-prod-icon" style="width:48px;height:48px;font-size:1.25rem"><i class="bi bi-award"></i></div>
          <div>
            <h6 class="fw-bold mb-1">Marka Gücü</h6>
            <p class="text-muted small mb-0">İstanbul, Konya, Ankara ve Aksaray'da tanınan kurumsal kimlik.</p>
          </div>
        </div>

        <div class="alert alert-warning small">
          <b><i class="bi bi-info-circle"></i> Önbilgi:</b> Sigorta acentelik mevzuatı gereği başvurunuzun değerlendirilmesi için sigortacılık tecrübeniz ve mevcut levha kaydınızın olması tercih sebebidir.
        </div>
      </div>

      <!-- Sag: Form -->
      <div class="col-lg-7">
        <div class="mz-form-card">
          <h4 class="fw-bold mb-3"><i class="bi bi-person-plus text-warning"></i> Başvuru Formu</h4>

          <?php if ($err): ?><div class="alert alert-danger small"><i class="bi bi-exclamation-triangle"></i> <?= e($err) ?></div><?php endif; ?>

          <form method="post" novalidate data-mz-captcha>
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e(csrf_token()) ?>">

            <div class="row g-3">
              <div class="col-md-6"><label class="form-label small fw-semibold">Ad Soyad *</label><input type="text" name="ad_soyad" required class="form-control" value="<?= e($_POST['ad_soyad'] ?? '') ?>"></div>
              <div class="col-md-6"><label class="form-label small fw-semibold">Firma Adı (varsa)</label><input type="text" name="firma_adi" class="form-control" value="<?= e($_POST['firma_adi'] ?? '') ?>"></div>
              <div class="col-md-6"><label class="form-label small fw-semibold">E-posta *</label><input type="email" name="email" required class="form-control" value="<?= e($_POST['email'] ?? '') ?>"></div>
              <div class="col-md-6"><label class="form-label small fw-semibold">Cep Telefonu *</label><input type="tel" name="telefon" required class="form-control" placeholder="0XXX XXX XX XX" value="<?= e($_POST['telefon'] ?? '') ?>"></div>

              <div class="col-md-6"><label class="form-label small fw-semibold">İl</label><input type="text" name="il" class="form-control" placeholder="Örn: Konya" value="<?= e($_POST['il'] ?? '') ?>"></div>
              <div class="col-md-6"><label class="form-label small fw-semibold">İlçe</label><input type="text" name="ilce" class="form-control" value="<?= e($_POST['ilce'] ?? '') ?>"></div>

              <div class="col-md-6"><label class="form-label small fw-semibold">Sigortacılık Tecrübesi (yıl)</label><input type="number" name="tecrube_yili" min="0" max="60" class="form-control" value="<?= e($_POST['tecrube_yili'] ?? '0') ?>"></div>
              <div class="col-md-6"><label class="form-label small fw-semibold">SBM Levha No (varsa)</label><input type="text" name="levha_no" class="form-control" value="<?= e($_POST['levha_no'] ?? '') ?>"></div>

              <div class="col-12"><label class="form-label small fw-semibold">Mevcut Acentelik / Çalıştığınız Şirketler</label><input type="text" name="mevcut_acentelik" class="form-control" placeholder="Allianz, Anadolu, Türkiye Sigorta..." value="<?= e($_POST['mevcut_acentelik'] ?? '') ?>"></div>

              <div class="col-12"><label class="form-label small fw-semibold">Açıklama / Mesajınız</label><textarea name="aciklama" rows="4" class="form-control" placeholder="Kendinizden ve hedeflerinizden bahsedin..."><?= e($_POST['aciklama'] ?? '') ?></textarea></div>

              <!-- Math captcha -->
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Doğrulama: <span data-mz-cap-a></span> + <span data-mz-cap-b></span> = ?</label>
                <input type="number" name="cap_input" required class="form-control" data-mz-cap-input>
                <input type="hidden" name="cap_correct" data-mz-cap-correct>
              </div>

              <div class="col-12">
                <div class="form-check">
                  <input type="checkbox" name="kvkk" id="kvkk" class="form-check-input" required <?= !empty($_POST['kvkk'])?'checked':'' ?>>
                  <label for="kvkk" class="form-check-label small">
                    <a href="<?= u('/sayfa/kvkk') ?>" target="_blank">KVKK Aydınlatma Metni</a>'ni okudum, kişisel verilerimin başvuru sürecinde işlenmesine rıza gösteriyorum. *
                  </label>
                </div>
              </div>

              <div class="col-12 d-grid">
                <button class="btn btn-warning btn-lg fw-semibold"><i class="bi bi-send"></i> Başvuruyu Gönder</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

    <?php endif; ?>

  </div>
</section>

<?php require MIZAN_INC . '/footer.php';
