<?php
include '../includes/db.php';
include '../includes/functions.php';
if (!is_student()) redirect('../dashboard.php');

$student_id = get_student_id($pdo, $_SESSION['user_id']);
$attendances = $pdo->prepare("SELECT l.start_time, s.name as subject, c.name as class FROM attendances att JOIN lectures l ON att.lecture_id = l.id JOIN assignments a ON l.assignment_id = a.id JOIN subjects s ON a.subject_id = s.id JOIN classes c ON a.class_id = c.id WHERE att.student_id = ? ORDER BY l.start_time DESC");
$attendances->execute([$student_id]);
$attendances = $attendances->fetchAll();

include '../includes/header.php';
?>
<h1 class="text-2xl">My Attendance</h1>
<input type="text" id="filterInput" onkeyup="filterTable('filterInput', 'attTable')" placeholder="Filter by Subject" class="border p-2 mb-4 w-full">
<table id="attTable" class="w-full border">
    <thead><tr><th class="border p-2">Date</th><th class="border p-2">Subject</th><th class="border p-2">Class</th></tr></thead>
    <tbody>
        <?php foreach ($attendances as $a): ?>
            <tr><td class="border p-2"><?php echo $a['start_time']; ?></td><td class="border p-2"><?php echo $a['subject']; ?></td><td class="border p-2"><?php echo $a['class']; ?></td></tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php include '../includes/footer.php'; ?>