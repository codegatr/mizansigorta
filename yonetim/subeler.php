<?php
/**
 * Mizan Sigorta - Sube Yonetimi
 * yonetim/subeler.php
 *
 * Iletisim sayfasinda gosterilen subelerin (sehir, adres, telefon, email,
 * harita) yonetimi. Genel Merkez vurgulu, diger subeler kart listesi.
 */

define('MZ_ADMIN', true);
$adminTitle = 'Şubeler';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_helpers.php';
require_role('superadmin', 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_assert_post();
    $act = (string)($_POST['action'] ?? '');

    if ($act === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $d = [
            'sehir'             => trim((string)($_POST['sehir'] ?? '')),
            'ilce'              => trim((string)($_POST['ilce'] ?? '')) ?: null,
            'etiket'            => trim((string)($_POST['etiket'] ?? 'Şube Ofis')) ?: 'Şube Ofis',
            'adres'             => trim((string)($_POST['adres'] ?? '')),
            'telefon'           => trim((string)($_POST['telefon'] ?? '')) ?: null,
            'telefon_2'         => trim((string)($_POST['telefon_2'] ?? '')) ?: null,
            'email'             => trim((string)($_POST['email'] ?? '')) ?: null,
            'harita_url'        => trim((string)($_POST['harita_url'] ?? '')) ?: null,
            'calisma_saatleri'  => trim((string)($_POST['calisma_saatleri'] ?? '')) ?: null,
            'merkez_mi'         => isset($_POST['merkez_mi']) ? 1 : 0,
            'sira'              => (int)($_POST['sira'] ?? 0),
            'aktif'             => isset($_POST['aktif']) ? 1 : 0,
        ];
        if ($d['sehir'] === '') admin_redirect('subeler.php', 'danger', 'Şehir zorunlu.');
        if ($d['adres'] === '') admin_redirect('subeler.php', 'danger', 'Adres zorunlu.');
        if ($d['email'] && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) admin_redirect('subeler.php' . ($id ? '?edit=' . $id : ''), 'danger', 'Geçerli bir e-posta giriniz.');

        if ($id) {
            $cols = array_keys($d);
            $set = implode(', ', array_map(fn($c) => "$c=?", $cols));
            db_exec('UPDATE ' . t('subeler') . " SET $set WHERE id=?", array_merge(array_values($d), [$id]));
            audit_log('sube_guncelle', 'sube', $id);
            admin_redirect('subeler.php', 'success', 'Şube güncellendi.');
        } else {
            $cols = array_keys($d);
            $ph = implode(',', array_fill(0, count($cols), '?'));
            db_exec('INSERT INTO ' . t('subeler') . ' (' . implode(',', $cols) . ',olusturma_tarihi) VALUES (' . $ph . ',NOW())', array_values($d));
            audit_log('sube_ekle', 'sube', db_last_id());
            admin_redirect('subeler.php', 'success', 'Şube eklendi.');
        }
    }

    if ($act === 'sil') {
        $id = (int)($_POST['id'] ?? 0);
        db_exec('DELETE FROM ' . t('subeler') . ' WHERE id=?', [$id]);
        audit_log('sube_sil', 'sube', $id);
        admin_redirect('subeler.php', 'success', 'Şube silindi.');
    }

    if ($act === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        db_exec('UPDATE ' . t('subeler') . ' SET aktif = 1-aktif WHERE id=?', [$id]);
        audit_log('sube_toggle', 'sube', $id);
        admin_redirect('subeler.php', 'success', 'Şube durumu değiştirildi.');
    }
}

$rows = db_all('SELECT * FROM ' . t('subeler') . ' ORDER BY merkez_mi DESC, sira ASC, id ASC');
$editId = (int)($_GET['edit'] ?? 0);
$edit = $editId ? db_row('SELECT * FROM ' . t('subeler') . ' WHERE id=?', [$editId]) : null;
?>

