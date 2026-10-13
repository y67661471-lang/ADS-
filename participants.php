<?php
$pageTitle = "Participants - RRR Esports";
require_once 'includes/functions.php';
require_once 'includes/header.php';

$match_id = $_GET['id'] ?? null;

if (!$match_id) {
    echo "<div class='app-section active'><p style='padding:20px; text-align:center;'>Invalid Match ID.</p></div>";
    require_once 'includes/footer.php';
    exit;
}

// Fetch Match Info
$stmt = $pdo->prepare("SELECT * FROM matches WHERE id = ?");
$stmt->execute([$match_id]);
$match = $stmt->fetch();

if (!$match) {
    echo "<div class='app-section active'><p style='padding:20px; text-align:center;'>Match not found.</p></div>";
    require_once 'includes/footer.php';
    exit;
}

// Fetch Participants
$stmt = $pdo->prepare("SELECT p.*, u.name as user_name 
                       FROM participants p 
                       JOIN users u ON p.user_id = u.id 
                       WHERE p.match_id = ? 
                       ORDER BY p.id ASC");
$stmt->execute([$match_id]);
$players = $stmt->fetchAll();

$joinedCount = count($players);
$totalSlots = $match['total_slots'];

?>

<section class="app-section active">
    <!-- Back Button -->
    <div class="back-nav" onclick="window.location.href='matches.php?cat_id=<?php echo $match['category_id']; ?>'">
        <i class="fas fa-arrow-left"></i> Back to Matches
    </div>

    <div style="padding: 15px;">
        <h2 style="margin-bottom: 5px; font-size: 18px;">
            <?php echo htmlspecialchars($match['title']); ?>
        </h2>
        <p style="color: #888; font-size: 14px; margin-bottom: 15px;">
            <?php echo date("d M, h:i A", strtotime($match['schedule_time'])); ?>
        </p>

        <div
            style="background: var(--card-bg, #fff); border-radius: 10px; padding: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <div
                style="display: flex; justify-content: space-between; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                <span style="font-weight: bold;">Participants (
                    <?php echo $joinedCount; ?>/
                    <?php echo $totalSlots; ?>)
                </span>
                <span style="color: <?php echo $joinedCount >= $totalSlots ? 'red' : 'green'; ?>">
                    <?php echo $joinedCount >= $totalSlots ? 'Full' : 'Open'; ?>
                </span>
            </div>

            <?php if (empty($players)): ?>
                <div style="text-align: center; padding: 20px; color: #999;">
                    No participants yet. Be the first to join!
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                        <thead>
                            <tr style="text-align: left; color: #666; font-size: 12px; border-bottom: 1px solid #ddd;">
                                <th style="padding: 8px;">#</th>
                                <th style="padding: 8px;">Player Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($players as $index => $player): ?>
                                <tr style="border-bottom: 1px solid #f5f5f5;">
                                    <td style="padding: 10px 8px; color: #888;">
                                        <?php echo $index + 1; ?>
                                    </td>
                                    <td style="padding: 10px 8px;">
                                        <div style="font-weight: 600;">
                                            <?php echo htmlspecialchars($player['user_name']); ?>
                                        </div>
                                        <?php if (!empty($player['game_username'])): ?>
                                            <div style="font-size: 11px; color: #999;">IGN:
                                                <?php echo htmlspecialchars($player['game_username']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>