<?php
include '../includes/db.php';
include '../includes/functions.php';
if (!is_teacher()) redirect('../dashboard.php');

$teacher_id = get_teacher_id($pdo, $_SESSION['user_id']);
$lectures = $pdo->prepare("SELECT l.id, s.name as subject, c.name as class, l.start_time FROM lectures l JOIN assignments a ON l.assignment_id = a.id JOIN subjects s ON a.subject_id = s.id JOIN classes c ON a.class_id = c.id WHERE a.teacher_id = ?");
$lectures->execute([$teacher_id]);
$lectures = $lectures->fetchAll();

$attendance = [];
if (isset($_GET['lecture_id'])) {
    $lecture_id = $_GET['lecture_id'];
    $stmt = $pdo->prepare("SELECT st.name, att.timestamp FROM attendances att JOIN students st ON att.student_id = st.id WHERE att.lecture_id = ?");
    $stmt->execute([$lecture_id]);
    $attendance = $stmt->fetchAll();
}

include '../includes/header.php';
?>
<h1 class="text-2xl">View Attendance</h1>
<select onchange="location = this.value;" class="border p-2 mb-4">
    <option>Select Lecture</option>
    <?php foreach ($lectures as $l): ?>
        <option value="?lecture_id=<?php echo $l['id']; ?>"><?php echo $l['subject'] . ' in ' . $l['class'] . ' at ' . $l['start_time']; ?></option>
    <?php endforeach; ?>
</select>
<?php if (!empty($attendance)): ?>
    <table class="w-full border">
        <thead><tr><th class="border p-2">Student</th><th class="border p-2">Time</th></tr></thead>
        <tbody>
            <?php foreach ($attendance as $a): ?>
                <tr><td class="border p-2"><?php echo $a['name']; ?></td><td class="border p-2"><?php echo $a['timestamp']; ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<?php include '../includes/footer.php'; ?>