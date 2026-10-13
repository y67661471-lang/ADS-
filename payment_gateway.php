<?php
require_once 'includes/functions.php';
requireLogin();

// Fetch System Settings
$settings = $pdo->query("SELECT key_name, key_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$apiKey = $settings['auto_pay_key'] ?? '';
$apiBaseUrl = $settings['auto_pay_url'] ?? '';
$action = $_GET['action'] ?? 'init';

if (empty($apiKey) || empty($apiBaseUrl)) {
    die("Payment Gateway Config Missing. Contact Admin.");
}

// Helper for CURL
function sendRequest($method, $url, $apiKey, $data)
{
    $headers = [
        'API-KEY: ' . $apiKey,
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// --- INITIALIZE PAYMENT ---
if ($action === 'init') {
    $amount = $_GET['amount'] ?? 0;
    if ($amount < 20)
        die("Invalid Amount");

    $user = getUser($pdo, $_SESSION['user_id']);

    // Create Pending Transaction in DB to track it
    // We use a temporary trx_id until verified, or generate one now
    $temp_trx = "TRX-" . time() . rand(1000, 9999);

    // Check if we should insert now or later? 
    // Best to insert now so we have a record.
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, method, account_number, trx_id, status) VALUES (?, ?, 'deposit', 'AutoPay', 'Gateway', ?, 'pending')");
    $stmt->execute([$user['id'], $amount, $temp_trx]);
    $db_id = $pdo->lastInsertId();

    // Helper to get current URL with Port
    // Priority: Auto-detect if DB has localhost but we are live. Otherwise DB.
    $dbUrl = $settings['site_url'] ?? '';

    // Auto Detection Logic
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']); // Current dir
    $autoUrl = rtrim($protocol . $host . $path, '/');

    if (empty($dbUrl) || (strpos($dbUrl, 'localhost') !== false && strpos($host, 'localhost') === false)) {
        $siteUrl = $autoUrl;
    } else {
        $siteUrl = rtrim($dbUrl, '/');
    }

    $siteUrl = str_replace(' ', '%20', $siteUrl); // Fix for spaces

    $successUrl = $siteUrl . "/payment_gateway.php?action=success&db_id=$db_id";
    $cancelUrl = $siteUrl . "/payment_gateway.php?action=cancel";

    // Log Init for Debugging
    $log = "Init Time: " . date("Y-m-d H:i:s") . "\n";
    $log .= "Detected Host: " . $host . "\n";
    $log .= "Using Success URL: " . $successUrl . "\n";
    $log .= "Request Data: " . print_r($_GET, true) . "\n";
    file_put_contents('gateway_init.log', $log, FILE_APPEND);

    // Trim Credentials
    $apiKey = trim($apiKey);
    $apiBaseUrl = trim($apiBaseUrl);

    // Validate User Data (Gateway is strict)
    $cusName = !empty($user['name']) ? $user['name'] : 'Guest User';
    $cusEmail = !empty($user['email']) && filter_var($user['email'], FILTER_VALIDATE_EMAIL) ? $user['email'] : 'guest@example.com';
    $cusPhone = !empty($user['phone']) ? $user['phone'] : '01700000000';

    // Generate Request Data
    $requestData = [
        'api_key' => $apiKey, // Some gateways prefer it in body
        'cus_name' => $cusName,
        'cus_email' => $cusEmail,
        'cus_phone' => $cusPhone,
        'amount' => (string) $amount, // Send as string to avoid JSON float issues
        'metadata' => [
            'db_id' => $db_id,
            'user_id' => $user['id'] ?? 0
        ],
        'success_url' => $successUrl,
        'cancel_url' => $cancelUrl,
        'webhook_url' => $successUrl,
        'request_id' => uniqid() // Unique Request ID often helps
    ];

    $apiUrl = rtrim($apiBaseUrl, '/') . '/api/payment/create';
    $jsonPayload = json_encode($requestData);

    // LOG THE FULL REQUEST (Masking API Key)
    $debugLog = "\n\n--- Sending Request [" . date("H:i:s") . "] ---\n";
    $debugLog .= "URL: " . $apiUrl . "\n";
    $debugLog .= "Key: " . substr($apiKey, 0, 5) . "***\n";
    $debugLog .= "Payload: " . $jsonPayload . "\n";
    file_put_contents('gateway_init.log', $debugLog, FILE_APPEND);

    // --- REAL PAYMENT MODE ---
    $response = sendRequest('POST', $apiUrl, $apiKey, $requestData);

    if (isset($response['payment_url'])) {
        header("Location: " . $response['payment_url']);
        exit;
    } else {
        // Log the full error to see why it failed
        $errLog = "Gateway Error Time: " . date("Y-m-d H:i:s") . "\n";
        $errLog .= "API Response: " . print_r($response, true) . "\n";
        file_put_contents('gateway_init.log', $errLog, FILE_APPEND);

        echo "<h3>Gateway Error</h3>";
        echo "Message: " . ($response['message'] ?? 'Unknown Error');
        echo "<br><small>Debug: Check gateway_init.log for more info.</small>";
        exit;
    }
}

// --- HANDLE SUCCESS / CALLBACK ---
if ($action === 'success') {
    // Debugging
    $log = "Timestamp: " . date("Y-m-d H:i:s") . "\n";
    $log .= "Request Params: " . print_r($_REQUEST, true) . "\n";
    $log .= "Input Stream: " . file_get_contents("php://input") . "\n";

    // Check for JSON Input if Request is empty
    $inputData = json_decode(file_get_contents("php://input"), true);
    $invoiceId = $_REQUEST['transactionId'] ?? $_REQUEST['transaction_id'] ?? $inputData['transactionId'] ?? null;
    if ($invoiceId)
        $invoiceId = trim($invoiceId); // Fix: Remove trailing spaces from copy-paste
    $db_id = $_GET['db_id'] ?? null;

    // Log extracted ID
    $log .= "Extracted Invoice ID: " . ($invoiceId ?? 'NULL') . "\n";
    $log .= "DB ID: " . ($db_id ?? 'NULL') . "\n";

    file_put_contents('gateway_debug.log', $log, FILE_APPEND);

    if (!$invoiceId) {
        // Fallback: Show Input Form
        ?>
        <!DOCTYPE html>
        <html>

        <head>
            <title>Verify Payment</title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
        </head>

        <body style="font-family:sans-serif; text-align:center; padding:20px;">
            <h3>Verifying Payment...</h3>
            <p>We received the callback but could not detect the Transaction ID automatically.</p>
            <p>Please enter the <b>TrxID</b> from your SMS/App below to complete the deposit.</p>
            <form method="POST" action="">
                <input type="text" name="transaction_id" placeholder="Enter TrxID (e.g. QQSY...)" required
                    style="padding:10px; width:80%; max-width:300px; border:1px solid #ccc; border-radius:5px; margin-bottom:10px;">
                <br>
                <button type="submit"
                    style="padding:10px 20px; background:blue; color:white; border:none; border-radius:5px; cursor:pointer;">
                    Verify Now
                </button>
            </form>
            <br>
            <a href="wallet.php">Cancel</a>
        </body>

        </html>
        <?php
        exit;
    }

    // Verify Payment
    $verifyUrl = rtrim($apiBaseUrl, '/') . '/api/payment/verify';
    $response = sendRequest('POST', $verifyUrl, $apiKey, ['transaction_id' => $invoiceId]);

    // Log Response
    file_put_contents('gateway_debug.log', "Verify Response: " . print_r($response, true) . "\n\n", FILE_APPEND);

    $status = $response['status'] ?? $_REQUEST['status'] ?? 'pending';
    if (strtoupper($status) === 'COMPLETED') {
        // Payment Success!

        // 1. Check if already processed
        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ?");
        $stmt->execute([$db_id]);
        $trx = $stmt->fetch();

        if ($trx && $trx['status'] === 'pending') {
            $pdo->beginTransaction();
            try {
                // details from response
                $method = $response['payment_method'] ?? $_REQUEST['paymentMethod'] ?? 'AutoPay';
                $sender = $response['sender_number'] ?? 'Gateway';

                // Update Transaction
                $stmt = $pdo->prepare("UPDATE transactions SET status = 'approved', trx_id = ?, method = ?, account_number = ? WHERE id = ?");
                $result = $stmt->execute([$invoiceId, $method, $sender, $db_id]);

                file_put_contents('gateway_debug.log', "DB Update Result for ID $db_id: " . ($result ? 'Success' : 'Fail') . "\n", FILE_APPEND);

                // Add Balance to User
                // Logic: Credits go to 'balance_deposit' usually for topup
                $stmt = $pdo->prepare("UPDATE users SET balance_deposit = balance_deposit + ? WHERE id = ?");
                $stmt->execute([$trx['amount'], $trx['user_id']]);

                // Update Session
                if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $trx['user_id']) {
                    $_SESSION['user_balance'] += $trx['amount'];
                }

                $pdo->commit();


                // Success Page Design
                $pageTitle = 'Payment Successful';
                include 'includes/header.php';
                ?>
                <div style="min-height: 70vh; display: flex; align-items: center; justify-content: center; padding: 20px;">
                    <div
                        style="background: var(--card-bg); padding: 40px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); text-align: center; max-width: 420px; width: 100%; border: 1px solid var(--border-color); animation: slideUp 0.5s ease-out;">

                        <div
                            style="width: 90px; height: 90px; background: rgba(0, 255, 0, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; color: #00cc00; font-size: 42px;">
                            <i class="fas fa-check-circle"></i>
                        </div>

                        <h2 style="color: var(--text-dark); margin-bottom: 10px; font-weight: 800; font-size: 26px;">Payment Successful!
                        </h2>
                        <p style="color: var(--text-light); margin-bottom: 30px; font-size: 15px; line-height: 1.5;">Your payment has
                            been processed and your wallet has been credited.</p>

                        <div
                            style="background: var(--bg-color); padding: 25px; border-radius: 16px; margin-bottom: 30px; border: 1px solid var(--border-color);">
                            <div
                                style="font-size: 13px; color: var(--text-light); margin-bottom: 8px; text-transform: uppercase; font-weight: 600; letter-spacing: 1px;">
                                Amount Added</div>
                            <div style="font-size: 32px; font-weight: 800; color: var(--primary);">৳
                                <?php echo number_format($trx['amount'], 2); ?>
                            </div>

                            <div
                                style="margin-top: 20px; padding-top: 20px; border-top: 1px dashed var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 13px; color: var(--text-light);">Transaction ID</span>
                                <span
                                    style="font-size: 13px; font-weight: 700; color: var(--text-dark); font-family: 'Courier New', monospace; background: rgba(0,0,0,0.05); padding: 4px 8px; border-radius: 4px;"><?php echo htmlspecialchars($invoiceId); ?></span>
                            </div>
                        </div>

                        <a href="wallet.php"
                            style="display: flex; align-items: center; justify-content: center; width: 100%; padding: 16px; background: var(--primary); color: white; border-radius: 12px; font-weight: 700; font-size: 16px; transition: 0.3s; box-shadow: 0 4px 15px rgba(255, 85, 0, 0.25);">
                            <i class="fas fa-wallet" style="margin-right: 10px;"></i> Return to Wallet
                        </a>
                    </div>
                </div>
                <?php
                include 'includes/footer.php';
                exit;


            } catch (Exception $e) {
                $pdo->rollBack();
                echo "DB Error: " . $e->getMessage();
                file_put_contents('gateway_debug.log', "DB Error: " . $e->getMessage() . "\n", FILE_APPEND);
            }
        } else {
            echo "Transaction already processed or invalid.";
            echo "<br><a href='wallet.php'>Go Back</a>";
        }

    } else {
        echo "<h3>Payment Failed or Pending</h3>";
        echo "Status: " . ($response['status'] ?? 'Unknown');
        echo "<br>Response: " . print_r($response, true);
        echo "<br><a href='wallet.php'>Go Back</a>";
    }
}

// --- HANDLE CANCEL ---
if ($action === 'cancel') {
    header("Location: wallet.php?msg=cancelled");
    exit;
}
?>