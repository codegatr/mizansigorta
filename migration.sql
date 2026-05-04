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
<strong>E-posta:</strong> info@mizansigorta.com.tr<br>
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
<li><strong>E-posta:</strong> kvkk@mizansigorta.com.tr (güvenli elektronik imzalı)</li>
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
<p>Web sitemiz çerezler kullanmaktadır. Çerezler hakkında detaylı bilgi için <a href="/v2/sayfa/cerez-politikasi">Çerez Politikamızı</a> inceleyebilirsiniz.</p>

<h3>6. Üçüncü Taraf Bağlantıları</h3>
<p>Web sitemiz, üçüncü taraf web sitelerine bağlantılar içerebilir. Mizan Sigorta, bu sitelerin gizlilik uygulamalarından sorumlu değildir. Söz konusu sitelerin gizlilik politikalarını ayrıca incelemenizi öneririz.</p>

<h3>7. Çocukların Gizliliği</h3>
<p>Web sitemiz 18 yaşın altındaki bireylere yönelik olarak tasarlanmamıştır. 18 yaşın altındaki kişilerden bilerek kişisel veri toplamayız. 18 yaşın altında olduğunu bildiğimiz bir kişiden veri toplandığını fark edersek, söz konusu verileri derhal sileriz.</p>

<h3>8. Gizlilik Politikasında Değişiklikler</h3>
<p>Bu Gizlilik Politikası zaman zaman güncellenebilir. Güncel sürüm her zaman web sitemizde yayınlanır. Önemli değişikliklerde sizleri ayrıca bilgilendireceğiz.</p>

<h3>9. İletişim</h3>
<p>Gizlilik Politikamız ile ilgili soru, görüş veya endişelerinizi <strong>info@mizansigorta.com.tr</strong> adresine veya yukarıda belirtilen iletişim kanallarımızdan birine iletebilirsiniz.</p>

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
<p>Çerez politikamız hakkında soru ve görüşleriniz için: <strong>info@mizansigorta.com.tr</strong></p>

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
<strong>E-posta:</strong> info@mizansigorta.com.tr<br>
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
<p>KVKK&rsquo;nın 11. maddesi kapsamındaki tüm haklarınız (bilgi alma, düzeltme, silme vb.) için <strong>kvkk@mizansigorta.com.tr</strong> adresine başvurabilirsiniz. Detaylı bilgi için <a href="/v2/sayfa/kvkk">KVKK Aydınlatma Metnimizi</a> inceleyiniz.</p>

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
<li><strong>E-posta:</strong> kvkk@mizansigorta.com.tr (&ldquo;Açık rızamı geri alıyorum&rdquo; konusu ile)</li>
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
<p>Açık Rıza Metni hakkında soru ve görüşleriniz için: <strong>kvkk@mizansigorta.com.tr</strong></p>

<p class="text-muted small mt-4"><em>Son güncelleme tarihi: 2026</em></p>
</div>', 'Açık Rıza Metni - Mizan Sigorta', 'Kişisel verilerin işlenmesine ilişkin açık rıza beyanı.', 0, 96, 1, 1);

-- Mevcut hukuki sayfalari da guncel icerikle UPDATE (idempotent)
UPDATE `mz_sayfalar` SET `icerik` = '<div class="mz-prose">
<p class="lead">İşbu Aydınlatma Metni, 6698 sayılı Kişisel Verilerin Korunması Kanunu (&ldquo;KVKK&rdquo;) kapsamında, veri sorumlusu sıfatıyla <strong>Mizan Sigorta Aracılık Hizmetleri</strong> tarafından kişisel verilerinizin işlenmesine ilişkin esasları açıklamak amacıyla hazırlanmıştır.</p>

<h3>1. Veri Sorumlusunun Kimliği</h3>
<p>
<strong>Veri Sorumlusu:</strong> Mizan Sigorta Aracılık Hizmetleri<br>
<strong>Adres:</strong> Fetih Mah. Libadiye Cad. Tahralı Sok. Kavakyeli İş Merkezi D-Blok K:9 D:24 ATAŞEHİR / İSTANBUL<br>
<strong>E-posta:</strong> info@mizansigorta.com.tr<br>
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
<li><strong>E-posta:</strong> kvkk@mizansigorta.com.tr (güvenli elektronik imzalı)</li>
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
<p>Web sitemiz çerezler kullanmaktadır. Çerezler hakkında detaylı bilgi için <a href="/v2/sayfa/cerez-politikasi">Çerez Politikamızı</a> inceleyebilirsiniz.</p>

<h3>6. Üçüncü Taraf Bağlantıları</h3>
<p>Web sitemiz, üçüncü taraf web sitelerine bağlantılar içerebilir. Mizan Sigorta, bu sitelerin gizlilik uygulamalarından sorumlu değildir. Söz konusu sitelerin gizlilik politikalarını ayrıca incelemenizi öneririz.</p>

