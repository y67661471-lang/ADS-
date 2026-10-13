<?php
$pageTitle = "Notifications - RRR Esports";
require_once 'includes/functions.php';
require_once 'includes/header.php';

// Fetch Notifications
$notifStmt = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 50");
$notifs = $notifStmt->fetchAll();
?>

<section id="notifications-view" class="app-section active">
    <!-- Back Navigation -->
    <div class="back-nav"
        style="background:#fff; padding:10px 15px; margin-bottom:10px; display:flex; align-items:center; border-bottom:1px solid #eee; cursor:pointer;"
        onclick="window.location.href='index.php'">
        <i class="fas fa-arrow-left" style="margin-right:10px; color:#333;"></i>
        <span style="font-weight:600; color:#333;">Back to Home</span>
    </div>

    <div class="notif-container" style="padding:15px;">
        <?php if (empty($notifs)): ?>
            <div style="text-align:center; color:#999; margin-top:50px;">
                <i class="far fa-bell-slash" style="font-size:50px; margin-bottom:15px; color:#ddd;"></i>
                <p>No new notifications</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifs as $n): ?>
                <div class="notif-card"
                    style="background:#fff; padding:15px; border-radius:10px; margin-bottom:15px; box-shadow:0 2px 5px rgba(0,0,0,0.05); border-left:4px solid var(--primary);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                        <h3 style="margin:0; font-size:16px; font-weight:700; color:#333;">
                            <?php echo htmlspecialchars($n['title']); ?>
                        </h3>
                        <span style="font-size:11px; color:#999; background:#f5f5f5; padding:2px 8px; border-radius:10px;">
                            <?php echo timeAgo($n['created_at']); ?>
                        </span>
                    </div>
                    <p style="margin:0; font-size:13px; color:#666; line-height:1.6; white-space: pre-wrap;">
                        <?php echo htmlspecialchars($n['message']); ?>
                    </p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>