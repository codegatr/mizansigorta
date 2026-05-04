<?php
define('MZ_ADMIN', true);
$adminTitle = 'Temsilci Başvuruları';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_assert_post();
    $act = (string)($_POST['action'] ?? '');

    if ($act === 'durum') {
        $id = (int)($_POST['id'] ?? 0);
        $durum = (string)($_POST['durum'] ?? '');
        $allowed = ['yeni', 'degerlendirme', 'goruseme', 'kabul', 'red', 'iptal'];
        if (!in_array($durum, $allowed, true)) admin_redirect('bayi-basvurulari.php', 'danger', 'Geçersiz durum.');
        db_exec('UPDATE ' . t('bayi_basvurulari') . ' SET durum=?, okundu=1 WHERE id=?', [$durum, $id]);
        audit_log('bayi_durum', 'bayi', $id, "Durum: $durum");
        admin_redirect('bayi-basvurulari.php?detay=' . $id, 'success', 'Durum güncellendi.');
    }

    if ($act === 'not_kaydet') {
        $id    = (int)($_POST['id'] ?? 0);
        $notlar = (string)($_POST['notlar'] ?? '');
        db_exec('UPDATE ' . t('bayi_basvurulari') . ' SET notlar=?, okundu=1 WHERE id=?', [$notlar, $id]);
        audit_log('bayi_not', 'bayi', $id);
        admin_redirect('bayi-basvurulari.php?detay=' . $id, 'success', 'Not kaydedildi.');
    }

    if ($act === 'sil') {
        $id = (int)($_POST['id'] ?? 0);
        db_exec('DELETE FROM ' . t('bayi_basvurulari') . ' WHERE id=?', [$id]);
        audit_log('bayi_sil', 'bayi', $id);
        admin_redirect('bayi-basvurulari.php', 'success', 'Başvuru silindi.');
    }
}

$detayId = (int)($_GET['detay'] ?? 0);

