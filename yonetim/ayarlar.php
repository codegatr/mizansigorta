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
            setting_set($key, (string)$val);
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

<ul class="nav nav-pills mb-3 flex-wrap">
  <?php foreach ($tabs as $key => $info): ?>
    <li class="nav-item"><a class="nav-link <?= $activeTab===$key?'active':'' ?>" href="?tab=<?= e($key) ?>"><i class="bi <?= $info[1] ?>"></i> <?= e($info[0]) ?></a></li>
  <?php endforeach; ?>
</ul>

<div class="card border-0 shadow-sm">
  <div class="card-body">
    <h5 class="fw-bold mb-3"><i class="bi <?= $tabs[$activeTab][1] ?> text-warning"></i> <?= e($tabs[$activeTab][0]) ?></h5>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="kaydet">
      <div class="row g-3">
        <?php
        $items = $groups[$activeTab] ?? [];
        foreach ($items as $r):
            $col = ($r['tip'] === 'textarea') ? 12 : 6;
        ?>
          <div class="col-md-<?= $col ?>">
            <label class="form-label small fw-semibold"><?= e($r['aciklama'] ?: $r['anahtar']) ?></label>
            <?= $renderField($r) ?>
            <div class="form-text small text-muted">Anahtar: <code><?= e($r['anahtar']) ?></code></div>
          </div>
        <?php endforeach; ?>
        <?php if (!$items): ?>
          <div class="col-12"><div class="alert alert-info">Bu sekmede henüz ayar yok.</div></div>
        <?php endif; ?>
      </div>
      <hr>
      <div class="d-flex justify-content-between">
        <button class="btn btn-primary btn-sm fw-semibold"><i class="bi bi-save"></i> Ayarları Kaydet</button>
      </div>
    </form>

    <?php if ($activeTab === 'sistem'): ?>
      <hr>
      <h6 class="fw-bold mb-3"><i class="bi bi-key text-warning"></i> Cron Anahtarı</h6>
      <p class="small text-muted">Hatırlatma cron'unu çalıştırmak için aşağıdaki komutu cPanel/DirectAdmin → Cron Jobs ekranına ekleyin (her gün saat 09:00):</p>
      <div class="bg-dark text-white p-3 rounded font-monospace small mb-2"><?= e('wget -q -O- "' . SITE_BASE_URL . '/cron/teklif-hatirlatma.php?key=' . (setting('cron_anahtar') ?: 'ANAHTAR_BELIRLENMEDI') . '" > /dev/null 2>&1') ?></div>
      <form method="post" class="d-inline" onsubmit="return confirm('Cron anahtarı yenilensin mi? Mevcut cron komutunuzu güncellemeniz gerekecek.');">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="cron_yenile">
        <button class="btn btn-warning btn-sm"><i class="bi bi-arrow-repeat"></i> Cron Anahtarını Yenile</button>
      </form>
    <?php endif; ?>

    <?php if ($activeTab === 'smtp'): ?>
      <hr>
      <h6 class="fw-bold mb-3"><i class="bi bi-send text-warning"></i> SMTP Test</h6>
      <form method="post" class="row g-2">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="smtp_test">
        <div class="col-md-6"><input type="email" name="test_email" required class="form-control form-control-sm" placeholder="test@example.com"></div>
        <div class="col-md-3"><button class="btn btn-warning btn-sm w-100"><i class="bi bi-send"></i> Test Gönder</button></div>
        <div class="col-12 small text-muted">Önce SMTP ayarlarını kaydetmeyi unutmayın.</div>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/_footer.php';
