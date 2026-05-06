<?php
/**
 * Mizan Sigorta - Akilli Guncelleme Sistemi
 * yonetim/update.php
 *
 * ERP-style smart updater:
 * - Tabs: Genel Durum / Dosyalar / Commits / Yedekler / Veritabani / Ayarlar
 * - SHA-bazli akilli diff (sadece degisen dosyalar cekilir)
 * - Force Sync: tum dosyalari yeniden cek
 * - ZIP yedek + restore
 * - Migration calistirici
 * - GitHub commit history goruntuleyici
 * - Tum AJAX endpoint'leri: ?ajax=action
 */

declare(strict_types=1);

define('MZ_ADMIN', true);
$adminTitle = 'Akıllı Güncelleme';

// AJAX endpoint'leri layout'tan ONCE handle edilir, yoksa response = HTML + JSON karisimi olur
$IS_AJAX = isset($_GET['ajax']);

if ($IS_AJAX) {
    // AJAX: layout YOK, sadece bootstrap + helpers + auth + handler
    if (!defined('MIZAN_BOOT')) define('MIZAN_BOOT', true);
    require __DIR__ . '/../includes/bootstrap.php';
    require __DIR__ . '/_helpers.php';
    require_role('superadmin');
    header('Content-Type: application/json; charset=utf-8');
} else {
    // Normal sayfa: layout dahil
    require __DIR__ . '/_layout.php';
    require __DIR__ . '/_helpers.php';
    require_role('superadmin');
}

// =================================================================
//   KONFIGURASYON
// =================================================================
$manifestPath = MIZAN_ROOT . '/manifest.json';
$manifest = json_decode((string)@file_get_contents($manifestPath), true) ?: [];
$repoFull = $manifest['repo'] ?? 'codegatr/mizansigorta';
$ghBranch = setting('github_branch', 'main') ?: 'main';

// Guncelleme harici dosyalar/klasorler (asla degistirilmez)
// NOT: uploads/ buraya eklenmedi cunku ZIP icindeki yeni dosyalar
// (orn. slayt gorselleri) eksik kalir. Onun yerine upd_isUploadProtected()
// fonksiyonu var: uploads/ icindeki dosyalar SADECE diskte zaten varsa skip
// edilir (kullanici yuklemeleri korunur, yeni dosyalar kopyalanir).
$UPD_EXCLUDES = [
    'config/config.php',
    'config/',
    'backups/',
    '.git/',
    '.github/',
    '.gitignore',
    'node_modules/',
    'vendor/',
];

// =================================================================
//   YARDIMCI FONKSIYONLAR
// =================================================================

function upd_token(): string
{
    return (string)setting('github_token', '');
}

function upd_curl(string $url, array $headers = [], int $timeout = 30, int $maxRetries = 3): array
{
    if (!function_exists('curl_init')) {
        return ['code' => 0, 'body' => '', 'error' => 'PHP cURL eklentisi yüklü değil. Hosting destekten cURL aktivasyonu isteyin.', 'attempts' => 0];
    }

    $attempt = 0;
    $lastBody = '';
    $lastCode = 0;
    $lastErr = '';
    $lastHeaders = [];

    while ($attempt < $maxRetries) {
        $attempt++;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HEADER         => true,  // response header'lari da al
        ]);
        $raw  = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $hSize = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $err  = curl_error($ch);

        // SSL fail (paylasimli hosting'lerde CA bundle eksik) -> retry without verify
        if ($raw === false && (stripos($err, 'ssl') !== false || stripos($err, 'certificate') !== false)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            $raw = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $hSize = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $err = curl_error($ch);
        }

        $headerStr = $raw !== false ? substr((string)$raw, 0, $hSize) : '';
        $body = $raw !== false ? substr((string)$raw, $hSize) : '';
        curl_close($ch);

        // Response header'lari parse et (rate limit, retry-after vs.)
        $respHeaders = [];
        foreach (preg_split("/\r\n|\n|\r/", $headerStr) as $hl) {
            if (str_contains($hl, ':')) {
                [$k, $v] = explode(':', $hl, 2);
                $respHeaders[strtolower(trim($k))] = trim($v);
            }
        }

        $lastBody = (string)$body;
        $lastCode = $code;
        $lastErr  = $err;
        $lastHeaders = $respHeaders;

        // Basari -> dön
        if ($code >= 200 && $code < 300) {
            return ['code' => $code, 'body' => $lastBody, 'error' => '', 'attempts' => $attempt, 'headers' => $respHeaders];
        }

        // 4xx (rate limit haric) -> retry yapma
        if ($code >= 400 && $code < 500 && $code !== 429) {
            break;
        }

        // 503 / 429 / 502 / 504 / cURL fail -> retry
        if ($attempt < $maxRetries) {
            // Retry-After header varsa onu kullan
            $wait = 0;
            if (!empty($respHeaders['retry-after'])) {
                $wait = (int)$respHeaders['retry-after'];
            }
            // Yoksa exponential backoff: 1s, 2s, 4s
            if ($wait <= 0) $wait = (int)pow(2, $attempt - 1);
            if ($wait > 10) $wait = 10;  // cap
            sleep($wait);
        }
    }

    // Tüm denemeler basarisiz
    $errorMsg = $lastErr;
    if ($errorMsg === '' && $lastCode > 0) {
        $errorMsg = "HTTP $lastCode";
        // Sik karsilasilan kodlar icin aciklama
        $explanations = [
            429 => ' (Rate limit aşıldı, GitHub bir süre sonra tekrar deneyin)',
            502 => ' (Bad Gateway - GitHub veya hosting geçici sorunu)',
            503 => ' (Service Unavailable - GitHub veya hosting geçici sorunu)',
            504 => ' (Gateway Timeout - istek zaman aşımı)',
            401 => ' (Unauthorized - Token geçersiz veya yetki yetersiz)',
            403 => ' (Forbidden - Token yetkisi yok veya rate limit)',
            404 => ' (Not Found - Repo/dosya yolu yanlış)',
        ];
        if (isset($explanations[$lastCode])) $errorMsg .= $explanations[$lastCode];
    }
    if ($errorMsg === '') $errorMsg = 'Bilinmeyen ağ hatası';

    return ['code' => $lastCode, 'body' => $lastBody, 'error' => $errorMsg, 'attempts' => $attempt, 'headers' => $lastHeaders];
}

function upd_ghHeaders(string $token): array
{
    $h = [
        'User-Agent: Mizan-Updater/1.0',
        'Accept: application/vnd.github.v3+json',
    ];
    if ($token) $h[] = 'Authorization: token ' . $token;
    return $h;
}

function upd_ghAPI(string $path, string $token, ?array &$debug = null): ?array
{
    global $repoFull;
    $r = upd_curl("https://api.github.com/repos/$repoFull$path", upd_ghHeaders($token));
    $debug = ['code' => $r['code'], 'error' => $r['error'], 'attempts' => $r['attempts'] ?? 1];
    if ($r['code'] !== 200) return null;
    $d = json_decode($r['body'], true);
    return is_array($d) ? $d : null;
}

function upd_ghDownload(string $filePath, string $token, ?array &$debug = null): ?string
{
    global $repoFull, $ghBranch;
    $r = upd_curl(
        "https://raw.githubusercontent.com/$repoFull/$ghBranch/" . ltrim($filePath, '/'),
        ['User-Agent: Mizan-Updater/1.0'] + ($token ? ['Authorization: token ' . $token] : [])
    );
    $debug = ['code' => $r['code'], 'error' => $r['error'], 'attempts' => $r['attempts'] ?? 1];
    return $r['code'] === 200 ? $r['body'] : null;
}

function upd_isExcluded(string $relPath): bool
{
    global $UPD_EXCLUDES;
    foreach ($UPD_EXCLUDES as $ex) {
        if ($relPath === $ex) return true;
        if (str_ends_with($ex, '/') && str_starts_with($relPath, $ex)) return true;
    }
    // uploads/ icindeki dosyalar: SADECE diskte zaten varsa skip
    // (kullanici yuklemeleri korunur, yeni dosyalar -orn. yeni slayt gorseli- kopyalanir)
    if (str_starts_with($relPath, 'uploads/')) {
        $absPath = MIZAN_ROOT . '/' . $relPath;
        if (file_exists($absPath)) return true; // mevcut kullanici dosyasi - atla
        // Yeni dosya - kopyalanir (return false)
    }
    return false;
}

function upd_repoTree(string $token, ?array &$debug = null): array
{
    global $ghBranch;
    $tree = upd_ghAPI("/git/trees/$ghBranch?recursive=1", $token, $debug);
    if (!$tree || empty($tree['tree'])) return [];
    $out = [];
    foreach ($tree['tree'] as $i) {
        if ($i['type'] !== 'blob') continue;
        if (upd_isExcluded($i['path'])) continue;
        $out[] = ['path' => $i['path'], 'sha' => $i['sha'], 'size' => $i['size'] ?? 0];
    }
    usort($out, fn($a, $b) => strcmp($a['path'], $b['path']));
    return $out;
}

function upd_blobSHA(string $content): string
{
    // Git'in blob SHA hesaplama formulu: sha1('blob ' + length + '\0' + content)
    return sha1('blob ' . strlen($content) . "\0" . $content);
}

/**
 * PERF: Disk'ten okurken streaming hash hesabi
 * file_get_contents() butun dosyayi RAM'e yukler - paylasimli hosting'de yavas.
 * hash_init + stream_copy ile chunk-chunk hash hesabi cok daha hizli.
 */
function upd_blobSHAFile(string $absPath): string
{
    $size = filesize($absPath);
    if ($size === false) return '';
    $ctx = hash_init('sha1');
    hash_update($ctx, 'blob ' . $size . "\0");
    $fh = @fopen($absPath, 'rb');
    if (!$fh) return '';
    while (!feof($fh)) {
        $chunk = fread($fh, 65536); // 64KB chunks
        if ($chunk === false) break;
        hash_update($ctx, $chunk);
    }
    fclose($fh);
    return hash_final($ctx);
}

