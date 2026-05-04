<?php
define('MZ_ADMIN', true);
$adminTitle = 'Referanslar';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_assert_post();
    $act = (string)($_POST['action'] ?? '');

    if ($act === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $d = [
            'tip'   => $_POST['tip'] ?? 'yorum',
            'ad'    => trim((string)($_POST['ad'] ?? '')),
            'unvan' => trim((string)($_POST['unvan'] ?? '')),
            'mesaj' => trim((string)($_POST['mesaj'] ?? '')),
            'puan'  => max(1, min(5, (int)($_POST['puan'] ?? 5))),
            'link'  => trim((string)($_POST['link'] ?? '')),
            'sira'  => (int)($_POST['sira'] ?? 0),
            'aktif' => isset($_POST['aktif']) ? 1 : 0,
        ];
        if ($d['ad'] === '') admin_redirect('referanslar.php', 'danger', 'Ad zorunlu.');

        try {
            $up = admin_handle_upload('gorsel', 'referans', ['jpg','jpeg','png','webp','svg']);
            if ($up) $d['gorsel'] = $up;
        } catch (Throwable $e) { admin_redirect('referanslar.php', 'danger', $e->getMessage()); }

        if ($id) {
            $cols = array_keys($d);
            $set = implode(', ', array_map(fn($c) => "$c=?", $cols));
            db_exec('UPDATE ' . t('referanslar') . " SET $set WHERE id=?", array_merge(array_values($d), [$id]));
            audit_log('referans_guncelle', 'referans', $id);
            admin_redirect('referanslar.php', 'success', 'Referans güncellendi.');
        } else {
            $cols = array_keys($d);
            $ph = implode(',', array_fill(0, count($cols), '?'));
            db_exec('INSERT INTO ' . t('referanslar') . ' (' . implode(',', $cols) . ',olusturma_tarihi) VALUES (' . $ph . ',NOW())', array_values($d));
            audit_log('referans_ekle', 'referans', db_last_id());
            admin_redirect('referanslar.php', 'success', 'Referans eklendi.');
        }
    }

    if ($act === 'sil') {
        $id = (int)($_POST['id'] ?? 0);
        $row = db_row('SELECT gorsel FROM ' . t('referanslar') . ' WHERE id=?', [$id]);
        if ($row && $row['gorsel']) admin_delete_upload('referans', $row['gorsel']);
        db_exec('DELETE FROM ' . t('referanslar') . ' WHERE id=?', [$id]);
        audit_log('referans_sil', 'referans', $id);
        admin_redirect('referanslar.php', 'success', 'Referans silindi.');
    }

    if ($act === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        db_exec('UPDATE ' . t('referanslar') . ' SET aktif=1-aktif WHERE id=?', [$id]);
        admin_redirect('referanslar.php', 'success', 'Durum güncellendi.');
    }
}

$tipFilter = (string)($_GET['tip'] ?? '');
$where = ['1=1']; $args = [];
if ($tipFilter) { $where[] = 'tip=?'; $args[] = $tipFilter; }
$wsql = implode(' AND ', $where);

$rows = db_all('SELECT * FROM ' . t('referanslar') . " WHERE $wsql ORDER BY tip, sira, ad", $args);
$editId = (int)($_GET['edit'] ?? 0);
$edit = $editId ? db_row('SELECT * FROM ' . t('referanslar') . ' WHERE id=?', [$editId]) : null;
?>

