<?php
include '../includes/db.php';
include '../includes/functions.php';
if (!is_teacher()) redirect('../dashboard.php');

$teacher_id = get_teacher_id($pdo, $_SESSION['user_id']);



$active_lectures = $pdo->prepare("SELECT l.id, s.name as subject, c.name as class FROM lectures l JOIN assignments a ON l.assignment_id = a.id JOIN subjects s ON a.subject_id = s.id JOIN classes c ON a.class_id = c.id WHERE a.teacher_id = ? AND l.end_time IS NULL");
$active_lectures->execute([$teacher_id]);
$active_lectures = $active_lectures->fetchAll();

$success = $error = null;

// Handle GET for NFC/URL (auto-end specific lecture)
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['lecture_id'])) {
    $lecture_id = $_GET['lecture_id'];
    // Verify lecture belongs to teacher and is active
    $stmt = $pdo->prepare("SELECT l.id FROM lectures l JOIN assignments a ON l.assignment_id = a.id WHERE l.id = ? AND a.teacher_id = ? AND l.end_time IS NULL");
    $stmt->execute([$lecture_id, $teacher_id]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("UPDATE lectures SET end_time = NOW() WHERE id = ?");
        $stmt->execute([$lecture_id]);
        unset($_SESSION['active_lecture_id']); // Clear active lecture from session
        $success = "Lecture $lecture_id ended";
        header("Refresh:2; url=../dashboard.php");
    } else {
        $error = "Invalid or already ended lecture";
    }
}

// Handle POST from form (manual)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $lecture_id = $_POST['lecture_id'];
    $stmt = $pdo->prepare("UPDATE lectures SET end_time = NOW() WHERE id = ?");
    $stmt->execute([$lecture_id]);
    unset($_SESSION['active_lecture_id']); // Clear active lecture from session
    $success = "Lecture ended";
}

include '../includes/header.php';
?>
<!-- PAGE WRAPPER -->
<div class="flex justify-center mt-10 px-4">

    <!-- MAIN CARD -->
    <div class="w-full max-w-lg bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">

        <!-- CARD HEADER -->
        <div class="bg-gradient-to-r from-violet-600 to-violet-500 p-8 text-white">
            <h1 class="text-3xl font-bold">End Lecture</h1>
            <p class="text-white/80 text-sm mt-1">
                Select an active lecture to end it.
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

            <!-- FORM -->
            <form method="POST" class="space-y-6">

                <label class="block text-gray-700 font-semibold text-lg">Active Lectures</label>

                <div class="relative">
                    <select name="lecture_id"
                        class="w-full p-3 rounded-2xl border border-violet-300 bg-violet-50 text-gray-900
                               focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition 
                               cursor-pointer shadow-sm appearance-none" required>
                        <?php foreach ($active_lectures as $l): ?>
                            <option value="<?= $l['id']; ?>">
                                <?= $l['subject'] . ' - ' . $l['class']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <i class="fas fa-chevron-down absolute right-4 top-4 text-violet-600"></i>
                </div>

                <button type="submit"
    class="w-full bg-violet-500 hover:bg-violet-400 text-white text-lg py-3 rounded-2xl shadow-md transition font-semibold">
    End Lecture
</button>


            </form>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>