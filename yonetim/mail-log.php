<?php
define('MZ_ADMIN', true);
$adminTitle = 'Mail Gönderim Logu';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_helpers.php';
require_role('superadmin', 'admin');

// Tekrar gönder POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tekrar_gonder') {
    csrf_assert_post();
    $id = (int)($_POST['id'] ?? 0);
    $log = db_row('SELECT * FROM ' . t('mail_log') . ' WHERE id=?', [$id]);
    if (!$log) {
        admin_redirect('mail-log.php', 'danger', 'Log kaydı bulunamadı.');
    }

    // Log'daki BCC ile mevcut talep_bildirim_alicilari() BCC'sini birlestir
    // Boylece eski (BCC'siz) bir maili tekrar gonderirken otomatik kopya gider
    $bccLog = $log['bcc_listesi'] ? array_filter(array_map('trim', explode(',', (string)$log['bcc_listesi']))) : [];
    $bccAktif = talep_bildirim_alicilari()['bcc'] ?? [];
    $bccArr = array_values(array_unique(array_merge($bccLog, $bccAktif)));

    $opts = [
        'ilgili_tip' => $log['ilgili_tip'] ?? 'tekrar_gonder',
        'ilgili_id'  => $log['ilgili_id'] ?? null,
    ];
    if ($bccArr) $opts['bcc'] = $bccArr;
    if (!empty($log['reply_to'])) $opts['reply_to'] = $log['reply_to'];

    $r = send_mail($log['alici'], (string)$log['konu'], (string)$log['govde_html'], '', $opts);
    audit_log('mail_tekrar_gonder', 'mail_log', $id, json_encode(['ok' => !empty($r['ok']), 'msg' => $r['msg'] ?? '', 'bcc' => $bccArr], JSON_UNESCAPED_UNICODE));
    if (!empty($r['ok'])) {
        $msg = 'Mail tekrar gönderildi.';
        if ($bccArr) $msg .= ' BCC: ' . implode(', ', $bccArr);
        admin_redirect('mail-log.php', 'success', $msg);
    } else {
        admin_redirect('mail-log.php', 'danger', 'Mail gönderilemedi: ' . ($r['msg'] ?? 'Bilinmeyen hata'));
    }
}

// Filtreler
$q          = trim((string)($_GET['q'] ?? ''));
$durum      = trim((string)($_GET['durum'] ?? ''));
$ilgili_tip = trim((string)($_GET['ilgili_tip'] ?? ''));
$tarih1     = $_GET['tarih1'] ?? '';
$tarih2     = $_GET['tarih2'] ?? '';

$where = ['1=1']; $args = [];
if ($q !== '') {
    $where[] = '(alici LIKE ? OR bcc_listesi LIKE ? OR konu LIKE ?)';
    $like = "%$q%";
    $args = array_merge($args, [$like, $like, $like]);
}
if ($durum !== '' && in_array($durum, ['basarili', 'hatali'], true)) {
    $where[] = 'durum=?';
    $args[] = $durum;
}
if ($ilgili_tip !== '') {
    $where[] = 'ilgili_tip=?';
    $args[] = $ilgili_tip;
}
if ($tarih1 !== '') {
    $where[] = 'olusturma_tarihi >= ?';
    $args[] = $tarih1 . ' 00:00:00';
}
if ($tarih2 !== '') {
    $where[] = 'olusturma_tarihi <= ?';
    $args[] = $tarih2 . ' 23:59:59';
}
$wsql = implode(' AND ', $where);

$page  = max(1, (int)($_GET['s'] ?? 1));
$per   = 50;
$total = (int)db_value('SELECT COUNT(*) FROM ' . t('mail_log') . " WHERE $wsql", $args);
$pag   = paginate($total, $per, $page);
$rows  = db_all('SELECT * FROM ' . t('mail_log') . " WHERE $wsql ORDER BY olusturma_tarihi DESC LIMIT $per OFFSET " . $pag['offset'], $args);

