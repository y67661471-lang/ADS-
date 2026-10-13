<?php
require_once '../config/db.php';
require_once 'includes/header.php';

// Debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Stats Queries
try {
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $pendingDep = $pdo->query("SELECT SUM(amount) FROM transactions WHERE type='deposit' AND status='pending'")->fetchColumn() ?: 0;
    $activeMatches = $pdo->query("SELECT COUNT(*) FROM matches WHERE status='ongoing'")->fetchColumn();
    $pendingWithdraw = $pdo->query("SELECT COUNT(*) FROM transactions WHERE type='withdraw' AND status='pending'")->fetchColumn();
} catch (Exception $e) {
    echo "<div style='background:red; color:white; padding:10px;'>SQL Error: " . $e->getMessage() . "</div>";
    $userCount = 0;
    $pendingDep = 0;
    $activeMatches = 0;
    $pendingWithdraw = 0;
}
?>

<div class="header-bar">
    <h2 class="page-title">Dashboard Overview</h2>
    <a href="dashboard.php" class="action-btn btn-orange"><i class="fas fa-sync-alt"></i> Refresh</a>
</div>

<div class="stats-grid">
    <div class="stat-card" style="border-bottom-color: #007bff;">
        <div class="stat-icon" style="background: #007bff;"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3>
                <?php echo $userCount; ?>
            </h3>
            <p>Total Users</p>
        </div>
    </div>
    <div class="stat-card" style="border-bottom-color: var(--success);">
        <div class="stat-icon" style="background: var(--success);"><i class="fas fa-wallet"></i></div>
        <div class="stat-info">
            <h3>
                <?php echo number_format($pendingDep, 2); ?>
            </h3>
            <p>Pending Deposits</p>
        </div>
    </div>
    <div class="stat-card" style="border-bottom-color: var(--primary);">
        <div class="stat-icon" style="background: var(--primary);"><i class="fas fa-gamepad"></i></div>
        <div class="stat-info">
            <h3>
                <?php echo $activeMatches; ?>
            </h3>
            <p>Active Matches</p>
        </div>
    </div>
    <div class="stat-card" style="border-bottom-color: var(--warning);">
        <div class="stat-icon" style="background: var(--warning);"><i class="fas fa-exclamation-circle"></i></div>
        <div class="stat-info">
            <h3>
                <?php echo $pendingWithdraw; ?>
            </h3>
            <p>Pending Withdrawals</p>
        </div>
    </div>
</div>

<div class="panel">
    <h3>Welcome to Admin Panel</h3>
    <p>Use the sidebar to manage users, deposits, and matches.</p>
</div>

<?php require_once 'includes/footer.php'; ?>