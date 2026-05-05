<?php
define('MZ_ADMIN', true);
$adminTitle = 'Giriş';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_helpers.php';

// Zaten giris yapilmissa panele yonlendir
if (user_id()) {
    header('Location: index.php');
    exit;
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_assert_post();
    $email = trim((string)($_POST['email'] ?? ''));
    $pass  = (string)($_POST['password'] ?? '');
    $r = user_login($email, $pass);
    if ($r['ok']) {
        flash_set('success', 'Hoş geldiniz, ' . ($r['user']['ad_soyad'] ?? ''));
        header('Location: index.php');
        exit;
    }
    $err = $r['error'] ?? 'Giriş başarısız.';
}
?>

<div class="mz-login-wrap">
  <div class="mz-login-stack">
    <div class="mz-login-card">
      <div class="mz-logo-lg text-center mb-4">
        <img src="<?= asset('assets/img/logo.png') ?>" alt="Mizan Sigorta">
        <p class="text-muted small mt-2 mb-0">Yönetim Paneli</p>
      </div>

      <?php if ($err): ?>
        <div class="alert alert-danger small"><i class="bi bi-exclamation-triangle"></i> <?= e($err) ?></div>
      <?php endif; ?>

      <form method="post" novalidate autocomplete="off">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label small fw-semibold">E-posta</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" name="email" class="form-control" required autofocus value="<?= e($_POST['email'] ?? '') ?>">
          </div>
        </div>
        <div class="mb-4">
          <label class="form-label small fw-semibold">Parola</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" id="pw" class="form-control" required>
            <button type="button" class="btn btn-outline-secondary" onclick="var p=document.getElementById('pw'); p.type=p.type==='password'?'text':'password';">
              <i class="bi bi-eye"></i>
            </button>
          </div>
        </div>
        <button class="btn btn-primary w-100 fw-semibold" type="submit"><i class="bi bi-box-arrow-in-right"></i> Giriş Yap</button>
      </form>

      <div class="text-center mt-4">
        <a href="<?= u('/') ?>" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left"></i> Siteye Dön</a>
      </div>
    </div>

    <div class="mz-login-foot">
      <small>v<?= e(SITE_VERSION) ?> · <a href="https://codega.com.tr" target="_blank" rel="noopener">CODEGA</a></small>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php';
