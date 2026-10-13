<?php
$pageTitle = "Wallet - RRR Esports";
require_once 'includes/functions.php';
require_once 'includes/header.php';
requireLogin();


// Fetch System Settings
$settings = $pdo->query("SELECT key_name, key_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);

// Fetch Withdraw Methods
$withdraw_methods = $pdo->query("SELECT * FROM withdraw_methods WHERE status = 'active'")->fetchAll();
// Fetch Deposit Methods
$deposit_methods = $pdo->query("SELECT * FROM deposit_methods WHERE status = 'active'")->fetchAll();

$action = $_GET['action'] ?? 'add';
$msg = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    // Method and Number are optional for 'transfer'
    $method = $_POST['method'] ?? null;
    $number = $_POST['number'] ?? null;
    $trx_id = $_POST['trx_id'] ?? null;

    if ($type === 'deposit') {
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, method, account_number, trx_id, status) VALUES (?, ?, 'deposit', ?, ?, ?, 'pending')");
        $stmt->execute([$_SESSION['user_id'], $amount, $method, $number, $trx_id]);
        $msg = "Deposit request submitted successfully! Wait for admin approval.";

    } elseif ($type === 'withdraw') {
        $user = getUser($pdo, $_SESSION['user_id']);
        if ($user['balance_winning'] >= $amount) {
            $pdo->beginTransaction();
            // Deduct
            $stmt = $pdo->prepare("UPDATE users SET balance_winning = balance_winning - ? WHERE id = ?");
            $stmt->execute([$amount, $_SESSION['user_id']]);

            // Record
            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, method, account_number, status) VALUES (?, ?, 'withdraw', ?, ?, 'pending')");
            $stmt->execute([$_SESSION['user_id'], $amount, $method, $number]);

            $pdo->commit();
            // Update session
            $_SESSION['user_balance'] -= $amount;
            $msg = "Withdraw request submitted successfully!";
        } else {
            $msg = "Insufficient winning balance!";
        }
    } elseif ($type === 'transfer') {
        $user = getUser($pdo, $_SESSION['user_id']);
        if ($user['balance_winning'] >= $amount && $amount > 0) {
            $pdo->beginTransaction();

            // Deduct from Winning
            $stmt = $pdo->prepare("UPDATE users SET balance_winning = balance_winning - ? WHERE id = ?");
            $stmt->execute([$amount, $_SESSION['user_id']]);

            // Add to Deposit
            $stmt = $pdo->prepare("UPDATE users SET balance_deposit = balance_deposit + ? WHERE id = ?");
            $stmt->execute([$amount, $_SESSION['user_id']]);

            // Record
            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, method, status, trx_id) VALUES (?, ?, 'transfer', 'Wallet Convert', 'approved', 'Internal')");
            $stmt->execute([$_SESSION['user_id'], $amount]);

            $pdo->commit();
            $msg = "Successfully transferred <b>$amount ৳</b> to playing balance!";
        } else {
            $msg = "Insufficient winning balance!";
        }
    }
}
?>

