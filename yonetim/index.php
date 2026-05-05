<?php
define('MZ_ADMIN', true);
$adminTitle = 'Gösterge Paneli';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_helpers.php';

$bugun     = date('Y-m-d');
$ayBas     = date('Y-m-01');
$ay30Once  = date('Y-m-d', strtotime('-30 days'));

// Ozet kartlari
$st_yeni_teklif       = (int)db_value("SELECT COUNT(*) FROM " . t('teklifler') . " WHERE durum='yeni'");
$st_aktif_teklif      = (int)db_value("SELECT COUNT(*) FROM " . t('teklifler') . " WHERE durum IN ('yeni','islemde','teklif_hazir','teklif_gonderildi')");
$st_bu_ay_police      = (int)db_value("SELECT COUNT(*) FROM " . t('teklifler') . " WHERE durum='police_oldu' AND DATE(guncelleme_tarihi)>=?", [$ayBas]);
$st_acik_hasar        = (int)db_value("SELECT COUNT(*) FROM " . t('hasarlar') . " WHERE durum IN ('yeni','inceleniyor','eksper_atandi')");
$st_musteri_toplam    = (int)db_value("SELECT COUNT(*) FROM " . t('musteriler'));
$st_police_yenileme   = (int)db_value("SELECT COUNT(*) FROM " . t('policeler') . " WHERE bitis_tarihi BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND durum='aktif'");
$st_yeni_mesaj        = (int)db_value("SELECT COUNT(*) FROM " . t('iletisim_mesajlari') . " WHERE okundu=0");
$st_hatirlatma_bekley = (int)db_value("SELECT COUNT(*) FROM " . t('teklifler') . " WHERE hatirlatma_aktif=1 AND bir_sonraki_takip_tarihi<=CURDATE() AND durum NOT IN ('police_oldu','iptal','kayip')");

// Son 30 gunde teklif sayisi (gunluk)
$gunluk = db_all(
    "SELECT DATE(olusturma_tarihi) g, COUNT(*) c
       FROM " . t('teklifler') . "
      WHERE olusturma_tarihi >= ?
      GROUP BY DATE(olusturma_tarihi)
      ORDER BY g ASC",
    [$ay30Once]
);
$daily = [];
for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $daily[$d] = 0;
}
foreach ($gunluk as $r) $daily[$r['g']] = (int)$r['c'];

// Urunlere gore teklif dagilimi
$urunDag = db_all(
    'SELECT u.baslik, COUNT(t.id) c
       FROM ' . t('urunler') . ' u
       LEFT JOIN ' . t('teklifler') . ' t ON t.urun_id=u.id AND t.olusturma_tarihi>=?
      WHERE u.aktif=1
      GROUP BY u.id
      ORDER BY c DESC LIMIT 6',
    [$ay30Once]
);

// Son teklifler
$sonTeklifler = db_all(
    'SELECT t.*, u.baslik urun_baslik
       FROM ' . t('teklifler') . ' t
       LEFT JOIN ' . t('urunler') . ' u ON u.id=t.urun_id
      ORDER BY t.olusturma_tarihi DESC LIMIT 8'
);

// Yenileme uyarisi
$yenilemeler = db_all(
    "SELECT p.*, m.ad_soyad, m.firma_adi
       FROM " . t('policeler') . " p
       LEFT JOIN " . t('musteriler') . " m ON m.id=p.musteri_id
      WHERE p.bitis_tarihi BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        AND p.durum='aktif'
      ORDER BY p.bitis_tarihi ASC LIMIT 10"
);
?>