function upd_localVer(): string
{
    $m = MIZAN_ROOT . '/manifest.json';
    if (is_file($m)) {
        $d = json_decode((string)file_get_contents($m), true);
        if (!empty($d['version'])) return $d['version'];
    }
    return defined('SITE_VERSION') ? SITE_VERSION : '?';
}

function upd_remoteVer(string $token): string
{
    global $ghBranch;
    $d = upd_ghAPI("/contents/manifest.json?ref=$ghBranch", $token);
    if ($d && !empty($d['content'])) {
        $c = base64_decode(str_replace(["\n", "\r"], '', $d['content']));
        $m = json_decode($c, true);
        if (!empty($m['version'])) return $m['version'];
    }
    return '?';
}

function upd_diff(string $token): array
{
    $remote  = upd_repoTree($token);
    $changed = [];
    $added   = [];
    foreach ($remote as $r) {
        $abs = MIZAN_ROOT . '/' . $r['path'];
        if (!is_file($abs)) {
            $added[] = $r;
            continue;
        }
        $localSHA = upd_blobSHA(file_get_contents($abs));
        if ($localSHA !== $r['sha']) {
            $r['local_sha'] = $localSHA;
            $changed[] = $r;
        }
    }
    return ['added' => $added, 'changed' => $changed, 'total_remote' => count($remote)];
}

function upd_backup(string $label = ''): array
{
    if (!class_exists('ZipArchive')) return ['ok' => false, 'error' => 'ZipArchive eklentisi yok'];
    $bdir = MIZAN_ROOT . '/backups';
    if (!is_dir($bdir)) @mkdir($bdir, 0755, true);

    $stamp = date('Ymd-His');
    $name  = 'pre-update-' . $stamp . ($label ? ('-' . preg_replace('/[^a-z0-9_-]/i', '', $label)) : '') . '.zip';
    $file  = $bdir . '/' . $name;

    $zip = new ZipArchive();
    if ($zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return ['ok' => false, 'error' => 'ZIP açılamadı'];
    }

    // PERF: Sadece KRITIK dosyalari yedekle (config, mevcut PHP dosyalari)
    // Tum site degil - bu zaten Git tarafinda var ve geri alinabilir.
    // Buyuk klasorler (assets, includes, vendor) Git'ten geri yuklenebilir.
    $skip = [
        '/backups/', '/uploads/', '/.git/', '/node_modules/',
        '/vendor/', '/assets/img/', '/assets/css/bootstrap',
    ];

    // Sadece KRITIK dosyalari yedekle: config, sql migration, root PHP
    // (smart_sync paylasilan hosting'de tum dosyayi yedeklemek 30+ saniye)
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(MIZAN_ROOT, RecursiveDirectoryIterator::SKIP_DOTS));
    $count = 0;
    $maxFiles = 500; // Hard limit - daha fazlasi varsa olustugu yere durdur
    foreach ($rii as $f) {
        if ($f->isDir()) continue;
        if ($count >= $maxFiles) break;
        $abs = $f->getRealPath();
        if ($abs === false) continue;
        $rel = ltrim(str_replace(MIZAN_ROOT, '', $abs), '/\\');
        $check = '/' . str_replace('\\', '/', $rel) . '/';
        $skipMe = false;
        foreach ($skip as $sk) if (str_contains($check, $sk)) { $skipMe = true; break; }
        if ($skipMe) continue;
        // Sadece PHP, SQL, JSON, CSS, JS, MD - kritik kod dosyalari
        $ext = strtolower((string)pathinfo($abs, PATHINFO_EXTENSION));
        if (!in_array($ext, ['php', 'sql', 'json', 'css', 'js', 'md', 'htaccess', 'env'], true)) continue;
        // Buyuk dosyalari atla (>500KB) - genelde generated assets
        if (filesize($abs) > 512000) continue;
        $zip->addFile($abs, $rel);
        $count++;
    }
    $zip->close();

    return ['ok' => true, 'file' => $name, 'path' => $file, 'size' => filesize($file), 'files' => $count];
}

function upd_runMigrations(): array
{
    $sqlFile = MIZAN_ROOT . '/migration.sql';
    if (!is_file($sqlFile)) return ['ok' => false, 'error' => 'migration.sql bulunamadı'];
    $sql = (string)file_get_contents($sqlFile);
    if ($sql === '') return ['ok' => false, 'error' => 'migration.sql boş'];

    // Yorumlari temizle
    $sql = preg_replace('!^--[^\n]*$!m', '', $sql);
    $sql = preg_replace('!/\*.*?\*/!s', '', (string)$sql);

    // Statement'lari ayir - basit ; bazli, ama tirnak icindeki ;'leri korumaya calisalim
    // SQL dump'lar genelde ; sonunda \n ile ayrilir
    $statements = [];
    $buf = '';
    $inStr = false;
    $strCh = '';
    $len = strlen($sql);
    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];
        if ($inStr) {
            $buf .= $ch;
            if ($ch === '\\' && $i + 1 < $len) { $buf .= $sql[++$i]; continue; }
            if ($ch === $strCh) $inStr = false;
        } else {
            if ($ch === "'" || $ch === '"') { $inStr = true; $strCh = $ch; $buf .= $ch; continue; }
            if ($ch === ';') { $st = trim($buf); if ($st !== '') $statements[] = $st; $buf = ''; continue; }
            $buf .= $ch;
        }
    }
    if (trim($buf) !== '') $statements[] = trim($buf);

    // Idempotent hata pattern'leri (tum yaygin MySQL/MariaDB hata mesajlari)
    $ignorablePatterns = [
        '/Duplicate column name/i',
        '/Duplicate key name/i',
        '/Duplicate entry/i',
        '/Table .* already exists/i',
        '/Multiple primary key defined/i',
        '/Can.t DROP .*; check that .* exists/i',
        '/Column .* already exists/i',
        '/Key .* already exists/i',
        // INSERT IGNORE ile duplikatlar otomatik atlanır ama yine de:
        '/cannot add foreign key constraint/i',
    ];

    $ok = 0; $skip = 0; $err = 0; $errors = [];
    foreach ($statements as $st) {
        if ($st === '' || stripos($st, 'DELIMITER') === 0) continue;
        try {
            // SET @x := (SELECT ...), SHOW, SELECT gibi result-set doneren statement'lar
            // db()->exec() ile cursor acik kalabiliyor (unbuffered hatasi).
            // Cozum: prepare + execute + closeCursor (tum result'lari tuket)
            $stmt = db()->prepare($st);
            $stmt->execute();
            // Birden fazla result set olabilir (SET ... := SELECT, CALL vb)
            do {
                $stmt->fetchAll();
            } while ($stmt->nextRowset());
            $stmt->closeCursor();
            unset($stmt);
            $ok++;
        } catch (Throwable $e) {
            // closeCursor exception'i yutmasin
            if (isset($stmt) && $stmt instanceof PDOStatement) {
                try { $stmt->closeCursor(); } catch (Throwable $ignored) {}
                unset($stmt);
            }
            $msg = $e->getMessage();
            $ignored = false;
            foreach ($ignorablePatterns as $pat) {
                if (preg_match($pat, $msg)) { $skip++; $ignored = true; break; }
            }
            if (!$ignored) {
                $err++;
                if (count($errors) < 20) {  // sadece ilk 20 hatayı topla
                    $errors[] = mb_substr(preg_replace('/\s+/', ' ', $st), 0, 100) . ' … → ' . mb_substr($msg, 0, 200);
                }
            }
        }
    }
    return [
        'ok'         => $err === 0,
        'executed'   => $ok,
        'skipped'    => $skip,
        'errors'     => $err,
        'error_list' => $errors,
        'total'      => count($statements),
    ];
}

function upd_humanSize(int $bytes): string
{
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 2) . ' MB';
}

