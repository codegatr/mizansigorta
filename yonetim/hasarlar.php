<?php
define('MZ_ADMIN', true);
$adminTitle = 'Hasar Dosyaları';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_assert_post();
    $act = (string)($_POST['action'] ?? '');

    if ($act === 'durum_degistir') {
        $id    = (int)($_POST['id'] ?? 0);
        $durum = (string)($_POST['durum'] ?? '');
        $okDurums = ['yeni','inceleniyor','eksiltme_talep','onaylandi','reddedildi','tamamlandi'];
        if ($id && in_array($durum, $okDurums, true)) {
            db_exec('UPDATE ' . t('hasarlar') . ' SET durum=?, guncelleme_tarihi=NOW() WHERE id=?', [$durum, $id]);
            audit_log('hasar_durum', 'hasar', $id, "durum=$durum");
            admin_redirect('hasarlar.php?detay=' . $id, 'success', 'Durum güncellendi.');
        }
    }

    if ($act === 'atama') {
        $id   = (int)($_POST['id'] ?? 0);
        $kid  = (int)($_POST['atanan_kullanici_id'] ?? 0) ?: null;
        if ($id) {
            db_exec('UPDATE ' . t('hasarlar') . ' SET atanan_kullanici_id=?, guncelleme_tarihi=NOW() WHERE id=?', [$kid, $id]);
            audit_log('hasar_atama', 'hasar', $id);
            admin_redirect('hasarlar.php?detay=' . $id, 'success', 'Atama güncellendi.');
        }
    }

    if ($act === 'sil') {
        $id = (int)($_POST['id'] ?? 0);
        // dosya eklerini de sil
        $ekler = db_all('SELECT * FROM ' . t('hasar_ekleri') . ' WHERE hasar_id=?', [$id]);
        foreach ($ekler as $ek) {
            $p = MIZAN_UPLOADS . '/hasar/' . basename($ek['dosya_yolu']);
            if (is_file($p)) @unlink($p);
        }
        db_exec('DELETE FROM ' . t('hasar_ekleri') . ' WHERE hasar_id=?', [$id]);
        db_exec('DELETE FROM ' . t('hasarlar') . ' WHERE id=?', [$id]);
        audit_log('hasar_sil', 'hasar', $id);
        admin_redirect('hasarlar.php', 'success', 'Hasar dosyası silindi.');
    }

    if ($act === 'manuel_ekle') {
        $d = [
            'dosya_no'      => generate_no(setting('hasar_otomatik_no', 'HSR') ?: 'HSR'),
            'ad_soyad'      => trim((string)($_POST['ad_soyad'] ?? '')),
            'email'         => trim((string)($_POST['email'] ?? '')),
            'telefon'       => normalize_phone((string)($_POST['telefon'] ?? '')),
            'urun_id'       => (int)($_POST['urun_id'] ?? 0) ?: null,
            'olay_tarihi'   => $_POST['olay_tarihi'] ?? null,
            'olay_yeri'     => trim((string)($_POST['olay_yeri'] ?? '')),
            'olay_aciklama' => trim((string)($_POST['olay_aciklama'] ?? '')),
            'tahmini_zarar' => (float)($_POST['tahmini_zarar'] ?? 0) ?: null,
            'durum'         => 'yeni',
        ];
        $d['olay_tarihi'] = $d['olay_tarihi'] ?: null;
        if (!$d['ad_soyad'] || !$d['olay_aciklama']) admin_redirect('hasarlar.php', 'danger', 'Ad soyad ve olay açıklaması zorunlu.');
        db_exec(
            'INSERT INTO ' . t('hasarlar') . ' (dosya_no,ad_soyad,email,telefon,urun_id,olay_tarihi,olay_yeri,olay_aciklama,tahmini_zarar,durum,olusturma_tarihi) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())',
            array_values($d)
        );
        $newId = db_last_id();
        audit_log('hasar_manuel_ekle', 'hasar', $newId);
        admin_redirect('hasarlar.php?detay=' . $newId, 'success', 'Hasar dosyası oluşturuldu.');
    }
}

$detayId = (int)($_GET['detay'] ?? 0);

