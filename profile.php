<?php
$pageTitle = "My Profile - RRR Esports";
require_once 'includes/functions.php';
require_once 'includes/header.php';
requireLogin();

$user = getUser($pdo, $_SESSION['user_id']);
?>

<section id="profile" class="app-section active">
    <div class="card-panel" style="text-align:center;">
        <img id="profile-page-avatar"
            src="<?php echo getSetting('default_avatar_url') ?: 'https://cdn-icons-png.flaticon.com/512/149/149071.png'; ?>"
            style="width:90px; border-radius:50%; margin-bottom:15px; border:3px solid var(--primary); padding:2px;">
        <h2 id="profile-page-name">
            <?php echo htmlspecialchars($user['name']); ?>
        </h2>
        <p id="profile-page-email">
            <?php echo htmlspecialchars($user['email']); ?>
        </p>
        <p id="profile-page-phone" style="color:#777; font-size:14px; margin-top:5px; font-weight:600;">
            <?php echo htmlspecialchars($user['phone']); ?>
        </p>

        <div
            style="display:flex; justify-content:space-between; margin-top:25px; border-top:1px solid #eee; padding-top:20px;">
            <div style="text-align:center; flex:1; border-right:1px solid #eee;">
                <span style="font-size:12px; color:#888; text-transform:uppercase;">Deposit Balance</span>
                <h3 style="color:#333; font-size:24px;">
                    <?php echo number_format($user['balance_deposit'], 2); ?> ৳
                </h3>
            </div>
            <div style="text-align:center; flex:1;">
                <span style="font-size:12px; color:#888; text-transform:uppercase;">Winning Balance</span>
                <h3 style="color:var(--primary); font-size:24px;">
                    <?php echo number_format($user['balance_winning'], 2); ?> ৳
                </h3>
            </div>
        </div>
    </div>

    <div class="category-grid">

        <button class="primary-btn" style="margin:0; background:#333; margin-bottom:10px;"
            onclick="window.location.href='history.php'">History</button>
        <button class="primary-btn" style="margin:0;" onclick="window.location.href='wallet.php?action=add'">Add
            Money</button>
        <button class="primary-btn" style="margin-top:10px; background: #fff; color: #333; border: 1px solid #ddd;"
            onclick="window.location.href='wallet.php?action=withdraw'">Withdraw</button>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>