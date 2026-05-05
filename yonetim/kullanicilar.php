<?php
define('MZ_ADMIN', true);
$adminTitle = 'Kullanıcılar';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_helpers.php';
require_role('superadmin', 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_assert_post();
    $act = (string)($_POST['action'] ?? '');

    if ($act === 'save') {
        $id            = (int)($_POST['id'] ?? 0);
        $ad_soyad      = trim((string)($_POST['ad_soyad'] ?? ''));
        $email         = strtolower(trim((string)($_POST['email'] ?? '')));
        $kullanici_adi = trim((string)($_POST['kullanici_adi'] ?? ''));
        $rol           = (string)($_POST['rol'] ?? 'operator');
        $telefon       = normalize_phone((string)($_POST['telefon'] ?? ''));
        $aktif         = isset($_POST['aktif']) ? 1 : 0;
        $sifre         = (string)($_POST['sifre'] ?? '');

        // Sadece superadmin baskasini superadmin yapabilir
        if ($rol === 'superadmin' && !is_superadmin()) $rol = 'admin';

        if ($ad_soyad === '' || $email === '') admin_redirect('kullanicilar.php', 'danger', 'Ad soyad ve e-posta zorunlu.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) admin_redirect('kullanicilar.php', 'danger', 'Geçerli bir e-posta giriniz.');

        // Kullanici adi opsiyonel, ama girildiyse formati dogrula:
        // - Harf (Turkce dahil tum diller \p{L}), rakam, nokta, alt cizgi, tire
        // - Bosluk yok (login icin pratik degil)
        // - @ yok (email ile cakismayi onler)
        // - 3-50 karakter
        // Buyuk/kucuk harf serbest. DB collation utf8mb4_unicode_ci sayesinde
        // 'Yunus' ve 'yunus' otomatik ayni kabul edilir (UNIQUE check + login ararken).
        if ($kullanici_adi !== '') {
            if (!preg_match('/^[\p{L}0-9_.\-]{3,50}$/u', $kullanici_adi)) {
                admin_redirect('kullanicilar.php', 'danger', 'Kullanıcı adı 3-50 karakter olmalı; harf, rakam, nokta, alt çizgi ve tire kullanılabilir (boşluk olmasın).');
            }
            if (strpos($kullanici_adi, '@') !== false) {
                admin_redirect('kullanicilar.php', 'danger', 'Kullanıcı adı "@" içeremez (e-posta gibi görünür, çakışmayı önler).');
            }
        }

        // Email tekrar kontrol
        $existing = db_value('SELECT id FROM ' . t('kullanicilar') . ' WHERE email=? AND id<>?', [$email, $id]);
        if ($existing) admin_redirect('kullanicilar.php', 'danger', 'Bu e-posta başka bir kullanıcıya ait.');

        // Kullanici adi tekrar kontrol (girildiyse)
        if ($kullanici_adi !== '') {
            $existingKa = db_value('SELECT id FROM ' . t('kullanicilar') . ' WHERE kullanici_adi=? AND id<>?', [$kullanici_adi, $id]);
            if ($existingKa) admin_redirect('kullanicilar.php', 'danger', 'Bu kullanıcı adı başka bir hesaba ait.');
        }

        $kaForDb = $kullanici_adi !== '' ? $kullanici_adi : null;

        if ($id) {
            $params = [$ad_soyad, $email, $kaForDb, $telefon, $rol, $aktif];
            $sql = 'UPDATE ' . t('kullanicilar') . ' SET ad_soyad=?, email=?, kullanici_adi=?, telefon=?, rol=?, aktif=?';
            if ($sifre !== '') {
                if (strlen($sifre) < 8) admin_redirect('kullanicilar.php', 'danger', 'Şifre en az 8 karakter olmalı.');
                $sql .= ', sifre_hash=?';
                $params[] = password_hash($sifre, PASSWORD_BCRYPT);
            }
            $sql .= ', guncelleme_tarihi=NOW() WHERE id=?';
            $params[] = $id;
            db_exec($sql, $params);
            audit_log('kullanici_guncelle', 'kullanici', $id);
            admin_redirect('kullanicilar.php', 'success', 'Kullanıcı güncellendi.');
        } else {
            if (strlen($sifre) < 8) admin_redirect('kullanicilar.php', 'danger', 'Yeni kullanıcı için şifre (en az 8 karakter) zorunlu.');
            $hash = password_hash($sifre, PASSWORD_BCRYPT);
            // kullanici_adi bos ise email'in @ oncesinden otomatik uret
            if ($kaForDb === null) {
                $auto = strtolower(substr($email, 0, strpos($email, '@')));
                $auto = preg_replace('/[^a-z0-9_.-]/', '', $auto);
                if ($auto && strlen($auto) >= 3) {
                    // Cakismayi kontrol et, varsa sayi ekle
                    $try = $auto; $i = 1;
                    while (db_value('SELECT id FROM ' . t('kullanicilar') . ' WHERE kullanici_adi=?', [$try])) {
                        $try = $auto . $i; $i++;
                        if ($i > 99) { $try = null; break; }
                    }
                    $kaForDb = $try;
                }
            }
            db_exec('INSERT INTO ' . t('kullanicilar') . ' (ad_soyad,email,kullanici_adi,telefon,sifre_hash,rol,aktif,olusturma_tarihi) VALUES (?,?,?,?,?,?,?,NOW())',
                [$ad_soyad, $email, $kaForDb, $telefon, $hash, $rol, $aktif]);
            audit_log('kullanici_ekle', 'kullanici', db_last_id());
            admin_redirect('kullanicilar.php', 'success', 'Yeni kullanıcı eklendi.' . ($kaForDb ? ' Kullanıcı adı: ' . $kaForDb : ''));
        }
    }

    if ($act === 'sil') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === user_id()) admin_redirect('kullanicilar.php', 'danger', 'Kendi hesabınızı silemezsiniz.');
        $u = db_row('SELECT rol FROM ' . t('kullanicilar') . ' WHERE id=?', [$id]);
        if ($u && $u['rol'] === 'superadmin' && !is_superadmin()) admin_redirect('kullanicilar.php', 'danger', 'Süper admin kullanıcıları silmek için süper admin yetkisi gerekli.');
        db_exec('DELETE FROM ' . t('kullanicilar') . ' WHERE id=?', [$id]);
        audit_log('kullanici_sil', 'kullanici', $id);
        admin_redirect('kullanicilar.php', 'success', 'Kullanıcı silindi.');
    }

    if ($act === 'kilit_ac') {
        $id = (int)($_POST['id'] ?? 0);
        db_exec('UPDATE ' . t('kullanicilar') . ' SET hatali_giris=0, kilit_bitis=NULL WHERE id=?', [$id]);
        audit_log('kilit_ac', 'kullanici', $id);
        admin_redirect('kullanicilar.php', 'success', 'Hesap kilidi açıldı.');
    }
}

