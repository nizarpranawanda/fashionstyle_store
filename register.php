<?php
require_once 'config.php';

if (pelanggan_login()) { header('Location: index.php'); exit; }

$errors = [
    'nama' => '', 'user' => '', 'hp' => '', 'alamat' => '', 'email' => '', 'pass' => '', 'pass2' => ''
];
$old = ['nama' => '', 'user' => '', 'hp' => '', 'alamat' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama   = trim($_POST['nama'] ?? '');
    $user   = trim($_POST['username'] ?? '');
    $hp     = trim($_POST['hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $pass   = $_POST['password'] ?? '';
    $pass2  = $_POST['password2'] ?? '';
    $old = compact('nama', 'user', 'hp', 'alamat', 'email');

    $valid = true;
    if (mb_strlen($nama) < 3) { $errors['nama'] = 'Nama minimal 3 karakter'; $valid = false; }
    if (mb_strlen($user) < 3) { $errors['user'] = 'Username minimal 3 karakter'; $valid = false; }
    if (!preg_match('/^[a-zA-Z0-9_.]+$/', $user)) { $errors['user'] = 'Username hanya boleh huruf, angka, titik, underscore'; $valid = false; }
    if (!preg_match('/^08\d{8,12}$/', $hp)) { $errors['hp'] = 'Nomor HP harus diawali 08 dan 10-13 digit'; $valid = false; }
    if (mb_strlen($alamat) < 10) { $errors['alamat'] = 'Alamat minimal 10 karakter'; $valid = false; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'Format email tidak valid'; $valid = false; }
    if (mb_strlen($pass) < 6) { $errors['pass'] = 'Password minimal 6 karakter'; $valid = false; }
    if ($pass !== $pass2) { $errors['pass2'] = 'Konfirmasi password tidak cocok'; $valid = false; }

    if ($valid) {
        $cek = $pdo->prepare("SELECT id FROM pelanggan WHERE username = ?");
        $cek->execute([$user]);
        if ($cek->fetch()) {
            $errors['user'] = 'Username sudah digunakan';
            $valid = false;
        }
    }

    if ($valid) {
        $cekEmail = $pdo->prepare("SELECT id FROM pelanggan WHERE email = ?");
        $cekEmail->execute([$email]);
        if ($cekEmail->fetch()) {
            $errors['email'] = 'Email sudah digunakan';
            $valid = false;
        }
    }

    if ($valid) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO pelanggan (nama, username, password, no_hp, alamat, email) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$nama, $user, $hash, $hp, $alamat, $email]);

        $_SESSION['notif_login'] = 'Registrasi berhasil! Silakan login.';
        $_SESSION['notif_login_type'] = 'success';
        header('Location: login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - FashionStyle Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        :root{--bg1:rgb(15,23,42);--bg2:rgb(30,41,59);--card:rgb(31,41,55);--btn:rgb(59,130,246);--btnH:rgb(37,99,235);--accent:rgb(14,165,233);--success:rgb(34,197,94);--danger:rgb(239,68,68);--warn:rgb(234,179,8);--txt1:rgb(248,250,252);--txt2:rgb(203,213,225);--border:rgba(255,255,255,.08);--radius:12px;}
        body{font-family:'Inter',sans-serif;background:var(--bg1);color:var(--txt1);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;position:relative;overflow:hidden;}
        body::before{content:'';position:absolute;width:500px;height:500px;background:radial-gradient(circle,rgba(59,130,246,.08),transparent);top:-150px;left:-150px;border-radius:50%;}
        .reg-wrapper{position:relative;z-index:1;width:100%;max-width:500px;}
        .reg-card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:36px;backdrop-filter:blur(20px);box-shadow:0 20px 60px rgba(0,0,0,.3);animation:fadeUp .5s ease;max-height:90vh;overflow-y:auto;}
        @keyframes fadeUp{from{opacity:0;transform:translateY(30px);}to{opacity:1;transform:translateY(0);}}
        .reg-logo{text-align:center;margin-bottom:4px;}
        .reg-logo a{font-family:'Playfair Display',serif;font-size:26px;font-weight:800;background:linear-gradient(135deg,var(--btn),var(--accent));-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
        .reg-subtitle{text-align:center;color:var(--txt2);font-size:14px;margin-bottom:6px;}
        .back-link{text-align:center;margin-bottom:20px;}
        .back-link a{color:var(--txt2);font-size:13px;text-decoration:none;transition:color .3s;}
        .back-link a:hover{color:var(--btn);}
        .form-group{margin-bottom:16px;}
        .form-group label{display:block;font-size:13px;font-weight:600;margin-bottom:5px;color:var(--txt2);}
        .input-wrap{position:relative;}
        .input-wrap input,.input-wrap textarea{width:100%;padding:11px 16px 11px 42px;background:var(--bg1);border:1px solid var(--border);border-radius:var(--radius);color:var(--txt1);font-size:14px;font-family:inherit;transition:border-color .3s;resize:none;}
        .input-wrap textarea{padding-left:16px;min-height:70px;}
        .input-wrap input:focus,.input-wrap textarea:focus{outline:none;border-color:var(--btn);}
        .input-wrap i.icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--txt2);font-size:13px;}
        .input-wrap .toggle-pw{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--txt2);cursor:pointer;font-size:13px;}
        .error-msg{color:var(--danger);font-size:11px;margin-top:3px;}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:13px 28px;border:none;border-radius:var(--radius);font-size:15px;font-weight:600;cursor:pointer;transition:all .3s;font-family:inherit;width:100%;}
        .btn-primary{background:var(--btn);color:#fff;}
        .btn-primary:hover{background:var(--btnH);transform:translateY(-1px);box-shadow:0 6px 20px rgba(59,130,246,.3);}
        .reg-footer{text-align:center;margin-top:20px;font-size:14px;color:var(--txt2);}
        .reg-footer a{color:var(--btn);text-decoration:none;font-weight:600;}
        .reg-footer a:hover{color:var(--accent);}
        .reg-card::-webkit-scrollbar{width:6px;}
        .reg-card::-webkit-scrollbar-track{background:transparent;}
        .reg-card::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px;}
        input.err, textarea.err{border-color:var(--danger) !important;}
    </style>
