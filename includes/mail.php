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

    $preheader = e((string)($opts['preheader'] ?? 'Mizan Sigorta — 12+ anlaşmalı şirket arasında en uygun teminat'));
    $ctaText   = (string)($opts['cta_text'] ?? '');
    $ctaUrl    = (string)($opts['cta_url'] ?? '');
    $badge     = (string)($opts['badge'] ?? '');
    $badgeBg   = (string)($opts['badge_color'] ?? '#22c55e');

    // Sosyal medya icon'lari (SVG inline - mail-safe)
    $socialIcons = [
        'facebook'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
        'instagram' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>',
        'twitter'   => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
        'linkedin'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.063 2.063 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
        'youtube'   => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
    ];

    $socials = [];
    foreach (['facebook', 'instagram', 'twitter', 'linkedin', 'youtube'] as $key) {
        if ($u = trim((string) setting($key))) {
            $socials[] = ['url' => e($u), 'icon' => $socialIcons[$key] ?? '', 'label' => ucfirst($key)];
        }
    }
    $socialHtml = '';
    foreach ($socials as $s) {
        $socialHtml .= '<a href="' . $s['url'] . '" style="display:inline-block;width:36px;height:36px;line-height:36px;border-radius:50%;background:rgba(255,255,255,.08);color:#9ca3af;text-align:center;text-decoration:none;margin:0 4px;vertical-align:middle" title="' . e($s['label']) . '">' . $s['icon'] . '</a>';
    }

    // CTA buton (varsa)
    $ctaHtml = '';
    if ($ctaText !== '' && $ctaUrl !== '') {
        $ctaHtml = '<table cellpadding="0" cellspacing="0" border="0" style="margin:28px 0"><tr><td>'
                 . '<a href="' . e($ctaUrl) . '" style="display:inline-block;background:linear-gradient(135deg,#e30b30 0%,#a91020 100%);color:#ffffff;padding:16px 36px;border-radius:10px;text-decoration:none;font-weight:700;font-size:15px;letter-spacing:.3px;box-shadow:0 4px 14px rgba(227,11,48,.35)">'
                 . e($ctaText) . ' &rarr;</a></td></tr></table>';
    }

    // Badge (rozet)
    $badgeHtml = '';
    if ($badge !== '') {
        $badgeHtml = '<table cellpadding="0" cellspacing="0" border="0"><tr><td style="background:' . e($badgeBg) . ';color:#ffffff;padding:7px 16px;border-radius:100px;font-size:11px;font-weight:800;letter-spacing:.8px;text-transform:uppercase;box-shadow:0 2px 8px rgba(0,0,0,.18)">&#10004; ' . e($badge) . '</td></tr></table>';
    }

    // Iletisim bloğu (kart sıralı)
    $iletisimCards = '';
    if ($tel) {
        $telClean = preg_replace('/[^0-9+]/', '', $tel);
        $iletisimCards .= '<td style="padding:8px;width:33.33%;vertical-align:top">'
            . '<a href="tel:' . $telClean . '" style="display:block;text-decoration:none;color:inherit">'
            .   '<table cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#ffffff;border:1px solid #e5e7eb;border-radius:10px;padding:14px;text-align:center"><tr><td>'
            .     '<div style="width:40px;height:40px;line-height:40px;border-radius:50%;background:linear-gradient(135deg,#e30b30,#a91020);color:#ffffff;font-size:18px;margin:0 auto 8px">&#9742;</div>'
            .     '<div style="font-size:10px;color:#6b7280;letter-spacing:.5px;text-transform:uppercase;font-weight:700;margin-bottom:4px">Telefon</div>'
            .     '<div style="font-size:13px;color:#0d1b2a;font-weight:700">' . $tel . '</div>'
            .   '</td></tr></table>'
            . '</a></td>';
    }
    if ($whatsapp && strlen($whatsapp) >= 11) {
        $iletisimCards .= '<td style="padding:8px;width:33.33%;vertical-align:top">'
            . '<a href="https://wa.me/' . $whatsapp . '" style="display:block;text-decoration:none;color:inherit">'
            .   '<table cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#ffffff;border:1px solid #e5e7eb;border-radius:10px;padding:14px;text-align:center"><tr><td>'
            .     '<div style="width:40px;height:40px;line-height:40px;border-radius:50%;background:linear-gradient(135deg,#25d366,#128c7e);color:#ffffff;font-size:18px;margin:0 auto 8px;font-weight:700">W</div>'
            .     '<div style="font-size:10px;color:#6b7280;letter-spacing:.5px;text-transform:uppercase;font-weight:700;margin-bottom:4px">WhatsApp</div>'
            .     '<div style="font-size:13px;color:#0d1b2a;font-weight:700">Hemen Yaz</div>'
            .   '</td></tr></table>'
            . '</a></td>';
    }
    if ($email) {
        $iletisimCards .= '<td style="padding:8px;width:33.33%;vertical-align:top">'
            . '<a href="mailto:' . $email . '" style="display:block;text-decoration:none;color:inherit">'
            .   '<table cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#ffffff;border:1px solid #e5e7eb;border-radius:10px;padding:14px;text-align:center"><tr><td>'
            .     '<div style="width:40px;height:40px;line-height:40px;border-radius:50%;background:linear-gradient(135deg,#0d6efd,#0a58ca);color:#ffffff;font-size:18px;margin:0 auto 8px">&#9993;</div>'
            .     '<div style="font-size:10px;color:#6b7280;letter-spacing:.5px;text-transform:uppercase;font-weight:700;margin-bottom:4px">E-posta</div>'
            .     '<div style="font-size:12px;color:#0d1b2a;font-weight:700;word-break:break-all">' . $email . '</div>'
            .   '</td></tr></table>'
            . '</a></td>';
    }

    // Trust badges (guven simgeleri)
    $trustHtml = '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:8px"><tr>'
        . '<td align="center" style="padding:6px 4px"><div style="font-size:18px;color:#f4d35e;line-height:1">&#9733;</div><div style="font-size:10px;color:#9ca3af;font-weight:600;margin-top:2px">12+ Şirket</div></td>'
        . '<td align="center" style="padding:6px 4px"><div style="font-size:18px;color:#f4d35e;line-height:1">&#10004;</div><div style="font-size:10px;color:#9ca3af;font-weight:600;margin-top:2px">KVKK Uyumlu</div></td>'
        . '<td align="center" style="padding:6px 4px"><div style="font-size:18px;color:#f4d35e;line-height:1">&#128274;</div><div style="font-size:10px;color:#9ca3af;font-weight:600;margin-top:2px">Güvenli</div></td>'
        . '<td align="center" style="padding:6px 4px"><div style="font-size:18px;color:#f4d35e;line-height:1">&#9200;</div><div style="font-size:10px;color:#9ca3af;font-weight:600;margin-top:2px">7/24 Destek</div></td>'
        . '</tr></table>';

    // Sigorta tipleri footer linkleri
    $urunLinks = '<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'
        . '<td align="center" style="font-size:11px;line-height:1.8;color:#6b7280">'
        .   '<a href="' . $web . '/urun/kasko" style="color:#6b7280;text-decoration:none;margin:0 6px">Kasko</a> &middot; '
        .   '<a href="' . $web . '/urun/trafik-zorunlu-sorumluluk" style="color:#6b7280;text-decoration:none;margin:0 6px">Trafik</a> &middot; '
        .   '<a href="' . $web . '/urun/konut-sigortasi" style="color:#6b7280;text-decoration:none;margin:0 6px">Konut</a> &middot; '
        .   '<a href="' . $web . '/urun/dask" style="color:#6b7280;text-decoration:none;margin:0 6px">DASK</a> &middot; '
        .   '<a href="' . $web . '/urun/ozel-saglik-sigortasi" style="color:#6b7280;text-decoration:none;margin:0 6px">Sağlık</a>'
        . '</td>'
        . '</tr></table>';

    return <<<HTML
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="x-apple-disable-message-reformatting">
<meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
<meta name="color-scheme" content="light only">
<title>{$title}</title>
<!--[if mso]><xml><o:OfficeDocumentSettings><o:AllowPNG/><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml><![endif]-->
<style>
@media screen and (max-width:600px){
  .mz-pad{padding:24px 20px !important}
  .mz-hero-pad{padding:32px 24px !important}
  .mz-hero-title{font-size:22px !important}
  .mz-iletisim-cards td{display:block !important;width:100% !important;padding:6px 0 !important}
  .mz-cta a{font-size:14px !important;padding:14px 24px !important}
}
</style>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;color:#1f2937;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale">

  <div style="display:none;font-size:1px;color:#f4f6f9;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden">{$preheader}</div>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f6f9;padding:32px 0">
    <tr>
      <td align="center">

        <!-- Ana Kart -->
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 12px 40px rgba(13,27,42,.12),0 4px 12px rgba(13,27,42,.08)">

          <!-- HEADER: Premium Navy + Hero -->
          <tr>
            <td class="mz-hero-pad" style="background:linear-gradient(135deg,#0d1b2a 0%,#1b263b 50%,#1a3a5c 100%);padding:40px 48px;position:relative">
              <!-- Marka satiri -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td>
                    <!-- Logo SVG inline (kalkan + M harfi) -->
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
                      <td style="vertical-align:middle;padding-right:14px">
                        <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#e30b30 0%,#a91020 100%);text-align:center;line-height:48px;font-family:Georgia,serif;font-size:24px;font-weight:800;color:#ffffff;box-shadow:0 4px 12px rgba(227,11,48,.35)">M</div>
                      </td>
                      <td style="vertical-align:middle">
                        <div style="font-family:Georgia,'Times New Roman',serif;color:#f4d35e;font-size:11px;font-style:italic;letter-spacing:2px;text-transform:uppercase;margin-bottom:2px">Güven ve Özen İle</div>
                        <div class="mz-hero-title" style="color:#ffffff;font-size:24px;font-weight:800;letter-spacing:.5px;line-height:1.2">{$brandShort}</div>
                      </td>
                    </tr></table>
                  </td>
                  <td align="right" valign="top" style="padding-top:6px">
                    {$badgeHtml}
                  </td>
                </tr>
              </table>

              <!-- Decorative gradient line -->
              <div style="height:3px;background:linear-gradient(90deg,#e30b30 0%,#f4d35e 50%,#e30b30 100%);margin-top:24px;border-radius:2px;opacity:.85"></div>
            </td>
          </tr>

          <!-- BODY -->
          <tr>
            <td class="mz-pad" style="padding:40px 48px;font-size:15px;line-height:1.7;color:#1f2937;background:#ffffff">
              {$bodyHtml}
              <div class="mz-cta">{$ctaHtml}</div>
            </td>
          </tr>

          <!-- Trust Bar -->
          <tr>
            <td style="background:#0d1b2a;padding:18px 48px">
              {$trustHtml}
            </td>
          </tr>

          <!-- ILETISIM CARD GRID (3 sutun) -->
          <tr>
            <td style="background:#f8fafc;padding:24px 36px">
              <div style="text-align:center;font-size:11px;color:#6b7280;letter-spacing:1.5px;text-transform:uppercase;font-weight:800;margin-bottom:14px">Bize Ulaşın</div>
              <table class="mz-iletisim-cards" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
                {$iletisimCards}
              </tr></table>
            </td>
          </tr>

          <!-- Hizli erisim - Sigorta tipleri footer linkler -->
          <tr>
            <td style="background:#ffffff;padding:20px 36px;border-top:1px solid #e5e7eb;border-bottom:1px solid #e5e7eb">
              <div style="text-align:center;font-size:10px;color:#9ca3af;letter-spacing:1.2px;text-transform:uppercase;font-weight:700;margin-bottom:8px">Sigorta Türlerimiz</div>
              {$urunLinks}
            </td>
          </tr>

          <!-- FOOTER: Sosyal + Telif -->
          <tr>
            <td style="background:linear-gradient(135deg,#0d1b2a 0%,#1b263b 100%);padding:28px 36px;text-align:center">
              <div style="margin-bottom:14px">{$socialHtml}</div>
              <div style="height:1px;background:rgba(255,255,255,.08);margin:14px 0"></div>
              <div style="font-size:11px;color:#9ca3af;line-height:1.6">
                <strong style="color:#ffffff">{$brand}</strong><br>
                {$adres}<br>
                <span style="opacity:.6">&copy; {$year} Tüm hakları saklıdır.</span>
              </div>
              <div style="font-size:10px;color:#6b7280;margin-top:14px;line-height:1.5">
                Bu otomatik gönderilen bir e-postadır.<br>
                Sorularınız için: <a href="mailto:{$email}" style="color:#9ca3af;text-decoration:underline">{$email}</a>
              </div>
            </td>
          </tr>

        </table>

        <!-- Anti-spam tagline -->
        <div style="max-width:600px;margin:18px auto 0;padding:0 20px;text-align:center;font-size:11px;color:#9ca3af;line-height:1.5">
          {$brandShort} &middot; T.C. Hazine ve Maliye Bakanlığı SBM Lisanslı Sigorta Aracılık Şirketi<br>
          KVKK kapsamında bilgileriniz koruma altındadır.
        </div>

      </td>
    </tr>
  </table>

</body>
</html>
HTML;
}
