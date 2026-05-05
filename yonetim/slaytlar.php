<?php
/**
 * Mizan Sigorta - Anasayfa Slider Yonetimi
 * yonetim/slaytlar.php
 *
 * CRUD: liste, ekle, duzenle, sil, aktif/pasif toggle, sira degisikligi.
 * Anasayfada gosterilen slider slaytlari yonetilir.
 */

define('MZ_ADMIN', true);
$adminTitle = 'Anasayfa Slider';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_helpers.php';
require_role('superadmin', 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_assert_post();
    $act = (string)($_POST['action'] ?? '');

    if ($act === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $d = [
            'baslik'        => trim((string)($_POST['baslik'] ?? '')),
            'accent_kelime' => trim((string)($_POST['accent_kelime'] ?? '')) ?: null,
            'ust_metin'     => trim((string)($_POST['ust_metin'] ?? '')) ?: null,
            'aciklama'      => trim((string)($_POST['aciklama'] ?? '')) ?: null,
            'buton1_metin'  => trim((string)($_POST['buton1_metin'] ?? '')) ?: null,
            'buton1_link'   => trim((string)($_POST['buton1_link'] ?? '')) ?: null,
            'buton1_ikon'   => trim((string)($_POST['buton1_ikon'] ?? '')) ?: 'bi-arrow-right',
            'buton2_metin'  => trim((string)($_POST['buton2_metin'] ?? '')) ?: null,
            'buton2_link'   => trim((string)($_POST['buton2_link'] ?? '')) ?: null,
            'buton2_ikon'   => trim((string)($_POST['buton2_ikon'] ?? '')) ?: 'bi-telephone',
            'gorsel_tip'    => $_POST['gorsel_tip'] ?? 'svg_kalkan',
            'gorsel_url'    => trim((string)($_POST['gorsel_url'] ?? '')) ?: null,
            'sira'          => (int)($_POST['sira'] ?? 0),
            'aktif'         => isset($_POST['aktif']) ? 1 : 0,
        ];

        if ($d['baslik'] === '') admin_redirect('slaytlar.php', 'danger', 'Başlık zorunlu.');
        if (!in_array($d['gorsel_tip'], array_keys(mz_svg_illustration_options()), true)) {
            $d['gorsel_tip'] = 'svg_kalkan';
        }
        if ($d['gorsel_tip'] === 'custom_url' && $d['gorsel_url'] && !filter_var($d['gorsel_url'], FILTER_VALIDATE_URL)) {
            admin_redirect('slaytlar.php' . ($id ? '?edit=' . $id : ''), 'danger', 'Geçerli bir görsel URL\'i giriniz (https:// ile başlamalı).');
        }

        if ($id) {
            $cols = array_keys($d);
            $set = implode(', ', array_map(fn($c) => "$c=?", $cols));
            db_exec('UPDATE ' . t('slaytlar') . " SET $set WHERE id=?", array_merge(array_values($d), [$id]));
            audit_log('slayt_guncelle', 'slayt', $id);
            admin_redirect('slaytlar.php', 'success', 'Slayt güncellendi.');
        } else {
            $cols = array_keys($d);
            $ph = implode(',', array_fill(0, count($cols), '?'));
            db_exec('INSERT INTO ' . t('slaytlar') . ' (' . implode(',', $cols) . ',olusturma_tarihi) VALUES (' . $ph . ',NOW())', array_values($d));
            audit_log('slayt_ekle', 'slayt', db_last_id());
            admin_redirect('slaytlar.php', 'success', 'Slayt eklendi.');
        }
    }

    if ($act === 'sil') {
        $id = (int)($_POST['id'] ?? 0);
        db_exec('DELETE FROM ' . t('slaytlar') . ' WHERE id=?', [$id]);
        audit_log('slayt_sil', 'slayt', $id);
        admin_redirect('slaytlar.php', 'success', 'Slayt silindi.');
    }

    if ($act === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        db_exec('UPDATE ' . t('slaytlar') . ' SET aktif = 1-aktif WHERE id=?', [$id]);
        audit_log('slayt_toggle', 'slayt', $id);
        admin_redirect('slaytlar.php', 'success', 'Slayt durumu değiştirildi.');
    }

    if ($act === 'sirala') {
        // Drag-drop sonrasi sira POST: id_sira_pairs
        $pairs = (array)($_POST['order'] ?? []);
        foreach ($pairs as $sira => $id) {
            $id = (int)$id; $sira = (int)$sira;
            if ($id > 0) db_exec('UPDATE ' . t('slaytlar') . ' SET sira=? WHERE id=?', [$sira * 10, $id]);
        }
        audit_log('slayt_siralama', 'slayt');
        admin_redirect('slaytlar.php', 'success', 'Sıralama kaydedildi.');
    }
}

