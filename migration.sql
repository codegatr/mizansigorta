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

-- Default superadmin: destek@mizansigorta.com.tr / Mizan2026!  (sifirlamak icin admin/login.php)
INSERT IGNORE INTO `mz_kullanicilar` (`id`,`ad_soyad`,`email`,`sifre_hash`,`rol`,`aktif`)
VALUES (1,'Sistem Yoneticisi','destek@mizansigorta.com.tr','$2y$10$2sYcHu1xxtmPuRzL.jFXLe0HRioz2PJe8E5lh38L3AzjON8LgpqkC','superadmin',1);
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
('email','destek@mizansigorta.com.tr','İletişim e-postası','iletisim','email'),
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


-- 5) Alt urunler (parent slug'a referans verir)
-- Oto Sigortalari
INSERT IGNORE INTO `mz_urunler` (`slug`,`baslik`,`kisa_aciklama`,`icon`,`sira`,`aktif`,`parent_id`) VALUES
('kasko',                          'Kasko',                                  'Kendi aracınızı kapsamlı korur',                'bi-car-front', 11, 1, NULL),
('trafik-zorunlu-sorumluluk',      'Karayolları Zorunlu Sorumluluk (Trafik)','Yasal zorunlu, üçüncü şahıs sorumluluk',        'bi-shield',    12, 1, NULL),
('ihtiyari-mali-mesuliyet-imm',    'İhtiyari Mali Mesuliyet (İMM)',          'Trafik limit üstü ek sorumluluk teminatı',      'bi-shield-plus',13,1, NULL),
('yesilkart',                      'Yeşilkart',                              'Yurtdışı sınır ötesi araç sorumluluk',          'bi-passport',  14, 1, NULL);

-- Yangin Policeleri
INSERT IGNORE INTO `mz_urunler` (`slug`,`baslik`,`kisa_aciklama`,`icon`,`sira`,`aktif`,`parent_id`) VALUES
('isyeri-yangin-sigortasi',        'İşyeri Yangın Sigortası',                'Ticari mekanlar için yangın güvencesi',         'bi-shop',      21, 1, NULL),
('konut-sigortasi',                'Konut Sigortası',                        'Eviniz için kapsamlı paket',                    'bi-house-fill',22, 1, NULL),
('dask',                           'DASK Zorunlu Deprem',                    'Yasal zorunlu deprem güvencesi',                'bi-buildings', 23, 1, NULL),
('ortak-alan-sigortasi',           'Ortak Alan Sigortası',                   'Apartman/site ortak alanları',                  'bi-building',  24, 1, NULL);

-- Saglik
INSERT IGNORE INTO `mz_urunler` (`slug`,`baslik`,`kisa_aciklama`,`icon`,`sira`,`aktif`,`parent_id`) VALUES
('yurt-disi-seyahat-saglik',       'Yurt Dışı Seyahat Sağlık',               'Yurt dışı yolculukta sağlık güvencesi',         'bi-airplane',  31, 1, NULL),
('tamamlayici-saglik-sigortasi',   'Tamamlayıcı Sağlık Sigortası',           'SGK üzerine tamamlayıcı paket',                 'bi-plus-square',32, 1, NULL),
('ozel-saglik-sigortasi',          'Özel Sağlık Sigortası',                  'Özel hastane ve nitelikli tedavi',              'bi-hospital',  33, 1, NULL);

-- All Riskler
INSERT IGNORE INTO `mz_urunler` (`slug`,`baslik`,`kisa_aciklama`,`icon`,`sira`,`aktif`,`parent_id`) VALUES
('insaat-all-risk',                'İnşaat All Risk',                        'Şantiye ve inşaat süreci güvencesi',            'bi-cone-striped',41,1, NULL),
('montaj-all-risk',                'Montaj All Risk',                        'Mühendislik ve makine montaj sigortası',        'bi-gear-wide-connected',42,1, NULL),
('makine-kirilmasi',               'Makine Kırılması',                       'Üretim hattı makine arızası teminatı',          'bi-wrench-adjustable',43,1, NULL),
('elektronik-cihaz',               'Elektronik Cihaz Sigortası',             'Bilişim ve elektronik ekipman güvencesi',       'bi-pc-display',44, 1, NULL);

-- Nakliyat
INSERT IGNORE INTO `mz_urunler` (`slug`,`baslik`,`kisa_aciklama`,`icon`,`sira`,`aktif`,`parent_id`) VALUES
('tekne-hull',                     'Tekne (Hull)',                           'Gemi/tekne gövde sigortası',                    'bi-bookmark-star',51, 1, NULL),
('yat-sigortasi',                  'Yat Sigortası',                          'Özel/ticari yat güvencesi',                     'bi-tsunami',   52, 1, NULL),
('nakliyat-emtea-abonman',         'Nakliyat Emtea ve Abonman',              'Yıllık abonman emtea taşıma sigortası',         'bi-box-seam',  53, 1, NULL),
('tasiyici-sorumluluk',            'Taşıyıcı Sorumluluk',                    'Lojistik firma yük sorumluluk teminatı',        'bi-truck-front',54, 1, NULL),
('tekne-insaat-sigortasi',         'Tekne İnşaat Sigortası',                 'Tersane inşaat süreci güvencesi',               'bi-hammer',    55, 1, NULL);

-- Sorumluluk
INSERT IGNORE INTO `mz_urunler` (`slug`,`baslik`,`kisa_aciklama`,`icon`,`sira`,`aktif`,`parent_id`) VALUES
('isveren-mali-sorumluluk',        'İşveren Mali Sorumluluk',                'İşveren-çalışan iş kazası sorumluluğu',         'bi-people',    61, 1, NULL),
('ucuncu-sahis-sorumluluk',        'Üçüncü Şahıs Sorumluluk',                'Üçüncü taraflara verilen zarar teminatı',       'bi-person-x',  62, 1, NULL),
('tehlikeli-maddeler-sorumluluk',  'Tehlikeli Maddeler Zorunlu Sorumluluk',  'Tehlikeli madde işletmeleri için',              'bi-exclamation-diamond',63,1,NULL),
('ozel-guvenlik-mali-sorumluluk',  'Özel Güvenlik Mali Sorumluluk',          'Güvenlik şirketleri için yasal teminat',        'bi-shield-lock',64,1, NULL),
('mesleki-sorumluluk',             'Mesleki Sorumluluk',                     'Avukat/mali müşavir/doktor mesleki teminatı',   'bi-briefcase', 65, 1, NULL);

-- TARSIM
INSERT IGNORE INTO `mz_urunler` (`slug`,`baslik`,`kisa_aciklama`,`icon`,`sira`,`aktif`,`parent_id`) VALUES
('bitkisel-urun',                  'Bitkisel Ürün Sigortası',                'Tarımsal ürün hasar güvencesi',                 'bi-tree',      71, 1, NULL),
('sera-sigortasi',                 'Sera Sigortası',                         'Sera yapı ve içindeki ürünler',                 'bi-house-heart',72,1, NULL),
('kucukbas-hayvan-hayat',          'Küçükbaş Hayvan Hayat Sigortası',        'Koyun/keçi hayat güvencesi',                    'bi-piggy-bank',73, 1, NULL),
('buyukbas-hayvan-hayat',          'Büyükbaş Hayvan Hayat Sigortası',        'Sığır hayat güvencesi',                         'bi-piggy-bank-fill',74,1, NULL),
('kumes-hayvanlari-hayat',         'Kümes Hayvanları Hayat Sigortası',       'Kanatlı hayvancılık güvencesi',                 'bi-egg',       75, 1, NULL),
('su-urunleri-sigortasi',          'Su Ürünleri Sigortası',                  'Su ürünleri yetiştiriciliği',                   'bi-droplet-half',76,1, NULL),
('aricilik-sigortasi',             'Arıcılık Sigortası',                     'Arı kovanları ve bal üretimi',                  'bi-bug',       77, 1, NULL);

-- Ferdi Kaza
INSERT IGNORE INTO `mz_urunler` (`slug`,`baslik`,`kisa_aciklama`,`icon`,`sira`,`aktif`,`parent_id`) VALUES
('ferdi-kaza-sigortasi',           'Ferdi Kaza Sigortası',                   'Bireysel kaza koruması',                        'bi-bandaid',   81, 1, NULL),
('deprem-destek-sigortasi',        'Deprem Destek Sigortası',                'DASK üzerine ek destek paketi',                 'bi-activity',  82, 1, NULL),
('kritik-hastaliklar',             'Kritik Hastalıklar',                     'Kanser, kalp gibi kritik hastalık paketi',      'bi-heart',     83, 1, NULL);
-- ====================================================
-- Alt urunlerin parent_id'lerini topluca ata (PDO-safe)
-- Daha once SET @parent_xxx := SELECT pattern'i vardi, PDO unbuffered
-- hatasi veriyordu. Simdi UPDATE...JOIN pattern kullaniliyor.
-- ====================================================
UPDATE `mz_urunler` u INNER JOIN `mz_urunler` p ON p.slug = 'oto-sigortalari' SET u.parent_id = p.id WHERE u.slug IN ('kasko','trafik-zorunlu-sorumluluk','ihtiyari-mali-mesuliyet-imm','yesilkart');
UPDATE `mz_urunler` u INNER JOIN `mz_urunler` p ON p.slug = 'yangin-policeleri' SET u.parent_id = p.id WHERE u.slug IN ('isyeri-yangin-sigortasi','konut-sigortasi','dask','ortak-alan-sigortasi');
UPDATE `mz_urunler` u INNER JOIN `mz_urunler` p ON p.slug = 'saglik-sigortalari' SET u.parent_id = p.id WHERE u.slug IN ('yurt-disi-seyahat-saglik','tamamlayici-saglik-sigortasi','ozel-saglik-sigortasi');
UPDATE `mz_urunler` u INNER JOIN `mz_urunler` p ON p.slug = 'all-riskler' SET u.parent_id = p.id WHERE u.slug IN ('insaat-all-risk','montaj-all-risk','makine-kirilmasi','elektronik-cihaz');
UPDATE `mz_urunler` u INNER JOIN `mz_urunler` p ON p.slug = 'nakliyat' SET u.parent_id = p.id WHERE u.slug IN ('tekne-hull','yat-sigortasi','nakliyat-emtea-abonman','tasiyici-sorumluluk','tekne-insaat-sigortasi');
UPDATE `mz_urunler` u INNER JOIN `mz_urunler` p ON p.slug = 'sorumluluk-sigortalari' SET u.parent_id = p.id WHERE u.slug IN ('isveren-mali-sorumluluk','ucuncu-sahis-sorumluluk','tehlikeli-maddeler-sorumluluk','ozel-guvenlik-mali-sorumluluk','mesleki-sorumluluk');
UPDATE `mz_urunler` u INNER JOIN `mz_urunler` p ON p.slug = 'tarsim' SET u.parent_id = p.id WHERE u.slug IN ('bitkisel-urun','sera-sigortasi','kucukbas-hayvan-hayat','buyukbas-hayvan-hayat','kumes-hayvanlari-hayat','su-urunleri-sigortasi','aricilik-sigortasi');
UPDATE `mz_urunler` u INNER JOIN `mz_urunler` p ON p.slug = 'ferdi-kaza' SET u.parent_id = p.id WHERE u.slug IN ('ferdi-kaza-sigortasi','deprem-destek-sigortasi','kritik-hastaliklar');


-- Kefalet
INSERT IGNORE INTO `mz_urunler` (`slug`,`baslik`,`kisa_aciklama`,`icon`,`sira`,`aktif`,`parent_id`) VALUES
('kefalet-senedi',                 'Kefalet Senedi',                         'İhale ve kefalet teminatı senedi',              'bi-file-earmark-ruled',91,1, NULL),
('kdv-iadesi',                     'KDV İadesi',                             'KDV iade kefalet sigortası',                    'bi-cash-coin', 92, 1, NULL),
('devlet-destekli-alacak-sigorta', 'Devlet Destekli Alacak Sigorta',         'KOBİ alacak güvencesi',                         'bi-bank',      93, 1, NULL),
('bina-tamamlama-sigortasi',       'Bina Tamamlama Sigortası',               'Müteahhit-tüketici tamamlama teminatı',         'bi-building-add',94,1, NULL),
('lisansli-depoculuk',             'Lisanslı Depoculuk',                     'Tarımsal lisanslı depo güvencesi',              'bi-archive',   95, 1, NULL);

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

-- v1.1.2 - EKSIK SIRA KOLONLARI (CRITICAL: bunlar eksikti, butun seed'ler fail oluyordu)
ALTER TABLE `mz_urunler`  ADD COLUMN IF NOT EXISTS `sira`         INT UNSIGNED NOT NULL DEFAULT 0 AFTER `aciklama`;
ALTER TABLE `mz_sss`      ADD COLUMN IF NOT EXISTS `sira`         INT UNSIGNED NOT NULL DEFAULT 0 AFTER `cevap`;
ALTER TABLE `mz_sayfalar` ADD COLUMN IF NOT EXISTS `menu_sirasi`  INT UNSIGNED NOT NULL DEFAULT 99 AFTER `menude_goster`;
ALTER TABLE `mz_blog`     ADD COLUMN IF NOT EXISTS `goruntulenme` INT UNSIGNED NOT NULL DEFAULT 0;

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

-- ====================================================
-- v1.1.2 - HUKUKI SAYFALAR (KVKK, Cerez, Gizlilik, Kullanim Sartlari)
-- Profesyonel mevzuat-uyumlu icerikler
-- ====================================================

-- Mevcut/eksik hukuki sayfalari INSERT IGNORE ile ekle (zaten varsa skip)
INSERT IGNORE INTO `mz_sayfalar` (`slug`, `baslik`, `icerik`, `seo_baslik`, `seo_aciklama`, `menude_goster`, `menu_sirasi`, `aktif`, `sistem`) VALUES
('kvkk', 'KVKK Aydınlatma Metni', '<div class="mz-prose">
<p class="lead">İşbu Aydınlatma Metni, 6698 sayılı Kişisel Verilerin Korunması Kanunu (&ldquo;KVKK&rdquo;) kapsamında, veri sorumlusu sıfatıyla <strong>Mizan Sigorta Aracılık Hizmetleri</strong> tarafından kişisel verilerinizin işlenmesine ilişkin esasları açıklamak amacıyla hazırlanmıştır.</p>

<h3>1. Veri Sorumlusunun Kimliği</h3>
<p>
<strong>Veri Sorumlusu:</strong> Mizan Sigorta Aracılık Hizmetleri<br>
<strong>Adres:</strong> Fetih Mah. Libadiye Cad. Tahralı Sok. Kavakyeli İş Merkezi D-Blok K:9 D:24 ATAŞEHİR / İSTANBUL<br>
<strong>E-posta:</strong> destek@mizansigorta.com.tr<br>
<strong>Telefon:</strong> +90 332 000 00 00
</p>

<h3>2. İşlenen Kişisel Veriler ve Toplama Yöntemi</h3>
<p>Mizan Sigorta, sigorta aracılık hizmetlerinin sunulması, müşteri ilişkilerinin yönetimi ve yasal yükümlülüklerin yerine getirilmesi amacıyla aşağıdaki kişisel verilerinizi işleyebilir:</p>
<ul>
<li><strong>Kimlik Bilgileri:</strong> Ad, soyad, T.C. kimlik numarası, doğum tarihi, cinsiyet</li>
<li><strong>İletişim Bilgileri:</strong> Telefon, e-posta, ikametgâh adresi</li>
<li><strong>Müşteri İşlem Bilgileri:</strong> Sigorta talepleriniz, poliçe bilgileriniz, hasar dosyalarınız</li>
<li><strong>Finansal Bilgiler:</strong> Ödeme bilgileriniz, prim ödemeleriniz</li>
<li><strong>Görsel/İşitsel Kayıtlar:</strong> Çağrı merkezi ses kayıtları (kalite ve güvenlik amacıyla)</li>
<li><strong>İşlem Güvenliği:</strong> IP adresi, çerez bilgileri, oturum bilgileri</li>
<li><strong>Araç Bilgileri:</strong> Plaka, marka, model, ruhsat bilgileri (oto sigortaları için)</li>
<li><strong>Sağlık Bilgileri (Özel Nitelikli):</strong> Sağlık sigortası kapsamında, açık rızanızla</li>
</ul>

<p>Bu veriler; web sitemiz üzerinden doldurduğunuz formlar, telefon görüşmelerimiz, e-posta yazışmaları, fiziki olarak iletilen belgeler veya yetkili sigorta şirketleri aracılığıyla toplanmaktadır.</p>

<h3>3. Kişisel Verilerin İşlenme Amaçları</h3>
<p>Kişisel verileriniz, KVKK&rsquo;nın 5. ve 6. maddelerinde belirtilen kişisel veri işleme şartları çerçevesinde aşağıdaki amaçlarla işlenmektedir:</p>
<ul>
<li>Sigorta teklifi hazırlanması ve sunulması</li>
<li>Sigorta poliçesi düzenleme ve aracılık faaliyetlerinin yürütülmesi</li>
<li>Müşteri kayıtlarının oluşturulması ve müşteri ilişkilerinin yönetimi</li>
<li>Hasar süreçlerinin takibi ve yönetimi</li>
<li>Yenileme dönemlerinde poliçe yenileme bildirimleri</li>
<li>Müşteri memnuniyetinin ölçülmesi ve iyileştirme çalışmaları</li>
<li>Yasal yükümlülüklerin yerine getirilmesi (SBM, MASAK, Hazine ve Maliye Bakanlığı vb.)</li>
<li>Bilgi güvenliği süreçlerinin yürütülmesi</li>
<li>İletişim faaliyetlerinin yürütülmesi (yalnızca açık rızanız varsa pazarlama amaçlı)</li>
</ul>

<h3>4. İşlenen Kişisel Verilerin Aktarımı</h3>
<p>Kişisel verileriniz, KVKK&rsquo;nın 8. ve 9. maddelerine uygun şekilde, aşağıdaki taraflara aktarılabilir:</p>
<ul>
<li><strong>Anlaşmalı Sigorta Şirketleri:</strong> Anadolu Sigorta, Allianz, AXA, Türkiye Sigorta, HDI, Quick Sigorta, Neova, Ak Sigorta, Doğa Sigorta vb. (poliçe düzenlenmesi amacıyla)</li>
<li><strong>Sigorta Bilgi ve Gözetim Merkezi (SBM):</strong> Yasal yükümlülük gereği</li>
<li><strong>Hazine ve Maliye Bakanlığı:</strong> Mevzuat gereği</li>
<li><strong>Yetkili Kamu Kurumları ve Yargı Mercileri:</strong> Yasal talep halinde</li>
<li><strong>Hizmet Aldığımız Tedarikçiler:</strong> IT altyapısı, hosting, e-posta gönderim hizmetleri</li>
<li><strong>Hukuk Müşavirleri ve Bağımsız Denetçiler:</strong> Hukuki süreçlerde gerektiği ölçüde</li>
</ul>

<h3>5. Kişisel Veri İşlemenin Hukuki Sebepleri</h3>
<p>Kişisel verileriniz aşağıdaki hukuki sebeplere dayanılarak işlenmektedir:</p>
<ul>
<li><strong>Kanunlarda açıkça öngörülmesi</strong> (5684 sayılı Sigortacılık Kanunu, KVKK)</li>
<li><strong>Sözleşmenin kurulması veya ifası</strong> (sigorta aracılık sözleşmesi)</li>
<li><strong>Veri sorumlusunun hukuki yükümlülüğünü yerine getirebilmesi</strong></li>
<li><strong>Bir hakkın tesisi, kullanılması veya korunması</strong></li>
<li><strong>Açık rızanız</strong> (özel nitelikli kişisel veriler ve pazarlama amaçlı kullanımlar için)</li>
</ul>

<h3>6. KVKK Kapsamındaki Haklarınız</h3>
<p>KVKK&rsquo;nın 11. maddesi uyarınca, veri sorumlusu olarak Mizan Sigorta&rsquo;ya başvurarak:</p>
<ul>
<li>Kişisel verilerinizin işlenip işlenmediğini öğrenme,</li>
<li>İşlenmişse buna ilişkin bilgi talep etme,</li>
<li>İşlenme amacını ve amacına uygun kullanılıp kullanılmadığını öğrenme,</li>
<li>Yurt içinde veya yurt dışında aktarıldığı üçüncü kişileri bilme,</li>
<li>Eksik veya yanlış işlenmişse düzeltilmesini isteme,</li>
<li>KVKK&rsquo;da öngörülen şartlar çerçevesinde silinmesini veya yok edilmesini isteme,</li>
<li>Düzeltme/silme/yok etme işlemlerinin verilerin aktarıldığı üçüncü kişilere bildirilmesini isteme,</li>
<li>Otomatik sistemlerle analiz edilmesi sonucu aleyhinize bir sonuç çıkmasına itiraz etme,</li>
<li>Kanuna aykırı işleme nedeniyle uğradığınız zararın giderilmesini talep etme</li>
</ul>
<p>haklarına sahipsiniz.</p>

<h3>7. Başvuru Yöntemi</h3>
<p>Yukarıdaki haklarınızı kullanmak için, aşağıdaki yöntemlerden biriyle başvuruda bulunabilirsiniz:</p>
<ul>
<li><strong>E-posta:</strong> destek@mizansigorta.com.tr (güvenli elektronik imzalı)</li>
<li><strong>Posta:</strong> Fetih Mah. Libadiye Cad. Tahralı Sok. Kavakyeli İş Merkezi D-Blok K:9 D:24 ATAŞEHİR / İSTANBUL (ıslak imzalı dilekçe ile)</li>
<li><strong>Noter Kanalıyla:</strong> Yukarıdaki adrese noter aracılığıyla</li>
</ul>
<p>Başvurunuz, talebin niteliğine göre en kısa sürede ve en geç <strong>30 gün içinde</strong> ücretsiz olarak sonuçlandırılacaktır. İşlemin ayrıca bir maliyet gerektirmesi halinde KVKK Kurulu&rsquo;nun belirlediği tarifedeki ücret talep edilebilir.</p>

<h3>8. Veri Saklama Süresi</h3>
<p>Kişisel verileriniz, ilgili mevzuatta öngörülen veya işleme amacının gerektirdiği süreler boyunca saklanır. Saklama süreleri sona erdiğinde verileriniz, KVKK ve ilgili yönetmelikler çerçevesinde silinir, yok edilir veya anonim hale getirilir. Sigortacılık mevzuatı gereği genel saklama süresi <strong>10 yıldır</strong>.</p>

<h3>9. Güncellemeler</h3>
<p>İşbu Aydınlatma Metni, ilgili mevzuat veya iş süreçlerimizdeki değişiklikler doğrultusunda güncellenebilir. Güncel metni daima web sitemizde bulabilirsiniz.</p>

<p class="text-muted small mt-4"><em>Son güncelleme tarihi: 2026</em></p>
</div>', 'KVKK Aydınlatma Metni - Mizan Sigorta', '6698 sayılı Kişisel Verilerin Korunması Kanunu kapsamında aydınlatma metnimiz.', 0, 91, 1, 1),
('gizlilik-politikasi', 'Gizlilik Politikası', '<div class="mz-prose">
<p class="lead">Mizan Sigorta Aracılık Hizmetleri olarak, müşterilerimizin ve web sitemizi ziyaret eden kullanıcılarımızın gizliliğine saygı duyuyoruz. Bu Gizlilik Politikası, kişisel bilgilerinizin nasıl toplandığını, kullanıldığını ve korunduğunu açıklamaktadır.</p>

<h3>1. Toplanan Bilgiler</h3>
<p>Web sitemiz üzerinden veya hizmetlerimiz vesilesiyle aşağıdaki tür bilgileri toplayabiliriz:</p>
<ul>
<li><strong>Doğrudan sağladığınız bilgiler:</strong> İletişim formları, teklif başvuruları, müşteri kayıt formları, hasar bildirim formları üzerinden ilettiğiniz bilgiler.</li>
<li><strong>Otomatik olarak toplanan bilgiler:</strong> IP adresi, tarayıcı türü, ziyaret edilen sayfalar, ziyaret süresi ve çerez verileri.</li>
<li><strong>Üçüncü taraflardan elde edilen bilgiler:</strong> Anlaşmalı sigorta şirketleri ile yürüttüğümüz iş süreçleri kapsamında elde edilen bilgiler.</li>
</ul>

<h3>2. Bilgilerin Kullanımı</h3>
<p>Topladığımız bilgileri yalnızca aşağıdaki amaçlarla kullanırız:</p>
<ul>
<li>Sigorta tekliflerinin hazırlanması ve hizmet sunumu,</li>
<li>Müşteri ilişkilerinin yönetimi,</li>
<li>Hasar süreçlerinin yönetimi,</li>
<li>Yasal ve düzenleyici yükümlülüklerin yerine getirilmesi,</li>
<li>Web sitesi performansının analizi ve iyileştirilmesi,</li>
<li>Açık rızanız varsa pazarlama ve bilgilendirme iletişimleri.</li>
</ul>

<h3>3. Bilgilerin Korunması</h3>
<p>Mizan Sigorta, kişisel bilgilerinizin güvenliğini sağlamak için endüstri standartlarında teknik ve idari tedbirleri uygulamaktadır:</p>
<ul>
<li>SSL şifrelemeli güvenli veri aktarımı (HTTPS),</li>
<li>Erişim kontrolü ve yetkilendirme sistemleri,</li>
<li>Düzenli güvenlik denetimleri,</li>
<li>Veri yedekleme ve felaket kurtarma planları,</li>
<li>Çalışan eğitimleri ve gizlilik sözleşmeleri,</li>
<li>Şifre güvenliği ve iki faktörlü doğrulama (2FA) altyapısı.</li>
</ul>

<h3>4. Bilgi Paylaşımı</h3>
<p>Kişisel bilgileriniz <strong>hiçbir koşulda satılmaz, kiralanmaz veya pazarlanmaz</strong>. Bilgileriniz yalnızca aşağıdaki durumlarda paylaşılabilir:</p>
<ul>
<li>Hizmet sunmak için gerekli olan anlaşmalı sigorta şirketleri ile,</li>
<li>Yasal zorunluluklar gereği yetkili kamu kurumları ile (Hazine ve Maliye Bakanlığı, SBM, MASAK, mahkemeler vb.),</li>
<li>Açık rızanızla belirttiğiniz üçüncü taraflarla.</li>
</ul>

<h3>5. Çerezler (Cookies)</h3>
<p>Web sitemiz çerezler kullanmaktadır. Çerezler hakkında detaylı bilgi için <a href="/sayfa/cerez-politikasi">Çerez Politikamızı</a> inceleyebilirsiniz.</p>

<h3>6. Üçüncü Taraf Bağlantıları</h3>
<p>Web sitemiz, üçüncü taraf web sitelerine bağlantılar içerebilir. Mizan Sigorta, bu sitelerin gizlilik uygulamalarından sorumlu değildir. Söz konusu sitelerin gizlilik politikalarını ayrıca incelemenizi öneririz.</p>

<h3>7. Çocukların Gizliliği</h3>
<p>Web sitemiz 18 yaşın altındaki bireylere yönelik olarak tasarlanmamıştır. 18 yaşın altındaki kişilerden bilerek kişisel veri toplamayız. 18 yaşın altında olduğunu bildiğimiz bir kişiden veri toplandığını fark edersek, söz konusu verileri derhal sileriz.</p>

<h3>8. Gizlilik Politikasında Değişiklikler</h3>
<p>Bu Gizlilik Politikası zaman zaman güncellenebilir. Güncel sürüm her zaman web sitemizde yayınlanır. Önemli değişikliklerde sizleri ayrıca bilgilendireceğiz.</p>

<h3>9. İletişim</h3>
<p>Gizlilik Politikamız ile ilgili soru, görüş veya endişelerinizi <strong>destek@mizansigorta.com.tr</strong> adresine veya yukarıda belirtilen iletişim kanallarımızdan birine iletebilirsiniz.</p>

<p class="text-muted small mt-4"><em>Son güncelleme tarihi: 2026</em></p>
</div>', 'Gizlilik Politikası - Mizan Sigorta', 'Mizan Sigorta gizlilik ilkeleri ve veri işleme politikamız.', 0, 92, 1, 1),
('cerez-politikasi', 'Çerez Politikası', '<div class="mz-prose">
<p class="lead">Bu Çerez Politikası, Mizan Sigorta Aracılık Hizmetleri (&ldquo;Mizan Sigorta&rdquo;) tarafından işletilen <strong>mizansigorta.com.tr</strong> web sitesinde kullanılan çerezler hakkında sizi bilgilendirmek amacıyla hazırlanmıştır.</p>

<h3>1. Çerez (Cookie) Nedir?</h3>
<p>Çerezler, ziyaret ettiğiniz web sitelerinin tarayıcınız üzerinden cihazınıza yerleştirdiği küçük metin dosyalarıdır. Çerezler, sitenin çalışması, kullanıcı deneyimini iyileştirilmesi ve trafik analizi gibi amaçlarla kullanılır.</p>

<h3>2. Kullandığımız Çerez Türleri</h3>

<h5>a) Zorunlu Çerezler</h5>
<p>Web sitesinin temel işlevlerini yerine getirmesi için gereklidir. Devre dışı bırakılamazlar. Örnekler:</p>
<ul>
<li>Oturum çerezleri (giriş/çıkış işlemleri)</li>
<li>Güvenlik çerezleri (CSRF token)</li>
<li>Form doldurma sırasındaki geçici veri çerezleri</li>
</ul>

<h5>b) Performans / Analitik Çerezleri</h5>
<p>Ziyaretçilerin siteyi nasıl kullandığını anlamamızı sağlayan çerezlerdir. Tüm bilgiler anonim olarak toplanır.</p>
<ul>
<li>Sayfa görüntüleme sayıları</li>
<li>Ziyaret süresi ve yolculuğu</li>
<li>Hata raporlama</li>
</ul>

<h5>c) İşlevsel Çerezler</h5>
<p>Tercihlerinizi (dil seçimi, çerez onayı vb.) hatırlamak için kullanılır.</p>

<h5>d) Hedefleme/Pazarlama Çerezleri</h5>
<p>İlgi alanlarınıza yönelik içerik göstermek amacıyla kullanılır. Yalnızca açık rızanızla aktif edilir.</p>

<h3>3. Çerez Yönetimi</h3>
<p>Çerez tercihlerinizi istediğiniz zaman değiştirebilirsiniz:</p>
<ul>
<li>Web sitemizdeki çerez bandındaki <strong>&ldquo;Tercihleri Yönet&rdquo;</strong> seçeneğini kullanarak,</li>
<li>Tarayıcınızın ayarlarından çerezleri kabul etmeyi reddedebilir veya bazı çerezleri silebilirsiniz.</li>
</ul>

<p>Tarayıcı bazlı çerez yönetimi adresleri:</p>
<ul>
<li><a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener">Google Chrome</a></li>
<li><a href="https://support.mozilla.org/tr/kb/cerezler-web-sitelerinin-bilgisayariniza-koyduklari" target="_blank" rel="noopener">Mozilla Firefox</a></li>
<li><a href="https://support.microsoft.com/tr-tr/microsoft-edge" target="_blank" rel="noopener">Microsoft Edge</a></li>
<li><a href="https://support.apple.com/tr-tr/safari" target="_blank" rel="noopener">Safari</a></li>
</ul>

<p><strong>Önemli:</strong> Çerezleri tamamen devre dışı bırakırsanız web sitemizin bazı bölümleri düzgün çalışmayabilir.</p>

<h3>4. Üçüncü Taraf Çerezleri</h3>
<p>Web sitemizde, hizmet sağlayıcılar tarafından sağlanan bazı üçüncü taraf çerezler bulunabilir:</p>
<ul>
<li>Google Maps (iletişim sayfasındaki harita için)</li>
<li>Bootstrap CDN ve font kaynakları</li>
<li>Sosyal medya entegrasyonları (paylaşım butonları)</li>
</ul>

<h3>5. Çerez Saklama Süreleri</h3>
<table class="table table-sm table-bordered mt-2">
<thead><tr><th>Çerez Türü</th><th>Süre</th></tr></thead>
<tbody>
<tr><td>Oturum çerezleri</td><td>Tarayıcı kapatılana kadar</td></tr>
<tr><td>Çerez onay çerezi</td><td>12 ay</td></tr>
<tr><td>Analitik çerezler</td><td>24 ay</td></tr>
<tr><td>İşlevsel çerezler</td><td>12 ay</td></tr>
</tbody>
</table>

<h3>6. Politika Güncellemeleri</h3>
<p>Bu Çerez Politikası, gerektiğinde güncellenebilir. Güncel sürüm her zaman bu sayfada yayınlanır.</p>

<h3>7. İletişim</h3>
<p>Çerez politikamız hakkında soru ve görüşleriniz için: <strong>destek@mizansigorta.com.tr</strong></p>

<p class="text-muted small mt-4"><em>Son güncelleme tarihi: 2026</em></p>
</div>', 'Çerez Politikası - Mizan Sigorta', 'Web sitemizde kullanılan çerezler ve çerez yönetim politikamız.', 0, 93, 1, 1),
('kullanim-sartlari', 'Kullanım Şartları', '<div class="mz-prose">
<p class="lead">Bu Kullanım Şartları, Mizan Sigorta Aracılık Hizmetleri (&ldquo;Mizan Sigorta&rdquo; veya &ldquo;Şirket&rdquo;) tarafından işletilen <strong>mizansigorta.com.tr</strong> web sitesinin kullanımına ilişkin koşulları belirler. Web sitemizi kullanarak bu şartları kabul etmiş sayılırsınız.</p>

<h3>1. Tanımlar</h3>
<ul>
<li><strong>Site:</strong> mizansigorta.com.tr alan adı altında yayınlanan tüm sayfalar ve içerikler.</li>
<li><strong>Kullanıcı:</strong> Siteyi ziyaret eden, bilgi alan, teklif talep eden veya hizmetlerden yararlanan gerçek/tüzel kişiler.</li>
<li><strong>İçerik:</strong> Site üzerinde yer alan metin, görsel, kod, logo, marka ve diğer her türlü materyal.</li>
</ul>

<h3>2. Hizmet Kapsamı</h3>
<p>Mizan Sigorta, 5684 sayılı Sigortacılık Kanunu çerçevesinde sigorta aracılık (acentecilik) hizmeti sunmaktadır. Site üzerinden:</p>
<ul>
<li>Sigorta ürünleri hakkında bilgi sunulur,</li>
<li>Teklif talebi alınır,</li>
<li>Müşteri iletişim ve destek sağlanır,</li>
<li>Hasar süreçleri yönetilir.</li>
</ul>

<p>Sitedeki bilgiler bilgilendirme amaçlıdır; bağlayıcı sigorta sözleşmesi yerine geçmez. Kesin teminat ve şartlar, ilgili sigorta şirketinin düzenlediği poliçede belirtilir.</p>

<h3>3. Kullanım Kuralları</h3>
<p>Web sitemizi kullanırken aşağıdaki kurallara uymanız gerekmektedir:</p>
<ul>
<li>Doğru, güncel ve eksiksiz bilgi sağlamak,</li>
<li>Yetkisiz erişim girişiminde bulunmamak,</li>
<li>Site altyapısına zarar verecek faaliyetlerde bulunmamak (DDoS, exploit, SQL injection vb.),</li>
<li>Telif haklı içerikleri izinsiz kopyalamamak veya yeniden yayınlamamak,</li>
<li>Başka kullanıcıların haklarına saygı göstermek,</li>
<li>Türkiye Cumhuriyeti yasalarına ve uluslararası yasalara uygun davranmak.</li>
</ul>

<h3>4. Fikri Mülkiyet Hakları</h3>
<p>Sitedeki tüm içerik (metin, görsel, marka, logo, tasarım, kod), Mizan Sigorta&rsquo;ya veya lisans alanlarına aittir ve telif hakkı yasaları ile korunmaktadır. Önceden yazılı izin alınmadıkça hiçbir içerik kopyalanamaz, dağıtılamaz veya ticari amaçla kullanılamaz.</p>

<h3>5. Sorumluluk Sınırlamaları</h3>
<p>Mizan Sigorta:</p>
<ul>
<li>Sitedeki bilgilerin daima güncel ve hatasız olduğunu garanti etmez,</li>
<li>Sitenin kesintisiz veya hatasız çalışacağını taahhüt etmez,</li>
<li>Üçüncü taraf hizmetleri (Google Maps vb.) için sorumluluk taşımaz,</li>
<li>Force majeure (deprem, savaş, salgın, siber saldırı vb.) durumlarda hizmet kesintilerinden sorumlu tutulamaz.</li>
</ul>

<p>Sitedeki bilgilere dayanarak alınan kararlardan kullanıcı kendisi sorumludur. Kesin sigorta kararı için poliçeyi ve mevzuatı incelemeniz önerilir.</p>

<h3>6. Hesap Güvenliği</h3>
<p>Eğer Mizan Sigorta&rsquo;da müşteri kaydınız varsa:</p>
<ul>
<li>Şifrenizi gizli tutmak ve düzenli güncellemek sizin sorumluluğunuzdadır,</li>
<li>Hesabınızdan yapılan tüm işlemlerden siz sorumlusunuz,</li>
<li>Yetkisiz erişim şüphesinde derhal bize bildirmelisiniz.</li>
</ul>

<h3>7. Üçüncü Taraf Bağlantıları</h3>
<p>Site, üçüncü taraf web sitelerine bağlantılar içerebilir. Bu sitelerin içeriği, gizlilik politikası veya hizmetlerinden Mizan Sigorta sorumlu değildir.</p>

<h3>8. Şartlarda Değişiklik</h3>
<p>Mizan Sigorta, bu Kullanım Şartlarını dilediği zaman değiştirme hakkını saklı tutar. Değişiklikler bu sayfada yayınlandığı andan itibaren geçerli olur. Önemli değişikliklerde mevcut müşterilerimizi e-posta ile bilgilendiririz.</p>

<h3>9. Uygulanacak Hukuk ve Yetki</h3>
<p>Bu Kullanım Şartları Türkiye Cumhuriyeti yasalarına tabidir. Doğacak tüm uyuşmazlıklarda <strong>İstanbul (Anadolu) Mahkemeleri ve İcra Daireleri</strong> yetkilidir.</p>

<h3>10. İletişim</h3>
<p>Kullanım Şartları ile ilgili soru ve görüşleriniz için:<br>
<strong>E-posta:</strong> destek@mizansigorta.com.tr<br>
<strong>Telefon:</strong> +90 332 000 00 00<br>
<strong>Adres:</strong> Fetih Mah. Libadiye Cad. Tahralı Sok. Kavakyeli İş Merkezi D-Blok K:9 D:24 ATAŞEHİR / İSTANBUL</p>

<p class="text-muted small mt-4"><em>Son güncelleme tarihi: 2026</em></p>
</div>', 'Kullanım Şartları - Mizan Sigorta', 'Web sitemizin kullanım koşulları ve şartları.', 0, 94, 1, 1),
('uye-aydinlatma', 'Üye Aydınlatma Metni', '<div class="mz-prose">
<p class="lead">İşbu Üye Aydınlatma Metni, web sitemiz üzerinden teklif başvurusu, iletişim formu doldurma veya kullanıcı kaydı yaptırma yoluyla bizlere ilettiğiniz kişisel verilerinizin işlenmesi süreçlerinde sizleri aydınlatmak amacıyla hazırlanmıştır.</p>

<h3>1. Hangi Verileriniz İşlenir?</h3>
<p>Web sitemizdeki form ve iletişim kanalları aracılığıyla:</p>
<ul>
<li>Ad, soyad, firma adı (kurumsal müşteriler için)</li>
<li>Telefon, e-posta, adres bilgileri</li>
<li>Sigorta talep konusu, açıklama, mesaj içerikleri</li>
<li>IP adresi ve oturum bilgisi (güvenlik amacıyla)</li>
</ul>

<h3>2. Hangi Amaçla İşlenir?</h3>
<ul>
<li>Sigorta teklifi hazırlanması ve sunulması,</li>
<li>Tarafınızla iletişim kurulması,</li>
<li>Mesajınıza/talebinize yanıt verilmesi,</li>
<li>Müşteri kaydı oluşturulması (talep ederseniz),</li>
<li>Yasal yükümlülüklerin yerine getirilmesi,</li>
<li>Bilgi güvenliği süreçlerinin yürütülmesi.</li>
</ul>

<h3>3. Hangi Hukuki Sebebe Dayanır?</h3>
<p>Verileriniz; sözleşmenin kurulması veya ifası için zorunlu olması (KVKK m.5/2-c), Mizan Sigorta&rsquo;nın meşru menfaatleri (KVKK m.5/2-f) ve açık rızanız (KVKK m.5/1) hukuki sebeplerine dayanılarak işlenir.</p>

<h3>4. Aktarılır mı?</h3>
<p>Verileriniz; teklif ürettiğimiz anlaşmalı sigorta şirketleri, yasal yükümlülükler kapsamında yetkili kamu kurumları ve iş süreçlerimizi yürüten hizmet tedarikçileri ile sınırlı olarak paylaşılabilir.</p>

<h3>5. Saklama Süresi</h3>
<p>Sigortacılık mevzuatı gereği genel saklama süresi 10 yıldır. Talep ettiğiniz hizmet sonuçlanmazsa veriler 1 yıl içinde silinir veya anonimleştirilir.</p>

<h3>6. Haklarınız</h3>
<p>KVKK&rsquo;nın 11. maddesi kapsamındaki tüm haklarınız (bilgi alma, düzeltme, silme vb.) için <strong>destek@mizansigorta.com.tr</strong> adresine başvurabilirsiniz. Detaylı bilgi için <a href="/sayfa/kvkk">KVKK Aydınlatma Metnimizi</a> inceleyiniz.</p>

<p class="text-muted small mt-4"><em>Son güncelleme tarihi: 2026</em></p>
</div>', 'Üye Aydınlatma Metni - Mizan Sigorta', 'Web üyeliği veya teklif başvurusu kapsamında aydınlatma metni.', 0, 95, 1, 1),
('acik-riza', 'Açık Rıza Metni', '<div class="mz-prose">
<p class="lead">İşbu Açık Rıza Metni, Mizan Sigorta Aracılık Hizmetleri tarafından kişisel verilerinizin 6698 sayılı Kişisel Verilerin Korunması Kanunu (&ldquo;KVKK&rdquo;) kapsamında açık rızaya tabi olarak işlenmesi durumunda alınan onayınızı belgelemek üzere hazırlanmıştır.</p>

<h3>1. Açık Rıza Kapsamı</h3>
<p>Aşağıdaki işlemler için <strong>ayrı ayrı</strong> açık rıza alınmaktadır:</p>

<h5>a) Pazarlama ve Bilgilendirme İletişimi</h5>
<p>Tarafınıza:</p>
<ul>
<li>Sigorta ürünlerine ilişkin promosyon ve kampanya bilgileri,</li>
<li>Yeni ürün ve hizmet duyuruları,</li>
<li>Sektörel bilgilendirmeler ve haber bültenleri,</li>
<li>Anket ve memnuniyet ölçümü çağrıları</li>
</ul>
<p>için e-posta, SMS veya telefon yoluyla iletişim kurulmasına onay vermeniz halinde, söz konusu iletişim faaliyetleri yürütülecektir.</p>

<h5>b) Özel Nitelikli Kişisel Verilerin İşlenmesi</h5>
<p>Sağlık sigortası başvurularında, KVKK m.6 kapsamında özel nitelikli kişisel veri sayılan sağlık verilerinizin işlenmesi açık rızanıza tabidir.</p>

<h5>c) Veri Aktarımı</h5>
<p>Yurt içindeki anlaşmalı sigorta şirketlerine sözleşme kurulması ve ifası için aktarım sözleşme kapsamında değerlendirilse de; pazarlama amaçlı paylaşım için açık rıza alınır.</p>

<h3>2. Rızanın Geri Alınması</h3>
<p>Verdiğiniz açık rızayı dilediğiniz zaman, herhangi bir gerekçe göstermeden geri alabilirsiniz. Bu durumda:</p>
<ul>
<li>İlgili işleme faaliyeti durdurulur,</li>
<li>Pazarlama listelerinden çıkarılırsınız,</li>
<li>Geri alma tarihinden önceki yasal işlemler etkilenmez.</li>
</ul>

<h3>3. Rıza Geri Alma Yöntemleri</h3>
<ul>
<li><strong>E-posta:</strong> destek@mizansigorta.com.tr (&ldquo;Açık rızamı geri alıyorum&rdquo; konusu ile)</li>
<li><strong>Posta:</strong> Yukarıdaki adresimize ıslak imzalı dilekçe</li>
<li><strong>Pazarlama bültenleri için:</strong> E-postadaki &ldquo;abonelikten çık&rdquo; bağlantısı</li>
</ul>

<h3>4. Önemli Bilgiler</h3>
<ul>
<li>Açık rıza vermek <strong>tamamen isteğe bağlıdır</strong>; rıza vermemeniz hizmet alma hakkınızı etkilemez.</li>
<li>Rıza geri alındıktan sonra verileriniz, mevzuatın gerektirmediği sürece silinir veya anonim hale getirilir.</li>
<li>Rıza alınmasına rağmen, mevzuat gereği saklanması zorunlu olan veriler ilgili sürelerin sonuna kadar muhafaza edilir.</li>
</ul>

<h3>5. İletişim</h3>
<p>Açık Rıza Metni hakkında soru ve görüşleriniz için: <strong>destek@mizansigorta.com.tr</strong></p>

<p class="text-muted small mt-4"><em>Son güncelleme tarihi: 2026</em></p>
</div>', 'Açık Rıza Metni - Mizan Sigorta', 'Kişisel verilerin işlenmesine ilişkin açık rıza beyanı.', 0, 96, 1, 1);

-- Mevcut hukuki sayfalari da guncel icerikle UPDATE (idempotent)
UPDATE `mz_sayfalar` SET `icerik` = '<div class="mz-prose">
<p class="lead">İşbu Aydınlatma Metni, 6698 sayılı Kişisel Verilerin Korunması Kanunu (&ldquo;KVKK&rdquo;) kapsamında, veri sorumlusu sıfatıyla <strong>Mizan Sigorta Aracılık Hizmetleri</strong> tarafından kişisel verilerinizin işlenmesine ilişkin esasları açıklamak amacıyla hazırlanmıştır.</p>

<h3>1. Veri Sorumlusunun Kimliği</h3>
<p>
<strong>Veri Sorumlusu:</strong> Mizan Sigorta Aracılık Hizmetleri<br>
<strong>Adres:</strong> Fetih Mah. Libadiye Cad. Tahralı Sok. Kavakyeli İş Merkezi D-Blok K:9 D:24 ATAŞEHİR / İSTANBUL<br>
<strong>E-posta:</strong> destek@mizansigorta.com.tr<br>
<strong>Telefon:</strong> +90 332 000 00 00
</p>

<h3>2. İşlenen Kişisel Veriler ve Toplama Yöntemi</h3>
<p>Mizan Sigorta, sigorta aracılık hizmetlerinin sunulması, müşteri ilişkilerinin yönetimi ve yasal yükümlülüklerin yerine getirilmesi amacıyla aşağıdaki kişisel verilerinizi işleyebilir:</p>
<ul>
<li><strong>Kimlik Bilgileri:</strong> Ad, soyad, T.C. kimlik numarası, doğum tarihi, cinsiyet</li>
<li><strong>İletişim Bilgileri:</strong> Telefon, e-posta, ikametgâh adresi</li>
<li><strong>Müşteri İşlem Bilgileri:</strong> Sigorta talepleriniz, poliçe bilgileriniz, hasar dosyalarınız</li>
<li><strong>Finansal Bilgiler:</strong> Ödeme bilgileriniz, prim ödemeleriniz</li>
<li><strong>Görsel/İşitsel Kayıtlar:</strong> Çağrı merkezi ses kayıtları (kalite ve güvenlik amacıyla)</li>
<li><strong>İşlem Güvenliği:</strong> IP adresi, çerez bilgileri, oturum bilgileri</li>
<li><strong>Araç Bilgileri:</strong> Plaka, marka, model, ruhsat bilgileri (oto sigortaları için)</li>
<li><strong>Sağlık Bilgileri (Özel Nitelikli):</strong> Sağlık sigortası kapsamında, açık rızanızla</li>
</ul>

<p>Bu veriler; web sitemiz üzerinden doldurduğunuz formlar, telefon görüşmelerimiz, e-posta yazışmaları, fiziki olarak iletilen belgeler veya yetkili sigorta şirketleri aracılığıyla toplanmaktadır.</p>

<h3>3. Kişisel Verilerin İşlenme Amaçları</h3>
<p>Kişisel verileriniz, KVKK&rsquo;nın 5. ve 6. maddelerinde belirtilen kişisel veri işleme şartları çerçevesinde aşağıdaki amaçlarla işlenmektedir:</p>
<ul>
<li>Sigorta teklifi hazırlanması ve sunulması</li>
<li>Sigorta poliçesi düzenleme ve aracılık faaliyetlerinin yürütülmesi</li>
<li>Müşteri kayıtlarının oluşturulması ve müşteri ilişkilerinin yönetimi</li>
<li>Hasar süreçlerinin takibi ve yönetimi</li>
<li>Yenileme dönemlerinde poliçe yenileme bildirimleri</li>
<li>Müşteri memnuniyetinin ölçülmesi ve iyileştirme çalışmaları</li>
<li>Yasal yükümlülüklerin yerine getirilmesi (SBM, MASAK, Hazine ve Maliye Bakanlığı vb.)</li>
<li>Bilgi güvenliği süreçlerinin yürütülmesi</li>
<li>İletişim faaliyetlerinin yürütülmesi (yalnızca açık rızanız varsa pazarlama amaçlı)</li>
</ul>

<h3>4. İşlenen Kişisel Verilerin Aktarımı</h3>
<p>Kişisel verileriniz, KVKK&rsquo;nın 8. ve 9. maddelerine uygun şekilde, aşağıdaki taraflara aktarılabilir:</p>
<ul>
<li><strong>Anlaşmalı Sigorta Şirketleri:</strong> Anadolu Sigorta, Allianz, AXA, Türkiye Sigorta, HDI, Quick Sigorta, Neova, Ak Sigorta, Doğa Sigorta vb. (poliçe düzenlenmesi amacıyla)</li>
<li><strong>Sigorta Bilgi ve Gözetim Merkezi (SBM):</strong> Yasal yükümlülük gereği</li>
<li><strong>Hazine ve Maliye Bakanlığı:</strong> Mevzuat gereği</li>
<li><strong>Yetkili Kamu Kurumları ve Yargı Mercileri:</strong> Yasal talep halinde</li>
<li><strong>Hizmet Aldığımız Tedarikçiler:</strong> IT altyapısı, hosting, e-posta gönderim hizmetleri</li>
<li><strong>Hukuk Müşavirleri ve Bağımsız Denetçiler:</strong> Hukuki süreçlerde gerektiği ölçüde</li>
</ul>

<h3>5. Kişisel Veri İşlemenin Hukuki Sebepleri</h3>
<p>Kişisel verileriniz aşağıdaki hukuki sebeplere dayanılarak işlenmektedir:</p>
<ul>
<li><strong>Kanunlarda açıkça öngörülmesi</strong> (5684 sayılı Sigortacılık Kanunu, KVKK)</li>
<li><strong>Sözleşmenin kurulması veya ifası</strong> (sigorta aracılık sözleşmesi)</li>
<li><strong>Veri sorumlusunun hukuki yükümlülüğünü yerine getirebilmesi</strong></li>
<li><strong>Bir hakkın tesisi, kullanılması veya korunması</strong></li>
<li><strong>Açık rızanız</strong> (özel nitelikli kişisel veriler ve pazarlama amaçlı kullanımlar için)</li>
</ul>

<h3>6. KVKK Kapsamındaki Haklarınız</h3>
<p>KVKK&rsquo;nın 11. maddesi uyarınca, veri sorumlusu olarak Mizan Sigorta&rsquo;ya başvurarak:</p>
<ul>
<li>Kişisel verilerinizin işlenip işlenmediğini öğrenme,</li>
<li>İşlenmişse buna ilişkin bilgi talep etme,</li>
<li>İşlenme amacını ve amacına uygun kullanılıp kullanılmadığını öğrenme,</li>
<li>Yurt içinde veya yurt dışında aktarıldığı üçüncü kişileri bilme,</li>
<li>Eksik veya yanlış işlenmişse düzeltilmesini isteme,</li>
<li>KVKK&rsquo;da öngörülen şartlar çerçevesinde silinmesini veya yok edilmesini isteme,</li>
<li>Düzeltme/silme/yok etme işlemlerinin verilerin aktarıldığı üçüncü kişilere bildirilmesini isteme,</li>
<li>Otomatik sistemlerle analiz edilmesi sonucu aleyhinize bir sonuç çıkmasına itiraz etme,</li>
<li>Kanuna aykırı işleme nedeniyle uğradığınız zararın giderilmesini talep etme</li>
</ul>
<p>haklarına sahipsiniz.</p>

<h3>7. Başvuru Yöntemi</h3>
<p>Yukarıdaki haklarınızı kullanmak için, aşağıdaki yöntemlerden biriyle başvuruda bulunabilirsiniz:</p>
<ul>
<li><strong>E-posta:</strong> destek@mizansigorta.com.tr (güvenli elektronik imzalı)</li>
<li><strong>Posta:</strong> Fetih Mah. Libadiye Cad. Tahralı Sok. Kavakyeli İş Merkezi D-Blok K:9 D:24 ATAŞEHİR / İSTANBUL (ıslak imzalı dilekçe ile)</li>
<li><strong>Noter Kanalıyla:</strong> Yukarıdaki adrese noter aracılığıyla</li>
</ul>
<p>Başvurunuz, talebin niteliğine göre en kısa sürede ve en geç <strong>30 gün içinde</strong> ücretsiz olarak sonuçlandırılacaktır. İşlemin ayrıca bir maliyet gerektirmesi halinde KVKK Kurulu&rsquo;nun belirlediği tarifedeki ücret talep edilebilir.</p>

<h3>8. Veri Saklama Süresi</h3>
<p>Kişisel verileriniz, ilgili mevzuatta öngörülen veya işleme amacının gerektirdiği süreler boyunca saklanır. Saklama süreleri sona erdiğinde verileriniz, KVKK ve ilgili yönetmelikler çerçevesinde silinir, yok edilir veya anonim hale getirilir. Sigortacılık mevzuatı gereği genel saklama süresi <strong>10 yıldır</strong>.</p>

<h3>9. Güncellemeler</h3>
<p>İşbu Aydınlatma Metni, ilgili mevzuat veya iş süreçlerimizdeki değişiklikler doğrultusunda güncellenebilir. Güncel metni daima web sitemizde bulabilirsiniz.</p>

<p class="text-muted small mt-4"><em>Son güncelleme tarihi: 2026</em></p>
</div>' WHERE `slug` = 'kvkk' AND CHAR_LENGTH(`icerik`) < 800;
UPDATE `mz_sayfalar` SET `icerik` = '<div class="mz-prose">
<p class="lead">Mizan Sigorta Aracılık Hizmetleri olarak, müşterilerimizin ve web sitemizi ziyaret eden kullanıcılarımızın gizliliğine saygı duyuyoruz. Bu Gizlilik Politikası, kişisel bilgilerinizin nasıl toplandığını, kullanıldığını ve korunduğunu açıklamaktadır.</p>

<h3>1. Toplanan Bilgiler</h3>
<p>Web sitemiz üzerinden veya hizmetlerimiz vesilesiyle aşağıdaki tür bilgileri toplayabiliriz:</p>
<ul>
<li><strong>Doğrudan sağladığınız bilgiler:</strong> İletişim formları, teklif başvuruları, müşteri kayıt formları, hasar bildirim formları üzerinden ilettiğiniz bilgiler.</li>
<li><strong>Otomatik olarak toplanan bilgiler:</strong> IP adresi, tarayıcı türü, ziyaret edilen sayfalar, ziyaret süresi ve çerez verileri.</li>
<li><strong>Üçüncü taraflardan elde edilen bilgiler:</strong> Anlaşmalı sigorta şirketleri ile yürüttüğümüz iş süreçleri kapsamında elde edilen bilgiler.</li>
</ul>

<h3>2. Bilgilerin Kullanımı</h3>
<p>Topladığımız bilgileri yalnızca aşağıdaki amaçlarla kullanırız:</p>
<ul>
<li>Sigorta tekliflerinin hazırlanması ve hizmet sunumu,</li>
<li>Müşteri ilişkilerinin yönetimi,</li>
<li>Hasar süreçlerinin yönetimi,</li>
<li>Yasal ve düzenleyici yükümlülüklerin yerine getirilmesi,</li>
<li>Web sitesi performansının analizi ve iyileştirilmesi,</li>
<li>Açık rızanız varsa pazarlama ve bilgilendirme iletişimleri.</li>
</ul>

<h3>3. Bilgilerin Korunması</h3>
<p>Mizan Sigorta, kişisel bilgilerinizin güvenliğini sağlamak için endüstri standartlarında teknik ve idari tedbirleri uygulamaktadır:</p>
<ul>
<li>SSL şifrelemeli güvenli veri aktarımı (HTTPS),</li>
<li>Erişim kontrolü ve yetkilendirme sistemleri,</li>
<li>Düzenli güvenlik denetimleri,</li>
<li>Veri yedekleme ve felaket kurtarma planları,</li>
<li>Çalışan eğitimleri ve gizlilik sözleşmeleri,</li>
<li>Şifre güvenliği ve iki faktörlü doğrulama (2FA) altyapısı.</li>
</ul>

<h3>4. Bilgi Paylaşımı</h3>
<p>Kişisel bilgileriniz <strong>hiçbir koşulda satılmaz, kiralanmaz veya pazarlanmaz</strong>. Bilgileriniz yalnızca aşağıdaki durumlarda paylaşılabilir:</p>
<ul>
<li>Hizmet sunmak için gerekli olan anlaşmalı sigorta şirketleri ile,</li>
<li>Yasal zorunluluklar gereği yetkili kamu kurumları ile (Hazine ve Maliye Bakanlığı, SBM, MASAK, mahkemeler vb.),</li>
<li>Açık rızanızla belirttiğiniz üçüncü taraflarla.</li>
</ul>

<h3>5. Çerezler (Cookies)</h3>
<p>Web sitemiz çerezler kullanmaktadır. Çerezler hakkında detaylı bilgi için <a href="/sayfa/cerez-politikasi">Çerez Politikamızı</a> inceleyebilirsiniz.</p>

<h3>6. Üçüncü Taraf Bağlantıları</h3>
<p>Web sitemiz, üçüncü taraf web sitelerine bağlantılar içerebilir. Mizan Sigorta, bu sitelerin gizlilik uygulamalarından sorumlu değildir. Söz konusu sitelerin gizlilik politikalarını ayrıca incelemenizi öneririz.</p>

<h3>7. Çocukların Gizliliği</h3>
<p>Web sitemiz 18 yaşın altındaki bireylere yönelik olarak tasarlanmamıştır. 18 yaşın altındaki kişilerden bilerek kişisel veri toplamayız. 18 yaşın altında olduğunu bildiğimiz bir kişiden veri toplandığını fark edersek, söz konusu verileri derhal sileriz.</p>

<h3>8. Gizlilik Politikasında Değişiklikler</h3>
<p>Bu Gizlilik Politikası zaman zaman güncellenebilir. Güncel sürüm her zaman web sitemizde yayınlanır. Önemli değişikliklerde sizleri ayrıca bilgilendireceğiz.</p>

<h3>9. İletişim</h3>
<p>Gizlilik Politikamız ile ilgili soru, görüş veya endişelerinizi <strong>destek@mizansigorta.com.tr</strong> adresine veya yukarıda belirtilen iletişim kanallarımızdan birine iletebilirsiniz.</p>

<p class="text-muted small mt-4"><em>Son güncelleme tarihi: 2026</em></p>
</div>' WHERE `slug` = 'gizlilik-politikasi' AND CHAR_LENGTH(`icerik`) < 800;
UPDATE `mz_sayfalar` SET `icerik` = '<div class="mz-prose">
<p class="lead">Bu Çerez Politikası, Mizan Sigorta Aracılık Hizmetleri (&ldquo;Mizan Sigorta&rdquo;) tarafından işletilen <strong>mizansigorta.com.tr</strong> web sitesinde kullanılan çerezler hakkında sizi bilgilendirmek amacıyla hazırlanmıştır.</p>

<h3>1. Çerez (Cookie) Nedir?</h3>
<p>Çerezler, ziyaret ettiğiniz web sitelerinin tarayıcınız üzerinden cihazınıza yerleştirdiği küçük metin dosyalarıdır. Çerezler, sitenin çalışması, kullanıcı deneyimini iyileştirilmesi ve trafik analizi gibi amaçlarla kullanılır.</p>

<h3>2. Kullandığımız Çerez Türleri</h3>

<h5>a) Zorunlu Çerezler</h5>
<p>Web sitesinin temel işlevlerini yerine getirmesi için gereklidir. Devre dışı bırakılamazlar. Örnekler:</p>
<ul>
<li>Oturum çerezleri (giriş/çıkış işlemleri)</li>
<li>Güvenlik çerezleri (CSRF token)</li>
<li>Form doldurma sırasındaki geçici veri çerezleri</li>
</ul>

