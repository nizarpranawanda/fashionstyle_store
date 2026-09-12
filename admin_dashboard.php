<?php
require_once 'config.php';
require_login_admin();

// Fallback gambar default (harus ada secara fisik di folder assets/images/products/ & categories/)
if (!defined('GAMBAR_PRODUK_DEFAULT'))   define('GAMBAR_PRODUK_DEFAULT', 'default-product.jpg');
if (!defined('GAMBAR_KATEGORI_DEFAULT')) define('GAMBAR_KATEGORI_DEFAULT', 'default-category.jpg');

$adminId   = (int) $_SESSION['admin_id'];
$adminNama = $_SESSION['admin_nama'] ?? 'Admin';
$tab       = $_GET['tab'] ?? 'overview';
if (!in_array($tab, ['overview', 'produk', 'kategori', 'transaksi', 'pelanggan'], true)) $tab = 'overview';

$notif     = $_SESSION['notif_admin'] ?? null;
$notifType = $_SESSION['notif_admin_type'] ?? 'success';
unset($_SESSION['notif_admin'], $_SESSION['notif_admin_type']);

function admin_redirect(string $tab, string $msg, string $type = 'success'): void {
    $_SESSION['notif_admin'] = $msg;
    $_SESSION['notif_admin_type'] = $type;
    header('Location: admin_dashboard.php?tab=' . $tab);
    exit;
}

/* ================= HANDLE AKSI (POST) ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ---------- TAMBAH PRODUK ----------
    if ($action === 'tambah_produk') {
        $nama       = trim($_POST['nama'] ?? '');
        $kategoriId = (int) ($_POST['kategori_id'] ?? 0);
        $harga      = (float) ($_POST['harga'] ?? 0);
        $hargaAsli  = (float) ($_POST['harga_asli'] ?? $harga);
        $diskon     = max(0, min(100, (int) ($_POST['diskon'] ?? 0)));
        $deskripsi  = trim($_POST['deskripsi'] ?? '');
        $stok       = max(0, (int) ($_POST['stok'] ?? 0));
        $status     = ($_POST['status'] ?? 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';
        $ukuran     = implode(',', $_POST['ukuran'] ?? ['M', 'L', 'XL', 'XXL']);
        $gambarNama = trim($_POST['gambar_lama'] ?? '');

        if (!empty($_FILES['gambar_file']['name'])) {
            $up = simpan_upload_gambar($_FILES['gambar_file']);
            if ($up) $gambarNama = $up;
        }

        if ($nama === '' || $kategoriId <= 0 || $harga <= 0) {
            admin_redirect('produk', 'Lengkapi nama, kategori, dan harga produk.', 'error');
        }

        $stmt = $pdo->prepare("INSERT INTO produk (kategori_id, nama, harga, harga_asli, diskon, deskripsi, gambar, stok, ukuran_tersedia, status) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$kategoriId, $nama, $harga, $hargaAsli, $diskon, $deskripsi, $gambarNama, $stok, $ukuran, $status]);
        admin_redirect('produk', 'Produk "' . $nama . '" berhasil ditambahkan.');
    }

    // ---------- EDIT PRODUK ----------
    if ($action === 'edit_produk') {
        $id         = (int) ($_POST['id'] ?? 0);
        $nama       = trim($_POST['nama'] ?? '');
        $kategoriId = (int) ($_POST['kategori_id'] ?? 0);
        $harga      = (float) ($_POST['harga'] ?? 0);
        $hargaAsli  = (float) ($_POST['harga_asli'] ?? $harga);
        $diskon     = max(0, min(100, (int) ($_POST['diskon'] ?? 0)));
        $deskripsi  = trim($_POST['deskripsi'] ?? '');
        $stok       = max(0, (int) ($_POST['stok'] ?? 0));
        $status     = ($_POST['status'] ?? 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';
        $ukuran     = implode(',', $_POST['ukuran'] ?? ['M', 'L', 'XL', 'XXL']);
        $gambarNama = trim($_POST['gambar_lama'] ?? '');

        if (!empty($_FILES['gambar_file']['name'])) {
            $up = simpan_upload_gambar($_FILES['gambar_file']);
            if ($up) $gambarNama = $up;
        }

        if ($id <= 0 || $nama === '' || $kategoriId <= 0 || $harga <= 0) {
            admin_redirect('produk', 'Data produk tidak valid.', 'error');
        }

        $stmt = $pdo->prepare("UPDATE produk SET kategori_id=?, nama=?, harga=?, harga_asli=?, diskon=?, deskripsi=?, gambar=?, stok=?, ukuran_tersedia=?, status=? WHERE id=?");
        $stmt->execute([$kategoriId, $nama, $harga, $hargaAsli, $diskon, $deskripsi, $gambarNama, $stok, $ukuran, $status, $id]);
        admin_redirect('produk', 'Produk "' . $nama . '" berhasil diperbarui.');
    }

    // ---------- HAPUS PRODUK ----------
    if ($action === 'hapus_produk') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM produk WHERE id = ?");
        $stmt->execute([$id]);
        admin_redirect('produk', 'Produk berhasil dihapus.');
    }

    // ---------- EDIT KATEGORI (nama, deskripsi, ganti gambar) ----------
    if ($action === 'edit_kategori') {
        $id        = (int) ($_POST['id'] ?? 0);
        $nama      = trim($_POST['nama'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $gambarLama = trim($_POST['gambar_lama'] ?? '');
        $gambarNama = $gambarLama; // default: pertahankan gambar lama

        if ($id <= 0 || $nama === '') {
            admin_redirect('kategori', 'Nama kategori tidak boleh kosong.', 'error');
        }

        if (!empty($_FILES['gambar_file']['name'])) {
            $up = simpan_upload_gambar_kategori($_FILES['gambar_file']);
            if ($up) {
                // hapus file gambar lama kalau memang beda & masih ada di server (biar tidak menumpuk file sampah)
                if ($gambarLama !== '' && $gambarLama !== $up) {
                    $pathLama = __DIR__ . '/' . GAMBAR_KATEGORI_DIR . $gambarLama;
                    if (is_file($pathLama)) @unlink($pathLama);
                }
                $gambarNama = $up;
            } else {
                admin_redirect('kategori', 'Upload gambar gagal. Pastikan format jpg/jpeg/png/webp/gif.', 'error');
            }
        }

        $stmt = $pdo->prepare("UPDATE kategori SET nama = ?, deskripsi = ?, gambar = ? WHERE id = ?");
        $stmt->execute([$nama, $deskripsi, $gambarNama, $id]);
        admin_redirect('kategori', 'Kategori "' . $nama . '" berhasil diperbarui.');
    }

    // ---------- HAPUS TRANSAKSI ----------
    if ($action === 'hapus_transaksi') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM transaksi WHERE id = ?"); // detail_transaksi & pembayaran ikut terhapus via ON DELETE CASCADE
        $stmt->execute([$id]);
        admin_redirect('transaksi', 'Data transaksi berhasil dihapus.');
    }

    // ---------- RESET PASSWORD PELANGGAN ----------
    if ($action === 'reset_password') {
        $id       = (int) ($_POST['id'] ?? 0);
        $passBaru = $_POST['password_baru'] ?? '';
        if (mb_strlen($passBaru) < 6) {
            admin_redirect('pelanggan', 'Password baru minimal 6 karakter.', 'error');
        }
        $hash = password_hash($passBaru, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE pelanggan SET password = ? WHERE id = ?");
        $stmt->execute([$hash, $id]);
        admin_redirect('pelanggan', 'Password pelanggan berhasil direset.');
    }
}

/**
 * Path gambar yang aman: kalau kolom gambar kosong / file fisiknya tidak ada di server,
 * otomatis pakai gambar default supaya preview di admin tidak jadi kotak kosong/patah.
 */
