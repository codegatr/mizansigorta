<?php
define('MZ_ADMIN', true);
$adminTitle = 'İletişim Mesajları';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_assert_post();
    $act = (string)($_POST['action'] ?? '');

    if ($act === 'okundu') {
        $id = (int)($_POST['id'] ?? 0);
        db_exec('UPDATE ' . t('iletisim_mesajlari') . ' SET okundu=1 WHERE id=?', [$id]);
        admin_redirect('iletisim-mesajlari.php?detay=' . $id, 'success', 'Mesaj okundu olarak işaretlendi.');
    }

    if ($act === 'cevapla') {
        $id     = (int)($_POST['id'] ?? 0);
        $konu   = trim((string)($_POST['konu'] ?? ''));
        $govde  = (string)($_POST['govde'] ?? '');
        $msg = db_row('SELECT * FROM ' . t('iletisim_mesajlari') . ' WHERE id=?', [$id]);
        if (!$msg) admin_redirect('iletisim-mesajlari.php', 'danger', 'Mesaj bulunamadı.');
        if (!$msg['email']) admin_redirect('iletisim-mesajlari.php?detay=' . $id, 'danger', 'Bu mesajda e-posta adresi yok.');
        if ($konu === '' || $govde === '') admin_redirect('iletisim-mesajlari.php?detay=' . $id, 'danger', 'Konu ve cevap zorunlu.');

        $html = mail_template($konu, '<p>' . nl2br(e($govde)) . '</p>');
        $ok = send_mail($msg['email'], $msg['ad_soyad'] ?: 'Sayın ziyaretçi', $konu, $html);
        if ($ok) {
            db_exec('UPDATE ' . t('iletisim_mesajlari') . ' SET cevaplandi=1, okundu=1 WHERE id=?', [$id]);
            audit_log('iletisim_cevap', 'iletisim', $id);
            admin_redirect('iletisim-mesajlari.php?detay=' . $id, 'success', 'Cevap e-postası gönderildi.');
        } else {
            admin_redirect('iletisim-mesajlari.php?detay=' . $id, 'danger', 'E-posta gönderilemedi. SMTP ayarlarını kontrol edin.');
        }
    }

    if ($act === 'sil') {
        $id = (int)($_POST['id'] ?? 0);
        db_exec('DELETE FROM ' . t('iletisim_mesajlari') . ' WHERE id=?', [$id]);
        audit_log('iletisim_sil', 'iletisim', $id);
        admin_redirect('iletisim-mesajlari.php', 'success', 'Mesaj silindi.');
    }
}

$detayId = (int)($_GET['detay'] ?? 0);

if ($detayId) {
    $m = db_row('SELECT * FROM ' . t('iletisim_mesajlari') . ' WHERE id=?', [$detayId]);
    if (!$m) admin_redirect('iletisim-mesajlari.php', 'danger', 'Mesaj bulunamadı.');
    if (!$m['okundu']) db_exec('UPDATE ' . t('iletisim_mesajlari') . ' SET okundu=1 WHERE id=?', [$detayId]);
    ?>
    <div class="mb-3"><a href="iletisim-mesajlari.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Liste</a></div>
    <div class="row g-3">
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-body">
            <div class="d-flex justify-content-between mb-3">
              <h5 class="mb-0"><?= e($m['konu'] ?: '(Konu yok)') ?></h5>
              <small class="text-muted"><?= tr_datetime($m['olusturma_tarihi']) ?></small>
            </div>
            <div class="d-flex flex-wrap gap-3 mb-3 small text-muted">
              <span><i class="bi bi-person"></i> <?= e($m['ad_soyad']) ?></span>
              <?php if ($m['email']): ?><span><i class="bi bi-envelope"></i> <a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></span><?php endif; ?>
              <?php if ($m['telefon']): ?><span><i class="bi bi-telephone"></i> <a href="tel:<?= e($m['telefon']) ?>"><?= e($m['telefon']) ?></a></span><?php endif; ?>
              <span><i class="bi bi-globe"></i> <?= e($m['ip_adresi']) ?></span>
              <?php if ($m['cevaplandi']): ?><span class="badge bg-success">Cevaplandı</span><?php endif; ?>
            </div>
            <hr>
            <div class="border rounded p-3 bg-light"><?= nl2br(e($m['mesaj'])) ?></div>
          </div>
        </div>

        <?php if ($m['email']): ?>
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="bi bi-reply text-warning"></i> Cevap Yaz</h6>
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="cevapla">
              <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
              <div class="mb-2"><label class="form-label small">Konu</label><input type="text" name="konu" class="form-control form-control-sm" value="Re: <?= e($m['konu']) ?>"></div>
              <div class="mb-2"><label class="form-label small">Cevap</label><textarea name="govde" rows="6" class="form-control form-control-sm" required></textarea></div>
              <button class="btn btn-warning btn-sm fw-semibold"><i class="bi bi-send"></i> E-posta Olarak Gönder</button>
            </form>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="bi bi-tools text-warning"></i> İşlemler</h6>
            <?php if ($m['telefon']): ?>
              <a href="tel:<?= e($m['telefon']) ?>" class="btn btn-success btn-sm w-100 mb-1"><i class="bi bi-telephone"></i> Ara</a>
              <a href="https://wa.me/<?= e(preg_replace('/[^0-9]/','',$m['telefon'])) ?>" target="_blank" class="btn btn-outline-success btn-sm w-100 mb-1"><i class="bi bi-whatsapp"></i> WhatsApp</a>
            <?php endif; ?>
            <hr>
            <form method="post" onsubmit="return confirm('Mesaj silinsin mi?');">
              <?= csrf_field() ?><input type="hidden" name="action" value="sil"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
              <button class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-trash"></i> Mesajı Sil</button>
            </form>
          </div>
        </div>
      </div>
    </div>
    <?php
    require __DIR__ . '/_footer.php';
    return;
}