<h5>b) Performans / Analitik Çerezleri</h5>
<p>Ziyaretçilerin siteyi nasıl kullandığını anlamamızı sağlayan çerezlerdir. Tüm bilgiler anonim olarak toplanır.</p>
<ul>
<li>Sayfa görüntüleme sayıları</li>
<li>Ziyaret süresi ve yolculuğu</li>
<li>Hata raporlama</li>
</ul>

<h5>c) İşlevsel Çerezler</h5>
<p>Tercihlerinizi (dil seçimi, çerez onayı vb.) hatırlamak için kullanılır.</p>

<h5>d) Hedefleme/Pazarlama Çerezleri</h5>
<p>İlgi alanlarınıza yönelik içerik göstermek amacıyla kullanılır. Yalnızca açık rızanızla aktif edilir.</p>

<h3>3. Çerez Yönetimi</h3>
<p>Çerez tercihlerinizi istediğiniz zaman değiştirebilirsiniz:</p>
<ul>
<li>Web sitemizdeki çerez bandındaki <strong>&ldquo;Tercihleri Yönet&rdquo;</strong> seçeneğini kullanarak,</li>
<li>Tarayıcınızın ayarlarından çerezleri kabul etmeyi reddedebilir veya bazı çerezleri silebilirsiniz.</li>
</ul>

<p>Tarayıcı bazlı çerez yönetimi adresleri:</p>
<ul>
<li><a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener">Google Chrome</a></li>
<li><a href="https://support.mozilla.org/tr/kb/cerezler-web-sitelerinin-bilgisayariniza-koyduklari" target="_blank" rel="noopener">Mozilla Firefox</a></li>
<li><a href="https://support.microsoft.com/tr-tr/microsoft-edge" target="_blank" rel="noopener">Microsoft Edge</a></li>
<li><a href="https://support.apple.com/tr-tr/safari" target="_blank" rel="noopener">Safari</a></li>
</ul>

<p><strong>Önemli:</strong> Çerezleri tamamen devre dışı bırakırsanız web sitemizin bazı bölümleri düzgün çalışmayabilir.</p>

<h3>4. Üçüncü Taraf Çerezleri</h3>
<p>Web sitemizde, hizmet sağlayıcılar tarafından sağlanan bazı üçüncü taraf çerezler bulunabilir:</p>
<ul>
<li>Google Maps (iletişim sayfasındaki harita için)</li>
<li>Bootstrap CDN ve font kaynakları</li>
<li>Sosyal medya entegrasyonları (paylaşım butonları)</li>
</ul>

<h3>5. Çerez Saklama Süreleri</h3>
<table class="table table-sm table-bordered mt-2">
<thead><tr><th>Çerez Türü</th><th>Süre</th></tr></thead>
<tbody>
<tr><td>Oturum çerezleri</td><td>Tarayıcı kapatılana kadar</td></tr>
<tr><td>Çerez onay çerezi</td><td>12 ay</td></tr>
<tr><td>Analitik çerezler</td><td>24 ay</td></tr>
<tr><td>İşlevsel çerezler</td><td>12 ay</td></tr>
</tbody>
</table>

<h3>6. Politika Güncellemeleri</h3>
<p>Bu Çerez Politikası, gerektiğinde güncellenebilir. Güncel sürüm her zaman bu sayfada yayınlanır.</p>

<h3>7. İletişim</h3>
<p>Çerez politikamız hakkında soru ve görüşleriniz için: <strong>destek@mizansigorta.com.tr</strong></p>

<p class="text-muted small mt-4"><em>Son güncelleme tarihi: 2026</em></p>
</div>' WHERE `slug` = 'cerez-politikasi' AND CHAR_LENGTH(`icerik`) < 800;
UPDATE `mz_sayfalar` SET `icerik` = '<div class="mz-prose">
<p class="lead">Bu Kullanım Şartları, Mizan Sigorta Aracılık Hizmetleri (&ldquo;Mizan Sigorta&rdquo; veya &ldquo;Şirket&rdquo;) tarafından işletilen <strong>mizansigorta.com.tr</strong> web sitesinin kullanımına ilişkin koşulları belirler. Web sitemizi kullanarak bu şartları kabul etmiş sayılırsınız.</p>

<h3>1. Tanımlar</h3>
<ul>
<li><strong>Site:</strong> mizansigorta.com.tr alan adı altında yayınlanan tüm sayfalar ve içerikler.</li>
<li><strong>Kullanıcı:</strong> Siteyi ziyaret eden, bilgi alan, teklif talep eden veya hizmetlerden yararlanan gerçek/tüzel kişiler.</li>
<li><strong>İçerik:</strong> Site üzerinde yer alan metin, görsel, kod, logo, marka ve diğer her türlü materyal.</li>
</ul>

<h3>2. Hizmet Kapsamı</h3>
<p>Mizan Sigorta, 5684 sayılı Sigortacılık Kanunu çerçevesinde sigorta aracılık (acentecilik) hizmeti sunmaktadır. Site üzerinden:</p>
<ul>
<li>Sigorta ürünleri hakkında bilgi sunulur,</li>
<li>Teklif talebi alınır,</li>
<li>Müşteri iletişim ve destek sağlanır,</li>
<li>Hasar süreçleri yönetilir.</li>
</ul>

<p>Sitedeki bilgiler bilgilendirme amaçlıdır; bağlayıcı sigorta sözleşmesi yerine geçmez. Kesin teminat ve şartlar, ilgili sigorta şirketinin düzenlediği poliçede belirtilir.</p>

<h3>3. Kullanım Kuralları</h3>
<p>Web sitemizi kullanırken aşağıdaki kurallara uymanız gerekmektedir:</p>
<ul>
<li>Doğru, güncel ve eksiksiz bilgi sağlamak,</li>
<li>Yetkisiz erişim girişiminde bulunmamak,</li>
<li>Site altyapısına zarar verecek faaliyetlerde bulunmamak (DDoS, exploit, SQL injection vb.),</li>
<li>Telif haklı içerikleri izinsiz kopyalamamak veya yeniden yayınlamamak,</li>
<li>Başka kullanıcıların haklarına saygı göstermek,</li>
<li>Türkiye Cumhuriyeti yasalarına ve uluslararası yasalara uygun davranmak.</li>
</ul>

<h3>4. Fikri Mülkiyet Hakları</h3>
<p>Sitedeki tüm içerik (metin, görsel, marka, logo, tasarım, kod), Mizan Sigorta&rsquo;ya veya lisans alanlarına aittir ve telif hakkı yasaları ile korunmaktadır. Önceden yazılı izin alınmadıkça hiçbir içerik kopyalanamaz, dağıtılamaz veya ticari amaçla kullanılamaz.</p>

<h3>5. Sorumluluk Sınırlamaları</h3>
<p>Mizan Sigorta:</p>
<ul>
<li>Sitedeki bilgilerin daima güncel ve hatasız olduğunu garanti etmez,</li>
<li>Sitenin kesintisiz veya hatasız çalışacağını taahhüt etmez,</li>
<li>Üçüncü taraf hizmetleri (Google Maps vb.) için sorumluluk taşımaz,</li>
<li>Force majeure (deprem, savaş, salgın, siber saldırı vb.) durumlarda hizmet kesintilerinden sorumlu tutulamaz.</li>
</ul>

<p>Sitedeki bilgilere dayanarak alınan kararlardan kullanıcı kendisi sorumludur. Kesin sigorta kararı için poliçeyi ve mevzuatı incelemeniz önerilir.</p>

<h3>6. Hesap Güvenliği</h3>
<p>Eğer Mizan Sigorta&rsquo;da müşteri kaydınız varsa:</p>
<ul>
<li>Şifrenizi gizli tutmak ve düzenli güncellemek sizin sorumluluğunuzdadır,</li>
<li>Hesabınızdan yapılan tüm işlemlerden siz sorumlusunuz,</li>
<li>Yetkisiz erişim şüphesinde derhal bize bildirmelisiniz.</li>
</ul>

<h3>7. Üçüncü Taraf Bağlantıları</h3>
<p>Site, üçüncü taraf web sitelerine bağlantılar içerebilir. Bu sitelerin içeriği, gizlilik politikası veya hizmetlerinden Mizan Sigorta sorumlu değildir.</p>

<h3>8. Şartlarda Değişiklik</h3>
<p>Mizan Sigorta, bu Kullanım Şartlarını dilediği zaman değiştirme hakkını saklı tutar. Değişiklikler bu sayfada yayınlandığı andan itibaren geçerli olur. Önemli değişikliklerde mevcut müşterilerimizi e-posta ile bilgilendiririz.</p>

<h3>9. Uygulanacak Hukuk ve Yetki</h3>
<p>Bu Kullanım Şartları Türkiye Cumhuriyeti yasalarına tabidir. Doğacak tüm uyuşmazlıklarda <strong>İstanbul (Anadolu) Mahkemeleri ve İcra Daireleri</strong> yetkilidir.</p>

<h3>10. İletişim</h3>
<p>Kullanım Şartları ile ilgili soru ve görüşleriniz için:<br>
<strong>E-posta:</strong> destek@mizansigorta.com.tr<br>
<strong>Telefon:</strong> +90 332 000 00 00<br>
<strong>Adres:</strong> Fetih Mah. Libadiye Cad. Tahralı Sok. Kavakyeli İş Merkezi D-Blok K:9 D:24 ATAŞEHİR / İSTANBUL</p>

<p class="text-muted small mt-4"><em>Son güncelleme tarihi: 2026</em></p>
</div>' WHERE `slug` = 'kullanim-sartlari' AND CHAR_LENGTH(`icerik`) < 800;
UPDATE `mz_sayfalar` SET `icerik` = '<div class="mz-prose">
<p class="lead">İşbu Üye Aydınlatma Metni, web sitemiz üzerinden teklif başvurusu, iletişim formu doldurma veya kullanıcı kaydı yaptırma yoluyla bizlere ilettiğiniz kişisel verilerinizin işlenmesi süreçlerinde sizleri aydınlatmak amacıyla hazırlanmıştır.</p>

<h3>1. Hangi Verileriniz İşlenir?</h3>
<p>Web sitemizdeki form ve iletişim kanalları aracılığıyla:</p>
<ul>
<li>Ad, soyad, firma adı (kurumsal müşteriler için)</li>
<li>Telefon, e-posta, adres bilgileri</li>
<li>Sigorta talep konusu, açıklama, mesaj içerikleri</li>
<li>IP adresi ve oturum bilgisi (güvenlik amacıyla)</li>
</ul>

<h3>2. Hangi Amaçla İşlenir?</h3>
<ul>
<li>Sigorta teklifi hazırlanması ve sunulması,</li>
<li>Tarafınızla iletişim kurulması,</li>
<li>Mesajınıza/talebinize yanıt verilmesi,</li>
<li>Müşteri kaydı oluşturulması (talep ederseniz),</li>
<li>Yasal yükümlülüklerin yerine getirilmesi,</li>
<li>Bilgi güvenliği süreçlerinin yürütülmesi.</li>
</ul>

<h3>3. Hangi Hukuki Sebebe Dayanır?</h3>
<p>Verileriniz; sözleşmenin kurulması veya ifası için zorunlu olması (KVKK m.5/2-c), Mizan Sigorta&rsquo;nın meşru menfaatleri (KVKK m.5/2-f) ve açık rızanız (KVKK m.5/1) hukuki sebeplerine dayanılarak işlenir.</p>

<h3>4. Aktarılır mı?</h3>
<p>Verileriniz; teklif ürettiğimiz anlaşmalı sigorta şirketleri, yasal yükümlülükler kapsamında yetkili kamu kurumları ve iş süreçlerimizi yürüten hizmet tedarikçileri ile sınırlı olarak paylaşılabilir.</p>

<h3>5. Saklama Süresi</h3>
<p>Sigortacılık mevzuatı gereği genel saklama süresi 10 yıldır. Talep ettiğiniz hizmet sonuçlanmazsa veriler 1 yıl içinde silinir veya anonimleştirilir.</p>

<h3>6. Haklarınız</h3>
<p>KVKK&rsquo;nın 11. maddesi kapsamındaki tüm haklarınız (bilgi alma, düzeltme, silme vb.) için <strong>destek@mizansigorta.com.tr</strong> adresine başvurabilirsiniz. Detaylı bilgi için <a href="/sayfa/kvkk">KVKK Aydınlatma Metnimizi</a> inceleyiniz.</p>

<p class="text-muted small mt-4"><em>Son güncelleme tarihi: 2026</em></p>
</div>' WHERE `slug` = 'uye-aydinlatma' AND CHAR_LENGTH(`icerik`) < 500;
UPDATE `mz_sayfalar` SET `icerik` = '<div class="mz-prose">
<p class="lead">İşbu Açık Rıza Metni, Mizan Sigorta Aracılık Hizmetleri tarafından kişisel verilerinizin 6698 sayılı Kişisel Verilerin Korunması Kanunu (&ldquo;KVKK&rdquo;) kapsamında açık rızaya tabi olarak işlenmesi durumunda alınan onayınızı belgelemek üzere hazırlanmıştır.</p>

<h3>1. Açık Rıza Kapsamı</h3>
<p>Aşağıdaki işlemler için <strong>ayrı ayrı</strong> açık rıza alınmaktadır:</p>

<h5>a) Pazarlama ve Bilgilendirme İletişimi</h5>
<p>Tarafınıza:</p>
<ul>
<li>Sigorta ürünlerine ilişkin promosyon ve kampanya bilgileri,</li>
<li>Yeni ürün ve hizmet duyuruları,</li>
<li>Sektörel bilgilendirmeler ve haber bültenleri,</li>
<li>Anket ve memnuniyet ölçümü çağrıları</li>
</ul>
<p>için e-posta, SMS veya telefon yoluyla iletişim kurulmasına onay vermeniz halinde, söz konusu iletişim faaliyetleri yürütülecektir.</p>

<h5>b) Özel Nitelikli Kişisel Verilerin İşlenmesi</h5>
<p>Sağlık sigortası başvurularında, KVKK m.6 kapsamında özel nitelikli kişisel veri sayılan sağlık verilerinizin işlenmesi açık rızanıza tabidir.</p>

<h5>c) Veri Aktarımı</h5>
<p>Yurt içindeki anlaşmalı sigorta şirketlerine sözleşme kurulması ve ifası için aktarım sözleşme kapsamında değerlendirilse de; pazarlama amaçlı paylaşım için açık rıza alınır.</p>

