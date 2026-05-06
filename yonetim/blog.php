<?php
define('MZ_ADMIN', true);
$adminTitle = 'Blog';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_assert_post();
    $act = (string)($_POST['action'] ?? '');

    if ($act === 'save') {
        $id     = (int)($_POST['id'] ?? 0);
        $baslik = trim((string)($_POST['baslik'] ?? ''));
        // Slug HER DURUMDA slugify'den gecsin (kullanici Türkçe karakter veya bosluk yazmis olabilir)
        $slugInput = trim((string)($_POST['slug'] ?? ''));
        $slug = $slugInput !== '' ? slugify($slugInput) : slugify($baslik);
        if ($slug === '' || $slug === 'kayit') $slug = slugify($baslik);
        if ($baslik === '') admin_redirect('blog.php', 'danger', 'Başlık zorunlu.');

        // Slug benzersizlik kontrolü
        $existsParams = [$slug];
        $existsSql = 'SELECT id FROM ' . t('blog') . ' WHERE slug = ?';
        if ($id) { $existsSql .= ' AND id != ?'; $existsParams[] = $id; }
        if (db_value($existsSql, $existsParams)) {
            $slug = $slug . '-' . substr((string) time(), -4);
        }

        $yt = trim((string)($_POST['yayin_tarihi'] ?? ''));
        if ($yt === '') $yt = date('Y-m-d H:i:s');

        $d = [
            'slug'         => $slug,
            'baslik'       => $baslik,
            'ozet'         => trim((string)($_POST['ozet'] ?? '')),
            'icerik'       => (string)($_POST['icerik'] ?? ''),
            'kategori'     => trim((string)($_POST['kategori'] ?? '')),
            'etiketler'    => trim((string)($_POST['etiketler'] ?? '')),
            'seo_baslik'   => trim((string)($_POST['seo_baslik'] ?? '')),
            'seo_aciklama' => trim((string)($_POST['seo_aciklama'] ?? '')),
            'yayin_tarihi' => $yt,
            'yazar_id'     => user_id(),
            'aktif'        => isset($_POST['aktif']) ? 1 : 0,
        ];

        try {
            $up = admin_handle_upload('kapak', 'blog', ['jpg','jpeg','png','webp']);
            if ($up) $d['kapak_gorseli'] = $up;
        } catch (Throwable $e) { admin_redirect('blog.php', 'danger', $e->getMessage()); }

        // Kampanya entegrasyonu - blog yazisindan otomatik kampanya yarat/guncelle/sil
        $kampanyaYap = isset($_POST['kampanya_yap']);
        $mevcutKampanyaId = $id ? (int) db_value('SELECT kampanya_id FROM ' . t('blog') . ' WHERE id=?', [$id]) : 0;

        if ($id) {
            $cols = array_keys($d);
            $set  = implode(', ', array_map(fn($c) => "$c=?", $cols));
            db_exec('UPDATE ' . t('blog') . " SET $set, guncelleme_tarihi=NOW() WHERE id=?", array_merge(array_values($d), [$id]));
            $blogId = $id;
            audit_log('blog_guncelle', 'blog', $id);
        } else {
            $cols = array_keys($d);
            $ph = implode(',', array_fill(0, count($cols), '?'));
            db_exec('INSERT INTO ' . t('blog') . ' (' . implode(',', $cols) . ',olusturma_tarihi) VALUES (' . $ph . ',NOW())', array_values($d));
            $blogId = (int) db_last_id();
            audit_log('blog_ekle', 'blog', $blogId);
        }

        // Kampanya senkronizasyonu
        $mesaj = $id ? 'Yazı güncellendi.' : 'Yazı eklendi.';
        if ($kampanyaYap) {
            // Kampanya alanlarini topla
            $kmpData = [
                'baslik'           => mb_substr($baslik, 0, 160),
                'alt_baslik'       => mb_substr(trim((string)($_POST['ozet'] ?? '')), 0, 200),
                'aciklama'         => mb_substr(strip_tags(trim((string)($_POST['ozet'] ?? '')) ?: trim((string)($_POST['icerik'] ?? ''))), 0, 500),
                'gorsel_url'       => !empty($d['kapak_gorseli']) ? (defined('SITE_BASE_URL') ? rtrim(SITE_BASE_URL, '/') : '') . '/uploads/blog/' . $d['kapak_gorseli'] : null,
                'blog_id'          => $blogId,
                'buton_metin'      => trim((string)($_POST['kmp_buton_metin'] ?? 'Yazıyı Oku')) ?: 'Yazıyı Oku',
                'buton_link'       => '/blog/' . $slug,
                'buton_renk'       => in_array($_POST['kmp_buton_renk'] ?? '', ['kirmizi','sari','mavi','yesil'], true) ? $_POST['kmp_buton_renk'] : 'kirmizi',
                'baslangic_tarihi' => trim((string)($_POST['kmp_baslangic'] ?? '')) ? date('Y-m-d H:i:s', strtotime($_POST['kmp_baslangic'])) : date('Y-m-d H:i:s'),
                'bitis_tarihi'     => trim((string)($_POST['kmp_bitis'] ?? '')) ? date('Y-m-d H:i:s', strtotime($_POST['kmp_bitis'])) : date('Y-m-d H:i:s', strtotime('+30 days')),
                'gosterim_kurali'  => in_array($_POST['kmp_gosterim'] ?? '', ['her_ziyaret','oturum_basina','gunde_bir','sadece_bir_kez'], true) ? $_POST['kmp_gosterim'] : 'gunde_bir',
                'hedef_sayfa'      => in_array($_POST['kmp_hedef'] ?? '', ['anasayfa','tum_sayfalar'], true) ? $_POST['kmp_hedef'] : 'tum_sayfalar',
                'aktif'            => $d['aktif'], // blog yayindaysa kampanya da aktif
                'acilis_gecikmesi' => 3,
                'sira'             => 100,
            ];

            if ($mevcutKampanyaId) {
                // Guncelle
                $kCols = array_keys($kmpData);
                $kSet  = implode(', ', array_map(fn($c) => "$c=?", $kCols));
                db_exec('UPDATE ' . t('kampanyalar') . " SET $kSet WHERE id=?", array_merge(array_values($kmpData), [$mevcutKampanyaId]));
                audit_log('kampanya_guncelle', 'kampanyalar', $mevcutKampanyaId, 'Blog yazisindan');
                $mesaj .= ' Kampanya pop-up güncellendi.';
            } else {
                // Yeni kampanya yarat
                $kCols = array_keys($kmpData);
                $kPh = implode(',', array_fill(0, count($kCols), '?'));
                db_exec('INSERT INTO ' . t('kampanyalar') . ' (' . implode(',', $kCols) . ',olusturma_tarihi) VALUES (' . $kPh . ',NOW())', array_values($kmpData));
                $yeniKmpId = (int) db_last_id();
                db_exec('UPDATE ' . t('blog') . ' SET kampanya_id=? WHERE id=?', [$yeniKmpId, $blogId]);
                audit_log('kampanya_ekle', 'kampanyalar', $yeniKmpId, 'Blog yazisindan otomatik');
                $mesaj .= ' Kampanya pop-up oluşturuldu.';
            }
        } else {
            // Kampanya istek yok - eger varsa sil
            if ($mevcutKampanyaId) {
                db_exec('DELETE FROM ' . t('kampanyalar') . ' WHERE id=?', [$mevcutKampanyaId]);
                db_exec('UPDATE ' . t('blog') . ' SET kampanya_id=NULL WHERE id=?', [$blogId]);
                audit_log('kampanya_sil', 'kampanyalar', $mevcutKampanyaId, 'Blog kampanya kaldirildi');
                $mesaj .= ' Bağlı kampanya kaldırıldı.';
            }
        }

        admin_redirect('blog.php', 'success', $mesaj);
    }

    if ($act === 'sil') {
        $id = (int)($_POST['id'] ?? 0);
        $row = db_row('SELECT kapak_gorseli, kampanya_id FROM ' . t('blog') . ' WHERE id=?', [$id]);
        if ($row && $row['kapak_gorseli']) admin_delete_upload('blog', $row['kapak_gorseli']);
        // Bagli kampanya varsa onu da sil
        if ($row && !empty($row['kampanya_id'])) {
            db_exec('DELETE FROM ' . t('kampanyalar') . ' WHERE id=?', [(int)$row['kampanya_id']]);
            audit_log('kampanya_sil', 'kampanyalar', (int)$row['kampanya_id'], 'Blog silindi');
        }
        db_exec('DELETE FROM ' . t('blog') . ' WHERE id=?', [$id]);
        audit_log('blog_sil', 'blog', $id);
        admin_redirect('blog.php', 'success', 'Yazı silindi' . ($row && !empty($row['kampanya_id']) ? ' (kampanya pop-up da kaldırıldı).' : '.'));
    }

    if ($act === 'sluglari_onar') {
        // Tum bozuk slug'lari tespit edip yeniden uret
        $rows = db_all('SELECT id, slug, baslik FROM ' . t('blog'));
        $onarilanSayi = 0;
        foreach ($rows as $r) {
            $eskiSlug = (string)$r['slug'];
            $yeniSlug = slugify($r['baslik']);
            // Sadece bozuk olanlari onar (Türkçe/bosluk/uppercase iceren)
            if ($eskiSlug !== $yeniSlug && !preg_match('/^[a-z0-9\-]+$/', $eskiSlug)) {
                // Benzersiz olmasini garantile
                $sayac = 0;
                $denemeSlug = $yeniSlug;
                while (db_value('SELECT id FROM ' . t('blog') . ' WHERE slug = ? AND id != ?', [$denemeSlug, $r['id']])) {
                    $sayac++;
                    $denemeSlug = $yeniSlug . '-' . $sayac;
                }
                db_exec('UPDATE ' . t('blog') . ' SET slug = ? WHERE id = ?', [$denemeSlug, $r['id']]);
                $onarilanSayi++;
            }
        }
        audit_log('blog_sluglari_onarildi', 'blog', null, "$onarilanSayi yazi");
        admin_redirect('blog.php', 'success', "$onarilanSayi yazının URL slug'ı onarıldı.");
    }

    if ($act === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        db_exec('UPDATE ' . t('blog') . ' SET aktif = 1 - aktif, guncelleme_tarihi=NOW() WHERE id=?', [$id]);
        admin_redirect('blog.php', 'success', 'Yayın durumu güncellendi.');
    }
}

