<?php
/**
 * Logout Page
 * Destroys session and redirects to login page
 */

require_once '../lib/auth.php';

logout();
header('Location: login.php');
exit;
?>
