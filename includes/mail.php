<?php
/**
 * Mizan Sigorta - SMTP E-posta Gondericisi
 * includes/mail.php
 *
 * PHPMailer'a baglilik olmadan minimal SMTP istemcisi (TLS/SSL/yok destekli).
 * Ayarlar mz_ayarlar tablosundan okunur (smtp_host, smtp_port, ...).
 */

declare(strict_types=1);

if (!defined('MIZAN_BOOT')) {
    http_response_code(403);
    exit('Forbidden');
}

/**
 * E-posta gonder.
 *
 * @param string|array $to       Tek alici e-posta veya alici dizisi
 * @param string       $subject  Konu (UTF-8)
 * @param string       $bodyHtml HTML govde
 * @param string       $bodyText Plain text govde (bos ise HTML'den uretilir)
 * @param array        $opts     Ek ayarlar:
 *                                 'cc'       => [email, ...]   - CC alicilari
 *                                 'bcc'      => [email, ...]   - BCC alicilari (gizli kopya)
 *                                 'reply_to' => 'email'         - Yanit adresi
 * @return array ['ok' => bool, 'msg' => string]
 */
function send_mail($to, string $subject, string $bodyHtml, string $bodyText = '', array $opts = []): array
{
    $host   = setting('smtp_host');
    $port   = (int) setting('smtp_port', '587');
    $user   = setting('smtp_user');
    $pass   = setting('smtp_pass');
    $from   = setting('smtp_from') ?: setting('email');
    $fname  = setting('smtp_from_name') ?: SITE_NAME;
    $secure = setting('smtp_secure', 'tls');

    // Log icin meta veri
    $logCtx = [
        'alici'        => is_array($to) ? implode(', ', (array)$to) : (string)$to,
        'cc'           => isset($opts['cc'])  ? implode(', ', (array)$opts['cc'])  : null,
        'bcc'          => isset($opts['bcc']) ? implode(', ', (array)$opts['bcc']) : null,
        'reply_to'     => isset($opts['reply_to']) ? (string)$opts['reply_to'] : null,
        'konu'         => $subject,
        'govde_html'   => $bodyHtml,
        'ilgili_tip'   => (string)($opts['ilgili_tip'] ?? 'genel'),
        'ilgili_id'    => isset($opts['ilgili_id']) ? (int)$opts['ilgili_id'] : null,
        'kullanici_id' => function_exists('user_id') ? user_id() : null,
        'ip'           => function_exists('client_ip') ? client_ip() : ($_SERVER['REMOTE_ADDR'] ?? null),
    ];

    if (!$host || !$from) {
        mail_log_yaz($logCtx, 'hatali', 'SMTP yapilandirilmamis.');
        return ['ok' => false, 'msg' => 'SMTP yapilandirilmamis.'];
    }
    if ($bodyText === '') {
        $bodyText = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $bodyHtml)));
    }

    // Alicilari normalize et
    $toList  = is_array($to) ? array_values(array_filter(array_map('trim', $to))) : [trim($to)];
    $ccList  = isset($opts['cc'])  ? array_values(array_filter(array_map('trim', (array)$opts['cc'])))  : [];
    $bccList = isset($opts['bcc']) ? array_values(array_filter(array_map('trim', (array)$opts['bcc']))) : [];
    $replyTo = isset($opts['reply_to']) ? trim((string)$opts['reply_to']) : $from;

    $validate = static function (array $arr): array {
        return array_values(array_filter($arr, static fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));
    };
    $toList  = $validate($toList);
    $ccList  = $validate($ccList);
    $bccList = $validate($bccList);

    if (!$toList) {
        mail_log_yaz($logCtx, 'hatali', 'Gecerli alici yok.');
        return ['ok' => false, 'msg' => 'Gecerli alici yok.'];
    }

    $eol      = "\r\n";
    $boundary = '----=_MIZAN_' . bin2hex(random_bytes(8));

    $headers = [];
    $headers[] = 'From: ' . sprintf('"%s" <%s>', addslashes($fname), $from);
    $headers[] = 'Reply-To: ' . $replyTo;
    $headers[] = 'To: ' . implode(', ', array_map(static fn($e) => '<' . $e . '>', $toList));
    if ($ccList) {
        $headers[] = 'Cc: ' . implode(', ', array_map(static fn($e) => '<' . $e . '>', $ccList));
    }
    // BCC header'a YAZILMAZ (gizli kopya), ama RCPT TO ile gonderilir
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
    $headers[] = 'X-Mailer: MizanSigorta/' . SITE_VERSION;
    $headers[] = 'Date: ' . date('r');
    $headers[] = 'Message-ID: <' . bin2hex(random_bytes(8)) . '@' . parse_url(SITE_BASE_URL, PHP_URL_HOST) . '>';

    $body  = "--{$boundary}{$eol}";
    $body .= "Content-Type: text/plain; charset=UTF-8{$eol}";
    $body .= "Content-Transfer-Encoding: 8bit{$eol}{$eol}";
    $body .= $bodyText . "{$eol}{$eol}";
    $body .= "--{$boundary}{$eol}";
    $body .= "Content-Type: text/html; charset=UTF-8{$eol}";
    $body .= "Content-Transfer-Encoding: 8bit{$eol}{$eol}";
    $body .= $bodyHtml . "{$eol}{$eol}";
    $body .= "--{$boundary}--{$eol}";

    $errno = 0; $errstr = '';
    $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host;
    $smtp = @stream_socket_client($remote . ':' . $port, $errno, $errstr, 20,
        STREAM_CLIENT_CONNECT, stream_context_create(['ssl' => [
            'verify_peer'      => false, 'verify_peer_name' => false, 'allow_self_signed' => true,
        ]]));
    if (!$smtp) {
        $msg = "SMTP baglanilamadi: $errstr ($errno)";
        mail_log_yaz($logCtx, 'hatali', $msg);
        return ['ok' => false, 'msg' => $msg];
    }
    stream_set_timeout($smtp, 20);

    $read = function () use ($smtp): string {
        $out = '';
        while (($line = fgets($smtp, 1024)) !== false) {
            $out .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $out;
    };
    $cmd = function (string $line) use ($smtp, $read): string {
        fwrite($smtp, $line . "\r\n");
        return $read();
    };

    $read();
    $cmd('EHLO ' . parse_url(SITE_BASE_URL, PHP_URL_HOST));
    if ($secure === 'tls') {
        $cmd('STARTTLS');
        if (!stream_socket_enable_crypto($smtp, true,
                STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
            fclose($smtp);
            mail_log_yaz($logCtx, 'hatali', 'TLS baslatilamadi.');
            return ['ok' => false, 'msg' => 'TLS baslatilamadi.'];
        }
        $cmd('EHLO ' . parse_url(SITE_BASE_URL, PHP_URL_HOST));
    }
    if ($user !== '') {
        $cmd('AUTH LOGIN');
        $cmd(base64_encode($user));
        $r = $cmd(base64_encode($pass));
        if (strpos($r, '235') !== 0) {
            fclose($smtp);
            mail_log_yaz($logCtx, 'hatali', 'SMTP kimlik dogrulama hatasi.', $r);
            return ['ok' => false, 'msg' => 'SMTP kimlik dogrulama hatasi.'];
        }
    }
    $cmd('MAIL FROM:<' . $from . '>');

    // Tum alicilari (To + CC + BCC) RCPT TO ile bildir
    $allRcpt = array_merge($toList, $ccList, $bccList);
    foreach ($allRcpt as $rcpt) {
        $r = $cmd('RCPT TO:<' . $rcpt . '>');
        if (!preg_match('/^25[01]/', $r)) {
            fclose($smtp);
            $msg = 'Alici reddedildi (' . $rcpt . '): ' . trim($r);
            mail_log_yaz($logCtx, 'hatali', $msg, $r);
            return ['ok' => false, 'msg' => $msg];
        }
    }
    $cmd('DATA');
    $payload  = 'Subject: =?UTF-8?B?' . base64_encode($subject) . "?=\r\n";
    $payload .= implode("\r\n", $headers) . "\r\n\r\n";
    $payload .= str_replace("\r\n.", "\r\n..", $body);
    $r = $cmd($payload . "\r\n.");
    $cmd('QUIT');
    fclose($smtp);

    if (strpos($r, '250') !== 0) {
        $msg = 'Mesaj gonderilemedi: ' . trim($r);
        mail_log_yaz($logCtx, 'hatali', $msg, $r);
        return ['ok' => false, 'msg' => $msg];
    }
    mail_log_yaz($logCtx, 'basarili', null, trim($r));
    return ['ok' => true, 'msg' => 'Gonderildi'];
}

/**
 * Mail log tablosuna kayit yaz. send_mail() icinde her donus noktasinda cagrilir.
 * Tablo yoksa veya DB hatasi olursa sessizce yutar (mail gonderim akisini bozmasın).
 *
 * @param array       $ctx  alici/cc/bcc/konu/govde_html/ilgili_tip/ilgili_id/kullanici_id/ip
 * @param string      $durum 'basarili' veya 'hatali'
 * @param string|null $hata
 * @param string|null $smtpYanit
 */
function mail_log_yaz(array $ctx, string $durum, ?string $hata = null, ?string $smtpYanit = null): void
{
    try {
        // Govde HTML cok buyuk olabilir - 64KB limit (mediumtext zaten max 16MB destekler ama
        // makul bir sınır mantikli olur, log tablosu sislememesin)
        $govde = (string)($ctx['govde_html'] ?? '');
        if (mb_strlen($govde) > 65535) {
            $govde = mb_substr($govde, 0, 65000) . "\n\n[... kesilmis ...]";
        }

        db_exec('INSERT INTO ' . t('mail_log') . '
            (olusturma_tarihi, alici, cc_listesi, bcc_listesi, reply_to, konu, govde_html,
             durum, hata_mesaji, smtp_yanit, ilgili_tip, ilgili_id, kullanici_id, ip)
            VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                (string)($ctx['alici']    ?? ''),
                $ctx['cc']                ?: null,
                $ctx['bcc']               ?: null,
                $ctx['reply_to']          ?: null,
                (string)($ctx['konu']     ?? ''),
                $govde ?: null,
                $durum,
                $hata                     ?: null,
                $smtpYanit                ?: null,
                (string)($ctx['ilgili_tip'] ?? 'genel'),
                $ctx['ilgili_id']         ?: null,
                $ctx['kullanici_id']      ?: null,
                $ctx['ip']                ?: null,
            ]);
    } catch (Throwable $e) {
        // Tablo yoksa veya DB hatasi - sessizce yut, mail gonderimi etkilenmesin
    }
}