</head>
<body>
    <div class="reg-wrapper">
        <div class="reg-card">
            <div class="reg-logo"><a href="index.php">FashionStyle</a></div>
            <p class="reg-subtitle">Buat akun baru</p>
            <div class="back-link"><a href="login.php"><i class="fas fa-arrow-left"></i> Kembali ke Login</a></div>
            <form id="regForm" method="POST" action="register.php">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <div class="input-wrap"><i class="fas fa-user icon"></i><input type="text" name="nama" class="<?= $errors['nama'] ? 'err' : '' ?>" value="<?= h($old['nama']) ?>" placeholder="Masukkan nama lengkap" required></div>
                    <?php if ($errors['nama']): ?><div class="error-msg"><?= h($errors['nama']) ?></div><?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <div class="input-wrap"><i class="fas fa-at icon"></i><input type="text" name="username" class="<?= $errors['user'] ? 'err' : '' ?>" value="<?= h($old['user']) ?>" placeholder="Masukkan username" required></div>
                    <?php if ($errors['user']): ?><div class="error-msg"><?= h($errors['user']) ?></div><?php endif; ?>
                </div>
                <div class="form-group">
                    <label>No. HP</label>
                    <div class="input-wrap"><i class="fas fa-phone icon"></i><input type="tel" name="hp" class="<?= $errors['hp'] ? 'err' : '' ?>" value="<?= h($old['hp']) ?>" placeholder="Contoh: 081234567890" required></div>
                    <?php if ($errors['hp']): ?><div class="error-msg"><?= h($errors['hp']) ?></div><?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Alamat</label>
                    <div class="input-wrap"><textarea name="alamat" class="<?= $errors['alamat'] ? 'err' : '' ?>" placeholder="Masukkan alamat lengkap" required><?= h($old['alamat']) ?></textarea></div>
                    <?php if ($errors['alamat']): ?><div class="error-msg"><?= h($errors['alamat']) ?></div><?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <div class="input-wrap"><i class="fas fa-envelope icon"></i><input type="email" name="email" class="<?= $errors['email'] ? 'err' : '' ?>" value="<?= h($old['email']) ?>" placeholder="Masukkan email lengkap" required></div>
                    <?php if ($errors['email']): ?><div class="error-msg"><?= h($errors['email']) ?></div><?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrap">
                        <i class="fas fa-lock icon"></i>
                        <input type="password" id="rPass" name="password" class="<?= $errors['pass'] ? 'err' : '' ?>" placeholder="Minimal 6 karakter" required>
                        <button type="button" class="toggle-pw" onclick="togglePw('rPass',this)"><i class="fas fa-eye"></i></button>
                    </div>
                    <?php if ($errors['pass']): ?><div class="error-msg"><?= h($errors['pass']) ?></div><?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Konfirmasi Password</label>
                    <div class="input-wrap">
                        <i class="fas fa-lock icon"></i>
                        <input type="password" id="rPass2" name="password2" class="<?= $errors['pass2'] ? 'err' : '' ?>" placeholder="Ulangi password" required>
                        <button type="button" class="toggle-pw" onclick="togglePw('rPass2',this)"><i class="fas fa-eye"></i></button>
                    </div>
                    <?php if ($errors['pass2']): ?><div class="error-msg"><?= h($errors['pass2']) ?></div><?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top:8px;"><i class="fas fa-user-plus"></i> Daftar Sekarang</button>
            </form>
            <div class="reg-footer">Sudah punya akun? <a href="login.php">Login di sini</a></div>
        </div>
    </div>
    <script>
    function togglePw(id,btn){const inp=document.getElementById(id);const isP=inp.type==='password';inp.type=isP?'text':'password';btn.innerHTML=isP?'<i class="fas fa-eye-slash"></i>':'<i class="fas fa-eye"></i>';}
    </script>
</body>
</html>