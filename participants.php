<?php
require_once '../config/db.php';
require_once 'includes/header.php';

if (!hasPermission('tournaments')) {
    echo "<div class='panel'>Access Denied</div>";
    require_once 'includes/footer.php';
    exit;
}

$mid = $_GET['mid'] ?? null;
if (!$mid) {
    echo "<div class='panel'>Invalid Match ID</div>";
    require_once 'includes/footer.php';
    exit;
}

// Handle Remove
if (isset($_POST['remove_pid'])) {
    $pid = $_POST['remove_pid'];
    // Logic to remove participant:
    // 1. Get match_id from participant
    // 2. Delete row
    // 3. Decrement joined_slots in matches
    // 4. Refund? (Optional, skipping for simple fix unless requested)

    $stmt = $pdo->prepare("DELETE FROM participants WHERE id = ?");
    $stmt->execute([$pid]);

    $pdo->prepare("UPDATE matches SET joined_slots = joined_slots - 1 WHERE id = ?")->execute([$mid]);

    echo "<div style='background:#d4edda; padding:10px; margin-bottom:10px; color:#155724;'>Participant removed.</div>";
}

// Fetch Match Info
$stmt = $pdo->prepare("SELECT title, total_slots, joined_slots FROM matches WHERE id = ?");
$stmt->execute([$mid]);
$match = $stmt->fetch();

// Fetch Participants
// user_id might be 0 if guest? Assuming linked to users table
$stmt = $pdo->prepare("SELECT p.*, u.name as user_name, u.email 
                       FROM participants p 
                       JOIN users u ON p.user_id = u.id 
                       WHERE p.match_id = ? 
                       ORDER BY p.id DESC");
$stmt->execute([$mid]);
$players = $stmt->fetchAll();

?>

<div class="header-bar">
    <h2 class="page-title">Participants:
        <?php echo htmlspecialchars($match['title']); ?>
    </h2>
    <a href="tournaments.php" class="action-btn btn-dark">Back</a>
</div>

<div class="panel">
    <p><strong>Slots:</strong>
        <?php echo $match['joined_slots']; ?> /
        <?php echo $match['total_slots']; ?>
    </p>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>User (App Account)</th>
                    <th>In-Game Data</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($players)): ?>
                    <tr>
                        <td colspan="4" style="text-align:center;">No participants yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($players as $k => $p): ?>
                        <tr>
                            <td>
                                <?php echo $k + 1; ?>
                            </td>
                            <td>
                                <strong>
                                    <?php echo htmlspecialchars($p['user_name']); ?>
                                </strong><br>
                                <small>
                                    <?php echo htmlspecialchars($p['email']); ?>
                                </small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($p['game_username']); ?>
                            </td>
                            <td>
                                <form method="POST"
                                    onsubmit="return confirm('Remove this player? (Balance will NOT be automatically refunded)');">
                                    <input type="hidden" name="remove_pid" value="<?php echo $p['id']; ?>">
                                    <button class="action-btn btn-red" style="padding:5px 10px; font-size:12px;">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
