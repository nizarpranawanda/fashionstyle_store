<?php
/**
 * kategori.php
 * Halaman kategori sekaligus daftar produk per kategori (1 file saja).
 *
 * Cara kerja:
 *  - kategori.php                -> menampilkan grid semua kategori (dengan gambar)
 *  - kategori.php?id=3           -> menampilkan semua produk milik kategori id=3
 *
 * Gambar kategori/produk dipasang dengan fallback ikon otomatis, jadi kalau file
 * gambar aslinya belum ada di folder assets/images/... halaman TETAP rapi,
 * tidak muncul ikon "gambar pecah/broken image" seperti di screenshot.
 */
require_once __DIR__ . '/config.php';

$kategoriId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

/* ================= AMBIL DATA KATEGORI ================= */
$stmtKat = $pdo->query("
    SELECT k.*, COUNT(p.id) AS jumlah_produk
    FROM kategori k
    LEFT JOIN produk p ON p.kategori_id = k.id AND p.status = 'aktif'
    GROUP BY k.id
    ORDER BY k.nama ASC
");
$semuaKategori = $stmtKat->fetchAll();

$kategoriAktif = null;
$produkList    = [];

if ($kategoriId > 0) {
    foreach ($semuaKategori as $k) {
        if ((int)$k['id'] === $kategoriId) { $kategoriAktif = $k; break; }
    }
    if (!$kategoriAktif) {
        // id kategori tidak ditemukan -> jangan error, cukup kembali ke grid kategori
        $kategoriId = 0;
    } else {
        $stmtProduk = $pdo->prepare("
            SELECT * FROM produk
            WHERE kategori_id = ? AND status = 'aktif'
            ORDER BY created_at DESC
        ");
        $stmtProduk->execute([$kategoriId]);
        $produkList = $stmtProduk->fetchAll();
    }
}

// Cek apakah halaman detail produk tersedia di server ini, supaya kartu produk
// hanya dibuat "link" kalau file tujuannya memang ada (menghindari link mati / error 404).
$detailFile = null;
foreach (['produk_detail.php', 'detail_produk.php', 'produk.php'] as $f) {
    if (file_exists(__DIR__ . '/' . $f)) { $detailFile = $f; break; }
}

$badgeKeranjang = hitung_badge_keranjang($pdo, pelanggan_login() ? (int)$_SESSION['pelanggan_id'] : null);

// Fallback gambar default (harus ada secara fisik di folder assets/images/categories/)
if (!defined('GAMBAR_KATEGORI_DEFAULT')) define('GAMBAR_KATEGORI_DEFAULT', 'default-category.jpg');
if (!defined('GAMBAR_PRODUK_DEFAULT'))   define('GAMBAR_PRODUK_DEFAULT', 'default-product.jpg');

/**
 * GAMBAR KATEGORI & PRODUK - PERMANEN DARI DATABASE + FOLDER SERVER
 * -------------------------------------------------------------------
 * Kolom `gambar` di tabel kategori/produk menyimpan NAMA FILE saja.
 * File aslinya disimpan permanen di assets/images/categories/ dan
 * assets/images/products/ (lihat GAMBAR_KATEGORI_DIR / GAMBAR_PRODUK_DIR
 * di config.php). Kalau file belum ada / kolomnya kosong, otomatis
 * pakai gambar default supaya tidak muncul broken image.
 */
function gambar_aman(string $dir, ?string $nama, string $default): string {
    $nama = trim((string)$nama);
    if ($nama !== '' && is_file(__DIR__ . '/' . $dir . $nama)) {
        return $dir . rawurlencode($nama);
    }
    return $dir . $default;
}

// Ikon fallback Font Awesome per nama kategori (kalau gambar tidak ada / gagal load)
function ikon_kategori(string $nama): string {
    $nama = mb_strtolower($nama);
    if (str_contains($nama, 'jas'))    return 'fa-user-tie';
    if (str_contains($nama, 'kemeja')) return 'fa-shirt';
    if (str_contains($nama, 'jeans'))  return 'fa-socks';
    if (str_contains($nama, 'bahan'))  return 'fa-vest';
    if (str_contains($nama, 'hoodie') || str_contains($nama, 'jaket')) return 'fa-mitten';
    if (str_contains($nama, 'kaos'))   return 'fa-shirt';
    return 'fa-bag-shopping';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $kategoriAktif ? h($kategoriAktif['nama']) . ' - ' : '' ?>Kategori - <?= h(NAMA_TOKO) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{--bg1:rgb(15,23,42);--bg2:rgb(30,41,59);--card:rgb(31,41,55);--btn:rgb(59,130,246);--btnH:rgb(37,99,235);
--accent:rgb(14,165,233);--success:rgb(34,197,94);--danger:rgb(239,68,68);--warn:rgb(234,179,8);
--txt1:rgb(248,250,252);--txt2:rgb(203,213,225);--border:rgba(255,255,255,.08);--radius:14px;}
body{font-family:'Inter',sans-serif;background:var(--bg1);color:var(--txt1);min-height:100vh;}
a{text-decoration:none;color:inherit;}

/* ===== Navbar ===== */
.navbar{position:sticky;top:0;z-index:200;background:rgb(17,24,39);border-bottom:1px solid var(--border);
display:flex;align-items:center;justify-content:space-between;padding:16px 36px;flex-wrap:wrap;gap:14px;}
.nav-logo{font-family:'Playfair Display',serif;font-size:24px;font-weight:800;
background:linear-gradient(135deg,var(--btn),var(--accent));-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.nav-links{display:flex;align-items:center;gap:28px;flex-wrap:wrap;}
.nav-links a{font-size:14px;font-weight:600;color:var(--txt2);padding:6px 2px;border-bottom:2px solid transparent;}
.nav-links a:hover,.nav-links a.active{color:var(--txt1);border-color:var(--btn);}
.nav-right{display:flex;align-items:center;gap:18px;}
.cart-ic{position:relative;font-size:18px;color:var(--txt1);}
.cart-ic .badge{position:absolute;top:-8px;right:-10px;background:var(--danger);color:#fff;font-size:10px;
font-weight:700;border-radius:50%;width:18px;height:18px;display:flex;align-items:center;justify-content:center;}

/* ===== Header halaman ===== */
.page-head{padding:34px 36px 10px;max-width:1280px;margin:0 auto;}
.breadcrumb{font-size:13px;color:var(--txt2);margin-bottom:10px;}
.breadcrumb a{color:var(--btn);font-weight:600;}
.breadcrumb a:hover{color:var(--accent);}
.page-head h1{font-size:26px;font-weight:800;margin-bottom:4px;}
.page-head p{color:var(--txt2);font-size:14px;}

/* ===== Grid kategori ===== */
.wrap{max-width:1280px;margin:0 auto;padding:20px 36px 70px;}
.kat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:22px;}
.kat-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;
transition:transform .2s,border-color .2s;display:block;}
.kat-card:hover{transform:translateY(-4px);border-color:var(--btn);}
.img-box{position:relative;width:100%;aspect-ratio:4/3;background:linear-gradient(135deg,rgb(30,58,95),rgb(15,23,42));
display:flex;align-items:center;justify-content:center;overflow:hidden;}
.img-box i{font-size:44px;color:rgba(148,163,184,.35);position:relative;z-index:1;}
.img-box img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:2;}
.kat-info{padding:16px 18px 18px;}
.kat-info h3{font-size:17px;font-weight:700;margin-bottom:5px;}
.kat-info p{font-size:12.5px;color:var(--txt2);line-height:1.5;margin-bottom:10px;min-height:34px;}
.kat-info .count{font-size:12px;font-weight:700;color:var(--btn);display:flex;align-items:center;gap:6px;}

/* ===== Grid produk ===== */
.prod-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:22px;}
.prod-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;
transition:transform .2s,border-color .2s;display:block;position:relative;}
.prod-card:hover{transform:translateY(-4px);border-color:var(--btn);}
.prod-card .img-box{aspect-ratio:3/4;}
.diskon-badge{position:absolute;top:10px;left:10px;background:var(--danger);color:#fff;font-size:11px;font-weight:800;
padding:4px 9px;border-radius:20px;z-index:3;}
.prod-info{padding:14px 16px 16px;}
.prod-info h3{font-size:14.5px;font-weight:700;margin-bottom:6px;line-height:1.35;
display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:38px;}
.price-row{display:flex;align-items:baseline;gap:8px;margin-bottom:8px;flex-wrap:wrap;}
.price-now{font-size:15.5px;font-weight:800;color:var(--btn);}
.price-old{font-size:12px;color:var(--txt2);text-decoration:line-through;}
.meta-row{display:flex;align-items:center;justify-content:space-between;font-size:11.5px;color:var(--txt2);}
.meta-row .rating{color:var(--warn);font-weight:700;}

