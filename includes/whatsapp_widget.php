<?php
/**
 * Mizan Sigorta - WhatsApp Floating Widget
 *
 * Sol alt kosede sabit floating WhatsApp butonu + acilan chat panel.
 * Tum public sayfalarda gorunur (footer.php ile include edilir).
 *
 * Whatsapp numarasi 'whatsapp' setting'inden gelir; otomatik
 * uluslararasi formata cevrilir (0552... -> 905526943232).
 */
if (defined('MZ_ADMIN')) return; // admin panelinde gosterme

$wa = trim((string) setting('whatsapp', ''));
if ($wa === '') return;

// Numara normalize (TR yerel format -> uluslararasi)
// 0552 694 32 32 -> 905526943232 / 5526943232 -> 905526943232 / 905526943232 -> 905526943232
$waClean = function_exists('normalize_phone') ? normalize_phone($wa) : preg_replace('/[^0-9]/', '', $wa);
if (strlen($waClean) < 11) return; // gecerli numara yok

// Karsilama mesaji
$brand   = (string) setting('site_basligi', 'Mizan Sigorta');
$mesaj   = 'Merhaba ' . $brand . ', sigorta hakkında bilgi almak istiyorum.';
$waUrl   = 'https://wa.me/' . $waClean . '?text=' . rawurlencode($mesaj);
?>

<style>
/* WhatsApp Floating Widget - sol alt */
.mz-wa-widget {
    position: fixed;
    left: 20px;
    bottom: 20px;
    z-index: 9998;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
}

/* Mobilde alt tab-bar varsa biraz yukari kaydir */
@media (max-width: 991.98px) {
    .mz-wa-widget { bottom: 80px; }
}

/* Buton */
.mz-wa-btn {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #25d366, #128c7e);
    color: #fff;
    border: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 6px 18px rgba(37, 211, 102, 0.4),
                0 2px 6px rgba(0, 0, 0, 0.15);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    font-size: 30px;
    position: relative;
}
.mz-wa-btn:hover {
    transform: scale(1.08);
    box-shadow: 0 8px 24px rgba(37, 211, 102, 0.55),
                0 4px 8px rgba(0, 0, 0, 0.2);
    color: #fff;
}
.mz-wa-btn i { line-height: 1; }

/* Pulse animasyon (dikkat cekmek icin) */
.mz-wa-btn::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: rgba(37, 211, 102, 0.5);
    animation: mz-wa-pulse 2s ease-out infinite;
    z-index: -1;
}
@keyframes mz-wa-pulse {
    0%   { transform: scale(1);   opacity: 0.7; }
    100% { transform: scale(1.6); opacity: 0; }
}

/* Tooltip - butonun yaninda kucuk balon */
.mz-wa-tooltip {
    position: absolute;
    left: 75px;
    bottom: 50%;
    transform: translateY(50%);
    background: #0d1b2a;
    color: #fff;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    pointer-events: none;
    opacity: 0;
    transform: translateY(50%) translateX(-8px);
    transition: opacity 0.2s, transform 0.2s;
}
.mz-wa-tooltip::before {
    content: '';
    position: absolute;
    left: -6px;
    top: 50%;
    transform: translateY(-50%);
    border: 6px solid transparent;
    border-right-color: #0d1b2a;
}
.mz-wa-widget:hover .mz-wa-tooltip {
    opacity: 1;
    transform: translateY(50%) translateX(0);
}

/* Acilan chat paneli */
.mz-wa-panel {
    position: absolute;
    left: 0;
    bottom: 78px;
    width: 320px;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.18);
    overflow: hidden;
    display: none;
    animation: mz-wa-slideup 0.25s ease;
    border: 1px solid #e5e7eb;
}
@keyframes mz-wa-slideup {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}
.mz-wa-panel.is-open { display: block; }

