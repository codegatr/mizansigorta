<?php
/**
 * Mizan Sigorta - Otomatik Hatırlatma Cron Görevi
 * cron/teklif-hatirlatma.php
 *
 * Kurulum (DirectAdmin/cPanel Cron Jobs):
 *   wget -q -O- "https://mizansigorta.com/cron/teklif-hatirlatma.php?key=XXX" > /dev/null 2>&1
 *
 * Önerilen frekans: Her gün saat 09:00
 *
 * Tetikleyici tipleri:
 *   teklif_yeni        : Yeni teklifin üzerinden N gün geçti, halen 'yeni' durumda
 *   teklif_islemde     : 'islemde' durumunda son güncelleme N gün önce
 *   teklif_gonderildi  : Teklif gönderildi, N gün geçti yanıt gelmedi
 *   police_yenileme    : Poliçe bitisine N gün kaldı
 *   dogum_gunu         : Müşterinin dogum gunu bugun
 */

declare(strict_types=1);

if (!defined('MIZAN_BOOT')) define('MIZAN_BOOT', true);
require __DIR__ . '/../includes/bootstrap.php';

// CLI veya web?
$isCli = (PHP_SAPI === 'cli');

// Web ise key kontrolu
if (!$isCli) {
    $expected = (string)setting('cron_anahtar');
    $given    = (string)($_GET['key'] ?? '');
    if ($expected === '' || !hash_equals($expected, $given)) {
        http_response_code(403);
        exit('Forbidden');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

@set_time_limit(120);
$started = microtime(true);
$logLines = [];
$totalOk = 0;
$totalSkip = 0;
$totalErr = 0;

function clog(string $line, array &$buf): void
{
    $buf[] = '[' . date('H:i:s') . '] ' . $line;
    echo end($buf) . "\n";
}

clog('=== Mizan Sigorta hatırlatma cron başlatıldı ===', $logLines);
clog('Tarih: ' . date('Y-m-d H:i:s'), $logLines);

$kurallar = db_all('SELECT * FROM ' . t('hatirlatma_kurallari') . ' WHERE aktif=1');
clog(count($kurallar) . ' aktif kural bulundu.', $logLines);

/**
 * Mukerrer hatirlatma kontrolu — ayni kural + ayni teklif/police icin bugun gonderildi mi?
 */
function alreadyLogged(int $kuralId, int $teklifId, int $policeId): bool
{
    $where = 'kural_id=? AND DATE(gonderim_tarihi)=CURDATE() AND durum=?';
    $args = [$kuralId, 'basarili'];
    if ($teklifId) { $where .= ' AND teklif_id=?'; $args[] = $teklifId; }
    elseif ($policeId) { $where .= ' AND police_id=?'; $args[] = $policeId; }
    return (int)db_value('SELECT COUNT(*) FROM ' . t('hatirlatma_log') . ' WHERE ' . $where, $args) > 0;
}

/**
 * Tek bir hedefin verilerini hazirla
 */
function buildVars(array $teklif = [], array $police = [], array $musteri = [], array $urun = [], int $gun = 0): array
{
    return [
        'ad_soyad'     => $teklif['ad_soyad']  ?? $musteri['ad_soyad']  ?? '',
        'firma_adi'    => $teklif['firma_adi'] ?? $musteri['firma_adi'] ?? '',
        'email'        => $teklif['email']     ?? $musteri['email']     ?? '',
        'telefon'      => $teklif['telefon']   ?? $musteri['telefon']   ?? '',
        'teklif_no'    => $teklif['teklif_no'] ?? '',
        'urun_adi'     => $urun['baslik']      ?? '',
        'police_no'    => $police['police_no'] ?? '',
        'baslangic_tarihi' => isset($police['baslangic_tarihi']) ? tr_date($police['baslangic_tarihi']) : '',
        'bitis_tarihi' => isset($police['bitis_tarihi']) ? tr_date($police['bitis_tarihi']) : '',
        'gun'          => (string)$gun,
        'site_adi'     => setting('site_basligi', 'Mizan Sigorta'),
        'firma_telefon'=> setting('telefon', ''),
    ];
}

/**
 * Hatirlatma gonder + log
 */
function sendReminder(array $kural, array $vars, ?int $teklifId = null, ?int $policeId = null, ?string $emailTo = null): array
{
    if ($kural['kanal'] === 'email' || $kural['kanal'] === 'panel_email') {
        $to = $emailTo ?: ($vars['email'] ?? '');
        if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'mesaj' => 'Geçersiz e-posta'];
        }
        $konu  = tpl_replace((string)$kural['email_konu'], $vars);
        $govde = tpl_replace((string)$kural['email_govde'], $vars);
        $html  = mail_template($konu, '<div style="white-space:pre-wrap">' . nl2br(e($govde)) . '</div>', [
            'badge' => 'HATIRLATMA',
            'badge_color' => '#f4d35e',
            'preheader' => $konu,
        ]);
        // Tum maillerin kopyasi BCC'ye (talep_bildirim_bcc setting)
        $extra = talep_bildirim_alicilari();
        $ok = send_mail($to, $konu, $html, '', ['bcc' => $extra['bcc']]);
        return ['ok' => $ok, 'mesaj' => $ok ? 'Gönderildi' : 'SMTP hatası', 'konu' => $konu, 'alici' => $to];
    }

    if ($kural['kanal'] === 'panel') {
        // Sadece panele bildirim — teklif kaydina hatirlatma_aktif true bayraga ekleme
        return ['ok' => true, 'mesaj' => 'Panele not eklendi'];
    }

    return ['ok' => false, 'mesaj' => 'Desteklenmeyen kanal: ' . $kural['kanal']];
}

