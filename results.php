<?php
require_once '../config/db.php';
require_once 'includes/header.php';

// HANDLE RESULT SUBMISSION (AND EDIT)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_results'])) {
    $match_id = $_POST['match_id'];
    $users = $_POST['users'] ?? []; // Array of user_ids (real_uids)
    $kills = $_POST['kills'] ?? []; // Array mapped by user_id
    $prizes = $_POST['prizes'] ?? []; // Array mapped by user_id
    $per_kill_rate = $_POST['per_kill_rate'];

    $pdo->beginTransaction();
    try {
        foreach ($users as $uid) {
            $k = (int) ($kills[$uid] ?? 0);
            $win = (float) ($prizes[$uid] ?? 0);

            // Calculate New Total Win: Base Prize + (Kills * Rate)
            $new_total_win = $win + ($k * $per_kill_rate);

            // Fetch Old Data (for adjustment)
            $stmt = $pdo->prepare("SELECT kills, win_amount FROM participants WHERE match_id = ? AND user_id = ?");
            $stmt->execute([$match_id, $uid]);
            $old_record = $stmt->fetch();

            $old_total_win = $old_record ? (float) $old_record['win_amount'] : 0;
            $diff = $new_total_win - $old_total_win;

            // 1. Update Participant Record
            $stmt = $pdo->prepare("UPDATE participants SET kills = ?, win_amount = ? WHERE match_id = ? AND user_id = ?");
            $stmt->execute([$k, $new_total_win, $match_id, $uid]);

            // 2. Adjust User Balance (Only if there is a difference)
            if (abs($diff) > 0) {
                // If diff is positive (e.g. 50 -> 100, diff=50), add to balance
                // If diff is negative (e.g. 100 -> 50, diff=-50), add negative (subtract)
                $stmt = $pdo->prepare("UPDATE users SET balance_winning = balance_winning + ? WHERE id = ?");
                $stmt->execute([$diff, $uid]);

                // 3. Log Transaction
                $type = $diff > 0 ? 'deposit' : 'withdraw'; // Just for classification, though negative deposit works too
                $status = 'approved';
                $note = "Match #$match_id Result " . ($diff > 0 ? "Credit" : "Adjustment");

                $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, method, status, trx_id) VALUES (?, ?, ?, 'Match Result', ?, ?)");
                $stmt->execute([$uid, abs($diff), $type, $status, $note]);
            }
        }

        // Mark Match Completed
        $pdo->prepare("UPDATE matches SET status = 'completed' WHERE id = ?")->execute([$match_id]);

        $pdo->commit();
        $msg = "Results updated successfully!";
        // Remove query param to close modal
        echo "<script>window.location.href='results.php?tab=history';</script>";
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $err = "Error: " . $e->getMessage();
    }
}

// Fetch Pending and Completed Matches
$pending_matches = $pdo->query("SELECT * FROM matches WHERE status != 'completed' ORDER BY id DESC")->fetchAll();
$completed_matches = $pdo->query("SELECT * FROM matches WHERE status = 'completed' ORDER BY id DESC LIMIT 50")->fetchAll();

// Modal Logic: Fetch Participants if MID is set
$modal_active = false;
$participants = [];
$selected_match = null;

if (isset($_GET['mid'])) {
    $mid = $_GET['mid'];
    $stmt = $pdo->prepare("SELECT * FROM matches WHERE id = ?");
    $stmt->execute([$mid]);
    $selected_match = $stmt->fetch();

    if ($selected_match) {
        $modal_active = true;
        // Fetch participants
        $stmt = $pdo->prepare("SELECT p.*, u.name, u.phone, u.id as real_uid FROM participants p JOIN users u ON p.user_id = u.id WHERE p.match_id = ?");
        $stmt->execute([$mid]);
        $participants = $stmt->fetchAll();
    }
}
?>

<div class="header-bar">
    <h2 class="page-title">Result Submission</h2>
</div>

<!-- Tabs -->
<div style="margin-bottom:20px;">
    <a href="?tab=pending"
        class="tab-btn <?php echo (!isset($_GET['tab']) || $_GET['tab'] == 'pending') ? 'active' : ''; ?>">Pending
        Matches</a>
    <a href="?tab=history"
        class="tab-btn <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'history') ? 'active' : ''; ?>">Result
        History</a>
</div>

<style>
    .tab-btn {
        padding: 10px 20px;
        background: #e9ecef;
        color: #333;
        text-decoration: none;
        border-radius: 5px;
        margin-right: 10px;
        font-weight: 600;
    }

    .tab-btn.active {
        background: var(--primary);
        color: white;
    }
</style>

