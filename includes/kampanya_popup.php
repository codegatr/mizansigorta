<?php
/**
 * Mizan Sigorta - Kampanya Pop-up (Public)
 * includes/kampanya_popup.php
 *
 * footer.php icinde include edilir. mz_kampanyalar tablosundan
 * o anda yayinda olan (aktif=1 AND NOW() BETWEEN baslangic AND bitis)
 * en yuksek sirali kampanyayi cekip modal olarak render eder.
 *
 * - Hedef sayfa kontrolu (anasayfa/tum_sayfalar)
 * - localStorage ile gosterim kurali ('her_ziyaret','oturum_basina','gunde_bir','sadece_bir_kez')
 * - Acilis gecikmesi (saniye)
 * - Buton tiklama -> /api/kampanya-tik?id=X (tiklama sayaci artirir)
 * - Gosterim sayaci modal acildiginda /api/kampanya-goster?id=X cagrilir
 */

if (!defined('MIZAN_BOOT')) return;

// API endpoint sayfasi ise renderlama
$_uri_check = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($_uri_check, '/api/') !== false) return;

// Hangi sayfadayiz - anasayfa kontrolu icin
$_isHome = false;
$_path = parse_url($_uri_check, PHP_URL_PATH) ?: '/';
$_path = rtrim($_path, '/') ?: '/';
if ($_path === '/' || $_path === '/index.php') $_isHome = true;

// Aktif kampanya cek
$_kampanya = null;
try {
    $sql = 'SELECT * FROM ' . t('kampanyalar') . '
            WHERE aktif=1
              AND NOW() BETWEEN baslangic_tarihi AND bitis_tarihi
              AND (hedef_sayfa = "tum_sayfalar" OR (hedef_sayfa = "anasayfa" AND ?))
            ORDER BY sira DESC, id DESC
            LIMIT 1';
    $_kampanya = db_row($sql, [$_isHome ? 1 : 0]);
} catch (Throwable $e) {
    // Tablo yoksa veya hata olursa pop-up'siz devam et
    return;
}

if (!$_kampanya) return;

// Buton renk -> CSS bg
$_butonBg = match($_kampanya['buton_renk'] ?? 'kirmizi') {
    'sari'    => 'linear-gradient(135deg, #f4d35e, #e0b834)',
    'mavi'    => 'linear-gradient(135deg, #0d6efd, #0a58ca)',
    'yesil'   => 'linear-gradient(135deg, #22c55e, #16a34a)',
    default   => 'linear-gradient(135deg, #e30b30, #a91020)',
};
$_butonColor = ($_kampanya['buton_renk'] ?? '') === 'sari' ? '#0d1b2a' : '#ffffff';
?>

