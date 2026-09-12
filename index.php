<?php
require_once 'config.php';

$pelangganId = $_SESSION['pelanggan_id'] ?? null;
$cartBadge = hitung_badge_keranjang($pdo, $pelangganId);

$kategoriRows = $pdo->query("SELECT * FROM kategori ORDER BY id ASC")->fetchAll();
$produkRows = $pdo->query("SELECT * FROM produk WHERE status='aktif' ORDER BY id DESC")->fetchAll();

// siapkan array untuk JS (pengganti localStorage fs_products / kategoriData)
$produkJs = array_map(function ($p) {
    return [
        'id' => (int)$p['id'],
        'kategori_id' => (int)$p['kategori_id'],
        'nama' => $p['nama'],
        'harga' => (float)$p['harga'],
        'harga_asli' => (float)$p['harga_asli'],
        'diskon' => (int)$p['diskon'],
        'deskripsi' => $p['deskripsi'],
        'gambar' => GAMBAR_PRODUK_DIR . $p['gambar'],
        'stok' => (int)$p['stok'],
        'ukuran' => array_values(array_filter(array_map('trim', explode(',', $p['ukuran_tersedia'] ?? 'M,L,XL,XXL')))),
        'rating' => (float)$p['rating'],
        'terjual' => (int)$p['terjual'],
    ];
}, $produkRows);

