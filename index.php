<?php
require 'db.php';

// Komiteleri ve üye sayılarını çek
$komiteSorgu = $pdo->query("
    SELECT k.*, 
           COUNT(u.id) as uye_sayisi 
    FROM komiteler k 
    LEFT JOIN üyeler u ON k.id = u.komite_id 
    GROUP BY k.id 
    ORDER BY k.ad
");
$komiteler = $komiteSorgu->fetchAll(PDO::FETCH_ASSOC);

// Sistem istatistikleri
$toplamKomite = count($komiteler);
$toplamUye = $pdo->query("SELECT COUNT(*) FROM üyeler")->fetchColumn();
$toplamMail = $pdo->query("SELECT COUNT(*) FROM mailler")->fetchColumn();

// Mesaj ve hata kontrolü
$mesaj = $_GET['mesaj'] ?? '';
$hata = $_GET['hata'] ?? '';

// AJAX istekleri için komite üyelerini çek
if (isset($_GET['ajax']) && $_GET['ajax'] === 'members' && isset($_GET['komite_id'])) {
    $komite_id = intval($_GET['komite_id']);
    $uyeStmt = $pdo->prepare("SELECT id, ad, soyad, mail FROM üyeler WHERE komite_id = ? ORDER BY ad, soyad");
    $uyeStmt->execute([$komite_id]);
    $uyeler = $uyeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($uyeler);
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toplu Mail Gönder</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 1rem 2rem;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stats-header {
            display: flex;
            gap: 2rem;
        }

        .stat-item {
            text-align: center;
            padding: 0.5rem;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 2rem;
            min-height: calc(100vh - 100px);
        }

        .sidebar {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 120px;
        }

        .sidebar h3 {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .committee-list {
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 1.5rem;
        }

        .committee-item {
            background: #f8f9fa;
            border: 2px solid transparent;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .committee-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .committee-item.selected {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: #667eea;
            transform: translateX(5px);
        }

        .committee-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .committee-name {
            font-weight: 600;
            font-size: 0.95rem;
        }

        .member-count {
            background: #28a745;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .committee-item.selected .member-count {
            background: rgba(255,255,255,0.3);
        }

        .view-members {
            color: #667eea;
            font-size: 0.8rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            margin-top: 0.5rem;
            opacity: 0.8;
            cursor: pointer;
        }

        .view-members:hover {
            opacity: 1;
        }

        .committee-item.selected .view-members {
            color: rgba(255,255,255,0.9);
        }

        .selected-summary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 1rem;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 1rem;
        }

        .selected-count {
            font-size: 1.2rem;
            font-weight: 700;
        }

        .selected-detail {
            font-size: 0.85rem;
            opacity: 0.9;
            margin-top: 0.5rem;
        }

        .main-content {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .content-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f3f4;
        }

        .content-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .content-subtitle {
            color: #666;
            font-size: 1rem;
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #495057;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-textarea {
            min-height: 150px;
            resize: vertical;
            font-family: inherit;
        }

        .send-section {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 2rem;
            border-radius: 15px;
            margin-top: 2rem;
            text-align: center;
        }

        .send-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 1.25rem 3rem;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .send-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .send-info {
            color: #666;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .logs-btn {
            background: rgba(255,255,255,0.9);
            color: #667eea;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logs-btn:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal.active {
            display: flex;
            opacity: 1;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            max-width: 600px;
            width: 100%;
            max-height: 80vh;
            overflow-y: auto;
            transform: translateY(50px);
            transition: transform 0.3s ease;
        }

        .modal.active .modal-content {
            transform: translateY(0);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f3f4;
        }

        .modal-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #333;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .close-btn:hover {
            background: #f1f3f4;
            color: #333;
        }

        .member-list {
            display: grid;
            gap: 0.75rem;
        }

        .member-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .member-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .member-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .member-details h4 {
            font-size: 1rem;
            color: #333;
            margin-bottom: 0.25rem;
        }

        .member-details p {
            font-size: 0.85rem;
            color: #666;
        }

        .no-selection-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #666;
        }

        .no-selection-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }

        .no-selection-state h3 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
            color: #999;
        }

        .no-selection-state p {
            font-size: 1rem;
            line-height: 1.6;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .loading i {
            font-size: 2rem;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-container {
                grid-template-columns: 1fr;
                padding: 1rem;
            }

            .sidebar {
                position: static;
            }

            .stats-header {
                display: none;
            }

            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
        }

        /* Scrollbar Styling */
        .committee-list::-webkit-scrollbar {
            width: 6px;
        }

        .committee-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .committee-list::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 3px;
        }

        .committee-list::-webkit-scrollbar-thumb:hover {
            background: #999;
        }
    </style>
</head>
<body>

