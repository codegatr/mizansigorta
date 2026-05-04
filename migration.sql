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
('github_branch','main','Güncelleme alınacak Git branch (varsayılan: main)','sistem','text'),
('bakim_modu','0','Bakım modu (1=aktif, ziyaretçilere bakım sayfası gösterilir)','sistem','checkbox');

-- ====================================================
-- v1.0.4 — Urun hiyerarsisi + Bayi basvurulari
-- ====================================================

-- 1) Urunler tablosuna parent_id ekle (hiyerarsi icin)
-- Not: Sade ALTER kullaniyoruz; runMigrations() Duplicate hata mesajini sessizce atlar
ALTER TABLE `mz_urunler` ADD COLUMN `parent_id` INT UNSIGNED DEFAULT NULL AFTER `id`;
ALTER TABLE `mz_urunler` ADD KEY `idx_parent` (`parent_id`);

-- 2) Bayi basvurulari tablosu (Temsilcimiz Olun)
CREATE TABLE IF NOT EXISTS `mz_bayi_basvurulari` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ad_soyad` VARCHAR(160) NOT NULL,
    `firma_adi` VARCHAR(200) DEFAULT NULL,
    `email` VARCHAR(160) NOT NULL,
    `telefon` VARCHAR(32) NOT NULL,
    `il` VARCHAR(80) DEFAULT NULL,
    `ilce` VARCHAR(80) DEFAULT NULL,
    `tecrube_yili` SMALLINT UNSIGNED DEFAULT 0,
    `mevcut_acentelik` VARCHAR(255) DEFAULT NULL,
    `levha_no` VARCHAR(40) DEFAULT NULL,
    `aciklama` TEXT,
    `kvkk_onay` TINYINT(1) NOT NULL DEFAULT 0,
    `durum` ENUM('yeni','degerlendirme','goruseme','kabul','red','iptal') NOT NULL DEFAULT 'yeni',
    `notlar` TEXT,
    `ip_adresi` VARCHAR(45) DEFAULT NULL,
    `okundu` TINYINT(1) NOT NULL DEFAULT 0,
    `olusturma_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `guncelleme_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_durum` (`durum`),
    KEY `idx_okundu` (`okundu`),
    KEY `idx_olusturma` (`olusturma_tarihi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Mevcut ornek urunleri sil (taze kategoriler ekleyecegiz)
DELETE FROM `mz_urunler` WHERE slug IN (
    'kasko','trafik','konut','dask','isyeri','saglik','seyahat','hayat'
) AND parent_id IS NULL;

-- 4) Ana kategoriler (parent_id NULL)
INSERT IGNORE INTO `mz_urunler` (`slug`,`baslik`,`kisa_aciklama`,`icon`,`sira`,`aktif`,`one_cikan`,`parent_id`) VALUES
('oto-sigortalari',         'Oto Sigortaları',         'Aracınız ve sürücü sorumluluğunuz için tam koruma',     'bi-car-front-fill',  10, 1, 1, NULL),
('yangin-policeleri',       'Yangın Poliçeleri',       'Konut, işyeri ve ortak alan yangın güvenceleri',         'bi-fire',            20, 1, 1, NULL),
('saglik-sigortalari',      'Sağlık Sigortaları',      'Özel ve tamamlayıcı sağlık paketleri',                   'bi-heart-pulse-fill',30, 1, 1, NULL),
('all-riskler',             'All Riskler',             'İnşaat, montaj, makine ve elektronik cihaz sigortaları', 'bi-tools',           40, 1, 0, NULL),
('nakliyat',                'Nakliyat Sigortaları',    'Tekne, yat, emtea ve taşıyıcı sorumluluk',               'bi-truck',           50, 1, 0, NULL),
('sorumluluk-sigortalari',  'Sorumluluk Sigortaları',  'Mali, mesleki ve özel güvenlik sorumluluk',              'bi-shield-shaded',   60, 1, 1, NULL),
('tarsim',                  'TARSİM Tarım Sigortaları','Bitkisel ürün, sera, hayvan ve arıcılık güvenceleri',    'bi-flower3',         70, 1, 0, NULL),
('ferdi-kaza',              'Ferdi Kaza',              'Bireysel kaza, deprem destek ve kritik hastalık',        'bi-bandaid-fill',    80, 1, 0, NULL),
('kefalet-sigortalari',     'Kefalet Sigortaları',     'Kefalet senedi, KDV iadesi, devlet destekli alacak',     'bi-file-earmark-check', 90, 1, 0, NULL);
-- SET @parent_<x> degiskenlerini tanimla (alt urunlerin parent_id'leri icin)
SET @parent_all_riskler := (SELECT id FROM mz_urunler WHERE slug='all-riskler');
SET @parent_ferdi_kaza := (SELECT id FROM mz_urunler WHERE slug='ferdi-kaza');
SET @parent_kefalet_sigortalari := (SELECT id FROM mz_urunler WHERE slug='kefalet-sigortalari');
SET @parent_nakliyat := (SELECT id FROM mz_urunler WHERE slug='nakliyat');
SET @parent_oto_sigortalari := (SELECT id FROM mz_urunler WHERE slug='oto-sigortalari');
SET @parent_saglik_sigortalari := (SELECT id FROM mz_urunler WHERE slug='saglik-sigortalari');
SET @parent_sorumluluk_sigortalari := (SELECT id FROM mz_urunler WHERE slug='sorumluluk-sigortalari');
SET @parent_tarsim := (SELECT id FROM mz_urunler WHERE slug='tarsim');
SET @parent_yangin_policeleri := (SELECT id FROM mz_urunler WHERE slug='yangin-policeleri');


-- 5) Alt urunler (parent slug'a referans verir)
-- Oto Sigortalari
INSERT IGNORE INTO `mz_urunler` (`slug`,`baslik`,`kisa_aciklama`,`icon`,`sira`,`aktif`,`parent_id`) VALUES
('kasko',                          'Kasko',                                  'Kendi aracınızı kapsamlı korur',                'bi-car-front', 11, 1, @parent_oto_sigortalari),
('trafik-zorunlu-sorumluluk',      'Karayolları Zorunlu Sorumluluk (Trafik)','Yasal zorunlu, üçüncü şahıs sorumluluk',        'bi-shield',    12, 1, @parent_oto_sigortalari),
('ihtiyari-mali-mesuliyet-imm',    'İhtiyari Mali Mesuliyet (İMM)',          'Trafik limit üstü ek sorumluluk teminatı',      'bi-shield-plus',13,1, @parent_oto_sigortalari),
('yesilkart',                      'Yeşilkart',                              'Yurtdışı sınır ötesi araç sorumluluk',          'bi-passport',  14, 1, @parent_oto_sigortalari);

-- Yangin Policeleri
INSERT IGNORE INTO `mz_urunler` (`slug`,`baslik`,`kisa_aciklama`,`icon`,`sira`,`aktif`,`parent_id`) VALUES
('isyeri-yangin-sigortasi',        'İşyeri Yangın Sigortası',                'Ticari mekanlar için yangın güvencesi',         'bi-shop',      21, 1, @parent_yangin_policeleri),
('konut-sigortasi',                'Konut Sigortası',                        'Eviniz için kapsamlı paket',                    'bi-house-fill',22, 1, @parent_yangin_policeleri),
('dask',                           'DASK Zorunlu Deprem',                    'Yasal zorunlu deprem güvencesi',                'bi-buildings', 23, 1, @parent_yangin_policeleri),
('ortak-alan-sigortasi',           'Ortak Alan Sigortası',                   'Apartman/site ortak alanları',                  'bi-building',  24, 1, @parent_yangin_policeleri);

-- Saglik
INSERT IGNORE INTO `mz_urunler` (`slug`,`baslik`,`kisa_aciklama`,`icon`,`sira`,`aktif`,`parent_id`) VALUES
('yurt-disi-seyahat-saglik',       'Yurt Dışı Seyahat Sağlık',               'Yurt dışı yolculukta sağlık güvencesi',         'bi-airplane',  31, 1, @parent_saglik_sigortalari),
('tamamlayici-saglik-sigortasi',   'Tamamlayıcı Sağlık Sigortası',           'SGK üzerine tamamlayıcı paket',                 'bi-plus-square',32, 1, @parent_saglik_sigortalari),
('ozel-saglik-sigortasi',          'Özel Sağlık Sigortası',                  'Özel hastane ve nitelikli tedavi',              'bi-hospital',  33, 1, @parent_saglik_sigortalari);

-- All Riskler
INSERT IGNORE INTO `mz_urunler` (`slug`,`baslik`,`kisa_aciklama`,`icon`,`sira`,`aktif`,`parent_id`) VALUES
('insaat-all-risk',                'İnşaat All Risk',                        'Şantiye ve inşaat süreci güvencesi',            'bi-cone-striped',41,1, @parent_all_riskler),
('montaj-all-risk',                'Montaj All Risk',                        'Mühendislik ve makine montaj sigortası',        'bi-gear-wide-connected',42,1, @parent_all_riskler),
('makine-kirilmasi',               'Makine Kırılması',                       'Üretim hattı makine arızası teminatı',          'bi-wrench-adjustable',43,1, @parent_all_riskler),
('elektronik-cihaz',               'Elektronik Cihaz Sigortası',             'Bilişim ve elektronik ekipman güvencesi',       'bi-pc-display',44, 1, @parent_all_riskler);

-- Nakliyat
INSERT IGNORE INTO `mz_urunler` (`slug`,`baslik`,`kisa_aciklama`,`icon`,`sira`,`aktif`,`parent_id`) VALUES
('tekne-hull',                     'Tekne (Hull)',                           'Gemi/tekne gövde sigortası',                    'bi-bookmark-star',51, 1, @parent_nakliyat),
('yat-sigortasi',                  'Yat Sigortası',                          'Özel/ticari yat güvencesi',                     'bi-tsunami',   52, 1, @parent_nakliyat),
('nakliyat-emtea-abonman',         'Nakliyat Emtea ve Abonman',              'Yıllık abonman emtea taşıma sigortası',         'bi-box-seam',  53, 1, @parent_nakliyat),
('tasiyici-sorumluluk',            'Taşıyıcı Sorumluluk',                    'Lojistik firma yük sorumluluk teminatı',        'bi-truck-front',54, 1, @parent_nakliyat),
('tekne-insaat-sigortasi',         'Tekne İnşaat Sigortası',                 'Tersane inşaat süreci güvencesi',               'bi-hammer',    55, 1, @parent_nakliyat);

-- Sorumluluk
INSERT IGNORE INTO `mz_urunler` (`slug`,`baslik`,`kisa_aciklama`,`icon`,`sira`,`aktif`,`parent_id`) VALUES
('isveren-mali-sorumluluk',        'İşveren Mali Sorumluluk',                'İşveren-çalışan iş kazası sorumluluğu',         'bi-people',    61, 1, @parent_sorumluluk_sigortalari),
('ucuncu-sahis-sorumluluk',        'Üçüncü Şahıs Sorumluluk',                'Üçüncü taraflara verilen zarar teminatı',       'bi-person-x',  62, 1, @parent_sorumluluk_sigortalari),
('tehlikeli-maddeler-sorumluluk',  'Tehlikeli Maddeler Zorunlu Sorumluluk',  'Tehlikeli madde işletmeleri için',              'bi-exclamation-diamond',63,1,@parent_sorumluluk_sigortalari),
('ozel-guvenlik-mali-sorumluluk',  'Özel Güvenlik Mali Sorumluluk',          'Güvenlik şirketleri için yasal teminat',        'bi-shield-lock',64,1, @parent_sorumluluk_sigortalari),
('mesleki-sorumluluk',             'Mesleki Sorumluluk',                     'Avukat/mali müşavir/doktor mesleki teminatı',   'bi-briefcase', 65, 1, @parent_sorumluluk_sigortalari);

-- TARSIM
INSERT IGNORE INTO `mz_urunler` (`slug`,`baslik`,`kisa_aciklama`,`icon`,`sira`,`aktif`,`parent_id`) VALUES
('bitkisel-urun',                  'Bitkisel Ürün Sigortası',                'Tarımsal ürün hasar güvencesi',                 'bi-tree',      71, 1, @parent_tarsim),
('sera-sigortasi',                 'Sera Sigortası',                         'Sera yapı ve içindeki ürünler',                 'bi-house-heart',72,1, @parent_tarsim),
('kucukbas-hayvan-hayat',          'Küçükbaş Hayvan Hayat Sigortası',        'Koyun/keçi hayat güvencesi',                    'bi-piggy-bank',73, 1, @parent_tarsim),
('buyukbas-hayvan-hayat',          'Büyükbaş Hayvan Hayat Sigortası',        'Sığır hayat güvencesi',                         'bi-piggy-bank-fill',74,1, @parent_tarsim),
('kumes-hayvanlari-hayat',         'Kümes Hayvanları Hayat Sigortası',       'Kanatlı hayvancılık güvencesi',                 'bi-egg',       75, 1, @parent_tarsim),
('su-urunleri-sigortasi',          'Su Ürünleri Sigortası',                  'Su ürünleri yetiştiriciliği',                   'bi-droplet-half',76,1, @parent_tarsim),
('aricilik-sigortasi',             'Arıcılık Sigortası',                     'Arı kovanları ve bal üretimi',                  'bi-bug',       77, 1, @parent_tarsim);

-- Ferdi Kaza
INSERT IGNORE INTO `mz_urunler` (`slug`,`baslik`,`kisa_aciklama`,`icon`,`sira`,`aktif`,`parent_id`) VALUES
('ferdi-kaza-sigortasi',           'Ferdi Kaza Sigortası',                   'Bireysel kaza koruması',                        'bi-bandaid',   81, 1, @parent_ferdi_kaza),
('deprem-destek-sigortasi',        'Deprem Destek Sigortası',                'DASK üzerine ek destek paketi',                 'bi-activity',  82, 1, @parent_ferdi_kaza),
('kritik-hastaliklar',             'Kritik Hastalıklar',                     'Kanser, kalp gibi kritik hastalık paketi',      'bi-heart',     83, 1, @parent_ferdi_kaza);

-- Kefalet
INSERT IGNORE INTO `mz_urunler` (`slug`,`baslik`,`kisa_aciklama`,`icon`,`sira`,`aktif`,`parent_id`) VALUES
('kefalet-senedi',                 'Kefalet Senedi',                         'İhale ve kefalet teminatı senedi',              'bi-file-earmark-ruled',91,1, @parent_kefalet_sigortalari),
('kdv-iadesi',                     'KDV İadesi',                             'KDV iade kefalet sigortası',                    'bi-cash-coin', 92, 1, @parent_kefalet_sigortalari),
('devlet-destekli-alacak-sigorta', 'Devlet Destekli Alacak Sigorta',         'KOBİ alacak güvencesi',                         'bi-bank',      93, 1, @parent_kefalet_sigortalari),
('bina-tamamlama-sigortasi',       'Bina Tamamlama Sigortası',               'Müteahhit-tüketici tamamlama teminatı',         'bi-building-add',94,1, @parent_kefalet_sigortalari),
('lisansli-depoculuk',             'Lisanslı Depoculuk',                     'Tarımsal lisanslı depo güvencesi',              'bi-archive',   95, 1, @parent_kefalet_sigortalari);

-- 6) one_cikan flagini ana kategoriler icin yeniden yukle (yukaridaki kayitlar one_cikan'i set etmedi)
UPDATE `mz_urunler` SET one_cikan = 1 WHERE slug IN ('oto-sigortalari','yangin-policeleri','saglik-sigortalari','sorumluluk-sigortalari') AND parent_id IS NULL;

-- 7) Yeni ayarlar
INSERT IGNORE INTO `mz_ayarlar` (`anahtar`,`deger`,`aciklama`,`grup`,`tip`) VALUES
('istanbul_adres','Fetih Mah. Libadiye Cad. Tahralı Sok. Kavakyeli İş Merkezi D-Blok K:9 D:24 ATAŞEHİR / İSTANBUL','İstanbul ofis adresi','iletisim','textarea'),
('konya_adres','Karaciğan Mah. Ali Ulvi Kurucu Cad. Enntepe Mall Office B Blok No:407 KARATAY / KONYA','Konya ofis adresi','iletisim','textarea'),
('ankara_adres','','Ankara ofis adresi','iletisim','textarea'),
('aksaray_adres','','Aksaray ofis adresi','iletisim','textarea');

-- ====================================================
-- v1.0.4 - Eksik kolonlar (ALTER idempotent)
-- ====================================================

-- iletisim_mesajlari'na user_agent kolonu ekle (MariaDB 10+ IF NOT EXISTS)
ALTER TABLE `mz_iletisim_mesajlari` ADD COLUMN IF NOT EXISTS `user_agent` VARCHAR(255) DEFAULT NULL AFTER `ip_adresi`;

-- ====================================================
-- v1.0.5 - Ek bug fix kolonlari
-- ====================================================
ALTER TABLE `mz_urunler`        ADD COLUMN IF NOT EXISTS `parent_id` INT UNSIGNED DEFAULT NULL AFTER `id`;
ALTER TABLE `mz_urunler`        ADD KEY    IF NOT EXISTS `idx_parent` (`parent_id`);
ALTER TABLE `mz_teklif_notlari` ADD COLUMN IF NOT EXISTS `baslik`     VARCHAR(160) DEFAULT NULL AFTER `tip`;
ALTER TABLE `mz_hatirlatma_log` ADD COLUMN IF NOT EXISTS `olusturma_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- ====================================================
-- v1.0.8 - Hakkimizda zengin icerik + 30+ SSS + Temsilcimiz Olun ipuclari
-- ====================================================

-- Hakkimizda CMS sayfasi: Mizan Sigorta'ya ozel zengin icerik
UPDATE `mz_sayfalar` SET
  `baslik` = 'Kurumsal',
  `icerik` = '<div class="mz-prose">
<h2>Güven ve Özen İle Yanınızdayız</h2>
<p class="lead">Mizan Sigorta Aracılık Hizmetleri olarak, sigortacılığı bir ürün satışı olarak değil, müşterilerimizin geleceğini güvence altına alma sorumluluğu olarak görüyoruz.</p>

<p>Mizan adı; <strong>denge, adalet ve ölçü</strong> anlamına gelir. Markamız bu felsefeyi sigortacılık anlayışımızın merkezine yerleştirmiştir: müşterimizin ihtiyacı ile sunulan teminat arasında, prim ile koruma kapsamı arasında, beklenti ile gerçek arasında dengeyi kurmak.</p>

<h3>Misyonumuz</h3>
<p>Müşterilerimizin risklerini anlayarak, ihtiyaçlarına en uygun ve en ekonomik sigorta çözümlerini sunmak. Hasar veya tazminat sürecinde profesyonel rehberlik yaparak hak ve menfaatlerini korumak. Sigorta şirketi ile sigortalı arasında güvenilir bir köprü olmak ve uzun soluklu ilişkiler kurmak.</p>

<h3>Vizyonumuz</h3>
<p>Türkiye genelinde yaygın hizmet ağıyla, kaliteli hizmetiyle tercih edilen, güvenilen ve referans gösterilen bir sigorta aracılık kurumu olmak. Dijital dönüşümü takip ederek müşterilerimize her an, her yerden ulaşılabilir bir deneyim sunmak.</p>

<h3>Değerlerimiz</h3>
<ul>
  <li><strong>Güven:</strong> Her müşteri ilişkimizin temelinde dürüstlük ve şeffaflık vardır.</li>
  <li><strong>Özen:</strong> Her poliçe, her hasar dosyası, her görüşme aynı titizlikle ele alınır.</li>
  <li><strong>Uzmanlık:</strong> Sigorta mevzuatı ve ürün gelişmelerini sürekli takip eden uzman kadro.</li>
  <li><strong>Adalet:</strong> Müşterimizin haklarını koruyarak sigorta şirketleri ile dengeyi sağlamak.</li>
  <li><strong>Süreklilik:</strong> Tek seferlik satış değil, hayatın her aşamasında yanınızda olmak.</li>
</ul>

<h3>Hizmet Kapsamımız</h3>
<p>9 ana kategoride 35''ten fazla sigorta ürünü ile bireysel ve kurumsal her ihtiyaca özel çözüm sunuyoruz:</p>
<ul>
  <li><strong>Oto Sigortaları:</strong> Kasko, Trafik, İMM, Yeşilkart</li>
  <li><strong>Yangın Poliçeleri:</strong> Konut, İşyeri, DASK, Ortak Alan</li>
  <li><strong>Sağlık Sigortaları:</strong> Özel Sağlık, Tamamlayıcı Sağlık, Yurt Dışı Seyahat</li>
  <li><strong>All Riskler:</strong> İnşaat, Montaj, Makine Kırılması, Elektronik Cihaz</li>
  <li><strong>Nakliyat:</strong> Tekne, Yat, Emtea, Taşıyıcı Sorumluluk</li>
  <li><strong>Sorumluluk Sigortaları:</strong> İşveren, Üçüncü Şahıs, Mesleki, Tehlikeli Maddeler</li>
  <li><strong>TARSİM:</strong> Bitkisel Ürün, Sera, Hayvancılık, Arıcılık</li>
  <li><strong>Ferdi Kaza:</strong> Bireysel Kaza, Deprem Destek, Kritik Hastalıklar</li>
  <li><strong>Kefalet Sigortaları:</strong> Kefalet Senedi, KDV İadesi, Devlet Destekli Alacak</li>
</ul>

<h3>Şubelerimiz</h3>
<p>İstanbul Genel Merkezimiz Ataşehir''de yer almaktadır. Konya, Ankara ve Aksaray illerinde de hizmet noktalarımız bulunmaktadır. Her şubemizde aynı kalite standardı ve aynı müşteri odaklı yaklaşım ile hizmet veriyoruz.</p>

<h3>Anlaşmalı Sigorta Şirketleri</h3>
<p>Türkiye''nin önde gelen 12''den fazla sigorta şirketi ile anlaşmalı olarak çalışıyoruz. Bu sayede her müşterimiz için piyasadaki en avantajlı teminat-prim dengesini kurabiliyoruz.</p>

<div class="alert alert-warning mt-4">
  <h5 class="fw-bold">Neden Aracı Acente Üzerinden Sigorta?</h5>
  <p class="mb-0">Doğrudan sigorta şirketinden poliçe yapmak yerine bir acente ile çalışmak; teklif karşılaştırması, hasar süreci desteği, mevzuat bilgisi ve uzun vadeli müşteri ilişkisi açısından önemli avantajlar sağlar. Hasarın yaşandığı kritik anda yalnız değil, yanınızda bir uzman bulursunuz.</p>
</div>
</div>'
WHERE `slug` = 'hakkimizda';

-- ===== 30+ SSS sorusu =====

-- Once mevcut basit SSS'leri silelim (yenilerini ekleyecegiz)
DELETE FROM `mz_sss` WHERE `kategori` IN ('Genel', 'Teklif', 'Hasar') AND `sira` < 100;

INSERT IGNORE INTO `mz_sss` (`kategori`,`soru`,`cevap`,`sira`,`aktif`) VALUES

-- ====== Genel ======
('Genel','Mizan Sigorta hangi sigorta şirketleri ile çalışıyor?','Türkiye''nin önde gelen 12''den fazla sigorta şirketi ile anlaşmalı çalışıyoruz. Anadolu Sigorta, Allianz, AXA, HDI, Türkiye Sigorta, Quick Sigorta, Neova, Ak Sigorta, Doğa Sigorta gibi köklü şirketlerle çalışarak size en uygun teminatları karşılaştırıyoruz.',1,1),
('Genel','Acente üzerinden sigorta yaptırmak daha mı pahalı?','Hayır, tam tersi. Aracı acenteler aynı poliçeyi sigorta şirketinin doğrudan kendisiyle yapacağınız fiyatla sunar; üzerinde bir komisyon yoktur. Ancak hasar sürecinde uzman destek, çoklu teklif karşılaştırması ve mevzuat danışmanlığı gibi büyük avantajlar sağlar.',2,1),
('Genel','Online teklif almak ücretli mi, beni bağlar mı?','Hayır, web sitemizden veya telefon ile alınan tüm teklifler tamamen ücretsizdir ve sizi hiçbir şekilde bağlamaz. Karar tamamen size aittir.',3,1),
('Genel','Sigortaymı yaptırdıktan sonra şirket değiştirmem mümkün mü?','Evet. Yenileme döneminde dilediğiniz şirkete geçebilirsiniz. Bizimle çalıştığınızda size yenileme öncesi otomatik karşılaştırma yapıyoruz, en uygun teklifi sunuyoruz.',4,1),
('Genel','Mizan Sigorta nerelerde hizmet veriyor?','Genel Merkezimiz İstanbul Ataşehir''dedir. Ek olarak Konya (Karatay), Ankara ve Aksaray''da hizmet noktalarımız bulunmaktadır. Türkiye genelinde online ve telefon ile hizmet vermekteyiz.',5,1),

-- ====== Teklif ======
('Teklif','Teklif almak için hangi bilgiler gerekiyor?','Sigorta türüne göre değişir. Trafik/kasko için araç plakası ve ruhsat sahibi TC kimlik numarası; konut/DASK için adres bilgileri; sağlık için yaş ve TC kimlik numarası yeterlidir.',10,1),
('Teklif','Aldığım teklifin geçerlilik süresi ne kadar?','Sigorta şirketleri tarafından üretilen teklifler genellikle 7-15 gün arasında geçerlidir. Bazı poliçelerde fiyat günlük değişebileceği için en güncel fiyat için bizi arayın.',11,1),
('Teklif','Birden fazla sigorta şirketinden teklif alabilir miyim?','Evet, bizim asıl işimiz bu. Anlaşmalı 12+ şirketten aynı anda teklif çekip size karşılaştırmalı sunarız. Hangi şirketin hangi teminatı, hangi fiyata verdiği şeffafça görüşülür.',12,1),
('Teklif','Hızlı teklif sihirbazı nasıl çalışıyor?','Sayfanın sağ üstündeki "Hızlı Teklif" butonuna tıklayın. 3 adımda (sigorta türü → bilgileriniz → iletişim) teklif talebi gönderebilirsiniz. Yetkilimiz size kısa sürede dönüş yapacak.',13,1),

-- ====== Kasko ======
('Kasko','Kasko zorunlu mu?','Hayır, kasko isteğe bağlıdır. Ancak özellikle yeni veya değerli araçlar için kesinlikle önerilir; kendi aracınızda oluşan zararlar (kaza, hırsızlık, yangın, doğal afet vb.) kasko ile karşılanır.',20,1),
('Kasko','Kasko ile trafik sigortası arasındaki fark nedir?','Trafik sigortası zorunludur ve sadece <strong>karşı tarafın</strong> hasarını karşılar. Kasko ise isteğe bağlıdır ve <strong>kendi aracınızı</strong> hırsızlık, yangın, deprem, kaza gibi risklere karşı korur.',21,1),
('Kasko','Kasko fiyatı nasıl belirlenir?','Aracın değeri, modeli, yaşı, kullanım amacı, sürücünün yaşı ve hasar geçmişi gibi faktörler etkiler. Hasarsızlık indirimi (%0-65) ile prim önemli ölçüde düşürülebilir.',22,1),
('Kasko','İkame araç teminatı nedir?','Kasko poliçenize ek olarak alınabilen bu teminat, aracınız hasarda olduğu sürece (genellikle 7-15 gün) size kiralık araç sağlanmasını ifade eder.',23,1),
('Kasko','Cam hasarımda mini onarım indirimi nasıl çalışır?','Mini onarım, aracın küçük çiziklerinin kasko hasarsızlık indirimi bozulmadan onarılmasıdır. Yılda 1-2 kez kullanılabilir, hasarsızlık derecenizi etkilemez.',24,1),

-- ====== Trafik ======
('Trafik','Trafik sigortası yaptırmazsam ne olur?','2918 sayılı Kanun gereği zorunludur. Yaptırmazsanız ceza yer, aracınız trafikten men edilir ve kaza halinde tüm zarar size kalır.',30,1),
('Trafik','Hasarsızlık indirimi nasıl çalışır?','İlk poliçede 4. basamaktan başlarsınız. Her hasarsız yıl bir basamak yukarı çıkarsınız (8. basamak en yüksek indirim, %50). Hasar olursa basamağınız düşer.',31,1),
('Trafik','Aracımı sattığımda trafik sigortası ne olur?','Mülkiyet değişiklik tarihinden 10 gün sonra otomatik iptal olur. Kullanılmayan prim gün esasına göre size iade edilir.',32,1),
('Trafik','Trafik sigortası karşı tarafın hasarını ne kadar karşılar?','Hazine ve Maliye Bakanlığı''nın belirlediği maddi ve bedeni teminat limitleri çerçevesinde karşılar. Limit üstü zarar için ek olarak İMM (İhtiyari Mali Mesuliyet) yaptırılması önerilir.',33,1),

-- ====== DASK ======
('DASK','DASK kimler için zorunlu?','Tapuya kayıtlı tüm konutlar için zorunludur. DASK''sız elektrik, su, doğalgaz aboneliği açılamaz; konut kredisi kullanılamaz; tapu işlemi yapılamaz.',40,1),
('DASK','DASK primleri nasıl hesaplanır?','Yapı tarzı (2 tip) ve deprem bölgesi (7 risk bölgesi) baz alınarak hesaplanır. Toplam 14 farklı fiyat seçeneği vardır. Her yıl yenilenir.',41,1),
('DASK','DASK ile konut sigortası arasındaki fark nedir?','DASK sadece <strong>deprem ve deprem kaynaklı hasarlarda binayı</strong> teminat altına alır. Konut sigortası ise yangın, hırsızlık, sel, ev eşyaları gibi geniş riskleri kapsar. İkisini birlikte yaptırmak idealdir.',42,1),
('DASK','Kiracı DASK yaptırmak zorunda mı?','Hayır, DASK ev sahibinin sorumluluğundadır. Ancak kiracılar kendi eşyalarını korumak için <strong>konut sigortası</strong> yaptırabilirler.',43,1),

-- ====== Konut ======
('Konut','Konut sigortası neleri kapsar?','Yangın, hırsızlık, sel, su baskını, fırtına, deprem (DASK üstü ek), patlama, vandalizm gibi geniş risklere karşı binayı ve içindeki eşyaları teminat altına alır.',50,1),
('Konut','Kiracıyım, konut sigortası yaptırabilir miyim?','Evet. Sadece eşyalarınızı (mobilya, beyaz eşya, elektronik vb.) sigortalatabilirsiniz. Bina ev sahibinin sorumluluğundadır.',51,1),
('Konut','Konut sigortasında eksik sigorta ne demek?','Eviniz ve eşyalarınızın gerçek değerinden düşük tutarda sigortalanması durumudur. Hasar halinde tazminat oranlı ödenir. Doğru bedeli belirlemek için eksperimizden destek alabilirsiniz.',52,1),

-- ====== Sağlık ======
('Sağlık','Özel sağlık ile tamamlayıcı sağlık farkı nedir?','<strong>Tamamlayıcı sağlık (TSS)</strong> SGK üzerine eklenir, daha uygundur (300-800 TL/ay), sadece SGK anlaşmalı özel hastanelerde geçerlidir. <strong>Özel sağlık</strong> (1.500-4.000 TL/ay) tamamen bağımsızdır, daha geniş kapsamlıdır.',60,1),
('Sağlık','Tamamlayıcı sağlık sigortasından kimler yararlanabilir?','SGK''lı (4A, 4B, 4C) tüm vatandaşlar yararlanabilir. SGK''sı olmayanlar tamamlayıcı sağlık yapamaz, özel sağlık yaptırmalıdır.',61,1),
('Sağlık','Mevcut hastalıklarım sağlık sigortası tarafından karşılanır mı?','Genellikle hayır. Poliçe yapımı sırasında bilinen hastalıklar (önceden var olan rahatsızlıklar) kapsam dışı bırakılabilir. Beyan etmek önemlidir, aksi halde tazminat ödenmeyebilir.',62,1),
('Sağlık','Yurt dışı seyahat sağlık sigortası ne kadar süreli?','Seyahat süreniz boyunca geçerlidir. Schengen vizesi için minimum 30.000 EUR teminat ve seyahat tarihlerini kapsayan süre gereklidir.',63,1),

-- ====== Hasar ======
('Hasar','Hasar olduğunda ne yapmalıyım?','Hemen <strong>112''yi arayın</strong> (yaralı varsa), kazaya tutanak tutturun veya kaza tespit tutanağı düzenleyin, fotoğraf çekin ve hemen bizi arayın. Süreç boyunca size eşlik ederiz.',70,1),
('Hasar','Hasar bildirimi için ne kadar süre var?','Genellikle 5 iş günü içinde bildirim yapmanız gerekir. Geç bildirim hak kaybına neden olabilir.',71,1),
('Hasar','Hasar tazminatı ne kadar sürede ödenir?','Tüm belgelerin tamamlanmasını takiben ortalama 7-15 iş günü içinde ödeme yapılır. Karmaşık dosyalarda eksper ataması gerekebilir.',72,1),
('Hasar','Hasarımı online takip edebilir miyim?','Evet. Sitemizdeki "Hasar İhbarı" sayfasından dosya numaranızla takip edebilir veya bizi arayarak güncel durumu öğrenebilirsiniz.',73,1),

-- ====== İşyeri / Kurumsal ======
('İşyeri','İşyeri sigortası neleri kapsar?','Yangın, hırsızlık, sel, fırtına, deprem (ek teminat), camlar, makine, mali sorumluluk, iş durması gibi geniş bir teminat yelpazesi sunar.',80,1),
('İşyeri','İşveren mali sorumluluk sigortası zorunlu mu?','Bazı sektörlerde (inşaat, tehlikeli işler) zorunludur. Tüm işverenler için yasal sorumluluğa karşı koruma sağladığından önemle önerilir.',81,1),
('İşyeri','Mesleki sorumluluk sigortası kimlere uygundur?','Avukat, mali müşavir, doktor, mühendis, eczacı gibi mesleki hizmet sunan kişilere uygundur. Yaptıkları iş nedeniyle 3. kişilere verilebilecek zararı teminat altına alır.',82,1),

-- ====== Kefalet ======
('Kefalet','Kefalet sigortası nedir, ne işe yarar?','İhale, taahhüt veya alacak süreçlerinde teminat mektubu yerine geçen sigortadır. Bankaya yüksek bloke koymadan kefalet sağlar.',90,1),
('Kefalet','KDV iadesi sigortası nedir?','İhracatçıların KDV iade taleplerinde, vergi dairesinin nakit teminat istemesi yerine kabul ettiği sigorta poliçesidir. Nakit akışını rahatlatır.',91,1);

-- TAMAMLANDI

-- ====================================================
-- v1.1.1 - Icon prefix fix (eski seed'lerdeki bi- prefix eksiklikleri)
-- ====================================================

-- Eski seed'lerde icon'lar 'car-front' olarak yazilmisti, bi- prefix eksik
-- Bootstrap Icons class'i 'bi bi-car-front' olmali, bu yuzden DB'de de 'bi-car-front' tutulmali
UPDATE `mz_urunler` SET `icon` = CONCAT('bi-', `icon`)
  WHERE `icon` IS NOT NULL
    AND `icon` != ''
    AND `icon` NOT LIKE 'bi-%';

-- 9 ana kategori icin icon ve aciklama duzenlemesi (bilinmeyenler icin guncel)
UPDATE `mz_urunler` SET `icon` = 'bi-car-front-fill' WHERE `slug` = 'oto-sigortalari';
UPDATE `mz_urunler` SET `icon` = 'bi-fire'           WHERE `slug` = 'yangin-policeleri';
UPDATE `mz_urunler` SET `icon` = 'bi-heart-pulse-fill' WHERE `slug` = 'saglik-sigortalari';
UPDATE `mz_urunler` SET `icon` = 'bi-shield-shaded' WHERE `slug` = 'all-riskler';
UPDATE `mz_urunler` SET `icon` = 'bi-truck'         WHERE `slug` = 'nakliyat';
UPDATE `mz_urunler` SET `icon` = 'bi-people-fill'   WHERE `slug` = 'sorumluluk-sigortalari';
UPDATE `mz_urunler` SET `icon` = 'bi-tree-fill'     WHERE `slug` = 'tarsim';
UPDATE `mz_urunler` SET `icon` = 'bi-bandaid'       WHERE `slug` = 'ferdi-kaza';
UPDATE `mz_urunler` SET `icon` = 'bi-bank'          WHERE `slug` = 'kefalet-sigortalari';

-- Eski seed'deki kategoriler de duzenlensin (eger varsa)
UPDATE `mz_urunler` SET `icon` = 'bi-car-front-fill' WHERE `slug` = 'kasko-sigortasi';
UPDATE `mz_urunler` SET `icon` = 'bi-traffic-cone'   WHERE `slug` = 'trafik-sigortasi';
UPDATE `mz_urunler` SET `icon` = 'bi-house-fill'     WHERE `slug` = 'konut-sigortasi';
UPDATE `mz_urunler` SET `icon` = 'bi-building'       WHERE `slug` = 'isyeri-sigortasi';
UPDATE `mz_urunler` SET `icon` = 'bi-heart-pulse-fill' WHERE `slug` = 'saglik-sigortasi';
UPDATE `mz_urunler` SET `icon` = 'bi-shield-check'   WHERE `slug` = 'hayat-sigortasi';
