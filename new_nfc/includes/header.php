<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance System</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" 
          rel="stylesheet" />

    <style>
/* ==============================
   RESPONSIVE SIDEBAR FIX
   (THE REAL FIX YOU NEEDED)
================================*/

/* Hide hamburger on desktop */
#menuBtn {
    display: none;
}

/* MOBILE MODE */
@media (max-width: 768px) {

    /* Show hamburger */
    #menuBtn {
        display: block;
        position: fixed;
        top: 12px;
        left: 12px;
        z-index: 9999;
        background: #7c3aed;
        color: white;
        padding: 10px 11px;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.25);
    }

    /* Sidebar disappears off-screen */
    aside.fixed {
        transform: translateX(-260px);
        width: 240px !important;
        transition: 0.3s ease;
    }

    /* When active: slide in */
    aside.fixed.open {
        transform: translateX(0);
    }

    /* Main area gets full width */
    main {
        margin-left: 0 !important;
        margin-right: 0 !important;
        padding: 12px !important;
    }
}

/* SMALL PHONES FULL FIX */
@media (max-width: 480px) {
    aside.fixed {
        width: 200px !important;
    }
}
@media (max-width: 1024px) {
    main {
        margin-right: 0 !important;
        margin-left: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }
}
@media (max-width: 768px) {
    main[class*="mr-72"] {
        margin-right: 0 !important;
    }
}

    </style>

</head>

<body class="bg-gray-100">

<!-- MOBILE HAMBURGER BUTTON -->
<button id="menuBtn">
    <i class="fas fa-bars text-xl"></i>
</button>

<!-- LEFT SIDEBAR -->
<aside class="fixed left-0 top-0 h-full mt-2 w-64 bg-white shadow-xl rounded-r-3xl p-6 flex flex-col z-50 pl-3">

    <!-- LOGO -->
    <div class="flex items-center space-x-2 mb-8 border-b pb-4">
        <img src="../image/modern.jpg" class="w-12 h-12 rounded-full border p-1 shadow">
        <h2 class="font-bold text-xl text-gray-800 leading-tight">
            Modern College<br>Of Engineering
        </h2>
    </div>

    <!-- NAVIGATION -->
    <nav class="flex flex-col space-y-4 text-lg font-medium">

        <a href="../index.php"
           class="flex items-center space-x-1 p-2 rounded-xl hover:bg-gray-100 transition text-gray-900">
            <img src="../image/home.png" class="w-7 h-7 opacity-90">
            <span>Home</span>
        </a>

        <?php if (is_logged_in()): ?>
        <a href="<?php echo is_admin()
            ? '../dashboards/admin_dashboard.php'
            : (is_teacher()
                ? '../dashboards/teacher_dashboard.php'
                : '../dashboards/student_dashboard.php'); ?>"
           class="flex items-center space-x-2 p-2 rounded-xl hover:bg-gray-100 transition text-gray-900">
            <img src="../image/dashboard.png" class="w-7 h-7 opacity-90">
            <span>Dashboard</span>
        </a>

        <a href="../logout.php"
           class="flex items-center space-x-2 p-2 rounded-xl hover:bg-gray-100 transition text-gray-900">
            <img src="../image/log.png" class="w-7 h-7 opacity-90">
            <span>Login</span>
        </a>
        <?php endif; ?>

    </nav>

    <!-- LOGOUT BUTTON -->
    <div class="mt-auto pt-6">
        <a href="../logout.php"
           class="flex items-center space-x-2 p-2 rounded-xl hover:bg-red-100 transition text-red-600 font-semibold text-lg">
            <img src="../image/logout.png" class="w-7 h-7">
            <span>Logout</span>
        </a>
    </div>

</aside>

<!-- MAIN CONTENT WRAPPER -->
<main class="ml-60 p-4 <?php echo isset($rightSidebar) && $rightSidebar ? 'mr-72' : ''; ?>">

<!-- JAVASCRIPT -->
<script>
document.getElementById("menuBtn").onclick = () => {
    document.querySelector("aside.fixed").classList.toggle("open");
};
</script>
