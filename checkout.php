<?php
require_once 'config.php';
require_login_pelanggan('keranjang.php');

$kurirRows = $pdo->query("SELECT * FROM kurir WHERE status='aktif' ORDER BY id ASC")->fetchAll();
$kurirJs = array_map(function ($k) {
    return ['id' => $k['kode'], 'kurir_id' => (int)$k['id'], 'courier' => $k['nama'], 'eta' => $k['estimasi'], 'price' => (float)$k['biaya']];
}, $kurirRows);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout - FashionStyle Store</title>
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
.topbar a.back{font-size:20px;color:var(--txt1);cursor:pointer;}
.topbar h1{font-size:18px;font-weight:700;}
.card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);margin-top:14px;overflow:hidden;}
.card-pad{padding:16px;}
.addr-row{display:flex;gap:12px;align-items:flex-start;cursor:pointer;}
.addr-row i.pin{color:var(--btn);margin-top:3px;}
.addr-row .name{font-weight:700;font-size:14px;}
.addr-row .phone{color:var(--txt2);font-size:13px;font-weight:400;}
.addr-row .addr{color:var(--txt2);font-size:13px;margin-top:4px;line-height:1.6;}
.addr-row .chev{margin-left:auto;color:var(--txt2);}
.addr-empty{color:var(--txt2);font-size:14px;}
.addr-form{display:none;margin-top:14px;flex-direction:column;gap:10px;}
.addr-form.show{display:flex;}
.addr-form input, .addr-form textarea{background:var(--bg1);border:1px solid var(--border);border-radius:8px;padding:11px 14px;color:var(--txt1);font-family:inherit;font-size:13px;}
.addr-form textarea{min-height:60px;resize:vertical;}
.addr-form button{align-self:flex-start;background:var(--btn);color:#fff;border:none;padding:9px 18px;border-radius:8px;font-weight:600;font-size:13px;cursor:pointer;}
.store-head{display:flex;align-items:center;gap:8px;padding:14px 16px;border-bottom:1px solid var(--border);font-size:14px;font-weight:600;}
.store-badge{background:var(--btn);color:#fff;font-size:10px;font-weight:800;padding:2px 6px;border-radius:4px;}
.co-item{display:flex;gap:12px;padding:14px 16px;border-bottom:1px solid var(--border);}
.co-item:last-child{border-bottom:none;}
.co-item img{width:64px;height:64px;object-fit:cover;border-radius:8px;background:var(--bg2);flex-shrink:0;}
.co-item .nm{font-size:14px;}
.co-item .uk{font-size:11px;color:var(--txt2);margin-top:2px;}
.co-item .pr{color:var(--btn);font-weight:700;margin-top:4px;}
.co-item .qty{margin-left:auto;color:var(--txt2);font-size:13px;align-self:flex-end;}
.row-link{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;font-size:14px;cursor:pointer;border-bottom:1px solid var(--border);}
.row-link:last-child{border-bottom:none;}
.row-link .r{color:var(--txt2);}
.row-link i.chev{color:var(--txt2);margin-left:6px;}
.ship-opt{display:flex;align-items:center;gap:12px;padding:14px 16px;border:2px solid var(--border);border-radius:10px;margin:12px 16px;cursor:pointer;transition:.2s;}
.ship-opt.active{border-color:var(--btn);background:rgba(59,130,246,.08);}
.ship-opt input{accent-color:var(--btn);width:18px;height:18px;}
.ship-opt .info{flex:1;}
.ship-opt .courier{font-weight:600;font-size:14px;}
.ship-opt .eta{color:var(--success);font-size:12px;margin-top:2px;}
.ship-opt .price{font-weight:700;font-size:14px;}
.pay-group-title{padding:12px 16px 4px;font-size:12px;color:var(--txt2);font-weight:700;text-transform:uppercase;letter-spacing:1px;}
.pay-opt{display:flex;align-items:center;gap:12px;padding:14px 16px;cursor:pointer;border-bottom:1px solid var(--border);}
.pay-opt:last-child{border-bottom:none;}
.pay-opt input{accent-color:var(--btn);width:18px;height:18px;}
.pay-opt i.picon{width:26px;text-align:center;color:var(--accent);font-size:16px;}
.pay-opt .lbl{flex:1;font-size:14px;}
.pay-opt.active{background:rgba(59,130,246,.08);}
.sum-row{display:flex;justify-content:space-between;padding:8px 16px;font-size:13px;color:var(--txt2);}
.sum-row.total{border-top:1px solid var(--border);padding-top:14px;font-size:15px;font-weight:700;color:var(--txt1);}
.sum-row span.val{color:var(--txt1);}
.sum-row.total span.val{color:var(--btn);}
textarea#noteInput{width:100%;background:var(--bg1);border:1px solid var(--border);border-radius:8px;padding:10px 14px;color:var(--txt1);font-family:inherit;font-size:13px;min-height:44px;}
.bottombar{position:fixed;bottom:0;left:0;right:0;background:rgb(17,24,39);border-top:1px solid var(--border);padding:12px 16px;z-index:200;}
.bottombar-inner{max-width:900px;margin:0 auto;display:flex;align-items:center;gap:14px;}
.bb-total{text-align:left;}
.bb-total .lbl{font-size:11px;color:var(--txt2);}
.bb-total .val{font-size:18px;font-weight:800;color:var(--btn);}
.bb-order{margin-left:auto;background:var(--btn);color:#fff;border:none;padding:13px 26px;border-radius:var(--radius);font-weight:700;font-size:14px;cursor:pointer;white-space:nowrap;}
.bb-order:hover{background:var(--btnH);}
.bb-order:disabled{opacity:.4;cursor:not-allowed;}
.notif-container{position:fixed;top:80px;right:16px;z-index:9999;display:flex;flex-direction:column;gap:10px;}
.notif{padding:14px 20px;border-radius:var(--radius);color:#fff;font-size:14px;font-weight:500;box-shadow:0 8px 32px rgba(0,0,0,.3);min-width:260px;}
.notif.success{background:rgba(34,197,94,.9);}
.notif.error{background:rgba(239,68,68,.9);}
.notif.warning{background:rgba(234,179,8,.9);color:#000;}
</style>
</head>
<body>
<div class="notif-container" id="notifContainer"></div>

<div class="topbar">
    <div class="topbar-inner">
        <span class="back" onclick="window.location.href='keranjang.php'"><i class="fas fa-arrow-left"></i></span>
        <h1>Checkout</h1>
    </div>
</div>

<div class="container">

    <div class="card card-pad" id="addrCard">
        <div class="addr-row" id="addrDisplay" onclick="toggleAddrForm()">
            <i class="fas fa-map-marker-alt pin"></i>
            <div style="flex:1;">
                <div id="addrText" class="addr-empty">Memuat alamat...</div>
            </div>
            <i class="fas fa-chevron-right chev"></i>
        </div>
        <div class="addr-form" id="addrForm">
            <input type="text" id="fNama" placeholder="Nama penerima">
            <input type="text" id="fHp" placeholder="No. HP (contoh: 0812xxxxxxx)">
            <textarea id="fAlamat" placeholder="Alamat lengkap penerima"></textarea>
            <button type="button" onclick="saveAddress()">Simpan Alamat</button>
        </div>
    </div>

    <div class="card">
        <div class="store-head"><span class="store-badge">Star+</span> <?= h(NAMA_TOKO) ?> Official Store</div>
        <div id="itemsList"></div>
    </div>

    <div class="card">
        <div class="row-link" onclick="applyVoucher()">
            <span><i class="fas fa-ticket-alt" style="color:var(--danger);margin-right:8px;"></i>Voucher Toko</span>
            <span class="r" id="voucherLabel">Gunakan/masukkan kode <i class="fas fa-chevron-right chev"></i></span>
        </div>
        <div class="card-pad" style="padding-top:10px;">
            <label style="font-size:12px;color:var(--txt2);display:block;margin-bottom:6px;">Pesan untuk Penjual</label>
            <textarea id="noteInput" placeholder="Tinggalkan pesan (opsional)"></textarea>
        </div>
    </div>

    <div class="card">
        <div class="store-head" style="border-bottom:none;">Opsi Pengiriman</div>
        <div id="shipList"></div>
    </div>

    <div class="card">
        <div class="store-head" style="border-bottom:none;">Metode Pembayaran</div>
        <div id="payList"></div>
    </div>

    <div class="card card-pad" style="padding-top:10px;padding-bottom:16px;">
        <div style="font-weight:700;font-size:14px;margin-bottom:6px;">Rincian Pembayaran</div>
        <div class="sum-row"><span>Subtotal Pesanan</span><span class="val" id="sumSubtotal">Rp0</span></div>
        <div class="sum-row"><span>Subtotal Pengiriman</span><span class="val" id="sumOngkir">Rp0</span></div>
        <div class="sum-row" id="sumDiskonRow" style="display:none;"><span>Diskon Voucher</span><span class="val" style="color:var(--success);" id="sumDiskon">-Rp0</span></div>
        <div class="sum-row total"><span>Total Pembayaran</span><span class="val" id="sumTotal">Rp0</span></div>
    </div>
</div>

<div class="bottombar">
    <div class="bottombar-inner">
        <div class="bb-total">
            <div class="lbl">Total Pembayaran</div>
            <div class="val" id="bbTotal">Rp0</div>
        </div>
        <button class="bb-order" id="bbOrderBtn" onclick="buatPesanan()">Buat Pesanan</button>
    </div>
</div>

<script>
function notify(msg,type='info'){
    const c=document.getElementById('notifContainer');
    c.querySelectorAll('.notif').forEach(el=>el.remove()); // jangan numpuk, ganti yang lama
    const n=document.createElement('div');n.className='notif '+type;n.textContent=msg;
    c.appendChild(n);setTimeout(()=>n.remove(),3500);
}
function fmt(n){ return 'Rp'+Math.round(n).toLocaleString('id-ID'); }

const shippingOptions = <?= json_encode($kurirJs) ?>;

const paymentGroups = [
    {group:'Transfer Bank', items:[
        {id:'bca', label:'Transfer Bank BCA', icon:'fa-building-columns'},
        {id:'mandiri', label:'Transfer Bank Mandiri', icon:'fa-building-columns'},
        {id:'bri', label:'Transfer Bank BRI', icon:'fa-building-columns'},
    ]},
    {group:'E-Wallet & QRIS', items:[
        {id:'qris', label:'QRIS (semua e-wallet & m-banking)', icon:'fa-qrcode'},
        {id:'dana', label:'DANA', icon:'fa-wallet'},
        {id:'ovo', label:'OVO', icon:'fa-wallet'},
        {id:'gopay', label:'GoPay', icon:'fa-wallet'},
        {id:'shopeepay', label:'ShopeePay', icon:'fa-wallet'},
    ]},
    {group:'Lainnya', items:[
        {id:'cod', label:'Bayar di Tempat (COD)', icon:'fa-truck-fast'},
    ]}
];

let chosenShip = shippingOptions.length ? shippingOptions[0].id : null;
let chosenPay = null;
let voucherKode = null;
let voucherDiskon = 0;
let voucherLabelText = 'Gunakan/masukkan kode';
let items = [];
let profil = null;

async function loadItems(){
    const ids = JSON.parse(sessionStorage.getItem('fs_checkout_ids')||'[]');
    if(ids.length===0){
        notify('Tidak ada produk untuk checkout','error');
        document.getElementById('bbOrderBtn').disabled = true;
        setTimeout(()=>window.location.href='keranjang.php', 1500);
        return;
    }
    let data;
    try{
        const res = await fetch('checkout_items.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ids})
        });
        const text = await res.text();
        try{
            data = JSON.parse(text);
        }catch(parseErr){
            console.error('Respons checkout_items.php BUKAN JSON. Isi mentahnya:\n' + text);
            notify('Server error saat memuat checkout. Lihat detail di Console (F12).','error');
            document.getElementById('bbOrderBtn').disabled = true;
            setTimeout(()=>window.location.href='keranjang.php', 2500);
            return;
        }
    }catch(networkErr){
        console.error(networkErr);
        notify('Gagal terhubung ke server saat memuat checkout.','error');
        document.getElementById('bbOrderBtn').disabled = true;
        setTimeout(()=>window.location.href='keranjang.php', 2500);
        return;
    }
    if(!data.ok){
        notify(data.message||'Gagal memuat data checkout','error');
        document.getElementById('bbOrderBtn').disabled = true;
        setTimeout(()=>window.location.href='keranjang.php', 1500);
        return;
    }
    if(!data.items || data.items.length===0){
        notify('Item keranjang tidak ditemukan / stok habis.','error');
        document.getElementById('bbOrderBtn').disabled = true;
        setTimeout(()=>window.location.href='keranjang.php', 1500);
        return;
    }
    items = data.items;
    profil = data.profil;
    document.getElementById('itemsList').innerHTML = items.map(it=>`
        <div class="co-item">
            <img src="${it.gambar}" alt="">
            <div>
                <div class="nm">${it.nama}</div>
                <div class="uk">Ukuran: ${it.ukuran}</div>
                <div class="pr">${fmt(it.harga)}</div>
            </div>
            <div class="qty">x${it.jumlah}</div>
        </div>
    `).join('');
    loadAddress();
    calcSummary();
}

function loadAddress(){
    const addr = profil ? {nama:profil.nama, hp:profil.no_hp||'-', alamat:profil.alamat||''} : null;
    if(addr && addr.alamat){
        renderAddress(addr);
    } else {
        document.getElementById('addrText').innerHTML = 'Belum ada alamat pengiriman. Klik untuk menambahkan.';
        toggleAddrForm(true);
    }
}
function renderAddress(addr){
    document.getElementById('addrText').innerHTML = `
        <span class="name">${addr.nama} <span class="phone">(${addr.hp})</span></span>
        <div class="addr">${addr.alamat}</div>`;
    document.getElementById('addrText').classList.remove('addr-empty');
    document.getElementById('addrText').dataset.nama = addr.nama;
    document.getElementById('addrText').dataset.hp = addr.hp;
    document.getElementById('addrText').dataset.alamat = addr.alamat;
}
function toggleAddrForm(forceOpen){
    const f = document.getElementById('addrForm');
    const open = forceOpen === true ? true : !f.classList.contains('show');
    f.classList.toggle('show', open);
    if(open){
        const fN = document.getElementById('fNama');
        const fH = document.getElementById('fHp');
        const fA = document.getElementById('fAlamat');
        const stillEmpty = !fN.value.trim() && !fH.value.trim() && !fA.value.trim();
        if(stillEmpty && profil){
            fN.value = profil.nama||'';
            fH.value = profil.no_hp||'';
            fA.value = profil.alamat||'';
        }
    }
}
function readAddressForm(){
    const nama = document.getElementById('fNama').value.trim();
    const hp = document.getElementById('fHp').value.trim();
    const alamat = document.getElementById('fAlamat').value.trim();
    if(!nama || !hp || !alamat) return null;
    return {nama, hp, alamat};
}
function saveAddress(){
    const addr = readAddressForm();
    if(!addr){ notify('Lengkapi data alamat terlebih dahulu (nama, HP, alamat)','error'); highlightEmptyAddrFields(); return; }
    renderAddress(addr);
    document.getElementById('addrForm').classList.remove('show');
    notify('Alamat pengiriman disimpan','success');
}
function highlightEmptyAddrFields(){
    ['fNama','fHp','fAlamat'].forEach(id=>{
        const el = document.getElementById(id);
        el.style.borderColor = el.value.trim() ? 'var(--border)' : 'var(--danger)';
    });
}
function getCurrentAddress(){
    const d = document.getElementById('addrText');
    if(d.dataset.alamat) return {nama:d.dataset.nama, hp:d.dataset.hp, alamat:d.dataset.alamat};
    return readAddressForm();
}

function renderShipping(){
    document.getElementById('shipList').innerHTML = shippingOptions.map(o=>`
        <label class="ship-opt ${o.id===chosenShip?'active':''}" onclick="selectShip('${o.id}')">
            <input type="radio" name="ship" ${o.id===chosenShip?'checked':''}>
            <div class="info">
                <div class="courier">${o.courier}</div>
                <div class="eta">${o.eta}</div>
            </div>
            <div class="price">${fmt(o.price)}</div>
        </label>
    `).join('');
}
function selectShip(id){ chosenShip = id; renderShipping(); calcSummary(); }

function renderPayment(){
    let html = '';
    paymentGroups.forEach(g=>{
        html += `<div class="pay-group-title">${g.group}</div>`;
        g.items.forEach(o=>{
            html += `<label class="pay-opt ${o.id===chosenPay?'active':''}" onclick="selectPay('${o.id}')">
                <input type="radio" name="pay" ${o.id===chosenPay?'checked':''}>
                <i class="fas ${o.icon} picon"></i>
                <span class="lbl">${o.label}</span>
            </label>`;
        });
    });
    document.getElementById('payList').innerHTML = html;
}
function selectPay(id){ chosenPay = id; renderPayment(); }

function applyVoucher(){
    const code = prompt('Masukkan kode voucher (coba: HEMAT10 atau ONGKIRGRATIS)');
    if(!code) return;
    const c = code.trim().toUpperCase();
    const subtotal = items.reduce((s,i)=>s+i.harga*i.jumlah,0);
    if(c==='HEMAT10'){
        voucherDiskon = Math.min(subtotal*0.1, 50000);
        voucherKode = c;
        voucherLabelText = 'HEMAT10 terpakai';
        notify('Voucher HEMAT10 berhasil digunakan!','success');
    } else if(c==='ONGKIRGRATIS'){
        const ship = shippingOptions.find(s=>s.id===chosenShip);
        voucherDiskon = ship ? ship.price : 0;
        voucherKode = c;
        voucherLabelText = 'ONGKIRGRATIS terpakai';
        notify('Voucher gratis ongkir berhasil digunakan!','success');
    } else {
        notify('Kode voucher tidak valid','error');
        return;
    }
    document.getElementById('voucherLabel').innerHTML = `<span style="color:var(--success);">${voucherLabelText}</span> <i class="fas fa-chevron-right chev"></i>`;
    calcSummary();
}

function calcSummary(){
    const subtotal = items.reduce((s,i)=>s+i.harga*i.jumlah,0);
    const ship = shippingOptions.find(s=>s.id===chosenShip);
    const ongkir = ship ? ship.price : 0;
    const total = Math.max(subtotal + ongkir - voucherDiskon, 0);

    document.getElementById('sumSubtotal').textContent = fmt(subtotal);
    document.getElementById('sumOngkir').textContent = fmt(ongkir);
    if(voucherDiskon>0){
        document.getElementById('sumDiskonRow').style.display='flex';
        document.getElementById('sumDiskon').textContent = '-'+fmt(voucherDiskon);
    } else {
        document.getElementById('sumDiskonRow').style.display='none';
    }
    document.getElementById('sumTotal').textContent = fmt(total);
    document.getElementById('bbTotal').textContent = fmt(total);
    return {subtotal, ongkir, diskon: voucherDiskon, total};
}

async function buatPesanan(){
    const btn = document.getElementById('bbOrderBtn');
    if(btn.disabled) return; // cegah klik dobel

    if(items.length===0){ notify('Tidak ada produk untuk checkout','error'); btn.disabled = true; return; }

    let addr = getCurrentAddress();
    if(!addr){
        notify('Lengkapi alamat pengiriman terlebih dahulu (nama, HP, alamat)','error');
        toggleAddrForm(true);
        highlightEmptyAddrFields();
        window.scrollTo({top:0,behavior:'smooth'});
        document.getElementById('fNama').focus();
        return;
    }
    if(!chosenPay){ notify('Pilih metode pembayaran terlebih dahulu','error'); window.scrollTo({top:document.getElementById('payList').offsetTop-80,behavior:'smooth'}); return; }

    btn.disabled = true; btn.textContent = 'Memproses...';

    const payload = {
        item_ids: items.map(i=>i.item_id),
        nama: addr.nama, hp: addr.hp, alamat: addr.alamat,
        kurir_kode: chosenShip, metode: chosenPay,
        voucher_kode: voucherKode,
        catatan: document.getElementById('noteInput').value.trim()
    };

    let data;
    try{
        const res = await fetch('checkout_proses.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify(payload)
        });
        const text = await res.text();
        try{
            data = JSON.parse(text);
        }catch(parseErr){
            console.error('Respons server bukan JSON:', text);
            notify('Server mengembalikan respons tidak valid. Cek console (F12) untuk detail.','error');
            return;
        }
    }catch(networkErr){
        console.error(networkErr);
        notify('Gagal terhubung ke server. Periksa koneksi atau pastikan checkout_proses.php ada.','error');
        return;
    }finally{
        btn.disabled=false; btn.textContent='Buat Pesanan';
    }

    if(!data.ok){
        notify(data.message || 'Gagal membuat pesanan','error');
        return;
    }

    btn.disabled = true; btn.textContent = 'Berhasil, mengalihkan...';
    sessionStorage.removeItem('fs_checkout_ids');
    notify('Pesanan berhasil dibuat!','success');
    setTimeout(()=>{ window.location.href = 'pembayaran.php?invoice='+encodeURIComponent(data.invoice); }, 700);
}

['fNama','fHp','fAlamat'].forEach(id=>{
    document.getElementById(id).addEventListener('input', function(){ this.style.borderColor = 'var(--border)'; });
});

loadItems();
renderShipping();
renderPayment();
</script>
</body>
</html>