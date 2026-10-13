<?php
require_once '../config/db.php';

// --- AJAX ACTION for SEARCH (Must be before header to output pure JSON) ---
if (isset($_GET['ajax_search_user'])) {

    // Disable any previous output
    ob_clean();
    header('Content-Type: application/json');

    $q = trim($_GET['q']);
    try {
        if (filter_var($q, FILTER_VALIDATE_EMAIL)) {
            $stmt = $pdo->prepare("SELECT id, name, email, balance_deposit, balance_winning FROM users WHERE email LIKE ? LIMIT 5");
            $stmt->execute(["%$q%"]);
        } else {
            $stmt = $pdo->prepare("SELECT id, name, email, balance_deposit, balance_winning FROM users WHERE id = ? OR name LIKE ? LIMIT 5");
            $stmt->execute([$q, "%$q%"]);
        }
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
        echo json_encode([]);
    }
    exit;
}

require_once 'includes/header.php';

$msg = '';

// --- POST HANDLING ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Request Actions
    if (isset($_POST['req_action'])) {
        $act = $_POST['req_action'];
        $id = $_POST['trx_id'];
        $uid = $_POST['user_id'] ?? null;
        $amount = $_POST['amount'] ?? 0;

        if ($act === 'approve') {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE transactions SET status = 'approved' WHERE id = ?");
            $stmt->execute([$id]);
            $stmt = $pdo->prepare("UPDATE users SET balance_deposit = balance_deposit + ? WHERE id = ?");
            $stmt->execute([$amount, $uid]);
            $pdo->commit();
            $msg = "Deposit request approved!";
        } elseif ($act === 'reject') {
            $stmt = $pdo->prepare("UPDATE transactions SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$id]);
            $msg = "Deposit request rejected.";
        }
    }
    // 2. Method Actions
    elseif (isset($_POST['add_method'])) {
        $name = trim($_POST['name']);
        $number = trim($_POST['number']);
        $logo = trim($_POST['logo_url']);

        if (!empty($name) && !empty($number)) {
            $stmt = $pdo->prepare("INSERT INTO deposit_methods (name, account_number, logo_url) VALUES (?, ?, ?)");
            $stmt->execute([$name, $number, $logo]);
            $msg = "Method added successfully!";
        }
    } elseif (isset($_POST['delete_method_id'])) {
        $stmt = $pdo->prepare("DELETE FROM deposit_methods WHERE id = ?");
        $stmt->execute([$_POST['delete_method_id']]);
        $msg = "Method deleted successfully!";

    } elseif (isset($_POST['toggle_method_id'])) {
        $stmt = $pdo->prepare("UPDATE deposit_methods SET status = IF(status='active','inactive','active') WHERE id = ?");
        $stmt->execute([$_POST['toggle_method_id']]);
    }
    // 3. Manual Transaction (Add/Deduct)
    elseif (isset($_POST['manual_action'])) {
        $uid = $_POST['user_uid'];
        $amount = floatval($_POST['amount']);
        $type = $_POST['m_type']; // add/deduct
        $note = $_POST['note'] ?? 'Manual Action';

        if ($uid && $amount > 0) {
            $pdo->beginTransaction();

            if ($type === 'add') {
                $stmt = $pdo->prepare("UPDATE users SET balance_deposit = balance_deposit + ? WHERE id = ?");
                $stmt->execute([$amount, $uid]);
                // Log as deposit
                $pdo->prepare("INSERT INTO transactions (user_id, amount, type, method, status, trx_id) VALUES (?, ?, 'deposit', 'Manual Credit', 'approved', ?)")->execute([$uid, $amount, $note]);
                $msg = "Successfully ADDED ৳$amount to user.";
            } else {
                // Deduct
                $stmt = $pdo->prepare("UPDATE users SET balance_deposit = balance_deposit - ? WHERE id = ?");
                $stmt->execute([$amount, $uid]);
                // Log as manual withdraw/deduction (using 'withdraw' type or custom)
                $pdo->prepare("INSERT INTO transactions (user_id, amount, type, method, status, trx_id) VALUES (?, ?, 'withdraw', 'Manual Debit', 'approved', ?)")->execute([$uid, $amount, $note]);
                $msg = "Successfully DEDUCTED ৳$amount from user.";
            }
            $pdo->commit();
        } else {
            $msg = "Invalid Amount or User.";
        }
    }
}


// --- DATA FETCHING ---

// 1. Requests (Pending)
$pending = $pdo->query("SELECT t.*, u.name, u.phone FROM transactions t JOIN users u ON t.user_id = u.id WHERE t.type='deposit' AND t.status='pending' ORDER BY t.id DESC")->fetchAll();

// 2. History (Pagination)
require_once 'includes/pagination_helper.php';

$limit = 50;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Count Total
$msg_count = $pdo->query("SELECT COUNT(*) FROM transactions WHERE type='deposit' AND status != 'pending'")->fetchColumn();
$total_records = $msg_count;

