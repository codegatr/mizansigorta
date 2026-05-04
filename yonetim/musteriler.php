<?php
define('MZ_ADMIN', true);
$adminTitle = 'Müşteriler';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_assert_post();
    $act = (string)($_POST['action'] ?? '');
    if ($act === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $d = [
            'tip'           => $_POST['tip'] ?? 'bireysel',
            'ad_soyad'      => trim((string)($_POST['ad_soyad'] ?? '')),
            'firma_adi'     => trim((string)($_POST['firma_adi'] ?? '')),
            'tckn_vkn'      => trim((string)($_POST['tckn_vkn'] ?? '')),
            'telefon'       => normalize_phone((string)($_POST['telefon'] ?? '')),
            'email'         => trim((string)($_POST['email'] ?? '')),
            'sehir'         => trim((string)($_POST['sehir'] ?? '')),
            'adres'         => trim((string)($_POST['adres'] ?? '')),
            'dogum_tarihi'  => $_POST['dogum_tarihi'] ?? null,
            'notlar'        => trim((string)($_POST['notlar'] ?? '')),
        ];
        $d['dogum_tarihi'] = $d['dogum_tarihi'] ?: null;
        if ($id) {
            db_exec('UPDATE ' . t('musteriler') . ' SET tip=?,ad_soyad=?,firma_adi=?,tckn_vkn=?,telefon=?,email=?,sehir=?,adres=?,dogum_tarihi=?,notlar=?,guncelleme_tarihi=NOW() WHERE id=?', array_merge(array_values($d), [$id]));
            audit_log('musteri_guncelle', 'musteri', $id);
            admin_redirect('musteriler.php', 'success', 'Müşteri güncellendi.');
        } else {
            db_exec('INSERT INTO ' . t('musteriler') . ' (tip,ad_soyad,firma_adi,tckn_vkn,telefon,email,sehir,adres,dogum_tarihi,notlar,olusturma_tarihi) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())', array_values($d));
            audit_log('musteri_ekle', 'musteri', db_last_id());
            admin_redirect('musteriler.php', 'success', 'Yeni müşteri eklendi.');
        }
    }
    if ($act === 'sil') {
        $id = (int)($_POST['id'] ?? 0);
        db_exec('DELETE FROM ' . t('musteriler') . ' WHERE id=?', [$id]);
        audit_log('musteri_sil', 'musteri', $id);
        admin_redirect('musteriler.php', 'success', 'Müşteri silindi.');
    }
}

$q = trim((string)($_GET['q'] ?? ''));
$tip = $_GET['tip'] ?? '';
$where = ['1=1']; $args = [];
if ($q !== '') { $where[] = '(ad_soyad LIKE ? OR firma_adi LIKE ? OR telefon LIKE ? OR email LIKE ? OR tckn_vkn LIKE ?)'; $like = "%$q%"; $args = [$like,$like,$like,$like,$like]; }
if ($tip)      { $where[] = 'tip=?'; $args[] = $tip; }
$wsql = implode(' AND ', $where);

$page = max(1, (int)($_GET['s'] ?? 1));
$per  = 30;
$total = (int)db_value('SELECT COUNT(*) FROM ' . t('musteriler') . " WHERE $wsql", $args);
$pag = paginate($total, $per, $page);
$rows = db_all('SELECT * FROM ' . t('musteriler') . " WHERE $wsql ORDER BY olusturma_tarihi DESC LIMIT $per OFFSET " . $pag['offset'], $args);

$editId = (int)($_GET['edit'] ?? 0);
$edit = $editId ? db_row('SELECT * FROM ' . t('musteriler') . ' WHERE id=?', [$editId]) : null;
?>

