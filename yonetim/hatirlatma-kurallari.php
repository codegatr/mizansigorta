<?php
define('MZ_ADMIN', true);
$adminTitle = 'Hatırlatma Kuralları';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_helpers.php';
require_role('superadmin', 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_assert_post();
    $act = (string)($_POST['action'] ?? '');

    if ($act === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'ad'    => trim((string)($_POST['ad'] ?? '')),
            'tetikleyici'  => (string)($_POST['tetikleyici'] ?? 'teklif_yeni'),
            'gun_sayisi'   => (int)($_POST['gun_sayisi'] ?? 0),
            'kanal'        => (string)($_POST['kanal'] ?? 'email'),
            'email_konu'   => (string)($_POST['email_konu'] ?? ''),
            'email_govde'  => (string)($_POST['email_govde'] ?? ''),
            'aktif'        => isset($_POST['aktif']) ? 1 : 0,
        ];
        if ($data['ad'] === '') admin_redirect('hatirlatma-kurallari.php', 'danger', 'Kural adı zorunlu.');

        if ($id) {
            db_exec('UPDATE ' . t('hatirlatma_kurallari') . '
                     SET ad=?, tetikleyici=?, gun_sayisi=?, kanal=?, email_konu=?, email_govde=?, aktif=?, guncelleme_tarihi=NOW()
                     WHERE id=?',
                array_merge(array_values($data), [$id]));
            audit_log('hatirlatma_kural_guncelle', 'kural', $id);
            admin_redirect('hatirlatma-kurallari.php', 'success', 'Kural güncellendi.');
        } else {
            db_exec('INSERT INTO ' . t('hatirlatma_kurallari') .
                ' (ad, tetikleyici, gun_sayisi, kanal, email_konu, email_govde, aktif, olusturma_tarihi)
                  VALUES (?,?,?,?,?,?,?,NOW())', array_values($data));
            audit_log('hatirlatma_kural_ekle', 'kural', db_last_id());
            admin_redirect('hatirlatma-kurallari.php', 'success', 'Yeni kural eklendi.');
        }
    }

    if ($act === 'sil') {
        $id = (int)($_POST['id'] ?? 0);
        db_exec('DELETE FROM ' . t('hatirlatma_kurallari') . ' WHERE id=?', [$id]);
        audit_log('hatirlatma_kural_sil', 'kural', $id);
        admin_redirect('hatirlatma-kurallari.php', 'success', 'Kural silindi.');
    }

    if ($act === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        db_exec('UPDATE ' . t('hatirlatma_kurallari') . ' SET aktif = 1-aktif WHERE id=?', [$id]);
        admin_redirect('hatirlatma-kurallari.php', 'success', 'Durum değiştirildi.');
    }
}

$rows = db_all('SELECT * FROM ' . t('hatirlatma_kurallari') . ' ORDER BY tetikleyici, gun_sayisi ASC');

$editId = (int)($_GET['edit'] ?? 0);
$edit = $editId ? db_row('SELECT * FROM ' . t('hatirlatma_kurallari') . ' WHERE id=?', [$editId]) : null;

