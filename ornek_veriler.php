<?php
require 'db.php';

// Komiteler tablosunu temizle ve örnek veriler ekle
$pdo->exec("DELETE FROM loglar");
$pdo->exec("DELETE FROM uyeler");
$pdo->exec("DELETE FROM komiteler");

// 70 komite oluştur
$komiteler = [
    'Yönetim Kurulu', 'Denetim Kurulu', 'İnsan Kaynakları', 'Finans Komitesi', 'Teknoloji Komitesi',
    'Pazarlama Komitesi', 'Satış Komitesi', 'Müşteri Hizmetleri', 'Kalite Kontrol', 'Üretim Komitesi',
    'Araştırma Geliştirme', 'Eğitim Komitesi', 'Sağlık Güvenlik', 'Çevre Komitesi', 'Hukuk Komitesi',
    'İletişim Komitesi', 'Sosyal Sorumluluk', 'Spor Komitesi', 'Kültür Sanat', 'Bilim Komitesi',
    'Teknik Komite', 'Strateji Komitesi', 'Risk Yönetimi', 'İç Denetim', 'Dış İlişkiler',
    'Uluslararası İşler', 'Yerel Yönetim', 'Merkezi Yönetim', 'Bölgesel Koordinasyon', 'Şube Yönetimi',
    'Üye Hizmetleri', 'Organizasyon', 'Etkinlik Yönetimi', 'Sponsorluk', 'Medya İlişkileri',
    'Dijital Pazarlama', 'Sosyal Medya', 'İçerik Yönetimi', 'Tasarım Komitesi', 'Yazılım Geliştirme',
    'Sistem Yönetimi', 'Ağ Güvenliği', 'Veri Analizi', 'İş Zekası', 'Proje Yönetimi',
    'Portföy Yönetimi', 'Yatırım Komitesi', 'Varlık Yönetimi', 'Sigorta Komitesi', 'Emeklilik',
    'Sağlık Sigortası', 'Hayat Sigortası', 'Araç Sigortası', 'Konut Sigortası', 'İş Sigortası',
    'Seyahat Sigortası', 'Eğitim Sigortası', 'Kredi Sigortası', 'Teminat Sigortası', 'Sorumluluk Sigortası',
    'Mesleki Sorumluluk', 'Ürün Sorumluluğu', 'Çevre Sorumluluğu', 'İşveren Sorumluluğu', 'Genel Sorumluluk',
    'Özel Riskler', 'Endüstriyel Riskler', 'Ticari Riskler', 'Kişisel Riskler', 'Kurumsal Riskler'
];

$stmt = $pdo->prepare("INSERT INTO komiteler (ad) VALUES (?)");
foreach ($komiteler as $komite) {
    $stmt->execute([$komite]);
}

// Örnek üyeler oluştur (her komite için 10-20 arası üye)
$adlar = ['Ahmet', 'Mehmet', 'Ali', 'Ayşe', 'Fatma', 'Zeynep', 'Mustafa', 'Hasan', 'Hüseyin', 'İbrahim',
           'Emine', 'Hatice', 'Elif', 'Meryem', 'Zehra', 'Esra', 'Büşra', 'Seda', 'Selin', 'Deniz',
           'Can', 'Cem', 'Burak', 'Emre', 'Kemal', 'Selim', 'Tamer', 'Uğur', 'Volkan', 'Yasin',
           'Yusuf', 'Zafer', 'Ziya', 'Ömer', 'Özkan', 'Pınar', 'Reyhan', 'Sibel', 'Tuğçe', 'Ufuk'];

$soyadlar = ['Yılmaz', 'Kaya', 'Demir', 'Çelik', 'Şahin', 'Yıldız', 'Yıldırım', 'Özkan', 'Arslan', 'Doğan',
              'Kılıç', 'Aslan', 'Çetin', 'Kurt', 'Koç', 'Özdemir', 'Şen', 'Erdoğan', 'Özkan', 'Aydın',
              'Öztürk', 'Güneş', 'Yavuz', 'Polat', 'Taş', 'Korkmaz', 'Keskin', 'Türk', 'Özer', 'Güler',
              'Çalışkan', 'Yalçın', 'Tekin', 'Sönmez', 'Bilgin', 'Korkut', 'Güven', 'Aktaş', 'Yalçın', 'Kaya'];

$mailDomainleri = ['gmail.com', 'hotmail.com', 'yahoo.com', 'outlook.com', 'live.com'];

$stmt = $pdo->prepare("INSERT INTO uyeler (ad, soyad, mail, telefon, komite_id) VALUES (?, ?, ?, ?, ?)");

for ($komite_id = 1; $komite_id <= 70; $komite_id++) {
    $uye_sayisi = rand(10, 20); // Her komite için 10-20 arası üye
    
    for ($i = 0; $i < $uye_sayisi; $i++) {
        $ad = $adlar[array_rand($adlar)];
        $soyad = $soyadlar[array_rand($soyadlar)];
        $mail = strtolower($ad . '.' . $soyad . '@' . $mailDomainleri[array_rand($mailDomainleri)]);
        $telefon = '05' . rand(10, 99) . rand(100, 999) . rand(10, 99) . rand(10, 99);
        
        try {
            $stmt->execute([$ad, $soyad, $mail, $telefon, $komite_id]);
        } catch (PDOException $e) {
            // Mail zaten varsa farklı bir mail oluştur
            $mail = strtolower($ad . '.' . $soyad . rand(1, 999) . '@' . $mailDomainleri[array_rand($mailDomainleri)]);
            $stmt->execute([$ad, $soyad, $mail, $telefon, $komite_id]);
        }
    }
}

echo "Örnek veriler başarıyla oluşturuldu!\n";
echo "70 komite ve toplam " . $pdo->query("SELECT COUNT(*) FROM uyeler")->fetchColumn() . " üye eklendi.\n";
?> 