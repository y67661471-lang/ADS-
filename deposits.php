<?php
require_once '../config/db.php';
require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action']; // approve/reject
    $id = $_POST['trx_id'];
    $uid = $_POST['user_id'];
    $amount = $_POST['amount'];

    if ($act === 'approve') {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE transactions SET status = 'approved' WHERE id = ?");
        $stmt->execute([$id]);
        
        $stmt = $pdo->prepare("UPDATE users SET balance_deposit = balance_deposit + ? WHERE id = ?");
        $stmt->execute([$amount, $uid]);
        $pdo->commit();
    } elseif ($act === 'reject') {
        $stmt = $pdo->prepare("UPDATE transactions SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$id]);
    }
}

// Get Pending Deposits
$pending = $pdo->query("SELECT t.*, u.name, u.phone FROM transactions t JOIN users u ON t.user_id = u.id WHERE t.type='deposit' AND t.status='pending' ORDER BY t.id DESC")->fetchAll();

// Get History
$history = $pdo->query("SELECT t.*, u.name FROM transactions t JOIN users u ON t.user_id = u.id WHERE t.type='deposit' AND t.status!='pending' ORDER BY t.id DESC LIMIT 50")->fetchAll();
?>

<div class="header-bar">
    <h2 class="page-title">Deposit Requests</h2>
</div>

<div class="panel">
    <h3>Pending Requests</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>BS</th>
                    <th>User</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Sender No</th>
                    <th>TrxID</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending as $p): ?>
                    <tr>
                        <td>#<?php echo $p['id']; ?></td>
                        <td>
                            <?php echo htmlspecialchars($p['name']); ?>
                            <br><small><?php echo htmlspecialchars($p['phone']); ?></small>
                        </td>
                        <td style="color:green; font-weight:bold;">৳ <?php echo $p['amount']; ?></td>
                        <td><?php echo $p['method']; ?></td>
                        <td><?php echo $p['account_number']; ?></td>
                        <td><?php echo $p['trx_id']; ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="trx_id" value="<?php echo $p['id']; ?>">
                                <input type="hidden" name="user_id" value="<?php echo $p['user_id']; ?>">
                                <input type="hidden" name="amount" value="<?php echo $p['amount']; ?>">
                                <button class="action-btn btn-green">Approve</button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="trx_id" value="<?php echo $p['id']; ?>">
                                <button class="action-btn btn-red">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="panel">
    <h3>Deposit History</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>User</th>
                    <th>Amount</th>
                    <th>TrxID</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $h): ?>
                    <tr>
                        <td><?php echo date("d M y", strtotime($h['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($h['name']); ?></td>
                        <td><?php echo $h['amount']; ?></td>
                        <td><?php echo $h['trx_id']; ?></td>
                        <td>
                            <span class="badge <?php echo $h['status']=='approved'?'badge-success':'badge-danger'; ?>">
                                <?php echo ucfirst($h['status']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
