<?php
if (!defined('MIZAN_BOOT')) { http_response_code(403); exit; }

require MIZAN_INC . '/header.php';
?>

<style>
.mz-hk-hero {
  position: relative; min-height: 480px; overflow: hidden;
  background: linear-gradient(135deg, var(--mz-navy) 0%, var(--mz-navy-2) 50%, var(--mz-navy) 100%);
  color: #fff;
}
.mz-hk-hero::before {
  content: ''; position: absolute; inset: 0;
  background:
    radial-gradient(ellipse 60% 80% at top right, rgba(227,11,48,.18) 0%, transparent 50%),
    radial-gradient(ellipse 60% 80% at bottom left, rgba(227,11,48,.08) 0%, transparent 50%);
  pointer-events: none;
}
.mz-hk-hero::after {
  content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 200px;
  background: linear-gradient(to bottom, transparent, rgba(0,0,0,.3));
  pointer-events: none;
}
.mz-hk-bigword {
  font-family: 'Allura', cursive; font-size: clamp(4rem, 12vw, 9rem); line-height: .9;
  background: linear-gradient(135deg, var(--mz-red), #ff5577);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
  opacity: .07; pointer-events: none; white-space: nowrap;
}

.mz-hk-pillar {
  background: #fff; border-radius: 16px; padding: 2rem; height: 100%;
  border: 1px solid #e5e7eb; transition: all .25s; position: relative; overflow: hidden;
}
.mz-hk-pillar::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
  background: linear-gradient(90deg, var(--mz-red), var(--mz-red-2));
  transform: scaleX(0); transform-origin: left; transition: transform .3s;
}
.mz-hk-pillar:hover { transform: translateY(-4px); border-color: var(--mz-red); box-shadow: 0 25px 50px rgba(15,30,55,.12); }
.mz-hk-pillar:hover::before { transform: scaleX(1); }
.mz-hk-pillar-num {
  font-size: 3.5rem; font-weight: 800; color: var(--mz-red);
  line-height: 1; opacity: .15; position: absolute; top: 1rem; right: 1.25rem;
  font-family: serif;
}

.mz-hk-step {
  background: #fff; border-radius: 12px; padding: 1.5rem;
  border-left: 4px solid var(--mz-red); transition: all .2s;
}
.mz-hk-step:hover { transform: translateX(4px); box-shadow: 0 10px 25px rgba(15,30,55,.08); }

.mz-anlasmali-grid {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 1rem;
}
.mz-anlasmali-card {
  background: #fff; border: 1px solid #e5e7eb; border-radius: 10px;
  padding: 1.25rem .75rem; text-align: center;
  transition: all .2s; font-weight: 600; color: var(--mz-navy); font-size: .92rem;
}
.mz-anlasmali-card:hover { border-color: var(--mz-red); transform: translateY(-2px); box-shadow: 0 12px 25px rgba(15,30,55,.08); }
.mz-anlasmali-card i { font-size: 1.75rem; color: var(--mz-red); display: block; margin-bottom: .35rem; }

.mz-deger-card {
  background: #fff; border-radius: 16px; padding: 2rem 1.5rem; text-align: center;
  border: 1px solid #e5e7eb; transition: all .25s; height: 100%;
}
.mz-deger-card:hover { border-color: var(--mz-red); box-shadow: 0 20px 40px rgba(227,11,48,.08); transform: translateY(-3px); }
.mz-deger-card-icon {
  width: 72px; height: 72px; margin: 0 auto 1.25rem;
  border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;
  font-size: 1.85rem; color: #fff;
  background: linear-gradient(135deg, var(--mz-red), var(--mz-red-2));
  box-shadow: 0 12px 30px var(--mz-red-glow);
}

