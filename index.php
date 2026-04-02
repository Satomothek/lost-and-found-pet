<?php
/**
 * PetFounds - Landing Page / Redirect
 * Auto-redirect ke halaman login
 */

// If already logged in, go to dashboard
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: pages/post_report.php');
    exit;
}

// Otherwise show landing page or redirect to login
header('Location: pages/login.php');
exit;

?>
