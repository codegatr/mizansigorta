<?php
/**
 * Mizan Sigorta - Slider/Hero Decoratif SVG Illustrations
 * includes/svg_illustrations.php
 *
 * 4 inline SVG illustration: kalkan (koruma), araba (oto), ev+kalp (saglik+dask),
 * kulaklik+saat (7/24 hasar). Mizan brand renkleri (kirmizi/sari/navy).
 *
 * Kullanim: echo mz_svg_illustration('svg_kalkan');
 */

declare(strict_types=1);

if (!defined('MIZAN_BOOT')) {
    http_response_code(403);
    exit('Forbidden');
}

/**
 * Slider arka plan dekoratif SVG illustration uretici.
 * @param string $tip svg_kalkan | svg_araba | svg_ev_kalp | svg_kulaklik | yok
 */
function mz_svg_illustration(string $tip): string
{
    switch ($tip) {
        case 'svg_kalkan':
            return <<<SVG
<svg viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="sh1" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#f4d35e" stop-opacity=".9"/>
      <stop offset="100%" stop-color="#e30b30" stop-opacity=".7"/>
    </linearGradient>
  </defs>
  <path d="M200 60 L320 100 V210 Q320 290 200 350 Q80 290 80 210 V100 Z" fill="url(#sh1)" stroke="#fff" stroke-width="3" stroke-opacity=".4"/>
  <path d="M140 200 L185 245 L265 165" stroke="#fff" stroke-width="14" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
  <circle cx="80" cy="80" r="4" fill="#f4d35e" opacity=".8"/>
  <circle cx="340" cy="120" r="5" fill="#fff" opacity=".7"/>
  <circle cx="60" cy="280" r="3" fill="#f4d35e" opacity=".6"/>
  <circle cx="350" cy="320" r="4" fill="#fff" opacity=".5"/>
</svg>
SVG;

        case 'svg_araba':
            return <<<SVG
<svg viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="car1" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#e30b30" stop-opacity=".9"/>
      <stop offset="100%" stop-color="#a91020" stop-opacity=".8"/>
    </linearGradient>
  </defs>
  <ellipse cx="200" cy="320" rx="180" ry="25" fill="#fff" opacity=".15"/>
  <line x1="40" y1="320" x2="80" y2="320" stroke="#f4d35e" stroke-width="4" stroke-linecap="round" opacity=".7"/>
  <line x1="120" y1="320" x2="160" y2="320" stroke="#f4d35e" stroke-width="4" stroke-linecap="round" opacity=".7"/>
  <line x1="240" y1="320" x2="280" y2="320" stroke="#f4d35e" stroke-width="4" stroke-linecap="round" opacity=".7"/>
  <line x1="320" y1="320" x2="360" y2="320" stroke="#f4d35e" stroke-width="4" stroke-linecap="round" opacity=".7"/>
  <path d="M90 280 L120 230 Q130 215 150 215 H260 Q275 215 285 235 L310 280 H300 V300 Q300 305 295 305 H285 Q280 305 280 300 V295 H125 V300 Q125 305 120 305 H110 Q105 305 105 300 V280 Z" fill="url(#car1)" stroke="#fff" stroke-width="2" stroke-opacity=".4"/>
  <path d="M135 280 L150 240 Q155 232 165 232 H240 Q252 232 257 245 L270 280 Z" fill="#fff" opacity=".25"/>
  <line x1="200" y1="232" x2="200" y2="280" stroke="#fff" stroke-width="2" opacity=".4"/>
  <circle cx="135" cy="305" r="22" fill="#0d1b2a" stroke="#fff" stroke-width="3" stroke-opacity=".5"/>
  <circle cx="135" cy="305" r="9" fill="#fff" opacity=".7"/>
  <circle cx="270" cy="305" r="22" fill="#0d1b2a" stroke="#fff" stroke-width="3" stroke-opacity=".5"/>
  <circle cx="270" cy="305" r="9" fill="#fff" opacity=".7"/>
  <circle cx="305" cy="265" r="5" fill="#f4d35e" opacity=".9"/>
  <line x1="20" y1="250" x2="60" y2="250" stroke="#fff" stroke-width="3" stroke-linecap="round" opacity=".5"/>
  <line x1="10" y1="270" x2="55" y2="270" stroke="#fff" stroke-width="3" stroke-linecap="round" opacity=".4"/>
  <line x1="25" y1="290" x2="60" y2="290" stroke="#fff" stroke-width="3" stroke-linecap="round" opacity=".3"/>
</svg>
SVG;

        case 'svg_ev_kalp':
            return <<<SVG
<svg viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="hg1" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#22c55e" stop-opacity=".8"/>
      <stop offset="100%" stop-color="#0d6efd" stop-opacity=".7"/>
    </linearGradient>
    <linearGradient id="hg2" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#e30b30" stop-opacity=".9"/>
      <stop offset="100%" stop-color="#f4d35e" stop-opacity=".8"/>
    </linearGradient>
  </defs>
  <path d="M120 200 L200 130 L280 200 V310 H120 Z" fill="url(#hg1)" stroke="#fff" stroke-width="3" stroke-opacity=".4"/>
  <path d="M105 210 L200 120 L295 210" stroke="#fff" stroke-width="4" stroke-linecap="round" fill="none" opacity=".8"/>
  <rect x="180" y="245" width="40" height="65" rx="3" fill="#fff" opacity=".25"/>
  <circle cx="212" cy="277" r="2" fill="#f4d35e"/>
  <rect x="140" y="220" width="25" height="25" rx="2" fill="#fff" opacity=".3"/>
  <rect x="235" y="220" width="25" height="25" rx="2" fill="#fff" opacity=".3"/>
  <line x1="152" y1="220" x2="152" y2="245" stroke="#0d1b2a" stroke-width="1" opacity=".5"/>
  <line x1="140" y1="232" x2="165" y2="232" stroke="#0d1b2a" stroke-width="1" opacity=".5"/>
  <line x1="247" y1="220" x2="247" y2="245" stroke="#0d1b2a" stroke-width="1" opacity=".5"/>
  <line x1="235" y1="232" x2="260" y2="232" stroke="#0d1b2a" stroke-width="1" opacity=".5"/>
  <path d="M310 80 Q310 60 330 60 Q345 60 350 75 Q355 60 370 60 Q390 60 390 80 Q390 105 350 135 Q310 105 310 80 Z" fill="url(#hg2)" stroke="#fff" stroke-width="2" stroke-opacity=".4"/>
  <path d="M312 90 L325 90 L330 80 L335 100 L342 75 L347 90 L388 90" stroke="#fff" stroke-width="2.5" fill="none" stroke-linecap="round" opacity=".9"/>
  <circle cx="60" cy="100" r="3" fill="#f4d35e" opacity=".7"/>
  <circle cx="80" cy="60" r="4" fill="#fff" opacity=".6"/>
  <circle cx="40" cy="280" r="3" fill="#f4d35e" opacity=".5"/>
</svg>
SVG;

        case 'svg_kulaklik':
            return <<<SVG
<svg viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="op1" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#f4d35e" stop-opacity=".9"/>
      <stop offset="100%" stop-color="#e30b30" stop-opacity=".8"/>
    </linearGradient>
    <linearGradient id="op2" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#0d6efd" stop-opacity=".9"/>
      <stop offset="100%" stop-color="#22c55e" stop-opacity=".7"/>
    </linearGradient>
  </defs>
  <path d="M100 220 Q100 110 200 110 Q300 110 300 220" fill="none" stroke="url(#op1)" stroke-width="14" stroke-linecap="round"/>
  <rect x="80" y="200" width="50" height="80" rx="20" fill="url(#op1)" stroke="#fff" stroke-width="2" stroke-opacity=".4"/>
  <path d="M105 280 Q105 320 145 325 Q175 328 185 320" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" opacity=".9"/>
  <ellipse cx="190" cy="318" rx="10" ry="6" fill="#e30b30" opacity=".95"/>
  <rect x="270" y="200" width="50" height="80" rx="20" fill="url(#op1)" stroke="#fff" stroke-width="2" stroke-opacity=".4"/>
  <circle cx="320" cy="80" r="42" fill="url(#op2)" stroke="#fff" stroke-width="3" stroke-opacity=".5"/>
  <line x1="320" y1="50" x2="320" y2="80" stroke="#fff" stroke-width="3" stroke-linecap="round"/>
  <line x1="320" y1="80" x2="345" y2="80" stroke="#fff" stroke-width="3" stroke-linecap="round"/>
  <circle cx="320" cy="80" r="3" fill="#fff"/>
  <text x="320" y="145" font-family="Arial,sans-serif" font-weight="bold" font-size="24" fill="#fff" text-anchor="middle" opacity=".95">7/24</text>
  <path d="M50 230 Q40 240 50 250" stroke="#fff" stroke-width="2" fill="none" opacity=".6" stroke-linecap="round"/>
  <path d="M40 220 Q25 240 40 260" stroke="#fff" stroke-width="2" fill="none" opacity=".4" stroke-linecap="round"/>
  <path d="M30 210 Q10 240 30 270" stroke="#fff" stroke-width="2" fill="none" opacity=".25" stroke-linecap="round"/>
</svg>
SVG;

        case 'yok':
        default:
            return '';
    }
}

/**
 * Tum gorsel tipi secenekleri (admin form dropdown icin).
 * @return array<string,string> tip => insan-okur etiket
 */
function mz_svg_illustration_options(): array
{
    return [
        'svg_kalkan'   => 'Kalkan + Check (Genel Koruma)',
        'svg_araba'    => 'Araba + Yol (Oto Sigortaları)',
        'svg_ev_kalp'  => 'Ev + Kalp (Sağlık & DASK)',
        'svg_kulaklik' => 'Kulaklık + 7/24 Saat (Hasar Desteği)',
        'custom_url'   => 'Özel Görsel URL',
        'yok'          => 'Görsel Yok (sadece metin)',
    ];
}