if ($detayId) {
    $b = db_row('SELECT * FROM ' . t('bayi_basvurulari') . ' WHERE id=?', [$detayId]);
    if (!$b) admin_redirect('bayi-basvurulari.php', 'danger', 'Başvuru bulunamadı.');
    if (!$b['okundu']) db_exec('UPDATE ' . t('bayi_basvurulari') . ' SET okundu=1 WHERE id=?', [$detayId]);

    $durumlar = ['yeni' => 'Yeni', 'degerlendirme' => 'Değerlendirme', 'goruseme' => 'Görüşme', 'kabul' => 'Kabul', 'red' => 'Red', 'iptal' => 'İptal'];
    $rb = ['yeni' => 'warning text-dark', 'degerlendirme' => 'info', 'goruseme' => 'primary', 'kabul' => 'success', 'red' => 'danger', 'iptal' => 'secondary'][$b['durum']] ?? 'secondary';
    ?>
    <div class="mb-3"><a href="bayi-basvurulari.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Liste</a></div>
    <div class="row g-3">
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div>
                <h4 class="mb-1"><?= e($b['ad_soyad']) ?></h4>
                <?php if ($b['firma_adi']): ?><div class="text-muted"><?= e($b['firma_adi']) ?></div><?php endif; ?>
              </div>
              <div class="text-end">
                <span class="badge bg-<?= $rb ?>"><?= e($durumlar[$b['durum']]) ?></span>
                <div class="small text-muted mt-1"><?= tr_datetime($b['olusturma_tarihi']) ?></div>
              </div>
            </div>
            <hr>
            <dl class="row mb-0 small">
              <dt class="col-sm-3 text-muted">E-posta</dt><dd class="col-sm-9"><a href="mailto:<?= e($b['email']) ?>"><?= e($b['email']) ?></a></dd>
              <dt class="col-sm-3 text-muted">Telefon</dt><dd class="col-sm-9"><a href="tel:<?= e($b['telefon']) ?>"><?= e($b['telefon']) ?></a> <a href="https://wa.me/<?= e(preg_replace('/[^0-9]/', '', $b['telefon'])) ?>" target="_blank" class="ms-2 text-success small"><i class="bi bi-whatsapp"></i> WhatsApp</a></dd>
              <dt class="col-sm-3 text-muted">Şehir</dt><dd class="col-sm-9"><?= e($b['il']) ?><?= $b['ilce'] ? ' / ' . e($b['ilce']) : '' ?></dd>
              <dt class="col-sm-3 text-muted">Tecrübe</dt><dd class="col-sm-9"><?= (int)$b['tecrube_yili'] ?> yıl</dd>
              <?php if ($b['levha_no']): ?><dt class="col-sm-3 text-muted">Levha No</dt><dd class="col-sm-9"><code><?= e($b['levha_no']) ?></code></dd><?php endif; ?>
              <?php if ($b['mevcut_acentelik']): ?><dt class="col-sm-3 text-muted">Mevcut Acentelik</dt><dd class="col-sm-9"><?= e($b['mevcut_acentelik']) ?></dd><?php endif; ?>
              <dt class="col-sm-3 text-muted">KVKK</dt><dd class="col-sm-9"><?= $b['kvkk_onay'] ? '<span class="badge bg-success">Onaylı</span>' : '<span class="badge bg-warning">Onaysız</span>' ?></dd>
              <dt class="col-sm-3 text-muted">IP</dt><dd class="col-sm-9 text-muted"><?= e($b['ip_adresi']) ?></dd>
            </dl>
            <?php if ($b['aciklama']): ?>
              <hr>
              <h6 class="fw-bold">Açıklama / Mesaj</h6>
              <div class="border rounded p-3 bg-light"><?= nl2br(e($b['aciklama'])) ?></div>
            <?php endif; ?>
          </div>
        </div>

        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="bi bi-pencil-square text-warning"></i> Notlar</h6>
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="not_kaydet">
              <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
              <textarea name="notlar" rows="5" class="form-control"><?= e($b['notlar']) ?></textarea>
              <button class="btn btn-warning btn-sm fw-semibold mt-2"><i class="bi bi-save"></i> Notu Kaydet</button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="bi bi-funnel text-warning"></i> Durum</h6>
            <form method="post" class="d-flex gap-2">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="durum">
              <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
              <select name="durum" class="form-select form-select-sm">
                <?php foreach ($durumlar as $k => $v): ?>
                  <option value="<?= $k ?>" <?= $b['durum'] === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                <?php endforeach; ?>
              </select>
              <button class="btn btn-warning btn-sm" data-no-spinner><i class="bi bi-check-lg"></i></button>
            </form>
          </div>
        </div>

        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="bi bi-trash text-danger"></i> Tehlikeli Bölge</h6>
            <form method="post" onsubmit="return confirm('Bu başvuru kalıcı olarak silinsin mi?');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="sil">
              <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
              <button class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-trash"></i> Başvuruyu Sil</button>
            </form>
          </div>
        </div>
      </div>
    </div>
    <?php
    require __DIR__ . '/_footer.php';
    return;
}

// Liste
$durum = (string)($_GET['durum'] ?? '');
$where = ['1=1']; $args = [];
if ($durum !== '' && in_array($durum, ['yeni', 'degerlendirme', 'goruseme', 'kabul', 'red', 'iptal'], true)) {
    $where[] = 'durum=?'; $args[] = $durum;
}
$wsql = implode(' AND ', $where);

$page  = max(1, (int)($_GET['s'] ?? 1));
$per   = 30;
$total = (int)db_value('SELECT COUNT(*) FROM ' . t('bayi_basvurulari') . " WHERE $wsql", $args);
$pag   = paginate($total, $per, $page);
$rows  = db_all('SELECT * FROM ' . t('bayi_basvurulari') . " WHERE $wsql ORDER BY olusturma_tarihi DESC LIMIT $per OFFSET " . $pag['offset'], $args);

$counts = [];
foreach (db_all('SELECT durum, COUNT(*) AS c FROM ' . t('bayi_basvurulari') . ' GROUP BY durum') as $c) $counts[$c['durum']] = (int)$c['c'];
?>