$q   = trim((string)($_GET['q'] ?? ''));
$kat = trim((string)($_GET['kategori'] ?? ''));
$where = ['1=1']; $args = [];
if ($q !== '')  { $where[] = '(baslik LIKE ? OR ozet LIKE ?)'; $like = "%$q%"; $args = [$like,$like]; }
if ($kat !== ''){ $where[] = 'kategori=?'; $args[] = $kat; }
$wsql = implode(' AND ', $where);

$rows = db_all('SELECT id, slug, baslik, ozet, kategori, kapak_gorseli, yayin_tarihi, aktif, goruntulenme FROM ' . t('blog') . " WHERE $wsql ORDER BY yayin_tarihi DESC LIMIT 200", $args);
$kategoriler = db_all('SELECT DISTINCT kategori FROM ' . t('blog') . " WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori");
$editId = (int)($_GET['edit'] ?? 0);
$edit = $editId ? db_row('SELECT * FROM ' . t('blog') . ' WHERE id=?', [$editId]) : null;
// Eger blog yazisinin kampanyasi varsa kampanya bilgilerini de getir
$editKampanya = null;
if ($edit && !empty($edit['kampanya_id'])) {
    $editKampanya = db_row('SELECT * FROM ' . t('kampanyalar') . ' WHERE id=?', [(int)$edit['kampanya_id']]);
}
?>

