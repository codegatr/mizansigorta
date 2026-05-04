<?php
define('MZ_ADMIN', true);
$adminTitle = 'İşlem Kayıtları (Audit Log)';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_helpers.php';
require_role('superadmin', 'admin');

$q       = trim((string)($_GET['q'] ?? ''));
$kid     = (int)($_GET['kullanici'] ?? 0);
$eylem   = trim((string)($_GET['eylem'] ?? ''));
$tarih1  = $_GET['tarih1'] ?? '';
$tarih2  = $_GET['tarih2'] ?? '';

$where = ['1=1']; $args = [];
if ($q !== '')      { $where[] = '(eylem LIKE ? OR aciklama LIKE ? OR nesne_tip LIKE ?)'; $like = "%$q%"; $args = [$like,$like,$like]; }
if ($kid)           { $where[] = 'kullanici_id=?'; $args[] = $kid; }
if ($eylem !== '')  { $where[] = 'eylem=?'; $args[] = $eylem; }
if ($tarih1 !== '') { $where[] = 'olusturma_tarihi >= ?'; $args[] = $tarih1 . ' 00:00:00'; }
if ($tarih2 !== '') { $where[] = 'olusturma_tarihi <= ?'; $args[] = $tarih2 . ' 23:59:59'; }
$wsql = implode(' AND ', $where);

$page  = max(1, (int)($_GET['s'] ?? 1));
$per   = 50;
$total = (int)db_value('SELECT COUNT(*) FROM ' . t('audit_log') . " WHERE $wsql", $args);
$pag   = paginate($total, $per, $page);
$rows  = db_all('SELECT a.*, k.ad_soyad AS kullanici_adi
                 FROM ' . t('audit_log') . ' a
                 LEFT JOIN ' . t('kullanicilar') . ' k ON k.id = a.kullanici_id
                 WHERE ' . $wsql . ' ORDER BY a.olusturma_tarihi DESC LIMIT ' . $per . ' OFFSET ' . $pag['offset'], $args);

$kullanicilar = db_all('SELECT id, ad_soyad FROM ' . t('kullanicilar') . ' ORDER BY ad_soyad');
$eylemler = db_all('SELECT DISTINCT eylem FROM ' . t('audit_log') . ' ORDER BY eylem');
?>

<form method="get" class="card border-0 shadow-sm mb-3">
  <div class="card-body p-3">
    <div class="row g-2">
      <div class="col-md-3"><input type="search" name="q" class="form-control form-control-sm" placeholder="Eylem, açıklama..." value="<?= e($q) ?>"></div>
      <div class="col-md-2">
        <select name="kullanici" class="form-select form-select-sm">
          <option value="">Tüm Kullanıcılar</option>
          <?php foreach ($kullanicilar as $u): ?>
            <option value="<?= (int)$u['id'] ?>" <?= $kid==$u['id']?'selected':'' ?>><?= e($u['ad_soyad']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="eylem" class="form-select form-select-sm">
          <option value="">Tüm Eylemler</option>
          <?php foreach ($eylemler as $e): ?>
            <option value="<?= e($e['eylem']) ?>" <?= $eylem===$e['eylem']?'selected':'' ?>><?= e($e['eylem']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2"><input type="date" name="tarih1" class="form-control form-control-sm" value="<?= e($tarih1) ?>"></div>
      <div class="col-md-2"><input type="date" name="tarih2" class="form-control form-control-sm" value="<?= e($tarih2) ?>"></div>
      <div class="col-md-1"><button class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i></button></div>
    </div>
  </div>
</form>

<div class="d-flex justify-content-between mb-2"><small class="text-muted">Toplam <b><?= $total ?></b> kayıt</small></div>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 small">
        <thead class="table-light"><tr><th>Tarih</th><th>Kullanıcı</th><th>Eylem</th><th>Nesne</th><th>Açıklama</th><th>IP</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="text-nowrap"><?= tr_datetime($r['olusturma_tarihi']) ?></td>
            <td><?= e($r['kullanici_adi'] ?: $r['kullanici_email'] ?: '-') ?></td>
            <td><span class="badge bg-warning text-dark"><?= e($r['eylem']) ?></span></td>
            <td><?= $r['nesne_tip'] ? e($r['nesne_tip']) . ' #' . (int)$r['nesne_id'] : '-' ?></td>
            <td class="text-muted"><?= e($r['aciklama'] ?: '-') ?></td>
            <td class="text-muted"><?= e($r['ip_adresi'] ?: '-') ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="6" class="text-muted text-center py-4">Kayıt yok.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if ($pag['pages'] > 1): ?>
  <nav class="mt-3"><ul class="pagination pagination-sm justify-content-center">
  <?php $qs = $_GET; unset($qs['s']); $b = 'audit-log.php?' . http_build_query($qs);
    $start = max(1, $pag['page'] - 5); $end = min($pag['pages'], $pag['page'] + 5);
    if ($start > 1) echo '<li class="page-item"><a class="page-link" href="'.e($b).'&s=1">1</a></li><li class="page-item disabled"><span class="page-link">…</span></li>';
    for ($i = $start; $i <= $end; $i++): ?>
    <li class="page-item <?= $i === $pag['page'] ? 'active' : '' ?>"><a class="page-link" href="<?= e($b) ?>&s=<?= $i ?>"><?= $i ?></a></li>
  <?php endfor;
    if ($end < $pag['pages']) echo '<li class="page-item disabled"><span class="page-link">…</span></li><li class="page-item"><a class="page-link" href="'.e($b).'&s='.$pag['pages'].'">'.$pag['pages'].'</a></li>';
  ?></ul></nav>
<?php endif; ?>

<?php require __DIR__ . '/_footer.php';