<div class="d-flex flex-wrap gap-2 mb-3">
  <a href="?" class="btn btn-sm <?= $durum === '' ? 'btn-primary' : 'btn-outline-primary' ?>">Tümü <span class="badge bg-light text-dark"><?= array_sum($counts) ?></span></a>
  <a href="?durum=yeni" class="btn btn-sm <?= $durum === 'yeni' ? 'btn-warning' : 'btn-outline-warning' ?>">Yeni <span class="badge bg-light text-dark"><?= $counts['yeni'] ?? 0 ?></span></a>
  <a href="?durum=degerlendirme" class="btn btn-sm <?= $durum === 'degerlendirme' ? 'btn-info' : 'btn-outline-info' ?>">Değerlendirme <span class="badge bg-light text-dark"><?= $counts['degerlendirme'] ?? 0 ?></span></a>
  <a href="?durum=goruseme" class="btn btn-sm <?= $durum === 'goruseme' ? 'btn-primary' : 'btn-outline-primary' ?>">Görüşme <span class="badge bg-light text-dark"><?= $counts['goruseme'] ?? 0 ?></span></a>
  <a href="?durum=kabul" class="btn btn-sm <?= $durum === 'kabul' ? 'btn-success' : 'btn-outline-success' ?>">Kabul <span class="badge bg-light text-dark"><?= $counts['kabul'] ?? 0 ?></span></a>
  <a href="?durum=red" class="btn btn-sm <?= $durum === 'red' ? 'btn-danger' : 'btn-outline-danger' ?>">Red <span class="badge bg-light text-dark"><?= $counts['red'] ?? 0 ?></span></a>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Başvuran</th><th>İletişim</th><th>Şehir</th><th>Tecrübe</th><th>Durum</th><th>Tarih</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <?php
            $rb = ['yeni' => 'warning text-dark', 'degerlendirme' => 'info', 'goruseme' => 'primary', 'kabul' => 'success', 'red' => 'danger', 'iptal' => 'secondary'][$r['durum']] ?? 'secondary';
          ?>
          <tr <?= !$r['okundu'] ? 'class="fw-bold"' : '' ?>>
            <td>
              <?php if (!$r['okundu']): ?><span class="badge bg-warning text-dark me-1">Yeni</span><?php endif; ?>
              <b><?= e($r['ad_soyad']) ?></b>
              <?php if ($r['firma_adi']): ?><div class="small text-muted"><?= e($r['firma_adi']) ?></div><?php endif; ?>
            </td>
            <td class="small"><?= e($r['email']) ?><br><span class="text-muted"><?= e($r['telefon']) ?></span></td>
            <td class="small"><?= e($r['il']) ?></td>
            <td class="small"><?= (int)$r['tecrube_yili'] ?> yıl</td>
            <td><span class="badge bg-<?= $rb ?>"><?= e(['yeni' => 'Yeni', 'degerlendirme' => 'Değerlendirme', 'goruseme' => 'Görüşme', 'kabul' => 'Kabul', 'red' => 'Red', 'iptal' => 'İptal'][$r['durum']] ?? $r['durum']) ?></span></td>
            <td class="small text-muted"><?= tr_date($r['olusturma_tarihi']) ?></td>
            <td><a class="btn btn-sm btn-outline-primary" href="?detay=<?= (int)$r['id'] ?>"><i class="bi bi-arrow-right"></i></a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="7" class="text-muted text-center py-5">Başvuru yok.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if ($pag['pages'] > 1): ?>
  <nav class="mt-3"><ul class="pagination pagination-sm justify-content-center">
  <?php $qs = $_GET; unset($qs['s']); $b = 'bayi-basvurulari.php?' . http_build_query($qs);
    for ($i = 1; $i <= $pag['pages']; $i++): ?>
    <li class="page-item <?= $i === $pag['page'] ? 'active' : '' ?>"><a class="page-link" href="<?= e($b) ?>&s=<?= $i ?>"><?= $i ?></a></li>
  <?php endfor; ?></ul></nav>
<?php endif; ?>

<?php require __DIR__ . '/_footer.php';