foreach ($kurallar as $kural) {
    $tetik = (string)$kural['tetikleyici'];
    $gun   = (int)$kural['gun_sayisi'];
    clog("Kural #{$kural['id']} '{$kural['ad']}' (tetik=$tetik, gun=$gun, kanal={$kural['kanal']})", $logLines);

    $hedefler = [];
    try {
        switch ($tetik) {
            case 'teklif_yeni':
                $hedefler = db_all(
                    'SELECT t.*, u.baslik AS urun_baslik FROM ' . t('teklifler') . ' t
                     LEFT JOIN ' . t('urunler') . ' u ON u.id = t.urun_id
                     WHERE t.durum=? AND t.hatirlatma_aktif=1 AND DATEDIFF(NOW(), t.olusturma_tarihi)=?',
                    ['yeni', $gun]
                );
                break;

            case 'teklif_islemde':
                $hedefler = db_all(
                    'SELECT t.*, u.baslik AS urun_baslik FROM ' . t('teklifler') . ' t
                     LEFT JOIN ' . t('urunler') . ' u ON u.id = t.urun_id
                     WHERE t.durum=? AND t.hatirlatma_aktif=1 AND DATEDIFF(NOW(), t.guncelleme_tarihi)=?',
                    ['islemde', $gun]
                );
                break;

            case 'teklif_gonderildi':
                $hedefler = db_all(
                    'SELECT t.*, u.baslik AS urun_baslik FROM ' . t('teklifler') . ' t
                     LEFT JOIN ' . t('urunler') . ' u ON u.id = t.urun_id
                     WHERE t.durum=? AND t.hatirlatma_aktif=1 AND DATEDIFF(NOW(), t.guncelleme_tarihi)=?',
                    ['teklif_gonderildi', $gun]
                );
                break;

            case 'police_yenileme':
                $hedefler = db_all(
                    'SELECT p.*, m.ad_soyad AS m_ad, m.firma_adi AS m_firma, m.email AS m_email, m.telefon AS m_telefon, u.baslik AS urun_baslik
                     FROM ' . t('policeler') . ' p
                     LEFT JOIN ' . t('musteriler') . ' m ON m.id = p.musteri_id
                     LEFT JOIN ' . t('urunler') . ' u ON u.id = p.urun_id
                     WHERE p.durum=? AND DATEDIFF(p.bitis_tarihi, CURDATE())=?',
                    ['aktif', $gun]
                );
                break;

            case 'dogum_gunu':
                $hedefler = db_all(
                    'SELECT * FROM ' . t('musteriler') . " WHERE dogum_tarihi IS NOT NULL AND DATE_FORMAT(dogum_tarihi,'%m-%d')=DATE_FORMAT(CURDATE(),'%m-%d')"
                );
                break;
        }
    } catch (Throwable $e) {
        clog('  → SORGU HATASI: ' . $e->getMessage(), $logLines);
        $totalErr++;
        continue;
    }

    clog('  → ' . count($hedefler) . ' hedef bulundu.', $logLines);

    foreach ($hedefler as $h) {
        $teklifId = 0; $policeId = 0; $musteriId = 0; $emailTo = null;
        $teklif = []; $police = []; $musteri = []; $urun = [];

        if (in_array($tetik, ['teklif_yeni','teklif_islemde','teklif_gonderildi'], true)) {
            $teklifId = (int)$h['id'];
            $teklif = $h;
            $urun = ['baslik' => $h['urun_baslik']];
            $emailTo = $h['email'] ?: null;
        } elseif ($tetik === 'police_yenileme') {
            $policeId = (int)$h['id'];
            $police = $h;
            $urun = ['baslik' => $h['urun_baslik']];
            $musteri = ['ad_soyad' => $h['m_ad'], 'firma_adi' => $h['m_firma'], 'email' => $h['m_email'], 'telefon' => $h['m_telefon']];
            $emailTo = $h['m_email'] ?: null;
        } elseif ($tetik === 'dogum_gunu') {
            $musteriId = (int)$h['id'];
            $musteri = $h;
            $emailTo = $h['email'] ?: null;
        }

        if (alreadyLogged((int)$kural['id'], $teklifId, $policeId)) {
            $totalSkip++;
            continue;
        }

        $vars = buildVars($teklif, $police, $musteri, $urun, $gun);
        $res = sendReminder($kural, $vars, $teklifId ?: null, $policeId ?: null, $emailTo);

        // Log kaydet
        try {
            db_exec(
                'INSERT INTO ' . t('hatirlatma_log') . ' (kural_id, teklif_id, police_id, musteri_id, kanal, alici, konu, durum, hata_mesaji, gonderim_tarihi) VALUES (?,?,?,?,?,?,?,?,?,NOW())',
                [
                    (int)$kural['id'],
                    $teklifId ?: null,
                    $policeId ?: null,
                    $musteriId ?: null,
                    $kural['kanal'],
                    $res['alici'] ?? $emailTo,
                    $res['konu'] ?? null,
                    $res['ok'] ? 'basarili' : 'hatali',
                    $res['ok'] ? null : ($res['mesaj'] ?? 'Bilinmeyen hata'),
                ]
            );
        } catch (Throwable $e) { /* sessizce gec */ }

        if ($res['ok']) {
            $totalOk++;
            // Teklif uzerindeki sayilari guncelle
            if ($teklifId) {
                db_exec('UPDATE ' . t('teklifler') . ' SET hatirlatma_sayisi=hatirlatma_sayisi+1, son_hatirlatma_tarihi=NOW() WHERE id=?', [$teklifId]);
            }
            if ($policeId) {
                db_exec('UPDATE ' . t('policeler') . ' SET yenileme_uyarildi=1 WHERE id=?', [$policeId]);
            }
            clog("    ✓ {$res['mesaj']} → " . ($res['alici'] ?? '-'), $logLines);
        } else {
            $totalErr++;
            clog("    ✗ HATA: {$res['mesaj']}", $logLines);
        }
    }
}

$elapsed = round(microtime(true) - $started, 2);
clog('--- ÖZET ---', $logLines);
clog("Başarılı: $totalOk  |  Atlanan (mukerrer): $totalSkip  |  Hatalı: $totalErr", $logLines);
clog("Süre: {$elapsed}s", $logLines);
clog('=== TAMAMLANDI ===', $logLines);

// Son özetin audit log'a kaydedilmesi
try {
    audit_log('cron_hatirlatma', 'cron', null, "OK=$totalOk SKIP=$totalSkip ERR=$totalErr (sure={$elapsed}s)");
} catch (Throwable $e) {}
