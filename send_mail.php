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
// ✅ GÜNCELLEME 1: trim() eklendi - boşlukları temizle
$baslik = trim($_POST['baslik'] ?? '');
$icerik = trim($_POST['icerik'] ?? '');

// Validasyon
if (empty($komiteler) || empty($baslik) || empty($icerik)) {
    $hata = "Tüm alanları doldurunuz!";
    header("Location: index.php?hata=" . urlencode($hata));
    exit;
}

// ✅ GÜNCELLEME 2: Komite ID'lerinin geçerli olduğunu kontrol et
$komite_ids = array_map('intval', $komiteler);
$placeholders = str_repeat('?,', count($komite_ids) - 1) . '?';
$komite_kontrol = $pdo->prepare("SELECT COUNT(*) FROM komiteler WHERE id IN ($placeholders)");
$komite_kontrol->execute($komite_ids);

if ($komite_kontrol->fetchColumn() != count($komite_ids)) {
    $hata = "Geçersiz komite seçimi!";
    header("Location: index.php?hata=" . urlencode($hata));
    exit;
}

try {
    // ✅ GÜNCELLEME 3: Transaction eklendi - veri tutarlılığı için
    $pdo->beginTransaction();
    
    // Mail kaydını oluştur
    $stmt = $pdo->prepare("INSERT INTO mailler (baslik, icerik) VALUES (?, ?)");
    $stmt->execute([$baslik, $icerik]);
    $mail_id = $pdo->lastInsertId();
    
    // ✅ GÜNCELLEME 4: NULL mail kontrolü eklendi
    $stmt = $pdo->prepare("SELECT id, ad, soyad, mail FROM üyeler WHERE komite_id IN ($placeholders) AND mail IS NOT NULL AND mail != ''");
    $stmt->execute($komite_ids);
    $uyeler = $stmt->fetchAll();
    
    // ✅ GÜNCELLEME 5: Üye bulunamazsa hata ver
    if (empty($uyeler)) {
        throw new Exception("Seçilen komitelerde mail adresi olan üye bulunamadı!");
    }
    
    $basarili = 0;
    $basarisiz = 0;
    $loglar = [];
    
    // Her üyeye mail gönder
    foreach ($uyeler as $uye) {
        // ✅ GÜNCELLEME 6: Mail adresinin geçerli olduğunu kontrol et
        if (!filter_var($uye['mail'], FILTER_VALIDATE_EMAIL)) {
            $basarisiz++;
            $loglar[] = [
                'uye_id' => $uye['id'],
                'mail_id' => $mail_id,
                'durum' => 'başarısız',
                'hata_mesaji' => 'Geçersiz mail adresi'
            ];
            continue;
        }
        
        $sonuc = mailGonder($uye['mail'], $uye['ad'] . ' ' . $uye['soyad'], $baslik, $icerik);
        
        if ($sonuc === true) {
            $basarili++;
            $durum = 'başarılı';
            $hata_mesaji = null;
        } else {
            $basarisiz++;
            $durum = 'başarısız';
            // ✅ GÜNCELLEME 7: Hata mesajı string kontrolü
            $hata_mesaji = is_string($sonuc) ? $sonuc : 'Bilinmeyen hata';
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
    
    // ✅ GÜNCELLEME 8: Transaction'ı bitir
    $pdo->commit();
    
    // ✅ GÜNCELLEME 9: Daha detaylı başarı mesajı
    $mesaj = "Mail gönderimi tamamlandı! ";
    $mesaj .= "Toplam: " . count($uyeler) . " kişi, ";
    $mesaj .= "Başarılı: $basarili, ";
    $mesaj .= "Başarısız: $basarisiz";
    
    header("Location: index.php?mesaj=" . urlencode($mesaj));
    
} catch (Exception $e) {
    // ✅ GÜNCELLEME 10: Transaction'ı geri al hata durumunda
    $pdo->rollback();
    
    $hata = "Bir hata oluştu: " . $e->getMessage();
    header("Location: index.php?hata=" . urlencode($hata));
}
?>