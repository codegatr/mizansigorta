<?php
define('MZ_ADMIN', true);
$adminTitle = 'Profilim';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_helpers.php';

$me = db_row('SELECT * FROM ' . t('kullanicilar') . ' WHERE id=?', [user_id()]);
if (!$me) admin_redirect('logout.php', 'danger', 'Oturum geçersiz.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_assert_post();
    $act = (string)($_POST['action'] ?? '');

    if ($act === 'profil_guncelle') {
        $ad_soyad = trim((string)($_POST['ad_soyad'] ?? ''));
        $telefon  = normalize_phone((string)($_POST['telefon'] ?? ''));
        if ($ad_soyad === '') admin_redirect('profil.php', 'danger', 'Ad soyad zorunlu.');
        db_exec('UPDATE ' . t('kullanicilar') . ' SET ad_soyad=?, telefon=?, guncelleme_tarihi=NOW() WHERE id=?', [$ad_soyad, $telefon, user_id()]);
        audit_log('profil_guncelle', 'kullanici', user_id());
        admin_redirect('profil.php', 'success', 'Profil bilgileri güncellendi.');
    }

    if ($act === 'sifre_degistir') {
        $eski = (string)($_POST['eski_sifre'] ?? '');
        $yeni = (string)($_POST['yeni_sifre'] ?? '');
        $tekrar = (string)($_POST['tekrar_sifre'] ?? '');
        if (!password_verify($eski, $me['sifre_hash'])) admin_redirect('profil.php', 'danger', 'Mevcut şifre hatalı.');
        if (strlen($yeni) < 8) admin_redirect('profil.php', 'danger', 'Yeni şifre en az 8 karakter olmalı.');
        if ($yeni !== $tekrar) admin_redirect('profil.php', 'danger', 'Yeni şifreler eşleşmiyor.');
        $hash = password_hash($yeni, PASSWORD_BCRYPT);
        db_exec('UPDATE ' . t('kullanicilar') . ' SET sifre_hash=?, guncelleme_tarihi=NOW() WHERE id=?', [$hash, user_id()]);
        audit_log('sifre_degistir', 'kullanici', user_id());
        admin_redirect('profil.php', 'success', 'Şifreniz güncellendi.');
    }
}

// Son işlem kayitlari (audit log)
$myLogs = db_all('SELECT * FROM ' . t('audit_log') . ' WHERE kullanici_id=? ORDER BY olusturma_tarihi DESC LIMIT 20', [user_id()]);
?>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-person-circle text-warning"></i> Kişisel Bilgiler</h6>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="profil_guncelle">
          <div class="row g-2">
            <div class="col-12"><label class="form-label small">Ad Soyad *</label><input type="text" name="ad_soyad" required class="form-control form-control-sm" value="<?= e($me['ad_soyad']) ?>"></div>
            <div class="col-12"><label class="form-label small">E-posta</label><input type="email" class="form-control form-control-sm" value="<?= e($me['email']) ?>" readonly disabled><div class="form-text small">E-posta değişikliği için yöneticinize başvurun.</div></div>
            <div class="col-12"><label class="form-label small">Telefon</label><input type="tel" name="telefon" class="form-control form-control-sm" value="<?= e($me['telefon']) ?>"></div>
            <div class="col-12"><label class="form-label small">Rol</label><input type="text" class="form-control form-control-sm" value="<?= e(['superadmin'=>'Süper Admin','admin'=>'Yönetici','operator'=>'Operatör','satis'=>'Satış'][$me['rol']] ?? $me['rol']) ?>" readonly disabled></div>
          </div>
          <div class="mt-3"><button class="btn btn-primary btn-sm fw-semibold"><i class="bi bi-save"></i> Kaydet</button></div>
        </form>
      </div>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-key text-warning"></i> Şifre Değiştir</h6>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="sifre_degistir">
          <div class="row g-2">
            <div class="col-12"><label class="form-label small">Mevcut Şifre *</label><input type="password" name="eski_sifre" required class="form-control form-control-sm"></div>
            <div class="col-md-6"><label class="form-label small">Yeni Şifre *</label><input type="password" name="yeni_sifre" required minlength="8" class="form-control form-control-sm"></div>
            <div class="col-md-6"><label class="form-label small">Tekrar *</label><input type="password" name="tekrar_sifre" required minlength="8" class="form-control form-control-sm"></div>
          </div>
          <div class="mt-3"><button class="btn btn-warning btn-sm fw-semibold"><i class="bi bi-shield-lock"></i> Şifreyi Güncelle</button></div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-info-circle text-warning"></i> Hesap Bilgisi</h6>
        <dl class="row mb-0 small">
          <dt class="col-sm-5 text-muted">Kayıt Tarihi</dt><dd class="col-sm-7"><?= tr_datetime($me['olusturma_tarihi']) ?></dd>
          <dt class="col-sm-5 text-muted">Son Güncelleme</dt><dd class="col-sm-7"><?= tr_datetime($me['guncelleme_tarihi']) ?></dd>
          <dt class="col-sm-5 text-muted">Son Giriş</dt><dd class="col-sm-7"><?= $me['son_giris'] ? tr_datetime($me['son_giris']) : '-' ?></dd>
          <dt class="col-sm-5 text-muted">Son IP</dt><dd class="col-sm-7"><?= e($me['son_giris_ip'] ?: '-') ?></dd>
        </dl>
      </div>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-clock-history text-warning"></i> Son İşlemleriniz</h6>
        <div class="list-group list-group-flush small">
          <?php foreach ($myLogs as $l): ?>
            <div class="list-group-item px-0 py-2">
              <div class="d-flex justify-content-between">
                <span><b><?= e($l['eylem']) ?></b><?php if ($l['nesne_tip']): ?> · <?= e($l['nesne_tip']) ?>#<?= (int)$l['nesne_id'] ?><?php endif; ?></span>
                <span class="text-muted"><?= tr_datetime($l['olusturma_tarihi']) ?></span>
              </div>
              <?php if ($l['aciklama']): ?><div class="text-muted"><?= e($l['aciklama']) ?></div><?php endif; ?>
            </div>
          <?php endforeach; ?>
          <?php if (!$myLogs): ?><div class="text-muted">Henüz işlem yok.</div><?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php';