<style>
.sube-card { transition: all .15s; border: 1px solid #e5e7eb; }
.sube-card.merkez { border-left: 4px solid #f4d35e; background: linear-gradient(90deg, rgba(244,211,94,.05) 0%, transparent 60%); }
.sube-card:hover { box-shadow: 0 8px 24px rgba(13,27,42,.08); }
.sube-info { font-size: .85rem; color: #6b7280; margin: .35rem 0; }
.sube-info i { color: var(--mz-red); width: 18px; }
</style>

<div class="row g-3">
  <!-- Sol: Form -->
  <div class="col-lg-5">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h5 class="fw-bold mb-3">
          <i class="bi bi-<?= $edit ? 'pencil-square' : 'plus-circle' ?> text-warning"></i>
          <?= $edit ? 'Şube Düzenle: ' . e($edit['sehir']) : 'Yeni Şube Ekle' ?>
        </h5>

        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="save">
          <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>

          <div class="row g-2 mb-2">
            <div class="col-7">
              <label class="form-label small fw-semibold">Şehir <span class="text-danger">*</span></label>
              <input type="text" name="sehir" maxlength="60" required class="form-control form-control-sm" value="<?= e($edit['sehir'] ?? '') ?>" placeholder="İstanbul">
            </div>
            <div class="col-5">
              <label class="form-label small fw-semibold">İlçe</label>
              <input type="text" name="ilce" maxlength="80" class="form-control form-control-sm" value="<?= e($edit['ilce'] ?? '') ?>" placeholder="Karatay">
            </div>
          </div>

          <div class="mb-2">
            <label class="form-label small fw-semibold">Etiket</label>
            <input type="text" name="etiket" maxlength="80" class="form-control form-control-sm" value="<?= e($edit['etiket'] ?? 'Şube Ofis') ?>" placeholder="Genel Merkez / Şube Ofis / Bölge Müdürlüğü">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Adres <span class="text-danger">*</span></label>
            <textarea name="adres" rows="3" required class="form-control form-control-sm" placeholder="Mahalle, Sokak, No, Bina, İlçe / İl"><?= e($edit['adres'] ?? '') ?></textarea>
          </div>

          <hr>
          <h6 class="fw-bold small mb-2"><i class="bi bi-telephone"></i> İletişim</h6>
          <div class="row g-2 mb-2">
            <div class="col-12">
              <label class="form-label small fw-semibold">Birinci Telefon</label>
              <input type="tel" name="telefon" maxlength="40" class="form-control form-control-sm" value="<?= e($edit['telefon'] ?? '') ?>" placeholder="0216 315 26 74">
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold">İkinci Telefon (cep / ek hat)</label>
              <input type="tel" name="telefon_2" maxlength="40" class="form-control form-control-sm" value="<?= e($edit['telefon_2'] ?? '') ?>" placeholder="0530 ...">
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold">E-posta</label>
              <input type="email" name="email" maxlength="120" class="form-control form-control-sm" value="<?= e($edit['email'] ?? '') ?>" placeholder="istanbul@mizansigorta.com.tr">
            </div>
          </div>

          <div class="mb-2">
            <label class="form-label small fw-semibold">Çalışma Saatleri</label>
            <input type="text" name="calisma_saatleri" maxlength="160" class="form-control form-control-sm" value="<?= e($edit['calisma_saatleri'] ?? '') ?>" placeholder="Pzt-Cum 09:00-18:00, Cmt 10:00-14:00">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Google Maps URL <span class="text-muted">(opsiyonel)</span></label>
            <input type="url" name="harita_url" maxlength="500" class="form-control form-control-sm" value="<?= e($edit['harita_url'] ?? '') ?>" placeholder="https://maps.google.com/?cid=...">
            <small class="form-text text-muted">Boş bırakırsan adresten otomatik harita araması yapılır.</small>
          </div>

          <hr>
          <div class="row g-2 mb-3">
            <div class="col-4">
              <label class="form-label small fw-semibold">Sıra</label>
              <input type="number" name="sira" min="0" class="form-control form-control-sm" value="<?= (int)($edit['sira'] ?? 0) ?>">
            </div>
            <div class="col-4 d-flex align-items-end">
              <div class="form-check form-switch">
                <input type="checkbox" name="merkez_mi" id="merkez_mi" class="form-check-input" <?= !empty($edit['merkez_mi']) ? 'checked' : '' ?>>
                <label for="merkez_mi" class="form-check-label small fw-semibold">Genel Merkez</label>
              </div>
            </div>
            <div class="col-4 d-flex align-items-end">
              <div class="form-check form-switch">
                <input type="checkbox" name="aktif" id="aktif" class="form-check-input" <?= !$edit || $edit['aktif'] ? 'checked' : '' ?>>
                <label for="aktif" class="form-check-label small fw-semibold">Aktif</label>
              </div>
            </div>
          </div>

          <div class="d-flex gap-2">
            <button class="btn btn-primary btn-sm fw-semibold flex-grow-1">
              <i class="bi bi-save"></i> <?= $edit ? 'Güncelle' : 'Şube Ekle' ?>
            </button>
            <?php if ($edit): ?>
              <a href="subeler.php" class="btn btn-outline-secondary btn-sm">İptal</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Sag: Liste -->
  <div class="col-lg-7">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h6 class="mb-0 small text-muted">Toplam <?= count($rows) ?> şube — <?= count(array_filter($rows, fn($r) => $r['aktif'])) ?> aktif</h6>
      <a href="<?= u('/iletisim') ?>" target="_blank" class="btn btn-outline-primary btn-sm"><i class="bi bi-eye"></i> Sitede Görüntüle</a>
    </div>

    <?php if (!$rows): ?>
      <div class="alert alert-info"><i class="bi bi-info-circle"></i> Henüz şube eklenmemiş.</div>
    <?php endif; ?>

    <div class="d-flex flex-column gap-2">
      <?php foreach ($rows as $r): ?>
        <div class="card sube-card<?= $r['merkez_mi'] ? ' merkez' : '' ?>">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2 gap-2 flex-wrap">
              <div>
                <h6 class="fw-bold mb-0">
                  <?php if ($r['merkez_mi']): ?><i class="bi bi-gem text-warning"></i> <?php endif; ?>
                  <?= e($r['sehir']) ?><?php if ($r['ilce']): ?> <small class="text-muted">· <?= e($r['ilce']) ?></small><?php endif; ?>
                </h6>
                <small class="text-muted"><?= e($r['etiket']) ?> · sıra <?= (int)$r['sira'] ?> · #<?= (int)$r['id'] ?></small>
              </div>
              <div class="slayt-actions d-flex gap-1 flex-wrap">
                <a href="?edit=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>

                <form method="post" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button class="btn btn-sm btn-outline-<?= $r['aktif'] ? 'success' : 'secondary' ?>" title="<?= $r['aktif'] ? 'Aktif' : 'Pasif' ?>">
                    <i class="bi bi-<?= $r['aktif'] ? 'eye-fill' : 'eye-slash' ?>"></i>
                  </button>
                </form>

                <form method="post" class="d-inline" onsubmit="return confirm('“<?= e(addslashes($r['sehir'])) ?>” şubesi silinsin mi?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="sil">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
              </div>
            </div>

            <p class="sube-info mb-1"><i class="bi bi-geo-alt-fill"></i> <?= e($r['adres']) ?></p>
            <?php if ($r['telefon']): ?>
              <p class="sube-info mb-1"><i class="bi bi-telephone-fill"></i> <?= e($r['telefon']) ?><?php if ($r['telefon_2']): ?> · <?= e($r['telefon_2']) ?><?php endif; ?></p>
            <?php endif; ?>
            <?php if ($r['email']): ?>
              <p class="sube-info mb-1"><i class="bi bi-envelope-fill"></i> <?= e($r['email']) ?></p>
            <?php endif; ?>
            <?php if ($r['calisma_saatleri']): ?>
              <p class="sube-info mb-0"><i class="bi bi-clock-fill"></i> <?= e($r['calisma_saatleri']) ?></p>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php';
