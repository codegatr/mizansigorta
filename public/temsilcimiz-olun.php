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
            $extra     = talep_bildirim_alicilari();
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
                @send_mail(
                    $adminMail,
                    'Yeni Temsilci Başvurusu — ' . $ad,
                    mail_template('Temsilci Başvurusu', $govde, [
                        'badge'       => 'TEMSİLCİ BAŞVURUSU',
                        'badge_color' => '#0d6efd',
                        'preheader'   => 'Yeni temsilci basvurusu: ' . $ad,
                    ]),
                    '',
                    [
                        'bcc'        => $extra['bcc'],
                        'reply_to'   => $email ?: null,
                        'ilgili_tip' => 'temsilci',
                    ]
                );
            }

            @send_mail(
                $email,
                'Başvurunuz alındı — Mizan Sigorta',
                mail_template('Başvurunuz alındı',
                    '<p>Sayın <b>' . e($ad) . '</b>,</p>'
                  . '<p>Mizan Sigorta temsilciliği başvurunuzu aldık. Yetkili ekibimiz başvurunuzu değerlendirecek ve en kısa sürede sizinle iletişime geçecektir.</p>'
                  . '<p>İlginiz için teşekkür ederiz.</p>',
                    [
                        'badge'       => 'BAŞVURUNUZ ALINDI',
                        'badge_color' => '#22c55e',
                        'preheader'   => 'Mizan Sigorta temsilcilik basvurunuz alindi.',
                    ]
                ),
                '',
                [
                    'bcc'        => $extra['bcc'],
                    'ilgili_tip' => 'temsilci',
                ]
            );

            $ok = true;
        }
    }
}

$pageTitle = 'Temsilcimiz Olun - ' . SITE_NAME;
$pageDesc  = 'Mizan Sigorta temsilcisi olun. Güçlü altyapı, geniş ürün yelpazesi ve profesyonel destek ile büyüyün.';
require MIZAN_INC . '/header.php';
?>

<!-- HERO -->
<section style="background:linear-gradient(135deg,var(--mz-navy),var(--mz-navy-2));color:#fff;padding:5rem 0 4rem;position:relative;overflow:hidden">
  <div style="position:absolute;inset:0;background:radial-gradient(ellipse at top right,rgba(227,11,48,.15) 0%,transparent 50%);pointer-events:none"></div>
  <div class="container position-relative">
    <div class="row align-items-center">
      <div class="col-lg-7">
        <span class="badge bg-warning text-dark mb-3 px-3 py-2 fw-semibold"><i class="bi bi-briefcase"></i> Acente Başvurusu</span>
        <h1 class="display-5 fw-bold mb-3">Mizan Sigorta Temsilcisi Olun</h1>
        <p class="lead text-white-50 mb-0 mb-md-4" style="max-width:560px">Köklü kurumsal yapı, 12+ anlaşmalı şirket portföyü ve profesyonel destek altyapısı ile yan yana çalışalım. Bağımsız acente olarak büyümeniz için güçlü bir ortak.</p>
        <div class="mt-4 d-flex gap-3 flex-wrap">
          <a href="#basvuru" class="btn btn-warning fw-semibold"><i class="bi bi-arrow-down-circle"></i> Başvuru Formu</a>
          <a href="#avantajlar" class="btn btn-outline-light"><i class="bi bi-info-circle"></i> Avantajları İncele</a>
        </div>
      </div>
      <div class="col-lg-5 d-none d-lg-block text-center">
        <div style="display:inline-flex;align-items:center;justify-content:center;width:200px;height:200px;border-radius:50%;background:rgba(227,11,48,.15);border:2px solid rgba(227,11,48,.3)">
          <i class="bi bi-handshake" style="font-size:5rem;color:var(--mz-red)"></i>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- İSTATİSTİK STRIP -->
