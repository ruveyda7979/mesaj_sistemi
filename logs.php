<?php
require 'db.php';

// Logları çek
$stmt = $pdo->query("
    SELECT 
        l.*,
        u.ad, u.soyad, u.mail,
        m.baslik as mail_baslik
    FROM loglar l
    JOIN üyeler u ON l.uye_id = u.id
    JOIN mailler m ON l.mail_id = m.id
    ORDER BY l.gonderim_tarihi DESC
    LIMIT 100
");
$loglar = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Mail Logları</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
        .error {
            color: #dc3545;
            font-weight: bold;
        }
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .back-btn:hover {
            background-color: #545b62;
        }
        .stats {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <a href="index.php" class="back-btn">← Ana Sayfaya Dön</a>
    
    <h2>Mail Gönderim Logları</h2>
    
    <div class="stats">
        <p><strong>Toplam Log:</strong> <?= count($loglar) ?></p>
        <p><strong>Başarılı Gönderim:</strong> <?= $pdo->query("SELECT COUNT(*) FROM loglar WHERE durum = 'başarılı'")->fetchColumn() ?></p>
        <p><strong>Başarısız Gönderim:</strong> <?= $pdo->query("SELECT COUNT(*) FROM loglar WHERE durum = 'başarısız'")->fetchColumn() ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Tarih</th>
                <th>Üye</th>
                <th>Mail</th>
                <th>Mail Başlığı</th>
                <th>Durum</th>
                <th>Hata Mesajı</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($loglar as $log): ?>
                <tr>
                    <td><?= htmlspecialchars(date('d.m.Y H:i:s', strtotime($log['gonderim_tarihi']))) ?></td>
                    <td><?= htmlspecialchars($log['ad'] . ' ' . $log['soyad']) ?></td>
                    <td><?= htmlspecialchars($log['mail']) ?></td>
                    <td><?= htmlspecialchars($log['mail_baslik']) ?></td>
                    <td class="<?= $log['durum'] === 'başarılı' ? 'success' : 'error' ?>">
                        <?= htmlspecialchars($log['durum']) ?>
                    </td>
                    <td><?= htmlspecialchars($log['hata_mesaji'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html> 