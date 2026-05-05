<?php
define('MZ_ADMIN', true);
$adminTitle = 'Teklifler';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_helpers.php';

// Toplu islem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toplu_durum') {
    csrf_assert_post();
    $ids   = array_map('intval', $_POST['ids'] ?? []);
    $durum = (string)($_POST['yeni_durum'] ?? '');
    $bildirim_gonder = !empty($_POST['bildirim']);
    $allowed = ['yeni','islemde','teklif_hazir','teklif_gonderildi','onaylandi','police_oldu','iptal','kayip'];
    if ($ids && in_array($durum, $allowed, true)) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        db_exec('UPDATE ' . t('teklifler') . " SET durum=?, guncelleme_tarihi=NOW() WHERE id IN ($in)", array_merge([$durum], $ids));

        // Durum maili (eger checkbox isaretliyse)
        $mailSayisi = 0;
        if ($bildirim_gonder) {
            foreach ($ids as $tid) {
                if (teklif_durum_bildirim_gonder($tid, $durum)) $mailSayisi++;
            }
        }

        audit_log('teklif_toplu_durum', 'teklif', null, json_encode(['ids' => $ids, 'durum' => $durum, 'mail' => $mailSayisi], JSON_UNESCAPED_UNICODE));
        $msg = count($ids) . ' teklifin durumu güncellendi.';
        if ($bildirim_gonder) $msg .= ' ' . $mailSayisi . ' müşteriye bilgilendirme maili gönderildi.';
        admin_redirect('teklifler.php', 'success', $msg);
    }
    admin_redirect('teklifler.php', 'danger', 'Geçersiz işlem.');
}

// Toplu silme (sahte/spam temizligi)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toplu_sil') {
    csrf_assert_post();
    require_role('superadmin', 'admin'); // sadece admin/superadmin silebilir
    $ids = array_map('intval', $_POST['ids'] ?? []);
    if ($ids) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        db_exec('DELETE FROM ' . t('teklifler') . " WHERE id IN ($in)", $ids);
        audit_log('teklif_toplu_sil', 'teklif', null, json_encode(['ids' => $ids, 'count' => count($ids)], JSON_UNESCAPED_UNICODE));
        admin_redirect('teklifler.php', 'success', count($ids) . ' teklif kalıcı olarak silindi.');
    }
    admin_redirect('teklifler.php', 'danger', 'Silinecek teklif seçilmedi.');
}

// Tekil silme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'sil') {
    csrf_assert_post();
    require_role('superadmin', 'admin');
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        db_exec('DELETE FROM ' . t('teklifler') . ' WHERE id=?', [$id]);
        audit_log('teklif_sil', 'teklif', $id);
        admin_redirect('teklifler.php', 'success', 'Teklif silindi.');
    }
    admin_redirect('teklifler.php', 'danger', 'Geçersiz teklif.');
}

// Filtreler
$durum    = admin_filter_input('durum');
$oncelik  = admin_filter_input('oncelik');
$urun_id  = admin_filter_input('urun_id', 'int');
$atanan   = admin_filter_input('atanan', 'int');
$q        = admin_filter_input('q');
$tarih_b  = admin_filter_input('tarih_b');
$tarih_s  = admin_filter_input('tarih_s');

$where = ['1=1'];
$args  = [];
if ($durum)   { $where[] = 't.durum=?';                  $args[] = $durum; }
if ($oncelik) { $where[] = 't.oncelik=?';                $args[] = $oncelik; }
if ($urun_id) { $where[] = 't.urun_id=?';                $args[] = $urun_id; }
if ($atanan)  { $where[] = 't.atanan_kullanici_id=?';    $args[] = $atanan; }
if ($q !== '') {
    $where[] = '(t.teklif_no LIKE ? OR t.ad_soyad LIKE ? OR t.firma_adi LIKE ? OR t.email LIKE ? OR t.telefon LIKE ?)';
    $like = "%$q%"; $args[] = $like; $args[] = $like; $args[] = $like; $args[] = $like; $args[] = $like;
}
if ($tarih_b) { $where[] = 'DATE(t.olusturma_tarihi)>=?'; $args[] = $tarih_b; }
if ($tarih_s) { $where[] = 'DATE(t.olusturma_tarihi)<=?'; $args[] = $tarih_s; }
$wsql = implode(' AND ', $where);

$page  = max(1, (int)($_GET['s'] ?? 1));
$per   = 25;
$total = (int)db_value('SELECT COUNT(*) FROM ' . t('teklifler') . " t WHERE $wsql", $args);
$pag   = paginate($total, $per, $page);

