<?php
/**
 * Mizan Sigorta - Admin Helpers
 * yonetim/_helpers.php
 *
 * Tum admin sayfalarinin _layout.php'den sonra dahil ettigi yardimci fonksiyonlar.
 */

if (!defined('MZ_ADMIN')) { http_response_code(403); exit; }

if (!function_exists('admin_url')) {
    function admin_url(string $path = ''): string
    {
        $path = ltrim($path, '/');
        return rtrim(SITE_BASE_URL, '/') . '/yonetim/' . $path;
    }
}

if (!function_exists('badge_durum')) {
    /**
     * Teklif/police/hasar durumlarina renkli badge.
     */
    function badge_durum(string $durum): string
    {
        $map = [
            // teklif
            'yeni'              => ['warning', 'Yeni'],
            'islemde'           => ['info', 'İşlemde'],
            'teklif_hazir'      => ['primary', 'Teklif Hazır'],
            'teklif_gonderildi' => ['secondary', 'Teklif Gönderildi'],
            'onaylandi'         => ['success', 'Onaylandı'],
            'police_oldu'       => ['success', 'Poliçe Oldu'],
            'iptal'             => ['danger', 'İptal'],
            'kayip'             => ['dark', 'Kayıp'],
            // police
            'aktif'             => ['success', 'Aktif'],
            'sona_erdi'         => ['secondary', 'Sona Erdi'],
            'iptal_police'      => ['danger', 'İptal'],
            // hasar
            'inceleniyor'       => ['info', 'İnceleniyor'],
            'eksper_atandi'     => ['primary', 'Eksper Atandı'],
            'odendi'            => ['success', 'Ödendi'],
            'reddedildi'        => ['danger', 'Reddedildi'],
        ];
        $row = $map[$durum] ?? ['secondary', $durum];
        return '<span class="badge bg-' . $row[0] . '">' . htmlspecialchars($row[1], ENT_QUOTES, 'UTF-8') . '</span>';
    }
}

if (!function_exists('badge_oncelik')) {
    function badge_oncelik(string $o): string
    {
        $map = [
            'dusuk'  => ['secondary', 'Düşük'],
            'normal' => ['info', 'Normal'],
            'yuksek' => ['warning', 'Yüksek'],
            'acil'   => ['danger', 'Acil'],
        ];
        $r = $map[$o] ?? ['secondary', $o];
        return '<span class="badge bg-' . $r[0] . '">' . htmlspecialchars($r[1], ENT_QUOTES, 'UTF-8') . '</span>';
    }
}

if (!function_exists('admin_redirect')) {
    function admin_redirect(string $page, string $type = 'success', string $msg = ''): void
    {
        if ($msg) flash_set($type, $msg);
        header('Location: ' . $page);
        exit;
    }
}

if (!function_exists('admin_handle_upload')) {
    /**
     * Tek dosya yukleme yardimcisi. Dondurur: kaydedilmis dosya adi veya null.
     */
    function admin_handle_upload(string $field, string $subdir, array $okExt = ['jpg','jpeg','png','webp','gif','svg','pdf']): ?string
    {
        if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Yükleme hatası (kod ' . (int)$_FILES[$field]['error'] . ')');
        }
        $orig = $_FILES[$field]['name'];
        $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if (!in_array($ext, $okExt, true)) {
            throw new RuntimeException('İzin verilmeyen dosya türü: ' . $ext);
        }
        if (filesize($_FILES[$field]['tmp_name']) > 10 * 1024 * 1024) {
            throw new RuntimeException('Dosya 10MB üst sınırını aşıyor.');
        }
        $dir = MIZAN_UPLOADS . '/' . $subdir;
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dir . '/' . $name)) {
            throw new RuntimeException('Dosya kaydedilemedi.');
        }
        return $name;
    }
}

if (!function_exists('admin_delete_upload')) {
    function admin_delete_upload(string $subdir, ?string $name): void
    {
        if (!$name) return;
        $p = MIZAN_UPLOADS . '/' . $subdir . '/' . basename($name);
        if (is_file($p)) @unlink($p);
    }
}

if (!function_exists('admin_filter_input')) {
    function admin_filter_input(string $key, string $type = 'string', $default = '')
    {
        $v = $_GET[$key] ?? $default;
        return match ($type) {
            'int'  => (int)$v,
            'bool' => in_array($v, ['1','true','on','yes'], true) ? 1 : 0,
            default => is_string($v) ? trim($v) : $default,
        };
    }
}
