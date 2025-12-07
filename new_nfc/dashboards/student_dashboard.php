<?php
include '../includes/db.php';
include '../includes/functions.php';
if (!is_student()) redirect('../login.php');

include '../includes/header.php';
?>

<div class="p-6">
    <h1 class="text-3xl font-bold text-gray-100 mb-4">Student Dashboard</h1>

    <div class="bg-gradient-to-r from-violet-600 to-indigo-600 p-6 rounded-xl shadow-lg text-white mb-6">
        <p class="text-xl font-semibold">
            Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
        </p>
        <p class="mt-2 text-gray-100">
            Ask your teacher to mark your attendance.
        </p>
    </div>

    <div class="bg-gray-800 p-6 rounded-xl shadow-lg text-gray-200">
        <h2 class="text-xl font-semibold mb-2 text-indigo-300">How Attendance Works</h2>
        <p class="text-gray-300">
            Attendance is marked via a secure link on your teacher's device.
        </p>
        <p class="mt-1 text-gray-400 italic">
            Example: <span class="text-indigo-400">?student_id=your_id</span>
        </p>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