$durum = (string)($_GET['durum'] ?? '');
$where = ['1=1']; $args = [];
if ($durum === 'okunmamis') $where[] = 'okundu=0';
if ($durum === 'cevaplanmamis') { $where[] = 'cevaplandi=0'; $where[] = 'okundu=1'; }
$wsql = implode(' AND ', $where);

$page  = max(1, (int)($_GET['s'] ?? 1));
$per   = 30;
$total = (int)db_value('SELECT COUNT(*) FROM ' . t('iletisim_mesajlari') . " WHERE $wsql", $args);
$pag   = paginate($total, $per, $page);
$rows  = db_all('SELECT * FROM ' . t('iletisim_mesajlari') . " WHERE $wsql ORDER BY olusturma_tarihi DESC LIMIT $per OFFSET " . $pag['offset'], $args);
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <a href="?" class="btn btn-sm <?= $durum===''?'btn-primary':'btn-outline-primary' ?>">Tümü</a>
  <a href="?durum=okunmamis" class="btn btn-sm <?= $durum==='okunmamis'?'btn-warning':'btn-outline-warning' ?>">Okunmamış</a>
  <a href="?durum=cevaplanmamis" class="btn btn-sm <?= $durum==='cevaplanmamis'?'btn-info':'btn-outline-info' ?>">Cevap Bekliyor</a>
  <small class="text-muted ms-auto">Toplam <b><?= $total ?></b> mesaj</small>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="list-group list-group-flush">
      <?php foreach ($rows as $r): ?>
        <a href="?detay=<?= (int)$r['id'] ?>" class="list-group-item list-group-item-action <?= !$r['okundu']?'fw-bold bg-warning-subtle':'' ?>">
          <div class="d-flex justify-content-between align-items-start">
            <div class="me-3">
              <div class="mb-1">
                <?php if (!$r['okundu']): ?><span class="badge bg-warning text-dark me-1">Yeni</span><?php endif; ?>
                <?= e($r['ad_soyad']) ?>
                <span class="text-muted small">— <?= e($r['email'] ?: $r['telefon']) ?></span>
              </div>
              <div class="small text-muted"><b><?= e($r['konu'] ?: '(Konu yok)') ?>:</b> <?= e(mb_substr($r['mesaj'], 0, 100)) ?>…</div>
            </div>
            <div class="text-end text-nowrap small text-muted">
              <?php if ($r['cevaplandi']): ?><span class="badge bg-success"><i class="bi bi-check-all"></i></span><?php endif; ?>
              <div><?= tr_date($r['olusturma_tarihi']) ?></div>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
      <?php if (!$rows): ?><div class="text-center text-muted py-5">Mesaj yok.</div><?php endif; ?>
    </div>
  </div>
</div>

<?php if ($pag['pages'] > 1): ?>
  <nav class="mt-3"><ul class="pagination pagination-sm justify-content-center">
  <?php $qs = $_GET; unset($qs['s']); $b = 'iletisim-mesajlari.php?' . http_build_query($qs);
    for ($i = 1; $i <= $pag['pages']; $i++): ?>
    <li class="page-item <?= $i === $pag['page'] ? 'active' : '' ?>"><a class="page-link" href="<?= e($b) ?>&s=<?= $i ?>"><?= $i ?></a></li>
  <?php endfor; ?></ul></nav>
<?php endif; ?>

<?php require __DIR__ . '/_footer.php';