<h3>2. Rızanın Geri Alınması</h3>
<p>Verdiğiniz açık rızayı dilediğiniz zaman, herhangi bir gerekçe göstermeden geri alabilirsiniz. Bu durumda:</p>
<ul>
<li>İlgili işleme faaliyeti durdurulur,</li>
<li>Pazarlama listelerinden çıkarılırsınız,</li>
<li>Geri alma tarihinden önceki yasal işlemler etkilenmez.</li>
</ul>

<h3>3. Rıza Geri Alma Yöntemleri</h3>
<ul>
<li><strong>E-posta:</strong> destek@mizansigorta.com.tr (&ldquo;Açık rızamı geri alıyorum&rdquo; konusu ile)</li>
<li><strong>Posta:</strong> Yukarıdaki adresimize ıslak imzalı dilekçe</li>
<li><strong>Pazarlama bültenleri için:</strong> E-postadaki &ldquo;abonelikten çık&rdquo; bağlantısı</li>
</ul>

<h3>4. Önemli Bilgiler</h3>
<ul>
<li>Açık rıza vermek <strong>tamamen isteğe bağlıdır</strong>; rıza vermemeniz hizmet alma hakkınızı etkilemez.</li>
<li>Rıza geri alındıktan sonra verileriniz, mevzuatın gerektirmediği sürece silinir veya anonim hale getirilir.</li>
<li>Rıza alınmasına rağmen, mevzuat gereği saklanması zorunlu olan veriler ilgili sürelerin sonuna kadar muhafaza edilir.</li>
</ul>

<h3>5. İletişim</h3>
<p>Açık Rıza Metni hakkında soru ve görüşleriniz için: <strong>destek@mizansigorta.com.tr</strong></p>

<p class="text-muted small mt-4"><em>Son güncelleme tarihi: 2026</em></p>
</div>' WHERE `slug` = 'acik-riza' AND CHAR_LENGTH(`icerik`) < 500;

-- ====================================================
-- v1.1.2 - 12+ Anlasmali sigorta sirketleri seed
-- ====================================================
INSERT IGNORE INTO `mz_sigorta_sirketleri` (`ad`, `web_sitesi`, `aciklama`, `aktif`) VALUES
('Anadolu Sigorta', 'https://www.anadolusigorta.com.tr', 'Türkiye''nin köklü ve güçlü sigorta şirketi.', 1),
('Allianz Sigorta', 'https://www.allianz.com.tr', 'Global ölçekte güvenilir Alman sigorta devi.', 1),
('AXA Sigorta', 'https://www.axasigorta.com.tr', 'Avrupa''nın önde gelen sigorta şirketlerinden.', 1),
('Türkiye Sigorta', 'https://www.turkiyesigorta.com.tr', 'Türkiye''nin yerli ve milli sigorta şirketi.', 1),
('HDI Sigorta', 'https://www.hdisigorta.com.tr', 'Köklü Alman sigorta grubu, Türkiye''de güvenilir hizmet.', 1),
('Quick Sigorta', 'https://www.quicksigorta.com', 'Hızlı ve dijital sigortacılığın öncüsü.', 1),
('Neova Sigorta', 'https://www.neova.com.tr', 'Yenilikçi sigorta çözümleri sunan kurum.', 1),
('Ak Sigorta', 'https://www.aksigorta.com.tr', 'Sabancı Holding bünyesinde köklü sigorta şirketi.', 1),
('Doğa Sigorta', 'https://www.dogasigorta.com', 'Türk sermayeli, çevre dostu sigorta yaklaşımı.', 1),
('Atlas Sigorta', 'https://www.atlassigorta.com', 'Ihracat/ithalat odaklı sigorta deneyimi.', 1),
('Corpus Sigorta', 'https://www.corpussigorta.com.tr', 'Kurumsal sigorta çözümlerinde deneyimli.', 1),
('Magdeburger Sigorta', 'https://www.magdeburger.com.tr', 'Köklü Alman kökenli sigorta deneyimi.', 1),
('Mapfre Sigorta', 'https://www.mapfre.com.tr', 'Global İspanyol kökenli sigorta grubu.', 1),
('Ray Sigorta', 'https://www.raysigorta.com.tr', 'Türk-Avusturya ortaklığı, geniş ürün yelpazesi.', 1),
('Sompo Sigorta', 'https://www.sompo.com.tr', 'Japon kökenli, kurumsal güçlü yapı.', 1);

-- ====================================================
-- v1.1.3 - UNIQUE CONSTRAINT'ler (kritik!)
-- Bu constraint'ler olmadigi icin INSERT IGNORE her seferinde
-- duplike yaratiyordu (mz_sss 196'ya, sirketler 30'a, kurallar 40'a kadar)
-- ====================================================

-- mz_sss: kategori+soru kombinasyonu UNIQUE olsun
-- Once duplikatlari temizle (en eski id kalsin)
DELETE s1 FROM `mz_sss` s1
INNER JOIN `mz_sss` s2
  ON s1.id > s2.id
  AND s1.kategori = s2.kategori
  AND s1.soru = s2.soru;
-- Yanlis encoding'li 'Police' kategorisini sil (dogru: 'Poliçe')
DELETE FROM `mz_sss` WHERE `kategori` = 'Police';
-- UNIQUE ekle (zaten varsa "Duplicate key name" -> migration runner yutar)
ALTER TABLE `mz_sss` ADD UNIQUE KEY `uk_kategori_soru` (`kategori`, `soru`(255));

-- mz_sigorta_sirketleri: ad UNIQUE olsun
DELETE s1 FROM `mz_sigorta_sirketleri` s1
INNER JOIN `mz_sigorta_sirketleri` s2 ON s1.id > s2.id AND s1.ad = s2.ad;
ALTER TABLE `mz_sigorta_sirketleri` ADD UNIQUE KEY `uk_ad` (`ad`);

-- mz_hatirlatma_kurallari: ad UNIQUE
DELETE k1 FROM `mz_hatirlatma_kurallari` k1
INNER JOIN `mz_hatirlatma_kurallari` k2 ON k1.id > k2.id AND k1.ad = k2.ad;
ALTER TABLE `mz_hatirlatma_kurallari` ADD UNIQUE KEY `uk_ad` (`ad`);

-- mz_urunler: Eski seed slug'larini sil (yeni hierarchy lehine)
DELETE FROM `mz_urunler` WHERE `slug` IN (
  'kasko-sigortasi', 'trafik-sigortasi', 'konut-sigortasi',
  'isyeri-sigortasi', 'saglik-sigortasi', 'hayat-sigortasi',
  'seyahat-sigortasi', 'tarim-sigortasi'
);

-- ====================================================
-- v1.1.4 - Tum urun aciklamalari + Blog + Referanslar
-- ====================================================

-- 1) URUN ACIKLAMALARINI DOLDUR (58 urun)

UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Aracınızı her türlü hasara karşı kapsamlı koruma altına alın. 12+ sigorta şirketinden alınacak en uygun teklifi bizimle keşfedin.</p><h3>Teminat Kapsamı</h3><ul><li>Çarpışma, devrilme, yangın, hırsızlık</li><li>Doğal afet teminatı</li><li>İkame araç</li><li>Cam ve mini onarım</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Hasarsızlık indirimi</li><li>3 farklı paket seçeneği</li><li>7/24 yol yardım</li></ul><h3>Kimler İçin Uygun?</h3><p>Aracını gerçek değeriyle korumak isteyen tüm sürücüler.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='kasko-sigortasi';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Yasal olarak zorunlu trafik sigortası — kanunun gerektirdiği tüm teminatları en uygun fiyatla.</p><h3>Teminat Kapsamı</h3><ul><li>Üçüncü kişilere maddi zarar</li><li>Bedeni zararlar</li><li>Manevi tazminat</li><li>Mahkeme giderleri</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Hasarsızlık basamağı indirimi</li><li>%65’e kadar indirim</li><li>Anında poliçe</li></ul><h3>Kimler İçin Uygun?</h3><p>Türkiye’de trafiğe çıkan tüm motorlu araç sahipleri için zorunlu.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='trafik-sigortasi';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Eviniz hayatınızın en değerli yatırımı. Konut Sigortası ile hem binanızı hem de eşyalarınızı yangın, deprem, sel, hırsızlık ve birçok riske karşı kapsamlı şekilde koruma altına alın.</p><h3>Teminat Kapsamı</h3><ul><li>Yangın, yıldırım, infilak</li><li>Deprem, sel, fırtına, dolu, yer kayması</li><li>Hırsızlık ve hırsızlığa teşebbüs</li><li>Cam kırılması</li><li>Su tesisat ve kanalizasyon hasarı</li><li>Komşuluk mali mesuliyet</li><li>Ferdi kaza teminatı</li><li>Hukuksal koruma</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Bina + eşya tek poliçede</li><li>Yeni değer / rayiç bedel seçimi</li><li>DASK ile birlikte yapıldığında ek indirim</li><li>7/24 asistans hizmetleri (tesisatçı, çilingir vb.)</li></ul><h3>Kimler İçin Uygun?</h3><p>Konut sahipleri ve kiracılar için. Kiracılar için "muhteviyat sigortası" yapılır.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='konut-sigortasi';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">İşletmenizin sürekliliği için kapsamlı işyeri sigortası paketleri. KOBİ’lerden büyük üreticilere kadar her ölçek için özel paketler sunuyoruz.</p><h3>Teminat Kapsamı</h3><ul><li>Yangın, doğal afet, hırsızlık</li><li>Cam kırılması, taşkın, sel</li><li>İş durması teminatı</li><li>Üçüncü şahıs mali sorumluluk</li><li>Emniyeti suiistimal</li><li>Mal ve nakit nakli</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Sektöre özel hazır paketler</li><li>KOBİ paketleri %30 indirimli</li><li>Yeniden inşa değeri seçeneği</li></ul><h3>Kimler İçin Uygun?</h3><p>Ofis, mağaza, restoran, fabrika, atölye, depo, klinik, eczane sahipleri için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='isyeri-sigortasi';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Sağlığınız için en uygun ürünü seçmenize yardımcı oluyoruz. Özel sağlık, tamamlayıcı sağlık ve yurt dışı seyahat sağlık ürünleri için 12+ şirketten anlık karşılaştırmalı teklif sunuyoruz.</p><h3>Teminat Kapsamı</h3><ul><li>Yatarak ve ayakta tedavi</li><li>Anlaşmalı 1.000+ hastane</li><li>Doğum, diş, gözlük seçenekleri</li><li>Aile paket indirimleri</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>12+ şirketten anlık teklif karşılaştırma</li><li>SGK + TSS ile ekonomik çözüm</li><li>Aile paketleri</li></ul><h3>Kimler İçin Uygun?</h3><p>Bireysel ve kurumsal sağlık sigortası ihtiyacı olan herkes.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='saglik-sigortasi';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Hayat Sigortası, sevdiklerinizin geleceğini güvence altına almanın en bilinçli yoludur. Vefat, sürekli sakatlık ve kritik hastalık durumlarında ailenize maddi destek sağlar.</p><h3>Teminat Kapsamı</h3><ul><li>Vefat teminatı</li><li>Sürekli tam ve kısmi sakatlık</li><li>Kritik hastalıklar (kanser, kalp krizi, felç)</li><li>Tehlikeli hastalıklar</li><li>Konut kredisi hayat sigortası</li><li>Birikim hayat sigortası</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Yıllık veya birikim seçenekleri</li><li>Vergi avantajı (BES gibi)</li><li>Konut kredisi için zorunlu</li><li>Aile için ekonomik koruma</li></ul><h3>Kimler İçin Uygun?</h3><p>Aile reisi, kredi alan kişiler, çocuklarının geleceğini garantilemek isteyen tüm bireyler için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='hayat-sigortasi';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Yurtiçi ve yurtdışı seyahatlerinizde ortaya çıkabilecek sağlık sorunları, bagaj kayıpları, uçuş gecikmeleri gibi durumlara karşı kapsamlı seyahat sigortası paketleri.</p><h3>Teminat Kapsamı</h3><ul><li>Acil tıbbi tedavi</li><li>Bagaj kaybı/gecikmesi</li><li>Uçuş iptali ve gecikmesi</li><li>Pasaport ve kişisel evrak kaybı</li><li>Hukuksal yardım</li><li>7/24 asistans</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Anında poliçe</li><li>Çoklu ülke paketleri (Avrupa, Dünya)</li><li>Yıllık çoklu seyahat seçeneği</li><li>Vize başvurularına uygun teminatlar</li></ul><h3>Kimler İçin Uygun?</h3><p>Tatil, iş seyahati, eğitim, sağlık turizmi gibi tüm seyahat amaçları için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='seyahat-sigortasi';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Ferdi Kaza Sigortası, gündelik hayatınızda karşılaşabileceğiniz beklenmedik kazalara karşı sizi ve sevdiklerinizi finansal olarak güvence altına alır.</p><h3>Teminat Kapsamı</h3><ul><li>Kaza sonucu vefat</li><li>Sürekli tam ve kısmi sakatlık</li><li>Geçici iş göremezlik</li><li>Tedavi giderleri</li><li>Hastane gündelik tazminat</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Düşük prim, yüksek teminat</li><li>Ailece kapsama imkanı</li><li>7/24 dünyada geçerli</li><li>İş yerinde, sporda, evde geçerli</li></ul><h3>Kimler İçin Uygun?</h3><p>Sporla ilgilenenler, riskli mesleklerde çalışanlar, çocuklar, aile reisleri için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='ferdi-kaza';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Çiftçilerimize özel devlet destekli tarım sigortaları (TARSİM) ile ürün ve hayvanlarınızı doğal afet ve hastalıklara karşı koruyun.</p><h3>Teminat Kapsamı</h3><ul><li>Bitkisel ürün, sera, hayvan hayat</li><li>Su ürünleri sigortası</li><li>TARSİM tüm ürünleri</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Devlet %50 prim desteği</li><li>Tek noktadan başvuru</li><li>Hızlı eksper hizmeti</li></ul><h3>Kimler İçin Uygun?</h3><p>Tarım ve hayvancılık yapan tüm üreticiler için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='tarim-sigortasi';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Nakliyat Sigortası hakkında detaylı bilgi ve teklif için Mizan Sigorta uzmanlarımızla iletişime geçin. 12+ anlaşmalı sigorta şirketinden size en uygun teklifi hazırlıyoruz.</p><h3>Teminat ve Detaylar</h3><p>Bu sigorta türü hakkında ihtiyaçlarınıza göre kapsamlı poliçe seçenekleri sunabiliyoruz. Detaylar için <a href="/iletisim">iletişim sayfamız</a> üzerinden bize ulaşabilirsiniz.</p><h3>Mizan Sigorta Farkı</h3><ul><li>12+ sigorta şirketinden anlık karşılaştırma</li><li>Profesyonel danışmanlık ve hasar süreç desteği</li><li>Yenileme dönemi otomatik takibi</li><li>7/24 müşteri hizmetleri</li></ul><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> Bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun.</div></div>' WHERE `slug`='nakliyat-sigortasi';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Sorumluluk Sigortaları hakkında detaylı bilgi ve teklif için Mizan Sigorta uzmanlarımızla iletişime geçin. 12+ anlaşmalı sigorta şirketinden size en uygun teklifi hazırlıyoruz.</p><h3>Teminat ve Detaylar</h3><p>Bu sigorta türü hakkında ihtiyaçlarınıza göre kapsamlı poliçe seçenekleri sunabiliyoruz. Detaylar için <a href="/iletisim">iletişim sayfamız</a> üzerinden bize ulaşabilirsiniz.</p><h3>Mizan Sigorta Farkı</h3><ul><li>12+ sigorta şirketinden anlık karşılaştırma</li><li>Profesyonel danışmanlık ve hasar süreç desteği</li><li>Yenileme dönemi otomatik takibi</li><li>7/24 müşteri hizmetleri</li></ul><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> Bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun.</div></div>' WHERE `slug`='sorumluluk-sigortasi';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Aracınız evinizden işinize, tatilinize, hayatınızın her anına eşlik eden değerli yatırımınız. Mizan Sigorta olarak Türkiye’nin önde gelen sigorta şirketleriyle çalışarak; kasko, trafik, İMM ve yeşil kart başta olmak üzere tüm oto sigorta ihtiyaçlarınızı tek çatı altında karşılıyoruz.</p><h3>Teminat Kapsamı</h3><ul><li>Kasko (Tam, Genişletilmiş, Dar)</li><li>Karayolları Zorunlu Trafik Sigortası</li><li>İhtiyari Mali Mesuliyet (İMM)</li><li>Yurt Dışı Çıkış Sigortası (Yeşil Kart)</li><li>Karayolu Yolcu Taşımacılığı Zorunlu Koltuk</li><li>Hasarsızlık koruma teminatı</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>12+ sigorta şirketinden tek formla teklif</li><li>Karşılaştırmalı en uygun fiyat garantisi</li><li>Hasarsızlık ve indirim takibi</li><li>Online poliçe yenileme ve takip imkanı</li></ul><h3>Kimler İçin Uygun?</h3><p>İster yeni araç sahibi olun, ister filo yöneticisi; bireysel ve kurumsal tüm araç sahipleri için kapsamlı oto sigorta paketleri sunuyoruz.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='oto-sigortalari';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Konut, işyeri ve ortak alanlarınızı yangın, doğal afet ve birçok riske karşı koruyan kapsamlı yangın sigorta paketlerimizle huzurla yaşayın, çalışın.</p><h3>Teminat Kapsamı</h3><ul><li>Konut sigortası (eşya + bina)</li><li>İşyeri yangın sigortası</li><li>Apartman ortak alan sigortası</li><li>DASK zorunlu deprem</li><li>Yangına ilişkin ek teminatlar</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Tek poliçeyle birden fazla risk teminatı</li><li>Eşya rayiç bedel/yeni değer seçenekleri</li><li>Bina + muhteviyat ayrı ayrı teminat</li></ul><h3>Kimler İçin Uygun?</h3><p>Konut, işyeri sahibi ve site/apartman yöneticileri için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='yangin-policeleri';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Sağlığınız en değerli varlığınız. Mizan Sigorta olarak özel sağlık, tamamlayıcı sağlık ve seyahat sağlık ürünlerinin tamamını sunuyor; bütçenize ve ihtiyacınıza göre en uygun çözümü öneriyoruz.</p><h3>Teminat Kapsamı</h3><ul><li>Özel Sağlık Sigortası</li><li>Tamamlayıcı Sağlık Sigortası (SGK ile birlikte)</li><li>Yurt Dışı Seyahat Sağlık Sigortası</li><li>Kritik hastalık sigortası</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>12+ şirketten anlık karşılaştırma</li><li>Aile paketi indirimleri</li><li>Anlaşmalı 1.000+ hastane ağı</li><li>Online sağlık raporu desteği</li></ul><h3>Kimler İçin Uygun?</h3><p>Bireysel, aile ve kurumsal grup sağlık sigortası ihtiyacı olan herkes için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='saglik-sigortalari';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">All Risk poliçeleri, sayılan risklerin dışında "her türlü ani ve beklenmedik" risklere karşı en kapsamlı koruma sağlar. İnşaat, montaj, elektronik cihaz ve makineler için ideal.</p><h3>Teminat Kapsamı</h3><ul><li>İnşaat All Risk (CAR)</li><li>Montaj All Risk (EAR)</li><li>Makine Kırılması</li><li>Elektronik Cihaz Sigortası</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>"Her riski kapsar" prensibi (istisnalar dışında)</li><li>Proje süresince koruma</li><li>Kredibilite ve teminat gerektiren projeler için ideal</li></ul><h3>Kimler İçin Uygun?</h3><p>İnşaat firmaları, müteahhitler, montaj projeleri yürüten firmalar, üretim tesisleri için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='all-riskler';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Nakliyat ve Tekne Sigortaları; karayolu, denizyolu, havayolu ve kombine taşımacılıkta yüklerinizi ve teknelerinizi her türlü riske karşı korur.</p><h3>Teminat Kapsamı</h3><ul><li>Emtea taşımacılığı</li><li>Tekne (Hull) ve Yat sigortası</li><li>Taşıyıcı sorumluluk</li><li>Tekne inşaat sigortası</li><li>Abonman poliçeleri</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Uluslararası klozlara uygun (ICC A/B/C)</li><li>Çoklu sefer paketleri</li><li>Detaylı taşıma rotası teminatı</li></ul><h3>Kimler İçin Uygun?</h3><p>İhracatçılar, ithalatçılar, lojistik firmaları, tekne sahipleri için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='nakliyat';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Sorumluluk Sigortaları, faaliyetleriniz sırasında üçüncü kişilere verebileceğiniz maddi/bedeni zararlardan kaynaklanan tazminat taleplerine karşı sizi korur.</p><h3>Teminat Kapsamı</h3><ul><li>İşveren mali sorumluluk</li><li>Üçüncü şahıs mali sorumluluk</li><li>Mesleki sorumluluk</li><li>Tehlikeli maddeler zorunlu sorumluluk</li><li>Özel güvenlik mali sorumluluk</li><li>Ürün sorumluluk</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Sektöre özel poliçeler</li><li>Yüksek teminat limitleri</li><li>Hukuki koruma kapsamı</li></ul><h3>Kimler İçin Uygun?</h3><p>İşverenler, profesyoneller (mühendis, doktor, avukat), perakendeciler, üreticiler için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='sorumluluk-sigortalari';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">TARSİM (Tarım Sigortaları Havuzu), 5363 sayılı Kanun kapsamında devletin %50’ye varan prim desteği sağladığı tarım sigortalarıdır. Çiftçimize özel ekonomik koruma.</p><h3>Teminat Kapsamı</h3><ul><li>Bitkisel ürün sigortası</li><li>Sera sigortası</li><li>Büyükbaş hayvan hayat</li><li>Küçükbaş hayvan hayat</li><li>Kümes hayvanları</li><li>Su ürünleri</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Devlet %50’ye varan prim desteği</li><li>Tek başvuru, geniş teminat</li><li>Yerinde hasar tespit</li></ul><h3>Kimler İçin Uygun?</h3><p>Tarım yapan tüm çiftçiler, ÇKS’ye kayıtlı üreticiler için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='tarsim';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Kefalet Sigortaları, banka teminat mektubu yerine geçen, ihale, kira, vergi, gümrük gibi alanlarda yükümlülüklerinizi karşılayan modern bir teminat çözümüdür.</p><h3>Teminat Kapsamı</h3><ul><li>İhale teminatı</li><li>Avans teminatı</li><li>Kesin teminat</li><li>KDV iadesi teminatı</li><li>Gümrük teminatı</li><li>Vergi/SGK borçları</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Banka teminat mektubuna göre %50’ye varan tasarruf</li><li>Bankada nakit bloke gerektirmez</li><li>Hızlı düzenleme</li></ul><h3>Kimler İçin Uygun?</h3><p>İhale alan firmalar, ithalatçılar, KDV iadesi alan ihracatçılar için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='kefalet-sigortalari';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Kasko sigortası, aracınızı çarpışma, yangın, hırsızlık, deprem, sel ve doğal afetler dahil her türlü riske karşı koruma altına alır. Trafik sigortasından farklı olarak kendi aracınızdaki hasarları da karşılar.</p><h3>Teminat Kapsamı</h3><ul><li>Çarpışma, çarpma, devrilme, düşme, yuvarlanma</li><li>Üçüncü kişilerin kötü niyetli hareketleri</li><li>Yangın, yıldırım, infilak</li><li>Hırsızlık ve hırsızlığa teşebbüs</li><li>Sel, deprem, terör, grev, halk hareketleri (genişletilmiş)</li><li>Cam kırılması teminatı</li><li>İkame araç teminatı (paketlere göre)</li><li>Asistans hizmetleri (çekici, lastik, akü)</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>3 farklı paket: Tam Kasko, Genişletilmiş, Dar Kasko</li><li>Hasarsızlık indirimi ile her yıl prim avantajı</li><li>Mini onarım indirimi (cam, vb.)</li><li>Anlaşmalı servis ağı</li></ul><h3>Kimler İçin Uygun?</h3><p>Aracını gerçek değeriyle koruma altına almak isteyen, finansal güvence arayan tüm araç sahipleri için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='kasko';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Karayolları Motorlu Araçlar Zorunlu Mali Sorumluluk Sigortası, bilinen adıyla Trafik Sigortası, 2918 sayılı Karayolları Trafik Kanunu ile zorunlu kılınmış bir sigortadır. Aracınızla üçüncü kişilere verebileceğiniz maddi ve bedeni zararları karşılar.</p><h3>Teminat Kapsamı</h3><ul><li>Üçüncü kişilerin maddi zararları</li><li>Bedeni zararlar (yaralanma, ölüm)</li><li>Manevi tazminat talepleri</li><li>Mahkeme ve avukat giderleri</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Hasarsızlık basamağı her yıl indirim sağlar (8 basamak %65 indirim)</li><li>Online poliçe çıktısı</li><li>7/24 hasar bildirim hattı</li></ul><h3>Kimler İçin Uygun?</h3><p>Türkiye’de trafiğe çıkan TÜM motorlu araç sahipleri için yasal olarak zorunludur.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='trafik-zorunlu-sorumluluk';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">İhtiyari Mali Mesuliyet (İMM) Sigortası, zorunlu trafik sigortasının teminat limitlerini aşan zararlar için ek teminat sağlar. Özellikle ağır kazalarda zorunlu trafik sigortası yetersiz kalabilir.</p><h3>Teminat Kapsamı</h3><ul><li>Trafik sigortası limit aşımı (10 milyon TL’ye kadar)</li><li>Üçüncü kişilere verilen ağır maddi/bedeni zararlar</li><li>Manevi tazminat talepleri</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Düşük prim ile yüksek teminat</li><li>Hukuki süreçlerde ek güvence</li><li>Aile bireylerine de uygulanabilir teminat seçenekleri</li></ul><h3>Kimler İçin Uygun?</h3><p>Lüks ve yüksek değerli araç sahipleri, ticari taşıt sahipleri, riski yüksek mesleklerde araç kullananlar için kritik öneme sahiptir.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='ihtiyari-mali-mesuliyet-imm';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Yeşil Kart Sigortası, aracınızla yurtdışına çıkacaksanız zorunlu olan, üye ülkelerin trafik sigortası niteliğindedir. Avrupa, Asya ve Kuzey Afrika’yı kapsayan 47 ülkede geçerlidir.</p><h3>Teminat Kapsamı</h3><ul><li>47 üye ülkede trafik sigortası geçerliliği</li><li>Üçüncü kişilere verilen maddi ve bedeni zararlar</li><li>Yerel mevzuata göre ödeme</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Yurtdışında zorunlu sigorta sorununu tek seferde çözer</li><li>15 gün, 1 ay, 3 ay, 1 yıl seçenekleri</li><li>Sınırda işlem kolaylığı</li></ul><h3>Kimler İçin Uygun?</h3><p>Aracıyla yurtdışına çıkacak tüm sürücüler — turistik, ticari ve özel amaçlı seyahatler için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='yesilkart';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">İşyeri Yangın Sigortası, ticari ve sanayi işletmenizi yangın, infilak, doğal afet, hırsızlık başta olmak üzere kapsamlı risk teminatına alır. İşinizin sürekliliği için temel güvence.</p><h3>Teminat Kapsamı</h3><ul><li>Bina, muhteviyat ve makine teçhizatı</li><li>Yangın, yıldırım, infilak</li><li>Sel, deprem, fırtına (ek teminat)</li><li>Hırsızlık, kasa hırsızlığı</li><li>Cam kırılması</li><li>İş durması teminatı</li><li>Üçüncü şahıs mali sorumluluk</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Tek poliçede 30+ teminat seçeneği</li><li>KOBİ paketleri ile düşük prim</li><li>Mali zarar güvencesi</li></ul><h3>Kimler İçin Uygun?</h3><p>Mağaza, ofis, fabrika, atölye, depo, restoran sahibi tüm işletmeler için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='isyeri-yangin-sigortasi';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">DASK (Doğal Afet Sigortaları Kurumu), 6305 sayılı Afet Sigortaları Kanunu ile zorunlu kılınmış olan deprem sigortasıdır. Bina hasarlarını teminat altına alır.</p><h3>Teminat Kapsamı</h3><ul><li>Deprem ve deprem sonucu yangın, infilak, tsunami</li><li>Bina yapı hasarları (1.272.000 TL’ye kadar)</li><li>Yıkım, çatlama, çökme</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Konut tapuları için zorunlu</li><li>Düşük prim ile yüksek koruma</li><li>Tüm Türkiye’de geçerli</li></ul><h3>Kimler İçin Uygun?</h3><p>634 sayılı Kat Mülkiyeti Kanunu kapsamındaki tüm meskenler ve ticari amaçlı kullanılan binalar için ZORUNLU. Tapu işlemlerinde DASK olmayan dairenin işlemleri yapılamaz.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='dask';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Apartman, site ve toplu konutların ortak alanlarına özel kapsamlı sigorta paketi. Yönetici sorumluluğunu da içeren bu paket modern site yönetiminin vazgeçilmezidir.</p><h3>Teminat Kapsamı</h3><ul><li>Ortak alan yangın, deprem, sel teminatı</li><li>Asansör kazaları</li><li>Cam kırılması</li><li>Yönetici mali sorumluluğu</li><li>Üçüncü şahıs sorumluluk</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Site yönetimini kapsamlı koruma</li><li>Maliyet kat maliklerine paylaştırılabilir</li><li>Hasar süreçlerinde profesyonel destek</li></ul><h3>Kimler İçin Uygun?</h3><p>Apartman yöneticileri ve site yönetim kurulları için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='ortak-alan-sigortasi';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Yurt Dışı Seyahat Sağlık Sigortası, yurtdışı seyahatlerinizde ortaya çıkabilecek sağlık sorunlarına karşı sizi güvence altına alır. Schengen vizesi başvurularında zorunludur.</p><h3>Teminat Kapsamı</h3><ul><li>Acil tıbbi tedavi giderleri</li><li>Acil diş tedavisi</li><li>Tıbbi tahliye ve naklin</li><li>Cenaze nakil giderleri</li><li>Bagaj kaybı (ek teminat)</li><li>Pasaport kaybı</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Schengen vize başvuruları için uygun</li><li>30.000 EUR’dan başlayan teminatlar</li><li>Anında poliçe çıktısı</li><li>Avrupa, Dünya seçenekleri</li></ul><h3>Kimler İçin Uygun?</h3><p>Tatil, iş seyahati, eğitim, sağlık turizmi gibi tüm yurtdışı seyahat amaçları için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='yurt-disi-seyahat-saglik';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Tamamlayıcı Sağlık Sigortası, SGK ile anlaşmalı özel hastanelerde size çıkacak fark ücretlerini karşılayan ekonomik bir sigorta türüdür. Düşük prim ile özel hastane konforunu sunar.</p><h3>Teminat Kapsamı</h3><ul><li>SGK anlaşmalı özel hastanelerde fark ücreti</li><li>Yatarak ve ayakta tedavi giderleri</li><li>Ameliyat ve yoğun bakım fark ücretleri</li><li>Ek imkan paketleri (doğum, diş, vb.)</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Özel sağlığa göre %60 daha ekonomik</li><li>SGK + TSS ile ücretsiz tedavi imkanı</li><li>Hızlı poliçe çıkışı</li><li>Aile paketi seçenekleri</li></ul><h3>Kimler İçin Uygun?</h3><p>SGK’lı çalışan veya emekli olup özel hastane fark ücretinden kurtulmak isteyenler için ideal.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='tamamlayici-saglik-sigortasi';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Özel Sağlık Sigortası, en kapsamlı sağlık sigortası ürünüdür. Hem yatarak hem ayakta tedavi giderlerinizi anlaşmalı özel hastanelerde karşılar. Bekleme süresi olmadan tüm sağlık hizmetlerine erişim sağlar.</p><h3>Teminat Kapsamı</h3><ul><li>Yatarak tedavi (ameliyat, yoğun bakım)</li><li>Ayakta tedavi (muayene, tahlil, görüntüleme)</li><li>İlaç teminatı</li><li>Doğum teminatı</li><li>Diş ve gözlük (ek teminat)</li><li>Check-up paketleri</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>SGK’dan bağımsız çalışır</li><li>Anlaşmalı hastane ağında nakit ödemesiz</li><li>Yenileme garantili paketler</li><li>Aile indirimleri</li></ul><h3>Kimler İçin Uygun?</h3><p>En kapsamlı sağlık güvencesi arayan, bekleme süresi yaşamak istemeyen, özel hastane konforu öncelikli kişiler için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='ozel-saglik-sigortasi';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">İnşaat All Risk (CAR) Sigortası, inşaat süresince meydana gelebilecek her türlü ani ve beklenmedik fiziksel hasara karşı kapsamlı koruma sağlar.</p><h3>Teminat Kapsamı</h3><ul><li>İnşaat süresince yangın, infilak, sel</li><li>Hırsızlık, vandalizm</li><li>Doğal afetler (deprem, fırtına vb.)</li><li>Üçüncü şahıs mali mesuliyet</li><li>İnşaat makinaları</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Tüm proje süresi boyunca tek poliçe</li><li>Banka ve finans kuruluşları teminat olarak kabul eder</li><li>Bakım dönemi de teminat altına alınabilir</li></ul><h3>Kimler İçin Uygun?</h3><p>Müteahhitler, gayrimenkul geliştiriciler, yapı kooperatifleri için zorunlu öneme sahip.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='insaat-all-risk';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Montaj All Risk (EAR) Sigortası, makine, ekipman ve tesisatların montaj sürecindeki riskleri kapsar. Endüstriyel projelerde temel güvencedir.</p><h3>Teminat Kapsamı</h3><ul><li>Montaj sırasında her türlü ani hasar</li><li>Makine devreye alma testleri</li><li>Doğal afetler</li><li>Üçüncü şahıs mali sorumluluk</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Endüstriyel proje finansmanlarında teminat olarak kabul</li><li>Bakım dönemini de kapsayabilir</li><li>Karmaşık montaj projeleri için ideal</li></ul><h3>Kimler İçin Uygun?</h3><p>Makine üreticileri, mühendislik firmaları, endüstriyel tesis kurulumcuları için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='montaj-all-risk';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Makine Kırılması Sigortası, üretim ve hizmet süreçlerinizde kullandığınız makinelerin ani ve beklenmedik mekanik arızalara karşı koruma altına alır.</p><h3>Teminat Kapsamı</h3><ul><li>Mekanik ve elektriksel arızalar</li><li>Operatör hataları</li><li>Dış etkiler (yabancı cisim girişi vb.)</li><li>Yedek parça ve onarım giderleri</li><li>İşletme kaybı (ek teminat)</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Üretim sürekliliğini garanti eder</li><li>Yüksek değerli makinelerin korunması</li><li>Teknik servis ağı</li></ul><h3>Kimler İçin Uygun?</h3><p>Sanayi tesisleri, üretim hatları, jeneratör/kompresör/CNC operatörleri için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='makine-kirilmasi';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Elektronik Cihaz Sigortası, ofis ve işyerlerindeki bilgisayar, sunucu, kamera sistemi gibi elektronik cihazlarınızı geniş risk yelpazesinde korur.</p><h3>Teminat Kapsamı</h3><ul><li>Yangın, sel, hırsızlık</li><li>Elektriksel ve mekanik arızalar</li><li>Operatör hataları</li><li>Veri kaybı (ek teminat)</li><li>Geçici cihaz kiralama</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Bilişim ekipmanları için özel paket</li><li>Düşük prim, yüksek teminat</li><li>Veri güvenliği opsiyonu</li></ul><h3>Kimler İçin Uygun?</h3><p>IT şirketleri, çağrı merkezleri, sunucu odaları, mağaza POS ve kamera sistemleri için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='elektronik-cihaz';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Tekne (Hull) Sigortası, ticari ve özel teknelerinizin gövdesini, makinasını ve teçhizatını her türlü deniz riskine karşı korur.</p><h3>Teminat Kapsamı</h3><ul><li>Karaya oturma, çarpışma, batma</li><li>Yangın, infilak, hırsızlık</li><li>Makine arızaları</li><li>Üçüncü şahıs mali sorumluluk</li><li>Kurtarma giderleri</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Deniz güzergahına özel teminat</li><li>Tekne değeri üzerinden poliçe</li><li>Yıllık veya seyrü sefer</li></ul><h3>Kimler İçin Uygun?</h3><p>Tekne sahipleri, balıkçı tekneleri, ticari yük gemisi sahipleri için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='tekne-hull';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Yat Sigortası, özel ve kiralık yatlar için kapsamlı sigorta paketidir. Türkiye sahillerinden Akdeniz’e kadar geniş seyir alanını kapsar.</p><h3>Teminat Kapsamı</h3><ul><li>Tekne gövdesi, makinesi</li><li>Yelken, tabela, ekipman</li><li>Üçüncü şahıs mali sorumluluk</li><li>Mürettebat ferdi kaza</li><li>Çekme ve kurtarma</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Marina ile entegre teminat</li><li>Charter ve özel kullanım seçenekleri</li><li>Akdeniz çapında geçerlilik</li></ul><h3>Kimler İçin Uygun?</h3><p>Yat sahipleri, kaptanlar, charter şirketleri için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='yat-sigortasi';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Nakliyat Emtea ve Abonman Sigortaları, ticari mal nakliyenizdeki riskleri uzun dönemli abonman avantajıyla teminat altına alır.</p><h3>Teminat Kapsamı</h3><ul><li>Karayolu, denizyolu, havayolu, demiryolu</li><li>ICC A/B/C kloz seçenekleri</li><li>Çalınma, hasar, kayıp</li><li>Abonman ile yıllık koruma</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Sefer başına bildirim kolaylığı</li><li>Yıllık abonmanlarda %40’a varan indirim</li><li>Online sefer beyanı</li></ul><h3>Kimler İçin Uygun?</h3><p>Düzenli ithalat/ihracat yapan firmalar, üreticiler, distribütörler için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='nakliyat-emtea-abonman';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Taşıyıcı Sorumluluk Sigortası (CMR), karayolu nakliyatında yük sahibine karşı taşıyıcı sıfatıyla doğacak sorumluluğunuzu güvence altına alır.</p><h3>Teminat Kapsamı</h3><ul><li>CMR sözleşmesi kapsamı</li><li>Yük zarar/ziyaı</li><li>Geç teslim</li><li>Üçüncü kişilere verilen zararlar</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Uluslararası taşımacılığa uygun</li><li>CMR konvansiyonu uyumlu</li><li>Yıllık kapsamlı koruma</li></ul><h3>Kimler İçin Uygun?</h3><p>TIR/kamyon firmaları, lojistik operatörler, freight forwarder’lar için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='tasiyici-sorumluluk';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Tekne İnşaat Sigortası, tersane veya inşa sahasında yapımı süren teknelerin inşa süresince oluşabilecek hasarlarını teminat altına alır.</p><h3>Teminat Kapsamı</h3><ul><li>Yangın, sel, hırsızlık</li><li>Montaj sırasında oluşan hasarlar</li><li>Yan sanayi katkıları</li><li>Suya indirme operasyonları</li><li>Üçüncü şahıs sorumluluk</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Tüm inşa süresi boyunca teminat</li><li>Banka kredisi teminatı olarak kabul</li><li>Test süreçleri kapsamında</li></ul><h3>Kimler İçin Uygun?</h3><p>Tersaneler, tekne yapımcıları, sipariş veren tekne sahipleri için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='tekne-insaat-sigortasi';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">İşveren Mali Sorumluluk Sigortası, çalışanlarınızın iş kazası geçirmesi durumunda işverenin yasal sorumluluğunu güvence altına alır.</p><h3>Teminat Kapsamı</h3><ul><li>İş kazası tazminatları</li><li>Meslek hastalığı tazminatları</li><li>SGK rücu davaları</li><li>Manevi tazminat talepleri</li><li>Mahkeme ve avukat masrafları</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>SGK rücu yükümlülüğüne karşı koruma</li><li>Yargılama süreçlerinde yasal destek</li><li>Çalışan başına teminat seçimi</li></ul><h3>Kimler İçin Uygun?</h3><p>Çalışanı olan tüm işverenler için kritik. Özellikle inşaat, üretim, ulaşım sektörleri için zorunlu.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='isveren-mali-sorumluluk';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Üçüncü Şahıs Mali Sorumluluk Sigortası, faaliyetleriniz sebebiyle müşteri, ziyaretçi gibi üçüncü kişilerin uğrayacağı zararları teminat altına alır.</p><h3>Teminat Kapsamı</h3><ul><li>Maddi zararlar (mal hasarı)</li><li>Bedeni zararlar (yaralanma, ölüm)</li><li>Manevi tazminat</li><li>Mahkeme giderleri</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>İşyeri ve ticari faaliyet için temel koruma</li><li>Yüksek limitlerde teminat</li><li>Sektöre özel paketler</li></ul><h3>Kimler İçin Uygun?</h3><p>Mağaza, otel, restoran, eğlence mekanı, etkinlik organizatörleri, eğitim kurumları için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='ucuncu-sahis-sorumluluk';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Tehlikeli Maddeler Zorunlu Sorumluluk Sigortası, LPG, akaryakıt gibi tehlikeli madde dolum/depolama tesislerinin yasal yükümlülüğüdür.</p><h3>Teminat Kapsamı</h3><ul><li>Yangın, patlama, infilak</li><li>Üçüncü kişi maddi/bedeni zararlar</li><li>Çevre kirliliği (ek teminat)</li><li>Manevi tazminat</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Yasal yükümlülüğü tek poliçe ile karşılar</li><li>Çevre tazminatları için ek teminat opsiyonu</li><li>EPDK uyumlu</li></ul><h3>Kimler İçin Uygun?</h3><p>Akaryakıt istasyonları, LPG dolum tesisleri, tehlikeli madde depo işletmecileri için ZORUNLU.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='tehlikeli-maddeler-sorumluluk';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Özel Güvenlik Mali Sorumluluk Sigortası, 5188 sayılı Özel Güvenlik Hizmetleri Kanunu kapsamında zorunlu kılınmış sigortadır.</p><h3>Teminat Kapsamı</h3><ul><li>Görev sırasında üçüncü kişilere verilen zararlar</li><li>Bedeni ve maddi zararlar</li><li>Manevi tazminat</li><li>Avukatlık ve mahkeme giderleri</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Yasal zorunluluğu karşılar</li><li>Personel başına teminat seçimi</li><li>EGM uyumlu</li></ul><h3>Kimler İçin Uygun?</h3><p>Özel güvenlik şirketleri ve hizmet alan kurumlar için ZORUNLU.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='ozel-guvenlik-mali-sorumluluk';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Mesleki Sorumluluk Sigortası, mesleki faaliyetinizden kaynaklanan ihmal, hata veya kusur sonucu üçüncü kişilerin uğrayacağı zararları teminat altına alır.</p><h3>Teminat Kapsamı</h3><ul><li>Mesleki ihmal ve hata tazminatları</li><li>Avukatlık ve dava masrafları</li><li>Mali kayıplar</li><li>Müşteri/hasta tazminatları</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Profesyonel itibarı korur</li><li>Hukuki süreçlerde uzman destek</li><li>Sektöre özel paketler (hekim, avukat, mühendis vb.)</li></ul><h3>Kimler İçin Uygun?</h3><p>Doktor, avukat, mühendis, mimar, mali müşavir, eczacı, mortgage uzmanı gibi profesyoneller için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='mesleki-sorumluluk';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Bitkisel Ürün Sigortası, dolu, fırtına, sel, don gibi doğal afetlerle bitkisel ürünlerinizde oluşacak verim kaybını TARSİM kapsamında karşılar.</p><h3>Teminat Kapsamı</h3><ul><li>Dolu, hortum, fırtına</li><li>Yangın, sel, taşkın</li><li>Don teminatı (meyve)</li><li>Heyelan, deprem</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Devlet %50 prim desteği</li><li>Verim kaybı bazlı tazminat</li><li>Hızlı eksper tespiti</li></ul><h3>Kimler İçin Uygun?</h3><p>Bitkisel üretim yapan çiftçiler, meyve bahçesi sahipleri, tahıl üreticileri için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='bitkisel-urun';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Sera Sigortası, sera yapı ve içerisindeki ürünlerinizi doğal afet, yangın, dolu ve don gibi risklere karşı TARSİM kapsamında korur.</p><h3>Teminat Kapsamı</h3><ul><li>Sera yapı hasarları</li><li>Sera içi ürün zararı</li><li>Yangın, dolu, fırtına</li><li>Don teminatı</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Yapı + ürün tek poliçe</li><li>Devlet prim desteği</li><li>Cam/plastik sera için ayrı tarife</li></ul><h3>Kimler İçin Uygun?</h3><p>Sera işletmecileri, modern tarım yatırımcıları için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='sera-sigortasi';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Küçükbaş Hayvan Hayat Sigortası, koyun, keçi gibi hayvanların TARSİM kapsamında hastalık ve kaza riskleri korur.</p><h3>Teminat Kapsamı</h3><ul><li>Hastalık vefat</li><li>Kaza ve çarpma</li><li>Salgın hastalıklar</li><li>Doğal afetler</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Devlet desteği</li><li>Sürü bazında poliçe</li><li>Hızlı işlem</li></ul><h3>Kimler İçin Uygun?</h3><p>Küçükbaş hayvancılık ile uğraşan üreticiler için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='kucukbas-hayvan-hayat';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Büyükbaş Hayvan Hayat Sigortası, sığır, manda gibi hayvanlarınızı hastalık, kaza, doğal afet sonucu vefatına karşı TARSİM’le korur.</p><h3>Teminat Kapsamı</h3><ul><li>Hastalık sonucu vefat</li><li>Kaza, çarpma, düşme</li><li>Doğum komplikasyonları</li><li>Yangın, sel, deprem</li><li>Hırsızlık (ek teminat)</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Devlet %50 prim desteği</li><li>Yüksek hayvan değerlerine yüksek teminat</li><li>Veteriner muayenesi sonrası poliçe</li></ul><h3>Kimler İçin Uygun?</h3><p>Süt sığırı, besi hayvanı yetiştiricileri için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='buyukbas-hayvan-hayat';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Kümes Hayvanları Hayat Sigortası, tavuk, hindi, ördek gibi kümes hayvanlarınızı hastalık ve doğal afetlere karşı TARSİM’le korur.</p><h3>Teminat Kapsamı</h3><ul><li>Salgın hastalıklar</li><li>Yangın, elektrik kesintisi</li><li>Havalandırma arızası</li><li>Doğal afetler</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Endüstriyel kümeslere özel paketler</li><li>Devlet prim desteği</li><li>Hızlı poliçe ve hasar süreci</li></ul><h3>Kimler İçin Uygun?</h3><p>Yumurtacı, etlik piliç işletmeleri için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='kumes-hayvanlari-hayat';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Su Ürünleri Sigortası, balık çiftliği gibi su ürünleri yetiştiriciliğinde stoklarınızı doğal ve hastalık risklerine karşı TARSİM’de korur.</p><h3>Teminat Kapsamı</h3><ul><li>Hastalık ve toplu ölüm</li><li>Su şartları (oksijen düşmesi vb.)</li><li>Doğal afetler</li><li>Predatör (yırtıcı) saldırıları</li></ul><h3>Mizan Sigorta Avantajları</h3><ul><li>Devlet %50 prim desteği</li><li>Stok bazında poliçe</li><li>Modern su ürünleri yatırımcıları için ideal</li></ul><h3>Kimler İçin Uygun?</h3><p>Balık çiftliği, alabalık üreticileri, kafes balıkçılığı için.</p><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> 12+ sigorta şirketinden bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun veya bize doğrudan ulaşın.</div></div>' WHERE `slug`='su-urunleri-sigortasi';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Arıcılık Sigortası hakkında detaylı bilgi ve teklif için Mizan Sigorta uzmanlarımızla iletişime geçin. 12+ anlaşmalı sigorta şirketinden size en uygun teklifi hazırlıyoruz.</p><h3>Teminat ve Detaylar</h3><p>Bu sigorta türü hakkında ihtiyaçlarınıza göre kapsamlı poliçe seçenekleri sunabiliyoruz. Detaylar için <a href="/iletisim">iletişim sayfamız</a> üzerinden bize ulaşabilirsiniz.</p><h3>Mizan Sigorta Farkı</h3><ul><li>12+ sigorta şirketinden anlık karşılaştırma</li><li>Profesyonel danışmanlık ve hasar süreç desteği</li><li>Yenileme dönemi otomatik takibi</li><li>7/24 müşteri hizmetleri</li></ul><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> Bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun.</div></div>' WHERE `slug`='aricilik-sigortasi';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Ferdi Kaza Sigortası hakkında detaylı bilgi ve teklif için Mizan Sigorta uzmanlarımızla iletişime geçin. 12+ anlaşmalı sigorta şirketinden size en uygun teklifi hazırlıyoruz.</p><h3>Teminat ve Detaylar</h3><p>Bu sigorta türü hakkında ihtiyaçlarınıza göre kapsamlı poliçe seçenekleri sunabiliyoruz. Detaylar için <a href="/iletisim">iletişim sayfamız</a> üzerinden bize ulaşabilirsiniz.</p><h3>Mizan Sigorta Farkı</h3><ul><li>12+ sigorta şirketinden anlık karşılaştırma</li><li>Profesyonel danışmanlık ve hasar süreç desteği</li><li>Yenileme dönemi otomatik takibi</li><li>7/24 müşteri hizmetleri</li></ul><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> Bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun.</div></div>' WHERE `slug`='ferdi-kaza-sigortasi';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Deprem Destek Sigortası hakkında detaylı bilgi ve teklif için Mizan Sigorta uzmanlarımızla iletişime geçin. 12+ anlaşmalı sigorta şirketinden size en uygun teklifi hazırlıyoruz.</p><h3>Teminat ve Detaylar</h3><p>Bu sigorta türü hakkında ihtiyaçlarınıza göre kapsamlı poliçe seçenekleri sunabiliyoruz. Detaylar için <a href="/iletisim">iletişim sayfamız</a> üzerinden bize ulaşabilirsiniz.</p><h3>Mizan Sigorta Farkı</h3><ul><li>12+ sigorta şirketinden anlık karşılaştırma</li><li>Profesyonel danışmanlık ve hasar süreç desteği</li><li>Yenileme dönemi otomatik takibi</li><li>7/24 müşteri hizmetleri</li></ul><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> Bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun.</div></div>' WHERE `slug`='deprem-destek-sigortasi';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Kritik Hastalıklar hakkında detaylı bilgi ve teklif için Mizan Sigorta uzmanlarımızla iletişime geçin. 12+ anlaşmalı sigorta şirketinden size en uygun teklifi hazırlıyoruz.</p><h3>Teminat ve Detaylar</h3><p>Bu sigorta türü hakkında ihtiyaçlarınıza göre kapsamlı poliçe seçenekleri sunabiliyoruz. Detaylar için <a href="/iletisim">iletişim sayfamız</a> üzerinden bize ulaşabilirsiniz.</p><h3>Mizan Sigorta Farkı</h3><ul><li>12+ sigorta şirketinden anlık karşılaştırma</li><li>Profesyonel danışmanlık ve hasar süreç desteği</li><li>Yenileme dönemi otomatik takibi</li><li>7/24 müşteri hizmetleri</li></ul><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> Bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun.</div></div>' WHERE `slug`='kritik-hastaliklar';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Kefalet Senedi hakkında detaylı bilgi ve teklif için Mizan Sigorta uzmanlarımızla iletişime geçin. 12+ anlaşmalı sigorta şirketinden size en uygun teklifi hazırlıyoruz.</p><h3>Teminat ve Detaylar</h3><p>Bu sigorta türü hakkında ihtiyaçlarınıza göre kapsamlı poliçe seçenekleri sunabiliyoruz. Detaylar için <a href="/iletisim">iletişim sayfamız</a> üzerinden bize ulaşabilirsiniz.</p><h3>Mizan Sigorta Farkı</h3><ul><li>12+ sigorta şirketinden anlık karşılaştırma</li><li>Profesyonel danışmanlık ve hasar süreç desteği</li><li>Yenileme dönemi otomatik takibi</li><li>7/24 müşteri hizmetleri</li></ul><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> Bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun.</div></div>' WHERE `slug`='kefalet-senedi';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">KDV İadesi hakkında detaylı bilgi ve teklif için Mizan Sigorta uzmanlarımızla iletişime geçin. 12+ anlaşmalı sigorta şirketinden size en uygun teklifi hazırlıyoruz.</p><h3>Teminat ve Detaylar</h3><p>Bu sigorta türü hakkında ihtiyaçlarınıza göre kapsamlı poliçe seçenekleri sunabiliyoruz. Detaylar için <a href="/iletisim">iletişim sayfamız</a> üzerinden bize ulaşabilirsiniz.</p><h3>Mizan Sigorta Farkı</h3><ul><li>12+ sigorta şirketinden anlık karşılaştırma</li><li>Profesyonel danışmanlık ve hasar süreç desteği</li><li>Yenileme dönemi otomatik takibi</li><li>7/24 müşteri hizmetleri</li></ul><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> Bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun.</div></div>' WHERE `slug`='kdv-iadesi';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Devlet Destekli Alacak Sigorta hakkında detaylı bilgi ve teklif için Mizan Sigorta uzmanlarımızla iletişime geçin. 12+ anlaşmalı sigorta şirketinden size en uygun teklifi hazırlıyoruz.</p><h3>Teminat ve Detaylar</h3><p>Bu sigorta türü hakkında ihtiyaçlarınıza göre kapsamlı poliçe seçenekleri sunabiliyoruz. Detaylar için <a href="/iletisim">iletişim sayfamız</a> üzerinden bize ulaşabilirsiniz.</p><h3>Mizan Sigorta Farkı</h3><ul><li>12+ sigorta şirketinden anlık karşılaştırma</li><li>Profesyonel danışmanlık ve hasar süreç desteği</li><li>Yenileme dönemi otomatik takibi</li><li>7/24 müşteri hizmetleri</li></ul><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> Bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun.</div></div>' WHERE `slug`='devlet-destekli-alacak-sigorta';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Bina Tamamlama Sigortası hakkında detaylı bilgi ve teklif için Mizan Sigorta uzmanlarımızla iletişime geçin. 12+ anlaşmalı sigorta şirketinden size en uygun teklifi hazırlıyoruz.</p><h3>Teminat ve Detaylar</h3><p>Bu sigorta türü hakkında ihtiyaçlarınıza göre kapsamlı poliçe seçenekleri sunabiliyoruz. Detaylar için <a href="/iletisim">iletişim sayfamız</a> üzerinden bize ulaşabilirsiniz.</p><h3>Mizan Sigorta Farkı</h3><ul><li>12+ sigorta şirketinden anlık karşılaştırma</li><li>Profesyonel danışmanlık ve hasar süreç desteği</li><li>Yenileme dönemi otomatik takibi</li><li>7/24 müşteri hizmetleri</li></ul><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> Bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun.</div></div>' WHERE `slug`='bina-tamamlama-sigortasi';
UPDATE `mz_urunler` SET `aciklama`='<div class="mz-prose"><p class="lead">Lisanslı Depoculuk hakkında detaylı bilgi ve teklif için Mizan Sigorta uzmanlarımızla iletişime geçin. 12+ anlaşmalı sigorta şirketinden size en uygun teklifi hazırlıyoruz.</p><h3>Teminat ve Detaylar</h3><p>Bu sigorta türü hakkında ihtiyaçlarınıza göre kapsamlı poliçe seçenekleri sunabiliyoruz. Detaylar için <a href="/iletisim">iletişim sayfamız</a> üzerinden bize ulaşabilirsiniz.</p><h3>Mizan Sigorta Farkı</h3><ul><li>12+ sigorta şirketinden anlık karşılaştırma</li><li>Profesyonel danışmanlık ve hasar süreç desteği</li><li>Yenileme dönemi otomatik takibi</li><li>7/24 müşteri hizmetleri</li></ul><div class="alert alert-warning mt-4 mb-0"><strong><i class="bi bi-stars"></i> Hızlı Teklif:</strong> Bu ürün için en uygun fiyat teklifini almak için <a href="/teklif-al" class="alert-link">teklif sihirbazımıza</a> başvurun.</div></div>' WHERE `slug`='lisansli-depoculuk';

