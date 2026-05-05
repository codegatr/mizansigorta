<?php
/**
 * Mizan Sigorta - Hizli Teklif Wizard API
 * public/api-teklif-wizard.php
 *
 * POST JSON body: { urun_slug, urun_baslik, tip, ad, firma, il, ilce, tel, email, mesaj, kvkk }
 * Returns JSON: { ok: true, teklif_no: 'TKL...' } or { ok: false, error: '...' }
 */
if (!defined('MIZAN_BOOT')) { http_response_code(403); exit; }

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'POST gerekli']);
    exit;
}

// Rate limit (basit IP-bazli — son 5 dakikada 5'ten fazla teklif yasak)
$ip = client_ip();
$recent = (int)db_value(
    'SELECT COUNT(*) FROM ' . t('teklifler') . ' WHERE ip_adresi=? AND olusturma_tarihi > (NOW() - INTERVAL 5 MINUTE)',
    [$ip]
);
if ($recent >= 5) {
    echo json_encode(['ok' => false, 'error' => 'Çok sık teklif gönderdiniz. Lütfen biraz bekleyin.']);
    exit;
}

// JSON oku
$body = (string)file_get_contents('php://input');
$d = json_decode($body, true);
if (!is_array($d)) {
    echo json_encode(['ok' => false, 'error' => 'Geçersiz istek formatı']);
    exit;
}

// Field temizle
$urunSlug = trim((string)($d['urun_slug'] ?? ''));
$tip      = in_array(($d['tip'] ?? 'bireysel'), ['bireysel', 'kurumsal'], true) ? $d['tip'] : 'bireysel';
$ad       = trim((string)($d['ad'] ?? ''));
$firma    = trim((string)($d['firma'] ?? ''));
$il       = trim((string)($d['il'] ?? ''));
$ilce     = trim((string)($d['ilce'] ?? ''));
$tel      = normalize_phone((string)($d['tel'] ?? ''));
$email    = strtolower(trim((string)($d['email'] ?? '')));
$mesaj    = trim((string)($d['mesaj'] ?? ''));
$kvkk     = (bool)($d['kvkk'] ?? false);

// Validasyon
if ($urunSlug === '' || mb_strlen($urunSlug) > 80) {
    echo json_encode(['ok' => false, 'error' => 'Sigorta ürünü seçilmedi.']);
    exit;
}
if ($ad === '' || mb_strlen($ad) < 3) {
    echo json_encode(['ok' => false, 'error' => 'Ad soyad zorunlu (en az 3 karakter).']);
    exit;
}
if ($tip === 'kurumsal' && $firma === '') {
    echo json_encode(['ok' => false, 'error' => 'Kurumsal teklif için firma adı zorunlu.']);
    exit;
}
if ($tel === '' || strlen(preg_replace('/[^0-9]/', '', $tel)) < 10) {
    echo json_encode(['ok' => false, 'error' => 'Geçerli bir telefon numarası girin.']);
    exit;
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'error' => 'Geçerli bir e-posta adresi girin.']);
    exit;
}
if (!$kvkk) {
    echo json_encode(['ok' => false, 'error' => 'KVKK onayı verilmeden teklif oluşturulamaz.']);
    exit;
}

// Urun bul
$urun = db_row('SELECT id, baslik, slug FROM ' . t('urunler') . ' WHERE slug=? AND aktif=1', [$urunSlug]);
if (!$urun) {
    echo json_encode(['ok' => false, 'error' => 'Seçilen sigorta ürünü bulunamadı.']);
    exit;
}

try {
    $teklifNo = generate_no(setting('teklif_otomatik_no', 'TKL'));
    db_exec(
        'INSERT INTO ' . t('teklifler') . '
         (teklif_no, urun_id, kaynak, durum, ad_soyad, firma_adi, email, telefon, il, ilce,
          aciklama, kvkk_onay, ip_adresi, user_agent, olusturma_tarihi)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())',
        [
            $teklifNo,
            (int)$urun['id'],
            'wizard',
            'yeni',
            $ad,
            $tip === 'kurumsal' ? $firma : null,
            $email !== '' ? $email : null,
            $tel,
            $il !== '' ? $il : null,
            $ilce !== '' ? $ilce : null,
            $mesaj !== '' ? $mesaj : null,
            1,
            $ip,
            substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]
    );
    $teklifId = db_last_id();

    // Operatör bildirimi
    $opMail = setting('teklif_bildirim_email', setting('email'));
    if ($opMail) {
        $body = '<h2>Yeni Teklif Talebi (Wizard)</h2>'
              . '<p><b>Teklif No:</b> ' . e($teklifNo) . '</p>'
              . '<p><b>Ürün:</b> ' . e($urun['baslik']) . '</p>'
              . '<p><b>Tip:</b> ' . e(ucfirst($tip)) . '</p>'
              . '<p><b>Ad Soyad:</b> ' . e($ad) . '</p>'
              . ($firma ? '<p><b>Firma:</b> ' . e($firma) . '</p>' : '')
              . '<p><b>Telefon:</b> ' . e($tel) . '</p>'
              . ($email ? '<p><b>E-posta:</b> ' . e($email) . '</p>' : '')
              . ($il ? '<p><b>Şehir:</b> ' . e($il . ($ilce ? ' / ' . $ilce : '')) . '</p>' : '')
              . ($mesaj ? '<p><b>Mesaj:</b><br>' . nl2br(e($mesaj)) . '</p>' : '')
              . '<hr><p>Yönetim panelinden teklifi inceleyebilirsiniz.</p>';
        $extra = talep_bildirim_alicilari();
        @send_mail(
            $opMail,
            'Yeni Teklif Talebi — ' . $teklifNo,
            mail_template('Yeni Teklif Talebi', $body, [
                'badge' => 'YENİ TEKLİF',
                'badge_color' => '#f4d35e',
                'preheader' => 'Yeni teklif talebi: ' . $ad . ' - ' . $teklifNo,
            ]),
            '',
            [
                'bcc'        => $extra['bcc'],
                'reply_to'   => $email ?: null,
                'ilgili_tip' => 'teklif',
                'ilgili_id'  => isset($teklifId) ? (int)$teklifId : null,
            ]
        );
    }

    // Müşteriye teyit
    if ($email !== '') {
        @send_mail(
            $email,
            'Teklifiniz alındı — Mizan Sigorta',
            mail_template('Teklifiniz alındı',
                '<p>Sayın <b>' . e($ad) . '</b>,</p>'
              . '<p><b>' . e($urun['baslik']) . '</b> için teklif talebinizi aldık.</p>'
              . '<p><b>Teklif No:</b> ' . e($teklifNo) . '</p>'
              . '<p>Yetkili ekibimiz <b>' . e($tel) . '</b> üzerinden en kısa sürede sizinle iletişime geçecek.</p>',
                [
                    'badge' => 'TALEBİNİZ ALINDI',
                    'badge_color' => '#22c55e',
                    'preheader' => 'Teklifiniz alindi - ' . $teklifNo,
                ]
            ),
            '',
            [
                'ilgili_tip' => 'teklif',
                'ilgili_id'  => isset($teklifId) ? (int)$teklifId : null,
            ]
        );
    }

    echo json_encode(['ok' => true, 'teklif_no' => $teklifNo]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => 'Sistem hatası: ' . $e->getMessage()]);
}
