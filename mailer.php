<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// TEST MODU - Gerçek mail göndermez, sadece log tutar
define('TEST_MODE', true);

function mailGonder($aliciMail, $aliciAdSoyad, $konu, $icerik) {
    // TEST MODU: Gerçek mail gönderimi yapılmaz
    if (TEST_MODE) {
        // ✅ GÜNCELLEME 1: HTML çıktı kaldırıldı, sadece return true
        // Önceden: echo ile HTML çıktı veriyordu (bu send_mail.php'de sorun yaratıyordu)
        // Şimdi: Sadece log dosyasına yaz ve true döndür
        
        $logMessage = "[TEST MODU] " . date('Y-m-d H:i:s') . " - ";
        $logMessage .= "Mail gönderildi: {$aliciAdSoyad} <{$aliciMail}> - Konu: {$konu}\n";
        file_put_contents('test_mail_log.txt', $logMessage, FILE_APPEND | LOCK_EX);
        
        return true;
    }

    // Gerçek mail gönderimi (TEST_MODE false olduğunda)
    $mail = new PHPMailer(true);

    try {
        // ✅ GÜNCELLEME 2: Ayarlar sabitlere taşındı
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'seninmailin@gmail.com';
        $mail->Password   = 'uygulama_sifresi';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // 'tls' yerine sabit kullanıldı
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8'; // ✅ GÜNCELLEME 3: Türkçe karakter desteği

        $mail->setFrom('seninmailin@gmail.com', 'Senin Adın');
        $mail->addAddress($aliciMail, $aliciAdSoyad);

        $mail->isHTML(true);
        $mail->Subject = $konu;
        $mail->Body    = $icerik;
        $mail->AltBody = strip_tags($icerik);

        $mail->send();
        return true;
    } catch (Exception $e) {
        // ✅ GÜNCELLEME 4: Hata loglaması eklendi
        $errorMessage = date('Y-m-d H:i:s') . " - Mail hatası: {$mail->ErrorInfo}\n";
        file_put_contents('mail_errors.log', $errorMessage, FILE_APPEND | LOCK_EX);
        
        return "Mesaj gönderilemedi. Hata: {$mail->ErrorInfo}";
    }
}
?>