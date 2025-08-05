<?php
require 'db.php';

// Mailler tablosunu temizle
$pdo->exec("DELETE FROM loglar");
$pdo->exec("DELETE FROM mailler");

// Örnek mailler
$ornekMailler = [
    [
        'baslik' => 'Aylık Toplantı Duyurusu',
        'icerik' => '<h2>Değerli Üyelerimiz,</h2>
<p>Bu ayki toplantımız <strong>15 Mart 2024 Cuma günü saat 14:00</strong>\'da yapılacaktır.</p>
<p>Toplantı gündemi:</p>
<ul>
<li>Geçen ay faaliyetlerinin değerlendirilmesi</li>
<li>Bu ay yapılacak etkinliklerin planlanması</li>
<li>Yeni üye önerilerinin görüşülmesi</li>
</ul>
<p>Katılımınızı bekleriz.</p>
<p>Saygılarımızla,<br>Yönetim Kurulu</p>'
    ],
    [
        'baslik' => 'Yeni Üye Kayıt Duyurusu',
        'icerik' => '<h2>Merhaba,</h2>
<p>Komitemize yeni üyeler katılmıştır. Kendilerini hoş karşılayalım.</p>
<p><strong>Yeni Üyeler:</strong></p>
<ul>
<li>Ahmet Yılmaz - Yazılım Geliştirici</li>
<li>Ayşe Demir - Proje Yöneticisi</li>
<li>Mehmet Kaya - Sistem Uzmanı</li>
</ul>
<p>Yeni üyelerimizle tanışmak için önümüzdeki toplantıya katılmanızı öneririz.</p>
<p>Teşekkürler,<br>İnsan Kaynakları Komitesi</p>'
    ],
    [
        'baslik' => 'Eğitim Programı Duyurusu',
        'icerik' => '<h2>Eğitim Programı</h2>
<p>Değerli üyelerimiz,</p>
<p>Bu ay <strong>"Dijital Dönüşüm ve Teknoloji Trendleri"</strong> konulu eğitim programımız başlayacaktır.</p>
<p><strong>Eğitim Detayları:</strong></p>
<ul>
<li>Tarih: 20-22 Mart 2024</li>
<li>Saat: 09:00-17:00</li>
<li>Yer: Konferans Salonu</li>
<li>Eğitmen: Prof. Dr. Ali Tekin</li>
</ul>
<p>Katılım için lütfen en geç 15 Mart\'a kadar kayıt yaptırınız.</p>
<p>İletişim: egitim@komite.org</p>'
    ],
    [
        'baslik' => 'Proje Güncellemesi',
        'icerik' => '<h2>Proje Durumu</h2>
<p>Merhaba,</p>
<p><strong>"Akıllı Şehir Projesi"</strong> kapsamında yapılan çalışmalar hakkında bilgilendirme:</p>
<p><strong>Tamamlanan İşler:</strong></p>
<ul>
<li>Proje planlaması %100 tamamlandı</li>
<li>Teknik analiz raporu hazırlandı</li>
<li>Pilot uygulama başlatıldı</li>
</ul>
<p><strong>Devam Eden İşler:</strong></p>
<ul>
<li>Veri toplama süreci</li>
<li>Kullanıcı geri bildirimleri</li>
<li>Optimizasyon çalışmaları</li>
</ul>
<p>Detaylı rapor için ekteki dosyayı inceleyebilirsiniz.</p>
<p>Proje Ekibi</p>'
    ],
    [
        'baslik' => 'Etkinlik Duyurusu',
        'icerik' => '<h2>Yıllık Etkinlik</h2>
<p>Sevgili üyelerimiz,</p>
<p>Bu yıl <strong>25. Yıllık Buluşma Etkinliğimiz</strong> gerçekleşecektir.</p>
<p><strong>Etkinlik Bilgileri:</strong></p>
<ul>
<li>Tarih: 15 Nisan 2024</li>
<li>Saat: 19:00-23:00</li>
<li>Yer: Grand Hotel</li>
<li>Program: Akşam yemeği, konferans, networking</li>
</ul>
<p><strong>Önemli:</strong> Katılım için lütfen 1 Nisan\'a kadar onay veriniz.</p>
<p>Organizasyon Komitesi</p>'
    ],
    [
        'baslik' => 'Acil Durum Bilgilendirmesi',
        'icerik' => '<h2>Önemli Duyuru</h2>
<p>Değerli üyelerimiz,</p>
<p>Yarın <strong>sistem bakımı</strong> nedeniyle web sitemiz ve e-posta sistemimiz 09:00-12:00 saatleri arasında erişime kapalı olacaktır.</p>
<p>Bu süre zarfında acil durumlar için:</p>
<ul>
<li>Telefon: 0212 555 0123</li>
<li>Mobil: 0532 123 4567</li>
</ul>
<p>Anlayışınız için teşekkür ederiz.</p>
<p>Bilgi İşlem Departmanı</p>'
    ]
];

$stmt = $pdo->prepare("INSERT INTO mailler (baslik, icerik) VALUES (?, ?)");

foreach ($ornekMailler as $mail) {
    $stmt->execute([$mail['baslik'], $mail['icerik']]);
}

echo "Örnek mailler başarıyla oluşturuldu!\n";
echo "Toplam " . count($ornekMailler) . " örnek mail eklendi.\n";
?> 