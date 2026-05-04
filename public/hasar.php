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
        if ($opMail) {
            $body = mail_template('Yeni Hasar İhbarı', '
                <h2>Hasar İhbarı: ' . e($no) . '</h2>
                <p><strong>Ad Soyad:</strong> ' . e($ad) . '</p>
                <p><strong>Telefon:</strong> ' . e($tel) . '</p>
                <p><strong>E-posta:</strong> ' . e($email) . '</p>
                <p><strong>Olay tarihi:</strong> ' . e($tarih ?: '-') . '</p>
                <p><strong>Olay yeri:</strong> ' . e($yer) . '</p>
                <p><strong>Açıklama:</strong> ' . nl2br(e($acik)) . '</p>
                <p><a href="' . u('/yonetim/hasarlar.php?id=' . $hasarId) . '" style="background:#0d1b2a;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none">Panelde aç</a></p>
            ');
            send_mail($opMail, 'Yeni hasar ihbarı: ' . $no, $body);
        }

        safe_redirect('/hasar-ihbari?ok=1&no=' . urlencode($no));
    }
}

$captchaA = random_int(2, 9);
$captchaB = random_int(2, 9);

$pageTitle = 'Hasar İhbarı - ' . setting('firma_adi', SITE_NAME);
require MIZAN_INC . '/header.php';
?>

<section class="mz-page-head">
  <div class="container py-4">
    <h1 class="fw-bold mb-1">Hasar İhbarı</h1>
    <nav><ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= u('/') ?>">Anasayfa</a></li>
      <li class="breadcrumb-item active">Hasar İhbarı</li>
    </ol></nav>
  </div>
</section>

<section class="container py-5">
  <div class="row g-4">
    <div class="col-lg-8">
      <?php if (!empty($_GET['ok'])): ?>
        <div class="alert alert-success">
          Hasar ihbarınız başarıyla iletildi. Dosya numaranız: <strong><?= e($_GET['no'] ?? '') ?></strong>
        </div>
      <?php endif; ?>
      <?php if ($errors): ?>
        <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data" class="mz-form-card" novalidate>
        <?= csrf_field() ?>
        <input type="text" name="website" style="display:none">

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Ad Soyad *</label>
            <input type="text" name="ad_soyad" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Telefon *</label>
            <input type="tel" name="telefon" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">E-posta</label>
            <input type="email" name="email" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Sigorta Türü</label>
            <select name="urun_id" class="form-select">
              <option value="">Seçiniz</option>
              <?php foreach ($urunler as $u): ?>
                <option value="<?= (int) $u['id'] ?>"><?= e($u['baslik']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Olay Tarihi</label>
            <input type="date" name="olay_tarihi" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Olay Yeri</label>
            <input type="text" name="olay_yeri" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">Olay Açıklaması *</label>
            <textarea name="olay_aciklama" class="form-control" rows="5" required></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">Belge / Fotoğraf (max 5 dosya, 5MB)</label>
            <input type="file" name="ekler[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.webp,.pdf">
          </div>
          <div class="col-md-4">
            <label class="form-label"><?= $captchaA ?> + <?= $captchaB ?> = ?</label>
            <input type="number" name="captcha" class="form-control" required>
            <input type="hidden" name="captcha_a" value="<?= $captchaA ?>">
            <input type="hidden" name="captcha_b" value="<?= $captchaB ?>">
          </div>
          <div class="col-md-8 d-flex align-items-end">
            <div class="form-check">
              <input type="checkbox" name="kvkk" id="hkvkk" class="form-check-input" required>
              <label class="form-check-label" for="hkvkk"><a href="<?= u('/sayfa/kvkk') ?>" target="_blank">KVKK Aydınlatma Metni</a>'ni onaylıyorum.</label>
            </div>
          </div>
          <div class="col-12 d-grid">
            <button class="btn btn-warning btn-lg fw-semibold"><i class="bi bi-send"></i> Hasar İhbarını Gönder</button>
          </div>
        </div>
      </form>
    </div>
    <div class="col-lg-4">
      <div class="mz-side-card">
        <h6 class="fw-bold"><i class="bi bi-info-circle text-warning"></i> Önemli Bilgi</h6>
        <p class="small">Hasar ihbarının ardından dosyanız sigorta şirketine iletilir. Süreç boyunca aramalarımıza yanıt vermeniz hızlı sonuç almanızı sağlar.</p>
      </div>
      <?php if ($tel = setting('telefon')): ?>
      <div class="mz-side-card mt-3 text-center">
        <h6 class="fw-bold">7/24 Hasar Hattı</h6>
        <a href="tel:<?= e(preg_replace('/\s+/','',$tel)) ?>" class="d-block fs-4 fw-bold"><?= e($tel) ?></a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require MIZAN_INC . '/footer.php';
