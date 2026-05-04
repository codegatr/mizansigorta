<?php
define('MZ_ADMIN', true);
$adminTitle = 'Sistem Güncellemesi';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_helpers.php';
require_role(['superadmin']);

// Manifest oku
$manifestPath = MIZAN_ROOT . '/manifest.json';
$manifest = json_decode((string)@file_get_contents($manifestPath), true) ?: [];
$currentVersion = $manifest['version'] ?? SITE_VERSION;
$repo = $manifest['repo'] ?? 'codegatr/mizansigorta';

/**
 * GitHub API'den en son release'i getir
 */
function gh_fetch_latest(string $repo): array
{
    $token = setting('github_token', '');
    $headers = [
        'User-Agent: Mizan-Sigorta-Updater',
        'Accept: application/vnd.github.v3+json',
    ];
    if ($token) $headers[] = 'Authorization: Bearer ' . $token;

    $ch = curl_init("https://api.github.com/repos/$repo/releases/latest");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($resp === false) throw new RuntimeException('GitHub API erişilemedi: ' . $err);
    if ($code !== 200)   throw new RuntimeException("GitHub API hatası (HTTP $code): " . substr((string)$resp, 0, 200));
    $data = json_decode((string)$resp, true);
    if (!is_array($data)) throw new RuntimeException('GitHub API yanıtı çözülemedi.');
    return $data;
}

/**
 * URL'den release zip indir (zipball_url veya release asset)
 */
function gh_download(string $url, string $dest): void
{
    $token = setting('github_token', '');
    $headers = ['User-Agent: Mizan-Sigorta-Updater'];
    if ($token) $headers[] = 'Authorization: Bearer ' . $token;

    $fp = fopen($dest, 'w');
    if (!$fp) throw new RuntimeException("İndirme dosyası açılamadı: $dest");

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_FILE           => $fp,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 300,
    ]);
    $ok   = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    fclose($fp);

    if (!$ok || $code !== 200) {
        @unlink($dest);
        throw new RuntimeException("İndirme başarısız (HTTP $code): $err");
    }
}

/**
 * ZIP yedegi olustur (uploads ve backups haric)
 */
