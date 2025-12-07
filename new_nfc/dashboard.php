<?php
include 'includes/db.php';
include 'includes/functions.php';
if (!is_logged_in()) redirect('login.php');

if (is_admin()) {
    redirect('dashboards/admin_dashboard.php');
} elseif (is_teacher()) {
    redirect('dashboards/teacher_dashboard.php');
} elseif (is_student()) {
    redirect('dashboards/student_dashboard.php');
} else {
    redirect('login.php');
}
?>