<div class="panel">
    <?php if (isset($msg))
        echo "<p style='color:green; padding:10px; border:1px solid green;'>$msg</p>"; ?>
    <?php if (isset($err))
        echo "<p style='color:red; padding:10px; border:1px solid red;'>$err</p>"; ?>

    <?php if (!isset($_GET['tab']) || $_GET['tab'] == 'pending'): ?>
        <!-- PENDING MATCHES -->
        <h3>Pending Matches</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Match</th>
                        <th>Date</th>
                        <th>Joined</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_matches as $m): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($m['title']); ?></strong><br><small>ID:
                                    <?php echo $m['id']; ?></small></td>
                            <td><?php echo date("d/m/Y, h:i A", strtotime($m['schedule_time'])); ?></td>
                            <td><?php echo $m['joined_slots']; ?></td>
                            <td>
                                <a href="?mid=<?php echo $m['id']; ?>&tab=pending" class="action-btn btn-blue">Declare
                                    Result</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($pending_matches)): ?>
                        <tr>
                            <td colspan="4" style="text-align:center; padding:20px;">No pending matches.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <!-- HISTORY MATCHES -->
        <h3>Result History</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Match</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($completed_matches as $m): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($m['title']); ?></strong><br><small>ID:
                                    <?php echo $m['id']; ?></small></td>
                            <td><?php echo date("d/m/Y, h:i A", strtotime($m['schedule_time'])); ?></td>
                            <td><span class="badge badge-success">Completed</span></td>
                            <td>
                                <a href="?mid=<?php echo $m['id']; ?>&tab=history" class="action-btn btn-orange">Edit Result</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($completed_matches)): ?>
                        <tr>
                            <td colspan="4" style="text-align:center; padding:20px;">No completed matches found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>
</div>

<!-- Distribute Result Modal -->
<?php if ($modal_active && $selected_match): ?>
    <div class="modal active" style="display:flex;">
        <div class="modal-content" style="width: 800px; max-width: 95%;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <?php echo $selected_match['status'] == 'completed' ? 'Edit Result: ' : 'Distribute Prizes: ';
                    echo htmlspecialchars($selected_match['title']); ?>
                </h3>
                <a href="results.php?tab=<?php echo $_GET['tab'] ?? 'pending'; ?>" class="close-modal">×</a>
            </div>

            <form method="POST">
                <input type="hidden" name="submit_results" value="1">
                <input type="hidden" name="match_id" value="<?php echo $selected_match['id']; ?>">
                <input type="hidden" name="per_kill_rate" value="<?php echo $selected_match['per_kill']; ?>">

                <div style="background:#f8f9fa; padding:10px; border-radius:5px; margin-bottom:15px; font-size:13px;">
                    <strong>Per Kill Rate:</strong> ৳<?php echo $selected_match['per_kill']; ?> <br>
                    <small>Winning = (Kills × Rate) + Win Prize. <br>
                        <span style="color:red;">Warning: Changing values here will automatically adjust user balances
                            (Credit/Debit difference).</span></small>
                </div>

                <div class="table-responsive" style="max-height:50vh; overflow-y:auto;">
                    <table style="border-collapse: collapse;">
                        <thead style="position:sticky; top:0; z-index:10;">
                            <tr>
                                <th style="background:#eee;">User</th>
                                <th style="background:#eee;">UID</th>
                                <th style="background:#eee;">Kills</th>
                                <th style="background:#eee;">Win Prize (Bonus)</th>
                                <th style="background:#eee;">Total Win</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($participants as $p): ?>
                                <?php
                                // Calculate existing derived prize to separate placement prize from total
                                // Current DB stores 'win_amount' (Total) and 'kills'.
                                // We need to reverse calculate Placement Prize to show in input.
                                // Placement = Total - (Kills * Rate)
                                $current_total = (float) $p['win_amount'];
                                $current_kills = (int) $p['kills'];
                                $rate = (float) $selected_match['per_kill'];
                                $placement_prize = max(0, $current_total - ($current_kills * $rate));
                                ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($p['name']); ?>
                                        <br><small
                                            style="color:#777;"><?php echo htmlspecialchars($p['game_username']); ?></small>
                                    </td>
                                    <td><?php echo $p['real_uid']; ?></td>
                                    <td>
                                        <input type="hidden" name="users[]" value="<?php echo $p['real_uid']; ?>">
                                        <input type="number" name="kills[<?php echo $p['real_uid']; ?>]" class="input-field"
                                            value="<?php echo $current_kills; ?>" style="width:80px; padding:8px;">
                                    </td>
                                    <td>
                                        <input type="number" name="prizes[<?php echo $p['real_uid']; ?>]" class="input-field"
                                            value="<?php echo $placement_prize; ?>" style="width:120px; padding:8px;">
                                    </td>
                                    <td>
                                        ৳<?php echo $current_total; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($participants)): ?>
                                <tr>
                                    <td colspan="5" style="text-align:center;">No participants joined yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div style="margin-top:20px; text-align:right;">
                    <a href="results.php?tab=<?php echo $_GET['tab'] ?? 'pending'; ?>" class="action-btn btn-dark"
                        style="text-decoration:none;">Cancel</a>
                    <button type="submit" class="action-btn btn-success"
                        onclick="return confirm('Confirm Update? This will adjust user balances.')">
                        <?php echo $selected_match['status'] == 'completed' ? 'Update Results' : 'Submit & Distribute'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
