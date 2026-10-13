<?php
require_once '../config/db.php';
require_once 'includes/header.php';

// Pagination Setup
$limit = 50;
$page = isset($_GET['page']) ? (int) $page = $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch History
$stmt = $pdo->prepare("SELECT t.*, u.name FROM transactions t JOIN users u ON t.user_id = u.id WHERE t.type='deposit' AND t.status != 'pending' ORDER BY t.id DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$history = $stmt->fetchAll();
?>

<div class="header-bar">
    <h2 class="page-title">Deposit History</h2>
</div>

<div class="panel">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>User</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>TrxID</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $h): ?>
                    <tr>
                        <td>
                            <?php echo date("d M y, h:i A", strtotime($h['created_at'])); ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($h['name']); ?> <small>(ID:
                                <?php echo $h['user_id']; ?>)
                            </small>
                        </td>
                        <td style="color:green; font-weight:bold;">+৳
                            <?php echo $h['amount']; ?>
                        </td>
                        <td>
                            <?php echo $h['method']; ?>
                        </td>
                        <td>
                            <?php echo $h['trx_id']; ?>
                        </td>
                        <td>
                            <span class="badge <?php echo $h['status'] == 'approved' ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo ucfirst($h['status']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Simple Pagination -->
        <div style="margin-top:20px; display:flex; gap:10px; justify-content:center;">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>" class="action-btn btn-dark">Previous</a>
            <?php endif; ?>
            <a href="?page=<?php echo $page + 1; ?>" class="action-btn btn-dark">Next</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
