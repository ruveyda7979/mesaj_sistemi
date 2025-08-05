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
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
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
        .komiteler-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .komite-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .komite-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            border-color: #667eea;
        }
        .komite-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .komite-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .komite-id {
            background: #667eea;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9em;
        }
        .komite-name {
            font-weight: 600;
            font-size: 1.1em;
            margin: 0;
        }
        .komite-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9em;
            opacity: 0.8;
        }
        .member-count {
            background: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .mail-form {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            margin-top: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        input[type="text"]:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        textarea {
            resize: vertical;
            min-height: 120px;
        }
        .send-btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        .send-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .send-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .selected-count {
            text-align: center;
            margin: 20px 0;
            font-weight: 600;
            color: #667eea;
        }
        .logs-link {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.9);
            padding: 10px 15px;
            border-radius: 25px;
            text-decoration: none;
            color: #667eea;
            font-weight: 600;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .logs-link:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .no-selection {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            margin: 20px 0;
        }
    </style>
</head>
<body>

<div class="container">
    <a href="logs.php" class="logs-link">📊 Logları Görüntüle</a>
    
    <h2>Komitelere Toplu Mail Gönder</h2>
    
    <?php if ($mesaj): ?>
        <div class="message success"><?= htmlspecialchars($mesaj) ?></div>
    <?php endif; ?>
    
    <?php if ($hata): ?>
        <div class="message error"><?= htmlspecialchars($hata) ?></div>
    <?php endif; ?>
    
    <div class="stats">
        <h3>📊 Sistem İstatistikleri</h3>
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-number"><?= count($komiteler) ?></span>
                <span class="stat-label">Toplam Komite</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?= $pdo->query("SELECT COUNT(*) FROM üyeler")->fetchColumn() ?></span>
                <span class="stat-label">Toplam Üye</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?= $pdo->query("SELECT COUNT(*) FROM mailler")->fetchColumn() ?></span>
                <span class="stat-label">Toplam Mail</span>
            </div>
        </div>
    </div>

    <h3 style="margin-bottom: 20px; color: #495057;">📋 Komiteleri Seçin</h3>
    
    <div class="komiteler-grid">
        <?php foreach ($komiteler as $index => $komite): 
            $uye_sayisi = $pdo->query("SELECT COUNT(*) FROM üyeler WHERE komite_id = " . $komite['id'])->fetchColumn();
        ?>
            <div class="komite-card" data-komite-id="<?= $komite['id'] ?>" onclick="toggleKomite(this)">
                <div class="komite-header">
                    <div class="komite-id"><?= $index + 1 ?></div>
                    <h4 class="komite-name"><?= htmlspecialchars($komite['ad']) ?></h4>
                </div>
                <div class="komite-stats">
                    <span>Üye Sayısı:</span>
                    <span class="member-count"><?= $uye_sayisi ?> kişi</span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="selected-count" id="selectedCount" style="display: none;">
        <span id="selectedText">0 komite seçildi</span>
    </div>

    <form action="send_mail.php" method="POST" class="mail-form">
        <input type="hidden" name="komiteler" id="selectedKomiteler">
        
        <div class="form-group">
            <label for="baslik">📧 Mail Başlığı</label>
            <input type="text" name="baslik" id="baslik" required placeholder="Mail başlığını giriniz">
        </div>

        <div class="form-group">
            <label for="icerik">📝 Mail İçeriği</label>
            <textarea name="icerik" id="icerik" required placeholder="Mail içeriğini giriniz (HTML desteklenir)"></textarea>
        </div>

        <button type="submit" class="send-btn" id="sendBtn" disabled>
            📤 Mail Gönder
        </button>
    </form>
</div>

<script>
let selectedKomiteler = [];

function toggleKomite(card) {
    const komiteId = card.dataset.komiteId;
    const index = selectedKomiteler.indexOf(komiteId);
    
    if (index > -1) {
        // Komiteyi seçimden çıkar
        selectedKomiteler.splice(index, 1);
        card.classList.remove('selected');
    } else {
        // Komiteyi seç
        selectedKomiteler.push(komiteId);
        card.classList.add('selected');
    }
    
    updateSelectedCount();
    updateSendButton();
}

function updateSelectedCount() {
    const countDiv = document.getElementById('selectedCount');
    const textSpan = document.getElementById('selectedText');
    const hiddenInput = document.getElementById('selectedKomiteler');
    
    if (selectedKomiteler.length > 0) {
        countDiv.style.display = 'block';
        textSpan.textContent = `${selectedKomiteler.length} komite seçildi`;
        hiddenInput.value = JSON.stringify(selectedKomiteler);
    } else {
        countDiv.style.display = 'none';
        hiddenInput.value = '';
    }
}

function updateSendButton() {
    const sendBtn = document.getElementById('sendBtn');
    const baslik = document.getElementById('baslik').value.trim();
    const icerik = document.getElementById('icerik').value.trim();
    
    if (selectedKomiteler.length > 0 && baslik && icerik) {
        sendBtn.disabled = false;
    } else {
        sendBtn.disabled = true;
    }
}

// Form alanlarını dinle
document.getElementById('baslik').addEventListener('input', updateSendButton);
document.getElementById('icerik').addEventListener('input', updateSendButton);
</script>

</body>
</html>