<header class="header">
    <div class="header-content">
        <div class="logo">
            <i class="fas fa-envelope"></i>
            Toplu Mail Sistemi
        </div>
        
        <div class="stats-header">
            <div class="stat-item">
                <div class="stat-number"><?= $toplamKomite ?></div>
                <div class="stat-label">Komite</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= $toplamUye ?></div>
                <div class="stat-label">Üye</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= $toplamMail ?></div>
                <div class="stat-label">Mail</div>
            </div>
        </div>
        
        <a href="logs.php" class="logs-btn">
            <i class="fas fa-chart-bar"></i>
            Loglar
        </a>
    </div>
</header>

<div class="main-container">
    <!-- Sidebar -->
    <div class="sidebar">
        <h3><i class="fas fa-users"></i> Komiteler</h3>
        
        <div class="committee-list">
            <?php foreach ($komiteler as $komite): ?>
                <div class="committee-item" 
                     data-committee-id="<?= $komite['id'] ?>" 
                     onclick="toggleCommittee(this, '<?= htmlspecialchars($komite['ad']) ?>', <?= $komite['uye_sayisi'] ?>)">
                    <div class="committee-header">
                        <div class="committee-name"><?= htmlspecialchars($komite['ad']) ?></div>
                        <div class="member-count"><?= $komite['uye_sayisi'] ?> kişi</div>
                    </div>
                    <a href="#" class="view-members" 
                       onclick="event.stopPropagation(); showMembers('<?= htmlspecialchars($komite['ad']) ?>', <?= $komite['id'] ?>)">
                        <i class="fas fa-eye"></i> Üyeleri Gör
                    </a>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($komiteler)): ?>
                <div style="text-align: center; color: #666; padding: 2rem;">
                    <i class="fas fa-info-circle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                    <p>Henüz komite bulunmuyor.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="selected-summary" id="selectedSummary" style="display: none;">
            <div class="selected-count" id="selectedCount">0 komite seçildi</div>
            <div class="selected-detail" id="selectedDetail">0 kişiye mail gönderilecek</div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-header">
            <h1 class="content-title">
                <i class="fas fa-paper-plane"></i>
                Mail Gönder
            </h1>
            <p class="content-subtitle">Seçtiğiniz komitelerdeki tüm üyelere mail gönderin</p>
        </div>

        <!-- Alertler -->
        <?php if ($mesaj): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($mesaj) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($hata): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($hata) ?>
            </div>
        <?php endif; ?>

        <div id="mailFormSection" style="display: none;">
            <form action="send_mail.php" method="POST">
                <input type="hidden" name="komiteler" id="selectedCommitteesInput">
                
                <div class="form-section">
                    <div class="form-group">
                        <label for="baslik" class="form-label">
                            <i class="fas fa-heading"></i>
                            Mail Başlığı
                        </label>
                        <input 
                            type="text" 
                            name="baslik" 
                            id="baslik" 
                            class="form-input" 
                            placeholder="Örn: Toplantı Daveti - 15 Ocak 2024"
                            required
                            oninput="updateSendButton()"
                        >
                    </div>

                    <div class="form-group">
                        <label for="icerik" class="form-label">
                            <i class="fas fa-edit"></i>
                            Mail İçeriği
                        </label>
                        <textarea 
                            name="icerik" 
                            id="icerik" 
                            class="form-input form-textarea" 
                            placeholder="Mail içeriğinizi buraya yazın... (HTML desteklenir)"
                            required
                            oninput="updateSendButton()"
                        ></textarea>
                    </div>
                </div>

                <div class="send-section">
                    <button type="submit" class="send-btn" id="sendBtn" disabled>
                        <i class="fas fa-rocket"></i>
                        Mail Gönder
                    </button>
                    <p class="send-info" id="sendInfo">
                        Mail göndermeden önce komite seçimi yapmalısınız
                    </p>
                </div>
            </form>
        </div>

        <div id="noSelectionState" class="no-selection-state">
            <i class="fas fa-mouse-pointer"></i>
            <h3>Komite Seçin</h3>
            <p>Mail göndermek için sol taraftan bir veya daha fazla komite seçin. Seçtiğiniz komitelerdeki tüm üyelere mail gönderilecektir.</p>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal" id="membersModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="modalTitle">Komite Üyeleri</h2>
            <button class="close-btn" onclick="closeMembersModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="member-list" id="memberList">
            <div class="loading">
                <i class="fas fa-spinner"></i>
                <p>Yükleniyor...</p>
            </div>
        </div>
    </div>
</div>

<script>
let selectedCommittees = [];
let committeeData = <?= json_encode(array_column($komiteler, 'uye_sayisi', 'id')) ?>;