function backup_current(string $version): string
{
    $bdir = MIZAN_ROOT . '/backups';
    if (!is_dir($bdir)) @mkdir($bdir, 0755, true);
    $bfile = $bdir . '/yedek-' . $version . '-' . date('Ymd-His') . '.zip';

    $zip = new ZipArchive();
    if ($zip->open($bfile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Yedek ZIP olusturulamadi.');
    }
    $skip = ['/backups/', '/uploads/', '/.git/', '/node_modules/'];
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(MIZAN_ROOT, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($rii as $file) {
        if ($file->isDir()) continue;
        $abs = $file->getRealPath();
        if ($abs === false) continue;
        $rel = ltrim(str_replace(MIZAN_ROOT, '', $abs), '/\\');
        $check = '/' . str_replace('\\', '/', $rel) . '/';
        $skipMe = false;
        foreach ($skip as $sk) {
            if (str_contains($check, $sk)) { $skipMe = true; break; }
        }
        if (!$skipMe) $zip->addFile($abs, $rel);
    }
    $zip->close();
    return $bfile;
}

/**
 * Migration SQL'i calistir (idempotent)
 */
function run_migration(): void
{
    $mig = MIZAN_ROOT . '/migration.sql';
    if (!is_file($mig)) return;
    $sql = file_get_contents($mig);
    if (!$sql) return;
    // Comment'leri ve bos satirlari at
    $sql = preg_replace('!^--[^\n]*$!m', '', $sql);
    $sql = preg_replace('!^/\*.*?\*/!ms', '', $sql);
    // ; ile ayir
    $statements = array_filter(array_map('trim', explode(';', (string)$sql)));
    foreach ($statements as $stmt) {
        if ($stmt === '' || preg_match('/^(SET\s+(NAMES|FOREIGN_KEY))/i', $stmt) === 1) {
            try { db()->exec($stmt . ';'); } catch (Throwable $e) {}
            continue;
        }
        try {
            db()->exec($stmt . ';');
        } catch (Throwable $e) {
            // Idempotent kosturma - duplicate key vb. hatalari yumusakca atla
            error_log('Migration uyari: ' . $e->getMessage());
        }
    }
}

/**
 * Indirilen ZIP'i acip dosyalari kopyala (uploads, config korundu)
 */
function apply_zip(string $zipFile): array
{
    $tmp = MIZAN_ROOT . '/backups/_extract_' . bin2hex(random_bytes(4));
    if (!@mkdir($tmp, 0755, true)) throw new RuntimeException('Gecici klasor olusturulamadi.');

    $zip = new ZipArchive();
    if ($zip->open($zipFile) !== true) throw new RuntimeException('Indirilen ZIP acilamadi.');
    $zip->extractTo($tmp);
    $zip->close();

    // GitHub zipball'lari root'ta tek bir klasor icine acar
    $entries = array_values(array_diff(scandir($tmp), ['.', '..']));
    $src = $tmp;
    if (count($entries) === 1 && is_dir($tmp . '/' . $entries[0])) {
        $src = $tmp . '/' . $entries[0];
    }

    $protectedDirs  = ['config', 'uploads', 'backups'];
    $protectedFiles = ['config/config.php', '.htaccess'];
    $copied = 0;

    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($rii as $file) {
        if ($file->isDir()) continue;
        $abs = $file->getRealPath();
        if ($abs === false) continue;
        $rel = ltrim(str_replace($src, '', $abs), '/\\');
        $rel = str_replace('\\', '/', $rel);

        // Korumali klasorler ve dosyalar
        $skip = false;
        foreach ($protectedDirs as $pd) if (str_starts_with($rel, $pd . '/')) $skip = true;
        if (in_array($rel, $protectedFiles, true)) $skip = true;
        if ($skip) continue;

        $target = MIZAN_ROOT . '/' . $rel;
        $tdir = dirname($target);
        if (!is_dir($tdir)) @mkdir($tdir, 0755, true);
        if (@copy($abs, $target)) $copied++;
    }

    // Tmp temizligi
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmp, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($rii as $file) { $file->isDir() ? @rmdir($file->getRealPath()) : @unlink($file->getRealPath()); }
    @rmdir($tmp);

    return ['copied' => $copied];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_assert_post();
    $act = (string)($_POST['action'] ?? '');

    if ($act === 'check') {
        try {
            $rel = gh_fetch_latest($repo);
            $_SESSION['mz_update_release'] = $rel;
            admin_redirect('update.php', 'success', 'Sürüm bilgisi alındı.');
        } catch (Throwable $e) {
            admin_redirect('update.php', 'danger', 'Hata: ' . $e->getMessage());
        }
    }

    if ($act === 'apply') {
        @set_time_limit(600);
        $log = [];
        $eski = $currentVersion;
        $yeni = trim((string)($_POST['version'] ?? ''));
        $url  = trim((string)($_POST['url'] ?? ''));
        if (!$yeni || !$url) admin_redirect('update.php', 'danger', 'Sürüm bilgisi eksik.');
        try {
            $log[] = '[1/5] Mevcut dosyaların yedeği alınıyor...';
            $bfile = backup_current($eski);
            $log[] = ' → Yedek: ' . basename($bfile);

            $log[] = '[2/5] Yeni sürüm indiriliyor...';
            $tmp = MIZAN_ROOT . '/backups/_dl_' . bin2hex(random_bytes(4)) . '.zip';
            gh_download($url, $tmp);
            $log[] = ' → Boyut: ' . number_format(filesize($tmp)/1024, 0) . ' KB';

            $log[] = '[3/5] Dosyalar kopyalanıyor (config & uploads korunuyor)...';
            $info = apply_zip($tmp);
            @unlink($tmp);
            $log[] = ' → ' . $info['copied'] . ' dosya güncellendi';

            $log[] = '[4/5] Veritabanı migration.sql çalıştırılıyor...';
            run_migration();
            $log[] = ' → OK';

            $log[] = '[5/5] manifest.json güncelleniyor...';
            $manifest['version']      = $yeni;
            $manifest['release_date'] = date('Y-m-d');
            file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            $log[] = ' → OK';

            db_exec('INSERT INTO ' . t('guncellemeler') . ' (surum,kaynak,aciklama,migration_calisti,durum,kullanici_id,olusturma_tarihi) VALUES (?,?,?,?,?,?,NOW())',
                [$yeni, 'github', implode("\n", $log), 1, 'basarili', user_id()]);
            audit_log('guncelle', 'sistem', null, "Sürüm $eski → $yeni");

            admin_redirect('update.php', 'success', "Güncelleme başarılı: $eski → $yeni");
        } catch (Throwable $e) {
            $log[] = ' HATA: ' . $e->getMessage();
            db_exec('INSERT INTO ' . t('guncellemeler') . ' (surum,kaynak,aciklama,migration_calisti,durum,hata_mesaji,kullanici_id,olusturma_tarihi) VALUES (?,?,?,?,?,?,?,NOW())',
                [$yeni ?: '?', 'github', implode("\n", $log), 0, 'hatali', $e->getMessage(), user_id()]);
            admin_redirect('update.php', 'danger', 'Güncelleme hatası: ' . $e->getMessage());
        }
    }
}

$release = $_SESSION['mz_update_release'] ?? null;
$gecmis = db_all('SELECT * FROM ' . t('guncellemeler') . ' ORDER BY olusturma_tarihi DESC LIMIT 10');
$tokenSet = (bool)setting('github_token');
?>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-body">
        <div class="d-flex justify-content-between mb-3">
          <div>
            <div class="text-muted small">Mevcut Sürüm</div>
            <h3 class="fw-bold mb-0">v<?= e($currentVersion) ?></h3>
          </div>
          <div class="text-end">
            <div class="text-muted small">GitHub Repository</div>
            <a href="https://github.com/<?= e($repo) ?>/releases" target="_blank" class="text-decoration-none"><i class="bi bi-github"></i> <?= e($repo) ?></a>
          </div>
        </div>
        <hr>

        <?php if (!$tokenSet): ?>
          <div class="alert alert-warning small"><i class="bi bi-exclamation-triangle"></i> <b>GitHub Token tanımlı değil.</b> Private repo veya rate limit için <a href="ayarlar.php?tab=sistem">Ayarlar → Sistem</a>'dan ekleyin.</div>
        <?php endif; ?>

        <?php if ($release): ?>
          <?php $newer = version_compare($release['tag_name'] ?? '0', 'v' . $currentVersion, '>') || version_compare(ltrim($release['tag_name'] ?? '0','v'), $currentVersion, '>'); ?>
          <div class="alert <?= $newer?'alert-warning':'alert-info' ?>">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <h6 class="alert-heading"><?= $newer?'<i class="bi bi-arrow-up-circle"></i> Yeni sürüm mevcut':'<i class="bi bi-check-circle"></i> Sürümünüz güncel' ?></h6>
                <div><b><?= e($release['name'] ?? $release['tag_name']) ?></b> · <?= tr_date($release['published_at'] ?? '') ?></div>
              </div>
              <?php if ($newer): ?>
                <form method="post" onsubmit="return confirm('Güncelleme uygulansın mı? Önce yedek alınacak, ardından dosyalar kopyalanacak.');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="apply">
                  <input type="hidden" name="version" value="<?= e(ltrim($release['tag_name'] ?? '', 'v')) ?>">
                  <input type="hidden" name="url" value="<?= e($release['zipball_url'] ?? '') ?>">
                  <button class="btn btn-warning fw-semibold"><i class="bi bi-download"></i> Şimdi Güncelle</button>
                </form>
              <?php endif; ?>
            </div>
            <?php if (!empty($release['body'])): ?>
              <hr>
              <details><summary class="small text-muted" style="cursor:pointer">Sürüm notları</summary>
                <pre class="small mb-0 mt-2" style="white-space:pre-wrap"><?= e($release['body']) ?></pre>
              </details>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="check">
          <button class="btn btn-primary"><i class="bi bi-arrow-repeat"></i> Sürüm Bilgisini Yenile</button>
        </form>
      </div>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-clock-history text-warning"></i> Güncelleme Geçmişi</h6>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr><th>Sürüm</th><th>Durum</th><th>Tarih</th><th>Açıklama</th></tr></thead>
            <tbody>
            <?php foreach ($gecmis as $g): ?>
              <tr>
                <td class="fw-bold">v<?= e($g['surum']) ?></td>
                <td>
                  <?php $cls = ['basarili'=>'success','hatali'=>'danger','iptal'=>'secondary'][$g['durum']] ?? 'secondary'; ?>
                  <span class="badge bg-<?= $cls ?>"><?= e($g['durum']) ?></span>
                </td>
                <td class="small"><?= tr_datetime($g['olusturma_tarihi']) ?></td>
                <td class="small text-muted"><?= e(mb_substr($g['hata_mesaji'] ?: $g['aciklama'] ?: '', 0, 100)) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$gecmis): ?><tr><td colspan="4" class="text-muted text-center py-3">Geçmiş yok.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-info-circle text-warning"></i> Nasıl Çalışır?</h6>
        <ol class="small">
          <li><b>Sürüm Kontrolü:</b> GitHub Releases API ile en son sürüm sorgulanır.</li>
          <li><b>Yedekleme:</b> Mevcut dosyalar <code>/backups/</code> klasörüne ZIP olarak yedeklenir.</li>
          <li><b>İndirme:</b> Yeni sürüm GitHub'tan indirilir.</li>
          <li><b>Uygulama:</b> Dosyalar üzerine kopyalanır. <code>config/</code>, <code>uploads/</code> ve <code>backups/</code> korunur.</li>
          <li><b>Migration:</b> <code>migration.sql</code> idempotent olarak çalıştırılır.</li>
          <li><b>Manifest:</b> <code>manifest.json</code> sürümü yeni sürümle güncellenir.</li>
        </ol>
        <hr>
        <h6 class="fw-bold small">Korunan Dosyalar</h6>
        <ul class="small text-muted mb-0">
          <li><code>config/config.php</code></li>
          <li><code>uploads/</code> tüm içerik</li>
          <li><code>backups/</code> tüm içerik</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php';
