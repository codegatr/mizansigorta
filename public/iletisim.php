<?php
if (!defined('MIZAN_BOOT')) { http_response_code(403); exit; }

$pageTitle = 'İletişim - ' . SITE_NAME;
$pageDesc  = 'Mizan Sigorta İstanbul Genel Merkez ve şubeleri. Adres, telefon, e-posta, harita ve mesaj formu.';

$errors = [];
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_assert_post();
    if (!empty($_POST['website'])) {
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
                ' (ad_soyad, email, telefon, konu, mesaj, ip_adresi, user_agent, olusturma_tarihi)
                  VALUES (?,?,?,?,?,?,?,NOW())',
                [$ad, $email, normalize_phone($tel), $konu, $msg, client_ip(), substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)]
            );

            $opMail = setting('operator_email', setting('email'));
            if ($opMail) {
                $body = '<h2>Yeni İletişim Mesajı</h2>'
                    . '<p><b>Ad Soyad:</b> ' . e($ad) . '</p>'
                    . '<p><b>E-posta:</b> ' . e($email) . '</p>'
                    . '<p><b>Telefon:</b> ' . e($tel) . '</p>'
                    . '<p><b>Konu:</b> ' . e($konu) . '</p>'
                    . '<p><b>Mesaj:</b><br>' . nl2br(e($msg)) . '</p>';
                @send_mail($opMail, 'Mizan Sigorta', 'İletişim Mesajı — ' . $konu, mail_template('İletişim Mesajı', $body));
            }

            $ok = true;
        }
    }
}

require MIZAN_INC . '/header.php';

$istanbul = setting('istanbul_adres');
$konya    = setting('konya_adres');
$ankara   = setting('ankara_adres');
$aksaray  = setting('aksaray_adres');
?>

