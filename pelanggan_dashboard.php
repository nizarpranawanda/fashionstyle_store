<?php
require_once 'config.php';
require_login_pelanggan('pelanggan_dashboard.php');

$pelangganId = (int) $_SESSION['pelanggan_id'];
$tab = $_GET['tab'] ?? 'profil';
if (!in_array($tab, ['profil', 'riwayat'], true)) $tab = 'profil';

$notif     = $_SESSION['notif_pelanggan'] ?? null;
$notifType = $_SESSION['notif_pelanggan_type'] ?? 'success';
unset($_SESSION['notif_pelanggan'], $_SESSION['notif_pelanggan_type']);

$errors = [];

/* ================= HANDLE UPDATE PROFIL ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profil') {
    $nama   = trim($_POST['nama'] ?? '');
    $user   = trim($_POST['username'] ?? '');
    $hp     = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $pass1  = $_POST['password_baru'] ?? '';
    $pass2  = $_POST['password_baru2'] ?? '';

    if (mb_strlen($nama) < 3) $errors['nama'] = 'Nama minimal 3 karakter';
    if (mb_strlen($user) < 3 || !preg_match('/^[a-zA-Z0-9_.]+$/', $user)) $errors['username'] = 'Username minimal 3 karakter, hanya huruf/angka/./_ ';
    if (!preg_match('/^08\d{8,12}$/', $hp)) $errors['no_hp'] = 'Nomor HP harus diawali 08 dan 10-13 digit';
    if (mb_strlen($alamat) < 10) $errors['alamat'] = 'Alamat minimal 10 karakter';
    if ($pass1 !== '' && mb_strlen($pass1) < 6) $errors['password_baru'] = 'Password baru minimal 6 karakter';
    if ($pass1 !== '' && $pass1 !== $pass2) $errors['password_baru2'] = 'Konfirmasi password tidak cocok';

    if (!$errors) {
        $cek = $pdo->prepare("SELECT id FROM pelanggan WHERE username = ? AND id != ?");
        $cek->execute([$user, $pelangganId]);
        if ($cek->fetch()) $errors['username'] = 'Username sudah dipakai pelanggan lain';
    }

    if (!$errors) {
        if ($pass1 !== '') {
            $hash = password_hash($pass1, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE pelanggan SET nama=?, username=?, no_hp=?, alamat=?, password=? WHERE id=?");
            $stmt->execute([$nama, $user, $hp, $alamat, $hash, $pelangganId]);
        } else {
            $stmt = $pdo->prepare("UPDATE pelanggan SET nama=?, username=?, no_hp=?, alamat=? WHERE id=?");
            $stmt->execute([$nama, $user, $hp, $alamat, $pelangganId]);
        }
        $_SESSION['pelanggan_nama'] = $nama;
        $_SESSION['notif_pelanggan'] = 'Profil berhasil diperbarui.';
        $_SESSION['notif_pelanggan_type'] = 'success';
        header('Location: pelanggan_dashboard.php?tab=profil');
        exit;
    }
}

/* ================= AMBIL DATA ================= */
$stmt = $pdo->prepare("SELECT * FROM pelanggan WHERE id = ?");
$stmt->execute([$pelangganId]);
$profil = $stmt->fetch();

$riwayat = [];
if ($tab === 'riwayat') {
    $stmt = $pdo->prepare("SELECT * FROM transaksi WHERE pelanggan_id = ? ORDER BY tanggal DESC");
    $stmt->execute([$pelangganId]);
    $riwayat = $stmt->fetchAll();
}

