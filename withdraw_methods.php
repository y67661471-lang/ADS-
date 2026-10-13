<?php
require_once '../config/db.php';
require_once 'includes/header.php';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_method'])) {
        $name = trim($_POST['name']);
        if (!empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO withdraw_methods (name) VALUES (?)");
            $stmt->execute([$name]);
            $msg = "Method added successfully!";
        }
    } elseif (isset($_POST['delete_id'])) {
        $stmt = $pdo->prepare("DELETE FROM withdraw_methods WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        $msg = "Method deleted successfully!";
    } elseif (isset($_POST['toggle_id'])) {
        $stmt = $pdo->prepare("UPDATE withdraw_methods SET status = IF(status='active','inactive','active') WHERE id = ?");
        $stmt->execute([$_POST['toggle_id']]);
        // No message needed, just refresh
    }
}

$methods = $pdo->query("SELECT * FROM withdraw_methods ORDER BY id DESC")->fetchAll();
?>

<div class="header-bar">
    <h2 class="page-title">Manage Withdraw Methods</h2>
</div>

<div class="panel" style="max-width:800px;">
    <?php if (isset($msg))
        echo "<p style='color:green; padding:10px; border:1px solid green; margin-bottom:15px; border-radius:5px;'>$msg</p>"; ?>

    <!-- Add New Form -->
    <form method="POST" style="margin-bottom:30px; background:#f9f9f9; padding:20px; border-radius:8px;">
        <h4 style="margin-top:0;">Add New Method</h4>
        <div style="display:flex; gap:10px;">
            <input type="text" name="name" class="input-field" placeholder="Method Name (e.g. USDT, Rocket)" required
                style="flex:1;">
            <button type="submit" name="add_method" class="action-btn btn-blue" style="width:auto;"><i
                    class="fas fa-plus"></i> Add</button>
        </div>
    </form>

    <!-- List -->
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($methods as $m): ?>
                    <tr>
                        <td>
                            <?php echo $m['id']; ?>
                        </td>
                        <td><strong>
                                <?php echo htmlspecialchars($m['name']); ?>
                            </strong></td>
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