// Detay sayfasi
if ($detayId) {
    $h = db_row('SELECT h.*, u.baslik AS urun_adi, k.ad_soyad AS atanan_adi
                 FROM ' . t('hasarlar') . ' h
                 LEFT JOIN ' . t('urunler') . ' u ON u.id = h.urun_id
                 LEFT JOIN ' . t('kullanicilar') . ' k ON k.id = h.atanan_kullanici_id
                 WHERE h.id=?', [$detayId]);
    if (!$h) admin_redirect('hasarlar.php', 'danger', 'Hasar dosyası bulunamadı.');
    $ekler = db_all('SELECT * FROM ' . t('hasar_ekleri') . ' WHERE hasar_id=? ORDER BY id DESC', [$detayId]);
    $kullanicilar = db_all('SELECT id, ad_soyad FROM ' . t('kullanicilar') . " WHERE aktif=1 ORDER BY ad_soyad");
    ?>
    <div class="mb-3">
      <a href="hasarlar.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Hasar Listesi</a>
    </div>
    <div class="row g-3">
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div>
                <div class="text-muted small">Dosya No</div>
                <h4 class="fw-bold mb-0"><?= e($h['dosya_no']) ?></h4>
              </div>
              <div class="text-end">
                <?= badge_durum($h['durum']) ?>
                <div class="text-muted small mt-1"><i class="bi bi-clock"></i> <?= tr_datetime($h['olusturma_tarihi']) ?></div>
              </div>
            </div>
            <hr>
            <div class="row g-3">
              <div class="col-md-6"><div class="text-muted small">Ad Soyad</div><div class="fw-semibold"><?= e($h['ad_soyad']) ?></div></div>
              <div class="col-md-6"><div class="text-muted small">Ürün</div><div class="fw-semibold"><?= e($h['urun_adi'] ?? '-') ?></div></div>
              <div class="col-md-6"><div class="text-muted small">Telefon</div><div><a href="tel:<?= e($h['telefon']) ?>"><?= e($h['telefon'] ?: '-') ?></a></div></div>
              <div class="col-md-6"><div class="text-muted small">E-posta</div><div><?= $h['email'] ? '<a href="mailto:'.e($h['email']).'">'.e($h['email']).'</a>' : '-' ?></div></div>
              <div class="col-md-6"><div class="text-muted small">Olay Tarihi</div><div><?= tr_date($h['olay_tarihi']) ?></div></div>
              <div class="col-md-6"><div class="text-muted small">Tahmini Zarar</div><div><?= $h['tahmini_zarar'] ? tr_money((float)$h['tahmini_zarar']) : '-' ?></div></div>
              <div class="col-12"><div class="text-muted small">Olay Yeri</div><div><?= e($h['olay_yeri'] ?: '-') ?></div></div>
              <div class="col-12"><div class="text-muted small">Olay Açıklaması</div><div class="border rounded p-3 bg-light"><?= nl2br(e($h['olay_aciklama'])) ?></div></div>
            </div>
          </div>
        </div>

        <?php if ($ekler): ?>
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="bi bi-paperclip text-warning"></i> Ekli Dosyalar (<?= count($ekler) ?>)</h6>
            <div class="row g-2">
              <?php foreach ($ekler as $ek): ?>
                <div class="col-md-6">
                  <a href="<?= u('uploads/hasar/' . rawurlencode($ek['dosya_yolu'])) ?>" target="_blank" class="d-flex align-items-center p-2 border rounded text-decoration-none">
                    <i class="bi bi-file-earmark-text fs-3 text-warning me-2"></i>
                    <div class="small">
                      <div class="fw-semibold text-dark"><?= e($ek['dosya_adi'] ?: $ek['dosya_yolu']) ?></div>
                      <div class="text-muted"><?= number_format(($ek['boyut'] ?? 0)/1024, 1) ?> KB · <?= e($ek['mime'] ?? '') ?></div>
                    </div>
                  </a>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="bi bi-arrow-repeat text-warning"></i> Durum Değiştir</h6>
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="durum_degistir">
              <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
              <select name="durum" class="form-select form-select-sm mb-2" onchange="this.form.submit()">
                <?php foreach (['yeni','inceleniyor','eksiltme_talep','onaylandi','reddedildi','tamamlandi'] as $d): ?>
                  <option value="<?= $d ?>" <?= $h['durum']===$d?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$d)) ?></option>
                <?php endforeach; ?>
              </select>
            </form>
          </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
          <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="bi bi-person-check text-warning"></i> Kullanıcıya Ata</h6>
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="atama">
              <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
              <select name="atanan_kullanici_id" class="form-select form-select-sm mb-2" onchange="this.form.submit()">
                <option value="">— Atanmamış —</option>
                <?php foreach ($kullanicilar as $u): ?>
                  <option value="<?= (int)$u['id'] ?>" <?= ($h['atanan_kullanici_id'] ?? 0)==$u['id']?'selected':'' ?>><?= e($u['ad_soyad']) ?></option>
                <?php endforeach; ?>
              </select>
            </form>
          </div>
        </div>

        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="bi bi-lightning text-warning"></i> Hızlı İletişim</h6>
            <?php if ($h['telefon']): ?>
              <a href="tel:<?= e($h['telefon']) ?>" class="btn btn-sm btn-success w-100 mb-1"><i class="bi bi-telephone"></i> Ara</a>
              <a href="https://wa.me/<?= e(preg_replace('/[^0-9]/','',$h['telefon'])) ?>" target="_blank" class="btn btn-sm btn-outline-success w-100 mb-1"><i class="bi bi-whatsapp"></i> WhatsApp</a>
            <?php endif; ?>
            <?php if ($h['email']): ?>
              <a href="mailto:<?= e($h['email']) ?>" class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-envelope"></i> E-posta</a>
            <?php endif; ?>
            <hr>
            <form method="post" onsubmit="return confirm('Hasar dosyasını silmek istediğinize emin misiniz? Bu işlem geri alınamaz.');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="sil">
              <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
              <button class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-trash"></i> Hasar Dosyasını Sil</button>
            </form>
          </div>
        </div>
      </div>
    </div>
    <?php
    require __DIR__ . '/_footer.php';
    return;
}