$tetikleyiciler = [
    'teklif_yeni'        => 'Yeni Teklif Geldiğinde',
    'teklif_islemde'     => 'Teklif İşleme Alındıktan X Gün Sonra',
    'teklif_gonderildi'  => 'Teklif Gönderildikten X Gün Sonra',
    'police_yenileme'    => 'Poliçe Bitiş Tarihinden X Gün Önce',
    'dogum_gunu'         => 'Müşteri Doğum Günü',
];
$kanallar = ['email' => 'E-posta', 'sms' => 'SMS', 'panel' => 'Panel Bildirimi'];
?>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-0">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
          <h6 class="fw-bold mb-0"><i class="bi bi-bell text-warning"></i> Tanımlı Kurallar (<?= count($rows) ?>)</h6>
          <a href="hatirlatma-kurallari.php" class="btn btn-sm btn-primary"><i class="bi bi-plus"></i> Yeni Kural</a>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr><th>Kural</th><th>Tetikleyici</th><th>Gün</th><th>Kanal</th><th>Durum</th><th width="120"></th></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td class="fw-semibold"><?= e($r['ad']) ?></td>
                <td class="small"><?= e($tetikleyiciler[$r['tetikleyici']] ?? $r['tetikleyici']) ?></td>
                <td><span class="badge bg-light text-dark"><?= (int)$r['gun_sayisi'] ?></span></td>
                <td><span class="badge bg-info"><?= e($kanallar[$r['kanal']] ?? $r['kanal']) ?></span></td>
                <td>
                  <form method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-sm btn-<?= $r['aktif'] ? 'success' : 'secondary' ?>">
                      <i class="bi bi-<?= $r['aktif'] ? 'check-circle' : 'x-circle' ?>"></i>
                      <?= $r['aktif'] ? 'Aktif' : 'Pasif' ?>
                    </button>
                  </form>
                </td>
                <td>
                  <a href="?edit=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                  <form method="post" class="d-inline" onsubmit="return confirm('Bu kuralı silmek istiyor musunuz?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="sil">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
              <tr><td colspan="6" class="text-muted text-center py-4">Henüz kural yok.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="alert alert-info mt-3 small">
      <strong><i class="bi bi-info-circle"></i> Cron Çalıştırma:</strong>
      Hatırlatma motorunun çalışması için aşağıdaki komutu DirectAdmin → Cron Jobs'a günde bir kez (örn. 09:00) ekleyin:
      <pre class="bg-white p-2 mt-2 mb-0 rounded small"><?= e(SITE_BASE_URL) ?>/cron/teklif-hatirlatma.php?key=<?= e(setting('cron_anahtar')) ?></pre>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h6 class="fw-bold mb-3">
          <i class="bi bi-<?= $edit ? 'pencil-square' : 'plus-square' ?> text-warning"></i>
          <?= $edit ? 'Kuralı Düzenle' : 'Yeni Kural Ekle' ?>
        </h6>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">

          <div class="mb-3">
            <label class="form-label small fw-semibold">Kural Adı *</label>
            <input type="text" name="ad" class="form-control form-control-sm" required value="<?= e($edit['ad'] ?? '') ?>" placeholder="Örn. Yeni Teklif Karşılama">
          </div>

          <div class="row g-2 mb-3">
            <div class="col-md-7">
              <label class="form-label small fw-semibold">Tetikleyici *</label>
              <select name="tetikleyici" class="form-select form-select-sm">
                <?php foreach ($tetikleyiciler as $k => $v): ?>
                  <option value="<?= $k ?>" <?= ($edit['tetikleyici'] ?? '')===$k?'selected':'' ?>><?= e($v) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-5">
              <label class="form-label small fw-semibold">Gün Sayısı</label>
              <input type="number" name="gun_sayisi" class="form-control form-control-sm" value="<?= (int)($edit['gun_sayisi'] ?? 0) ?>" min="0">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Kanal</label>
            <select name="kanal" class="form-select form-select-sm">
              <?php foreach ($kanallar as $k => $v): ?>
                <option value="<?= $k ?>" <?= ($edit['kanal'] ?? 'email')===$k?'selected':'' ?>><?= e($v) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">E-posta Konu</label>
            <input type="text" name="email_konu" class="form-control form-control-sm" value="<?= e($edit['email_konu'] ?? '') ?>" placeholder="Örn. {urun_adi} teklifiniz hk. - {teklif_no}">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">E-posta Gövde</label>
            <textarea name="email_govde" class="form-control form-control-sm" rows="8" placeholder="E-posta içeriği..."><?= e($edit['email_govde'] ?? '') ?></textarea>
            <div class="form-text small">
              Yer tutucular: <code>{ad_soyad}</code> <code>{teklif_no}</code> <code>{urun_adi}</code> <code>{police_no}</code> <code>{bitis_tarihi}</code> <code>{gun}</code> <code>{firma_adi}</code> <code>{telefon}</code>
            </div>
          </div>

          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" name="aktif" id="aktif" <?= ($edit['aktif'] ?? 1) ? 'checked' : '' ?>>
            <label class="form-check-label" for="aktif">Kural aktif</label>
          </div>

          <div class="d-flex gap-2">
            <button class="btn btn-primary fw-semibold"><i class="bi bi-save"></i> Kaydet</button>
            <?php if ($edit): ?>
              <a href="hatirlatma-kurallari.php" class="btn btn-outline-secondary">Vazgeç</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php';