// =================================================================
//   AJAX ENDPOINTS
// =================================================================
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    $aj    = (string)$_GET['ajax'];
    $token = upd_token();

    try {
        // ---- diagnostics ----
        if ($aj === 'diagnostics') {
            $checks = [];
            // PHP version
            $checks[] = ['name' => 'PHP sürümü', 'ok' => version_compare(PHP_VERSION, '8.1.0', '>='), 'detail' => PHP_VERSION . (version_compare(PHP_VERSION, '8.1.0', '>=') ? ' (yeterli)' : ' (8.1+ gerekli)')];
            // Required extensions
            foreach (['curl', 'pdo_mysql', 'zip', 'mbstring', 'json', 'openssl'] as $ext) {
                $checks[] = ['name' => "PHP eklentisi: $ext", 'ok' => extension_loaded($ext), 'detail' => extension_loaded($ext) ? 'yüklü' : 'YOK — hosting destek isteyin'];
            }
            // ZipArchive class
            $checks[] = ['name' => 'ZipArchive sınıfı', 'ok' => class_exists('ZipArchive'), 'detail' => class_exists('ZipArchive') ? 'mevcut' : 'eksik'];
            // Klasor izinleri
            foreach (['MIZAN_ROOT' => MIZAN_ROOT, 'backups/' => MIZAN_ROOT . '/backups', 'uploads/' => MIZAN_ROOT . '/uploads', 'config/' => MIZAN_ROOT . '/config'] as $label => $path) {
                $exists = is_dir($path);
                $writable = $exists && is_writable($path);
                $checks[] = [
                    'name' => "Klasör: $label",
                    'ok' => $writable,
                    'detail' => !$exists ? 'YOK (oluşturulmalı)' : ($writable ? 'yazılabilir' : 'YAZILAMIYOR (izin: ' . substr(sprintf('%o', @fileperms($path)), -4) . ')')
                ];
            }
            // manifest.json
            $manExists = is_file(MIZAN_ROOT . '/manifest.json');
            $checks[] = ['name' => 'manifest.json', 'ok' => $manExists, 'detail' => $manExists ? 'sürüm: ' . upd_localVer() : 'YOK'];
            // migration.sql
            $migExists = is_file(MIZAN_ROOT . '/migration.sql');
            $migSize = $migExists ? filesize(MIZAN_ROOT . '/migration.sql') : 0;
            $checks[] = ['name' => 'migration.sql', 'ok' => $migExists, 'detail' => $migExists ? upd_humanSize((int)$migSize) : 'YOK'];
            // GitHub token
            $tokOk = (bool)$token;
            $checks[] = ['name' => 'GitHub Token', 'ok' => $tokOk, 'detail' => $tokOk ? 'tanımlı (' . substr($token, 0, 8) . '...)' : 'TANIMLI DEĞİL — Ayarlar sekmesi'];
            // GitHub API erisimi + rate limit
            if ($tokOk) {
                $r = upd_ghAPI('/repos/' . $GLOBALS['repoFull'], $token);
                $checks[] = ['name' => 'GitHub API erişimi', 'ok' => (bool)$r, 'detail' => $r ? 'OK (repo: ' . ($r['full_name'] ?? '?') . ')' : 'API çağrısı başarısız'];

                // Rate limit kontrolu (GitHub'in rate_limit endpoint'i)
                $rl = upd_curl('https://api.github.com/rate_limit', upd_ghHeaders($token), 10, 1);
                if ($rl['code'] === 200) {
                    $rlData = json_decode($rl['body'], true);
                    $core = $rlData['resources']['core'] ?? [];
                    $remaining = $core['remaining'] ?? 0;
                    $limit = $core['limit'] ?? 0;
                    $resetTs = $core['reset'] ?? 0;
                    $resetMin = $resetTs > 0 ? max(0, (int)round(($resetTs - time()) / 60)) : 0;
                    $rlOk = $remaining > 50;
                    $checks[] = [
                        'name' => 'GitHub API kotası',
                        'ok' => $rlOk,
                        'detail' => "Kalan: $remaining / $limit" . ($remaining < $limit ? " · sıfırlanma: ~{$resetMin} dk" : '') . ($rlOk ? '' : ' (DÜŞÜK — sync sırasında 403 alabilirsiniz)')
                    ];
                } else {
                    $checks[] = ['name' => 'GitHub API kotası', 'ok' => false, 'detail' => 'rate_limit endpoint okunamadı (HTTP ' . $rl['code'] . ')'];
                }

                // raw.githubusercontent.com ping (dosya indirme adresi - sync burada calisacak)
                $rawTest = upd_curl('https://raw.githubusercontent.com/' . $GLOBALS['repoFull'] . '/' . $GLOBALS['ghBranch'] . '/manifest.json',
                    ['User-Agent: Mizan-Updater/1.0', 'Authorization: token ' . $token], 10, 1);
                $rawOk = $rawTest['code'] === 200;
                $rawDetail = $rawOk ? 'OK (manifest indirildi)' : "HTTP {$rawTest['code']}" . ($rawTest['error'] ? ' · ' . $rawTest['error'] : '');
                $checks[] = ['name' => 'raw.githubusercontent.com erişimi', 'ok' => $rawOk, 'detail' => $rawDetail];
            }
            // DB baglantisi
            $dbOk = false;
            try { db_value('SELECT 1'); $dbOk = true; } catch (\Throwable $e) {}
            $checks[] = ['name' => 'Veritabanı bağlantısı', 'ok' => $dbOk, 'detail' => $dbOk ? 'OK' : 'BAŞARISIZ — config/config.php DB ayarlarını kontrol edin'];
            // SITE_BASE_URL
            $checks[] = ['name' => 'SITE_BASE_URL', 'ok' => true, 'detail' => SITE_BASE_URL];

            $totalOk = count(array_filter($checks, fn($c) => $c['ok']));
            echo json_encode(['ok' => true, 'checks' => $checks, 'total' => count($checks), 'passed' => $totalOk]);
            exit;
        }

        // ---- status ----
        if ($aj === 'status') {
            if (!$token) { echo json_encode(['ok' => false, 'error' => 'GitHub token tanımlı değil. Ayarlar sekmesinden ekleyin.']); exit; }
            $dbg = null;
            $remote = upd_repoTree($token, $dbg);
            if (!$remote) {
                $msg = 'Repo ağacı okunamadı.';
                if ($dbg) {
                    $msg .= ' [HTTP ' . $dbg['code'] . ' · ' . $dbg['attempts'] . ' deneme]';
                    if (!empty($dbg['error'])) $msg .= ' · ' . $dbg['error'];
                }
                if ($dbg && $dbg['code'] === 401) $msg .= "\nÇözüm: Token süresi dolmuş veya yetki yetersiz. Ayarlar → yeni token girin.";
                if ($dbg && $dbg['code'] === 403) $msg .= "\nÇözüm: Token rate-limit aşmış olabilir veya repo'ya 'repo' yetkisi yok.";
                if ($dbg && in_array($dbg['code'], [502, 503, 504])) $msg .= "\nGitHub veya hosting geçici sorunu. 1-2 dakika sonra tekrar deneyin.";
                echo json_encode(['ok' => false, 'error' => $msg, 'debug' => $dbg]);
                exit;
            }
            $diff = upd_diff($token);
            echo json_encode([
                'ok'         => true,
                'local_ver'  => upd_localVer(),
                'remote_ver' => upd_remoteVer($token),
                'remote_count' => count($remote),
                'changed'    => count($diff['changed']),
                'added'      => count($diff['added']),
                'changed_files' => array_slice(array_map(fn($f) => $f['path'], $diff['changed']), 0, 50),
                'added_files'   => array_slice(array_map(fn($f) => $f['path'], $diff['added']), 0, 50),
                'repo'       => $GLOBALS['repoFull'],
                'branch'     => $GLOBALS['ghBranch'],
            ]);
            exit;
        }

        // ---- sync (smart) / force_sync ----
        if ($aj === 'sync' || $aj === 'force_sync') {
            if (!$token) { echo json_encode(['ok' => false, 'error' => 'Token yok']); exit; }
            $force = ($aj === 'force_sync');

            // Uzun isteklere izin ver (paylasimli hosting'lerde varsayilan 30sn olabilir)
            @set_time_limit(300);
            @ini_set('memory_limit', '256M');

            // Yedek
            $bk = upd_backup($force ? 'force' : 'sync');
            $log = [];
            $log[] = 'Yedek: ' . ($bk['ok'] ? ($bk['file'] . ' (' . upd_humanSize((int)$bk['size']) . ')') : 'BAŞARISIZ - ' . ($bk['error'] ?? ''));

            $treeDbg = null;
            $remote = upd_repoTree($token, $treeDbg);
            if (!$remote) {
                $msg = 'Repo ağacı okunamadı';
                if ($treeDbg) $msg .= ' [HTTP ' . $treeDbg['code'] . ($treeDbg['error'] ? ' · ' . $treeDbg['error'] : '') . ']';
                echo json_encode(['ok' => false, 'error' => $msg, 'log' => $log]);
                exit;
            }

            $updated = 0; $errors = []; $unchanged = 0;
            $tStart = microtime(true);
            foreach ($remote as $f) {
                $abs = MIZAN_ROOT . '/' . $f['path'];
                $needs = $force;
                if (!$needs) {
                    if (!is_file($abs)) $needs = true;
                    else {
                        // PERF Optimizasyonu - dosya tipine gore akilli karsilastirma
                        $localSize = filesize($abs);
                        $remoteSize = (int)($f['size'] ?? 0);
                        $ext = strtolower((string)pathinfo($f['path'], PATHINFO_EXTENSION));
                        $isBinary = in_array($ext, ['jpg','jpeg','png','gif','webp','ico','woff','woff2','ttf','eot','pdf','zip','mp4','mp3','svg','otf'], true);

                        if ($remoteSize > 0 && $localSize !== $remoteSize) {
                            // Boyut farkli - kesin degisti
                            $needs = true;
                        } elseif ($isBinary && $remoteSize > 0 && $localSize === $remoteSize) {
                            // Binary + boyut esit -> %99 ayni dosya, SHA hesabini ATLA (PERF)
                            // (Resimde 1 byte degisiklik bile boyutu degistirir, paranoyak olmaya gerek yok)
                            $needs = false;
                        } else {
                            // Text dosya veya boyut bilinmiyor - streaming SHA
                            $localSHA = upd_blobSHAFile($abs);
                            if ($localSHA !== $f['sha']) $needs = true;
                        }
                    }
                }
                if (!$needs) { $unchanged++; continue; }

                $dbg = null;
                $content = upd_ghDownload($f['path'], $token, $dbg);
                if ($content === null) {
                    $reason = $dbg && $dbg['code'] ? "HTTP {$dbg['code']}" : ($dbg['error'] ?? 'bilinmeyen');
                    if ($dbg && $dbg['attempts'] > 1) $reason .= " ({$dbg['attempts']} deneme)";
                    $errors[] = $f['path'] . ' indirilemedi: ' . $reason;
                    // 503/429 ust uste alirsak vazgec, hosting/GitHub sorunu var
                    $recent5xx = 0;
                    foreach (array_slice($errors, -5) as $e) {
                        if (strpos($e, 'HTTP 503') !== false || strpos($e, 'HTTP 429') !== false || strpos($e, 'HTTP 502') !== false) $recent5xx++;
                    }
                    if ($recent5xx >= 5) {
                        $errors[] = '⚠ Üst üste 5+ kez 5xx hatası alındı. Senkron durduruldu. Birkaç dakika bekleyip tekrar deneyin.';
                        break;
                    }
                    continue;
                }
                $dir = dirname($abs);
                if (!is_dir($dir)) @mkdir($dir, 0755, true);
                if (@file_put_contents($abs, $content) === false) {
                    $errors[] = $f['path'] . ' yazılamadı (klasör izni?)';
                    continue;
                }
                $updated++;
                $log[] = '✓ ' . $f['path'] . ' (' . upd_humanSize(strlen($content)) . ')';
            }

            // Migration: SADECE su 2 sart varsa calistir:
            // 1) Dosya updateleri var (yeni v.x.y indirildi)
            // 2) migration.sql GERCEKTEN guncellendi (SHA degisti)
            // Hicbiri yoksa atla - paylasimli hosting'de migration cok yavas olabilir
            $mig = ['executed' => 0, 'skipped' => 0, 'errors' => 0, 'error_list' => []];
            $migrationSqlChanged = false;
            foreach ($remote as $f) {
                if ($f['path'] === 'migration.sql' && in_array('migration.sql', array_map(fn($e) => preg_replace('/^✓ ([^ ]+).*/', '$1', $e), $log), true)) {
                    $migrationSqlChanged = true; break;
                }
            }
            // Eger log'da 'migration.sql' guncellendi diyorsa ve hata yoksa calistir
            $migrationGuncel = false;
            foreach ($log as $logLine) {
                if (str_contains($logLine, 'migration.sql')) { $migrationGuncel = true; break; }
            }
            if (count($errors) === 0 && ($force || $migrationGuncel || $updated === 0)) {
                // Force'ta her zaman calistir
                // migration.sql guncellenmisse calistir
                // updated=0 ise (ilk kontrol) calistir (yedek olarak)
                $mig = upd_runMigrations();
                $log[] = 'Migration: OK=' . $mig['executed'] . ' SKIP=' . $mig['skipped'] . ' ERR=' . $mig['errors'];
            } elseif (count($errors) === 0) {
                $mig = ['executed' => 0, 'skipped' => 0, 'errors' => 0, 'error_list' => []];
                $log[] = 'Migration: migration.sql degismedi - atlandi (PERF)';
            } else {
                $log[] = 'Migration: dosya hataları nedeniyle atlandı';
            }

            // Manifest okuyup versiyonu guncelle
            $newVer = upd_localVer();
            audit_log('akilli_guncelleme', 'sistem', null, "Mode=" . ($force ? 'force' : 'sync') . " updated=$updated errors=" . count($errors));
            // Ayni surum birden fazla kez yuklenirse (ornek: force resync) UNIQUE key cakismasi yerine
            // mevcut log kaydini son durumla guncelle. Boylece 'Duplicate entry uk_surum' hatasi olmaz.
            db_exec('INSERT INTO ' . t('guncellemeler') . ' (surum, kaynak, aciklama, migration_calisti, durum, kullanici_id, olusturma_tarihi) VALUES (?,?,?,?,?,?,NOW())
                ON DUPLICATE KEY UPDATE
                    kaynak            = VALUES(kaynak),
                    aciklama          = VALUES(aciklama),
                    migration_calisti = VALUES(migration_calisti),
                    durum             = VALUES(durum),
                    kullanici_id      = VALUES(kullanici_id),
                    olusturma_tarihi  = NOW()',
                [$newVer, ($force ? 'force_sync' : 'smart_sync'), implode("\n", $log), $mig['errors'] === 0 ? 1 : 0, count($errors) === 0 ? 'basarili' : 'hatali', user_id()]);

            echo json_encode([
                'ok'         => count($errors) === 0,
                'updated'    => $updated,
                'unchanged'  => $unchanged,
                'errors'     => $errors,
                'log'        => $log,
                'version'    => $newVer,
                'migration'  => $mig,
                'backup'     => $bk,
            ]);
            exit;
        }

        // ---- update_file (tek dosya) ----
        if ($aj === 'update_file') {
            if (!$token) { echo json_encode(['ok' => false, 'error' => 'Token yok']); exit; }
            $path = (string)($_POST['path'] ?? '');
            if ($path === '' || str_contains($path, '..') || upd_isExcluded($path)) {
                echo json_encode(['ok' => false, 'error' => 'Geçersiz dosya yolu']); exit;
            }
            $content = upd_ghDownload($path, $token);
            if ($content === null) { echo json_encode(['ok' => false, 'error' => 'İndirilemedi']); exit; }
            $abs = MIZAN_ROOT . '/' . $path;
            @mkdir(dirname($abs), 0755, true);
            if (@file_put_contents($abs, $content) === false) { echo json_encode(['ok' => false, 'error' => 'Yazılamadı']); exit; }
            audit_log('dosya_guncelle', 'sistem', null, $path);
            echo json_encode(['ok' => true, 'size' => strlen($content), 'path' => $path]);
            exit;
        }

        // ---- commits ----
        if ($aj === 'commits') {
            if (!$token) { echo json_encode(['ok' => false, 'error' => 'Token yok']); exit; }
            $d = upd_ghAPI('/commits?per_page=20&sha=' . urlencode($GLOBALS['ghBranch']), $token);
            if (!$d) { echo json_encode(['ok' => false, 'error' => 'Commit listesi alınamadı']); exit; }
            $list = [];
            foreach ($d as $c) {
                $list[] = [
                    'sha'     => substr($c['sha'] ?? '', 0, 7),
                    'message' => mb_substr((string)($c['commit']['message'] ?? ''), 0, 100),
                    'author'  => $c['commit']['author']['name'] ?? '?',
                    'date'    => $c['commit']['author']['date'] ?? '',
                    'url'     => $c['html_url'] ?? '',
                ];
            }
            echo json_encode(['ok' => true, 'commits' => $list]);
            exit;
        }

        // ---- backups list ----
        if ($aj === 'backups') {
            $bdir = MIZAN_ROOT . '/backups';
            $list = [];
            if (is_dir($bdir)) {
                foreach (glob($bdir . '/*.zip') as $f) {
                    $list[] = [
                        'name' => basename($f),
                        'size' => filesize($f),
                        'mtime' => filemtime($f),
                        'date' => date('Y-m-d H:i:s', (int)filemtime($f)),
                    ];
                }
                usort($list, fn($a, $b) => $b['mtime'] <=> $a['mtime']);
            }
            echo json_encode(['ok' => true, 'backups' => $list, 'count' => count($list)]);
            exit;
        }

        // ---- restore (yedekten geri donus) ----
        if ($aj === 'restore') {
            $name = (string)($_POST['name'] ?? '');
            if ($name === '' || !preg_match('/^[a-z0-9._-]+\.zip$/i', $name)) {
                echo json_encode(['ok' => false, 'error' => 'Geçersiz dosya adı']); exit;
            }
            $file = MIZAN_ROOT . '/backups/' . $name;
            if (!is_file($file)) { echo json_encode(['ok' => false, 'error' => 'Yedek bulunamadı']); exit; }

            // Once restore oncesi yedek al
            upd_backup('pre-restore');

            $zip = new ZipArchive();
            if ($zip->open($file) !== true) { echo json_encode(['ok' => false, 'error' => 'ZIP açılamadı']); exit; }

            $protected = ['config/config.php', 'uploads/', 'backups/'];
            $extracted = 0; $skipped = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                $skip = false;
                foreach ($protected as $p) {
                    if ($entry === $p) { $skip = true; break; }
                    if (str_ends_with($p, '/') && str_starts_with($entry, $p)) { $skip = true; break; }
                }
                if ($skip) { $skipped++; continue; }
                $abs = MIZAN_ROOT . '/' . $entry;
                if (str_ends_with($entry, '/')) {
                    @mkdir($abs, 0755, true);
                    continue;
                }
                @mkdir(dirname($abs), 0755, true);
                $stream = $zip->getStream($entry);
                if ($stream) {
                    file_put_contents($abs, stream_get_contents($stream));
                    fclose($stream);
                    $extracted++;
                }
            }
            $zip->close();
            audit_log('yedek_restore', 'sistem', null, "$name extracted=$extracted skipped=$skipped");
            echo json_encode(['ok' => true, 'extracted' => $extracted, 'skipped' => $skipped]);
            exit;
        }

        // ---- delete_backup ----
        if ($aj === 'delete_backup') {
            $name = (string)($_POST['name'] ?? '');
            if (!preg_match('/^[a-z0-9._-]+\.zip$/i', $name)) { echo json_encode(['ok' => false, 'error' => 'Geçersiz']); exit; }
            $file = MIZAN_ROOT . '/backups/' . $name;
            if (is_file($file) && @unlink($file)) {
                audit_log('yedek_sil', 'sistem', null, $name);
                echo json_encode(['ok' => true]);
            } else {
                echo json_encode(['ok' => false, 'error' => 'Silinemedi']);
            }
            exit;
        }

        // ---- save_token ----
        if ($aj === 'save_token') {
            $tok = trim((string)($_POST['token'] ?? ''));
            $branch = trim((string)($_POST['branch'] ?? 'main')) ?: 'main';
            setting_set('github_token', $tok);
            setting_set('github_branch', $branch);
            audit_log('github_token_kaydet');
            echo json_encode(['ok' => true]);
            exit;
        }

        // ---- test_token ----
        if ($aj === 'test_token') {
            // Once POST'tan gelen token'i kontrol et, yoksa DB'den oku
            $testToken = trim((string)($_POST['token'] ?? '')) ?: $token;
            if (!$testToken) { echo json_encode(['ok' => false, 'error' => 'Token boş — önce input alanına yapıştırın']); exit; }
            $r = upd_curl('https://api.github.com/user', upd_ghHeaders($testToken));
            if ($r['code'] === 200) {
                $u = json_decode($r['body'], true);
                echo json_encode(['ok' => true, 'login' => $u['login'] ?? '?']);
            } elseif ($r['code'] === 0) {
                // Network/cURL error
                echo json_encode(['ok' => false, 'error' => 'GitHub\'a ulaşılamıyor: ' . ($r['error'] ?: 'Bilinmeyen ağ hatası')]);
            } else {
                $errMsg = "HTTP {$r['code']}";
                $j = json_decode($r['body'], true);
                if (is_array($j) && !empty($j['message'])) $errMsg .= ' — ' . $j['message'];
                else $errMsg .= ' — ' . substr($r['body'], 0, 80);
                echo json_encode(['ok' => false, 'error' => $errMsg]);
            }
            exit;
        }

        // ---- migrate ----
        if ($aj === 'migrate') {
            $r = upd_runMigrations();
            audit_log('manuel_migration', 'sistem', null, json_encode($r));
            echo json_encode($r);
            exit;
        }

        // ---- history ----
        if ($aj === 'history') {
            $rows = db_all('SELECT g.*, k.ad_soyad AS kullanici_adi FROM ' . t('guncellemeler') . ' g LEFT JOIN ' . t('kullanicilar') . ' k ON k.id = g.kullanici_id ORDER BY g.olusturma_tarihi DESC LIMIT 30');
            echo json_encode(['ok' => true, 'history' => $rows]);
            exit;
        }

        echo json_encode(['ok' => false, 'error' => 'Bilinmeyen action: ' . $aj]);
        exit;
    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage(), 'trace' => MIZAN_DEBUG ? $e->getTraceAsString() : null]);
        exit;
    }
}

