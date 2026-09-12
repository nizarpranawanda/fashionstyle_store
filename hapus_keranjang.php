<?php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'message' => 'Metode tidak diizinkan'], 405);
if (!pelanggan_login()) json_out(['ok' => false, 'message' => 'Silakan login terlebih dahulu.'], 401);

$pelangganId = (int) $_SESSION['pelanggan_id'];
$in = body_input();
$itemId = (int) ($in['item_id'] ?? 0);

$stmt = $pdo->prepare("
    DELETE dk FROM detail_keranjang dk
    JOIN keranjang k ON k.id = dk.keranjang_id
    WHERE dk.id = ? AND k.pelanggan_id = ?
");
$stmt->execute([$itemId, $pelangganId]);

$badge = hitung_badge_keranjang($pdo, $pelangganId);
json_out(['ok' => true, 'badge' => $badge]);