<div class="row g-3 mb-4">
  <div class="col-md-6 col-xl-3">
    <div class="mz-stat mz-stat-warning">
      <div class="mz-stat-icon"><i class="bi bi-file-earmark-plus"></i></div>
      <div>
        <div class="mz-stat-num"><?= $st_yeni_teklif ?></div>
        <div class="mz-stat-lbl">Yeni Teklif</div>
      </div>
      <a class="stretched-link" href="teklifler.php?durum=yeni"></a>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="mz-stat mz-stat-info">
      <div class="mz-stat-icon"><i class="bi bi-clock-history"></i></div>
      <div>
        <div class="mz-stat-num"><?= $st_aktif_teklif ?></div>
        <div class="mz-stat-lbl">Aktif Teklif</div>
      </div>
      <a class="stretched-link" href="teklifler.php"></a>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="mz-stat mz-stat-success">
      <div class="mz-stat-icon"><i class="bi bi-shield-check"></i></div>
      <div>
        <div class="mz-stat-num"><?= $st_bu_ay_police ?></div>
        <div class="mz-stat-lbl">Bu Ay Poliçe</div>
      </div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="mz-stat mz-stat-danger">
      <div class="mz-stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
      <div>
        <div class="mz-stat-num"><?= $st_acik_hasar ?></div>
        <div class="mz-stat-lbl">Açık Hasar</div>
      </div>
      <a class="stretched-link" href="hasarlar.php"></a>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="mz-mini-stat">
      <i class="bi bi-people text-primary"></i>
      <div>
        <div class="fw-bold"><?= number_format($st_musteri_toplam, 0, ',', '.') ?></div>
        <small class="text-muted">Toplam Müşteri</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="mz-mini-stat">
      <i class="bi bi-arrow-clockwise text-warning"></i>
      <div>
        <div class="fw-bold"><?= $st_police_yenileme ?></div>
        <small class="text-muted">30 Gün İçinde Yenileme</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="mz-mini-stat">
      <i class="bi bi-bell text-danger"></i>
      <div>
        <div class="fw-bold"><?= $st_hatirlatma_bekley ?></div>
        <small class="text-muted">Bekleyen Hatırlatma</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="mz-mini-stat">
      <i class="bi bi-envelope-paper text-info"></i>
      <div>
        <div class="fw-bold"><?= $st_yeni_mesaj ?></div>
        <small class="text-muted">Okunmayan Mesaj</small>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-graph-up text-warning"></i> Son 30 Gün - Günlük Teklif</h6>
        <div style="position:relative; height:240px; width:100%;">
          <canvas id="dailyChart"></canvas>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-pie-chart text-warning"></i> Ürün Dağılımı (30 Gün)</h6>
        <?php if (!array_filter(array_column($urunDag, 'c'))): ?>
          <p class="text-muted small text-center py-4">Henüz veri yok</p>
        <?php else: ?>
          <?php $maxC = max(1, max(array_column($urunDag, 'c'))); ?>
          <?php foreach ($urunDag as $u): ?>
            <div class="mb-2">
              <div class="d-flex justify-content-between small">
                <span><?= e($u['baslik']) ?></span>
                <b><?= (int)$u['c'] ?></b>
              </div>
              <div class="progress" style="height:6px;">
                <div class="progress-bar bg-warning" style="width:<?= ($u['c'] / $maxC * 100) ?>%"></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="fw-bold mb-0"><i class="bi bi-list-ul text-warning"></i> Son Teklifler</h6>
          <a href="teklifler.php" class="btn btn-sm btn-outline-primary">Tümünü Gör <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead><tr><th>Teklif No</th><th>Müşteri</th><th>Ürün</th><th>Durum</th><th>Tarih</th></tr></thead>
            <tbody>
            <?php foreach ($sonTeklifler as $t): ?>
              <tr>
                <td><a class="text-decoration-none fw-semibold" href="teklif-detay.php?id=<?= (int)$t['id'] ?>"><?= e($t['teklif_no']) ?></a></td>
                <td class="small"><?= e($t['ad_soyad'] ?? $t['firma_adi'] ?? '-') ?></td>
                <td class="small"><?= e($t['urun_baslik'] ?? '-') ?></td>
                <td><?= badge_durum($t['durum']) ?></td>
                <td class="small text-muted"><?= tr_datetime($t['olusturma_tarihi']) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$sonTeklifler): ?>
              <tr><td colspan="5" class="text-center text-muted py-4">Henüz teklif yok.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-arrow-clockwise text-danger"></i> Yaklaşan Yenilemeler</h6>
        <?php if (!$yenilemeler): ?>
          <p class="text-muted small text-center py-4">30 gün içinde yenilenecek poliçe yok.</p>
        <?php else: ?>
          <?php foreach ($yenilemeler as $p): $kalan = (int)((strtotime($p['bitis_tarihi']) - time()) / 86400); ?>
            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
              <div>
                <div class="fw-semibold small"><?= e($p['firma_adi'] ?? $p['ad_soyad']) ?></div>
                <small class="text-muted"><?= e($p['police_no']) ?> · <?= tr_date($p['bitis_tarihi']) ?></small>
              </div>
              <span class="badge <?= $kalan <= 7 ? 'bg-danger' : ($kalan <= 15 ? 'bg-warning text-dark' : 'bg-info') ?>">
                <?= $kalan ?> gün
              </span>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const dailyData = <?= json_encode(['labels' => array_keys($daily), 'data' => array_values($daily)]) ?>;
new Chart(document.getElementById('dailyChart'), {
  type: 'line',
  data: {
    labels: dailyData.labels.map(d => d.substring(5)),
    datasets: [{
      label: 'Teklif Sayısı',
      data: dailyData.data,
      borderColor: '#ffc107',
      backgroundColor: 'rgba(255,193,7,0.15)',
      tension: 0.3,
      fill: true,
      pointRadius: 3
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
  }
});
</script>

<?php require __DIR__ . '/_footer.php';