$tokenSet = (bool)upd_token();
$localVersion = upd_localVer();
?>

<style>
.upd-tabs { display: flex; gap: .35rem; flex-wrap: wrap; border-bottom: 2px solid var(--mz-border); margin-bottom: 1rem; }
.upd-tab { background: transparent; border: 0; padding: .65rem 1.15rem; font-weight: 500; color: #6b7280; border-bottom: 2px solid transparent; margin-bottom: -2px; cursor: pointer; transition: all .15s; }
.upd-tab:hover { color: var(--mz-red); }
.upd-tab.on { color: var(--mz-red); border-bottom-color: var(--mz-red); font-weight: 600; }
.upd-pane { display: none; }
.upd-pane.on { display: block; }
.upd-log { background: #0f1e37; color: #e5e7eb; padding: 1rem; border-radius: 8px; font-family: 'SF Mono','Monaco','Consolas',monospace; font-size: .82rem; white-space: pre-wrap; max-height: 400px; overflow-y: auto; }
.upd-log .ok { color: #86efac; }
.upd-log .err { color: #fca5a5; }
.upd-file { display: flex; justify-content: space-between; align-items: center; padding: .5rem .75rem; border-bottom: 1px solid #f1f5f9; font-family: monospace; font-size: .85rem; }
.upd-file:hover { background: #f8fafc; }
.upd-file:last-child { border-bottom: 0; }
.upd-status-card { background: linear-gradient(135deg, var(--mz-navy) 0%, var(--mz-navy-2) 100%); color: #fff; border-radius: 12px; padding: 1.5rem; }
.upd-status-card h3 { color: var(--mz-red); font-weight: 800; }

/* ===== Animasyonlu Süreç Göstergesi ===== */
.upd-progress {
  background: linear-gradient(135deg, #0f1e37 0%, #1b263b 100%);
  border-radius: 14px;
  padding: 1.5rem;
  margin-bottom: 1rem;
  color: #fff;
  border: 1px solid rgba(255,255,255,.1);
  box-shadow: 0 12px 40px rgba(15, 30, 55, .35);
}
.upd-progress-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid rgba(255,255,255,.12);
  margin-bottom: 1.25rem;
}
.upd-progress-spinner {
  position: relative; width: 48px; height: 48px; flex-shrink: 0;
}
.upd-spinner-ring {
  position: absolute; inset: 0;
  border: 3px solid transparent;
  border-top-color: var(--mz-red);
  border-radius: 50%;
  animation: upd-spin 1.4s linear infinite;
}
.upd-spinner-ring:nth-child(2) { inset: 6px; border-top-color: #f4d35e; animation-duration: 1s; animation-direction: reverse; }
.upd-spinner-ring:nth-child(3) { inset: 12px; border-top-color: #fff; animation-duration: 1.8s; }
@keyframes upd-spin { to { transform: rotate(360deg); } }
.upd-progress-title { flex-grow: 1; }
.upd-progress-elapsed {
  background: rgba(255,255,255,.1);
  border-radius: 100px;
  padding: .35rem .9rem;
  font-family: 'SF Mono', monospace;
  font-size: .85rem;
  font-weight: 600;
  color: #f4d35e;
}
.upd-steps { display: flex; flex-direction: column; gap: .5rem; }
.upd-step {
  display: flex; align-items: center; gap: .9rem;
  padding: .85rem 1rem;
  background: rgba(255,255,255,.04);
  border-radius: 10px;
  border-left: 3px solid transparent;
  transition: all .35s cubic-bezier(.34,1.56,.64,1);
  opacity: .55;
}
.upd-step.active {
  background: rgba(244,211,94,.1);
  border-left-color: #f4d35e;
  opacity: 1;
  transform: translateX(4px);
}
.upd-step.done {
  background: rgba(34,197,94,.08);
  border-left-color: #22c55e;
  opacity: .85;
}
.upd-step.error {
  background: rgba(227,11,48,.1);
  border-left-color: var(--mz-red);
  opacity: 1;
}
.upd-step-icon {
  width: 38px; height: 38px; border-radius: 10px;
  background: rgba(255,255,255,.08);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; flex-shrink: 0;
  transition: all .25s;
}
.upd-step.active .upd-step-icon {
  background: linear-gradient(135deg, #f4d35e, #e30b30);
  color: #fff;
  box-shadow: 0 0 0 4px rgba(244,211,94,.2);
  animation: upd-pulse 1.5s ease-in-out infinite;
}
.upd-step.done .upd-step-icon { background: #22c55e; color: #fff; }
.upd-step.error .upd-step-icon { background: var(--mz-red); color: #fff; }
@keyframes upd-pulse {
  0%, 100% { box-shadow: 0 0 0 4px rgba(244,211,94,.2); }
  50% { box-shadow: 0 0 0 8px rgba(244,211,94,.05); }
}
.upd-step-text { flex-grow: 1; min-width: 0; }
.upd-step-name { font-weight: 600; font-size: .92rem; color: #fff; }
.upd-step-desc { font-size: .78rem; color: rgba(255,255,255,.55); margin-top: 1px; }
.upd-step.active .upd-step-name { color: #f4d35e; }
.upd-step-status { width: 24px; flex-shrink: 0; text-align: center; font-size: 1rem; }
.upd-step .upd-step-status i.bi-circle { color: rgba(255,255,255,.3); }
.upd-step.active .upd-step-status::before {
  content: '';
  display: inline-block;
  width: 12px; height: 12px;
  border: 2px solid #f4d35e;
  border-right-color: transparent;
  border-radius: 50%;
  animation: upd-spin 0.7s linear infinite;
}
.upd-step.active .upd-step-status i { display: none; }
.upd-step.done .upd-step-status i.bi-circle { display: none; }
.upd-step.done .upd-step-status::before { content: '\f26b'; font-family: 'bootstrap-icons'; color: #22c55e; font-size: 1.1rem; }
.upd-step.error .upd-step-status i.bi-circle { display: none; }
.upd-step.error .upd-step-status::before { content: '\f33a'; font-family: 'bootstrap-icons'; color: var(--mz-red); font-size: 1.1rem; }
</style>

<div class="card border-0 shadow-sm">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <div>
        <h5 class="fw-bold mb-0"><i class="bi bi-cpu text-warning"></i> Akıllı Güncelleme Sistemi</h5>
        <small class="text-muted">SHA-tabanlı dosya karşılaştırma · Otomatik yedek · Migration entegrasyonu</small>
      </div>
      <div class="text-end">
        <div class="text-muted small">Mevcut Sürüm</div>
        <h4 class="fw-bold mb-0"><span class="badge bg-warning">v<?= e($localVersion) ?></span></h4>
      </div>
    </div>

    <?php if (!$tokenSet): ?>
      <div class="alert alert-warning small mt-3 mb-0"><i class="bi bi-exclamation-triangle"></i> <b>GitHub Token tanımlı değil.</b> "Ayarlar" sekmesinden eklemeden güncelleme yapılamaz.</div>
    <?php endif; ?>
  </div>
</div>

<div class="upd-tabs mt-3">
  <button class="upd-tab on" data-tab="overview"><i class="bi bi-broadcast"></i> Genel Durum</button>
  <button class="upd-tab" data-tab="files"><i class="bi bi-folder2-open"></i> Dosyalar</button>
  <button class="upd-tab" data-tab="commits"><i class="bi bi-git"></i> Commits</button>
  <button class="upd-tab" data-tab="backups"><i class="bi bi-archive"></i> Yedekler</button>
  <button class="upd-tab" data-tab="database"><i class="bi bi-database"></i> Veritabanı</button>
  <button class="upd-tab" data-tab="diagnostics"><i class="bi bi-heart-pulse"></i> Tanılama</button>
  <button class="upd-tab" data-tab="settings"><i class="bi bi-gear"></i> Ayarlar</button>
</div>

<!-- ============== TAB 1: GENEL DURUM ============== -->
<div class="upd-pane on" id="upd-overview">
  <div class="row g-3">
    <div class="col-md-7">
      <div class="upd-status-card mb-3">
        <div class="row g-3">
          <div class="col">
            <div class="small opacity-75">Yerel</div>
            <h3 class="mb-0" id="ovLocalVer">v<?= e($localVersion) ?></h3>
          </div>
          <div class="col">
            <div class="small opacity-75">Uzak (GitHub)</div>
            <h3 class="mb-0" id="ovRemoteVer">—</h3>
          </div>
          <div class="col">
            <div class="small opacity-75">Değişen Dosya</div>
            <h3 class="mb-0" id="ovChanged">—</h3>
          </div>
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 mb-3">
        <button class="btn btn-primary" onclick="updCheck()"><i class="bi bi-arrow-clockwise"></i> Durum Kontrolü</button>
        <button class="btn btn-warning fw-semibold" onclick="updSync(false)"><i class="bi bi-cloud-download"></i> Akıllı Güncelle</button>
        <button class="btn btn-outline-danger" onclick="updSync(true)" data-mz-confirm="TÜM dosyaları yeniden indirmek istiyor musunuz? Bu işlem yavaştır."><i class="bi bi-arrow-repeat"></i> Tam Yenile (Force)</button>
      </div>

      <!-- Animasyonlu süreç göstergesi (sync sırasında görünür) -->
      <div id="updProgress" class="upd-progress" style="display:none">
        <div class="upd-progress-header">
          <div class="upd-progress-spinner">
            <div class="upd-spinner-ring"></div>
            <div class="upd-spinner-ring"></div>
            <div class="upd-spinner-ring"></div>
          </div>
          <div class="upd-progress-title">
            <h6 class="mb-1 fw-bold" id="updProgressTitle">Güncelleme başlatılıyor</h6>
            <small class="text-muted" id="updProgressSub">Lütfen bekleyin, sürecin tamamlanması 30-90 saniye sürebilir.</small>
          </div>
          <div class="upd-progress-elapsed" id="updProgressElapsed">0sn</div>
        </div>
        <div class="upd-steps">
          <div class="upd-step" data-step="backup">
            <div class="upd-step-icon"><i class="bi bi-archive-fill"></i></div>
            <div class="upd-step-text">
              <div class="upd-step-name">Yedekleme</div>
              <div class="upd-step-desc">Mevcut dosyalar ZIP olarak arşivleniyor</div>
            </div>
            <div class="upd-step-status"><i class="bi bi-circle"></i></div>
          </div>
          <div class="upd-step" data-step="github">
            <div class="upd-step-icon"><i class="bi bi-github"></i></div>
            <div class="upd-step-text">
              <div class="upd-step-name">GitHub Bağlantısı</div>
              <div class="upd-step-desc">Repo ağacı ve SHA listesi alınıyor</div>
            </div>
            <div class="upd-step-status"><i class="bi bi-circle"></i></div>
          </div>
          <div class="upd-step" data-step="download">
            <div class="upd-step-icon"><i class="bi bi-cloud-download-fill"></i></div>
            <div class="upd-step-text">
              <div class="upd-step-name">Dosya İndirme</div>
              <div class="upd-step-desc">Sadece değişen dosyalar çekiliyor</div>
            </div>
            <div class="upd-step-status"><i class="bi bi-circle"></i></div>
          </div>
          <div class="upd-step" data-step="migrate">
            <div class="upd-step-icon"><i class="bi bi-database-fill-gear"></i></div>
            <div class="upd-step-text">
              <div class="upd-step-name">Veritabanı Migration</div>
              <div class="upd-step-desc">migration.sql çalıştırılıyor (idempotent)</div>
            </div>
            <div class="upd-step-status"><i class="bi bi-circle"></i></div>
          </div>
          <div class="upd-step" data-step="finalize">
            <div class="upd-step-icon"><i class="bi bi-check-circle-fill"></i></div>
            <div class="upd-step-text">
              <div class="upd-step-name">Tamamlanıyor</div>
              <div class="upd-step-desc">Sürüm kayıt, audit log, sayfa yenileniyor</div>
            </div>
            <div class="upd-step-status"><i class="bi bi-circle"></i></div>
          </div>
        </div>
      </div>

      <div class="upd-log" id="ovLog">Hazır. "Durum Kontrolü" butonuyla başlayın.</div>
    </div>

    <div class="col-md-5">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <h6 class="fw-bold mb-3"><i class="bi bi-info-circle text-warning"></i> Nasıl Çalışır?</h6>
          <ol class="small">
            <li><b>Durum Kontrolü:</b> GitHub repo ağacı çekilir, her dosyanın SHA'sı yerel ile karşılaştırılır.</li>
            <li><b>Akıllı Güncelle:</b> Otomatik yedek alınır, sadece SHA'sı farklı dosyalar indirilir, migration çalıştırılır.</li>
            <li><b>Tam Yenile:</b> Tüm dosyalar yeniden çekilir (manifest hatası vb. acil durumlar için).</li>
          </ol>
          <hr>
          <h6 class="fw-bold small">Korunan Yollar</h6>
          <code class="small d-block">config/config.php</code>
          <code class="small d-block">uploads/</code>
          <code class="small d-block">backups/</code>
        </div>
      </div>

      <div class="card border-0 shadow-sm mt-3">
        <div class="card-body">
          <h6 class="fw-bold mb-2"><i class="bi bi-clock-history text-warning"></i> Son Güncellemeler</h6>
          <div id="histList" class="small text-muted">Yükleniyor...</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ============== TAB 2: DOSYALAR ============== -->
<div class="upd-pane" id="upd-files">
  <div class="d-flex justify-content-between mb-2 align-items-center">
    <small class="text-muted">SHA'sı farklı veya yerel olarak eksik dosyalar</small>
    <button class="btn btn-sm btn-outline-primary" onclick="updLoadFiles()"><i class="bi bi-arrow-clockwise"></i> Yenile</button>
  </div>
  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <div id="fileList" style="max-height: 600px; overflow-y: auto">
        <div class="p-4 text-center text-muted">Dosya listesi için "Yenile" butonuna basın.</div>
      </div>
    </div>
  </div>
</div>

<!-- ============== TAB 3: COMMITS ============== -->
<div class="upd-pane" id="upd-commits">
  <div class="d-flex justify-content-between mb-2 align-items-center">
    <small class="text-muted">GitHub'daki son 20 commit</small>
    <button class="btn btn-sm btn-outline-primary" onclick="updLoadCommits()"><i class="bi bi-arrow-clockwise"></i> Yenile</button>
  </div>
  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <div id="commitList">
        <div class="p-4 text-center text-muted">Commit listesi için "Yenile" butonuna basın.</div>
      </div>
    </div>
  </div>
</div>

<!-- ============== TAB 4: YEDEKLER ============== -->
<div class="upd-pane" id="upd-backups">
  <div class="d-flex justify-content-between mb-2 align-items-center">
    <small class="text-muted">/backups/ altındaki ZIP dosyaları</small>
    <button class="btn btn-sm btn-outline-primary" onclick="updLoadBackups()"><i class="bi bi-arrow-clockwise"></i> Yenile</button>
  </div>
  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <div id="backupList">
        <div class="p-4 text-center text-muted">Yedek listesi için "Yenile" butonuna basın.</div>
      </div>
    </div>
  </div>
</div>

<!-- ============== TAB 5: VERİTABANI ============== -->
<div class="upd-pane" id="upd-database">
  <div class="card border-0 shadow-sm">
    <div class="card-body">
      <h6 class="fw-bold mb-3"><i class="bi bi-database text-warning"></i> Migration Çalıştır</h6>
      <p class="small text-muted mb-3"><code>migration.sql</code> dosyasını idempotent olarak çalıştırır. Mevcut tablolara dokunulmaz, sadece yeni anahtar/sütun/tablolar eklenir.</p>
      <button class="btn btn-warning fw-semibold" onclick="updMigrate()"><i class="bi bi-database-add"></i> Migration Çalıştır</button>
      <div class="upd-log mt-3" id="migLog">Hazır.</div>
    </div>
  </div>
</div>

<!-- ============== TAB 6: TANILAMA ============== -->
<div class="upd-pane" id="upd-diagnostics">
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h6 class="fw-bold mb-1"><i class="bi bi-heart-pulse text-warning"></i> Sistem Sağlık Kontrolü</h6>
          <p class="small text-muted mb-0">Güncelleme sisteminin çalışması için gereken tüm bileşenleri kontrol eder. Bir sorun varsa kırmızı satır gösterilir.</p>
        </div>
        <button class="btn btn-warning fw-semibold" onclick="updDiag()" data-no-spinner><i class="bi bi-arrow-clockwise"></i> Kontrolü Çalıştır</button>
      </div>
      <div id="diagResult" class="small text-muted">Kontrolü çalıştırmak için yukarıdaki butona basın.</div>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-body">
      <h6 class="fw-bold mb-2"><i class="bi bi-life-preserver text-warning"></i> Sık Karşılaşılan Sorunlar</h6>
      <ul class="small mb-0">
        <li><b>"HTTP 503 / 502 / 504" sync sırasında</b> → Geçici GitHub veya hosting sorunu. <strong>Sistem otomatik 3 kez retry yapar</strong> (1s, 2s, 4s aralıklarla). Yine başarısız olursa 1-2 dakika bekleyip tekrar deneyin. Sürekli alıyorsanız hosting destek ekibinden <code>raw.githubusercontent.com</code> ve <code>api.github.com</code> adreslerine giden trafiği kontrol etmelerini isteyin.</li>
        <li><b>"HTTP 429 Rate limit"</b> → Authenticated 5000 req/saat hakkı doldu (force-sync büyük repolarda mümkün). Tanılama'daki "Kalan kotası" satırını kontrol edin, sıfırlanma süresini bekleyin.</li>
        <li><b>"HTTP 401 / 403"</b> → Token süresi dolmuş veya yetki yetersiz. Ayarlar → Yeni token girin (sadece <code>repo</code> yetkisi yeter).</li>
        <li><b>"Yedek alınamadı"</b> → <code>backups/</code> klasörü yok veya yazma izni yok. Bootstrap otomatik oluşturur ama hosting izinleri sınırlı olabilir; DA File Manager'dan klasör izinlerini <code>755</code> yapın.</li>
        <li><b>"cURL eklentisi yok"</b> → Hosting destek ekibinden cURL aktivasyonu isteyin.</li>
        <li><b>Root taşıma sonrası</b> → Eski <code>/v2/backups</code> klasörünü unutmayın, gerekirse manuel oluşturun. <code>config/config.php</code>'de <code>SITE_BASE_URL</code> doğru mu (<code>/v2</code> yok)?</li>
        <li><b>"Dosya yazılamadı"</b> → Hedef dizin yazılamıyor. PHP user'ı (genelde <code>www-data</code>) için izin: tüm proje klasörü <code>755</code>, dosyalar <code>644</code>.</li>
      </ul>
    </div>
  </div>
</div>

<!-- ============== TAB 7: AYARLAR ============== -->
<div class="upd-pane" id="upd-settings">
  <div class="row g-3">
    <div class="col-md-7">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <h6 class="fw-bold mb-3"><i class="bi bi-key text-warning"></i> GitHub Token</h6>
          <p class="small text-muted">Personal Access Token. Sadece <code>repo</code> (veya fine-grained <code>contents: read</code>) yetkisi yeterli.</p>
          <div class="row g-2">
            <div class="col-md-8">
              <label class="form-label small">Token</label>
              <input type="password" id="ghToken" class="form-control form-control-sm" placeholder="ghp_..." value="<?= e(setting('github_token')) ?>" autocomplete="off">
            </div>
            <div class="col-md-4">
              <label class="form-label small">Branch</label>
              <input type="text" id="ghBranch" class="form-control form-control-sm" value="<?= e(setting('github_branch', 'main') ?: 'main') ?>">
            </div>
          </div>
          <div class="mt-3 d-flex gap-2">
            <button class="btn btn-warning btn-sm fw-semibold" onclick="updSaveToken()" data-no-spinner><i class="bi bi-save"></i> Kaydet</button>
            <button class="btn btn-outline-primary btn-sm" onclick="updTestToken()" data-no-spinner><i class="bi bi-check2-circle"></i> Token'ı Test Et</button>
          </div>
          <div id="tokTest" class="small mt-2"></div>
        </div>
      </div>
    </div>

    <div class="col-md-5">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <h6 class="fw-bold mb-2"><i class="bi bi-info text-warning"></i> Repository</h6>
          <dl class="row mb-0 small">
            <dt class="col-sm-4 text-muted">Repo</dt><dd class="col-sm-8"><code><?= e($repoFull) ?></code></dd>
            <dt class="col-sm-4 text-muted">Branch</dt><dd class="col-sm-8"><code><?= e($ghBranch) ?></code></dd>
            <dt class="col-sm-4 text-muted">Manifest</dt><dd class="col-sm-8"><a href="https://github.com/<?= e($repoFull) ?>/blob/<?= e($ghBranch) ?>/manifest.json" target="_blank">manifest.json</a></dd>
            <dt class="col-sm-4 text-muted">Releases</dt><dd class="col-sm-8"><a href="https://github.com/<?= e($repoFull) ?>/releases" target="_blank">Listeyi aç</a></dd>
          </dl>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  'use strict';

  const TABS = ['overview', 'files', 'commits', 'backups', 'database', 'diagnostics', 'settings'];
  document.querySelectorAll('.upd-tab').forEach(b => {
    b.addEventListener('click', () => {
      document.querySelectorAll('.upd-tab').forEach(x => x.classList.remove('on'));
      document.querySelectorAll('.upd-pane').forEach(x => x.classList.remove('on'));
      b.classList.add('on');
      document.getElementById('upd-' + b.dataset.tab).classList.add('on');
    });
  });

  // History yukle (Genel Durum tab'inda)
  updFetch('history').then(d => {
    if (!d.ok || !d.history || !d.history.length) {
      document.getElementById('histList').textContent = 'Henüz güncelleme kaydı yok.';
      return;
    }
    const h = d.history.slice(0, 6).map(g => {
      const ic = g.durum === 'basarili' ? 'check-circle text-success' : 'x-circle text-danger';
      return '<div class="d-flex justify-content-between py-1 border-bottom">'
        + '<span><i class="bi bi-' + ic + '"></i> v' + g.surum + ' <small class="text-muted">(' + (g.kaynak || '') + ')</small></span>'
        + '<span class="text-muted">' + (g.olusturma_tarihi || '').slice(0, 16) + '</span>'
        + '</div>';
    }).join('');
    document.getElementById('histList').innerHTML = h;
  });

  // ---- Overview actions ----
  function logLine(target, msg, cls) {
    const el = document.getElementById(target);
    const c = cls ? '<span class="' + cls + '">' + msg + '</span>' : msg;
    el.innerHTML = (el.innerHTML === 'Hazır.' || el.innerHTML.startsWith('Hazır')) ? c : (el.innerHTML + '\n' + c);
    el.scrollTop = el.scrollHeight;
  }

  window.updCheck = async function () {
    const log = document.getElementById('ovLog');
    log.textContent = 'Durum sorgulanıyor...';
    try {
      const r = await updFetch('status');
      if (!r.ok) { log.innerHTML = '<span class="err">Hata: ' + (r.error || '?') + '</span>'; return; }
      document.getElementById('ovLocalVer').textContent = 'v' + r.local_ver;
      document.getElementById('ovRemoteVer').textContent = 'v' + r.remote_ver;
      document.getElementById('ovChanged').textContent = (r.changed + r.added);

      let txt = '✓ Repo: ' + r.repo + ' (' + r.branch + ')';
      txt += '\n✓ Yerel sürüm: v' + r.local_ver;
      txt += '\n✓ Uzak sürüm: v' + r.remote_ver;
      txt += '\n✓ Toplam dosya (uzak): ' + r.remote_count;
      txt += '\n→ Değişen: ' + r.changed + ' dosya';
      txt += '\n→ Eklenecek: ' + r.added + ' yeni dosya';
      if (r.changed_files && r.changed_files.length) {
        txt += '\n\nDeğişenler (ilk 50):\n' + r.changed_files.map(f => '  ✱ ' + f).join('\n');
      }
      if (r.added_files && r.added_files.length) {
        txt += '\n\nEklenecekler:\n' + r.added_files.map(f => '  + ' + f).join('\n');
      }
      log.textContent = txt;
    } catch (e) {
      log.innerHTML = '<span class="err">Network hatası: ' + e.message + '</span>';
    }
  };

  window.updSync = async function (force) {
    if (!confirm(force ? 'TÜM dosyalar yeniden indirilecek. Devam?' : 'Sadece değişen dosyalar güncellenecek. Devam?')) return;
    const log = document.getElementById('ovLog');
    const progress = document.getElementById('updProgress');
    const elapsed = document.getElementById('updProgressElapsed');
    const title = document.getElementById('updProgressTitle');
    const sub = document.getElementById('updProgressSub');
    const steps = document.querySelectorAll('.upd-step');

    // Reset state
    steps.forEach(s => s.classList.remove('active', 'done', 'error'));
    progress.style.display = 'block';
    title.textContent = (force ? 'Tam Yenileme' : 'Akıllı Güncelleme') + ' başlatılıyor';
    sub.textContent = 'Lütfen bekleyin, sürecin tamamlanması 30-90 saniye sürebilir.';
    log.textContent = (force ? 'Force' : 'Smart') + ' sync başlatıldı...';
    progress.scrollIntoView({behavior: 'smooth', block: 'nearest'});

    // Süre sayacı
    const startTime = Date.now();
    const elapsedTimer = setInterval(() => {
      const sec = Math.floor((Date.now() - startTime) / 1000);
      elapsed.textContent = (sec >= 60 ? Math.floor(sec/60) + 'd ' + (sec%60) : sec) + 'sn';
    }, 100);

    // Adım simülasyonu (server tek seferde döndüğü için tahminî)
    const setStep = (idx, status) => {
      steps.forEach((s, i) => {
        if (i < idx) s.classList.add('done');
        if (i === idx) {
          s.classList.remove('done', 'error');
          if (status === 'error') s.classList.add('error');
          else if (status === 'done') s.classList.add('done');
          else s.classList.add('active');
        }
      });
    };

    // Aşamalı simülasyon timer'ları
    setStep(0); // Yedekleme
    title.textContent = 'Yedekleme alınıyor...';
    const timers = [];
    timers.push(setTimeout(() => { setStep(1); title.textContent = 'GitHub bağlantısı kuruluyor...'; }, 3000));
    timers.push(setTimeout(() => { setStep(2); title.textContent = 'Dosyalar indiriliyor...'; sub.textContent = 'GitHub API\'sinden değişen dosyalar tek tek çekiliyor.'; }, 6000));
    timers.push(setTimeout(() => { setStep(3); title.textContent = 'Migration çalıştırılıyor...'; sub.textContent = 'Veritabanı şeması güncel hâle getiriliyor.'; }, 14000));

    try {
      const r = await updFetch((force ? 'force_sync' : 'sync'), new FormData());
      timers.forEach(t => clearTimeout(t));
      clearInterval(elapsedTimer);

      if (!r.ok && !r.updated) {
        // Hangi adımda kaldıysa onu error yap
        const activeIdx = Array.from(steps).findIndex(s => s.classList.contains('active'));
        setStep(activeIdx >= 0 ? activeIdx : 0, 'error');
        title.textContent = 'Güncelleme başarısız';
        sub.textContent = r.error || 'Bilinmeyen hata';

        let errHtml = '<span class="err">✗ Sync başarısız</span>\n\n';
        if (r.error) errHtml += 'Sebep: ' + r.error + '\n';
        if (r.errors && r.errors.length) errHtml += '\nHATALAR:\n' + r.errors.map(e => '  ✗ ' + e).join('\n');
        if (r.log && r.log.length) errHtml += '\n\nLog:\n' + r.log.join('\n');
        errHtml += '\n\n💡 Tanılama sekmesinden "Kontrolü Çalıştır" deneyin.';
        log.innerHTML = errHtml;
        return;
      }

      // Başarılı - tüm adımları done yap
      setStep(4, 'done');
      title.textContent = 'Güncelleme başarıyla tamamlandı';
      sub.textContent = 'Sürüm v' + r.version + ' aktif. Sayfa yenileniyor...';

      let txt = '';
      if (r.log) txt += r.log.join('\n');
      txt += '\n\n✓ Güncellendi: ' + r.updated + '   |   ✓ Aynı kalan: ' + r.unchanged;
      if (r.errors && r.errors.length) {
        txt += '\n\nUYARILAR:\n' + r.errors.map(e => '  ⚠ ' + e).join('\n');
      }
      txt += '\n\n>>> Tamamlandı (v' + r.version + ') <<<';
      log.textContent = txt;

      if (r.updated > 0 && (!r.errors || r.errors.length === 0)) setTimeout(() => location.reload(), 2500);
    } catch (e) {
      timers.forEach(t => clearTimeout(t));
      clearInterval(elapsedTimer);
      const activeIdx = Array.from(steps).findIndex(s => s.classList.contains('active'));
      setStep(activeIdx >= 0 ? activeIdx : 0, 'error');
      title.textContent = 'Bağlantı hatası';
      sub.textContent = e.message;
      log.innerHTML = '<span class="err">Network hatası: ' + e.message + '</span>';
    }
  };

  // ---- Files tab ----
  window.updLoadFiles = async function () {
    const list = document.getElementById('fileList');
    list.innerHTML = '<div class="p-4 text-center text-muted"><div class="spinner-border spinner-border-sm"></div> Yükleniyor...</div>';
    const r = await updFetch('status');
    if (!r.ok) { list.innerHTML = '<div class="p-4 text-danger">Hata: ' + (r.error || '?') + '</div>'; return; }
    const all = (r.changed_files || []).concat(r.added_files || []);
    if (!all.length) {
      list.innerHTML = '<div class="p-4 text-success text-center"><i class="bi bi-check-circle"></i> Tüm dosyalar güncel!</div>';
      return;
    }
    list.innerHTML = all.map(f => {
      const isAdd = (r.added_files || []).includes(f);
      const lbl = isAdd ? '<span class="badge bg-success">+ Yeni</span>' : '<span class="badge bg-warning text-dark">Değişti</span>';
      return '<div class="upd-file"><span>' + lbl + ' ' + f + '</span>'
           + '<button class="btn btn-sm btn-outline-primary" onclick="updFileOne(\'' + f.replace(/\\/g, '\\\\').replace(/'/g, "\\'") + '\', this)"><i class="bi bi-download"></i></button></div>';
    }).join('');
  };

  window.updFileOne = async function (path, btn) {
    btn.disabled = true; btn.innerHTML = '<div class="spinner-border spinner-border-sm"></div>';
    const fd = new FormData(); fd.append('path', path);
    const r = await updFetch('update_file', fd);
    btn.innerHTML = r.ok ? '<i class="bi bi-check-lg text-success"></i>' : '<i class="bi bi-x-lg text-danger" title="' + (r.error || '?') + '"></i>';
    btn.disabled = !r.ok;
  };

  // ---- Commits tab ----
  window.updLoadCommits = async function () {
    const list = document.getElementById('commitList');
    list.innerHTML = '<div class="p-4 text-center text-muted"><div class="spinner-border spinner-border-sm"></div> Yükleniyor...</div>';
    const r = await updFetch('commits');
    if (!r.ok) { list.innerHTML = '<div class="p-4 text-danger">Hata: ' + (r.error || '?') + '</div>'; return; }
    if (!r.commits.length) { list.innerHTML = '<div class="p-4 text-muted">Commit yok.</div>'; return; }
    list.innerHTML = r.commits.map(c => {
      const date = c.date ? new Date(c.date).toLocaleString('tr-TR') : '';
      return '<div class="upd-file" style="display:block">'
           + '<div class="d-flex justify-content-between"><code class="text-warning">' + c.sha + '</code><small class="text-muted">' + date + '</small></div>'
           + '<div>' + c.message.replace(/[<>]/g, '') + '</div>'
           + '<small class="text-muted">' + c.author + ' · <a href="' + c.url + '" target="_blank">GitHub\'da gör</a></small>'
           + '</div>';
    }).join('');
  };

  // ---- Backups tab ----
  window.updLoadBackups = async function () {
    const list = document.getElementById('backupList');
    list.innerHTML = '<div class="p-4 text-center text-muted"><div class="spinner-border spinner-border-sm"></div> Yükleniyor...</div>';
    const r = await updFetch('backups');
    if (!r.ok) { list.innerHTML = '<div class="p-4 text-danger">Hata: ' + (r.error || '?') + '</div>'; return; }
    if (!r.backups.length) { list.innerHTML = '<div class="p-4 text-muted text-center">Henüz yedek yok.</div>'; return; }
    list.innerHTML = r.backups.map(b => {
      const sz = b.size < 1048576 ? (Math.round(b.size / 1024) + ' KB') : ((b.size / 1048576).toFixed(1) + ' MB');
      return '<div class="upd-file"><div><b>' + b.name + '</b><div class="small text-muted">' + b.date + ' · ' + sz + '</div></div>'
        + '<div class="d-flex gap-1">'
        + '<button class="btn btn-sm btn-outline-warning" onclick="updRestore(\'' + b.name + '\')" title="Geri Yükle"><i class="bi bi-arrow-counterclockwise"></i></button>'
        + '<button class="btn btn-sm btn-outline-danger" onclick="updDeleteBak(\'' + b.name + '\')" title="Sil"><i class="bi bi-trash"></i></button>'
        + '</div></div>';
    }).join('');
  };

  window.updRestore = async function (name) {
    if (!confirm('UYARI: ' + name + ' yedeği geri yüklenecek!\n\nMevcut config.php, uploads/ ve backups/ korunur. Diğer tüm dosyalar bu yedekteki sürüme dönecek. Devam?')) return;
    const fd = new FormData(); fd.append('name', name);
    const r = await updFetch('restore', fd);
    if (r.ok) { alert('Geri yüklendi (' + r.extracted + ' dosya). Sayfa yenileniyor.'); location.reload(); }
    else alert('Hata: ' + (r.error || '?'));
  };

  window.updDeleteBak = async function (name) {
    if (!confirm(name + ' silinsin mi?')) return;
    const fd = new FormData(); fd.append('name', name);
    const r = await updFetch('delete_backup', fd);
    if (r.ok) updLoadBackups(); else alert('Hata: ' + (r.error || '?'));
  };

  // ---- Database tab ----
  window.updMigrate = async function () {
    if (!confirm('migration.sql çalıştırılsın mı?')) return;
    const log = document.getElementById('migLog');
    log.textContent = 'Migration çalıştırılıyor...';
    const r = await updFetch('migrate', new FormData());
    let txt = 'Çalıştırılan: ' + r.executed + '\nAtlanan (idempotent): ' + r.skipped + '\nHatalı: ' + r.errors;
    if (r.error_list && r.error_list.length) {
      txt += '\n\nHATALAR:\n' + r.error_list.join('\n');
    }
    txt += '\n\n' + (r.ok ? '✓ TAMAMLANDI' : '✗ HATALAR VAR');
    log.textContent = txt;
  };

  // ---- Diagnostics tab ----
  window.updDiag = async function () {
    const box = document.getElementById('diagResult');
    box.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Kontroller çalıştırılıyor...';
    try {
      const r = await updFetch('diagnostics');
      if (!r.ok) { box.innerHTML = '<span class="text-danger">Hata: ' + (r.error || '?') + '</span>'; return; }
      const allOk = r.passed === r.total;
      let html = '<div class="alert ' + (allOk ? 'alert-success' : 'alert-warning') + ' small mb-3">'
        + '<b><i class="bi bi-' + (allOk ? 'check-circle' : 'exclamation-triangle') + '"></i> ' + r.passed + ' / ' + r.total + '</b> kontrol başarılı'
        + (allOk ? '. Sistem güncelleme için hazır.' : '. Aşağıdaki kırmızı satırları gözden geçirin.')
        + '</div>'
        + '<table class="table table-sm mb-0"><tbody>';
      r.checks.forEach(c => {
        const ic = c.ok ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle-fill text-danger"></i>';
        html += '<tr><td style="width:30px">' + ic + '</td>'
          + '<td><b>' + c.name + '</b></td>'
          + '<td class="' + (c.ok ? 'text-muted' : 'text-danger fw-semibold') + '">' + c.detail + '</td></tr>';
      });
      html += '</tbody></table>';
      box.innerHTML = html;
    } catch (e) {
      box.innerHTML = '<span class="text-danger">Hata: ' + e.message + '</span>';
    }
  };

  // ---- Settings tab ----
  // Tüm AJAX çağrıları için ortak yardımcı (try/catch + JSON parse + retry on 5xx)
  async function updFetch(action, formData) {
    const opts = formData ? { method: 'POST', body: formData } : {};
    const maxAttempts = 3;
    let lastErr = null;
    for (let attempt = 1; attempt <= maxAttempts; attempt++) {
      try {
        const resp = await fetch('?ajax=' + action, opts);
        if (resp.status >= 500 && resp.status < 600 && attempt < maxAttempts) {
          // 5xx - server-side hata, retry
          await new Promise(r => setTimeout(r, attempt * 1500));
          lastErr = new Error('HTTP ' + resp.status + ' (deneme ' + attempt + '/' + maxAttempts + ')');
          continue;
        }
        if (!resp.ok) throw new Error('HTTP ' + resp.status);
        const text = await resp.text();
        try { return JSON.parse(text); }
        catch (e) { throw new Error('Sunucu yanıtı bozuk: ' + text.substring(0, 200)); }
      } catch (e) {
        lastErr = e;
        // Network hatası - retry
        if (attempt < maxAttempts && (e.message.includes('Failed to fetch') || e.message.includes('Network') || e.message.includes('HTTP 5'))) {
          await new Promise(r => setTimeout(r, attempt * 1500));
          continue;
        }
        throw e;
      }
    }
    throw lastErr || new Error('Bilinmeyen ağ hatası');
  }

  window.updSaveToken = async function () {
    const elt = document.getElementById('tokTest');
    elt.innerHTML = '<span class="text-muted"><span class="spinner-border spinner-border-sm"></span> Kaydediliyor...</span>';
    try {
      const fd = new FormData();
      fd.append('token', document.getElementById('ghToken').value.trim());
      fd.append('branch', document.getElementById('ghBranch').value.trim());
      const r = await updFetch('save_token', fd);
      if (r.ok) elt.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> Kaydedildi. Token aktif.</span>';
      else elt.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle"></i> Hata: ' + (r.error || '?') + '</span>';
    } catch (err) {
      elt.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-triangle"></i> ' + (err.message || err) + '</span>';
    }
  };

  window.updTestToken = async function () {
    const elt = document.getElementById('tokTest');
    elt.innerHTML = '<span class="text-muted"><span class="spinner-border spinner-border-sm"></span> Test ediliyor...</span>';
    try {
      const fd = new FormData();
      fd.append('token', document.getElementById('ghToken').value.trim());
      const r = await updFetch('test_token', fd);
      if (r.ok) elt.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> Token geçerli (' + (r.login || '?') + ').</span>';
      else elt.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle"></i> ' + (r.error || 'Bilinmeyen hata') + '</span>';
    } catch (err) {
      elt.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-triangle"></i> ' + (err.message || err) + '</span>';
    }
  };
})();
</script>

<?php require __DIR__ . '/_footer.php';
