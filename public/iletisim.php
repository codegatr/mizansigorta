<?php
if (!defined('MIZAN_BOOT')) { http_response_code(403); exit; }

$pageTitle = 'İletişim - ' . SITE_NAME;
$pageDesc  = 'Mizan Sigorta iletişim bilgileri. Adres, telefon, e-posta ve mesaj formu.';

$errors = [];
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_assert_post();
    if (!empty($_POST['website'])) {
        // honeypot
        $errors[] = 'Geçersiz istek.';
    } else {
        $a = (int)($_POST['captcha_a'] ?? 0);
        $b = (int)($_POST['captcha_b'] ?? 0);
        $cap = (int)($_POST['captcha'] ?? 0);
        $ad   = trim((string)($_POST['ad_soyad'] ?? ''));
        $tel  = trim((string)($_POST['telefon'] ?? ''));
        $email= trim((string)($_POST['email'] ?? ''));
        $konu = trim((string)($_POST['konu'] ?? ''));
        $msg  = trim((string)($_POST['mesaj'] ?? ''));
        $kvkk = isset($_POST['kvkk']);

        if ($cap !== ($a + $b))                       $errors[] = 'Doğrulama hatalı.';
        if ($ad === '' || mb_strlen($ad) < 3)         $errors[] = 'Ad Soyad zorunlu.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli e-posta giriniz.';
        if ($tel === '')                              $errors[] = 'Telefon zorunlu.';
        if ($konu === '')                             $errors[] = 'Konu zorunlu.';
        if (mb_strlen($msg) < 10)                     $errors[] = 'Mesaj en az 10 karakter olmalı.';
        if (!$kvkk)                                   $errors[] = 'KVKK metnini onaylayınız.';

        if (!$errors) {
            db_exec(
                'INSERT INTO ' . t('iletisim_mesajlari') .
                ' (ad_soyad, email, telefon, konu, mesaj, ip, user_agent, olusturma_tarihi)
                  VALUES (?,?,?,?,?,?,?,NOW())',
                [$ad, $email, normalize_phone($tel), $konu, $msg, client_ip(), substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)]
            );

            // Operatore bildirim
            $opMail = setting('operator_email', setting('email'));
            if ($opMail) {
                $body = '<h2>Yeni İletişim Mesajı</h2>'
                    . '<p><b>Ad Soyad:</b> ' . e($ad) . '</p>'
                    . '<p><b>E-posta:</b> ' . e($email) . '</p>'
                    . '<p><b>Telefon:</b> ' . e($tel) . '</p>'
                    . '<p><b>Konu:</b> ' . e($konu) . '</p>'
                    . '<p><b>Mesaj:</b><br>' . nl2br(e($msg)) . '</p>';
                @send_mail($opMail, 'Yeni İletişim Mesajı: ' . $konu, mail_template('İletişim Mesajı', $body));
            }

            $ok = true;
        }
    }
}

require MIZAN_INC . '/header.php';
?>

<section class="mz-page-head py-5">
  <div class="container">
    <h1 class="display-6 fw-bold mb-2">İletişim</h1>
    <p class="lead text-white-50 mb-0">Sorularınız ve teklif talepleriniz için bize ulaşın.</p>
  </div>
</section>

