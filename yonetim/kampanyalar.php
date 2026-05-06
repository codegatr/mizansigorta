<?php
/**
 * Mizan Sigorta - Kampanya Pop-up Yonetimi
 * yonetim/kampanyalar.php
 *
 * Anasayfada/diger sayfalarda gosterilecek kampanya pop-uplari yonetimi.
 * Tarih araligi (baslangic-bitis), gosterim kurali, hedef sayfa ayarlanabilir.
 * Birden fazla aktif kampanya varsa SIRA buyuk olan gosterilir.
 */

define('MZ_ADMIN', true);
$adminTitle = 'Kampanyalar';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_helpers.php';
require_role('superadmin', 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_assert_post();
    $act = (string)($_POST['action'] ?? '');

    if ($act === 'save') {
        $id = (int)($_POST['id'] ?? 0);

        // Mevcut kayit (varsa) - eski gorseli silmek icin gerekli
        $eski = $id ? db_row('SELECT * FROM ' . t('kampanyalar') . ' WHERE id=?', [$id]) : null;

        // Tarih dogrulama
        $bas = trim((string)($_POST['baslangic_tarihi'] ?? ''));
        $bit = trim((string)($_POST['bitis_tarihi'] ?? ''));
        if ($bas === '' || $bit === '') {
            admin_redirect('kampanyalar.php' . ($id ? '?edit=' . $id : ''), 'danger', 'Başlangıç ve bitiş tarihi zorunlu.');
        }
        // datetime-local (Y-m-d\TH:i) -> Y-m-d H:i:s
        $bas = str_replace('T', ' ', $bas) . ':00';
        $bit = str_replace('T', ' ', $bit) . ':00';
        if (strtotime($bas) === false || strtotime($bit) === false) {
            admin_redirect('kampanyalar.php' . ($id ? '?edit=' . $id : ''), 'danger', 'Geçerli tarih giriniz.');
        }
        if (strtotime($bit) <= strtotime($bas)) {
            admin_redirect('kampanyalar.php' . ($id ? '?edit=' . $id : ''), 'danger', 'Bitiş tarihi başlangıç tarihinden sonra olmalı.');
        }

        // GORSEL: file upload veya URL veya silme
        $gorselUrl = trim((string)($_POST['gorsel_url'] ?? ''));
        $gorselSil = !empty($_POST['gorsel_sil']);

        // 1) Yeni dosya yuklenmis mi?
        if (!empty($_FILES['gorsel_dosya']['name']) && $_FILES['gorsel_dosya']['error'] !== UPLOAD_ERR_NO_FILE) {
            $r = upload_image($_FILES['gorsel_dosya'], 'kampanyalar');
            if (!$r['ok']) {
                admin_redirect('kampanyalar.php' . ($id ? '?edit=' . $id : ''), 'danger', 'Görsel yüklenemedi: ' . $r['msg']);
            }
            // Eski yuklenen gorseli sil (sadece /uploads/ altindakileri)
            if ($eski && !empty($eski['gorsel_url'])) {
                delete_uploaded_image((string)$eski['gorsel_url']);
            }
            $gorselUrl = $r['url'];
        }
        // 2) Gorsel sil isaretliyse
        elseif ($gorselSil) {
            if ($eski && !empty($eski['gorsel_url'])) {
                delete_uploaded_image((string)$eski['gorsel_url']);
            }
            $gorselUrl = '';
        }
        // 3) URL kontrolu (boş veya geçerli url)
        elseif ($gorselUrl !== '' && !filter_var($gorselUrl, FILTER_VALIDATE_URL)) {
            // Yuklenen path /uploads/... veya tam URL kabul
            if (strpos($gorselUrl, '/uploads/') !== 0) {
                admin_redirect('kampanyalar.php' . ($id ? '?edit=' . $id : ''), 'danger', 'Görsel URL geçerli bir adres olmalı (https://... veya yüklenmiş dosya).');
            }
        }

        $d = [
            'baslik'           => trim((string)($_POST['baslik'] ?? '')),
            'alt_baslik'       => trim((string)($_POST['alt_baslik'] ?? '')) ?: null,
            'aciklama'         => trim((string)($_POST['aciklama'] ?? '')) ?: null,
            'gorsel_url'       => $gorselUrl ?: null,
            'buton_metin'      => trim((string)($_POST['buton_metin'] ?? '')) ?: null,
            'buton_link'       => trim((string)($_POST['buton_link'] ?? '')) ?: null,
            'buton_renk'       => $_POST['buton_renk'] ?? 'kirmizi',
            'baslangic_tarihi' => $bas,
            'bitis_tarihi'     => $bit,
            'gosterim_kurali'  => $_POST['gosterim_kurali'] ?? 'oturum_basina',
            'acilis_gecikmesi' => max(0, min(60, (int)($_POST['acilis_gecikmesi'] ?? 3))),
            'hedef_sayfa'      => $_POST['hedef_sayfa'] ?? 'tum_sayfalar',
            'sira'             => (int)($_POST['sira'] ?? 0),
            'aktif'            => isset($_POST['aktif']) ? 1 : 0,
        ];

        if ($d['baslik'] === '') admin_redirect('kampanyalar.php', 'danger', 'Başlık zorunlu.');
        // Enum dogrulama
        if (!in_array($d['buton_renk'], ['kirmizi','sari','mavi','yesil'], true)) $d['buton_renk'] = 'kirmizi';
        if (!in_array($d['gosterim_kurali'], ['her_ziyaret','oturum_basina','gunde_bir','sadece_bir_kez'], true)) $d['gosterim_kurali'] = 'oturum_basina';
        if (!in_array($d['hedef_sayfa'], ['anasayfa','tum_sayfalar'], true)) $d['hedef_sayfa'] = 'tum_sayfalar';

        if ($id) {
            $cols = array_keys($d);
            $set = implode(', ', array_map(fn($c) => "$c=?", $cols));
            db_exec('UPDATE ' . t('kampanyalar') . " SET $set WHERE id=?", array_merge(array_values($d), [$id]));
            audit_log('kampanya_guncelle', 'kampanya', $id);
            admin_redirect('kampanyalar.php', 'success', 'Kampanya güncellendi.');
        } else {
            $cols = array_keys($d);
            $ph = implode(',', array_fill(0, count($cols), '?'));
            db_exec('INSERT INTO ' . t('kampanyalar') . ' (' . implode(',', $cols) . ',olusturma_tarihi) VALUES (' . $ph . ',NOW())', array_values($d));
            audit_log('kampanya_ekle', 'kampanya', db_last_id());
            admin_redirect('kampanyalar.php', 'success', 'Kampanya eklendi.');
        }
    }

    if ($act === 'sil') {
        $id = (int)($_POST['id'] ?? 0);
        $silinecek = db_row('SELECT gorsel_url FROM ' . t('kampanyalar') . ' WHERE id=?', [$id]);
        db_exec('DELETE FROM ' . t('kampanyalar') . ' WHERE id=?', [$id]);
        if ($silinecek && !empty($silinecek['gorsel_url'])) {
            delete_uploaded_image((string)$silinecek['gorsel_url']);
        }
        audit_log('kampanya_sil', 'kampanya', $id);
        admin_redirect('kampanyalar.php', 'success', 'Kampanya silindi.');
    }

    if ($act === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        db_exec('UPDATE ' . t('kampanyalar') . ' SET aktif = 1-aktif WHERE id=?', [$id]);
        audit_log('kampanya_toggle', 'kampanya', $id);
        admin_redirect('kampanyalar.php', 'success', 'Kampanya durumu değiştirildi.');
    }

    if ($act === 'sayac_sifirla') {
        $id = (int)($_POST['id'] ?? 0);
        db_exec('UPDATE ' . t('kampanyalar') . ' SET gosterim_sayisi=0, tiklama_sayisi=0 WHERE id=?', [$id]);
        audit_log('kampanya_sayac', 'kampanya', $id);
        admin_redirect('kampanyalar.php', 'success', 'İstatistik sayaçları sıfırlandı.');
    }
}