.mz-wa-panel-head {
    background: linear-gradient(135deg, #25d366, #128c7e);
    color: #fff;
    padding: 16px 18px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.mz-wa-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}
.mz-wa-panel-head .name {
    font-weight: 700;
    font-size: 14px;
    line-height: 1.2;
    margin: 0;
}
.mz-wa-panel-head .status {
    font-size: 11px;
    opacity: 0.85;
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 2px;
}
.mz-wa-panel-head .status::before {
    content: '';
    width: 6px;
    height: 6px;
    background: #4ade80;
    border-radius: 50%;
    box-shadow: 0 0 0 2px rgba(74, 222, 128, 0.4);
}
.mz-wa-panel-close {
    margin-left: auto;
    background: transparent;
    border: 0;
    color: #fff;
    font-size: 20px;
    cursor: pointer;
    opacity: 0.8;
    padding: 4px 8px;
    line-height: 1;
}
.mz-wa-panel-close:hover { opacity: 1; }

.mz-wa-panel-body {
    padding: 18px;
    background: #f0f2f5;
    background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='80' height='80'><g fill='%23dbe1e8' opacity='.5'><circle cx='10' cy='10' r='2'/><circle cx='40' cy='25' r='2'/><circle cx='65' cy='15' r='2'/><circle cx='20' cy='50' r='2'/><circle cx='55' cy='55' r='2'/><circle cx='15' cy='75' r='2'/><circle cx='45' cy='70' r='2'/></g></svg>");
}
.mz-wa-msg {
    background: #fff;
    border-radius: 0 12px 12px 12px;
    padding: 12px 14px;
    font-size: 13px;
    color: #1f2937;
    line-height: 1.5;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
    max-width: 85%;
    margin-bottom: 10px;
    position: relative;
}
.mz-wa-msg-time {
    font-size: 10px;
    color: #6b7280;
    text-align: right;
    margin-top: 4px;
}

.mz-wa-panel-input {
    padding: 12px 14px;
    background: #fff;
    border-top: 1px solid #e5e7eb;
}
.mz-wa-panel-input textarea {
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: 20px;
    padding: 10px 14px;
    font-size: 13px;
    resize: none;
    outline: none;
    font-family: inherit;
    transition: border-color 0.15s;
}
.mz-wa-panel-input textarea:focus {
    border-color: #25d366;
}
.mz-wa-send-btn {
    width: 100%;
    margin-top: 10px;
    background: linear-gradient(135deg, #25d366, #128c7e);
    color: #fff;
    border: 0;
    border-radius: 22px;
    padding: 10px 16px;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: transform 0.15s, box-shadow 0.15s;
}
.mz-wa-send-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 211, 102, 0.35);
    color: #fff;
}

/* Mobil panel responsive */
@media (max-width: 480px) {
    .mz-wa-panel { width: calc(100vw - 40px); }
    .mz-wa-tooltip { display: none; }
}
</style>

<div class="mz-wa-widget" id="mzWaWidget">
    <div class="mz-wa-panel" id="mzWaPanel">
        <div class="mz-wa-panel-head">
            <div class="mz-wa-avatar"><i class="bi bi-whatsapp"></i></div>
            <div>
                <p class="name"><?= e($brand) ?></p>
                <span class="status">Çevrimiçi</span>
            </div>
            <button type="button" class="mz-wa-panel-close" id="mzWaClose" aria-label="Kapat">×</button>
        </div>
        <div class="mz-wa-panel-body">
            <div class="mz-wa-msg">
                Merhaba 👋<br>
                Sigorta ile ilgili sorularınız için buradayız. WhatsApp üzerinden hızlıca yanıt veriyoruz.
                <div class="mz-wa-msg-time"><i class="bi bi-check2-all" style="color:#25d366"></i> şimdi</div>
            </div>
        </div>
        <div class="mz-wa-panel-input">
            <textarea id="mzWaText" rows="2" placeholder="Mesajınızı yazın..."><?= e($mesaj) ?></textarea>
            <button type="button" class="mz-wa-send-btn" id="mzWaSend">
                <i class="bi bi-whatsapp"></i> WhatsApp'ta Gönder
            </button>
        </div>
    </div>

    <button type="button" class="mz-wa-btn" id="mzWaToggle" aria-label="WhatsApp ile iletişime geç">
        <i class="bi bi-whatsapp"></i>
        <span class="mz-wa-tooltip">WhatsApp ile yazın</span>
    </button>
</div>

<script>
(function(){
    var widget = document.getElementById('mzWaWidget');
    var btn    = document.getElementById('mzWaToggle');
    var panel  = document.getElementById('mzWaPanel');
    var closeB = document.getElementById('mzWaClose');
    var send   = document.getElementById('mzWaSend');
    var text   = document.getElementById('mzWaText');
    var waNum  = '<?= e($waClean) ?>';

    function toggle(){ panel.classList.toggle('is-open'); }
    function close(){  panel.classList.remove('is-open'); }

    btn.addEventListener('click', function(e){
        e.stopPropagation();
        toggle();
    });
    closeB.addEventListener('click', close);

    // Panel disina tiklayinca kapat
    document.addEventListener('click', function(e){
        if (!widget.contains(e.target)) close();
    });
    // ESC tusu
    document.addEventListener('keydown', function(e){
        if (e.key === 'Escape') close();
    });

    // Mesaj gonder
    send.addEventListener('click', function(){
        var msg = (text.value || '').trim();
        if (!msg) {
            text.focus();
            return;
        }
        var url = 'https://wa.me/' + waNum + '?text=' + encodeURIComponent(msg);
        window.open(url, '_blank', 'noopener');
    });

    // Enter ile gonder (Shift+Enter yeni satir)
    text.addEventListener('keydown', function(e){
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            send.click();
        }
    });
})();
</script>