<section class="app-section active">
    <?php if ($msg): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <?php if ($action === 'add'): ?>
        <!-- ADD MONEY VIEW -->
        <div class="card-panel">
            <h3>Add Money to Wallet</h3>

            <!-- Step 1: Amount -->
            <div id="add-step-1" class="step-container active-step">
                <div class="form-group">
                    <label>Enter Amount (BDT)</label>
                    <input type="number" id="add-amount-input" class="input-field" placeholder="Minimum 20 BDT">
                </div>
                <button class="primary-btn" onclick="goToMethods()">NEXT <i class="fas fa-arrow-right"></i></button>
            </div>


            <!-- Step 2: Method -->
            <div id="add-step-2" class="step-container">
                <div class="back-nav" onclick="showStep(1)"><i class="fas fa-arrow-left"></i> Change Amount</div>
                <p style="margin-bottom:15px; font-size:14px; color:#666;">Adding: <strong id="display-amount"
                        style="color:var(--primary);">0 ৳</strong></p>

                <?php foreach ($deposit_methods as $dm): ?>
                    <div class="method-btn"
                        onclick="showPayment('<?php echo htmlspecialchars($dm['name']); ?>', '<?php echo htmlspecialchars($dm['account_number']); ?>')">
                        <?php if ($dm['logo_url']): ?>
                            <img src="<?php echo htmlspecialchars($dm['logo_url']); ?>"
                                alt="<?php echo htmlspecialchars($dm['name']); ?>">
                        <?php else: ?>
                            <i class="fas fa-wallet" style="font-size:30px; margin-right:15px; color:#555;"></i>
                        <?php endif; ?>
                        <span>Pay with <?php echo htmlspecialchars($dm['name']); ?></span>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($deposit_methods)): ?>
                    <p style="color:red; text-align:center;">No payment methods available. Please contact admin.</p>
                <?php endif; ?>
            </div>

            <!-- Step 3: Details form (Dynamic) -->
            <div id="add-step-final" class="step-container">
                <div class="back-nav" onclick="showStep(2)"><i class="fas fa-arrow-left"></i> Back to Methods</div>
                <div class="payment-details-box">
                    <div id="payment-header" class="theme-bkash-bg">
                        <h2 style="font-weight:800;">৳ <span id="final-amount">0</span></h2>
                        <p>Send Money to Personal</p>
                    </div>
                    <div style="padding:25px;">
                        <label class="form-group">
                            <span style="font-size:12px; font-weight:600; color:#555;">Copy Number:</span>
                            <div style="display:flex; gap:10px; margin-top:5px;">
                                <input type="text" id="admin-number" value="01700000000" readonly class="input-field"
                                    style="font-weight:bold;">
                                <button class="primary-btn" style="width:auto; margin:0; padding:0 20px;"
                                    onclick="copyToClipboard(document.getElementById('admin-number').value)"><i
                                        class="fas fa-copy"></i></button>
                            </div>
                        </label>

                        <form method="POST">
                            <input type="hidden" name="type" value="deposit">
                            <input type="hidden" name="amount" id="form-amount">
                            <input type="hidden" name="method" id="form-method">

                            <div class="form-group" style="margin-top:20px;">
                                <label>Your Sender Number</label>
                                <input type="number" name="number" class="input-field" placeholder="017xxxxxxxx" required>
                            </div>
                            <div class="form-group">
                                <label>Transaction ID (TrxID)</label>
                                <input type="text" name="trx_id" class="input-field" placeholder="TrxID..." required>
                            </div>
                            <button type="submit" class="primary-btn">VERIFY PAYMENT</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- WITHDRAW & TRANSFER VIEW -->
        <div class="card-panel">
            <!-- Tabs -->
            <div style="display:flex; gap:10px; margin-bottom:20px;">
                <button id="btn-withdraw" class="primary-btn" style="flex:1; background:var(--primary);"
                    onclick="switchWithdrawTab('withdraw')">Withdraw</button>
                <button id="btn-transfer" class="primary-btn" style="flex:1; background:#ccc; color:#333;"
                    onclick="switchWithdrawTab('transfer')">Transfer</button>
            </div>

            <div
                style="background:#f0fff4; padding:10px; border:1px solid #c6f6d5; border-radius:8px; margin-bottom:20px; text-align:center;">
                <span style="font-size:12px; color:#2f855a;">Available balance (Winning)</span>
                <h2 style="color:#2f855a;">
                    <?php echo number_format(getUser($pdo, $_SESSION['user_id'])['balance_winning'], 2); ?> ৳
                </h2>
            </div>

            <!-- Withdraw Form -->
            <div id="tab-withdraw" style="display:block;">
                <h3>Withdraw Winning Funds</h3>
                <form method="POST">
                    <input type="hidden" name="type" value="withdraw">
                    <div class="form-group">
                        <label>Amount</label>
                        <input type="number" name="amount" class="input-field" placeholder="Minimum 100" required>
                    </div>
                    <div class="form-group">
                        <label>Payment Method</label>

                        <select name="method" class="input-field">
                            <?php foreach ($withdraw_methods as $wm): ?>
                                <option value="<?php echo htmlspecialchars($wm['name']); ?>">
                                    <?php echo htmlspecialchars($wm['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Account Number</label>
                        <input type="text" name="number" class="input-field" placeholder="017xxxxxxxx" required>
                    </div>
                    <button type="submit" class="primary-btn">Submit Request</button>
                </form>
            </div>

            <!-- Transfer To Wallet Form -->
            <div id="tab-transfer" style="display:none;">
                <h3>Transfer To Wallet Money</h3>
                <form method="POST">
                    <input type="hidden" name="type" value="transfer">
                    <div class="form-group">
                        <label>Amount</label>
                        <input type="number" name="amount" class="input-field" placeholder="0" required>
                    </div>
                    <button type="submit" class="primary-btn" style="background:#FF5500;">Transfer To Wallet Money</button>
                    <p style="font-size:12px; color:#666; margin-top:10px;">Note: This will move funds from your Winning
                        Balance to your Playable Balance.</p>
                </form>
            </div>
        </div>
    <?php endif; ?>
</section>

<script>
    let amount = 0;
    // Pass PHP settings to JS
    const paymentMode = "<?php echo $settings['payment_mode'] ?? 'manual'; ?>";
    const bkashNumber = "<?php echo $settings['bkash_number'] ?? '01700000000'; ?>";
    const nagadNumber = "<?php echo $settings['nagad_number'] ?? '01800000000'; ?>";

    function showStep(n) {
        document.querySelectorAll('.step-container').forEach(el => el.classList.remove('active-step'));
        if (n === 1) document.getElementById('add-step-1').classList.add('active-step');
        if (n === 2) document.getElementById('add-step-2').classList.add('active-step');
        if (n === 3) document.getElementById('add-step-final').classList.add('active-step');
    }

    function goToMethods() {
        amount = document.getElementById('add-amount-input').value;
        if (amount < 20) { alert("Minimum 20 BDT"); return; }

        // Auto Payment Check
        if (paymentMode === 'auto') {
            if (confirm("Proceed to Secure Auto Payment?")) {
                window.location.href = `payment_gateway.php?amount=${amount}&user_id=<?php echo $_SESSION['user_id']; ?>`;
            }
            return;
        }

        document.getElementById('display-amount').innerText = amount + " ৳";
        showStep(2);
    }


    function showPayment(method, number) {
        document.getElementById('final-amount').innerText = amount;
        document.getElementById('form-amount').value = amount;
        document.getElementById('form-method').value = method;
        document.getElementById('admin-number').value = number;

        // Set Header Color & Visuals based on method name
        const head = document.getElementById('payment-header');

        // Simple heuristic for colors, default to blue for others
        const m = method.toLowerCase();
        if (m.includes('bkash')) {
            head.className = 'theme-bkash-bg';
        } else if (m.includes('nagad')) {
            head.className = 'theme-nagad-bg';
        } else if (m.includes('rocket')) {
            head.style.background = '#8c3494'; // Rocket Purple
            head.style.color = 'white';
        } else {
            head.className = '';
            head.style.background = '#007bff'; // Default Blue
            head.style.color = 'white';
        }

        showStep(3);
    }

    /* Tab Switcher for Withdraw/Transfer */
    function switchWithdrawTab(tab) {
        const btnW = document.getElementById('btn-withdraw');
        const btnT = document.getElementById('btn-transfer');
        const viewW = document.getElementById('tab-withdraw');
        const viewT = document.getElementById('tab-transfer');

        if (tab === 'withdraw') {
            btnW.style.background = 'var(--primary)';
            btnW.style.color = 'white';
            btnT.style.background = '#ccc';
            btnT.style.color = '#333';
            viewW.style.display = 'block';
            viewT.style.display = 'none';
        } else {
            btnT.style.background = 'var(--primary)';
            btnT.style.color = 'white';
            btnW.style.background = '#ccc';
            btnW.style.color = '#333';
            viewT.style.display = 'block';
            viewW.style.display = 'none';
        }
    }
</script>


<?php require_once 'includes/footer.php'; ?>