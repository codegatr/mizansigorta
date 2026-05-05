<?php
/**
 * Mizan Sigorta - Guvenli gorsel upload helper
 *
 * Kullanim:
 *   $r = upload_image($_FILES['gorsel_dosya'], 'kampanyalar');
 *   if ($r['ok']) { $url = $r['url']; }  // /uploads/kampanyalar/abc123.jpg
 *   else { $hata = $r['msg']; }
 *
 * Guvenlik:
 * - Sadece izinli MIME + uzanti
 * - Magic byte (image/*) dogrulamasi (getimagesize)
 * - Path traversal koruma (sadece oz dosya adi)
 * - Random uniqid dosya adi (sahte uzanti saldirilarini onler)
 * - 5 MB limit (varsayilan)
 */

/**
 * @param array  $file   $_FILES['gorsel_dosya'] gibi
 * @param string $klasor uploads/$klasor/ altina kaydedilir (kampanyalar/blog/...)
 * @param array  $opts   max_bytes (default 5MB), allowed (default jpg/png/webp/gif)
 * @return array ['ok' => bool, 'url' => '/uploads/...', 'msg' => 'hata'|null, 'path' => fs path]
 */
function upload_image(array $file, string $klasor, array $opts = []): array
{
    $maxBytes = (int)($opts['max_bytes'] ?? (5 * 1024 * 1024)); // 5 MB
    $allowed  = $opts['allowed'] ?? [
        'image/jpeg' => 'jpg',
        'image/jpg'  => 'jpg',
        'image/pjpeg'=> 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    // Yuklenmis bir dosya yok
    if (empty($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'msg' => 'Dosya yuklenmedi.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errMsg = [
            UPLOAD_ERR_INI_SIZE   => 'Dosya boyutu PHP ini limitini astı.',
            UPLOAD_ERR_FORM_SIZE  => 'Dosya boyutu form limitini astı.',
            UPLOAD_ERR_PARTIAL    => 'Dosya yarım yuklendi.',
            UPLOAD_ERR_NO_TMP_DIR => 'Sunucuda gecici klasor bulunamadi.',
            UPLOAD_ERR_CANT_WRITE => 'Sunucuya yazilamadi (izin sorunu).',
            UPLOAD_ERR_EXTENSION  => 'PHP eklentisi yuklemeyi engelledi.',
        ][$file['error']] ?? 'Bilinmeyen yukleme hatasi.';
        return ['ok' => false, 'msg' => $errMsg];
    }

    // Boyut kontrol
    if ($file['size'] > $maxBytes) {
        $mb = round($maxBytes / 1024 / 1024, 1);
        return ['ok' => false, 'msg' => 'Dosya boyutu çok büyük. En fazla ' . $mb . ' MB olabilir.'];
    }

    // Tmp dosya gecerli mi
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'msg' => 'Yukleme dogrulanamadi.'];
    }

    // Magic byte kontrolu (gercekten image mi?) - getimagesize kullanır
    $info = @getimagesize($file['tmp_name']);
    if (!$info || empty($info['mime'])) {
        return ['ok' => false, 'msg' => 'Yuklenen dosya gecerli bir resim degil.'];
    }
    $mime = strtolower($info['mime']);
    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'msg' => 'Bu resim tipi izinli degil. Sadece: JPG, PNG, WebP, GIF'];
    }
    $ext = $allowed[$mime];

    // Hedef klasor (path traversal koruma - sadece [a-z0-9_])
    $klasor = preg_replace('/[^a-z0-9_]/i', '', $klasor);
    if ($klasor === '') $klasor = 'genel';

    // /home/.../public_html/uploads/<klasor>/
    $base = defined('MIZAN_ROOT') ? MIZAN_ROOT : dirname(__DIR__);
    $dir  = $base . '/uploads/' . $klasor;
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0755, true)) {
            return ['ok' => false, 'msg' => 'Hedef klasor olusturulamadi: ' . $klasor];
        }
    }

    // Guvenli dosya adi (random + extension)
    $name = date('Ymd') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest = $dir . '/' . $name;

    if (!@move_uploaded_file($file['tmp_name'], $dest)) {
        return ['ok' => false, 'msg' => 'Dosya tasinamadi (izin sorunu olabilir).'];
    }

    // Web URL'si (kok-dizin gore relative)
    $url = '/uploads/' . $klasor . '/' . $name;

    return [
        'ok'   => true,
        'url'  => $url,
        'path' => $dest,
        'msg'  => null,
        'mime' => $mime,
        'size' => $file['size'],
        'w'    => (int)$info[0],
        'h'    => (int)$info[1],
    ];
}

/**
 * Yuklenmis bir gorseli sil. URL veya filesystem path olabilir.
 * Path traversal koruma: sadece /uploads/ altindaki dosyalar silinir.
 */
function delete_uploaded_image(?string $url): bool
{
    if (!$url) return false;
    $rel = parse_url($url, PHP_URL_PATH) ?: $url;
    // Sadece /uploads/ ile baslayan path'lere izin
    if (strpos($rel, '/uploads/') !== 0) return false;
    $base = defined('MIZAN_ROOT') ? MIZAN_ROOT : dirname(__DIR__);
    $path = $base . $rel;
    // Path normalize - iki nokta ust uste yasak
    if (strpos($rel, '..') !== false) return false;
    if (is_file($path)) {
        return @unlink($path);
    }
    return false;
}