.empty-state{text-align:center;padding:80px 20px;color:var(--txt2);}
.empty-state i{font-size:46px;margin-bottom:16px;opacity:.4;}
.empty-state p{font-size:14px;}

@media(max-width:640px){
    .navbar{padding:14px 18px;}
    .nav-links{display:none;}
    .page-head,.wrap{padding-left:18px;padding-right:18px;}
}
</style>
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="nav-logo"><?= h(NAMA_TOKO) ?></a>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="kategori.php" class="active">Kategori</a>
        <?php if (file_exists(__DIR__.'/produk.php')): ?><a href="produk.php">Produk</a><?php endif; ?>
        <?php if (file_exists(__DIR__.'/tentang.php')): ?><a href="tentang.php">Tentang Kami</a><?php endif; ?>
        <?php if (file_exists(__DIR__.'/kontak.php')): ?><a href="kontak.php">Kontak</a><?php endif; ?>
    </div>
    <div class="nav-right">
        <?php if (pelanggan_login()): ?>
            <span style="font-size:13px;color:var(--txt2);">Halo, <?= h($_SESSION['pelanggan_nama'] ?? 'Pelanggan') ?></span>
            <?php if (file_exists(__DIR__.'/keranjang.php')): ?>
            <a href="keranjang.php" class="cart-ic"><i class="fas fa-shopping-bag"></i>
                <span class="badge"><?= $badgeKeranjang ?></span>
            </a>
            <?php endif; ?>
            <a href="logout.php" style="font-size:13px;font-weight:600;color:var(--txt2);">Logout</a>
        <?php elseif (file_exists(__DIR__.'/login.php')): ?>
            <a href="login.php" style="font-size:13px;font-weight:700;color:var(--btn);">Login</a>
        <?php endif; ?>
    </div>
