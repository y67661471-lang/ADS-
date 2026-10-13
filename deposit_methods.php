<?php
require_once '../config/db.php';
require_once 'includes/header.php';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_method'])) {
        $name = trim($_POST['name']);
        $number = trim($_POST['number']);
        $logo = trim($_POST['logo_url']);

        if (!empty($name) && !empty($number)) {
            $stmt = $pdo->prepare("INSERT INTO deposit_methods (name, account_number, logo_url) VALUES (?, ?, ?)");
            $stmt->execute([$name, $number, $logo]);
            $msg = "Method added successfully!";
        }
    } elseif (isset($_POST['delete_id'])) {
        $stmt = $pdo->prepare("DELETE FROM deposit_methods WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        $msg = "Method deleted successfully!";
    } elseif (isset($_POST['toggle_id'])) {
        $stmt = $pdo->prepare("UPDATE deposit_methods SET status = IF(status='active','inactive','active') WHERE id = ?");
        $stmt->execute([$_POST['toggle_id']]);
    }
}

$methods = $pdo->query("SELECT * FROM deposit_methods ORDER BY id DESC")->fetchAll();
?>

<div class="header-bar">
    <h2 class="page-title">Manage Deposit Methods</h2>
</div>

<div class="panel" style="max-width:800px;">
    <?php if (isset($msg))
        echo "<p style='color:green; padding:10px; border:1px solid green; margin-bottom:15px; border-radius:5px;'>$msg</p>"; ?>

    <!-- Add New Form -->
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

    <!-- List -->
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
                        <td>
                            <?php if (!empty($m['logo_url'])): ?>
                                <img src="<?php echo htmlspecialchars($m['logo_url']); ?>"
                                    style="width:30px; height:30px; object-fit:contain;">
                            <?php else: ?>
                                <i class="fas fa-wallet"></i>
                            <?php endif; ?>
                        </td>
                        <td><strong>
                                <?php echo htmlspecialchars($m['name']); ?>
                            </strong></td>
                        <td>
                            <?php echo htmlspecialchars($m['account_number']); ?>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="toggle_id" value="<?php echo $m['id']; ?>">
                                <button
                                    class="badge <?php echo $m['status'] == 'active' ? 'badge-success' : 'badge-danger'; ?>"
                                    style="border:none; cursor:pointer;">
                                    <?php echo ucfirst($m['status']); ?>
                                </button>
                            </form>
                        </td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Delete this method permanently?');"
                                style="display:inline;">
                                <input type="hidden" name="delete_id" value="<?php echo $m['id']; ?>">
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

<?php require_once 'includes/footer.php'; ?>
