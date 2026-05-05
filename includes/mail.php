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

    if (!$host || !$from) {
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
        return ['ok' => false, 'msg' => "SMTP baglanilamadi: $errstr ($errno)"];
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
            return ['ok' => false, 'msg' => 'Alici reddedildi (' . $rcpt . '): ' . trim($r)];
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
        return ['ok' => false, 'msg' => 'Mesaj gonderilemedi: ' . trim($r)];
    }
    return ['ok' => true, 'msg' => 'Gonderildi'];
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
function mail_template(string $title, string $bodyHtml, array $opts = []): string
{
    $brand      = e(setting('firma_adi', SITE_NAME));
    $brandShort = e(setting('site_basligi', 'Mizan Sigorta'));
    $year       = date('Y');
    $tel        = e(setting('telefon', ''));
    $email      = e(setting('email', ''));
    $adres      = e(setting('adres', ''));
    $web        = e(rtrim(SITE_BASE_URL, '/'));
    $teklifUrl  = $web . '/teklif-al';
    $hasarUrl   = $web . '/hasar-ihbari';

    $preheader = e((string)($opts['preheader'] ?? 'Mizan Sigorta - Guven ve Ozen Ile'));
    $ctaText   = (string)($opts['cta_text'] ?? '');
    $ctaUrl    = (string)($opts['cta_url'] ?? '');
    $badge     = (string)($opts['badge'] ?? '');
    $badgeBg   = (string)($opts['badge_color'] ?? '#f4d35e');

    $socials = [];
    foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'twitter' => 'Twitter', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube'] as $key => $label) {
        if ($u = trim((string) setting($key))) {
            $socials[] = ['url' => e($u), 'label' => $label];
        }
    }
    $socialHtml = '';
    foreach ($socials as $s) {
        $socialHtml .= '<a href="' . $s['url'] . '" style="color:#9ca3af;text-decoration:none;margin:0 6px;font-size:12px">' . e($s['label']) . '</a>';
    }

    $ctaHtml = '';
    if ($ctaText !== '' && $ctaUrl !== '') {
        $ctaHtml = '<table cellpadding="0" cellspacing="0" style="margin:24px 0"><tr><td>'
                 . '<a href="' . e($ctaUrl) . '" style="display:inline-block;background:#e30b30;color:#ffffff;padding:14px 28px;border-radius:8px;text-decoration:none;font-weight:700;font-size:14px;letter-spacing:.3px">'
                 . e($ctaText) . ' &rarr;</a></td></tr></table>';
    }

    $badgeHtml = '';
    if ($badge !== '') {
        $badgeHtml = '<span style="display:inline-block;background:' . e($badgeBg) . ';color:#0d1b2a;padding:4px 12px;border-radius:100px;font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase">' . e($badge) . '</span>';
    }

    $contactBlock = '<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:8px">';
    if ($tel) {
        $contactBlock .= '<tr><td style="padding:6px 0;font-size:13px;color:#374151"><span style="display:inline-block;width:18px;color:#e30b30">&#9742;</span> <a href="tel:' . preg_replace('/\s+/', '', $tel) . '" style="color:#374151;text-decoration:none">' . $tel . '</a></td></tr>';
    }
    if ($email) {
        $contactBlock .= '<tr><td style="padding:6px 0;font-size:13px;color:#374151"><span style="display:inline-block;width:18px;color:#e30b30">&#9993;</span> <a href="mailto:' . $email . '" style="color:#374151;text-decoration:none">' . $email . '</a></td></tr>';
    }
    if ($adres) {
        $contactBlock .= '<tr><td style="padding:6px 0;font-size:13px;color:#374151"><span style="display:inline-block;width:18px;color:#e30b30">&#9678;</span> ' . $adres . '</td></tr>';
    }
    $contactBlock .= '</table>';

    return <<<HTML
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>{$title}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;color:#1f2937;-webkit-font-smoothing:antialiased">

  <div style="display:none;font-size:1px;color:#f4f6f9;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden">{$preheader}</div>

  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:32px 0">
    <tr>
      <td align="center">

        <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 8px 24px rgba(13,27,42,.08)">

          <tr>
            <td style="background:#0d1b2a;background-image:linear-gradient(135deg,#0d1b2a 0%,#1b263b 100%);padding:32px 40px;border-bottom:4px solid #e30b30">
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td>
                    <div style="font-family:Georgia,'Times New Roman',serif;color:#e30b30;font-size:14px;font-style:italic;letter-spacing:1px;margin-bottom:6px">Guven ve Ozen Ile</div>
                    <div style="color:#ffffff;font-size:24px;font-weight:800;letter-spacing:.5px">{$brandShort}</div>
                  </td>
                  <td align="right" valign="top">
                    {$badgeHtml}
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td style="padding:36px 40px;font-size:15px;line-height:1.7;color:#1f2937">
              {$bodyHtml}
              {$ctaHtml}
            </td>
          </tr>

          <tr>
            <td style="background:#f8fafc;padding:18px 40px;border-top:1px solid #e5e7eb">
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="font-size:11px;color:#6b7280;letter-spacing:.8px;text-transform:uppercase;font-weight:600;padding-bottom:10px">Hizli Erisim</td>
                </tr>
                <tr>
                  <td align="center">
                    <a href="{$teklifUrl}" style="display:inline-block;color:#0d1b2a;text-decoration:none;font-size:12px;font-weight:600;padding:6px 12px;border:1px solid #d1d5db;border-radius:6px;margin:2px">Teklif Al</a>
                    <a href="{$hasarUrl}" style="display:inline-block;color:#0d1b2a;text-decoration:none;font-size:12px;font-weight:600;padding:6px 12px;border:1px solid #d1d5db;border-radius:6px;margin:2px">Hasar Ihbari</a>
                    <a href="{$web}" style="display:inline-block;color:#0d1b2a;text-decoration:none;font-size:12px;font-weight:600;padding:6px 12px;border:1px solid #d1d5db;border-radius:6px;margin:2px">Web Sitesi</a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td style="padding:24px 40px 16px;background:#ffffff">
              <div style="font-size:11px;color:#9ca3af;letter-spacing:1px;text-transform:uppercase;font-weight:700;margin-bottom:6px">Iletisim</div>
              {$contactBlock}
            </td>
          </tr>

          <tr>
            <td style="background:#0d1b2a;padding:20px 40px;text-align:center">
              <div style="margin-bottom:8px">{$socialHtml}</div>
              <div style="font-size:11px;color:#6b7280;line-height:1.5">
                &copy; {$year} {$brand}<br>
                Bu otomatik gonderilen bir e-postadir.<br>
                Sorulariniz icin: <a href="mailto:{$email}" style="color:#9ca3af">{$email}</a>
              </div>
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