$stmt = $pdo->prepare("SELECT COUNT(*) c FROM transaksi WHERE pelanggan_id = ?");
$stmt->execute([$pelangganId]);
$jmlTransaksi = (int) $stmt->fetch()['c'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Akun Saya - FashionStyle Store</title>
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

.layout{display:flex;min-height:100vh;}
.sidebar{width:240px;background:rgb(17,24,39);border-right:1px solid var(--border);flex-shrink:0;display:flex;flex-direction:column;position:sticky;top:0;height:100vh;}
.sb-logo{padding:22px 20px;font-family:'Playfair Display',serif;font-size:22px;font-weight:800;background:linear-gradient(135deg,var(--btn),var(--accent));-webkit-background-clip:text;-webkit-text-fill-color:transparent;border-bottom:1px solid var(--border);}
.sb-user{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;}
.sb-user .av{width:42px;height:42px;border-radius:50%;background:var(--btn);display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;}
.sb-user b{display:block;font-size:14px;}
.sb-user span{font-size:12px;color:var(--txt2);}
.sb-menu{flex:1;padding:14px 10px;}
.sb-menu a{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:10px;color:var(--txt2);font-size:14px;font-weight:500;margin-bottom:4px;transition:all .2s;}
.sb-menu a i{width:18px;text-align:center;}
.sb-menu a:hover{background:var(--bg2);color:var(--txt1);}
.sb-menu a.active{background:var(--btn);color:#fff;}
.sb-foot{padding:16px 10px;border-top:1px solid var(--border);}
.sb-foot a{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:10px;color:var(--txt2);font-size:14px;font-weight:500;}
.sb-foot a:hover{background:var(--bg2);color:var(--txt1);}
.sb-foot a.logout:hover{background:rgba(239,68,68,.15);color:var(--danger);}

.main{flex:1;padding:26px 30px 60px;}
.topline h1{font-size:22px;font-weight:800;margin-bottom:2px;}
.topline p{color:var(--txt2);font-size:13px;margin-bottom:22px;}

.card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;margin-bottom:20px;max-width:640px;}
.form-group{margin-bottom:16px;}
.form-group label{display:block;font-size:13px;font-weight:600;margin-bottom:6px;color:var(--txt2);}
.form-group input,.form-group textarea{width:100%;padding:11px 15px;background:var(--bg1);border:1px solid var(--border);border-radius:10px;color:var(--txt1);font-size:14px;}
.form-group textarea{min-height:76px;resize:vertical;}
.error-msg{color:var(--danger);font-size:12px;margin-top:4px;}
.form-row2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.hint{font-size:12px;color:var(--txt2);margin:-6px 0 16px;}
.btn{display:inline-flex;align-items:center;gap:8px;padding:12px 22px;border:none;border-radius:var(--radius);font-size:14px;font-weight:600;background:var(--btn);color:#fff;}
.btn:hover{background:var(--btnH);}

.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;font-size:13px;min-width:600px;}
th{text-align:left;padding:12px 16px;color:var(--txt2);font-weight:600;font-size:11px;text-transform:uppercase;border-bottom:1px solid var(--border);}
td{padding:12px 16px;border-bottom:1px solid var(--border);}
tr:last-child td{border-bottom:none;}
.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;}
.badge.lunas,.badge.diterima{background:rgba(34,197,94,.15);color:var(--success);}
.badge.gagal,.badge.batal{background:rgba(239,68,68,.15);color:var(--danger);}
.badge.belum,.badge.pending{background:rgba(234,179,8,.15);color:var(--warn);}
.badge.dikirim{background:rgba(59,130,246,.15);color:var(--btn);}
.empty-row{text-align:center;color:var(--txt2);padding:36px;}
.link-btn{color:var(--btn);font-weight:600;font-size:12px;}
.link-btn:hover{color:var(--accent);}

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
    .form-row2{grid-template-columns:1fr;}
}
</style>
</head>
<body>
<div class="notif-container" id="notifContainer"></div>

<div class="mobile-topbar">
    <button class="burger" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button>
    <span style="font-weight:700;">Akun Saya</span>
    <span></span>
</div>

