<?php
require_once 'config.php';
require_login_pelanggan('index.php');

$pelangganId = (int) $_SESSION['pelanggan_id'];
$invoice = $_GET['invoice'] ?? '';

$stmt = $pdo->prepare("SELECT t.*, k.nama AS kurir_nama FROM transaksi t LEFT JOIN kurir k ON k.id = t.kurir_id WHERE t.invoice_no = ? AND t.pelanggan_id = ?");
$stmt->execute([$invoice, $pelangganId]);
$trx = $stmt->fetch();

if (!$trx) {
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Pesanan tidak ditemukan</title>
    <style>body{font-family:sans-serif;background:rgb(15,23,42);color:#fff;text-align:center;padding:80px 20px;}
    a{display:inline-block;margin-top:18px;background:rgb(59,130,246);color:#fff;padding:12px 26px;border-radius:12px;font-weight:600;text-decoration:none;}</style></head>
    <body><i class="fas fa-receipt" style="font-size:56px;"></i><p style="margin-top:16px;">Pesanan tidak ditemukan.</p><a href="index.php">Kembali ke Beranda</a></body></html>';
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM pembayaran WHERE transaksi_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$trx['id']]);
$bayar = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM detail_transaksi WHERE transaksi_id = ?");
$stmt->execute([$trx['id']]);
$detailItems = $stmt->fetchAll();

$isLunas = $trx['status_bayar'] === 'lunas';
$payLabelMap = [
    'bca' => 'Transfer Bank BCA', 'mandiri' => 'Transfer Bank Mandiri', 'bri' => 'Transfer Bank BRI',
    'qris' => 'QRIS (semua e-wallet & m-banking)', 'dana' => 'DANA', 'ovo' => 'OVO', 'gopay' => 'GoPay',
    'shopeepay' => 'ShopeePay', 'cod' => 'Bayar di Tempat (COD)',
];
$metodeLabel = $payLabelMap[$bayar['metode']] ?? strtoupper($bayar['metode']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pembayaran - FashionStyle Store</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Inter:wght@300;400;500;600;700&family=Roboto+Mono:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{--bg1:rgb(15,23,42);--bg2:rgb(30,41,59);--card:rgb(31,41,55);--btn:rgb(59,130,246);--btnH:rgb(37,99,235);
--accent:rgb(14,165,233);--success:rgb(34,197,94);--danger:rgb(239,68,68);--warn:rgb(234,179,8);
--txt1:rgb(248,250,252);--txt2:rgb(203,213,225);--border:rgba(255,255,255,.08);--radius:12px;}
body{font-family:'Inter',sans-serif;background:var(--bg1);color:var(--txt1);min-height:100vh;padding-bottom:40px;}
a{text-decoration:none;color:inherit;}
.container{max-width:640px;margin:0 auto;padding:0 16px;}
.topbar{position:sticky;top:0;z-index:100;background:rgb(17,24,39);border-bottom:1px solid var(--border);}
.topbar-inner{display:flex;align-items:center;gap:14px;height:64px;max-width:640px;margin:0 auto;padding:0 16px;}
.topbar a.back{font-size:20px;color:var(--txt1);}
.topbar h1{font-size:18px;font-weight:700;}
.status-hero{text-align:center;padding:36px 20px 24px;}
.status-hero .icon{width:72px;height:72px;border-radius:50%;background:rgba(234,179,8,.15);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:30px;color:var(--warn);}
.status-hero.paid .icon{background:rgba(34,197,94,.15);color:var(--success);}
.status-hero h2{font-size:20px;font-weight:800;}
.status-hero p{color:var(--txt2);font-size:13px;margin-top:6px;}
.countdown{display:inline-block;margin-top:10px;background:rgba(239,68,68,.12);color:var(--danger);padding:6px 14px;border-radius:20px;font-size:13px;font-weight:700;}
.card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);margin-top:16px;overflow:hidden;}
.card-pad{padding:20px;}
.card-title{font-size:13px;color:var(--txt2);font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:14px;}
.code-box{background:var(--bg1);border:1.5px dashed var(--btn);border-radius:10px;padding:18px;text-align:center;}
.code-box .lbl{font-size:12px;color:var(--txt2);margin-bottom:8px;}
.code-value{font-family:'Roboto Mono',monospace;font-size:22px;font-weight:700;letter-spacing:2px;color:var(--btn);word-break:break-all;}
.copy-btn{margin-top:12px;background:var(--btn);color:#fff;border:none;padding:9px 20px;border-radius:8px;font-weight:600;font-size:13px;cursor:pointer;}
.copy-btn:hover{background:var(--btnH);}
.qr-wrap{text-align:center;}
.qr-wrap img{width:200px;height:200px;border-radius:10px;background:#fff;padding:8px;}
.info-row{display:flex;justify-content:space-between;padding:9px 0;font-size:13px;border-bottom:1px dashed var(--border);}
.info-row:last-child{border-bottom:none;}
.info-row .k{color:var(--txt2);}
.info-row .v{font-weight:600;text-align:right;}
.steps{margin-top:6px;padding-left:18px;color:var(--txt2);font-size:13px;line-height:2;}
.total-box{text-align:center;padding:18px;background:rgba(59,130,246,.08);border-radius:10px;}
.total-box .lbl{font-size:12px;color:var(--txt2);}
.total-box .val{font-size:26px;font-weight:800;color:var(--btn);margin-top:4px;}
.actions{display:flex;gap:12px;margin-top:20px;}
.btn{flex:1;display:flex;align-items:center;justify-content:center;gap:8px;padding:13px;border-radius:var(--radius);font-weight:700;font-size:14px;cursor:pointer;border:none;font-family:inherit;}
.btn-outline{background:transparent;border:1px solid var(--border);color:var(--txt1);}
.btn-outline:hover{border-color:var(--btn);color:var(--btn);}
.btn-primary{background:var(--btn);color:#fff;}
.btn-primary:hover{background:var(--btnH);}
.btn-success{background:var(--success);color:#fff;}
.notif-container{position:fixed;top:80px;right:16px;z-index:9999;display:flex;flex-direction:column;gap:10px;}
.notif{padding:14px 20px;border-radius:var(--radius);color:#fff;font-size:14px;font-weight:500;box-shadow:0 8px 32px rgba(0,0,0,.3);min-width:260px;}
.notif.success{background:rgba(34,197,94,.9);}
.notif.error{background:rgba(239,68,68,.9);}
</style>
</head>
<body>
<div class="notif-container" id="notifContainer"></div>

<div class="topbar">
    <div class="topbar-inner">
        <a href="index.php" class="back"><i class="fas fa-arrow-left"></i></a>
        <h1>Pembayaran</h1>
    </div>
</div>

<div class="container">
    <div class="status-hero <?= $isLunas ? 'paid' : '' ?>" id="statusHero">
        <div class="icon" id="statusIcon"><i class="fas <?= $isLunas ? 'fa-check-circle' : 'fa-clock' ?>"></i></div>
        <h2 id="statusTitle"><?= $isLunas ? 'Pembayaran Berhasil' : 'Menunggu Pembayaran' ?></h2>
        <p id="statusDesc"><?= $isLunas ? 'Pesananmu sedang diproses dan dikirim' : 'Selesaikan pembayaran sebelum waktu habis' ?></p>
        <?php if (!$isLunas && $bayar['batas_waktu']): ?>
        <div class="countdown" id="countdown" data-deadline="<?= h($bayar['batas_waktu']) ?>">23:59:59</div>
        <?php endif; ?>
    </div>

    <div class="card card-pad">
        <div class="total-box">
            <div class="lbl">Total Pembayaran</div>
            <div class="val"><?= rupiah($trx['total']) ?></div>
        </div>
    </div>

    <div class="card card-pad" id="paymentDetailCard">
        <?php if ($bayar['metode'] === 'qris'): ?>
            <div class="card-title">Scan QRIS untuk Membayar</div>
            <div class="qr-wrap">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode('FASHIONSTYLE|' . $trx['invoice_no'] . '|' . $trx['total']) ?>" alt="QRIS Code">
                <div style="font-size:12px;color:var(--txt2);margin-top:10px;">Berlaku untuk semua e-wallet & m-banking</div>
            </div>
        <?php elseif (in_array($bayar['metode'], ['bca', 'mandiri', 'bri'], true)): ?>
            <div class="card-title">Kode Pembayaran / Virtual Account <?= strtoupper($bayar['metode']) ?></div>
            <div class="code-box">
                <div class="lbl">Nomor Virtual Account</div>
                <div class="code-value" id="vaCode"><?= h($bayar['kode_bayar']) ?></div>
                <button class="copy-btn" onclick="copyCode('<?= h($bayar['kode_bayar']) ?>')"><i class="fas fa-copy"></i> Salin Kode</button>
            </div>
            <ol class="steps">
                <li>Buka aplikasi m-banking / ATM <?= strtoupper($bayar['metode']) ?></li>
                <li>Pilih menu Transfer &rarr; Virtual Account</li>
                <li>Masukkan nomor VA di atas</li>
                <li>Periksa detail & konfirmasi pembayaran sejumlah <?= rupiah($trx['total']) ?></li>
                <li>Simpan bukti pembayaran</li>
            </ol>
        <?php elseif (in_array($bayar['metode'], ['dana', 'ovo', 'gopay', 'shopeepay'], true)): ?>
            <div class="card-title">Kode Pembayaran <?= h($metodeLabel) ?></div>
            <div class="code-box">
                <div class="lbl">Kode Pembayaran</div>
                <div class="code-value" id="vaCode"><?= h($bayar['kode_bayar']) ?></div>
                <button class="copy-btn" onclick="copyCode('<?= h($bayar['kode_bayar']) ?>')"><i class="fas fa-copy"></i> Salin Kode</button>
            </div>
            <ol class="steps">
                <li>Buka aplikasi <?= h($metodeLabel) ?></li>
                <li>Pilih menu Bayar / Bayar Tagihan</li>
                <li>Masukkan kode pembayaran di atas</li>
                <li>Selesaikan pembayaran sejumlah <?= rupiah($trx['total']) ?></li>
            </ol>
        <?php elseif ($bayar['metode'] === 'cod'): ?>
            <div class="card-title">Kode Pesanan (COD)</div>
            <div class="code-box">
                <div class="lbl">Tunjukkan kode ini ke kurir saat barang tiba</div>
                <div class="code-value" id="vaCode"><?= h($trx['invoice_no']) ?></div>
                <button class="copy-btn" onclick="copyCode('<?= h($trx['invoice_no']) ?>')"><i class="fas fa-copy"></i> Salin Kode</button>
            </div>
            <p style="font-size:12px;color:var(--txt2);margin-top:10px;">Siapkan uang tunai sejumlah <strong><?= rupiah($trx['total']) ?></strong> saat kurir tiba.</p>
        <?php else: ?>
            <div class="code-box"><div class="code-value"><?= h($trx['invoice_no']) ?></div></div>
        <?php endif; ?>
    </div>

    <div class="card card-pad">
        <div class="card-title">Detail Pesanan</div>
        <div class="info-row"><span class="k">No. Invoice</span><span class="v"><?= h($trx['invoice_no']) ?></span></div>
        <div class="info-row"><span class="k">Tanggal</span><span class="v"><?= date('d/m/Y H:i', strtotime($trx['tanggal'])) ?></span></div>
        <div class="info-row"><span class="k">Penerima</span><span class="v"><?= h($trx['nama_penerima']) ?> (<?= h($trx['hp_penerima']) ?>)</span></div>
        <div class="info-row"><span class="k">Alamat</span><span class="v" style="max-width:60%;"><?= h($trx['alamat_kirim']) ?></span></div>
        <div class="info-row"><span class="k">Kurir</span><span class="v"><?= h($trx['kurir_nama'] ?? '-') ?> - <?= rupiah($trx['ongkir']) ?></span></div>
        <div class="info-row"><span class="k">Metode Bayar</span><span class="v"><?= h($metodeLabel) ?></span></div>
        <div class="info-row"><span class="k">Subtotal Produk</span><span class="v"><?= rupiah($trx['subtotal']) ?></span></div>
        <div class="info-row"><span class="k">Ongkos Kirim</span><span class="v"><?= rupiah($trx['ongkir']) ?></span></div>
        <?php if ((float)$trx['diskon'] > 0): ?>
        <div class="info-row"><span class="k">Diskon Voucher</span><span class="v">-<?= rupiah($trx['diskon']) ?></span></div>
        <?php endif; ?>
    </div>

    <div class="card card-pad">
        <div class="card-title">Rincian Produk</div>
        <div id="itemsList">
            <?php foreach ($detailItems as $it): ?>
            <div class="info-row"><span class="k"><?= h($it['nama_produk']) ?> (<?= h($it['ukuran']) ?>) x<?= (int)$it['jumlah'] ?></span><span class="v"><?= rupiah($it['subtotal']) ?></span></div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="actions">
        <a class="btn btn-outline" href="invoice.php?invoice=<?= urlencode($trx['invoice_no']) ?>"><i class="fas fa-file-invoice"></i> Lihat Invoice</a>
        <?php if (!$isLunas): ?>
        <button class="btn btn-success" id="paidBtn" onclick="markPaid()"><i class="fas fa-check"></i> Saya Sudah Bayar</button>
        <?php else: ?>
        <button class="btn btn-success" disabled style="opacity:.5;"><i class="fas fa-check"></i> Sudah Dibayar</button>
        <?php endif; ?>
    </div>
    <div class="actions">
        <a class="btn btn-primary" style="flex:1;" href="index.php"><i class="fas fa-home"></i> Kembali ke Beranda</a>
    </div>
</div>

<script>
function notify(msg,type='info'){
    const c=document.getElementById('notifContainer');
    const n=document.createElement('div');n.className='notif '+type;n.textContent=msg;
    c.appendChild(n);setTimeout(()=>n.remove(),2500);
}
function copyCode(code){
    navigator.clipboard.writeText(code).then(()=>notify('Kode berhasil disalin','success'));
}

async function markPaid(){
    const btn = document.getElementById('paidBtn');
    btn.disabled = true; btn.style.opacity=.6;
    const res = await fetch('konfirmasi_bayar.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({invoice: <?= json_encode($trx['invoice_no']) ?>})
    });
    const data = await res.json();
    if(!data.ok){ notify(data.message||'Gagal konfirmasi pembayaran','error'); btn.disabled=false; btn.style.opacity=1; return; }
    document.getElementById('statusHero').classList.add('paid');
    document.getElementById('statusIcon').innerHTML = '<i class="fas fa-check-circle"></i>';
    document.getElementById('statusTitle').textContent = 'Pembayaran Berhasil';
    document.getElementById('statusDesc').textContent = 'Pesananmu akan segera diproses dan dikirim';
    const cd = document.getElementById('countdown'); if(cd) cd.style.display='none';
    notify('Pembayaran dikonfirmasi. Terima kasih!','success');
}

function startCountdown(){
    const cd = document.getElementById('countdown');
    if(!cd) return;
    const end = new Date(cd.dataset.deadline.replace(' ','T')).getTime();
    function tick(){
        const diff = end - Date.now();
        if(diff<=0){ cd.textContent = 'Waktu habis'; return; }
        const h = Math.floor(diff/3600000);
        const m = Math.floor((diff%3600000)/60000);
        const s = Math.floor((diff%60000)/1000);
        cd.textContent = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
        setTimeout(tick,1000);
    }
    tick();
}
startCountdown();
</script>
</body>
</html>
