<?php
require 'db.php';

$komite_id = $_GET['komite_id'] ?? 0;
$komite_id = (int)$komite_id;

$stmt = $pdo->prepare("SELECT ad, soyad, mail FROM üyeler WHERE komite_id = ?");
$stmt->execute([$komite_id]);
$uyeler = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode($uyeler);