-- 2) BLOG YAZILARI SEED (8 SEO-uyumlu yazi)

INSERT IGNORE INTO `mz_blog` (`slug`, `baslik`, `ozet`, `icerik`, `kategori`, `etiketler`, `seo_baslik`, `seo_aciklama`, `yayin_tarihi`, `aktif`) VALUES
('kasko-trafik-sigortasi-arasindaki-fark-nedir', 'Kasko ve Trafik Sigortası Arasındaki Fark Nedir?', 'Aracınızı korumak için kasko mu yoksa trafik sigortası mı yeterli? Bu yazıda iki sigorta arasındaki temel farkları, hangisinin neyi kapsadığını detaylı olarak inceliyoruz.', '<p class="lead">Aracınız için sigorta seçerken karşınıza çıkan iki temel ürün vardır: kasko ve trafik sigortası. Her ikisi de aracınızı korur ama tamamen farklı amaçlarla.</p>
<h2>Trafik Sigortası: Yasal Zorunluluk</h2>
<p>Karayolları Motorlu Araçlar Zorunlu Mali Sorumluluk Sigortası, kısaca trafik sigortası, 2918 sayılı Karayolları Trafik Kanunu ile zorunlu kılınmıştır. Türkiye’de trafiğe çıkan tüm motorlu araçların yapması gereken sigortadır.</p>
<h3>Trafik Sigortası Neyi Kapsar?</h3>
<ul><li>Aracınızla üçüncü kişilere verdiğiniz maddi zararları</li><li>Üçüncü kişilerin yaralanma ve ölüm gibi bedeni zararlarını</li><li>Manevi tazminat taleplerini</li><li>Mahkeme ve avukat masraflarını</li></ul>
<p><strong>ÖNEMLİ:</strong> Trafik sigortası KENDİ aracınızdaki hasarları KARŞILAMAZ. Sadece karşı tarafa verdiğiniz zararı öder.</p>
<h2>Kasko: Aracınızı Korumak İçin</h2>
<p>Kasko sigortası ihtiyaridir, yani yapılması zorunlu değildir. Ancak aracınız için en kapsamlı korumayı sunar. Kendi aracınızdaki hasarları, hırsızlığı, doğal afetleri ve birçok başka riski karşılar.</p>
<h3>Kasko Sigortası Neyi Kapsar?</h3>
<ul><li>Çarpışma, devrilme, düşme, yuvarlanma sonucu hasarlar</li><li>Yangın, infilak, hırsızlık, gasp</li><li>Sel, deprem, fırtına gibi doğal afetler (genişletilmiş)</li><li>Üçüncü kişilerin kötü niyetli hareketleri</li><li>Cam kırılması ve mini onarım</li><li>İkame araç hizmeti (paketlere göre)</li></ul>
<h2>Hangisini Yapmalıyım?</h2>
<p>Aslında ideal olan her ikisini de yapmaktır. Trafik sigortası yasal zorunluluğunuzu karşılarken, kasko aracınızın değerini koruma altına alır. Özellikle:</p>
<ul><li>Aracınız 5 yaşından küçükse veya değerli bir araç ise mutlaka kasko yapın</li><li>Kredili araç sahipleri için banka kasko zorunluluğu olabilir</li><li>Sıkça uzun yolculuk yapıyorsanız genişletilmiş kasko düşünün</li></ul>
<p>Mizan Sigorta olarak 12+ sigorta şirketinden hem trafik hem de kasko için en uygun teklifleri sunuyoruz. <a href="/teklif-al">Hızlı teklif</a> alabilirsiniz.</p>', 'Oto Sigortası', 'kasko, trafik sigortası, oto sigortası, fark, karşılaştırma', 'Kasko ve Trafik Sigortası Arasındaki Fark Nedir? - Mizan Sigorta', 'Aracınızı korumak için kasko mu yoksa trafik sigortası mı yeterli? Bu yazıda iki sigorta arasındaki temel farkları, hangisinin neyi kapsadığını detaylı olarak inceliyoruz.', NOW(), 1);

INSERT IGNORE INTO `mz_blog` (`slug`, `baslik`, `ozet`, `icerik`, `kategori`, `etiketler`, `seo_baslik`, `seo_aciklama`, `yayin_tarihi`, `aktif`) VALUES
('dask-zorunlu-mu-cezasi-nedir', 'DASK Zorunlu mu? Yaptırmamanın Cezası Var mı?', 'DASK (Doğal Afet Sigortaları Kurumu) zorunlu deprem sigortası kimler için zorunlu, hangi durumlarda yaptırılması şart? Tapu işlemlerinde DASK’ın rolünü inceliyoruz.', '<p class="lead">DASK, 6305 sayılı Afet Sigortaları Kanunu ile getirilen zorunlu bir sigorta türüdür. Türkiye’nin deprem coğrafyasında yer alması nedeniyle her konut için yapılması yasal yükümlülüktür.</p>
<h2>DASK Kimler İçin Zorunlu?</h2>
<p>634 sayılı Kat Mülkiyeti Kanunu kapsamındaki tüm meskenler ve ticari amaçlı kullanılan binalar için zorunludur. Bu kapsamda:</p>
<ul><li>Konut olarak kullanılan tüm bağımsız bölümler</li><li>Apartman daireleri ve müstakil evler</li><li>Site ve toplu konut projelerindeki konutlar</li><li>Ticari amaçlı kullanılan binalar (dükkân, ofis vb.)</li></ul>
<h3>İstisnalar Nelerdir?</h3>
<p>Aşağıdaki yapılar DASK kapsamı dışındadır:</p>
<ul><li>Köy yerleşim alanlarında yapılan binalar</li><li>Kamu kurumlarına ait binalar</li><li>9/11/1983 tarihli yapı kullanım izinli olmayan kaçak yapılar</li></ul>
<h2>DASK Olmadan Tapu İşlemi Yapılır mı?</h2>
<p><strong>Hayır.</strong> Tapu Müdürlüğü, alım-satım, ipotek, intikal gibi tapu işlemlerinde DASK poliçesini ister. DASK olmayan dairenin tapu işlemleri yapılamaz.</p>
<p>Ayrıca elektrik, su gibi abonelik işlemlerinde de DASK poliçesi istenir.</p>
<h2>DASK Primi Nasıl Hesaplanır?</h2>
<p>DASK priminizi şu kriterler belirler:</p>
<ul><li><strong>Konum:</strong> Bulunduğunuz deprem bölgesi (1, 2, 3, 4, 5)</li><li><strong>Yapı tipi:</strong> Betonarme, yığma, ahşap vb.</li><li><strong>Brüt yüzölçümü:</strong> Konutun metrekaresi</li><li><strong>Yapı yaşı:</strong> Yapım tarihi</li></ul>
<p>Türkiye’nin yüksek riskli illerinde 100m² betonarme bir daire için yıllık prim ortalama 700-1.500 TL arasındadır.</p>
<h2>DASK ile Konut Sigortası Aynı mı?</h2>
<p>Hayır. DASK SADECE bina hasarlarını ve sadece deprem (ve sonrası yangın, infilak, tsunami) riskini kapsar. Eşyalarınız, yangın, sel, hırsızlık DASK kapsamında değildir. Bunlar için ayrıca <a href="/urun/konut-sigortasi">Konut Sigortası</a> yaptırılmalıdır.</p>
<p>Mizan Sigorta olarak DASK + Konut Sigortası birlikte yapıldığında özel indirimler sunuyoruz. <a href="/teklif-al">Teklif almak için tıklayın</a>.</p>', 'Konut Sigortası', 'dask, zorunlu deprem, konut sigortası, tapu, prim', 'DASK Zorunlu mu? Yaptırmamanın Cezası Var mı? - Mizan Sigorta', 'DASK (Doğal Afet Sigortaları Kurumu) zorunlu deprem sigortası kimler için zorunlu, hangi durumlarda yaptırılması şart? Tapu işlemlerinde DASK’ın rolünü inceliyoruz.', NOW(), 1);

INSERT IGNORE INTO `mz_blog` (`slug`, `baslik`, `ozet`, `icerik`, `kategori`, `etiketler`, `seo_baslik`, `seo_aciklama`, `yayin_tarihi`, `aktif`) VALUES
('tamamlayici-saglik-sigortasi-nedir', 'Tamamlayıcı Sağlık Sigortası Nedir? Özel Sağlıktan Farkı?', 'SGK’lı çalışanlar için ekonomik özel sağlık çözümü olan tamamlayıcı sağlık sigortasını tanıyalım. Özel sağlık sigortasından farkları, avantajları ve kimler için uygun olduğunu anlatıyoruz.', '<p class="lead">Tamamlayıcı Sağlık Sigortası (TSS), SGK kapsamında olduğunuz halde, SGK anlaşmalı özel hastanelerde size çıkacak fark ücretini karşılayan ekonomik bir sigortadır.</p>
<h2>Tamamlayıcı Sağlık Nedir?</h2>
<p>SGK’lı bir çalışan olarak özel hastanelerde tedavi gördüğünüzde, hastane size SGK’nın ödediğinden fazla ücret talep eder. Bu fark "katılım payı" olarak adlandırılır. TSS bu fark ücretlerini karşılar.</p>
<p>Yani SGK + TSS kombinasyonu ile özel hastanede ücretsiz tedavi imkanı doğar.</p>
<h2>Özel Sağlık Sigortasından Farkları</h2>
<table class="table table-bordered"><thead><tr><th>Özellik</th><th>Tamamlayıcı Sağlık</th><th>Özel Sağlık</th></tr></thead><tbody>
<tr><td>SGK gerekli mi?</td><td>Evet, zorunlu</td><td>Hayır, gerekli değil</td></tr>
<tr><td>Bekleme süresi</td><td>Yok (acil)</td><td>Genellikle 30 gün</td></tr>
<tr><td>Aylık prim</td><td>Çok ekonomik</td><td>Daha yüksek</td></tr>
<tr><td>Hastane ağı</td><td>SGK anlaşmalı özel hastaneler</td><td>Sigorta anlaşmalı özel hastaneler</td></tr>
<tr><td>Doğum teminatı</td><td>SGK kapsamında</td><td>Geniş kapsam</td></tr>
</tbody></table>
<h2>Hangisi Sizin İçin?</h2>
<ul><li><strong>SGK’lısınız ve özel hastane konforu istiyorsunuz:</strong> Tamamlayıcı sağlık idealdir.</li><li><strong>SGK’nız yok veya bağımsız çalışıyorsunuz:</strong> Özel sağlık sigortası gereklidir.</li><li><strong>Geniş kapsam ve yurtdışı tedavi istiyorsanız:</strong> Özel sağlık daha uygundur.</li><li><strong>Düşük prim ile özel hastane konforu istiyorsanız:</strong> Tamamlayıcı sağlık avantajlıdır.</li></ul>
<h2>Aile Paketi Var mı?</h2>
<p>Evet. Eş ve çocukları kapsayan aile paketleri mevcut. Her bireyin SGK’sı varsa hepsi için TSS yapılabilir.</p>
<h2>Hangi Hastanelerde Geçerli?</h2>
<p>SGK ile anlaşmalı özel hastanelerin neredeyse tamamında geçerlidir. Türkiye’de 600+ özel hastane SGK ile anlaşmalıdır.</p>
<p>Mizan Sigorta olarak 8+ farklı sigorta şirketinin tamamlayıcı sağlık ürünlerini karşılaştırmalı olarak sunuyoruz. <a href="/teklif-al">Hızlı teklif alın</a>.</p>', 'Sağlık Sigortası', 'tamamlayici saglik, sgk, ozel saglik, fark ucreti', 'Tamamlayıcı Sağlık Sigortası Nedir? Özel Sağlıktan Farkı? - Mizan Sigorta', 'SGK’lı çalışanlar için ekonomik özel sağlık çözümü olan tamamlayıcı sağlık sigortasını tanıyalım. Özel sağlık sigortasından farkları, avantajları ve kimler için uygun olduğunu anlatıyoruz.', NOW(), 1);

INSERT IGNORE INTO `mz_blog` (`slug`, `baslik`, `ozet`, `icerik`, `kategori`, `etiketler`, `seo_baslik`, `seo_aciklama`, `yayin_tarihi`, `aktif`) VALUES
('is-yeri-sigortasi-neden-onemli', 'İşyeri Sigortası: KOBİ’ler İçin Vazgeçilmez Güvence', 'Bir yangın, sel veya hırsızlık olayı işletmenizi geri dönüşü olmayan zarara uğratabilir. İşyeri sigortasının önemini, kapsamını ve KOBİ’ler için neden vazgeçilmez olduğunu inceliyoruz.', '<p class="lead">Türkiye’de KOBİ’lerin sadece %30’u işyeri sigortası yaptırıyor. Oysa bir gece çıkacak yangın bir ömürlük emeği yok edebilir. İşyeri sigortası bir maliyet değil, işletmenizin hayat sigortasıdır.</p>
<h2>Neden İşyeri Sigortası Yaptırmalıyım?</h2>
<p>İstatistikler endişe verici:</p>
<ul><li>Türkiye’de yıllık ortalama 200.000+ yangın olayı yaşanır</li><li>İşyerlerinin %15’i çeşitli zamanlarda hırsızlık olayına maruz kalır</li><li>Sel ve doğal afetlerden ciddi maddi kayıplar yaşanır</li><li>Bir kez büyük hasar yaşayan KOBİ’lerin %43’ü kapanır veya küçülür</li></ul>
<h2>Standart Bir İşyeri Sigortası Neleri Kapsar?</h2>
<h3>Temel Teminatlar</h3>
<ul><li><strong>Yangın, yıldırım, infilak:</strong> En temel risk</li><li><strong>Hırsızlık ve hırsızlığa teşebbüs:</strong> Mağaza ve depolar için kritik</li><li><strong>Sel, deprem, fırtına:</strong> Doğal afetler</li><li><strong>Cam kırılması:</strong> Vitrin sigortası</li><li><strong>Kasa ve nakit hırsızlığı</strong></li></ul>
<h3>Genişletilmiş Teminatlar</h3>
<ul><li><strong>İş durması teminatı:</strong> Hasar nedeniyle kapanırsanız aylık gelir kaybı karşılanır</li><li><strong>Üçüncü şahıs mali sorumluluk:</strong> Müşterilere karşı sorumluluk</li><li><strong>Emniyeti suiistimal:</strong> Çalışan kaynaklı zararlar</li><li><strong>Mal ve para nakli:</strong> Banka ile işyeri arası</li></ul>
<h2>Sektöre Özel Paketler</h2>
<p>Her sektörün riskleri farklıdır. Sigorta paketleri sektörünüze göre özelleştirilebilir:</p>
<ul><li><strong>Mağaza/Perakende:</strong> Hırsızlık, vitrin, müşteri sorumluluğu öne çıkar</li><li><strong>Restoran/Kafe:</strong> Yangın, gıda zehirlenmesi, mali sorumluluk</li><li><strong>Üretim/Fabrika:</strong> Makine kırılması, iş durması, depolanan mal</li><li><strong>Ofis/Hizmet:</strong> Elektronik cihaz, mesleki sorumluluk</li></ul>
<h2>Prim Tutarı Ne Kadar?</h2>
<p>İşyeri sigortası primi şu faktörlere göre değişir:</p>
<ul><li>İşyerinin tipi ve büyüklüğü</li><li>Sigortalanacak değer (bina + muhteviyat)</li><li>Bulunduğunuz bölgenin risk durumu</li><li>Seçtiğiniz teminat kapsamı</li></ul>
<p>Genel olarak 100 m² bir mağaza için yıllık prim 1.500-5.000 TL aralığındadır. Yıl içinde olabilecek bir hasarın maliyeti milyonlarca TL’yi bulabilir.</p>
<p>Mizan Sigorta olarak işyeriniz için en uygun paketi 12+ sigorta şirketinden karşılaştırmalı olarak sunuyoruz. <a href="/teklif-al">Hızlı teklif</a> alabilirsiniz.</p>', 'İşyeri Sigortası', 'isyeri sigortasi, kobi, yangin, hirsizlik, is durmasi', 'İşyeri Sigortası: KOBİ’ler İçin Vazgeçilmez Güvence - Mizan Sigorta', 'Bir yangın, sel veya hırsızlık olayı işletmenizi geri dönüşü olmayan zarara uğratabilir. İşyeri sigortasının önemini, kapsamını ve KOBİ’ler için neden vazgeçilmez olduğunu inceliyoruz.', NOW(), 1);

INSERT IGNORE INTO `mz_blog` (`slug`, `baslik`, `ozet`, `icerik`, `kategori`, `etiketler`, `seo_baslik`, `seo_aciklama`, `yayin_tarihi`, `aktif`) VALUES
('sigorta-acentelik-haklari-ve-avantajlari', 'Sigorta Acentesi mi, Doğrudan Sigorta Şirketi mi?', 'Sigorta yaptırırken acente üzerinden mi yoksa doğrudan sigorta şirketinden mi yaptırmak daha avantajlı? Acente avantajlarını ve sıkça sorulan soruları yanıtlıyoruz.', '<p class="lead">Sigorta yaptırırken sıkça sorulan bir soru: "Acente üzerinden mi yoksa doğrudan şirketten mi yaptırmak daha iyi?" Cevap, çoğu kişinin bilmediği bir gerçeği barındırıyor.</p>
<h2>Acente Üzerinden Daha Pahalı mı?</h2>
<p>Çok yaygın bir yanılgıdır: "Aracı koymadan direkt yaparsam daha ucuza yaparım." <strong>Bu kesinlikle YANLIŞTIR.</strong></p>
<p>Sigorta tarifeleri Hazine ve Maliye Bakanlığı tarafından düzenlenir. Her sigorta şirketi için poliçe fiyatı aynıdır - acente üzerinden veya doğrudan şirketten alın, fark etmez. Acente komisyonu sigorta şirketi tarafından ödenir, müşteriden değil.</p>
<h2>Acentenin Size Sağladığı Avantajlar</h2>
<h3>1. Çoklu Şirket Karşılaştırması</h3>
<p>Tek bir sigorta şirketinin web sitesinde sadece o şirketin teklifini görürsünüz. Mizan Sigorta gibi çok şirketli acenteler size 12+ farklı sigorta şirketinden anlık karşılaştırmalı teklif sunar. Aynı kasko için bir şirket 5.000 TL teklif ederken diğeri 3.500 TL teklif edebilir.</p>
<h3>2. Bağımsız Danışmanlık</h3>
<p>Sigorta şirketi çağrı merkezi sadece kendi ürününü pazarlar. Acente ise sizin için en uygun çözümü bulmak adına bağımsız çalışır. Hangi teminatın size gerekli olduğunu, hangisinin gereksiz olduğunu objektif olarak değerlendirir.</p>
<h3>3. Hasar Sürecinde Profesyonel Destek</h3>
<p>Hasar yaşadığınızda sigorta şirketi ile yalnız uğraşmazsınız. Acenteniz sürecin başından sonuna sizi temsil eder, eksper ile koordinasyon kurar, prosedürleri sizin için takip eder.</p>
<h3>4. Yenileme Dönemi Otomatik Takip</h3>
<p>Mizan Sigorta gibi profesyonel acenteler poliçenizin yenileme dönemini sistematik olarak takip eder. 30 gün önceden sizi arayıp en uygun yenileme teklifini hazırlar.</p>
<h3>5. Mevzuat ve Hukuki Bilgi</h3>
<p>Sigorta mevzuatı sürekli değişir. Acenteniz size yeni düzenlemeler hakkında bilgi verir, hak kaybı yaşamamanız için uyarır.</p>
<h2>İyi Bir Acente Nasıl Anlaşılır?</h2>
<ul><li>Çok sayıda sigorta şirketi ile çalışıyor olmalı (en az 8-10)</li><li>Hasar süreçlerinde geçmiş referansları olmalı</li><li>Levha numarası olmalı (Sigorta Bilgi Merkezi’nde sorgulanabilir)</li><li>Kurumsal yapısı olmalı, sadece tek kişi değil</li><li>İletişimi açık ve hızlı olmalı</li></ul>
<h2>Mizan Sigorta Farkı</h2>
<ul><li>12+ anlaşmalı sigorta şirketi</li><li>İstanbul, Konya, Ankara, Aksaray’da fiziki ofisler</li><li>Profesyonel hasar süreç yönetimi</li><li>Otomatik yenileme takip sistemi</li><li>Online ve offline entegre hizmet</li></ul>
<p>Sigortanızı bizimle yapın, farkı hissedin. <a href="/teklif-al">Hızlı teklif</a> almak ücretsizdir.</p>', 'Genel Bilgi', 'acente, sigorta sirketi, fiyat, hizmet, danismanlik', 'Sigorta Acentesi mi, Doğrudan Sigorta Şirketi mi? - Mizan Sigorta', 'Sigorta yaptırırken acente üzerinden mi yoksa doğrudan sigorta şirketinden mi yaptırmak daha avantajlı? Acente avantajlarını ve sıkça sorulan soruları yanıtlıyoruz.', NOW(), 1);

INSERT IGNORE INTO `mz_blog` (`slug`, `baslik`, `ozet`, `icerik`, `kategori`, `etiketler`, `seo_baslik`, `seo_aciklama`, `yayin_tarihi`, `aktif`) VALUES
('oto-kasko-fiyatlari-nasil-belirlenir', 'Kasko Sigortası Fiyatları Nasıl Belirlenir? Düşürme Yolları', 'Kasko priminizi belirleyen faktörler nelerdir? Hasarsızlık indirimi, pazarlık alanı ve fiyat düşürmenin yolları hakkında detaylı rehber.', '<p class="lead">Kasko sigortası primi tek bir formülle hesaplanmaz. Onlarca farklı faktör fiyatınızı belirler. Bu faktörleri bilmek ve doğru stratejiyle yaklaşmak yıllık prim ödemenizde önemli tasarruf sağlayabilir.</p>
<h2>Kasko Primini Belirleyen Faktörler</h2>
<h3>1. Aracın Markası, Modeli, Yaşı</h3>
<p>En temel kriter aracın değeridir. Markaya göre yedek parça maliyetleri ve risk grupları değişir:</p>
<ul><li>Lüks markalar (BMW, Mercedes, Audi): Yedek parça yüksek olduğu için prim yüksek</li><li>Türk yapımı araçlar: Yedek parça uygun, prim düşük</li><li>Yeni nesil araçlar: Hırsızlık riski daha düşük (immobilizer vb.)</li></ul>
<h3>2. Bulunduğunuz Şehir ve İlçe</h3>
<p>Hırsızlık ve kaza istatistiklerine göre fiyatlar değişir:</p>
<ul><li>İstanbul, Ankara gibi büyük şehirlerde primler daha yüksek</li><li>Anadolu illeri genellikle daha avantajlıdır</li></ul>
<h3>3. Sürücü Profili</h3>
<ul><li><strong>Yaş:</strong> 30 yaş üzeri sürücüler avantajlıdır</li><li><strong>Cinsiyet:</strong> Bazı ürünlerde kadın sürücülere indirim</li><li><strong>Sürücü belgesi süresi:</strong> 5+ yıl olanlar avantajlı</li></ul>
<h3>4. Hasarsızlık Geçmişi</h3>
<p>En önemli faktörlerden biri. Önceki yıllarda hasar yapmadıysanız her yıl hasarsızlık basamağı artar:</p>
<ul><li>1. yıl: Standart prim</li><li>2. yıl: %15 indirim</li><li>3. yıl: %30 indirim</li><li>4. yıl: %40 indirim</li><li>5. yıl ve sonrası: %65’e kadar indirim</li></ul>
<h3>5. Aracın Park Yeri</h3>
<ul><li>Kapalı garaj/otopark: %5-10 indirim</li><li>Açık otopark: Standart</li><li>Cadde üzeri park: Bazı şirketlerde +%5</li></ul>
<h3>6. Yıllık Kullanım Mesafesi</h3>
<p>Az kullanılan araçlara indirim uygulanır. Yıllık 15.000 km altı kullanım, %5-10 prim indirimi sağlayabilir.</p>
<h2>Kasko Primini Nasıl Düşürürüm?</h2>
<h3>1. Çoklu Teklif Alın</h3>
<p>Aynı araç için bir şirket 5.000 TL, başka şirket 3.500 TL teklif edebilir. Mizan Sigorta gibi çok şirketli acentelerle 12+ teklifi karşılaştırın.</p>
<h3>2. Hasarsızlık İndirimini Koruyun</h3>
<p>Küçük çiziklerden hasar talep etmeyin. Cep ödemesi yaparsanız hasarsızlık indiriminizi korursunuz, uzun vadede kazanırsınız.</p>
<h3>3. Genişletilmiş Yerine Tam Kasko Yapın</h3>
<p>Genişletilmiş kasko sadece deprem, sel, terör için ek prim ister. İhtiyacınız yoksa tam kasko (standart) yeterlidir.</p>
<h3>4. İkame Aracı Atlayın (Eğer İhtiyaç Yoksa)</h3>
<p>İkame araç teminatı %3-5 prim ekler. İkinci aracınız varsa bu opsiyonu çıkarabilirsiniz.</p>
<h3>5. Yenileme Dönemi Bekleyin</h3>
<p>Yıl ortasında poliçe değiştirmek genelde dezavantajlıdır. Yenileme döneminde sigorta şirketleri rekabet ettiği için fiyatlar daha avantajlıdır.</p>
<h3>6. Aile İndirimleri</h3>
<p>Eşinizin de sigortasını aynı şirkete yaptırırsanız %5-10 aile indirimi uygulanır.</p>
<h2>2026 Kasko Fiyat Aralıkları (Örnek)</h2>
<table class="table table-bordered"><thead><tr><th>Araç Tipi</th><th>Yıllık Prim Aralığı</th></tr></thead><tbody>
<tr><td>Renault Clio (3 yıl)</td><td>4.500 - 6.000 TL</td></tr>
<tr><td>Volkswagen Passat</td><td>7.000 - 11.000 TL</td></tr>
<tr><td>BMW 3.20i</td><td>15.000 - 25.000 TL</td></tr>
<tr><td>Mercedes E180</td><td>20.000 - 35.000 TL</td></tr>
</tbody></table>
<p>Kişisel duruma ve şirkete göre değişir. Doğru fiyat için karşılaştırmalı teklif şart.</p>
<p>Mizan Sigorta olarak 12+ şirketten anlık karşılaştırmalı kasko teklifi sunuyoruz. <a href="/teklif-al">Hızlı teklif</a> alın, en uygun fiyatı keşfedin.</p>', 'Oto Sigortası', 'kasko fiyat, prim, hasarsizlik, indirim, kasko hesaplama', 'Kasko Sigortası Fiyatları Nasıl Belirlenir? Düşürme Yolları - Mizan Sigorta', 'Kasko priminizi belirleyen faktörler nelerdir? Hasarsızlık indirimi, pazarlık alanı ve fiyat düşürmenin yolları hakkında detaylı rehber.', NOW(), 1);

INSERT IGNORE INTO `mz_blog` (`slug`, `baslik`, `ozet`, `icerik`, `kategori`, `etiketler`, `seo_baslik`, `seo_aciklama`, `yayin_tarihi`, `aktif`) VALUES
('hayat-sigortasi-rehberi', 'Hayat Sigortası Rehberi: Aileniz İçin Doğru Tercih', 'Hayat sigortası türleri, kimler için uygun olduğu, prim hesaplaması ve aile reisleri için neden kritik olduğu hakkında kapsamlı rehber.', '<p class="lead">Hayat sigortası, sevdiklerinizin geleceğini güvence altına almanın en sorumlu ve bilinçli yoludur. Vefat ve sürekli sakatlık durumlarında ailenize maddi destek sağlar.</p>
<h2>Hayat Sigortası Türleri</h2>
<h3>1. Yıllık Yenilenebilir Hayat Sigortası</h3>
<p>En basit ve ekonomik türdür. Yıllık prim ödenir, vefat durumunda anlaşılan tutar yakınlarınıza ödenir. Birikim sağlamaz, sadece koruma amaçlıdır.</p>
<h3>2. Birikim Hayat Sigortası</h3>
<p>Hem koruma hem birikim sağlar. Ödenen primlerin bir kısmı birikime yatırılır, vade sonunda ya da vefat halinde geri alınır. Faiz/getiri sağlar.</p>
<h3>3. Kredi Hayat Sigortası</h3>
<p>Konut veya tüketici kredisi alanlar için zorunludur. Kredi süresince vefat veya sürekli sakatlık durumunda kalan kredi borcunu sigorta şirketi öder.</p>
<h3>4. Kritik Hastalık Sigortası</h3>
<p>Kanser, kalp krizi, felç gibi kritik hastalık tanısı konulduğunda toplu ödeme alırsınız. Tedavi süresince mali yükü hafifletir.</p>
<h2>Kimler Hayat Sigortası Yaptırmalı?</h2>
<ul><li><strong>Aile Reisleri:</strong> Eş ve çocuklarınızın geleceğini güvence altına almak için</li><li><strong>Borç Sahibi Kişiler:</strong> Vefatınız halinde mirasın borçtan etkilenmemesi için</li><li><strong>Kredi Alanlar:</strong> Konut/araç kredisi sahipleri için zorunlu olabilir</li><li><strong>Bekarlar:</strong> Anne-babalarına bakmak isteyenler için</li><li><strong>İş Sahipleri:</strong> Şirketin sürekliliği için ortakların hayat sigortası</li></ul>
<h2>Prim Tutarı Ne Olur?</h2>
<p>Hayat sigortası primini belirleyen faktörler:</p>
<ul><li><strong>Yaş:</strong> Genç yaşta yapanlar daha düşük prim öder</li><li><strong>Sağlık durumu:</strong> Sağlıklı bireyler avantajlıdır</li><li><strong>Sigara/alkol kullanımı:</strong> Sigara primleri %30’a kadar artırabilir</li><li><strong>Mesleki risk:</strong> Riskli mesleklerde ek prim</li><li><strong>Teminat tutarı:</strong> Yüksek teminat = yüksek prim</li><li><strong>Süre:</strong> Daha uzun süreler genellikle avantajlı</li></ul>
<h3>Örnek Prim (35 yaş, sağlıklı, 1 milyon TL teminat)</h3>
<table class="table table-bordered"><thead><tr><th>Tür</th><th>Yıllık Prim Aralığı</th></tr></thead><tbody>
<tr><td>Yıllık Yenilenebilir</td><td>1.500 - 3.000 TL</td></tr>
<tr><td>10 Yıl Sabit Prim</td><td>2.500 - 4.500 TL</td></tr>
<tr><td>Birikim Hayat</td><td>5.000 - 15.000 TL</td></tr>
</tbody></table>
<h2>Vergi Avantajı Var mı?</h2>
<p>Evet. Hayat sigortası primlerinin %15’i, brüt gelirinizin %15’ini geçmemek üzere gelir vergisi matrahından düşülebilir. Yıllık 5.000-7.500 TL’ye kadar vergi avantajı sağlanabilir.</p>
<h2>Vefat Halinde Süreç Nasıl?</h2>
<ul><li>Sigorta şirketi vefat belgesi ile başvuru alır</li><li>Belirlenen lehdar(lar) tazminat alır</li><li>Süreç ortalama 15-30 gün içinde tamamlanır</li><li>Aile mirası dışında ayrı ödenir, vergisi yoktur</li></ul>
<p>Mizan Sigorta olarak 8+ farklı şirketten hayat sigortası ürünlerini karşılaştırmalı sunuyoruz. <a href="/teklif-al">Hızlı teklif</a> alın, ailenizin geleceğini bugünden güvenceye alın.</p>', 'Hayat Sigortası', 'hayat sigortasi, kredi sigortasi, vefat teminati, birikim', 'Hayat Sigortası Rehberi: Aileniz İçin Doğru Tercih - Mizan Sigorta', 'Hayat sigortası türleri, kimler için uygun olduğu, prim hesaplaması ve aile reisleri için neden kritik olduğu hakkında kapsamlı rehber.', NOW(), 1);

INSERT IGNORE INTO `mz_blog` (`slug`, `baslik`, `ozet`, `icerik`, `kategori`, `etiketler`, `seo_baslik`, `seo_aciklama`, `yayin_tarihi`, `aktif`) VALUES
('hasar-anlik-yapilmasi-gereken-islemler', 'Sigorta Hasarı Olduğunda Yapılması Gerekenler', 'Trafik kazası, ev yangını veya işyeri hırsızlığı durumunda hasar süreci nasıl başlatılır? Hasar başvurusu için gerekli belgeler ve dikkat edilecek noktalar.', '<p class="lead">Hasar olayı yaşadığınızda doğru adımlar atmak süreç hızını ve tazminat tutarını doğrudan etkiler. İşte adım adım hasar süreci.</p>
<h2>Adım 1: İlk Müdahale ve Güvenlik</h2>
<p>Hangi tür hasar olursa olsun ilk öncelik can güvenliğidir:</p>
<ul><li><strong>Yangın:</strong> 112 ve itfaiye 110’u arayın, çıkın</li><li><strong>Trafik Kazası:</strong> Yaralı varsa 112’yi, polis 155’i arayın</li><li><strong>Su Baskını:</strong> Elektriği kesin, dışarı çıkın</li><li><strong>Hırsızlık:</strong> İçeri girmeyin, polisi arayın</li></ul>
<h2>Adım 2: Olay Yerini Koruyun</h2>
<ul><li>Eksperliğin gelmesini bekleyin</li><li>Hasar yerine müdahalede bulunmayın (özellikle yangın, hırsızlık)</li><li>Bol miktarda fotoğraf çekin (her açıdan)</li><li>Tanıkların iletişim bilgisini alın</li></ul>
<h2>Adım 3: Sigorta Şirketine Bildirim</h2>
<p>İlk 24-48 saat içinde sigorta şirketine veya acentenize bildirim yapın:</p>
<ul><li>Mizan Sigorta müşterisi iseniz: <strong>+90 332 000 00 00</strong> 7/24 hattımızı arayın</li><li>Online hasar bildirimi de yapabilirsiniz</li><li>Hasar dosya numarası alın</li></ul>
<p><strong>ÖNEMLİ:</strong> Geç bildirim hasar tazminatınıza zarar verebilir. Mevzuata göre maksimum 5 iş günü içinde bildirim yapılmalıdır.</p>
<h2>Adım 4: Gerekli Belgeleri Hazırlayın</h2>
<h3>Trafik Kazası</h3>
<ul><li>Kaza tespit tutanağı (anlaşmalı veya polis tutanağı)</li><li>Sürücü ve karşı taraf TC kimlik fotokopisi</li><li>Ehliyet ve ruhsat fotokopileri</li><li>Trafik sigorta poliçesi fotokopisi</li><li>Kasko poliçesi (varsa)</li><li>Aracın hasar fotoğrafları</li></ul>
<h3>Ev/İşyeri Yangını</h3>
<ul><li>İtfaiye raporu</li><li>Polis tutanağı (varsa)</li><li>Sigorta poliçesi</li><li>Hasar gören eşyaların fatura/değer belgeleri</li><li>Hasarın detaylı fotoğrafları</li><li>Tahmini hasar listesi</li></ul>
<h3>Hırsızlık</h3>
<ul><li>Polis ifade tutanağı (mutlaka karakola ihbar)</li><li>Sigorta poliçesi</li><li>Çalınan eşyaların listesi (mümkünse fatura)</li><li>Olay yeri fotoğrafları</li><li>Güvenlik kamera kayıtları (varsa)</li></ul>
<h2>Adım 5: Eksper İncelemesi</h2>
<p>Sigorta şirketi bir eksper atayacak. Eksper:</p>
<ul><li>Hasar bölgesini inceler</li><li>Gerekli belgeleri toplar</li><li>Hasar tutar tespiti yapar</li><li>Raporu sigorta şirketine sunar</li></ul>
<p>Eksperle iletişiminizde dürüst ve detaylı olun, gizleme yapmayın - bu tazminatı tehlikeye sokar.</p>
<h2>Adım 6: Tazminat Süreci</h2>
<p>Mevzuata göre sigorta şirketi:</p>
<ul><li>Eksperin raporundan sonra 15 iş günü içinde teklif sunmak zorundadır</li><li>Anlaşma sağlanırsa 5 iş günü içinde ödeme yapılır</li><li>Anlaşmazlık halinde tahkim veya yargı yoluna başvurulabilir</li></ul>
<h2>Sık Yapılan Hatalar</h2>
<ul><li><strong>Geç bildirim:</strong> Mevzuata göre 5 iş günü içinde bildirilmemiş hasarlar reddedilebilir</li><li><strong>Eksik belge:</strong> Polis tutanağı yoksa hasar reddedilebilir</li><li><strong>Hasar yerini değiştirmek:</strong> Eksperin gelmeden önce alanı temizlemek</li><li><strong>Yanlış beyan:</strong> Gerçek dışı bilgi vermek tazminatı düşürür</li></ul>
<h2>Mizan Sigorta Hasar Desteği</h2>
<p>Mizan Sigorta müşterilerimiz için hasar süreçlerini bizzat takip ediyoruz:</p>
<ul><li>İlk bildirimden tazminata kadar uzman destek</li><li>Eksperle koordinasyon</li><li>Belge tamamlama yardımı</li><li>Anlaşmazlıkta hukuki danışmanlık</li><li>Online hasar takip sistemi</li></ul>
<p>Hasar yaşadıysanız vakit kaybetmeden bize ulaşın: <a href="/hasar-ihbari">Hasar bildirim formu</a>.</p>', 'Hasar Yönetimi', 'hasar, sigorta hasari, kaza, evrak, tutanak', 'Sigorta Hasarı Olduğunda Yapılması Gerekenler - Mizan Sigorta', 'Trafik kazası, ev yangını veya işyeri hırsızlığı durumunda hasar süreci nasıl başlatılır? Hasar başvurusu için gerekli belgeler ve dikkat edilecek noktalar.', NOW(), 1);

-- 3) REFERANSLAR (mz_referanslar)

INSERT IGNORE INTO `mz_referanslar` (`tip`, `ad`, `unvan`, `mesaj`, `puan`, `sira`, `aktif`) VALUES ('yorum', 'Ahmet Yılmaz', 'Bireysel Müşteri', 'Kasko poliçemi yenilerken Mizan Sigorta inanılmaz uygun bir teklif sundu. Üstelik hasar sürecinde de gerçekten yanımdaydılar. 5 yıldır müşteri kalmamın sebebi bu.', 5, 1, 1);
INSERT IGNORE INTO `mz_referanslar` (`tip`, `ad`, `unvan`, `mesaj`, `puan`, `sira`, `aktif`) VALUES ('yorum', 'Mehmet Kaya', 'Otelci - Konya', 'İşyerimiz için kapsamlı bir paket arıyorduk. Mizan ekibi 12 farklı şirketten teklif çekip karşılaştırarak en uygun çözümü gösterdi. Şeffaf ve profesyonel hizmet.', 5, 2, 1);
INSERT IGNORE INTO `mz_referanslar` (`tip`, `ad`, `unvan`, `mesaj`, `puan`, `sira`, `aktif`) VALUES ('yorum', 'Ayşe Demir', 'Doktor', 'Mesleki sorumluluk sigortam için yıllarca farklı şirketlerle çalıştım. Mizan Sigorta hem fiyat hem de hizmet kalitesinde diğerlerini geride bırakıyor.', 5, 3, 1);
INSERT IGNORE INTO `mz_referanslar` (`tip`, `ad`, `unvan`, `mesaj`, `puan`, `sira`, `aktif`) VALUES ('yorum', 'Mustafa Aksoy', 'İnşaat Firması Sahibi', 'İnşaat all risk poliçemizi Mizan üzerinden yaptırdık. Bir kazada gerçekten hızlı ve sorunsuz hasar süreci yaşadık. Tavsiye ederim.', 5, 4, 1);
INSERT IGNORE INTO `mz_referanslar` (`tip`, `ad`, `unvan`, `mesaj`, `puan`, `sira`, `aktif`) VALUES ('yorum', 'Selin Öztürk', 'Bireysel Müşteri', 'DASK ve konut sigortamı bir paket olarak yaptırdım, çift indirim aldım. Üstelik yenileme dönemini benden önce takip ediyorlar. Hizmet kalitesi yüksek.', 5, 5, 1);
INSERT IGNORE INTO `mz_referanslar` (`tip`, `ad`, `unvan`, `mesaj`, `puan`, `sira`, `aktif`) VALUES ('yorum', 'Hasan Çelik', 'Lojistik Firma Sahibi', 'Filo sigortamızı yıllardır Mizan Sigorta yapıyor. 25+ aracımız var, hepsinin yenileme takibi tek noktadan yapılıyor. Çok pratik.', 5, 6, 1);
INSERT IGNORE INTO `mz_referanslar` (`tip`, `ad`, `unvan`, `mesaj`, `puan`, `sira`, `aktif`) VALUES ('yorum', 'Fatma Şahin', 'Tarım Üreticisi - Aksaray', 'TARSİM bitkisel ürün sigortamı Mizan üzerinden yaptırdım. Devlet desteğini de hesaba katarak en uygun primi sundular. Geçen yıl dolu hasarımı sorunsuz aldım.', 5, 7, 1);
INSERT IGNORE INTO `mz_referanslar` (`tip`, `ad`, `unvan`, `mesaj`, `puan`, `sira`, `aktif`) VALUES ('yorum', 'Kerem Aydın', 'Gen. Müd. - İstanbul', 'Şirketimizin grup sağlık sigortasını yaptırırken 8 farklı sigorta şirketinden teklif aldık. Mizan profesyonel ekibiyle bizi en uygun şirkete yönlendirdi.', 5, 8, 1);

-- BITIS
-- ====================================================
-- v1.1.9 - KVKK uyumlu referanslar + UNIQUE constraint
-- ====================================================
-- (NOT: v1.1.11'de placeholder SVG'ler kaldirildi; logo path'leri NULL'a cekildi.)
-- (v1.1.14: Yuvarlak avatar SVG'leri geri eklendi - paketle gelir.)

-- v1.1.14: Yuvarlak avatar logo path'leri (paketle gelen SVG'ler)
UPDATE `mz_sigorta_sirketleri` SET `logo` = 'assets/img/sirketler/anadolu-sigorta.svg'      WHERE `ad` = 'Anadolu Sigorta'      AND (`logo` IS NULL OR `logo` = '');
UPDATE `mz_sigorta_sirketleri` SET `logo` = 'assets/img/sirketler/allianz-sigorta.svg'      WHERE `ad` = 'Allianz Sigorta'      AND (`logo` IS NULL OR `logo` = '');
UPDATE `mz_sigorta_sirketleri` SET `logo` = 'assets/img/sirketler/axa-sigorta.svg'          WHERE `ad` = 'AXA Sigorta'          AND (`logo` IS NULL OR `logo` = '');
UPDATE `mz_sigorta_sirketleri` SET `logo` = 'assets/img/sirketler/turkiye-sigorta.svg'      WHERE `ad` = 'Türkiye Sigorta'      AND (`logo` IS NULL OR `logo` = '');
UPDATE `mz_sigorta_sirketleri` SET `logo` = 'assets/img/sirketler/hdi-sigorta.svg'          WHERE `ad` = 'HDI Sigorta'          AND (`logo` IS NULL OR `logo` = '');
UPDATE `mz_sigorta_sirketleri` SET `logo` = 'assets/img/sirketler/quick-sigorta.svg'        WHERE `ad` = 'Quick Sigorta'        AND (`logo` IS NULL OR `logo` = '');
UPDATE `mz_sigorta_sirketleri` SET `logo` = 'assets/img/sirketler/neova-sigorta.svg'        WHERE `ad` = 'Neova Sigorta'        AND (`logo` IS NULL OR `logo` = '');
UPDATE `mz_sigorta_sirketleri` SET `logo` = 'assets/img/sirketler/ak-sigorta.svg'           WHERE `ad` = 'Ak Sigorta'           AND (`logo` IS NULL OR `logo` = '');
UPDATE `mz_sigorta_sirketleri` SET `logo` = 'assets/img/sirketler/doga-sigorta.svg'         WHERE `ad` = 'Doğa Sigorta'         AND (`logo` IS NULL OR `logo` = '');
UPDATE `mz_sigorta_sirketleri` SET `logo` = 'assets/img/sirketler/atlas-sigorta.svg'        WHERE `ad` = 'Atlas Sigorta'        AND (`logo` IS NULL OR `logo` = '');
UPDATE `mz_sigorta_sirketleri` SET `logo` = 'assets/img/sirketler/corpus-sigorta.svg'       WHERE `ad` = 'Corpus Sigorta'       AND (`logo` IS NULL OR `logo` = '');
UPDATE `mz_sigorta_sirketleri` SET `logo` = 'assets/img/sirketler/magdeburger-sigorta.svg'  WHERE `ad` = 'Magdeburger Sigorta'  AND (`logo` IS NULL OR `logo` = '');
UPDATE `mz_sigorta_sirketleri` SET `logo` = 'assets/img/sirketler/mapfre-sigorta.svg'       WHERE `ad` = 'Mapfre Sigorta'       AND (`logo` IS NULL OR `logo` = '');
UPDATE `mz_sigorta_sirketleri` SET `logo` = 'assets/img/sirketler/ray-sigorta.svg'          WHERE `ad` = 'Ray Sigorta'          AND (`logo` IS NULL OR `logo` = '');
UPDATE `mz_sigorta_sirketleri` SET `logo` = 'assets/img/sirketler/sompo-sigorta.svg'        WHERE `ad` = 'Sompo Sigorta'        AND (`logo` IS NULL OR `logo` = '');

-- Referanslar UNIQUE constraint
DELETE r1 FROM `mz_referanslar` r1
INNER JOIN `mz_referanslar` r2 ON r1.id > r2.id AND r1.ad = r2.ad AND r1.mesaj = r2.mesaj;
ALTER TABLE `mz_referanslar` ADD UNIQUE KEY `uk_ad_mesaj` (`ad`, `mesaj`(255));

-- Eski full-name referanslari sil
DELETE FROM `mz_referanslar` WHERE `ad` IN (
  'Ahmet Yılmaz', 'Mehmet Kaya', 'Ayşe Demir', 'Mustafa Aksoy',
  'Selin Öztürk', 'Hasan Çelik', 'Fatma Şahin', 'Kerem Aydın'
);
INSERT IGNORE INTO `mz_referanslar` (`tip`, `ad`, `unvan`, `mesaj`, `puan`, `sira`, `aktif`) VALUES
('yorum', 'Ahmet Y.',    'Bireysel Müşteri',
 'Kasko poliçemi yenilerken birkaç şirketten teklif aldılar, hepsini açıklayarak en uygununu seçmeme yardımcı oldular. Hasar sürecinde de gerçekten yanımda hissettim. 5 yıldır müşteri kalmamın sebebi bu.',
 5, 1, 1),

('yorum', 'Mehmet K.',   'Otelci · Konya',
 'Otelimiz için kapsamlı işyeri sigortası arıyorduk. Mizan ekibi 12 farklı şirketten teklif çekip karşılaştırmalı tablo hâlinde sundu. Şeffaf, profesyonel hizmet — saygı duyduğum noktada.',
 5, 2, 1),

('yorum', 'Ayşe D.',     'Doktor',
 'Mesleki sorumluluk sigortam için yıllarca farklı şirketlerle çalıştım. Mizan Sigorta hem fiyat hem de hizmet kalitesinde fark yaratıyor; özellikle yenileme dönemini ben hatırlamadan onlar arıyor.',
 5, 3, 1),

('yorum', 'Mustafa A.',  'İnşaat Firması',
 'Şantiyemizde inşaat all risk poliçemizi Mizan üzerinden yaptırdık. Yaşadığımız bir vinç hasarında eksperle koordinasyondan tazminat ödemesine kadar tüm süreci onlar yönetti. Tavsiye ederim.',
 5, 4, 1),

('yorum', 'Selin Ö.',    'Bireysel Müşteri',
 'DASK ve konut sigortamı bir paket olarak yaptırdığımda fark ücreti almadılar, üstelik ek indirim sundular. Yenileme dönemini benden önce takip ediyorlar — gerçekten profesyonel.',
 5, 5, 1),

('yorum', 'Hasan Ç.',    'Lojistik Firma Sahibi',
 'Filo sigortamızı yıllardır Mizan Sigorta yapıyor; 25+ aracımızın tüm yenileme takibi tek noktadan yapılıyor. Hasar bildiriminde 7/24 destek aldığımız tek acente.',
 5, 6, 1),

('yorum', 'Fatma Ş.',    'Tarım Üreticisi · Aksaray',
 'TARSİM bitkisel ürün sigortamı Mizan üzerinden yaptırdım. Devlet desteğini de hesaba katıp en uygun primi sundular. Geçen yıl dolu hasarımı sorunsuz aldım — köyde herkese tavsiye ediyorum.',
 5, 7, 1),

('yorum', 'Kerem A.',    'Genel Müdür · İstanbul',
 'Şirketimizin grup sağlık sigortasını yenilerken 8 farklı sigorta şirketinden teklif aldık. Mizan profesyonel ekibiyle çalışanlarımıza en uygun teminatı sunan paketi belirlememizde büyük katkı sağladı.',
 5, 8, 1),

('yorum', 'Burcu T.',    'Eczacı',
 'Eczanemiz için işyeri sigortası ararken yangın, hırsızlık ve mali sorumluluk teminatlarını ayrı ayrı incelediler. Mevzuatla uyumlu, eksiksiz bir paket hazırlamışlar — meslektaşlarıma da öneriyorum.',
 5, 9, 1),

('yorum', 'Emre B.',     'Yazılım Mühendisi',
 'Aracımı Türkiye dışına çıkarırken yeşilkart sigortası gerektiğini bilmiyordum. Aradığım gün hızlı bir şekilde poliçeyi düzenlediler, sınırda hiç sorun yaşamadım. Hızlı ve net hizmet.',
 5, 10, 1);


-- ===========================================================================
-- KONTROL SORGULARI (Yunus calistirip dogrulayabilir):
-- ===========================================================================
-- SELECT 'sirket_logo_dolu' k, COUNT(*) v FROM mz_sigorta_sirketleri WHERE logo LIKE 'assets/img/sirketler/%'
-- UNION SELECT 'sirket_toplam', COUNT(*) FROM mz_sigorta_sirketleri WHERE aktif=1
-- UNION SELECT 'referans_toplam', COUNT(*) FROM mz_referanslar WHERE aktif=1
-- UNION SELECT 'referans_kvkk_uyumsuz', COUNT(*) FROM mz_referanslar WHERE ad LIKE '% %' AND ad NOT LIKE '% _.';
-- Beklenen: sirket_logo_dolu=15, sirket_toplam=15, referans_toplam=10, referans_kvkk_uyumsuz=0

-- ====================================================
-- v1.1.14 - Urun aciklamalari toplu guncelleme
-- ====================================================

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Aracınızı, sürücü sorumluluğunuzu ve yurt dışı yolculuklarınızı kapsayan tüm motorlu taşıt sigortaları.</div><h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-collection"></i> Bu Kategori Altında</h3><div class="row g-3 mb-4"><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">Kasko Sigortası</h6><p class="small text-muted mb-0">Aracınızı çarpışma, yangın, hırsızlık, doğal afetler ve cam kırılması gibi geniş risklere karşı korur.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">Trafik Sigortası</h6><p class="small text-muted mb-0">Yasal zorunlu sigorta — üçüncü şahıslara verilen maddi ve bedeni zararları karşılar.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">İhtiyari Mali Mesuliyet (İMM)</h6><p class="small text-muted mb-0">Trafik sigortasının limit üstündeki tutarlar için ek koruma sağlar.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">Yeşilkart</h6><p class="small text-muted mb-0">Yurt dışına aracınızla çıkarken sınır geçişi için zorunlu sorumluluk teminatı.</p></div></div></div><h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-people"></i> Kimler İçin Uygun?</h3><p>Bireysel araç sahipleri, ticari filo işleten firmalar, yurt dışına seyahat eden sürücüler.</p><div class="alert alert-info mt-4 d-flex gap-3 align-items-start"><i class="bi bi-info-circle-fill fs-4"></i><div><strong class="d-block mb-1">Hangi alt ürün size uygun?</strong>Her durumda en uygun teminat farklılık gösterebilir. Müsait temsilcimiz ihtiyacınızı analiz ederek 12+ anlaşmalı şirketten en uygun teklifi sunar.</div></div>' WHERE `slug` = 'oto-sigortalari';

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Eviniz, işyeriniz ve apartmanınızın ortak alanları için yangın, doğal afet ve hırsızlık güvencesi.</div><h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-collection"></i> Bu Kategori Altında</h3><div class="row g-3 mb-4"><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">Konut Sigortası</h6><p class="small text-muted mb-0">Ev eşyaları, dekorasyon ve bina için kapsamlı paket — yangın, sel, hırsızlık, cam kırılması, asistans.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">İşyeri Yangın Sigortası</h6><p class="small text-muted mb-0">Ticari mekan, mal stoğu, makineler ve kâr kaybı için özel yangın koruması.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">DASK Zorunlu Deprem Sigortası</h6><p class="small text-muted mb-0">Yasal zorunlu — meskenler için deprem ve sonrası yangın/infilak/tsunami teminatı.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">Ortak Alan Sigortası</h6><p class="small text-muted mb-0">Apartman ve site yönetimleri için merdiven, asansör, otopark gibi ortak alanlar.</p></div></div></div><h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-people"></i> Kimler İçin Uygun?</h3><p>Ev sahipleri ve kiracılar, işyeri sahipleri, apartman/site yöneticileri.</p><div class="alert alert-info mt-4 d-flex gap-3 align-items-start"><i class="bi bi-info-circle-fill fs-4"></i><div><strong class="d-block mb-1">Hangi alt ürün size uygun?</strong>Her durumda en uygun teminat farklılık gösterebilir. Müsait temsilcimiz ihtiyacınızı analiz ederek 12+ anlaşmalı şirketten en uygun teklifi sunar.</div></div>' WHERE `slug` = 'yangin-policeleri';

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Bireysel ve kurumsal sağlık güvencesi paketleri — özel hastane, tamamlayıcı ve yurt dışı seçenekler.</div><h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-collection"></i> Bu Kategori Altında</h3><div class="row g-3 mb-4"><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">Özel Sağlık Sigortası</h6><p class="small text-muted mb-0">Anlaşmalı özel hastanelerde nitelikli tedavi, ameliyat, doğum ve check-up paketleri.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">Tamamlayıcı Sağlık Sigortası</h6><p class="small text-muted mb-0">SGK üzerine eklenen, anlaşmalı özel hastanelerde fark ücretsiz tedavi imkanı.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">Yurt Dışı Seyahat Sağlık</h6><p class="small text-muted mb-0">Yurt dışında ani hastalık, kaza ve sağlık masrafları teminatı.</p></div></div></div><h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-people"></i> Kimler İçin Uygun?</h3><p>Bireyler ve aileler, çalışan kadrolarına grup sağlık paketi sunan kurumlar, sık seyahat edenler.</p><div class="alert alert-info mt-4 d-flex gap-3 align-items-start"><i class="bi bi-info-circle-fill fs-4"></i><div><strong class="d-block mb-1">Hangi alt ürün size uygun?</strong>Her durumda en uygun teminat farklılık gösterebilir. Müsait temsilcimiz ihtiyacınızı analiz ederek 12+ anlaşmalı şirketten en uygun teklifi sunar.</div></div>' WHERE `slug` = 'saglik-sigortalari';

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">İnşaat, montaj, makine ve elektronik cihazlar için "tüm riskler" teminatı — istisnalar haricindeki her hasarı kapsar.</div><h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-collection"></i> Bu Kategori Altında</h3><div class="row g-3 mb-4"><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">İnşaat All Risk (CAR)</h6><p class="small text-muted mb-0">Şantiye süresince inşaat malzemesi, kalıp, iskele ve geçici imalat teminatı.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">Montaj All Risk (EAR)</h6><p class="small text-muted mb-0">Mühendislik kapsamında makine kurulumu ve test süreci için özel teminat.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">Makine Kırılması</h6><p class="small text-muted mb-0">Üretim hattındaki makinelerin elektrik/mekanik arızalarını karşılar.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">Elektronik Cihaz Sigortası</h6><p class="small text-muted mb-0">Bilgisayar, sunucu, tıbbi cihaz ve diğer elektronik ekipmanlar için.</p></div></div></div><h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-people"></i> Kimler İçin Uygun?</h3><p>İnşaat firmaları, üretim tesisleri, mühendislik şirketleri, IT yoğun ofisler.</p><div class="alert alert-info mt-4 d-flex gap-3 align-items-start"><i class="bi bi-info-circle-fill fs-4"></i><div><strong class="d-block mb-1">Hangi alt ürün size uygun?</strong>Her durumda en uygun teminat farklılık gösterebilir. Müsait temsilcimiz ihtiyacınızı analiz ederek 12+ anlaşmalı şirketten en uygun teklifi sunar.</div></div>' WHERE `slug` = 'all-riskler';

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Tekne, yat ve emtea taşımacılığı için denizcilik ve lojistik sigortaları.</div><h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-collection"></i> Bu Kategori Altında</h3><div class="row g-3 mb-4"><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">Tekne (Hull)</h6><p class="small text-muted mb-0">Gemi ve tekne gövdesi için kapsamlı sigorta — çarpışma, fırtına, yangın.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">Yat Sigortası</h6><p class="small text-muted mb-0">Özel ve ticari yatlar için gövde, makine ve sorumluluk teminatları.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">Nakliyat Emtea</h6><p class="small text-muted mb-0">Yük taşımacılığında mal hasarı, eksilme ve hırsızlık teminatı.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">Taşıyıcı Sorumluluk</h6><p class="small text-muted mb-0">Lojistik firmaları için müşteri yüküne karşı sorumluluk.</p></div></div></div><h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-people"></i> Kimler İçin Uygun?</h3><p>Lojistik firmaları, tekne/yat sahipleri, ihracat-ithalat yapan firmalar.</p><div class="alert alert-info mt-4 d-flex gap-3 align-items-start"><i class="bi bi-info-circle-fill fs-4"></i><div><strong class="d-block mb-1">Hangi alt ürün size uygun?</strong>Her durumda en uygun teminat farklılık gösterebilir. Müsait temsilcimiz ihtiyacınızı analiz ederek 12+ anlaşmalı şirketten en uygun teklifi sunar.</div></div>' WHERE `slug` = 'nakliyat';

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Mali, mesleki ve özel güvenlik sorumluluk teminatları — başkalarına verilen zararlar için yasal koruma.</div><h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-collection"></i> Bu Kategori Altında</h3><div class="row g-3 mb-4"><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">İşveren Mali Sorumluluk</h6><p class="small text-muted mb-0">İş kazası durumunda çalışanlara karşı işveren sorumluluğu.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">Üçüncü Şahıs Sorumluluk</h6><p class="small text-muted mb-0">İşletme faaliyetinizden kaynaklı üçüncü kişilere verilen zararlar.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">Mesleki Sorumluluk</h6><p class="small text-muted mb-0">Avukat, mali müşavir, doktor, mimar gibi meslekler için mesleki hata teminatı.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">Özel Güvenlik Mali Sorumluluk</h6><p class="small text-muted mb-0">Özel güvenlik şirketleri için yasal zorunlu teminat.</p></div></div></div><h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-people"></i> Kimler İçin Uygun?</h3><p>İşveren sıfatıyla çalışan firmalar, serbest meslek sahipleri (doktor, avukat, müşavir), güvenlik şirketleri.</p><div class="alert alert-info mt-4 d-flex gap-3 align-items-start"><i class="bi bi-info-circle-fill fs-4"></i><div><strong class="d-block mb-1">Hangi alt ürün size uygun?</strong>Her durumda en uygun teminat farklılık gösterebilir. Müsait temsilcimiz ihtiyacınızı analiz ederek 12+ anlaşmalı şirketten en uygun teklifi sunar.</div></div>' WHERE `slug` = 'sorumluluk-sigortalari';

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Devlet destekli TARSİM havuzu kapsamında tarım, hayvancılık, sera ve su ürünleri sigortaları.</div><h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-collection"></i> Bu Kategori Altında</h3><div class="row g-3 mb-4"><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">Bitkisel Ürün Sigortası</h6><p class="small text-muted mb-0">Tahıl, meyve, sebze ve diğer bitkisel ürünler için dolu, fırtına, hortum teminatı.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">Sera Sigortası</h6><p class="small text-muted mb-0">Sera yapısı ve içindeki ürünler için kapsamlı doğal afet teminatı.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">Hayvan Hayat Sigortası</h6><p class="small text-muted mb-0">Büyükbaş, küçükbaş, kümes hayvanları için hastalık ve kaza teminatı.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">Arıcılık Sigortası</h6><p class="small text-muted mb-0">Arı kovanları ve bal üretimi için doğal afet ve hayvan kaybı teminatı.</p></div></div></div><h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-people"></i> Kimler İçin Uygun?</h3><p>Çiftçiler, hayvan yetiştiricileri, sera işletmecileri, arıcılar — devlet primi yarısını öder.</p><div class="alert alert-info mt-4 d-flex gap-3 align-items-start"><i class="bi bi-info-circle-fill fs-4"></i><div><strong class="d-block mb-1">Hangi alt ürün size uygun?</strong>Her durumda en uygun teminat farklılık gösterebilir. Müsait temsilcimiz ihtiyacınızı analiz ederek 12+ anlaşmalı şirketten en uygun teklifi sunar.</div></div>' WHERE `slug` = 'tarsim';

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Bireysel kaza, deprem ve kritik hastalık durumlarında nakit ödeme yapan koruma paketleri.</div><h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-collection"></i> Bu Kategori Altında</h3><div class="row g-3 mb-4"><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">Ferdi Kaza Sigortası</h6><p class="small text-muted mb-0">Kaza sonucu ölüm, sürekli sakatlık ve geçici iş göremezlik durumları için tazminat.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">Deprem Destek Sigortası</h6><p class="small text-muted mb-0">DASK üzerine ek olarak deprem hasarlarında nakit destek paketi.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">Kritik Hastalık Sigortası</h6><p class="small text-muted mb-0">Kanser, kalp krizi, felç gibi 10+ kritik hastalık tanısında peşin ödeme.</p></div></div></div><h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-people"></i> Kimler İçin Uygun?</h3><p>Aile reisi olarak gelir güvencesi arayanlar, riskli mesleklerde çalışanlar, deprem bölgesinde yaşayanlar.</p><div class="alert alert-info mt-4 d-flex gap-3 align-items-start"><i class="bi bi-info-circle-fill fs-4"></i><div><strong class="d-block mb-1">Hangi alt ürün size uygun?</strong>Her durumda en uygun teminat farklılık gösterebilir. Müsait temsilcimiz ihtiyacınızı analiz ederek 12+ anlaşmalı şirketten en uygun teklifi sunar.</div></div>' WHERE `slug` = 'ferdi-kaza';

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Kefalet senedi, KDV iadesi ve devlet destekli alacak sigortaları — banka teminat mektuplarına alternatif.</div><h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-collection"></i> Bu Kategori Altında</h3><div class="row g-3 mb-4"><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">Kefalet Senedi</h6><p class="small text-muted mb-0">İhale, sözleşme ve gümrük süreçlerinde teminat mektubu yerine kullanılan poliçe.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">KDV İadesi Kefalet</h6><p class="small text-muted mb-0">KDV iade süreçlerinde mali idareye verilen teminat.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)">Devlet Destekli Alacak Sigortası</h6><p class="small text-muted mb-0">KOBİ ticari alacakları için devlet destekli koruma.</p></div></div></div><h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-people"></i> Kimler İçin Uygun?</h3><p>İhalelere katılan firmalar, ihracatçılar, ticari alacak riski yöneten KOBİ''ler.</p><div class="alert alert-info mt-4 d-flex gap-3 align-items-start"><i class="bi bi-info-circle-fill fs-4"></i><div><strong class="d-block mb-1">Hangi alt ürün size uygun?</strong>Her durumda en uygun teminat farklılık gösterebilir. Müsait temsilcimiz ihtiyacınızı analiz ederek 12+ anlaşmalı şirketten en uygun teklifi sunar.</div></div>' WHERE `slug` = 'kefalet-sigortalari';

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Beklenmedik kazalar — trafik kazası, ev kazası, iş kazası, spor yaralanmaları — sonucu yaşanabilecek mali yükleri karşılayan bireysel poliçedir.</div><h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Teminat Kapsamı</h3><div class="row g-3 mb-4"><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid #198754;padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)"><i class="bi bi-check-circle-fill text-success"></i> Kaza Sonucu Ölüm</h6><p class="small text-muted mb-0">Sigortalının kazanın yarattığı bir nedenle vefat etmesi halinde poliçedeki tutar lehtara/varislere ödenir.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid #198754;padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)"><i class="bi bi-check-circle-fill text-success"></i> Kaza Sonucu Sürekli Sakatlık</h6><p class="small text-muted mb-0">Kaza sonucu kalıcı sakatlık tanısında, sakatlık oranına göre kısmi veya tam tazminat.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid #198754;padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)"><i class="bi bi-check-circle-fill text-success"></i> Geçici İş Göremezlik (Gündelik Tazminat)</h6><p class="small text-muted mb-0">Kaza nedeniyle çalışamadığınız her gün için günlük tazminat ödemesi.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid #198754;padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)"><i class="bi bi-check-circle-fill text-success"></i> Tedavi Masrafları</h6><p class="small text-muted mb-0">Kaza sonrası hastane, ameliyat, ilaç ve fizik tedavi giderleri.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid #198754;padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)"><i class="bi bi-check-circle-fill text-success"></i> Hastane Gündelik Tazminatı</h6><p class="small text-muted mb-0">Kaza nedeniyle yatarak tedavi süresince günlük destek ödemesi.</p></div></div></div><h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-x-octagon"></i> Kapsam Dışı Durumlar</h3><ul class="list-unstyled"><li class="mb-2"><i class="bi bi-x-circle text-muted me-2"></i>İntihar veya intihar girişimi sonucu hasarlar</li><li class="mb-2"><i class="bi bi-x-circle text-muted me-2"></i>Sigortalının uyuşturucu/alkol etkisi altındayken yaşadığı kazalar</li><li class="mb-2"><i class="bi bi-x-circle text-muted me-2"></i>Profesyonel sporcuların yarışma kazaları (özel paket gerekli)</li><li class="mb-2"><i class="bi bi-x-circle text-muted me-2"></i>Savaş, terör, isyan ve nükleer riskler</li></ul><div class="alert alert-warning mt-4"><h6 class="fw-bold mb-2"><i class="bi bi-lightbulb-fill"></i> Mizan İpuçları</h6><ul class="mb-0 small"><li class="mb-1">Aile reisi iseniz, eşinizin ve çocuklarınızın da kapsama alındığı **Aile Paketleri** çok daha avantajlıdır.</li><li class="mb-1">Riskli meslek (inşaat, lojistik, güvenlik vb.) yapıyorsanız mesleki paketle prim daha uygun olur.</li><li class="mb-1">Kredi kullanıyorsanız bankalar genelde ferdi kaza şartı koşar — Mizan üzerinden yaptırırsanız çoğu zaman yarı fiyatına aynı teminatı alabilirsiniz.</li></ul></div>' WHERE `slug` = 'ferdi-kaza-sigortasi';

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">DASK''ın üzerine eklenen, deprem sonrası nakit destek ödemesi yapan tamamlayıcı pakettir. DASK bina yapısını öderken, bu poliçe size doğrudan nakit destek verir.</div><h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Teminat Kapsamı</h3><div class="row g-3 mb-4"><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid #198754;padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)"><i class="bi bi-check-circle-fill text-success"></i> Deprem Anlık Nakit Desteği</h6><p class="small text-muted mb-0">Hasarın boyutuna göre, tanı sonrası 7 gün içinde nakit ödeme.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid #198754;padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)"><i class="bi bi-check-circle-fill text-success"></i> Geçici Konaklama</h6><p class="small text-muted mb-0">Eviniz oturulamaz hale geldiyse 3-6 ay otel/kira desteği.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid #198754;padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)"><i class="bi bi-check-circle-fill text-success"></i> Eşya Tazminatı</h6><p class="small text-muted mb-0">DASK''ın karşılamadığı ev eşyaları, beyaz eşya, mobilya hasarları.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid #198754;padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)"><i class="bi bi-check-circle-fill text-success"></i> Acil Yaşam Paketi</h6><p class="small text-muted mb-0">Deprem sonrası ilk 72 saat için temel ihtiyaç malzemeleri kataloğu.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid #198754;padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)"><i class="bi bi-check-circle-fill text-success"></i> Tıbbi Tahliye</h6><p class="small text-muted mb-0">Bölgenizden güvenli bir noktaya tıbbi tahliye desteği.</p></div></div></div><h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-x-octagon"></i> Kapsam Dışı Durumlar</h3><ul class="list-unstyled"><li class="mb-2"><i class="bi bi-x-circle text-muted me-2"></i>Deprem dışındaki doğal afetler (sel, heyelan vb.) — bunlar konut sigortasında</li><li class="mb-2"><i class="bi bi-x-circle text-muted me-2"></i>Bina yapısal hasarı (DASK kapsamında)</li><li class="mb-2"><i class="bi bi-x-circle text-muted me-2"></i>Deprem öncesi mevcut hasarlar</li></ul><div class="alert alert-warning mt-4"><h6 class="fw-bold mb-2"><i class="bi bi-lightbulb-fill"></i> Mizan İpuçları</h6><ul class="mb-0 small"><li class="mb-1">**DASK''ın yerine geçmez, üzerine eklenir.** Önce DASK''ınız olmalı.</li><li class="mb-1">Deprem bölgesinde (özellikle Marmara, Ege) yaşıyorsanız bu paket güçlü tavsiye ediliyor.</li><li class="mb-1">Aile büyüklüğünüze göre konaklama limitini doğru seçmeniz önemli — temsilcimiz hesaplar.</li></ul></div>' WHERE `slug` = 'deprem-destek-sigortasi';

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Hayatınızı altüst edebilecek kanser, kalp krizi, felç, organ yetmezliği gibi 10+ kritik hastalığın tanısında **peşin nakit ödeme** yapan koruma paketidir.</div><h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Teminat Kapsamı</h3><div class="row g-3 mb-4"><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid #198754;padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)"><i class="bi bi-check-circle-fill text-success"></i> Tek Seferlik Tanı Tazminatı</h6><p class="small text-muted mb-0">Tanı konulduğu anda — tedavi başlamadan — poliçedeki nakit ödeme yapılır.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid #198754;padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)"><i class="bi bi-check-circle-fill text-success"></i> İkinci Tıbbi Görüş</h6><p class="small text-muted mb-0">Yurt içi/dışı önde gelen merkezlerde uzman onayı için ücretsiz danışmanlık.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid #198754;padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)"><i class="bi bi-check-circle-fill text-success"></i> Tedavi Süresi Geliri</h6><p class="small text-muted mb-0">Tedavi nedeniyle çalışamadığınız aylarda gelir kaybı tazminatı.</p></div></div><div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid #198754;padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%"><h6 class="fw-bold mb-1" style="color:var(--mz-navy)"><i class="bi bi-check-circle-fill text-success"></i> Yurt Dışı Tedavi Desteği</h6><p class="small text-muted mb-0">Türkiye''de yapılamayan tedavilerde yurt dışı sevki ve seyahat masrafları.</p></div></div></div><h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-list-check"></i> Kapsanan Hastalıklar</h3><div class="d-flex flex-wrap gap-2 mb-4"><span class="badge bg-light text-dark border px-3 py-2"><i class="bi bi-dot text-danger"></i> Kanser</span><span class="badge bg-light text-dark border px-3 py-2"><i class="bi bi-dot text-danger"></i> Kalp krizi (miyokart enfarktüsü)</span><span class="badge bg-light text-dark border px-3 py-2"><i class="bi bi-dot text-danger"></i> Felç (inme)</span><span class="badge bg-light text-dark border px-3 py-2"><i class="bi bi-dot text-danger"></i> Böbrek yetmezliği</span><span class="badge bg-light text-dark border px-3 py-2"><i class="bi bi-dot text-danger"></i> Organ nakli</span><span class="badge bg-light text-dark border px-3 py-2"><i class="bi bi-dot text-danger"></i> Kalp bypass ameliyatı</span><span class="badge bg-light text-dark border px-3 py-2"><i class="bi bi-dot text-danger"></i> Multipl skleroz</span><span class="badge bg-light text-dark border px-3 py-2"><i class="bi bi-dot text-danger"></i> Körlük</span><span class="badge bg-light text-dark border px-3 py-2"><i class="bi bi-dot text-danger"></i> Sağırlık</span><span class="badge bg-light text-dark border px-3 py-2"><i class="bi bi-dot text-danger"></i> Komada kalmak</span></div><h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-x-octagon"></i> Kapsam Dışı Durumlar</h3><ul class="list-unstyled"><li class="mb-2"><i class="bi bi-x-circle text-muted me-2"></i>Poliçe başlangıcından önce tanısı konmuş hastalıklar</li><li class="mb-2"><i class="bi bi-x-circle text-muted me-2"></i>HIV/AIDS (özel paket gerekli)</li><li class="mb-2"><i class="bi bi-x-circle text-muted me-2"></i>Doğuştan gelen hastalıklar</li><li class="mb-2"><i class="bi bi-x-circle text-muted me-2"></i>İlk 3-6 ay (bekleme süresi) içinde tanı konulan vakalar</li></ul><div class="alert alert-warning mt-4"><h6 class="fw-bold mb-2"><i class="bi bi-lightbulb-fill"></i> Mizan İpuçları</h6><ul class="mb-0 small"><li class="mb-1">Genç ve sağlıklı iken yaptırırsanız primler çok düşük olur — yıllık 1000-2000 TL civarında, **5 yıllık prim sabitleyebilirsiniz.**</li><li class="mb-1">Aile geçmişinizde kanser veya kalp hastalığı varsa öncelikli düşünün.</li><li class="mb-1">Tanı anında peşin ödeme almak, **tedavi sürecinde kritik nakit akışı** sağlar.</li></ul></div>' WHERE `slug` = 'kritik-hastaliklar';

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Aracınızı çarpışma, yangın, hırsızlık, doğal afetler ve cam kırılması gibi geniş risklere karşı korumak için tasarlanan kapsamlı bir poliçedir.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>Kasko Sigortası, riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
Kasko Sigortası her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'kasko' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Karayolları Zorunlu Mali Sorumluluk Sigortası — yasal zorunlu poliçe. Üçüncü şahıslara verilen maddi ve bedeni zararları karşılar.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>Trafik Sigortası, riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
Trafik Sigortası her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'trafik-zorunlu-sorumluluk' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Trafik sigortasının limit üstünde kalan zararlar için tamamlayıcı, isteğe bağlı sorumluluk teminatıdır.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>İhtiyari Mali Mesuliyet (İMM), riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
İhtiyari Mali Mesuliyet (İMM) her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'ihtiyari-mali-mesuliyet-imm' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Yurt dışına aracınızla çıkışta sınır kapısında talep edilen, aracınızın yurt dışındaki sorumluluk teminatıdır.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>Yeşilkart, riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
Yeşilkart her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'yesilkart' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Eviniz, eşyalarınız ve dekorasyonunuz için yangın, sel, hırsızlık, cam kırılması ve asistans hizmetlerini kapsayan paket sigortadır.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>Konut Sigortası, riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
Konut Sigortası her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'konut-sigortasi' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Ticari mekan, mal stoğu, makine-tesisat ve kâr kaybı için özel olarak tasarlanan yangın güvencesidir.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>İşyeri Yangın Sigortası, riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
İşyeri Yangın Sigortası her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'isyeri-yangin-sigortasi' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Yasal zorunlu — meskenler için deprem ve sonrasında oluşan yangın, infilak, tsunami hasarlarını bina yapısı bazında karşılar.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>DASK Zorunlu Deprem Sigortası, riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
DASK Zorunlu Deprem Sigortası her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'dask' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Apartman ve site yönetimleri için merdiven, asansör, otopark, ortak çatı gibi alanları kapsayan toplu sigortadır.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>Ortak Alan Sigortası, riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
Ortak Alan Sigortası her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'ortak-alan-sigortasi' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Anlaşmalı özel hastanelerde nitelikli tedavi, ameliyat, doğum ve check-up hizmetleri için kapsamlı bireysel sağlık güvencesidir.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>Özel Sağlık Sigortası, riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
Özel Sağlık Sigortası her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'ozel-saglik-sigortasi' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">SGK kapsamındaki tedavilere ek olarak, anlaşmalı özel hastanelerde fark ücretsiz tedavi imkanı sunan ekonomik bir alternatiftir.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>Tamamlayıcı Sağlık Sigortası, riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
Tamamlayıcı Sağlık Sigortası her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'tamamlayici-saglik-sigortasi' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Yurt dışı yolculuklarda ani hastalık, kaza ve sağlık masraflarını karşılayan, vize başvurularında zorunlu olabilen poliçedir.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>Yurt Dışı Seyahat Sağlık Sigortası, riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
Yurt Dışı Seyahat Sağlık Sigortası her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'yurt-disi-seyahat-saglik' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Şantiye süresince inşaat malzemesi, kalıp, iskele, geçici imalat ve üçüncü şahıs sorumluluk için kapsamlı koruma sağlar.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>İnşaat All Risk (CAR), riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
İnşaat All Risk (CAR) her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'insaat-all-risk' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Mühendislik kapsamında makine kurulumu, deneme ve test sürecinde ortaya çıkabilecek hasarlara karşı tasarlanmıştır.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>Montaj All Risk (EAR), riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
Montaj All Risk (EAR) her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'montaj-all-risk' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Üretim hattındaki makinelerin elektrik, mekanik ve montaj hatası kaynaklı arızalarını karşılar.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>Makine Kırılması Sigortası, riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
Makine Kırılması Sigortası her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'makine-kirilmasi' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Bilgisayar, sunucu, tıbbi cihaz, kamera ve diğer elektronik ekipmanlar için ani-beklenmeyen hasar teminatıdır.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>Elektronik Cihaz Sigortası, riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
Elektronik Cihaz Sigortası her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'elektronik-cihaz' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">İş kazası durumunda çalışana veya yakınlarına karşı işverenin mali sorumluluğunu karşılayan zorunlu olabilen teminat.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>İşveren Mali Sorumluluk, riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
İşveren Mali Sorumluluk her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'isveren-mali-sorumluluk' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">İşletme faaliyetinizden kaynaklı olarak üçüncü kişilere verilen maddi ve bedensel zararları karşılayan sorumluluk poliçesidir.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>Üçüncü Şahıs Sorumluluk, riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
Üçüncü Şahıs Sorumluluk her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'ucuncu-sahis-sorumluluk' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Avukat, mali müşavir, doktor, mimar gibi serbest meslek sahipleri için mesleki hata kaynaklı tazminat teminatıdır.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>Mesleki Sorumluluk Sigortası, riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
Mesleki Sorumluluk Sigortası her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'mesleki-sorumluluk' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">5188 sayılı kanun gereği özel güvenlik şirketleri için yasal zorunlu sorumluluk sigortasıdır.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>Özel Güvenlik Mali Sorumluluk, riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
Özel Güvenlik Mali Sorumluluk her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'ozel-guvenlik-mali-sorumluluk' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">TARSİM kapsamında tahıl, meyve, sebze ve tarım ürünleri için dolu, fırtına, hortum, sel teminatı sağlar — devlet primin yarısını öder.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>Bitkisel Ürün Sigortası, riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
Bitkisel Ürün Sigortası her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'bitkisel-urun' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">TARSİM kapsamında sera yapısı ve içindeki ürünler için kapsamlı doğal afet ve yangın güvencesidir.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>Sera Sigortası, riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
Sera Sigortası her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'sera-sigortasi' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Koyun, keçi gibi küçükbaş hayvanlar için hastalık, kaza ve afet kaynaklı kayıp teminatıdır.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>Küçükbaş Hayvan Hayat Sigortası, riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
Küçükbaş Hayvan Hayat Sigortası her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'kucukbas-hayvan-hayat' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Sığır ve manda gibi büyükbaş hayvanlar için TARSİM destekli hayat sigortasıdır.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>Büyükbaş Hayvan Hayat Sigortası, riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
Büyükbaş Hayvan Hayat Sigortası her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'buyukbas-hayvan-hayat' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Tavuk, hindi gibi kanatlı hayvancılık işletmeleri için sürü hayat güvencesidir.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>Kümes Hayvanları Hayat Sigortası, riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
Kümes Hayvanları Hayat Sigortası her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'kumes-hayvanlari-hayat' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Balık çiftlikleri ve su ürünleri yetiştiriciliği için doğal afet ve hastalık teminatı sağlar.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>Su Ürünleri Sigortası, riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
Su Ürünleri Sigortası her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'su-urunleri-sigortasi' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Arı kovanları ve bal üretimi için doğal afet, hayvan kaybı ve hırsızlık güvencesi sunar.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>Arıcılık Sigortası, riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
Arıcılık Sigortası her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'aricilik-sigortasi' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Gemi ve tekne gövdesi için çarpışma, fırtına, yangın ve diğer denizcilik risklerine karşı kapsamlı sigorta.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>Tekne (Hull) Sigortası, riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
Tekne (Hull) Sigortası her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'tekne-hull' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Özel ve ticari yatlar için gövde, makine, donanım ve sorumluluk teminatlarını içeren kapsamlı paket.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>Yat Sigortası, riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
Yat Sigortası her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'yat-sigortasi' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Yıllık abonman sözleşmesiyle ihracat-ithalat yüklerinde mal hasarı, eksilme ve hırsızlık teminatı.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>Nakliyat Emtea Abonman, riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
Nakliyat Emtea Abonman her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'nakliyat-emtea-abonman' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Lojistik firmaları için müşteri yüküne karşı sorumluluğu karşılayan, CMR şartlarıyla uyumlu poliçe.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>Taşıyıcı Sorumluluk, riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
Taşıyıcı Sorumluluk her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'tasiyici-sorumluluk' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Tersanelerde gemi/tekne inşaat süreci boyunca yaşanabilecek hasarları karşılar.</div>
<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-shield-check"></i> Bu Sigorta Neyi Kapsar?</h3>
<p>Tekne İnşaat Sigortası, riskinize özgü teminatlarla tasarlanmış bir poliçedir. Anlaşmalı 12+ sigorta şirketinden teklif çekerek sizin durumunuza en uygun:</p>
<ul>
<li>Teminat limitlerini doğru ayarlayan,</li>
<li>İstisnaları net belirlenmiş,</li>
<li>Hasar süreci hızlı ilerleyen,</li>
<li>Prim açısından en avantajlı paketi seçeriz.</li>
</ul>
<div class="alert alert-info mt-4 d-flex gap-3 align-items-start">
<i class="bi bi-info-circle-fill fs-4"></i>
<div>
<strong class="d-block mb-1">Detaylı bilgi için temsilcimiz arasın</strong>
Tekne İnşaat Sigortası her durumda farklı teminat ve limit gerektirebilir. Talep formunu doldurun, müsait temsilcimiz size özel ürün analizi yaparak karşılaştırmalı teklif hazırlasın.
</div>
</div>' WHERE `slug` = 'tekne-insaat-sigortasi' AND (`aciklama` IS NULL OR `aciklama` = '' OR LENGTH(`aciklama`) < 200);


-- Bitti.

-- ====================================================
-- v1.1.15 - Hasar rehber CMS sayfalari
-- (hepiyi.com.tr/hasar-islemleri tarzi yonlendirme icerikleri)
-- ====================================================

INSERT IGNORE INTO `mz_sayfalar` (`slug`,`baslik`,`icerik`,`menude_goster`,`menu_sirasi`,`aktif`) VALUES
('hasar-anlik-rehber','Hasar Anında Ne Yapmalısınız?',
'<div class="lead mb-4">Beklenmedik bir hasar yaşadığınızda ilk dakikalar kritik. Bu rehber, en sık karşılaşılan üç hasar türü için adım adım eylem planını sunar — kazaya, yangına ya da sele uğradığınızda tam olarak ne yapacağınızı bilirsiniz.</div>

<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-car-front-fill"></i> Trafik Kazası Anında</h3>
<ol>
<li><strong>Hayatınızı önceleyin.</strong> Kaza alanından güvenli bir noktaya çekilin. Yaralı varsa <strong>112 Acil Sağlık</strong>''ı arayın.</li>
<li><strong>Aracı emniyet üçgeniyle işaretleyin.</strong> Trafiğin yönüne göre 30-150 metre arasında üçgeni yerleştirin.</li>
<li><strong>Polise haber verin.</strong> Yaralanma, ölüm, kamu malına zarar, ehliyet/poliçe eksikliği veya alkol şüphesi varsa <strong>155</strong> mutlaka aranır.</li>
<li><strong>Anlaşmalı tutanak doldurun.</strong> Sadece maddi hasarlı kazalarda taraflar ortak tutanak doldurabilir (Mobil Kaza Tutanağı MKT uygulaması veya basılı form).</li>
<li><strong>Olay yerini fotoğraflayın.</strong> Plakaları, hasarlı bölgeleri, çevre koşulları, fren izlerini, trafik işaretlerini farklı açılardan çekin.</li>
<li><strong>Tanıkları kayıt altına alın.</strong> İsim, telefon, açıklama — yazılı not da alabilirsiniz.</li>
<li><strong>Mizan Sigorta''ya bildirin.</strong> İlk fırsatta hasar ihbar formumuzu doldurun veya 7/24 hattımızı arayın.</li>
</ol>

<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-fire"></i> Yangın Hasarında</h3>
<ol>
<li><strong>Önce kendinizi ve yakınlarınızı çıkarın.</strong> Asansör kullanmayın, dumandan korunmak için yere yakın hareket edin.</li>
<li><strong>İtfaiyeyi arayın: 110.</strong> Olayı net açıklayın, adresi ve binadaki insan sayısını paylaşın.</li>
<li><strong>Mümkünse elektriği ve gazı kapatın.</strong> Ana şalteri ve doğalgaz vanasını kapatın — alev kaynağını sınırlayın.</li>
<li><strong>İtfaiye raporu mutlaka alın.</strong> Sigorta süreci için hayati önem taşır.</li>
<li><strong>Hasar mahallini koruyun.</strong> Sigortacı eksperi gelene kadar olay yerini değiştirmeyin, varsa fotoğraflarla belgeleyin.</li>
<li><strong>Faturalarınızı saklayın.</strong> Hasar gören eşyaların alış faturalarını (varsa) bir araya getirin.</li>
</ol>

<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-tsunami"></i> Sel ve Doğal Afet</h3>
<ol>
<li><strong>Güvenli bir noktaya çekilin.</strong> Su yükselen yerden uzaklaşın, akıntıya girmeyin.</li>
<li><strong>Aracınızı sel sularında çalıştırmayın.</strong> Motor su çekerse ciddi hasar verir, sigorta kapsam dışına çıkabilir.</li>
<li><strong>AFAD/itfaiye/jandarma raporu alın.</strong> Doğal afet dosyaları için resmi belge zorunludur.</li>
<li><strong>Hasarı eksiltmeden fotoğraflayın.</strong> Su seviyesini, hasarlı eşyaları, yapısal çatlakları belgeleyin.</li>
<li><strong>Eşyaları temizlemeden önce ekspere haber verin.</strong> Bazı şirketler eksper raporu sonrası temizliği talep eder.</li>
</ol>

<div class="alert alert-warning mt-4">
<h6 class="fw-bold mb-2"><i class="bi bi-lightbulb-fill"></i> Mizan İpucu — İlk 5 Gün Kuralı</h6>
<p class="mb-0">Hasarınızı sigorta şirketine genelde 5 iş günü içinde bildirmeniz gerekir. Geç kalan ihbarlar tazminat hakkınızı azaltabilir veya tümüyle düşürebilir. Şüpheniz varsa hemen bizi arayın — birlikte değerlendirelim.</p>
</div>

<div class="text-center mt-4">
<a href="../hasar-ihbari" class="btn btn-warning btn-lg fw-semibold"><i class="bi bi-send-fill"></i> Hemen Hasar İhbarı Yap</a>
</div>',
0, 0, 1),

('kaza-tespit-tutanagi','Kaza Tespit Tutanağı Nedir? Nasıl Doldurulur?',
'<div class="lead mb-4">Kaza tespit tutanağı (Anlaşmalı Tutanak), maddi hasarlı trafik kazalarında polise gerek kalmadan tarafların ortaklaşa düzenlediği yasal belgedir. Doğru doldurulduğunda hasar süreci hızlanır.</div>

<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-check-circle"></i> Anlaşmalı Tutanak Hangi Durumlarda Doldurulur?</h3>
<ul>
<li>Kazada yaralanma veya ölüm <strong>yoksa</strong></li>
<li>Tarafların geçerli sürücü belgesi <strong>varsa</strong></li>
<li>Tarafların geçerli trafik sigortası <strong>varsa</strong></li>
<li>Kamu malına zarar <strong>vermemişse</strong> (direk, levha, bariyer vb.)</li>
<li>Sürücülerden hiçbiri alkollü ya da uyuşturucu etkisinde <strong>değilse</strong></li>
<li>Bir araç tek başına ve içinde başka biri olmadan kazaya <strong>uğramamışsa</strong></li>
</ul>

<div class="alert alert-danger mt-4">
<strong><i class="bi bi-exclamation-triangle-fill"></i> Bu durumlardan biri yoksa polise haber verin: 155</strong>
</div>

<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-clipboard-check"></i> Tutanağa Yazılacak Bilgiler</h3>
<div class="row g-3 mb-3">
<div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem;border-radius:0 8px 8px 0"><h6 class="fw-bold">Kaza Bilgileri</h6><ul class="small mb-0"><li>Kaza tarihi ve saati</li><li>Adres / il / ilçe</li><li>Tanık varsa ad-soyad-telefon</li></ul></div></div>
<div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem;border-radius:0 8px 8px 0"><h6 class="fw-bold">Sürücü Bilgileri</h6><ul class="small mb-0"><li>Ad-soyad, TC, ehliyet no</li><li>Plaka, ruhsat sahibi</li><li>Sigorta şirketi ve poliçe no</li></ul></div></div>
<div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem;border-radius:0 8px 8px 0"><h6 class="fw-bold">Olay Krokisi</h6><ul class="small mb-0"><li>Yolun durumunu çizin (şerit, yön)</li><li>Araçların kazadan önceki ve sonraki konumu</li><li>Çarpma noktalarını ok ile gösterin</li></ul></div></div>
<div class="col-md-6"><div style="background:#f8f9fa;border-left:4px solid var(--mz-red);padding:1rem;border-radius:0 8px 8px 0"><h6 class="fw-bold">Hasar Görselleri</h6><ul class="small mb-0"><li>Her aracın hasarlı bölgelerini ayrı işaretleyin</li><li>Genel ve detay fotoğraflar çekin</li><li>Plakalar net görünmeli</li></ul></div></div>
</div>

<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-list-check"></i> İmza Aşaması</h3>
<ol>
<li>Tutanağı her iki taraf da okuyup onaylar.</li>
<li>Her iki sürücü <strong>ıslak imza</strong> atar.</li>
<li>Bir kopyası her iki tarafta kalır (basılı form 2 nüsha).</li>
<li>Mobil Kaza Tutanağı (MKT) kullanıyorsanız sistem otomatik olarak iki tarafa da iletir.</li>
</ol>

<div class="alert alert-info mt-4">
<h6 class="fw-bold mb-2"><i class="bi bi-phone"></i> Mobil Kaza Tutanağı (MKT) — Daha Kolay Yol</h6>
<p class="mb-2">SBM''nin geliştirdiği <strong>MKT mobil uygulaması</strong>, basılı tutanak yerine telefonunuzla anlaşmalı tutanak hazırlamanıza imkan tanır:</p>
<ul class="small mb-0">
<li>App Store ve Google Play''den ücretsiz indirilir</li>
<li>Plaka okuma + fotoğraf çekimi otomatik yapılır</li>
<li>Karşı tarafla QR kod ile eşleşip dijital imza atılır</li>
<li>Sigorta şirketlerine doğrudan iletilir</li>
</ul>
</div>

<div class="text-center mt-4">
<a href="https://www.sbm.org.tr/tr/mobil-kaza-tutanagi-mkt" target="_blank" rel="noopener" class="btn btn-outline-primary me-2">SBM MKT Sayfası <i class="bi bi-box-arrow-up-right"></i></a>
<a href="../hasar-ihbari" class="btn btn-warning fw-semibold">Hasar İhbarı Yap <i class="bi bi-send-fill"></i></a>
</div>',
0, 0, 1),

('deger-kaybi-basvurusu','Değer Kaybı Başvurusu — Gerekli Evraklar',
'<div class="lead mb-4">Trafik kazasında kusursuz veya az kusurlu olduğunuz halde aracınız hasar gördüyse, karşı tarafın trafik sigortasından <strong>değer kaybı tazminatı</strong> talep edebilirsiniz. İşte süreç ve gerekli evraklar.</div>

<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-info-circle"></i> Değer Kaybı Nedir?</h3>
<p>Aracınız kazadan sonra onarılsa bile ikinci el piyasa değeri düşer. Bu fark — onarım sonrası araçtaki azalan değer — değer kaybı olarak ifade edilir ve karşı tarafın trafik sigortasından talep edilebilir.</p>

<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-list-check"></i> Başvuru İçin Gerekli Belgeler</h3>
<div class="row g-3">
<div class="col-md-6">
<div style="background:#f8f9fa;border-left:4px solid #198754;padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%">
<h6 class="fw-bold" style="color:var(--mz-navy)"><i class="bi bi-check-circle-fill text-success"></i> Kaza Belgeleri</h6>
<ul class="small mb-0">
<li>Kaza tespit tutanağı (anlaşmalı veya polis)</li>
<li>Alkol raporu (varsa)</li>
<li>Olay yeri fotoğrafları</li>
</ul>
</div>
</div>
<div class="col-md-6">
<div style="background:#f8f9fa;border-left:4px solid #198754;padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%">
<h6 class="fw-bold" style="color:var(--mz-navy)"><i class="bi bi-check-circle-fill text-success"></i> Araç Belgeleri</h6>
<ul class="small mb-0">
<li>Ruhsat fotokopisi</li>
<li>Sürücü belgesi fotokopisi</li>
<li>Aracın kaza öncesi durumu (eski kazalar)</li>
</ul>
</div>
</div>
<div class="col-md-6">
<div style="background:#f8f9fa;border-left:4px solid #198754;padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%">
<h6 class="fw-bold" style="color:var(--mz-navy)"><i class="bi bi-check-circle-fill text-success"></i> Onarım Belgeleri</h6>
<ul class="small mb-0">
<li>Servis faturası (orijinal)</li>
<li>İş emri / hasar dökümü</li>
<li>Onarılan parçaların listesi</li>
<li>Onarım öncesi/sonrası fotoğraflar</li>
</ul>
</div>
</div>
<div class="col-md-6">
<div style="background:#f8f9fa;border-left:4px solid #198754;padding:1rem 1.25rem;border-radius:0 8px 8px 0;height:100%">
<h6 class="fw-bold" style="color:var(--mz-navy)"><i class="bi bi-check-circle-fill text-success"></i> Kişisel Belgeler</h6>
<ul class="small mb-0">
<li>Kimlik fotokopisi</li>
<li>IBAN / banka hesap bilgisi</li>
<li>Vekaletname (avukat üzerinden başvuru ise)</li>
</ul>
</div>
</div>
</div>

<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-clock-history"></i> Süreç ve Süre</h3>
<ol>
<li><strong>Hasar onarımı tamamlanır.</strong> Aracınız tamir edilir, faturalar saklanır.</li>
<li><strong>Karşı tarafın sigortasına başvurulur.</strong> Belgelerle birlikte yazılı başvuru yapılır.</li>
<li><strong>Sigorta şirketi 8 iş günü içinde değerlendirir.</strong> Eksik belge varsa bildirilir.</li>
<li><strong>Onaylanan tutar IBAN''a yatırılır.</strong> Reddedilen başvuru için Sigorta Tahkim Komisyonu''na itiraz hakkı vardır.</li>
</ol>

<div class="alert alert-warning mt-4">
<h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill"></i> Önemli Hatırlatmalar</h6>
<ul class="mb-0 small">
<li><strong>Zaman aşımı:</strong> Kaza tarihinden itibaren 2 yıl içinde başvurmanız gerekir.</li>
<li><strong>Kusur durumu önemli:</strong> Tam kusurluysanız değer kaybı talep edemezsiniz.</li>
<li><strong>Aracın yaşı:</strong> Genellikle ilk tescilden 165.000 km veya 12 yaş üstü araçlar değerlendirme dışı kalır (şirkete göre değişir).</li>
<li><strong>Mizan üzerinden başvuru:</strong> Süreci sizin yerinize biz takip edebiliriz — temsilcinizle iletişime geçin.</li>
</ul>
</div>

<div class="text-center mt-4">
<a href="../iletisim" class="btn btn-warning btn-lg fw-semibold"><i class="bi bi-headset"></i> Bize Ulaşın</a>
</div>',
0, 0, 1),

('anlasmali-saglik-kurumlari','Anlaşmalı Sağlık Kurumları',
'<div class="lead mb-4">Sağlık sigortanızı kullanırken fark ücretsiz tedavi alabileceğiniz anlaşmalı özel hastane ve poliklinikler. Liste şirkete ve ürüne göre değişir — temsilciniz size özel listeyi paylaşır.</div>

<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-info-circle"></i> Nasıl Çalışır?</h3>
<p>Tamamlayıcı sağlık sigortanız varsa, anlaşmalı kurumlarda <strong>SGK farkını sigortanız öder</strong> — siz ek ücret ödemezsiniz. Özel sağlık sigortanız varsa, anlaşmalı kurumlarda <strong>fark ücretsiz</strong> hizmet alırsınız.</p>

<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-list-ul"></i> Genelde Anlaşmalı Olunan Zincirler</h3>
<div class="row g-3">
<div class="col-md-6 col-lg-4"><div style="background:#f8f9fa;padding:1rem;border-radius:8px;text-align:center"><strong>Acıbadem</strong></div></div>
<div class="col-md-6 col-lg-4"><div style="background:#f8f9fa;padding:1rem;border-radius:8px;text-align:center"><strong>Memorial</strong></div></div>
<div class="col-md-6 col-lg-4"><div style="background:#f8f9fa;padding:1rem;border-radius:8px;text-align:center"><strong>Liv Hospital</strong></div></div>
<div class="col-md-6 col-lg-4"><div style="background:#f8f9fa;padding:1rem;border-radius:8px;text-align:center"><strong>Medicana</strong></div></div>
<div class="col-md-6 col-lg-4"><div style="background:#f8f9fa;padding:1rem;border-radius:8px;text-align:center"><strong>Florence Nightingale</strong></div></div>
<div class="col-md-6 col-lg-4"><div style="background:#f8f9fa;padding:1rem;border-radius:8px;text-align:center"><strong>Yeditepe Hastanesi</strong></div></div>
<div class="col-md-6 col-lg-4"><div style="background:#f8f9fa;padding:1rem;border-radius:8px;text-align:center"><strong>Medical Park</strong></div></div>
<div class="col-md-6 col-lg-4"><div style="background:#f8f9fa;padding:1rem;border-radius:8px;text-align:center"><strong>Anadolu Sağlık Merkezi</strong></div></div>
<div class="col-md-6 col-lg-4"><div style="background:#f8f9fa;padding:1rem;border-radius:8px;text-align:center"><strong>VKV Amerikan Hastanesi</strong></div></div>
</div>

<div class="alert alert-info mt-4">
<h6 class="fw-bold mb-2"><i class="bi bi-info-circle-fill"></i> Şirkete Göre Değişir</h6>
<p class="mb-0">Anlaşmalı kurum listesi her sigorta şirketi ve her poliçe paketi için farklıdır. Yukarıdaki liste genel bir referanstır. <strong>Sizin poliçenizin geçerli olduğu kurumların tam listesini temsilciniz veya sigorta şirketinizin müşteri hizmetleri size iletecektir.</strong></p>
</div>

<h3 class="fw-bold mt-4 mb-3" style="color:var(--mz-navy)"><i class="bi bi-question-circle"></i> Hizmet Almadan Önce</h3>
<ol>
<li><strong>Provizyon alın.</strong> Hastaneye gitmeden önce sigorta şirketini arayarak veya online sistemden tedavinin onayını alın.</li>
<li><strong>Bekleme sürelerini kontrol edin.</strong> Bazı tedaviler için poliçe başlangıcından sonra 3-6 aylık bekleme süresi olabilir.</li>
<li><strong>Sevk gerekiyor mu?</strong> Bazı paketlerde uzman doktora geçmek için aile hekimi sevki istenebilir.</li>
<li><strong>Limitlerinizi bilin.</strong> Yıllık ödenecek maksimum tutar, ameliyat limiti vb. önceden netleştirin.</li>
</ol>

<div class="text-center mt-4">
<a href="../urun/saglik-sigortalari" class="btn btn-outline-primary me-2"><i class="bi bi-heart-pulse"></i> Sağlık Sigortaları</a>
<a href="../iletisim" class="btn btn-warning fw-semibold"><i class="bi bi-headset"></i> Bize Ulaşın</a>
</div>',
0, 0, 1);

-- ===========================================================
-- v1.1.15 - Urun aciklamalari ZENGIN icerik (Hepiyi tarzi)
-- 7 kritik urun: kasko, trafik, konut, dask, ozel-saglik, tamamlayici-saglik, ferdi-kaza-sigortasi
-- Lead + One Cikan + Diger Teminatlar + Avantajlar + SSS + CTA
-- ===========================================================

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Kasko sigortasi, aracinizin carpisma, yangin, hirsizlik, dogal afet ve bircok riske karsi kapsamli koruma altina alindigi motorlu kara tasiti sigortasidir. Trafik sigortasi karsi tarafi korurken, kasko <strong>sizin aracinizi</strong> teminat altina alir.</div><h3 class="fw-bold mt-5 mb-3" style="color:var(--mz-navy)"><i class="bi bi-stars text-warning"></i> Öne Çıkan Teminatlar</h3><div class="row g-3 mb-4"><div class="col-md-6 col-lg-3"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:14px;padding:1.25rem;text-align:center;height:100%;transition:all .25s"><div style="font-size:2.5rem;line-height:1;margin-bottom:.5rem">🚗</div><h6 class="fw-bold" style="color:var(--mz-navy)">Carpma</h6><p class="small text-muted mb-0">Aracinizin sabit ve hareketli cisimlere carpmasindan dogan zararlar.</p></div></div><div class="col-md-6 col-lg-3"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:14px;padding:1.25rem;text-align:center;height:100%;transition:all .25s"><div style="font-size:2.5rem;line-height:1;margin-bottom:.5rem">💥</div><h6 class="fw-bold" style="color:var(--mz-navy)">Carpisma</h6><p class="small text-muted mb-0">Bir baska araclarla karsilikli carpismadan kaynaklanan hasarlar.</p></div></div><div class="col-md-6 col-lg-3"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:14px;padding:1.25rem;text-align:center;height:100%;transition:all .25s"><div style="font-size:2.5rem;line-height:1;margin-bottom:.5rem">🔥</div><h6 class="fw-bold" style="color:var(--mz-navy)">Yangin</h6><p class="small text-muted mb-0">Yangin, infilak ve yildirim sonucu aracinizda meydana gelen hasarlar.</p></div></div><div class="col-md-6 col-lg-3"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:14px;padding:1.25rem;text-align:center;height:100%;transition:all .25s"><div style="font-size:2.5rem;line-height:1;margin-bottom:.5rem">🚨</div><h6 class="fw-bold" style="color:var(--mz-navy)">Hirsizlik</h6><p class="small text-muted mb-0">Aracin calinmasi veya calinma girisimi sirasinda olusan hasarlar.</p></div></div></div><h3 class="fw-bold mt-5 mb-3" style="color:var(--mz-navy)"><i class="bi bi-list-check"></i> Diğer Teminatlar</h3><div class="row g-2 mb-4"><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Deprem</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Sel ve Su Baskini</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Toprak Kaymasi</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Firtina ve Dolu</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Yildirim</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Grev, Lokavt, Halk Hareketi, Teror (GLKHHT)</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Kilit Sisteminin Degistirilmesi</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Anahtar Calinmasi ile Aracin Kaybi</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Yetkisiz Cekilme</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Ferdi Kaza Koltuk</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Hukuksal Koruma Teminati</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Yurt Disi Teminati</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Ikame Arac</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Hasarsizlik Koruma</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Mini Onarim</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Orijinal Cam Degisimi</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Anahtar Teslim Yenisi (0 km icin)</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">3. Sahislara Verilen Zararlar (IMM)</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Manevi Tazminat</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Yanlis Akaryakit</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Hayvanlarin Verecegi Zararlar</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Kisisel Esya Teminati</span></div></div></div><h3 class="fw-bold mt-5 mb-3" style="color:var(--mz-navy)"><i class="bi bi-trophy-fill text-warning"></i> Ürün Avantajları</h3><div class="row g-3 mb-4"><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-tools" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Yaygin Yetkili Servis Agi</h6><p class="small text-muted mb-0">Turkiye genelinde 1.500+ anlasmali servis secenegi.</p></div></div><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-droplet-half" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Mini Onarim Hizmeti</h6><p class="small text-muted mb-0">Ufak ciziklerde fark ucretsiz hizli onarim.</p></div></div><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-truck-front" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Yol Yardim</h6><p class="small text-muted mb-0">Cekici, lastik patlamasi, aku takviyesi 7/24.</p></div></div><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-car-front" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Ikame Arac</h6><p class="small text-muted mb-0">Araciniz serviste iken yedek arac imkani.</p></div></div><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-window" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Orijinal Cam Degisimi</h6><p class="small text-muted mb-0">Anlasmali camcilarda fark ucretsiz degisim.</p></div></div><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-percent" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Hasarsizlik Indirimi</h6><p class="small text-muted mb-0">Hasarsiz her yil yuzde 30 baslayip yuzde 65 cikar.</p></div></div></div><h3 class="fw-bold mt-5 mb-3" style="color:var(--mz-navy)"><i class="bi bi-question-circle-fill text-warning"></i> Sıkça Sorulan Sorular</h3><div class="accordion mb-4" id="urunSSS-kasko"><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#sss-kasko-0">Kasko zorunlu mu?</button></h2><div id="sss-kasko-0" class="accordion-collapse collapse show" data-bs-parent="#urunSSS-kasko"><div class="accordion-body"><p class="mb-0">Hayir, kasko istege baglidir. Trafik sigortasi zorunludur, kasko ise arac sahibinin tercihine baglidir. Ancak arac degeri yuksekse veya kredili aracsa siddetle onerilir.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-kasko-1">Kasko fiyati neye gore belirlenir?</button></h2><div id="sss-kasko-1" class="accordion-collapse collapse" data-bs-parent="#urunSSS-kasko"><div class="accordion-body"><p class="mb-0">Aracin markasi, modeli, yasi ve degeri; surucunun hasarsizlik gecmisi; ikamet edilen il ve aracin kullanim amaci kasko primini belirleyen ana etkenlerdir.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-kasko-2">Hasarsizlik indirimi nedir?</button></h2><div id="sss-kasko-2" class="accordion-collapse collapse" data-bs-parent="#urunSSS-kasko"><div class="accordion-body"><p class="mb-0">Hasarsiz gecirdiginiz her yil prime indirim olarak yansir. 1. yil 30, 2. yil 40, 3. yil 50, 4. yil 60 ve 5+ yil 65 olarak uygulanir.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-kasko-3">Aracimi sattim, hasarsizlik indirimim ne olur?</button></h2><div id="sss-kasko-3" class="accordion-collapse collapse" data-bs-parent="#urunSSS-kasko"><div class="accordion-body"><p class="mb-0">Hasarsizlik indirimi araca degil size aittir. Yeni aracinizla 1 yil icinde gecerseniz indirim hakki korunur.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-kasko-4">Sigorta sirketimi degistirirsem hasarsizlik bozulur mu?</button></h2><div id="sss-kasko-4" class="accordion-collapse collapse" data-bs-parent="#urunSSS-kasko"><div class="accordion-body"><p class="mb-0">Hayir. Hasarsizlik indirimi kisiye taninmis bir haktir. Sirket degisikliginde de devam eder.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-kasko-5">Kasko hangi durumlarda odeme yapmaz?</button></h2><div id="sss-kasko-5" class="accordion-collapse collapse" data-bs-parent="#urunSSS-kasko"><div class="accordion-body"><p class="mb-0">Alkol/uyusturucu etkisi altinda kullanim, kasitli hasarlar, yetkisiz kisi tarafindan kullanim, periyodik bakim yapilmamasi nedeniyle curume/eskime gibi durumlar kapsam disidir.</p></div></div></div></div><div class="alert alert-warning mt-4 d-flex gap-3 align-items-center"><i class="bi bi-lightbulb-fill fs-3"></i><div class="flex-grow-1"><strong class="d-block">Size en uygun teklifi karşılaştıralım</strong><span class="small">Anlaşmalı 12+ sigorta şirketinden teklifleri toplayıp size en uygun paketi sunalım. Süreç ücretsiz, bağlayıcılığı yok.</span></div></div>' WHERE `slug` = 'kasko';

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Trafik sigortasi, <strong>2918 sayili Karayollari Trafik Kanunu</strong> geregi tum motorlu araclar icin yapilmasi zorunlu olan sorumluluk sigortasidir. Kazada karsi tarafa verilen maddi ve bedensel zararlari karsilar.</div><h3 class="fw-bold mt-5 mb-3" style="color:var(--mz-navy)"><i class="bi bi-stars text-warning"></i> Öne Çıkan Teminatlar</h3><div class="row g-3 mb-4"><div class="col-md-6 col-lg-3"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:14px;padding:1.25rem;text-align:center;height:100%;transition:all .25s"><div style="font-size:2.5rem;line-height:1;margin-bottom:.5rem">🚦</div><h6 class="fw-bold" style="color:var(--mz-navy)">Yasal Zorunlu</h6><p class="small text-muted mb-0">Karayolunda arac bulundurmanin yasal sarti.</p></div></div><div class="col-md-6 col-lg-3"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:14px;padding:1.25rem;text-align:center;height:100%;transition:all .25s"><div style="font-size:2.5rem;line-height:1;margin-bottom:.5rem">🛡️</div><h6 class="fw-bold" style="color:var(--mz-navy)">Ucuncu Sahis Korumasi</h6><p class="small text-muted mb-0">Karsi tarafa verilen zararlari karsilar.</p></div></div><div class="col-md-6 col-lg-3"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:14px;padding:1.25rem;text-align:center;height:100%;transition:all .25s"><div style="font-size:2.5rem;line-height:1;margin-bottom:.5rem">💰</div><h6 class="fw-bold" style="color:var(--mz-navy)">Maddi Tazminat</h6><p class="small text-muted mb-0">Karsi aracin hasari, esyalari, gelir kaybi.</p></div></div><div class="col-md-6 col-lg-3"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:14px;padding:1.25rem;text-align:center;height:100%;transition:all .25s"><div style="font-size:2.5rem;line-height:1;margin-bottom:.5rem">🏥</div><h6 class="fw-bold" style="color:var(--mz-navy)">Bedensel Tazminat</h6><p class="small text-muted mb-0">Yaralanma, surekli sakatlik, olum tazminatlari.</p></div></div></div><h3 class="fw-bold mt-5 mb-3" style="color:var(--mz-navy)"><i class="bi bi-list-check"></i> Diğer Teminatlar</h3><div class="row g-2 mb-4"><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Maddi Tazminat (Karsi arac hasari)</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Bedensel Tazminat (Yaralanma, sakatlik, olum)</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Tedavi Giderleri</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Gecici Is Goremezlik</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Surekli Sakatlik Tazminati</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Destekten Yoksun Kalma</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Manevi Tazminat (sinirli)</span></div></div></div><h3 class="fw-bold mt-5 mb-3" style="color:var(--mz-navy)"><i class="bi bi-trophy-fill text-warning"></i> Ürün Avantajları</h3><div class="row g-3 mb-4"><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-shield-check" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Yasal Guvence</h6><p class="small text-muted mb-0">Trafik kontrolunde belge yoklugu cezasi onlenir.</p></div></div><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-people-fill" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Karsi Taraf Garantisi</h6><p class="small text-muted mb-0">Kaza yaptiginizda karsi tarafa oz cebinizden odeme yapmazsiniz.</p></div></div><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-piggy-bank" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Hasarsizlik Basamagi</h6><p class="small text-muted mb-0">8 basamakli sistem - her hasarsiz yil bir basamak yukari.</p></div></div><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-arrow-repeat" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Otomatik Yenileme</h6><p class="small text-muted mb-0">Yenileme tarihi yaklastiginda size hatirlatma yapariz.</p></div></div></div><h3 class="fw-bold mt-5 mb-3" style="color:var(--mz-navy)"><i class="bi bi-question-circle-fill text-warning"></i> Sıkça Sorulan Sorular</h3><div class="accordion mb-4" id="urunSSS-trafik-zorunlu-sorumluluk"><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#sss-trafik-zorunlu-sorumluluk-0">Trafik sigortasi neyi kapsar?</button></h2><div id="sss-trafik-zorunlu-sorumluluk-0" class="accordion-collapse collapse show" data-bs-parent="#urunSSS-trafik-zorunlu-sorumluluk"><div class="accordion-body"><p class="mb-0">Sadece ucuncu sahislara (karsi tarafa) verilen maddi ve bedensel zararlari kapsar. Kendi aracinizin hasari KAPSAMI DISINDADIR - bunun icin kasko gerekir.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-trafik-zorunlu-sorumluluk-1">Trafik sigortasi ile kasko arasinda ne fark var?</button></h2><div id="sss-trafik-zorunlu-sorumluluk-1" class="accordion-collapse collapse" data-bs-parent="#urunSSS-trafik-zorunlu-sorumluluk"><div class="accordion-body"><p class="mb-0">Trafik karsi tarafi, kasko ise sizi korur. Trafik zorunludur, kasko ihtiyaridir. Ideal olan ikisini de yaptirmaktir.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-trafik-zorunlu-sorumluluk-2">Trafik sigortasi fiyati neye gore belirlenir?</button></h2><div id="sss-trafik-zorunlu-sorumluluk-2" class="accordion-collapse collapse" data-bs-parent="#urunSSS-trafik-zorunlu-sorumluluk"><div class="accordion-body"><p class="mb-0">Hasarsizlik basamagi (1-8), aracin turu (otomobil/kamyon vb.), motor gucu ve aracin yasi ana etkenlerdir. Tarifeler her yil Hazine ve Maliye Bakanligi tarafindan belirlenir.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-trafik-zorunlu-sorumluluk-3">Trafik sigortasi olmadan trafige cikarsam ne olur?</button></h2><div id="sss-trafik-zorunlu-sorumluluk-3" class="accordion-collapse collapse" data-bs-parent="#urunSSS-trafik-zorunlu-sorumluluk"><div class="accordion-body"><p class="mb-0">Yasal olarak arac trafikten men edilir, idari para cezasi kesilir, ehliyet suresi azaltilir. Bir kaza durumunda tum zararlar sahsen odenir.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-trafik-zorunlu-sorumluluk-4">Hasarsizlik basamagim ne ise yarar?</button></h2><div id="sss-trafik-zorunlu-sorumluluk-4" class="accordion-collapse collapse" data-bs-parent="#urunSSS-trafik-zorunlu-sorumluluk"><div class="accordion-body"><p class="mb-0">8 basamakli sistemde ust basamaga ciktikca primlerde indirim alir, alt basamaga dustukce zam gorursunuz. Sigortaniz sirasinda olusan kazalar basamagi dusurur.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-trafik-zorunlu-sorumluluk-5">Trafik sigortasinda ne tur kaza odemez?</button></h2><div id="sss-trafik-zorunlu-sorumluluk-5" class="accordion-collapse collapse" data-bs-parent="#urunSSS-trafik-zorunlu-sorumluluk"><div class="accordion-body"><p class="mb-0">Aracinizin tek basina kaza yapmasi (size ait), alkollu/uyusturucu etkisinde kullanim, ehliyetsiz kullanim, kasitli hasarlar ve trafik kurali disi kullanimlar kapsam disidir.</p></div></div></div></div><div class="alert alert-warning mt-4 d-flex gap-3 align-items-center"><i class="bi bi-lightbulb-fill fs-3"></i><div class="flex-grow-1"><strong class="d-block">Size en uygun teklifi karşılaştıralım</strong><span class="small">Anlaşmalı 12+ sigorta şirketinden teklifleri toplayıp size en uygun paketi sunalım. Süreç ücretsiz, bağlayıcılığı yok.</span></div></div>' WHERE `slug` = 'trafik-zorunlu-sorumluluk';

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Konut sigortasi; eviniz, esyalariniz ve dekorasyonunuzu yangin, hirsizlik, su baskini ve diger pek cok riske karsi kapsamli koruma altina alan paket sigortadir. Asistans hizmetleriyle birlikte 7/24 konforunuzu guvenceye alir.</div><h3 class="fw-bold mt-5 mb-3" style="color:var(--mz-navy)"><i class="bi bi-stars text-warning"></i> Öne Çıkan Teminatlar</h3><div class="row g-3 mb-4"><div class="col-md-6 col-lg-3"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:14px;padding:1.25rem;text-align:center;height:100%;transition:all .25s"><div style="font-size:2.5rem;line-height:1;margin-bottom:.5rem">🔥</div><h6 class="fw-bold" style="color:var(--mz-navy)">Yangin & Infilak</h6><p class="small text-muted mb-0">Yangin, infilak, yildirim sonucu yapi ve esya hasarlari.</p></div></div><div class="col-md-6 col-lg-3"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:14px;padding:1.25rem;text-align:center;height:100%;transition:all .25s"><div style="font-size:2.5rem;line-height:1;margin-bottom:.5rem">💧</div><h6 class="fw-bold" style="color:var(--mz-navy)">Su Baskini</h6><p class="small text-muted mb-0">Ic tesisat patlamasi, sel ve dis su baskini sonucu hasarlar.</p></div></div><div class="col-md-6 col-lg-3"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:14px;padding:1.25rem;text-align:center;height:100%;transition:all .25s"><div style="font-size:2.5rem;line-height:1;margin-bottom:.5rem">🚪</div><h6 class="fw-bold" style="color:var(--mz-navy)">Hirsizlik</h6><p class="small text-muted mb-0">Eve girisle gerceklesen hirsizliklarda esya kaybi.</p></div></div><div class="col-md-6 col-lg-3"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:14px;padding:1.25rem;text-align:center;height:100%;transition:all .25s"><div style="font-size:2.5rem;line-height:1;margin-bottom:.5rem">🌊</div><h6 class="fw-bold" style="color:var(--mz-navy)">Dogal Afetler</h6><p class="small text-muted mb-0">Firtina, dolu, yer kaymasi, cig hasarlari.</p></div></div></div><h3 class="fw-bold mt-5 mb-3" style="color:var(--mz-navy)"><i class="bi bi-list-check"></i> Diğer Teminatlar</h3><div class="row g-2 mb-4"><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Cam Kirilmasi</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Dahili Su Tesisat</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Komsuya Verilen Zararlar</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Kira Kaybi</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Asistans Hizmeti (Tesisatci, elektrikci, cilingir)</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Elektronik Cihaz Sigortasi</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Hirsizliga Karsi Para ve Kiymetli Evrak</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Kisisel Sorumluluk</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Cenaze ve Defin Masraflari</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Gecici Ikamet (Eviniz oturulamaz hale gelirse)</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Ferdi Kaza</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Aile Reisi Sorumluluk</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Acil Sihhi Yardim</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Veteriner Hizmetleri (Evcil hayvan)</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Hukuksal Koruma</span></div></div></div><h3 class="fw-bold mt-5 mb-3" style="color:var(--mz-navy)"><i class="bi bi-trophy-fill text-warning"></i> Ürün Avantajları</h3><div class="row g-3 mb-4"><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-house-heart" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Esya + Bina Birarada</h6><p class="small text-muted mb-0">Hem yapisal hasar hem ev ici esya teminati.</p></div></div><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-person-arms-up" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">7/24 Asistans</h6><p class="small text-muted mb-0">Tesisatci, elektrikci, cilingir, cam ustasi.</p></div></div><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-suitcase" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Gecici Konaklama</h6><p class="small text-muted mb-0">Eviniz oturulamaz hale gelirse otel masrafi.</p></div></div><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-piggy-bank" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Komsu Sorumlulugu</h6><p class="small text-muted mb-0">Sizinti vb. nedenlerle alt komsuya verilen zarar.</p></div></div><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-buildings" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Apartman Ortak Alan</h6><p class="small text-muted mb-0">Bina ortak alanlarinda ugradiginiz hasarlar.</p></div></div><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-percent" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Yillik Sabit Prim</h6><p class="small text-muted mb-0">Tek seferde yil boyu guvence - taksit imkani var.</p></div></div></div><h3 class="fw-bold mt-5 mb-3" style="color:var(--mz-navy)"><i class="bi bi-question-circle-fill text-warning"></i> Sıkça Sorulan Sorular</h3><div class="accordion mb-4" id="urunSSS-konut-sigortasi"><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#sss-konut-sigortasi-0">Konut sigortasi DASK ile ayni mi?</button></h2><div id="sss-konut-sigortasi-0" class="accordion-collapse collapse show" data-bs-parent="#urunSSS-konut-sigortasi"><div class="accordion-body"><p class="mb-0">Hayir. DASK <strong>sadece deprem</strong> ve yapisal hasari kapsar, zorunludur. Konut sigortasi ise yangin, hirsizlik, su baskini gibi daha genis riskleri ve <strong>esyalari</strong> kapsar, istege baglidir.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-konut-sigortasi-1">Kira evimde konut sigortasi yaptirabilir miyim?</button></h2><div id="sss-konut-sigortasi-1" class="accordion-collapse collapse" data-bs-parent="#urunSSS-konut-sigortasi"><div class="accordion-body"><p class="mb-0">Evet. Kiraci olarak <strong>esya teminati</strong> ana hedefiniz olur. Bina yapisi icin ev sahibinin sigortasi gecerlidir.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-konut-sigortasi-2">Esya degerimi nasil belirlerim?</button></h2><div id="sss-konut-sigortasi-2" class="accordion-collapse collapse" data-bs-parent="#urunSSS-konut-sigortasi"><div class="accordion-body"><p class="mb-0">Tum ev esyalarinizin guncel ikinci el piyasa degerini toplayin (mobilya, beyaz esya, elektronik, giyim). Pahali sanat eserleri/antika varsa ayrica beyan edin.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-konut-sigortasi-3">Hirsizlik teminati her zaman gecerli mi?</button></h2><div id="sss-konut-sigortasi-3" class="accordion-collapse collapse" data-bs-parent="#urunSSS-konut-sigortasi"><div class="accordion-body"><p class="mb-0">Genelde balkon/pencereden zorla giris, anahtar ile yetkisiz acma durumlarinda gecerli. Ev sahibinin/kiracinin kendi tarafindan calinma kapsam disidir.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-konut-sigortasi-4">Komsuya verdigim zarar odenir mi?</button></h2><div id="sss-konut-sigortasi-4" class="accordion-collapse collapse" data-bs-parent="#urunSSS-konut-sigortasi"><div class="accordion-body"><p class="mb-0">Evet. Eviniz kaynakli sizinti, yangin vb. nedenlerle alt-ust komsunuza zarar verirseniz, sigortaniz bunu karsilar.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-konut-sigortasi-5">Konut sigortasi ne kadar tutar?</button></h2><div id="sss-konut-sigortasi-5" class="accordion-collapse collapse" data-bs-parent="#urunSSS-konut-sigortasi"><div class="accordion-body"><p class="mb-0">Esya degerine, evin degerine, yasadiginiz bolgenin risk durumuna gore degisir. Genellikle <strong>ayda 100-300 TL</strong> civarinda bir paket bulunabilir.</p></div></div></div></div><div class="alert alert-warning mt-4 d-flex gap-3 align-items-center"><i class="bi bi-lightbulb-fill fs-3"></i><div class="flex-grow-1"><strong class="d-block">Size en uygun teklifi karşılaştıralım</strong><span class="small">Anlaşmalı 12+ sigorta şirketinden teklifleri toplayıp size en uygun paketi sunalım. Süreç ücretsiz, bağlayıcılığı yok.</span></div></div>' WHERE `slug` = 'konut-sigortasi';

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4"><strong>Dogal Afet Sigortalari Kurumu (DASK)</strong>, 2000 yilinda 587 sayili KHK ile kurulmus ve <strong>tum meskenler icin yasal zorunlu</strong> olan deprem sigortasidir. Bina yapisal hasarlarini teminat altina alir.</div><h3 class="fw-bold mt-5 mb-3" style="color:var(--mz-navy)"><i class="bi bi-stars text-warning"></i> Öne Çıkan Teminatlar</h3><div class="row g-3 mb-4"><div class="col-md-6 col-lg-3"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:14px;padding:1.25rem;text-align:center;height:100%;transition:all .25s"><div style="font-size:2.5rem;line-height:1;margin-bottom:.5rem">🏠</div><h6 class="fw-bold" style="color:var(--mz-navy)">Yasal Zorunlu</h6><p class="small text-muted mb-0">Tapu islemleri, su/elektrik abonelik icin zorunludur.</p></div></div><div class="col-md-6 col-lg-3"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:14px;padding:1.25rem;text-align:center;height:100%;transition:all .25s"><div style="font-size:2.5rem;line-height:1;margin-bottom:.5rem">🌐</div><h6 class="fw-bold" style="color:var(--mz-navy)">Devlet Guvencesi</h6><p class="small text-muted mb-0">DASK havuzu devlet kontrolunde, odemeler garantili.</p></div></div><div class="col-md-6 col-lg-3"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:14px;padding:1.25rem;text-align:center;height:100%;transition:all .25s"><div style="font-size:2.5rem;line-height:1;margin-bottom:.5rem">💰</div><h6 class="fw-bold" style="color:var(--mz-navy)">Uygun Prim</h6><p class="small text-muted mb-0">Konut metrekaresi ve risk bolgesine gore dusuk prim.</p></div></div><div class="col-md-6 col-lg-3"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:14px;padding:1.25rem;text-align:center;height:100%;transition:all .25s"><div style="font-size:2.5rem;line-height:1;margin-bottom:.5rem">⚡</div><h6 class="fw-bold" style="color:var(--mz-navy)">Hizli Odeme</h6><p class="small text-muted mb-0">Hasar tespiti sonrasi hizli tazminat sureci.</p></div></div></div><h3 class="fw-bold mt-5 mb-3" style="color:var(--mz-navy)"><i class="bi bi-list-check"></i> Diğer Teminatlar</h3><div class="row g-2 mb-4"><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Deprem</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Deprem sonucu yangin</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Deprem sonucu infilak</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Tsunami</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Yer kaymasi</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Bina yapisal hasarlari</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Temel ve ana duvarlar</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Tavan ve tabanlar</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Merdivenler ve asansorler</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Sahanliklar ve koridorlar</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Catilar ve bacalar</span></div></div></div><h3 class="fw-bold mt-5 mb-3" style="color:var(--mz-navy)"><i class="bi bi-trophy-fill text-warning"></i> Ürün Avantajları</h3><div class="row g-3 mb-4"><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-shield-check" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Yasal Zorunlu</h6><p class="small text-muted mb-0">Tapu, kira, abonelik islemlerinde gerekli.</p></div></div><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-bank" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Devlet Garantili</h6><p class="small text-muted mb-0">DASK havuzu devlet guvencesinde.</p></div></div><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-cash-coin" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Dusuk Prim</h6><p class="small text-muted mb-0">Yillik 200-500 TL arasi baslar.</p></div></div><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-clock-history" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Hizli Odeme</h6><p class="small text-muted mb-0">Hasar tespiti sonrasi 1 ay icinde tazminat.</p></div></div></div><h3 class="fw-bold mt-5 mb-3" style="color:var(--mz-navy)"><i class="bi bi-question-circle-fill text-warning"></i> Sıkça Sorulan Sorular</h3><div class="accordion mb-4" id="urunSSS-dask"><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#sss-dask-0">DASK yaptirmazsam ne olur?</button></h2><div id="sss-dask-0" class="accordion-collapse collapse show" data-bs-parent="#urunSSS-dask"><div class="accordion-body"><p class="mb-0">Tapu devri yapamaz, konut kredisi alamaz, su/elektrik/dogalgaz aboneligini yenileyemezsiniz. Deprem hasarinda <strong>hicbir tazminat alamazsiniz</strong>.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-dask-1">DASK esyalarimi kapsar mi?</button></h2><div id="sss-dask-1" class="accordion-collapse collapse" data-bs-parent="#urunSSS-dask"><div class="accordion-body"><p class="mb-0">Hayir. DASK <strong>sadece bina yapisini</strong> kapsar. Ev esyalari icin konut sigortasi gerekir.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-dask-2">DASKin limiti ne kadar?</button></h2><div id="sss-dask-2" class="accordion-collapse collapse" data-bs-parent="#urunSSS-dask"><div class="accordion-body"><p class="mb-0">DASK havuzunun teminat tavani her yil yeniden belirlenir. 2025 itibariyla <strong>azami 1.000.000 TL</strong> civari. Ustundeki kisim icin <strong>ozel deprem sigortasi</strong> alinabilir.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-dask-3">Kira evimde DASK ben mi yaptiririm?</button></h2><div id="sss-dask-3" class="accordion-collapse collapse" data-bs-parent="#urunSSS-dask"><div class="accordion-body"><p class="mb-0">DASK ev sahibinin yukumlulugudur ama kiraci da yapabilir. Onemli olan binanin DASKi olmasi.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-dask-4">DASK fiyati neye gore belirlenir?</button></h2><div id="sss-dask-4" class="accordion-collapse collapse" data-bs-parent="#urunSSS-dask"><div class="accordion-body"><p class="mb-0">Konutun bulundugu deprem risk bolgesi (1-7), yapi tarzi (betonarme, yigma vb.) ve yuzolcumune gore belirlenir.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-dask-5">Apartmanda her daireye ayri DASK gerekir mi?</button></h2><div id="sss-dask-5" class="accordion-collapse collapse" data-bs-parent="#urunSSS-dask"><div class="accordion-body"><p class="mb-0">Evet. DASK <strong>bagimsiz bolum bazinda</strong> yapilir. Apartmanin her dairesi icin ayri police duzenlenir.</p></div></div></div></div><div class="alert alert-warning mt-4 d-flex gap-3 align-items-center"><i class="bi bi-lightbulb-fill fs-3"></i><div class="flex-grow-1"><strong class="d-block">Size en uygun teklifi karşılaştıralım</strong><span class="small">Anlaşmalı 12+ sigorta şirketinden teklifleri toplayıp size en uygun paketi sunalım. Süreç ücretsiz, bağlayıcılığı yok.</span></div></div>' WHERE `slug` = 'dask';

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Ozel saglik sigortasi; anlasmali ozel hastanelerde nitelikli saglik hizmeti, ameliyat, dogum ve check-up paketleri icin <strong>fark ucretsiz</strong> tedavi imkani sunan bireysel saglik guvencesidir.</div><h3 class="fw-bold mt-5 mb-3" style="color:var(--mz-navy)"><i class="bi bi-stars text-warning"></i> Öne Çıkan Teminatlar</h3><div class="row g-3 mb-4"><div class="col-md-6 col-lg-3"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:14px;padding:1.25rem;text-align:center;height:100%;transition:all .25s"><div style="font-size:2.5rem;line-height:1;margin-bottom:.5rem">🏥</div><h6 class="fw-bold" style="color:var(--mz-navy)">Yatarak Tedavi</h6><p class="small text-muted mb-0">Ameliyat, hastane yatisi, yogun bakim masraflari.</p></div></div><div class="col-md-6 col-lg-3"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:14px;padding:1.25rem;text-align:center;height:100%;transition:all .25s"><div style="font-size:2.5rem;line-height:1;margin-bottom:.5rem">🩺</div><h6 class="fw-bold" style="color:var(--mz-navy)">Ayakta Tedavi</h6><p class="small text-muted mb-0">Doktor muayenesi, tahlil, goruntuleme, fizik tedavi.</p></div></div><div class="col-md-6 col-lg-3"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:14px;padding:1.25rem;text-align:center;height:100%;transition:all .25s"><div style="font-size:2.5rem;line-height:1;margin-bottom:.5rem">🤰</div><h6 class="fw-bold" style="color:var(--mz-navy)">Dogum Paketi</h6><p class="small text-muted mb-0">Hamilelik takibi, dogum, yenidogan bakimi.</p></div></div><div class="col-md-6 col-lg-3"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:14px;padding:1.25rem;text-align:center;height:100%;transition:all .25s"><div style="font-size:2.5rem;line-height:1;margin-bottom:.5rem">🧬</div><h6 class="fw-bold" style="color:var(--mz-navy)">Check-up</h6><p class="small text-muted mb-0">Yillik koruyucu saglik taramasi.</p></div></div></div><h3 class="fw-bold mt-5 mb-3" style="color:var(--mz-navy)"><i class="bi bi-list-check"></i> Diğer Teminatlar</h3><div class="row g-2 mb-4"><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Ameliyat ve Cerrahi Tedavi</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Yogun Bakim</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Anjiyografi ve Kalp Islemleri</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Onkolojik Tedaviler (Kanser tedavisi)</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Nukleer Tip</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Dis Tedavisi (paket)</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Goz Tedavisi (paket)</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Fizik Tedavi ve Rehabilitasyon</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Goruntuleme (MR, BT, USG)</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Laboratuvar Tetkikleri</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Acil Durum (24 saat)</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Ambulans Hizmeti</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Ikinci Tibbi Gorus</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Yurt Disi Tedavi</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Moduler Paketler</span></div></div></div><h3 class="fw-bold mt-5 mb-3" style="color:var(--mz-navy)"><i class="bi bi-trophy-fill text-warning"></i> Ürün Avantajları</h3><div class="row g-3 mb-4"><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-hospital-fill" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Anlasmali Ozel Hastaneler</h6><p class="small text-muted mb-0">Acibadem, Memorial, Liv, Medicana ve daha fazlasi.</p></div></div><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-cash-stack" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Fark Ucretsiz</h6><p class="small text-muted mb-0">Anlasmali kurumlarda cep odemesi gerekmez.</p></div></div><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-heart-pulse" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Hastaneye Direkt Gecis</h6><p class="small text-muted mb-0">Online provizyon ile bekleme suresi azalir.</p></div></div><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-chat-text" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">7/24 Saglik Danismani</h6><p class="small text-muted mb-0">Telefonla doktor gorusme imkani.</p></div></div><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-people-fill" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Aile Paketi Indirimi</h6><p class="small text-muted mb-0">Es ve cocuklar icin ek prim avantaji.</p></div></div><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-plus-square" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Moduler Tasarim</h6><p class="small text-muted mb-0">Ihtiyaciniza gore teminat ekleyip cikarabilirsiniz.</p></div></div></div><h3 class="fw-bold mt-5 mb-3" style="color:var(--mz-navy)"><i class="bi bi-question-circle-fill text-warning"></i> Sıkça Sorulan Sorular</h3><div class="accordion mb-4" id="urunSSS-ozel-saglik-sigortasi"><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#sss-ozel-saglik-sigortasi-0">Ozel saglik sigortasi ile tamamlayici saglik arasinda ne fark var?</button></h2><div id="sss-ozel-saglik-sigortasi-0" class="accordion-collapse collapse show" data-bs-parent="#urunSSS-ozel-saglik-sigortasi"><div class="accordion-body"><p class="mb-0">Tamamlayici saglik SGK ya ek olarak calisir ve <strong>sadece SGK farkini</strong> oder, daha ucuzdur. Ozel saglik ise SGK dan bagimsizdir, anlasmali hastanelerde tum tedaviyi karsilar, daha kapsamlidir.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-ozel-saglik-sigortasi-1">Mevcut hastaliklarim kapsam dahilinde mi?</button></h2><div id="sss-ozel-saglik-sigortasi-1" class="accordion-collapse collapse" data-bs-parent="#urunSSS-ozel-saglik-sigortasi"><div class="accordion-body"><p class="mb-0">Genelde police baslangicindan once tanisi konmus hastaliklar (preexisting condition) kapsam disindadir. Bazi sirketlerde 2-3 yil bekleme suresi sonrasi kapsama alinir.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-ozel-saglik-sigortasi-2">Bekleme suresi nedir?</button></h2><div id="sss-ozel-saglik-sigortasi-2" class="accordion-collapse collapse" data-bs-parent="#urunSSS-ozel-saglik-sigortasi"><div class="accordion-body"><p class="mb-0">Police baslangicindan sonra belirli tedaviler icin 3-12 aylik bekleme suresi vardir. Dogum 12 ay, kronik hastaliklar 6-12 ay, kanser 3 ay vb.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-ozel-saglik-sigortasi-3">Anlasmasiz bir hastaneye gidersem ne olur?</button></h2><div id="sss-ozel-saglik-sigortasi-3" class="accordion-collapse collapse" data-bs-parent="#urunSSS-ozel-saglik-sigortasi"><div class="accordion-body"><p class="mb-0">Tedaviyi kendi cebinizden odersiniz, faturayi sigortaya gondersiniz, anlasmasiz tarife uzerinden geri odeme alirsiniz (genelde 70-80 oraninda).</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-ozel-saglik-sigortasi-4">Yillik limit nedir?</button></h2><div id="sss-ozel-saglik-sigortasi-4" class="accordion-collapse collapse" data-bs-parent="#urunSSS-ozel-saglik-sigortasi"><div class="accordion-body"><p class="mb-0">Policenin bir yillik donem boyunca odeyecegi toplam azami tutardir. 250.000 TL den baslayip 5.000.000 TL ye kadar paketler bulunur.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-ozel-saglik-sigortasi-5">Yenilemede primler nasil artar?</button></h2><div id="sss-ozel-saglik-sigortasi-5" class="accordion-collapse collapse" data-bs-parent="#urunSSS-ozel-saglik-sigortasi"><div class="accordion-body"><p class="mb-0">Yas ilerledikce ve hasar gecmisine gore prim artar. Turkiye Sigorta Birligi tarafindan belirlenen genel artis oranlari + sirketin kendi tarifesi etkilidir.</p></div></div></div></div><div class="alert alert-warning mt-4 d-flex gap-3 align-items-center"><i class="bi bi-lightbulb-fill fs-3"></i><div class="flex-grow-1"><strong class="d-block">Size en uygun teklifi karşılaştıralım</strong><span class="small">Anlaşmalı 12+ sigorta şirketinden teklifleri toplayıp size en uygun paketi sunalım. Süreç ücretsiz, bağlayıcılığı yok.</span></div></div>' WHERE `slug` = 'ozel-saglik-sigortasi';

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Tamamlayici Saglik Sigortasi (TSS), <strong>SGK kapsamindaki tedavilere ek olarak</strong> anlasmali ozel hastanelerde fark ucretsiz tedavi imkani sunar. SGKnizi tamamlayan, ekonomik bir koruma kalkanidir.</div><h3 class="fw-bold mt-5 mb-3" style="color:var(--mz-navy)"><i class="bi bi-stars text-warning"></i> Öne Çıkan Teminatlar</h3><div class="row g-3 mb-4"><div class="col-md-6 col-lg-3"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:14px;padding:1.25rem;text-align:center;height:100%;transition:all .25s"><div style="font-size:2.5rem;line-height:1;margin-bottom:.5rem">🏥</div><h6 class="fw-bold" style="color:var(--mz-navy)">SGK + Ozel Hastane</h6><p class="small text-muted mb-0">SGK nizin anlasmali ozel hastane farkini sigorta oder.</p></div></div><div class="col-md-6 col-lg-3"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:14px;padding:1.25rem;text-align:center;height:100%;transition:all .25s"><div style="font-size:2.5rem;line-height:1;margin-bottom:.5rem">💰</div><h6 class="fw-bold" style="color:var(--mz-navy)">Dusuk Prim</h6><p class="small text-muted mb-0">Ozel saglik sigortasinin yarisi kadar fiyat.</p></div></div><div class="col-md-6 col-lg-3"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:14px;padding:1.25rem;text-align:center;height:100%;transition:all .25s"><div style="font-size:2.5rem;line-height:1;margin-bottom:.5rem">🩺</div><h6 class="fw-bold" style="color:var(--mz-navy)">Yatarak + Ayakta</h6><p class="small text-muted mb-0">Ameliyat, dogum, muayene, tahlil hepsi dahil.</p></div></div><div class="col-md-6 col-lg-3"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:14px;padding:1.25rem;text-align:center;height:100%;transition:all .25s"><div style="font-size:2.5rem;line-height:1;margin-bottom:.5rem">🚀</div><h6 class="fw-bold" style="color:var(--mz-navy)">Hizli Provizyon</h6><p class="small text-muted mb-0">Anlasmali hastanede online onay ile hemen tedavi.</p></div></div></div><h3 class="fw-bold mt-5 mb-3" style="color:var(--mz-navy)"><i class="bi bi-list-check"></i> Diğer Teminatlar</h3><div class="row g-2 mb-4"><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Yatarak Tedavi (Ameliyat, hastane yatisi)</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Ayakta Tedavi (paketli)</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Dogum (paketli)</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Tahlil ve Goruntuleme</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Fizik Tedavi</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Acil Durum</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Kemoterapi ve Radyoterapi</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Diyaliz</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Yenidogan Bakim</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Anlasmali Ozel Hastane Agi</span></div></div></div><h3 class="fw-bold mt-5 mb-3" style="color:var(--mz-navy)"><i class="bi bi-trophy-fill text-warning"></i> Ürün Avantajları</h3><div class="row g-3 mb-4"><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-piggy-bank" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Ekonomik Cozum</h6><p class="small text-muted mb-0">Ozel sagliga kiyasla 50 daha uygun.</p></div></div><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-hospital" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Anlasmali Hastane Agi</h6><p class="small text-muted mb-0">Acibadem, Memorial, Medicana ve daha fazlasi.</p></div></div><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-shield-plus" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">SGK Tamamlayici</h6><p class="small text-muted mb-0">SGK nizin kapsamadigi farklari kapatir.</p></div></div><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-chat-dots" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Hizli Provizyon</h6><p class="small text-muted mb-0">Online sistem uzerinden anlik onay.</p></div></div><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-people-fill" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Aile Paketi</h6><p class="small text-muted mb-0">Es ve cocuklar icin indirimli ek prim.</p></div></div></div><h3 class="fw-bold mt-5 mb-3" style="color:var(--mz-navy)"><i class="bi bi-question-circle-fill text-warning"></i> Sıkça Sorulan Sorular</h3><div class="accordion mb-4" id="urunSSS-tamamlayici-saglik-sigortasi"><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#sss-tamamlayici-saglik-sigortasi-0">Tamamlayici saglik sigortasi nasil calisir?</button></h2><div id="sss-tamamlayici-saglik-sigortasi-0" class="accordion-collapse collapse show" data-bs-parent="#urunSSS-tamamlayici-saglik-sigortasi"><div class="accordion-body"><p class="mb-0">Anlasmali ozel hastaneye giderken SGK nizi kullanirsiniz. Hastane SGK ya fatura kesip kalan farki tamamlayici sigortaniza yonlendirir. Siz cebinizden odeme yapmazsiniz.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-tamamlayici-saglik-sigortasi-1">SGK m olmasa da yaptirabilir miyim?</button></h2><div id="sss-tamamlayici-saglik-sigortasi-1" class="accordion-collapse collapse" data-bs-parent="#urunSSS-tamamlayici-saglik-sigortasi"><div class="accordion-body"><p class="mb-0">Hayir. TSS nin isleyebilmesi icin <strong>aktif SGK kaydinizin olmasi zorunludur.</strong> SGK niz yoksa ozel saglik sigortasi secenegini degerlendirin.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-tamamlayici-saglik-sigortasi-2">Hangi hastaneler anlasmali?</button></h2><div id="sss-tamamlayici-saglik-sigortasi-2" class="accordion-collapse collapse" data-bs-parent="#urunSSS-tamamlayici-saglik-sigortasi"><div class="accordion-body"><p class="mb-0">Sirkete gore degisir. Genelde Acibadem, Memorial, Liv, Medicana, Florence, Yeditepe, Anadolu Saglik Merkezi gibi buyuk zincirler vardir.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-tamamlayici-saglik-sigortasi-3">Bekleme suresi var mi?</button></h2><div id="sss-tamamlayici-saglik-sigortasi-3" class="accordion-collapse collapse" data-bs-parent="#urunSSS-tamamlayici-saglik-sigortasi"><div class="accordion-body"><p class="mb-0">Var. Police baslangicindan sonra acil durumlar haric cogu tedavide 3-12 ay bekleme suresi uygulanir.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-tamamlayici-saglik-sigortasi-4">Dogum kapsama dahil mi?</button></h2><div id="sss-tamamlayici-saglik-sigortasi-4" class="accordion-collapse collapse" data-bs-parent="#urunSSS-tamamlayici-saglik-sigortasi"><div class="accordion-body"><p class="mb-0">Genelde police baslangicindan sonra 12 ay bekleme suresi vardir. Sonrasinda dogumla ilgili tum masraflar kapsam dahilindedir.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-tamamlayici-saglik-sigortasi-5">Tamamlayici saglik sigortasi ne kadar tutar?</button></h2><div id="sss-tamamlayici-saglik-sigortasi-5" class="accordion-collapse collapse" data-bs-parent="#urunSSS-tamamlayici-saglik-sigortasi"><div class="accordion-body"><p class="mb-0">Yasa, cinsiyete ve secilen pakete gore degisir. Genelde aylik 200-500 TL arasinda baslangic paketleri bulunabilir.</p></div></div></div></div><div class="alert alert-warning mt-4 d-flex gap-3 align-items-center"><i class="bi bi-lightbulb-fill fs-3"></i><div class="flex-grow-1"><strong class="d-block">Size en uygun teklifi karşılaştıralım</strong><span class="small">Anlaşmalı 12+ sigorta şirketinden teklifleri toplayıp size en uygun paketi sunalım. Süreç ücretsiz, bağlayıcılığı yok.</span></div></div>' WHERE `slug` = 'tamamlayici-saglik-sigortasi';

UPDATE `mz_urunler` SET `aciklama` = '<div class="lead mb-4">Ferdi kaza sigortasi; trafik kazasi, ev kazasi, is kazasi, spor yaralanmasi gibi <strong>beklenmedik kazalar</strong> sonucunda yasanabilecek mali yukleri karsilar. Kaza sonucu olum, surekli sakatlik, gecici is goremezlik ve tedavi masraflari tek policede toplanir.</div><h3 class="fw-bold mt-5 mb-3" style="color:var(--mz-navy)"><i class="bi bi-stars text-warning"></i> Öne Çıkan Teminatlar</h3><div class="row g-3 mb-4"><div class="col-md-6 col-lg-3"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:14px;padding:1.25rem;text-align:center;height:100%;transition:all .25s"><div style="font-size:2.5rem;line-height:1;margin-bottom:.5rem">💔</div><h6 class="fw-bold" style="color:var(--mz-navy)">Vefat Tazminati</h6><p class="small text-muted mb-0">Kaza sonucu olum halinde lehtarlar pesin nakit odeme.</p></div></div><div class="col-md-6 col-lg-3"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:14px;padding:1.25rem;text-align:center;height:100%;transition:all .25s"><div style="font-size:2.5rem;line-height:1;margin-bottom:.5rem">🦽</div><h6 class="fw-bold" style="color:var(--mz-navy)">Surekli Sakatlik</h6><p class="small text-muted mb-0">Kaza nedeniyle kalici sakatlikta tazminat.</p></div></div><div class="col-md-6 col-lg-3"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:14px;padding:1.25rem;text-align:center;height:100%;transition:all .25s"><div style="font-size:2.5rem;line-height:1;margin-bottom:.5rem">💼</div><h6 class="fw-bold" style="color:var(--mz-navy)">Is Goremezlik</h6><p class="small text-muted mb-0">Calisamadiginiz her gun icin gunluk tazminat.</p></div></div><div class="col-md-6 col-lg-3"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:14px;padding:1.25rem;text-align:center;height:100%;transition:all .25s"><div style="font-size:2.5rem;line-height:1;margin-bottom:.5rem">💊</div><h6 class="fw-bold" style="color:var(--mz-navy)">Tedavi Masraflari</h6><p class="small text-muted mb-0">Hastane, ameliyat, ilac, fizik tedavi.</p></div></div></div><h3 class="fw-bold mt-5 mb-3" style="color:var(--mz-navy)"><i class="bi bi-list-check"></i> Diğer Teminatlar</h3><div class="row g-2 mb-4"><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Kaza Sonucu Olum</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Kaza Sonucu Surekli Sakatlik (oran bazli)</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Gecici Is Goremezlik (Gundelik)</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Tedavi Masraflari (Hastane, ilac)</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Hastane Gundelik Tazminati</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Cenaze Masraflari</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Yurt Disi Tedavi</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Ambulans / Tahliye</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Ikinci Tibbi Gorus</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Aile Paketi (Es, cocuklar)</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Spor Kazalari (paketli)</span></div></div><div class="col-md-6 col-lg-4"><div style="display:flex;align-items:start;gap:.5rem;padding:.5rem .75rem;background:#f8f9fa;border-radius:8px"><i class="bi bi-check-circle-fill text-success" style="margin-top:2px;flex-shrink:0"></i><span class="small">Tedavi Gormek Icin Refakatci</span></div></div></div><h3 class="fw-bold mt-5 mb-3" style="color:var(--mz-navy)"><i class="bi bi-trophy-fill text-warning"></i> Ürün Avantajları</h3><div class="row g-3 mb-4"><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-cash-coin" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Pesin Nakit Odeme</h6><p class="small text-muted mb-0">Tani sonrasi 7 gun icinde tazminat.</p></div></div><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-people" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Aile Paketi</h6><p class="small text-muted mb-0">Es ve cocuklar tek policede koruma altinda.</p></div></div><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-percent" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Dusuk Prim</h6><p class="small text-muted mb-0">Yillik 500-2.000 TL arasinda ekonomik fiyatlar.</p></div></div><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-shield-check" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">24 Saat Koruma</h6><p class="small text-muted mb-0">Ev, is, sokak, tatil - her yerde gecerli.</p></div></div><div class="col-md-6 col-lg-4"><div style="background:#fff;border:1px solid var(--mz-border);border-radius:12px;padding:1.25rem;height:100%"><i class="bi bi-globe" style="font-size:1.75rem;color:var(--mz-red);display:block;margin-bottom:.5rem"></i><h6 class="fw-bold" style="color:var(--mz-navy)">Yurt Disi Gecerli</h6><p class="small text-muted mb-0">Tatil ve is seyahatlerinde de teminat surer.</p></div></div></div><h3 class="fw-bold mt-5 mb-3" style="color:var(--mz-navy)"><i class="bi bi-question-circle-fill text-warning"></i> Sıkça Sorulan Sorular</h3><div class="accordion mb-4" id="urunSSS-ferdi-kaza-sigortasi"><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#sss-ferdi-kaza-sigortasi-0">Ferdi kaza sigortasi ne ise yarar?</button></h2><div id="sss-ferdi-kaza-sigortasi-0" class="accordion-collapse collapse show" data-bs-parent="#urunSSS-ferdi-kaza-sigortasi"><div class="accordion-body"><p class="mb-0">Beklenmedik bir kaza yasandiginda - trafik, ev, is, spor - yasanan mali yuku karsilar. Calisamadiginiz surede gelir kaybi, tedavi masraflari, surekli sakatlik halinde tazminat odenir.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-ferdi-kaza-sigortasi-1">Hayat sigortasindan farki ne?</button></h2><div id="sss-ferdi-kaza-sigortasi-1" class="accordion-collapse collapse" data-bs-parent="#urunSSS-ferdi-kaza-sigortasi"><div class="accordion-body"><p class="mb-0">Hayat sigortasi <strong>her sebeple olum</strong>, ferdi kaza ise <strong>yalnizca kaza sonucu olum</strong> kapsar. Hayat daha genis, ferdi kaza daha ucuzdur.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-ferdi-kaza-sigortasi-2">Aile paketi nedir?</button></h2><div id="sss-ferdi-kaza-sigortasi-2" class="accordion-collapse collapse" data-bs-parent="#urunSSS-ferdi-kaza-sigortasi"><div class="accordion-body"><p class="mb-0">Tek policede es, cocuklar (genelde 18-25 yas arasi) ve hatta eve bakan yardimcilar koruma altina alinir. Bireysel paketlere gore cok daha avantajlidir.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-ferdi-kaza-sigortasi-3">Hangi kazalar kapsam disi?</button></h2><div id="sss-ferdi-kaza-sigortasi-3" class="accordion-collapse collapse" data-bs-parent="#urunSSS-ferdi-kaza-sigortasi"><div class="accordion-body"><p class="mb-0">Intihar/intihar girisimi, alkol/uyusturucu etkisinde kullanim, profesyonel sporcu yarisma kazalari (ozel paket gerekir), savas ve nukleer riskler kapsam disidir.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-ferdi-kaza-sigortasi-4">Kazaya ne kadar sure sonra basvurmam gerekir?</button></h2><div id="sss-ferdi-kaza-sigortasi-4" class="accordion-collapse collapse" data-bs-parent="#urunSSS-ferdi-kaza-sigortasi"><div class="accordion-body"><p class="mb-0">Kaza tarihinden itibaren <strong>5 is gunu icinde</strong> ihbar etmeniz beklenir. Gec basvurular tazminat hakkini azaltabilir.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-ferdi-kaza-sigortasi-5">Tedavi masraflarini tum hastanelerde kullanabilir miyim?</button></h2><div id="sss-ferdi-kaza-sigortasi-5" class="accordion-collapse collapse" data-bs-parent="#urunSSS-ferdi-kaza-sigortasi"><div class="accordion-body"><p class="mb-0">Evet. Ferdi kaza sigortasinda <strong>anlasmali hastane siniri yoktur</strong> - istediginiz hastanede tedavi olabilirsiniz, faturayi geri alirsiniz.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sss-ferdi-kaza-sigortasi-6">Surekli sakatlik nasil hesaplanir?</button></h2><div id="sss-ferdi-kaza-sigortasi-6" class="accordion-collapse collapse" data-bs-parent="#urunSSS-ferdi-kaza-sigortasi"><div class="accordion-body"><p class="mb-0">Doktor raporu uzerinden <strong>sakatlik oranina gore</strong> odeme yapilir. 100 sakatlikta police limitinin tamami, daha dusuk oranlarda orantisal odeme yapilir.</p></div></div></div></div><div class="alert alert-warning mt-4 d-flex gap-3 align-items-center"><i class="bi bi-lightbulb-fill fs-3"></i><div class="flex-grow-1"><strong class="d-block">Size en uygun teklifi karşılaştıralım</strong><span class="small">Anlaşmalı 12+ sigorta şirketinden teklifleri toplayıp size en uygun paketi sunalım. Süreç ücretsiz, bağlayıcılığı yok.</span></div></div>' WHERE `slug` = 'ferdi-kaza-sigortasi';

-- Bitti.

-- ============================================================
-- v1.1.15 - SEO Ayarlari (GSC + zenginlestirilmis anahtar kelimeler)
-- ============================================================
INSERT IGNORE INTO `mz_ayarlar` (`anahtar`,`deger`,`aciklama`,`grup`,`tip`) VALUES
('gsc_verification', '', 'Google Search Console dogrulama kodu (sadece icerik, meta tag tum hali degil)', 'seo', 'text');

-- SEO anahtar kelimelerini zenginleştir (sadece ESKI/KISA olani guncelle)
UPDATE `mz_ayarlar`
SET `deger` = 'sigorta, kasko, trafik sigortasi, konut sigortasi, dask, deprem sigortasi, saglik sigortasi, tamamlayici saglik, ozel saglik sigortasi, ferdi kaza sigortasi, isyeri sigortasi, mizan sigorta, sigorta acentesi, sigorta aracilik, online sigorta teklifi, en uygun sigorta, sigorta karsilastirma, anlasmali sigorta sirketleri, hasar ihbari, konya sigorta, istanbul sigorta, ankara sigorta'
WHERE `anahtar` = 'site_anahtar_kelimeler' AND LENGTH(`deger`) < 200;

UPDATE `mz_ayarlar`
SET `deger` = 'Anlasmali 12+ sigorta sirketinden kasko, trafik, konut, DASK, saglik, ferdi kaza ve diger tum sigorta urunleri icin en uygun teklifleri Mizan Sigorta Aracilik Hizmetleri olarak karsilastiriyoruz. Konya, Istanbul, Ankara ve Aksaray''da fiziksel ofislerimiz, 7/24 hasar destegimiz var.'
WHERE `anahtar` = 'site_aciklamasi' AND LENGTH(`deger`) < 150;

-- ============================================================
-- v1.1.16 - Talep bildirim BCC + operator_email ayarlari
-- Yunus istegi: Tum talepler (teklif/hasar/iletisim) ek olarak
-- teklifmerkezi@mizansigorta.com.tr adresine de gizli kopya gonderilsin.
-- ============================================================

INSERT IGNORE INTO `mz_ayarlar` (`anahtar`,`deger`,`aciklama`,`grup`,`tip`) VALUES
('talep_bildirim_bcc', 'teklifmerkezi@mizansigorta.com.tr',
 'Tum talepler (teklif/hasar/iletisim) ek olarak bu adres(ler)e gizli kopya gonderilir. Birden fazla email icin virgul ile ayirin: ornek1@x.com, ornek2@y.com',
 'smtp', 'textarea'),
('operator_email', '',
 'Iletisim formundan gelen mesajlar bu adrese gonderilir (bos ise genel email kullanilir)',
 'smtp', 'email');

-- ============================================================
-- v1.1.17 - Slider gorselleri + guncelleme animasyonu + version display fix
-- (Sadece kod degisikligi, SQL migration yok)
-- ============================================================

-- ============================================================
-- v1.1.18 - Slider Yonetim Tablosu
-- Yunus istegi: Slider yonetim panelinden duzenlenebilir olsun
-- ============================================================

CREATE TABLE IF NOT EXISTS `mz_slaytlar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `baslik` varchar(255) NOT NULL,
  `accent_kelime` varchar(100) DEFAULT NULL COMMENT 'Sari renkli vurgulu kelime baslik icinde',
  `ust_metin` varchar(150) DEFAULT NULL COMMENT 'Italik kirmizi script (ornek: Aileniz Icin)',
  `aciklama` text DEFAULT NULL,
  `buton1_metin` varchar(80) DEFAULT NULL,
  `buton1_link` varchar(255) DEFAULT NULL,
  `buton1_ikon` varchar(50) DEFAULT 'bi-headset' COMMENT 'Bootstrap Icons class adi',
  `buton2_metin` varchar(80) DEFAULT NULL,
  `buton2_link` varchar(255) DEFAULT NULL,
  `buton2_ikon` varchar(50) DEFAULT 'bi-telephone' COMMENT 'Bootstrap Icons class adi',
  `gorsel_tip` enum('svg_kalkan','svg_araba','svg_ev_kalp','svg_kulaklik','custom_url','yok') NOT NULL DEFAULT 'svg_kalkan' COMMENT 'Slide arka plan illustration tipi',
  `gorsel_url` varchar(500) DEFAULT NULL COMMENT 'gorsel_tip=custom_url icin gercek resim URL',
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `sira` int(11) NOT NULL DEFAULT 0,
  `olusturma_tarihi` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `guncelleme_tarihi` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_aktif_sira` (`aktif`,`sira`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4 varsayilan slayt seed (sadece tablo bossa eklenir - INSERT IGNORE pattern)
INSERT IGNORE INTO `mz_slaytlar` (`id`,`baslik`,`accent_kelime`,`ust_metin`,`aciklama`,`buton1_metin`,`buton1_link`,`buton1_ikon`,`buton2_metin`,`buton2_link`,`buton2_ikon`,`gorsel_tip`,`aktif`,`sira`) VALUES
(1, 'Hayatınıza, aracınıza ve işinize tam koruma', 'tam koruma', 'Güven ve Özen İle',
 '12+ anlaşmalı sigorta şirketi arasından, ihtiyacınıza özel en avantajlı teminatları biz buluruz. Talebinizi iletin, müsait temsilcimiz en kısa sürede sizinle iletişime geçsin.',
 'Teklif Talebi Oluştur', '/teklif-al', 'bi-headset',
 'Bize Ulaşın', '/iletisim', 'bi-telephone',
 'svg_kalkan', 1, 10),
(2, 'Kasko ve Trafik Sigortası en uygun fiyat', 'en uygun fiyat', 'Aracınız İçin',
 'Anadolu, Allianz, Türkiye Sigorta, AXA, HDI ve daha fazlası — tek bir talepte tüm şirketlerin teklifini karşılaştırın. Yenileme zamanı yaklaştığında size hatırlatma yapıyoruz.',
 'Oto Sigortalarını İncele', '/urun/oto-sigortalari', 'bi-car-front-fill',
 'Teklif Al', '/teklif-al?urun=oto-sigortalari', 'bi-arrow-right',
 'svg_araba', 1, 20),
(3, 'Sağlık ve DASK geleceğinizi güvenceye alın', 'geleceğinizi güvenceye alın', 'Aileniz İçin',
 'Tamamlayıcı sağlık, özel sağlık ve DASK zorunlu deprem sigortası. Aile bireylerinize özel paketler, anlaşmalı özel hastanelerde fark ücretsiz tedavi ve deprem sonrası nakit destek.',
 'Sağlık Sigortaları', '/urun/saglik-sigortalari', 'bi-heart-pulse-fill',
 'DASK · Konut', '/urun/yangin-policeleri', 'bi-houses-fill',
 'svg_ev_kalp', 1, 30),
(4, 'Hasar anında 7/24 yanınızdayız', 'yalnız değilsiniz', 'Hasar Anında',
 'Hasar durumunda online ihbar formu, eksper takibi, belge süreci ve ödeme — hepsini biz yönetiyoruz. Aramamız yeterli, sürecin gerisini bize bırakın.',
 'Hasar İhbarı Yap', '/hasar-ihbari', 'bi-exclamation-triangle-fill',
 'Bizi Arayın', 'tel:', 'bi-telephone-fill',
 'svg_kulaklik', 1, 40);

-- ============================================================
-- v1.1.19 - Subeler Tablosu (iletisim sayfasi sube telefonlari)
-- Yunus istegi: 'mizansigorta.com.tr/iletisim subelerin Iletisim
-- numaralari nerede?'
-- ============================================================

CREATE TABLE IF NOT EXISTS `mz_subeler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sehir` varchar(60) NOT NULL,
  `ilce` varchar(80) DEFAULT NULL,
  `etiket` varchar(80) DEFAULT 'Sube Ofis' COMMENT 'Sube Ofis / Genel Merkez vb.',
  `adres` text NOT NULL,
  `telefon` varchar(40) DEFAULT NULL,
  `telefon_2` varchar(40) DEFAULT NULL COMMENT 'Ikinci hat veya cep',
  `email` varchar(120) DEFAULT NULL,
  `harita_url` varchar(500) DEFAULT NULL COMMENT 'Google Maps link, bos ise adresten otomatik uretilir',
  `calisma_saatleri` varchar(160) DEFAULT NULL COMMENT 'Pzt-Cum 09:00-18:00',
  `merkez_mi` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=Genel Merkez (sayfada vurgulu)',
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `sira` int(11) NOT NULL DEFAULT 0,
  `olusturma_tarihi` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `guncelleme_tarihi` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_aktif_sira` (`aktif`,`sira`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Eski sehir-bazli setting alanlarindan ilk kurulum seed (idempotent: id=1 cakisirsa atlanir)
-- Yunus admin panelden duzenleyebilir/silebilir, yenilerini ekleyebilir.
INSERT IGNORE INTO `mz_subeler` (`id`,`sehir`,`ilce`,`etiket`,`adres`,`telefon`,`merkez_mi`,`aktif`,`sira`) VALUES
(1, 'Istanbul', NULL, 'Genel Merkez', 'Adres bilgisi yonetim panelden duzenlenebilir.', NULL, 1, 1, 10),
(2, 'Konya',    'Karatay', 'Sube Ofis', 'Adres bilgisi yonetim panelden duzenlenebilir.', NULL, 0, 1, 20),
(3, 'Ankara',   NULL,      'Sube Ofis', 'Adres bilgisi yonetim panelden duzenlenebilir.', NULL, 0, 1, 30),
(4, 'Aksaray',  NULL,      'Sube Ofis', 'Adres bilgisi yonetim panelden duzenlenebilir.', NULL, 0, 1, 40);

-- ============================================================
-- v1.1.20 - SEO + Mobil iyilestirmeler (sadece kod, SQL yok)
-- ============================================================

-- ============================================================
-- v1.1.21 - Admin POST redirect bug fix (sadece kod, SQL yok)
-- ============================================================

-- ============================================================
-- v1.1.22 - Kampanya Pop-up Yonetim Sistemi
-- Yunus istegi: 'Yeni bir Kampanya oldugunda bunu yayinlayabilmek icin
-- birde Pop-Up lazim, Baslama ve Bitis Tarihlerini girdiğimde o zamana
-- göre gelmesi gerek Kampanya.'
-- ============================================================

CREATE TABLE IF NOT EXISTS `mz_kampanyalar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `baslik` varchar(160) NOT NULL,
  `alt_baslik` varchar(200) DEFAULT NULL COMMENT 'Kucuk metin baslik altinda',
  `aciklama` text DEFAULT NULL,
  `gorsel_url` varchar(500) DEFAULT NULL COMMENT 'Pop-up sol/ust gorseli URL (opsiyonel)',
  `buton_metin` varchar(80) DEFAULT NULL,
  `buton_link` varchar(255) DEFAULT NULL,
  `buton_renk` enum('kirmizi','sari','mavi','yesil') NOT NULL DEFAULT 'kirmizi',
  `baslangic_tarihi` datetime NOT NULL COMMENT 'Pop-up bu tarihten itibaren goruntulenir',
  `bitis_tarihi` datetime NOT NULL COMMENT 'Pop-up bu tarihten sonra goruntulenmez',
  `gosterim_kurali` enum('her_ziyaret','oturum_basina','gunde_bir','sadece_bir_kez') NOT NULL DEFAULT 'gunde_bir' COMMENT 'her_ziyaret=her sayfa yenilemesinde / oturum_basina=tarayici kapanana kadar 1 kez / gunde_bir=24 saatte 1 kez / sadece_bir_kez=kapatilirsa bir daha asla',
  `acilis_gecikmesi` int(11) NOT NULL DEFAULT 3 COMMENT 'Sayfa yuklendikten kac saniye sonra acilsin',
  `hedef_sayfa` enum('anasayfa','tum_sayfalar') NOT NULL DEFAULT 'tum_sayfalar' COMMENT 'anasayfa=sadece / tum_sayfalar=her sayfada',
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `sira` int(11) NOT NULL DEFAULT 0 COMMENT 'Birden fazla aktif kampanya varsa sira buyuk olan gosterilir',
  `gosterim_sayisi` int(11) NOT NULL DEFAULT 0 COMMENT 'Toplam gosterim (analytics)',
  `tiklama_sayisi` int(11) NOT NULL DEFAULT 0 COMMENT 'Toplam buton tiklama (analytics)',
  `olusturma_tarihi` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `guncelleme_tarihi` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_aktif_tarih` (`aktif`,`baslangic_tarihi`,`bitis_tarihi`),
  KEY `idx_sira` (`sira`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- v1.1.23 - Kullanici adi ile giris destegi
-- Yunus istegi: 'Yoneticilere kolaylik, Kullanici Adi VE Mail ile girebilsinler'
-- ============================================================

-- Idempotent: kullanici_adi kolonu yoksa ekle
SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'mz_kullanicilar'
      AND COLUMN_NAME  = 'kullanici_adi'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `mz_kullanicilar`
       ADD COLUMN `kullanici_adi` VARCHAR(50) DEFAULT NULL COMMENT ''Login icin alternatif (email yerine kullanilabilir)'' AFTER `ad_soyad`,
       ADD UNIQUE KEY `uk_kullanici_adi` (`kullanici_adi`)',
    'SELECT ''Column kullanici_adi already exists'' AS msg'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Mevcut kullanicilar icin kullanici_adi'ni email'in @ oncesinden otomatik doldur
-- (sadece NULL olanlar icin, manuel set edilmis olanlar korunur)
UPDATE `mz_kullanicilar`
SET `kullanici_adi` = LOWER(SUBSTRING_INDEX(`email`, '@', 1))
WHERE `kullanici_adi` IS NULL OR `kullanici_adi` = '';

-- ============================================================
-- v1.1.24 - Admin topbar hizli aksiyon butonlari (sadece kod, SQL yok)
-- + /sitemap.php legacy URL 301 redirect (sadece kod, SQL yok)
-- ============================================================

-- ============================================================
-- v1.1.25 - Kullanici adi: Turkce + buyuk harf izni (sadece kod)
-- DB collation utf8mb4_unicode_ci zaten case-insensitive
-- ============================================================

-- ============================================================
-- v1.1.27 - send_mail imza fix + BCC eksiklikleri + bcc default guncelle
-- ============================================================

-- Talep bildirim BCC default 'teklifmerkezi@...' -> 'teklifbirimi@...'
-- Yunus 'teklifbirimi@mizansigorta.com.tr' istiyor (onceki default 'teklifmerkezi'di).
-- Sadece henuz manuel degistirmemis olanlar icin guncelle (idempotent).
UPDATE `mz_ayarlar`
   SET `deger` = 'teklifbirimi@mizansigorta.com.tr'
 WHERE `anahtar` = 'talep_bildirim_bcc'
   AND (`deger` IS NULL OR `deger` = '' OR `deger` = 'teklifmerkezi@mizansigorta.com.tr');

-- Eger anahtar tabloda hic yoksa ekle (yeni kurulumlar icin)
INSERT INTO `mz_ayarlar` (`anahtar`,`deger`,`aciklama`,`grup`,`tip`)
SELECT 'talep_bildirim_bcc', 'teklifbirimi@mizansigorta.com.tr',
       'Teklif/hasar/iletisim/temsilci formlari ve hatirlatma mailleri bu adrese de BCC ile kopyalanir. Birden fazla adres virgulle ayrilabilir.',
       'mail', 'text'
WHERE NOT EXISTS (SELECT 1 FROM `mz_ayarlar` WHERE `anahtar` = 'talep_bildirim_bcc');

-- ============================================================
-- v1.1.29 - Teklif toplu/tekil silme + durum degisikligi mail bildirimi
-- (sadece kod, SQL yok)
-- ============================================================
