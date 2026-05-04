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

require __DIR__ . '/_layout.php';
require __DIR__ . '/_helpers.php';
require_role('superadmin');

// =================================================================
//   KONFIGURASYON
// =================================================================
$manifestPath = MIZAN_ROOT . '/manifest.json';
$manifest = json_decode((string)@file_get_contents($manifestPath), true) ?: [];
$repoFull = $manifest['repo'] ?? 'codegatr/mizansigorta';
$ghBranch = setting('github_branch', 'main') ?: 'main';

// Guncelleme harici dosyalar/klasorler (asla degistirilmez)
$UPD_EXCLUDES = [
    'config/config.php',
    'config/',
    'uploads/',
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

function upd_curl(string $url, array $headers = [], int $timeout = 30): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    return ['code' => $code, 'body' => (string)$body, 'error' => $err];
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

function upd_ghAPI(string $path, string $token): ?array
{
    global $repoFull;
    $r = upd_curl("https://api.github.com/repos/$repoFull$path", upd_ghHeaders($token));
    if ($r['code'] !== 200) return null;
    $d = json_decode($r['body'], true);
    return is_array($d) ? $d : null;
}

function upd_ghDownload(string $filePath, string $token): ?string
{
    global $repoFull, $ghBranch;
    $r = upd_curl(
        "https://raw.githubusercontent.com/$repoFull/$ghBranch/" . ltrim($filePath, '/'),
        ['User-Agent: Mizan-Updater/1.0'] + ($token ? ['Authorization: token ' . $token] : [])
    );
    return $r['code'] === 200 ? $r['body'] : null;
}

function upd_isExcluded(string $relPath): bool
{
    global $UPD_EXCLUDES;
    foreach ($UPD_EXCLUDES as $ex) {
        if ($relPath === $ex) return true;
        if (str_ends_with($ex, '/') && str_starts_with($relPath, $ex)) return true;
    }
    return false;
}

function upd_repoTree(string $token): array
{
    global $ghBranch;
    $tree = upd_ghAPI("/git/trees/$ghBranch?recursive=1", $token);
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
    $skip = ['/backups/', '/uploads/', '/.git/', '/node_modules/'];
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(MIZAN_ROOT, RecursiveDirectoryIterator::SKIP_DOTS));
    $count = 0;
    foreach ($rii as $f) {
        if ($f->isDir()) continue;
        $abs = $f->getRealPath();
        if ($abs === false) continue;
        $rel = ltrim(str_replace(MIZAN_ROOT, '', $abs), '/\\');
        $check = '/' . str_replace('\\', '/', $rel) . '/';
        $skipMe = false;
        foreach ($skip as $sk) if (str_contains($check, $sk)) { $skipMe = true; break; }
        if (!$skipMe) {
            $zip->addFile($abs, $rel);
            $count++;
        }
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
            db()->exec($st);
            $ok++;
        } catch (Throwable $e) {
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
        // ---- status ----
        if ($aj === 'status') {
            if (!$token) { echo json_encode(['ok' => false, 'error' => 'GitHub token tanımlı değil. Ayarlar sekmesinden ekleyin.']); exit; }
            $remote = upd_repoTree($token);
            if (!$remote) { echo json_encode(['ok' => false, 'error' => 'Repo ağacı okunamadı. Token yetkisini kontrol edin.']); exit; }
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

            // Yedek
            $bk = upd_backup($force ? 'force' : 'sync');
            $log = [];
            $log[] = 'Yedek: ' . ($bk['ok'] ? ($bk['file'] . ' (' . upd_humanSize((int)$bk['size']) . ')') : 'BAŞARISIZ - ' . ($bk['error'] ?? ''));

            $remote = upd_repoTree($token);
            $updated = 0; $errors = []; $unchanged = 0;
            foreach ($remote as $f) {
                $abs = MIZAN_ROOT . '/' . $f['path'];
                $needs = $force;
                if (!$needs) {
                    if (!is_file($abs)) $needs = true;
                    else {
                        $localSHA = upd_blobSHA(file_get_contents($abs));
                        if ($localSHA !== $f['sha']) $needs = true;
                    }
                }
                if (!$needs) { $unchanged++; continue; }

                $content = upd_ghDownload($f['path'], $token);
                if ($content === null) {
                    $errors[] = $f['path'] . ' indirilemedi';
                    continue;
                }
                $dir = dirname($abs);
                if (!is_dir($dir)) @mkdir($dir, 0755, true);
                if (@file_put_contents($abs, $content) === false) {
                    $errors[] = $f['path'] . ' yazılamadı';
                    continue;
                }
                $updated++;
                $log[] = '✓ ' . $f['path'] . ' (' . upd_humanSize(strlen($content)) . ')';
            }

            // Migration
            $mig = upd_runMigrations();
            $log[] = 'Migration: OK=' . $mig['executed'] . ' SKIP=' . $mig['skipped'] . ' ERR=' . $mig['errors'];

            // Manifest okuyup versiyonu guncelle
            $newVer = upd_localVer();
            audit_log('akilli_guncelleme', 'sistem', null, "Mode=" . ($force ? 'force' : 'sync') . " updated=$updated errors=" . count($errors));
            db_exec('INSERT INTO ' . t('guncellemeler') . ' (surum, kaynak, aciklama, migration_calisti, durum, kullanici_id, olusturma_tarihi) VALUES (?,?,?,?,?,?,NOW())',
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
                echo json_encode(['ok' => true, 'login' => $u['login'] ?? '?', 'scopes' => $r['error'] ?? '']);
            } else {
                echo json_encode(['ok' => false, 'error' => "HTTP {$r['code']}: " . substr($r['body'], 0, 80)]);
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

<!-- ============== TAB 6: AYARLAR ============== -->
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

  const TABS = ['overview', 'files', 'commits', 'backups', 'database', 'settings'];
  document.querySelectorAll('.upd-tab').forEach(b => {
    b.addEventListener('click', () => {
      document.querySelectorAll('.upd-tab').forEach(x => x.classList.remove('on'));
      document.querySelectorAll('.upd-pane').forEach(x => x.classList.remove('on'));
      b.classList.add('on');
      document.getElementById('upd-' + b.dataset.tab).classList.add('on');
    });
  });

  // History yukle (Genel Durum tab'inda)
  fetch('?ajax=history').then(r => r.json()).then(d => {
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
      const r = await fetch('?ajax=status').then(x => x.json());
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
    log.textContent = (force ? 'Force' : 'Smart') + ' sync başlatılıyor...';
    try {
      const r = await fetch('?ajax=' + (force ? 'force_sync' : 'sync'), { method: 'POST' }).then(x => x.json());
      if (!r.ok && !r.updated) {
        log.innerHTML = '<span class="err">Hata: ' + (r.error || (r.errors && r.errors.join('\n')) || '?') + '</span>';
        return;
      }
      let txt = '';
      if (r.log) txt += r.log.join('\n');
      txt += '\n\n✓ Güncellendi: ' + r.updated + '   |   ✓ Aynı kalan: ' + r.unchanged;
      if (r.errors && r.errors.length) {
        txt += '\n\nHATALAR:\n' + r.errors.map(e => '  ✗ ' + e).join('\n');
      }
      txt += '\n\n>>> Tamamlandı (v' + r.version + ') <<<';
      log.textContent = txt;
      // Sayfayi 2 saniye sonra yenile (yeni surum gosterimi icin)
      if (r.updated > 0) setTimeout(() => location.reload(), 2500);
    } catch (e) {
      log.innerHTML = '<span class="err">Network hatası: ' + e.message + '</span>';
    }
  };

  // ---- Files tab ----
  window.updLoadFiles = async function () {
    const list = document.getElementById('fileList');
    list.innerHTML = '<div class="p-4 text-center text-muted"><div class="spinner-border spinner-border-sm"></div> Yükleniyor...</div>';
    const r = await fetch('?ajax=status').then(x => x.json());
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
    const r = await fetch('?ajax=update_file', { method: 'POST', body: fd }).then(x => x.json());
    btn.innerHTML = r.ok ? '<i class="bi bi-check-lg text-success"></i>' : '<i class="bi bi-x-lg text-danger" title="' + (r.error || '?') + '"></i>';
    btn.disabled = !r.ok;
  };

  // ---- Commits tab ----
  window.updLoadCommits = async function () {
    const list = document.getElementById('commitList');
    list.innerHTML = '<div class="p-4 text-center text-muted"><div class="spinner-border spinner-border-sm"></div> Yükleniyor...</div>';
    const r = await fetch('?ajax=commits').then(x => x.json());
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
    const r = await fetch('?ajax=backups').then(x => x.json());
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
    const r = await fetch('?ajax=restore', { method: 'POST', body: fd }).then(x => x.json());
    if (r.ok) { alert('Geri yüklendi (' + r.extracted + ' dosya). Sayfa yenileniyor.'); location.reload(); }
    else alert('Hata: ' + (r.error || '?'));
  };

  window.updDeleteBak = async function (name) {
    if (!confirm(name + ' silinsin mi?')) return;
    const fd = new FormData(); fd.append('name', name);
    const r = await fetch('?ajax=delete_backup', { method: 'POST', body: fd }).then(x => x.json());
    if (r.ok) updLoadBackups(); else alert('Hata: ' + (r.error || '?'));
  };

  // ---- Database tab ----
  window.updMigrate = async function () {
    if (!confirm('migration.sql çalıştırılsın mı?')) return;
    const log = document.getElementById('migLog');
    log.textContent = 'Migration çalıştırılıyor...';
    const r = await fetch('?ajax=migrate', { method: 'POST' }).then(x => x.json());
    let txt = 'Çalıştırılan: ' + r.executed + '\nAtlanan (idempotent): ' + r.skipped + '\nHatalı: ' + r.errors;
    if (r.error_list && r.error_list.length) {
      txt += '\n\nHATALAR:\n' + r.error_list.join('\n');
    }
    txt += '\n\n' + (r.ok ? '✓ TAMAMLANDI' : '✗ HATALAR VAR');
    log.textContent = txt;
  };

  // ---- Settings tab ----
  window.updSaveToken = async function () {
    const fd = new FormData();
    fd.append('token', document.getElementById('ghToken').value.trim());
    fd.append('branch', document.getElementById('ghBranch').value.trim());
    const r = await fetch('?ajax=save_token', { method: 'POST', body: fd }).then(x => x.json());
    document.getElementById('tokTest').innerHTML = r.ok ? '<span class="text-success"><i class="bi bi-check-circle"></i> Kaydedildi.</span>' : '<span class="text-danger">Hata: ' + (r.error || '?') + '</span>';
  };

  window.updTestToken = async function () {
    document.getElementById('tokTest').textContent = 'Test ediliyor...';
    const fd = new FormData();
    fd.append('token', document.getElementById('ghToken').value.trim());
    const r = await fetch('?ajax=test_token', { method: 'POST', body: fd }).then(x => x.json());
    if (r.ok) document.getElementById('tokTest').innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> Token geçerli (' + r.login + ').</span>';
    else document.getElementById('tokTest').innerHTML = '<span class="text-danger"><i class="bi bi-x-circle"></i> ' + (r.error || '?') + '</span>';
  };
})();
</script>

<?php require __DIR__ . '/_footer.php';
