<?php
require_once '../config/db.php';
require_once 'includes/header.php';

// Handle Manual Deposit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = $_POST['user_id'];
    $amount = $_POST['amount'];
    $note = $_POST['note'] ?? 'Manual Deposit';

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $user = $stmt->fetch();

    if ($user) {
        $pdo->beginTransaction();

        // Add Balance
        $pdo->prepare("UPDATE users SET balance_deposit = balance_deposit + ? WHERE id = ?")->execute([$amount, $uid]);

        // Log Transaction
        $pdo->prepare("INSERT INTO transactions (user_id, amount, type, method, status, trx_id) VALUES (?, ?, 'deposit', 'Manual', 'approved', ?)")->execute([$uid, $amount, $note]);

        $pdo->commit();
        $msg = "Successfully added ৳$amount to {$user['name']} (ID: $uid)";
    } else {
        $err = "User ID not found!";
    }
}
?>

<div class="header-bar">
    <h2 class="page-title">Manual Deposit</h2>
</div>

<div class="panel" style="max-width:600px;">
    <?php if (isset($msg))
        echo "<div style='color:green; padding:10px; border:1px solid green; margin-bottom:15px; border-radius:5px;'>$msg</div>"; ?>
    <?php if (isset($err))
        echo "<div style='color:red; padding:10px; border:1px solid red; margin-bottom:15px; border-radius:5px;'>$err</div>"; ?>

    <form method="POST">
        <div class="form-group">
            <label>User ID</label>
            <input type="number" name="user_id" class="input-field" placeholder="Enter User ID" required>
        </div>
        <div class="form-group">
            <label>Amount (৳)</label>
            <input type="number" name="amount" class="input-field" placeholder="Amount to add" required>
        </div>
        <div class="form-group">
            <label>Reference Note</label>
            <input type="text" name="note" class="input-field" placeholder="Reason (Optional)">
        </div>
        <button type="submit" class="action-btn btn-success" style="width:100%; font-size:16px; padding:12px;">Add
            Funds</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>