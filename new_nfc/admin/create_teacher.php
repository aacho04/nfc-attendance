<?php
include '../includes/db.php';
include '../includes/functions.php';
if (!is_admin()) redirect('../dashboard.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $name = $_POST['name'];
    
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'teacher')");
        $stmt->execute([$username, $pass]);
        $user_id = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("INSERT INTO teachers (user_id, name) VALUES (?, ?)");
        $stmt->execute([$user_id, $name]);
        $pdo->commit();
        $success = "Teacher created";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}
include '../includes/header.php';
?>
<h1 class="text-2xl">Create Teacher</h1>
<?php if (isset($success)): ?><p class="text-green-500"><?php echo $success; ?></p><?php endif; ?>
<?php if (isset($error)): ?><p class="text-red-500"><?php echo $error; ?></p><?php endif; ?>
<form method="POST" class="space-y-4">
    <input type="text" name="username" placeholder="Username" class="border p-2 w-full" required>
    <input type="password" name="password" placeholder="Password" class="border p-2 w-full" required>
    <input type="text" name="name" placeholder="Name" class="border p-2 w-full" required>
    <button type="submit" class="bg-blue-500 text-white p-2">Create</button>
</form>
<?php include '../includes/footer.php'; ?>