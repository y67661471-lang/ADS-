<?php
require_once '../config/db.php';
require_once 'includes/header.php';

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    echo "<script>window.location.href='notification.php';</script>";
    exit;
}

// Handle Post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $body = $_POST['body'];

    if (!empty($title) && !empty($body)) {
        $stmt = $pdo->prepare("INSERT INTO notifications (title, message) VALUES (?, ?)");
        $stmt->execute([$title, $body]);
        $success = "Notification posted successfully!";
    }
}

// Fetch all notifications
$notifications = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC")->fetchAll();
?>

<div class="header-bar">
    <h2 class="page-title">Noticeboard Management</h2>
</div>

<div class="panel">
    <?php if (isset($success)): ?>
        <div style="background:#d4edda; color:#155724; padding:10px; margin-bottom:15px; border-radius:5px;">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <h3>Send New Notification</h3>
    <form method="POST">
        <div class="form-group">
            <label>Title</label>
            <input type="text" name="title" class="input-field" placeholder="e.g. Server Maintenance" required>
        </div>
        <div class="form-group">
            <label>Message</label>
            <textarea name="body" class="input-field" rows="4" placeholder="Enter notification details..."
                required></textarea>
        </div>
        <button type="submit" class="action-btn btn-orange">Post Notification</button>
    </form>
</div>

<div class="panel" style="margin-top:20px;">
    <h3>History</h3>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Title</th>
                    <th>Message</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($notifications)): ?>
                    <tr>
                        <td colspan="4" style="text-align:center;">No notifications found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($notifications as $n): ?>
                        <tr>
                            <td><?php echo date("d M Y h:i A", strtotime($n['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($n['title']); ?></td>
                            <td><?php echo htmlspecialchars(substr($n['message'], 0, 50)) . (strlen($n['message']) > 50 ? '...' : ''); ?>
                            </td>
                            <td>
                                <a href="notification.php?delete=<?php echo $n['id']; ?>" class="btn-delete"
                                    onclick="return confirm('Delete this notification?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    .btn-delete {
        background: #ff4444;
        color: #fff;
        padding: 5px 10px;
        text-decoration: none;
        border-radius: 4px;
        font-size: 12px;
    }
</style>

<?php require_once 'includes/footer.php'; ?>