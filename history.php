<?php
$pageTitle = "History - RRR Esports";
require_once 'includes/functions.php';
require_once 'includes/header.php';
requireLogin();

$stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$_SESSION['user_id']]);
$transactions = $stmt->fetchAll();
?>

<section class="app-section active">
    <div class="card-panel">
        <h3>Transaction History</h3>

        <?php if (empty($transactions)): ?>
            <div style="text-align:center; padding:30px; color:#999;">No transactions found.</div>
        <?php else: ?>
            <table class="result-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td>
                                <?php echo date("d M y", strtotime($t['created_at'])); ?>
                            </td>
                            <td style="text-transform:capitalize;">
                                <?php echo $t['type']; ?>
                            </td>
                            <td style="font-weight:bold; color:<?php echo $t['type'] == 'deposit' ? 'green' : 'red'; ?>;">
                                <?php echo $t['type'] == 'deposit' ? '+' : '-'; ?>
                                <?php echo $t['amount']; ?>
                            </td>
                            <td>
                                <?php echo $t['method']; ?>
                            </td>
                            <td>
                                <span class="badge" style="
                                    background: <?php echo $t['status'] == 'approved' ? '#d4edda' : ($t['status'] == 'rejected' ? '#f8d7da' : '#fff3cd'); ?>;
                                    color: <?php echo $t['status'] == 'approved' ? '#155724' : ($t['status'] == 'rejected' ? '#721c24' : '#856404'); ?>;
                                    padding:3px 8px; border-radius:10px; font-size:10px;
                                ">
                                    <?php echo ucfirst($t['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