<section style="background:#fff;border-bottom:1px solid #f1f5f9">
  <div class="container py-4">
    <div class="row text-center g-3">
      <div class="col-6 col-md-3">
        <div class="fw-bold" style="font-size:2rem;color:var(--mz-red)">12+</div>
        <small class="text-muted">Anlaşmalı Şirket</small>
      </div>
      <div class="col-6 col-md-3">
        <div class="fw-bold" style="font-size:2rem;color:var(--mz-red)">9</div>
        <small class="text-muted">Sigorta Kategorisi</small>
      </div>
      <div class="col-6 col-md-3">
        <div class="fw-bold" style="font-size:2rem;color:var(--mz-red)">35+</div>
        <small class="text-muted">Ürün Çeşidi</small>
      </div>
      <div class="col-6 col-md-3">
        <div class="fw-bold" style="font-size:2rem;color:var(--mz-red)">4</div>
        <small class="text-muted">Şehirde Şube</small>
      </div>
    </div>
  </div>
</section>

<!-- AVANTAJLAR -->
<section class="mz-band" id="avantajlar">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="fw-bold">Neden Mizan Sigorta?</h2>
      <p class="text-muted">Acente olarak başarınızı destekleyen 6 temel avantaj</p>
    </div>

    <div class="row g-4">
      <?php
      $avantajlar = [
          ['bi-buildings', 'Köklü Kurumsal Yapı', 'İstanbul Genel Merkez ve 3 şube ağı; SBM\'de levhalı, mevzuata uyumlu kurumsal alt yapı.'],
          ['bi-graph-up-arrow', '12+ Şirket Anlaşması', 'Anadolu, Allianz, AXA, Türkiye Sigorta, HDI, Quick, Neova, Ak, Doğa Sigorta gibi köklü şirketler tek elden.'],
          ['bi-headset', '7/24 Operasyonel Destek', 'Hasar takibi, teklif desteği, mevzuat danışmanlığı. Yalnız değilsiniz; her aşamada uzman ekibimiz yanınızda.'],
          ['bi-laptop', 'Dijital Altyapı', 'Modern teklif sistemi, otomatik hatırlatma motoru, müşteri yönetimi ve hasar takip yazılımları.'],
          ['bi-cash-stack', 'Rekabetçi Komisyon', 'Şeffaf komisyon yapısı, performans bazlı ek primler. Üretiminize göre artan kazanç.'],
          ['bi-mortarboard', 'Sürekli Eğitim', 'Yeni ürünler, mevzuat değişiklikleri, satış teknikleri. Her ay sektörel güncellemelerle bir adım önde olun.'],
      ];
      foreach ($avantajlar as $a): ?>
        <div class="col-md-6 col-lg-4">
          <div class="h-100 p-4 bg-white rounded-3" style="border:1px solid #e5e7eb;transition:all .2s" onmouseover="this.style.borderColor='var(--mz-red)';this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 30px rgba(15,30,55,.08)'" onmouseout="this.style.borderColor='#e5e7eb';this.style.transform='';this.style.boxShadow=''">
            <div class="d-inline-flex align-items-center justify-content-center mb-3" style="width:48px;height:48px;border-radius:10px;background:rgba(227,11,48,.08);color:var(--mz-red);font-size:1.4rem">
              <i class="bi <?= $a[0] ?>"></i>
            </div>
            <h5 class="fw-bold" style="color:var(--mz-navy)"><?= e($a[1]) ?></h5>
            <p class="text-muted mb-0 small"><?= e($a[2]) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- KİME UYGUN -->
