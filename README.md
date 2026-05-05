# Mizan Sigorta

Kurumsal sigorta brokerliği web sitesi ve yönetim paneli.
**PHP 8.3+** · **MariaDB/MySQL** · **PDO** · **DirectAdmin/LiteSpeed** · sıfır framework

---

## İçindekiler

- [Özellikler](#özellikler)
- [Sistem Gereksinimleri](#sistem-gereksinimleri)
- [Kurulum](#kurulum)
- [İlk Giriş](#ilk-giriş)
- [Cron Görevleri](#cron-görevleri)
- [Otomatik Güncelleme Sistemi](#otomatik-güncelleme-sistemi)
- [Klasör Yapısı](#klasör-yapısı)
- [Sürüm Notları](#sürüm-notları)

---

## Özellikler

### Public Site
- Anasayfa, ürün sayfaları, çoklu adımlı teklif formu
- Hasar bildirimi, iletişim, S.S.S., blog, dinamik CMS sayfaları
- SEO dostu URL yapısı, sitemap.xml, robots.txt
- Mobil uyumlu Navy/Gold tema
- WhatsApp ve hızlı teklif yüzen butonları

### Yönetim Paneli (`/yonetim/`)
- Dashboard: istatistikler, son teklifler, sürüm bilgisi
- Müşteri / Teklif / Poliçe / Hasar yönetimi
- Hatırlatma kuralları (yeni teklif, takip, poliçe yenileme, doğum günü)
- Hatırlatma logu ve görsel zaman çizelgesi
- CMS sayfaları, blog, S.S.S., iletişim mesajları
- Anlaşmalı sigorta şirketleri ve referanslar
- Çok kullanıcılı rol sistemi (Süper Admin / Yönetici / Operatör / Satış)
- Audit log (tüm işlemler kaydedilir)
- 7 sekmeli kapsamlı ayarlar paneli (genel, iletişim, SEO, sosyal, SMTP, teklif, sistem)
- GitHub Releases tabanlı tek tıkla güncelleme sistemi

### Cron Görevleri
- `cron/teklif-hatirlatma.php` — günlük otomatik hatırlatma motoru (5 tetikleyici tipi)
- `cron/otomatik-yedekleme.php` — haftalık DB+uploads ZIP yedekleme (30 gün retention)

---

## Sistem Gereksinimleri

- PHP **8.3** veya üzeri
- MariaDB 10.5+ / MySQL 8.0+
- PHP eklentileri: `pdo_mysql`, `mbstring`, `curl`, `zip`, `gd` veya `imagick`, `openssl`
- Apache/LiteSpeed (mod_rewrite gerekli)
- En az 200 MB disk alanı

---

## Kurulum

### 1. Dosyaları Yükle

ZIP'i sunucuya yükleyin ve DirectAdmin'in **File Manager**'ı veya SSH üzerinden açın:

```bash
unzip mizansigorta-1.0.0.zip
```

İçeriği `public_html/` (veya domain'inizin document root'u) klasörüne taşıyın.

### 2. Veritabanı Oluştur

DirectAdmin → MySQL Management → "Create new database" ile veritabanı ve kullanıcı oluşturun.

### 3. Konfigürasyon

```bash
cp config/config.sample.php config/config.php
```

`config/config.php` dosyasını açıp aşağıdaki sabitleri kendi değerlerinizle değiştirin:

```php
const DB_HOST    = 'localhost';
const DB_NAME    = 'sizin_db_adi';
const DB_USER    = 'sizin_db_user';
const DB_PASS    = 'sizin_sifre';
const SITE_BASE_URL = 'https://mizansigorta.com';
```

### 4. Veritabanı Şemasını Yükle

İki seçeneğiniz var:

**A) phpMyAdmin üzerinden:** `migration.sql` dosyasının içeriğini phpMyAdmin → "SQL" sekmesinde çalıştırın.

**B) Otomatik:** Site ilk açıldığında bootstrap migration'ı kontrol eder ve eksikse çalıştırır.

### 5. İzinler

Aşağıdaki klasörlere yazma izni gerekir (DirectAdmin'de chmod 755 genelde yeterlidir):

```bash
chmod -R 755 uploads/ backups/
```

### 6. .htaccess Kontrolü

Repository'deki `.htaccess` dosyasının kopyalandığını ve Apache'nin `mod_rewrite` modülünün aktif olduğunu doğrulayın.

---

## İlk Giriş

`https://mizansigorta.com/yonetim/` adresine gidin.

**Varsayılan giriş:**
```
E-posta: destek@mizansigorta.com.tr
Şifre:   Mizan2026!
```

> **ÖNEMLİ:** İlk girişten sonra hemen `Profilim → Şifre Değiştir` üzerinden şifrenizi güncelleyin.

---

## Cron Görevleri

### Hatırlatma Cron'u (Önerilen: her gün 09:00)

DirectAdmin → Cron Jobs → Add new cron job:

```
0 9 * * *  wget -q -O- "https://mizansigorta.com/cron/teklif-hatirlatma.php?key=ANAHTARINIZ" > /dev/null 2>&1
```

`ANAHTARINIZ` değeri yönetim panelinde **Ayarlar → Sistem & Güncelleme** sekmesinden kopyalanır.

### Otomatik Yedekleme (Önerilen: Pazar 03:00)

```
0 3 * * 0  wget -q -O- "https://mizansigorta.com/cron/otomatik-yedekleme.php?key=ANAHTARINIZ" > /dev/null 2>&1
```

Yedekler `/backups/` klasörüne ZIP olarak kaydedilir, 30 günden eski olanlar otomatik silinir.

---

## Otomatik Güncelleme Sistemi

GitHub Releases tabanlı tek tıkla güncelleme sistemi mevcuttur.

### Hazırlık

1. **Ayarlar → Sistem & Güncelleme** sekmesine gidin.
2. `github_token` alanına GitHub Personal Access Token (repo scope) girin.
3. Kaydedin.

### Güncelleme

1. **Sistem Güncellemesi** menüsünü açın.
2. **Sürüm Bilgisini Yenile** butonuna basın.
3. Yeni sürüm varsa **Şimdi Güncelle** butonuna tıklayın.
4. Sistem otomatik olarak: yedek alır → yeni sürümü indirir → dosyaları kopyalar (config & uploads korunur) → migration.sql'i çalıştırır → manifest'i günceller.

### Korunan Dosyalar/Klasörler

Güncelleme sırasında dokunulmayan içerikler:
- `config/config.php`
- `uploads/` (tüm içerik)
- `backups/` (tüm içerik)
- `.htaccess`

---

## Klasör Yapısı

```
mizansigorta/
├── manifest.json           # Sürüm bilgisi
├── migration.sql           # Idempotent şema
├── index.php               # Public router
├── .htaccess               # Rewrite kuralları
├── config/                 # Yapılandırma (config.php production'da)
├── includes/               # Bootstrap, db, auth, csrf, mail, helpers
├── public/                 # Public sayfaları (home, teklif, blog vb.)
├── yonetim/                # Yönetim paneli
├── cron/                   # Cron scriptleri
├── assets/                 # CSS, JS, img
├── uploads/                # Kullanıcı yüklemeleri (korunur)
└── backups/                # Otomatik yedekler (korunur)
```

---

## Sürüm Notları

### v1.0.0 (İlk Sürüm — 2026-05)
- Public site (anasayfa, ürünler, teklif, hasar, iletişim, blog, S.S.S., CMS sayfaları)
- Tam fonksiyonlu yönetim paneli (24+ modül)
- Hatırlatma motoru (5 tetikleyici tipi)
- Otomatik yedekleme cron'u
- GitHub Releases tabanlı güncelleme sistemi
- 7 sekmeli ayarlar paneli + SMTP test
- Çok kullanıcılı rol sistemi
- Tam audit log
- Mobil uyumlu Navy/Gold tema

---

## Geliştirici

**CODEGA** · [codega.com.tr](https://codega.com.tr) · Konya, Türkiye

GitHub: [github.com/codegatr/mizansigorta](https://github.com/codegatr/mizansigorta)
