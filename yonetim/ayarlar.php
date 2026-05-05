<?php
define('MZ_ADMIN', true);
$adminTitle = 'Site Ayarları';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_helpers.php';
require_role('superadmin', 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_assert_post();
    $act = (string)($_POST['action'] ?? '');

    if ($act === 'kaydet') {
        $values = (array)($_POST['v'] ?? []);
        $count = 0;
        foreach ($values as $key => $val) {
            $key = preg_replace('/[^a-z0-9_]/i', '', (string)$key);
            if ($key === '') continue;
            if (is_array($val)) $val = implode(',', $val);
            $val = (string)$val;
            // WhatsApp numarasi: otomatik uluslararasi formata cevir
            // (kullanici 0552... veya 552... yazsa da DB'ye 905526943232 olarak yaz)
            if ($key === 'whatsapp' && trim($val) !== '') {
                $val = normalize_phone($val);
            }
            // Telefon ayarlari icin de standart format
            if (in_array($key, ['telefon', 'telefon_2', 'gsm'], true) && trim($val) !== '') {
                // Sadece rakam ve + bosluk birak (kullanici okunakli yazmis olabilir, koru)
                // Ama 'telefon' gosterim icin, bozulmasin
            }
            setting_set($key, $val);
            $count++;
        }
        // Checkbox'lar gonderilmediginde 0 yapilmali
        $cbKeys = (array)($_POST['_checkboxes'] ?? []);
        foreach ($cbKeys as $cbKey) {
            $cbKey = preg_replace('/[^a-z0-9_]/i', '', (string)$cbKey);
            if (!isset($values[$cbKey]) && $cbKey !== '') setting_set($cbKey, '0');
        }
        audit_log('ayar_guncelle', 'ayarlar', null, "$count ayar güncellendi");
        admin_redirect('ayarlar.php' . (isset($_GET['tab']) ? '?tab=' . urlencode($_GET['tab']) : ''), 'success', 'Ayarlar kaydedildi.');
    }

    if ($act === 'cron_yenile') {
        $newKey = bin2hex(random_bytes(16));
        setting_set('cron_anahtar', $newKey);
        audit_log('cron_anahtar_yenile', 'ayarlar');
        admin_redirect('ayarlar.php?tab=sistem', 'success', 'Cron anahtarı yenilendi.');
    }

    if ($act === 'smtp_test') {
        $to = trim((string)($_POST['test_email'] ?? ''));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) admin_redirect('ayarlar.php?tab=smtp', 'danger', 'Geçerli bir test e-postası giriniz.');
        $body = '<h2 style="margin-top:0;color:#0d1b2a;font-size:22px">SMTP Test E-postası</h2>'
              . '<p>Bu, SMTP ayarlarınızın doğru çalıştığını doğrulamak için gönderilen bir test e-postasıdır.</p>'
              . '<table cellpadding="0" cellspacing="0" style="margin:20px 0;background:#f8fafc;border-radius:8px;width:100%">'
              . '<tr><td style="padding:14px 18px;border-bottom:1px solid #e5e7eb"><strong>Gönderim Zamanı:</strong> ' . date('d.m.Y H:i:s') . '</td></tr>'
              . '<tr><td style="padding:14px 18px;border-bottom:1px solid #e5e7eb"><strong>SMTP Sunucu:</strong> ' . e(setting('smtp_host', '-')) . ':' . e(setting('smtp_port', '-')) . '</td></tr>'
              . '<tr><td style="padding:14px 18px;border-bottom:1px solid #e5e7eb"><strong>Gönderen:</strong> ' . e(setting('smtp_from', '-')) . '</td></tr>'
              . '<tr><td style="padding:14px 18px"><strong>Şifreleme:</strong> ' . strtoupper((string) setting('smtp_secure', 'tls')) . '</td></tr>'
              . '</table>'
              . '<p>Bu mailı düzgün biçimde aldıysanız, müşterilere gidecek teklif/hasar/iletişim bildirimleri de aynı şablonla iletiliyordur. ✓</p>';
        $html = mail_template('SMTP Test', $body, [
            'badge'       => 'SMTP TEST',
            'badge_color' => '#22c55e',
            'preheader'   => 'SMTP ayarlariniz dogru calisiyor - test e-postasi',
        ]);
        $res = send_mail($to, 'Mizan Sigorta - SMTP Test', $html);
        if ($res['ok']) admin_redirect('ayarlar.php?tab=smtp', 'success', "Test e-postası $to adresine gönderildi.");
        admin_redirect('ayarlar.php?tab=smtp', 'danger', 'E-posta gönderilemedi: ' . ($res['msg'] ?? 'Bilinmeyen hata') . '. Sunucu loglarını kontrol edin.');
    }
}

