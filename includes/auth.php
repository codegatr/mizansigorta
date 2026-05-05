<?php
/**
 * Mizan Sigorta - Yetkilendirme
 * includes/auth.php
 */

declare(strict_types=1);

if (!defined('MIZAN_BOOT')) {
    http_response_code(403);
    exit('Forbidden');
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function user_login(string $identifier, string $password): array
{
    // $identifier email veya kullanici_adi olabilir.
    // Iceren '@' ise email olarak, aksi halde kullanici_adi olarak ara.
    $identifier = strtolower(trim($identifier));
    if ($identifier === '') {
        return ['ok' => false, 'msg' => 'E-posta veya kullanici adi giriniz.'];
    }

    if (strpos($identifier, '@') !== false) {
        // Email gibi gorunuyor - email kolonundan ara
        $u = db_row('SELECT * FROM ' . t('kullanicilar') . ' WHERE email = ? LIMIT 1', [$identifier]);
    } else {
        // Kullanici adi - kullanici_adi kolonundan ara (yoksa fallback olarak emailin @ oncesi)
        $u = null;
        try {
            $u = db_row('SELECT * FROM ' . t('kullanicilar') . ' WHERE kullanici_adi = ? LIMIT 1', [$identifier]);
        } catch (Throwable $e) {
            // kullanici_adi kolonu yoksa (migration calismamissa) graceful fallback
            $u = null;
        }
        // Bulunamazsa: emailin @ oncesi karsilastirmasi (kolon yokken cakismayi onler)
        if (!$u) {
            $u = db_row('SELECT * FROM ' . t('kullanicilar') . ' WHERE LOWER(SUBSTRING_INDEX(email, "@", 1)) = ? LIMIT 1', [$identifier]);
        }
    }

    if (!$u) {
        return ['ok' => false, 'msg' => 'Kullanici adi/e-posta veya sifre hatali.'];
    }
    if ((int) $u['aktif'] !== 1) {
        return ['ok' => false, 'msg' => 'Hesabiniz devre disi.'];
    }
    if ($u['kilit_bitis'] && strtotime($u['kilit_bitis']) > time()) {
        $kalan = (int) ceil((strtotime($u['kilit_bitis']) - time()) / 60);
        return ['ok' => false, 'msg' => 'Cok fazla hatali deneme. ' . $kalan . ' dk sonra tekrar deneyin.'];
    }
    if (!password_verify($password, $u['sifre_hash'])) {
        $hatali = (int) $u['hatali_giris'] + 1;
        $kilit = $hatali >= ADMIN_LOGIN_LOCK
            ? date('Y-m-d H:i:s', time() + ADMIN_LOCK_MINUTES * 60)
            : null;
        db_exec('UPDATE ' . t('kullanicilar') . ' SET hatali_giris=?, kilit_bitis=? WHERE id=?',
            [$hatali, $kilit, $u['id']]);
        return ['ok' => false, 'msg' => 'Kullanici adi/e-posta veya sifre hatali.'];
    }
    db_exec('UPDATE ' . t('kullanicilar') .
        ' SET hatali_giris=0, kilit_bitis=NULL, son_giris=NOW(), son_giris_ip=? WHERE id=?',
        [client_ip(), $u['id']]);
    session_regenerate_id(true);
    $_SESSION['user_id']      = (int) $u['id'];
    $_SESSION['user_email']   = $u['email'];
    $_SESSION['user_name']    = $u['ad_soyad'];
    $_SESSION['user_role']    = $u['rol'];
    $_SESSION['login_at']     = time();
    audit_log('giris', 'kullanici', (int) $u['id'], 'Yonetim paneline giris');
    return ['ok' => true, 'user' => $u];
}

function user_logout(): void
{
    if (!empty($_SESSION['user_id'])) {
        audit_log('cikis', 'kullanici', (int) $_SESSION['user_id'], 'Cikis yapildi');
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function user_role(): string
{
    return (string) ($_SESSION['user_role'] ?? '');
}

function require_login(): void
{
    if (!user_id()) {
        $_SESSION['login_redirect'] = $_SERVER['REQUEST_URI'] ?? '/yonetim/';
        safe_redirect('/yonetim/login.php');
    }
}

function require_role(string ...$roles): void
{
    require_login();
    if (!in_array(user_role(), $roles, true)) {
        http_response_code(403);
        die('Bu sayfaya erisim yetkiniz yok.');
    }
}

function is_superadmin(): bool { return user_role() === 'superadmin'; }
function is_admin(): bool      { return in_array(user_role(), ['superadmin','admin'], true); }
