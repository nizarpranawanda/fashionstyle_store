<?php
require_once 'config.php';

// invoice bisa diakses pelanggan pemilik transaksi ATAU admin
$invoice = $_GET['invoice'] ?? '';
if ($invoice === '') { header('Location: index.php'); exit; }

if (admin_login()) {
    $stmt = $pdo->prepare("SELECT t.*, k.nama AS kurir_nama, p.nama AS nama_pelanggan, p.username FROM transaksi t
        LEFT JOIN kurir k ON k.id = t.kurir_id LEFT JOIN pelanggan p ON p.id = t.pelanggan_id
        WHERE t.invoice_no = ?");
    $stmt->execute([$invoice]);
} elseif (pelanggan_login()) {
    $stmt = $pdo->prepare("SELECT t.*, k.nama AS kurir_nama, p.nama AS nama_pelanggan, p.username FROM transaksi t
        LEFT JOIN kurir k ON k.id = t.kurir_id LEFT JOIN pelanggan p ON p.id = t.pelanggan_id
        WHERE t.invoice_no = ? AND t.pelanggan_id = ?");
    $stmt->execute([$invoice, (int)$_SESSION['pelanggan_id']]);
} else {
    header('Location: login.php?redirect=' . urlencode('invoice.php?invoice=' . $invoice));
    exit;
}
$trx = $stmt->fetch();
if (!$trx) { echo '<div style="font-family:sans-serif;text-align:center;padding:80px;">Invoice tidak ditemukan.</div>'; exit; }

$stmt = $pdo->prepare("SELECT * FROM detail_transaksi WHERE transaksi_id = ?");
$stmt->execute([$trx['id']]);
$items = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM pembayaran WHERE transaksi_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$trx['id']]);
$bayar = $stmt->fetch();

