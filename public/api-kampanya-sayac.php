<?php
/**
 * Mizan Sigorta - Kampanya Sayac API
 * public/api-kampanya-sayac.php
 *
 * Endpoint: /api/kampanya-goster?id=X  -> gosterim_sayisi+1
 *           /api/kampanya-tik?id=X     -> tiklama_sayisi+1
 *
 * Sadece POST kabul eder. Same-origin.
 * Hizli rate-limit: ayni IP'den ayni kampanya icin <2 sn arayla yeni sayac sayilmaz.
 */

if (!defined('MIZAN_BOOT')) { http_response_code(403); exit; }

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid id']);
    exit;
}

// Hangi alan artirilacak
$path = $_SERVER['REQUEST_URI'] ?? '';
$kolon = strpos($path, '/api/kampanya-tik') !== false ? 'tiklama_sayisi' : 'gosterim_sayisi';

// Rate limit (session bazli, ayni id icin <2 sn)
start_session();
$ssKey = 'kamp_rl_' . $kolon . '_' . $id;
$now = microtime(true);
if (isset($_SESSION[$ssKey]) && ($now - $_SESSION[$ssKey]) < 2.0) {
    echo json_encode(['ok' => true, 'rate_limited' => true]);
    exit;
}
$_SESSION[$ssKey] = $now;

try {
    db_exec('UPDATE ' . t('kampanyalar') . " SET $kolon = $kolon + 1 WHERE id = ?", [$id]);
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'DB error']);
}