/**
 * Talep bildirim BCC listesi.
 * Yunus'un istegi: tum talepler (teklif/hasar/iletisim) ek olarak
 * teklifmerkezi@mizansigorta.com.tr (veya admin'de tanimladigi) adresine de gitsin.
 *
 * @return array ['cc' => [...], 'bcc' => [...]]
 */
function talep_bildirim_alicilari(): array
{
    $extra = trim((string) setting('talep_bildirim_bcc', ''));
    if ($extra === '') return ['cc' => [], 'bcc' => []];

    $parts = preg_split('/[,;\n\r]+/', $extra);
    $bcc   = [];
    foreach ($parts as $p) {
        $p = trim((string) $p);
        if ($p !== '' && filter_var($p, FILTER_VALIDATE_EMAIL)) $bcc[] = $p;
    }
    return ['cc' => [], 'bcc' => $bcc];
}

/**
 * Teklif durumu degistiginde musteriye bilgilendirme maili gonderir.
 * Sadece musteri email'i varsa ve durum gercekten farkli ise.
 *
 * @param int    $teklifId
 * @param string $yeniDurum  - yeni/islemde/teklif_hazir/teklif_gonderildi/onaylandi/police_oldu/iptal/kayip
 * @return bool gonderildi mi
 */
function teklif_durum_bildirim_gonder(int $teklifId, string $yeniDurum): bool
{
    $t = db_row('SELECT * FROM ' . t('teklifler') . ' WHERE id=?', [$teklifId]);
    if (!$t) return false;

    $email = trim((string)($t['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;

    // Durum etiketleri ve renkleri
    $etiketler = [
        'yeni'              => ['Yeni',              '#0d6efd', 'Talebiniz alındı, ekibimiz değerlendirmeye başlıyor.'],
        'islemde'           => ['İşleme Alındı',     '#f4d35e', 'Talebiniz değerlendirmeye alındı. Anlaşmalı şirketler arasından sizin için en uygun teminatları araştırıyoruz.'],
        'teklif_hazir'      => ['Teklif Hazır',      '#22c55e', 'Sizin için hazırladığımız teklifler hazır. Yetkilimiz en kısa sürede sizinle iletişime geçecektir.'],
        'teklif_gonderildi' => ['Teklif İletildi',   '#22c55e', 'Teklifimiz size iletildi. Sorularınız için bize ulaşabilirsiniz.'],
        'onaylandi'         => ['Onaylandı',         '#0d6efd', 'Teklifiniz onaylandı. Poliçeleştirme süreci başlatıldı.'],
        'police_oldu'       => ['Poliçeniz Düzenlendi', '#22c55e', 'Tebrikler! Poliçeniz düzenlendi. Detaylı poliçe bilgileri ve evrakları için sizinle iletişime geçeceğiz.'],
        'iptal'             => ['İptal Edildi',      '#6b7280', 'Talebiniz iptal edildi. Yeniden değerlendirmek isterseniz bize ulaşabilirsiniz.'],
        'kayip'             => ['Sonlandırıldı',     '#6b7280', 'Talebinize ilişkin süreç sonlandırıldı. Yeni bir talep için her zaman buradayız.'],
    ];
    if (!isset($etiketler[$yeniDurum])) return false;

    [$durumLabel, $durumRenk, $aciklama] = $etiketler[$yeniDurum];
    $teklifNo = $t['teklif_no'] ?? ('#' . $teklifId);
    $ad       = trim((string)($t['ad_soyad'] ?? '')) ?: 'Sayın müşterimiz';

    $body = '<p>Sayın <b>' . e($ad) . '</b>,</p>'
          . '<p><b>' . e($teklifNo) . '</b> numaralı teklif talebinizin durumu güncellendi.</p>'
          . '<table cellpadding="0" cellspacing="0" style="background:#f8fafc;border-radius:8px;margin:18px 0;width:100%">'
          .   '<tr><td style="padding:18px 22px">'
          .     '<div style="font-size:12px;color:#6b7280;letter-spacing:.5px;text-transform:uppercase;font-weight:600;margin-bottom:6px">Yeni Durum</div>'
          .     '<div style="display:inline-block;background:' . e($durumRenk) . ';color:#ffffff;padding:8px 16px;border-radius:6px;font-weight:700;font-size:15px;letter-spacing:.3px">' . e($durumLabel) . '</div>'
          .   '</td></tr>'
          . '</table>'
          . '<p style="color:#374151">' . e($aciklama) . '</p>'
          . '<p style="color:#1f2937;margin-top:1.5rem;margin-bottom:0"><strong>Teşekkür ederiz.</strong><br>'
          .   '<span style="color:#6b7280">' . e(setting('firma_adi', SITE_NAME)) . '</span></p>';

    $html = mail_template('Teklifinizin Durumu Güncellendi', $body, [
        'badge'       => 'DURUM GÜNCELLENDİ',
        'badge_color' => $durumRenk,
        'preheader'   => $teklifNo . ' - ' . $durumLabel,
    ]);

    $extra = talep_bildirim_alicilari();
    $r = send_mail($email, 'Teklif durumunuz güncellendi - ' . $teklifNo, $html, '', [
        'bcc'        => $extra['bcc'],
        'ilgili_tip' => 'durum_bildirim',
        'ilgili_id'  => $teklifId,
    ]);

    return is_array($r) ? !empty($r['ok']) : (bool)$r;
}

/**
 * Kurumsal HTML e-posta sablonu - Mizan Sigorta marka kimligi
 *
 * @param string $title    Mail basligi (sadece <title>'da)
 * @param string $bodyHtml Ana icerik HTML
 * @param array  $opts     Opsiyonlar:
 *                            'preheader'   => string  - inbox onizleme
 *                            'cta_text'    => string  - buyuk buton metni
 *                            'cta_url'     => string  - buton link
 *                            'badge'       => string  - ust sag rozet (or. "YENI TEKLIF")
 *                            'badge_color' => string  - rozet bg rengi
 */

/**
 * Kurumsal HTML e-posta sablonu - Outlook-safe + yatay duzen
 *
 * Outlook 2007/2010/2013/2016/2019/365 icin uyumlu:
 * - SVG yok (PNG/emoji/HTML entity)
 * - Linear-gradient yok (MSO VML fallback)
 * - Box-shadow yok
 * - Modern CSS yok (table-based layout)
 * - Sistem fontlar (Arial, Helvetica, Verdana, Georgia, Tahoma)
 * - bgcolor attribute fallback
 * - mso-line-height-rule, mso-table-lspace
 *
 * @param string $title    Mail basligi (sadece <title>'da)
 * @param string $bodyHtml Ana icerik HTML
 * @param array  $opts     Opsiyonlar:
 *                            'preheader'   => string  - inbox onizleme
 *                            'cta_text'    => string  - buton metni
 *                            'cta_url'     => string  - buton link
 *                            'badge'       => string  - rozet (or. "YENI TEKLIF")
 *                            'badge_color' => string  - rozet bg (#22c55e gibi)
 */
function mail_template(string $title, string $bodyHtml, array $opts = []): string
{
    $brand      = e(setting('firma_adi', SITE_NAME));
    $brandShort = e(setting('site_basligi', 'Mizan Sigorta'));
    $year       = date('Y');
    $tel        = e(setting('telefon', ''));
    $email      = e(setting('email', ''));
    $adres      = e(setting('adres', ''));
    $whatsappRaw = trim((string) setting('whatsapp', ''));
    $whatsapp   = function_exists('normalize_phone') ? normalize_phone($whatsappRaw) : preg_replace('/[^0-9]/', '', $whatsappRaw);
    $web        = e(rtrim(SITE_BASE_URL, '/'));
    $teklifUrl  = $web . '/teklif-al';
    $hasarUrl   = $web . '/hasar-ihbari';

    $preheader = e((string)($opts['preheader'] ?? 'Mizan Sigorta - 12+ anlasmali sirket arasinda en uygun teminat'));
    $ctaText   = (string)($opts['cta_text'] ?? '');
    $ctaUrl    = (string)($opts['cta_url'] ?? '');
    $badge     = (string)($opts['badge'] ?? '');
    $badgeBg   = (string)($opts['badge_color'] ?? '#22c55e');

    // Sosyal medya - text linkler (Outlook'ta SVG render olmaz)
    $socialNames = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'twitter' => 'X (Twitter)', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube'];
    $socialHtml = '';
    foreach ($socialNames as $key => $label) {
        if ($u = trim((string) setting($key))) {
            $socialHtml .= '<a href="' . e($u) . '" style="color:#cbd5e1;text-decoration:none;font-size:12px;font-weight:600;padding:6px 12px;background:#1b263b;border-radius:4px;margin:0 3px;display:inline-block">' . e($label) . '</a>';
        }
    }
    if (!$socialHtml) $socialHtml = '<span style="color:#64748b;font-size:11px">Sosyal medya hesaplari yakinda</span>';

    // CTA buton (Outlook bulletproof - VML)
    $ctaHtml = '';
    if ($ctaText !== '' && $ctaUrl !== '') {
        $ctaText = e($ctaText);
        $ctaUrlE = e($ctaUrl);
        $ctaHtml = <<<CTA
<table cellpadding="0" cellspacing="0" border="0" style="margin:24px 0">
  <tr><td align="left">
    <!--[if mso]>
    <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{$ctaUrlE}" style="height:48px;v-text-anchor:middle;width:240px;" arcsize="17%" stroke="f" fillcolor="#e30b30">
      <w:anchorlock/>
      <center style="color:#ffffff;font-family:Arial,sans-serif;font-size:15px;font-weight:bold;">{$ctaText}</center>
    </v:roundrect>
    <![endif]-->
    <!--[if !mso]><!-- -->
    <a href="{$ctaUrlE}" style="background:#e30b30;color:#ffffff;padding:14px 32px;text-decoration:none;font-weight:bold;font-size:15px;border-radius:6px;display:inline-block;font-family:Arial,Helvetica,sans-serif">{$ctaText} &rarr;</a>
    <!--<![endif]-->
  </td></tr>
</table>
CTA;
    }

    // Badge (Outlook'ta basit rozet)
    $badgeHtml = '';
    if ($badge !== '') {
        $badgeBgE = e($badgeBg);
        $badgeE = e($badge);
        $badgeHtml = '<table cellpadding="0" cellspacing="0" border="0" align="right"><tr><td bgcolor="' . $badgeBgE . '" style="background:' . $badgeBgE . ';padding:7px 14px;font-family:Arial,sans-serif;font-size:11px;font-weight:bold;color:#ffffff;letter-spacing:.8px;text-transform:uppercase;border-radius:4px">' . $badgeE . '</td></tr></table>';
    }

    // Iletisim 3-sutun yatay grid (Outlook table)
    $telClean = preg_replace('/[^0-9+]/', '', $tel);
    $iletisimRow = '<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"><tr>';

    if ($tel) {
        $iletisimRow .= '<td valign="top" align="center" width="33%" style="padding:8px;width:33.33%">'
            . '<table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="background:#ffffff;border:1px solid #e5e7eb;border-radius:6px"><tr><td align="center" style="padding:14px 8px">'
            .   '<table cellpadding="0" cellspacing="0" border="0"><tr><td bgcolor="#e30b30" style="background:#e30b30;width:36px;height:36px;border-radius:50%;text-align:center;color:#ffffff;font-size:18px;font-family:Arial,sans-serif" align="center">&#9742;</td></tr></table>'
            .   '<div style="font-family:Arial,sans-serif;font-size:10px;color:#6b7280;letter-spacing:.5px;text-transform:uppercase;font-weight:bold;margin-top:8px">Telefon</div>'
            .   '<a href="tel:' . $telClean . '" style="font-family:Arial,sans-serif;font-size:13px;color:#0d1b2a;font-weight:bold;text-decoration:none;display:block;margin-top:3px">' . $tel . '</a>'
            . '</td></tr></table></td>';
    }

    if ($whatsapp && strlen($whatsapp) >= 11) {
        $iletisimRow .= '<td valign="top" align="center" width="33%" style="padding:8px;width:33.33%">'
            . '<table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="background:#ffffff;border:1px solid #e5e7eb;border-radius:6px"><tr><td align="center" style="padding:14px 8px">'
            .   '<table cellpadding="0" cellspacing="0" border="0"><tr><td bgcolor="#25d366" style="background:#25d366;width:36px;height:36px;border-radius:50%;text-align:center;color:#ffffff;font-size:18px;font-weight:bold;font-family:Arial,sans-serif" align="center">W</td></tr></table>'
            .   '<div style="font-family:Arial,sans-serif;font-size:10px;color:#6b7280;letter-spacing:.5px;text-transform:uppercase;font-weight:bold;margin-top:8px">WhatsApp</div>'
            .   '<a href="https://wa.me/' . $whatsapp . '" style="font-family:Arial,sans-serif;font-size:13px;color:#0d1b2a;font-weight:bold;text-decoration:none;display:block;margin-top:3px">Hemen Yaz</a>'
            . '</td></tr></table></td>';
    }

    if ($email) {
        $iletisimRow .= '<td valign="top" align="center" width="33%" style="padding:8px;width:33.33%">'
            . '<table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="background:#ffffff;border:1px solid #e5e7eb;border-radius:6px"><tr><td align="center" style="padding:14px 8px">'
            .   '<table cellpadding="0" cellspacing="0" border="0"><tr><td bgcolor="#0d6efd" style="background:#0d6efd;width:36px;height:36px;border-radius:50%;text-align:center;color:#ffffff;font-size:18px;font-family:Arial,sans-serif" align="center">&#9993;</td></tr></table>'
            .   '<div style="font-family:Arial,sans-serif;font-size:10px;color:#6b7280;letter-spacing:.5px;text-transform:uppercase;font-weight:bold;margin-top:8px">E-posta</div>'
            .   '<a href="mailto:' . $email . '" style="font-family:Arial,sans-serif;font-size:11.5px;color:#0d1b2a;font-weight:bold;text-decoration:none;display:block;margin-top:3px;word-break:break-all">' . $email . '</a>'
            . '</td></tr></table></td>';
    }

    $iletisimRow .= '</tr></table>';

    // Trust bar - 4 sutun (Outlook table-based)
    $trustHtml = '<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"><tr>'
        . '<td align="center" valign="top" width="25%" style="padding:0 4px"><div style="font-family:Arial,sans-serif;font-size:18px;color:#f4d35e;line-height:1;font-weight:bold">&#9733;</div><div style="font-family:Arial,sans-serif;font-size:10px;color:#9ca3af;font-weight:bold;margin-top:4px;letter-spacing:.3px">12+ SIRKET</div></td>'
        . '<td align="center" valign="top" width="25%" style="padding:0 4px"><div style="font-family:Arial,sans-serif;font-size:18px;color:#f4d35e;line-height:1;font-weight:bold">&#10004;</div><div style="font-family:Arial,sans-serif;font-size:10px;color:#9ca3af;font-weight:bold;margin-top:4px;letter-spacing:.3px">KVKK UYUMLU</div></td>'
        . '<td align="center" valign="top" width="25%" style="padding:0 4px"><div style="font-family:Arial,sans-serif;font-size:18px;color:#f4d35e;line-height:1;font-weight:bold">&#128274;</div><div style="font-family:Arial,sans-serif;font-size:10px;color:#9ca3af;font-weight:bold;margin-top:4px;letter-spacing:.3px">GUVENLI</div></td>'
        . '<td align="center" valign="top" width="25%" style="padding:0 4px"><div style="font-family:Arial,sans-serif;font-size:18px;color:#f4d35e;line-height:1;font-weight:bold">&#9200;</div><div style="font-family:Arial,sans-serif;font-size:10px;color:#9ca3af;font-weight:bold;margin-top:4px;letter-spacing:.3px">7/24 DESTEK</div></td>'
        . '</tr></table>';

    // Sigorta turleri footer linkleri
    $urunLinks = '<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" style="font-family:Arial,sans-serif;font-size:11px;line-height:1.8;color:#9ca3af">'
        . '<a href="' . $web . '/urun/kasko" style="color:#9ca3af;text-decoration:none;margin:0 6px">Kasko</a> | '
        . '<a href="' . $web . '/urun/trafik-zorunlu-sorumluluk" style="color:#9ca3af;text-decoration:none;margin:0 6px">Trafik</a> | '
        . '<a href="' . $web . '/urun/konut-sigortasi" style="color:#9ca3af;text-decoration:none;margin:0 6px">Konut</a> | '
        . '<a href="' . $web . '/urun/dask" style="color:#9ca3af;text-decoration:none;margin:0 6px">DASK</a> | '
        . '<a href="' . $web . '/urun/ozel-saglik-sigortasi" style="color:#9ca3af;text-decoration:none;margin:0 6px">Saglik</a>'
        . '</td></tr></table>';

    return <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" lang="tr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="x-apple-disable-message-reformatting">
<meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
<meta name="color-scheme" content="light only">
<title>{$title}</title>
<!--[if gte mso 9]>
<xml>
  <o:OfficeDocumentSettings>
    <o:AllowPNG/>
    <o:PixelsPerInch>96</o:PixelsPerInch>
  </o:OfficeDocumentSettings>
</xml>
<![endif]-->
<!--[if mso]>
<style type="text/css">
  table, td, div, p, a { font-family: Arial, Helvetica, sans-serif !important; }
  .mz-hero-title { font-family: 'Georgia', 'Times New Roman', serif !important; }
</style>
<![endif]-->
<style type="text/css">
  body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
  table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
  img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
  body { margin: 0 !important; padding: 0 !important; width: 100% !important; min-width: 100% !important; }
  @media screen and (max-width: 700px) {
    .mz-container { width: 100% !important; max-width: 100% !important; }
    .mz-pad { padding: 24px 20px !important; }
    .mz-hero-pad { padding: 28px 24px !important; }
    .mz-hero-title { font-size: 22px !important; }
    .mz-iletisim-cards td { display: block !important; width: 100% !important; padding: 6px 0 !important; }
  }
</style>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:Arial,Helvetica,sans-serif;color:#1f2937" bgcolor="#f4f6f9">

  <div style="display:none;font-size:1px;color:#f4f6f9;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden">{$preheader}</div>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f4f6f9" style="background:#f4f6f9">
    <tr>
      <td align="center" style="padding:24px 0">

        <!-- Ana Kart - 700px geniş, yatay duzen -->
        <table role="presentation" class="mz-container" width="700" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="max-width:700px;width:100%;background:#ffffff;border:1px solid #e5e7eb">

          <!-- HEADER: Navy bg + VML gradient (Outlook icin) -->
          <tr>
            <td bgcolor="#0d1b2a" style="background:#0d1b2a;padding:0">
              <!--[if gte mso 9]>
              <v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="width:700px;height:120px;">
                <v:fill type="gradient" color="#0d1b2a" color2="#1a3a5c" angle="135"/>
              </v:rect>
              <div style="position:relative;mso-position-horizontal:left;mso-position-vertical:top">
              <![endif]-->
              <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td class="mz-hero-pad" style="padding:32px 40px" bgcolor="#0d1b2a">
                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td valign="middle">
                          <table cellpadding="0" cellspacing="0" border="0">
                            <tr>
                              <td bgcolor="#e30b30" style="background:#e30b30;width:48px;height:48px;border-radius:8px;text-align:center;font-family:Georgia,'Times New Roman',serif;font-size:26px;font-weight:bold;color:#ffffff;line-height:48px" align="center" width="48" height="48">M</td>
                              <td style="padding-left:14px" valign="middle">
                                <div style="font-family:Georgia,'Times New Roman',serif;color:#f4d35e;font-size:11px;font-style:italic;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:2px">Guven ve Ozen Ile</div>
                                <div class="mz-hero-title" style="font-family:Arial,Helvetica,sans-serif;color:#ffffff;font-size:24px;font-weight:bold;letter-spacing:.3px;line-height:1.2">{$brandShort}</div>
                              </td>
                            </tr>
                          </table>
                        </td>
                        <td valign="top" align="right" width="180">
                          {$badgeHtml}
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
              <!--[if gte mso 9]></div><![endif]-->
            </td>
          </tr>

          <!-- Kirmizi accent line -->
          <tr>
            <td bgcolor="#e30b30" style="background:#e30b30;height:3px;line-height:3px;font-size:0">&nbsp;</td>
          </tr>

          <!-- BODY - kompakt yatay duzen -->
          <tr>
            <td class="mz-pad" style="padding:32px 40px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.65;color:#1f2937" bgcolor="#ffffff">
              {$bodyHtml}
              {$ctaHtml}
            </td>
          </tr>

          <!-- TRUST BAR - Navy strip -->
          <tr>
            <td bgcolor="#0d1b2a" style="background:#0d1b2a;padding:16px 40px">
              {$trustHtml}
            </td>
          </tr>

          <!-- ILETISIM CARD GRID -->
          <tr>
            <td bgcolor="#f8fafc" style="background:#f8fafc;padding:24px 32px">
              <div style="text-align:center;font-family:Arial,sans-serif;font-size:11px;color:#6b7280;letter-spacing:1.2px;text-transform:uppercase;font-weight:bold;margin-bottom:14px">Bize Ulasin</div>
              {$iletisimRow}
            </td>
          </tr>

          <!-- Sigorta turleri footer linkler -->
          <tr>
            <td bgcolor="#0d1b2a" style="background:#0d1b2a;padding:18px 32px;border-top:1px solid rgba(255,255,255,.05)">
              <div style="text-align:center;font-family:Arial,sans-serif;font-size:10px;color:#64748b;letter-spacing:1.2px;text-transform:uppercase;font-weight:bold;margin-bottom:8px">Sigorta Turlerimiz</div>
              {$urunLinks}
            </td>
          </tr>

          <!-- FOOTER: Sosyal + Telif -->
          <tr>
            <td bgcolor="#050b18" style="background:#050b18;padding:20px 32px;text-align:center">
              <div style="margin-bottom:12px">{$socialHtml}</div>
              <div style="font-family:Arial,sans-serif;font-size:11px;color:#9ca3af;line-height:1.6">
                <strong style="color:#ffffff">{$brand}</strong><br>
                <span style="color:#64748b">{$adres}</span><br>
                <span style="color:#64748b">&copy; {$year} Tum haklari saklidir.</span>
              </div>
              <div style="font-family:Arial,sans-serif;font-size:10px;color:#64748b;margin-top:12px;line-height:1.4">
                Bu otomatik gonderilen bir e-postadir.<br>
                Sorulariniz icin: <a href="mailto:{$email}" style="color:#9ca3af;text-decoration:underline">{$email}</a>
              </div>
            </td>
          </tr>

        </table>

        <!-- Anti-spam tagline (kart disinda) -->
        <table role="presentation" class="mz-container" width="700" cellpadding="0" cellspacing="0" border="0" style="max-width:700px;width:100%">
          <tr>
            <td align="center" style="padding:14px 20px;font-family:Arial,sans-serif;font-size:11px;color:#9ca3af;line-height:1.5">
              {$brandShort} &middot; T.C. Hazine ve Maliye Bakanligi SBM Lisansli Sigorta Aracilik Sirketi<br>
              KVKK kapsaminda bilgileriniz koruma altindadir.
            </td>
          </tr>
        </table>

      </td>
    </tr>
  </table>

</body>
</html>
HTML;
}