</nav>

<?php if (!$kategoriAktif): ?>
    <!-- ===================== TAMPILAN: GRID SEMUA KATEGORI ===================== -->
    <div class="page-head">
        <h1>Kategori Produk</h1>
        <p>Pilih kategori untuk melihat semua produk di dalamnya</p>
    </div>
    <div class="wrap">
        <?php if (!$semuaKategori): ?>
            <div class="empty-state"><i class="fas fa-box-open"></i><p>Belum ada kategori.</p></div>
        <?php else: ?>
        <div class="kat-grid">
            <?php foreach ($semuaKategori as $k):
                $imgSrc = gambar_aman(GAMBAR_KATEGORI_DIR, $k['gambar'], GAMBAR_KATEGORI_DEFAULT);
                $icon   = ikon_kategori($k['nama']);
            ?>
            <a class="kat-card" href="kategori.php?id=<?= (int)$k['id'] ?>">
                <div class="img-box">
                    <i class="fas <?= $icon ?>"></i>
                    <img src="<?= h($imgSrc) ?>" alt="<?= h($k['nama']) ?>"
                         onerror="this.onerror=null;this.src='<?= h(GAMBAR_KATEGORI_DIR . GAMBAR_KATEGORI_DEFAULT) ?>';"
                         loading="lazy">
                </div>
                <div class="kat-info">
                    <h3><?= h($k['nama']) ?></h3>
                    <p><?= h($k['deskripsi'] ?? '') ?></p>
                    <div class="count"><i class="fas fa-shirt"></i> <?= (int)$k['jumlah_produk'] ?> produk</div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- ===================== TAMPILAN: PRODUK DALAM 1 KATEGORI ===================== -->
    <div class="page-head">
        <?php $imgSrcAktif = gambar_aman(GAMBAR_KATEGORI_DIR, $kategoriAktif['gambar'], GAMBAR_KATEGORI_DEFAULT); ?>
        <div class="breadcrumb"><a href="kategori.php">Kategori</a> / <?= h($kategoriAktif['nama']) ?></div>
        <h1><?= h($kategoriAktif['nama']) ?></h1>
        <p><?= h($kategoriAktif['deskripsi'] ?? '') ?> &middot; <?= count($produkList) ?> produk</p>
    </div>
    <div class="wrap">
        <?php if (!$produkList): ?>
            <div class="empty-state"><i class="fas fa-box-open"></i><p>Belum ada produk pada kategori ini.</p></div>
        <?php else: ?>
        <div class="prod-grid">
            <?php foreach ($produkList as $p):
                $imgSrc = gambar_aman(GAMBAR_PRODUK_DIR, $p['gambar'], GAMBAR_PRODUK_DEFAULT);
                $icon   = ikon_kategori($kategoriAktif['nama']);
                $link   = $detailFile ? $detailFile . '?id=' . (int)$p['id'] : null;
                $Tag    = $link ? 'a' : 'div';
            ?>
            <<?= $Tag ?> class="prod-card"<?= $link ? ' href="'.h($link).'"' : '' ?>>
                <div class="img-box">
                    <?php if ((int)$p['diskon'] > 0): ?><span class="diskon-badge">-<?= (int)$p['diskon'] ?>%</span><?php endif; ?>
                    <i class="fas <?= $icon ?>"></i>
                    <img src="<?= h($imgSrc) ?>" alt="<?= h($p['nama']) ?>"
                         onerror="this.onerror=null;this.src='<?= h(GAMBAR_PRODUK_DIR . GAMBAR_PRODUK_DEFAULT) ?>';"
                         loading="lazy">
                </div>
                <div class="prod-info">
                    <h3><?= h($p['nama']) ?></h3>
                    <div class="price-row">
                        <span class="price-now"><?= rupiah($p['harga']) ?></span>
                        <?php if ((int)$p['diskon'] > 0): ?><span class="price-old"><?= rupiah($p['harga_asli']) ?></span><?php endif; ?>
                    </div>
                    <div class="meta-row">
                        <span class="rating"><i class="fas fa-star"></i> <?= number_format((float)$p['rating'],1) ?></span>
                        <span><?= (int)$p['terjual'] ?> terjual</span>
                    </div>
                </div>
            </<?= $Tag ?>>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

</body>
</html>