$rows = db_all('SELECT * FROM ' . t('kullanicilar') . ' ORDER BY rol, ad_soyad');
$editId = (int)($_GET['edit'] ?? 0);
$edit = $editId ? db_row('SELECT * FROM ' . t('kullanicilar') . ' WHERE id=?', [$editId]) : null;
?>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Kullanıcı</th><th>Rol</th><th>Son Giriş</th><th>Durum</th><th width="120"></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
              <?php $kilitli = $r['kilit_bitis'] && strtotime($r['kilit_bitis']) > time(); ?>
              <tr>
                <td>
                  <b><?= e($r['ad_soyad']) ?></b>
                  <?php if ($r['id']==user_id()): ?><span class="badge bg-info ms-1">Siz</span><?php endif; ?>
                  <div class="small text-muted">
                    <i class="bi bi-envelope"></i> <?= e($r['email']) ?>
                    <?php if (!empty($r['kullanici_adi'])): ?>
                      <span class="ms-2"><i class="bi bi-person-badge"></i> <code><?= e($r['kullanici_adi']) ?></code></span>
                    <?php endif; ?>
                  </div>
                </td>
                <td>
                  <?php
                    $rb = ['superadmin'=>'danger','admin'=>'warning text-dark','operator'=>'info','satis'=>'primary'][$r['rol']] ?? 'secondary';
                    $rl = ['superadmin'=>'Süper Admin','admin'=>'Yönetici','operator'=>'Operatör','satis'=>'Satış'][$r['rol']] ?? $r['rol'];
                  ?>
                  <span class="badge bg-<?= $rb ?>"><?= e($rl) ?></span>
                </td>
                <td class="small text-muted">
                  <?= $r['son_giris'] ? tr_datetime($r['son_giris']) : 'Hiç' ?>
                  <?php if ($r['son_giris_ip']): ?><div class="text-muted">IP: <?= e($r['son_giris_ip']) ?></div><?php endif; ?>
                </td>
                <td>
                  <?= $r['aktif'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Pasif</span>' ?>
                  <?php if ($kilitli): ?><span class="badge bg-danger">Kilitli</span><?php endif; ?>
                </td>
                <td>
                  <?php if ($kilitli): ?>
                    <form method="post" class="d-inline">
                      <?= csrf_field() ?><input type="hidden" name="action" value="kilit_ac"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                      <button class="btn btn-sm btn-warning" title="Kilidi Aç"><i class="bi bi-unlock"></i></button>
                    </form>
                  <?php endif; ?>
                  <a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int)$r['id'] ?>"><i class="bi bi-pencil"></i></a>
                  <?php if ($r['id'] != user_id()): ?>
                    <form method="post" class="d-inline" onsubmit="return confirm('Kullanıcı silinsin mi?');">
                      <?= csrf_field() ?><input type="hidden" name="action" value="sil"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                      <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-<?= $edit?'pencil-square':'plus-square' ?> text-warning"></i> <?= $edit?'Düzenle':'Yeni Kullanıcı' ?></h6>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
          <div class="row g-2">
            <div class="col-12"><label class="form-label small">Ad Soyad *</label><input type="text" name="ad_soyad" required class="form-control form-control-sm" value="<?= e($edit['ad_soyad'] ?? '') ?>"></div>
            <div class="col-12"><label class="form-label small">E-posta *</label><input type="email" name="email" required class="form-control form-control-sm" value="<?= e($edit['email'] ?? '') ?>"></div>
            <div class="col-12">
              <label class="form-label small">Kullanıcı Adı <span class="text-muted">(opsiyonel — login için kolaylık)</span></label>
              <input type="text" name="kullanici_adi" class="form-control form-control-sm"
                     maxlength="50"
                     value="<?= e($edit['kullanici_adi'] ?? '') ?>"
                     placeholder="Yunus, Oktay, Beyza, Mesut, mehmet.demir, satis_01...">
              <div class="form-text small">3-50 karakter. Türkçe karakter ve büyük harf serbest. <code>@</code> ve boşluk olmasın. Boş bırakırsan e-posta adresinin <code>@</code> öncesinden otomatik üretilir.</div>
            </div>
            <div class="col-12"><label class="form-label small">Telefon</label><input type="tel" name="telefon" class="form-control form-control-sm" value="<?= e($edit['telefon'] ?? '') ?>"></div>
            <div class="col-12"><label class="form-label small">Rol *</label>
              <select name="rol" class="form-select form-select-sm" required>
                <?php if (is_superadmin()): ?><option value="superadmin"<?= ($edit['rol']??'')==='superadmin'?' selected':'' ?>>Süper Admin</option><?php endif; ?>
                <option value="admin"<?= ($edit['rol']??'')==='admin'?' selected':'' ?>>Yönetici</option>
                <option value="operator"<?= ($edit['rol']??'operator')==='operator'?' selected':'' ?>>Operatör</option>
                <option value="satis"<?= ($edit['rol']??'')==='satis'?' selected':'' ?>>Satış Temsilcisi</option>
              </select>
            </div>
            <div class="col-12"><label class="form-label small"><?= $edit ? 'Şifre (değiştirmek için doldurun)' : 'Şifre *' ?></label>
              <input type="password" name="sifre" class="form-control form-control-sm" minlength="8" <?= $edit?'':'required' ?>>
              <div class="form-text small">En az 8 karakter</div>
            </div>
            <div class="col-12"><div class="form-check"><input type="checkbox" name="aktif" id="kuAk" class="form-check-input" <?= ($edit['aktif']??1)?'checked':'' ?>><label for="kuAk" class="form-check-label small">Aktif</label></div></div>
          </div>
          <div class="mt-3">
            <button class="btn btn-primary btn-sm fw-semibold"><i class="bi bi-save"></i> Kaydet</button>
            <?php if ($edit): ?><a href="kullanicilar.php" class="btn btn-outline-secondary btn-sm">Vazgeç</a><?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php';