$rows = db_all('SELECT * FROM ' . t('kampanyalar') . ' ORDER BY sira DESC, id DESC');
$editId = (int)($_GET['edit'] ?? 0);
$edit = $editId ? db_row('SELECT * FROM ' . t('kampanyalar') . ' WHERE id=?', [$editId]) : null;

$now = time();
$durum = function ($r) use ($now) {
    if (!$r['aktif']) return ['pasif', 'secondary', 'Pasif'];
    $bas = strtotime($r['baslangic_tarihi']);
    $bit = strtotime($r['bitis_tarihi']);
    if ($now < $bas) return ['bekliyor', 'info', 'Yayına Başlanacak'];
    if ($now > $bit) return ['bitti', 'dark', 'Süresi Doldu'];
    return ['aktif', 'success', 'Yayında'];
};

$gosterimEtiket = [
    'her_ziyaret'    => 'Her sayfa yüklemesi',
    'oturum_basina'  => 'Oturum başına 1 kez',
    'gunde_bir'      => '24 saatte 1 kez',
    'sadece_bir_kez' => 'Kapatılırsa bir daha asla',
];

// HTML datetime-local format icin
$dtLocal = function ($v) {
    if (!$v) return '';
    $ts = strtotime($v);
    return $ts ? date('Y-m-d\TH:i', $ts) : '';
};
?>

