<?php
// auth_secure.php - VERSI SIMPLE TANPA DATABASE
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// KONFIGURASI - GANTI SESUAI KEBUTUHAN
$VALID_CODE = 'SMADAPAS292929'; // Kode akses yang benar
$MAX_ATTEMPTS = 5; // Maksimal percobaan salah
$LOCKOUT_TIME = 900; // 15 menit (dalam detik)

// Data credentials (AMAN karena di server-side)
$CREDENTIALS = [
    [
        'url' => 'https://smadapas.biz.id/x-adminnya/',
        'username' => 'admin',
        'password' => 'Admin@123'
    ],
    [
        'url' => 'https://smadapas.biz.id/xi-adminnya/',
        'username' => 'admin',
        'password' => 'Admin@123'
    ],
    [
        'url' => 'https://smadapas.biz.id/xii-adminnya/',
        'username' => 'admin',
        'password' => 'Suchman@21'
    ]
];

// Fungsi untuk mencatat percobaan gagal
function recordFailedAttempt() {
    if (!isset($_SESSION['failed_attempts'])) {
        $_SESSION['failed_attempts'] = 0;
        $_SESSION['lockout_until'] = 0;
    }
    $_SESSION['failed_attempts']++;
    
    if ($_SESSION['failed_attempts'] >= $GLOBALS['MAX_ATTEMPTS']) {
        $_SESSION['lockout_until'] = time() + $GLOBALS['LOCKOUT_TIME'];
    }
}

// Fungsi untuk cek apakah sedang dikunci
function isLockedOut() {
    if (isset($_SESSION['lockout_until']) && $_SESSION['lockout_until'] > time()) {
        $remaining = $_SESSION['lockout_until'] - time();
        return [
            'locked' => true,
            'remaining' => $remaining,
            'message' => 'Terlalu banyak percobaan gagal. Coba lagi dalam ' . ceil($remaining / 60) . ' menit.'
        ];
    }
    return ['locked' => false];
}

// Fungsi untuk reset percobaan setelah sukses
function resetAttempts() {
    $_SESSION['failed_attempts'] = 0;
    $_SESSION['lockout_until'] = 0;
}

// Fungsi untuk log akses (simpan ke file)
function logAccess($status, $ip) {
    $logFile = 'access_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] IP: $ip | Status: $status\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Main Logic
try {
    // Cek method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        exit;
    }
    
    // Ambil IP address
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    // Cek apakah sedang locked out
    $lockStatus = isLockedOut();
    if ($lockStatus['locked']) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => $lockStatus['message'],
            'locked' => true
        ]);
        logAccess('LOCKED_OUT', $ip_address);
        exit;
    }
    
    // Ambil input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['kode'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Kode tidak boleh kosong'
        ]);
        exit;
    }
    
    $kode = trim($input['kode']);
    
    // Validasi kode
    if ($kode === $VALID_CODE) {
        // SUKSES - Set session
        $_SESSION['authenticated'] = true;
        $_SESSION['login_time'] = time();
        resetAttempts();
        
        // Log akses sukses
        logAccess('SUCCESS', $ip_address);
        
        // Kirim response dengan data
        echo json_encode([
            'success' => true,
            'message' => 'Akses diberikan',
            'data' => $CREDENTIALS
        ]);
        
    } else {
        // GAGAL - Record percobaan gagal
        recordFailedAttempt();
        logAccess('FAILED', $ip_address);
        
        $remainingAttempts = $MAX_ATTEMPTS - ($_SESSION['failed_attempts'] ?? 0);
        
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Kode salah! Sisa percobaan: ' . max(0, $remainingAttempts),
            'remaining_attempts' => max(0, $remainingAttempts)
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan server'
    ]);
    
    // Log error
    error_log('Auth Error: ' . $e->getMessage());
}
?>