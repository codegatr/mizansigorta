<?php
if (!defined('MIZAN_BOOT')) { http_response_code(403); exit; }

$pageTitle = 'İletişim — Konya, İstanbul, Ankara, Aksaray Şubeleri | ' . SITE_NAME;
$pageDesc  = 'Mizan Sigorta İletişim. Konya genel merkez ve şubelerimizin telefon, e-posta, adres ve çalışma saatleri. Online mesaj formu ile bize ulaşın, en kısa sürede dönüş yapalım.';
$pageBreadcrumbs = [
    ['name' => 'Anasayfa', 'url' => '/'],
    ['name' => 'İletişim', 'url' => '/iletisim'],
];

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
            $mesajId = db_last_id();

            $opMail = setting('operator_email', setting('email'));
            $extra  = talep_bildirim_alicilari();
            if ($opMail) {
                $infoTable = '<table cellpadding="0" cellspacing="0" style="width:100%;background:#f8fafc;border-radius:8px;margin:16px 0">'
                    . '<tr><td style="padding:12px 18px;border-bottom:1px solid #e5e7eb;width:140px;color:#6b7280;font-size:13px">Ad Soyad</td><td style="padding:12px 18px;border-bottom:1px solid #e5e7eb;font-weight:600">' . e($ad) . '</td></tr>'
                    . '<tr><td style="padding:12px 18px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px">E-posta</td><td style="padding:12px 18px;border-bottom:1px solid #e5e7eb"><a href="mailto:' . e($email) . '" style="color:#0d1b2a;text-decoration:none">' . e($email) . '</a></td></tr>'
                    . '<tr><td style="padding:12px 18px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px">Telefon</td><td style="padding:12px 18px;border-bottom:1px solid #e5e7eb"><a href="tel:' . preg_replace('/\s+/', '', $tel) . '" style="color:#e30b30;text-decoration:none;font-weight:600">' . e($tel) . '</a></td></tr>'
                    . '<tr><td style="padding:12px 18px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px">Konu</td><td style="padding:12px 18px;border-bottom:1px solid #e5e7eb;font-weight:600">' . e($konu) . '</td></tr>'
                    . '<tr><td style="padding:12px 18px;color:#6b7280;font-size:13px;vertical-align:top">Mesaj</td><td style="padding:12px 18px;line-height:1.6">' . nl2br(e($msg)) . '</td></tr>'
                    . '</table>';

                $bodyHtml = '<h2 style="margin:0 0 8px;color:#0d1b2a;font-size:22px">Yeni İletişim Mesajı</h2>'
                          . '<p style="color:#6b7280;margin:0 0 16px">Web sitesi iletişim formundan ' . date('d.m.Y H:i') . ' tarihinde yeni mesaj geldi:</p>'
                          . $infoTable;
                $html = mail_template('İletişim Mesajı — ' . $konu, $bodyHtml, [
                    'badge'       => 'YENİ MESAJ',
                    'badge_color' => '#0d6efd',
                    'preheader'   => 'Yeni iletisim mesaji: ' . $ad . ' - ' . $konu,
                ]);
                @send_mail($opMail, 'İletişim Mesajı — ' . $konu, $html, '', [
                    'bcc'        => $extra['bcc'],
                    'reply_to'   => $email,
                    'ilgili_tip' => 'iletisim',
                    'ilgili_id'  => isset($mesajId) ? (int)$mesajId : null,
                ]);
            }

            $ok = true;
        }
    }
}

require MIZAN_INC . '/header.php';

// Subeler DB'den (v1.1.19+) - tablo yoksa veya bos donerse legacy setting'lere fallback
$subelerDb = [];
try {
    $subelerDb = db_all('SELECT * FROM ' . t('subeler') . ' WHERE aktif=1 ORDER BY merkez_mi DESC, sira ASC, id ASC');
} catch (Throwable $e) { $subelerDb = []; }

$merkez   = null;
$digerler = [];
foreach ($subelerDb as $s) {
    if ($s['merkez_mi']) { $merkez = $merkez ?: $s; }
    else { $digerler[] = $s; }
}

// Merkez kaydi var ama adres bos ise -> setting('adres')'i fallback olarak kullan
// (Yunus 'Ayarlar > Iletisim > adres' gridine yazip iletisim sayfasinda gormesini bekliyor)
if ($merkez && empty(trim((string)$merkez['adres'])) && ($genelAdres = trim((string)setting('adres')))) {
    $merkez['adres'] = $genelAdres;
}
if ($merkez && empty(trim((string)$merkez['telefon'])) && ($genelTel = trim((string)setting('telefon')))) {
    $merkez['telefon'] = $genelTel;
}
if ($merkez && empty(trim((string)$merkez['email'])) && ($genelEmail = trim((string)setting('email')))) {
    $merkez['email'] = $genelEmail;
}

