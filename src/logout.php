<?php
require 'db.php';

if (isset($_SESSION['logged_in_user'])) {
    $u = $_SESSION['logged_in_user'];
    add_activity_log($conn, $session_id, 'SESSION_CLOSE', "Operative '{$u}' terminated active terminal session.");
    unset($_SESSION['logged_in_user']);
}

header("Location: index.php");
exit();
?>
