<?php
// view_logs.php - Untuk melihat access log
// PROTEKSI: Ganti password ini!
$ADMIN_PASSWORD = 'admin123'; 

session_start();

// Cek login admin
if (!isset($_SESSION['admin_logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['password'] === $ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Admin Login</title>
            <style>
                body { font-family: Arial; background: #f5f5f5; padding: 50px; }
                .login-box { max-width: 300px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; }
                button { width: 100%; padding: 10px; background: #667eea; color: white; border: none; border-radius: 4px; cursor: pointer; }
            </style>
        </head>
        <body>
            <div class="login-box">
                <h3>🔐 Admin Login</h3>
                <form method="POST">
                    <input type="password" name="password" placeholder="Password Admin" required>
                    <button type="submit">Login</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: view_logs.php');
    exit;
}

// Tampilkan log
$logFile = 'access_log.txt';
$logs = file_exists($logFile) ? file_get_contents($logFile) : 'Belum ada log akses.';
$logLines = array_reverse(explode("\n", trim($logs))); // Terbaru di atas
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Logs - SMADA</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
        }
        .header {
            background: #2d2d2d;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        h1 { color: #4ec9b0; font-size: 1.5rem; }
        .btn-logout {
            background: #d32f2f;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .log-container {
            background: #2d2d2d;
            padding: 20px;
            border-radius: 8px;
            max-height: 70vh;
            overflow-y: auto;
        }
        .log-line {
            padding: 8px 12px;
            margin: 4px 0;
            border-left: 3px solid #4ec9b0;
            background: #252525;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .log-line:hover {
            background: #2a2a2a;
        }
        .status-success { border-left-color: #4ec9b0; }
        .status-failed { border-left-color: #f48771; }
        .status-locked { border-left-color: #ce9178; }
        .empty-log {
            text-align: center;
            padding: 40px;
            color: #858585;
            font-size: 1.1rem;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: #2d2d2d;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #4ec9b0;
        }
        .stat-label {
            color: #858585;
            font-size: 0.9rem;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Access Logs - SMADA</h1>
        <a href="?logout=1" class="btn-logout">Logout</a>
    </div>
    
    <?php
    // Hitung statistik
    $totalSuccess = substr_count($logs, 'SUCCESS');
    $totalFailed = substr_count($logs, 'FAILED');
    $totalLocked = substr_count($logs, 'LOCKED_OUT');
    $totalAccess = count($logLines);
    ?>
    
    <div class="stats">
        <div class="stat-card">
            <div class="stat-number"><?= $totalSuccess ?></div>
            <div class="stat-label">✅ Sukses Login</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $totalFailed ?></div>
            <div class="stat-label">❌ Gagal Login</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $totalLocked ?></div>
            <div class="stat-label">🔒 Locked Out</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $totalAccess ?></div>
            <div class="stat-label">📝 Total Log</div>
        </div>
    </div>
    
    <div class="log-container">
        <?php if (empty(trim($logs))): ?>
            <div class="empty-log">📋 Belum ada aktivitas login</div>
        <?php else: ?>
            <?php foreach ($logLines as $line): ?>
                <?php if (trim($line)): ?>
                    <?php
                    $statusClass = 'status-success';
                    if (strpos($line, 'FAILED') !== false) $statusClass = 'status-failed';
                    if (strpos($line, 'LOCKED_OUT') !== false) $statusClass = 'status-locked';
                    ?>
                    <div class="log-line <?= $statusClass ?>">
                        <?= htmlspecialchars($line) ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>