<form method="get" class="card border-0 shadow-sm mb-3">
  <div class="card-body p-3">
    <div class="row g-2">
      <div class="col-md-6"><input type="search" name="q" class="form-control form-control-sm" placeholder="Başlık veya özet ara..." value="<?= e($q) ?>"></div>
      <div class="col-md-3">
        <select name="kategori" class="form-select form-select-sm">
          <option value="">Tüm Kategoriler</option>
          <?php foreach ($kategoriler as $k): ?>
            <option value="<?= e($k['kategori']) ?>" <?= $kat===$k['kategori']?'selected':'' ?>><?= e($k['kategori']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3"><button class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i> Filtrele</button></div>
    </div>
  </div>
</form>

<div class="row g-3">
  <div class="col-lg-7">
    <?php
    // Bozuk slug var mi kontrol (Türkçe karakter, bosluk veya uppercase iceren)
    $bozukSlugSayisi = (int) db_value("SELECT COUNT(*) FROM " . t('blog') . " WHERE slug REGEXP '[^a-z0-9-]' OR slug = '' OR slug LIKE '% %'");
    if ($bozukSlugSayisi > 0):
    ?>
    <div class="alert alert-warning d-flex justify-content-between align-items-center py-2 mb-2" style="border-left:4px solid #f59e0b">
      <div>
        <i class="bi bi-exclamation-triangle-fill text-warning"></i>
        <strong><?= $bozukSlugSayisi ?> yazının</strong> URL slug'ı bozuk (Türkçe karakter veya boşluk içeriyor).
      </div>
      <form method="post" class="d-inline" onsubmit="return confirm('<?= $bozukSlugSayisi ?> yazının URL adresi yeniden üretilecek. Eski URL\'lere artık erişilemez (404). Devam edilsin mi?');">
        <?= csrf_field() ?><input type="hidden" name="action" value="sluglari_onar">
        <button class="btn btn-warning btn-sm fw-semibold"><i class="bi bi-magic"></i> Hemen Onar</button>
      </form>
    </div>
    <?php endif; ?>
    <div class="card border-0 shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th width="60"></th><th>Başlık</th><th>Kategori</th><th>Yayın</th><th>Görün.</th><th width="100"></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td>
                  <?php if ($r['kapak_gorseli']): ?>
                    <img src="<?= u('uploads/blog/' . rawurlencode($r['kapak_gorseli'])) ?>" alt="" style="width:50px;height:50px;object-fit:cover;border-radius:6px">
                  <?php else: ?>
                    <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:50px;height:50px"><i class="bi bi-image text-muted"></i></div>
                  <?php endif; ?>
                </td>
                <td>
                  <b><?= e($r['baslik']) ?></b>
                  <a href="<?= u('blog/' . $r['slug']) ?>" target="_blank" class="text-muted small ms-1"><i class="bi bi-box-arrow-up-right"></i></a>
                  <?php if (!empty($r['kampanya_id'])): ?>
                    <span class="badge bg-warning text-dark small ms-1" title="Bu yazı kampanya pop-up olarak da gösteriliyor"><i class="bi bi-megaphone-fill"></i> Kampanya</span>
                  <?php endif; ?>
                  <?php if ($r['ozet']): ?><div class="text-muted small"><?= e(mb_substr($r['ozet'], 0, 70)) ?>…</div><?php endif; ?>
                </td>
                <td class="small"><?= e($r['kategori'] ?: '-') ?></td>
                <td class="small text-muted"><?= tr_date($r['yayin_tarihi']) ?></td>
                <td class="small text-muted"><?= (int)$r['goruntulenme'] ?></td>
                <td>
                  <form method="post" class="d-inline">
                    <?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-sm <?= $r['aktif']?'btn-success':'btn-outline-secondary' ?>" title="<?= $r['aktif']?'Yayında':'Taslak' ?>"><i class="bi bi-<?= $r['aktif']?'eye':'eye-slash' ?>"></i></button>
                  </form>
                  <a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int)$r['id'] ?>"><i class="bi bi-pencil"></i></a>
                  <form method="post" class="d-inline" onsubmit="return confirm('Silinsin mi?');">
                    <?= csrf_field() ?><input type="hidden" name="action" value="sil"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="6" class="text-muted text-center py-5">Henüz blog yazısı yok.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-<?= $edit?'pencil-square':'plus-square' ?> text-warning"></i> <?= $edit?'Yazıyı Düzenle':'Yeni Yazı' ?></h6>
        <form method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
          <div class="row g-2">
            <div class="col-12"><label class="form-label small">Başlık *</label><input type="text" name="baslik" id="blogBaslik" required class="form-control form-control-sm" value="<?= e($edit['baslik'] ?? '') ?>"></div>
            <div class="col-md-7">
              <label class="form-label small">URL Slug <small class="text-muted">(boş bırakırsan başlıktan otomatik üretilir)</small></label>
              <div class="input-group input-group-sm">
                <span class="input-group-text"><?= e(rtrim(SITE_BASE_URL, '/')) ?>/blog/</span>
                <input type="text" name="slug" id="blogSlug" class="form-control form-control-sm" value="<?= e($edit['slug'] ?? '') ?>" placeholder="otomatik-uretilir" pattern="[a-z0-9\-]+" title="Sadece kucuk harf, rakam ve tire">
              </div>
              <small class="text-muted">Sadece <code>a-z, 0-9, -</code> kullanılır. Türkçe karakter ve boşluklar otomatik dönüştürülür.</small>
            </div>
            <div class="col-md-5"><label class="form-label small">Yayın Tarihi</label><input type="datetime-local" name="yayin_tarihi" class="form-control form-control-sm" value="<?= $edit && $edit['yayin_tarihi'] ? date('Y-m-d\TH:i', strtotime($edit['yayin_tarihi'])) : date('Y-m-d\TH:i') ?>"></div>
            <div class="col-md-7"><label class="form-label small">Kategori</label><input type="text" name="kategori" class="form-control form-control-sm" value="<?= e($edit['kategori'] ?? '') ?>"></div>
            <div class="col-md-5"><label class="form-label small">Etiketler (virgülle)</label><input type="text" name="etiketler" class="form-control form-control-sm" value="<?= e($edit['etiketler'] ?? '') ?>"></div>
            <div class="col-12"><label class="form-label small">Kapak Görseli</label><input type="file" name="kapak" class="form-control form-control-sm" accept="image/*">
              <?php if ($edit && $edit['kapak_gorseli']): ?><div class="small text-muted mt-1">Mevcut: <?= e($edit['kapak_gorseli']) ?></div><?php endif; ?>
            </div>
            <div class="col-12"><label class="form-label small">Özet</label><textarea name="ozet" rows="2" class="form-control form-control-sm"><?= e($edit['ozet'] ?? '') ?></textarea></div>
            <div class="col-12"><label class="form-label small">İçerik (HTML)</label><textarea name="icerik" rows="10" class="form-control form-control-sm font-monospace"><?= e($edit['icerik'] ?? '') ?></textarea></div>
            <div class="col-12"><label class="form-label small">SEO Başlığı</label><input type="text" name="seo_baslik" class="form-control form-control-sm" value="<?= e($edit['seo_baslik'] ?? '') ?>"></div>
            <div class="col-12"><label class="form-label small">SEO Açıklaması</label><textarea name="seo_aciklama" rows="2" class="form-control form-control-sm"><?= e($edit['seo_aciklama'] ?? '') ?></textarea></div>
            <div class="col-12"><div class="form-check"><input type="checkbox" name="aktif" id="bgAk" class="form-check-input" <?= ($edit['aktif']??1)?'checked':'' ?>><label for="bgAk" class="form-check-label small">Yayında</label></div></div>

            <!-- ====== Kampanya entegrasyonu (v1.1.49+) ====== -->
            <div class="col-12 mt-3">
              <div class="border-top pt-3">
                <div class="form-check">
                  <input type="checkbox" name="kampanya_yap" id="bgKmp" class="form-check-input" <?= $editKampanya ? 'checked' : '' ?> onchange="document.getElementById('kmpAlanlari').style.display=this.checked?'block':'none'">
                  <label for="bgKmp" class="form-check-label fw-semibold">
                    <i class="bi bi-megaphone-fill text-warning"></i>
                    Bu yazıyı kampanya pop-up olarak da göster
                  </label>
                </div>
                <small class="text-muted ms-4 d-block">İşaretlenirse blog yazısı sitenin pop-up alanında otomatik görüntülenir. Pop-up tıklandığında ziyaretçi blog yazısına yönlendirilir.</small>
              </div>

              <div id="kmpAlanlari" style="display:<?= $editKampanya ? 'block' : 'none' ?>" class="mt-3 p-3 rounded" style2="background:rgba(251,191,36,.04);border:1px dashed #fbbf24">
                <div class="row g-2">
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold">Pop-up Başlama Tarihi</label>
                    <input type="datetime-local" name="kmp_baslangic" class="form-control form-control-sm"
                           value="<?= $editKampanya && $editKampanya['baslangic_tarihi'] ? date('Y-m-d\TH:i', strtotime($editKampanya['baslangic_tarihi'])) : date('Y-m-d\TH:i') ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold">Pop-up Bitiş Tarihi</label>
                    <input type="datetime-local" name="kmp_bitis" class="form-control form-control-sm"
                           value="<?= $editKampanya && $editKampanya['bitis_tarihi'] ? date('Y-m-d\TH:i', strtotime($editKampanya['bitis_tarihi'])) : date('Y-m-d\TH:i', strtotime('+30 days')) ?>">
                    <small class="text-muted">Varsayılan: 30 gün sonra</small>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label small fw-semibold">Buton Metni</label>
                    <input type="text" name="kmp_buton_metin" class="form-control form-control-sm"
                           value="<?= e($editKampanya['buton_metin'] ?? 'Yazıyı Oku') ?>"
                           placeholder="Yazıyı Oku">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold">Buton Rengi</label>
                    <select name="kmp_buton_renk" class="form-select form-select-sm">
                      <?php $renkler = ['kirmizi'=>'Kırmızı (Mizan)', 'sari'=>'Sarı', 'mavi'=>'Mavi', 'yesil'=>'Yeşil'];
                      foreach ($renkler as $rk => $rl): ?>
                        <option value="<?= $rk ?>" <?= ($editKampanya['buton_renk'] ?? 'kirmizi') === $rk ? 'selected' : '' ?>><?= $rl ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label small fw-semibold">Gösterim Kuralı</label>
                    <select name="kmp_gosterim" class="form-select form-select-sm">
                      <?php $kurallar = ['gunde_bir'=>'Günde bir kez', 'oturum_basina'=>'Oturum başına 1 kez', 'her_ziyaret'=>'Her ziyaret', 'sadece_bir_kez'=>'Sadece 1 kez (kapanırsa bir daha asla)'];
                      foreach ($kurallar as $kk => $kl): ?>
                        <option value="<?= $kk ?>" <?= ($editKampanya['gosterim_kurali'] ?? 'gunde_bir') === $kk ? 'selected' : '' ?>><?= $kl ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold">Hedef Sayfa</label>
                    <select name="kmp_hedef" class="form-select form-select-sm">
                      <option value="tum_sayfalar" <?= ($editKampanya['hedef_sayfa'] ?? 'tum_sayfalar') === 'tum_sayfalar' ? 'selected' : '' ?>>Tüm Sayfalar</option>
                      <option value="anasayfa" <?= ($editKampanya['hedef_sayfa'] ?? '') === 'anasayfa' ? 'selected' : '' ?>>Sadece Anasayfa</option>
                    </select>
                  </div>

                  <div class="col-12">
                    <small class="text-muted">
                      <i class="bi bi-info-circle"></i>
                      Pop-up <strong>başlık ve içerik</strong> blog yazısından otomatik alınır. Tıklandığında <code>/blog/<?= e($edit['slug'] ?? 'yazi-slug') ?></code> adresine yönlendirir.
                    </small>
                  </div>

                  <?php if ($editKampanya): ?>
                  <div class="col-12">
                    <div class="alert alert-info py-2 px-3 mb-0 small">
                      <i class="bi bi-check-circle-fill"></i>
                      Bu yazı için kampanya zaten oluşturulmuş.
                      <strong>Gösterim:</strong> <?= (int)$editKampanya['gosterim_sayisi'] ?> /
                      <strong>Tıklama:</strong> <?= (int)$editKampanya['tiklama_sayisi'] ?>
                    </div>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
          <div class="mt-3">
            <button class="btn btn-primary btn-sm fw-semibold"><i class="bi bi-save"></i> Kaydet</button>
            <?php if ($edit): ?><a href="blog.php" class="btn btn-outline-secondary btn-sm">Vazgeç</a><?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
