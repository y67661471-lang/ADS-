<?php
$pageTitle = "Match Result";
require_once 'includes/functions.php';
require_once 'includes/header.php';

$match_id = $_GET['id'] ?? null;

if (!$match_id) {
    echo "<div class='app-section active'><p>Invalid Match ID.</p></div>";
    require_once 'includes/footer.php';
    exit;
}

// Fetch Match Details
$stmt = $pdo->prepare("SELECT * FROM matches WHERE id = ?");
$stmt->execute([$match_id]);
$match = $stmt->fetch();

if (!$match) {
    echo "<div class='app-section active'><p>Match not found.</p></div>";
    require_once 'includes/footer.php';
    exit;
}

// Fetch Participants (Results)
// Sorted by Win Amount DESC, then Kills DESC
$stmt = $pdo->prepare("
    SELECT p.*, u.name, p.game_username as u_ign 
    FROM participants p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.match_id = ? 
    ORDER BY p.win_amount DESC, p.kills DESC
");
$stmt->execute([$match_id]);
$participants = $stmt->fetchAll();

?>

<section id="result-view" class="app-section active">
    <div class="back-nav"
        onclick="window.location.href='matches.php?cat_id=<?php echo $match['category_id']; ?>&tab=result'">
        <i class="fas fa-arrow-left"></i> Back to Matches
    </div>

    <div class="match-card" style="margin-top:15px;">
        <h3 class="m-title" style="margin-bottom:5px;">
            <?php echo htmlspecialchars($match['title']); ?>
        </h3>
        <p style="font-size:12px; color:#aaa;">
            Completed on:
            <?php echo date("d M, h:i A", strtotime($match['schedule_time'])); ?>
        </p>

        <div class="m-stats-grid" style="margin-top:15px;">
            <div class="m-stat-box">
                <span class="stat-label">TOTAL PRIZE</span>
                <span class="stat-value">৳
                    <?php echo $match['prize_pool']; ?>
                </span>
            </div>
            <div class="m-stat-box">
                <span class="stat-label">PER KILL</span>
                <span class="stat-value">৳
                    <?php echo $match['per_kill']; ?>
                </span>
            </div>
            <div class="m-stat-box">
                <span class="stat-label">ENTRY FEE</span>
                <span class="stat-value">৳
                    <?php echo $match['entry_fee']; ?>
                </span>
            </div>
        </div>
    </div>

    <div class="panel">
        <h3 style="margin-bottom:15px; border-left:4px solid var(--primary); padding-left:10px;">Leaderboard</h3>

        <?php if (empty($participants)): ?>
            <p style="text-align:center; color:#777; padding:20px;">No participants or results yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr style="border-bottom:1px solid var(--border-color); color:var(--text-light); text-align:left;">
                            <th style="padding:10px;">#</th>
                            <th style="padding:10px;">Player</th>
                            <th style="padding:10px; text-align:center;">Kills</th>
                            <th style="padding:10px; text-align:right;">Winning</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rank = 1;
                        foreach ($participants as $p):
                            $isWinner = $p['win_amount'] > 0;
                            $rowStyle = $isWinner ? "background:rgba(255, 85, 0, 0.1);" : "";
                            ?>
                            <tr
                                style="border-bottom:1px solid var(--border-color); color:var(--text-dark); <?php echo $rowStyle; ?>">
                                <td style="padding:12px 10px;">
                                    <?php
                                    if ($rank == 1)
                                        echo "👑";
                                    elseif ($rank == 2)
                                        echo "🥈";
                                    elseif ($rank == 3)
                                        echo "🥉";
                                    else
                                        echo "#" . $rank;
                                    ?>
                                </td>
                                <td style="padding:12px 10px;">
                                    <div style="font-weight:bold; color:var(--text-dark);">
                                        <?php echo htmlspecialchars($p['u_ign'] ?: $p['game_username']); ?></div>
                                </td>
                                <td style="padding:12px 10px; text-align:center; font-weight:bold; color:var(--text-dark);">
                                    <?php echo $p['kills']; ?>
                                </td>
                                <td style="padding:12px 10px; text-align:right; font-weight:bold; color:var(--primary);">
                                    <?php echo number_format($p['win_amount'], 2); ?> ৳
                                </td>
                            </tr>
                            <?php
                            $rank++;
                        endforeach;
                        ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</section>

<?php require_once 'includes/footer.php'; ?>
