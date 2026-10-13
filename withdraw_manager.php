<?php
require_once '../config/db.php';
require_once 'includes/header.php';

$msg = '';

// --- POST HANDLING ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Withdraw Request Actions
    if (isset($_POST['req_action'])) {
        $act = $_POST['req_action'];
        $id = $_POST['trx_id'];
        $uid = $_POST['user_id'] ?? null;
        $amount = $_POST['amount'] ?? 0;

        if ($act === 'approve') {
            $stmt = $pdo->prepare("UPDATE transactions SET status = 'approved' WHERE id = ?");
            $stmt->execute([$id]);
            $msg = "Withdraw request approved!";
        } elseif ($act === 'reject') {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE transactions SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$id]);
            // Refund
            $stmt = $pdo->prepare("UPDATE users SET balance_winning = balance_winning + ? WHERE id = ?");
            $stmt->execute([$amount, $uid]);
            $pdo->commit();
            $msg = "Withdraw request rejected and refunded.";
        }
    }
    // 2. Withdraw Method Actions
    elseif (isset($_POST['add_method'])) {
        $name = trim($_POST['name']);
        if (!empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO withdraw_methods (name) VALUES (?)");
            $stmt->execute([$name]);
            $msg = "Method added successfully!";
        }
    } elseif (isset($_POST['delete_method_id'])) {
        $stmt = $pdo->prepare("DELETE FROM withdraw_methods WHERE id = ?");
        $stmt->execute([$_POST['delete_method_id']]);
        $msg = "Method deleted successfully!";
    } elseif (isset($_POST['toggle_method_id'])) {
        $stmt = $pdo->prepare("UPDATE withdraw_methods SET status = IF(status='active','inactive','active') WHERE id = ?");
        $stmt->execute([$_POST['toggle_method_id']]);
    }
}

// --- DATA FETCHING ---

// 1. Requests (Pending)
$pending = $pdo->query("SELECT t.*, u.name, u.phone FROM transactions t JOIN users u ON t.user_id = u.id WHERE t.type='withdraw' AND t.status='pending' ORDER BY t.id DESC")->fetchAll();

// 2. History (Pagination)
$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$history_stmt = $pdo->prepare("SELECT t.*, u.name FROM transactions t JOIN users u ON t.user_id = u.id WHERE t.type='withdraw' AND t.status != 'pending' ORDER BY t.id DESC LIMIT ? OFFSET ?");
$history_stmt->bindValue(1, $limit, PDO::PARAM_INT);
$history_stmt->bindValue(2, $offset, PDO::PARAM_INT);
$history_stmt->execute();
$history = $history_stmt->fetchAll();

// 3. Methods
$methods = $pdo->query("SELECT * FROM withdraw_methods ORDER BY id DESC")->fetchAll();

?>

