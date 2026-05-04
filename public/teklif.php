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

        // Operatore bildirim (panel + email)
        $opMail = setting('teklif_bildirim_email');
        if ($opMail) {
            $body = mail_template('Yeni teklif geldi', '
                <h2 style="margin-top:0">Yeni Teklif: #' . e($no) . '</h2>
                <p><strong>Ürün:</strong> ' . e($urun['baslik']) . '</p>
                <p><strong>Ad Soyad:</strong> ' . e($ad) . '</p>
                <p><strong>Telefon:</strong> ' . e($tel) . '</p>
                <p><strong>E-posta:</strong> ' . e($email) . '</p>
                <p><strong>İl/İlçe:</strong> ' . e($il . ' / ' . $ilce) . '</p>
                <p><strong>Açıklama:</strong> ' . nl2br(e($aciklama)) . '</p>
                <p><a href="' . u('/yonetim/teklif-detay.php?id=' . $teklifId) . '" style="display:inline-block;background:#0d1b2a;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none">Panelde aç</a></p>
            ');
            send_mail($opMail, 'Yeni teklif: ' . $no, $body);
        }
        // Musteriye otomatik tesekkur
        if ($email !== '') {
            $body = mail_template('Teklif talebiniz alındı', '
                <h2 style="margin-top:0">Merhaba ' . e($ad) . ',</h2>
                <p>' . e($urun['baslik']) . ' için teklif talebiniz başarıyla iletildi.</p>
                <p><strong>Teklif No:</strong> ' . e($no) . '</p>
                <p>Uzman ekibimiz en kısa sürede sizinle iletişime geçecek ve size en uygun teklifi sunacaktır.</p>
                <p>Teşekkür ederiz.<br><strong>' . e(setting('firma_adi', SITE_NAME)) . '</strong></p>
            ');
            send_mail($email, 'Teklif talebiniz alındı - ' . $no, $body);
        }

        // Tesekkur sayfasina yonlendir (PRG)
        safe_redirect('/teklif-tesekkur?no=' . urlencode($no));
    }
}

$captchaA = random_int(2, 9);
$captchaB = random_int(2, 9);

$pageTitle = 'Online Teklif Al - ' . setting('firma_adi', SITE_NAME);
require MIZAN_INC . '/header.php';
?>

<section class="mz-page-head">
  <div class="container py-4">
    <h1 class="fw-bold mb-1">Online Teklif Al</h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= u('/') ?>">Anasayfa</a></li>
      <li class="breadcrumb-item active">Teklif Al</li>
    </ol></nav>
  </div>
</section>

