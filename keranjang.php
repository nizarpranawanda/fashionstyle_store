<?php
require_once 'config.php';
require_login_pelanggan('keranjang.php');

$pelangganId = (int) $_SESSION['pelanggan_id'];
$keranjangId = get_or_create_keranjang($pdo, $pelangganId);

$stmt = $pdo->prepare("
    SELECT dk.id, dk.produk_id, dk.ukuran, dk.jumlah, dk.harga, dk.subtotal,
           p.nama, p.gambar, p.stok, p.status
    FROM detail_keranjang dk
    JOIN produk p ON p.id = dk.produk_id
    WHERE dk.keranjang_id = ?
    ORDER BY dk.id DESC
");
$stmt->execute([$keranjangId]);
$items = $stmt->fetchAll();

$valid = [];
$invalid = [];
foreach ($items as $it) {
    if ($it['status'] !== 'aktif' || (int)$it['stok'] === 0) {
        $invalid[] = $it;
    } else {
        $it['jumlah'] = min((int)$it['jumlah'], (int)$it['stok']);
        $valid[] = $it;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Keranjang Saya - FashionStyle Store</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{--bg1:rgb(15,23,42);--bg2:rgb(30,41,59);--card:rgb(31,41,55);--btn:rgb(59,130,246);--btnH:rgb(37,99,235);
--accent:rgb(14,165,233);--success:rgb(34,197,94);--danger:rgb(239,68,68);--warn:rgb(234,179,8);
--txt1:rgb(248,250,252);--txt2:rgb(203,213,225);--border:rgba(255,255,255,.08);--radius:12px;}
body{font-family:'Inter',sans-serif;background:var(--bg1);color:var(--txt1);min-height:100vh;padding-bottom:110px;}
a{text-decoration:none;color:inherit;}
.container{max-width:900px;margin:0 auto;padding:0 16px;}
.topbar{position:sticky;top:0;z-index:100;background:rgb(17,24,39);border-bottom:1px solid var(--border);}
.topbar-inner{display:flex;align-items:center;gap:14px;height:64px;max-width:900px;margin:0 auto;padding:0 16px;}
.topbar a.back{font-size:20px;color:var(--txt1);}
.topbar h1{font-size:18px;font-weight:700;flex:1;}
.section-title{padding:20px 0 10px;font-size:15px;font-weight:700;color:var(--txt2);}
.store-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);margin-top:14px;overflow:hidden;}
.store-head{display:flex;align-items:center;gap:10px;padding:14px 16px;border-bottom:1px solid var(--border);}
.store-head input[type=checkbox]{width:18px;height:18px;accent-color:var(--btn);cursor:pointer;}
.store-head .store-badge{background:var(--btn);color:#fff;font-size:10px;font-weight:800;padding:2px 6px;border-radius:4px;}
.store-head .store-name{font-weight:600;font-size:14px;flex:1;}
.cart-item{display:flex;gap:12px;padding:14px 16px;border-bottom:1px solid var(--border);}
.cart-item:last-child{border-bottom:none;}
.cart-item input[type=checkbox]{width:18px;height:18px;accent-color:var(--btn);cursor:pointer;margin-top:6px;}
.cart-item img{width:76px;height:76px;object-fit:cover;border-radius:8px;flex-shrink:0;background:var(--bg2);}
.ci-info{flex:1;min-width:0;}
.ci-name{font-size:14px;font-weight:500;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;}
.ci-ukuran{display:inline-block;margin-top:4px;background:var(--bg2);color:var(--txt2);font-size:11px;padding:2px 8px;border-radius:6px;}
.ci-price{color:var(--btn);font-weight:700;margin-top:6px;font-size:15px;}
.ci-bottom{display:flex;align-items:center;justify-content:space-between;margin-top:10px;}
.qty-ctrl{display:flex;align-items:center;border:1px solid var(--border);border-radius:8px;overflow:hidden;}
.qty-ctrl button{width:28px;height:28px;background:var(--bg2);border:none;color:var(--txt1);cursor:pointer;font-size:14px;}
.qty-ctrl button:hover{background:var(--btn);}
.qty-ctrl span{width:34px;text-align:center;font-size:13px;}
.ci-remove{color:var(--danger);font-size:13px;cursor:pointer;background:none;border:none;font-family:inherit;}
.ci-remove:hover{text-decoration:underline;}
.invalid-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);margin-top:14px;overflow:hidden;}
.invalid-item{display:flex;gap:12px;padding:14px 16px;align-items:center;opacity:.55;}
.invalid-item img{width:64px;height:64px;object-fit:cover;border-radius:8px;filter:grayscale(1);}
.invalid-item .tag{display:inline-block;background:var(--danger);color:#fff;font-size:10px;padding:2px 6px;border-radius:4px;margin-bottom:4px;}
.invalid-item .del{margin-left:auto;color:var(--danger);cursor:pointer;font-size:16px;background:none;border:none;}
.empty-cart{text-align:center;padding:80px 20px;color:var(--txt2);}
.empty-cart i{font-size:56px;color:var(--border);margin-bottom:16px;}
.empty-cart a{display:inline-block;margin-top:18px;background:var(--btn);color:#fff;padding:12px 26px;border-radius:var(--radius);font-weight:600;}
.bottombar{position:fixed;bottom:0;left:0;right:0;background:rgb(17,24,39);border-top:1px solid var(--border);padding:12px 16px;z-index:200;}
.bottombar-inner{max-width:900px;margin:0 auto;display:flex;align-items:center;gap:14px;}
.bb-check{width:19px;height:19px;accent-color:var(--btn);cursor:pointer;}
.bb-label{font-size:13px;color:var(--txt2);cursor:pointer;}
.bb-total{margin-left:auto;text-align:right;}
.bb-total .lbl{font-size:11px;color:var(--txt2);}
.bb-total .val{font-size:18px;font-weight:800;color:var(--btn);}
.bb-checkout{background:var(--btn);color:#fff;border:none;padding:13px 26px;border-radius:var(--radius);font-weight:700;font-size:14px;cursor:pointer;white-space:nowrap;}
.bb-checkout:hover{background:var(--btnH);}
.bb-checkout:disabled{opacity:.4;cursor:not-allowed;}
.notif-container{position:fixed;top:80px;right:16px;z-index:9999;display:flex;flex-direction:column;gap:10px;}
.notif{padding:14px 20px;border-radius:var(--radius);color:#fff;font-size:14px;font-weight:500;box-shadow:0 8px 32px rgba(0,0,0,.3);min-width:260px;}
.notif.success{background:rgba(34,197,94,.9);}
.notif.error{background:rgba(239,68,68,.9);}
.notif.warning{background:rgba(234,179,8,.9);color:#000;}
@media(max-width:480px){ .bb-label{display:none;} }
</style>
</head>
<body>
<div class="notif-container" id="notifContainer"></div>

<div class="topbar">
    <div class="topbar-inner">
        <a href="index.php" class="back"><i class="fas fa-arrow-left"></i></a>
        <h1 id="pageTitle">Keranjang Saya (<?= count($valid) ?>)</h1>
    </div>
</div>

<div class="container">
    <div id="cartContent">
        <?php if (empty($valid) && empty($invalid)): ?>
            <div class="empty-cart"><i class="fas fa-shopping-cart"></i><div>Keranjang kamu masih kosong</div><a href="index.php">Mulai Belanja</a></div>
        <?php else: ?>
            <?php if (!empty($valid)): ?>
            <div class="store-card">
                <div class="store-head">
                    <input type="checkbox" id="storeAll" checked onchange="toggleSelectAll(this.checked)">
                    <span class="store-badge">Star+</span>
                    <span class="store-name"><?= h(NAMA_TOKO) ?> Official Store</span>
                </div>
                <?php foreach ($valid as $item): ?>
                <div class="cart-item" data-id="<?= (int)$item['id'] ?>" data-harga="<?= (float)$item['harga'] ?>" data-jumlah="<?= (int)$item['jumlah'] ?>">
                    <input type="checkbox" class="item-check" data-id="<?= (int)$item['id'] ?>" checked onchange="onSelect()">
                    <img src="<?= h(GAMBAR_PRODUK_DIR . $item['gambar']) ?>" alt="<?= h($item['nama']) ?>">
                    <div class="ci-info">
                        <div class="ci-name"><?= h($item['nama']) ?></div>
                        <span class="ci-ukuran">Ukuran: <?= h($item['ukuran']) ?></span>
                        <div class="ci-price"><?= rupiah($item['harga']) ?></div>
                        <div class="ci-bottom">
                            <div class="qty-ctrl">
                                <button onclick="changeQty(<?= (int)$item['id'] ?>,-1)">-</button>
                                <span class="qty-val" id="qty-<?= (int)$item['id'] ?>"><?= (int)$item['jumlah'] ?></span>
                                <button onclick="changeQty(<?= (int)$item['id'] ?>,1)">+</button>
                            </div>
                            <button class="ci-remove" onclick="removeItem(<?= (int)$item['id'] ?>)"><i class="fas fa-trash"></i> Hapus</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($invalid)): ?>
            <div class="section-title">Produk tidak valid (<?= count($invalid) ?>)</div>
            <div class="invalid-card">
                <?php foreach ($invalid as $item): ?>
                <div class="invalid-item">
                    <img src="<?= h(GAMBAR_PRODUK_DIR . $item['gambar']) ?>" alt="">
                    <div>
                        <div class="tag">Stok Habis</div>
                        <div style="font-size:13px;"><?= h($item['nama']) ?> (<?= h($item['ukuran']) ?>)</div>
                    </div>
                    <button class="del" onclick="removeItem(<?= (int)$item['id'] ?>)"><i class="fas fa-times"></i></button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($valid)): ?>
<div class="bottombar" id="bottombar">
    <div class="bottombar-inner">
        <input type="checkbox" class="bb-check" id="selectAll" checked onchange="toggleSelectAll(this.checked)">
        <label class="bb-label" for="selectAll">Semua</label>
        <div class="bb-total">
            <div class="lbl">Total</div>
            <div class="val" id="bbTotal">Rp0</div>
        </div>
        <button class="bb-checkout" id="bbCheckoutBtn" onclick="goCheckout()">Checkout (0)</button>
    </div>
</div>
<?php endif; ?>

<script>
function notify(msg,type='info'){
    const c=document.getElementById('notifContainer');
    const n=document.createElement('div');n.className='notif '+type;n.textContent=msg;
    c.appendChild(n);setTimeout(()=>n.remove(),2500);
}
function fmt(n){ return 'Rp'+Math.round(n).toLocaleString('id-ID'); }

function updateBottom(){
    let total = 0, count = 0;
    document.querySelectorAll('.item-check:checked').forEach(chk=>{
        const el = document.querySelector(`.cart-item[data-id="${chk.dataset.id}"]`);
        const jumlah = parseInt(document.getElementById('qty-'+chk.dataset.id).textContent);
        const harga = parseFloat(el.dataset.harga);
        total += harga*jumlah; count += jumlah;
    });
    const bbTotal = document.getElementById('bbTotal');
    if(bbTotal) bbTotal.textContent = fmt(total);
    const btn = document.getElementById('bbCheckoutBtn');
    if(btn){ btn.textContent = `Checkout (${count})`; btn.disabled = count===0; }
}

function onSelect(){
    const all = document.querySelectorAll('.item-check');
    const checked = document.querySelectorAll('.item-check:checked');
    const sa = document.getElementById('selectAll'); if(sa) sa.checked = all.length===checked.length;
    const sa2 = document.getElementById('storeAll'); if(sa2) sa2.checked = all.length===checked.length;
    updateBottom();
}

function toggleSelectAll(checked){
    document.querySelectorAll('.item-check').forEach(c=>c.checked=checked);
    const sa = document.getElementById('selectAll'); if(sa) sa.checked = checked;
    const sa2 = document.getElementById('storeAll'); if(sa2) sa2.checked = checked;
    updateBottom();
}

async function changeQty(id, delta){
    const res = await fetch('update_keranjang.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({item_id:id, delta:delta})
    });
    const data = await res.json();
    if(!data.ok){ notify(data.message||'Gagal memperbarui keranjang','error'); return; }
    document.getElementById('qty-'+id).textContent = data.jumlah;
    if(data.message) notify(data.message,'warning');
    updateBottom();
}

async function removeItem(id){
    const res = await fetch('hapus_keranjang.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({item_id:id})
    });
    const data = await res.json();
    if(!data.ok){ notify(data.message||'Gagal menghapus item','error'); return; }
    notify('Produk dihapus dari keranjang','warning');
    setTimeout(()=>window.location.reload(), 500);
}

function goCheckout(){
    const chosenIds = Array.from(document.querySelectorAll('.item-check:checked')).map(c=>c.dataset.id);
    if(chosenIds.length===0){ notify('Pilih produk terlebih dahulu','error'); return; }
    sessionStorage.setItem('fs_checkout_ids', JSON.stringify(chosenIds));
    window.location.href = 'checkout.php';
}

updateBottom();
</script>
</body>
</html>
