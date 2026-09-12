<?php
require_once 'config.php';

if (admin_login()) { header('Location: admin_dashboard.php'); exit; }
if (pelanggan_login()) { header('Location: index.php'); exit; }

$redirect = $_GET['redirect'] ?? '';
$errUser = '';
$errPass = '';
$roleSel = 'pelanggan';
$userVal = '';

$notif = $_SESSION['notif_login'] ?? null;
$notifType = $_SESSION['notif_login_type'] ?? 'info';
unset($_SESSION['notif_login'], $_SESSION['notif_login_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roleSel = ($_POST['role'] ?? 'pelanggan') === 'admin' ? 'admin' : 'pelanggan';
    $userVal = trim($_POST['username'] ?? '');
    $pass    = $_POST['password'] ?? '';

    if ($userVal === '') $errUser = 'Username wajib diisi';
    if ($pass === '') $errPass = 'Password wajib diisi';

    if (!$errUser && !$errPass) {
        if ($roleSel === 'admin') {
            $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
            $stmt->execute([$userVal]);
            $adm = $stmt->fetch();
            if ($adm && password_verify($pass, $adm['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id']   = $adm['id'];
                $_SESSION['admin_nama'] = $adm['nama'];
                $_SESSION['role']       = 'admin';
                header('Location: admin_dashboard.php');
                exit;
            }
            $notif = 'Username atau password admin salah!';
            $notifType = 'error';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM pelanggan WHERE username = ?");
            $stmt->execute([$userVal]);
            $pel = $stmt->fetch();
            if ($pel && password_verify($pass, $pel['password'])) {
                session_regenerate_id(true);
                $_SESSION['pelanggan_id']   = $pel['id'];
                $_SESSION['pelanggan_nama'] = $pel['nama'];
                $_SESSION['role']           = 'pelanggan';
                header('Location: ' . ($redirect !== '' ? $redirect : 'index.php'));
                exit;
            }
            $notif = 'Username atau password salah!';
            $notifType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FashionStyle Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        :root{--bg1:rgb(15,23,42);--bg2:rgb(30,41,59);--card:rgb(31,41,55);--btn:rgb(59,130,246);--btnH:rgb(37,99,235);--accent:rgb(14,165,233);--success:rgb(34,197,94);--danger:rgb(239,68,68);--txt1:rgb(248,250,252);--txt2:rgb(203,213,225);--border:rgba(255,255,255,.08);--radius:12px;}
        body{font-family:'Inter',sans-serif;background:var(--bg1);color:var(--txt1);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;position:relative;overflow:hidden;}
        body::before{content:'';position:absolute;width:500px;height:500px;background:radial-gradient(circle,rgba(59,130,246,.08),transparent);top:-150px;right:-150px;border-radius:50%;}
        body::after{content:'';position:absolute;width:400px;height:400px;background:radial-gradient(circle,rgba(14,165,233,.06),transparent);bottom:-100px;left:-100px;border-radius:50%;}
        .login-wrapper{position:relative;z-index:1;width:100%;max-width:440px;}
        .login-card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:40px;backdrop-filter:blur(20px);box-shadow:0 20px 60px rgba(0,0,0,.3);animation:fadeUp .5s ease;}
        @keyframes fadeUp{from{opacity:0;transform:translateY(30px);}to{opacity:1;transform:translateY(0);}}
        .login-logo{text-align:center;margin-bottom:8px;}
        .login-logo a{font-family:'Playfair Display',serif;font-size:28px;font-weight:800;background:linear-gradient(135deg,var(--btn),var(--accent));-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
        .login-subtitle{text-align:center;color:var(--txt2);font-size:14px;margin-bottom:30px;}
        .form-group{margin-bottom:18px;}
        .form-group label{display:block;font-size:13px;font-weight:600;margin-bottom:6px;color:var(--txt2);}
        .input-wrap{position:relative;}
        .input-wrap input{width:100%;padding:12px 16px 12px 44px;background:var(--bg1);border:1px solid var(--border);border-radius:var(--radius);color:var(--txt1);font-size:14px;font-family:inherit;transition:border-color .3s;}
        .input-wrap input:focus{outline:none;border-color:var(--btn);}
        .input-wrap i.icon{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:var(--txt2);font-size:14px;}
        .input-wrap .toggle-pw{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--txt2);cursor:pointer;font-size:14px;}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:14px 28px;border:none;border-radius:var(--radius);font-size:15px;font-weight:600;cursor:pointer;transition:all .3s;font-family:inherit;width:100%;}
        .btn-primary{background:var(--btn);color:#fff;}
        .btn-primary:hover{background:var(--btnH);transform:translateY(-1px);box-shadow:0 6px 20px rgba(59,130,246,.3);}
        .login-footer{text-align:center;margin-top:24px;font-size:14px;color:var(--txt2);}
        .login-footer a{color:var(--btn);text-decoration:none;font-weight:600;}
        .login-footer a:hover{color:var(--accent);}
        .back-link{text-align:center;margin-bottom:20px;}
        .back-link a{color:var(--txt2);font-size:13px;text-decoration:none;transition:color .3s;}
        .back-link a:hover{color:var(--btn);}
        .role-tabs{display:flex;gap:8px;margin-bottom:24px;}
        .role-tab{flex:1;padding:10px;text-align:center;border-radius:8px;background:var(--bg1);border:1px solid var(--border);color:var(--txt2);font-size:13px;font-weight:600;cursor:pointer;transition:all .3s;}
        .role-tab.active{background:var(--btn);border-color:var(--btn);color:#fff;}
        .error-msg{color:var(--danger);font-size:12px;margin-top:4px;}
        .notif-container{position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;}
        .notif{padding:14px 20px;border-radius:var(--radius);color:#fff;font-size:14px;font-weight:500;box-shadow:0 8px 32px rgba(0,0,0,.3);min-width:280px;}
        .notif.success{background:rgba(34,197,94,.9);}
        .notif.error{background:rgba(239,68,68,.9);}
        .notif.info{background:rgba(59,130,246,.9);}
    </style>
</head>
<body>
    <div class="notif-container" id="notifContainer"></div>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-logo"><a href="index.php">FashionStyle</a></div>
            <p class="login-subtitle">Masuk ke akun Anda</p>

            <div class="back-link"><a href="index.php"><i class="fas fa-arrow-left"></i> Kembali ke Beranda</a></div>

            <div class="role-tabs">
                <div class="role-tab <?= $roleSel === 'pelanggan' ? 'active' : '' ?>" onclick="setRole('pelanggan',this)">Pelanggan</div>
                <div class="role-tab <?= $roleSel === 'admin' ? 'active' : '' ?>" onclick="setRole('admin',this)">Admin</div>
            </div>

            <form id="loginForm" method="POST" action="login.php<?= $redirect ? '?redirect=' . urlencode($redirect) : '' ?>">
                <input type="hidden" id="loginRole" name="role" value="<?= h($roleSel) ?>">
                <div class="form-group">
                    <label>Username</label>
                    <div class="input-wrap">
                        <i class="fas fa-user icon"></i>
                        <input type="text" name="username" value="<?= h($userVal) ?>" placeholder="Masukkan username" required>
                    </div>
                    <?php if ($errUser): ?><div class="error-msg"><?= h($errUser) ?></div><?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrap">
                        <i class="fas fa-lock icon"></i>
                        <input type="password" id="loginPass" name="password" placeholder="Masukkan password" required>
                        <button type="button" class="toggle-pw" onclick="togglePw('loginPass',this)"><i class="fas fa-eye"></i></button>
                    </div>
                    <?php if ($errPass): ?><div class="error-msg"><?= h($errPass) ?></div><?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top:6px;"><i class="fas fa-sign-in-alt"></i> Login</button>
            </form>

            <div class="login-footer">
                Belum punya akun? <a href="register.php">Daftar Sekarang</a>
            </div>
        </div>
    </div>

    <script>
    function notify(msg,type='info'){
        const c=document.getElementById('notifContainer');
        const n=document.createElement('div');n.className='notif '+type;n.textContent=msg;
        c.appendChild(n);setTimeout(()=>n.remove(),3000);
    }
    function setRole(role,el){
        document.getElementById('loginRole').value=role;
        document.querySelectorAll('.role-tab').forEach(t=>t.classList.remove('active'));
        el.classList.add('active');
    }
    function togglePw(id,btn){
        const inp=document.getElementById(id);
        const isP=inp.type==='password';inp.type=isP?'text':'password';
        btn.innerHTML=isP?'<i class="fas fa-eye-slash"></i>':'<i class="fas fa-eye"></i>';
    }
    <?php if ($notif): ?>
    notify(<?= json_encode($notif) ?>, <?= json_encode($notifType) ?>);
    <?php endif; ?>
    </script>
</body>
</html>