function toggleCommittee(element, name, count) {
    const committeeId = element.dataset.committeeId;
    const index = selectedCommittees.indexOf(committeeId);
    
    if (index > -1) {
        selectedCommittees.splice(index, 1);
        element.classList.remove('selected');
    } else {
        selectedCommittees.push(committeeId);
        element.classList.add('selected');
    }
    
    updateSelectedSummary();
    updateFormVisibility();
    updateSendButton();
}

function updateSelectedSummary() {
    const summaryDiv = document.getElementById('selectedSummary');
    const countSpan = document.getElementById('selectedCount');
    const detailSpan = document.getElementById('selectedDetail');
    const hiddenInput = document.getElementById('selectedCommitteesInput');
    
    if (selectedCommittees.length > 0) {
        summaryDiv.style.display = 'block';
        
        let totalMembers = 0;
        selectedCommittees.forEach(id => {
            totalMembers += parseInt(committeeData[id]) || 0;
        });
        
        countSpan.textContent = `${selectedCommittees.length} komite seçildi`;
        detailSpan.textContent = `${totalMembers} kişiye mail gönderilecek`;
        hiddenInput.value = JSON.stringify(selectedCommittees);
    } else {
        summaryDiv.style.display = 'none';
        hiddenInput.value = '';
    }
}

function updateFormVisibility() {
    const formSection = document.getElementById('mailFormSection');
    const noSelectionState = document.getElementById('noSelectionState');
    
    if (selectedCommittees.length > 0) {
        formSection.style.display = 'block';
        noSelectionState.style.display = 'none';
    } else {
        formSection.style.display = 'none';
        noSelectionState.style.display = 'block';
    }
}

function updateSendButton() {
    const sendBtn = document.getElementById('sendBtn');
    const baslik = document.getElementById('baslik').value.trim();
    const icerik = document.getElementById('icerik').value.trim();
    const sendInfo = document.getElementById('sendInfo');
    
    if (selectedCommittees.length > 0 && baslik && icerik) {
        sendBtn.disabled = false;
        let totalMembers = 0;
        selectedCommittees.forEach(id => {
            totalMembers += parseInt(committeeData[id]) || 0;
        });
        sendInfo.textContent = `${selectedCommittees.length} komitedeki ${totalMembers} kişiye mail gönderilecek`;
    } else {
        sendBtn.disabled = true;
        if (selectedCommittees.length === 0) {
            sendInfo.textContent = 'Mail göndermeden önce komite seçimi yapmalısınız';
        } else if (!baslik) {
            sendInfo.textContent = 'Mail başlığını giriniz';
        } else if (!icerik) {
            sendInfo.textContent = 'Mail içeriğini giriniz';
        }
    }
}

function showMembers(committeeName, committeeId) {
    const modal = document.getElementById('membersModal');
    const modalTitle = document.getElementById('modalTitle');
    const memberList = document.getElementById('memberList');
    
    modalTitle.textContent = `${committeeName} Üyeleri`;
    
    // Loading durumu
    memberList.innerHTML = `
        <div class="loading">
            <i class="fas fa-spinner"></i>
            <p>Üyeler yükleniyor...</p>
        </div>
    `;
    
    modal.classList.add('active');
    
    // AJAX ile üyeleri çek
    fetch(`?ajax=members&komite_id=${committeeId}`)
        .then(response => response.json())
        .then(members => {
            memberList.innerHTML = '';
            
            if (members.length > 0) {
                members.forEach(member => {
                    const memberDiv = document.createElement('div');
                    memberDiv.className = 'member-item';
                    memberDiv.innerHTML = `
                        <div class="member-info">
                            <div class="member-avatar">${member.ad.charAt(0)}</div>
                            <div class="member-details">
                                <h4>${member.ad} ${member.soyad}</h4>
                                <p>${member.mail}</p>
                            </div>
                        </div>
                    `;
                    memberList.appendChild(memberDiv);
                });
            } else {
                memberList.innerHTML = '<p style="text-align: center; color: #666; padding: 2rem;">Bu komitede henüz üye bulunmuyor.</p>';
            }
        })
        .catch(error => {
            console.error('Üyeler yüklenemedi:', error);
            memberList.innerHTML = '<p style="text-align: center; color: #dc3545; padding: 2rem;">Üyeler yüklenirken bir hata oluştu.</p>';
        });
}

function closeMembersModal() {
    const modal = document.getElementById('membersModal');
    modal.classList.remove('active');
}

// Modal dışına tıklanınca kapat
document.getElementById('membersModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeMembersModal();
    }
});

// ESC tuşu ile modal kapat
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeMembersModal();
    }
});

// Sayfa yüklendiğinde başlangıç durumunu ayarla
document.addEventListener('DOMContentLoaded', function() {
    updateFormVisibility();
    updateSendButton();
});
</script>

</body>
</html>