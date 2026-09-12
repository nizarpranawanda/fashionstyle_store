<?php
require_once __DIR__ . '/config.php';

if (!pelanggan_login()) json_out(['ok' => false, 'message' => 'Silakan login terlebih dahulu.'], 401);

$pelangganId = (int) $_SESSION['pelanggan_id'];
$in = body_input();
$ids = array_map('intval', $in['ids'] ?? []);
if (empty($ids)) json_out(['ok' => false, 'message' => 'Tidak ada produk untuk checkout']);

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("
    SELECT dk.id, dk.ukuran, dk.jumlah, dk.harga, dk.subtotal,
           p.id AS produk_id, p.nama, p.gambar, p.stok, p.status
    FROM detail_keranjang dk
    JOIN keranjang k ON k.id = dk.keranjang_id
    JOIN produk p ON p.id = dk.produk_id
    WHERE k.pelanggan_id = ? AND dk.id IN ($placeholders)
");
$stmt->execute(array_merge([$pelangganId], $ids));
$rows = $stmt->fetchAll();

$items = [];
foreach ($rows as $r) {
    if ($r['status'] !== 'aktif' || (int)$r['stok'] < (int)$r['jumlah']) {
        json_out(['ok' => false, 'message' => 'Stok produk tidak mencukupi.']);
    }
    $items[] = [
        'item_id' => (int) $r['id'],
        'produk_id' => (int) $r['produk_id'],
        'nama' => $r['nama'],
        'ukuran' => $r['ukuran'],
        'gambar' => GAMBAR_PRODUK_DIR . $r['gambar'],
        'harga' => (float) $r['harga'],
        'jumlah' => (int) $r['jumlah'],
        'subtotal' => (float) $r['subtotal'],
    ];
}

// data alamat default dari profil pelanggan
$stmt = $pdo->prepare("SELECT nama, no_hp, alamat FROM pelanggan WHERE id = ?");
$stmt->execute([$pelangganId]);
$profil = $stmt->fetch();

json_out(['ok' => true, 'items' => $items, 'profil' => $profil]);