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

function send_mail(string $to, string $subject, string $bodyHtml, string $bodyText = ''): array
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

    $eol      = "\r\n";
    $boundary = '----=_MIZAN_' . bin2hex(random_bytes(8));

    $headers = [];
    $headers[] = 'From: ' . sprintf('"%s" <%s>', addslashes($fname), $from);
    $headers[] = 'Reply-To: ' . $from;
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
    $r = $cmd('RCPT TO:<' . $to . '>');
    if (!preg_match('/^25[01]/', $r)) {
        fclose($smtp);
        return ['ok' => false, 'msg' => 'Alici reddedildi: ' . trim($r)];
    }
    $cmd('DATA');
    $payload  = 'Subject: =?UTF-8?B?' . base64_encode($subject) . "?=\r\n";
    $payload .= 'To: <' . $to . ">\r\n";
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

/** HTML sablonu ile sarmala (admin bildirim ve musteri yanitlari icin kullan) */
function mail_template(string $title, string $bodyHtml): string
{
    $brand = e(setting('firma_adi', SITE_NAME));
    $year  = date('Y');
    return <<<HTML
<!DOCTYPE html>
<html lang="tr"><head><meta charset="UTF-8"><title>{$title}</title></head>
<body style="margin:0;background:#f4f6f9;font-family:Arial,Helvetica,sans-serif;color:#111">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding:24px 0;background:#f4f6f9">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 6px 24px rgba(13,27,42,.08)">
        <tr><td style="background:#0d1b2a;color:#fff;padding:18px 28px;font-size:20px;font-weight:700;letter-spacing:.3px">{$brand}</td></tr>
        <tr><td style="padding:28px;font-size:15px;line-height:1.6;color:#1f2937">{$bodyHtml}</td></tr>
        <tr><td style="background:#0d1b2a;color:#9ca3af;padding:14px 28px;font-size:12px">© {$year} {$brand} — Otomatik gonderilen e-postadir.</td></tr>
      </table>
    </td></tr>
  </table>
</body></html>
HTML;
}