<section class="mz-band bg-light">
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-lg-6">
        <h3 class="fw-bold mb-3">Kimler Başvurabilir?</h3>
        <p class="text-muted mb-4">Sigortacılık tecrübesi olan veya bu sektörde kariyerine yön vermek isteyen herkesi temsilci olarak değerlendiriyoruz.</p>

        <div class="d-flex gap-3 mb-3">
          <i class="bi bi-check-circle-fill text-success fs-4 flex-shrink-0"></i>
          <div>
            <strong>SBM Levhalı Acenteler</strong>
            <p class="text-muted small mb-0">Mevcut acente sahipleri, portföyünü genişletmek isteyenler.</p>
          </div>
        </div>
        <div class="d-flex gap-3 mb-3">
          <i class="bi bi-check-circle-fill text-success fs-4 flex-shrink-0"></i>
          <div>
            <strong>Sigorta Sektörü Tecrübeli Profesyoneller</strong>
            <p class="text-muted small mb-0">Brokerlikte, sigorta şirketinde çalışmış ve kendi acentesini açmak isteyenler.</p>
          </div>
        </div>
        <div class="d-flex gap-3 mb-3">
          <i class="bi bi-check-circle-fill text-success fs-4 flex-shrink-0"></i>
          <div>
            <strong>Ticari Geçmişi Olan Girişimciler</strong>
            <p class="text-muted small mb-0">Sigorta sektörüne ilgi duyan, müşteri ilişkileri tecrübesi olan girişimciler.</p>
          </div>
        </div>
        <div class="d-flex gap-3">
          <i class="bi bi-check-circle-fill text-success fs-4 flex-shrink-0"></i>
          <div>
            <strong>Kurumsal Firmalar</strong>
            <p class="text-muted small mb-0">Mevcut müşteri portföyüne sigorta hizmeti eklemek isteyen şirketler.</p>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
          <div class="card-body p-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-list-check text-warning"></i> Başvuru Süreci</h5>

            <div class="position-relative">
              <?php
              $adimlar = [
                  ['Form Doldurma', 'Sağdaki başvuru formunu doldurun, bilgileriniz tarafımıza ulaşsın.'],
                  ['Ön Görüşme', 'Yetkili ekibimiz 1-3 iş günü içinde sizi arar.'],
                  ['Belge Hazırlama', 'Gerekli evrakları (levha, kimlik, vergi vb.) toplarsınız.'],
                  ['Sözleşme', 'Karşılıklı uygunluk durumunda temsilci sözleşmesi imzalanır.'],
                  ['Eğitim & Aktivasyon', 'Sistem eğitimleri verilir, üretime başlayabilirsiniz.'],
              ];
              foreach ($adimlar as $i => $ad): ?>
                <div class="d-flex gap-3 mb-3">
                  <div class="flex-shrink-0" style="width:32px;height:32px;border-radius:50%;background:var(--mz-red);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem"><?= $i + 1 ?></div>
                  <div>
                    <strong><?= e($ad[0]) ?></strong>
                    <p class="text-muted small mb-0"><?= e($ad[1]) ?></p>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- BAŞVURU FORMU -->
