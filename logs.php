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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        h2 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.2em;
            font-weight: 300;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        th {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            font-weight: 600;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        tr:hover {
            background-color: #e9ecef;
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
            padding: 12px 20px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            margin-bottom: 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .stats {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        .stats h3 {
            margin: 0 0 15px 0;
            font-size: 1.3em;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        .stat-item {
            background: rgba(255,255,255,0.2);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            display: block;
        }
        .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
        }
        .no-logs {
            text-align: center;
            padding: 50px;
            color: #6c757d;
            font-style: italic;
        }
    </style>
</head>
<body>

<div class="container">
    <a href="index.php" class="back-btn">← Ana Sayfaya Dön</a>
    
    <h2>📊 Mail Gönderim Logları</h2>
    
    <div class="stats">
        <h3>📈 Log İstatistikleri</h3>
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-number"><?= count($loglar) ?></span>
                <span class="stat-label">Toplam Log</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?= $pdo->query("SELECT COUNT(*) FROM loglar WHERE durum = 'başarılı'")->fetchColumn() ?></span>
                <span class="stat-label">Başarılı</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?= $pdo->query("SELECT COUNT(*) FROM loglar WHERE durum = 'başarısız'")->fetchColumn() ?></span>
                <span class="stat-label">Başarısız</span>
            </div>
        </div>
    </div>

    <?php if (empty($loglar)): ?>
        <div class="no-logs">
            <h3>📝 Henüz log kaydı bulunmuyor</h3>
            <p>Mail gönderimi yaptığınızda burada loglar görünecektir.</p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>📅 Tarih</th>
                    <th>👤 Üye</th>
                    <th>📧 Mail</th>
                    <th>📋 Mail Başlığı</th>
                    <th>✅ Durum</th>
                    <th>⚠️ Hata Mesajı</th>
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
                            <?= $log['durum'] === 'başarılı' ? '✅' : '❌' ?> <?= htmlspecialchars($log['durum']) ?>
                        </td>
                        <td><?= htmlspecialchars($log['hata_mesaji'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>