<style>
.mz-merkez-hero {
  background: linear-gradient(135deg, var(--mz-navy) 0%, var(--mz-navy-2) 100%);
  color: #fff; padding: 4rem 0 8rem; position: relative; overflow: hidden;
}
.mz-merkez-hero::before {
  content: ''; position: absolute; inset: 0;
  background: radial-gradient(ellipse at center, rgba(244,211,94,.08) 0%, transparent 60%);
}
.mz-merkez-card {
  background: #fff; border-radius: 20px; overflow: hidden;
  box-shadow: 0 30px 80px rgba(15,30,55,.18);
  margin-top: -100px; position: relative; z-index: 2;
  border-top: 6px solid #f4d35e;
}
.mz-merkez-badge {
  background: linear-gradient(135deg, #f4d35e, #d4af37);
  color: var(--mz-navy);
  font-weight: 700; padding: .5rem 1.25rem;
  border-radius: 100px; font-size: .8rem; letter-spacing: .1em;
  text-transform: uppercase; display: inline-flex; align-items: center; gap: .5rem;
  box-shadow: 0 8px 25px rgba(244,211,94,.4);
}
.mz-sube-card {
  background: #fff; border-radius: 16px;
  border: 2px solid #f1f5f9; transition: all .25s; height: 100%;
  overflow: hidden; position: relative;
}
.mz-sube-card:hover { border-color: var(--mz-red); transform: translateY(-4px); box-shadow: 0 20px 50px rgba(15,30,55,.1); }
.mz-sube-card-head {
  padding: 1.25rem 1.5rem; background: linear-gradient(135deg, #f8fafc, #fff);
  border-bottom: 1px solid #f1f5f9;
  display: flex; justify-content: space-between; align-items: center;
}
.mz-sube-num {
  width: 36px; height: 36px; border-radius: 50%;
  background: linear-gradient(135deg, var(--mz-red), var(--mz-red-2));
  color: #fff; display: inline-flex; align-items: center; justify-content: center;
  font-size: .8rem; font-weight: 700;
}
.mz-channel {
  display: flex; align-items: center; gap: .85rem;
  padding: .85rem 1rem; border-radius: 10px;
  background: #f8fafc; transition: all .15s;
  text-decoration: none; color: var(--mz-navy);
}
.mz-channel:hover { background: rgba(227,11,48,.06); color: var(--mz-red); }
.mz-channel-icon {
  width: 38px; height: 38px; border-radius: 50%;
  background: linear-gradient(135deg, var(--mz-red), var(--mz-red-2));
  color: #fff; display: inline-flex; align-items: center; justify-content: center;
  font-size: 1rem; flex-shrink: 0;
}
</style>

<!-- ===== HERO ===== -->
<section class="mz-merkez-hero">
  <div class="container text-center position-relative">
    <span class="mz-script mz-script-md mz-script-red d-inline-block mb-2" style="color:#f4d35e !important">İletişim</span>
    <h1 class="display-4 fw-bold">Bize Ulaşın</h1>
    <p class="lead text-white-50 mb-0 mx-auto" style="max-width:640px">İstanbul Genel Merkez ve 3 şubemizle Türkiye'nin önde gelen acentesi olarak hizmetinizdeyiz.</p>
  </div>
</section>

<!-- ===== GENEL MERKEZ — Vurgulu ===== -->
<div class="container">
  <div class="mz-merkez-card">
    <div class="row g-0">
      <div class="col-md-7 p-4 p-md-5">
        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
          <div>
            <span class="mz-merkez-badge"><i class="bi bi-gem"></i> Genel Merkez</span>
            <h2 class="fw-bold mt-3 mb-1">İstanbul · Ataşehir</h2>
            <p class="text-muted mb-0">Mizan Sigorta'nın yönetim ve operasyon merkezi</p>
          </div>
          <div class="text-end">
            <small class="text-muted d-block">Çalışma Saatleri</small>
            <strong class="text-warning"><?= e(setting('calisma_saatleri', 'Pzt-Cum 09:00-18:00')) ?></strong>
          </div>
        </div>

        <hr class="my-3">

        <?php if ($istanbul): ?>
          <div class="mz-channel mb-2">
            <div class="mz-channel-icon"><i class="bi bi-geo-alt-fill"></i></div>
            <div class="flex-grow-1">
              <small class="text-muted d-block">Adres</small>
              <span><?= nl2br(e($istanbul)) ?></span>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($v = setting('telefon')): ?>
          <a class="mz-channel mb-2" href="tel:<?= e(preg_replace('/\s+/', '', $v)) ?>">
            <div class="mz-channel-icon"><i class="bi bi-telephone-fill"></i></div>
            <div class="flex-grow-1">
              <small class="text-muted d-block">Telefon</small>
              <strong><?= e($v) ?></strong>
            </div>
            <i class="bi bi-arrow-right"></i>
          </a>
        <?php endif; ?>

        <?php if ($v = setting('email')): ?>
          <a class="mz-channel mb-2" href="mailto:<?= e($v) ?>">
            <div class="mz-channel-icon"><i class="bi bi-envelope-fill"></i></div>
            <div class="flex-grow-1">
              <small class="text-muted d-block">E-posta</small>
              <strong><?= e($v) ?></strong>
            </div>
            <i class="bi bi-arrow-right"></i>
          </a>
        <?php endif; ?>

        <?php if ($wa = setting('whatsapp')): ?>
          <a class="mz-channel" href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener">
            <div class="mz-channel-icon" style="background:linear-gradient(135deg,#25d366,#128c7e)"><i class="bi bi-whatsapp"></i></div>
            <div class="flex-grow-1">
              <small class="text-muted d-block">WhatsApp</small>
              <strong>+<?= e($wa) ?></strong>
            </div>
            <i class="bi bi-arrow-right"></i>
          </a>
        <?php endif; ?>
      </div>

      <div class="col-md-5" style="background:linear-gradient(135deg,#f8fafc,#fff);min-height:380px;position:relative;display:flex;align-items:center;justify-content:center">
        <?php if ($istanbul): ?>
          <iframe
            src="https://maps.google.com/maps?q=<?= rawurlencode($istanbul) ?>&output=embed"
            style="width:100%;height:100%;border:0;min-height:380px"
            loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        <?php else: ?>
          <div class="text-center text-muted p-4"><i class="bi bi-geo-alt" style="font-size:3rem"></i><p>Harita</p></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ===== ŞUBELER ===== -->
<section class="mz-band">
  <div class="container">
    <div class="text-center mb-5">
      <span class="mz-script mz-script-md mz-script-red">Şubelerimiz</span>
      <h2 class="fw-bold display-6 mt-2">3 Şehirde Yanınızdayız</h2>
      <p class="text-muted lead">Genel Merkez İstanbul'a ek olarak Anadolu'nun stratejik noktalarında</p>
    </div>

    <div class="row g-4">
      <?php
      $subeler = [
          ['no' => '01', 'sehir' => 'Konya',   'ilce' => 'Karatay',   'adres' => $konya,   'icon' => 'bi-geo-alt-fill'],
          ['no' => '02', 'sehir' => 'Ankara',  'ilce' => '',          'adres' => $ankara,  'icon' => 'bi-geo-alt-fill'],
          ['no' => '03', 'sehir' => 'Aksaray', 'ilce' => '',          'adres' => $aksaray, 'icon' => 'bi-geo-alt-fill'],
      ];
      foreach ($subeler as $s):
          if (!$s['adres']) continue;
      ?>
        <div class="col-md-6 col-lg-4">
          <div class="mz-sube-card">
            <div class="mz-sube-card-head">
              <div>
                <h5 class="fw-bold mb-0" style="color:var(--mz-navy)"><?= e($s['sehir']) ?> <?php if ($s['ilce']): ?><small class="text-muted">· <?= e($s['ilce']) ?></small><?php endif; ?></h5>
                <small class="text-muted">Şube Ofis</small>
              </div>
              <span class="mz-sube-num"><?= $s['no'] ?></span>
            </div>
            <div class="p-3">
              <p class="small text-muted mb-3" style="line-height:1.55"><?= nl2br(e($s['adres'])) ?></p>
              <a href="https://www.google.com/maps/search/<?= rawurlencode($s['adres']) ?>" target="_blank" class="btn btn-sm btn-outline-warning w-100"><i class="bi bi-map"></i> Haritada Göster</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>

      <?php
      $bos = array_filter($subeler, fn($s) => !$s['adres']);
      if ($bos):
      ?>
        <div class="col-12">
          <div class="alert alert-light border text-center small">
            <i class="bi bi-info-circle text-warning"></i>
            <?php foreach ($bos as $b): ?><strong><?= e($b['sehir']) ?></strong> ofisimiz <?php endforeach; ?> yakında hizmetinizde olacak.
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ===== Mesaj Formu ===== -->
<section class="mz-band bg-light">
  <div class="container">
    <div class="row g-4 justify-content-center">
      <div class="col-lg-7">
        <div class="mz-form-card">
          <h3 class="fw-bold mb-2"><i class="bi bi-chat-dots text-warning"></i> Bize Mesaj Gönderin</h3>
          <p class="text-muted small mb-4">Sigorta türü, hasar süreci, teklif veya genel bilgi — sorularınızı bize iletin.</p>

          <?php if ($ok): ?>
            <div class="alert alert-success">
              <i class="bi bi-check-circle"></i> Mesajınız ulaştı. En kısa sürede size dönüş yapacağız.
            </div>
          <?php else: ?>
            <?php if ($errors): ?>
              <div class="alert alert-danger small"><?= implode('<br>', array_map('e', $errors)) ?></div>
            <?php endif; ?>

            <form method="post" novalidate data-mz-captcha>
              <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e(csrf_token()) ?>">
              <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">

              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Ad Soyad *</label>
                  <input type="text" name="ad_soyad" class="form-control" required value="<?= e($_POST['ad_soyad'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Telefon *</label>
                  <input type="tel" name="telefon" class="form-control" required value="<?= e($_POST['telefon'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">E-posta *</label>
                  <input type="email" name="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Konu *</label>
                  <input type="text" name="konu" class="form-control" required value="<?= e($_POST['konu'] ?? '') ?>">
                </div>
                <div class="col-12">
                  <label class="form-label small fw-semibold">Mesajınız *</label>
                  <textarea name="mesaj" class="form-control" rows="5" required><?= e($_POST['mesaj'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Doğrulama: <span data-mz-cap-a></span> + <span data-mz-cap-b></span> = ?</label>
                  <input type="number" name="captcha" class="form-control" required data-mz-cap-input>
                  <input type="hidden" name="captcha_a" data-mz-cap-correct-a>
                  <input type="hidden" name="captcha_b" data-mz-cap-correct-b>
                </div>
                <div class="col-12">
                  <div class="form-check">
                    <input type="checkbox" name="kvkk" id="kvkk2" class="form-check-input" required>
                    <label for="kvkk2" class="form-check-label small">
                      <a href="<?= u('/sayfa/kvkk') ?>" target="_blank">KVKK Aydınlatma Metni</a>'ni okudum, kişisel verilerimin işlenmesine rıza gösteriyorum. *
                    </label>
                  </div>
                </div>
                <div class="col-12 d-grid">
                  <button class="btn btn-warning fw-semibold"><i class="bi bi-send"></i> Mesajı Gönder</button>
                </div>
              </div>
            </form>
          <?php endif; ?>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body p-4">
            <span class="mz-script mz-script-md mz-script-red d-block mb-2">Hızlı Erişim</span>
            <h5 class="fw-bold mb-3">Hemen mi sormak istiyorsunuz?</h5>

            <?php if ($v = setting('telefon')): ?>
              <a href="tel:<?= e(preg_replace('/\s+/', '', $v)) ?>" class="btn btn-warning w-100 mb-2"><i class="bi bi-telephone-fill"></i> <?= e($v) ?></a>
            <?php endif; ?>
            <?php if ($wa = setting('whatsapp')): ?>
              <a href="https://wa.me/<?= e($wa) ?>" target="_blank" class="btn btn-success w-100 mb-2"><i class="bi bi-whatsapp"></i> WhatsApp ile yaz</a>
            <?php endif; ?>
            <button type="button" class="btn btn-outline-warning w-100" data-bs-toggle="modal" data-bs-target="#teklifWizard"><i class="bi bi-headset"></i> Teklif Talebi</button>

            <hr class="my-4">

            <h6 class="fw-bold mb-2">Çalışma Saatleri</h6>
            <p class="small text-muted mb-0"><i class="bi bi-clock"></i> <?= e(setting('calisma_saatleri', 'Pzt-Cum 09:00-18:00, Cmt 10:00-14:00')) ?></p>

            <hr class="my-4">

            <h6 class="fw-bold mb-2">Sosyal Medya</h6>
            <div class="d-flex gap-2">
              <?php foreach (['facebook','instagram','linkedin','twitter','youtube'] as $sn): if ($u = setting($sn)): ?>
                <a class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" href="<?= e($u) ?>" aria-label="<?= e($sn) ?>"><i class="bi bi-<?= e($sn === 'twitter' ? 'twitter-x' : $sn) ?>"></i></a>
              <?php endif; endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require MIZAN_INC . '/footer.php';