// Legacy fallback: tablo bos ise eski setting'lerden minimum bilgi
if (!$merkez && !$digerler) {
    $istanbul = setting('istanbul_adres');
    $konya    = setting('konya_adres');
    $ankara   = setting('ankara_adres');
    $aksaray  = setting('aksaray_adres');
    $genelAdres = trim((string)setting('adres'));

    // Genel merkez: once istanbul_adres, sonra genel adres setting'i, sonra placeholder
    if ($istanbul) {
        $merkez = ['sehir' => 'İstanbul', 'ilce' => null, 'etiket' => 'Genel Merkez', 'adres' => $istanbul,
                   'telefon' => setting('telefon'), 'telefon_2' => null, 'email' => setting('email'),
                   'harita_url' => null, 'calisma_saatleri' => setting('calisma_saatleri', 'Pzt-Cum 09:00-18:00'), 'merkez_mi' => 1, 'aktif' => 1];
    } elseif ($genelAdres) {
        // istanbul_adres yok ama genel 'adres' setting var -> onu kullan
        $merkez = ['sehir' => setting('sehir', 'Türkiye'), 'ilce' => null, 'etiket' => 'Genel Merkez', 'adres' => $genelAdres,
                   'telefon' => setting('telefon'), 'telefon_2' => null, 'email' => setting('email'),
                   'harita_url' => null, 'calisma_saatleri' => setting('calisma_saatleri', 'Pzt-Cum 09:00-18:00'), 'merkez_mi' => 1, 'aktif' => 1];
    }
    foreach ([['Konya', 'Karatay', $konya], ['Ankara', null, $ankara], ['Aksaray', null, $aksaray]] as $i => $row) {
        [$sehir, $ilce, $adres] = $row;
        if ($adres) {
            $digerler[] = ['sehir' => $sehir, 'ilce' => $ilce, 'etiket' => 'Şube Ofis', 'adres' => $adres,
                           'telefon' => null, 'telefon_2' => null, 'email' => null, 'harita_url' => null,
                           'calisma_saatleri' => null, 'merkez_mi' => 0, 'aktif' => 1];
        }
    }
}

// Harita URL helper - admin URL girdiyse onu, yoksa adresten Google Maps embed
$_haritaSrc = function(array $s): string {
    if (!empty($s['harita_url'])) {
        $u = $s['harita_url'];
        // Eger harita_url tam Google Maps embed degilse, adres aramasina cevir
        if (strpos($u, 'output=embed') === false && strpos($u, '/embed') === false) {
            return 'https://maps.google.com/maps?q=' . rawurlencode((string)$s['adres']) . '&output=embed';
        }
        return $u;
    }
    return 'https://maps.google.com/maps?q=' . rawurlencode((string)$s['adres']) . '&output=embed';
};
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
    <?php
    $subeSayisi = count($digerler);
    $heroAciklama = $merkez
        ? sprintf('%s Genel Merkez%s%sile Türkiye\'nin önde gelen acentesi olarak hizmetinizdeyiz.',
                  e($merkez['sehir']),
                  $subeSayisi > 0 ? sprintf(' ve %d şubemiz', $subeSayisi) : '',
                  $subeSayisi > 0 ? ' ' : ' ')
        : 'Türkiye\'nin önde gelen acentelerinden olarak hizmetinizdeyiz.';
    ?>
    <p class="lead text-white-50 mb-0 mx-auto" style="max-width:640px"><?= $heroAciklama ?></p>
  </div>
</section>

