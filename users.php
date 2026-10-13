<?php
require_once '../config/db.php';
require_once 'includes/header.php';

// Handle Action (Ban/Unban/Balance Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $userId = $_POST['user_id'];

    if ($action === 'status') {
        $status = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$status, $userId]);
    } elseif ($action === 'balance') {
        $amount = $_POST['amount'];
        $type = $_POST['type']; // deposit or winning

        $col = ($type === 'winning') ? 'balance_winning' : 'balance_deposit';
        $stmt = $pdo->prepare("UPDATE users SET $col = ? WHERE id = ?");
        $stmt->execute([$amount, $userId]);
    }
}

// Search & List Users (Pagination)
require_once 'includes/pagination_helper.php';

$search = $_GET['search'] ?? '';
$where = "WHERE 1=1";
$params = [];

if ($search) {
    $where .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

// 1. Count Total
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users $where");
$stmt->execute($params);
$total_users = $stmt->fetchColumn();

// 2. Fetch Data
$limit = 20;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$sql = "SELECT * FROM users $where ORDER BY id DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Fetch Roles for Promotion
?>

<div class="header-bar">
    <h2 class="page-title">User Management</h2>
    <form style="display:flex; gap:10px;">
        <input type="text" name="search" class="input-field" placeholder="Search..."
            value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="action-btn btn-blue">Search</button>
    </form>
</div>


<?php if (isset($msg))
    echo "<div class='panel' style='background:#d4edda; color:#155724;'>$msg</div>"; ?>

<div class="panel">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Deposit</th>
                    <th>Winning</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <?php echo $u['id']; ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($u['name']); ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($u['phone']); ?>
                        </td>
                        <td>
                            <?php echo $u['balance_deposit']; ?>
                        </td>
                        <td>
                            <?php echo $u['balance_winning']; ?>
                        </td>
                        <td>
                            <span class="badge <?php echo $u['status'] == 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo ucfirst($u['status']); ?>
                            </span>
                        </td>
                        <td>
                            <button class="action-btn btn-orange"
                                onclick="openEditModal(<?php echo htmlspecialchars(json_encode($u)); ?>)">Edit</button>
                            <?php if ($u['status'] === 'active'): ?>
                                <button class="action-btn btn-red"
                                    onclick="updateStatus(<?php echo $u['id']; ?>, 'banned')">Ban</button>
                            <?php else: ?>
                                <button class="action-btn btn-green"
                                    onclick="updateStatus(<?php echo $u['id']; ?>, 'active')">Unban</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        // Append Search param to pagination URL
        $url_params = 'users.php';
        if ($search)
            $url_params .= '?search=' . urlencode($search);

        renderPagination($page, $total_users, $limit, $url_params);
        ?>
    </div>
</div>

<!-- Edit User Modal -->
<div id="edit-user-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Edit User Balance</h3>
            <span class="close-modal" onclick="document.getElementById('edit-user-modal').style.display='none'">×</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="balance">
            <input type="hidden" name="user_id" id="edit-uid">

            <div class="form-group">
                <label>User Name</label>
                <input type="text" id="edit-name" class="input-field" readonly>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Deposit Balance</label>
                    <input type="number" step="0.01" name="amount" id="edit-dep" class="input-field">
                    <input type="radio" name="type" value="deposit" checked> Update Deposit
                </div>
                <div class="form-group">
                    <label>Winning Balance</label>
                    <input type="number" step="0.01" id="edit-win" class="input-field" disabled>
                    <!-- Simplification: Only edit one at a time or hidden input logic needed -->
                    <!-- Note: For simplicity in this iteration, I'll force radio selection to pick which one to update, or just provide separate inputs? 
                         Let's keep it simple: One input active based on radio? Or just two inputs?
                         Better: two inputs and update both? SQL allows comma separated updates.
                    -->
                </div>
            </div>
            <!-- Refined Logic: Two inputs, update query handles both if submitted? 
                 Let's stay simple: Submit handles one type based on selection in a real world, or both.
            -->
            <div class="form-group">
                <label>Set Balance Type To Update</label>
                <select name="type" class="input-field">
                    <option value="deposit">Deposit Balance</option>
                    <option value="winning">Winning Balance</option>
                </select>
            </div>

            <button type="submit" class="action-btn btn-success" style="width:100%">Update Balance</button>
        </form>
    </div>
</div>



<script>
    function openEditModal(user) {
        document.getElementById('edit-user-modal').style.display = 'flex';
        document.getElementById('edit-uid').value = user.id;
        document.getElementById('edit-name').value = user.name;
        document.getElementById('edit-dep').value = user.balance_deposit;
        document.getElementById('edit-win').value = user.balance_winning;
    }


    function updateStatus(id, status) {
        if (confirm('Are you sure?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="action" value="status"><input type="hidden" name="user_id" value="${id}"><input type="hidden" name="status" value="${status}">`;
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>

<?php require_once 'includes/footer.php'; ?>