function gambar_aman(string $dir, ?string $nama, string $default): string {
    $nama = trim((string)$nama);
    if ($nama !== '' && is_file(__DIR__ . '/' . $dir . $nama)) {
        return $dir . rawurlencode($nama);
    }
    return $dir . $default;
}

/** Cek MIME type asli file gambar (bukan cuma dari ekstensi nama file) supaya file .php tidak bisa menyamar jadi gambar. */
function mime_gambar_valid(string $tmpPath): bool {
    $mimeAllowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);
        return in_array($mime, $mimeAllowed, true);
    }
    // fallback kalau ext finfo tidak tersedia di server
    $info = @getimagesize($tmpPath);
    return $info !== false && in_array($info['mime'], $mimeAllowed, true);
}

/** Simpan file gambar yang diupload admin ke folder assets/images/products/ dan kembalikan nama filenya. */
function simpan_upload_gambar(array $file): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    if (!is_uploaded_file($file['tmp_name'])) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) return null;
    if (!mime_gambar_valid($file['tmp_name'])) return null;
    $dir = __DIR__ . '/' . GAMBAR_PRODUK_DIR;
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $newName = 'produk_' . time() . '_' . random_int(100, 999) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $dir . $newName)) return $newName;
    return null;
}

/** Simpan file gambar KATEGORI yang diupload admin ke folder assets/images/categories/ dan kembalikan nama filenya. */
function simpan_upload_gambar_kategori(array $file): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    if (!is_uploaded_file($file['tmp_name'])) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) return null;
    if (!mime_gambar_valid($file['tmp_name'])) return null;
    $dir = __DIR__ . '/' . GAMBAR_KATEGORI_DIR;
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $newName = 'kategori_' . time() . '_' . random_int(100, 999) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $dir . $newName)) return $newName;
    return null;
}

