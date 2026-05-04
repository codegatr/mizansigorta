<?php
define('MZ_ADMIN', true);
$adminTitle = 'CMS Sayfalar';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_assert_post();
    $act = (string)($_POST['action'] ?? '');

    if ($act === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $baslik = trim((string)($_POST['baslik'] ?? ''));
        $slug = trim((string)($_POST['slug'] ?? '')) ?: slugify($baslik);
        if ($baslik === '') admin_redirect('sayfa-yonetimi.php', 'danger', 'Başlık zorunlu.');

        $d = [
            'slug'                  => $slug,
            'baslik'                => $baslik,
            'icerik'                => (string)($_POST['icerik'] ?? ''),
            'seo_baslik'            => trim((string)($_POST['seo_baslik'] ?? '')),
            'seo_aciklama'          => trim((string)($_POST['seo_aciklama'] ?? '')),
            'seo_anahtar_kelimeler' => trim((string)($_POST['seo_anahtar_kelimeler'] ?? '')),
            'menude_goster'         => isset($_POST['menude_goster']) ? 1 : 0,
            'menu_sirasi'           => (int)($_POST['menu_sirasi'] ?? 0),
            'aktif'                 => isset($_POST['aktif']) ? 1 : 0,
        ];

        try {
            $up = admin_handle_upload('kapak', 'sayfa', ['jpg','jpeg','png','webp']);
            if ($up) $d['kapak_gorseli'] = $up;
        } catch (Throwable $e) { admin_redirect('sayfa-yonetimi.php', 'danger', $e->getMessage()); }

        if ($id) {
            $cols = array_keys($d);
            $set  = implode(', ', array_map(fn($c) => "$c=?", $cols));
            db_exec('UPDATE ' . t('sayfalar') . " SET $set, guncelleme_tarihi=NOW() WHERE id=?", array_merge(array_values($d), [$id]));
            audit_log('sayfa_guncelle', 'sayfa', $id);
            admin_redirect('sayfa-yonetimi.php', 'success', 'Sayfa güncellendi.');
        } else {
            $cols = array_keys($d);
            $ph = implode(',', array_fill(0, count($cols), '?'));
            db_exec('INSERT INTO ' . t('sayfalar') . ' (' . implode(',', $cols) . ',olusturma_tarihi) VALUES (' . $ph . ',NOW())', array_values($d));
            audit_log('sayfa_ekle', 'sayfa', db_last_id());
            admin_redirect('sayfa-yonetimi.php', 'success', 'Sayfa eklendi.');
        }
    }

    if ($act === 'sil') {
        $id = (int)($_POST['id'] ?? 0);
        $row = db_row('SELECT sistem, kapak_gorseli FROM ' . t('sayfalar') . ' WHERE id=?', [$id]);
        if ($row && $row['sistem']) admin_redirect('sayfa-yonetimi.php', 'danger', 'Sistem sayfası silinemez.');
        if ($row && $row['kapak_gorseli']) admin_delete_upload('sayfa', $row['kapak_gorseli']);
        db_exec('DELETE FROM ' . t('sayfalar') . ' WHERE id=?', [$id]);
        audit_log('sayfa_sil', 'sayfa', $id);
        admin_redirect('sayfa-yonetimi.php', 'success', 'Sayfa silindi.');
    }
}

