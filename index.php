<?php
/**
 * Mizan Sigorta - Front Controller
 * index.php
 *
 * URL Rewrite ile (.htaccess) tum istekler bu dosyaya gelir; basit eslestirme.
 * /                   -> public/home.php
 * /urun/<slug>        -> public/urun.php
 * /teklif-al          -> public/teklif.php
 * /hasar-ihbari       -> public/hasar.php
 * /blog               -> public/blog-list.php
 * /blog/<slug>        -> public/blog-detay.php
 * /sayfa/<slug>       -> public/sayfa.php
 * /sss                -> public/sss.php
 * /iletisim           -> public/iletisim.php
 * /sitemap.xml        -> public/sitemap.php
 * /robots.txt         -> public/robots.php
 */

declare(strict_types=1);

define('MIZAN_BOOT', true);
require __DIR__ . '/includes/bootstrap.php';

// Bakim modu (admin alanlari haric)
check_maintenance(false);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = '/' . trim($path, '/');

$routes = [
    '#^/$#'                          => 'home.php',
    '#^/teklif-al$#'                 => 'teklif.php',
    '#^/teklif-tesekkur$#'           => 'teklif-tesekkur.php',
    '#^/hasar-ihbari$#'              => 'hasar.php',
    '#^/iletisim$#'                  => 'iletisim.php',
    '#^/sss$#'                       => 'sss.php',
    '#^/blog$#'                      => 'blog-list.php',
    '#^/blog/([a-z0-9\-]+)$#'        => 'blog-detay.php',
    '#^/urun/([a-z0-9\-]+)$#'        => 'urun.php',
    '#^/sayfa/([a-z0-9\-]+)$#'       => 'sayfa.php',
    '#^/sitemap\.xml$#'              => 'sitemap.php',
    '#^/robots\.txt$#'               => 'robots.php',
];

foreach ($routes as $regex => $file) {
    if (preg_match($regex, $path, $m)) {
        $params = array_slice($m, 1);
        require MIZAN_PUBLIC . '/' . $file;
        exit;
    }
}

// 404
http_response_code(404);
$pageTitle = 'Sayfa bulunamadi - ' . SITE_NAME;
require MIZAN_INC . '/header.php';
?>
<section class="container py-5 text-center">
  <h1 class="display-4 text-warning">404</h1>
  <p class="lead">Aradığınız sayfa bulunamadı.</p>
  <a href="<?= u('/') ?>" class="btn btn-primary">Anasayfaya Dön</a>
</section>
<?php require MIZAN_INC . '/footer.php';
