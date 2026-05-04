<?php
/**
 * Mizan Sigorta - Veritabani baglantisi (PDO)
 * includes/db.php
 */

declare(strict_types=1);

if (!defined('MIZAN_BOOT')) {
    http_response_code(403);
    exit('Forbidden');
}

/** @return PDO Singleton PDO baglantisi */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci, time_zone='+03:00'",
        ]);
        return $pdo;
    } catch (PDOException $e) {
        if (MIZAN_DEBUG) {
            die('DB error: ' . $e->getMessage());
        }
        http_response_code(503);
        die('Veritabani baglanti hatasi. Lutfen daha sonra tekrar deneyin.');
    }
}

/** Tablo prefix yardimcisi: t('teklifler') => "mz_teklifler" */
function t(string $name): string
{
    return DB_PREFIX . $name;
}

/** Tek satir donduren kisayol */
function db_row(string $sql, array $params = []): ?array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $r = $st->fetch();
    return $r ?: null;
}

/** Tek deger donduren kisayol (ilk kolon) */
function db_value(string $sql, array $params = [])
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchColumn();
}

/** Coklu satir donduren kisayol */
function db_all(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll() ?: [];
}

/** Insert/update/delete; etkilenen satir sayisini doner */
function db_exec(string $sql, array $params = []): int
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->rowCount();
}

/** Son insert id */
function db_last_id(): int
{
    return (int) db()->lastInsertId();
}
