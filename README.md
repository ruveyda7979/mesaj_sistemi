# Mesaj Sistemi

Bu proje, komitelere toplu mail gönderimi yapabilen bir PHP uygulamasıdır.

## Özellikler

- 70 farklı komite yönetimi
- Komitelere göre üye gruplandırma
- Toplu mail gönderimi
- Mail gönderim logları
- Modern ve kullanıcı dostu arayüz
- PHPMailer entegrasyonu

## Kurulum

### 1. Gereksinimler
- PHP 7.4+
- MySQL 5.7+
- Composer
- XAMPP/WAMP/LAMP

### 2. Veritabanı Kurulumu

```sql
-- Veritabanını oluştur
CREATE DATABASE mesaj_sistemi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabloları oluştur
CREATE TABLE komiteler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad VARCHAR(100) NOT NULL
);

CREATE TABLE uyeler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad VARCHAR(50) NOT NULL,
    soyad VARCHAR(50) NOT NULL,
    mail VARCHAR(100) NOT NULL UNIQUE,
    telefon VARCHAR(20),
    komite_id INT,
    FOREIGN KEY (komite_id) REFERENCES komiteler(id)
);

CREATE TABLE mailler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    baslik VARCHAR(150) NOT NULL,
    icerik TEXT NOT NULL,
    olusturma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE loglar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uye_id INT NOT NULL,
    mail_id INT NOT NULL,
    gonderim_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    durum ENUM('başarılı','başarısız') NOT NULL,
    hata_mesaji TEXT NULL,
    FOREIGN KEY (uye_id) REFERENCES uyeler(id),
    FOREIGN KEY (mail_id) REFERENCES mailler(id)
);
```

### 3. Composer Bağımlılıkları

```bash
composer install
```

### 4. Örnek Veriler

```bash
php ornek_veriler.php
php ornek_mailler.php
```

### 5. Mail Ayarları

`mailer.php` dosyasında SMTP ayarlarını güncelleyin:

```php
$mail->Host       = 'smtp.gmail.com';     // SMTP sunucusu
$mail->Username   = 'seninmailin@gmail.com'; // Gmail adresi
$mail->Password   = 'uygulama şifresi';      // Gmail uygulama şifresi
```

## Kullanım

1. `index.php` sayfasını açın
2. Mail göndermek istediğiniz komiteleri seçin
3. Mail başlığı ve içeriğini girin
4. "Mail Gönder" butonuna tıklayın
5. Logları `logs.php` sayfasından takip edin

## Dosya Yapısı

```
mesaj_sistemi/
├── index.php          # Ana sayfa
├── send_mail.php      # Mail gönderme işlemi
├── mailer.php         # PHPMailer konfigürasyonu
├── db.php            # Veritabanı bağlantısı
├── logs.php          # Log görüntüleme
├── ornek_veriler.php # Örnek veriler oluşturma
├── ornek_mailler.php # Örnek mailler oluşturma
├── composer.json     # Composer konfigürasyonu
└── README.md         # Bu dosya
```

## Güvenlik

- PDO prepared statements kullanılmıştır
- SQL injection koruması
- XSS koruması (htmlspecialchars)
- Input validasyonu

## Teknik Detaylar

- **Backend:** PHP 7.4+
- **Veritabanı:** MySQL
- **Mail:** PHPMailer
- **Frontend:** HTML5, CSS3
- **Veritabanı ORM:** PDO

## Lisans

Bu proje MIT lisansı altında lisanslanmıştır. 