// İstatistikler (filtre uygulanmadan, son 30 gun)
$stats = db_row('
    SELECT
        COUNT(*) AS toplam,
        SUM(durum = "basarili") AS basarili,
        SUM(durum = "hatali") AS hatali,
        COUNT(DISTINCT alici) AS farkli_alici
    FROM ' . t('mail_log') . '
    WHERE olusturma_tarihi >= DATE_SUB(NOW(), INTERVAL 30 DAY)');

// İlgili tip için liste (filtre dropdown)
$tipler = [
    'genel'           => 'Genel',
    'teklif'          => 'Teklif',
    'hasar'           => 'Hasar',
    'iletisim'        => 'İletişim',
    'temsilci'        => 'Temsilci Başvurusu',
    'durum_bildirim'  => 'Durum Bildirimi',
    'hatirlatma'      => 'Hatırlatma',
    'sifre'           => 'Şifre',
    'test'            => 'SMTP Test',
    'tekrar_gonder'   => 'Tekrar Gönderim',
];
?>

<h1 class="page-title"><i class="bi bi-envelope-paper text-primary"></i> Mail Gönderim Logu</h1>

<!-- Istatistik kartlari -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card border-0 shadow-sm h-100" style="background:linear-gradient(135deg,#0d6efd,#0a58ca);color:#fff">
      <div class="card-body">
        <div class="text-white-50 small fw-semibold text-uppercase mb-1">Son 30 Gün Toplam</div>
        <div class="h2 fw-bold mb-0"><?= number_format((int)($stats['toplam'] ?? 0), 0, ',', '.') ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border-0 shadow-sm h-100" style="background:linear-gradient(135deg,#22c55e,#15803d);color:#fff">
      <div class="card-body">
        <div class="text-white-50 small fw-semibold text-uppercase mb-1">Başarılı</div>
        <div class="h2 fw-bold mb-0"><?= number_format((int)($stats['basarili'] ?? 0), 0, ',', '.') ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border-0 shadow-sm h-100" style="background:linear-gradient(135deg,#dc3545,#a91020);color:#fff">
      <div class="card-body">
        <div class="text-white-50 small fw-semibold text-uppercase mb-1">Hatalı</div>
        <div class="h2 fw-bold mb-0"><?= number_format((int)($stats['hatali'] ?? 0), 0, ',', '.') ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border-0 shadow-sm h-100" style="background:linear-gradient(135deg,#475569,#334155);color:#fff">
      <div class="card-body">
        <div class="text-white-50 small fw-semibold text-uppercase mb-1">Farklı Alıcı</div>
        <div class="h2 fw-bold mb-0"><?= number_format((int)($stats['farkli_alici'] ?? 0), 0, ',', '.') ?></div>
      </div>
    </div>
  </div>
</div>

<!-- Filtreler -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <form method="get" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small mb-1">Arama</label>
        <input type="text" name="q" value="<?= e($q) ?>" class="form-control form-control-sm" placeholder="Alıcı, BCC, konu...">
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">Durum</label>
        <select name="durum" class="form-select form-select-sm">
          <option value="">Tümü</option>
          <option value="basarili" <?= $durum==='basarili'?'selected':'' ?>>Başarılı</option>
          <option value="hatali"   <?= $durum==='hatali'  ?'selected':'' ?>>Hatalı</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">Tip</label>
        <select name="ilgili_tip" class="form-select form-select-sm">
          <option value="">Tümü</option>
          <?php foreach ($tipler as $k=>$v): ?>
            <option value="<?= e($k) ?>" <?= $ilgili_tip===$k?'selected':'' ?>><?= e($v) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">Başlangıç</label>
        <input type="date" name="tarih1" value="<?= e($tarih1) ?>" class="form-control form-control-sm">
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">Bitiş</label>
        <input type="date" name="tarih2" value="<?= e($tarih2) ?>" class="form-control form-control-sm">
      </div>
      <div class="col-md-1 d-flex gap-1">
        <button class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
        <a href="mail-log.php" class="btn btn-sm btn-outline-secondary" title="Temizle"><i class="bi bi-x-lg"></i></a>
      </div>
    </form>
  </div>
</div>

<!-- Liste -->
<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover table-sm align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th width="140">Tarih</th>
            <th width="80">Durum</th>
            <th>Alıcı</th>
            <th>Konu</th>
            <th>BCC</th>
            <th width="100">Tip</th>
            <th width="80">Aksiyon</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td class="small text-nowrap text-muted"><?= tr_datetime($r['olusturma_tarihi']) ?></td>
              <td>
                <?php if ($r['durum'] === 'basarili'): ?>
                  <span class="badge bg-success"><i class="bi bi-check-circle"></i> Gönderildi</span>
                <?php else: ?>
                  <span class="badge bg-danger" title="<?= e((string)$r['hata_mesaji']) ?>"><i class="bi bi-x-circle"></i> Hata</span>
                <?php endif; ?>
              </td>
              <td class="small"><a href="mailto:<?= e($r['alici']) ?>" class="text-decoration-none"><?= e($r['alici']) ?></a></td>
              <td class="small text-truncate" style="max-width:280px" title="<?= e((string)$r['konu']) ?>"><?= e((string)$r['konu']) ?></td>
              <td class="small text-truncate text-muted" style="max-width:180px" title="<?= e((string)$r['bcc_listesi']) ?>">
                <?php if (!empty($r['bcc_listesi'])): ?>
                  <i class="bi bi-people"></i> <?= e((string)$r['bcc_listesi']) ?>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td><span class="badge bg-light text-dark border"><?= e($tipler[$r['ilgili_tip']] ?? $r['ilgili_tip']) ?></span></td>
              <td class="text-nowrap">
                <button type="button" class="btn btn-sm btn-outline-primary" title="Detay"
                  onclick='showDetail(<?= json_encode([
                    "id"        => (int)$r["id"],
                    "tarih"     => tr_datetime($r["olusturma_tarihi"]),
                    "durum"     => $r["durum"],
                    "alici"     => (string)$r["alici"],
                    "cc"        => (string)($r["cc_listesi"] ?? ""),
                    "bcc"       => (string)($r["bcc_listesi"] ?? ""),
                    "reply_to"  => (string)($r["reply_to"] ?? ""),
                    "konu"      => (string)$r["konu"],
                    "govde"     => (string)($r["govde_html"] ?? ""),
                    "hata"      => (string)($r["hata_mesaji"] ?? ""),
                    "smtp"      => (string)($r["smtp_yanit"] ?? ""),
                    "tip"       => (string)($r["ilgili_tip"] ?? ""),
                    "ilgili_id" => $r["ilgili_id"] ? (int)$r["ilgili_id"] : null,
                  ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>)'>
                  <i class="bi bi-eye"></i>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?>
            <tr><td colspan="7" class="text-center text-muted py-5">
              <i class="bi bi-inbox display-4 d-block mb-2"></i>
              Henüz mail gönderim kaydı yok.
            </td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if ($pag['pages'] > 1): ?>
  <ul class="pagination pagination-sm mt-3 justify-content-end">
    <?php
      $qs = $_GET; unset($qs['s']);
      $base = 'mail-log.php?' . http_build_query($qs);
      for ($i = 1; $i <= $pag['pages']; $i++):
    ?>
      <li class="page-item <?= $i === $pag['page'] ? 'active' : '' ?>">
        <a class="page-link" href="<?= e($base) ?>&s=<?= $i ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
<?php endif; ?>

<!-- Detay Modal -->
<div class="modal fade" id="detayModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-envelope-open"></i> Mail Detayı</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-2 small mb-3">
          <div class="col-md-4"><b>Tarih:</b> <span id="d_tarih"></span></div>
          <div class="col-md-4"><b>Durum:</b> <span id="d_durum"></span></div>
          <div class="col-md-4"><b>Tip:</b> <span id="d_tip"></span></div>
          <div class="col-md-12"><b>Alıcı (To):</b> <span id="d_alici"></span></div>
          <div class="col-md-12"><b>BCC (gizli kopya):</b> <span id="d_bcc"></span></div>
          <div class="col-md-12"><b>CC:</b> <span id="d_cc"></span></div>
          <div class="col-md-12"><b>Reply-To:</b> <span id="d_reply"></span></div>
          <div class="col-md-12"><b>Konu:</b> <span id="d_konu"></span></div>
          <div class="col-md-12" id="d_hata_row" style="display:none">
            <div class="alert alert-danger small mb-1 mt-2"><b><i class="bi bi-exclamation-triangle"></i> Hata:</b> <span id="d_hata"></span></div>
          </div>
          <div class="col-md-12" id="d_smtp_row" style="display:none">
            <details class="mt-2"><summary class="small text-muted">SMTP Yanıtı</summary><pre class="small bg-light p-2 mt-1 mb-0" id="d_smtp" style="max-height:120px;overflow:auto"></pre></details>
          </div>
        </div>

        <hr>
        <div class="d-flex gap-2 mb-2">
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleView('html')"><i class="bi bi-window"></i> HTML Önizleme</button>
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleView('source')"><i class="bi bi-code-slash"></i> Kaynak</button>
        </div>
        <div id="d_html_wrap" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;background:#fff">
          <iframe id="d_iframe" sandbox="allow-same-origin" style="width:100%;height:520px;border:0;background:#fff"></iframe>
        </div>
        <div id="d_source_wrap" style="display:none">
          <pre class="small bg-light p-2 mb-0" id="d_source" style="max-height:520px;overflow:auto"></pre>
        </div>
      </div>
      <div class="modal-footer">
        <form method="post" id="resendForm" style="display:inline">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="tekrar_gonder">
          <input type="hidden" name="id" id="d_id">
          <button type="submit" class="btn btn-warning btn-sm" id="resendBtn"
                  onclick="return confirm('Bu mail aynı alıcıya tekrar gönderilsin mi?')">
            <i class="bi bi-arrow-clockwise"></i> Tekrar Gönder
          </button>
        </form>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Kapat</button>
      </div>
    </div>
  </div>
</div>

<script>
function showDetail(d) {
  document.getElementById('d_id').value     = d.id;
  document.getElementById('d_tarih').textContent = d.tarih;
  document.getElementById('d_durum').innerHTML = d.durum === 'basarili'
    ? '<span class="badge bg-success">Gönderildi</span>'
    : '<span class="badge bg-danger">Hata</span>';
  document.getElementById('d_tip').textContent = d.tip || 'genel';
  document.getElementById('d_alici').textContent = d.alici;
  document.getElementById('d_konu').textContent = d.konu;

  // BCC/CC/Reply-To her zaman goster - bossa kirmizi vurgulu '— gonderilmedi' uyarisi
  setFieldValue('d_bcc',   d.bcc,      'BCC adresi yok — bu mail kopya gönderilmemiş');
  setFieldValue('d_cc',    d.cc,       '—');
  setFieldValue('d_reply', d.reply_to, '—');

  toggleRow('d_hata_row', 'd_hata', d.hata);
  toggleRow('d_smtp_row', 'd_smtp', d.smtp);

  // HTML onizleme - iframe'e srcdoc ile yaz
  var iframe = document.getElementById('d_iframe');
  iframe.srcdoc = d.govde || '<p style="padding:1rem;color:#999">Boş içerik</p>';
  document.getElementById('d_source').textContent = d.govde || '';

  var btn = document.getElementById('resendBtn');
  btn.style.display = d.alici ? 'inline-block' : 'none';

  toggleView('html');
  new bootstrap.Modal(document.getElementById('detayModal')).show();
}

function setFieldValue(id, value, emptyText) {
  var el = document.getElementById(id);
  if (value && value.trim() !== '') {
    el.textContent = value;
    el.style.color = '';
    el.style.fontStyle = '';
  } else {
    el.textContent = emptyText || '—';
    el.style.color = (emptyText && emptyText.indexOf('gönderilmemiş') !== -1) ? '#dc3545' : '#9ca3af';
    el.style.fontStyle = 'italic';
  }
}

function toggleRow(rowId, valId, val) {
  var row = document.getElementById(rowId);
  if (val && val.trim() !== '') {
    document.getElementById(valId).textContent = val;
    row.style.display = '';
  } else {
    row.style.display = 'none';
  }
}

function toggleView(mode) {
  document.getElementById('d_html_wrap').style.display    = mode === 'html'   ? '' : 'none';
  document.getElementById('d_source_wrap').style.display  = mode === 'source' ? '' : 'none';
}
</script>

<?php require __DIR__ . '/_footer.php';