// Liste sayfasi
$q     = trim((string)($_GET['q'] ?? ''));
$durum = (string)($_GET['durum'] ?? '');
$where = ['1=1']; $args = [];
if ($q !== '') { $where[] = '(h.dosya_no LIKE ? OR h.ad_soyad LIKE ? OR h.telefon LIKE ? OR h.email LIKE ?)'; $like = "%$q%"; $args = [$like,$like,$like,$like]; }
if ($durum)    { $where[] = 'h.durum=?'; $args[] = $durum; }
$wsql = implode(' AND ', $where);

$page  = max(1, (int)($_GET['s'] ?? 1));
$per   = 30;
$total = (int)db_value('SELECT COUNT(*) FROM ' . t('hasarlar') . " h WHERE $wsql", $args);
$pag   = paginate($total, $per, $page);
$rows  = db_all('SELECT h.*, u.baslik AS urun_adi
                 FROM ' . t('hasarlar') . ' h
                 LEFT JOIN ' . t('urunler') . ' u ON u.id = h.urun_id
                 WHERE ' . $wsql . ' ORDER BY h.olusturma_tarihi DESC LIMIT ' . $per . ' OFFSET ' . $pag['offset'], $args);

$urunler = db_all('SELECT id, baslik FROM ' . t('urunler') . ' WHERE aktif=1 ORDER BY sira, baslik');
?>

<div class="row g-3 mb-3">
  <div class="col-md-3">
    <div class="mz-mini-stat bg-warning-subtle border-warning">
      <div class="text-muted small">Yeni</div>
      <div class="fw-bold fs-4"><?= (int)db_value('SELECT COUNT(*) FROM ' . t('hasarlar') . " WHERE durum='yeni'") ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="mz-mini-stat bg-info-subtle border-info">
      <div class="text-muted small">İnceleniyor</div>
      <div class="fw-bold fs-4"><?= (int)db_value('SELECT COUNT(*) FROM ' . t('hasarlar') . " WHERE durum='inceleniyor'") ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="mz-mini-stat bg-success-subtle border-success">
      <div class="text-muted small">Tamamlandı</div>
      <div class="fw-bold fs-4"><?= (int)db_value('SELECT COUNT(*) FROM ' . t('hasarlar') . " WHERE durum='tamamlandi'") ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="mz-mini-stat bg-secondary-subtle border-secondary">
      <div class="text-muted small">Toplam</div>
      <div class="fw-bold fs-4"><?= (int)db_value('SELECT COUNT(*) FROM ' . t('hasarlar')) ?></div>
    </div>
  </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
  <small class="text-muted">Toplam <b><?= $total ?></b> kayıt</small>
  <button class="btn btn-sm btn-warning fw-semibold" data-bs-toggle="modal" data-bs-target="#yeniHasarModal"><i class="bi bi-plus-lg"></i> Manuel Hasar Ekle</button>
</div>

<form method="get" class="card border-0 shadow-sm mb-3">
  <div class="card-body p-3">
    <div class="row g-2">
      <div class="col-md-6"><input type="search" name="q" class="form-control form-control-sm" placeholder="Dosya no, ad, telefon, e-posta..." value="<?= e($q) ?>"></div>
      <div class="col-md-3">
        <select name="durum" class="form-select form-select-sm">
          <option value="">Tüm Durumlar</option>
          <?php foreach (['yeni','inceleniyor','eksiltme_talep','onaylandi','reddedildi','tamamlandi'] as $d): ?>
            <option value="<?= $d ?>" <?= $durum===$d?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$d)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3"><button class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i> Filtrele</button></div>
    </div>
  </div>
