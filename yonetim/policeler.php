<?php
define('MZ_ADMIN', true);
$adminTitle = 'Poliçeler';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_assert_post();
    $act = (string)($_POST['action'] ?? '');
    if ($act === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $d = [
            'police_no'      => trim((string)($_POST['police_no'] ?? '')),
            'musteri_id'     => (int)($_POST['musteri_id'] ?? 0) ?: null,
            'urun_id'        => (int)($_POST['urun_id'] ?? 0) ?: null,
            'sirket_id'      => (int)($_POST['sirket_id'] ?? 0) ?: null,
            'baslangic_tarihi'=> $_POST['baslangic_tarihi'] ?? null,
            'bitis_tarihi'   => $_POST['bitis_tarihi'] ?? null,
            'prim_tutari'    => (float)($_POST['prim_tutari'] ?? 0),
            'para_birimi'    => $_POST['para_birimi'] ?? 'TRY',
            'durum'          => $_POST['durum'] ?? 'aktif',
            'notlar'         => trim((string)($_POST['notlar'] ?? '')),
        ];
        if (!$d['police_no']) admin_redirect('policeler.php', 'danger', 'Poliçe no zorunlu.');
        if ($id) {
            db_exec('UPDATE ' . t('policeler') . ' SET police_no=?, musteri_id=?, urun_id=?, sirket_id=?, baslangic_tarihi=?, bitis_tarihi=?, prim_tutari=?, para_birimi=?, durum=?, notlar=?, guncelleme_tarihi=NOW() WHERE id=?', array_merge(array_values($d), [$id]));
            audit_log('police_guncelle', 'police', $id);
            admin_redirect('policeler.php', 'success', 'Poliçe güncellendi.');
        } else {
            db_exec('INSERT INTO ' . t('policeler') . ' (police_no, musteri_id, urun_id, sirket_id, baslangic_tarihi, bitis_tarihi, prim_tutari, para_birimi, durum, notlar, olusturma_tarihi) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())', array_values($d));
            audit_log('police_ekle', 'police', db_last_id());
            admin_redirect('policeler.php', 'success', 'Yeni poliçe eklendi.');
        }
    }
    if ($act === 'sil') {
        $id = (int)($_POST['id'] ?? 0);
        db_exec('DELETE FROM ' . t('policeler') . ' WHERE id=?', [$id]);
        audit_log('police_sil', 'police', $id);
        admin_redirect('policeler.php', 'success', 'Poliçe silindi.');
    }
}

$q = trim((string)($_GET['q'] ?? ''));
$durum = $_GET['durum'] ?? '';
$yenile = $_GET['yenile'] ?? '';
$where = ['1=1']; $args = [];
if ($q !== '') { $where[] = '(p.police_no LIKE ? OR m.ad_soyad LIKE ? OR m.firma_adi LIKE ?)'; $like = "%$q%"; $args = [$like, $like, $like]; }
if ($durum)    { $where[] = 'p.durum=?'; $args[] = $durum; }
if ($yenile === '30') { $where[] = "p.bitis_tarihi BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND p.durum='aktif'"; }
$wsql = implode(' AND ', $where);

$total = (int)db_value('SELECT COUNT(*) FROM ' . t('policeler') . " p LEFT JOIN " . t('musteriler') . " m ON m.id=p.musteri_id WHERE $wsql", $args);
$page = max(1, (int)($_GET['s'] ?? 1));
$per = 25;
$pag = paginate($total, $per, $page);

