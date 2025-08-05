<?php
require 'db.php';

// Komiteleri veritabanından çek
$komiteSorgu = $pdo->query("SELECT * FROM komiteler");
$komiteler = $komiteSorgu->fetchAll(PDO::FETCH_ASSOC);

// Mesaj ve hata kontrolü
$mesaj = $_GET['mesaj'] ?? '';
$hata = $_GET['hata'] ?? '';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Toplu Mail Gönder</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
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
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
            color: #555;
        }
        input[type="text"], textarea, select {
            width: 100%;
            padding: 12px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        select[multiple] {
            height: 200px;
        }
        button {
            margin-top: 20px;
            padding: 12px 30px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        button:hover {
            background-color: #0056b3;
        }
        .stats {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .stats p {
            margin: 5px 0;
            color: #666;
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>Komitelere Toplu Mail Gönder</h2>
        <div style="text-align: right; margin-bottom: 20px;">
            <a href="logs.php" style="color: #007bff; text-decoration: none;">📊 Logları Görüntüle</a>
        </div>
    
    <?php if ($mesaj): ?>
        <div class="message success"><?= htmlspecialchars($mesaj) ?></div>
    <?php endif; ?>
    
    <?php if ($hata): ?>
        <div class="message error"><?= htmlspecialchars($hata) ?></div>
    <?php endif; ?>
    
    <div class="stats">
        <p><strong>Toplam Komite:</strong> <?= count($komiteler) ?></p>
        <p><strong>Toplam Üye:</strong> <?= $pdo->query("SELECT COUNT(*) FROM üyeler")->fetchColumn() ?></p>
        <p><strong>Toplam Mail:</strong> <?= $pdo->query("SELECT COUNT(*) FROM mailler")->fetchColumn() ?></p>
    </div>

    <form action="send_mail.php" method="POST">
        <label for="komiteler">Komiteler (Birden fazla seçebilirsiniz - Ctrl tuşu ile)</label>
        <select name="komiteler[]" id="komiteler" multiple required>
            <?php foreach ($komiteler as $komite): ?>
                <option value="<?= htmlspecialchars($komite['id']) ?>">
                    <?= htmlspecialchars($komite['ad']) ?> 
                    (<?= $pdo->query("SELECT COUNT(*) FROM üyeler WHERE komite_id = " . $komite['id'])->fetchColumn() ?> üye)
                </option>
            <?php endforeach; ?>
        </select>

        <label for="baslik">Mail Başlığı</label>
        <input type="text" name="baslik" id="baslik" required placeholder="Mail başlığını giriniz">

        <label for="icerik">Mail İçeriği</label>
        <textarea name="icerik" id="icerik" rows="12" required placeholder="Mail içeriğini giriniz (HTML desteklenir)"></textarea>

        <button type="submit">Mail Gönder</button>
    </form>
</div>

</body>
</html>