$rows = db_all('SELECT * FROM ' . t('ayarlar') . ' ORDER BY grup, anahtar');
$groups = [];
foreach ($rows as $r) $groups[$r['grup']][] = $r;

$tabs = [
    'genel'    => ['Genel', 'bi-globe'],
    'iletisim' => ['İletişim', 'bi-telephone'],
    'seo'      => ['SEO', 'bi-search'],
    'sosyal'   => ['Sosyal Medya', 'bi-share'],
    'smtp'     => ['SMTP / E-posta', 'bi-envelope-gear'],
    'teklif'   => ['Teklif Ayarları', 'bi-file-text'],
    'sistem'   => ['Sistem & Güncelleme', 'bi-gear'],
];

// Insan dostu etiket + yardim metni mapping (anahtar -> [Label, Help])
$ayarLabels = [
    // GENEL
    'site_basligi'           => ['Site Başlığı', 'Tarayıcı sekmesinde ve Google sonuç başlığında gözükür'],
    'site_aciklamasi'        => ['Site Açıklaması', 'Anasayfa meta description (Google sonuçlarında alt yazı). 160 karakteri geçmesin.'],
    'site_anahtar_kelimeler' => ['SEO Anahtar Kelimeler', 'Virgülle ayırın: kasko, trafik sigortası, dask...'],
    'firma_adi'              => ['Resmi Firma Ünvanı', 'Yasal yazışma ve sözleşmelerde kullanılır'],
    'site_url'               => ['Site URL', 'https:// ile başlamalı'],
    'logo'                   => ['Logo Dosya Adı', 'uploads/sirket/ klasöründeki dosya adı'],
    'favicon'                => ['Favicon', 'Tarayıcı sekmesindeki küçük ikon'],
    'gsc_verification'       => ['Google Search Console Doğrulama', 'GSC\'den aldığınız meta-tag content değeri'],
    'sehir'                  => ['Genel Merkez Şehri', 'İstanbul, Konya, Ankara vb.'],
    'ofis_sehirler'          => ['Hizmet Verilen Şehirler', 'SEO için: Konya, İstanbul, Ankara, Aksaray'],

    // ILETISIM
    'telefon'                => ['Ana Telefon', 'Sitede ve maillerde gözükür, tıklanabilir tel: linki olur'],
    'telefon_2'              => ['Yedek Telefon', 'Opsiyonel ikinci numara'],
    'gsm'                    => ['GSM / Cep Telefonu', 'Acil çağrı için cep numarası'],
    'whatsapp'               => ['WhatsApp Numarası', '0552 694 32 32 veya 5526943232 yazsanız da otomatik 905526943232 formatına çevrilir.'],
    'email'                  => ['Ana E-posta', 'Müşteri yanıtları ve form bildirimleri buraya gelir'],
    'destek_email'           => ['Destek E-posta', 'Müşteri destek hattı için ayrı adres (opsiyonel)'],
    'adres'                  => ['Genel Merkez Adresi', 'İletişim sayfasında ve mail footer\'da görünür'],
    'kvkk_aydinlatma_sorumlu'=> ['KVKK Veri Sorumlusu', 'Aydınlatma metninde gösterilir'],
    'kvkk_iletisim'          => ['KVKK İletişim', 'Veri talepleri için iletişim bilgisi'],
    'calisma_saatleri'       => ['Çalışma Saatleri', 'Örn: Pzt-Cum 09:00-18:00'],
    'harita_embed'           => ['Google Maps Embed Kodu', '<iframe src="..."> kodu (opsiyonel)'],
    'talep_bildirim_bcc'     => ['Talep Bildirim BCC ⭐', 'ÖNEMLİ: Sisteme gelen tüm form mailleri (teklif/hasar/iletişim/temsilci) ve hatırlatmalar bu adrese BCC ile kopyalanır. Birden fazla için virgül.'],

    // SOSYAL
    'facebook'               => ['Facebook Sayfası', 'https://facebook.com/...'],
    'instagram'              => ['Instagram Hesabı', 'https://instagram.com/...'],
    'twitter'                => ['Twitter / X', 'https://x.com/...'],
    'linkedin'               => ['LinkedIn Sayfası', 'https://linkedin.com/company/...'],
    'youtube'                => ['YouTube Kanalı', 'https://youtube.com/@...'],
    'tiktok'                 => ['TikTok', 'https://tiktok.com/@...'],

    // SMTP
    'smtp_host'              => ['SMTP Sunucu', 'Örn: mail.mizansigorta.com.tr'],
    'smtp_port'              => ['SMTP Port', 'Genelde 465 (SSL) veya 587 (TLS)'],
    'smtp_user'              => ['SMTP Kullanıcı Adı', 'Genellikle e-posta adresi (örn: bilgi@...)'],
    'smtp_pass'              => ['SMTP Şifre', 'E-posta hesabının şifresi'],
    'smtp_secure'            => ['Şifreleme Tipi', 'tls (587), ssl (465) veya yok'],
    'smtp_from'              => ['Gönderen E-posta', 'Maillerin "kimden" alanında görünen adres'],
    'smtp_from_name'         => ['Gönderen Adı', 'Maillerin başında görünen ad (örn: Mizan Sigorta)'],

    // TEKLIF
    'teklif_bildirim_email'  => ['Teklif Bildirim E-postası', 'Yeni teklif talebi geldiğinde bildirim alır'],
    'hasar_bildirim_email'   => ['Hasar Bildirim E-postası', 'Yeni hasar ihbarı bildirimi gelir'],
    'iletisim_bildirim_email'=> ['İletişim Mesaj Bildirim', 'Web sitesi iletişim formu mesajları'],
    'teklif_otomatik_no'     => ['Teklif No Formatı', 'TKL-{tarih}-{rastgele} gibi'],

    // SISTEM
    'cron_anahtar'           => ['Cron Güvenlik Anahtarı', 'Hatırlatma cron\'unu URL\'den tetiklemek için. Aşağıdan yenileyebilirsiniz.'],
    'site_durum'             => ['Site Durumu', 'aktif / bakim'],
    'bakim_mesaji'           => ['Bakım Modu Mesajı', 'Site bakımdayken ziyaretçiye gösterilen metin'],
    'gtag_id'                => ['Google Analytics ID', 'G-XXXXXXXXXX formatında'],
    'meta_pixel_id'          => ['Meta (Facebook) Pixel ID', 'Sadece rakam, örn: 123456789'],
];