$rows = db_all('SELECT p.*, m.ad_soyad, m.firma_adi, u.baslik urun_baslik, s.ad sirket_adi
                FROM ' . t('policeler') . ' p
                LEFT JOIN ' . t('musteriler') . ' m ON m.id=p.musteri_id
                LEFT JOIN ' . t('urunler') . ' u ON u.id=p.urun_id
                LEFT JOIN ' . t('sigorta_sirketleri') . " s ON s.id=p.sirket_id
                WHERE $wsql
                ORDER BY p.bitis_tarihi ASC
                LIMIT $per OFFSET " . $pag['offset'], $args);

$musteriler = db_all('SELECT id, ad_soyad, firma_adi FROM ' . t('musteriler') . ' ORDER BY ad_soyad LIMIT 500');
$urunler    = db_all('SELECT id, baslik FROM ' . t('urunler') . ' WHERE aktif=1 ORDER BY baslik');
$sirketler  = db_all('SELECT id, ad FROM ' . t('sigorta_sirketleri') . ' WHERE aktif=1 ORDER BY ad');

$editId = (int)($_GET['edit'] ?? 0);
$edit = $editId ? db_row('SELECT * FROM ' . t('policeler') . ' WHERE id=?', [$editId]) : null;
?>

<form method="get" class="card border-0 shadow-sm mb-3">
  <div class="card-body p-3">
    <div class="row g-2">
      <div class="col-md-3"><input type="search" name="q" class="form-control form-control-sm" placeholder="Poliçe no veya müşteri..." value="<?= e($q) ?>"></div>
      <div class="col-md-2">
        <select name="durum" class="form-select form-select-sm">
          <option value="">Tüm Durumlar</option>
          <option value="aktif"     <?= $durum==='aktif'?'selected':'' ?>>Aktif</option>
          <option value="sona_erdi" <?= $durum==='sona_erdi'?'selected':'' ?>>Sona Erdi</option>
          <option value="iptal"     <?= $durum==='iptal'?'selected':'' ?>>İptal</option>
        </select>
      </div>
      <div class="col-md-2">
        <a href="?yenile=30" class="btn btn-warning btn-sm <?= $yenile==='30'?'active':'' ?>"><i class="bi bi-arrow-clockwise"></i> 30 Gün İçinde Yenileme</a>
      </div>
      <div class="col-md-2"><button class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel"></i> Filtrele</button></div>
      <div class="col-md-3 text-end">
        <a href="?yeni=1" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#policeModal"><i class="bi bi-plus"></i> Yeni Poliçe</a>
      </div>
    </div>
  </div>
</form>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Poliçe No</th><th>Müşteri</th><th>Ürün</th><th>Şirket</th><th>Başl./Bitiş</th><th>Prim</th><th>Durum</th><th width="100"></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r):
          $kalan = $r['durum']==='aktif' ? (int)((strtotime($r['bitis_tarihi']) - time()) / 86400) : null;
        ?>
          <tr>
            <td class="fw-semibold"><?= e($r['police_no']) ?></td>
            <td class="small"><?= e($r['firma_adi'] ?: $r['ad_soyad']) ?></td>
            <td class="small"><?= e($r['urun_baslik'] ?: '-') ?></td>
            <td class="small"><?= e($r['sirket_adi'] ?: '-') ?></td>
            <td class="small"><?= tr_date($r['baslangic_tarihi']) ?> → <?= tr_date($r['bitis_tarihi']) ?>
              <?php if ($kalan !== null): ?>
                <br><span class="badge bg-<?= $kalan<=7?'danger':($kalan<=30?'warning text-dark':'success') ?>"><?= $kalan ?> gün kaldı</span>
              <?php endif; ?>
            </td>
            <td class="text-end"><?= tr_money($r['prim_tutari'], $r['para_birimi']) ?></td>
            <td><?= badge_durum($r['durum']) ?></td>
            <td>
              <a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int)$r['id'] ?>" data-bs-toggle="modal" data-bs-target="#policeModal"><i class="bi bi-pencil"></i></a>
              <form class="d-inline" method="post" onsubmit="return confirm('Silmek istediğinize emin misiniz?');">
                <?= csrf_field() ?><input type="hidden" name="action" value="sil"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="8" class="text-muted text-center py-4">Poliçe bulunamadı.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if ($pag['pages'] > 1): ?>
  <nav class="mt-3"><ul class="pagination pagination-sm justify-content-center">
  <?php $qs = $_GET; unset($qs['s']); $b = 'policeler.php?' . http_build_query($qs);
    for ($i = 1; $i <= $pag['pages']; $i++): ?>
    <li class="page-item <?= $i === $pag['page'] ? 'active' : '' ?>"><a class="page-link" href="<?= e($b) ?>&s=<?= $i ?>"><?= $i ?></a></li>
  <?php endfor; ?></ul></nav>
