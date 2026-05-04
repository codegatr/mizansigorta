<?php
/**
 * Mizan Sigorta - CSRF Korumasi (HMAC tabanli)
 * includes/csrf.php
 *
 * Stateless: oturuma bagli rastgele bir secret turetiyor; token'i HMAC ile imzaliyor.
 * Forms: <?= csrf_field() ?> | API: csrf_check($_POST[CSRF_TOKEN_NAME] ?? '')
 */

declare(strict_types=1);

if (!defined('MIZAN_BOOT')) {
    http_response_code(403);
    exit('Forbidden');
}

function csrf_secret(): string
{
    if (empty($_SESSION['_csrf_secret'])) {
        $_SESSION['_csrf_secret'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['_csrf_secret'];
}

function csrf_token(int $ttl = 7200): string
{
    $exp     = time() + $ttl;
    $payload = $exp . '.' . bin2hex(random_bytes(8));
    $sig     = hash_hmac('sha256', $payload, csrf_secret());
    return $payload . '.' . $sig;
}

function csrf_check(?string $token): bool
{
    if (!$token) return false;
    $parts = explode('.', $token);
    if (count($parts) !== 3) return false;
    [$exp, $rand, $sig] = $parts;
    if (!ctype_digit($exp) || (int) $exp < time()) return false;
    $expected = hash_hmac('sha256', $exp . '.' . $rand, csrf_secret());
    return hash_equals($expected, $sig);
}

function csrf_field(): string
{
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . e(csrf_token()) . '">';
}

function csrf_assert_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    if (!csrf_check($_POST[CSRF_TOKEN_NAME] ?? null)) {
        http_response_code(419);
        die('Oturum suresi dolmus veya gecersiz form. Lutfen sayfayi yenileyin.');
    }
}