$rows = db_all('SELECT * FROM ' . t('slaytlar') . ' ORDER BY sira ASC, id ASC');
$editId = (int)($_GET['edit'] ?? 0);
$edit = $editId ? db_row('SELECT * FROM ' . t('slaytlar') . ' WHERE id=?', [$editId]) : null;
$gorselOptions = mz_svg_illustration_options();
?>

<style>
.slayt-card { transition: all .15s; border: 1px solid #e5e7eb; }
.slayt-card:hover { box-shadow: 0 8px 24px rgba(13,27,42,.08); transform: translateY(-2px); }
.slayt-thumb {
    width: 100%; height: 140px; border-radius: 8px;
    background: linear-gradient(135deg, #0d1b2a 0%, #1b263b 100%);
    display: flex; align-items: center; justify-content: center;
    overflow: hidden; position: relative;
}
.slayt-thumb svg { width: 80%; height: 80%; opacity: .6; }
.slayt-thumb img { width: 100%; height: 100%; object-fit: cover; }
.slayt-thumb-badge {
    position: absolute; top: 8px; right: 8px;
    background: rgba(0,0,0,.6); color: #fff;
    padding: 2px 8px; border-radius: 100px; font-size: .7rem; font-weight: 600;
}
.slayt-actions { display: flex; gap: .35rem; flex-wrap: wrap; }
.gorsel-onizleme {
    margin-top: .5rem; padding: 1rem;
    background: linear-gradient(135deg, #0d1b2a 0%, #1b263b 100%);
    border-radius: 8px; display: flex; align-items: center; justify-content: center;
    height: 200px; overflow: hidden;
}
.gorsel-onizleme svg { width: 70%; height: 100%; }
.gorsel-onizleme img { max-width: 100%; max-height: 100%; object-fit: contain; }
</style>

<div class="row g-3">
  <!-- Sol: Form -->
  <div class="col-lg-5">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h5 class="fw-bold mb-3">
          <i class="bi bi-<?= $edit ? 'pencil-square' : 'plus-circle' ?> text-warning"></i>
          <?= $edit ? 'Slayt Düzenle: #' . (int)$edit['id'] : 'Yeni Slayt Ekle' ?>
        </h5>

        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="save">
          <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Üst Metin <span class="text-muted">(italik kırmızı script)</span></label>
            <input type="text" name="ust_metin" maxlength="150" class="form-control form-control-sm"
                   value="<?= e($edit['ust_metin'] ?? '') ?>"
                   placeholder="Aileniz İçin / Aracınız İçin / Hasar Anında">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Başlık <span class="text-danger">*</span></label>
            <input type="text" name="baslik" maxlength="255" required class="form-control form-control-sm"
                   value="<?= e($edit['baslik'] ?? '') ?>"
                   placeholder="Hayatınıza, aracınıza ve işinize tam koruma">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Vurgulu Kelime <span class="text-muted">(başlıkta sarı renkli olur)</span></label>
            <input type="text" name="accent_kelime" maxlength="100" class="form-control form-control-sm"
                   value="<?= e($edit['accent_kelime'] ?? '') ?>"
                   placeholder="tam koruma">
            <small class="form-text text-muted">Başlıkta geçen bir kelime/ifade yazın — otomatik sarı renkli vurgulu gösterilir.</small>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Açıklama Paragrafı</label>
            <textarea name="aciklama" rows="3" class="form-control form-control-sm" placeholder="Slayt altında görünen açıklama metni..."><?= e($edit['aciklama'] ?? '') ?></textarea>
          </div>

          <hr>
          <h6 class="fw-bold small mb-2"><i class="bi bi-hand-index"></i> Birinci Buton (sarı)</h6>
          <div class="row g-2 mb-3">
            <div class="col-7"><input type="text" name="buton1_metin" maxlength="80" class="form-control form-control-sm" value="<?= e($edit['buton1_metin'] ?? '') ?>" placeholder="Buton metni"></div>
            <div class="col-5"><input type="text" name="buton1_ikon" maxlength="50" class="form-control form-control-sm" value="<?= e($edit['buton1_ikon'] ?? 'bi-headset') ?>" placeholder="bi-headset"></div>
            <div class="col-12"><input type="text" name="buton1_link" maxlength="255" class="form-control form-control-sm" value="<?= e($edit['buton1_link'] ?? '') ?>" placeholder="/teklif-al veya https://..."></div>
          </div>

          <h6 class="fw-bold small mb-2"><i class="bi bi-hand-index"></i> İkinci Buton (outline)</h6>
          <div class="row g-2 mb-3">
            <div class="col-7"><input type="text" name="buton2_metin" maxlength="80" class="form-control form-control-sm" value="<?= e($edit['buton2_metin'] ?? '') ?>" placeholder="Buton metni"></div>
            <div class="col-5"><input type="text" name="buton2_ikon" maxlength="50" class="form-control form-control-sm" value="<?= e($edit['buton2_ikon'] ?? 'bi-telephone') ?>" placeholder="bi-telephone"></div>
            <div class="col-12">
              <input type="text" name="buton2_link" maxlength="255" class="form-control form-control-sm" value="<?= e($edit['buton2_link'] ?? '') ?>" placeholder="/iletisim veya tel: (telefon ayarlarından otomatik)">
              <small class="form-text text-muted"><code>tel:</code> yazarsanız ayarlardan telefon otomatik kullanılır</small>
            </div>
          </div>

          <hr>
          <h6 class="fw-bold small mb-2"><i class="bi bi-image"></i> Arka Plan Görseli</h6>
          <div class="mb-3">
            <select name="gorsel_tip" id="gorselTip" class="form-select form-select-sm" onchange="updateOnizleme()">
              <?php foreach ($gorselOptions as $val => $label): ?>
                <option value="<?= e($val) ?>" <?= ($edit['gorsel_tip'] ?? 'svg_kalkan') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3" id="gorselUrlGroup" style="display:none">
            <label class="form-label small fw-semibold">Özel Görsel URL</label>
            <input type="url" name="gorsel_url" id="gorselUrl" class="form-control form-control-sm"
                   value="<?= e($edit['gorsel_url'] ?? '') ?>"
                   placeholder="https://example.com/resim.png">
            <small class="form-text text-muted">PNG/JPG/SVG. Şeffaf arkaplanlı PNG önerilir.</small>
          </div>

          <div class="gorsel-onizleme" id="gorselOnizleme"><!-- JS ile dolar --></div>

          <hr>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Sıra</label>
              <input type="number" name="sira" min="0" class="form-control form-control-sm" value="<?= (int)($edit['sira'] ?? 0) ?>">
            </div>
            <div class="col-6 d-flex align-items-end">
              <div class="form-check form-switch">
                <input type="checkbox" name="aktif" id="aktif" class="form-check-input" <?= !$edit || $edit['aktif'] ? 'checked' : '' ?>>
                <label for="aktif" class="form-check-label small fw-semibold">Aktif</label>
              </div>
            </div>
          </div>

          <div class="d-flex gap-2">
            <button class="btn btn-primary btn-sm fw-semibold flex-grow-1">
              <i class="bi bi-save"></i> <?= $edit ? 'Güncelle' : 'Slayt Ekle' ?>
            </button>
            <?php if ($edit): ?>
              <a href="slaytlar.php" class="btn btn-outline-secondary btn-sm">İptal</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Sag: Liste -->
  <div class="col-lg-7">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h6 class="mb-0 small text-muted">Toplam <?= count($rows) ?> slayt — <?= count(array_filter($rows, fn($r) => $r['aktif'])) ?> aktif</h6>
      <a href="<?= u('/') ?>" target="_blank" class="btn btn-outline-primary btn-sm"><i class="bi bi-eye"></i> Sitede Görüntüle</a>
    </div>

    <?php if (!$rows): ?>
      <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> Henüz slayt eklenmemiş. Sol formdan ilkini ekleyin. Yoksa anasayfa varsayılan slaytı gösterecek.
      </div>
    <?php endif; ?>

    <div class="row g-3">
      <?php foreach ($rows as $r):
        $gtip = $r['gorsel_tip'] ?: 'yok';
        $gImg = $r['gorsel_url'] ?: '';
      ?>
        <div class="col-md-6">
          <div class="card slayt-card h-100">
            <div class="card-body">
              <div class="slayt-thumb mb-3">
                <?php if ($gtip === 'custom_url' && $gImg): ?>
                  <img src="<?= e($gImg) ?>" alt="">
                <?php elseif ($gtip !== 'yok'): ?>
                  <?= mz_svg_illustration($gtip) ?>
                <?php else: ?>
                  <i class="bi bi-image text-light opacity-50" style="font-size:3rem"></i>
                <?php endif; ?>
                <span class="slayt-thumb-badge">#<?= (int)$r['id'] ?> · sıra <?= (int)$r['sira'] ?></span>
              </div>

              <?php if ($r['ust_metin']): ?>
                <small class="text-danger fst-italic d-block mb-1"><?= e($r['ust_metin']) ?></small>
              <?php endif; ?>
              <h6 class="fw-bold mb-2 small"><?= e($r['baslik']) ?></h6>
              <?php if ($r['aciklama']): ?>
                <p class="text-muted small mb-2" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?= e($r['aciklama']) ?></p>
              <?php endif; ?>

              <div class="d-flex gap-1 flex-wrap mb-2 small">
                <?php if ($r['buton1_metin']): ?>
                  <span class="badge bg-warning text-dark"><i class="bi <?= e($r['buton1_ikon'] ?: 'bi-arrow-right') ?>"></i> <?= e($r['buton1_metin']) ?></span>
                <?php endif; ?>
                <?php if ($r['buton2_metin']): ?>
                  <span class="badge bg-secondary"><i class="bi <?= e($r['buton2_ikon'] ?: 'bi-link') ?>"></i> <?= e($r['buton2_metin']) ?></span>
                <?php endif; ?>
              </div>

              <div class="slayt-actions mt-2">
                <a href="?edit=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Düzenle</a>

                <form method="post" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button class="btn btn-sm btn-outline-<?= $r['aktif'] ? 'success' : 'secondary' ?>" title="<?= $r['aktif'] ? 'Aktif' : 'Pasif' ?>">
                    <i class="bi bi-<?= $r['aktif'] ? 'eye-fill' : 'eye-slash' ?>"></i>
                    <?= $r['aktif'] ? 'Aktif' : 'Pasif' ?>
                  </button>
                </form>

                <form method="post" class="d-inline" onsubmit="return confirm('“<?= e(addslashes($r['baslik'])) ?>” slaytı silinsin mi?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="sil">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script>
// SVG illustration onizleme - select degisikliginde / sayfa yuklendiginde
const svgPreviews = <?= json_encode(array_map(fn($k) => mz_svg_illustration($k), array_keys($gorselOptions))) ?>;
const svgKeys = <?= json_encode(array_keys($gorselOptions)) ?>;

window.updateOnizleme = function() {
    const tipSelect = document.getElementById('gorselTip');
    const urlGroup = document.getElementById('gorselUrlGroup');
    const urlInput = document.getElementById('gorselUrl');
    const onizleme = document.getElementById('gorselOnizleme');
    const tip = tipSelect.value;

    if (tip === 'custom_url') {
        urlGroup.style.display = 'block';
        const url = urlInput.value.trim();
        onizleme.innerHTML = url
            ? '<img src="' + url.replace(/"/g, '&quot;') + '" alt="" onerror="this.parentNode.innerHTML=\'<small class=text-warning>Görsel yüklenemedi</small>\'">'
            : '<small class="text-muted">URL girince önizleme burada gösterilir</small>';
    } else if (tip === 'yok') {
        urlGroup.style.display = 'none';
        onizleme.innerHTML = '<small class="text-muted">Görsel yok — sadece metin gösterilecek</small>';
    } else {
        urlGroup.style.display = 'none';
        const idx = svgKeys.indexOf(tip);
        onizleme.innerHTML = idx >= 0 ? svgPreviews[idx] : '';
    }
};

// URL input degisiminde de onizleme guncelle
document.getElementById('gorselUrl')?.addEventListener('input', () => {
    if (document.getElementById('gorselTip').value === 'custom_url') updateOnizleme();
});

// Sayfa yuklendiginde
updateOnizleme();
</script>

<?php require __DIR__ . '/_footer.php';
