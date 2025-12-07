<?php
include 'includes/functions.php';
if (is_logged_in()) {
    redirect('dashboard.php');
} else {
    redirect('login.php');
}
?>