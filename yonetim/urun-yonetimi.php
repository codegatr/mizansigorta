<?php
define('MZ_ADMIN', true);
$adminTitle = 'Sigorta Ürünleri';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_assert_post();
    $act = (string)($_POST['action'] ?? '');

    if ($act === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $slug = trim((string)($_POST['slug'] ?? ''));
        $baslik = trim((string)($_POST['baslik'] ?? ''));
        if ($baslik === '') admin_redirect('urun-yonetimi.php', 'danger', 'Başlık zorunlu.');
        if ($slug === '') $slug = slugify($baslik);

        $d = [
            'slug'          => $slug,
            'baslik'        => $baslik,
            'kisa_aciklama' => trim((string)($_POST['kisa_aciklama'] ?? '')),
            'aciklama'      => (string)($_POST['aciklama'] ?? ''),
            'icon'          => trim((string)($_POST['icon'] ?? '')),
            'seo_baslik'    => trim((string)($_POST['seo_baslik'] ?? '')),
            'seo_aciklama'  => trim((string)($_POST['seo_aciklama'] ?? '')),
            'sira'          => (int)($_POST['sira'] ?? 0),
            'aktif'         => isset($_POST['aktif']) ? 1 : 0,
            'one_cikan'     => isset($_POST['one_cikan']) ? 1 : 0,
        ];

        // Gorsel yukleme
        try {
            $upload = admin_handle_upload('gorsel', 'urun', ['jpg','jpeg','png','webp','svg']);
            if ($upload) $d['gorsel'] = $upload;
        } catch (Throwable $e) {
            admin_redirect('urun-yonetimi.php', 'danger', 'Görsel: ' . $e->getMessage());
        }

        if ($id) {
            $cols = array_keys($d);
            $set  = implode(', ', array_map(fn($c) => "$c=?", $cols));
            db_exec('UPDATE ' . t('urunler') . " SET $set, guncelleme_tarihi=NOW() WHERE id=?", array_merge(array_values($d), [$id]));
            audit_log('urun_guncelle', 'urun', $id);
            admin_redirect('urun-yonetimi.php', 'success', 'Ürün güncellendi.');
        } else {
            $cols = array_keys($d);
            $ph   = implode(',', array_fill(0, count($cols), '?'));
            db_exec('INSERT INTO ' . t('urunler') . ' (' . implode(',', $cols) . ',olusturma_tarihi) VALUES (' . $ph . ',NOW())', array_values($d));
            audit_log('urun_ekle', 'urun', db_last_id());
            admin_redirect('urun-yonetimi.php', 'success', 'Ürün eklendi.');
        }
    }

    if ($act === 'sil') {
        $id = (int)($_POST['id'] ?? 0);
        $u = db_row('SELECT gorsel FROM ' . t('urunler') . ' WHERE id=?', [$id]);
        if ($u && $u['gorsel']) admin_delete_upload('urun', $u['gorsel']);
        db_exec('DELETE FROM ' . t('urunler') . ' WHERE id=?', [$id]);
        audit_log('urun_sil', 'urun', $id);
        admin_redirect('urun-yonetimi.php', 'success', 'Ürün silindi.');
    }

    if ($act === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $field = (string)($_POST['field'] ?? '');
        if ($id && in_array($field, ['aktif','one_cikan'], true)) {
            db_exec("UPDATE " . t('urunler') . " SET $field = 1 - $field, guncelleme_tarihi=NOW() WHERE id=?", [$id]);
            audit_log('urun_toggle', 'urun', $id, $field);
            admin_redirect('urun-yonetimi.php', 'success', 'Durum güncellendi.');
        }
    }
}