/* ================= AMBIL DATA UNTUK TAMPILAN ================= */
$jmlProduk    = (int) $pdo->query("SELECT COUNT(*) c FROM produk")->fetch()['c'];
$jmlPelanggan = (int) $pdo->query("SELECT COUNT(*) c FROM pelanggan")->fetch()['c'];
$jmlTransaksi = (int) $pdo->query("SELECT COUNT(*) c FROM transaksi")->fetch()['c'];
$totalOmzet   = (float) $pdo->query("SELECT COALESCE(SUM(total),0) c FROM transaksi WHERE status_bayar='lunas'")->fetch()['c'];

$kategoriRows = $pdo->query("SELECT * FROM kategori ORDER BY nama ASC")->fetchAll();

$produkRows = [];
if ($tab === 'produk' || $tab === 'overview') {
    $produkRows = $pdo->query("SELECT p.*, k.nama AS kategori_nama FROM produk p JOIN kategori k ON k.id = p.kategori_id ORDER BY p.id DESC")->fetchAll();
}

$transaksiRows = [];
$transaksiDetailMap = [];
if ($tab === 'transaksi' || $tab === 'overview') {
    $limit = $tab === 'overview' ? 'LIMIT 6' : '';
    $transaksiRows = $pdo->query("SELECT t.*, pl.nama AS nama_pelanggan, pl.username FROM transaksi t JOIN pelanggan pl ON pl.id = t.pelanggan_id ORDER BY t.tanggal DESC $limit")->fetchAll();
    if ($transaksiRows) {
        $ids = array_column($transaksiRows, 'id');
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $dstmt = $pdo->prepare("SELECT * FROM detail_transaksi WHERE transaksi_id IN ($ph)");
        $dstmt->execute($ids);
        foreach ($dstmt->fetchAll() as $d) {
            $transaksiDetailMap[$d['transaksi_id']][] = $d;
        }
    }
}

$pelangganRows = [];
if ($tab === 'pelanggan') {
    $pelangganRows = $pdo->query("SELECT pl.*, (SELECT COUNT(*) FROM transaksi t WHERE t.pelanggan_id = pl.id) AS jml_transaksi FROM pelanggan pl ORDER BY pl.created_at DESC")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - FashionStyle Store</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{--bg1:rgb(15,23,42);--bg2:rgb(30,41,59);--card:rgb(31,41,55);--btn:rgb(59,130,246);--btnH:rgb(37,99,235);
--accent:rgb(14,165,233);--success:rgb(34,197,94);--danger:rgb(239,68,68);--warn:rgb(234,179,8);
--txt1:rgb(248,250,252);--txt2:rgb(203,213,225);--border:rgba(255,255,255,.08);--radius:12px;}
body{font-family:'Inter',sans-serif;background:var(--bg1);color:var(--txt1);min-height:100vh;}
a{text-decoration:none;color:inherit;}
button{font-family:inherit;cursor:pointer;}
input,select,textarea{font-family:inherit;}

.layout{display:flex;min-height:100vh;}
.sidebar{width:240px;background:rgb(17,24,39);border-right:1px solid var(--border);flex-shrink:0;display:flex;flex-direction:column;position:sticky;top:0;height:100vh;}
.sb-logo{padding:22px 20px;font-family:'Playfair Display',serif;font-size:22px;font-weight:800;background:linear-gradient(135deg,var(--btn),var(--accent));-webkit-background-clip:text;-webkit-text-fill-color:transparent;border-bottom:1px solid var(--border);}
.sb-admin{padding:14px 20px;font-size:13px;color:var(--txt2);border-bottom:1px solid var(--border);}
.sb-admin b{color:var(--txt1);display:block;font-size:14px;}
.sb-menu{flex:1;padding:14px 10px;}
.sb-menu a{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:10px;color:var(--txt2);font-size:14px;font-weight:500;margin-bottom:4px;transition:all .2s;}
.sb-menu a i{width:18px;text-align:center;}
.sb-menu a:hover{background:var(--bg2);color:var(--txt1);}
.sb-menu a.active{background:var(--btn);color:#fff;}
.sb-foot{padding:16px 10px;border-top:1px solid var(--border);}
.sb-foot a{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:10px;color:var(--txt2);font-size:14px;font-weight:500;}
.sb-foot a:hover{background:var(--bg2);color:var(--txt1);}
.sb-foot a.logout:hover{background:rgba(239,68,68,.15);color:var(--danger);}

.main{flex:1;padding:26px 30px 60px;max-width:100%;overflow-x:hidden;}
.topline{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;flex-wrap:wrap;gap:10px;}
.topline h1{font-size:22px;font-weight:800;}
.topline p{color:var(--txt2);font-size:13px;margin-top:2px;}
.btn{display:inline-flex;align-items:center;gap:8px;padding:11px 18px;border:none;border-radius:var(--radius);font-size:13px;font-weight:600;transition:all .2s;}
.btn-primary{background:var(--btn);color:#fff;}
.btn-primary:hover{background:var(--btnH);}
.btn-danger{background:rgba(239,68,68,.12);color:var(--danger);}
.btn-danger:hover{background:var(--danger);color:#fff;}
.btn-outline{background:transparent;border:1px solid var(--border);color:var(--txt1);}
.btn-outline:hover{border-color:var(--btn);color:var(--btn);}
.btn-sm{padding:7px 12px;font-size:12px;}

.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:26px;}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:20px;display:flex;align-items:center;gap:16px;}
.stat-card .ic{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;}
.stat-card .ic.blue{background:rgba(59,130,246,.15);color:var(--btn);}
.stat-card .ic.green{background:rgba(34,197,94,.15);color:var(--success);}
.stat-card .ic.yellow{background:rgba(234,179,8,.15);color:var(--warn);}
.stat-card .ic.purple{background:rgba(168,85,247,.15);color:rgb(168,85,247);}
.stat-card .val{font-size:22px;font-weight:800;}
.stat-card .lbl{font-size:12px;color:var(--txt2);margin-top:2px;}

.card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:24px;}
.card-head{padding:16px 20px;border-bottom:1px solid var(--border);font-weight:700;font-size:15px;display:flex;justify-content:space-between;align-items:center;}

.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;font-size:13px;min-width:640px;}
th{text-align:left;padding:12px 16px;color:var(--txt2);font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border);background:rgba(255,255,255,.02);white-space:nowrap;}
td{padding:12px 16px;border-bottom:1px solid var(--border);vertical-align:middle;}
tr:last-child td{border-bottom:none;}
.prod-thumb{width:42px;height:42px;object-fit:cover;border-radius:8px;background:var(--bg2);}
.pname{display:flex;align-items:center;gap:10px;}
.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;}
.badge.aktif,.badge.lunas,.badge.diterima,.badge.berhasil{background:rgba(34,197,94,.15);color:var(--success);}
.badge.nonaktif,.badge.gagal,.badge.batal{background:rgba(239,68,68,.15);color:var(--danger);}
.badge.belum,.badge.pending,.badge.menunggu{background:rgba(234,179,8,.15);color:var(--warn);}
.badge.dikirim{background:rgba(59,130,246,.15);color:var(--btn);}
.row-actions{display:flex;gap:6px;}
.icon-btn{width:30px;height:30px;border-radius:8px;border:1px solid var(--border);background:var(--bg2);color:var(--txt2);display:inline-flex;align-items:center;justify-content:center;font-size:12px;}
.icon-btn:hover{border-color:var(--btn);color:var(--btn);}
.icon-btn.danger:hover{border-color:var(--danger);color:var(--danger);}
.empty-row{text-align:center;color:var(--txt2);padding:36px;}

