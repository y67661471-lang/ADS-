<?php
$pageTitle = "Login - RRR Esports";
require_once 'includes/functions.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];


    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] === 'banned') {
            $error = "Your account is banned.";
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_balance'] = $user['balance_deposit'] + $user['balance_winning'];
            header("Location: index.php");
            exit;
        }
    } else {
        $error = "Invalid credentials.";
    }
}

// Handle Google Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['google_credential'])) {
    $credential = $_POST['google_credential'];
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $credential;
    $response = @file_get_contents($url);
    $payload = json_decode($response, true);

    if ($payload && isset($payload['email_verified']) && $payload['email_verified']) {
        $email = $payload['email'];
        $name = $payload['name'];

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            if ($user['status'] === 'banned') {
                $error = "Your account is banned.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_balance'] = $user['balance_deposit'] + $user['balance_winning'];
                header("Location: index.php");
                exit;
            }
        } else {
            // Clean register
            $password = password_hash(uniqid() . 'G!', PASSWORD_BCRYPT);
            // Generate unique dummy phone
            $phone = 'G-' . uniqid();

            try {
                $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, status, balance_deposit, balance_winning) VALUES (?, ?, ?, ?, 'active', 0, 0)");
                if ($stmt->execute([$name, $email, $phone, $password])) {
                    $_SESSION['user_id'] = $pdo->lastInsertId();
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_balance'] = 0;
                    header("Location: index.php");
                    exit;
                }
            } catch (PDOException $e) {
                $error = "Registration Failed: " . $e->getMessage();
            }
        }
    } else {
        $error = "Google Login Failed.";
    }
}

// Fetch Settings for Google Auth (if not already fetched in functions, assume we need to fetch)
$settings = $pdo->query("SELECT key_name, key_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <?php if (isset($settings['google_auth_enable']) && $settings['google_auth_enable'] == '1'): ?>
        <script src="https://accounts.google.com/gsi/client" async defer></script>
    <?php endif; ?>
</head>

<body>
    <div class="auth-page active" style="display:flex;">
        <div class="auth-container">
            <div style="margin-bottom:20px;">
                <img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEg2v5v5v5v5v5v5/s1600/rrr-esports-logo.png"
                    style="height:60px; border-radius:10px;">
            </div>
            <h2 class="auth-title">Login</h2>
            <p class="auth-sub">Login to your account to continue</p>

            <?php if ($error): ?>
                <div style="color:red; margin-bottom:10px; font-size:14px;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="input-field" placeholder="Enter your email" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="input-field" placeholder="Enter password" required>
                </div>
                <button type="submit" class="primary-btn">Sign In</button>
            </form>

            <?php if (isset($settings['google_auth_enable']) && $settings['google_auth_enable'] == '1'): ?>
                <div style="margin-top: 15px;">
                    <div id="g_id_onload" data-client_id="<?php echo $settings['google_client_id'] ?? ''; ?>"
                        data-context="signin" data-ux_mode="popup" data-callback="handleCredentialResponse"
                        data-auto_prompt="false">
                    </div>

                    <div class="g_id_signin" data-type="standard" data-shape="rectangular" data-theme="outline"
                        data-text="signin_with" data-size="large" data-logo_alignment="left" data-width="100%">
                    </div>

                    <form id="google-form" method="POST" style="display:none;">
                        <input type="hidden" name="google_credential" id="google-credential">
                    </form>

                    <script>
                        function handleCredentialResponse(response) {
                            document.getElementById('google-credential').value = response.credential;
                            document.getElementById('google-form').submit();
                        }
                    </script>
                </div>
            <?php endif; ?>

            <p style="margin-top:20px; font-size:14px; color:#666;">
                Don't have an account? <a href="register.php" style="color:var(--primary); font-weight:700;">Register
                    Now</a>
            </p>
            <button class="btn-cancel" onclick="window.location.href='index.php'">Cancel</button>
        </div>
    </div>
</body>

</html>