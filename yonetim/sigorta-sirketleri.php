<?php
define('MZ_ADMIN', true);
$adminTitle = 'Anlaşmalı Sigorta Şirketleri';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_assert_post();
    $act = (string)($_POST['action'] ?? '');

    if ($act === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $d = [
            'ad'          => trim((string)($_POST['ad'] ?? '')),
            'web_sitesi'  => trim((string)($_POST['web_sitesi'] ?? '')),
            'acente_kodu' => trim((string)($_POST['acente_kodu'] ?? '')),
            'aciklama'    => trim((string)($_POST['aciklama'] ?? '')),
            'sira'        => (int)($_POST['sira'] ?? 0),
            'aktif'       => isset($_POST['aktif']) ? 1 : 0,
        ];
        if ($d['ad'] === '') admin_redirect('sigorta-sirketleri.php', 'danger', 'Şirket adı zorunlu.');

        try {
            $up = admin_handle_upload('logo', 'sirket', ['jpg','jpeg','png','webp','svg']);
            if ($up) $d['logo'] = $up;
        } catch (Throwable $e) { admin_redirect('sigorta-sirketleri.php', 'danger', $e->getMessage()); }

        if ($id) {
            $cols = array_keys($d);
            $set = implode(', ', array_map(fn($c) => "$c=?", $cols));
            db_exec('UPDATE ' . t('sigorta_sirketleri') . " SET $set WHERE id=?", array_merge(array_values($d), [$id]));
            audit_log('sirket_guncelle', 'sirket', $id);
            admin_redirect('sigorta-sirketleri.php', 'success', 'Şirket güncellendi.');
        } else {
            $cols = array_keys($d);
            $ph = implode(',', array_fill(0, count($cols), '?'));
            db_exec('INSERT INTO ' . t('sigorta_sirketleri') . ' (' . implode(',', $cols) . ',olusturma_tarihi) VALUES (' . $ph . ',NOW())', array_values($d));
            audit_log('sirket_ekle', 'sirket', db_last_id());
            admin_redirect('sigorta-sirketleri.php', 'success', 'Şirket eklendi.');
        }
    }

    if ($act === 'sil') {
        $id = (int)($_POST['id'] ?? 0);
        $row = db_row('SELECT logo FROM ' . t('sigorta_sirketleri') . ' WHERE id=?', [$id]);
        if ($row && $row['logo']) admin_delete_upload('sirket', $row['logo']);
        db_exec('DELETE FROM ' . t('sigorta_sirketleri') . ' WHERE id=?', [$id]);
        audit_log('sirket_sil', 'sirket', $id);
        admin_redirect('sigorta-sirketleri.php', 'success', 'Şirket silindi.');
    }

    if ($act === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        db_exec('UPDATE ' . t('sigorta_sirketleri') . ' SET aktif=1-aktif WHERE id=?', [$id]);
        admin_redirect('sigorta-sirketleri.php', 'success', 'Durum güncellendi.');
    }
}

$rows = db_all('SELECT * FROM ' . t('sigorta_sirketleri') . ' ORDER BY sira, ad');
$editId = (int)($_GET['edit'] ?? 0);
$edit = $editId ? db_row('SELECT * FROM ' . t('sigorta_sirketleri') . ' WHERE id=?', [$editId]) : null;
?>

