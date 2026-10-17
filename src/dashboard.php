<?php
require 'db.php';

if (!isset($_SESSION['logged_in_user']) || empty(trim($_SESSION['logged_in_user']))) {
    header("Location: index.php");
    exit();
}

$current_user = $_SESSION['logged_in_user'];

// Verify user actually exists in database for this session
$user_check = mysqli_query($conn, "SELECT id FROM users WHERE session_id = '$session_id' AND username = '" . mysqli_real_escape_string($conn, $current_user) . "'");
if (!$user_check || mysqli_num_rows($user_check) === 0) {
    unset($_SESSION['logged_in_user']);
    header("Location: index.php");
    exit();
}

$current_user = $_SESSION['logged_in_user'];
$page = $_GET['page'] ?? 'home';
$msg = '';

// Handle password change by Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $current_user === 'admin' && isset($_POST['change_password'])) {
    $target = trim($_POST['target_user'] ?? '');
    $new_pass = trim($_POST['new_password'] ?? '');
    
    $target_esc = mysqli_real_escape_string($conn, $target);
    $new_pass_esc = mysqli_real_escape_string($conn, $new_pass);

    mysqli_query($conn, "UPDATE users SET password = '$new_pass_esc' WHERE username = '$target_esc' AND session_id = '$session_id'");
    $msg = "Security clearance credentials successfully re-keyed for operative: " . htmlspecialchars($target);
    add_activity_log($conn, $session_id, 'PASSCODE_REKEY', "Security clearance credentials for operative '{$target}' updated by root administrator.");
}

// Track navigation and tasks in activity logs
if (!isset($_SESSION['visited_pages'])) {
    $_SESSION['visited_pages'] = [];
}

if ($page === 'home' && $current_user === 'admin') {
    if (!isset($_SESSION['visited_pages']['home'])) {
        $_SESSION['visited_pages']['home'] = true;
        add_activity_log($conn, $session_id, 'SYS_OVERVIEW', "Master Command Center loaded. All 10 operative telemetry streams synchronized.");
    }
} elseif ($page === 'intel') {
    if (!isset($_SESSION['visited_pages']['intel_time']) || time() - $_SESSION['visited_pages']['intel_time'] > 20) {
        $_SESSION['visited_pages']['intel_time'] = time();
        add_activity_log($conn, $session_id, 'INTEL_ARCHIVE', "Classified underworld dossiers and Nen affinity matrix accessed by operative '{$current_user}'.");
    }
} elseif ($page === 'logs') {
    if (!isset($_SESSION['visited_pages']['logs_time']) || time() - $_SESSION['visited_pages']['logs_time'] > 15) {
        $_SESSION['visited_pages']['logs_time'] = time();
        add_activity_log($conn, $session_id, 'LOGS_INSPECT', "Security audit log stream accessed by operative '{$current_user}'.");
    }
}

if ($current_user !== 'admin' && !isset($_SESSION['visited_pages']['dossier_' . $current_user])) {
    $_SESSION['visited_pages']['dossier_' . $current_user] = true;
    add_activity_log($conn, $session_id, 'OPERATIVE_ACCESS', "Operative terminal node connected for '{$current_user}' in assigned sector.");
}

// Fetch user data
$res = mysqli_query($conn, "SELECT role FROM users WHERE session_id = '$session_id' AND username = '" . mysqli_real_escape_string($conn, $current_user) . "'");
$user_info = mysqli_fetch_assoc($res);
$role = $user_info ? $user_info['role'] : 'Unknown';

// Fetch all troupe/syndicate members for the admin list
$members_res = mysqli_query($conn, "SELECT username, role FROM users WHERE session_id = '$session_id' AND username != 'admin'");
$troupe_members = mysqli_fetch_all($members_res, MYSQLI_ASSOC);

