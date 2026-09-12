<?php
/**
 * config.php
 * Koneksi database + helper umum untuk FashionStyle Store.
 * Sesuaikan DB_HOST / DB_USER / DB_PASS dengan pengaturan server kamu (XAMPP/Laragon dll).
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Buffer semua output. Ini mencegah warning/notice PHP yang nyelip sebelum
// json_out() ikut tercetak dan merusak format JSON (penyebab umum tombol
// "Memproses..." macet karena res.json() gagal parsing di JavaScript).
if (!ob_get_level()) {
    ob_start();
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'fashionstyle_store');
define('DB_USER', 'root');
define('DB_PASS', '');       // isi password MySQL kamu kalau ada
define('DB_CHARSET', 'utf8mb4');

// Nama toko & path folder gambar produk (samakan dengan lokasi gambar asli kamu)
define('NAMA_TOKO', 'FashionStyle Store');
define('GAMBAR_PRODUK_DIR', 'assets/images/products/');
define('GAMBAR_KATEGORI_DIR', 'assets/images/kategori/');
define('UKURAN_DEFAULT', ['M', 'L', 'XL', 'XXL']);

// Session dipakai untuk menyimpan status login (admin / pelanggan) & TIDAK memakai localStorage
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    die('<div style="font-family:sans-serif;max-width:600px;margin:60px auto;padding:24px;background:#fff5f5;border:1px solid #f5c2c2;border-radius:10px;color:#7a1f1f;">
        <h2 style="margin-top:0;">Koneksi Database Gagal</h2>
        <p>Website tidak bisa terhubung ke database <b>' . DB_NAME . '</b>.</p>
        <p>Pastikan MySQL/XAMPP/Laragon sudah menyala, dan database <b>' . DB_NAME . '</b> sudah di-import lewat phpMyAdmin dari file <code>database/fashionstyle.sql</code>.</p>
        <p style="color:#999;font-size:13px;">Detail teknis: ' . htmlspecialchars($e->getMessage()) . '</p>
    </div>');
}

/**
 * Kalau terjadi fatal error PHP yang tidak tertangkap (mis. typo, fungsi
 * tidak ada, dll), pastikan browser tetap menerima JSON yang valid supaya
 * tombol di halaman tidak macet selamanya di "Memproses...".
 */
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        while (ob_get_level() > 0) { ob_end_clean(); }
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json');
        }
        echo json_encode([
            'ok' => false,
            'message' => 'Terjadi kesalahan server: ' . $err['message'] . ' (' . basename($err['file']) . ':' . $err['line'] . ')',
        ]);
    }
});

/** Kirim response JSON lalu berhenti (dipakai endpoint AJAX). */
function json_out($data, int $code = 200) {
    // Buang semua output yang mungkin nyelip sebelum ini (warning/notice/HTML)
    // supaya body response benar-benar JSON murni.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/** Ambil body JSON dari request POST (fetch mengirim JSON). Fallback ke $_POST kalau bukan JSON. */
function body_input(): array {
    $raw = file_get_contents('php://input');
    if ($raw) {
        $data = json_decode($raw, true);
        if (is_array($data)) return $data;
    }
    return $_POST;
}

/** Format rupiah, contoh: 150000 -> Rp150.000 */
function rupiah($angka): string {
    return 'Rp' . number_format((float)$angka, 0, ',', '.');
}

/** Buat nomor invoice unik, format: INV-YYYYMMDD-XXXX */
function generate_invoice(PDO $pdo): string {
    do {
        $invoice = 'INV-' . date('Ymd') . '-' . random_int(1000, 9999);
        $stmt = $pdo->prepare("SELECT id FROM transaksi WHERE invoice_no = ?");
        $stmt->execute([$invoice]);
    } while ($stmt->fetch());
    return $invoice;
}

/** True kalau pelanggan sedang login */
function pelanggan_login(): bool {
    return !empty($_SESSION['pelanggan_id']);
}

/** True kalau admin sedang login */
function admin_login(): bool {
    return !empty($_SESSION['admin_id']);
}

/** Wajibkan pelanggan login, kalau tidak redirect ke login.php dengan pesan */
function require_login_pelanggan(string $redirectBack = ''): int {
    if (!pelanggan_login()) {
        $_SESSION['notif_login'] = 'Silakan login terlebih dahulu untuk melakukan transaksi.';
        $back = $redirectBack ? '?redirect=' . urlencode($redirectBack) : '';
        header('Location: login.php' . $back);
        exit;
    }
    return (int) $_SESSION['pelanggan_id'];
}

/** Lindungi halaman admin */
function require_login_admin(): int {
    if (!admin_login()) {
        header('Location: ../login.php');
        exit;
    }
    return (int) $_SESSION['admin_id'];
}

/** Ambil / buat baris keranjang aktif milik seorang pelanggan */
function get_or_create_keranjang(PDO $pdo, int $pelangganId): int {
    $stmt = $pdo->prepare("SELECT id FROM keranjang WHERE pelanggan_id = ?");
    $stmt->execute([$pelangganId]);
    $row = $stmt->fetch();
    if ($row) return (int) $row['id'];

    $ins = $pdo->prepare("INSERT INTO keranjang (pelanggan_id) VALUES (?)");
    $ins->execute([$pelangganId]);
    return (int) $pdo->lastInsertId();
}

/** Hitung jumlah item (total qty) di keranjang pelanggan, untuk badge navbar */
function hitung_badge_keranjang(PDO $pdo, ?int $pelangganId): int {
    if (!$pelangganId) return 0;
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(dk.jumlah),0) AS total
        FROM detail_keranjang dk
        JOIN keranjang k ON k.id = dk.keranjang_id
        WHERE k.pelanggan_id = ?
    ");
    $stmt->execute([$pelangganId]);
    return (int) $stmt->fetchColumn();
}

/** Escape aman untuk output HTML */
function h($str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}