$history_stmt = $pdo->prepare("SELECT t.*, u.name FROM transactions t JOIN users u ON t.user_id = u.id WHERE t.type='deposit' AND t.status != 'pending' ORDER BY t.id DESC LIMIT ? OFFSET ?");
$history_stmt->bindValue(1, $limit, PDO::PARAM_INT);
$history_stmt->bindValue(2, $offset, PDO::PARAM_INT);
$history_stmt->execute();
$history = $history_stmt->fetchAll();

// 3. Methods
$methods = $pdo->query("SELECT * FROM deposit_methods ORDER BY id DESC")->fetchAll();

?>

<style>
    .tab-nav {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }

    .tab-btn {
        padding: 10px 20px;
        border: none;
        background: #eee;
        cursor: pointer;
        font-weight: 600;
        border-radius: 5px;
    }

    .tab-btn.active {
        background: var(--primary);
        color: white;
    }

    .tab-content {
        display: none;
        animation: fadeIn 0.3s;
    }

    .tab-content.active {
        display: block;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(5px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<div class="header-bar">
    <h2 class="page-title">Deposits Manager</h2>
</div>

<?php if ($msg): ?>
    <div style="background:#d4edda; color:#155724; padding:10px; border-radius:5px; margin-bottom:20px;"><?php echo $msg; ?>
    </div>
<?php endif; ?>

<!-- TABS -->
<div class="tab-nav">

    <button class="tab-btn active" onclick="openTab('requests')">Requests</button>
    <button class="tab-btn" onclick="openTab('history')">History</button>
    <button class="tab-btn" onclick="openTab('methods')">Methods</button>
    <button class="tab-btn" onclick="openTab('manual')">Manual Action</button>
</div>

<!-- DATA: REQUESTS -->
<div id="tab-requests" class="tab-content active">
    <div class="panel">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
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
                            <td><?php echo htmlspecialchars($p['name']); ?><br><small><?php echo htmlspecialchars($p['phone']); ?></small>
                            </td>
                            <td style="color:green; font-weight:bold;">৳ <?php echo $p['amount']; ?></td>
                            <td><?php echo $p['method']; ?></td>
                            <td><?php echo $p['account_number']; ?></td>
                            <td><?php echo $p['trx_id']; ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="req_action" value="approve">
                                    <input type="hidden" name="trx_id" value="<?php echo $p['id']; ?>">
                                    <input type="hidden" name="user_id" value="<?php echo $p['user_id']; ?>">
                                    <input type="hidden" name="amount" value="<?php echo $p['amount']; ?>">
                                    <button class="action-btn btn-green">Approve</button>
                                </form>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="req_action" value="reject">
                                    <input type="hidden" name="trx_id" value="<?php echo $p['id']; ?>">
                                    <button class="action-btn btn-red">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (empty($pending))
                echo "<p style='padding:15px; text-align:center;'>No pending requests.</p>"; ?>
        </div>
    </div>
</div>

<!-- DATA: HISTORY -->
<div id="tab-history" class="tab-content">
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
                            <td><?php echo date("d M y, h:i A", strtotime($h['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($h['name']); ?></td>
                            <td style="color:green; font-weight:bold;">+৳ <?php echo $h['amount']; ?></td>
                            <td><?php echo $h['method']; ?></td>
                            <td><?php echo $h['trx_id']; ?></td>
                            <td>
                                <span
                                    class="badge <?php echo $h['status'] == 'approved' ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo ucfirst($h['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </table>
            <?php renderPagination($page, $total_records, $limit, 'deposit_manager.php'); ?>
        </div>
    </div>
</div>

<!-- DATA: METHODS -->
<div id="tab-methods" class="tab-content">
    <div class="panel" style="max-width:800px;">
        <form method="POST" style="margin-bottom:30px; background:#f9f9f9; padding:20px; border-radius:8px;">
            <h4 style="margin-top:0;">Add New Method</h4>
            <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr; gap:10px;">
                <div class="form-group" style="margin:0;">
                    <label>Name</label>
                    <input type="text" name="name" class="input-field" placeholder="e.g. Bkash" required>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Number</label>
                    <input type="text" name="number" class="input-field" placeholder="017xxxxxxxx" required>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Logo URL (Optional)</label>
                    <input type="text" name="logo_url" class="input-field" placeholder="https://...">
                </div>
            </div>
            <button type="submit" name="add_method" class="action-btn btn-blue" style="margin-top:10px; width:auto;"><i
                    class="fas fa-plus"></i> Add Method</button>
        </form>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Logo</th>
                        <th>Name</th>
                        <th>Number</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($methods as $m): ?>
                        <tr>
                            <td><?php if (!empty($m['logo_url'])): ?><img
                                        src="<?php echo htmlspecialchars($m['logo_url']); ?>"
                                        style="width:30px; height:30px; object-fit:contain;"><?php else: ?><i
                                        class="fas fa-wallet"></i><?php endif; ?></td>
                            <td><strong><?php echo htmlspecialchars($m['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($m['account_number']); ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="toggle_method_id" value="<?php echo $m['id']; ?>">
                                    <button
                                        class="badge <?php echo $m['status'] == 'active' ? 'badge-success' : 'badge-danger'; ?>"
                                        style="border:none; cursor:pointer;"><?php echo ucfirst($m['status']); ?></button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Delete this method permanently?');"
                                    style="display:inline;">
                                    <input type="hidden" name="delete_method_id" value="<?php echo $m['id']; ?>">
                                    <button class="action-btn btn-red" style="padding:5px 10px; font-size:12px;"><i
                                            class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<!-- DATA: MANUAL ACTION -->
<div id="tab-manual" class="tab-content">
    <div class="panel" style="max-width:800px;">
        <h3>Manual Add / Deduct Funds</h3>

        <div class="form-group" style="position:relative;">
            <label>Search User (Email or ID)</label>
            <input type="text" id="user-search" class="input-field" placeholder="Type email or ID..."
                onkeyup="searchUser(this.value)">
            <div id="search-results"
                style="border:1px solid #ddd; display:none; position:absolute; width:100%; background:#fff; z-index:10; max-height:200px; overflow-y:auto; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
            </div>
        </div>

        <div id="selected-user-card"
            style="display:none; background:#f0f4f8; padding:15px; border-radius:8px; border:1px solid #d9e2ec; margin-bottom:20px;">
            <h4 style="margin:0 0 10px;">Selected User: <span id="u_name" style="color:var(--primary);"></span></h4>
            <p style="margin:5px 0; font-size:14px;">Email: <strong id="u_email"></strong></p>
            <div style="display:flex; gap:20px; margin-top:10px;">
                <span class="badge badge-success">Deposit: ৳<span id="u_bal_dep"></span></span>
                <span class="badge badge-warning">Winning: ৳<span id="u_bal_win"></span></span>
            </div>
        </div>

        <form method="POST" id="manual-form" style="display:none;">
            <input type="hidden" name="manual_action" value="1">
            <input type="hidden" name="user_uid" id="u_uid">

            <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group">
                    <label>Action</label>
                    <select name="m_type" class="input-field" onchange="updateBtnColor(this.value)">
                        <option value="add">Add Balance (+)</option>
                        <option value="deduct">Deduct Balance (-)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount</label>
                    <input type="number" name="amount" class="input-field" placeholder="0" required>
                </div>
            </div>
            <div class="form-group">
                <label>Note (Internal)</label>
                <input type="text" name="note" class="input-field" placeholder="Reason...">
            </div>

            <button type="submit" id="manual-btn" class="action-btn btn-green">Proceed Transaction</button>
        </form>
    </div>
</div>

<script>
    function searchUser(q) {
        const resBox = document.getElementById('search-results');
        if (q.length < 2) { resBox.style.display = 'none'; return; }

        fetch('?ajax_search_user=1&q=' + q)
            .then(res => res.json())
            .then(data => {
                resBox.innerHTML = '';
                if (data.length > 0) {
                    resBox.style.display = 'block';
                    data.forEach(user => {
                        const div = document.createElement('div');
                        div.style.padding = '10px';
                        div.style.borderBottom = '1px solid #eee';
                        div.style.cursor = 'pointer';
                        div.innerHTML = `<strong>${user.name}</strong> (${user.email})`;
                        div.onmouseover = function () { this.style.background = '#f9f9f9'; };
                        div.onmouseout = function () { this.style.background = '#fff'; };
                        div.onclick = function () { selectUser(user); };
                        resBox.appendChild(div);
                    });
                } else {
                    resBox.style.display = 'none';
                }
            });
    }

    function selectUser(user) {
        document.getElementById('search-results').style.display = 'none';
        document.getElementById('user-search').value = user.email; // Fill input

        // Show Card
        document.getElementById('selected-user-card').style.display = 'block';
        document.getElementById('u_name').innerText = user.name;
        document.getElementById('u_email').innerText = user.email;
        document.getElementById('u_bal_dep').innerText = user.balance_deposit;
        document.getElementById('u_bal_win').innerText = user.balance_winning;

        // Setup Form
        document.getElementById('manual-form').style.display = 'block';
        document.getElementById('u_uid').value = user.id;
    }

    function updateBtnColor(type) {
        const btn = document.getElementById('manual-btn');
        if (type === 'add') {
            btn.className = 'action-btn btn-green';
            btn.innerText = 'Add Funds (+)'
        } else {
            btn.className = 'action-btn btn-red';
            btn.innerText = 'Deduct Funds (-)'
        }
    }

    // Tab Switching Logic
    function openTab(name) {
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('tab-' + name).classList.add('active');
        event.target.classList.add('active');
        localStorage.setItem('activeDepositTab', name);
    }
    const savedTab = localStorage.getItem('activeDepositTab');
    if (savedTab) {
        const btn = document.querySelector(`button[onclick="openTab('${savedTab}')"]`);
        if (btn) btn.click();
    }
</script>

<?php require_once 'includes/footer.php'; ?>