<?php endif; ?>

<div class="modal fade" id="policeModal" tabindex="-1" <?= ($edit || isset($_GET['yeni'])) ? 'data-show="1"' : '' ?>>
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
        <div class="modal-header">
          <h5 class="modal-title"><?= $edit?'Poliçeyi Düzenle':'Yeni Poliçe' ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-2">
            <div class="col-md-6"><label class="form-label small">Poliçe No *</label><input type="text" name="police_no" class="form-control form-control-sm" required value="<?= e($edit['police_no'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label small">Durum</label>
              <select name="durum" class="form-select form-select-sm">
                <option value="aktif"     <?= ($edit['durum']??'')==='aktif'?'selected':'' ?>>Aktif</option>
                <option value="sona_erdi" <?= ($edit['durum']??'')==='sona_erdi'?'selected':'' ?>>Sona Erdi</option>
                <option value="iptal"     <?= ($edit['durum']??'')==='iptal'?'selected':'' ?>>İptal</option>
              </select>
            </div>
            <div class="col-md-6"><label class="form-label small">Müşteri</label>
              <select name="musteri_id" class="form-select form-select-sm">
                <option value="">— Seçiniz —</option>
                <?php foreach ($musteriler as $m): ?>
                  <option value="<?= (int)$m['id'] ?>" <?= ($edit['musteri_id']??0)==(int)$m['id']?'selected':'' ?>><?= e($m['firma_adi'] ?: $m['ad_soyad']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6"><label class="form-label small">Ürün</label>
              <select name="urun_id" class="form-select form-select-sm">
                <option value="">— Seçiniz —</option>
                <?php foreach ($urunler as $u): ?>
                  <option value="<?= (int)$u['id'] ?>" <?= ($edit['urun_id']??0)==(int)$u['id']?'selected':'' ?>><?= e($u['baslik']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-12"><label class="form-label small">Sigorta Şirketi</label>
              <select name="sirket_id" class="form-select form-select-sm">
                <option value="">— Seçiniz —</option>
                <?php foreach ($sirketler as $s): ?>
                  <option value="<?= (int)$s['id'] ?>" <?= ($edit['sirket_id']??0)==(int)$s['id']?'selected':'' ?>><?= e($s['ad']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6"><label class="form-label small">Başlangıç</label><input type="date" name="baslangic_tarihi" class="form-control form-control-sm" value="<?= e($edit['baslangic_tarihi'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label small">Bitiş</label><input type="date" name="bitis_tarihi" class="form-control form-control-sm" value="<?= e($edit['bitis_tarihi'] ?? '') ?>"></div>
            <div class="col-md-8"><label class="form-label small">Prim Tutarı</label><input type="number" step="0.01" name="prim_tutari" class="form-control form-control-sm" value="<?= e($edit['prim_tutari'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label small">Para Birimi</label>
              <select name="para_birimi" class="form-select form-select-sm">
                <?php foreach (['TRY','USD','EUR','GBP'] as $c): ?><option <?= ($edit['para_birimi']??'TRY')===$c?'selected':'' ?>><?= $c ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-12"><label class="form-label small">Notlar</label><textarea name="notlar" class="form-control form-control-sm" rows="2"><?= e($edit['notlar'] ?? '') ?></textarea></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Vazgeç</button>
          <button class="btn btn-primary btn-sm fw-semibold"><i class="bi bi-save"></i> Kaydet</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('[data-show="1"]').forEach(el => {
  new bootstrap.Modal(el).show();
});
</script>

<?php require __DIR__ . '/_footer.php';