/* Modal */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.6);display:none;align-items:center;justify-content:center;z-index:1000;padding:20px;}
.modal-overlay.show{display:flex;}
.modal{background:var(--card);border:1px solid var(--border);border-radius:16px;max-width:560px;width:100%;max-height:88vh;overflow-y:auto;padding:26px;position:relative;}
.modal h3{font-size:17px;margin-bottom:18px;}
.modal-close{position:absolute;top:16px;right:16px;background:var(--bg2);border:none;width:32px;height:32px;border-radius:8px;color:var(--txt1);font-size:14px;}
.form-group{margin-bottom:14px;}
.form-group label{display:block;font-size:12px;font-weight:600;color:var(--txt2);margin-bottom:6px;}
.form-group input[type=text],.form-group input[type=number],.form-group input[type=password],.form-group select,.form-group textarea{width:100%;padding:10px 14px;background:var(--bg1);border:1px solid var(--border);border-radius:10px;color:var(--txt1);font-size:13px;}
.form-group textarea{min-height:70px;resize:vertical;}
.form-row2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.chk-group{display:flex;gap:14px;flex-wrap:wrap;}
.chk-group label{display:flex;align-items:center;gap:6px;font-size:13px;color:var(--txt1);font-weight:500;}
.modal-actions{display:flex;gap:10px;margin-top:20px;}
.modal-actions .btn{flex:1;justify-content:center;}