<?php if ($merkez): ?>
<!-- ===== GENEL MERKEZ — Vurgulu ===== -->
<div class="container">
  <div class="mz-merkez-card">
    <div class="row g-0">
      <div class="col-md-7 p-4 p-md-5">
        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
          <div>
            <span class="mz-merkez-badge"><i class="bi bi-gem"></i> <?= e($merkez['etiket'] ?: 'Genel Merkez') ?></span>
            <h2 class="fw-bold mt-3 mb-1"><?= e($merkez['sehir']) ?><?php if (!empty($merkez['ilce'])): ?> <small class="text-muted">· <?= e($merkez['ilce']) ?></small><?php endif; ?></h2>
            <p class="text-muted mb-0">Mizan Sigorta'nın yönetim ve operasyon merkezi</p>
          </div>
          <?php if (!empty($merkez['calisma_saatleri']) || ($cs = setting('calisma_saatleri'))): ?>
            <div class="text-end">
              <small class="text-muted d-block">Çalışma Saatleri</small>
              <strong class="text-warning"><?= e($merkez['calisma_saatleri'] ?? setting('calisma_saatleri', 'Pzt-Cum 09:00-18:00')) ?></strong>
            </div>
          <?php endif; ?>
        </div>

        <hr class="my-3">

        <div class="mz-channel mb-2">
          <div class="mz-channel-icon"><i class="bi bi-geo-alt-fill"></i></div>
          <div class="flex-grow-1">
            <small class="text-muted d-block">Adres</small>
            <span><?= nl2br(e($merkez['adres'])) ?></span>
          </div>
        </div>

        <?php
        // Telefon: once merkez kayitli, yoksa genel ayardan
        $telMerkez = $merkez['telefon'] ?: setting('telefon');
        $telMerkez2 = $merkez['telefon_2'] ?? null;
        if ($telMerkez):
        ?>
          <a class="mz-channel mb-2" href="tel:<?= e(preg_replace('/\s+/', '', $telMerkez)) ?>">
            <div class="mz-channel-icon"><i class="bi bi-telephone-fill"></i></div>
            <div class="flex-grow-1">
              <small class="text-muted d-block">Telefon</small>
              <strong><?= e($telMerkez) ?><?php if ($telMerkez2): ?> <span class="text-muted fw-normal">· <?= e($telMerkez2) ?></span><?php endif; ?></strong>
            </div>
            <i class="bi bi-arrow-right"></i>
          </a>
        <?php endif; ?>

        <?php
        // E-posta: merkez kayitli olan veya genel ayar
        $emailMerkez = $merkez['email'] ?: setting('email');
        if ($emailMerkez):
        ?>
          <a class="mz-channel mb-2" href="mailto:<?= e($emailMerkez) ?>">
            <div class="mz-channel-icon"><i class="bi bi-envelope-fill"></i></div>
            <div class="flex-grow-1">
              <small class="text-muted d-block">E-posta</small>
              <strong><?= e($emailMerkez) ?></strong>
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
        <iframe
          src="<?= e($_haritaSrc($merkez)) ?>"
          style="width:100%;height:100%;border:0;min-height:380px"
          loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($digerler): ?>
<!-- ===== ŞUBELER ===== -->
<section class="mz-band">
  <div class="container">
    <div class="text-center mb-5">
      <span class="mz-script mz-script-md mz-script-red">Şubelerimiz</span>
      <h2 class="fw-bold display-6 mt-2"><?= count($digerler) ?> Şehirde Yanınızdayız</h2>
      <?php if ($merkez): ?>
        <p class="text-muted lead"><?= e($merkez['etiket'] ?: 'Genel Merkez') ?> <?= e($merkez['sehir']) ?>'a ek olarak Türkiye'nin stratejik noktalarında</p>
      <?php endif; ?>
    </div>

    <div class="row g-4">
      <?php foreach ($digerler as $idx => $s):
        $no = str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT);
        $tel = $s['telefon'] ?? null;
        $tel2 = $s['telefon_2'] ?? null;
      ?>
        <div class="col-md-6 col-lg-4">
          <div class="mz-sube-card">
            <div class="mz-sube-card-head">
              <div>
                <h5 class="fw-bold mb-0" style="color:var(--mz-navy)"><?= e($s['sehir']) ?> <?php if (!empty($s['ilce'])): ?><small class="text-muted">· <?= e($s['ilce']) ?></small><?php endif; ?></h5>
                <small class="text-muted"><?= e($s['etiket'] ?? 'Şube Ofis') ?></small>
              </div>
              <span class="mz-sube-num"><?= e($no) ?></span>
            </div>
            <div class="p-3">
              <p class="small text-muted mb-3" style="line-height:1.55"><i class="bi bi-geo-alt-fill text-danger"></i> <?= nl2br(e($s['adres'])) ?></p>

              <?php if ($tel): ?>
                <a href="tel:<?= e(preg_replace('/\s+/', '', $tel)) ?>" class="d-flex align-items-center gap-2 mb-2 small text-decoration-none" style="color:var(--mz-navy)">
                  <i class="bi bi-telephone-fill text-danger"></i>
                  <strong><?= e($tel) ?></strong>
                  <?php if ($tel2): ?><span class="text-muted">· <?= e($tel2) ?></span><?php endif; ?>
                </a>
              <?php endif; ?>

              <?php if (!empty($s['email'])): ?>
                <a href="mailto:<?= e($s['email']) ?>" class="d-flex align-items-center gap-2 mb-2 small text-decoration-none" style="color:var(--mz-navy)">
                  <i class="bi bi-envelope-fill text-danger"></i>
                  <span><?= e($s['email']) ?></span>
                </a>
              <?php endif; ?>

              <?php if (!empty($s['calisma_saatleri'])): ?>
                <div class="d-flex align-items-center gap-2 mb-3 small text-muted">
                  <i class="bi bi-clock-fill text-danger"></i>
                  <span><?= e($s['calisma_saatleri']) ?></span>
                </div>
              <?php else: ?>
                <div class="mb-3"></div>
              <?php endif; ?>

              <a href="<?= e($s['harita_url'] ?: ('https://www.google.com/maps/search/' . rawurlencode((string)$s['adres']))) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-warning w-100"><i class="bi bi-map"></i> Haritada Göster</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ===== Mesaj Formu ===== -->
