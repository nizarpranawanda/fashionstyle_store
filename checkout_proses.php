<?php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'message' => 'Metode tidak diizinkan'], 405);
if (!pelanggan_login()) json_out(['ok' => false, 'message' => 'Silakan login terlebih dahulu untuk melakukan transaksi.'], 401);

$pelangganId = (int) $_SESSION['pelanggan_id'];
$in = body_input();

$itemIds  = array_map('intval', $in['item_ids'] ?? []);
$nama     = trim($in['nama'] ?? '');
$hp       = trim($in['hp'] ?? '');
$alamat   = trim($in['alamat'] ?? '');
$kurirKode = trim($in['kurir_kode'] ?? '');
$metode   = trim($in['metode'] ?? '');
$voucherKode = trim($in['voucher_kode'] ?? '');
$catatan  = trim($in['catatan'] ?? '');

if (empty($itemIds)) json_out(['ok' => false, 'message' => 'Tidak ada produk untuk checkout']);
if ($nama === '' || $hp === '' || $alamat === '') json_out(['ok' => false, 'message' => 'Lengkapi alamat pengiriman terlebih dahulu']);
if ($metode === '') json_out(['ok' => false, 'message' => 'Pilih metode pembayaran terlebih dahulu']);

try {
    $pdo->beginTransaction();

    // ambil item keranjang milik pelanggan ini saja (keamanan)
    $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
    $stmt = $pdo->prepare("
        SELECT dk.id, dk.produk_id, dk.ukuran, dk.jumlah, dk.harga,
               p.nama AS nama_produk, p.stok, p.status, p.terjual
        FROM detail_keranjang dk
        JOIN keranjang k ON k.id = dk.keranjang_id
        JOIN produk p ON p.id = dk.produk_id
        WHERE k.pelanggan_id = ? AND dk.id IN ($placeholders)
        FOR UPDATE
    ");
    $stmt->execute(array_merge([$pelangganId], $itemIds));
    $items = $stmt->fetchAll();

    if (empty($items)) {
        $pdo->rollBack();
        json_out(['ok' => false, 'message' => 'Tidak ada produk untuk checkout']);
    }

    // validasi stok
    foreach ($items as $it) {
        if ($it['status'] !== 'aktif' || (int)$it['stok'] < (int)$it['jumlah']) {
            $pdo->rollBack();
            json_out(['ok' => false, 'message' => 'Stok produk tidak mencukupi.']);
        }
    }

    // kurir
    $kurirId = null; $ongkir = 0;
    if ($kurirKode !== '') {
        $stmt = $pdo->prepare("SELECT * FROM kurir WHERE kode = ? AND status='aktif'");
        $stmt->execute([$kurirKode]);
        $kurir = $stmt->fetch();
        if ($kurir) { $kurirId = (int)$kurir['id']; $ongkir = (float)$kurir['biaya']; }
    }

    $subtotal = array_sum(array_map(fn($it) => (float)$it['harga'] * (int)$it['jumlah'], $items));

    // voucher
    $voucherId = null; $diskon = 0;
    if ($voucherKode !== '') {
        $stmt = $pdo->prepare("SELECT * FROM voucher WHERE kode = ? AND status='aktif'");
        $stmt->execute([strtoupper($voucherKode)]);
        $voucher = $stmt->fetch();
        if ($voucher) {
            $voucherId = (int) $voucher['id'];
            if ($voucher['tipe'] === 'persen') {
                $diskon = $subtotal * ((float)$voucher['nilai'] / 100);
                if ($voucher['maksimal_diskon'] !== null) $diskon = min($diskon, (float)$voucher['maksimal_diskon']);
            } elseif ($voucher['tipe'] === 'nominal') {
                $diskon = (float) $voucher['nilai'];
            } elseif ($voucher['tipe'] === 'ongkir_gratis') {
                $diskon = $ongkir;
            }
        }
    }

    $total = max($subtotal + $ongkir - $diskon, 0);
    $invoice = generate_invoice($pdo);

    $ins = $pdo->prepare("
        INSERT INTO transaksi (pelanggan_id, voucher_id, kurir_id, invoice_no, nama_penerima, hp_penerima, alamat_kirim, catatan, subtotal, ongkir, diskon, total, status_bayar, status_kirim)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?, 'belum', 'pending')
    ");
    $ins->execute([$pelangganId, $voucherId, $kurirId, $invoice, $nama, $hp, $alamat, $catatan, $subtotal, $ongkir, $diskon, $total]);
    $transaksiId = (int) $pdo->lastInsertId();

    $insDetail = $pdo->prepare("INSERT INTO detail_transaksi (transaksi_id, produk_id, nama_produk, ukuran, jumlah, harga, subtotal) VALUES (?,?,?,?,?,?,?)");
    $updStok = $pdo->prepare("UPDATE produk SET stok = stok - ?, terjual = terjual + ? WHERE id = ?");
    $delCart = $pdo->prepare("DELETE FROM detail_keranjang WHERE id = ?");

    foreach ($items as $it) {
        $insDetail->execute([$transaksiId, $it['produk_id'], $it['nama_produk'], $it['ukuran'], $it['jumlah'], $it['harga'], $it['harga'] * $it['jumlah']]);
        $updStok->execute([$it['jumlah'], $it['jumlah'], $it['produk_id']]);
        $delCart->execute([$it['id']]);
    }

    // buat kode pembayaran (VA / kode e-wallet / kode COD) supaya bisa ditampilkan & dicetak
    $kodeBayar = match (true) {
        in_array($metode, ['bca', 'mandiri', 'bri'], true) => strtoupper($metode) . '-' . random_int(100000000, 999999999) . random_int(0, 9),
        $metode === 'qris' => 'QRIS-' . $invoice,
        $metode === 'cod' => $invoice,
        default => strtoupper($metode) . '-' . $invoice,
    };
    $batasWaktu = ($metode === 'cod') ? null : date('Y-m-d H:i:s', strtotime('+1 day'));
    $statusBayarAwal = ($metode === 'cod') ? 'menunggu' : 'menunggu';

    $insBayar = $pdo->prepare("INSERT INTO pembayaran (transaksi_id, metode, kode_bayar, jumlah, batas_waktu, status) VALUES (?,?,?,?,?,?)");
    $insBayar->execute([$transaksiId, $metode, $kodeBayar, $total, $batasWaktu, $statusBayarAwal]);

    $pdo->commit();
    json_out(['ok' => true, 'invoice' => $invoice]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_out(['ok' => false, 'message' => 'Gagal memproses pesanan: ' . $e->getMessage()], 500);
}