<style>
.kamp-card { transition: all .15s; border: 1px solid #e5e7eb; }
.kamp-card:hover { box-shadow: 0 8px 24px rgba(13,27,42,.08); }
.kamp-thumb {
    width: 100%; height: 120px; border-radius: 8px;
    background: linear-gradient(135deg, #0d1b2a 0%, #1b263b 100%);
    display: flex; align-items: center; justify-content: center;
    overflow: hidden; position: relative;
}
.kamp-thumb img { width: 100%; height: 100%; object-fit: cover; }
.kamp-info-row { display: flex; align-items: center; gap: .35rem; font-size: .8rem; margin: .25rem 0; }
.kamp-info-row i { color: var(--mz-red); width: 16px; }
.kamp-stats { display: flex; gap: .75rem; font-size: .75rem; color: #6b7280; padding-top: .5rem; border-top: 1px dashed #e5e7eb; margin-top: .5rem; }
.kamp-onizleme {
    padding: 1.25rem;
    background: #f8fafc;
    border-radius: 8px;
    border: 2px dashed #cbd5e1;
    margin-top: .5rem;
    min-height: 120px;
}
</style>

<div class="row g-3">
  <!-- Sol: Form -->
  <div class="col-lg-5">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h5 class="fw-bold mb-3">
          <i class="bi bi-<?= $edit ? 'pencil-square' : 'plus-circle' ?> text-warning"></i>
          <?= $edit ? 'Kampanya Düzenle: #' . (int)$edit['id'] : 'Yeni Kampanya Ekle' ?>
        </h5>

        <form method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="save">
          <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Başlık <span class="text-danger">*</span></label>
            <input type="text" name="baslik" maxlength="160" required class="form-control form-control-sm"
                   value="<?= e($edit['baslik'] ?? '') ?>"
                   placeholder="Yaz Kampanyası — Kasko'da %20 İndirim">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Alt Başlık <span class="text-muted">(opsiyonel)</span></label>
            <input type="text" name="alt_baslik" maxlength="200" class="form-control form-control-sm"
                   value="<?= e($edit['alt_baslik'] ?? '') ?>"
                   placeholder="Sınırlı süre · Tüm anlaşmalı şirketler">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Açıklama <span class="text-muted">(opsiyonel)</span></label>
            <textarea name="aciklama" rows="3" class="form-control form-control-sm"
                      placeholder="Pop-up gövdesinde gösterilecek açıklama metni..."><?= e($edit['aciklama'] ?? '') ?></textarea>
          </div>

          <!-- GORSEL: Dosya yukle veya URL -->
          <div class="mb-3 p-3 border rounded bg-light">
            <label class="form-label small fw-bold mb-2"><i class="bi bi-image text-primary"></i> Kampanya Görseli <span class="text-muted fw-normal">(opsiyonel)</span></label>

            <?php $gMevcut = $edit['gorsel_url'] ?? ''; ?>
            <?php if ($gMevcut): ?>
              <div class="mb-2 position-relative" style="max-width:240px">
                <img src="<?= e($gMevcut) ?>" alt="Mevcut görsel" class="img-fluid rounded border" style="max-height:120px;display:block">
                <div class="form-check mt-1">
                  <input type="checkbox" name="gorsel_sil" id="gorsel_sil" class="form-check-input">
                  <label for="gorsel_sil" class="form-check-label small text-danger"><i class="bi bi-trash"></i> Mevcut görseli sil</label>
                </div>
              </div>
            <?php endif; ?>

            <div class="mb-2">
              <label class="form-label small mb-1">Bilgisayardan resim yükle</label>
              <input type="file" name="gorsel_dosya" accept="image/jpeg,image/png,image/webp,image/gif" class="form-control form-control-sm">
              <small class="form-text text-muted">JPG, PNG, WebP veya GIF · En fazla 5 MB</small>
            </div>

            <details class="mt-2">
              <summary class="small text-muted" style="cursor:pointer"><i class="bi bi-link-45deg"></i> Veya internetteki bir resmin URL'ini yapıştır</summary>
              <input type="text" name="gorsel_url" maxlength="500" class="form-control form-control-sm mt-2"
                     value="<?= e(strpos($gMevcut, '/uploads/') === 0 ? '' : $gMevcut) ?>"
                     placeholder="https://example.com/kampanya.jpg">
            </details>
          </div>

          <hr>

          <!-- EYLEM BUTONU -->
          <h6 class="fw-bold small mb-2"><i class="bi bi-hand-index"></i> Eylem Butonu <span class="text-muted fw-normal">(opsiyonel)</span></h6>
          <div class="row g-2 mb-3">
            <div class="col-7">
              <input type="text" name="buton_metin" maxlength="80" class="form-control form-control-sm"
                     value="<?= e($edit['buton_metin'] ?? '') ?>"
                     placeholder="Hemen Teklif Al">
            </div>
            <div class="col-5">
              <select name="buton_renk" class="form-select form-select-sm">
                <option value="kirmizi" <?= ($edit['buton_renk'] ?? 'kirmizi') === 'kirmizi' ? 'selected' : '' ?>>Kırmızı</option>
                <option value="sari"    <?= ($edit['buton_renk'] ?? '') === 'sari' ? 'selected' : '' ?>>Sarı</option>
                <option value="mavi"    <?= ($edit['buton_renk'] ?? '') === 'mavi' ? 'selected' : '' ?>>Mavi</option>
                <option value="yesil"   <?= ($edit['buton_renk'] ?? '') === 'yesil' ? 'selected' : '' ?>>Yeşil</option>
              </select>
            </div>
            <div class="col-12">
              <input type="text" name="buton_link" maxlength="255" class="form-control form-control-sm"
                     value="<?= e($edit['buton_link'] ?? '/teklif-al') ?>"
                     placeholder="/teklif-al veya https://...">
              <small class="form-text text-muted">Site içi sayfa için <code>/teklif-al</code> · Dış link için <code>https://...</code></small>
            </div>
          </div>

          <hr>

          <!-- TARIH -->
          <h6 class="fw-bold small mb-2"><i class="bi bi-calendar-range text-warning"></i> Yayın Tarihi <span class="text-danger">*</span></h6>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small">Başlangıç</label>
              <input type="datetime-local" name="baslangic_tarihi" required class="form-control form-control-sm"
                     value="<?= e($dtLocal($edit['baslangic_tarihi'] ?? date('Y-m-d H:i:s'))) ?>">
            </div>
            <div class="col-6">
              <label class="form-label small">Bitiş</label>
              <input type="datetime-local" name="bitis_tarihi" required class="form-control form-control-sm"
                     value="<?= e($dtLocal($edit['bitis_tarihi'] ?? date('Y-m-d H:i:s', strtotime('+30 days')))) ?>">
            </div>
          </div>

          <!-- AKTIF SWITCH -->
          <div class="mb-3 p-2 bg-light rounded border">
            <div class="form-check form-switch">
              <input type="checkbox" name="aktif" id="aktif" class="form-check-input" <?= !$edit || $edit['aktif'] ? 'checked' : '' ?>>
              <label for="aktif" class="form-check-label small fw-semibold">Aktif <span class="text-muted">(işaret kaldırılırsa kampanya gösterilmez)</span></label>
            </div>
          </div>

          <!-- GELISMIS AYARLAR (collapse - default kapali) -->
          <details class="mb-3">
            <summary class="small fw-semibold text-muted" style="cursor:pointer">
              <i class="bi bi-sliders"></i> Gelişmiş Ayarlar
              <span class="text-muted fw-normal">(çoğu kullanıcı için gerekli değil)</span>
            </summary>

            <div class="mt-3 ps-2 border-start border-2 border-warning">
              <div class="mb-3">
                <label class="form-label small fw-semibold">Gösterim Kuralı</label>
                <select name="gosterim_kurali" class="form-select form-select-sm">
                  <?php foreach ($gosterimEtiket as $val => $lbl): ?>
                    <option value="<?= e($val) ?>" <?= ($edit['gosterim_kurali'] ?? 'oturum_basina') === $val ? 'selected' : '' ?>><?= e($lbl) ?></option>
                  <?php endforeach; ?>
                </select>
                <small class="form-text text-muted">Aynı ziyaretçiye ne kadar sıklıkla gösterilsin?</small>
              </div>

              <div class="row g-2 mb-3">
                <div class="col-6">
                  <label class="form-label small fw-semibold">Hangi Sayfalarda?</label>
                  <select name="hedef_sayfa" class="form-select form-select-sm">
                    <option value="tum_sayfalar" <?= ($edit['hedef_sayfa'] ?? 'tum_sayfalar') === 'tum_sayfalar' ? 'selected' : '' ?>>Tüm sayfalar</option>
                    <option value="anasayfa"     <?= ($edit['hedef_sayfa'] ?? '') === 'anasayfa' ? 'selected' : '' ?>>Sadece anasayfa</option>
                  </select>
                </div>
                <div class="col-6">
                  <label class="form-label small fw-semibold">Açılış Gecikmesi</label>
                  <div class="input-group input-group-sm">
                    <input type="number" name="acilis_gecikmesi" min="0" max="60" class="form-control"
                           value="<?= (int)($edit['acilis_gecikmesi'] ?? 3) ?>">
                    <span class="input-group-text">saniye</span>
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-semibold">Sıra</label>
                <input type="number" name="sira" class="form-control form-control-sm" value="<?= (int)($edit['sira'] ?? 0) ?>" style="max-width:120px">
                <small class="form-text text-muted">Aynı anda birden fazla kampanya aktifse, sayısı büyük olan gösterilir.</small>
              </div>
            </div>
          </details>

          <div class="d-flex gap-2">
            <button class="btn btn-primary fw-semibold flex-grow-1">
              <i class="bi bi-save"></i> <?= $edit ? 'Güncelle' : 'Kampanyayı Kaydet' ?>
            </button>
            <?php if ($edit): ?>
              <a href="kampanyalar.php" class="btn btn-outline-secondary">İptal</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Sag: Liste -->
  <div class="col-lg-7">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h6 class="mb-0 small text-muted">
        Toplam <?= count($rows) ?> kampanya —
        <?= count(array_filter($rows, fn($r) => $durum($r)[0] === 'aktif')) ?> yayında,
        <?= count(array_filter($rows, fn($r) => $durum($r)[0] === 'bekliyor')) ?> bekleyen,
        <?= count(array_filter($rows, fn($r) => $durum($r)[0] === 'bitti')) ?> bitmiş
      </h6>
      <a href="<?= u('/') ?>" target="_blank" class="btn btn-outline-primary btn-sm"><i class="bi bi-eye"></i> Sitede Görüntüle</a>
    </div>

    <?php if (!$rows): ?>
      <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> Henüz kampanya eklenmemiş. Sol formdan ilkini ekleyin.
      </div>
    <?php endif; ?>

    <div class="d-flex flex-column gap-2">
      <?php foreach ($rows as $r):
        [$dKey, $dColor, $dLabel] = $durum($r);
        $bas = strtotime($r['baslangic_tarihi']);
        $bit = strtotime($r['bitis_tarihi']);
      ?>
        <div class="card kamp-card">
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-4">
                <div class="kamp-thumb">
                  <?php if ($r['gorsel_url']): ?>
                    <img src="<?= e($r['gorsel_url']) ?>" alt="">
                  <?php else: ?>
                    <i class="bi bi-megaphone-fill text-warning" style="font-size:2.5rem;opacity:.5"></i>
                  <?php endif; ?>
                </div>
              </div>
              <div class="col-md-8">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2 flex-wrap">
                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-<?= $dColor ?>"><i class="bi bi-circle-fill" style="font-size:.4rem"></i> <?= e($dLabel) ?></span>
                    <?php if (!empty($r['blog_id'])): ?>
                      <span class="badge bg-warning text-dark" title="Bu kampanya bir blog yazısından otomatik oluşturulmuştur. Düzenlemek için blog yazısını açın.">
                        <i class="bi bi-link-45deg"></i> Blog'dan
                      </span>
                    <?php endif; ?>
                  </div>
                  <small class="text-muted">#<?= (int)$r['id'] ?> · sıra <?= (int)$r['sira'] ?></small>
                </div>

                <h6 class="fw-bold mb-1"><?= e($r['baslik']) ?></h6>
                <?php if (!empty($r['blog_id'])):
                    $blogYazi = db_row('SELECT slug, baslik FROM ' . t('blog') . ' WHERE id=?', [(int)$r['blog_id']]);
                    if ($blogYazi):
                ?>
                  <div class="alert alert-warning py-2 px-3 mb-2 small d-flex justify-content-between align-items-center">
                    <span>
                      <i class="bi bi-info-circle"></i>
                      Bu kampanya <strong>blog yazısından</strong> otomatik üretilmiş — düzenlemek için yazıyı açın.
                    </span>
                    <a href="blog.php?edit=<?= (int)$r['blog_id'] ?>" class="btn btn-sm btn-warning fw-semibold">
                      <i class="bi bi-pencil"></i> Yazıyı Düzenle
                    </a>
                  </div>
                <?php endif; endif; ?>

                <?php if ($r['alt_baslik']): ?>
                  <small class="text-muted d-block mb-2"><?= e($r['alt_baslik']) ?></small>
                <?php endif; ?>

                <div class="kamp-info-row">
                  <i class="bi bi-calendar-range"></i>
                  <span><?= date('d.m.Y H:i', $bas) ?> — <?= date('d.m.Y H:i', $bit) ?></span>
                </div>
                <div class="kamp-info-row">
                  <i class="bi bi-arrow-repeat"></i>
                  <span><?= e($gosterimEtiket[$r['gosterim_kurali']] ?? $r['gosterim_kurali']) ?></span>
                </div>
                <div class="kamp-info-row">
                  <i class="bi bi-bullseye"></i>
                  <span><?= $r['hedef_sayfa'] === 'anasayfa' ? 'Sadece anasayfa' : 'Tüm sayfalar' ?> · <?= (int)$r['acilis_gecikmesi'] ?> sn gecikme</span>
                </div>

                <?php if ($r['gosterim_sayisi'] > 0 || $r['tiklama_sayisi'] > 0):
                    $oran = $r['gosterim_sayisi'] > 0 ? round(($r['tiklama_sayisi'] / $r['gosterim_sayisi']) * 100, 1) : 0;
                ?>
                  <div class="kamp-stats">
                    <span><i class="bi bi-eye"></i> <?= number_format($r['gosterim_sayisi'], 0, ',', '.') ?> gösterim</span>
                    <span><i class="bi bi-cursor"></i> <?= number_format($r['tiklama_sayisi'], 0, ',', '.') ?> tıklama</span>
                    <span><i class="bi bi-percent"></i> %<?= $oran ?> CTR</span>
                  </div>
                <?php endif; ?>

                <div class="d-flex gap-1 flex-wrap mt-2">
                  <a href="?edit=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Düzenle</a>
                  <form method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-sm btn-outline-<?= $r['aktif'] ? 'success' : 'secondary' ?>">
                      <i class="bi bi-<?= $r['aktif'] ? 'eye-fill' : 'eye-slash' ?>"></i>
                      <?= $r['aktif'] ? 'Aktif' : 'Pasif' ?>
                    </button>
                  </form>
                  <?php if ($r['gosterim_sayisi'] > 0): ?>
                    <form method="post" class="d-inline" onsubmit="return confirm('İstatistik sayaçları sıfırlansın mı?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="sayac_sifirla">
                      <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                      <button class="btn btn-sm btn-outline-warning"><i class="bi bi-arrow-counterclockwise"></i> Sayaç Sıfırla</button>
                    </form>
                  <?php endif; ?>
                  <form method="post" class="d-inline" onsubmit="return confirm('“<?= e(addslashes($r['baslik'])) ?>” kampanyası silinsin mi?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="sil">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php';