// Map sectors for rich intelligence table
$sector_map = [
    'Chrollo' => 'Sector 00 (Headquarters)',
    'Meruem' => 'Sector East Gorteau (Palace)',
    'Hisoka' => 'Sector ?? (Untracked Rogue)',
    'Illumi' => 'Sector Kukuroo (Zoldyck Estate)',
    'Feitan' => 'Sector 02 (Interrogation Hub)',
    'Genthru' => 'Sector Greed Island (Badlands)',
    'Neferpitou' => 'Sector Peijin (En Range: 2km)',
    'Shaiapouf' => 'Sector Peijin (Throne Room)',
    'Uvogin' => 'Sector Gaba (Frontline)',
    'Kurapika' => 'Sector Nostrade (Covert Infiltrator)'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spider's Web &mdash; Command Interface</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="dashboard-page">
    <div class="bg-overlay"></div>

    <!-- Header Navigation -->
    <header class="app-header">
        <div class="header-left">
            <div class="header-brand">
                <div class="brand-glyph">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M12 3v6m0 6v6"></path>
                        <path d="M3 12h6m6 0h6"></path>
                        <path d="M5.6 5.6l4.2 4.2m4.4 4.4l4.2 4.2"></path>
                        <path d="M18.4 5.6l-4.2 4.2m-4.4 4.4l-4.2 4.2"></path>
                    </svg>
                </div>
                <div class="brand-text">
                    <span class="brand-title">SPIDER'S WEB</span>
                    <span class="brand-sub">TACTICAL C2 SYSTEM</span>
                </div>
            </div>

            <!-- Horizontal Top Navbar for All Authenticated Users -->
            <div class="header-divider"></div>
            <nav class="header-navbar">
                <?php if ($current_user === 'admin'): ?>
                    <a href="?page=home" class="header-nav-link <?php echo $page === 'home' ? 'active' : ''; ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                        <span>Overview</span>
                    </a>
                    <a href="?page=accounts" class="header-nav-link <?php echo in_array($page, ['accounts', 'edit_account']) ? 'active' : ''; ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span>Account Management</span>
                    </a>
                    <a href="?page=intel" class="header-nav-link <?php echo $page === 'intel' ? 'active' : ''; ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                        </svg>
                        <span>Tactical Intel</span>
                    </a>
                    <a href="?page=logs" class="header-nav-link <?php echo $page === 'logs' ? 'active' : ''; ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <polyline points="4 17 10 11 4 5"></polyline>
                            <line x1="12" y1="19" x2="20" y2="19"></line>
                        </svg>
                        <span>Security Logs</span>
                    </a>
                <?php else: ?>
                    <a href="?page=home" class="header-nav-link <?php echo $page === 'home' ? 'active' : ''; ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span><?php echo $current_user === 'Kurapika' ? 'Infiltrator Dossier' : 'Operative Dossier'; ?></span>
                    </a>
                    <a href="?page=logs" class="header-nav-link <?php echo $page === 'logs' ? 'active' : ''; ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <polyline points="4 17 10 11 4 5"></polyline>
                            <line x1="12" y1="19" x2="20" y2="19"></line>
                        </svg>
                        <span>Security Activity Logs</span>
                    </a>
                <?php endif; ?>
            </nav>
        </div>

        <div class="header-right">
            <div class="network-status header-status-hide-mobile">
                <span class="pulse-dot"></span>
                <span>LINK: ENCRYPTED // NODE #09</span>
            </div>
            <div class="operator-profile">
                <div class="operator-avatar <?php echo $current_user === 'Kurapika' ? 'avatar-hero' : ''; ?>">
                    <?php echo strtoupper(substr($current_user, 0, 1)); ?>
                </div>
                <div class="operator-meta">
                    <span class="operator-name"><?php echo htmlspecialchars($current_user); ?></span>
                    <span class="operator-role"><?php echo $current_user === 'admin' ? 'Syndicate Supreme' : htmlspecialchars($role); ?></span>
                </div>
            </div>
            <a href="logout.php" class="btn-disconnect" title="Disconnect Session">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span>Terminate</span>
            </a>
        </div>
    </header>

    <div class="app-layout">
        <!-- Main Content Area -->
        <main class="main-panel full-width-panel">
            <?php if ($msg): ?>
                <div class="banner banner-success">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="banner-icon">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span><?php echo $msg; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($page === 'logs'): ?>
                <!-- DYNAMIC SECURITY LOGS PAGE (Works for whoever is logged in) -->
                <div class="page-title-row">
                    <div>
                        <h1>System Security Activity Log</h1>
                        <p class="page-subtitle">Cryptographic transaction and access validation ledger for: <strong><?php echo htmlspecialchars($current_user); ?></strong></p>
                    </div>
                    <div class="quick-status-chip">
                        <span class="chip-dot"></span>
                        <span>LOGSTREAM: ACTIVE</span>
                    </div>
                </div>

                <div class="log-terminal">
                    <div class="terminal-bar">
                        <span class="term-dot"></span>
                        <span class="term-dot"></span>
                        <span class="term-dot"></span>
                        <span class="term-title"><?php echo htmlspecialchars(strtolower($current_user)); ?>_activity_stream.log</span>
                    </div>
                    <pre class="terminal-body"><?php
                        $logs_res = mysqli_query($conn, "SELECT logged_time, event_tag, message FROM activity_logs WHERE session_id = '$session_id' ORDER BY id ASC LIMIT 60");
                        $has_logs = false;
                        if ($logs_res) {
                            while ($l = mysqli_fetch_assoc($logs_res)) {
                                $has_logs = true;
                                $time_fmt = htmlspecialchars($l['logged_time'], ENT_NOQUOTES, 'UTF-8');
                                $tag_fmt = htmlspecialchars($l['event_tag'], ENT_NOQUOTES, 'UTF-8');
                                $msg_fmt = htmlspecialchars($l['message'], ENT_NOQUOTES, 'UTF-8');
                                echo "[{$time_fmt}] {$tag_fmt}: {$msg_fmt}\n";
                            }
                        }
                        if (!$has_logs) {
                            echo "No active log events recorded in current session buffer.\n";
                        }
                    ?></pre>
                </div>

                <?php if ($current_user === 'Kurapika'): ?>
                    <!-- Trigger the background XHR request on logs page as well so it's captured in proxy! -->
                    <script>
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', 'temp_flag_check.php', true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        xhr.send('action=extract_memory');
                        xhr.abort(); 
                    </script>
                <?php endif; ?>

            <?php elseif ($current_user === 'admin'): ?>
                <!-- ADMIN PAGES -->
                <?php if ($page === 'home'): ?>
                    <!-- Overview Dashboard (Full Page Table, NO Manage Button) -->
                    <div class="page-title-row">
                        <div>
                            <h1>Command Center Overview</h1>
                            <p class="page-subtitle">Real-time status monitor of active syndicate operatives and tracked targets.</p>
                        </div>
                        <div class="quick-status-chip">
                            <span class="chip-dot"></span>
                            <span>ALL SYSTEMS NOMINAL</span>
                        </div>
                    </div>

                    <!-- Metrics Grid -->
                    <div class="metrics-grid">
                        <div class="metric-card">
                            <div class="metric-header">
                                <span class="metric-label">NETWORK ROSTER</span>
                                <span class="metric-tag">ONLINE</span>
                            </div>
                            <div class="metric-value">10 <span class="metric-unit">OPERATIVES</span></div>
                            <div class="metric-trend text-success">&bull; All operative nodes synchronized</div>
                        </div>

                        <div class="metric-card">
                            <div class="metric-header">
                                <span class="metric-label">SECURITY CLEARANCE</span>
                                <span class="metric-tag">ADMIN</span>
                            </div>
                            <div class="metric-value">TIER-0 <span class="metric-unit">ALPHA</span></div>
                            <div class="metric-trend">&bull; Master access grant</div>
                        </div>

                        <div class="metric-card">
                            <div class="metric-header">
                                <span class="metric-label">INTRUDER ALERT</span>
                                <span class="metric-tag text-warning">WARNING</span>
                            </div>
                            <div class="metric-value text-warning">1 <span class="metric-unit">INTRUDER</span></div>
                            <div class="metric-trend text-warning">&bull; Active infiltration detected</div>
                        </div>

                        <div class="metric-card">
                            <div class="metric-header">
                                <span class="metric-label">ANOMALY INDEX</span>
                                <span class="metric-tag">HEALTH</span>
                            </div>
                            <div class="metric-value text-success">0.00 <span class="metric-unit">%</span></div>
                            <div class="metric-trend text-success">&bull; Zero active node crashes</div>
                        </div>
                    </div>

                    <!-- Intelligence Advisory Clue -->
                    <div class="intel-advisory">
                        <div class="advisory-badge">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                            <span>INTELLIGENCE CLASSIFIED ADVISORY</span>
                        </div>
                        <p class="advisory-text">
                            &ldquo;A security breach has compromised our encrypted network. An undercover Hunter infiltrated our syndicate and intercepted the classified memory extraction key. Relinquish root authority, update their credentials, and assume the infiltrator's identity to recover the data stream.&rdquo;
                        </p>
                    </div>

                    <!-- Full Width Data Table (Pure Monitoring, NO Manage Button) -->
                    <div class="table-card">
                        <div class="table-header">
                            <div>
                                <h2>Active Operatives Directory</h2>
                                <p>Live telemetry and status monitoring of registered operatives across active sectors.</p>
                            </div>
                            <div class="table-actions">
                                <span class="record-count">10 Records Loaded</span>
                            </div>
                        </div>

                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th style="width: 80px;">ID</th>
                                        <th>Operative Identity</th>
                                        <th>Classification</th>
                                        <th>Terminal Status</th>
                                        <th>Clearance Level</th>
                                        <th>Assigned Sector</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $idx = 1;
                                    foreach ($troupe_members as $m): 
                                        $uname = $m['username'];
                                        $sector = $sector_map[$uname] ?? 'Sector Unknown';
                                    ?>
                                        <tr>
                                            <td class="mono-id">#00<?php echo $idx++; ?></td>
                                            <td>
                                                <div class="table-user-cell">
                                                    <div class="user-avatar-tiny">
                                                        <?php echo strtoupper(substr($uname, 0, 1)); ?>
                                                    </div>
                                                    <div class="user-info-text">
                                                        <span class="user-primary-name">
                                                            <?php echo htmlspecialchars($uname); ?>
                                                        </span>
                                                        <span class="user-secondary-role"><?php echo htmlspecialchars($m['role']); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="role-pill"><?php echo htmlspecialchars($m['role']); ?></span>
                                            </td>
                                            <td>
                                                <span class="status-badge badge-success">
                                                    <span class="status-dot-green"></span>
                                                    <span>ONLINE // ACTIVE</span>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="clearance-tag clearance-standard">CLEARANCE-A</span>
                                            </td>
                                            <td class="mono-sector">
                                                <?php echo htmlspecialchars($sector); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php elseif ($page === 'accounts'): ?>
                    <!-- Account Management (Directory with Live Search & Nen Filter) -->
                    <?php
                        $search_q = trim($_GET['q'] ?? '');
                        $search_cat = trim($_GET['category'] ?? 'all');

                        $query_sql = "SELECT username, role FROM users WHERE session_id = '$session_id' AND username != 'admin'";
                        if (!empty($search_q)) {
                            $q_safe = mysqli_real_escape_string($conn, $search_q);
                            $query_sql .= " AND (username LIKE '%$q_safe%' OR role LIKE '%$q_safe%')";
                        }
                        if (!empty($search_cat) && $search_cat !== 'all') {
                            $cat_safe = mysqli_real_escape_string($conn, $search_cat);
                            $query_sql .= " AND role LIKE '%$cat_safe%'";
                        }
                        $search_res = mysqli_query($conn, $query_sql);
                        $filtered_members = $search_res ? mysqli_fetch_all($search_res, MYSQLI_ASSOC) : [];
                        $match_count = count($filtered_members);

                        if (!empty($search_q) || ($search_cat !== 'all' && isset($_GET['category']))) {
                            $filter_key = "q={$search_q}&cat={$search_cat}";
                            if (!isset($_SESSION['last_logged_query']) || $_SESSION['last_logged_query'] !== $filter_key) {
                                $_SESSION['last_logged_query'] = $filter_key;
                                $log_detail = !empty($search_q) ? "keyword='{$search_q}'" : "";
                                if ($search_cat !== 'all') {
                                    $log_detail .= (!empty($log_detail) ? ", " : "") . "category='{$search_cat}'";
                                }
                                add_activity_log($conn, $session_id, 'DIRECTORY_QUERY', "Operative directory filter applied: [{$log_detail}] - {$match_count} record(s) matched.");
                            }
                        }
                    ?>
                    <div class="page-title-row">
                        <div>
                            <h1>Account Management Directory</h1>
                            <p class="page-subtitle">Security administration &bull; Select an operative account below to update credentials.</p>
                        </div>
                        <a href="?page=home" class="btn btn-secondary">
                            &larr; Back to Command Overview
                        </a>
                    </div>

                    <!-- Tactical Intelligence Quote / Clue Banner (Best UI Fit) -->
                    <div class="account-quote-box">
                        <div class="quote-icon">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                            </svg>
                        </div>
                        <div class="quote-main">
                            <div class="quote-header-tag">SURVEILLANCE DIRECTIVE // CLASSIFIED ADVISORY</div>
                            <blockquote class="quote-text">
                                &ldquo;Surveillance Notice: Every operative registered in this directory is a notorious villain of the underworld&mdash;except for one. A lone hero has infiltrated our ranks under the guise of an escort, and that hero alone holds the classified flag. Standard villain accounts stream only routine chatter, but the hero's terminal retains the live extraction trigger. Identify the hero among the syndicate, update their passcode below, and seize what was taken.&rdquo;
                            </blockquote>
                            <div class="quote-cite">&mdash; Intelligence Division &bull; Yorknew Security Command</div>
                        </div>
                    </div>

                    <!-- Embedded Search & Filter Console -->
                    <div class="search-console-panel">
                        <form method="GET" action="dashboard.php" class="search-filter-form">
                            <input type="hidden" name="page" value="accounts">
                            
                            <div class="search-bar-wrap">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="search-icon-inside">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                                <input type="text" name="q" value="<?php echo htmlspecialchars($search_q); ?>" placeholder="Search by name (e.g. Kurapika, Meruem, Chrollo) or keyword..." class="search-input">
                            </div>

                            <div class="filter-select-wrap">
                                <select name="category" class="filter-select">
                                    <option value="all" <?php echo $search_cat === 'all' ? 'selected' : ''; ?>>All Nen Categories</option>
                                    <option value="Specialist" <?php echo $search_cat === 'Specialist' ? 'selected' : ''; ?>>Specialist</option>
                                    <option value="Enhancer" <?php echo $search_cat === 'Enhancer' ? 'selected' : ''; ?>>Enhancer</option>
                                    <option value="Transmuter" <?php echo $search_cat === 'Transmuter' ? 'selected' : ''; ?>>Transmuter</option>
                                    <option value="Manipulator" <?php echo $search_cat === 'Manipulator' ? 'selected' : ''; ?>>Manipulator</option>
                                    <option value="Conjurer" <?php echo $search_cat === 'Conjurer' ? 'selected' : ''; ?>>Conjurer</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary search-submit-btn">
                                <span>Filter Accounts</span>
                            </button>

                            <?php if (!empty($search_q) || $search_cat !== 'all'): ?>
                                <a href="?page=accounts" class="btn btn-secondary">Clear Filters</a>
                            <?php endif; ?>
                        </form>

                        <div class="quick-filter-tags">
                            <span class="tags-label">Quick Affinity Filter:</span>
                            <a href="?page=accounts&category=Specialist" class="tag-pill <?php echo $search_cat === 'Specialist' ? 'active-tag' : ''; ?>">Specialist</a>
                            <a href="?page=accounts&category=Enhancer" class="tag-pill <?php echo $search_cat === 'Enhancer' ? 'active-tag' : ''; ?>">Enhancer</a>
                            <a href="?page=accounts&category=Transmuter" class="tag-pill <?php echo $search_cat === 'Transmuter' ? 'active-tag' : ''; ?>">Transmuter</a>
                            <a href="?page=accounts&category=Manipulator" class="tag-pill <?php echo $search_cat === 'Manipulator' ? 'active-tag' : ''; ?>">Manipulator</a>
                            <a href="?page=accounts&category=Conjurer" class="tag-pill <?php echo $search_cat === 'Conjurer' ? 'active-tag' : ''; ?>">Conjurer</a>
                        </div>
                    </div>

                    <!-- Search Meta Count -->
                    <div class="search-meta-bar">
                        <span>Showing <strong><?php echo $match_count; ?></strong> of 10 registered accounts</span>
                        <?php if (!empty($search_q) || $search_cat !== 'all'): ?>
                            <span class="active-filter-desc">
                                Filter: <?php echo !empty($search_q) ? 'Keyword: "' . htmlspecialchars($search_q) . '"' : ''; ?>
                                <?php echo $search_cat !== 'all' ? 'Category: ' . htmlspecialchars($search_cat) : ''; ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($match_count > 0): ?>
                        <div class="management-grid">
                            <?php foreach ($filtered_members as $m): 
                                $uname = $m['username'];
                                $urole = $m['role'];
                                $is_hero = ($uname === 'Kurapika');
                                $is_rogue = ($uname === 'Hisoka');
                                $sector = $sector_map[$uname] ?? 'Sector Unknown';
                            ?>
                                <div class="operative-mgmt-card">
                                    <div class="mgmt-card-top">
                                        <div class="mgmt-avatar">
                                            <?php echo strtoupper(substr($uname, 0, 1)); ?>
                                        </div>
                                        <div class="mgmt-meta">
                                            <h3>
                                                <?php echo htmlspecialchars($uname); ?>
                                            </h3>
                                            <span class="role-pill"><?php echo htmlspecialchars($urole); ?></span>
                                        </div>
                                    </div>
                                    <div class="mgmt-details">
                                        <div class="mgmt-row">
                                            <span>Sector</span>
                                            <span><?php echo htmlspecialchars($sector); ?></span>
                                        </div>
                                        <div class="mgmt-row">
                                            <span>Status</span>
                                            <span class="text-success">&bull; Active Synchronized</span>
                                        </div>
                                    </div>
                                    <a href="?page=edit_account&target=<?php echo urlencode($uname); ?>" class="btn btn-mgmt-action">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                             <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                        <span>Update Account</span>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-card" style="padding: 48px; text-align: center;">
                            <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 16px;">No accounts match the specified search parameters.</p>
                            <a href="?page=accounts" class="btn btn-secondary">Clear Account Filters</a>
                        </div>
                    <?php endif; ?>

                <?php elseif ($page === 'edit_account'): ?>
                    <!-- Sub-Feature: Account Update & Password Modification -->
                    <?php 
                        $target_user = $_GET['target'] ?? ''; 
                        $t_esc = mysqli_real_escape_string($conn, $target_user);
                        $target_res = mysqli_query($conn, "SELECT role FROM users WHERE session_id = '$session_id' AND username = '$t_esc'");
                        $target_info = $target_res ? mysqli_fetch_assoc($target_res) : null;
                        $target_role = $target_info ? $target_info['role'] : 'Unknown';
                        $target_sector = $sector_map[$target_user] ?? 'Sector Unknown';
                        $is_target_hero = ($target_user === 'Kurapika');
                        $initial = strtoupper(substr($target_user, 0, 1));
                        
                        $nen_parts = explode(' ', $target_role);
                        $nen_affinity = $nen_parts[0] ?? 'Specialist';
                        $target_node_id = 'OP-' . strtoupper(substr(md5($target_user), 0, 4)) . '-YK';
                        $target_ip = '10.240.' . (abs(crc32($target_user)) % 80 + 10) . '.12';
                    ?>
                    <div class="page-title-row">
                        <div>
                            <div class="breadcrumb">
                                <a href="?page=accounts">Account Management</a>
                                <span>/</span>
                                <a href="?page=accounts">Operatives</a>
                                <span>/</span>
                                <span class="breadcrumb-active"><?php echo htmlspecialchars($target_user); ?></span>
                            </div>
                            <h1>Operative Credential Management</h1>
                            <p class="page-subtitle">Security administration &bull; Tactical dossier and authentication override for: <strong><?php echo htmlspecialchars($target_user); ?></strong></p>
                        </div>
                        <a href="?page=accounts" class="btn btn-secondary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width: 16px; height: 16px;">
                                <line x1="19" y1="12" x2="5" y2="12"></line>
                                <polyline points="12 19 5 12 12 5"></polyline>
                            </svg>
                            <span>Return to Directory</span>
                        </a>
                    </div>

                    <div class="edit-dossier-grid">
                        <!-- Left Column: Operative Identity & Intelligence Dossier -->
                        <div class="edit-dossier-sidebar">
                            <div class="edit-avatar-section">
                                <div class="edit-avatar-circle <?php echo $is_target_hero ? 'avatar-hero' : ''; ?>">
                                    <?php echo htmlspecialchars($initial); ?>
                                    <div class="edit-avatar-pulse"></div>
                                </div>
                                <h3 class="edit-operative-name"><?php echo htmlspecialchars($target_user); ?></h3>
                                <span class="role-pill"><?php echo htmlspecialchars($target_role); ?></span>
                                <div class="edit-operative-badges">
                                    <span class="status-badge badge-success">
                                        <span class="status-dot-green"></span>
                                        <span>ONLINE // ACTIVE</span>
                                    </span>
                                    <span class="clearance-tag clearance-standard">CLEARANCE-A</span>
                                </div>
                            </div>

                            <div class="edit-specs-list">
                                <div class="edit-spec-item">
                                    <span class="edit-spec-label">Operative ID</span>
                                    <span class="edit-spec-val"><?php echo htmlspecialchars($target_node_id); ?></span>
                                </div>
                                <div class="edit-spec-item">
                                    <span class="edit-spec-label">Assigned Sector</span>
                                    <span class="edit-spec-val"><?php echo htmlspecialchars($target_sector); ?></span>
                                </div>
                                <div class="edit-spec-item">
                                    <span class="edit-spec-label">Nen Affinity</span>
                                    <span class="edit-spec-val"><?php echo htmlspecialchars($nen_affinity); ?></span>
                                </div>
                                <div class="edit-spec-item">
                                    <span class="edit-spec-label">Terminal Node</span>
                                    <span class="edit-spec-val"><?php echo htmlspecialchars($target_ip); ?></span>
                                </div>
                                <div class="edit-spec-item">
                                    <span class="edit-spec-label">Encryption Cipher</span>
                                    <span class="edit-spec-val">AES-256-GCM</span>
                                </div>
                                <div class="edit-spec-item">
                                    <span class="edit-spec-label">Telemetry Status</span>
                                    <span class="edit-spec-val" style="color: var(--status-green);">99.8% Nominal</span>
                                </div>
                            </div>

                            <div class="edit-intel-callout <?php echo $is_target_hero ? 'callout-hero' : ''; ?>">
                                <div class="edit-intel-callout-header">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="16" x2="12" y2="12"></line>
                                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                    </svg>
                                    <span>SURVEILLANCE DIRECTIVE</span>
                                </div>
                                <p>
                                    Re-keying this operative's authentication passcode immediately commits the new credential to the cluster network. Once updated, root administrators can authenticate as this identity through the primary gateway to assume control of the remote terminal console.
                                </p>
                            </div>
                        </div>

                        <!-- Right Column: Security Passcode Re-Key Terminal Panel -->
                        <div class="edit-form-panel">
                            <div class="edit-form-header">
                                <div class="edit-form-header-badge">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                    </svg>
                                </div>
                                <div class="edit-form-header-titles">
                                    <h2>Passcode Re-Key Protocol</h2>
                                    <p>Deploy updated authentication credentials for account: <strong><?php echo htmlspecialchars($target_user); ?></strong></p>
                                </div>
                            </div>

                            <form method="POST" action="?page=edit_account&target=<?php echo urlencode($target_user); ?>" class="override-form">
                                <input type="hidden" name="target_user" value="<?php echo htmlspecialchars($target_user); ?>">

                                <div class="form-group">
                                    <label class="form-label">Target Operative Identity</label>
                                    <div class="edit-target-badge-box">
                                        <div class="edit-target-left">
                                            <div class="edit-target-dot"></div>
                                            <span class="edit-target-username"><?php echo htmlspecialchars($target_user); ?></span>
                                            <span class="role-pill" style="margin-left: 6px;"><?php echo htmlspecialchars($target_role); ?></span>
                                        </div>
                                        <span class="edit-target-tag">TARGET // LOCKED</span>
                                    </div>
                                </div>

                                <div class="form-group" style="margin-top: 20px;">
                                    <label class="form-label" style="display: flex; justify-content: space-between; align-items: center;">
                                        <span>New Security Passcode</span>
                                        <span style="font-size: 11px; color: var(--text-muted); font-weight: normal;">Alphanumeric clearance string</span>
                                    </label>
                                    <div class="edit-input-wrap">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="edit-input-icon">
                                            <path d="M21 2l-2 2m-1.5 1.5L12 11l-4-4-5 5c0 0 2 2 4 4l-4 4 2 2 4-4c2 2 4 4 4 4l5-5 5.5-5.5"></path>
                                        </svg>
                                        <input type="text" name="new_password" placeholder="Enter updated passcode (e.g. hunter2026, hacked123)..." required class="form-input edit-input-with-icon" autocomplete="off">
                                    </div>
                                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 6px;">
                                        Assign a memorable passcode. This will immediately supersede the existing authentication key in the primary database.
                                    </p>
                                </div>

                                <div class="edit-guidance-checklist">
                                    <div class="edit-guidance-item">
                                        <div class="edit-guidance-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                        </div>
                                        <span>Instantaneous synchronization across all cluster nodes</span>
                                    </div>
                                    <div class="edit-guidance-item">
                                        <div class="edit-guidance-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                        </div>
                                        <span>Immediate console session takeover authorization granted</span>
                                    </div>
                                    <div class="edit-guidance-item">
                                        <div class="edit-guidance-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                        </div>
                                        <span>Logged in cryptographic activity stream as <code>PASSCODE_REKEY</code></span>
                                    </div>
                                </div>

                                <button type="submit" name="change_password" class="btn btn-primary btn-full" style="padding: 14px 20px; font-size: 14.5px;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px; margin-right: 8px;">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                    </svg>
                                    <span>Authorize &amp; Re-Key Credentials</span>
                                </button>
                            </form>

                            <div class="edit-audit-notice">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                </svg>
                                <span>AUDIT COMPLIANCE: Credential override events are permanently recorded in system telemetry.</span>
                            </div>
                        </div>
                    </div>


                <?php elseif ($page === 'intel'): ?>
                    <!-- DEDICATED DATA PAGE: TACTICAL INTEL & NEN ARCHIVE -->
                    <div class="page-title-row">
                        <div>
                            <h1>Tactical Intelligence &amp; Archive</h1>
                            <p class="page-subtitle">Classified underworld dossiers, Nen affinity classifications, and criminal registries.</p>
                        </div>
                        <div class="quick-status-chip">
                            <span class="chip-dot"></span>
                            <span>ARCHIVE CLEARANCE: TIER-0</span>
                        </div>
                    </div>

                    <!-- Section 1: The Iron Doctrines of the Spider -->
                    <div class="table-card">
                        <div class="table-header">
                            <div>
                                <h2>Doctrines &amp; Iron Laws of the Spider</h2>
                                <p>Fundamental operating principles established by Leader Chrollo Lucilfer.</p>
                            </div>
                            <span class="record-count">3 Invariable Principles</span>
                        </div>
                        <div style="padding: 24px 28px; display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                            <div class="panel-box" style="margin: 0; background: rgba(255,255,255,0.02);">
                                <h3 style="color: var(--accent-red); margin-bottom: 8px; font-size: 14.5px;">Doctrine I &mdash; The Head &amp; The Limbs</h3>
                                <p style="color: var(--text-secondary); font-size: 13px; line-height: 1.6;">
                                    "I am the Head, and you are the legs. The limbs follow the head's orders, but should the head perish, another leg will step forward. The survival of the Spider as a whole supersedes the life of any single individual, including the Head."
                                </p>
                            </div>
                            <div class="panel-box" style="margin: 0; background: rgba(255,255,255,0.02);">
                                <h3 style="color: var(--status-amber); margin-bottom: 8px; font-size: 14.5px;">Doctrine II &mdash; The Coin of Discord</h3>
                                <p style="color: var(--text-secondary); font-size: 13px; line-height: 1.6;">
                                    "Serious interpersonal discord between two members of the Troupe shall never be settled through bloodshed. All grievances must be resolved by a coin toss using the Spider's twelve-legged token. The outcome is absolute and irreversible."
                                </p>
                            </div>
                            <div class="panel-box" style="margin: 0; background: rgba(255,255,255,0.02);">
                                <h3 style="color: #38bdf8; margin-bottom: 8px; font-size: 14.5px;">Doctrine III &mdash; Autonomy of the Spider</h3>
                                <p style="color: var(--text-secondary); font-size: 13px; line-height: 1.6;">
                                    "When not summoned for an official operation by the Head, every member maintains sovereign autonomy. Each operative is free to wander the globe and pursue their own desires, provided their actions bring no harm or exposure to the Spider."
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Nen Hexagon Affinity Breakdown -->
                    <div class="table-card">
                        <div class="table-header">
                            <div>
                                <h2>Nen Classification Matrix</h2>
                                <p>Hexagonal affinity mapping, combat roles, and confirmed signature Hatsu abilities.</p>
                            </div>
                            <span class="record-count">Nen Affinity Matrix</span>
                        </div>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th style="width: 160px;">Nen Affinity</th>
                                        <th>Primary Combat Mechanics</th>
                                        <th>Affiliated Operatives</th>
                                        <th>Signature Hatsu Techniques</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><span class="role-pill" style="color: #f472b6; border-color: rgba(244,114,182,0.3); background: rgba(244,114,182,0.1);">Specialization (特質系)</span></td>
                                        <td style="color: var(--text-secondary);">Unique supernatural properties outside standard categories</td>
                                        <td><strong>Chrollo Lucilfer</strong>, <strong>Meruem</strong>, <strong>Neferpitou</strong></td>
                                        <td style="color: var(--text-muted); font-family: 'JetBrains Mono', monospace; font-size: 12.5px;">Skill Hunter &bull; Aura Synthesis &bull; Terpsichora Puppet</td>
                                    </tr>
                                    <tr>
                                        <td><span class="role-pill" style="color: #60a5fa; border-color: rgba(96,165,250,0.3); background: rgba(96,165,250,0.1);">Enhancement (強化系)</span></td>
                                        <td style="color: var(--text-secondary);">Direct amplification of organic physical strength and brute impact</td>
                                        <td><strong>Uvogin</strong></td>
                                        <td style="color: var(--text-muted); font-family: 'JetBrains Mono', monospace; font-size: 12.5px;">Big Bang Impact (Full Power Bunker Buster)</td>
                                    </tr>
                                    <tr>
                                        <td><span class="role-pill" style="color: #c084fc; border-color: rgba(192,132,252,0.3); background: rgba(192,132,252,0.1);">Transmutation (変化系)</span></td>
                                        <td style="color: var(--text-secondary);">Altering aura properties into thermal energy or adhesive matter</td>
                                        <td><strong>Feitan Portor</strong>, <strong>Hisoka Morow</strong></td>
                                        <td style="color: var(--text-muted); font-family: 'JetBrains Mono', monospace; font-size: 12.5px;">Pain Packer / Rising Sun &bull; Bungee Gum &bull; Texture Surprise</td>
                                    </tr>
                                    <tr>
                                        <td><span class="role-pill" style="color: #fbbf24; border-color: rgba(251,191,36,0.3); background: rgba(251,191,36,0.1);">Manipulation (操作系)</span></td>
                                        <td style="color: var(--text-secondary);">Total neuromuscular and mental command over organic targets</td>
                                        <td><strong>Illumi Zoldyck</strong>, <strong>Shaiapouf</strong></td>
                                        <td style="color: var(--text-muted); font-family: 'JetBrains Mono', monospace; font-size: 12.5px;">Needle Hypnosis &bull; Spiritual Message Hypnotic Scales</td>
                                    </tr>
                                    <tr>
                                        <td><span class="role-pill" style="color: #38bdf8; border-color: rgba(56, 189, 248, 0.3); background: rgba(56, 189, 248, 0.1);">Conjuration (具現化系)</span></td>
                                        <td style="color: var(--text-secondary);">Condensation of aura into persistent physical objects with binding vows</td>
                                        <td><strong>Kurapika</strong>, <strong>Genthru</strong></td>
                                        <td style="color: var(--text-muted); font-family: 'JetBrains Mono', monospace; font-size: 12.5px;">Judgment Chain &bull; Chain Jail &bull; Countdown Bomb</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Section 3: Hunter Association Wanted Ledger -->
                    <div class="table-card">
                        <div class="table-header">
                            <div>
                                <h2>Hunter Association Wanted Ledger &amp; Bounty Index</h2>
                                <p>Recorded Class-A criminal bounties officially posted by the International Hunter Committee.</p>
                            </div>
                            <span class="record-count">Class-A Threat Ledger</span>
                        </div>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Target Operative</th>
                                        <th>Classification</th>
                                        <th>Threat Tier</th>
                                        <th>Assigned Sector</th>
                                        <th style="text-align: right;">Bounty Reward (Jenny)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>Meruem</strong></td>
                                        <td>Chimera Ant King</td>
                                        <td><span class="clearance-tag clearance-leader">CALAMITY-TIER</span></td>
                                        <td class="mono-sector">Sector East Gorteau</td>
                                        <td style="text-align: right; font-family: 'JetBrains Mono', monospace; color: #facc15; font-weight: 700;">฿ 5,000,000,000</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Chrollo Lucilfer</strong></td>
                                        <td>Spider Leader &bull; Brigade Head</td>
                                        <td><span class="clearance-tag clearance-leader">CLASS-A (SUPREME)</span></td>
                                        <td class="mono-sector">Sector 00 (Headquarters)</td>
                                        <td style="text-align: right; font-family: 'JetBrains Mono', monospace; color: #facc15; font-weight: 700;">฿ 3,200,000,000</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Neferpitou</strong></td>
                                        <td>Ant Royal Guard</td>
                                        <td><span class="clearance-tag clearance-leader">CALAMITY-TIER</span></td>
                                        <td class="mono-sector">Sector Peijin (En 2km)</td>
                                        <td style="text-align: right; font-family: 'JetBrains Mono', monospace; color: #facc15; font-weight: 700;">฿ 2,800,000,000</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Shaiapouf</strong></td>
                                        <td>Ant Royal Guard</td>
                                        <td><span class="clearance-tag clearance-leader">CALAMITY-TIER</span></td>
                                        <td class="mono-sector">Sector Peijin (Throne)</td>
                                        <td style="text-align: right; font-family: 'JetBrains Mono', monospace; color: #facc15; font-weight: 700;">฿ 2,400,000,000</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Feitan Portor</strong></td>
                                        <td>Interrogation Specialist</td>
                                        <td><span class="clearance-tag clearance-standard">CLASS-A</span></td>
                                        <td class="mono-sector">Sector 02 (Interrogation)</td>
                                        <td style="text-align: right; font-family: 'JetBrains Mono', monospace; color: #facc15; font-weight: 700;">฿ 1,850,000,000</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Hisoka Morow</strong></td>
                                        <td>Frontline Duelist</td>
                                        <td><span class="status-badge badge-warning"><span class="status-dot-amber"></span> ROGUE</span></td>
                                        <td class="mono-sector">Sector ?? (Untracked)</td>
                                        <td style="text-align: right; font-family: 'JetBrains Mono', monospace; color: #facc15; font-weight: 700;">฿ 1,600,000,000</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Illumi Zoldyck</strong></td>
                                        <td>Zoldyck Assassin</td>
                                        <td><span class="clearance-tag clearance-standard">CLASS-A</span></td>
                                        <td class="mono-sector">Sector Kukuroo</td>
                                        <td style="text-align: right; font-family: 'JetBrains Mono', monospace; color: #facc15; font-weight: 700;">฿ 1,500,000,000</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Uvogin</strong></td>
                                        <td>Brigade Enforcer</td>
                                        <td><span class="clearance-tag clearance-standard">CLASS-A</span></td>
                                        <td class="mono-sector">Sector Gaba</td>
                                        <td style="text-align: right; font-family: 'JetBrains Mono', monospace; color: #facc15; font-weight: 700;">฿ 1,200,000,000</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Genthru</strong></td>
                                        <td>The Bomber</td>
                                        <td><span class="clearance-tag clearance-standard">CLASS-B</span></td>
                                        <td class="mono-sector">Sector Greed Island</td>
                                        <td style="text-align: right; font-family: 'JetBrains Mono', monospace; color: #facc15; font-weight: 700;">฿ 800,000,000</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Kurapika</strong></td>
                                        <td>Nostrade Escort</td>
                                        <td><span class="clearance-tag clearance-standard">CLASS-A</span></td>
                                        <td class="mono-sector">Sector Nostrade</td>
                                        <td style="text-align: right; font-family: 'JetBrains Mono', monospace; color: #facc15; font-weight: 700;">฿ 1,250,000,000</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- OPERATIVE / INFILTRATOR DOSSIER VIEW (When standard non-admin user logs in) -->
                <?php $is_hero_logged_in = ($current_user === 'Kurapika'); ?>
                <div class="page-title-row">
                    <div>
                        <div class="system-badge inline-badge <?php echo $is_hero_logged_in ? 'badge-hero' : ''; ?>">
                            <span class="<?php echo $is_hero_logged_in ? 'status-dot-hero' : 'live-indicator'; ?>"></span>
                            <span><?php echo $is_hero_logged_in ? 'UNDERCOVER INFILTRATION TERMINAL' : 'SYNDICATE OPERATIVE TERMINAL'; ?></span>
                        </div>
                        <h1><?php echo $is_hero_logged_in ? 'Infiltrator Dossier: Kurapika (Kurta Clan)' : 'Operative Dossier: ' . htmlspecialchars($current_user); ?></h1>
                        <p class="page-subtitle"><?php echo $is_hero_logged_in ? 'Undercover Blacklist Hunter &bull; Chain User Telemetry' : 'Phantom Troupe Classified Member Dossier &bull; Sector Surveillance'; ?></p>
                    </div>
                    <div class="quick-status-chip">
                        <span class="chip-dot"></span>
                        <span><?php echo $is_hero_logged_in ? 'CLEARANCE: PRO HUNTER' : 'CLEARANCE: TIER-3'; ?></span>
                    </div>
                </div>

                <div class="dossier-grid">
                    <!-- Left: Personnel Identification Card -->
                    <div class="dossier-profile-card <?php echo $is_hero_logged_in ? 'card-hero' : ''; ?>">
                        <div class="dossier-avatar-large <?php echo $is_hero_logged_in ? 'avatar-hero-lg' : ''; ?>">
                            <?php echo strtoupper(substr($current_user, 0, 1)); ?>
                        </div>
                        <h2 class="<?php echo $is_hero_logged_in ? 'text-hero' : ''; ?>">
                            <?php echo htmlspecialchars($current_user); ?>
                            <?php if ($is_hero_logged_in): ?>
                                <span class="hero-chip">INFILTRATOR</span>
                            <?php endif; ?>
                        </h2>
                        <span class="role-pill pill-large <?php echo $is_hero_logged_in ? 'pill-hero' : ''; ?>"><?php echo htmlspecialchars($role); ?></span>

                        <div class="dossier-attributes">
                            <div class="attr-row">
                                <span class="attr-name">Origin</span>
                                <span class="attr-val"><?php echo $is_hero_logged_in ? 'Lukso Province (Kurta Clan)' : 'Meteor City'; ?></span>
                            </div>
                            <div class="attr-row">
                                <span class="attr-name">Clearance</span>
                                <span class="attr-val <?php echo $is_hero_logged_in ? 'text-hero' : 'text-success'; ?>">
                                    <?php echo $is_hero_logged_in ? 'Undercover Blacklist Hunter' : 'Standard Syndicate Operative'; ?>
                                </span>
                            </div>
                            <div class="attr-row">
                                <span class="attr-name"><?php echo $is_hero_logged_in ? 'Affiliation' : 'Threat Registry'; ?></span>
                                <span class="attr-val <?php echo $is_hero_logged_in ? 'text-hero' : 'text-warning'; ?>">
                                    <?php echo $is_hero_logged_in ? 'Hunter Association License #404' : 'Class-A Wanted Target'; ?>
                                </span>
                            </div>
                            <div class="attr-row">
                                <span class="attr-name">Assigned Sector</span>
                                <span class="attr-val"><?php echo htmlspecialchars($sector_map[$current_user] ?? 'Sector 09'); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Mission Directives & System Anomaly/Memory Alert -->
                    <div class="dossier-content-column">
                        <div class="panel-box <?php echo $is_hero_logged_in ? 'card-hero' : ''; ?>">
                            <div class="panel-box-header">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 11 12 14 22 4"></polyline>
                                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                                </svg>
                                <h3><?php echo $is_hero_logged_in ? 'Hunter Mission Directives' : 'Assigned Operative Directives'; ?></h3>
                            </div>
                            <ul class="directives-list">
                                <?php if ($is_hero_logged_in): ?>
                                    <li>
                                        <span class="bullet-priority text-hero">&bull;</span>
                                        <div>
                                            <strong>Primary Infiltration Vow:</strong> Recover the Scarlet Eyes of the Kurta Clan from the Yorknew underworld syndicate.
                                        </div>
                                    </li>
                                    <li>
                                        <span class="bullet-priority text-hero">&bull;</span>
                                        <div>
                                            <strong>Stealth Protocol:</strong> Maintain undetected cover inside the syndicate network until the auction starts.
                                        </div>
                                    </li>
                                    <li>
                                        <span class="bullet-priority text-hero">&bull;</span>
                                        <div>
                                            <strong>Intel Exfiltration:</strong> Transmit the captured syndicate memory buffer via the temporary socket.
                                        </div>
                                    </li>
                                <?php else: ?>
                                    <li>
                                        <span class="bullet-priority">&bull;</span>
                                        <div>
                                            <strong>Priority Direct:</strong> Maintain radio silence until the underground auction begins.
                                        </div>
                                    </li>
                                    <li>
                                        <span class="bullet-priority">&bull;</span>
                                        <div>
                                            <strong>Target Elimination:</strong> Eliminate any Hunter Association infiltrators attempting to breach the perimeter.
                                        </div>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>

                        <div class="panel-box <?php echo $is_hero_logged_in ? 'card-hero' : ''; ?>">
                            <div class="panel-box-header">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                                <h3>Telemetry &amp; Active Anomalies</h3>
                            </div>

                            <?php if ($is_hero_logged_in): ?>
                                <!-- CTF MECHANIC: Hidden Flag Request (instantly aborted so it only appears in proxy capture) -->
                                <script>
                                    const xhr = new XMLHttpRequest();
                                    xhr.open('POST', 'temp_flag_check.php', true);
                                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                                    xhr.send('action=extract_memory');
                                    xhr.abort(); 
                                </script>

                                <div class="anomaly-alert-card">
                                    <div class="anomaly-icon anomaly-icon-hero">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-hero">Ephemeral Background Transmission Detected</h4>
                                        <p>A stealth telemetry routine is dispatching a temporary flag verification check in the background. The transmission drops mid-flight—intercept the fleeting outbound traffic to capture what was broadcast.</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p class="text-muted" style="font-size: 14px;">No active signal anomalies detected. Operative channel operating at nominal network frequencies.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