$rows = db_all('SELECT id, slug, baslik, menude_goster, menu_sirasi, aktif, sistem, guncelleme_tarihi FROM ' . t('sayfalar') . ' ORDER BY menu_sirasi, baslik');
$editId = (int)($_GET['edit'] ?? 0);
$edit = $editId ? db_row('SELECT * FROM ' . t('sayfalar') . ' WHERE id=?', [$editId]) : null;
?>
<div class="row g-3">
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Başlık</th><th>Slug</th><th>Menü</th><th>Aktif</th><th>Güncelleme</th><th width="100"></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><b><?= e($r['baslik']) ?></b><?php if ($r['sistem']): ?> <span class="badge bg-info">Sistem</span><?php endif; ?></td>
                <td><a href="<?= u($r['slug']) ?>" target="_blank" class="text-decoration-none small">/<?= e($r['slug']) ?></a></td>
                <td><?= $r['menude_goster'] ? '<i class="bi bi-check-lg text-success"></i> ' . (int)$r['menu_sirasi'] : '<i class="bi bi-x text-muted"></i>' ?></td>
                <td><?= $r['aktif'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Pasif</span>' ?></td>
                <td class="small text-muted"><?= tr_datetime($r['guncelleme_tarihi']) ?></td>
                <td>
                  <a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int)$r['id'] ?>"><i class="bi bi-pencil"></i></a>
                  <?php if (!$r['sistem']): ?>
                    <form method="post" class="d-inline" onsubmit="return confirm('Silinsin mi?');">
                      <?= csrf_field() ?><input type="hidden" name="action" value="sil"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                      <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="6" class="text-muted text-center py-4">Sayfa yok.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-<?= $edit?'pencil-square':'plus-square' ?> text-warning"></i> <?= $edit?'Sayfayı Düzenle':'Yeni Sayfa' ?></h6>
        <form method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
          <div class="row g-2">
            <div class="col-12"><label class="form-label small">Başlık *</label><input type="text" name="baslik" required class="form-control form-control-sm" value="<?= e($edit['baslik'] ?? '') ?>"></div>
            <div class="col-md-7"><label class="form-label small">Slug</label><input type="text" name="slug" class="form-control form-control-sm" value="<?= e($edit['slug'] ?? '') ?>"></div>
            <div class="col-md-5"><label class="form-label small">Menü Sırası</label><input type="number" name="menu_sirasi" class="form-control form-control-sm" value="<?= (int)($edit['menu_sirasi'] ?? 0) ?>"></div>
            <div class="col-12"><label class="form-label small">Kapak Görseli</label><input type="file" name="kapak" class="form-control form-control-sm" accept="image/*">
              <?php if ($edit && $edit['kapak_gorseli']): ?><div class="small text-muted mt-1">Mevcut: <?= e($edit['kapak_gorseli']) ?></div><?php endif; ?>
            </div>
            <div class="col-12"><label class="form-label small">İçerik (HTML)</label><textarea name="icerik" rows="10" class="form-control form-control-sm font-monospace"><?= e($edit['icerik'] ?? '') ?></textarea></div>
            <div class="col-12"><label class="form-label small">SEO Başlığı</label><input type="text" name="seo_baslik" class="form-control form-control-sm" value="<?= e($edit['seo_baslik'] ?? '') ?>"></div>
            <div class="col-12"><label class="form-label small">SEO Açıklaması</label><textarea name="seo_aciklama" rows="2" class="form-control form-control-sm"><?= e($edit['seo_aciklama'] ?? '') ?></textarea></div>
            <div class="col-12"><label class="form-label small">SEO Anahtar Kelimeler</label><input type="text" name="seo_anahtar_kelimeler" class="form-control form-control-sm" value="<?= e($edit['seo_anahtar_kelimeler'] ?? '') ?>"></div>
            <div class="col-md-6"><div class="form-check"><input type="checkbox" name="aktif" id="syAk" class="form-check-input" <?= ($edit['aktif']??1)?'checked':'' ?>><label for="syAk" class="form-check-label small">Aktif</label></div></div>
            <div class="col-md-6"><div class="form-check"><input type="checkbox" name="menude_goster" id="syMen" class="form-check-input" <?= ($edit['menude_goster']??0)?'checked':'' ?>><label for="syMen" class="form-check-label small">Üst menüde göster</label></div></div>
          </div>
          <div class="mt-3">
            <button class="btn btn-primary btn-sm fw-semibold"><i class="bi bi-save"></i> Kaydet</button>
            <?php if ($edit): ?><a href="sayfa-yonetimi.php" class="btn btn-outline-secondary btn-sm">Vazgeç</a><?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php';