.notif-container{position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;}
.notif{padding:14px 20px;border-radius:var(--radius);color:#fff;font-size:14px;font-weight:500;box-shadow:0 8px 32px rgba(0,0,0,.3);min-width:260px;}
.notif.success{background:rgba(34,197,94,.9);}
.notif.error{background:rgba(239,68,68,.9);}

.mobile-topbar{display:none;position:sticky;top:0;z-index:200;background:rgb(17,24,39);border-bottom:1px solid var(--border);padding:14px 16px;align-items:center;justify-content:space-between;}
.mobile-topbar .burger{background:none;border:none;color:#fff;font-size:20px;}

@media(max-width:900px){
    .sidebar{position:fixed;left:-260px;top:0;z-index:300;transition:left .25s;box-shadow:0 0 40px rgba(0,0,0,.5);}
    .sidebar.open{left:0;}
    .mobile-topbar{display:flex;}
    .main{padding:18px 14px 50px;}
}
</style>
</head>
<body>
<div class="notif-container" id="notifContainer"></div>

<div class="mobile-topbar">
    <button class="burger" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button>
    <span style="font-weight:700;">Admin Dashboard</span>
    <span></span>
</div>

<div class="layout">
    <aside class="sidebar" id="sidebar">
        <div class="sb-logo">FashionStyle</div>
        <div class="sb-admin">Masuk sebagai<b><?= h($adminNama) ?></b></div>
        <nav class="sb-menu">
            <a href="admin_dashboard.php?tab=overview" class="<?= $tab==='overview'?'active':'' ?>"><i class="fas fa-gauge"></i> Overview</a>
            <a href="admin_dashboard.php?tab=produk" class="<?= $tab==='produk'?'active':'' ?>"><i class="fas fa-shirt"></i> Produk</a>
            <a href="admin_dashboard.php?tab=kategori" class="<?= $tab==='kategori'?'active':'' ?>"><i class="fas fa-layer-group"></i> Kategori</a>
            <a href="admin_dashboard.php?tab=transaksi" class="<?= $tab==='transaksi'?'active':'' ?>"><i class="fas fa-receipt"></i> Transaksi</a>
            <a href="admin_dashboard.php?tab=pelanggan" class="<?= $tab==='pelanggan'?'active':'' ?>"><i class="fas fa-users"></i> Pelanggan</a>
        </nav>
        <div class="sb-foot">
            <a href="index.php"><i class="fas fa-store"></i> Lihat Toko</a>
            <a href="logout.php" class="logout"><i class="fas fa-right-from-bracket"></i> Logout</a>
        </div>
    </aside>

    <main class="main">

    <?php if ($tab === 'overview'): ?>
        <div class="topline">
            <div><h1>Overview</h1><p>Ringkasan performa toko FashionStyle Store</p></div>
        </div>
        <div class="stat-grid">
            <div class="stat-card"><div class="ic blue"><i class="fas fa-shirt"></i></div><div><div class="val"><?= $jmlProduk ?></div><div class="lbl">Jumlah Produk</div></div></div>
            <div class="stat-card"><div class="ic purple"><i class="fas fa-users"></i></div><div><div class="val"><?= $jmlPelanggan ?></div><div class="lbl">Jumlah Pelanggan</div></div></div>
            <div class="stat-card"><div class="ic yellow"><i class="fas fa-receipt"></i></div><div><div class="val"><?= $jmlTransaksi ?></div><div class="lbl">Jumlah Transaksi</div></div></div>
            <div class="stat-card"><div class="ic green"><i class="fas fa-sack-dollar"></i></div><div><div class="val" style="font-size:16px;"><?= rupiah($totalOmzet) ?></div><div class="lbl">Omzet (Lunas)</div></div></div>
        </div>

        <div class="card">
            <div class="card-head">Transaksi Terbaru <a href="admin_dashboard.php?tab=transaksi" class="btn btn-outline btn-sm">Lihat Semua</a></div>
            <div class="table-wrap">
            <table>
                <thead><tr><th>Invoice</th><th>Pelanggan</th><th>Tanggal</th><th>Total</th><th>Bayar</th><th>Kirim</th></tr></thead>
                <tbody>
                <?php if (!$transaksiRows): ?><tr><td colspan="6" class="empty-row">Belum ada transaksi.</td></tr><?php endif; ?>
                <?php foreach ($transaksiRows as $t): ?>
                    <tr>
                        <td><?= h($t['invoice_no']) ?></td>
                        <td><?= h($t['nama_pelanggan']) ?></td>
                        <td><?= date('d M Y H:i', strtotime($t['tanggal'])) ?></td>
                        <td><?= rupiah($t['total']) ?></td>
                        <td><span class="badge <?= $t['status_bayar'] ?>"><?= h($t['status_bayar']) ?></span></td>
                        <td><span class="badge <?= $t['status_kirim'] ?>"><?= h($t['status_kirim']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>

    <?php elseif ($tab === 'produk'): ?>
        <div class="topline">
            <div><h1>Manajemen Produk</h1><p>Kelola produk yang tampil di toko</p></div>
            <button class="btn btn-primary" onclick="openProdukModal()"><i class="fas fa-plus"></i> Tambah Produk</button>
        </div>
        <div class="card">
            <div class="table-wrap">
            <table>
                <thead><tr><th>Produk</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Ukuran</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php if (!$produkRows): ?><tr><td colspan="7" class="empty-row">Belum ada produk.</td></tr><?php endif; ?>
                <?php foreach ($produkRows as $p): ?>
                    <tr>
                        <td>
                            <div class="pname">
                                <img class="prod-thumb" src="<?= h(gambar_aman(GAMBAR_PRODUK_DIR, $p['gambar'], GAMBAR_PRODUK_DEFAULT)) ?>" alt=""
                                     onerror="this.onerror=null;this.src='<?= h(GAMBAR_PRODUK_DIR . GAMBAR_PRODUK_DEFAULT) ?>';">
                                <span><?= h($p['nama']) ?></span>
                            </div>
                        </td>
                        <td><?= h($p['kategori_nama']) ?></td>
                        <td><?= rupiah($p['harga']) ?></td>
                        <td><?= (int)$p['stok'] ?></td>
                        <td><?= h($p['ukuran_tersedia']) ?></td>
                        <td><span class="badge <?= $p['status'] ?>"><?= h($p['status']) ?></span></td>
                        <td>
                            <div class="row-actions">
                                <button class="icon-btn" title="Edit" onclick='openProdukModal(<?= json_encode($p, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'><i class="fas fa-pen"></i></button>
                                <form method="POST" onsubmit="return confirm('Hapus produk ini?');" style="display:inline;">
                                    <input type="hidden" name="action" value="hapus_produk">
                                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                    <button type="submit" class="icon-btn danger" title="Hapus"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>

        <!-- Modal Tambah/Edit Produk -->
        <div class="modal-overlay" id="produkModal">
            <div class="modal">
                <button class="modal-close" onclick="closeProdukModal()"><i class="fas fa-times"></i></button>
                <h3 id="produkModalTitle">Tambah Produk</h3>
                <form method="POST" enctype="multipart/form-data" id="produkForm">
                    <input type="hidden" name="action" id="pAction" value="tambah_produk">
                    <input type="hidden" name="id" id="pId">
                    <input type="hidden" name="gambar_lama" id="pGambarLama">
                    <div class="form-group"><label>Nama Produk</label><input type="text" name="nama" id="pNama" required></div>
                    <div class="form-row2">
                        <div class="form-group"><label>Kategori</label>
                            <select name="kategori_id" id="pKategori" required>
                                <?php foreach ($kategoriRows as $k): ?>
                                <option value="<?= (int)$k['id'] ?>"><?= h($k['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Status</label>
                            <select name="status" id="pStatus"><option value="aktif">Aktif</option><option value="nonaktif">Nonaktif</option></select>
                        </div>
                    </div>
                    <div class="form-row2">
                        <div class="form-group"><label>Harga Jual (Rp)</label><input type="number" name="harga" id="pHarga" min="0" required></div>
                        <div class="form-group"><label>Harga Asli (Rp)</label><input type="number" name="harga_asli" id="pHargaAsli" min="0"></div>
                    </div>
                    <div class="form-row2">
                        <div class="form-group"><label>Diskon (%)</label><input type="number" name="diskon" id="pDiskon" min="0" max="100" value="0"></div>
                        <div class="form-group"><label>Stok</label><input type="number" name="stok" id="pStok" min="0" required></div>
                    </div>
                    <div class="form-group"><label>Deskripsi</label><textarea name="deskripsi" id="pDeskripsi"></textarea></div>
                    <div class="form-group">
                        <label>Ukuran Tersedia</label>
                        <div class="chk-group" id="pUkuranGroup">
                            <?php foreach (UKURAN_DEFAULT as $u): ?>
                            <label><input type="checkbox" name="ukuran[]" value="<?= $u ?>" checked> <?= $u ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Gambar Produk (nama file yang sudah ada, atau upload baru)</label>
                        <input type="text" name="gambar_lama_display" id="pGambarDisplay" placeholder="contoh: jas1.jpg" onkeyup="document.getElementById('pGambarLama').value=this.value">
                        <input type="file" name="gambar_file" id="pGambarFile" accept="image/*" style="margin-top:8px;">
                        <div style="font-size:11px;color:var(--txt2);margin-top:6px;">Kosongkan file upload untuk tetap memakai gambar lama. File baru akan disimpan ke folder <?= GAMBAR_PRODUK_DIR ?></div>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-outline" onclick="closeProdukModal()">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>

    <?php elseif ($tab === 'kategori'): ?>
        <div class="topline">
            <div><h1>Manajemen Kategori</h1><p>Kelola gambar &amp; deskripsi kategori yang tampil di toko (permanen, tersimpan di server)</p></div>
        </div>
        <div class="card">
            <div class="table-wrap">
            <table>
                <thead><tr><th>Gambar</th><th>Nama Kategori</th><th>Deskripsi</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php if (!$kategoriRows): ?><tr><td colspan="4" class="empty-row">Belum ada kategori.</td></tr><?php endif; ?>
                <?php foreach ($kategoriRows as $k): ?>
                    <tr>
                        <td>
                            <img class="prod-thumb" src="<?= h(gambar_aman(GAMBAR_KATEGORI_DIR, $k['gambar'], GAMBAR_KATEGORI_DEFAULT)) ?>" alt=""
                                 onerror="this.onerror=null;this.src='<?= h(GAMBAR_KATEGORI_DIR . GAMBAR_KATEGORI_DEFAULT) ?>';">
                        </td>
                        <td><?= h($k['nama']) ?></td>
                        <td style="max-width:320px;"><?= h($k['deskripsi'] ?? '') ?></td>
                        <td>
                            <div class="row-actions">
                                <button class="icon-btn" title="Edit / Ganti Gambar" onclick='openKategoriModal(<?= json_encode($k, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'><i class="fas fa-pen"></i></button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>

        <!-- Modal Edit Kategori -->
        <div class="modal-overlay" id="kategoriModal">
            <div class="modal">
                <button class="modal-close" onclick="closeKategoriModal()"><i class="fas fa-times"></i></button>
                <h3>Edit Kategori</h3>
                <form method="POST" enctype="multipart/form-data" id="kategoriForm">
                    <input type="hidden" name="action" value="edit_kategori">
                    <input type="hidden" name="id" id="kId">
                    <input type="hidden" name="gambar_lama" id="kGambarLama">
                    <div class="form-group"><label>Nama Kategori</label><input type="text" name="nama" id="kNama" required></div>
                    <div class="form-group"><label>Deskripsi</label><textarea name="deskripsi" id="kDeskripsi"></textarea></div>
                    <div class="form-group">
                        <label>Gambar Kategori Saat Ini</label>
                        <img id="kPreview" src="" alt="" style="width:100%;max-width:220px;height:140px;object-fit:cover;border-radius:10px;background:var(--bg1);margin-bottom:10px;display:block;"
                             onerror="this.onerror=null;this.src='<?= h(GAMBAR_KATEGORI_DIR . GAMBAR_KATEGORI_DEFAULT) ?>';">
                        <input type="file" name="gambar_file" id="kGambarFile" accept="image/*">
                        <div style="font-size:11px;color:var(--txt2);margin-top:6px;">Kosongkan file upload untuk tetap memakai gambar lama. File baru akan disimpan permanen ke folder <?= h(GAMBAR_KATEGORI_DIR) ?></div>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-outline" onclick="closeKategoriModal()">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>

    <?php elseif ($tab === 'transaksi'): ?>
        <div class="topline">
            <div><h1>Data Transaksi</h1><p>Semua transaksi pelanggan</p></div>
        </div>
        <div class="card">
            <div class="table-wrap">
            <table>
                <thead><tr><th>Invoice</th><th>Pelanggan</th><th>Tanggal</th><th>Total</th><th>Bayar</th><th>Kirim</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php if (!$transaksiRows): ?><tr><td colspan="7" class="empty-row">Belum ada transaksi.</td></tr><?php endif; ?>
                <?php foreach ($transaksiRows as $t): ?>
                    <tr>
                        <td><?= h($t['invoice_no']) ?></td>
                        <td><?= h($t['nama_pelanggan']) ?> <span style="color:var(--txt2);">(@<?= h($t['username']) ?>)</span></td>
                        <td><?= date('d M Y H:i', strtotime($t['tanggal'])) ?></td>
                        <td><?= rupiah($t['total']) ?></td>
                        <td><span class="badge <?= $t['status_bayar'] ?>"><?= h($t['status_bayar']) ?></span></td>
                        <td><span class="badge <?= $t['status_kirim'] ?>"><?= h($t['status_kirim']) ?></span></td>
                        <td>
                            <div class="row-actions">
                                <a class="icon-btn" title="Lihat Invoice" href="invoice.php?invoice=<?= urlencode($t['invoice_no']) ?>" target="_blank"><i class="fas fa-eye"></i></a>
                                <form method="POST" onsubmit="return confirm('Hapus transaksi <?= h($t['invoice_no']) ?>? Data detail & pembayaran ikut terhapus.');" style="display:inline;">
                                    <input type="hidden" name="action" value="hapus_transaksi">
                                    <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                                    <button type="submit" class="icon-btn danger" title="Hapus"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>

    <?php elseif ($tab === 'pelanggan'): ?>
        <div class="topline">
            <div><h1>Data Pelanggan</h1><p>Semua akun pelanggan terdaftar</p></div>
        </div>
        <div class="card">
            <div class="table-wrap">
            <table>
                <thead><tr><th>Nama</th><th>Username</th><th>No. HP</th><th>Alamat</th><th>Jml Transaksi</th><th>Terdaftar</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php if (!$pelangganRows): ?><tr><td colspan="7" class="empty-row">Belum ada pelanggan.</td></tr><?php endif; ?>
                <?php foreach ($pelangganRows as $pl): ?>
                    <tr>
                        <td><?= h($pl['nama']) ?></td>
                        <td>@<?= h($pl['username']) ?></td>
                        <td><?= h($pl['no_hp']) ?></td>
                        <td style="max-width:220px;"><?= h($pl['alamat']) ?></td>
                        <td><?= (int)$pl['jml_transaksi'] ?></td>
                        <td><?= date('d M Y', strtotime($pl['created_at'])) ?></td>
                        <td>
                            <button class="icon-btn" title="Reset Password" onclick="openResetModal(<?= (int)$pl['id'] ?>, '<?= h(addslashes($pl['nama'])) ?>')"><i class="fas fa-key"></i></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>

        <!-- Modal Reset Password -->
        <div class="modal-overlay" id="resetModal">
            <div class="modal" style="max-width:400px;">
                <button class="modal-close" onclick="closeResetModal()"><i class="fas fa-times"></i></button>
                <h3>Reset Password <span id="resetNamaLabel"></span></h3>
                <form method="POST">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="id" id="resetId">
                    <div class="form-group">
                        <label>Password Baru</label>
                        <input type="password" name="password_baru" minlength="6" required placeholder="Minimal 6 karakter">
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-outline" onclick="closeResetModal()">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> Reset</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    </main>
</div>

<script>
function notify(msg,type='success'){
    const c=document.getElementById('notifContainer');
    const n=document.createElement('div');n.className='notif '+type;n.textContent=msg;
    c.appendChild(n);setTimeout(()=>n.remove(),3000);
}
<?php if ($notif): ?>notify(<?= json_encode($notif) ?>, <?= json_encode($notifType) ?>);<?php endif; ?>

const ukuranDefault = <?= json_encode(UKURAN_DEFAULT) ?>;
const GAMBAR_KATEGORI_DIR_JS = <?= json_encode(GAMBAR_KATEGORI_DIR) ?>;
const GAMBAR_KATEGORI_DEFAULT_JS = <?= json_encode(GAMBAR_KATEGORI_DEFAULT) ?>;

function openKategoriModal(data){
    document.getElementById('kategoriForm').reset();
    document.getElementById('kId').value = data.id;
    document.getElementById('kNama').value = data.nama;
    document.getElementById('kDeskripsi').value = data.deskripsi || '';
    document.getElementById('kGambarLama').value = data.gambar || '';
    document.getElementById('kPreview').src = data.gambar
        ? GAMBAR_KATEGORI_DIR_JS + encodeURIComponent(data.gambar)
        : GAMBAR_KATEGORI_DIR_JS + GAMBAR_KATEGORI_DEFAULT_JS;
    document.getElementById('kategoriModal').classList.add('show');
}
function closeKategoriModal(){ document.getElementById('kategoriModal').classList.remove('show'); }

function openProdukModal(data){
    const modal = document.getElementById('produkModal');
    const title = document.getElementById('produkModalTitle');
    document.getElementById('produkForm').reset();
    document.querySelectorAll('#pUkuranGroup input').forEach(c=>c.checked=true);

    if(data){
        title.textContent = 'Edit Produk';
        document.getElementById('pAction').value = 'edit_produk';
        document.getElementById('pId').value = data.id;
        document.getElementById('pNama').value = data.nama;
        document.getElementById('pKategori').value = data.kategori_id;
        document.getElementById('pStatus').value = data.status;
        document.getElementById('pHarga').value = data.harga;
        document.getElementById('pHargaAsli').value = data.harga_asli;
        document.getElementById('pDiskon').value = data.diskon;
        document.getElementById('pStok').value = data.stok;
        document.getElementById('pDeskripsi').value = data.deskripsi || '';
        document.getElementById('pGambarLama').value = data.gambar || '';
        document.getElementById('pGambarDisplay').value = data.gambar || '';
        const selUk = (data.ukuran_tersedia||'').split(',').map(s=>s.trim());
        document.querySelectorAll('#pUkuranGroup input').forEach(c=>{ c.checked = selUk.includes(c.value); });
    } else {
        title.textContent = 'Tambah Produk';
        document.getElementById('pAction').value = 'tambah_produk';
        document.getElementById('pId').value = '';
        document.getElementById('pStatus').value = 'aktif';
    }
    modal.classList.add('show');
}
function closeProdukModal(){ document.getElementById('produkModal').classList.remove('show'); }

function openResetModal(id, nama){
    document.getElementById('resetId').value = id;
    document.getElementById('resetNamaLabel').textContent = '- ' + nama;
    document.getElementById('resetModal').classList.add('show');
}
function closeResetModal(){ document.getElementById('resetModal').classList.remove('show'); }

document.addEventListener('click', function(e){
    if(e.target.classList && e.target.classList.contains('modal-overlay')) e.target.classList.remove('show');
});
</script>
</body>
</html>