<div class="layout">
    <aside class="sidebar" id="sidebar">
        <div class="sb-logo">FashionStyle</div>
        <div class="sb-user">
            <div class="av"><?= h(mb_substr($profil['nama'], 0, 1)) ?></div>
            <div><b><?= h($profil['nama']) ?></b><span>@<?= h($profil['username']) ?></span></div>
        </div>
        <nav class="sb-menu">
            <a href="pelanggan_dashboard.php?tab=profil" class="<?= $tab==='profil'?'active':'' ?>"><i class="fas fa-user"></i> Profil Saya</a>
            <a href="pelanggan_dashboard.php?tab=riwayat" class="<?= $tab==='riwayat'?'active':'' ?>"><i class="fas fa-receipt"></i> Riwayat Transaksi</a>
            <a href="keranjang.php"><i class="fas fa-shopping-bag"></i> Keranjang Saya</a>
        </nav>
        <div class="sb-foot">
            <a href="index.php"><i class="fas fa-store"></i> Kembali ke Toko</a>
            <a href="logout.php" class="logout"><i class="fas fa-right-from-bracket"></i> Logout</a>
        </div>
    </aside>

    <main class="main">
    <?php if ($tab === 'profil'): ?>
        <div class="topline"><h1>Profil Saya</h1><p>Kelola data akun dan alamat kamu</p></div>
        <div class="card">
            <form method="POST">
                <input type="hidden" name="action" value="update_profil">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" value="<?= h($_POST['nama'] ?? $profil['nama']) ?>" required>
                    <?php if (!empty($errors['nama'])): ?><div class="error-msg"><?= h($errors['nama']) ?></div><?php endif; ?>
                </div>
                <div class="form-row2">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" value="<?= h($_POST['username'] ?? $profil['username']) ?>" required>
                        <?php if (!empty($errors['username'])): ?><div class="error-msg"><?= h($errors['username']) ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>No. HP</label>
                        <input type="text" name="no_hp" value="<?= h($_POST['no_hp'] ?? $profil['no_hp']) ?>" required>
                        <?php if (!empty($errors['no_hp'])): ?><div class="error-msg"><?= h($errors['no_hp']) ?></div><?php endif; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="alamat" required><?= h($_POST['alamat'] ?? $profil['alamat']) ?></textarea>
                    <?php if (!empty($errors['alamat'])): ?><div class="error-msg"><?= h($errors['alamat']) ?></div><?php endif; ?>
                </div>
                <div class="form-row2">
                    <div class="form-group">
                        <label>Password Baru (opsional)</label>
                        <input type="password" name="password_baru" placeholder="Kosongkan jika tidak diubah">
                        <?php if (!empty($errors['password_baru'])): ?><div class="error-msg"><?= h($errors['password_baru']) ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Konfirmasi Password Baru</label>
                        <input type="password" name="password_baru2" placeholder="Ulangi password baru">
                        <?php if (!empty($errors['password_baru2'])): ?><div class="error-msg"><?= h($errors['password_baru2']) ?></div><?php endif; ?>
                    </div>
                </div>
                <button type="submit" class="btn"><i class="fas fa-save"></i> Simpan Perubahan</button>
            </form>
        </div>

    <?php else: /* riwayat */ ?>
        <div class="topline"><h1>Riwayat Transaksi</h1><p>Daftar pesanan yang pernah kamu buat (total <?= $jmlTransaksi ?> transaksi)</p></div>
        <div class="card" style="max-width:100%;">
            <div class="table-wrap">
            <table>
                <thead><tr><th>Invoice</th><th>Tanggal</th><th>Total</th><th>Bayar</th><th>Kirim</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php if (!$riwayat): ?><tr><td colspan="6" class="empty-row">Kamu belum memiliki transaksi.</td></tr><?php endif; ?>
                <?php foreach ($riwayat as $r): ?>
                    <tr>
                        <td><?= h($r['invoice_no']) ?></td>
                        <td><?= date('d M Y H:i', strtotime($r['tanggal'])) ?></td>
                        <td><?= rupiah($r['total']) ?></td>
                        <td><span class="badge <?= $r['status_bayar'] ?>"><?= h($r['status_bayar']) ?></span></td>
                        <td><span class="badge <?= $r['status_kirim'] ?>"><?= h($r['status_kirim']) ?></span></td>
                        <td><a class="link-btn" href="invoice.php?invoice=<?= urlencode($r['invoice_no']) ?>" target="_blank">Lihat Invoice <i class="fas fa-arrow-up-right-from-square"></i></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
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
</script>
</body>
</html>