$rows = db_all(
    'SELECT t.*, u.baslik urun_baslik, k.ad_soyad atanan_ad
       FROM ' . t('teklifler') . ' t
       LEFT JOIN ' . t('urunler') . ' u ON u.id=t.urun_id
       LEFT JOIN ' . t('kullanicilar') . ' k ON k.id=t.atanan_kullanici_id
      WHERE ' . $wsql . '
      ORDER BY t.olusturma_tarihi DESC
      LIMIT ' . $per . ' OFFSET ' . $pag['offset'],
    $args
);

$urunler   = db_all('SELECT id, baslik FROM ' . t('urunler') . ' WHERE aktif=1 ORDER BY baslik');
$personel  = db_all('SELECT id, ad_soyad FROM ' . t('kullanicilar') . " WHERE aktif=1 ORDER BY ad_soyad");
?>

<form method="get" class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <div class="row g-2">
      <div class="col-md-3">
        <input type="search" name="q" class="form-control form-control-sm" placeholder="Teklif No, ad, e-posta, telefon..." value="<?= e($q) ?>">
      </div>
      <div class="col-md-2">
        <select name="durum" class="form-select form-select-sm">
          <option value="">Tüm Durumlar</option>
          <?php foreach (['yeni'=>'Yeni','islemde'=>'İşlemde','teklif_hazir'=>'Teklif Hazır','teklif_gonderildi'=>'Gönderildi','onaylandi'=>'Onaylandı','police_oldu'=>'Poliçe Oldu','iptal'=>'İptal','kayip'=>'Kayıp'] as $k=>$v): ?>
            <option value="<?= $k ?>" <?= $durum===$k?'selected':'' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="urun_id" class="form-select form-select-sm">
          <option value="">Tüm Ürünler</option>
          <?php foreach ($urunler as $u): ?>
            <option value="<?= (int)$u['id'] ?>" <?= $urun_id==(int)$u['id']?'selected':'' ?>><?= e($u['baslik']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="oncelik" class="form-select form-select-sm">
          <option value="">Öncelik</option>
          <?php foreach (['dusuk'=>'Düşük','normal'=>'Normal','yuksek'=>'Yüksek','acil'=>'Acil'] as $k=>$v): ?>
            <option value="<?= $k ?>" <?= $oncelik===$k?'selected':'' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="atanan" class="form-select form-select-sm">
          <option value="">Atanan Kişi</option>
          <?php foreach ($personel as $p): ?>
            <option value="<?= (int)$p['id'] ?>" <?= $atanan==(int)$p['id']?'selected':'' ?>><?= e($p['ad_soyad']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-1">
        <button class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i></button>
      </div>
      <div class="col-md-3">
        <input type="date" name="tarih_b" class="form-control form-control-sm" value="<?= e($tarih_b) ?>" placeholder="Başlangıç">
      </div>
      <div class="col-md-3">
        <input type="date" name="tarih_s" class="form-control form-control-sm" value="<?= e($tarih_s) ?>" placeholder="Bitiş">
      </div>
      <div class="col-md-3">
        <a href="teklifler.php" class="btn btn-outline-secondary btn-sm">Temizle</a>
        <span class="text-muted small ms-2">Toplam: <b><?= $total ?></b> kayıt</span>
      </div>
    </div>
  </div>
</form>

<form method="post" id="bulkForm">
<?= csrf_field() ?>
<input type="hidden" name="action" value="toplu_durum">

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th width="30"><input type="checkbox" id="checkAll"></th>
            <th>Teklif No</th>
            <th>Müşteri</th>
            <th>İletişim</th>
            <th>Ürün</th>
            <th>Durum</th>
            <th>Öncelik</th>
            <th>Atanan</th>
            <th>Tarih</th>
            <th width="80"></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><input type="checkbox" name="ids[]" value="<?= (int)$r['id'] ?>" class="ck-row"></td>
            <td><a href="teklif-detay.php?id=<?= (int)$r['id'] ?>" class="fw-semibold text-decoration-none"><?= e($r['teklif_no']) ?></a></td>
            <td class="small">
              <?= e($r['firma_adi'] ?: $r['ad_soyad']) ?>
              <?php if ($r['firma_adi'] && $r['ad_soyad']): ?><br><span class="text-muted"><?= e($r['ad_soyad']) ?></span><?php endif; ?>
            </td>
            <td class="small">
              <a href="tel:<?= e($r['telefon']) ?>" class="text-decoration-none"><i class="bi bi-telephone"></i> <?= e($r['telefon']) ?></a><br>
              <a href="mailto:<?= e($r['email']) ?>" class="text-decoration-none text-muted"><?= e(mb_substr($r['email'], 0, 25)) ?></a>
            </td>
            <td class="small"><?= e($r['urun_baslik'] ?: '-') ?></td>
            <td><?= badge_durum($r['durum']) ?></td>
            <td><?= badge_oncelik($r['oncelik'] ?: 'normal') ?></td>
            <td class="small text-muted"><?= e($r['atanan_ad'] ?: '-') ?></td>
            <td class="small text-muted"><?= tr_datetime($r['olusturma_tarihi']) ?></td>
            <td class="text-nowrap">
              <a href="teklif-detay.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary" title="Görüntüle"><i class="bi bi-eye"></i></a>
              <button type="button" class="btn btn-sm btn-outline-danger ms-1" title="Sil"
                      onclick="if(confirm('“<?= e(addslashes($r['teklif_no'])) ?>” numaralı teklif KALICI olarak silinsin mi?\n\nBu işlem geri alınamaz.')){var f=document.getElementById('singleDelForm');document.getElementById('singleDelId').value='<?= (int)$r['id'] ?>';f.submit();}">
                <i class="bi bi-trash"></i>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
          <tr><td colspan="10" class="text-center text-muted py-5"><i class="bi bi-inbox display-4 d-block"></i>Filtre kriterine uygun teklif bulunamadı.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">
  <div class="d-flex flex-wrap gap-2 align-items-center">
    <select name="yeni_durum" class="form-select form-select-sm" style="width:auto;">
      <option value="">Toplu durum değiştir...</option>
      <?php foreach (['islemde'=>'İşlemde','teklif_hazir'=>'Teklif Hazır','teklif_gonderildi'=>'Gönderildi','onaylandi'=>'Onaylandı','police_oldu'=>'Poliçe Oldu','iptal'=>'İptal','kayip'=>'Kayıp'] as $k=>$v): ?>
        <option value="<?= $k ?>"><?= $v ?></option>
      <?php endforeach; ?>
    </select>

    <div class="form-check form-check-inline ms-1" title="İşaretliyse durum değişikliği maili müşteriye otomatik gönderilir">
      <input class="form-check-input" type="checkbox" name="bildirim" id="ckBildirim" value="1" checked>
      <label class="form-check-label small fw-semibold" for="ckBildirim">
        <i class="bi bi-envelope-check text-primary"></i> Müşteriye mail gönder
      </label>
    </div>

    <button type="button" class="btn btn-sm btn-warning" onclick="bulkSubmit('toplu_durum')"><i class="bi bi-arrow-repeat"></i> Durumu Uygula</button>

    <span class="vr mx-1 d-none d-md-inline"></span>

    <button type="button" class="btn btn-sm btn-outline-danger" onclick="bulkSubmit('toplu_sil')" title="Sahte/spam teklifleri toplu silmek için seçili teklifleri kalıcı olarak sil">
      <i class="bi bi-trash"></i> Seçilenleri Sil
    </button>
  </div>
  <?php if ($pag['pages'] > 1): ?>
    <ul class="pagination pagination-sm mb-0">
      <?php
        $qs = $_GET; unset($qs['s']);
        $base = 'teklifler.php?' . http_build_query($qs);
        for ($i = 1; $i <= $pag['pages']; $i++):
      ?>
        <li class="page-item <?= $i === $pag['page'] ? 'active' : '' ?>">
          <a class="page-link" href="<?= e($base) ?>&s=<?= $i ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  <?php endif; ?>
</div>

</form>

<!-- Tekil silme icin gizli form (her satirdaki cop ikonu bu forma baglanir) -->
<form method="post" id="singleDelForm" style="display:none">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="sil">
  <input type="hidden" name="id" id="singleDelId" value="">
</form>

<script>
document.getElementById('checkAll').addEventListener('change', function(){
  document.querySelectorAll('.ck-row').forEach(c => c.checked = this.checked);
});

// Toplu form submit handler - hangi aksiyon yapilacak
function bulkSubmit(action) {
  var form = document.getElementById('bulkForm');
  var checked = form.querySelectorAll('.ck-row:checked');
  if (checked.length === 0) {
    alert('Lutfen en az bir teklif secin.');
    return;
  }

  if (action === 'toplu_sil') {
    var ok = confirm(checked.length + ' teklif KALICI olarak silinsin mi?\n\nBu islem geri alinamaz.');
    if (!ok) return;
    form.querySelector('input[name="action"]').value = 'toplu_sil';
    form.submit();
    return;
  }

  if (action === 'toplu_durum') {
    var durum = form.querySelector('select[name="yeni_durum"]').value;
    if (!durum) {
      alert('Lutfen bir durum secin.');
      return;
    }
    var bildirim = form.querySelector('input[name="bildirim"]').checked;
    var msg = checked.length + ' teklifin durumu degistirilecek.';
    if (bildirim) msg += '\n\nMusteriye e-posta ile bilgilendirme gonderilecek.';
    msg += '\n\nDevam edilsin mi?';
    if (!confirm(msg)) return;
    form.querySelector('input[name="action"]').value = 'toplu_durum';
    form.submit();
  }
}
</script>

<?php require __DIR__ . '/_footer.php';