$kategoriJs = array_map(function ($k) {
    return [
        'id' => (int)$k['id'],
        'nama' => $k['nama'],
        'gambar' => GAMBAR_KATEGORI_DIR . $k['gambar'],
        'deskripsi' => $k['deskripsi'],
    ];
}, $kategoriRows);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FashionStyle Store - Fashion Berkualitas, Harga Bersahabat</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ===== RESET & VARIABEL ===== */
        *{margin:0;padding:0;box-sizing:border-box;}
        :root{
            --bg1:rgb(15,23,42);--bg2:rgb(30,41,59);--nav:rgb(17,24,39);
            --card:rgb(31,41,55);--btn:rgb(59,130,246);--btnH:rgb(37,99,235);
            --accent:rgb(14,165,233);--success:rgb(34,197,94);--warn:rgb(234,179,8);
            --danger:rgb(239,68,68);--txt1:rgb(248,250,252);--txt2:rgb(203,213,225);
            --border:rgba(255,255,255,.08);--shadow:0 8px 32px rgba(0,0,0,.3);
            --glass:rgba(255,255,255,.05);--radius:12px;
        }
        html{scroll-behavior:smooth;}
        body{font-family:'Inter',sans-serif;background:var(--bg1);color:var(--txt1);overflow-x:hidden;line-height:1.6;}
        a{text-decoration:none;color:inherit;}
        ul{list-style:none;}
        img{max-width:100%;display:block;}
        .container{max-width:1280px;margin:0 auto;padding:0 20px;}

        #loader{position:fixed;inset:0;background:var(--bg1);z-index:10000;display:flex;align-items:center;justify-content:center;flex-direction:column;transition:opacity .5s,visibility .5s;}
        #loader.hide{opacity:0;visibility:hidden;}
        .loader-spinner{width:50px;height:50px;border:4px solid var(--border);border-top-color:var(--btn);border-radius:50%;animation:spin 1s linear infinite;}
        .loader-text{margin-top:16px;color:var(--txt2);font-size:14px;letter-spacing:2px;}
        @keyframes spin{to{transform:rotate(360deg);}}

        .notif-container{position:fixed;top:80px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;}
        .notif{padding:14px 20px;border-radius:var(--radius);color:#fff;font-size:14px;font-weight:500;box-shadow:var(--shadow);animation:slideIn .3s ease;min-width:280px;display:flex;align-items:center;gap:10px;backdrop-filter:blur(10px);}
        .notif.success{background:rgba(34,197,94,.9);}
        .notif.error{background:rgba(239,68,68,.9);}
        .notif.warning{background:rgba(234,179,8,.9);color:#000;}
        .notif.info{background:rgba(59,130,246,.9);}
        @keyframes slideIn{from{transform:translateX(100%);opacity:0;}to{transform:translateX(0);opacity:1;}}
        @keyframes slideOut{from{transform:translateX(0);opacity:1;}to{transform:translateX(100%);opacity:0;}}

        .navbar{position:fixed;top:0;left:0;right:0;z-index:1000;background:var(--nav);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);transition:all .3s;}
        .navbar.scrolled{box-shadow:0 4px 30px rgba(0,0,0,.4);}
        .nav-inner{display:flex;align-items:center;justify-content:space-between;height:70px;}
        .nav-logo{font-family:'Playfair Display',serif;font-size:24px;font-weight:800;background:linear-gradient(135deg,var(--btn),var(--accent));-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
        .nav-links{display:flex;align-items:center;gap:28px;}
        .nav-links a{font-size:14px;font-weight:500;color:var(--txt2);transition:color .3s;position:relative;}
        .nav-links a:hover,.nav-links a.active{color:var(--btn);}
        .nav-links a::after{content:'';position:absolute;bottom:-4px;left:0;width:0;height:2px;background:var(--btn);transition:width .3s;}
        .nav-links a:hover::after,.nav-links a.active::after{width:100%;}
        .nav-actions{display:flex;align-items:center;gap:12px;}
        .nav-btn{background:var(--glass);border:1px solid var(--border);color:var(--txt1);padding:8px 12px;border-radius:8px;cursor:pointer;font-size:14px;transition:all .3s;position:relative;}
        .nav-btn:hover{background:var(--btn);border-color:var(--btn);}
        .cart-badge{position:absolute;top:-6px;right:-6px;background:var(--danger);color:#fff;font-size:10px;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;}
        .hamburger{display:none;flex-direction:column;gap:5px;cursor:pointer;padding:5px;}
        .hamburger span{width:24px;height:2px;background:var(--txt1);transition:all .3s;}
        .mobile-menu{display:none;position:fixed;top:70px;left:0;right:0;bottom:0;background:var(--nav);padding:20px;z-index:999;overflow-y:auto;}
        .mobile-menu.show{display:block;animation:fadeIn .3s;}
        .mobile-menu a{display:block;padding:14px 0;border-bottom:1px solid var(--border);color:var(--txt2);font-size:16px;}
        .mobile-menu a:hover{color:var(--btn);}
        @keyframes fadeIn{from{opacity:0;}to{opacity:1;}}

        .hero{position:relative;height:90vh;min-height:600px;overflow:hidden;}
        .hero-slide{position:absolute;inset:0;opacity:0;transition:opacity 1s ease;}
        .hero-slide.active{opacity:1;}
        .hero-slide .slide-bg{position:absolute;inset:0;background-size:cover;background-position:center;filter:brightness(.3);}
        .hero-content{position:relative;z-index:2;height:100%;display:flex;align-items:center;}
        .hero-text{max-width:650px;animation:heroFade 1s ease;}
        .hero-badge{display:inline-block;background:rgba(59,130,246,.2);border:1px solid rgba(59,130,246,.4);color:var(--btn);padding:6px 16px;border-radius:20px;font-size:13px;font-weight:600;margin-bottom:20px;}
        .hero-title{font-family:'Playfair Display',serif;font-size:clamp(36px,6vw,72px);font-weight:900;line-height:1.1;margin-bottom:16px;}
        .hero-title span{background:linear-gradient(135deg,var(--btn),var(--accent));-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
        .hero-desc{font-size:18px;color:var(--txt2);margin-bottom:32px;max-width:500px;}
        .hero-btns{display:flex;gap:14px;flex-wrap:wrap;}
        @keyframes heroFade{from{opacity:0;transform:translateY(30px);}to{opacity:1;transform:translateY(0);}}
        .hero-dots{position:absolute;bottom:30px;left:50%;transform:translateX(-50%);display:flex;gap:10px;z-index:3;}
        .hero-dot{width:12px;height:12px;border-radius:50%;background:rgba(255,255,255,.3);cursor:pointer;transition:all .3s;}
        .hero-dot.active{background:var(--btn);transform:scale(1.3);}

        .btn{display:inline-flex;align-items:center;gap:8px;padding:14px 28px;border:none;border-radius:var(--radius);font-size:15px;font-weight:600;cursor:pointer;transition:all .3s;font-family:inherit;}
        .btn-primary{background:var(--btn);color:#fff;}
        .btn-primary:hover{background:var(--btnH);transform:translateY(-2px);box-shadow:0 8px 25px rgba(59,130,246,.3);}
        .btn-outline{background:transparent;color:var(--txt1);border:1px solid var(--border);}
        .btn-outline:hover{border-color:var(--btn);color:var(--btn);transform:translateY(-2px);}
        .btn-sm{padding:10px 18px;font-size:13px;}
        .btn-danger{background:var(--danger);color:#fff;}
        .btn-danger:hover{background:#dc2626;}
        .btn-success{background:var(--success);color:#fff;}
        .btn-success:hover{background:#16a34a;}

        .banners{padding:60px 0;}
        .banner-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;}
        .banner-card{position:relative;border-radius:var(--radius);overflow:hidden;height:200px;cursor:pointer;transition:transform .3s;}
        .banner-card:hover{transform:translateY(-5px);}
        .banner-card img{width:100%;height:100%;object-fit:cover;transition:transform .5s;}
        .banner-card:hover img{transform:scale(1.1);}
        .banner-overlay{position:absolute;inset:0;background:linear-gradient(135deg,rgba(15,23,42,.8),rgba(15,23,42,.3));display:flex;flex-direction:column;justify-content:flex-end;padding:24px;}
        .banner-overlay h3{font-family:'Playfair Display',serif;font-size:22px;font-weight:700;}
        .banner-overlay p{color:var(--txt2);font-size:13px;margin-top:4px;}
        .banner-overlay .discount{position:absolute;top:16px;right:16px;background:var(--danger);color:#fff;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;}

        .section{padding:80px 0;}
        .section-header{text-align:center;margin-bottom:50px;}
        .section-header h2{font-family:'Playfair Display',serif;font-size:clamp(28px,4vw,42px);font-weight:800;margin-bottom:12px;}
        .section-header p{color:var(--txt2);font-size:16px;max-width:500px;margin:0 auto;}
        .section-header .line{width:60px;height:3px;background:linear-gradient(90deg,var(--btn),var(--accent));margin:16px auto 0;border-radius:2px;}

        .kategori-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;}
        .kategori-card{background:var(--card);border-radius:var(--radius);overflow:hidden;cursor:pointer;transition:all .3s;border:1px solid var(--border);}
        .kategori-card:hover{transform:translateY(-8px);box-shadow:0 12px 40px rgba(0,0,0,.3);border-color:rgba(59,130,246,.3);}
        .kategori-card img{width:100%;height:180px;object-fit:cover;transition:transform .5s;}
        .kategori-card:hover img{transform:scale(1.08);}
        .kategori-card .kat-info{padding:16px;text-align:center;}
        .kategori-card .kat-info h3{font-size:16px;font-weight:600;}
        .kategori-card .kat-info p{color:var(--txt2);font-size:12px;margin-top:4px;}

        .toolbar{display:flex;flex-wrap:wrap;gap:14px;align-items:center;justify-content:space-between;margin-bottom:30px;}
        .search-box{position:relative;flex:1;min-width:250px;max-width:400px;}
        .search-box input{width:100%;padding:12px 16px 12px 44px;background:var(--card);border:1px solid var(--border);border-radius:var(--radius);color:var(--txt1);font-size:14px;font-family:inherit;transition:border-color .3s;}
        .search-box input:focus{outline:none;border-color:var(--btn);}
        .search-box i{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:var(--txt2);}
        .search-results{position:absolute;top:100%;left:0;right:0;background:var(--card);border:1px solid var(--border);border-radius:0 0 var(--radius) var(--radius);max-height:300px;overflow-y:auto;display:none;z-index:10;}
        .search-results.show{display:block;}
        .search-item{padding:12px 16px;display:flex;align-items:center;gap:12px;cursor:pointer;transition:background .2s;}
        .search-item:hover{background:rgba(59,130,246,.1);}
        .search-item img{width:40px;height:40px;border-radius:8px;object-fit:cover;}
        .filter-group{display:flex;gap:10px;flex-wrap:wrap;}
        .filter-select{padding:10px 14px;background:var(--card);border:1px solid var(--border);border-radius:8px;color:var(--txt1);font-size:13px;font-family:inherit;cursor:pointer;}
        .filter-select:focus{outline:none;border-color:var(--btn);}
        .filter-select option{background:var(--card);}

        .produk-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:24px;}
        .produk-card{background:var(--card);border-radius:var(--radius);overflow:hidden;border:1px solid var(--border);transition:all .3s;position:relative;}
        .produk-card:hover{transform:translateY(-6px);box-shadow:0 16px 48px rgba(0,0,0,.3);border-color:rgba(59,130,246,.2);}
        .produk-img{position:relative;overflow:hidden;height:300px;}
        .produk-img img{width:100%;height:100%;object-fit:cover;transition:transform .5s;cursor:zoom-in;}
        .produk-card:hover .produk-img img{transform:scale(1.08);}
        .produk-badge{position:absolute;top:12px;left:12px;display:flex;flex-direction:column;gap:6px;}
        .badge{padding:4px 10px;border-radius:6px;font-size:11px;font-weight:700;text-transform:uppercase;}
        .badge-diskon{background:var(--danger);color:#fff;}
        .badge-baru{background:var(--success);color:#fff;}
        .badge-terlaris{background:var(--warn);color:#000;}
        .produk-actions{position:absolute;top:12px;right:12px;display:flex;flex-direction:column;gap:8px;opacity:0;transform:translateX(10px);transition:all .3s;}
        .produk-card:hover .produk-actions{opacity:1;transform:translateX(0);}
        .aksi-btn{width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.9);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;color:var(--bg1);transition:all .3s;}
        .aksi-btn:hover{background:var(--btn);color:#fff;transform:scale(1.1);}
        .produk-info{padding:16px;}
        .produk-kat{font-size:11px;color:var(--accent);font-weight:600;text-transform:uppercase;letter-spacing:1px;}
        .produk-nama{font-size:15px;font-weight:600;margin-top:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .produk-rating{display:flex;align-items:center;gap:4px;margin-top:6px;font-size:12px;color:var(--warn);}
        .produk-harga{display:flex;align-items:center;gap:10px;margin-top:8px;}
        .harga-aktu{font-size:18px;font-weight:700;color:var(--btn);}
        .harga-asli{font-size:13px;color:var(--txt2);text-decoration:line-through;}
        .produk-stok{font-size:11px;margin-top:6px;color:var(--success);}
        .produk-stok.habis{color:var(--danger);}
        .produk-ukuran{margin-top:8px;}
        .produk-ukuran select{width:100%;padding:6px 8px;background:var(--bg1);border:1px solid var(--border);border-radius:8px;color:var(--txt1);font-size:12px;font-family:inherit;}
        .produk-bottom{display:flex;gap:8px;margin-top:10px;}
        .produk-bottom .btn{flex:1;justify-content:center;padding:10px;font-size:13px;}

        .pagination{display:flex;justify-content:center;align-items:center;gap:8px;margin-top:40px;}
        .page-btn{width:40px;height:40px;border-radius:8px;background:var(--card);border:1px solid var(--border);color:var(--txt2);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;transition:all .3s;}
        .page-btn:hover,.page-btn.active{background:var(--btn);border-color:var(--btn);color:#fff;}

        .newsletter{padding:80px 0;}
        .newsletter-box{background:linear-gradient(135deg,rgba(59,130,246,.15),rgba(14,165,233,.1));border:1px solid rgba(59,130,246,.2);border-radius:20px;padding:60px 40px;text-align:center;position:relative;overflow:hidden;}
        .newsletter-box::before{content:'';position:absolute;width:300px;height:300px;background:radial-gradient(circle,rgba(59,130,246,.15),transparent);top:-100px;right:-100px;border-radius:50%;}
        .newsletter-box h2{font-family:'Playfair Display',serif;font-size:32px;font-weight:800;margin-bottom:12px;}
        .newsletter-box p{color:var(--txt2);margin-bottom:28px;}
        .newsletter-form{display:flex;gap:12px;max-width:500px;margin:0 auto;}
        .newsletter-form input{flex:1;padding:14px 20px;background:var(--bg1);border:1px solid var(--border);border-radius:var(--radius);color:var(--txt1);font-size:14px;font-family:inherit;}
        .newsletter-form input:focus{outline:none;border-color:var(--btn);}

        .faq-list{max-width:800px;margin:0 auto;display:flex;flex-direction:column;gap:12px;}
        .faq-item{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;transition:all .3s;}
        .faq-item:hover{border-color:rgba(59,130,246,.3);}
        .faq-q{padding:18px 20px;display:flex;align-items:center;justify-content:space-between;cursor:pointer;font-weight:600;font-size:15px;}
        .faq-q i{transition:transform .3s;color:var(--btn);}
        .faq-item.open .faq-q i{transform:rotate(180deg);}
        .faq-a{max-height:0;overflow:hidden;transition:max-height .4s ease;}
        .faq-item.open .faq-a{max-height:200px;}
        .faq-a p{padding:0 20px 18px;color:var(--txt2);font-size:14px;line-height:1.8;}

        .kontak-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:24px;}
        .kontak-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:30px;text-align:center;transition:all .3s;}
        .kontak-card:hover{transform:translateY(-5px);border-color:rgba(59,130,246,.3);}
        .kontak-icon{width:60px;height:60px;border-radius:50%;background:rgba(59,130,246,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:22px;color:var(--btn);}
        .kontak-card h3{font-size:16px;font-weight:600;margin-bottom:8px;}
        .kontak-card p{color:var(--txt2);font-size:14px;}

        .footer{background:var(--nav);border-top:1px solid var(--border);padding:60px 0 0;}
        .footer-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:40px;padding-bottom:40px;}
        .footer h4{font-size:16px;font-weight:700;margin-bottom:20px;position:relative;}
        .footer h4::after{content:'';position:absolute;bottom:-8px;left:0;width:30px;height:2px;background:var(--btn);}
        .footer p,.footer a{color:var(--txt2);font-size:14px;line-height:2;}
        .footer a:hover{color:var(--btn);}
        .footer-bottom{border-top:1px solid var(--border);padding:20px 0;text-align:center;color:var(--txt2);font-size:13px;}

        .scroll-top{position:fixed;bottom:30px;right:30px;width:46px;height:46px;border-radius:50%;background:var(--btn);color:#fff;border:none;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center;opacity:0;visibility:hidden;transition:all .3s;box-shadow:0 4px 20px rgba(59,130,246,.4);z-index:900;}
        .scroll-top.show{opacity:1;visibility:visible;}
        .scroll-top:hover{background:var(--btnH);transform:translateY(-3px);}

        .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(8px);z-index:2000;display:none;align-items:center;justify-content:center;padding:20px;}
        .modal-overlay.show{display:flex;animation:fadeIn .3s;}
        .modal{background:var(--bg2);border:1px solid var(--border);border-radius:16px;max-width:900px;width:100%;max-height:90vh;overflow-y:auto;position:relative;}
        .modal-close{position:absolute;top:16px;right:16px;width:36px;height:36px;border-radius:50%;background:var(--card);border:1px solid var(--border);color:var(--txt1);cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;transition:all .3s;z-index:1;}
        .modal-close:hover{background:var(--danger);border-color:var(--danger);}
        .modal-body{padding:30px;}
        .detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:30px;}
        .detail-img{border-radius:var(--radius);overflow:hidden;height:400px;}
        .detail-img img{width:100%;height:100%;object-fit:cover;}
        .detail-info h2{font-family:'Playfair Display',serif;font-size:26px;margin-bottom:8px;}
        .detail-info .kat{color:var(--accent);font-size:13px;font-weight:600;text-transform:uppercase;}
        .detail-info .rating{display:flex;align-items:center;gap:6px;margin:12px 0;color:var(--warn);font-size:14px;}
        .detail-info .desc{color:var(--txt2);font-size:14px;line-height:1.8;margin:16px 0;}
        .detail-info .price{font-size:28px;font-weight:800;color:var(--btn);}
        .detail-info .price-old{font-size:16px;color:var(--txt2);text-decoration:line-through;margin-left:10px;}
        .detail-info .stok-info{margin:12px 0;font-size:14px;}
        .detail-opts{display:flex;gap:12px;align-items:center;margin-top:14px;flex-wrap:wrap;}
        .detail-opts select{padding:10px 14px;background:var(--bg1);border:1px solid var(--border);border-radius:8px;color:var(--txt1);font-family:inherit;font-size:13px;}
        .qty-box{display:flex;align-items:center;border:1px solid var(--border);border-radius:8px;overflow:hidden;}
        .qty-box button{width:34px;height:36px;background:var(--bg1);border:none;color:var(--txt1);cursor:pointer;font-size:15px;}
        .qty-box button:hover{background:var(--btn);}
        .qty-box span{width:40px;text-align:center;font-size:14px;}
        .detail-actions{display:flex;gap:12px;margin-top:20px;}

        .reveal{opacity:0;transform:translateY(40px);transition:all .7s ease;}
        .reveal.visible{opacity:1;transform:translateY(0);}

        @media(max-width:1024px){
            .detail-grid{grid-template-columns:1fr;}
            .detail-img{height:300px;}
        }
        @media(max-width:768px){
            .nav-links{display:none;}
            .hamburger{display:flex;}
            .hero{height:70vh;min-height:500px;}
            .hero-title{font-size:32px;}
            .hero-desc{font-size:15px;}
            .section{padding:50px 0;}
            .newsletter-form{flex-direction:column;}
            .toolbar{flex-direction:column;align-items:stretch;}
            .search-box{max-width:100%;}
            .produk-grid{grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;}
            .produk-img{height:220px;}
        }
        @media(max-width:480px){
            .produk-grid{grid-template-columns:1fr 1fr;gap:12px;}
            .produk-img{height:180px;}
            .produk-info{padding:10px;}
            .produk-nama{font-size:13px;}
            .harga-aktu{font-size:15px;}
            .produk-bottom{flex-direction:column;}
            .produk-bottom .btn{padding:8px;font-size:12px;}
            .banner-grid{grid-template-columns:1fr;}
        }
    </style>
</head>
<body>
    <div id="loader"><div class="loader-spinner"></div><div class="loader-text">FASHIONSTYLE STORE</div></div>
    <div class="notif-container" id="notifContainer"></div>

    <nav class="navbar" id="navbar">
        <div class="container nav-inner">
            <a href="index.php" class="nav-logo">FashionStyle</a>
            <div class="nav-links" id="navLinks">
                <a href="#home" class="active">Home</a>
                <a href="#kategori">Kategori</a>
                <a href="#produk">Produk</a>
                <a href="#tentang">Tentang Kami</a>
                <a href="#kontak">Kontak</a>
                <?php if (pelanggan_login()): ?>
                    <a href="pelanggan_dashboard.php">Halo, <?= h($_SESSION['pelanggan_nama']) ?></a>
                    <a href="logout.php">Logout</a>
                <?php elseif (admin_login()): ?>
                    <a href="admin_dashboard.php">Dashboard Admin</a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php">Registrasi</a>
                <?php endif; ?>
            </div>
            <div class="nav-actions">
                <button class="nav-btn" onclick="openSearch()" title="Cari"><i class="fas fa-search"></i></button>
                <button class="nav-btn" id="cartBtn" onclick="goToCart()" title="Keranjang">
                    <i class="fas fa-shopping-bag"></i>
                    <span class="cart-badge" id="cartBadge"><?= (int)$cartBadge ?></span>
                </button>
                <div class="hamburger" id="hamburger" onclick="toggleMobile()">
                    <span></span><span></span><span></span>
                </div>
            </div>
        </div>
    </nav>

    <div class="mobile-menu" id="mobileMenu">
        <a href="#home" onclick="closeMobile()">Home</a>
        <a href="#kategori" onclick="closeMobile()">Kategori</a>
        <a href="#produk" onclick="closeMobile()">Produk</a>
        <a href="#tentang" onclick="closeMobile()">Tentang Kami</a>
        <a href="#kontak" onclick="closeMobile()">Kontak</a>
        <?php if (pelanggan_login()): ?>
            <a href="pelanggan_dashboard.php">Halo, <?= h($_SESSION['pelanggan_nama']) ?></a>
            <a href="logout.php">Logout</a>
        <?php elseif (admin_login()): ?>
            <a href="admin_dashboard.php">Dashboard Admin</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Registrasi</a>
        <?php endif; ?>
    </div>

    <section class="hero" id="home">
        <div class="hero-slide active">
            <div class="slide-bg" style="background-image:url('https://loremflickr.com/1600/900/fashion,clothing,store/all?lock=301')"></div>
            <div class="hero-content container">
                <div class="hero-text">
                    <span class="hero-badge"><i class="fas fa-fire"></i> Diskon Hingga 50%</span>
                    <h1 class="hero-title">Koleksi <span>Fashion</span> Terbaru 2025</h1>
                    <p class="hero-desc">Temukan gaya terbaikmu dengan koleksi fashion premium berkualitas tinggi. Tampil percaya diri setiap saat.</p>
                    <div class="hero-btns">
                        <a href="#produk" class="btn btn-primary"><i class="fas fa-shopping-bag"></i> Belanja Sekarang</a>
                        <a href="#kategori" class="btn btn-outline"><i class="fas fa-eye"></i> Lihat Produk</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="hero-slide">
            <div class="slide-bg" style="background-image:url('baju jas.jpg')"></div>
            <div class="hero-content container">
                <div class="hero-text">
                    <span class="hero-badge"><i class="fas fa-gem"></i> Premium Quality</span>
                    <h1 class="hero-title">Jas <span>Premium</span> Elegan</h1>
                    <p class="hero-desc">Jas berkualitas tinggi dengan desain modern. Cocok untuk acara formal maupun semi-formal.</p>
                    <div class="hero-btns">
                        <a href="#produk" class="btn btn-primary"><i class="fas fa-shopping-bag"></i> Belanja Sekarang</a>
                        <a href="#kategori" class="btn btn-outline"><i class="fas fa-eye"></i> Lihat Koleksi</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="hero-slide">
            <div class="slide-bg" style="background-image:url('celana jens uniqlo.jpg')"></div>
            <div class="hero-content container">
                <div class="hero-text">
                    <span class="hero-badge"><i class="fas fa-bolt"></i> New Arrival</span>
                    <h1 class="hero-title">Celana Jeans <span>Modern</span></h1>
                    <p class="hero-desc">Celana jeans trendy dengan berbagai pilihan model. Nyaman dipakai seharian tanpa batas.</p>
                    <div class="hero-btns">
                        <a href="#produk" class="btn btn-primary"><i class="fas fa-shopping-bag"></i> Belanja Sekarang</a>
                        <a href="#kategori" class="btn btn-outline"><i class="fas fa-eye"></i> Lihat Koleksi</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="hero-dots" id="heroDots"></div>
    </section>

    <section class="banners">
        <div class="container">
            <div class="banner-grid">
                <div class="banner-card" onclick="filterByCategory('Jas')">
                    <img src="jas uniqlo.avif" alt="Jas Premium">
                    <div class="banner-overlay"><h3>Jas Premium</h3><p>Koleksi jas terbaik</p></div>
                </div>
                <div class="banner-card" onclick="filterByCategory('Kemeja')">
                    <img src="kemeja uniqlo.jpg.jpeg" alt="Kemeja Elegan">
                    <div class="banner-overlay"><span class="discount">-20%</span><h3>Kemeja Elegan</h3><p>Kemeja linen & cotton</p></div>
                </div>
                <div class="banner-card" onclick="filterByCategory('Celana Jeans')">
                    <img src="celana jens uniqlo.jpg" alt="Jeans Modern">
                    <div class="banner-overlay"><span class="discount">-20%</span><h3>Jeans Modern</h3><p>Slim fit & stylish</p></div>
                </div>
                <div class="banner-card" onclick="filterByCategory('Hoodie & Jaket')">
                    <img src="hodie uniqlo.jpg" alt="Hoodie & Jaket">
                    <div class="banner-overlay"><h3>Hoodie & Jaket</h3><p>Hangat & kekinian</p></div>
                </div>
            </div>
        </div>
    </section>

    <section class="section" id="kategori">
        <div class="container">
            <div class="section-header reveal">
                <h2>Kategori Produk</h2>
                <p>Jelajahi berbagai kategori fashion sesuai kebutuhanmu</p>
                <div class="line"></div>
            </div>
            <div class="kategori-grid reveal" id="kategoriGrid"></div>
        </div>
    </section>

    <section class="section" id="produk" style="background:var(--bg2);">
        <div class="container">
            <div class="section-header reveal">
                <h2>Semua Produk</h2>
                <p>Pilihan fashion terlengkap dengan harga terbaik</p>
                <div class="line"></div>
            </div>
            <div class="toolbar reveal">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Cari produk..." oninput="liveSearch(this.value)">
                    <div class="search-results" id="searchResults"></div>
                </div>
                <div class="filter-group">
                    <select class="filter-select" id="filterKategori" onchange="applyFilters()">
                        <option value="">Semua Kategori</option>
                    </select>
                    <select class="filter-select" id="filterHarga" onchange="applyFilters()">
                        <option value="">Semua Harga</option>
                        <option value="0-200000">Di bawah Rp200.000</option>
                        <option value="200000-350000">Rp200.000 - Rp350.000</option>
                        <option value="350000-999999">Di atas Rp350.000</option>
                    </select>
                    <select class="filter-select" id="filterSort" onchange="applyFilters()">
                        <option value="terbaru">Terbaru</option>
                        <option value="terlaris">Terlaris</option>
                        <option value="termurah">Termurah</option>
                        <option value="termahal">Termahal</option>
                        <option value="diskon">Diskon Terbesar</option>
                    </select>
                </div>
            </div>
            <div class="produk-grid reveal" id="produkGrid"></div>
            <div class="pagination" id="pagination"></div>
        </div>
    </section>

    <section class="section" id="tentang">
        <div class="container">
            <div class="section-header reveal">
                <h2>Tentang Kami</h2>
                <p>Mengenal lebih dekat FashionStyle Store</p>
                <div class="line"></div>
            </div>
            <div style="max-width:800px;margin:0 auto;text-align:center;" class="reveal">
                <p style="color:var(--txt2);font-size:16px;line-height:2;">
                    <strong style="color:var(--txt1);">FashionStyle Store</strong> adalah toko fashion online yang menyediakan berbagai koleksi pakaian berkualitas premium dengan harga yang bersahabat. Kami berkomitmen untuk memberikan pengalaman berbelanja terbaik dengan produk-produk pilihan yang mengikuti tren fashion terkini.
                </p>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:20px;margin-top:40px;">
                    <div style="background:var(--card);padding:30px 20px;border-radius:var(--radius);border:1px solid var(--border);">
                        <div style="font-size:32px;font-weight:800;color:var(--btn);">500+</div>
                        <div style="color:var(--txt2);font-size:13px;margin-top:4px;">Produk</div>
                    </div>
                    <div style="background:var(--card);padding:30px 20px;border-radius:var(--radius);border:1px solid var(--border);">
                        <div style="font-size:32px;font-weight:800;color:var(--btn);">1K+</div>
                        <div style="color:var(--txt2);font-size:13px;margin-top:4px;">Pelanggan</div>
                    </div>
                    <div style="background:var(--card);padding:30px 20px;border-radius:var(--radius);border:1px solid var(--border);">
                        <div style="font-size:32px;font-weight:800;color:var(--btn);">5K+</div>
                        <div style="color:var(--txt2);font-size:13px;margin-top:4px;">Transaksi</div>
                    </div>
                    <div style="background:var(--card);padding:30px 20px;border-radius:var(--radius);border:1px solid var(--border);">
                        <div style="font-size:32px;font-weight:800;color:var(--btn);">4.8</div>
                        <div style="color:var(--txt2);font-size:13px;margin-top:4px;">Rating</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section" id="faq" style="background:var(--bg2);">
        <div class="container">
            <div class="section-header reveal">
                <h2>Pertanyaan Umum</h2>
                <p>Hal-hal yang sering ditanyakan pelanggan kami</p>
                <div class="line"></div>
            </div>
            <div class="faq-list reveal">
                <div class="faq-item">
                    <div class="faq-q" onclick="toggleFaq(this)"><span>Bagaimana cara melakukan pemesanan?</span><i class="fas fa-chevron-down"></i></div>
                    <div class="faq-a"><p>Pilih produk yang diinginkan, tentukan ukuran & jumlah, tambahkan ke keranjang, lalu lanjutkan ke checkout. Isi data pengiriman dan pilih metode pembayaran.</p></div>
                </div>
                <div class="faq-item">
                    <div class="faq-q" onclick="toggleFaq(this)"><span>Apakah bisa menukar ukuran?</span><i class="fas fa-chevron-down"></i></div>
                    <div class="faq-a"><p>Ya, kami menyediakan layanan penukaran ukuran dalam waktu 7 hari setelah barang diterima, dengan syarat barang belum dicuci dan tag masih menempel.</p></div>
                </div>
                <div class="faq-item">
                    <div class="faq-q" onclick="toggleFaq(this)"><span>Metode pembayaran apa saja yang tersedia?</span><i class="fas fa-chevron-down"></i></div>
                    <div class="faq-a"><p>Kami menerima Transfer Bank, QRIS, COD, DANA, OVO, GoPay, dan ShopeePay.</p></div>
                </div>
                <div class="faq-item">
                    <div class="faq-q" onclick="toggleFaq(this)"><span>Berapa lama estimasi pengiriman?</span><i class="fas fa-chevron-down"></i></div>
                    <div class="faq-a"><p>Estimasi pengiriman 2-5 hari kerja untuk Pulau Jawa, dan 5-10 hari kerja untuk luar Jawa, tergantung jasa pengiriman yang dipilih.</p></div>
                </div>
                <div class="faq-item">
                    <div class="faq-q" onclick="toggleFaq(this)"><span>Apakah produk original?</span><i class="fas fa-chevron-down"></i></div>
                    <div class="faq-a"><p>Seluruh produk kami adalah original dengan kualitas premium. Kami memberikan garansi 100% uang kembali jika produk tidak sesuai deskripsi.</p></div>
                </div>
            </div>
        </div>
    </section>

    <section class="section" id="kontak">
        <div class="container">
            <div class="section-header reveal">
                <h2>Hubungi Kami</h2>
                <p>Jangan ragu untuk menghubungi kami kapan saja</p>
                <div class="line"></div>
            </div>
            <div class="kontak-grid reveal">
                <div class="kontak-card">
                    <div class="kontak-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <h3>Alamat</h3>
                    <p>Jl. Sudirman No. 88<br>Cirebon, 12345</p>
                </div>
                <div class="kontak-card">
                    <div class="kontak-icon"><i class="fas fa-phone"></i></div>
                    <h3>Telepon</h3>
                    <p>+62 812-3456-7890<br>+62 851-8820-7258</p>
                </div>
                <div class="kontak-card">
                    <div class="kontak-icon"><i class="fas fa-envelope"></i></div>
                    <h3>Email</h3>
                    <p>info@fashionstyle.com<br>cs@fashionstyle.com</p>
                </div>
                <div class="kontak-card">
                    <div class="kontak-icon"><i class="fas fa-clock"></i></div>
                    <h3>Jam Operasional</h3>
                    <p>Senin - Sabtu: 09.00 - 21.00<br>Minggu: 10.00 - 18.00</p>
                </div>
            </div>
        </div>
    </section>

    <section class="newsletter">
        <div class="container">
            <div class="newsletter-box reveal">
                <h2>Dapatkan Update Terbaru</h2>
                <p>Daftarkan email kamu untuk mendapatkan info diskon dan produk terbaru</p>
                <div class="newsletter-form">
                    <input type="email" id="nlEmail" placeholder="Masukkan email kamu...">
                    <button class="btn btn-primary" onclick="subscribeNL()">Berlangganan</button>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <h4>FashionStyle</h4>
                    <p>Fashion Berkualitas, Harga Bersahabat. Menyediakan koleksi fashion terlengkap untuk pria dan wanita.</p>
                </div>
                <div>
                    <h4>Navigasi</h4>
                    <a href="#home" style="display:block;">Home</a>
                    <a href="#kategori" style="display:block;">Kategori</a>
                    <a href="#produk" style="display:block;">Produk</a>
                    <a href="#tentang" style="display:block;">Tentang Kami</a>
                </div>
                <div>
                    <h4>Kategori</h4>
                    <a href="#produk" style="display:block;">Jas</a>
                    <a href="#produk" style="display:block;">Kemeja</a>
                    <a href="#produk" style="display:block;">Celana Jeans</a>
                    <a href="#produk" style="display:block;">Hoodie & Jaket</a>
                </div>
                <div>
                    <h4>Ikuti Kami</h4>
                    <a href="https://www.instagram.com/yahyaalifalmthr?igsh=MW51d2l2Nnk1M2N4eA==&igsi=MW51d2l2Nnk1M2N4eA==" target="_blank" rel="noopener noreferrer" style="display:block;"><i class="fab fa-instagram"></i> Instagram</a>
                    <a href="https://facebook.com/USERNAME_FACEBOOK_KAMU" target="_blank" rel="noopener noreferrer" style="display:block;"><i class="fab fa-facebook"></i> Facebook</a>
                    <a href="https://www.tiktok.com/@nizarpranawanda?_r=1&_t=ZS-98lixZyQb0n" target="_blank" rel="noopener noreferrer" style="display:block;"><i class="fab fa-tiktok"></i> TikTok</a>
                    <a href="https://wa.me/qr/7R6M2ATDA3VJP1" target="_blank" rel="noopener noreferrer" style="display:block;"><i class="fab fa-whatsapp"></i> WhatsApp</a>
                </div>
            </div>
            <div class="footer-bottom">&copy; 2025 FashionStyle Store. All rights reserved.</div>
        </div>
    </footer>

    <button class="scroll-top" id="scrollTop" onclick="window.scrollTo({top:0,behavior:'smooth'})"><i class="fas fa-arrow-up"></i></button>

    <div class="modal-overlay" id="detailModal">
        <div class="modal">
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
            <div class="modal-body">
                <div class="detail-grid">
                    <div class="detail-img"><img id="modalImg" src="" alt=""></div>
                    <div class="detail-info">
                        <div class="kat" id="modalKat"></div>
                        <h2 id="modalNama"></h2>
                        <div class="rating" id="modalRating"></div>
                        <div class="price">
                            <span id="modalHarga"></span>
                            <span class="price-old" id="modalHargaAsli"></span>
                        </div>
                        <div class="stok-info" id="modalStok"></div>
                        <p class="desc" id="modalDesc"></p>
                        <div class="detail-opts">
                            <select id="modalUkuran"></select>
                            <div class="qty-box">
                                <button type="button" onclick="modalQty(-1)">-</button>
                                <span id="modalQtyVal">1</span>
                                <button type="button" onclick="modalQty(1)">+</button>
                            </div>
                        </div>
                        <div class="detail-actions">
                            <button class="btn btn-primary" id="modalAddCart" onclick="addToCartFromModal()"><i class="fas fa-cart-plus"></i> Tambah Keranjang</button>
                            <button class="btn btn-success" id="modalBuyNow" onclick="buyNowFromModal()"><i class="fas fa-bolt"></i> Beli Sekarang</button>
                        </div>
                        <div style="margin-top:14px;"><a href="produk_detail.php?id=" id="modalDetailLink" style="color:var(--btn);font-size:13px;">Lihat halaman detail lengkap <i class="fas fa-arrow-right"></i></a></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    /* ===== DATA PRODUK & KATEGORI (dari database MySQL via PHP) ===== */
    const products = <?= json_encode($produkJs) ?>;
    const kategoriData = <?= json_encode($kategoriJs) ?>;
    const isLoggedIn = <?= pelanggan_login() ? 'true' : 'false' ?>;

    let currentPage = 1;
    const perPage = 6;
    let filteredProducts = [...products];
    let currentModalProduct = null;
    let modalQtyVal = 1;

    window.addEventListener('load', ()=>{
        setTimeout(()=>{ document.getElementById('loader').classList.add('hide'); }, 800);
    });

    function notify(msg, type='info'){
        const c = document.getElementById('notifContainer');
        const n = document.createElement('div');
        n.className = 'notif ' + type;
        const icons = {success:'fa-check-circle',error:'fa-times-circle',warning:'fa-exclamation-triangle',info:'fa-info-circle'};
        n.innerHTML = `<i class="fas ${icons[type]}"></i><span>${msg}</span>`;
        c.appendChild(n);
        setTimeout(()=>{ n.style.animation='slideOut .3s ease forwards'; setTimeout(()=>n.remove(),300); },3000);
    }

    window.addEventListener('scroll', ()=>{
        const nb = document.getElementById('navbar');
        const st = document.getElementById('scrollTop');
        const footerEl = document.querySelector('.footer');
        const footerRect = footerEl.getBoundingClientRect();
        const footerVisible = footerRect.top < window.innerHeight;
        if(window.scrollY > 50) nb.classList.add('scrolled'); else nb.classList.remove('scrolled');
        if(window.scrollY > 400 && !footerVisible) st.classList.add('show'); else st.classList.remove('show');
    });

    function toggleMobile(){ document.getElementById('mobileMenu').classList.toggle('show'); }
    function closeMobile(){ document.getElementById('mobileMenu').classList.remove('show'); }

    const slides = document.querySelectorAll('.hero-slide');
    const dotsC = document.getElementById('heroDots');
    let slideIdx = 0;
    slides.forEach((_,i)=>{
        const d = document.createElement('div');
        d.className = 'hero-dot' + (i===0?' active':'');
        d.onclick = ()=> goSlide(i);
        dotsC.appendChild(d);
    });
    function goSlide(i){
        slides[slideIdx].classList.remove('active');
        dotsC.children[slideIdx].classList.remove('active');
        slideIdx = i;
        slides[slideIdx].classList.add('active');
        dotsC.children[slideIdx].classList.add('active');
    }
    setInterval(()=>{ goSlide((slideIdx+1)%slides.length); }, 5000);

    function renderKategori(){
        const g = document.getElementById('kategoriGrid');
        g.innerHTML = kategoriData.map(k=>`
            <div class="kategori-card" onclick="filterByCategory('${k.nama}')">
                <img src="${k.gambar}" alt="${k.nama}">
                <div class="kat-info"><h3>${k.nama}</h3><p>${k.deskripsi||''}</p></div>
            </div>
        `).join('');
    }

    function renderProducts(){
        const g = document.getElementById('produkGrid');
        const start = (currentPage-1)*perPage;
        const end = start + perPage;
        const pageItems = filteredProducts.slice(start, end);

        if(pageItems.length===0){
            g.innerHTML = '<p style="text-align:center;color:var(--txt2);grid-column:1/-1;padding:40px;">Tidak ada produk ditemukan.</p>';
            document.getElementById('pagination').innerHTML = '';
            return;
        }

        g.innerHTML = pageItems.map(p=>{
            const kat = kategoriData.find(k=>k.id===p.kategori_id);
            let badges = '';
            if(p.diskon > 0) badges += `<span class="badge badge-diskon">-${p.diskon}%</span>`;
            if(p.terjual >= 200) badges += `<span class="badge badge-terlaris">Terlaris</span>`;
            const stars = '★'.repeat(Math.floor(p.rating)) + (p.rating%1>=0.5?'½':'');
            const ukuranOpts = (p.ukuran&&p.ukuran.length?p.ukuran:['M','L','XL','XXL']).map(u=>`<option value="${u}">${u}</option>`).join('');
            return `
            <div class="produk-card">
                <div class="produk-img">
                    <img src="${p.gambar}" alt="${p.nama}" onclick="openDetail(${p.id})" title="Klik untuk zoom">
                    <div class="produk-badge">${badges}</div>
                    <div class="produk-actions">
                        <button class="aksi-btn" onclick="openDetail(${p.id})" title="Detail"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
                <div class="produk-info">
                    <div class="produk-kat">${kat?kat.nama:'Lainnya'}</div>
                    <div class="produk-nama" title="${p.nama}">${p.nama}</div>
                    <div class="produk-rating">${stars} <span style="color:var(--txt2);margin-left:4px;">(${p.rating})</span></div>
                    <div class="produk-harga">
                        <span class="harga-aktu">Rp${p.harga.toLocaleString('id-ID')}</span>
                        ${p.diskon>0?`<span class="harga-asli">Rp${p.harga_asli.toLocaleString('id-ID')}</span>`:''}
                    </div>
                    <div class="produk-stok ${p.stok===0?'habis':''}">${p.stok>0?'Stok: '+p.stok:'Stok Habis'}</div>
                    <div class="produk-ukuran"><select id="ukuran-${p.id}">${ukuranOpts}</select></div>
                    <div class="produk-bottom">
                        <button class="btn btn-primary btn-sm" onclick="addToCart(${p.id})" ${p.stok===0?'disabled style="opacity:.5;cursor:not-allowed;"':''}><i class="fas fa-cart-plus"></i> Keranjang</button>
                        <button class="btn btn-success btn-sm" onclick="buyNow(${p.id})" ${p.stok===0?'disabled style="opacity:.5;cursor:not-allowed;"':''}><i class="fas fa-bolt"></i> Beli</button>
                    </div>
                </div>
            </div>`;
        }).join('');

        renderPagination();
    }

    function renderPagination(){
        const pg = document.getElementById('pagination');
        const total = Math.ceil(filteredProducts.length/perPage);
        if(total<=1){ pg.innerHTML=''; return; }
        let html = `<button class="page-btn" onclick="changePage(${currentPage-1})" ${currentPage===1?'disabled style="opacity:.4"':''}><i class="fas fa-chevron-left"></i></button>`;
        for(let i=1;i<=total;i++){
            html += `<button class="page-btn ${i===currentPage?'active':''}" onclick="changePage(${i})">${i}</button>`;
        }
        html += `<button class="page-btn" onclick="changePage(${currentPage+1})" ${currentPage===total?'disabled style="opacity:.4"':''}><i class="fas fa-chevron-right"></i></button>`;
        pg.innerHTML = html;
    }

    function changePage(p){
        const total = Math.ceil(filteredProducts.length/perPage);
        if(p<1||p>total) return;
        currentPage = p;
        renderProducts();
        document.getElementById('produk').scrollIntoView({behavior:'smooth'});
    }

    function applyFilters(){
        const kat = document.getElementById('filterKategori').value;
        const harga = document.getElementById('filterHarga').value;
        const sort = document.getElementById('filterSort').value;
        filteredProducts = products.filter(p=>{
            const k = kategoriData.find(x=>x.id===p.kategori_id);
            if(kat && (!k || k.nama !== kat)) return false;
            if(harga){
                const [min,max] = harga.split('-').map(Number);
                if(p.harga < min || p.harga > max) return false;
            }
            return true;
        });
        switch(sort){
            case 'terlaris': filteredProducts.sort((a,b)=>b.terjual-a.terjual); break;
            case 'termurah': filteredProducts.sort((a,b)=>a.harga-b.harga); break;
            case 'termahal': filteredProducts.sort((a,b)=>b.harga-a.harga); break;
            case 'diskon': filteredProducts.sort((a,b)=>b.diskon-a.diskon); break;
            default: filteredProducts.sort((a,b)=>b.id-a.id);
        }
        currentPage = 1;
        renderProducts();
    }

    function filterByCategory(kat){
        document.getElementById('filterKategori').value = kat;
        applyFilters();
        document.getElementById('produk').scrollIntoView({behavior:'smooth'});
    }

    function liveSearch(q){
        const sr = document.getElementById('searchResults');
        if(q.length < 2){ sr.classList.remove('show'); return; }
        const res = products.filter(p=> p.nama.toLowerCase().includes(q.toLowerCase()));
        if(res.length===0){ sr.innerHTML='<div style="padding:16px;color:var(--txt2);font-size:14px;">Tidak ditemukan</div>'; sr.classList.add('show'); return; }
        sr.innerHTML = res.slice(0,5).map(p=>`
            <div class="search-item" onclick="openDetail(${p.id});document.getElementById('searchResults').classList.remove('show');">
                <img src="${p.gambar}" alt="">
                <div><div style="font-size:14px;font-weight:500;">${p.nama}</div><div style="font-size:12px;color:var(--btn);">Rp${p.harga.toLocaleString('id-ID')}</div></div>
            </div>
        `).join('');
        sr.classList.add('show');
    }

    document.addEventListener('click',(e)=>{
        if(!e.target.closest('.search-box')) document.getElementById('searchResults').classList.remove('show');
    });

    function openSearch(){ document.getElementById('searchInput').focus(); }

    function populateFilterKategori(){
        const s = document.getElementById('filterKategori');
        kategoriData.forEach(k=>{
            const o = document.createElement('option');
            o.value = k.nama; o.textContent = k.nama;
            s.appendChild(o);
        });
    }

    /* ===== KERANJANG (tersimpan di database via PHP session, BUKAN localStorage) ===== */
    async function kirimKeKeranjang(id, jumlah, ukuran){
        const res = await fetch('tambah_keranjang.php', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({produk_id:id, jumlah:jumlah, ukuran:ukuran})
        });
        return res.json();
    }

    async function addToCart(id, silent){
        if(!isLoggedIn){
            notify('Silakan login terlebih dahulu untuk melakukan transaksi.','error');
            setTimeout(()=>window.location.href='login.php?redirect='+encodeURIComponent('index.php'), 900);
            return false;
        }
        const p = products.find(x=>x.id===id);
        if(!p || p.stok===0) { notify('Stok produk tidak mencukupi.','error'); return false; }
        const sel = document.getElementById('ukuran-'+id);
        const ukuran = sel ? sel.value : 'M';
        const data = await kirimKeKeranjang(id, 1, ukuran);
        if(!data.ok){ notify(data.message || 'Stok produk tidak mencukupi.', 'error'); return false; }
        document.getElementById('cartBadge').textContent = data.badge;
        if(!silent) notify(`${p.nama} (${ukuran}) ditambahkan ke keranjang`,'success');
        return true;
    }

    async function buyNow(id){
        const ok = await addToCart(id, true);
        if(ok) window.location.href = 'keranjang.php';
    }

    function goToCart(){
        if(!isLoggedIn){ notify('Silakan login terlebih dahulu untuk melakukan transaksi.','error'); setTimeout(()=>window.location.href='login.php?redirect='+encodeURIComponent('keranjang.php'),900); return; }
        window.location.href = 'keranjang.php';
    }

    /* ===== MODAL DETAIL ===== */
    function openDetail(id){
        const p = products.find(x=>x.id===id);
        if(!p) return;
        currentModalProduct = p;
        modalQtyVal = 1;
        document.getElementById('modalQtyVal').textContent = modalQtyVal;
        const kat = kategoriData.find(k=>k.id===p.kategori_id);
        document.getElementById('modalImg').src = p.gambar;
        document.getElementById('modalKat').textContent = kat?kat.nama:'Lainnya';
        document.getElementById('modalNama').textContent = p.nama;
        document.getElementById('modalRating').innerHTML = '★'.repeat(Math.floor(p.rating)) + ` <span style="color:var(--txt2)">${p.rating}/5 (${p.terjual} terjual)</span>`;
        document.getElementById('modalHarga').textContent = 'Rp'+p.harga.toLocaleString('id-ID');
        document.getElementById('modalHargaAsli').textContent = p.diskon>0?'Rp'+p.harga_asli.toLocaleString('id-ID'):'';
        document.getElementById('modalStok').innerHTML = p.stok>0?`<span style="color:var(--success);"><i class="fas fa-check-circle"></i> Stok tersedia: ${p.stok}</span>`:`<span style="color:var(--danger);"><i class="fas fa-times-circle"></i> Stok habis</span>`;
        document.getElementById('modalDesc').textContent = p.deskripsi;
        const ukuranSel = document.getElementById('modalUkuran');
        ukuranSel.innerHTML = (p.ukuran&&p.ukuran.length?p.ukuran:['M','L','XL','XXL']).map(u=>`<option value="${u}">${u}</option>`).join('');
        document.getElementById('modalAddCart').disabled = p.stok===0;
        document.getElementById('modalBuyNow').disabled = p.stok===0;
        document.getElementById('modalDetailLink').href = 'produk_detail.php?id='+p.id;
        document.getElementById('detailModal').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function modalQty(delta){
        if(!currentModalProduct) return;
        modalQtyVal = Math.max(1, Math.min(currentModalProduct.stok||99, modalQtyVal+delta));
        document.getElementById('modalQtyVal').textContent = modalQtyVal;
    }

    function closeModal(){
        document.getElementById('detailModal').classList.remove('show');
        document.body.style.overflow = '';
    }

    document.getElementById('detailModal').addEventListener('click',(e)=>{
        if(e.target === document.getElementById('detailModal')) closeModal();
    });

    async function addToCartFromModal(){
        if(!currentModalProduct) return;
        if(!isLoggedIn){
            notify('Silakan login terlebih dahulu untuk melakukan transaksi.','error');
            setTimeout(()=>window.location.href='login.php?redirect='+encodeURIComponent('index.php'), 900);
            return;
        }
        const ukuran = document.getElementById('modalUkuran').value;
        const data = await kirimKeKeranjang(currentModalProduct.id, modalQtyVal, ukuran);
        if(!data.ok){ notify(data.message || 'Stok produk tidak mencukupi.', 'error'); return; }
        document.getElementById('cartBadge').textContent = data.badge;
        notify(`${currentModalProduct.nama} (${ukuran}) x${modalQtyVal} ditambahkan ke keranjang`,'success');
    }
    async function buyNowFromModal(){
        if(!currentModalProduct) return;
        if(!isLoggedIn){
            notify('Silakan login terlebih dahulu untuk melakukan transaksi.','error');
            setTimeout(()=>window.location.href='login.php?redirect='+encodeURIComponent('index.php'), 900);
            return;
        }
        const ukuran = document.getElementById('modalUkuran').value;
        const data = await kirimKeKeranjang(currentModalProduct.id, modalQtyVal, ukuran);
        if(!data.ok){ notify(data.message || 'Stok produk tidak mencukupi.', 'error'); return; }
        window.location.href = 'keranjang.php';
    }

    function toggleFaq(el){
        const item = el.parentElement;
        const wasOpen = item.classList.contains('open');
        document.querySelectorAll('.faq-item').forEach(x=>x.classList.remove('open'));
        if(!wasOpen) item.classList.add('open');
    }

    function subscribeNL(){
        const e = document.getElementById('nlEmail').value;
        if(!e || !e.includes('@')) return notify('Masukkan email yang valid','error');
        notify('Berhasil berlangganan! Terima kasih.','success');
        document.getElementById('nlEmail').value = '';
    }

    function revealOnScroll(){
        document.querySelectorAll('.reveal').forEach(el=>{
            const top = el.getBoundingClientRect().top;
            if(top < window.innerHeight - 80) el.classList.add('visible');
        });
    }
    window.addEventListener('scroll', revealOnScroll);

    renderKategori();
    populateFilterKategori();
    applyFilters();
    revealOnScroll();

    <?php if (!empty($_SESSION['notif_login'])): ?>
    notify(<?= json_encode($_SESSION['notif_login']) ?>, <?= json_encode($_SESSION['notif_login_type'] ?? 'info') ?>);
    <?php unset($_SESSION['notif_login'], $_SESSION['notif_login_type']); endif; ?>
    </script>
</body>
</html>