<style>
    .tab-nav { display:flex; gap:10px; margin-bottom:20px; }
    .tab-btn { padding:10px 20px; border:none; background:#eee; cursor:pointer; font-weight:600; border-radius:5px; }
    .tab-btn.active { background:var(--primary); color:white; }
    .tab-content { display:none; animation: fadeIn 0.3s; }
    .tab-content.active { display:block; }
    @keyframes fadeIn { from { opacity:0; transform:translateY(5px); } to { opacity:1; transform:translateY(0); } }
</style>

<div class="header-bar">
    <h2 class="page-title">Withdrawals Manager</h2>
</div>

<?php if ($msg): ?>
    <div style="background:#d4edda; color:#155724; padding:10px; border-radius:5px; margin-bottom:20px;"><?php echo $msg; ?></div>
<?php endif; ?>

<!-- TABS -->
<div class="tab-nav">
    <button class="tab-btn active" onclick="openTab('requests')">Requests</button>
    <button class="tab-btn" onclick="openTab('history')">History</button>
    <button class="tab-btn" onclick="openTab('methods')">Methods</button>
</div>

<!-- DATA: REQUESTS -->
<div id="tab-requests" class="tab-content active">
    <!-- COPIED FROM withdraws.php -->
    <div class="panel">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th> <th>User</th> <th>Amount</th> <th>Method</th> <th>Account No</th> <th>Action</th>
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
                            <td style="color:red; font-weight:bold;">৳ <?php echo $p['amount']; ?></td>
                            <td><?php echo $p['method']; ?></td>
                            <td><?php echo $p['account_number']; ?></td>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Confirm payment sent?')">
                                    <input type="hidden" name="req_action" value="approve">
                                    <input type="hidden" name="trx_id" value="<?php echo $p['id']; ?>">
                                    <button class="action-btn btn-green">Paid</button>
                                </form>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Reject and Refund?')">
                                    <input type="hidden" name="req_action" value="reject">
                                    <input type="hidden" name="trx_id" value="<?php echo $p['id']; ?>">
                                    <input type="hidden" name="user_id" value="<?php echo $p['user_id']; ?>">
                                    <input type="hidden" name="amount" value="<?php echo $p['amount']; ?>">
                                    <button class="action-btn btn-red">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (empty($pending)) echo "<p style='padding:15px; text-align:center;'>No pending withdraws.</p>"; ?>
        </div>
    </div>
</div>

<!-- DATA: HISTORY -->
<div id="tab-history" class="tab-content">
    <!-- COPIED FROM withdraw_history.php -->
    <div class="panel">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr><th>Date</th> <th>User</th> <th>Amount</th> <th>Method</th> <th>Account</th> <th>Status</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $h): ?>
                        <tr>
                            <td><?php echo date("d M y, h:i A", strtotime($h['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($h['name']); ?></td>
                            <td style="color:red; font-weight:bold;">-৳ <?php echo $h['amount']; ?></td>
                            <td><?php echo $h['method']; ?></td>
                            <td><?php echo $h['account_number']; ?></td>
                            <td>
                                <span class="badge <?php echo $h['status'] == 'approved' ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $h['status'] == 'approved' ? 'Paid' : 'Rejected'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div style="margin-top:20px; display:flex; gap:10px; justify-content:center;">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="action-btn btn-dark">Previous</a>
                <?php endif; ?>
                <a href="?page=<?php echo $page + 1; ?>" class="action-btn btn-dark">Next</a>
            </div>
        </div>
    </div>
</div>

<!-- DATA: METHODS -->
<div id="tab-methods" class="tab-content">
    <!-- COPIED FROM withdraw_methods.php -->
    <div class="panel" style="max-width:800px;">
        <form method="POST" style="margin-bottom:30px; background:#f9f9f9; padding:20px; border-radius:8px;">
            <h4 style="margin-top:0;">Add New Method</h4>
            <div style="display:flex; gap:10px;">
                <input type="text" name="name" class="input-field" placeholder="Method Name (e.g. USDT, Rocket)" required style="flex:1;">
                <button type="submit" name="add_method" class="action-btn btn-blue" style="width:auto;"><i class="fas fa-plus"></i> Add</button>
            </div>
        </form>

        <div class="table-responsive">
            <table>
                <thead><tr><th>ID</th> <th>Name</th> <th>Status</th> <th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($methods as $m): ?>
                        <tr>
                            <td><?php echo $m['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($m['name']); ?></strong></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="toggle_method_id" value="<?php echo $m['id']; ?>">
                                    <button class="badge <?php echo $m['status'] == 'active' ? 'badge-success' : 'badge-danger'; ?>" style="border:none; cursor:pointer;">
                                        <?php echo ucfirst($m['status']); ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Delete this method permanently?');" style="display:inline;">
                                    <input type="hidden" name="delete_method_id" value="<?php echo $m['id']; ?>">
                                    <button class="action-btn btn-red" style="padding:5px 10px; font-size:12px;"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Tab Switching Logic
    function openTab(name) {
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        
        document.getElementById('tab-' + name).classList.add('active');
        // Find button that calls this function. simple check
        event.target.classList.add('active');
        
        // Store in localStorage to persist on reload
        localStorage.setItem('activeWithdrawTab', name);
    }

    // Restore Tab
    const savedTab = localStorage.getItem('activeWithdrawTab');
    if(savedTab) {
        const btn = document.querySelector(`button[onclick="openTab('${savedTab}')"]`);
        if(btn) btn.click();
    }
</script>

<?php require_once 'includes/footer.php'; ?>
