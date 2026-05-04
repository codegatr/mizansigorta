<?php
/**
 * Mizan Sigorta - Otomatik Yedekleme Cron
 * cron/otomatik-yedekleme.php
 *
 * - Veritabanı dökümünü PHP üzerinden alır (mysqldump gerektirmez).
 * - /backups/ klasörüne ZIP olarak yazar.
 * - Audit log'a boyut + dosya adı bilgisini düşer.
 * - 30 günden eski yedekleri otomatik siler.
 *
 * Kurulum: wget -q -O- "https://mizansigorta.com/cron/otomatik-yedekleme.php?key=XXX" > /dev/null 2>&1
 * Önerilen frekans: Haftada 1 (Pazar 03:00)
 */

declare(strict_types=1);

if (!defined('MIZAN_BOOT')) define('MIZAN_BOOT', true);
require __DIR__ . '/../includes/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    $expected = (string)setting('cron_anahtar');
    $given    = (string)($_GET['key'] ?? '');
    if ($expected === '' || !hash_equals($expected, $given)) { http_response_code(403); exit('Forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}

@set_time_limit(900);

$started  = microtime(true);
$bdir     = MIZAN_ROOT . '/backups';
if (!is_dir($bdir)) @mkdir($bdir, 0755, true);
$stamp    = date('Ymd-His');
$sqlFile  = $bdir . '/db-' . $stamp . '.sql';
$zipFile  = $bdir . '/yedek-' . $stamp . '.zip';

echo "Mizan Sigorta yedekleme — $stamp\n";

// 1) Veritabanı dump
echo "[1/3] Veritabanı dökümü hazırlanıyor...\n";
$pdo = db();
$dump = "-- Mizan Sigorta otomatik yedek — " . date('Y-m-d H:i:s') . "\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";

$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$prefix = DB_PREFIX;
foreach ($tables as $tbl) {
    if ($prefix && !str_starts_with((string)$tbl, $prefix)) continue;

    $create = $pdo->query("SHOW CREATE TABLE `$tbl`")->fetch(PDO::FETCH_ASSOC);
    $dump .= "\n-- Tablo: $tbl\nDROP TABLE IF EXISTS `$tbl`;\n" . ($create['Create Table'] ?? '') . ";\n\n";

    $rows = $pdo->query("SELECT * FROM `$tbl`")->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) continue;
    $cols = array_keys($rows[0]);
    $colSql = '`' . implode('`,`', $cols) . '`';
    foreach ($rows as $row) {
        $vals = [];
        foreach ($row as $v) {
            if ($v === null) $vals[] = 'NULL';
            else $vals[] = $pdo->quote((string)$v);
        }
        $dump .= "INSERT INTO `$tbl` ($colSql) VALUES (" . implode(',', $vals) . ");\n";
    }
}
$dump .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
file_put_contents($sqlFile, $dump);
echo " → " . number_format(filesize($sqlFile)/1024, 1) . " KB SQL\n";

// 2) ZIP olustur (sql + uploads + manifest)
echo "[2/3] ZIP arşivi oluşturuluyor...\n";
$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("ZIP açılamadı.\n");
}
$zip->addFile($sqlFile, basename($sqlFile));
$zip->addFile(MIZAN_ROOT . '/manifest.json', 'manifest.json');

// uploads ekle
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(MIZAN_UPLOADS, RecursiveDirectoryIterator::SKIP_DOTS));
foreach ($rii as $file) {
    if ($file->isDir()) continue;
    $abs = $file->getRealPath();
    if ($abs === false) continue;
    $rel = 'uploads/' . ltrim(str_replace(MIZAN_UPLOADS, '', $abs), '/\\');
    $zip->addFile($abs, str_replace('\\', '/', $rel));
}
$zip->close();
@unlink($sqlFile);
$zipKb = round(filesize($zipFile) / 1024, 1);
echo " → " . $zipKb . " KB ZIP: " . basename($zipFile) . "\n";

// 3) Eski yedekleri temizle (30 gunden eski)
echo "[3/3] Eski yedekler temizleniyor...\n";
$silinen = 0;
foreach (glob($bdir . '/yedek-*.zip') as $f) {
    if (filemtime($f) < strtotime('-30 days')) {
        @unlink($f);
        $silinen++;
    }
}
echo " → $silinen eski yedek silindi.\n";

$elapsed = round(microtime(true) - $started, 2);
echo "TAMAMLANDI ({$elapsed}s)\n";

try {
    audit_log('cron_yedekleme', 'cron', null, basename($zipFile) . " ({$zipKb} KB, sure={$elapsed}s, eski_silinen={$silinen})");
} catch (Throwable $e) {}
