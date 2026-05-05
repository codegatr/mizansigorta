<?php
if (!defined('MZ_ADMIN')) { http_response_code(403); exit; }
$current = basename($_SERVER['SCRIPT_NAME']);
?>

  </main>

<?php if ($current !== 'login.php'): ?>
  <footer class="mz-admin-foot">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
      <small class="text-muted">&copy; <?= date('Y') ?> Mizan Sigorta. Tüm hakları saklıdır.</small>
      <small class="text-muted">v<?= e(MIZAN_RUNTIME_VERSION) ?> · <a href="https://codega.com.tr" target="_blank" class="text-decoration-none">CODEGA Yazılım</a></small>
    </div>
  </footer>
</div><!-- /mz-admin-main -->
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('assets/js/admin.js') ?>"></script>
</body>
</html>
