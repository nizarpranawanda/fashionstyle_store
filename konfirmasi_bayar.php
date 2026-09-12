<?php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'message' => 'Metode tidak diizinkan'], 405);
if (!pelanggan_login()) json_out(['ok' => false, 'message' => 'Silakan login terlebih dahulu.'], 401);

$pelangganId = (int) $_SESSION['pelanggan_id'];
$in = body_input();
$invoice = trim($in['invoice'] ?? '');

$stmt = $pdo->prepare("SELECT id FROM transaksi WHERE invoice_no = ? AND pelanggan_id = ?");
$stmt->execute([$invoice, $pelangganId]);
$trx = $stmt->fetch();
if (!$trx) json_out(['ok' => false, 'message' => 'Transaksi tidak ditemukan.'], 404);

$pdo->prepare("UPDATE transaksi SET status_bayar = 'lunas' WHERE id = ?")->execute([$trx['id']]);
$pdo->prepare("UPDATE pembayaran SET status = 'berhasil' WHERE transaksi_id = ?")->execute([$trx['id']]);

json_out(['ok' => true]);
