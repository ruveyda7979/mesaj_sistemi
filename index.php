<?php
require 'db.php';

// Komiteleri veritabanından çek
$komiteSorgu = $pdo->query("SELECT * FROM komiteler");
$komiteler = $komiteSorgu->fetchAll(PDO::FETCH_ASSOC);
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
        }
        form {
            max-width: 600px;
            margin: auto;
        }
        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }
        input[type="text"], textarea, select {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
        }
        button {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>

<h2>Komitelere Mail Gönder</h2>

<form action="send_mail.php" method="POST">
    <label for="komiteler">Komiteler</label>
    <select name="komiteler[]" id="komiteler" multiple size="5" required>
        <?php foreach ($komiteler as $komite): ?>
            <option value="<?= htmlspecialchars($komite['id']) ?>">
                <?= htmlspecialchars($komite['ad']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="baslik">Mail Başlığı</label>
    <input type="text" name="baslik" id="baslik" required>

    <label for="icerik">Mail İçeriği</label>
    <textarea name="icerik" id="icerik" rows="10" required></textarea>

    <button type="submit">Gönder</button>
</form>

</body>
</html>
