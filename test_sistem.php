<?php
require 'db.php';
require 'mailer.php';

echo "<h2>Sistem Test Sayfası</h2>";

// 1. Veritabanı Bağlantısı Testi
echo "<h3>1. Veritabanı Bağlantısı Testi</h3>";
try {
    $test_query = $pdo->query("SELECT COUNT(*) as total FROM komiteler");
    $result = $test_query->fetch();
    echo "✅ Veritabanı bağlantısı başarılı<br>";
    echo "📊 Toplam komite sayısı: " . $result['total'] . "<br><br>";
} catch (Exception $e) {
    echo "❌ Veritabanı bağlantı hatası: " . $e->getMessage() . "<br><br>";
}

// 2. Komiteler Testi
echo "<h3>2. Komiteler Testi</h3>";
try {
    $komiteler = $pdo->query("SELECT * FROM komiteler LIMIT 5")->fetchAll();
    echo "✅ Komiteler yüklendi<br>";
    foreach ($komiteler as $komite) {
        echo "- " . $komite['ad'] . "<br>";
    }
    echo "<br>";
} catch (Exception $e) {
    echo "❌ Komiteler yüklenemedi: " . $e->getMessage() . "<br><br>";
}

// 3. Üyeler Testi
echo "<h3>3. Üyeler Testi</h3>";
try {
    $uyeler = $pdo->query("SELECT * FROM üyeler LIMIT 5")->fetchAll();
    echo "✅ Üyeler yüklendi<br>";
    foreach ($uyeler as $uye) {
        echo "- " . $uye['ad'] . " " . $uye['soyad'] . " (" . $uye['mail'] . ")<br>";
    }
    echo "<br>";
} catch (Exception $e) {
    echo "❌ Üyeler yüklenemedi: " . $e->getMessage() . "<br><br>";
}

// 4. Mail Gönderme Testi
echo "<h3>4. Mail Gönderme Testi</h3>";
if (defined('TEST_MODE') && TEST_MODE) {
    echo "✅ Test modu aktif - Gerçek mail gönderilmez<br>";
} else {
    echo "⚠️ Test modu kapalı - Gerçek mail gönderilir<br>";
}

// Test maili gönder
$test_sonuc = mailGonder('test@example.com', 'Test Kullanıcı', 'Test Maili', 'Bu bir test mailidir.');
if ($test_sonuc === true) {
    echo "✅ Mail gönderme fonksiyonu çalışıyor<br><br>";
} else {
    echo "❌ Mail gönderme hatası: " . $test_sonuc . "<br><br>";
}

// 5. PHPMailer Testi
echo "<h3>5. PHPMailer Testi</h3>";
try {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    echo "✅ PHPMailer yüklendi<br><br>";
} catch (Exception $e) {
    echo "❌ PHPMailer hatası: " . $e->getMessage() . "<br><br>";
}

// 6. Dosya İzinleri Testi
echo "<h3>6. Dosya İzinleri Testi</h3>";
$files = ['index.php', 'send_mail.php', 'mailer.php', 'db.php', 'logs.php'];
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file dosyası mevcut<br>";
    } else {
        echo "❌ $file dosyası bulunamadı<br>";
    }
}
echo "<br>";

// 7. Sistem Özeti
echo "<h3>7. Sistem Özeti</h3>";
$komite_sayisi = $pdo->query("SELECT COUNT(*) FROM komiteler")->fetchColumn();
$uye_sayisi = $pdo->query("SELECT COUNT(*) FROM üyeler")->fetchColumn();
$mail_sayisi = $pdo->query("SELECT COUNT(*) FROM mailler")->fetchColumn();
$log_sayisi = $pdo->query("SELECT COUNT(*) FROM loglar")->fetchColumn();

echo "📊 Komite sayısı: $komite_sayisi<br>";
echo "👥 Üye sayısı: $uye_sayisi<br>";
echo "📧 Mail sayısı: $mail_sayisi<br>";
echo "📝 Log sayısı: $log_sayisi<br><br>";

// 8. Test Sonucu
echo "<h3>8. Test Sonucu</h3>";
if ($komite_sayisi > 0 && $uye_sayisi > 0) {
    echo "🎉 Sistem çalışır durumda! Test edebilirsiniz.<br>";
    echo "<a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ana Sayfaya Git</a>";
} else {
    echo "⚠️ Sistemde eksiklikler var. Lütfen veritabanını kontrol edin.<br>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 40px;
    background-color: #f5f5f5;
}
h2, h3 {
    color: #333;
}
</style> 