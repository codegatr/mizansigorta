-- =============================================================
-- Mizan Sigorta v1.0.0 - migration.sql
-- PHP 8.3+ / MariaDB 10.6+ uyumlu
-- Prefix: mz_
-- Karakter seti: utf8mb4_unicode_ci
-- Idempotent: INFORMATION_SCHEMA kontrolu ile guvenli yeniden calistirma
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------
-- 1) Kullanicilar (admin, operator, satis temsilcisi)
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mz_kullanicilar` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ad_soyad` VARCHAR(120) NOT NULL,
    `email` VARCHAR(160) NOT NULL,
    `telefon` VARCHAR(32) DEFAULT NULL,
    `sifre_hash` VARCHAR(255) NOT NULL,
    `rol` ENUM('superadmin','admin','operator','satis') NOT NULL DEFAULT 'operator',
    `aktif` TINYINT(1) NOT NULL DEFAULT 1,
    `son_giris` DATETIME DEFAULT NULL,
    `son_giris_ip` VARCHAR(45) DEFAULT NULL,
    `hatali_giris` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `kilit_bitis` DATETIME DEFAULT NULL,
    `olusturma_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `guncelleme_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_email` (`email`),
    KEY `idx_rol` (`rol`),
    KEY `idx_aktif` (`aktif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------
-- 2) Sigorta urun kategorileri (Kasko, Trafik, Konut...)
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mz_urunler` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug` VARCHAR(80) NOT NULL,
    `baslik` VARCHAR(160) NOT NULL,
    `kisa_aciklama` VARCHAR(280) DEFAULT NULL,
    `aciklama` LONGTEXT,
    `icon` VARCHAR(80) DEFAULT NULL,
    `gorsel` VARCHAR(255) DEFAULT NULL,
    `seo_baslik` VARCHAR(180) DEFAULT NULL,
    `seo_aciklama` VARCHAR(280) DEFAULT NULL,
    `sira` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `aktif` TINYINT(1) NOT NULL DEFAULT 1,
    `one_cikan` TINYINT(1) NOT NULL DEFAULT 0,
    `olusturma_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `guncelleme_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_aktif_sira` (`aktif`,`sira`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------
-- 3) Musteriler (cari)
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mz_musteriler` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tip` ENUM('bireysel','kurumsal') NOT NULL DEFAULT 'bireysel',
    `ad_soyad` VARCHAR(160) NOT NULL,
    `firma_adi` VARCHAR(200) DEFAULT NULL,
    `tckn_vkn` VARCHAR(11) DEFAULT NULL,
    `vergi_dairesi` VARCHAR(120) DEFAULT NULL,
    `email` VARCHAR(160) DEFAULT NULL,
    `telefon` VARCHAR(32) DEFAULT NULL,
    `adres` TEXT,
    `sehir` VARCHAR(60) DEFAULT NULL,
    `ilce` VARCHAR(60) DEFAULT NULL,
    `dogum_tarihi` DATE DEFAULT NULL,
    `notlar` TEXT,
    `kvkk_onay` TINYINT(1) NOT NULL DEFAULT 0,
    `kvkk_onay_tarihi` DATETIME DEFAULT NULL,
    `kvkk_onay_ip` VARCHAR(45) DEFAULT NULL,
    `eklemeyen_id` INT UNSIGNED DEFAULT NULL,
    `olusturma_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `guncelleme_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tckn_vkn` (`tckn_vkn`),
    KEY `idx_email` (`email`),
    KEY `idx_telefon` (`telefon`),
    KEY `idx_tip` (`tip`),
    KEY `idx_dogum` (`dogum_tarihi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------
-- 4) Teklifler (websiteden gelen + manuel acilan)
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mz_teklifler` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `teklif_no` VARCHAR(20) NOT NULL,
    `urun_id` INT UNSIGNED DEFAULT NULL,
    `musteri_id` INT UNSIGNED DEFAULT NULL,
    `kaynak` ENUM('web','telefon','whatsapp','email','sosyal','referans','manuel') NOT NULL DEFAULT 'web',
    `durum` ENUM('yeni','islemde','teklif_hazir','teklif_gonderildi','onaylandi','police_oldu','iptal','kayip') NOT NULL DEFAULT 'yeni',
    `oncelik` ENUM('dusuk','normal','yuksek','acil') NOT NULL DEFAULT 'normal',
    `atanan_kullanici_id` INT UNSIGNED DEFAULT NULL,
    `ad_soyad` VARCHAR(160) NOT NULL,
    `firma_adi` VARCHAR(200) DEFAULT NULL,
    `email` VARCHAR(160) DEFAULT NULL,
    `telefon` VARCHAR(32) DEFAULT NULL,
    `il` VARCHAR(60) DEFAULT NULL,
    `ilce` VARCHAR(60) DEFAULT NULL,
    `aciklama` TEXT,
    `urun_detay_json` LONGTEXT,
    `tahmini_tutar` DECIMAL(12,2) DEFAULT NULL,
    `verilen_tutar` DECIMAL(12,2) DEFAULT NULL,
    `para_birimi` CHAR(3) NOT NULL DEFAULT 'TRY',
    `gecerlilik_tarihi` DATE DEFAULT NULL,
    `son_iletisim_tarihi` DATETIME DEFAULT NULL,
    `bir_sonraki_takip_tarihi` DATE DEFAULT NULL,
    `hatirlatma_aktif` TINYINT(1) NOT NULL DEFAULT 1,
    `hatirlatma_sayisi` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `son_hatirlatma_tarihi` DATETIME DEFAULT NULL,
    `kvkk_onay` TINYINT(1) NOT NULL DEFAULT 0,
    `ip_adresi` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `olusturma_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `guncelleme_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_teklif_no` (`teklif_no`),
    KEY `idx_durum` (`durum`),
    KEY `idx_urun` (`urun_id`),
    KEY `idx_musteri` (`musteri_id`),
    KEY `idx_atanan` (`atanan_kullanici_id`),
    KEY `idx_olusturma` (`olusturma_tarihi`),
    KEY `idx_takip_tarihi` (`bir_sonraki_takip_tarihi`),
    KEY `idx_hatirlatma` (`hatirlatma_aktif`,`durum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------
-- 5) Teklif notlari / iletisim gecmisi
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mz_teklif_notlari` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `teklif_id` INT UNSIGNED NOT NULL,
    `kullanici_id` INT UNSIGNED DEFAULT NULL,
    `tip` ENUM('not','arama','email','sms','whatsapp','toplanti','sistem') NOT NULL DEFAULT 'not',
    `icerik` TEXT NOT NULL,
    `olusturma_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_teklif` (`teklif_id`),
    KEY `idx_kullanici` (`kullanici_id`),
    KEY `idx_tip` (`tip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------