<section class="container py-5">
  <div class="row g-4">
    <div class="col-lg-8">
      <?php if ($errors): ?>
        <div class="alert alert-danger"><strong>Lütfen aşağıdaki hataları düzeltin:</strong>
          <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>

      <form method="post" class="mz-form-card" novalidate>
        <?= csrf_field() ?>
        <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">

        <h5 class="fw-bold mb-3">1. Müşteri Tipi</h5>
        <div class="btn-group w-100 mb-4" role="group">
          <input type="radio" class="btn-check" name="tip" id="tipB" value="bireysel" checked>
          <label class="btn btn-outline-primary" for="tipB"><i class="bi bi-person"></i> Bireysel</label>
          <input type="radio" class="btn-check" name="tip" id="tipK" value="kurumsal">
          <label class="btn btn-outline-primary" for="tipK"><i class="bi bi-building"></i> Kurumsal</label>
        </div>

        <h5 class="fw-bold mb-3">2. Sigorta Türü</h5>
        <div class="row g-2 mb-4">
          <?php foreach ($urunler as $u): ?>
            <div class="col-6 col-md-4">
              <input type="radio" class="btn-check mz-prod-radio" name="urun" id="u_<?= e($u['slug']) ?>" value="<?= e($u['slug']) ?>"
                <?= $onSecili === $u['slug'] ? 'checked' : '' ?>>
              <label class="btn w-100 mz-prod-btn text-start" for="u_<?= e($u['slug']) ?>">
                <i class="bi bi-<?= e($u['icon'] ?: 'shield-check') ?>"></i>
                <span><?= e($u['baslik']) ?></span>
              </label>
            </div>
          <?php endforeach; ?>
        </div>

        <h5 class="fw-bold mb-3">3. İletişim Bilgileri</h5>
        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label class="form-label">Ad Soyad *</label>
            <input type="text" name="ad_soyad" class="form-control" value="<?= e($_POST['ad_soyad'] ?? '') ?>" required>
          </div>
          <div class="col-md-6 mz-firma-field" style="display:none">
            <label class="form-label">Firma Adı</label>
            <input type="text" name="firma_adi" class="form-control" value="<?= e($_POST['firma_adi'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Telefon *</label>
            <input type="tel" name="telefon" class="form-control" value="<?= e($onTel ?: ($_POST['telefon'] ?? '')) ?>" placeholder="0 5xx xxx xx xx" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">E-posta</label>
            <input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">İl</label>
            <input type="text" name="il" class="form-control" value="<?= e($_POST['il'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">İlçe</label>
            <input type="text" name="ilce" class="form-control" value="<?= e($_POST['ilce'] ?? '') ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Ek Açıklama / Talep</label>
            <textarea name="aciklama" class="form-control" rows="3" placeholder="Aracınızın model yılı, evinizin metrekaresi gibi detayları yazabilirsiniz."><?= e($_POST['aciklama'] ?? '') ?></textarea>
          </div>
        </div>

        <h5 class="fw-bold mb-3">4. Doğrulama</h5>
        <div class="row g-3 align-items-end mb-3">
          <div class="col-md-4">
            <label class="form-label"><?= $captchaA ?> + <?= $captchaB ?> = ?</label>
            <input type="number" name="captcha" class="form-control" required>
            <input type="hidden" name="captcha_a" value="<?= $captchaA ?>">
            <input type="hidden" name="captcha_b" value="<?= $captchaB ?>">
          </div>
          <div class="col-md-8">
            <div class="form-check">
              <input type="checkbox" name="kvkk" id="kvkk" class="form-check-input" required>
              <label class="form-check-label" for="kvkk">
                <a href="<?= u('/sayfa/kvkk') ?>" target="_blank">KVKK Aydınlatma Metni</a>'ni okudum, onaylıyorum.
              </label>
            </div>
          </div>
        </div>

        <button class="btn btn-warning btn-lg w-100 fw-semibold"><i class="bi bi-send"></i> Teklif Talebimi Gönder</button>
      </form>
    </div>

    <div class="col-lg-4">
      <div class="mz-side-card">
        <h6 class="fw-bold"><i class="bi bi-info-circle text-warning"></i> Nasıl çalışır?</h6>
        <ol class="small mb-0">
          <li>Talebinizi formdan iletin.</li>
          <li>Uzmanımız 12+ şirketten karşılaştırma yapar.</li>
          <li>Size en uygun teklifi e-posta veya telefonla sunar.</li>
          <li>Onayınızla poliçeniz hazırlanır.</li>
        </ol>
      </div>
      <div class="mz-side-card mt-3">
        <h6 class="fw-bold"><i class="bi bi-shield-lock text-warning"></i> Güvende misiniz?</h6>
        <p class="small mb-0">Bilgileriniz KVKK kapsamında, yalnızca teklif sürecinde kullanılır. Üçüncü taraflarla pazarlama amacıyla paylaşılmaz.</p>
      </div>
    </div>
  </div>
</section>

<script>
document.querySelectorAll('input[name="tip"]').forEach(r => r.addEventListener('change', e => {
  document.querySelector('.mz-firma-field').style.display = e.target.value === 'kurumsal' ? '' : 'none';
}));
</script>

<?php require MIZAN_INC . '/footer.php';