</form>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Dosya No</th><th>Ad Soyad</th><th>Ürün</th><th>İletişim</th><th>Durum</th><th>Olay Tarihi</th><th>Tarih</th><th width="80"></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><a href="?detay=<?= (int)$r['id'] ?>" class="fw-bold text-decoration-none"><?= e($r['dosya_no']) ?></a></td>
            <td><?= e($r['ad_soyad']) ?></td>
            <td class="small"><?= e($r['urun_adi'] ?? '-') ?></td>
            <td class="small"><?= e($r['telefon']) ?><br><span class="text-muted"><?= e($r['email']) ?></span></td>
            <td><?= badge_durum($r['durum']) ?></td>
            <td class="small"><?= tr_date($r['olay_tarihi']) ?></td>
            <td class="small text-muted"><?= tr_date($r['olusturma_tarihi']) ?></td>
            <td><a class="btn btn-sm btn-outline-primary" href="?detay=<?= (int)$r['id'] ?>"><i class="bi bi-eye"></i></a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="8" class="text-muted text-center py-5">Henüz hasar dosyası yok.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if ($pag['pages'] > 1): ?>
  <nav class="mt-3"><ul class="pagination pagination-sm justify-content-center">
  <?php $qs = $_GET; unset($qs['s']); $b = 'hasarlar.php?' . http_build_query($qs);
    for ($i = 1; $i <= $pag['pages']; $i++): ?>
    <li class="page-item <?= $i === $pag['page'] ? 'active' : '' ?>"><a class="page-link" href="<?= e($b) ?>&s=<?= $i ?>"><?= $i ?></a></li>
  <?php endfor; ?></ul></nav>
<?php endif; ?>

<!-- Manuel Hasar Modal -->
<div class="modal fade" id="yeniHasarModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="manuel_ekle">
        <div class="modal-header bg-warning"><h5 class="modal-title"><i class="bi bi-plus-square"></i> Manuel Hasar Dosyası</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="row g-2">
            <div class="col-md-6"><label class="form-label small">Ad Soyad *</label><input type="text" name="ad_soyad" class="form-control form-control-sm" required></div>
            <div class="col-md-6"><label class="form-label small">Ürün</label>
              <select name="urun_id" class="form-select form-select-sm">
                <option value="">Seçiniz</option>
                <?php foreach ($urunler as $u): ?><option value="<?= (int)$u['id'] ?>"><?= e($u['baslik']) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6"><label class="form-label small">Telefon</label><input type="tel" name="telefon" class="form-control form-control-sm"></div>
            <div class="col-md-6"><label class="form-label small">E-posta</label><input type="email" name="email" class="form-control form-control-sm"></div>
            <div class="col-md-6"><label class="form-label small">Olay Tarihi</label><input type="date" name="olay_tarihi" class="form-control form-control-sm"></div>
            <div class="col-md-6"><label class="form-label small">Tahmini Zarar (₺)</label><input type="number" step="0.01" name="tahmini_zarar" class="form-control form-control-sm"></div>
            <div class="col-12"><label class="form-label small">Olay Yeri</label><input type="text" name="olay_yeri" class="form-control form-control-sm"></div>
            <div class="col-12"><label class="form-label small">Olay Açıklaması *</label><textarea name="olay_aciklama" rows="4" class="form-control form-control-sm" required></textarea></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Vazgeç</button>
          <button class="btn btn-warning btn-sm fw-semibold"><i class="bi bi-save"></i> Kaydet</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php';