-- 6) Hatirlatma kurallari (esnek konfigurasyon)
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mz_hatirlatma_kurallari` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ad` VARCHAR(160) NOT NULL,
    `aciklama` TEXT,
    `tetikleyici` ENUM('teklif_yeni','teklif_islemde','teklif_gonderildi','police_yenileme','dogum_gunu') NOT NULL,
    `gun_sayisi` SMALLINT NOT NULL DEFAULT 3,
    `kanal` ENUM('email','sms','panel','tumu') NOT NULL DEFAULT 'email',
    `email_konu` VARCHAR(200) DEFAULT NULL,
    `email_govde` LONGTEXT,
    `sms_metni` VARCHAR(320) DEFAULT NULL,
    `aktif` TINYINT(1) NOT NULL DEFAULT 1,
    `olusturma_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `guncelleme_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_aktif_tetik` (`aktif`,`tetikleyici`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------
-- 7) Gonderilen hatirlatmalar log (mukerrer onleme)
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mz_hatirlatma_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kural_id` INT UNSIGNED DEFAULT NULL,
    `teklif_id` INT UNSIGNED DEFAULT NULL,
    `police_id` INT UNSIGNED DEFAULT NULL,
    `musteri_id` INT UNSIGNED DEFAULT NULL,
    `kanal` ENUM('email','sms','panel') NOT NULL,
    `alici` VARCHAR(160) DEFAULT NULL,
    `konu` VARCHAR(200) DEFAULT NULL,
    `icerik` TEXT,
    `durum` ENUM('basarili','hatali','bekliyor') NOT NULL DEFAULT 'basarili',
    `hata_mesaji` VARCHAR(500) DEFAULT NULL,
    `gonderim_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_teklif` (`teklif_id`),
    KEY `idx_police` (`police_id`),
    KEY `idx_kural` (`kural_id`),
    KEY `idx_durum_tarih` (`durum`,`gonderim_tarihi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------
-- 8) Police / sigorta kayitlari (yenileme takibi icin kritik)
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mz_policeler` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `police_no` VARCHAR(50) NOT NULL,
    `teklif_id` INT UNSIGNED DEFAULT NULL,
    `musteri_id` INT UNSIGNED DEFAULT NULL,
    `urun_id` INT UNSIGNED DEFAULT NULL,
    `sirket_id` INT UNSIGNED DEFAULT NULL,
    `acente_kodu` VARCHAR(60) DEFAULT NULL,
    `baslangic_tarihi` DATE DEFAULT NULL,
    `bitis_tarihi` DATE DEFAULT NULL,
    `prim_tutari` DECIMAL(12,2) DEFAULT NULL,
    `komisyon_tutari` DECIMAL(12,2) DEFAULT NULL,
    `para_birimi` CHAR(3) NOT NULL DEFAULT 'TRY',
    `odeme_tipi` ENUM('pesin','taksit') NOT NULL DEFAULT 'pesin',
    `taksit_sayisi` SMALLINT UNSIGNED DEFAULT NULL,
    `durum` ENUM('aktif','sona_erdi','iptal','beklemede') NOT NULL DEFAULT 'aktif',
    `yenileme_uyarildi` TINYINT(1) NOT NULL DEFAULT 0,
    `dosya` VARCHAR(255) DEFAULT NULL,
    `notlar` TEXT,
    `olusturma_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `guncelleme_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_police_no` (`police_no`),
    KEY `idx_musteri` (`musteri_id`),
    KEY `idx_urun` (`urun_id`),
    KEY `idx_sirket` (`sirket_id`),
    KEY `idx_bitis` (`bitis_tarihi`),
    KEY `idx_durum` (`durum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------
-- 9) Hasar ihbarlari
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mz_hasarlar` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `dosya_no` VARCHAR(20) NOT NULL,
    `musteri_id` INT UNSIGNED DEFAULT NULL,
    `police_id` INT UNSIGNED DEFAULT NULL,
    `urun_id` INT UNSIGNED DEFAULT NULL,
    `ad_soyad` VARCHAR(160) NOT NULL,
    `email` VARCHAR(160) DEFAULT NULL,
    `telefon` VARCHAR(32) DEFAULT NULL,
    `olay_tarihi` DATE DEFAULT NULL,
    `olay_yeri` VARCHAR(255) DEFAULT NULL,
    `olay_aciklama` TEXT NOT NULL,
    `tahmini_zarar` DECIMAL(12,2) DEFAULT NULL,
    `durum` ENUM('yeni','inceleniyor','eksiltme_talep','onaylandi','reddedildi','tamamlandi') NOT NULL DEFAULT 'yeni',
    `atanan_kullanici_id` INT UNSIGNED DEFAULT NULL,
    `kvkk_onay` TINYINT(1) NOT NULL DEFAULT 0,
    `ip_adresi` VARCHAR(45) DEFAULT NULL,
    `olusturma_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `guncelleme_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_dosya_no` (`dosya_no`),
    KEY `idx_musteri` (`musteri_id`),
    KEY `idx_police` (`police_id`),
    KEY `idx_durum` (`durum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------
-- 10) Hasar dosya ekleri
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mz_hasar_ekleri` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `hasar_id` INT UNSIGNED NOT NULL,
    `dosya_yolu` VARCHAR(255) NOT NULL,
    `dosya_adi` VARCHAR(200) DEFAULT NULL,
    `boyut` INT UNSIGNED DEFAULT NULL,
    `mime` VARCHAR(120) DEFAULT NULL,
    `olusturma_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_hasar` (`hasar_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------
-- 11) Sayfalar (CMS - kurumsal, hakkimizda, kvkk vs.)
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mz_sayfalar` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug` VARCHAR(120) NOT NULL,
    `baslik` VARCHAR(200) NOT NULL,
    `icerik` LONGTEXT,
    `seo_baslik` VARCHAR(180) DEFAULT NULL,
    `seo_aciklama` VARCHAR(280) DEFAULT NULL,
    `seo_anahtar_kelimeler` VARCHAR(280) DEFAULT NULL,
    `kapak_gorseli` VARCHAR(255) DEFAULT NULL,
    `menude_goster` TINYINT(1) NOT NULL DEFAULT 0,
    `menu_sirasi` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `aktif` TINYINT(1) NOT NULL DEFAULT 1,
    `sistem` TINYINT(1) NOT NULL DEFAULT 0,
    `olusturma_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `guncelleme_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_aktif_menu` (`aktif`,`menude_goster`,`menu_sirasi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------
-- 12) Blog yazilari
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mz_blog` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug` VARCHAR(140) NOT NULL,
    `baslik` VARCHAR(220) NOT NULL,
    `ozet` VARCHAR(500) DEFAULT NULL,
    `icerik` LONGTEXT,
    `kapak_gorseli` VARCHAR(255) DEFAULT NULL,
    `kategori` VARCHAR(80) DEFAULT NULL,
    `etiketler` VARCHAR(280) DEFAULT NULL,
    `seo_baslik` VARCHAR(180) DEFAULT NULL,
    `seo_aciklama` VARCHAR(280) DEFAULT NULL,
    `goruntulenme` INT UNSIGNED NOT NULL DEFAULT 0,
    `yazar_id` INT UNSIGNED DEFAULT NULL,
    `yayin_tarihi` DATETIME DEFAULT NULL,
    `aktif` TINYINT(1) NOT NULL DEFAULT 1,
    `olusturma_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `guncelleme_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_aktif_yayin` (`aktif`,`yayin_tarihi`),
    KEY `idx_kategori` (`kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------
-- 13) SSS (sik sorulan sorular)
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mz_sss` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kategori` VARCHAR(80) DEFAULT 'Genel',
    `soru` VARCHAR(280) NOT NULL,
    `cevap` LONGTEXT NOT NULL,
    `sira` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `aktif` TINYINT(1) NOT NULL DEFAULT 1,
    `olusturma_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `guncelleme_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_aktif_kategori_sira` (`aktif`,`kategori`,`sira`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------
-- 14) Iletisim formu mesajlari
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mz_iletisim_mesajlari` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ad_soyad` VARCHAR(160) NOT NULL,
    `email` VARCHAR(160) DEFAULT NULL,
    `telefon` VARCHAR(32) DEFAULT NULL,
    `konu` VARCHAR(200) DEFAULT NULL,
    `mesaj` TEXT NOT NULL,
    `okundu` TINYINT(1) NOT NULL DEFAULT 0,
    `cevaplandi` TINYINT(1) NOT NULL DEFAULT 0,
    `kvkk_onay` TINYINT(1) NOT NULL DEFAULT 0,
    `ip_adresi` VARCHAR(45) DEFAULT NULL,
    `olusturma_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_okundu` (`okundu`),
    KEY `idx_olusturma` (`olusturma_tarihi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------
-- 15) Referanslar (musteri yorumlari/anlasmali kurumlar)
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mz_referanslar` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tip` ENUM('yorum','kurum') NOT NULL DEFAULT 'yorum',
    `ad` VARCHAR(160) NOT NULL,
    `unvan` VARCHAR(160) DEFAULT NULL,
    `mesaj` TEXT,
    `puan` TINYINT UNSIGNED DEFAULT 5,
    `gorsel` VARCHAR(255) DEFAULT NULL,
    `link` VARCHAR(255) DEFAULT NULL,
    `sira` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `aktif` TINYINT(1) NOT NULL DEFAULT 1,
    `olusturma_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tip_aktif_sira` (`tip`,`aktif`,`sira`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------
-- 16) Anlasmali sigorta sirketleri
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mz_sigorta_sirketleri` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ad` VARCHAR(160) NOT NULL,
    `logo` VARCHAR(255) DEFAULT NULL,
    `web_sitesi` VARCHAR(255) DEFAULT NULL,
    `acente_kodu` VARCHAR(60) DEFAULT NULL,
    `aciklama` TEXT,
    `sira` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `aktif` TINYINT(1) NOT NULL DEFAULT 1,
    `olusturma_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_aktif_sira` (`aktif`,`sira`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------
-- 17) Bulten aboneleri
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mz_bulten_aboneleri` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(160) NOT NULL,
    `ad_soyad` VARCHAR(160) DEFAULT NULL,
    `aktif` TINYINT(1) NOT NULL DEFAULT 1,
    `kvkk_onay` TINYINT(1) NOT NULL DEFAULT 1,
    `ip_adresi` VARCHAR(45) DEFAULT NULL,
    `olusturma_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------
-- 18) Site ayarlari (anahtar/deger)
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mz_ayarlar` (
    `anahtar` VARCHAR(80) NOT NULL,
    `deger` LONGTEXT,
    `aciklama` VARCHAR(255) DEFAULT NULL,
    `grup` VARCHAR(60) NOT NULL DEFAULT 'genel',
    `tip` ENUM('text','textarea','number','email','tel','url','password','select','checkbox','json','color','file') NOT NULL DEFAULT 'text',
    `secenekler` TEXT,
    `guncelleme_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`anahtar`),
    KEY `idx_grup` (`grup`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------
-- 19) Audit log (denetim kaydi)
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mz_audit_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kullanici_id` INT UNSIGNED DEFAULT NULL,
    `kullanici_email` VARCHAR(160) DEFAULT NULL,
    `eylem` VARCHAR(80) NOT NULL,
    `nesne_tip` VARCHAR(80) DEFAULT NULL,
    `nesne_id` INT UNSIGNED DEFAULT NULL,
    `aciklama` VARCHAR(500) DEFAULT NULL,
    `eski_veri` LONGTEXT,
    `yeni_veri` LONGTEXT,
    `ip_adresi` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `olusturma_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kullanici` (`kullanici_id`),
    KEY `idx_eylem` (`eylem`),
    KEY `idx_nesne` (`nesne_tip`,`nesne_id`),
    KEY `idx_tarih` (`olusturma_tarihi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------
-- 20) Guncelleme gecmisi (manifest update sistemi icin)
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mz_guncellemeler` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `surum` VARCHAR(20) NOT NULL,
    `kaynak` VARCHAR(120) DEFAULT NULL,
    `aciklama` TEXT,
    `migration_calisti` TINYINT(1) NOT NULL DEFAULT 0,
    `durum` ENUM('basarili','hatali','iptal') NOT NULL DEFAULT 'basarili',
    `hata_mesaji` TEXT,
    `kullanici_id` INT UNSIGNED DEFAULT NULL,
    `olusturma_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_surum` (`surum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- INITIAL DATA (idempotent - INSERT IGNORE)
-- ============================================================

-- Default superadmin: admin@mizansigorta.com / Mizan2026!  (sifirlamak icin admin/login.php)
INSERT IGNORE INTO `mz_kullanicilar` (`id`,`ad_soyad`,`email`,`sifre_hash`,`rol`,`aktif`)
VALUES (1,'Sistem Yoneticisi','admin@mizansigorta.com','$2y$10$2sYcHu1xxtmPuRzL.jFXLe0HRioz2PJe8E5lh38L3AzjON8LgpqkC','superadmin',1);
-- Hash karsiligi: Mizan2026!

INSERT IGNORE INTO `mz_urunler` (`slug`,`baslik`,`kisa_aciklama`,`icon`,`sira`,`aktif`,`one_cikan`) VALUES
('kasko-sigortasi','Kasko Sigortası','Aracınızı her türlü hasara karşı kapsamlı koruma altına alın.','car-front',1,1,1),
('trafik-sigortasi','Trafik Sigortası','Zorunlu trafik sigortası — yasal güvence.','traffic-cone',2,1,1),
('konut-sigortasi','Konut Sigortası','Eviniz, eşyalarınız ve sevdikleriniz için kapsamlı güvence.','house-fill',3,1,1),
('dask','DASK Zorunlu Deprem','Zorunlu Deprem Sigortası — yasal koruma.','geo-fill',4,1,1),
('isyeri-sigortasi','İşyeri Sigortası','İşletmenize özel kapsamlı paketler.','building',5,1,0),
('saglik-sigortasi','Sağlık Sigortası','Özel sağlık ve tamamlayıcı sağlık çözümleri.','heart-pulse',6,1,1),
('hayat-sigortasi','Hayat Sigortası','Sevdiklerinizin geleceği için güvence.','shield-check',7,1,0),
('seyahat-sigortasi','Seyahat Sigortası','Yurtiçi ve yurtdışı seyahatleriniz için.','airplane',8,1,0),
('ferdi-kaza','Ferdi Kaza Sigortası','Bireysel kazalara karşı güvence paketleri.','person-bounding-box',9,1,0),
('tarim-sigortasi','Tarım Sigortası (TARSİM)','Çiftçimize özel devlet destekli sigortalar.','tree-fill',10,1,0),
('nakliyat-sigortasi','Nakliyat Sigortası','Yük ve emtia taşıma teminatları.','truck',11,1,0),
('sorumluluk-sigortasi','Sorumluluk Sigortaları','Mesleki ve işveren sorumluluk paketleri.','briefcase',12,1,0);

INSERT IGNORE INTO `mz_sayfalar` (`slug`,`baslik`,`icerik`,`menude_goster`,`menu_sirasi`,`sistem`) VALUES
('hakkimizda','Hakkımızda','<h2>Mizan Sigorta</h2><p>Müşteri memnuniyetini önceliklendiren çözüm ortağınız. Anlaşmalı sigorta şirketleri ile en uygun teklifleri size sunuyoruz.</p>',1,10,1),
('kvkk','KVKK Aydınlatma Metni','<p>6698 sayılı Kişisel Verilerin Korunması Kanunu kapsamında kişisel verileriniz, sigorta aracılık hizmetlerinin sunulması amacıyla işlenmektedir.</p>',0,90,1),
('cerez-politikasi','Çerez Politikası','<p>Web sitemizde kullanıcı deneyimini iyileştirmek için çerezler kullanılmaktadır.</p>',0,91,1),
('gizlilik-politikasi','Gizlilik Politikası','<p>Kişisel verileriniz yalnızca size hizmet sunmak amacıyla işlenir, üçüncü taraflarla yasal zorunluluk dışında paylaşılmaz.</p>',0,92,1);

INSERT IGNORE INTO `mz_sss` (`kategori`,`soru`,`cevap`,`sira`) VALUES
('Genel','Mizan Sigorta hangi sigorta şirketleri ile çalışıyor?','Türkiye''nin önde gelen sigorta şirketleri ile anlaşmalı çalışıyoruz. Müşterimize en uygun teminat ve fiyatı bulmak için tekliflerinizi karşılaştırıyoruz.',1),
('Teklif','Online teklif almak ücretli mi?','Hayır, web sitemizden alınan tüm teklifler tamamen ücretsizdir ve sizi hiçbir şekilde bağlamaz.',2),
('Teklif','Teklifim ne kadar sürede hazır olur?','Çalışma saatleri içinde gönderilen tekliflere genellikle 1-3 saat içinde dönüş yapılır.',3),
('Police','Poliçemi yenilemek için ne yapmalıyım?','Poliçenizin bitiş tarihinden 30 gün önce sizi otomatik olarak hatırlatma sistemimiz ile bilgilendiriyoruz. Yeni teklif almak için bizimle iletişime geçebilirsiniz.',4),
('Hasar','Hasar durumunda ne yapmalıyım?','Web sitemizden Hasar İhbarı formunu doldurabilir veya bizi telefonla arayabilirsiniz. Süreci sigorta şirketi ile birlikte takip ediyoruz.',5);

INSERT IGNORE INTO `mz_hatirlatma_kurallari` (`ad`,`tetikleyici`,`gun_sayisi`,`kanal`,`email_konu`,`email_govde`,`aktif`) VALUES
('Yeni Teklif - Operatör Bildirimi','teklif_yeni',0,'panel','Yeni teklif geldi: #{teklif_no}','{ad_soyad} adlı müşteriden {urun_adi} için yeni teklif talebi alındı. Telefon: {telefon}',1),
('İşlemde Teklif - 2. Gün Hatırlatma','teklif_islemde',2,'email','Sayın {ad_soyad}, teklifiniz hazırlanıyor','Merhaba {ad_soyad},\n\nMizan Sigorta olarak {urun_adi} talebinizi aldık ve sizin için en uygun teklifi hazırlıyoruz. Kısa süre içinde sizinle iletişime geçeceğiz.\n\nTeklif No: {teklif_no}',1),
('Gönderilen Teklif - 3. Gün Takip','teklif_gonderildi',3,'email','Sayın {ad_soyad}, teklifimiz hakkında ne düşünüyorsunuz?','Merhaba {ad_soyad},\n\n{urun_adi} için size sunduğumuz teklif hâlâ geçerlidir. Sorularınız varsa lütfen bize ulaşın.\n\nTeklif No: {teklif_no}',1),
('Poliçe Yenileme - 30 Gün Önce','police_yenileme',30,'email','Poliçeniz {gun} gün sonra sona eriyor','Merhaba {ad_soyad},\n\n{police_no} numaralı {urun_adi} poliçeniz {bitis_tarihi} tarihinde sona eriyor. Yenileme için yeni teklif almak ister misiniz?',1),
('Poliçe Yenileme - 7 Gün Önce','police_yenileme',7,'email','Acele edin: poliçeniz 7 gün sonra bitiyor','Merhaba {ad_soyad},\n\n{police_no} numaralı poliçenizin sona ermesine 7 gün kaldı. En kısa sürede sizinle iletişime geçmemiz için lütfen yanıt verin.',1);

INSERT IGNORE INTO `mz_ayarlar` (`anahtar`,`deger`,`aciklama`,`grup`,`tip`) VALUES
('site_basligi','Mizan Sigorta - Çözüm Ortağınız','Tarayıcı sekmesinde görünen başlık','genel','text'),
('site_slogan','Güven ve Özen İle','Marka sloganı','genel','text'),
('ofis_sehirler','İstanbul, Konya, Ankara, Aksaray','Ofis bulunan şehirler (virgülle)','genel','text'),
('site_aciklamasi','Kasko, trafik, konut, sağlık ve daha fazlası için en uygun sigorta tekliflerini Mizan Sigorta''dan alın.','SEO açıklaması','seo','textarea'),
('site_anahtar_kelimeler','sigorta, kasko, trafik sigortası, konut sigortası, dask, sağlık sigortası, mizan sigorta','SEO anahtar kelimeleri','seo','text'),
('firma_adi','Mizan Sigorta Aracılık Hizmetleri','Resmi firma ünvanı','iletisim','text'),
('telefon','+90 332 000 00 00','Ana telefon','iletisim','tel'),
('whatsapp','905300000000','WhatsApp numarası (uluslararası, +/boşluksuz)','iletisim','tel'),
('email','info@mizansigorta.com','İletişim e-postası','iletisim','email'),
('adres','Konya, Türkiye','Adres','iletisim','textarea'),
('calisma_saatleri','Pzt-Cum 09:00-18:00, Cmt 10:00-14:00','Çalışma saatleri','iletisim','text'),
('harita_iframe','','Google Maps iframe HTML kodu','iletisim','textarea'),
('facebook','','Facebook URL','sosyal','url'),
('instagram','','Instagram URL','sosyal','url'),
('twitter','','Twitter/X URL','sosyal','url'),
('linkedin','','LinkedIn URL','sosyal','url'),
('youtube','','YouTube URL','sosyal','url'),
('smtp_host','','SMTP sunucu','smtp','text'),
('smtp_port','587','SMTP port','smtp','number'),
('smtp_user','','SMTP kullanıcı','smtp','text'),
('smtp_pass','','SMTP şifre','smtp','password'),
('smtp_from','','Gönderen e-posta','smtp','email'),
('smtp_from_name','Mizan Sigorta','Gönderen adı','smtp','text'),
('smtp_secure','tls','tls/ssl/yok','smtp','select'),
('teklif_bildirim_email','','Yeni teklif geldiğinde bildirim gönderilecek e-posta','teklif','email'),
('teklif_otomatik_no','TKL','Teklif numarası ön eki','teklif','text'),
('hasar_otomatik_no','HSR','Hasar dosya numarası ön eki','teklif','text'),
('cron_anahtar','','Cron URL koruması için rastgele anahtar (cron?key=...)','sistem','text'),
('github_token','','GitHub güncellemeleri için (opsiyonel) PAT','sistem','password'),
('bakim_modu','0','Bakım modu (1=aktif, ziyaretçilere bakım sayfası gösterilir)','sistem','checkbox');