<section class="mz-band" id="basvuru">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-9">

        <?php if ($ok): ?>
          <div class="card border-0 shadow-sm text-center p-5">
            <div class="mx-auto mb-4 d-inline-flex align-items-center justify-content-center" style="width:80px;height:80px;border-radius:50%;background:rgba(34,197,94,.12);color:#16a34a;font-size:2.5rem">
              <i class="bi bi-check-circle-fill"></i>
            </div>
            <h2 class="fw-bold mb-2">Başvurunuz alındı</h2>
            <p class="text-muted mb-4">Yetkili ekibimiz başvurunuzu inceleyerek 1-3 iş günü içinde sizinle iletişime geçecek. İlginiz için teşekkür ederiz.</p>
            <div>
              <a href="<?= u('/') ?>" class="btn btn-warning fw-semibold"><i class="bi bi-house"></i> Anasayfaya Dön</a>
            </div>
          </div>
        <?php else: ?>

        <div class="text-center mb-4">
          <h2 class="fw-bold">Başvuru Formu</h2>
          <p class="text-muted">Bilgilerinizi girin, ekibimiz sizinle iletişime geçsin.</p>
        </div>

        <div class="card border-0 shadow-sm">
          <div class="card-body p-4 p-md-5">
            <?php if ($err): ?><div class="alert alert-danger small"><i class="bi bi-exclamation-triangle"></i> <?= e($err) ?></div><?php endif; ?>

            <form method="post" novalidate data-mz-captcha>
              <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e(csrf_token()) ?>">

              <h6 class="fw-bold text-uppercase text-muted small mb-3"><i class="bi bi-person"></i> Kimlik Bilgileri</h6>
              <div class="row g-3 mb-4">
                <div class="col-md-6"><label class="form-label small fw-semibold">Ad Soyad *</label><input type="text" name="ad_soyad" autocomplete="name" required class="form-control" value="<?= e($_POST['ad_soyad'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Firma Adı (varsa)</label><input type="text" name="firma_adi" class="form-control" value="<?= e($_POST['firma_adi'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">E-posta *</label><input type="email" name="email" inputmode="email" autocomplete="email" required class="form-control" value="<?= e($_POST['email'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Cep Telefonu *</label><input type="tel" name="telefon" inputmode="tel" autocomplete="tel" required class="form-control" placeholder="0XXX XXX XX XX" value="<?= e($_POST['telefon'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">İl</label><input type="text" name="il" class="form-control" placeholder="İstanbul" value="<?= e($_POST['il'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">İlçe</label><input type="text" name="ilce" class="form-control" placeholder="Ataşehir" value="<?= e($_POST['ilce'] ?? '') ?>"></div>
              </div>

              <h6 class="fw-bold text-uppercase text-muted small mb-3"><i class="bi bi-briefcase"></i> Mesleki Bilgiler</h6>
              <div class="row g-3 mb-4">
                <div class="col-md-6"><label class="form-label small fw-semibold">Sigortacılık Tecrübesi (yıl)</label><input type="number" name="tecrube_yili" min="0" max="60" class="form-control" value="<?= e($_POST['tecrube_yili'] ?? '0') ?>"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">SBM Levha No (varsa)</label><input type="text" name="levha_no" class="form-control" value="<?= e($_POST['levha_no'] ?? '') ?>"></div>
                <div class="col-12"><label class="form-label small fw-semibold">Mevcut / Önceki Acentelikleriniz</label><input type="text" name="mevcut_acentelik" class="form-control" placeholder="Örn: Allianz, Anadolu Sigorta..." value="<?= e($_POST['mevcut_acentelik'] ?? '') ?>"></div>
                <div class="col-12"><label class="form-label small fw-semibold">Hedefleriniz / Açıklama</label><textarea name="aciklama" rows="3" class="form-control" placeholder="Kendinizden, deneyiminizden ve hedeflerinizden kısaca bahsedin..."><?= e($_POST['aciklama'] ?? '') ?></textarea></div>
              </div>

              <div class="row g-3 align-items-end">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Doğrulama: <span data-mz-cap-a></span> + <span data-mz-cap-b></span> = ?</label>
                  <input type="number" name="cap_input" required class="form-control" data-mz-cap-input>
                  <input type="hidden" name="cap_correct" data-mz-cap-correct>
                </div>
                <div class="col-md-6">
                  <div class="form-check">
                    <input type="checkbox" name="kvkk" id="kvkk" class="form-check-input" required <?= !empty($_POST['kvkk'])?'checked':'' ?>>
                    <label for="kvkk" class="form-check-label small">
                      <a href="<?= u('/sayfa/kvkk') ?>" target="_blank">KVKK Aydınlatma Metni</a>'ni okudum, onaylıyorum. *
                    </label>
                  </div>
                </div>
                <div class="col-12 d-grid mt-3">
                  <button class="btn btn-warning btn-lg fw-semibold"><i class="bi bi-send"></i> Başvuruyu Gönder</button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <?php endif; ?>

      </div>
    </div>
  </div>
</section>

<?php require MIZAN_INC . '/footer.php';
