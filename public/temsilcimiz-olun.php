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
                @send_mail($adminMail, 'Mizan Sigorta', 'Yeni Temsilci Başvurusu — ' . $ad, mail_template('Temsilci Başvurusu', $govde));
            }

            @send_mail($email, $ad, 'Başvurunuz alındı — Mizan Sigorta',
                mail_template('Başvurunuz alındı',
                    '<p>Sayın <b>' . e($ad) . '</b>,</p>'
                  . '<p>Mizan Sigorta ailesine katılma başvurunuzu aldık. Yetkili ekibimiz başvurunuzu değerlendirecek ve en kısa sürede sizinle iletişime geçecektir.</p>'
                  . '<p>İlginiz için teşekkür ederiz.</p>'
                  . '<p style="font-style:italic">Güven ve Özen İle<br><b>Mizan Sigorta</b></p>'));

            $ok = true;
        }
    }
}

$pageTitle = 'Temsilcimiz Olun — ' . SITE_NAME;
$pageDesc  = 'Mizan Sigorta ailesine katılın. Sigorta acentesi olarak güçlü altyapımızla yan yana çalışın, kurumsal kimlik desteği ile büyüyün.';
require MIZAN_INC . '/header.php';
?>

<style>
.mz-royal-hero {
  position: relative; min-height: 540px; overflow: hidden;
  background: linear-gradient(135deg, #0f1e37 0%, #1a3354 50%, #0f1e37 100%);
  color: #fff;
}
.mz-royal-hero::before {
  content: ''; position: absolute; inset: 0;
  background:
    radial-gradient(ellipse at top right, rgba(227,11,48,.18) 0%, transparent 50%),
    radial-gradient(ellipse at bottom left, rgba(218,165,32,.12) 0%, transparent 50%);
  pointer-events: none;
}
.mz-royal-hero::after {
  content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 100px;
  background: linear-gradient(to bottom, transparent, rgba(255,255,255,.04));
}

.mz-royal-crest {
  width: 90px; height: 90px; margin: 0 auto 1.5rem;
  background: linear-gradient(135deg, #f4d35e, #e8a83a, #d4af37);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 2.75rem; color: var(--mz-navy);
  box-shadow: 0 0 0 4px rgba(244,211,94,.2), 0 25px 60px rgba(244,211,94,.3);
  animation: royalPulse 3s ease-in-out infinite;
}
@keyframes royalPulse {
  0%, 100% { transform: scale(1); box-shadow: 0 0 0 4px rgba(244,211,94,.2), 0 25px 60px rgba(244,211,94,.3); }
  50% { transform: scale(1.04); box-shadow: 0 0 0 8px rgba(244,211,94,.15), 0 35px 80px rgba(244,211,94,.4); }
}

.mz-royal-title {
  font-size: clamp(2.5rem, 6vw, 4.5rem);
  font-weight: 800;
  line-height: 1; letter-spacing: -.02em;
  background: linear-gradient(135deg, #fff 0%, #f4d35e 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  background-clip: text;
  text-shadow: 0 4px 60px rgba(244,211,94,.2);
}
.mz-royal-script {
  font-family: 'Allura', 'Pinyon Script', cursive;
  font-size: 4rem; color: #f4d35e;
  line-height: 1; margin-bottom: 1rem;
  text-shadow: 0 4px 30px rgba(244,211,94,.3);
}
.mz-royal-sub { font-size: 1.25rem; color: rgba(255,255,255,.85); max-width: 720px; margin: 1.5rem auto; line-height: 1.6; }

.mz-royal-divider { display: flex; align-items: center; gap: 1rem; max-width: 320px; margin: 2rem auto 1rem; }
.mz-royal-divider::before, .mz-royal-divider::after { content: ''; flex: 1; height: 1px; background: linear-gradient(to right, transparent, #f4d35e, transparent); }
.mz-royal-divider i { color: #f4d35e; font-size: 1.25rem; }

.mz-royal-stats { display: flex; justify-content: center; gap: 3rem; flex-wrap: wrap; margin-top: 3rem; }
.mz-royal-stat { text-align: center; }
.mz-royal-stat-num { font-size: 3rem; font-weight: 800; color: #f4d35e; line-height: 1; }
.mz-royal-stat-lbl { font-size: .9rem; color: rgba(255,255,255,.7); text-transform: uppercase; letter-spacing: .1em; margin-top: .5rem; }

.mz-pillar {
  background: #fff; border-radius: 16px; padding: 2rem; height: 100%;
  border: 2px solid transparent;
  box-shadow: 0 10px 30px rgba(15,30,55,.08);
  transition: all .3s;
  position: relative; overflow: hidden;
}
.mz-pillar::before {
  content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px;
  background: linear-gradient(90deg, #d4af37, #f4d35e, #d4af37);
}
.mz-pillar:hover { border-color: #f4d35e; transform: translateY(-4px); box-shadow: 0 20px 50px rgba(15,30,55,.15); }
.mz-pillar-icon {
  width: 60px; height: 60px; border-radius: 50%;
  background: linear-gradient(135deg, var(--mz-red), var(--mz-red-2));
  color: #fff; display: inline-flex; align-items: center; justify-content: center;
  font-size: 1.5rem; margin-bottom: 1rem;
  box-shadow: 0 8px 20px var(--mz-red-glow);
}
.mz-pillar h5 { color: var(--mz-navy); font-weight: 800; margin-bottom: .75rem; }

.mz-form-royal {
  background: linear-gradient(135deg, #fff 0%, #fffbf0 100%);
  border-radius: 20px;
  padding: 2.5rem;
  box-shadow: 0 30px 80px rgba(15,30,55,.12);
  border-top: 6px solid #d4af37;
  position: relative;
}
.mz-form-royal::before {
  content: ''; position: absolute; top: -3px; right: 30px;
  width: 60px; height: 60px;
  background: #f4d35e; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 10px 30px rgba(244,211,94,.4);
}
.mz-form-royal::after {
  content: '\F47A'; /* bi-stars */
  font-family: 'bootstrap-icons';
  position: absolute; top: 13px; right: 38px;
  font-size: 1.75rem; color: var(--mz-navy);
  z-index: 2;
}

.mz-quote {
  background: linear-gradient(135deg, var(--mz-navy), var(--mz-navy-2));
  color: #fff; border-radius: 16px; padding: 2.5rem;
  position: relative; overflow: hidden;
}
.mz-quote::before {
  content: '\201C';
  position: absolute; top: -20px; left: 20px;
  font-size: 9rem; color: rgba(244,211,94,.15); font-family: serif;
  line-height: 1;
}
.mz-quote-text { font-size: 1.35rem; font-style: italic; line-height: 1.5; position: relative; }
.mz-quote-author { color: #f4d35e; font-weight: 600; margin-top: 1rem; font-size: .95rem; }
</style>

<!-- ====== HERO ====== -->
<section class="mz-royal-hero d-flex align-items-center text-center">
  <div class="container py-5 position-relative">
    <div class="mz-royal-crest"><i class="bi bi-stars"></i></div>
    <span class="mz-royal-script d-block">Aileye Hoş Geldiniz</span>
    <h1 class="mz-royal-title">Mizan Sigorta<br>Temsilciliği</h1>

    <div class="mz-royal-divider"><i class="bi bi-gem"></i></div>

    <p class="mz-royal-sub">Sigortacılık deneyiminizi kraliyet ailesinin gücüyle birleştirin. Köklü kurumsal yapı, geniş ürün yelpazesi ve <strong style="color:#f4d35e">Güven ve Özen İle</strong> sloganının ardındaki itinayla yan yana çalışalım.</p>

    <a href="#basvuru" class="btn btn-warning btn-lg fw-bold px-5 py-3 mt-3" style="border-radius:50px;box-shadow:0 15px 40px rgba(244,211,94,.4)">
      <i class="bi bi-stars"></i> Aileye Katılın
    </a>

    <div class="mz-royal-stats">
      <div class="mz-royal-stat">
        <div class="mz-royal-stat-num">12+</div>
        <div class="mz-royal-stat-lbl">Anlaşmalı Şirket</div>
      </div>
      <div class="mz-royal-stat">
        <div class="mz-royal-stat-num">9</div>
        <div class="mz-royal-stat-lbl">Sigorta Kategorisi</div>
      </div>
      <div class="mz-royal-stat">
        <div class="mz-royal-stat-num">35+</div>
        <div class="mz-royal-stat-lbl">Ürün Çeşidi</div>
      </div>
      <div class="mz-royal-stat">
        <div class="mz-royal-stat-num">4</div>
        <div class="mz-royal-stat-lbl">Şehirde Şube</div>
      </div>
    </div>
  </div>
</section>

<!-- ====== Neden Mizan Ailesi ====== -->
<section class="mz-band">
  <div class="container">
    <div class="text-center mb-5">
      <span class="mz-script mz-script-md mz-script-red">Avantajlar</span>
      <h2 class="fw-bold display-6 mt-2">Neden Mizan'a Katılmalısınız?</h2>
      <p class="text-muted lead">Bireysel acente olarak değil, ailenin parçası olarak çalışın</p>
    </div>

    <div class="row g-4">
      <div class="col-md-6 col-lg-4">
        <div class="mz-pillar">
          <div class="mz-pillar-icon"><i class="bi bi-buildings"></i></div>
          <h5>Köklü Kurumsal Yapı</h5>
          <p class="text-muted mb-0">İstanbul'dan Aksaray'a uzanan 4 şehirli ağımız ve SBM'de levhalı kuruluşumuz ile arkanızda güçlü bir kurum.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="mz-pillar">
          <div class="mz-pillar-icon"><i class="bi bi-graph-up-arrow"></i></div>
          <h5>12+ Şirket Anlaşması</h5>
          <p class="text-muted mb-0">Anadolu, Allianz, AXA, Türkiye Sigorta, HDI ve daha pek çok şirket portföyünüze katılır. Tek elden hepsine erişim.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="mz-pillar">
          <div class="mz-pillar-icon"><i class="bi bi-headset"></i></div>
          <h5>7/24 Destek</h5>
          <p class="text-muted mb-0">Hasar takibi, teklif desteği, mevzuat danışmanlığı. Yalnız değilsiniz; her aşamada uzman ekibimiz yanınızda.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="mz-pillar">
          <div class="mz-pillar-icon"><i class="bi bi-laptop"></i></div>
          <h5>Dijital Altyapı</h5>
          <p class="text-muted mb-0">Modern teklif sistemi, otomatik hatırlatma motoru, müşteri yönetim paneli ve hasar takibi yazılımları.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="mz-pillar">
          <div class="mz-pillar-icon"><i class="bi bi-cash-stack"></i></div>
          <h5>Rekabetçi Komisyon</h5>
          <p class="text-muted mb-0">Şeffaf komisyon yapısı, performans bazlı ek primler. Ne kadar üretirseniz o kadar kazanırsınız.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="mz-pillar">
          <div class="mz-pillar-icon"><i class="bi bi-mortarboard"></i></div>
          <h5>Sürekli Eğitim</h5>
          <p class="text-muted mb-0">Yeni ürünler, mevzuat değişiklikleri, satış teknikleri. Her ay sektörel güncellemelerle hep bir adım önde olun.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ====== Quote ====== -->
<section class="mz-band bg-light">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-9">
        <div class="mz-quote text-center">
          <p class="mz-quote-text">Mizan adı, denge ve adalet anlamına gelir. Her temsilcimiz bu değerin taşıyıcısıdır — müşterimizin ihtiyacı ile çözümümüz arasındaki dengeyi kuran köprü.</p>
          <p class="mb-0 mz-script mz-script-md mz-script-red mt-3" style="font-size:2rem;color:#f4d35e !important">Güven ve Özen İle</p>
          <div class="mz-quote-author mt-2">— Mizan Sigorta Aile Felsefesi</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ====== Form ====== -->
<section class="mz-band" id="basvuru">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-9">

        <?php if ($ok): ?>
          <div class="mz-form-royal text-center">
            <div class="d-inline-flex align-items-center justify-content-center mb-4" style="width:100px;height:100px;border-radius:50%;background:linear-gradient(135deg,#f4d35e,#d4af37);font-size:3rem;color:var(--mz-navy);box-shadow:0 20px 50px rgba(244,211,94,.3)">
              <i class="bi bi-check-circle-fill"></i>
            </div>
            <span class="mz-script mz-script-lg mz-script-red d-block mb-2">Hoş geldiniz</span>
            <h2 class="fw-bold mb-3">Aile Üyeliği Başvurunuz Alındı</h2>
            <p class="text-muted lead mb-4">Yetkili ekibimiz başvurunuzu inceleyerek en kısa sürede sizinle iletişime geçecek. İlginiz için teşekkür ederiz.</p>
            <a href="<?= u('/') ?>" class="btn btn-warning fw-semibold"><i class="bi bi-house"></i> Anasayfaya Dön</a>
          </div>
        <?php else: ?>

        <div class="text-center mb-4">
          <span class="mz-script mz-script-md mz-script-red">Başvuru</span>
          <h2 class="fw-bold mt-1">Aileye Katılma Formu</h2>
          <p class="text-muted">Bilgilerinizi doldurun, yetkilimiz size dönüş yapsın.</p>
        </div>

        <div class="mz-form-royal">
          <?php if ($err): ?><div class="alert alert-danger small"><i class="bi bi-exclamation-triangle"></i> <?= e($err) ?></div><?php endif; ?>

          <form method="post" novalidate data-mz-captcha>
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e(csrf_token()) ?>">

            <h5 class="fw-bold mb-3 mt-2" style="color:var(--mz-navy)"><i class="bi bi-person-badge text-warning"></i> Kimlik Bilgileri</h5>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label small fw-semibold">Ad Soyad *</label><input type="text" name="ad_soyad" required class="form-control" value="<?= e($_POST['ad_soyad'] ?? '') ?>"></div>
              <div class="col-md-6"><label class="form-label small fw-semibold">Firma Adı (varsa)</label><input type="text" name="firma_adi" class="form-control" value="<?= e($_POST['firma_adi'] ?? '') ?>"></div>
              <div class="col-md-6"><label class="form-label small fw-semibold">E-posta *</label><input type="email" name="email" required class="form-control" value="<?= e($_POST['email'] ?? '') ?>"></div>
              <div class="col-md-6"><label class="form-label small fw-semibold">Cep Telefonu *</label><input type="tel" name="telefon" required class="form-control" placeholder="0XXX XXX XX XX" value="<?= e($_POST['telefon'] ?? '') ?>"></div>
              <div class="col-md-6"><label class="form-label small fw-semibold">İl</label><input type="text" name="il" class="form-control" placeholder="Konya" value="<?= e($_POST['il'] ?? '') ?>"></div>
              <div class="col-md-6"><label class="form-label small fw-semibold">İlçe</label><input type="text" name="ilce" class="form-control" value="<?= e($_POST['ilce'] ?? '') ?>"></div>
            </div>

            <h5 class="fw-bold mb-3 mt-4" style="color:var(--mz-navy)"><i class="bi bi-briefcase text-warning"></i> Mesleki Bilgiler</h5>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label small fw-semibold">Sigortacılık Tecrübesi (yıl)</label><input type="number" name="tecrube_yili" min="0" max="60" class="form-control" value="<?= e($_POST['tecrube_yili'] ?? '0') ?>"></div>
              <div class="col-md-6"><label class="form-label small fw-semibold">SBM Levha No (varsa)</label><input type="text" name="levha_no" class="form-control" value="<?= e($_POST['levha_no'] ?? '') ?>"></div>
              <div class="col-12"><label class="form-label small fw-semibold">Mevcut / Önceki Acentelikleriniz</label><input type="text" name="mevcut_acentelik" class="form-control" placeholder="Allianz, Anadolu Sigorta, Türkiye Sigorta..." value="<?= e($_POST['mevcut_acentelik'] ?? '') ?>"></div>
              <div class="col-12"><label class="form-label small fw-semibold">Hedefleriniz / Mesajınız</label><textarea name="aciklama" rows="4" class="form-control" placeholder="Kendinizden, deneyiminizden ve hedeflerinizden bahsedin..."><?= e($_POST['aciklama'] ?? '') ?></textarea></div>

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
                <button class="btn btn-warning btn-lg fw-semibold" style="border-radius:50px;padding:1rem"><i class="bi bi-stars"></i> Aileye Katıl</button>
              </div>
            </div>
          </form>
        </div>

        <?php endif; ?>

      </div>
    </div>
  </div>
</section>

<?php require MIZAN_INC . '/footer.php';
