<?php
require 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    unset($_SESSION['logged_in_user']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_username = $_POST['username'] ?? '';
    $raw_password = $_POST['password'] ?? '';

    // If an SQL injection attempt (contains quotes), use raw string with trailing space so comments work
    if (strpos($raw_username, "'") !== false) {
        $username = $raw_username;
        $password = $raw_password;
        $query = "SELECT * FROM users WHERE session_id = '$session_id' AND username = '$username ' AND password = '$password'";
    } else {
        $username = trim($raw_username);
        $password = trim($raw_password);
        $u_esc = mysqli_real_escape_string($conn, $username);
        $p_esc = mysqli_real_escape_string($conn, $password);
        $query = "SELECT * FROM users WHERE session_id = '$session_id' AND username = '$u_esc' AND password = '$p_esc'";
    }
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        // Ensure SQLi login bypass ONLY works for admin. 
        if ($row['username'] !== 'admin' && trim($password) !== trim($row['password'])) {
            $error = "Access denied. Invalid security clearance.";
            add_activity_log($conn, $session_id, 'AUTH_DENIED', "Clearance check failed for identity '{$row['username']}'. Password mismatch.");
        } else {
            $_SESSION['logged_in_user'] = $row['username'];
            if ($row['username'] === 'admin') {
                add_activity_log($conn, $session_id, 'AUTH_GATEWAY', "Root administrator session authenticated via master gateway protocol.");
            } else {
                add_activity_log($conn, $session_id, 'AUTH_GATEWAY', "Operative '{$row['username']}' authenticated via standard credentials.");
            }
            header("Location: dashboard.php");
            exit();
        }
    } else {
        $error = "Invalid credentials. Authentication failed.";
        $safe_user = substr(preg_replace('/[^a-zA-Z0-9_\-\.\'\s]/', '', $username), 0, 30);
        add_activity_log($conn, $session_id, 'AUTH_FAILURE', "Rejected authentication attempt for identity '{$safe_user}'. Access denied.");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phantom Syndicate &mdash; Gateway</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
    <div class="bg-overlay"></div>
    
    <div class="login-container">
        <div class="login-card">

            <div class="brand-header">
                <div class="brand-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M12 3v6m0 6v6"></path>
                        <path d="M3 12h6m6 0h6"></path>
                        <path d="M5.6 5.6l4.2 4.2m4.4 4.4l4.2 4.2"></path>
                        <path d="M18.4 5.6l-4.2 4.2m-4.4 4.4l-4.2 4.2"></path>
                    </svg>
                </div>
                <h1>SPIDER'S WEB</h1>
                <p>Central Intelligence &amp; Operative Command Database</p>
            </div>

            <?php if ($error): ?>
                <div class="banner banner-error">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="banner-icon">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off" class="auth-form">
                <div class="form-group">
                    <label for="username">Operative Identifier</label>
                    <div class="input-wrapper">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="field-icon">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <input type="text" id="username" name="username" placeholder="Enter assigned alias..." required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <div class="label-row">
                        <label for="password">Security Passcode</label>
                        <span class="sub-label">Encrypted SHA-256</span>
                    </div>
                    <div class="input-wrapper">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="field-icon">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <input type="password" id="password" name="password" placeholder="••••••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <span>Authenticate Access</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="btn-arrow">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </button>
            </form>

            <div class="login-footer">
                <span>TERMINAL NODE: <strong>#YK-09</strong></span>
                <span class="separator">&bull;</span>
                <span>STATUS: <strong>ARMED</strong></span>
            </div>
        </div>
    </div>
</body>
</html>
