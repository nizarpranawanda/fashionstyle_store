<?php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'message' => 'Metode tidak diizinkan'], 405);
if (!pelanggan_login()) json_out(['ok' => false, 'message' => 'Silakan login terlebih dahulu.'], 401);

$pelangganId = (int) $_SESSION['pelanggan_id'];
$in = body_input();
$itemId = (int) ($in['item_id'] ?? 0);
$delta  = (int) ($in['delta'] ?? 0); // +1 atau -1

$stmt = $pdo->prepare("
    SELECT dk.*, p.stok, p.harga AS harga_produk, p.nama AS nama_produk
    FROM detail_keranjang dk
    JOIN keranjang k ON k.id = dk.keranjang_id
    JOIN produk p ON p.id = dk.produk_id
    WHERE dk.id = ? AND k.pelanggan_id = ?
");
$stmt->execute([$itemId, $pelangganId]);
$item = $stmt->fetch();
if (!$item) json_out(['ok' => false, 'message' => 'Item tidak ditemukan.'], 404);

$jumlahBaru = (int) $item['jumlah'] + $delta;
if ($jumlahBaru < 1) $jumlahBaru = 1;
if ($jumlahBaru > (int) $item['stok']) {
    $jumlahBaru = (int) $item['stok'];
    $pesan = 'Stok produk tidak mencukupi.';
}

$harga = (float) $item['harga_produk'];
$upd = $pdo->prepare("UPDATE detail_keranjang SET jumlah = ?, harga = ?, subtotal = ? WHERE id = ?");
$upd->execute([$jumlahBaru, $harga, $jumlahBaru * $harga, $itemId]);

$badge = hitung_badge_keranjang($pdo, $pelangganId);
json_out(['ok' => true, 'jumlah' => $jumlahBaru, 'subtotal' => $jumlahBaru * $harga, 'badge' => $badge, 'message' => $pesan ?? null]);
