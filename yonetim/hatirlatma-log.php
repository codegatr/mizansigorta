<?php
define('MZ_ADMIN', true);
$adminTitle = 'Hatırlatma Gönderim Logu';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_helpers.php';

$tarih = $_GET['tarih'] ?? '';
$kural = (int)($_GET['kural'] ?? 0);
$durum = $_GET['durum'] ?? '';

$where = ['1=1'];
$args  = [];
if ($tarih) { $where[] = 'DATE(l.olusturma_tarihi)=?';   $args[] = $tarih; }
if ($kural) { $where[] = 'l.kural_id=?';                  $args[] = $kural; }
if ($durum) { $where[] = 'l.durum=?';                      $args[] = $durum; }
$wsql = implode(' AND ', $where);

$page  = max(1, (int)($_GET['s'] ?? 1));
$per   = 30;
$total = (int)db_value('SELECT COUNT(*) FROM ' . t('hatirlatma_log') . " l WHERE $wsql", $args);
$pag   = paginate($total, $per, $page);

$rows = db_all(
    'SELECT l.*, k.ad, t.teklif_no, p.police_no
       FROM ' . t('hatirlatma_log') . ' l
       LEFT JOIN ' . t('hatirlatma_kurallari') . ' k ON k.id=l.kural_id
       LEFT JOIN ' . t('teklifler') . ' t ON t.id=l.teklif_id
       LEFT JOIN ' . t('policeler') . ' p ON p.id=l.police_id
      WHERE ' . $wsql . '
      ORDER BY l.olusturma_tarihi DESC
      LIMIT ' . $per . ' OFFSET ' . $pag['offset'],
    $args
);

$kurallar = db_all('SELECT id, ad FROM ' . t('hatirlatma_kurallari') . ' ORDER BY ad');
?>

<form method="get" class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <div class="row g-2">
      <div class="col-md-3">
        <input type="date" name="tarih" class="form-control form-control-sm" value="<?= e($tarih) ?>">
      </div>
      <div class="col-md-3">
        <select name="kural" class="form-select form-select-sm">
          <option value="">Tüm Kurallar</option>
          <?php foreach ($kurallar as $k): ?>
            <option value="<?= (int)$k['id'] ?>" <?= $kural===(int)$k['id']?'selected':'' ?>><?= e($k['ad']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="durum" class="form-select form-select-sm">
          <option value="">Tüm Durumlar</option>
          <option value="basarili" <?= $durum==='basarili'?'selected':'' ?>>Başarılı</option>
          <option value="hata"     <?= $durum==='hata'?'selected':'' ?>>Hata</option>
        </select>
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel"></i> Filtrele</button>
      </div>
      <div class="col-md-2">
        <a class="btn btn-outline-secondary btn-sm w-100" href="hatirlatma-log.php">Temizle</a>
      </div>
    </div>
  </div>
</form>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Tarih</th>
            <th>Kural</th>
            <th>İlgili Kayıt</th>
            <th>Alıcı</th>
            <th>Kanal</th>
            <th>Durum</th>
            <th>Mesaj</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="small text-muted"><?= tr_datetime($r['olusturma_tarihi']) ?></td>
            <td class="small"><?= e($r['ad'] ?: '-') ?></td>
            <td class="small">
              <?php if ($r['teklif_no']): ?>
                <a href="teklif-detay.php?id=<?= (int)$r['teklif_id'] ?>" class="text-decoration-none">Teklif <?= e($r['teklif_no']) ?></a>
              <?php elseif ($r['police_no']): ?>
                <span>Poliçe <?= e($r['police_no']) ?></span>
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
            <td class="small"><?= e($r['alici']) ?></td>
            <td><span class="badge bg-info"><?= e($r['kanal']) ?></span></td>
            <td>
              <?php if ($r['durum'] === 'basarili'): ?>
                <span class="badge bg-success"><i class="bi bi-check"></i> Başarılı</span>
              <?php else: ?>
                <span class="badge bg-danger"><i class="bi bi-x"></i> Hata</span>
              <?php endif; ?>
            </td>
            <td class="small text-muted" style="max-width:300px;"><?= e(mb_substr($r['mesaj'] ?? '', 0, 120)) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
          <tr><td colspan="7" class="text-center text-muted py-5">Kayıt bulunamadı.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if ($pag['pages'] > 1): ?>
<nav class="mt-3">
  <ul class="pagination pagination-sm justify-content-center">
    <?php $qs = $_GET; unset($qs['s']); $base = 'hatirlatma-log.php?' . http_build_query($qs);
      for ($i = 1; $i <= $pag['pages']; $i++): ?>
      <li class="page-item <?= $i === $pag['page'] ? 'active' : '' ?>">
        <a class="page-link" href="<?= e($base) ?>&s=<?= $i ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>

<?php require __DIR__ . '/_footer.php';
