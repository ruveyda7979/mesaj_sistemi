<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Composer ile yüklenen PHPMailer klasörünü dahil eder

function mailGonder($aliciMail, $aliciAdSoyad, $konu, $icerik) {
    $mail = new PHPMailer(true); // true → hata yakalamayı aktif eder

    try {
        // Server ayarları
        $mail->isSMTP();                          // SMTP kullan
        $mail->Host       = 'smtp.gmail.com';     // SMTP sunucusu (örnek: Gmail)
        $mail->SMTPAuth   = true;                 // SMTP doğrulaması aktif
        $mail->Username   = 'seninmailin@gmail.com'; // Gönderici Gmail adresi
        $mail->Password   = 'uygulama şifresi';      // Gmail için uygulama şifresi
        $mail->SMTPSecure = 'tls';                // Şifreleme türü: ssl veya tls
        $mail->Port       = 587;                  // Gmail için TLS portu

        // Gönderici ve alıcı bilgileri
        $mail->setFrom('seninmailin@gmail.com', 'Senin Adın'); // Gönderici
        $mail->addAddress($aliciMail, $aliciAdSoyad);          // Alıcı

        // İçerik
        $mail->isHTML(true);                      // HTML içerik gönder
        $mail->Subject = $konu;                   // Mail konusu
        $mail->Body    = $icerik;                 // Mail içeriği (HTML olabilir)
        $mail->AltBody = strip_tags($icerik);     // HTML desteklemeyenler için düz metin

        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Mesaj gönderilemedi. Hata: {$mail->ErrorInfo}";
    }
}
