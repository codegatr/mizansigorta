<?php
define('MZ_ADMIN', true);
$adminTitle = 'Teklif Detayı';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_helpers.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) admin_redirect('teklifler.php', 'danger', 'Teklif bulunamadı.');

$teklif = db_row('SELECT t.*, u.baslik urun_baslik FROM ' . t('teklifler') . ' t LEFT JOIN ' . t('urunler') . ' u ON u.id=t.urun_id WHERE t.id=?', [$id]);
if (!$teklif) admin_redirect('teklifler.php', 'danger', 'Teklif bulunamadı.');

// Aksiyonlar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_assert_post();
    $act = (string)($_POST['action'] ?? '');

    if ($act === 'durum_guncelle') {
        $yeni = (string)($_POST['durum'] ?? '');
        $allowed = ['yeni','islemde','teklif_hazir','teklif_gonderildi','onaylandi','police_oldu','iptal','kayip'];
        if (in_array($yeni, $allowed, true)) {
            db_exec('UPDATE ' . t('teklifler') . ' SET durum=?, guncelleme_tarihi=NOW() WHERE id=?', [$yeni, $id]);
            db_exec('INSERT INTO ' . t('teklif_notlari') . ' (teklif_id, kullanici_id, tip, baslik, icerik, olusturma_tarihi)
                     VALUES (?,?, "sistem", "Durum güncellendi", ?, NOW())', [$id, user_id(), 'Yeni durum: ' . $yeni]);
            audit_log('teklif_durum', 'teklif', $id, 'Eski: '.$teklif['durum'].' → Yeni: '.$yeni);
            admin_redirect('teklif-detay.php?id=' . $id, 'success', 'Durum güncellendi.');
        }
    }

    if ($act === 'atama') {
        $u = (int)($_POST['atanan'] ?? 0);
        db_exec('UPDATE ' . t('teklifler') . ' SET atanan_kullanici_id=?, guncelleme_tarihi=NOW() WHERE id=?', [$u ?: null, $id]);
        audit_log('teklif_atama', 'teklif', $id, 'Atanan: '.$u);
        admin_redirect('teklif-detay.php?id=' . $id, 'success', 'Atama güncellendi.');
    }

    if ($act === 'oncelik') {
        $o = (string)($_POST['oncelik'] ?? 'normal');
        db_exec('UPDATE ' . t('teklifler') . ' SET oncelik=? WHERE id=?', [$o, $id]);
        admin_redirect('teklif-detay.php?id=' . $id, 'success', 'Öncelik güncellendi.');
    }

    if ($act === 'takip_tarihi') {
        $tt = (string)($_POST['bir_sonraki_takip_tarihi'] ?? '');
        $h  = isset($_POST['hatirlatma_aktif']) ? 1 : 0;
        db_exec('UPDATE ' . t('teklifler') . ' SET bir_sonraki_takip_tarihi=?, hatirlatma_aktif=? WHERE id=?', [$tt ?: null, $h, $id]);
        admin_redirect('teklif-detay.php?id=' . $id, 'success', 'Takip ayarları güncellendi.');
    }

    if ($act === 'not_ekle') {
        $tip = (string)($_POST['tip'] ?? 'not');
        $bas = trim((string)($_POST['baslik'] ?? ''));
        $ic  = trim((string)($_POST['icerik'] ?? ''));
        if ($ic) {
            db_exec('INSERT INTO ' . t('teklif_notlari') . ' (teklif_id, kullanici_id, tip, baslik, icerik, olusturma_tarihi)
                     VALUES (?,?,?,?,?,NOW())', [$id, user_id(), $tip, $bas ?: null, $ic]);
            audit_log('teklif_not', 'teklif', $id);
            admin_redirect('teklif-detay.php?id=' . $id, 'success', 'Not eklendi.');
        }
        admin_redirect('teklif-detay.php?id=' . $id, 'danger', 'Not içeriği boş olamaz.');
    }

    if ($act === 'manuel_email') {
        $konu = trim((string)($_POST['konu'] ?? ''));
        $mesg = trim((string)($_POST['mesaj'] ?? ''));
        if ($konu && $mesg && filter_var($teklif['email'], FILTER_VALIDATE_EMAIL)) {
            $vars = [
                'ad_soyad'   => $teklif['ad_soyad'] ?: $teklif['firma_adi'],
                'teklif_no'  => $teklif['teklif_no'],
                'urun_adi'   => $teklif['urun_baslik'],
                'firma_adi'  => setting('site_basligi', SITE_NAME),
                'telefon'    => setting('telefon'),
            ];
            $body = nl2br(e(tpl_replace($mesg, $vars)));
            $r = send_mail($teklif['email'], tpl_replace($konu, $vars), mail_template($konu, $body));
            if ($r['ok']) {
                db_exec('INSERT INTO ' . t('teklif_notlari') . ' (teklif_id, kullanici_id, tip, baslik, icerik, olusturma_tarihi)
                         VALUES (?,?, "email", ?, ?, NOW())', [$id, user_id(), $konu, $mesg]);
                db_exec('UPDATE ' . t('teklifler') . ' SET hatirlatma_sayisi=hatirlatma_sayisi+1, son_hatirlatma_tarihi=NOW() WHERE id=?', [$id]);
                admin_redirect('teklif-detay.php?id=' . $id, 'success', 'E-posta gönderildi.');
            }
            admin_redirect('teklif-detay.php?id=' . $id, 'danger', 'E-posta gönderilemedi: ' . ($r['error'] ?? ''));
        }
        admin_redirect('teklif-detay.php?id=' . $id, 'danger', 'Geçersiz veri.');
    }
}

$notlar = db_all(
    'SELECT n.*, k.ad_soyad kul_ad
       FROM ' . t('teklif_notlari') . ' n
       LEFT JOIN ' . t('kullanicilar') . ' k ON k.id=n.kullanici_id
      WHERE n.teklif_id=?
      ORDER BY n.olusturma_tarihi DESC',
    [$id]
);
$personel = db_all('SELECT id, ad_soyad FROM ' . t('kullanicilar') . " WHERE aktif=1 ORDER BY ad_soyad");
$urunDetay = !empty($teklif['urun_detay_json']) ? json_decode((string)$teklif['urun_detay_json'], true) : [];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <a href="teklifler.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Listeye Dön</a>
  </div>
  <div>
    <span class="text-muted small">Teklif No:</span>
    <span class="fw-bold ms-1"><?= e($teklif['teklif_no']) ?></span>
    <?= badge_durum($teklif['durum']) ?>
    <?= badge_oncelik($teklif['oncelik'] ?: 'normal') ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-person-badge text-warning"></i> Müşteri Bilgileri</h6>
        <div class="row">
          <div class="col-md-6">
            <table class="table table-sm">
              <tr><th width="40%">Ad Soyad</th><td><?= e($teklif['ad_soyad']) ?></td></tr>
              <?php if ($teklif['firma_adi']): ?><tr><th>Firma</th><td><?= e($teklif['firma_adi']) ?></td></tr><?php endif; ?>
              <tr><th>Telefon</th><td><a href="tel:<?= e($teklif['telefon']) ?>" class="text-decoration-none"><?= e($teklif['telefon']) ?></a></td></tr>
              <tr><th>E-posta</th><td><a href="mailto:<?= e($teklif['email']) ?>" class="text-decoration-none"><?= e($teklif['email']) ?></a></td></tr>
              <?php if ($teklif['tckn_vkn']): ?><tr><th>TCKN/VKN</th><td><?= e($teklif['tckn_vkn']) ?></td></tr><?php endif; ?>
            </table>
          </div>
          <div class="col-md-6">
            <table class="table table-sm">
              <tr><th width="40%">Ürün</th><td><?= e($teklif['urun_baslik']) ?></td></tr>
              <tr><th>Şehir</th><td><?= e($teklif['sehir'] ?: '-') ?></td></tr>
              <tr><th>Müşteri Tipi</th><td><span class="badge bg-info"><?= $teklif['musteri_tipi'] === 'kurumsal' ? 'Kurumsal' : 'Bireysel' ?></span></td></tr>
              <tr><th>Geliş Tarihi</th><td><?= tr_datetime($teklif['olusturma_tarihi']) ?></td></tr>
            </table>
          </div>
        </div>
        <?php if ($urunDetay): ?>
          <hr>
          <h6 class="fw-bold small text-muted">Ürüne Özel Detaylar</h6>
          <table class="table table-sm">
            <?php foreach ($urunDetay as $k => $v): if (!is_scalar($v)) continue; ?>
              <tr><th width="30%"><?= e(ucfirst(str_replace('_', ' ', (string)$k))) ?></th><td><?= e((string)$v) ?></td></tr>
            <?php endforeach; ?>
          </table>
        <?php endif; ?>
        <?php if ($teklif['notlar']): ?>
          <hr>
          <h6 class="fw-bold small text-muted">Müşteri Notu</h6>
          <p class="bg-light p-3 rounded mb-0"><?= nl2br(e($teklif['notlar'])) ?></p>
        <?php endif; ?>
      </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
      <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-chat-left-text text-warning"></i> Zaman Çizelgesi & Notlar</h6>
        <ul class="nav nav-tabs nav-sm" role="tablist">
          <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabNot">Not Ekle</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabMail">Manuel E-posta</a></li>
        </ul>
        <div class="tab-content pt-3">
          <div id="tabNot" class="tab-pane active">
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="not_ekle">
              <div class="row g-2 mb-2">
                <div class="col-md-3">
                  <select name="tip" class="form-select form-select-sm">
                    <option value="not">Not</option>
                    <option value="arama">Telefon Görüşmesi</option>
                    <option value="email">E-posta</option>
                    <option value="whatsapp">WhatsApp</option>
                    <option value="sms">SMS</option>
                    <option value="toplanti">Toplantı</option>
                  </select>
                </div>
                <div class="col-md-9">
                  <input type="text" name="baslik" class="form-control form-control-sm" placeholder="Başlık (opsiyonel)">
                </div>
              </div>
              <textarea name="icerik" class="form-control form-control-sm mb-2" rows="3" placeholder="Detay/içerik..." required></textarea>
              <button class="btn btn-sm btn-primary"><i class="bi bi-plus"></i> Not Ekle</button>
            </form>
          </div>
          <div id="tabMail" class="tab-pane">
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="manuel_email">
              <div class="alert alert-info small">Yer tutucular: <code>{ad_soyad}</code> <code>{teklif_no}</code> <code>{urun_adi}</code> <code>{firma_adi}</code> <code>{telefon}</code></div>
              <input type="text" name="konu" class="form-control form-control-sm mb-2" placeholder="Konu" required value="Teklifiniz hk. - {teklif_no}">
              <textarea name="mesaj" class="form-control form-control-sm mb-2" rows="6" required>Sayın {ad_soyad},

{urun_adi} için talebinizi aldık. Teklif numaranız: {teklif_no}

Saygılarımızla,
{firma_adi}</textarea>
              <button class="btn btn-sm btn-warning fw-semibold"><i class="bi bi-send"></i> Müşteriye Gönder</button>
            </form>
          </div>
        </div>

        <hr>
        <div class="mz-timeline">
          <?php if (!$notlar): ?>
            <p class="text-muted small text-center py-3">Henüz not yok.</p>
          <?php else: foreach ($notlar as $n):
            $iconMap = ['not'=>'pencil','arama'=>'telephone','email'=>'envelope','whatsapp'=>'whatsapp','sms'=>'chat-dots','toplanti'=>'people','sistem'=>'cpu'];
            $colMap  = ['not'=>'secondary','arama'=>'info','email'=>'primary','whatsapp'=>'success','sms'=>'warning','toplanti'=>'dark','sistem'=>'light'];
            $ic = $iconMap[$n['tip']] ?? 'pencil';
            $cl = $colMap[$n['tip']] ?? 'secondary';
          ?>
            <div class="mz-timeline-item">
              <div class="mz-timeline-dot bg-<?= e($cl) ?> text-white"><i class="bi bi-<?= e($ic) ?>"></i></div>
              <div class="mz-timeline-body">
                <div class="d-flex justify-content-between">
                  <strong><?= e($n['baslik'] ?: ucfirst($n['tip'])) ?></strong>
                  <small class="text-muted"><?= tr_datetime($n['olusturma_tarihi']) ?> · <?= e($n['kul_ad'] ?: 'Sistem') ?></small>
                </div>
                <div><?= nl2br(e($n['icerik'])) ?></div>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-toggles text-warning"></i> Durum Yönetimi</h6>
        <form method="post" class="mb-3">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="durum_guncelle">
          <select name="durum" class="form-select form-select-sm mb-2" onchange="this.form.submit()">
            <?php foreach (['yeni'=>'Yeni','islemde'=>'İşlemde','teklif_hazir'=>'Teklif Hazır','teklif_gonderildi'=>'Gönderildi','onaylandi'=>'Onaylandı','police_oldu'=>'Poliçe Oldu','iptal'=>'İptal','kayip'=>'Kayıp'] as $k=>$v): ?>
              <option value="<?= $k ?>" <?= $teklif['durum']===$k?'selected':'' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </form>

        <form method="post" class="mb-3">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="oncelik">
          <label class="form-label small fw-semibold">Öncelik</label>
          <select name="oncelik" class="form-select form-select-sm" onchange="this.form.submit()">
            <?php foreach (['dusuk'=>'Düşük','normal'=>'Normal','yuksek'=>'Yüksek','acil'=>'Acil'] as $k=>$v): ?>
              <option value="<?= $k ?>" <?= ($teklif['oncelik']?:'normal')===$k?'selected':'' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </form>

        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="atama">
          <label class="form-label small fw-semibold">Atanan Kişi</label>
          <select name="atanan" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">— Atanmamış —</option>
            <?php foreach ($personel as $p): ?>
              <option value="<?= (int)$p['id'] ?>" <?= $teklif['atanan_kullanici_id']==(int)$p['id']?'selected':'' ?>><?= e($p['ad_soyad']) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
      <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-bell text-warning"></i> Hatırlatma & Takip</h6>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="takip_tarihi">
          <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" name="hatirlatma_aktif" id="haRem" <?= $teklif['hatirlatma_aktif'] ? 'checked' : '' ?>>
            <label class="form-check-label small" for="haRem">Otomatik hatırlatmalar aktif</label>
          </div>
          <label class="form-label small fw-semibold">Sonraki Takip Tarihi</label>
          <input type="date" name="bir_sonraki_takip_tarihi" class="form-control form-control-sm mb-2" value="<?= e($teklif['bir_sonraki_takip_tarihi']) ?>">
          <button class="btn btn-sm btn-primary w-100"><i class="bi bi-save"></i> Kaydet</button>
        </form>
        <hr class="my-3">
        <small class="text-muted d-block">Gönderilen hatırlatma: <b><?= (int)$teklif['hatirlatma_sayisi'] ?></b></small>
        <?php if ($teklif['son_hatirlatma_tarihi']): ?>
          <small class="text-muted d-block">Son hatırlatma: <?= tr_datetime($teklif['son_hatirlatma_tarihi']) ?></small>
        <?php endif; ?>
      </div>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-link-45deg text-warning"></i> Hızlı İşlemler</h6>
        <a href="https://wa.me/<?= e(preg_replace('/\D/', '', $teklif['telefon'])) ?>" target="_blank" class="btn btn-sm btn-success w-100 mb-2"><i class="bi bi-whatsapp"></i> WhatsApp ile Aç</a>
        <a href="tel:<?= e($teklif['telefon']) ?>" class="btn btn-sm btn-outline-primary w-100 mb-2"><i class="bi bi-telephone"></i> Ara</a>
        <a href="mailto:<?= e($teklif['email']) ?>" class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-envelope"></i> E-posta İstemcisi</a>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php';
