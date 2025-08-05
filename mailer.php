<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// TEST MODU - Gerçek mail göndermez, sadece log tutar
define('TEST_MODE', true);

function mailGonder($aliciMail, $aliciAdSoyad, $konu, $icerik) {
    if (TEST_MODE) {
        // Test modu - gerçek mail göndermez
        echo "TEST MODU: Mail gönderildi (gerçekte gönderilmedi)<br>";
        echo "Alıcı: $aliciMail<br>";
        echo "Konu: $konu<br>";
        echo "İçerik: $icerik<br><br>";
        return true; // Başarılı olarak döner
    }
    
    // Gerçek mail gönderme kodu (TEST_MODE false olduğunda çalışır)
    $mail = new PHPMailer(true);

    try {
        // Server ayarları
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'gercek_mail_adresiniz@gmail.com'; // Gerçek mail adresiniz
        $mail->Password   = 'uygulama_sifresi'; // Gmail uygulama şifresi
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Gönderici ve alıcı bilgileri
        $mail->setFrom('gercek_mail_adresiniz@gmail.com', 'Mesaj Sistemi');
        $mail->addAddress($aliciMail, $aliciAdSoyad);

        // İçerik
        $mail->isHTML(true);
        $mail->Subject = $konu;
        $mail->Body    = $icerik;
        $mail->AltBody = strip_tags($icerik);

        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Mesaj gönderilemedi. Hata: {$mail->ErrorInfo}";
    }
}
