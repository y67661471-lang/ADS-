<?php
require_once '../config/db.php';

// TEMP DEBUGGING
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Verify Admin
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['admin_role_id'] = $admin['role_id'];

        header("Location: dashboard.php");
        exit;
    } else {
        // Fallback for PIN (Legacy Support during transition)
        // If the user entered a PIN in the password field and email is empty (or we add a separate check)
        // But for now, we strictly move to Email/Pass as requested.
        $error = "Invalid Email or Password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>

<body>
    <div class="pin-screen">
        <i class="fas fa-shield-alt" style="font-size: 50px; color: var(--primary); margin-bottom: 20px;"></i>
        <h2>RRR Admin</h2>
        <p style="color: #666; font-size: 14px; margin-bottom:20px;">Staff Access</p>

        <?php if ($error): ?>
            <p style="background:rgba(255,0,0,0.1); color: #ff4444; padding:10px; border-radius:4px; font-size:14px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </p>
        <?php endif; ?>

        <form method="POST">
            <div style="margin-bottom:15px; text-align:left;">
                <label style="font-size:12px; font-weight:600; color:#555;">EMAIL</label>
                <input type="email" name="email" class="pin-input" placeholder="admin@example.com"
                    style="width:100%; text-align:left; padding:10px; font-size:14px;" required autofocus>
            </div>
            <div style="margin-bottom:20px; text-align:left;">
                <label style="font-size:12px; font-weight:600; color:#555;">PASSWORD</label>
                <input type="password" name="password" class="pin-input" placeholder="••••••"
                    style="width:100%; text-align:left; padding:10px; font-size:14px;" required>
            </div>

            <button type="submit" class="action-btn btn-orange" style="width:100%; padding:12px; font-size:16px;">SECURE
                LOGIN <i class="fas fa-arrow-right"></i></button>
        </form>
    </div>
</body>

</html>