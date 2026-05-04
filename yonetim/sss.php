<?php
define('MZ_ADMIN', true);
$adminTitle = 'S.S.S. Yönetimi';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_assert_post();
    $act = (string)($_POST['action'] ?? '');

    if ($act === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $d = [
            'kategori' => trim((string)($_POST['kategori'] ?? 'Genel')) ?: 'Genel',
            'soru'     => trim((string)($_POST['soru'] ?? '')),
            'cevap'    => (string)($_POST['cevap'] ?? ''),
            'sira'     => (int)($_POST['sira'] ?? 0),
            'aktif'    => isset($_POST['aktif']) ? 1 : 0,
        ];
        if ($d['soru'] === '' || $d['cevap'] === '') admin_redirect('sss.php', 'danger', 'Soru ve cevap zorunlu.');

        if ($id) {
            db_exec('UPDATE ' . t('sss') . ' SET kategori=?,soru=?,cevap=?,sira=?,aktif=?,guncelleme_tarihi=NOW() WHERE id=?', array_merge(array_values($d), [$id]));
            audit_log('sss_guncelle', 'sss', $id);
            admin_redirect('sss.php', 'success', 'Soru güncellendi.');
        } else {
            db_exec('INSERT INTO ' . t('sss') . ' (kategori,soru,cevap,sira,aktif,olusturma_tarihi) VALUES (?,?,?,?,?,NOW())', array_values($d));
            audit_log('sss_ekle', 'sss', db_last_id());
            admin_redirect('sss.php', 'success', 'Soru eklendi.');
        }
    }

    if ($act === 'sil') {
        $id = (int)($_POST['id'] ?? 0);
        db_exec('DELETE FROM ' . t('sss') . ' WHERE id=?', [$id]);
        audit_log('sss_sil', 'sss', $id);
        admin_redirect('sss.php', 'success', 'Soru silindi.');
    }

    if ($act === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        db_exec('UPDATE ' . t('sss') . ' SET aktif=1-aktif, guncelleme_tarihi=NOW() WHERE id=?', [$id]);
        admin_redirect('sss.php', 'success', 'Durum güncellendi.');
    }
}

$rows = db_all('SELECT * FROM ' . t('sss') . ' ORDER BY kategori, sira, id');
$grouped = [];
foreach ($rows as $r) $grouped[$r['kategori']][] = $r;
$editId = (int)($_GET['edit'] ?? 0);
$edit = $editId ? db_row('SELECT * FROM ' . t('sss') . ' WHERE id=?', [$editId]) : null;
?>

<div class="row g-3">
  <div class="col-lg-7">
    <?php foreach ($grouped as $kat => $items): ?>
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-warning-subtle border-0 fw-bold"><i class="bi bi-folder"></i> <?= e($kat) ?> <small class="text-muted">(<?= count($items) ?>)</small></div>
        <div class="list-group list-group-flush">
          <?php foreach ($items as $r): ?>
            <div class="list-group-item d-flex justify-content-between align-items-start">
              <div class="me-3 flex-grow-1">
                <div class="fw-semibold"><?= e($r['soru']) ?> <span class="text-muted small">#<?= (int)$r['sira'] ?></span></div>
                <div class="small text-muted"><?= e(mb_substr(strip_tags($r['cevap']), 0, 140)) ?>…</div>
              </div>
              <div class="text-nowrap">
                <form method="post" class="d-inline">
                  <?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button class="btn btn-sm <?= $r['aktif']?'btn-success':'btn-outline-secondary' ?>"><i class="bi bi-<?= $r['aktif']?'eye':'eye-slash' ?>"></i></button>
                </form>
                <a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int)$r['id'] ?>"><i class="bi bi-pencil"></i></a>
                <form method="post" class="d-inline" onsubmit="return confirm('Silinsin mi?');">
                  <?= csrf_field() ?><input type="hidden" name="action" value="sil"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (!$grouped): ?><div class="alert alert-info">Henüz soru yok. Sağdaki formdan ilk sorunuzu ekleyin.</div><?php endif; ?>
  </div>

  <div class="col-lg-5">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-<?= $edit?'pencil-square':'plus-square' ?> text-warning"></i> <?= $edit?'Soruyu Düzenle':'Yeni Soru' ?></h6>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
          <div class="row g-2">
            <div class="col-md-7"><label class="form-label small">Kategori</label><input type="text" name="kategori" class="form-control form-control-sm" value="<?= e($edit['kategori'] ?? 'Genel') ?>"></div>
            <div class="col-md-5"><label class="form-label small">Sıra</label><input type="number" name="sira" class="form-control form-control-sm" value="<?= (int)($edit['sira'] ?? 0) ?>"></div>
            <div class="col-12"><label class="form-label small">Soru *</label><input type="text" name="soru" required class="form-control form-control-sm" value="<?= e($edit['soru'] ?? '') ?>"></div>
            <div class="col-12"><label class="form-label small">Cevap (HTML) *</label><textarea name="cevap" rows="6" required class="form-control form-control-sm"><?= e($edit['cevap'] ?? '') ?></textarea></div>
            <div class="col-12"><div class="form-check"><input type="checkbox" name="aktif" id="ssAk" class="form-check-input" <?= ($edit['aktif']??1)?'checked':'' ?>><label for="ssAk" class="form-check-label small">Aktif</label></div></div>
          </div>
          <div class="mt-3">
            <button class="btn btn-primary btn-sm fw-semibold"><i class="bi bi-save"></i> Kaydet</button>
            <?php if ($edit): ?><a href="sss.php" class="btn btn-outline-secondary btn-sm">Vazgeç</a><?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php';
