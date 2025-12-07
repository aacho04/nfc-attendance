<?php
include 'includes/db.php';
include 'includes/functions.php';
$error = null; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
             $_SESSION['username'] = $user['username'];
            redirect('dashboard.php');
        } else {
            $error = "Invalid credentials";
        }
    } else {
        $error = "Invalid credentials";
    }
}

// include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendence</title>
    <style>

:root {
    --primary: #6C63FF;
    --dark-bg: #ffffff; /* White background */
    --dark-card: #ffffff; /* White card */
    --dark-text: #1E293B;
    --light-text: #64748B;
    --border: #CBD5E1;
}
body{
    display:flex;
    justify-content:center;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}






/* Left panel becomes full card */
.login-left {
    background-color: #ffffff;
    padding: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

/* Adjust text colors for white theme */
.logo-text {
    color: #1E293B;
}

h1 {
    font-size: 28px;
    margin-bottom: 8px;
    color: #1E293B;
}

.subtitle {
    color: #64748B;
    margin-bottom: 25px;
}

.form-group {
    margin-bottom: 20px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
}

input {
    width: 100%;
    padding: 14px 16px;
    background-color: #F8FAFC;
    border: 1px solid var(--border);
    border-radius: 8px;
    color: #1E293B;
    font-size: 15px;
    transition: all 0.3s;
}

input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.2);
}

.login-btn {
    width: 100%;
    padding: 14px;
    background-color:#7C3AED;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
               

}

.login-btn:hover {
    background-color: #5a52e0;
}

.forgot-password {
    text-align: right;
    margin-top: 12px;
}

.forgot-password a {
    color: #64748B;
    font-size: 14px;
    text-decoration: none;
}

.forgot-password a:hover {
    color: var(--primary);
}

.error-message {
    background-color: rgba(255,0,0,0.1);
    border: 1px solid rgba(255,0,0,0.3);
    color: #dc2626;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
    text-align: center;
}
body {
    background-color: var(--dark-bg);
    color: var(--dark-text);
    min-height: 100vh;

    display: flex;
    align-items: center;
    justify-content: center;

    padding: 20px;          /* Left–right margin */
}

/* Centered card */
.login-container {
    width: 100%;
    max-width: 460px;       /* Controls card size */      /* Ensures perfect centering */
    
    border-radius: 16px;
    background: #ffffff;
    margin: 0 auto;          
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);

    padding: 40px;
}

.login-wrapper {
    display: flex;
    width: 100%;
    max-width: 900px; /* bigger because image added */
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.login-right-image {
    width: 50%;
    height: 100%;
    background-size: cover;
    background-position: center;
     min-height: 400px; 
}




    </style>
</head>
<body>
   <div class="login-wrapper">

    <!-- LEFT SIDE (your original login UI untouched) -->
    <div class="login-container">
        <div class="login-left">
            <div class="logo">
                <div class="logo-text">PROGRESSIVE EDUCATION SOCIETY'S</div>
            </div>

            <h1>Modern College Of Engineering</h1>
            <p class="subtitle">Sign in </p>

            <?php if (isset($error)): ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>

                <button type="submit" class="login-btn">Login</button>
            </form>
        </div>
    </div>

    <!-- RIGHT SIDE IMAGE -->
    <div class="login-right-image" style="background-image:url('image/login.png');">
    </div>

</div>

</body>
</html>

<?php include 'includes/footer.php'; ?>