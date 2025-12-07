<?php
include '../includes/db.php';
include '../includes/functions.php';
if (!is_admin()) redirect('../dashboard.php');

$teachers = $pdo->query("SELECT id, name FROM teachers")->fetchAll();
$classes = $pdo->query("SELECT id, name FROM classes")->fetchAll();
$subjects = $pdo->query("SELECT id, name FROM subjects")->fetchAll();

$success = null;

// Handle POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if (isset($_POST['create_class'])) {
        $stmt = $pdo->prepare("INSERT INTO classes (name) VALUES (?)");
        $stmt->execute([$_POST['class_name']]);
        $success = "Class created successfully!";
    
    } elseif (isset($_POST['create_subject'])) {
        $stmt = $pdo->prepare("INSERT INTO subjects (name) VALUES (?)");
        $stmt->execute([$_POST['subject_name']]);
        $success = "Subject created successfully!";
    
    } else {
        $teacher_id = $_POST['teacher_id'];
        $subject_id = $_POST['subject_id'];
        $class_id  = $_POST['class_id'];

        $stmt = $pdo->prepare("INSERT INTO assignments (teacher_id, subject_id, class_id) VALUES (?, ?, ?)");
        $stmt->execute([$teacher_id, $subject_id, $class_id]);

        $success = "Subject assigned successfully!";
    }
}

include '../includes/header.php';
?>



<div class="p-2 -mt-3 w-full pr-0">

    <!-- Main Card -->
    <div class="max-w-full bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">

        <!-- HEADER -->
        <div class="bg-gradient-to-r from-violet-600 to-violet-500 p-8 text-white">
            <h1 class="text-3xl font-bold">Assign Subject</h1>
            <p class="text-white/80 text-sm mt-1">Assign subjects, create classes & add new subjects</p>
        </div>

        <!-- BODY -->
        <div class="p-8 space-y-10">

            <!-- SUCCESS MESSAGE -->
<?php if ($success): ?>
<div class="flex items-center gap-3 p-4 mb-4 rounded-xl shadow-lg 
            bg-gradient-to-r from-green-500 to-green-600 text-white animate-fade-in">
    
    <i class="fas fa-check-circle text-2xl"></i>
    
    <div>
        <p class="font-semibold text-lg">Success</p>
        <p class="text-sm text-white/90"><?= $success ?></p>
    </div>

</div>
<?php endif; ?>



            <!-- Assign Subject Section -->
            <div>
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Assign Subject</h2>

                <form method="POST" class="space-y-5">

                    <!-- Teacher -->
                    <div class="relative">
                        <select name="teacher_id"
                            class="w-full p-3 rounded-xl border border-gray-300 bg-gray-50 text-gray-700
                                   focus:ring-2 focus:ring-violet-500 focus:border-violet-500 appearance-none">
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= $t['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-4 text-gray-500"></i>
                    </div>

                    <!-- Subject -->
                    <div class="relative">
                        <select name="subject_id"
                            class="w-full p-3 rounded-xl border border-gray-300 bg-gray-50 text-gray-700
                                   focus:ring-2 focus:ring-violet-500 focus:border-violet-500 appearance-none">
                            <?php foreach ($subjects as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= $s['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-4 text-gray-500"></i>
                    </div>

                    <!-- Class -->
                    <div class="relative">
                        <select name="class_id"
                            class="w-full p-3 rounded-xl border border-gray-300 bg-gray-50 text-gray-700
                                   focus:ring-2 focus:ring-violet-500 focus:border-violet-500 appearance-none">
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-4 text-gray-500"></i>
                    </div>

                    <!-- Assign Button -->
                    <button type="submit"
                        class="w-full bg-violet-600 hover:bg-violet-700 text-white py-3 rounded-xl font-semibold shadow-md">
                        Assign Subject
                    </button>

                </form>
            </div>

            <hr class="border-gray-200">

            <!-- Create Class -->
            <div>
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Create Class</h2>

                <form method="POST" class="space-y-5">
                    <input type="hidden" name="create_class" value="1">

                    <input type="text" name="class_name"
                        placeholder="Class Name"
                        class="w-full p-3 rounded-xl border border-gray-300 bg-gray-50
                               focus:ring-2 focus:ring-violet-500 focus:border-violet-500">

                    <button type="submit"
                        class="w-full bg-violet-600 hover:bg-violet-700 text-white py-3 rounded-xl font-semibold shadow-md">
                        Create Class
                    </button>
                </form>
            </div>

            <hr class="border-gray-200">

            <!-- Create Subject -->
            <div>
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Create Subject</h2>

                <form method="POST" class="space-y-5">
                    <input type="hidden" name="create_subject" value="1">

                    <input type="text" name="subject_name"
                        placeholder="Subject Name"
                        class="w-full p-3 rounded-xl border border-gray-300 bg-gray-50
                               focus:ring-2 focus:ring-violet-500 focus:border-violet-500">

                    <button type="submit"
                        class="w-full bg-violet-600 hover:bg-violet-700 text-white py-3 rounded-xl font-semibold shadow-md">
                        Create Subject
                    </button>
                </form>
            </div>

        </div>
    </div>

</div>

<?php include '../includes/footer.php'; ?>