<section class="mz-band bg-light">
  <div class="container">
    <div class="row g-4 justify-content-center">
      <div class="col-lg-7">
        <div class="mz-form-card">
          <div class="text-center mb-4">
            <span class="mz-trust-badge mb-2"><i class="bi bi-chat-dots"></i> Bize Yazın</span>
            <h3 class="fw-bold mb-1" style="color:var(--mz-navy)">Bir mesaj bırakın, dönelim</h3>
            <p class="text-muted small mb-0">Sigorta türü, hasar süreci, teklif veya genel bilgi — kısa süre içinde size geri dönüş yapalım.</p>
          </div>

          <?php if ($ok): ?>
            <div class="alert alert-success d-flex gap-3 align-items-start">
              <i class="bi bi-check-circle-fill fs-3"></i>
              <div>
                <strong class="d-block">Mesajınız ulaştı!</strong>
                <small>İlgili temsilcimiz en kısa sürede size dönüş yapacak. Aciliyet durumunda doğrudan telefonla arayabilirsiniz.</small>
              </div>
            </div>
          <?php else: ?>
            <?php if ($errors): ?>
              <div class="alert alert-danger d-flex gap-3 align-items-start">
                <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                <div>
                  <strong class="d-block mb-1">Lütfen aşağıdaki hataları düzeltin:</strong>
                  <ul class="mb-0 small"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
                </div>
              </div>
            <?php endif; ?>

            <form method="post" novalidate data-mz-captcha>
              <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e(csrf_token()) ?>">
              <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">

              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Ad Soyad <span class="text-danger">*</span></label>
                  <div class="input-group"><span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                    <input type="text" name="ad_soyad" autocomplete="name" class="form-control" required value="<?= e($_POST['ad_soyad'] ?? '') ?>" placeholder="Adınız ve soyadınız">
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Telefon <span class="text-danger">*</span></label>
                  <div class="input-group"><span class="input-group-text bg-light"><i class="bi bi-telephone"></i></span>
                    <input type="tel" name="telefon" inputmode="tel" autocomplete="tel" class="form-control" required value="<?= e($_POST['telefon'] ?? '') ?>" placeholder="0 5xx xxx xx xx">
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">E-posta <span class="text-danger">*</span></label>
                  <div class="input-group"><span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" inputmode="email" autocomplete="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>" placeholder="ornek@email.com">
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Konu <span class="text-danger">*</span></label>
                  <div class="input-group"><span class="input-group-text bg-light"><i class="bi bi-tag"></i></span>
                    <input type="text" name="konu" class="form-control" required value="<?= e($_POST['konu'] ?? '') ?>" placeholder="Örn. Kasko fiyatı, hasar süreci">
                  </div>
                </div>
                <div class="col-12">
                  <label class="form-label small fw-semibold">Mesajınız <span class="text-danger">*</span></label>
                  <textarea name="mesaj" class="form-control" rows="5" required placeholder="Sorularınızı veya talebinizi detaylı olarak yazabilirsiniz..."><?= e($_POST['mesaj'] ?? '') ?></textarea>
                </div>

                <div class="col-12">
                  <div class="p-3 rounded" style="background:rgba(13,27,42,.04);border:1px dashed rgba(13,27,42,.15)">
                    <div class="row g-3 align-items-center">
                      <div class="col-md-5">
                        <label class="form-label small fw-semibold mb-1">Doğrulama: <span data-mz-cap-a></span> + <span data-mz-cap-b></span> = ?</label>
                        <input type="number" name="captcha" class="form-control form-control-sm" required data-mz-cap-input placeholder="Sonuç">
                        <input type="hidden" name="captcha_a" data-mz-cap-correct-a>
                        <input type="hidden" name="captcha_b" data-mz-cap-correct-b>
                      </div>
                      <div class="col-md-7">
                        <div class="form-check">
                          <input type="checkbox" name="kvkk" id="kvkk2" class="form-check-input" required>
                          <label for="kvkk2" class="form-check-label small">
                            <a href="<?= u('/sayfa/kvkk') ?>" target="_blank">KVKK Aydınlatma Metni</a>'ni okudum, kişisel verilerimin işlenmesine rıza gösteriyorum. <span class="text-danger">*</span>
                          </label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-12 d-grid">
                  <button class="btn btn-warning btn-lg fw-semibold"><i class="bi bi-send-fill"></i> Mesajı Gönder</button>
                </div>
                <div class="col-12 text-center">
                  <small class="text-muted"><i class="bi bi-shield-check"></i> Bilgileriniz KVKK kapsamında korunmaktadır</small>
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
