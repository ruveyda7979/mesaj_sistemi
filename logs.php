<?php
require 'db.php';

// Excel export kontrolü
if (isset($_GET['export']) && $_GET['export'] == '1') {
    // Aynı filtreleme mantığını kullan
    $where_kosullari = [];
    $parametreler = [];
    
    if (isset($_GET['durum']) && $_GET['durum']) {
        $where_kosullari[] = "l.durum = ?";
        $parametreler[] = $_GET['durum'];
    }
    
    if (isset($_GET['tarih_baslangic']) && $_GET['tarih_baslangic']) {
        $where_kosullari[] = "DATE(l.gonderim_tarihi) >= ?";
        $parametreler[] = $_GET['tarih_baslangic'];
    }
    
    if (isset($_GET['tarih_bitis']) && $_GET['tarih_bitis']) {
        $where_kosullari[] = "DATE(l.gonderim_tarihi) <= ?";
        $parametreler[] = $_GET['tarih_bitis'];
    }
    
    if (isset($_GET['arama']) && $_GET['arama']) {
        $where_kosullari[] = "(u.ad LIKE ? OR u.soyad LIKE ? OR u.mail LIKE ? OR m.baslik LIKE ?)";
        $arama_param = '%' . $_GET['arama'] . '%';
        $parametreler[] = $arama_param;
        $parametreler[] = $arama_param;
        $parametreler[] = $arama_param;
        $parametreler[] = $arama_param;
    }
    
    $where_sql = '';
    if (!empty($where_kosullari)) {
        $where_sql = 'WHERE ' . implode(' AND ', $where_kosullari);
    }
    
    // Export için tüm verileri al
    $export_sql = "SELECT 
                    l.gonderim_tarihi,
                    CONCAT(u.ad, ' ', u.soyad) as kullanici,
                    u.mail,
                    m.baslik as mail_baslik,
                    l.durum,
                    l.hata_mesaji
                FROM loglar l
                JOIN üyeler u ON l.uye_id = u.id
                JOIN mailler m ON l.mail_id = m.id
                $where_sql
                ORDER BY l.gonderim_tarihi DESC";
    
    $export_stmt = $pdo->prepare($export_sql);
    $export_stmt->execute($parametreler);
    $export_data = $export_stmt->fetchAll();
    
    // Excel headers
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="mail_loglari_' . date('Y-m-d_H-i-s') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // BOM ekle (Türkçe karakterler için)
    echo "\xEF\xBB\xBF";
    
    // Excel tablo başlığı
    echo "<!DOCTYPE html>\n";
    echo "<html>\n<head>\n";
    echo "<meta charset='UTF-8'>\n";
    echo "<style>\n";
    echo "table { border-collapse: collapse; width: 100%; }\n";
    echo "th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }\n";
    echo "th { background-color: #f2f2f2; font-weight: bold; }\n";
    echo ".success { color: green; font-weight: bold; }\n";
    echo ".error { color: red; font-weight: bold; }\n";
    echo "</style>\n";
    echo "</head>\n<body>\n";
    
    echo "<h2>Mail Gönderim Logları - " . date('d.m.Y H:i') . "</h2>\n";
    echo "<p>Toplam Kayıt: " . count($export_data) . "</p>\n\n";
    
    echo "<table>\n";
    echo "<tr>\n";
    echo "<th>Gönderim Tarihi</th>\n";
    echo "<th>Kullanıcı</th>\n";
    echo "<th>E-posta</th>\n";
    echo "<th>Mail Başlığı</th>\n";
    echo "<th>Durum</th>\n";
    echo "<th>Hata Mesajı</th>\n";
    echo "</tr>\n";
    
    foreach ($export_data as $row) {
        echo "<tr>\n";
        echo "<td>" . htmlspecialchars(date('d.m.Y H:i:s', strtotime($row['gonderim_tarihi']))) . "</td>\n";
        echo "<td>" . htmlspecialchars($row['kullanici']) . "</td>\n";
        echo "<td>" . htmlspecialchars($row['mail']) . "</td>\n";
        echo "<td>" . htmlspecialchars($row['mail_baslik']) . "</td>\n";
        echo "<td class='" . ($row['durum'] === 'başarılı' ? 'success' : 'error') . "'>" . htmlspecialchars($row['durum']) . "</td>\n";
        echo "<td>" . htmlspecialchars($row['hata_mesaji'] ?? '-') . "</td>\n";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    echo "</body>\n</html>\n";
    exit;
}

// Bulk delete işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['bulk_delete'])) {
    header('Content-Type: application/json');
    
    $input = json_decode(file_get_contents('php://input'), true);
    $ids = $input['ids'] ?? [];
    
    if (empty($ids)) {
        echo json_encode(['success' => false, 'message' => 'Silinecek kayıt seçilmedi']);
        exit;
    }
    
    try {
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $pdo->prepare("DELETE FROM loglar WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        
        echo json_encode([
            'success' => true, 
            'deleted' => $stmt->rowCount(),
            'message' => $stmt->rowCount() . ' kayıt silindi'
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Chart data
if (isset($_GET['chart_data'])) {
    header('Content-Type: application/json');
    
    // Başarı/başarısızlık oranları
    $basarili = $pdo->query("SELECT COUNT(*) FROM loglar WHERE durum = 'başarılı'")->fetchColumn();
    $basarisiz = $pdo->query("SELECT COUNT(*) FROM loglar WHERE durum = 'başarısız'")->fetchColumn();
    
    // Son 7 gün için zaman çizelgesi
    $zaman_data = $pdo->query("
        SELECT 
            DATE(gonderim_tarihi) as tarih,
            SUM(CASE WHEN durum = 'başarılı' THEN 1 ELSE 0 END) as basarili,
            SUM(CASE WHEN durum = 'başarısız' THEN 1 ELSE 0 END) as basarisiz
        FROM loglar 
        WHERE gonderim_tarihi >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(gonderim_tarihi)
        ORDER BY tarih
    ")->fetchAll();
    
    $zaman_etiketleri = [];
    $zaman_basarili = [];
    $zaman_basarisiz = [];
    
    foreach ($zaman_data as $row) {
        $zaman_etiketleri[] = date('d.m', strtotime($row['tarih']));
        $zaman_basarili[] = (int)$row['basarili'];
        $zaman_basarisiz[] = (int)$row['basarisiz'];
    }
    
    echo json_encode([
        'basarili' => (int)$basarili,
        'basarisiz' => (int)$basarisiz,
        'zaman_etiketleri' => $zaman_etiketleri,
        'zaman_basarili' => $zaman_basarili,
        'zaman_basarisiz' => $zaman_basarisiz
    ]);
    exit;
}

// Log detayı
if (isset($_GET['log_detail'])) {
    header('Content-Type: application/json');
    
    $log_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("
        SELECT 
            l.*,
            CONCAT(u.ad, ' ', u.soyad) as kullanici,
            u.mail,
            m.baslik as mail_baslik,
            m.icerik as mail_icerik
        FROM loglar l
        JOIN üyeler u ON l.uye_id = u.id
        JOIN mailler m ON l.mail_id = m.id
        WHERE l.id = ?
    ");
    $stmt->execute([$log_id]);
    $log = $stmt->fetch();
    
    if ($log) {
        echo json_encode([
            'success' => true,
            'log' => [
                'gonderim_tarihi' => date('d.m.Y H:i:s', strtotime($log['gonderim_tarihi'])),
                'kullanici' => $log['kullanici'],
                'mail' => $log['mail'],
                'mail_baslik' => $log['mail_baslik'],
                'durum' => $log['durum'],
                'hata_mesaji' => $log['hata_mesaji'],
                'mail_icerik' => $log['mail_icerik']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Log bulunamadı']);
    }
    exit;
}

// Sayfa parametreleri
$sayfa = isset($_GET['sayfa']) ? (int)$_GET['sayfa'] : 1;
$sayfa_basina = 20; // Her sayfada 20 kayıt
$baslangic = ($sayfa - 1) * $sayfa_basina;

// Filtreleme parametreleri
$durum_filtre = isset($_GET['durum']) ? $_GET['durum'] : '';
$tarih_baslangic = isset($_GET['tarih_baslangic']) ? $_GET['tarih_baslangic'] : '';
$tarih_bitis = isset($_GET['tarih_bitis']) ? $_GET['tarih_bitis'] : '';
$arama = isset($_GET['arama']) ? $_GET['arama'] : '';

// WHERE koşulları oluştur
$where_kosullari = [];
$parametreler = [];

if ($durum_filtre) {
    $where_kosullari[] = "l.durum = ?";
    $parametreler[] = $durum_filtre;
}

if ($tarih_baslangic) {
    $where_kosullari[] = "DATE(l.gonderim_tarihi) >= ?";
    $parametreler[] = $tarih_baslangic;
}

if ($tarih_bitis) {
    $where_kosullari[] = "DATE(l.gonderim_tarihi) <= ?";
    $parametreler[] = $tarih_bitis;
}

if ($arama) {
    $where_kosullari[] = "(u.ad LIKE ? OR u.soyad LIKE ? OR u.mail LIKE ? OR m.baslik LIKE ?)";
    $arama_param = '%' . $arama . '%';
    $parametreler[] = $arama_param;
    $parametreler[] = $arama_param;
    $parametreler[] = $arama_param;
    $parametreler[] = $arama_param;
}

$where_sql = '';
if (!empty($where_kosullari)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_kosullari);
}

// Toplam kayıt sayısını al
$toplam_sql = "SELECT COUNT(*) FROM loglar l 
               JOIN üyeler u ON l.uye_id = u.id 
               JOIN mailler m ON l.mail_id = m.id 
               $where_sql";
$toplam_stmt = $pdo->prepare($toplam_sql);
$toplam_stmt->execute($parametreler);
$toplam_kayit = $toplam_stmt->fetchColumn();
$toplam_sayfa = ceil($toplam_kayit / $sayfa_basina);

// Logları çek
$sql = "SELECT 
            l.*,
            u.ad, u.soyad, u.mail,
            m.baslik as mail_baslik
        FROM loglar l
        JOIN üyeler u ON l.uye_id = u.id
        JOIN mailler m ON l.mail_id = m.id
        $where_sql
        ORDER BY l.gonderim_tarihi DESC
        LIMIT $sayfa_basina OFFSET $baslangic";

$stmt = $pdo->prepare($sql);
$stmt->execute($parametreler);
$loglar = $stmt->fetchAll();

// İstatistikler
$istatistikler = [
    'toplam' => $pdo->query("SELECT COUNT(*) FROM loglar")->fetchColumn(),
    'basarili' => $pdo->query("SELECT COUNT(*) FROM loglar WHERE durum = 'başarılı'")->fetchColumn(),
    'basarisiz' => $pdo->query("SELECT COUNT(*) FROM loglar WHERE durum = 'başarısız'")->fetchColumn(),
    'bugun' => $pdo->query("SELECT COUNT(*) FROM loglar WHERE DATE(gonderim_tarihi) = CURDATE()")->fetchColumn()
];
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mail Logları - Gelişmiş Görünüm</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            font-weight: 300;
        }
        
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .back-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .stat-card {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }
        
        .content {
            padding: 30px;
        }
        
        .additional-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filters {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #495057;
        }
        
        .filter-group input, 
        .filter-group select {
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .filter-group input:focus, 
        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
            transform: translateY(-1px);
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .bulk-actions {
            display: none;
            gap: 10px;
            margin-bottom: 20px;
            padding: 15px;
            background: #fff3cd;
            border-radius: 8px;
            border: 1px solid #ffeaa7;
            align-items: center;
        }
        
        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            min-width: 900px;
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
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        tr:hover {
            background-color: #e3f2fd;
            transform: scale(1.01);
            transition: all 0.2s ease;
        }
        
        .status-success {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-error {
            color: #dc3545;
            font-weight: bold;
        }
        
        .row-checkbox {
            cursor: pointer;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .page-link {
            padding: 10px 15px;
            background: white;
            border: 2px solid #e9ecef;
            color: #495057;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .page-link:hover {
            border-color: #667eea;
            color: #667eea;
            transform: translateY(-1px);
        }
        
        .page-link.active {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-color: transparent;
        }
        
        .page-info {
            padding: 10px 15px;
            background: #f8f9fa;
            border-radius: 8px;
            font-weight: 600;
            color: #495057;
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .no-data i {
            font-size: 4em;
            margin-bottom: 20px;
            color: #dee2e6;
        }
        
        .chart-container {
            display: none;
            padding: 30px;
        }
        
        .chart-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        #log-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .detail-item {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-item.full-width {
            grid-template-columns: 1fr;
        }
        
        .mail-content {
            max-height: 200px;
            overflow-y: auto;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            white-space: pre-wrap;
        }
        
        .error-message {
            color: #dc3545;
            font-family: monospace;
            background: #f8d7da;
            padding: 10px;
            border-radius: 5px;
        }
        
        .floating-buttons {
            position: fixed;
            bottom: 30px;
            right: 30px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 1000;
        }
        
        .floating-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: none;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            color: white;
        }
        
        .floating-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }
        
        .auto-refresh-btn.active {
            background: #28a745 !important;
        }
        
        .dark-mode {
            filter: invert(1) hue-rotate(180deg);
        }
        
        .dark-mode img,
        .dark-mode video,
        .dark-mode iframe {
            filter: invert(1) hue-rotate(180deg);
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 10px;
            }
            
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 1.8em;
            }
            
            .content {
                padding: 20px;
            }
            
            .filter-row {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .floating-buttons {
                bottom: 20px;
                right: 20px;
            }
            
            .floating-btn {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }
            
            .chart-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <a href="index.php" class="back-btn">← Ana Sayfa</a>
        <h1>📊 Mail Gönderim Logları</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number"><?= number_format($istatistikler['toplam']) ?></span>
                <span>Toplam Log</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?= number_format($istatistikler['basarili']) ?></span>
                <span>Başarılı</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?= number_format($istatistikler['basarisiz']) ?></span>
                <span>Başarısız</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?= number_format($istatistikler['bugun']) ?></span>
                <span>Bugün</span>
            </div>
        </div>
    </div>
    
    <div class="content">
        <!-- Ek Butonlar -->
        <div class="additional-buttons">
            <button onclick="toggleAutoRefresh()" class="btn btn-secondary auto-refresh-btn">
                🔄 Otomatik Yenileme Başlat
            </button>
            <button onclick="toggleChartView()" class="btn btn-primary chart-toggle-btn">
                📈 Grafik Görünümü
            </button>
            <button onclick="toggleDarkMode()" class="btn btn-secondary dark-mode-btn">
                🌙
            </button>
        </div>
        
        <!-- Bulk Actions -->
        <div class="bulk-actions">
            <span>📋 <span class="selected-count">0</span> kayıt seçildi</span>
            <button onclick="deleteSelectedLogs()" class="btn btn-danger">🗑️ Seçilenleri Sil</button>
            <button onclick="exportSelected()" class="btn btn-success">📊 Seçilenleri Dışa Aktar</button>
        </div>
        
        <!-- Filtreler -->
        <div class="filters">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>📅 Başlangıç Tarihi</label>
                        <input type="date" name="tarih_baslangic" value="<?= htmlspecialchars($tarih_baslangic) ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>📅 Bitiş Tarihi</label>
                        <input type="date" name="tarih_bitis" value="<?= htmlspecialchars($tarih_bitis) ?>">
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">🔍 Filtrele</button>
                    <a href="logs.php" class="btn btn-secondary">🔄 Temizle</a>
                </div>
            </form>
        </div>
        
        <!-- Chart Container -->
        <div class="chart-container">
            <div class="chart-grid">
                <div class="chart-card">
                    <canvas id="successChart"></canvas>
                </div>
                <div class="chart-card">
                    <canvas id="timeChart"></canvas>
                </div>
            </div>
        </div>
        
        <?php if (empty($loglar)): ?>
            <div class="no-data">
                <div style="font-size: 4em; margin-bottom: 20px;">📝</div>
                <h3>Log kaydı bulunamadı</h3>
                <p>Belirttiğiniz kriterlere uygun log kaydı bulunmuyor.</p>
                <?php if (!empty($_GET)): ?>
                    <a href="logs.php" class="btn btn-primary" style="margin-top: 20px;">Tüm Logları Görüntüle</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all" onchange="toggleAllCheckboxes()"></th>
                            <th>📅 Tarih & Saat</th>
                            <th>👤 Kullanıcı</th>
                            <th>📧 E-posta</th>
                            <th>📋 Mail Başlığı</th>
                            <th>📊 Durum</th>
                            <th>⚠️ Hata Detayı</th>
                            <th>🔍 Detay</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loglar as $log): ?>
                            <tr>
                                <td><input type="checkbox" class="row-checkbox" value="<?= $log['id'] ?>" onchange="updateBulkActions()"></td>
                                <td>
                                    <strong><?= date('d.m.Y', strtotime($log['gonderim_tarihi'])) ?></strong><br>
                                    <small><?= date('H:i:s', strtotime($log['gonderim_tarihi'])) ?></small>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($log['ad'] . ' ' . $log['soyad']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($log['mail']) ?></td>
                                <td><?= htmlspecialchars($log['mail_baslik']) ?></td>
                                <td>
                                    <span class="<?= $log['durum'] === 'başarılı' ? 'status-success' : 'status-error' ?>">
                                        <?= $log['durum'] === 'başarılı' ? '✅ Başarılı' : '❌ Başarısız' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($log['hata_mesaji']): ?>
                                        <span title="<?= htmlspecialchars($log['hata_mesaji']) ?>">
                                            <?= htmlspecialchars(mb_substr($log['hata_mesaji'], 0, 50)) ?>
                                            <?= mb_strlen($log['hata_mesaji']) > 50 ? '...' : '' ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #6c757d;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button onclick="showLogDetail(<?= $log['id'] ?>)" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;">
                                        👁️ Görüntüle
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Sayfalandırma -->
            <?php if ($toplam_sayfa > 1): ?>
                <div class="pagination">
                    <?php if ($sayfa > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['sayfa' => 1])) ?>" class="page-link">⏮️ İlk</a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['sayfa' => $sayfa - 1])) ?>" class="page-link">⬅️ Önceki</a>
                    <?php endif; ?>
                    
                    <div class="page-info">
                        Sayfa <?= $sayfa ?> / <?= $toplam_sayfa ?> (<?= number_format($toplam_kayit) ?> kayıt)
                    </div>
                    
                    <?php if ($sayfa < $toplam_sayfa): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['sayfa' => $sayfa + 1])) ?>" class="page-link">Sonraki ➡️</a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['sayfa' => $toplam_sayfa])) ?>" class="page-link">Son ⏭️</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal -->
<div id="log-modal">
    <div class="modal-content">
        <button onclick="closeModal()" style="position: absolute; top: 10px; right: 15px; background: none; border: none; font-size: 24px; cursor: pointer;">×</button>
        <div id="modal-content">
            <!-- Log detayları buraya yüklenecek -->
        </div>
    </div>
</div>

<!-- Floating Buttons -->
<div class="floating-buttons">
    <button class="floating-btn" onclick="exportLogs()" title="Excel'e Aktar" style="background: linear-gradient(45deg, #28a745, #20c997);">
        📊
    </button>
    <button class="floating-btn" onclick="location.reload()" title="Sayfayı Yenile" style="background: linear-gradient(45deg, #007bff, #0056b3);">
        🔄
    </button>
    <button class="floating-btn" onclick="window.scrollTo(0,0)" title="Yukarı Çık" style="background: linear-gradient(45deg, #6c757d, #495057);">
        ⬆️
    </button>
</div>

<script>
// Canlı arama - kullanıcı yazmayı bıraktığında otomatik ara
let aramaTimeout;
document.querySelector('input[name="arama"]').addEventListener('input', function() {
    clearTimeout(aramaTimeout);
    aramaTimeout = setTimeout(() => {
        if (this.value.length >= 3 || this.value.length === 0) {
            document.querySelector('form').submit();
        }
    }, 500);
});

// Otomatik yenileme toggle
let autoRefresh = false;
let refreshInterval;

function toggleAutoRefresh() {
    const btn = document.querySelector('.auto-refresh-btn');
    if (!autoRefresh) {
        autoRefresh = true;
        btn.textContent = '⏸️ Otomatik Yenileme Durdur';
        btn.classList.add('active');
        refreshInterval = setInterval(() => {
            if (!document.querySelector('input:focus, select:focus')) {
                location.reload();
            }
        }, 10000); // 10 saniyede bir
    } else {
        autoRefresh = false;
        btn.textContent = '🔄 Otomatik Yenileme Başlat';
        btn.classList.remove('active');
        clearInterval(refreshInterval);
    }
}

// Bulk işlemler için seçim sistemi
function toggleAllCheckboxes() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    const selectAll = document.querySelector('#select-all');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateBulkActions();
}

function updateBulkActions() {
    const selected = document.querySelectorAll('.row-checkbox:checked');
    const bulkActions = document.querySelector('.bulk-actions');
    
    if (selected.length > 0) {
        bulkActions.style.display = 'flex';
        document.querySelector('.selected-count').textContent = selected.length;
    } else {
        bulkActions.style.display = 'none';
    }
}

// Seçili logları sil
async function deleteSelectedLogs() {
    const selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
    
    if (selected.length === 0) {
        alert('Lütfen silinecek kayıtları seçin.');
        return;
    }
    
    if (!confirm(`${selected.length} adet log kaydını silmek istediğinizden emin misiniz?`)) {
        return;
    }
    
    try {
        const response = await fetch('logs.php?bulk_delete=1', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ ids: selected })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(`${result.deleted} adet kayıt silindi.`);
            location.reload();
        } else {
            alert('Hata: ' + result.message);
        }
    } catch (error) {
        alert('İşlem sırasında hata oluştu: ' + error.message);
    }
}

// Seçili logları export et
function exportSelected() {
    const selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
    
    if (selected.length === 0) {
        alert('Lütfen export edilecek kayıtları seçin.');
        return;
    }
    
    // Selected IDs ile export URL'i oluştur
    const params = new URLSearchParams(window.location.search);
    params.set('export', '1');
    params.set('selected_ids', selected.join(','));
    
    window.open('logs.php?' + params.toString(), '_blank');
}

// Log detayları modal
function showLogDetail(logId) {
    fetch(`logs.php?log_detail=1&id=${logId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelector('#modal-content').innerHTML = `
                    <h3>📋 Log Detayları</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <strong>Gönderim Tarihi:</strong>
                            <span>${data.log.gonderim_tarihi}</span>
                        </div>
                        <div class="detail-item">
                            <strong>Kullanıcı:</strong>
                            <span>${data.log.kullanici}</span>
                        </div>
                        <div class="detail-item">
                            <strong>E-posta:</strong>
                            <span>${data.log.mail}</span>
                        </div>
                        <div class="detail-item">
                            <strong>Mail Başlığı:</strong>
                            <span>${data.log.mail_baslik}</span>
                        </div>
                        <div class="detail-item">
                            <strong>Durum:</strong>
                            <span class="${data.log.durum === 'başarılı' ? 'status-success' : 'status-error'}">
                                ${data.log.durum === 'başarılı' ? '✅ Başarılı' : '❌ Başarısız'}
                            </span>
                        </div>
                        ${data.log.hata_mesaji ? `
                            <div class="detail-item full-width">
                                <strong>Hata Mesajı:</strong>
                                <div class="error-message">${data.log.hata_mesaji}</div>
                            </div>
                        ` : ''}
                        ${data.log.mail_icerik ? `
                            <div class="detail-item full-width">
                                <strong>Mail İçeriği:</strong>
                                <div class="mail-content">${data.log.mail_icerik}</div>
                            </div>
                        ` : ''}
                    </div>
                `;
                document.querySelector('#log-modal').style.display = 'flex';
            }
        })
        .catch(error => {
            alert('Log detayı yüklenirken hata oluştu: ' + error.message);
        });
}

function closeModal() {
    document.querySelector('#log-modal').style.display = 'none';
}

// Grafik görünümü toggle
function toggleChartView() {
    const table = document.querySelector('.table-container');
    const chart = document.querySelector('.chart-container');
    const btn = document.querySelector('.chart-toggle-btn');
    
    if (table.style.display !== 'none') {
        table.style.display = 'none';
        chart.style.display = 'block';
        btn.textContent = '📊 Tablo Görünümü';
        loadChart();
    } else {
        table.style.display = 'block';
        chart.style.display = 'none';
        btn.textContent = '📈 Grafik Görünümü';
    }
}

// Başarı oranı grafiği yükle
async function loadChart() {
    try {
        const response = await fetch('logs.php?chart_data=1' + window.location.search);
        const data = await response.json();
        
        // Chart.js ile grafik oluştur
        const ctx = document.getElementById('successChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Başarılı', 'Başarısız'],
                datasets: [{
                    data: [data.basarili, data.basarisiz],
                    backgroundColor: ['#28a745', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: true,
                        text: 'Mail Gönderim Başarı Oranı'
                    }
                }
            }
        });
        
        // Zaman çizelgesi
        const timeCtx = document.getElementById('timeChart').getContext('2d');
        new Chart(timeCtx, {
            type: 'line',
            data: {
                labels: data.zaman_etiketleri,
                datasets: [{
                    label: 'Başarılı Gönderimler',
                    data: data.zaman_basarili,
                    borderColor: '#28a745',
                    fill: false
                }, {
                    label: 'Başarısız Gönderimler',
                    data: data.zaman_basarisiz,
                    borderColor: '#dc3545',
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
    } catch (error) {
        console.error('Grafik yüklenirken hata:', error);
    }
}

function exportLogs() {
    // Mevcut filtrelerle export sayfasına git
    const params = new URLSearchParams(window.location.search);
    params.set('export', '1');
    window.open('logs.php?' + params.toString(), '_blank');
}

// Dark mode toggle
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    localStorage.setItem('darkMode', isDark);
    
    document.querySelector('.dark-mode-btn').textContent = isDark ? '☀️' : '🌙';
}

// Dark mode yükle
if (localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark-mode');
    document.querySelector('.dark-mode-btn').textContent = '☀️';
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl+F: Arama alanına odaklan
    if (e.ctrlKey && e.key === 'f') {
        e.preventDefault();
        document.querySelector('input[name="arama"]').focus();
    }
    
    // Ctrl+R: Sayfayı yenile
    if (e.ctrlKey && e.key === 'r') {
        e.preventDefault();
        location.reload();
    }
    
    // Escape: Modal'ı kapat
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>

</body>
</html><label>🔍 Arama</label>
                        <input type="text" name="arama" value="<?= htmlspecialchars($arama) ?>" 
                               placeholder="İsim, email, mail başlığı...">
                    </div>
                    
                    <div class="filter-group">
                        <label>📊 Durum</label>
                        <select name="durum">
                            <option value="">Tümü</option>
                            <option value="başarılı" <?= $durum_filtre === 'başarılı' ? 'selected' : '' ?>>Başarılı</option>
                            <option value="başarısız" <?= $durum_filtre === 'başarısız' ? 'selected' : '' ?>>Başarısız</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">