$labelOf = function(array $r) use ($ayarLabels): array {
    if (isset($ayarLabels[$r['anahtar']])) return $ayarLabels[$r['anahtar']];
    // Mapping yoksa: aciklama varsa onu label, anahtar'i help olarak goster
    $label = $r['aciklama'] ?: ucwords(str_replace('_', ' ', $r['anahtar']));
    return [$label, ''];
};

$activeTab = (string)($_GET['tab'] ?? 'genel');
if (!isset($tabs[$activeTab])) $activeTab = 'genel';

$renderField = function(array $r) {
    $name = $r['anahtar']; $val = $r['deger']; $tip = $r['tip']; $secs = $r['secenekler'];
    $cls = 'form-control form-control-sm';
    switch ($tip) {
        case 'textarea':
            return '<textarea name="v[' . e($name) . ']" rows="4" class="' . $cls . '">' . e($val) . '</textarea>';
        case 'number':
            return '<input type="number" name="v[' . e($name) . ']" class="' . $cls . '" value="' . e($val) . '">';
        case 'email':
            return '<input type="email" name="v[' . e($name) . ']" class="' . $cls . '" value="' . e($val) . '">';
        case 'tel':
            return '<input type="tel" name="v[' . e($name) . ']" class="' . $cls . '" value="' . e($val) . '">';
        case 'url':
            return '<input type="url" name="v[' . e($name) . ']" class="' . $cls . '" value="' . e($val) . '" placeholder="https://...">';
        case 'password':
            return '<input type="password" name="v[' . e($name) . ']" class="' . $cls . '" value="' . e($val) . '" autocomplete="new-password">';
        case 'color':
            return '<input type="color" name="v[' . e($name) . ']" class="form-control form-control-color" value="' . e($val ?: '#0d1b2a') . '">';
        case 'checkbox':
            return '<input type="hidden" name="_checkboxes[]" value="' . e($name) . '">'
                 . '<div class="form-check form-switch"><input type="checkbox" name="v[' . e($name) . ']" value="1" id="cb_' . e($name) . '" class="form-check-input" ' . ($val=='1'?'checked':'') . '><label for="cb_' . e($name) . '" class="form-check-label small">Etkin</label></div>';
        case 'select':
            $opts = $secs ? explode(',', $secs) : ['tls','ssl','yok'];
            $h = '<select name="v[' . e($name) . ']" class="form-select form-select-sm">';
            foreach ($opts as $o) {
                $o = trim($o);
                $h .= '<option value="' . e($o) . '"' . ($val===$o?' selected':'') . '>' . e($o) . '</option>';
            }
            return $h . '</select>';
        default:
            return '<input type="text" name="v[' . e($name) . ']" class="' . $cls . '" value="' . e($val) . '">';
    }
};
?>