<h3>7. Çocukların Gizliliği</h3>
<p>Web sitemiz 18 yaşın altındaki bireylere yönelik olarak tasarlanmamıştır. 18 yaşın altındaki kişilerden bilerek kişisel veri toplamayız. 18 yaşın altında olduğunu bildiğimiz bir kişiden veri toplandığını fark edersek, söz konusu verileri derhal sileriz.</p>

<h3>8. Gizlilik Politikasında Değişiklikler</h3>
<p>Bu Gizlilik Politikası zaman zaman güncellenebilir. Güncel sürüm her zaman web sitemizde yayınlanır. Önemli değişikliklerde sizleri ayrıca bilgilendireceğiz.</p>

<h3>9. İletişim</h3>
<p>Gizlilik Politikamız ile ilgili soru, görüş veya endişelerinizi <strong>info@mizansigorta.com.tr</strong> adresine veya yukarıda belirtilen iletişim kanallarımızdan birine iletebilirsiniz.</p>

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
<p>Çerez politikamız hakkında soru ve görüşleriniz için: <strong>info@mizansigorta.com.tr</strong></p>

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
<strong>E-posta:</strong> info@mizansigorta.com.tr<br>
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
<p>KVKK&rsquo;nın 11. maddesi kapsamındaki tüm haklarınız (bilgi alma, düzeltme, silme vb.) için <strong>kvkk@mizansigorta.com.tr</strong> adresine başvurabilirsiniz. Detaylı bilgi için <a href="/v2/sayfa/kvkk">KVKK Aydınlatma Metnimizi</a> inceleyiniz.</p>

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
<li><strong>E-posta:</strong> kvkk@mizansigorta.com.tr (&ldquo;Açık rızamı geri alıyorum&rdquo; konusu ile)</li>
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
<p>Açık Rıza Metni hakkında soru ve görüşleriniz için: <strong>kvkk@mizansigorta.com.tr</strong></p>

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
SET @has_uk_kategori_soru := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'mz_sss'
    AND INDEX_NAME = 'uk_kategori_soru'
);
-- Once duplikatlari temizle (en eski id kalsin)
DELETE s1 FROM `mz_sss` s1
INNER JOIN `mz_sss` s2
  ON s1.id > s2.id
  AND s1.kategori = s2.kategori
  AND s1.soru = s2.soru;
-- Yanlis encoding'li 'Police' kategorisini sil (dogru: 'Poliçe')
DELETE FROM `mz_sss` WHERE `kategori` = 'Police';
-- UNIQUE ekle
SET @sql_uk_sss := IF(@has_uk_kategori_soru = 0,
  'ALTER TABLE `mz_sss` ADD UNIQUE KEY `uk_kategori_soru` (`kategori`, `soru`(255))',
  'SELECT 1');
PREPARE stmt FROM @sql_uk_sss; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- mz_sigorta_sirketleri: ad UNIQUE olsun
DELETE s1 FROM `mz_sigorta_sirketleri` s1
INNER JOIN `mz_sigorta_sirketleri` s2 ON s1.id > s2.id AND s1.ad = s2.ad;
SET @has_uk_sirket := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'mz_sigorta_sirketleri' AND INDEX_NAME = 'uk_ad'
);
SET @sql_uk_sirket := IF(@has_uk_sirket = 0,
  'ALTER TABLE `mz_sigorta_sirketleri` ADD UNIQUE KEY `uk_ad` (`ad`)',
  'SELECT 1');
PREPARE stmt FROM @sql_uk_sirket; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- mz_hatirlatma_kurallari: ad UNIQUE
DELETE k1 FROM `mz_hatirlatma_kurallari` k1
INNER JOIN `mz_hatirlatma_kurallari` k2 ON k1.id > k2.id AND k1.ad = k2.ad;
SET @has_uk_kural := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'mz_hatirlatma_kurallari' AND INDEX_NAME = 'uk_ad'
);
SET @sql_uk_kural := IF(@has_uk_kural = 0,
  'ALTER TABLE `mz_hatirlatma_kurallari` ADD UNIQUE KEY `uk_ad` (`ad`)',
  'SELECT 1');
PREPARE stmt FROM @sql_uk_kural; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- mz_urunler: Eski seed slug'larini sil (yeni hierarchy lehine)
DELETE FROM `mz_urunler` WHERE `slug` IN (
  'kasko-sigortasi', 'trafik-sigortasi', 'konut-sigortasi',
  'isyeri-sigortasi', 'saglik-sigortasi', 'hayat-sigortasi',
  'seyahat-sigortasi', 'tarim-sigortasi'
);