<div class="alert alert-info d-flex gap-3 align-items-start mb-3">
  <i class="bi bi-info-circle-fill fs-4"></i>
  <div class="flex-grow-1 small">
    <strong class="d-block mb-1">Şirket logoları hakkında</strong>
    Anasayfada ve hakkımızda sayfasında, logo yüklü olan şirketler için logo, yüklü olmayanlar için şirket adı metin olarak görünür.
    Resmi logoları acente sözleşmenizle veya şirketin <a href="https://www.tsb.org.tr/tr/uye-sirketler" target="_blank" rel="noopener">TSB üye listesi <i class="bi bi-box-arrow-up-right small"></i></a> üzerinden temin ettiğiniz kurumsal kit sayfasından edinebilirsiniz.
    Önerilen format: <strong>PNG/SVG, şeffaf zemin, 240×90 px civarı, ~50 KB altı.</strong>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th width="80">Logo</th><th>Şirket</th><th>Acente Kodu</th><th>Web</th><th>Sıra</th><th>Aktif</th><th width="100"></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td>
                  <?php if ($r['logo']): ?>
                    <img src="<?= e(sirket_logo_url($r["logo"])) ?>" alt="" style="max-width:60px;max-height:40px;object-fit:contain">
                  <?php else: ?>
                    <i class="bi bi-buildings text-muted fs-3"></i>
                  <?php endif; ?>
                </td>
                <td><b><?= e($r['ad']) ?></b><?php if ($r['aciklama']): ?><div class="small text-muted"><?= e(mb_substr($r['aciklama'], 0, 60)) ?></div><?php endif; ?></td>
                <td class="small"><?= e($r['acente_kodu'] ?: '-') ?></td>
                <td class="small"><?= $r['web_sitesi'] ? '<a href="'.e($r['web_sitesi']).'" target="_blank" class="text-decoration-none"><i class="bi bi-box-arrow-up-right"></i></a>' : '-' ?></td>
                <td><?= (int)$r['sira'] ?></td>
                <td>
                  <form method="post" class="d-inline">
                    <?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-sm <?= $r['aktif']?'btn-success':'btn-outline-secondary' ?>"><i class="bi bi-<?= $r['aktif']?'check-lg':'x-lg' ?>"></i></button>
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
            <?php if (!$rows): ?><tr><td colspan="7" class="text-muted text-center py-5">Henüz anlaşmalı şirket yok.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-<?= $edit?'pencil-square':'plus-square' ?> text-warning"></i> <?= $edit?'Şirketi Düzenle':'Yeni Şirket' ?></h6>
        <form method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
          <div class="row g-2">
            <div class="col-12"><label class="form-label small">Şirket Adı *</label><input type="text" name="ad" required class="form-control form-control-sm" value="<?= e($edit['ad'] ?? '') ?>"></div>
            <div class="col-12">
              <label class="form-label small">Logo</label>
              <input type="file" name="logo" class="form-control form-control-sm" accept="image/png,image/svg+xml,image/webp,image/jpeg">
              <?php if ($edit && $edit['logo']): ?>
                <div class="small text-muted mt-1"><i class="bi bi-image"></i> Mevcut: <code><?= e($edit['logo']) ?></code></div>
              <?php endif; ?>
              <div class="form-text small">
                Önerilen: <strong>PNG/SVG, şeffaf zemin, 240×90 px civarı.</strong>
                Şirketin resmi web sitesinden veya acente portalından temin ettiğiniz logoyu yükleyin.
                Logo yüklenmediği takdirde anasayfada şirket adı metin olarak görünür.
              </div>
            </div>
            <div class="col-md-7"><label class="form-label small">Web Sitesi</label><input type="url" name="web_sitesi" class="form-control form-control-sm" value="<?= e($edit['web_sitesi'] ?? '') ?>"></div>
            <div class="col-md-5"><label class="form-label small">Acente Kodu</label><input type="text" name="acente_kodu" class="form-control form-control-sm" value="<?= e($edit['acente_kodu'] ?? '') ?>"></div>
            <div class="col-12"><label class="form-label small">Açıklama</label><textarea name="aciklama" rows="2" class="form-control form-control-sm"><?= e($edit['aciklama'] ?? '') ?></textarea></div>
            <div class="col-md-6"><label class="form-label small">Sıra</label><input type="number" name="sira" class="form-control form-control-sm" value="<?= (int)($edit['sira'] ?? 0) ?>"></div>
            <div class="col-md-6 d-flex align-items-end"><div class="form-check"><input type="checkbox" name="aktif" id="ssAk2" class="form-check-input" <?= ($edit['aktif']??1)?'checked':'' ?>><label for="ssAk2" class="form-check-label small">Aktif</label></div></div>
          </div>
          <div class="mt-3">
            <button class="btn btn-primary btn-sm fw-semibold"><i class="bi bi-save"></i> Kaydet</button>
            <?php if ($edit): ?><a href="sigorta-sirketleri.php" class="btn btn-outline-secondary btn-sm">Vazgeç</a><?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php';