<div class="d-flex gap-2 mb-3">
  <a href="?" class="btn btn-sm <?= $tipFilter===''?'btn-primary':'btn-outline-primary' ?>">Tümü</a>
  <a href="?tip=yorum" class="btn btn-sm <?= $tipFilter==='yorum'?'btn-warning':'btn-outline-warning' ?>"><i class="bi bi-chat-quote"></i> Müşteri Yorumları</a>
  <a href="?tip=kurum" class="btn btn-sm <?= $tipFilter==='kurum'?'btn-info':'btn-outline-info' ?>"><i class="bi bi-buildings"></i> Kurumsal Referanslar</a>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="row g-3">
      <?php foreach ($rows as $r): ?>
        <div class="col-md-6">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between mb-2">
                <span class="badge bg-<?= $r['tip']==='yorum'?'warning text-dark':'info' ?>"><?= $r['tip']==='yorum'?'Yorum':'Kurum' ?></span>
                <small class="text-muted">#<?= (int)$r['sira'] ?></small>
              </div>
              <?php if ($r['gorsel']): ?>
                <img src="<?= u('uploads/referans/' . rawurlencode($r['gorsel'])) ?>" alt="" style="max-width:80px;max-height:60px;object-fit:contain" class="mb-2">
              <?php endif; ?>
              <div class="fw-bold"><?= e($r['ad']) ?></div>
              <div class="small text-muted"><?= e($r['unvan']) ?></div>
              <?php if ($r['tip']==='yorum'): ?>
                <div class="mt-2"><?php for ($i=1;$i<=5;$i++): ?><i class="bi bi-star<?= $i<=$r['puan']?'-fill text-warning':' text-muted' ?>"></i><?php endfor; ?></div>
                <?php if ($r['mesaj']): ?><div class="small mt-2 fst-italic">"<?= e(mb_substr($r['mesaj'], 0, 120)) ?>"</div><?php endif; ?>
              <?php endif; ?>
              <hr>
              <div class="d-flex gap-1">
                <form method="post" class="d-inline">
                  <?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button class="btn btn-sm <?= $r['aktif']?'btn-success':'btn-outline-secondary' ?>"><i class="bi bi-<?= $r['aktif']?'eye':'eye-slash' ?>"></i></button>
                </form>
                <a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int)$r['id'] ?><?= $tipFilter ? '&tip='.$tipFilter : '' ?>"><i class="bi bi-pencil"></i></a>
                <form method="post" class="d-inline ms-auto" onsubmit="return confirm('Silinsin mi?');">
                  <?= csrf_field() ?><input type="hidden" name="action" value="sil"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$rows): ?><div class="col-12"><div class="alert alert-info">Henüz referans yok.</div></div><?php endif; ?>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card border-0 shadow-sm sticky-lg-top" style="top:80px">
      <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-<?= $edit?'pencil-square':'plus-square' ?> text-warning"></i> <?= $edit?'Düzenle':'Yeni Referans' ?></h6>
        <form method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
          <div class="row g-2">
            <div class="col-md-6"><label class="form-label small">Tip</label>
              <select name="tip" class="form-select form-select-sm">
                <option value="yorum"<?= ($edit['tip']??'')==='yorum'?' selected':'' ?>>Müşteri Yorumu</option>
                <option value="kurum"<?= ($edit['tip']??'')==='kurum'?' selected':'' ?>>Kurumsal Referans</option>
              </select>
            </div>
            <div class="col-md-6"><label class="form-label small">Sıra</label><input type="number" name="sira" class="form-control form-control-sm" value="<?= (int)($edit['sira'] ?? 0) ?>"></div>
            <div class="col-12"><label class="form-label small">Ad / Kurum *</label><input type="text" name="ad" required class="form-control form-control-sm" value="<?= e($edit['ad'] ?? '') ?>"></div>
            <div class="col-12"><label class="form-label small">Unvan / Sektör</label><input type="text" name="unvan" class="form-control form-control-sm" value="<?= e($edit['unvan'] ?? '') ?>"></div>
            <div class="col-12"><label class="form-label small">Görsel / Logo</label><input type="file" name="gorsel" class="form-control form-control-sm" accept="image/*">
              <?php if ($edit && $edit['gorsel']): ?><div class="small text-muted mt-1">Mevcut: <?= e($edit['gorsel']) ?></div><?php endif; ?>
            </div>
            <div class="col-12"><label class="form-label small">Yorum / Açıklama</label><textarea name="mesaj" rows="4" class="form-control form-control-sm"><?= e($edit['mesaj'] ?? '') ?></textarea></div>
            <div class="col-md-6"><label class="form-label small">Puan (1-5)</label><input type="number" min="1" max="5" name="puan" class="form-control form-control-sm" value="<?= (int)($edit['puan'] ?? 5) ?>"></div>
            <div class="col-md-6"><label class="form-label small">Bağlantı (URL)</label><input type="url" name="link" class="form-control form-control-sm" value="<?= e($edit['link'] ?? '') ?>"></div>
            <div class="col-12"><div class="form-check"><input type="checkbox" name="aktif" id="rfAk" class="form-check-input" <?= ($edit['aktif']??1)?'checked':'' ?>><label for="rfAk" class="form-check-label small">Aktif</label></div></div>
          </div>
          <div class="mt-3">
            <button class="btn btn-primary btn-sm fw-semibold"><i class="bi bi-save"></i> Kaydet</button>
            <?php if ($edit): ?><a href="referanslar.php" class="btn btn-outline-secondary btn-sm">Vazgeç</a><?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php';