.mz-istatistik {
  background: linear-gradient(135deg, var(--mz-navy), var(--mz-navy-2));
  border-radius: 20px; padding: 3rem 2rem; color: #fff;
  position: relative; overflow: hidden;
}
.mz-istatistik::before {
  content: ''; position: absolute; inset: 0;
  background: radial-gradient(ellipse at top right, rgba(227,11,48,.15) 0%, transparent 60%);
}
.mz-istatistik-num {
  font-size: 3.5rem; font-weight: 800; line-height: 1;
  background: linear-gradient(135deg, var(--mz-red), #ff7799);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}

.mz-quote-big {
  background: linear-gradient(135deg, #fff 0%, #fffbf5 100%);
  border-radius: 20px; padding: 3rem 2.5rem; position: relative;
  border: 1px solid #f3e8d8;
  box-shadow: 0 25px 50px rgba(15,30,55,.08);
}
.mz-quote-big::before {
  content: '\201C'; font-family: serif; font-size: 12rem; line-height: 1;
  position: absolute; top: -2rem; left: 1rem; color: var(--mz-red); opacity: .15;
}
</style>

<!-- ==== HERO ==== -->
<section class="mz-hk-hero d-flex align-items-center">
  <span class="mz-hk-bigword d-none d-md-block">Mizan</span>
  <div class="container py-5 position-relative" style="z-index:2">
    <div class="row align-items-center">
      <div class="col-lg-7">
        <span class="badge bg-warning text-dark mb-3 px-3 py-2 fw-semibold"><i class="bi bi-stars"></i> 2026 İtibariyle</span>
        <h1 class="display-3 fw-bold mb-3" style="line-height:1.1">
          <?= e($sayfa['baslik']) ?>
        </h1>
        <p class="lead text-white-50 mb-4" style="max-width:560px">
          Mizan Sigorta olarak sigortacılığı bir ürün satışı değil, sevdiklerinizin geleceğini güvence altına alma sorumluluğu olarak görüyoruz. <strong style="color:var(--mz-red)">Güven ve özen ile</strong>, hayatın her aşamasında yanınızdayız.
        </p>
        <div class="d-flex gap-2 flex-wrap">
          <a href="#deger" class="btn btn-warning fw-semibold"><i class="bi bi-arrow-down-circle"></i> Değerlerimiz</a>
          <a href="<?= u('/iletisim') ?>" class="btn btn-outline-light"><i class="bi bi-telephone"></i> Bize Ulaşın</a>
        </div>
      </div>
      <div class="col-lg-5 d-none d-lg-block text-center">
        <div class="position-relative d-inline-block">
          <div style="width:280px;height:280px;border-radius:50%;background:radial-gradient(circle, rgba(227,11,48,.2) 0%, transparent 70%);display:flex;align-items:center;justify-content:center">
            <div style="width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.05);border:2px solid rgba(227,11,48,.4);display:flex;align-items:center;justify-content:center;backdrop-filter:blur(10px)">
              <i class="bi bi-shield-fill-check" style="font-size:5rem;color:var(--mz-red)"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ==== İSTATİSTİK BANT ==== -->
<section class="container" style="margin-top:-60px;position:relative;z-index:5">
  <div class="mz-istatistik">
    <div class="row text-center g-4 position-relative">
      <div class="col-6 col-md-3">
        <div class="mz-istatistik-num">12<span style="font-size:2rem">+</span></div>
        <div class="text-white-50 small text-uppercase mt-2" style="letter-spacing:.1em">Anlaşmalı Şirket</div>
      </div>
      <div class="col-6 col-md-3">
        <div class="mz-istatistik-num">9</div>
        <div class="text-white-50 small text-uppercase mt-2" style="letter-spacing:.1em">Sigorta Kategorisi</div>
      </div>
      <div class="col-6 col-md-3">
        <div class="mz-istatistik-num">35<span style="font-size:2rem">+</span></div>
        <div class="text-white-50 small text-uppercase mt-2" style="letter-spacing:.1em">Ürün Çeşidi</div>
      </div>
      <div class="col-6 col-md-3">
        <div class="mz-istatistik-num">4</div>
        <div class="text-white-50 small text-uppercase mt-2" style="letter-spacing:.1em">Şehirde Şube</div>
      </div>
    </div>
  </div>
</section>

<!-- ==== Mizan Felsefesi ==== -->
<section class="mz-band">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <span class="mz-script mz-script-md mz-script-red d-block mb-2">Felsefemiz</span>
        <h2 class="display-5 fw-bold mb-4">Mizan: <em style="color:var(--mz-red)">Denge ve Adalet</em></h2>
        <p class="lead text-muted">"Mizan" Arapça'dan Türkçe'ye geçen, <strong style="color:var(--mz-navy)">terazi, denge, ölçü, adalet</strong> anlamlarına gelen köklü bir kelime.</p>
        <p>Markamızı bu felsefe üzerine kurduk: Müşterimizin <strong>ihtiyacı ile sunulan teminat arasında</strong>, <strong>ödediği prim ile aldığı koruma arasında</strong>, <strong>beklentisi ile gerçek arasında</strong> dengeyi kurmak.</p>
        <p class="mb-0">Bu yüzden her teklifte <em>en uygun fiyatı</em> değil <em>en uygun çözümü</em> ararız. Çünkü gerçek değer, ucuza alınan değil, doğru zaman ve doğru kapsam ile alınan poliçedir.</p>
      </div>
      <div class="col-lg-6">
        <div class="mz-quote-big">
          <p class="mb-3 fs-5 fst-italic" style="color:var(--mz-navy);line-height:1.5">
            Sigortacılığı, bir <strong>satış</strong> olarak değil; müşterimizin geleceğini güvence altına alma <strong>sorumluluğu</strong> olarak görürüz. Hasar yaşandığı kritik anda, müşterimizin yanında doğru bir uzman bulması — bizim için tüm ticari kazançlardan değerlidir.
          </p>
          <div class="d-flex align-items-center gap-3 mt-4">
            <div style="width:50px;height:50px;border-radius:50%;background:var(--mz-red);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700"><i class="bi bi-quote"></i></div>
            <div>
              <strong>Mizan Sigorta</strong>
              <div class="text-muted small">Kurumsal Aile Felsefesi</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ==== Misyon · Vizyon ==== -->
<section class="mz-band bg-light" id="deger">
  <div class="container">
    <div class="text-center mb-5">
      <span class="mz-script mz-script-md mz-script-red">Yön Verici</span>
      <h2 class="display-6 fw-bold">Misyon &amp; Vizyon</h2>
    </div>

    <div class="row g-4">
      <div class="col-lg-6">
        <div class="mz-hk-pillar">
          <span class="mz-hk-pillar-num">M</span>
          <div class="d-inline-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,var(--mz-red),var(--mz-red-2));color:#fff;font-size:1.5rem">
            <i class="bi bi-bullseye"></i>
          </div>
          <h3 class="fw-bold">Misyonumuz</h3>
          <p class="text-muted mb-3">Müşterilerimizin risklerini derinden anlayarak, ihtiyaçlarına en uygun ve en ekonomik sigorta çözümlerini sunmak.</p>
          <ul class="list-unstyled mb-0 small">
            <li class="mb-2"><i class="bi bi-check2-circle text-success"></i> Hasar / tazminat sürecinde profesyonel rehberlik</li>
            <li class="mb-2"><i class="bi bi-check2-circle text-success"></i> Sigorta şirketi ile sigortalı arasında güvenilir köprü</li>
            <li><i class="bi bi-check2-circle text-success"></i> Tek seferlik satış değil, uzun soluklu güven ilişkisi</li>
          </ul>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="mz-hk-pillar">
          <span class="mz-hk-pillar-num">V</span>
          <div class="d-inline-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,var(--mz-red),var(--mz-red-2));color:#fff;font-size:1.5rem">
            <i class="bi bi-binoculars"></i>
          </div>
          <h3 class="fw-bold">Vizyonumuz</h3>
          <p class="text-muted mb-3">Türkiye genelinde yaygın hizmet ağıyla, kalitesi ile tercih edilen ve referans gösterilen bir sigorta aracılık kurumu olmak.</p>
          <ul class="list-unstyled mb-0 small">
            <li class="mb-2"><i class="bi bi-check2-circle text-success"></i> Her şehirde aynı kalite ve aynı özen</li>
            <li class="mb-2"><i class="bi bi-check2-circle text-success"></i> Dijital dönüşümle her an, her yerden ulaşılabilir hizmet</li>
            <li><i class="bi bi-check2-circle text-success"></i> Sektörde "güvenin adı" olarak anılmak</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ==== 5 Değer ==== -->
<section class="mz-band">
  <div class="container">
    <div class="text-center mb-5">
      <span class="mz-script mz-script-md mz-script-red">Temel İlkeler</span>
      <h2 class="display-6 fw-bold">Değerlerimiz</h2>
      <p class="text-muted lead">İş yapışımızı şekillendiren beş temel değer</p>
    </div>

    <div class="row g-4">
      <?php
      $degerler = [
          ['shield-check',    'Güven',     'Her ilişkimizin temelinde dürüstlük ve şeffaflık. Ne sözünü tutuyorsak onu sunarız.'],
          ['hand-thumbs-up',  'Özen',      'Her poliçe, her hasar dosyası, her görüşme aynı titizlikle ele alınır.'],
          ['mortarboard',     'Uzmanlık',  'Sigorta mevzuatı ve ürün gelişmelerini sürekli takip eden uzman kadro.'],
          ['scale',           'Adalet',    'Müşterimizin haklarını koruyarak sigorta şirketleri ile dengeyi sağlamak.'],
          ['arrow-repeat',    'Süreklilik','Tek seferlik satış değil, hayatın her aşamasında yanınızda olmak.'],
      ];
      foreach ($degerler as $d): ?>
        <div class="col-md-6 col-lg-4">
          <div class="mz-deger-card">
            <div class="mz-deger-card-icon"><i class="bi bi-<?= $d[0] ?>"></i></div>
            <h5 class="fw-bold"><?= e($d[1]) ?></h5>
            <p class="text-muted small mb-0"><?= e($d[2]) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ==== Anlaşmalı Sigorta Şirketleri ==== -->
<section class="mz-band bg-light">
  <div class="container">
    <div class="text-center mb-5">
      <span class="mz-script mz-script-md mz-script-red">İş Ortaklarımız</span>
      <h2 class="display-6 fw-bold">12+ Anlaşmalı Sigorta Şirketi</h2>
      <p class="text-muted lead">Türkiye'nin en köklü şirketleriyle yan yana çalışıyoruz</p>
    </div>

    <div class="mz-anlasmali-grid">
      <?php
      $sirketler = ['Anadolu Sigorta','Allianz','AXA','Türkiye Sigorta','HDI Sigorta','Quick Sigorta','Neova Sigorta','Ak Sigorta','Doğa Sigorta','Atlas Sigorta','Corpus Sigorta','Magdeburger Sigorta'];
      foreach ($sirketler as $s): ?>
        <div class="mz-anlasmali-card">
          <i class="bi bi-shield-fill-check"></i>
          <?= e($s) ?>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="text-center mt-4">
      <small class="text-muted"><i class="bi bi-info-circle"></i> Sigorta şirketleri ile ticari ilişkilerimiz devam etmekte; liste güncellenebilir.</small>
    </div>
  </div>
</section>

<!-- ==== Hizmet Süreci ==== -->
<section class="mz-band">
  <div class="container">
    <div class="text-center mb-5">
      <span class="mz-script mz-script-md mz-script-red">Süreç</span>
      <h2 class="display-6 fw-bold">Müşteri Yolculuğumuz</h2>
      <p class="text-muted lead">İlk kontaktan hasar sonrasına: Adım adım yanınızdayız</p>
    </div>

    <div class="row g-3">
      <?php
      $adimlar = [
          ['1', 'bi-chat-dots',     'İhtiyaç Analizi',       'Sigorta türünüz ve risk profiliniz hakkında detaylı görüşme yaparız. Yanlış teminat almanızı önlemek bize hızdan daha önemli.'],
          ['2', 'bi-list-check',    'Çoklu Teklif',          '12+ anlaşmalı şirketten paralel teklifler çekeriz. Her birinin kapsamını şeffafça karşılaştırırız.'],
          ['3', 'bi-shield-check',  'Optimum Çözüm',         'Sadece en ucuz değil, en doğru poliçeyi seçmenizi sağlarız. Eksik ya da fazla teminat olmasın.'],
          ['4', 'bi-file-earmark-check', 'Poliçe Düzenleme', 'Tüm evrak ve resmi süreçlerde size eşlik ederiz. KVKK uyumlu, mevzuata tam uyumlu süreç.'],
          ['5', 'bi-bell',          'Sürekli Hatırlatma',    'Otomatik hatırlatma sistemi: yenileme, vade, mevzuat değişiklikleri. Hiçbir önemli tarihi kaçırmayın.'],
          ['6', 'bi-life-preserver','Hasar Desteği',         'Hasar anında profesyonel rehberlik. Eksper süreci, evrak takibi, tazminat müzakeresi — tüm aşamalarda yanınızda.'],
      ];
      foreach ($adimlar as $a): ?>
        <div class="col-md-6 col-lg-4">
          <div class="mz-hk-step h-100">
            <div class="d-flex align-items-start gap-3">
              <div class="flex-shrink-0" style="width:42px;height:42px;border-radius:50%;background:rgba(227,11,48,.1);color:var(--mz-red);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.1rem"><?= $a[0] ?></div>
              <div>
                <h6 class="fw-bold mb-1"><i class="bi <?= $a[1] ?> text-warning"></i> <?= e($a[2]) ?></h6>
                <p class="text-muted small mb-0"><?= e($a[3]) ?></p>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ==== Şubeler ==== -->
<section class="mz-band bg-light">
  <div class="container">
    <div class="text-center mb-5">
      <span class="mz-script mz-script-md mz-script-red">Yaygın Hizmet</span>
      <h2 class="display-6 fw-bold">4 Şehirde Mizan Sigorta</h2>
    </div>

    <div class="row g-4">
      <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body p-4">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#f4d35e,#d4af37);color:var(--mz-navy);display:flex;align-items:center;justify-content:center"><i class="bi bi-gem fs-5"></i></div>
              <div>
                <span class="badge bg-warning text-dark fw-semibold mb-1">Genel Merkez</span>
                <h4 class="fw-bold mb-0">İstanbul · Ataşehir</h4>
              </div>
            </div>
            <p class="text-muted small mb-3"><?= e(setting('istanbul_adres', 'Fetih Mah. Libadiye Cad. Tahralı Sok. Kavakyeli İş Merkezi D-Blok K:9 D:24 ATAŞEHİR / İSTANBUL')) ?></p>
            <a href="<?= u('/iletisim') ?>" class="btn btn-sm btn-warning fw-semibold"><i class="bi bi-arrow-right"></i> İletişim Sayfası</a>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="row g-3 h-100">
          <?php
          $subeler = [
              ['Konya', 'Karatay', 'konya_adres'],
              ['Ankara', '', 'ankara_adres'],
              ['Aksaray', '', 'aksaray_adres'],
          ];
          foreach ($subeler as $idx => $s):
              $adres = setting($s[2]);
              if (!$adres) continue;
          ?>
            <div class="col-12">
              <div class="card border-0 shadow-sm">
                <div class="card-body p-3 d-flex gap-3 align-items-start">
                  <div style="width:36px;height:36px;border-radius:50%;background:rgba(227,11,48,.1);color:var(--mz-red);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0"><?= sprintf('%02d', $idx + 1) ?></div>
                  <div class="flex-grow-1">
                    <strong><?= e($s[0]) ?></strong> <?php if($s[1]): ?><small class="text-muted">· <?= e($s[1]) ?></small><?php endif; ?>
                    <p class="text-muted small mb-0" style="line-height:1.4"><?= e($adres) ?></p>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ==== CMS İçerik (admin tarafından düzenlenebilen) ==== -->
<?php if (!empty($sayfa['icerik']) && strlen(strip_tags($sayfa['icerik'])) > 200): ?>
<section class="mz-band">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-9">
        <div class="mz-prose">
          <?= $sayfa['icerik'] ?>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ==== CTA ==== -->
<section class="mz-band" style="background:linear-gradient(135deg,var(--mz-navy),var(--mz-navy-2));color:#fff">
  <div class="container text-center py-4">
    <span class="mz-script mz-script-md mz-script-red d-block mb-2" style="color:#f4d35e !important">İlk Adım</span>
    <h2 class="display-6 fw-bold mb-3">Sizinle tanışmak için sabırsızlanıyoruz</h2>
    <p class="lead text-white-50 mb-4 mx-auto" style="max-width:560px">Bir teklif kadar basit. Bilgileriniz bizde tamamen güvende, kararı siz veriyorsunuz.</p>
    <div class="d-flex gap-3 justify-content-center flex-wrap">
      <button type="button" class="btn btn-warning btn-lg fw-semibold px-4" data-bs-toggle="modal" data-bs-target="#teklifWizard"><i class="bi bi-lightning-charge-fill"></i> Hızlı Teklif Al</button>
      <a href="<?= u('/iletisim') ?>" class="btn btn-outline-light btn-lg fw-semibold px-4"><i class="bi bi-telephone"></i> Bize Ulaşın</a>
    </div>
  </div>
</section>

<?php require MIZAN_INC . '/footer.php';