<section class="container py-5">
  <div class="row g-4 mb-5">
    <div class="col-md-4">
      <div class="mz-quick-card text-center h-100">
        <div class="mz-prod-icon mb-3"><i class="bi bi-geo-alt"></i></div>
        <h5 class="fw-bold">Adres</h5>
        <p class="mb-0"><?= nl2br(e(setting('adres', 'Konya, Türkiye'))) ?></p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="mz-quick-card text-center h-100">
        <div class="mz-prod-icon mb-3"><i class="bi bi-telephone"></i></div>
        <h5 class="fw-bold">Telefon</h5>
        <p class="mb-1"><a class="text-decoration-none" href="tel:<?= e(preg_replace('/\s+/', '', setting('telefon'))) ?>"><?= e(setting('telefon')) ?></a></p>
        <?php if (setting('whatsapp')): ?>
          <a class="text-decoration-none" target="_blank" rel="noopener" href="https://wa.me/<?= e(setting('whatsapp')) ?>"><i class="bi bi-whatsapp text-success"></i> WhatsApp</a>
        <?php endif; ?>
      </div>
    </div>
    <div class="col-md-4">
      <div class="mz-quick-card text-center h-100">
        <div class="mz-prod-icon mb-3"><i class="bi bi-envelope"></i></div>
        <h5 class="fw-bold">E-posta</h5>
        <p class="mb-0"><a class="text-decoration-none" href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></p>
        <small class="text-muted"><?= e(setting('calisma_saatleri', 'Pzt-Cum 09:00-18:00')) ?></small>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-7">
      <div class="mz-form-card">
        <h3 class="fw-bold mb-4">Bize Mesaj Gönderin</h3>

        <?php if ($ok): ?>
          <div class="alert alert-success">
            <i class="bi bi-check-circle"></i> Mesajınız ulaştı. En kısa sürede size dönüş yapacağız.
          </div>
        <?php endif; ?>
        <?php if ($errors): ?>
          <div class="alert alert-danger">
            <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
          </div>
        <?php endif; ?>

        <?php
          $a = random_int(2, 9); $b = random_int(2, 9);
        ?>
        <form method="post" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="captcha_a" value="<?= $a ?>">
          <input type="hidden" name="captcha_b" value="<?= $b ?>">
          <input type="text" name="website" class="d-none" tabindex="-1" autocomplete="off">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Ad Soyad *</label>
              <input type="text" name="ad_soyad" class="form-control" required value="<?= e($_POST['ad_soyad'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Telefon *</label>
              <input type="tel" name="telefon" class="form-control" required value="<?= e($_POST['telefon'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">E-posta *</label>
              <input type="email" name="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Konu *</label>
              <input type="text" name="konu" class="form-control" required value="<?= e($_POST['konu'] ?? '') ?>">
            </div>
            <div class="col-12">
              <label class="form-label">Mesajınız *</label>
              <textarea name="mesaj" rows="5" class="form-control" required><?= e($_POST['mesaj'] ?? '') ?></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">Doğrulama: <?= $a ?> + <?= $b ?> = ? *</label>
              <input type="number" name="captcha" class="form-control" required>
            </div>
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="kvkk" id="kvkk" required>
                <label class="form-check-label" for="kvkk"><a href="<?= u('/sayfa/kvkk') ?>" target="_blank">KVKK metnini</a> okudum, kabul ediyorum.</label>
              </div>
            </div>
            <div class="col-12">
              <button class="btn btn-primary btn-lg" type="submit"><i class="bi bi-send"></i> Mesajı Gönder</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="mz-side-card mb-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-info-circle text-warning"></i> Çalışma Saatleri</h5>
        <ul class="list-unstyled mb-0">
          <li class="d-flex justify-content-between border-bottom py-2"><span>Pazartesi - Cuma</span><b>09:00 - 18:00</b></li>
          <li class="d-flex justify-content-between border-bottom py-2"><span>Cumartesi</span><b>10:00 - 14:00</b></li>
          <li class="d-flex justify-content-between py-2"><span>Pazar</span><b class="text-muted">Kapalı</b></li>
        </ul>
      </div>

      <?php $harita = setting('harita_embed'); if ($harita): ?>
        <div class="ratio ratio-4x3 rounded shadow-sm overflow-hidden">
          <?= $harita /* Konum embed kodu - admin panelden ayarlanir */ ?>
        </div>
      <?php else: ?>
        <div class="ratio ratio-4x3 rounded bg-light d-flex align-items-center justify-content-center">
          <div class="text-center text-muted">
            <i class="bi bi-geo-alt display-3"></i><br>
            <small>Konum bilgisi yakında eklenecektir.</small>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require MIZAN_INC . '/footer.php';