<style>
.mz-ayar-card {border:1px solid #e5e7eb; border-radius:10px; padding:18px 20px; margin-bottom:14px; background:#fff; transition:all .2s ease; position:relative}
.mz-ayar-card:hover {border-color:#cbd5e1; box-shadow:0 2px 8px rgba(13,27,42,.05)}
.mz-ayar-label {font-size:14px; font-weight:700; color:#0f172a; margin-bottom:6px; display:flex; align-items:center; gap:6px}
.mz-ayar-help {font-size:12.5px; color:#64748b; margin-bottom:10px; line-height:1.55}
.mz-ayar-key {position:absolute; top:14px; right:18px; font-size:10px; color:#cbd5e1; font-family:'Monaco',monospace; user-select:all}
.mz-ayar-key:hover {color:#94a3b8; cursor:copy}
.mz-ayar-card input.form-control, .mz-ayar-card textarea, .mz-ayar-card select {border:1px solid #d1d5db; border-radius:8px; padding:10px 12px; font-size:14px; transition:border .15s; font-size:14px !important}
.mz-ayar-card input.form-control:focus, .mz-ayar-card textarea:focus, .mz-ayar-card select:focus {border-color:#0d6efd; box-shadow:0 0 0 3px rgba(13,110,253,.12)}
.mz-ayar-section-header {display:flex; align-items:center; gap:12px; margin:6px 0 18px; padding-bottom:14px; border-bottom:2px solid #f1f5f9}
.mz-ayar-section-header .mz-icon-bg {width:40px; height:40px; border-radius:10px; background:linear-gradient(135deg,#0d1b2a,#1b263b); color:#f4d35e; display:flex; align-items:center; justify-content:center; font-size:18px}
.mz-ayar-section-header h5 {margin:0; font-weight:700; color:#0f172a; font-size:18px}
.mz-ayar-section-header p {margin:2px 0 0; font-size:12.5px; color:#64748b}
.mz-ayar-sticky-bar {position:sticky; bottom:0; background:#fff; border-top:1px solid #e5e7eb; padding:14px 20px; margin:18px -20px -20px; border-radius:0 0 8px 8px; box-shadow:0 -4px 12px rgba(0,0,0,.04); z-index:10}
.mz-dirty-indicator {display:none; color:#f59e0b; font-size:13px; font-weight:600; margin-right:14px}
.mz-dirty-indicator.show {display:inline-flex; align-items:center; gap:6px}
.mz-dirty-indicator i {animation:mz-pulse 1.5s infinite}
@keyframes mz-pulse {0%,100% {opacity:1} 50% {opacity:.5}}
.mz-ayar-tab .nav-link {padding:10px 16px; border-radius:10px; font-weight:600; color:#475569; transition:all .2s}
.mz-ayar-tab .nav-link:hover {background:#f1f5f9; color:#0f172a}
.mz-ayar-tab .nav-link.active {background:linear-gradient(135deg,#0d1b2a,#1b263b); color:#fff; box-shadow:0 4px 12px rgba(13,27,42,.15)}
.mz-ayar-tab .nav-link i {margin-right:6px}
</style>

<ul class="nav nav-pills mb-4 flex-wrap mz-ayar-tab">
  <?php foreach ($tabs as $key => $info): ?>
    <li class="nav-item me-2 mb-2"><a class="nav-link <?= $activeTab===$key?'active':'' ?>" href="?tab=<?= e($key) ?>"><i class="bi <?= $info[1] ?>"></i> <?= e($info[0]) ?></a></li>
  <?php endforeach; ?>
</ul>

<div class="card border-0 shadow-sm">
  <div class="card-body p-4">
    <div class="mz-ayar-section-header">
      <div class="mz-icon-bg"><i class="bi <?= $tabs[$activeTab][1] ?>"></i></div>
      <div>
        <h5><?= e($tabs[$activeTab][0]) ?></h5>
        <p>Bu sekmede <?= count($groups[$activeTab] ?? []) ?> ayar bulunuyor. Değişiklikleri kaydetmeyi unutmayın.</p>
      </div>
    </div>

    <form method="post" id="ayarForm">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="kaydet">
      <div class="row g-3">
        <?php
        $items = $groups[$activeTab] ?? [];
        foreach ($items as $r):
            [$label, $help] = $labelOf($r);
            $col = ($r['tip'] === 'textarea') ? 12 : 6;
        ?>
          <div class="col-md-<?= $col ?>">
            <div class="mz-ayar-card">
              <span class="mz-ayar-key" title="Sistemdeki teknik anahtar (kopyalamak için tıklayın)" onclick="navigator.clipboard.writeText('<?= e($r['anahtar']) ?>')">&lt;<?= e($r['anahtar']) ?>&gt;</span>
              <div class="mz-ayar-label"><?= e($label) ?></div>
              <?php if ($help): ?>
                <div class="mz-ayar-help"><?= e($help) ?></div>
              <?php endif; ?>
              <?= $renderField($r) ?>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (!$items): ?>
          <div class="col-12"><div class="alert alert-info"><i class="bi bi-info-circle"></i> Bu sekmede henüz ayar yok.</div></div>
        <?php endif; ?>
      </div>

      <?php if ($items): ?>
      <div class="mz-ayar-sticky-bar d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="mz-dirty-indicator" id="mzDirty">
          <i class="bi bi-exclamation-circle-fill"></i> Kaydedilmemiş değişiklikler var
        </span>
        <div class="ms-auto d-flex gap-2">
          <a href="?tab=<?= e($activeTab) ?>" class="btn btn-light btn-sm" id="mzResetBtn" style="display:none">
            <i class="bi bi-x"></i> Vazgeç
          </a>
          <button class="btn btn-primary fw-semibold" type="submit"><i class="bi bi-save"></i> Ayarları Kaydet</button>
        </div>
      </div>
      <?php endif; ?>
    </form>

    <?php if ($activeTab === 'sistem'): ?>
      <hr class="my-4">
      <div class="mz-ayar-section-header">
        <div class="mz-icon-bg" style="background:linear-gradient(135deg,#f59e0b,#d97706); color:#fff"><i class="bi bi-key"></i></div>
        <div>
          <h5>Cron Anahtarı</h5>
          <p>Hatırlatma cron'unu otomatik çalıştırmak için</p>
        </div>
      </div>
      <p class="small text-muted mb-3"><i class="bi bi-info-circle text-primary"></i> <strong>DirectAdmin → Advanced Features → Cron Jobs</strong> ekranını açın ve aşağıdaki bilgileri 6 alana yapıştırın (her gün saat 09:00'da çalıştırmak için):</p>

      <?php
      $cronUrl = SITE_BASE_URL . '/cron/teklif-hatirlatma.php?key=' . (setting('cron_anahtar') ?: 'ANAHTAR_BELIRLENMEDI');
      $cronCmd = '/usr/bin/curl -s "' . $cronUrl . '" > /dev/null 2>&1';
      $cronCmdAlt = 'wget -q -O- "' . $cronUrl . '" > /dev/null 2>&1';
      ?>

      <!-- DirectAdmin alan-bazli kart -->
      <div class="card border-0 mb-3" style="background:#f8fafc;border:1px solid #e5e7eb !important;border-radius:10px">
        <div class="card-body p-3">
          <div class="small fw-bold text-uppercase text-muted mb-2" style="letter-spacing:.5px"><i class="bi bi-clock"></i> Zamanlama (DirectAdmin alanları)</div>
          <table class="table table-sm mb-0" style="background:transparent">
            <thead>
              <tr style="font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.4px">
                <th style="border:0">Minute</th>
                <th style="border:0">Hour</th>
                <th style="border:0">Day</th>
                <th style="border:0">Month</th>
                <th style="border:0">Day of Week</th>
              </tr>
            </thead>
            <tbody>
              <tr style="font-family:Monaco,monospace;font-size:14px;font-weight:700;color:#0d1b2a">
                <td style="border:0;background:#fff;border-radius:6px;padding:8px 10px"><span title="Saatin 0. dakikası">0</span></td>
                <td style="border:0;background:#fff;border-radius:6px;padding:8px 10px;margin-left:4px"><span title="Saat 09:00">9</span></td>
                <td style="border:0;background:#fff;border-radius:6px;padding:8px 10px"><span title="Her ayın her günü">*</span></td>
                <td style="border:0;background:#fff;border-radius:6px;padding:8px 10px"><span title="Her ay">*</span></td>
                <td style="border:0;background:#fff;border-radius:6px;padding:8px 10px"><span title="Her gün">*</span></td>
              </tr>
            </tbody>
          </table>
          <div class="small text-muted mt-1" style="font-size:11.5px">
            <i class="bi bi-lightbulb"></i> Bu zamanlama: <strong>her gün saat 09:00'da</strong> çalışır.
            Saati değiştirmek için "Hour" değerini güncelleyin (örn: 10:00 için 10, 14:30 için Minute=30, Hour=14).
          </div>
        </div>
      </div>

      <!-- Komut alani (kopyalama butonlu) -->
      <div class="mb-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="small fw-bold text-uppercase text-muted" style="letter-spacing:.5px"><i class="bi bi-terminal"></i> Komut (Command alanı)</div>
        <small class="text-success"><i class="bi bi-check-circle-fill"></i> Önerilen: <strong>curl</strong> (DirectAdmin'de daha güvenilir)</small>
      </div>
      <div class="position-relative mb-3">
        <pre class="bg-dark text-white p-3 rounded font-monospace small mb-0" style="word-break:break-all;white-space:pre-wrap;font-size:12.5px;line-height:1.6" id="cronCmdBox"><?= e($cronCmd) ?></pre>
        <button type="button" class="btn btn-sm btn-light position-absolute top-0 end-0 m-2" onclick="navigator.clipboard.writeText(document.getElementById('cronCmdBox').textContent).then(()=>{this.innerHTML='<i class=\'bi bi-check\'></i> Kopyalandı';setTimeout(()=>this.innerHTML='<i class=\'bi bi-clipboard\'></i> Kopyala',2000)})" style="font-size:11px"><i class="bi bi-clipboard"></i> Kopyala</button>
      </div>

      <!-- Alternatif: wget -->
      <details class="mb-3">
        <summary class="small text-muted" style="cursor:pointer"><i class="bi bi-chevron-right"></i> Alternatif: <code>wget</code> komutu (curl çalışmazsa)</summary>
        <div class="position-relative mt-2">
          <pre class="bg-dark text-white p-3 rounded font-monospace small mb-0" style="word-break:break-all;white-space:pre-wrap;font-size:12.5px;line-height:1.6" id="cronCmdAlt"><?= e($cronCmdAlt) ?></pre>
          <button type="button" class="btn btn-sm btn-light position-absolute top-0 end-0 m-2" onclick="navigator.clipboard.writeText(document.getElementById('cronCmdAlt').textContent).then(()=>{this.innerHTML='<i class=\'bi bi-check\'></i> Kopyalandı';setTimeout(()=>this.innerHTML='<i class=\'bi bi-clipboard\'></i> Kopyala',2000)})" style="font-size:11px"><i class="bi bi-clipboard"></i> Kopyala</button>
        </div>
      </details>

      <!-- DirectAdmin adim-adim ipucu -->
      <div class="alert alert-info small mb-3" style="border-left:4px solid #0d6efd;background:#eff6ff">
        <strong><i class="bi bi-question-circle"></i> DirectAdmin'de nereye eklenecek?</strong>
        <ol class="mb-0 mt-1" style="padding-left:1.4rem;font-size:13px">
          <li>DirectAdmin'e giriş yapın</li>
          <li>Sol menü <strong>"Advanced Features"</strong> → <strong>"Cron Jobs"</strong></li>
          <li>Yukarıdaki 5 zaman alanını (0, 9, *, *, *) doldurun</li>
          <li>Komut kutusuna yukarıdaki <code>curl ...</code> komutunu yapıştırın</li>
          <li><strong>"Add"</strong> butonuna tıklayın → İşlem tamam ✓</li>
        </ol>
      </div>

      <form method="post" class="d-inline" onsubmit="return confirm('Cron anahtarı yenilensin mi?\n\nMevcut cron komutunuzu güncellemeniz gerekecek (yeni anahtarla yukarıdaki komutu DirectAdmin\'de tekrar oluşturun).');">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="cron_yenile">
        <button class="btn btn-warning btn-sm fw-semibold"><i class="bi bi-arrow-repeat"></i> Cron Anahtarını Yenile</button>
      </form>
    <?php endif; ?>

    <?php if ($activeTab === 'smtp'): ?>
      <hr class="my-4">
      <div class="mz-ayar-section-header">
        <div class="mz-icon-bg" style="background:linear-gradient(135deg,#22c55e,#16a34a); color:#fff"><i class="bi bi-send"></i></div>
        <div>
          <h5>SMTP Test</h5>
          <p>Mail ayarlarının doğru çalışıp çalışmadığını test edin</p>
        </div>
      </div>
      <form method="post" class="row g-2 align-items-end">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="smtp_test">
        <div class="col-md-6"><input type="email" name="test_email" required class="form-control form-control-sm" placeholder="test@example.com"></div>
        <div class="col-md-3"><button class="btn btn-warning btn-sm w-100"><i class="bi bi-send"></i> Test Gönder</button></div>
        <div class="col-12 small text-muted">Önce SMTP ayarlarını kaydetmeyi unutmayın.</div>
      </form>
    <?php endif; ?>
  </div>
</div>

<script>
// Form dirty state - degisiklik var ise sticky bar'a uyari + sayfa terkinde onay
(function(){
  var form = document.getElementById('ayarForm');
  if (!form) return;
  var dirtyEl = document.getElementById('mzDirty');
  var resetEl = document.getElementById('mzResetBtn');
  var initial = new FormData(form);
  var initialJson = {};
  for (var pair of initial.entries()) initialJson[pair[0]] = pair[1];
  var isDirty = false;

  function checkDirty() {
    var current = new FormData(form);
    var changed = false;
    var seen = {};
    for (var pair of current.entries()) {
      seen[pair[0]] = true;
      if ((initialJson[pair[0]] || '') !== pair[1]) { changed = true; break; }
    }
    if (!changed) for (var k in initialJson) if (!(k in seen) && initialJson[k]) { changed = true; break; }
    isDirty = changed;
    dirtyEl.classList.toggle('show', changed);
    resetEl.style.display = changed ? 'inline-block' : 'none';
  }

  form.addEventListener('input', checkDirty);
  form.addEventListener('change', checkDirty);

  // Form submit edilince dirty yok
  form.addEventListener('submit', function(){ isDirty = false; });

  // Sayfayi terk etmeden once uyar
  window.addEventListener('beforeunload', function(e){
    if (isDirty) { e.preventDefault(); e.returnValue = ''; return ''; }
  });
})();
</script>

<?php require __DIR__ . '/_footer.php';
