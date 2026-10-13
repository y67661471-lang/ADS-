<?php
$pageTitle = "My Matches - RRR Esports";
require_once 'includes/functions.php';
require_once 'includes/header.php';

requireLogin();

$user_id = $_SESSION['user_id'];

// Fetch Joined Matches (Not Completed)
// Join matches with participants to find which matches the user joined
$stmt = $pdo->prepare("
    SELECT m.*, p.game_username 
    FROM matches m 
    JOIN participants p ON m.id = p.match_id 
    WHERE p.user_id = ? AND m.status != 'completed' 
    ORDER BY m.schedule_time ASC
");
$stmt->execute([$user_id]);
$matches = $stmt->fetchAll();

// We might want to remove duplicates if the user joined multiple times (e.g. Duo with 2 slots?), 
// but typically 'participants' has unique (match_id, user_id) or we want to show multiple entries?
// Let's assume grouping by match ID is better for display, or just show them.
// If user joined multiple times, they might want to see multiple entries?
// Let's group by Match ID to avoid duplicate cards for the same match.
$unique_matches = [];
foreach ($matches as $m) {
    $unique_matches[$m['id']] = $m;
}

?>

<section id="my-matches-view" class="app-section active">
    <!-- Back Navigation -->
    <div class="back-nav"
        style="background:#fff; padding:10px 15px; margin-bottom:10px; display:flex; align-items:center; border-bottom:1px solid #eee; cursor:pointer;"
        onclick="window.location.href='profile.php'">
        <i class="fas fa-arrow-left" style="margin-right:10px; color:#333;"></i>
        <span style="font-weight:600; color:#333;">Back to Profile</span>
    </div>

    <div id="matches-container" style="display:flex; flex-direction:column; gap:15px;">
        <?php if (empty($unique_matches)): ?>
            <div style='text-align:center; padding:30px; color:#666;'>
                <i class="fas fa-gamepad" style="font-size:40px; margin-bottom:10px; color:#ddd;"></i>
                <p>No upcoming matches joined.</p>
                <button class="primary-btn" style="width:auto; margin-top:15px;"
                    onclick="window.location.href='index.php'">Join a Match</button>
            </div>
        <?php else: ?>
            <?php foreach ($unique_matches as $m): ?>
                <?php
                // Get category details for icon
                $catStmt = $pdo->prepare("SELECT icon_url FROM categories WHERE id = ?");
                $catStmt->execute([$m['category_id']]);
                $category = $catStmt->fetch();

                $joinedCount = $m['joined_slots'];
                $totalSlots = $m['total_slots'];
                $progress = ($totalSlots > 0) ? ($joinedCount / $totalSlots) * 100 : 0;
                ?>
                <div class="match-card">
                    <div class="m-header">
                        <img src="<?php echo htmlspecialchars($category['icon_url'] ?? 'assets/img/game.png'); ?>" class="m-img"
                            alt="Game">
                        <div class="m-title-box">
                            <h3 class="m-title">
                                <?php echo htmlspecialchars($m['title']); ?>
                            </h3>
                            <div class="m-time">
                                <?php echo date("d M, h:i A", strtotime($m['schedule_time'])); ?>
                            </div>
                        </div>
                    </div>

                    <div class="m-stats-grid">
                        <div class="m-stat-box">
                            <span class="stat-label">PRIZE</span>
                            <span class="stat-value">৳
                                <?php echo $m['prize_pool']; ?>
                            </span>
                        </div>
                        <div class="m-stat-box">
                            <span class="stat-label">PER KILL</span>
                            <span class="stat-value">৳
                                <?php echo $m['per_kill']; ?>
                            </span>
                        </div>
                        <div class="m-stat-box">
                            <span class="stat-label">ENTRY</span>
                            <span class="stat-value">৳
                                <?php echo $m['entry_fee']; ?>
                            </span>
                        </div>
                        <div class="m-stat-box">
                            <span class="stat-label">TYPE</span>
                            <span class="stat-value">
                                <?php echo $m['type']; ?>
                            </span>
                        </div>
                        <div class="m-stat-box">
                            <span class="stat-label">MAP</span>
                            <span class="stat-value">
                                <?php echo $m['map']; ?>
                            </span>
                        </div>
                    </div>

                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $progress; ?>%;"></div>
                    </div>
                    <div class="m-status-row">
                        <span>
                            <?php echo $joinedCount; ?> Joined
                        </span>
                        <span>
                            <?php echo $totalSlots - $joinedCount; ?> Available
                        </span>
                    </div>

                    <div class="m-btn-row" style="margin-top:15px;">
                        <!-- Room ID Button Logic -->
                        <button class="btn-room"
                            onclick="openRoomModal('<?php echo $m['room_id'] ?: 'Wait'; ?>', '<?php echo $m['room_pass'] ?: 'Wait'; ?>')">ROOM
                            ID</button>
                    </div>

                    <div class="timer-display match-countdown" data-time="<?php echo strtotime($m['schedule_time']); ?>">
                        Loading...
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- Room ID Modal -->
<div id="room-modal" class="modal">
    <div class="modal-box" style="text-align:center; padding: 25px;">
        <h3 style="margin-bottom:20px; font-weight:700; font-size:18px;">Room Details</h3>

        <div style="margin-bottom:20px;">
            <p style="font-size:14px; color:#666; margin-bottom:5px;">Room ID</p>
            <div style="display:flex; justify-content:center; align-items:center; gap:10px;">
                <span id="room-id-val" style="font-weight:700; font-size:22px; color:#000;"></span>
                <i class="fas fa-copy" onclick="copyText('room-id-val')"
                    style="cursor:pointer; color:#666; font-size:18px;"></i>
            </div>
        </div>

        <div style="margin-bottom:30px;">
            <p style="font-size:14px; color:#666; margin-bottom:5px;">Password</p>
            <div style="display:flex; justify-content:center; align-items:center; gap:10px;">
                <span id="room-pass-val" style="font-weight:700; font-size:22px; color:#000;"></span>
                <i class="fas fa-copy" onclick="copyText('room-pass-val')"
                    style="cursor:pointer; color:#666; font-size:18px;"></i>
            </div>
        </div>

        <button class="btn-cancel" onclick="document.getElementById('room-modal').classList.remove('active')"
            style="margin-top:10px; width:100%; background:#f5f5f5; color:#333; font-weight:600; padding:12px; border-radius:8px; border:none;">CLOSE</button>
    </div>
</div>

<script>
    function openRoomModal(roomId, roomPass) {
        document.getElementById('room-id-val').innerText = roomId;
        document.getElementById('room-pass-val').innerText = roomPass;
        document.getElementById('room-modal').classList.add('active');
    }

    function copyText(elementId) {
        const text = document.getElementById(elementId).innerText;
        if (text === 'Wait') return;

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(() => {
                alert("Copied!");
            }).catch(err => {
                fallbackCopy(text);
            });
        } else {
            fallbackCopy(text);
        }
    }

    function fallbackCopy(text) {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";
        textArea.style.left = "-9999px";
        textArea.style.top = "0";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            const successful = document.execCommand('copy');
            if (successful) {
                alert("Copied!");
            } else {
                alert("Failed to copy. Please copy manually.");
            }
        } catch (err) {
            alert("Failed to copy. Please copy manually.");
        }
        document.body.removeChild(textArea);
    }
</script>

<?php require_once 'includes/footer.php'; ?>