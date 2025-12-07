<?php
include '../includes/db.php';
include '../includes/functions.php';
if (!is_teacher()) redirect('../dashboard.php');
$rightSidebar = false;

$teacher_id = get_teacher_id($pdo, $_SESSION['user_id']);
$success = $error = null;

/* -----------------------------------------
   AUTO-START VIA GET REQUEST
------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] == 'GET') {

    if (isset($_GET['assignment_id'])) {

        $assignment_id = $_GET['assignment_id'];

        $stmt = $pdo->prepare("SELECT id FROM assignments WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$assignment_id, $teacher_id]);

        if ($stmt->fetch()) {

            $stmt = $pdo->prepare("INSERT INTO lectures (assignment_id, start_time) VALUES (?, NOW())");
            $stmt->execute([$assignment_id]);

            $lecture_id = $pdo->lastInsertId();
            $_SESSION['active_lecture_id'] = $lecture_id;

            $success = "Lecture started. ID: $lecture_id";
            header("Refresh:2; url=../dashboard.php");

        } else {
            $error = "Invalid assignment for this teacher";
        }
    }

    elseif (isset($_GET['class_id'])) {

        $class_id = $_GET['class_id'];

        $stmt = $pdo->prepare("SELECT id FROM assignments WHERE teacher_id = ? AND class_id = ?");
        $stmt->execute([$teacher_id, $class_id]);
        $assignments_in_class = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($assignments_in_class) == 1) {

            $assignment_id = $assignments_in_class[0];

            $stmt = $pdo->prepare("INSERT INTO lectures (assignment_id, start_time) VALUES (?, NOW())");
            $stmt->execute([$assignment_id]);

            $lecture_id = $pdo->lastInsertId();
            $_SESSION['active_lecture_id'] = $lecture_id;

            $success = "Lecture started in class $class_id. ID: $lecture_id";
            header("Refresh:2; url=../dashboard.php");

        } elseif (count($assignments_in_class) == 0) {
            $error = "No assignments for you in this class";
        } else {
            $error = "Multiple subjects found — choose one below.";
        }
    }
}

/* -----------------------------------------
   MANUAL START VIA POST
------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $assignment_id = $_POST['assignment_id'];

    $stmt = $pdo->prepare("INSERT INTO lectures (assignment_id, start_time) VALUES (?, NOW())");
    $stmt->execute([$assignment_id]);

    $lecture_id = $pdo->lastInsertId();
    $_SESSION['active_lecture_id'] = $lecture_id;

    $success = "Lecture started. ID: $lecture_id";
}

/* -----------------------------------------
   FETCH ASSIGNMENTS
------------------------------------------ */
$stmt = $pdo->prepare("
    SELECT a.id, s.name AS subject, c.name AS class
    FROM assignments a
    JOIN subjects s ON a.subject_id = s.id
    JOIN classes c ON a.class_id = c.id
    WHERE teacher_id = ?
");
$stmt->execute([$teacher_id]);
$assignments = $stmt->fetchAll();

include '../includes/header.php';
?>

<!-- FULL WIDTH HEADER WITH SMALL LEFT SPACE -->

  

<!-- PAGE HEADER -->

<!-- PAGE HEADER -->
<!-- PAGE WRAPPER (after sidebar) -->
<!-- PAGE WRAPPER -->
<!-- PAGE WRAPPER -->
<div class="flex justify-center mt-10 px-4">

    <!-- MAIN CARD (smaller width) -->
    <div class="w-full max-w-lg bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">

        <!-- CARD HEADER -->
        <div class="bg-gradient-to-r from-violet-600 to-violet-500 p-8 text-white">
            <h1 class="text-3xl font-bold">Start Lecture</h1>
            <p class="text-white/80 text-sm mt-1">
                Choose your subject and begin your lecture.
            </p>
        </div>

        <!-- CARD BODY -->
        <div class="p-8">

            <?php if ($success): ?>
                <div class="bg-green-100 text-green-800 border border-green-300 p-3 rounded-xl mb-4">
                    <?= $success ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-100 text-red-800 border border-red-300 p-3 rounded-xl mb-4">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">

                <label class="block text-gray-800 font-semibold text-lg">Select Subject</label>

                <!-- DROPDOWN WITH THEME -->
                <div class="relative">
                    <select 
                        name="assignment_id"
                        class="w-full p-3 rounded-2xl border border-violet-300 bg-violet-50 text-gray-900 
                               focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition 
                               cursor-pointer shadow-sm appearance-none">
                        <?php foreach ($assignments as $a): ?>
                            <option value="<?= $a['id'] ?>">
                                <?= $a['subject'] ?> — <?= $a['class'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <i class="fas fa-chevron-down absolute right-4 top-4 text-violet-600"></i>
                </div>

                <button type="submit"
                    class="w-full bg-violet-600 hover:bg-violet-700 text-white text-lg py-3 rounded-2xl shadow-md transition font-semibold">
                    Start Lecture
                </button>

            </form>
        </div>

    </div>

</div>



<?php include '../includes/footer.php'; ?>