$backUrl = admin_login() ? 'admin_dashboard.php?tab=transaksi' : 'pelanggan_dashboard.php?tab=riwayat';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoice <?= h($trx['invoice_no']) ?> - FashionStyle Store</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{--bg1:rgb(15,23,42);--bg2:rgb(30,41,59);--card:rgb(31,41,55);--btn:rgb(59,130,246);--btnH:rgb(37,99,235);
--success:rgb(34,197,94);--danger:rgb(239,68,68);--txt1:rgb(248,250,252);--txt2:rgb(203,213,225);--border:rgba(255,255,255,.08);--radius:12px;}
body{font-family:'Inter',sans-serif;background:var(--bg1);color:var(--txt1);min-height:100vh;padding:24px 16px 60px;}
a{text-decoration:none;}
.wrap{max-width:720px;margin:0 auto;}
.toolbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;}
.toolbar a.back{color:var(--txt2);font-size:14px;}
.toolbar .actions{display:flex;gap:10px;}
.btn{display:inline-flex;align-items:center;gap:8px;padding:10px 18px;border-radius:8px;font-weight:600;font-size:13px;cursor:pointer;border:none;font-family:inherit;}
.btn-primary{background:var(--btn);color:#fff;}
.btn-outline{background:transparent;border:1px solid var(--border);color:var(--txt1);}
.invoice-box{background:#fff;color:#1a1a1a;border-radius:var(--radius);padding:40px;}
.inv-head{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #eee;padding-bottom:20px;margin-bottom:20px;}
.inv-head h1{font-family:'Playfair Display',serif;font-size:22px;font-weight:800;color:var(--btn);}
.inv-head p{font-size:12px;color:#666;margin-top:4px;}
.inv-status{text-align:right;}
.inv-status .badge{display:inline-block;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;}
.badge-lunas{background:#dcfce7;color:#15803d;}
.badge-belum{background:#fef3c7;color:#92400e;}
.inv-meta{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;font-size:13px;}
.inv-meta .lbl{color:#888;font-size:11px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;}
.inv-meta .val{font-weight:600;}
table{width:100%;border-collapse:collapse;font-size:13px;margin-bottom:20px;}
th{text-align:left;background:#f8f9fa;padding:10px;font-size:11px;text-transform:uppercase;color:#666;}
td{padding:10px;border-bottom:1px solid #eee;}
.text-right{text-align:right;}
.summary{margin-left:auto;max-width:280px;font-size:13px;}
.summary .row{display:flex;justify-content:space-between;padding:6px 0;color:#555;}
.summary .row.total{border-top:2px solid #eee;margin-top:6px;padding-top:10px;font-size:16px;font-weight:800;color:#1a1a1a;}
.inv-footer{text-align:center;margin-top:30px;padding-top:20px;border-top:1px solid #eee;font-size:12px;color:#888;}
@media print{
    body{background:#fff;padding:0;}
    .toolbar{display:none;}
    .invoice-box{border-radius:0;padding:20px;}
}
</style>
</head>
<body>
<div class="wrap">
    <div class="toolbar">
        <a href="<?= h($backUrl) ?>" class="back"><i class="fas fa-arrow-left"></i> Kembali</a>
        <div class="actions">
            <button class="btn btn-outline" onclick="window.print()"><i class="fas fa-print"></i> Cetak Invoice</button>
            <button class="btn btn-primary" onclick="window.print()"><i class="fas fa-download"></i> Download PDF</button>
        </div>
    </div>

    <div class="invoice-box">
        <div class="inv-head">
            <div>
                <h1><?= h(NAMA_TOKO) ?></h1>
                <p>Jl. Sudirman No. 88, Cirebon, 12345</p>
                <p>info@fashionstyle.com &middot; +62 851-8820-7258</p>
            </div>
            <div class="inv-status">
                <div style="font-size:20px;font-weight:800;margin-bottom:6px;">INVOICE</div>
                <div style="font-size:13px;color:#666;"><?= h($trx['invoice_no']) ?></div>
                <div class="badge <?= $trx['status_bayar'] === 'lunas' ? 'badge-lunas' : 'badge-belum' ?>" style="margin-top:8px;">
                    <?= $trx['status_bayar'] === 'lunas' ? 'LUNAS' : 'BELUM DIBAYAR' ?>
                </div>
            </div>
        </div>

        <div class="inv-meta">
            <div>
                <div class="lbl">Ditagihkan Kepada</div>
                <div class="val"><?= h($trx['nama_penerima']) ?></div>
                <div style="color:#666;margin-top:2px;">@<?= h($trx['username']) ?> &middot; <?= h($trx['hp_penerima']) ?></div>
                <div style="color:#666;margin-top:2px;"><?= h($trx['alamat_kirim']) ?></div>
            </div>
            <div style="text-align:right;">
                <div class="lbl">Tanggal Transaksi</div>
                <div class="val"><?= date('d F Y, H:i', strtotime($trx['tanggal'])) ?></div>
                <div class="lbl" style="margin-top:10px;">Kurir</div>
                <div class="val"><?= h($trx['kurir_nama'] ?? '-') ?></div>
                <div class="lbl" style="margin-top:10px;">Metode Pembayaran</div>
                <div class="val"><?= h(strtoupper($bayar['metode'] ?? '-')) ?></div>
            </div>
        </div>

        <table>
            <thead>
                <tr><th>Produk</th><th>Ukuran</th><th class="text-right">Harga</th><th class="text-right">Qty</th><th class="text-right">Subtotal</th></tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= h($it['nama_produk']) ?></td>
                    <td><?= h($it['ukuran']) ?></td>
                    <td class="text-right"><?= rupiah($it['harga']) ?></td>
                    <td class="text-right"><?= (int)$it['jumlah'] ?></td>
                    <td class="text-right"><?= rupiah($it['subtotal']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="summary">
            <div class="row"><span>Subtotal</span><span><?= rupiah($trx['subtotal']) ?></span></div>
            <div class="row"><span>Ongkos Kirim</span><span><?= rupiah($trx['ongkir']) ?></span></div>
            <?php if ((float)$trx['diskon'] > 0): ?>
            <div class="row"><span>Diskon Voucher</span><span>-<?= rupiah($trx['diskon']) ?></span></div>
            <?php endif; ?>
            <div class="row total"><span>Total</span><span><?= rupiah($trx['total']) ?></span></div>
        </div>

        <?php if (!empty($trx['catatan'])): ?>
        <div style="margin-top:20px;font-size:13px;color:#555;"><strong>Catatan:</strong> <?= h($trx['catatan']) ?></div>
        <?php endif; ?>

        <div class="inv-footer">Terima kasih telah berbelanja di <?= h(NAMA_TOKO) ?>!</div>
    </div>
</div>
</body>
</html>
