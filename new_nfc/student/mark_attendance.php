<?php
include '../includes/db.php';
include '../includes/functions.php';

$success = $error = null;

// Check if teacher session exists and has an active lecture
if (!isset($_SESSION['active_lecture_id']) || !is_teacher()) {
    $error = "No active class—teacher must be logged in and have an active lecture.";
} else {
    // Handle GET for URL/NFC (auto-mark with student_id)
    if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['student_id'])) {
        $student_id = $_GET['student_id'];
        $lecture_id = $_SESSION['active_lecture_id'];
        
        // Verify lecture is active
        $check_lecture = $pdo->prepare("SELECT a.class_id FROM lectures l JOIN assignments a ON l.assignment_id = a.id WHERE l.id = ? AND l.end_time IS NULL");
        $check_lecture->execute([$lecture_id]);
        $lecture_class = $check_lecture->fetchColumn();
        
        if (!$lecture_class) {
            $error = "Invalid or ended lecture";
        } else {
            // Get student's class
            $check_student = $pdo->prepare("SELECT class_id FROM students WHERE id = ?");
            $check_student->execute([$student_id]);
            $student_class = $check_student->fetchColumn();
            
            if (!$student_class) {
                $error = "Invalid student ID";
            } elseif ($student_class != $lecture_class) {
                $error = "Student not in this lecture's class";
            } else {
                // Check duplicate
                $check_duplicate = $pdo->prepare("SELECT id FROM attendances WHERE lecture_id = ? AND student_id = ?");
                $check_duplicate->execute([$lecture_id, $student_id]);
                if (!$check_duplicate->fetch()) {
                    $stmt = $pdo->prepare("INSERT INTO attendances (lecture_id, student_id) VALUES (?, ?)");
                    $stmt->execute([$lecture_id, $student_id]);
                    $success = "Attendance marked for student $student_id in lecture $lecture_id";
                    header("Refresh:2; url=../dashboard.php"); // Back to teacher's dashboard
                } else {
                    $error = "Already marked for this lecture";
                }
            }
        }
    }
}

// Optional: Keep GET form if manual needed (e.g., teacher enters student_id)
if ($_SERVER['REQUEST_METHOD'] == 'GET' && !isset($_GET['student_id'])) {
    // No action needed—form below handles manual input
}

include '../includes/header.php';
?>
<!-- PAGE WRAPPER -->
<div class="flex justify-center mt-10 px-4">

    <!-- MAIN CARD -->
    <div class="w-full max-w-3xl bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">

        <!-- CARD HEADER -->
        <div class="bg-gradient-to-r from-violet-600 to-violet-500 p-8 text-white">
            <h1 class="text-3xl font-bold">Mark Attendance</h1>
            <p class="text-white/80 text-sm mt-1">
                URL-based attendance marking for students.
            </p>
        </div>

        <!-- CARD BODY -->
        <div class="p-8">

            <!-- SUCCESS -->
            <?php if (isset($success)): ?>
                <div class="bg-green-100 text-green-800 border border-green-300 p-3 rounded-xl mb-4">
                    <?= $success ?>
                </div>
            <?php endif; ?>

            <!-- ERROR -->
            <?php if (isset($error)): ?>
                <div class="bg-red-100 text-red-800 border border-red-300 p-3 rounded-xl mb-4">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <p class="text-gray-600 mb-6">
                Paste URL like <span class="font-mono bg-gray-100 px-2 py-1 rounded">?student_id=5</span>  
                to mark attendance (must be logged in & active lecture running).
            </p>

            <!-- FORM -->
            <form method="GET" class="space-y-6">

                <label class="block text-gray-700 font-semibold text-lg">
                    Student ID
                </label>

                <input type="text" name="student_id"
                    placeholder="Enter Student ID"
                    class="w-full p-3 rounded-2xl border border-gray-300 bg-gray-50 text-gray-800
                           focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition" required>

                <button type="submit"
                    class="w-full bg-violet-600 hover:bg-violet-700 text-white text-lg py-3 rounded-2xl shadow-md transition font-semibold">
                    Mark Manually
                </button>

            </form>

        </div>

    </div>

</div>

<?php include '../includes/footer.php'; ?>