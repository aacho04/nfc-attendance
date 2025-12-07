<?php
include '../includes/db.php';
include '../includes/functions.php';
if (!is_admin()) redirect('../login.php');

$teachers = $pdo->query("SELECT t.id, t.name FROM teachers t")->fetchAll();
$classes = $pdo->query("SELECT DISTINCT id, name FROM classes")->fetchAll();
$subjects = $pdo->query("SELECT id, name FROM subjects")->fetchAll();

include '../includes/header.php';
?>
<!-- <div class="p-2 -mt-4"> -->
    <div class="p-2 -mt-3 w-full pr-0">



    <!-- Main Card -->
    <div class="max-w-full bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">

        <!-- Header -->
        <div class="bg-gradient-to-r from-violet-600 to-violet-500 p-8 text-white">
            <h1 class="text-3xl font-bold">Admin Dashboard</h1>
            <p class="text-white/80 text-sm mt-1">Manage Teachers, Subjects & Classes</p>
        </div>

        <!-- Body -->
        <div class="p-8 space-y-10">

            <!-- Assign Subject Section -->
            <div>
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Assign Subject</h2>

                <form method="POST" action="../admin/assign_subject.php" class="space-y-5">

                    <!-- Teacher -->
                    <div class="relative">
                        <select name="teacher_id"
                            class="w-full p-3 rounded-xl border border-gray-300 bg-gray-50 text-gray-800
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
                            class="w-full p-3 rounded-xl border border-gray-300 bg-gray-50 text-gray-800
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
                            class="w-full p-3 rounded-xl border border-gray-300 bg-gray-50 text-gray-800
                                   focus:ring-2 focus:ring-violet-500 focus:border-violet-500 appearance-none">
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-4 text-gray-500"></i>
                    </div>

                    <!-- Button -->
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

                <form method="POST" action="../assign_subject.php" class="space-y-5">
                    <input type="hidden" name="create_class" value="1">

                    <input type="text" name="class_name"
                        placeholder="Class Name"
                        class="w-full p-3 rounded-xl border border-gray-300 bg-gray-50 focus:ring-2 
                               focus:ring-violet-500 focus:border-violet-500">

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

                <form method="POST" action="../assign_subject.php" class="space-y-5">
                    <input type="hidden" name="create_subject" value="1">

                    <input type="text" name="subject_name"
                        placeholder="Subject Name"
                        class="w-full p-3 rounded-xl border border-gray-300 bg-gray-50 focus:ring-2 
                               focus:ring-violet-500 focus:border-violet-500">

                    <button type="submit"
                        class="w-full bg-violet-600 hover:bg-violet-700 text-white py-3 rounded-xl font-semibold shadow-md">
                        Create Subject
                    </button>
                </form>
            </div>

        </div>
    </div>

</div> <!-- END WRAPPER -->

<?php include '../includes/footer.php'; ?>