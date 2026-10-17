<?php
session_start();

// Disable error reporting to prevent Error-Based SQLi data leaks
error_reporting(0);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_OFF);

$host = 'db';
$db   = 'hxh_db';
$user = 'root';
$pass = 'root123';

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$session_id = session_id();

// Ensure activity_logs table exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100) NOT NULL,
    logged_time VARCHAR(20) NOT NULL,
    event_tag VARCHAR(50) NOT NULL,
    message TEXT NOT NULL
)");

function add_activity_log($conn, $session_id, $tag, $message) {
    if (!$conn || empty($session_id)) return;
    $time = date('H:i:s');
    $tag_esc = mysqli_real_escape_string($conn, $tag);
    $msg_esc = mysqli_real_escape_string($conn, $message);
    mysqli_query($conn, "INSERT INTO activity_logs (session_id, logged_time, event_tag, message) VALUES ('$session_id', '$time', '$tag_esc', '$msg_esc')");
}

// Check if this session already has the clean roster initialized
$check_query = "SELECT count(*) as c FROM users WHERE session_id = '$session_id' AND username = 'Kurapika' AND role LIKE '%Infiltrator%'";
$check_result = mysqli_query($conn, $check_query);
$row = $check_result ? mysqli_fetch_assoc($check_result) : null;

if (!$row || $row['c'] == 0) {
    // Clear out any old session records and seed the roster: Syndicate Operatives + Infiltrator (Kurapika)
    mysqli_query($conn, "DELETE FROM users WHERE session_id = '$session_id'");
    mysqli_query($conn, "DELETE FROM activity_logs WHERE session_id = '$session_id'");

    $users = [
        ['admin', 'super_secret_leader_pass', 'System Administrator'],
        ['Chrollo', 'spider_head_1', 'Specialist (Brigade Head)'],
        ['Meruem', 'ant_king_2', 'Specialist (Chimera King)'],
        ['Hisoka', 'bungee_gum_3', 'Transmuter (Rogue)'],
        ['Illumi', 'needle_man_4', 'Manipulator (Assassin)'],
        ['Feitan', 'pain_packer_5', 'Transmuter (Brigade #2)'],
        ['Genthru', 'countdown_bomber_6', 'Conjurer (Bomber)'],
        ['Neferpitou', 'terpsichora_7', 'Specialist (Royal Guard)'],
        ['Shaiapouf', 'spiritual_message_8', 'Manipulator (Royal Guard)'],
        ['Uvogin', 'big_bang_impact_9', 'Enhancer (Brigade #11)'],
        ['Kurapika', 'chain_jail_infiltrator_10', 'Conjurer (Infiltrator)']
    ];

    foreach ($users as $u) {
        $username = mysqli_real_escape_string($conn, $u[0]);
        $password = mysqli_real_escape_string($conn, $u[1]);
        $role = mysqli_real_escape_string($conn, $u[2]);
        mysqli_query($conn, "INSERT INTO users (session_id, username, password, role) VALUES ('$session_id', '$username', '$password', '$role')");
    }
}
?>
