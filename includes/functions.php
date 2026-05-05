<?php
/**
 * Mizan Sigorta - Yardimci Fonksiyonlar
 * includes/functions.php
 */

declare(strict_types=1);

if (!defined('MIZAN_BOOT')) {
    http_response_code(403);
    exit('Forbidden');
}

/** XSS-safe HTML escape */
function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Yonlendirme */
function redirect(string $url, int $code = 302): void
{
    if (!headers_sent()) {
        header('Location: ' . $url, true, $code);
    }
    exit;
}

/** Sadece site icindeki url'leri kabul eden yonlendirme */
function safe_redirect(string $path, int $code = 302): void
{
    $path = '/' . ltrim($path, '/');
    redirect(SITE_BASE_URL . $path, $code);
}

/** url helper */
function u(string $path = ''): string
{
    return SITE_BASE_URL . '/' . ltrim($path, '/');
}

/** asset helper (cache buster ile) */
function asset(string $path): string
{
    $abs = MIZAN_ROOT . '/' . ltrim($path, '/');
    $v   = file_exists($abs) ? filemtime($abs) : SITE_VERSION;
    return SITE_BASE_URL . '/' . ltrim($path, '/') . '?v=' . $v;
}

/**
 * Sigorta sirketi logo URL'i — esnek yol cozumu
 * - 'assets/img/sirketler/anadolu.svg' (path icerir) → direkt
 * - 'mylogo.png' (sadece dosya adi) → uploads/sirket/mylogo.png
 * - bos/null → null
 */
function sirket_logo_url(?string $logo): ?string
{
    if (!$logo) return null;
    if (str_contains($logo, '/')) return u(ltrim($logo, '/'));
    return u('uploads/sirket/' . rawurlencode($logo));
}

/** ASCII slug (Turkce karakterleri donusturur) */
function slugify(string $text): string
{
    $map = [
        'ı'=>'i','İ'=>'i','ğ'=>'g','Ğ'=>'g','ü'=>'u','Ü'=>'u',
        'ş'=>'s','Ş'=>'s','ö'=>'o','Ö'=>'o','ç'=>'c','Ç'=>'c'
    ];
    $text = strtr($text, $map);
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('~[^a-z0-9]+~u', '-', $text);
    $text = trim((string) $text, '-');
    return $text === '' ? 'kayit' : $text;
}

/** Tarih formatlari (TR) */
function tr_date($v, string $fmt = 'd.m.Y'): string
{
    if (!$v) return '-';
    $t = is_numeric($v) ? (int) $v : strtotime((string) $v);
    return $t ? date($fmt, $t) : '-';
}

function tr_datetime($v): string
{
    return tr_date($v, 'd.m.Y H:i');
}

/** Para formati */
function tr_money($v, string $cur = 'TRY'): string
{
    $sym = ['TRY' => '₺', 'USD' => '$', 'EUR' => '€'][$cur] ?? $cur;
    return number_format((float) $v, 2, ',', '.') . ' ' . $sym;
}

/** Ayar oku (caching) */
function setting(string $key, $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        try {
            $rows = db_all('SELECT anahtar, deger FROM ' . t('ayarlar'));
            $cache = [];
            foreach ($rows as $r) $cache[$r['anahtar']] = (string) $r['deger'];
        } catch (Throwable $e) { $cache = []; }
    }
    return $cache[$key] ?? (string) $default;
}

/** Ayar yaz (cache invalidate icin runtime'da yeniden okuyacak; basit set) */
function setting_set(string $key, string $value): void
{
    db_exec(
        'INSERT INTO ' . t('ayarlar') . ' (anahtar, deger) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE deger = VALUES(deger)',
        [$key, $value]
    );
}

/** Benzersiz teklif/hasar numarasi uretici */
function generate_no(string $prefix = 'TKL'): string
{
    return $prefix . '-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
}

/** Istemci IP'si */
function client_ip(): string
{
    foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) {
            $ip = trim(explode(',', $_SERVER[$k])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

/** Telefon normalize: +90 5XX XXX XX XX -> 905XXXXXXXXX */
function normalize_phone(string $phone): string
{
    $p = preg_replace('/\D+/', '', $phone) ?? '';
    if (strlen($p) === 10 && $p[0] === '5') $p = '90' . $p;
    if (strlen($p) === 11 && $p[0] === '0') $p = '9' . $p;
    return $p;
}

/** TCKN dogrulamasi (algoritma) */
function valid_tckn(string $tckn): bool
{
    if (!preg_match('/^[1-9][0-9]{10}$/', $tckn)) return false;
    $d = array_map('intval', str_split($tckn));
    $oddSum  = $d[0] + $d[2] + $d[4] + $d[6] + $d[8];
    $evenSum = $d[1] + $d[3] + $d[5] + $d[7];
    if ((($oddSum * 7) - $evenSum) % 10 !== $d[9]) return false;
    if ((array_sum(array_slice($d, 0, 10)) % 10) !== $d[10]) return false;
    return true;
}

/** Pagination yardimcisi */
function paginate(int $total, int $perPage, int $page): array
{
    $total = max(0, $total); $perPage = max(1, $perPage);
    $pages = max(1, (int) ceil($total / $perPage));
    $page  = max(1, min($page, $pages));
    return [
        'total'   => $total,
        'pages'   => $pages,
        'page'    => $page,
        'offset'  => ($page - 1) * $perPage,
        'perPage' => $perPage,
    ];
}

/** Audit log yaz */
function audit_log(string $eylem, ?string $nesne_tip = null, ?int $nesne_id = null,
                   ?string $aciklama = null, ?array $eski = null, ?array $yeni = null): void
{
    try {
        db_exec(
            'INSERT INTO ' . t('audit_log') .
            ' (kullanici_id, kullanici_email, eylem, nesne_tip, nesne_id, aciklama, eski_veri, yeni_veri, ip_adresi, user_agent)
              VALUES (?,?,?,?,?,?,?,?,?,?)',
            [
                $_SESSION['user_id'] ?? null,
                $_SESSION['user_email'] ?? null,
                $eylem, $nesne_tip, $nesne_id, $aciklama,
                $eski ? json_encode($eski, JSON_UNESCAPED_UNICODE) : null,
                $yeni ? json_encode($yeni, JSON_UNESCAPED_UNICODE) : null,
                client_ip(),
                substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]
        );
    } catch (Throwable $e) { /* sessizce gec */ }
}

/** Mesaj (flash) */
function flash_set(string $type, string $msg): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'msg' => $msg];
}
function flash_get(): array
{
    $list = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $list;
}

/** JSON yanit (api endpoint'leri icin) */
function json_response($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Sablon degiskenlerini doldur ({ad_soyad}, {teklif_no} vs.) */
function tpl_replace(string $template, array $vars): string
{
    foreach ($vars as $k => $v) {
        $template = str_replace('{' . $k . '}', (string) $v, $template);
    }
    return $template;
}

/** Bakim modu kontrolu (yoneticiler hariic herkese 503) */
function check_maintenance(bool $is_admin_area = false): void
{
    if ($is_admin_area) return;
    if (setting('bakim_modu', '0') === '1' && empty($_SESSION['user_id'])) {
        http_response_code(503);
        header('Retry-After: 3600');
        echo '<!DOCTYPE html><html lang="tr"><head><meta charset="utf-8"><title>Bakim</title>'
            . '<style>body{font-family:system-ui;text-align:center;padding:60px;background:#0d1b2a;color:#fff}h1{font-size:32px}</style>'
            . '</head><body><h1>Sistem bakimda</h1><p>Kisa sure icinde geri donecegiz.</p></body></html>';
        exit;
    }
}