<!-- Mizan Kampanya Pop-up (id=<?= (int)$_kampanya['id'] ?>) -->
<style>
.mz-kamp-overlay {
    position: fixed; inset: 0; z-index: 1090;
    background: rgba(13, 27, 42, .72);
    backdrop-filter: blur(6px);
    display: none;
    align-items: center; justify-content: center;
    padding: 20px;
    animation: mzKampFadeIn .25s ease-out;
}
.mz-kamp-overlay.show { display: flex; }
@keyframes mzKampFadeIn {
    from { opacity: 0; }
    to   { opacity: 1; }
}
.mz-kamp-modal {
    background: #ffffff;
    border-radius: 16px;
    max-width: 540px;
    width: 100%;
    max-height: 92vh;
    overflow: hidden;
    box-shadow: 0 30px 80px rgba(0,0,0,.4), 0 0 0 1px rgba(255,255,255,.1);
    position: relative;
    animation: mzKampSlideUp .35s cubic-bezier(.34, 1.56, .64, 1);
}
@keyframes mzKampSlideUp {
    from { opacity: 0; transform: translateY(40px) scale(.96); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}
.mz-kamp-close {
    position: absolute;
    top: 12px; right: 12px;
    width: 36px; height: 36px;
    border-radius: 50%;
    background: rgba(255,255,255,.95);
    border: 0;
    color: #0d1b2a;
    font-size: 1.1rem;
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer;
    z-index: 2;
    box-shadow: 0 4px 12px rgba(0,0,0,.15);
    transition: all .15s;
}
.mz-kamp-close:hover { background: #e30b30; color: #fff; transform: rotate(90deg); }
.mz-kamp-img {
    width: 100%;
    aspect-ratio: 16/9;
    object-fit: cover;
    background: linear-gradient(135deg, #0d1b2a 0%, #1b263b 100%);
}
.mz-kamp-img-fallback {
    background: linear-gradient(135deg, #0d1b2a 0%, #1b263b 100%);
    aspect-ratio: 16/9;
    display: flex; align-items: center; justify-content: center;
    border-bottom: 4px solid #e30b30;
    position: relative; overflow: hidden;
}
.mz-kamp-img-fallback::before {
    content: '';
    position: absolute; top: -50%; right: -30%;
    width: 200px; height: 200px; border-radius: 50%;
    background: radial-gradient(circle, rgba(244,211,94,.18), transparent 70%);
}
.mz-kamp-img-fallback i { font-size: 4rem; color: #f4d35e; opacity: .85; z-index: 1; }
.mz-kamp-body { padding: 28px 32px 32px; }
.mz-kamp-baslik {
    color: #0d1b2a;
    font-size: 1.55rem;
    font-weight: 800;
    line-height: 1.25;
    margin: 0 0 .5rem;
    letter-spacing: -.3px;
}
.mz-kamp-altbaslik {
    color: #e30b30;
    font-size: .85rem;
    font-weight: 600;
    letter-spacing: .5px;
    text-transform: uppercase;
    margin: 0 0 .85rem;
}
.mz-kamp-aciklama {
    color: #4b5563;
    font-size: 1rem;
    line-height: 1.6;
    margin: 0 0 1.5rem;
}
.mz-kamp-buton {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 28px;
    background: <?= $_butonBg ?>;
    color: <?= $_butonColor ?> !important;
    font-weight: 700;
    font-size: 1rem;
    border-radius: 10px;
    text-decoration: none;
    box-shadow: 0 8px 24px rgba(227,11,48,.3);
    transition: all .2s ease;
    width: 100%;
    border: 0;
    cursor: pointer;
}
.mz-kamp-buton:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(227,11,48,.4);
    color: <?= $_butonColor ?> !important;
}
.mz-kamp-bir-daha-gosterme {
    margin-top: 14px;
    text-align: center;
    font-size: .8rem;
    color: #9ca3af;
}
.mz-kamp-bir-daha-gosterme button {
    background: none; border: 0; color: #6b7280; cursor: pointer;
    font-size: .8rem; text-decoration: underline;
}
.mz-kamp-bir-daha-gosterme button:hover { color: #e30b30; }

/* Mobil */
@media (max-width: 575.98px) {
    .mz-kamp-overlay { padding: 12px; }
    .mz-kamp-body { padding: 20px 22px 24px; }
    .mz-kamp-baslik { font-size: 1.3rem; }
    .mz-kamp-aciklama { font-size: .95rem; }
    .mz-kamp-buton { padding: 12px 22px; font-size: .95rem; }
    .mz-kamp-img, .mz-kamp-img-fallback { aspect-ratio: 16/10; }
}
</style>

<div class="mz-kamp-overlay" id="mzKampOverlay" role="dialog" aria-modal="true" aria-labelledby="mzKampBaslik" hidden>
    <div class="mz-kamp-modal">
        <button type="button" class="mz-kamp-close" id="mzKampClose" aria-label="Kapat">
            <i class="bi bi-x-lg"></i>
        </button>

        <?php if (!empty($_kampanya['gorsel_url'])): ?>
            <img src="<?= e($_kampanya['gorsel_url']) ?>" alt="<?= e($_kampanya['baslik']) ?>" class="mz-kamp-img" loading="lazy">
        <?php else: ?>
            <div class="mz-kamp-img-fallback"><i class="bi bi-megaphone-fill"></i></div>
        <?php endif; ?>

        <div class="mz-kamp-body">
            <?php if (!empty($_kampanya['alt_baslik'])): ?>
                <p class="mz-kamp-altbaslik"><?= e($_kampanya['alt_baslik']) ?></p>
            <?php endif; ?>

            <h3 class="mz-kamp-baslik" id="mzKampBaslik"><?= e($_kampanya['baslik']) ?></h3>

            <?php if (!empty($_kampanya['aciklama'])): ?>
                <p class="mz-kamp-aciklama"><?= nl2br(e($_kampanya['aciklama'])) ?></p>
            <?php endif; ?>

            <?php if (!empty($_kampanya['buton_metin']) && !empty($_kampanya['buton_link'])): ?>
                <a href="<?= e($_kampanya['buton_link']) ?>" class="mz-kamp-buton" id="mzKampCta">
                    <?= e($_kampanya['buton_metin']) ?>
                    <i class="bi bi-arrow-right"></i>
                </a>
            <?php endif; ?>

            <?php if (($_kampanya['gosterim_kurali'] ?? '') !== 'sadece_bir_kez'): ?>
                <div class="mz-kamp-bir-daha-gosterme">
                    <button type="button" id="mzKampDismissForever">Bu kampanyayı bir daha gösterme</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    var KAMP_ID  = <?= (int)$_kampanya['id'] ?>;
    var KURAL    = <?= json_encode($_kampanya['gosterim_kurali'] ?? 'gunde_bir') ?>;
    var GECIKME  = <?= (int)($_kampanya['acilis_gecikmesi'] ?? 3) ?> * 1000;
    var STORAGE_KEY = 'mz_kamp_' + KAMP_ID;
    var SS_KEY      = 'mz_kamp_ss_' + KAMP_ID;

    // Tracking Prevention veya gizli mod sebebiyle storage erisilemezse fail-safe
    function safeGet(storage, key) {
        try { return storage.getItem(key); } catch (e) { return null; }
    }
    function safeSet(storage, key, val) {
        try { storage.setItem(key, val); return true; } catch (e) { return false; }
    }

    // Kullanici daha once gordu mu?
    function shouldShow() {
        var now = Date.now();

        // sadece_bir_kez veya gunde_bir/oturum_basina: localStorage kontrol
        if (KURAL === 'sadece_bir_kez') {
            return safeGet(localStorage, STORAGE_KEY) !== 'dismissed';
        }
        if (KURAL === 'gunde_bir') {
            var last = parseInt(safeGet(localStorage, STORAGE_KEY) || '0', 10);
            return (now - last) >= 24 * 60 * 60 * 1000;
        }
        if (KURAL === 'oturum_basina') {
            return safeGet(sessionStorage, SS_KEY) !== '1';
        }
        // her_ziyaret
        return true;
    }

    function markShown() {
        if (KURAL === 'gunde_bir') {
            safeSet(localStorage, STORAGE_KEY, String(Date.now()));
        } else if (KURAL === 'oturum_basina') {
            safeSet(sessionStorage, SS_KEY, '1');
        }
        // Sayac (best-effort, fail olsa da pop-up calisir)
        try {
            fetch('<?= e(SITE_BASE_URL) ?>/api/kampanya-goster?id=' + KAMP_ID, { method: 'POST', credentials: 'same-origin' });
        } catch (e) {}
    }

    function dismiss(forever) {
        var overlay = document.getElementById('mzKampOverlay');
        if (overlay) {
            overlay.classList.remove('show');
            overlay.setAttribute('hidden', '');
        }
        if (forever) {
            safeSet(localStorage, STORAGE_KEY, 'dismissed');
        }
    }

    function show() {
        var overlay = document.getElementById('mzKampOverlay');
        if (!overlay) return;
        overlay.removeAttribute('hidden');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';

        // Kapatma handler
        var closeBtn = document.getElementById('mzKampClose');
        if (closeBtn) closeBtn.addEventListener('click', function() {
            dismiss(KURAL === 'sadece_bir_kez');
            document.body.style.overflow = '';
        });

        // Bir daha gosterme
        var dismissForever = document.getElementById('mzKampDismissForever');
        if (dismissForever) dismissForever.addEventListener('click', function() {
            dismiss(true);
            document.body.style.overflow = '';
        });

        // Overlay'a tikla -> kapat (modal alaninin disina)
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                dismiss(KURAL === 'sadece_bir_kez');
                document.body.style.overflow = '';
            }
        });

        // ESC ile kapat
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !overlay.hidden) {
                dismiss(KURAL === 'sadece_bir_kez');
                document.body.style.overflow = '';
            }
        });

        // CTA tiklamasi -> sayac
        var cta = document.getElementById('mzKampCta');
        if (cta) cta.addEventListener('click', function() {
            try {
                // sendBeacon mevcutsa kullan (sayfa kapansa bile gider)
                if (navigator.sendBeacon) {
                    navigator.sendBeacon('<?= e(SITE_BASE_URL) ?>/api/kampanya-tik?id=' + KAMP_ID);
                } else {
                    fetch('<?= e(SITE_BASE_URL) ?>/api/kampanya-tik?id=' + KAMP_ID, { method: 'POST', credentials: 'same-origin', keepalive: true });
                }
            } catch (e) {}
        });

        markShown();
    }

    if (shouldShow()) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() { setTimeout(show, GECIKME); });
        } else {
            setTimeout(show, GECIKME);
        }
    }
})();
</script>