// Canli slug uretim - basligi yazarken slug alani otomatik doldur
(function() {
  var baslik = document.getElementById('blogBaslik');
  var slug   = document.getElementById('blogSlug');
  if (!baslik || !slug) return;

  // Türkçe karakter map
  var trMap = {'ı':'i','İ':'i','ğ':'g','Ğ':'g','ü':'u','Ü':'u','ş':'s','Ş':'s','ö':'o','Ö':'o','ç':'c','Ç':'c'};

  function slugify(text) {
    text = text.replace(/[ıİğĞüÜşŞöÖçÇ]/g, function(c) { return trMap[c] || c; });
    text = text.toLowerCase();
    text = text.replace(/[^a-z0-9]+/g, '-');
    text = text.replace(/^-+|-+$/g, '');
    return text || 'kayit';
  }

  // Slug bos veya kullanici degistirmemisse otomatik doldur
  var slugDokunuldu = slug.value.trim() !== '';

  baslik.addEventListener('input', function() {
    if (!slugDokunuldu) {
      slug.value = slugify(baslik.value);
    }
  });

  slug.addEventListener('input', function() {
    slugDokunuldu = slug.value.trim() !== '';
    // Yazdiginda da sanitize et (Türkçe yazmasin)
    var clean = slugify(slug.value);
    if (clean !== slug.value.toLowerCase()) {
      var pos = slug.selectionStart;
      slug.value = clean;
      try { slug.setSelectionRange(pos, pos); } catch(e){}
    }
  });

  // Sayfa acilisinda baslik dolu, slug bos ise otomatik doldur
  if (baslik.value.trim() && !slug.value.trim()) {
    slug.value = slugify(baslik.value);
    slugDokunuldu = false;
  }
})();
</script>

<?php require __DIR__ . '/_footer.php';