$rows = db_all('SELECT * FROM ' . t('urunler') . ' ORDER BY sira, baslik');
$editId = (int)($_GET['edit'] ?? 0);
$edit = $editId ? db_row('SELECT * FROM ' . t('urunler') . ' WHERE id=?', [$editId]) : null;
?>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th width="40">#</th><th>Ürün</th><th width="80">Aktif</th><th width="80">Öne Çık.</th><th width="100"></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><span class="text-muted small"><?= (int)$r['sira'] ?></span></td>
                <td>
                  <?php if ($r['icon']): ?><i class="bi bi-<?= e($r['icon']) ?> text-warning fs-5 me-2"></i><?php endif; ?>
                  <b><?= e($r['baslik']) ?></b>
                  <span class="text-muted small ms-2">/<?= e($r['slug']) ?></span>
                  <?php if ($r['kisa_aciklama']): ?><div class="small text-muted"><?= e(mb_substr($r['kisa_aciklama'], 0, 80)) ?></div><?php endif; ?>
                </td>
                <td>
                  <form method="post" class="d-inline">
                    <?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="field" value="aktif"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-sm <?= $r['aktif']?'btn-success':'btn-outline-secondary' ?>"><i class="bi bi-<?= $r['aktif']?'check-lg':'x-lg' ?>"></i></button>
                  </form>
                </td>
                <td>
                  <form method="post" class="d-inline">
                    <?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="field" value="one_cikan"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-sm <?= $r['one_cikan']?'btn-warning':'btn-outline-secondary' ?>"><i class="bi bi-star<?= $r['one_cikan']?'-fill':'' ?>"></i></button>
                  </form>
                </td>
                <td>
                  <a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int)$r['id'] ?>"><i class="bi bi-pencil"></i></a>
                  <form method="post" class="d-inline" onsubmit="return confirm('Silinsin mi?');">
                    <?= csrf_field() ?><input type="hidden" name="action" value="sil"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card border-0 shadow-sm sticky-lg-top" style="top:80px">
      <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-<?= $edit?'pencil-square':'plus-square' ?> text-warning"></i> <?= $edit?'Ürünü Düzenle':'Yeni Ürün' ?></h6>
        <form method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
          <div class="row g-2">
            <div class="col-12"><label class="form-label small">Başlık *</label><input type="text" name="baslik" class="form-control form-control-sm" required value="<?= e($edit['baslik'] ?? '') ?>"></div>
            <div class="col-md-7"><label class="form-label small">Slug (boş bırakılırsa otomatik)</label><input type="text" name="slug" class="form-control form-control-sm" value="<?= e($edit['slug'] ?? '') ?>"></div>
            <div class="col-md-5"><label class="form-label small">Sıra</label><input type="number" name="sira" class="form-control form-control-sm" value="<?= (int)($edit['sira'] ?? 0) ?>"></div>
            <div class="col-md-7"><label class="form-label small">Bootstrap Icon (örn: shield-check)</label><input type="text" name="icon" class="form-control form-control-sm" value="<?= e($edit['icon'] ?? '') ?>"></div>
            <div class="col-md-5"><label class="form-label small">Görsel</label><input type="file" name="gorsel" class="form-control form-control-sm" accept="image/*">
              <?php if ($edit && $edit['gorsel']): ?><div class="small text-muted mt-1">Mevcut: <?= e($edit['gorsel']) ?></div><?php endif; ?>
            </div>
            <div class="col-12"><label class="form-label small">Kısa Açıklama</label><textarea name="kisa_aciklama" rows="2" class="form-control form-control-sm"><?= e($edit['kisa_aciklama'] ?? '') ?></textarea></div>
            <div class="col-12"><label class="form-label small">Açıklama (HTML destekli)</label><textarea name="aciklama" rows="6" class="form-control form-control-sm"><?= e($edit['aciklama'] ?? '') ?></textarea></div>
            <div class="col-12"><label class="form-label small">SEO Başlığı</label><input type="text" name="seo_baslik" class="form-control form-control-sm" value="<?= e($edit['seo_baslik'] ?? '') ?>"></div>
            <div class="col-12"><label class="form-label small">SEO Açıklaması</label><textarea name="seo_aciklama" rows="2" class="form-control form-control-sm"><?= e($edit['seo_aciklama'] ?? '') ?></textarea></div>
            <div class="col-md-6"><div class="form-check"><input type="checkbox" name="aktif" id="urAk" class="form-check-input" <?= ($edit['aktif']??1)?'checked':'' ?>><label for="urAk" class="form-check-label small">Aktif</label></div></div>
            <div class="col-md-6"><div class="form-check"><input type="checkbox" name="one_cikan" id="urOn" class="form-check-input" <?= ($edit['one_cikan']??0)?'checked':'' ?>><label for="urOn" class="form-check-label small">Anasayfada öne çıksın</label></div></div>
          </div>
          <div class="mt-3"><button class="btn btn-primary btn-sm fw-semibold"><i class="bi bi-save"></i> Kaydet</button>
            <?php if ($edit): ?><a href="urun-yonetimi.php" class="btn btn-outline-secondary btn-sm">Vazgeç</a><?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php';
