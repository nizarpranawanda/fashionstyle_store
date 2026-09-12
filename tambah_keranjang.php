<?php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'message' => 'Metode tidak diizinkan'], 405);
if (!pelanggan_login()) json_out(['ok' => false, 'message' => 'Silakan login terlebih dahulu untuk melakukan transaksi.'], 401);

$pelangganId = (int) $_SESSION['pelanggan_id'];
$in = body_input();
$produkId = (int) ($in['produk_id'] ?? 0);
$jumlah   = max(1, (int) ($in['jumlah'] ?? 1));
$ukuran   = strtoupper(trim($in['ukuran'] ?? 'M'));
if (!in_array($ukuran, UKURAN_DEFAULT, true)) $ukuran = 'M';

$stmt = $pdo->prepare("SELECT * FROM produk WHERE id = ? AND status='aktif'");
$stmt->execute([$produkId]);
$produk = $stmt->fetch();
if (!$produk) json_out(['ok' => false, 'message' => 'Produk tidak ditemukan.'], 404);

$keranjangId = get_or_create_keranjang($pdo, $pelangganId);

$stmt = $pdo->prepare("SELECT * FROM detail_keranjang WHERE keranjang_id = ? AND produk_id = ? AND ukuran = ?");
$stmt->execute([$keranjangId, $produkId, $ukuran]);
$existing = $stmt->fetch();

$jumlahBaru = $jumlah + ($existing ? (int) $existing['jumlah'] : 0);
if ($jumlahBaru > (int) $produk['stok']) {
    json_out(['ok' => false, 'message' => 'Stok produk tidak mencukupi.']);
}

if ($existing) {
    $upd = $pdo->prepare("UPDATE detail_keranjang SET jumlah = ?, harga = ?, subtotal = ? WHERE id = ?");
    $upd->execute([$jumlahBaru, $produk['harga'], $jumlahBaru * $produk['harga'], $existing['id']]);
} else {
    $ins = $pdo->prepare("INSERT INTO detail_keranjang (keranjang_id, produk_id, ukuran, jumlah, harga, subtotal) VALUES (?,?,?,?,?,?)");
    $ins->execute([$keranjangId, $produkId, $ukuran, $jumlahBaru, $produk['harga'], $jumlahBaru * $produk['harga']]);
}

$badge = hitung_badge_keranjang($pdo, $pelangganId);
json_out(['ok' => true, 'badge' => $badge]);
