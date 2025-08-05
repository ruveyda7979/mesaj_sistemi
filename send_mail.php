<?php
require 'db.php';
require 'mailer.php';

// POST verilerini kontrol et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$komiteler_json = $_POST['komiteler'] ?? '[]';
$komiteler = json_decode($komiteler_json, true) ?? [];
$baslik = $_POST['baslik'] ?? '';
$icerik = $_POST['icerik'] ?? '';

// Validasyon
if (empty($komiteler) || empty($baslik) || empty($icerik)) {
    $hata = "Tüm alanları doldurunuz!";
    header("Location: index.php?hata=" . urlencode($hata));
    exit;
}

try {
    // Mail kaydını oluştur
    $stmt = $pdo->prepare("INSERT INTO mailler (baslik, icerik) VALUES (?, ?)");
    $stmt->execute([$baslik, $icerik]);
    $mail_id = $pdo->lastInsertId();
    
    // Seçilen komitelerdeki üyeleri al
    $placeholders = str_repeat('?,', count($komiteler) - 1) . '?';
    $stmt = $pdo->prepare("SELECT id, ad, soyad, mail FROM üyeler WHERE komite_id IN ($placeholders)");
    $stmt->execute($komiteler);
    $uyeler = $stmt->fetchAll();
    
    $basarili = 0;
    $basarisiz = 0;
    $loglar = [];
    
    // Her üyeye mail gönder
    foreach ($uyeler as $uye) {
        $sonuc = mailGonder($uye['mail'], $uye['ad'] . ' ' . $uye['soyad'], $baslik, $icerik);
        
        if ($sonuc === true) {
            $basarili++;
            $durum = 'başarılı';
            $hata_mesaji = null;
        } else {
            $basarisiz++;
            $durum = 'başarısız';
            $hata_mesaji = $sonuc;
        }
        
        // Log kaydı
        $loglar[] = [
            'uye_id' => $uye['id'],
            'mail_id' => $mail_id,
            'durum' => $durum,
            'hata_mesaji' => $hata_mesaji
        ];
    }
    
    // Logları veritabanına kaydet
    $stmt = $pdo->prepare("INSERT INTO loglar (uye_id, mail_id, durum, hata_mesaji) VALUES (?, ?, ?, ?)");
    foreach ($loglar as $log) {
        $stmt->execute([$log['uye_id'], $log['mail_id'], $log['durum'], $log['hata_mesaji']]);
    }
    
    $mesaj = "Mail gönderimi tamamlandı! Başarılı: $basarili, Başarısız: $basarisiz";
    header("Location: index.php?mesaj=" . urlencode($mesaj));
    
} catch (Exception $e) {
    $hata = "Bir hata oluştu: " . $e->getMessage();
    header("Location: index.php?hata=" . urlencode($hata));
}
?>