<div class="row g-3">
  <div class="col-lg-8">
    <form method="get" class="card border-0 shadow-sm mb-3">
      <div class="card-body p-3">
        <div class="row g-2">
          <div class="col-md-6"><input type="search" name="q" class="form-control form-control-sm" placeholder="Ad, telefon, e-posta, TCKN..." value="<?= e($q) ?>"></div>
          <div class="col-md-3">
            <select name="tip" class="form-select form-select-sm">
              <option value="">Tüm Tipler</option>
              <option value="bireysel"  <?= $tip==='bireysel'?'selected':'' ?>>Bireysel</option>
              <option value="kurumsal"  <?= $tip==='kurumsal'?'selected':'' ?>>Kurumsal</option>
            </select>
          </div>
          <div class="col-md-3"><button class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i> Ara</button></div>
        </div>
      </div>
    </form>

    <div class="card border-0 shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Tip</th><th>Ad / Firma</th><th>İletişim</th><th>Şehir</th><th>Tarih</th><th width="100"></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><span class="badge bg-<?= $r['tip']==='kurumsal'?'primary':'info' ?>"><?= $r['tip']==='kurumsal'?'K':'B' ?></span></td>
                <td><b><?= e($r['firma_adi'] ?: $r['ad_soyad']) ?></b><?php if ($r['firma_adi'] && $r['ad_soyad']): ?><br><small class="text-muted"><?= e($r['ad_soyad']) ?></small><?php endif; ?></td>
                <td class="small"><?= e($r['telefon']) ?><br><span class="text-muted"><?= e($r['email']) ?></span></td>
                <td class="small"><?= e($r['sehir'] ?: '-') ?></td>
                <td class="small text-muted"><?= tr_date($r['olusturma_tarihi']) ?></td>
                <td>
                  <a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int)$r['id'] ?>"><i class="bi bi-pencil"></i></a>
                  <form class="d-inline" method="post" onsubmit="return confirm('Silmek istediğinize emin misiniz?');">
                    <?= csrf_field() ?><input type="hidden" name="action" value="sil"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                  </form>
                </td>
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
      <?php $qs = $_GET; unset($qs['s']); $b = 'musteriler.php?' . http_build_query($qs);
        for ($i = 1; $i <= $pag['pages']; $i++): ?>
        <li class="page-item <?= $i === $pag['page'] ? 'active' : '' ?>"><a class="page-link" href="<?= e($b) ?>&s=<?= $i ?>"><?= $i ?></a></li>
      <?php endfor; ?></ul></nav>
    <?php endif; ?>
  </div>

  <div class="col-lg-4">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-<?= $edit?'pencil-square':'plus-square' ?> text-warning"></i> <?= $edit?'Müşteriyi Düzenle':'Yeni Müşteri' ?></h6>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">

          <div class="row g-2">
            <div class="col-md-6"><label class="form-label small">Tip</label>
              <select name="tip" class="form-select form-select-sm">
                <option value="bireysel" <?= ($edit['tip']??'')==='bireysel'?'selected':'' ?>>Bireysel</option>
                <option value="kurumsal" <?= ($edit['tip']??'')==='kurumsal'?'selected':'' ?>>Kurumsal</option>
              </select>
            </div>
            <div class="col-md-6"><label class="form-label small">TCKN/VKN</label><input type="text" name="tckn_vkn" class="form-control form-control-sm" value="<?= e($edit['tckn_vkn'] ?? '') ?>"></div>
            <div class="col-12"><label class="form-label small">Ad Soyad *</label><input type="text" name="ad_soyad" class="form-control form-control-sm" required value="<?= e($edit['ad_soyad'] ?? '') ?>"></div>
            <div class="col-12"><label class="form-label small">Firma Adı</label><input type="text" name="firma_adi" class="form-control form-control-sm" value="<?= e($edit['firma_adi'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label small">Telefon *</label><input type="tel" name="telefon" class="form-control form-control-sm" required value="<?= e($edit['telefon'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label small">E-posta</label><input type="email" name="email" class="form-control form-control-sm" value="<?= e($edit['email'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label small">Şehir</label><input type="text" name="sehir" class="form-control form-control-sm" value="<?= e($edit['sehir'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label small">Doğum Tarihi</label><input type="date" name="dogum_tarihi" class="form-control form-control-sm" value="<?= e($edit['dogum_tarihi'] ?? '') ?>"></div>
            <div class="col-12"><label class="form-label small">Adres</label><textarea name="adres" class="form-control form-control-sm" rows="2"><?= e($edit['adres'] ?? '') ?></textarea></div>
            <div class="col-12"><label class="form-label small">Notlar</label><textarea name="notlar" class="form-control form-control-sm" rows="2"><?= e($edit['notlar'] ?? '') ?></textarea></div>
            <div class="col-12 mt-3">
              <button class="btn btn-primary btn-sm fw-semibold"><i class="bi bi-save"></i> Kaydet</button>
              <?php if ($edit): ?><a href="musteriler.php" class="btn btn-outline-secondary btn-sm">Vazgeç</a><?php endif; ?>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php';
