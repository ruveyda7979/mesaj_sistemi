<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Composer ile yüklenen PHPMailer klasörünü dahil eder

function mailGonder($aliciMail, $aliciAdSoyad, $konu, $icerik) {
    // TEST MODU: Gerçek mail gönderimi yapılmaz
    $testModu = true;

    if ($testModu) {
        echo "<div style='border:2px dashed orange; padding:10px; margin:15px 0; background:#fffbe6'>";
        echo "<strong>[TEST MODU]</strong> Mail gönderimi simüle edildi:<br>";
        echo "👤 <strong>Alıcı:</strong> " . htmlspecialchars($aliciAdSoyad) . " &lt;" . htmlspecialchars($aliciMail) . "&gt;<br>";
        echo "✉️ <strong>Konu:</strong> " . htmlspecialchars($konu) . "<br>";
        echo "📝 <strong>İçerik:</strong><br>" . nl2br(htmlspecialchars($icerik));
        echo "</div>";
        return true;
    }

    $mail = new PHPMailer(true); // Gerçek gönderim

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'seninmailin@gmail.com';
        $mail->Password   = 'uygulama şifresi';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('seninmailin@gmail.com', 'Senin Adın');
        $mail->addAddress($aliciMail, $aliciAdSoyad);

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
