<?php
require 'db.php';

if (!isset($_SESSION['logged_in_user'])) {
    die("Unauthorized");
}

if ($_SESSION['logged_in_user'] === 'Kurapika' && isset($_POST['action']) && $_POST['action'] === 'extract_memory') {
    // This is the flag!
    // Since the frontend aborts the request, this won't show in browser dev tools easily,
    // but a proxy like Burp Suite will intercept the full response.
    echo "SSP{WEB_SQLI_71FA92CD}";
} else {
    echo "No memories found.";
}
?>
