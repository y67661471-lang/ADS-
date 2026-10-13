<?php
$pageTitle = "Matches - RRR Esports";
require_once 'includes/functions.php';
require_once 'includes/header.php';

$cat_id = $_GET['cat_id'] ?? null;
$tab = $_GET['tab'] ?? 'play';

if (!$cat_id) {
    echo "<div class='app-section active'><p>Invalid Category.</p></div>";
    require_once 'includes/footer.php';
    exit;
}

// Fetch Category
$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$cat_id]);
$category = $stmt->fetch();

if (!$category) {
    echo "<div class='app-section active'><p>Category not found.</p></div>";
    require_once 'includes/footer.php';
    exit;
}

$statusCondition = ($tab === 'result') ? "status = 'completed'" : "status != 'completed'";
$stmt = $pdo->prepare("SELECT * FROM matches WHERE category_id = ? AND $statusCondition ORDER BY id DESC");
$stmt->execute([$cat_id]);
$matches = $stmt->fetchAll();

?>
<?php
// Fetch User's Joined Matches
$myJoinedMatches = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT match_id FROM participants WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $myJoinedMatches = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>

<section id="matches-view" class="app-section active">
    <div class="back-nav" onclick="window.location.href='index.php'">
        <i class="fas fa-arrow-left"></i> Back to Categories
    </div>

    <div class="how-to-join-btn">
        How to join info... (Static or Dynamic)
    </div>

    <div class="tab-container">
        <div class="tab-btn <?php echo $tab === 'play' ? 'active' : ''; ?>"
            onclick="window.location.href='matches.php?cat_id=<?php echo $cat_id; ?>&tab=play'">Play</div>
        <div class="tab-btn <?php echo $tab === 'result' ? 'active' : ''; ?>"
            onclick="window.location.href='matches.php?cat_id=<?php echo $cat_id; ?>&tab=result'">Result</div>
    </div>

    <div id="matches-container">
        <?php if (empty($matches)): ?>
            <div style='text-align:center; padding:30px; color:#666;'>No matches found.</div>
        <?php else: ?>
            <?php foreach ($matches as $m): ?>
                <?php
                $joinedCount = $m['joined_slots'];
                $totalSlots = $m['total_slots'];
                $progress = ($totalSlots > 0) ? ($joinedCount / $totalSlots) * 100 : 0;
                $isFull = $joinedCount >= $totalSlots;
                $isJoined = in_array($m['id'], $myJoinedMatches);
                ?>
                <div class="match-card">
                    <div class="m-header">
                        <img src="<?php echo htmlspecialchars($category['icon_url']); ?>" class="m-img" alt="Game">
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
                            <span class="stat-label">TOTAL PRIZE</span>
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
                            <span class="stat-label">ENTRY FEE</span>
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

                    <?php if ($tab === 'result'): ?>
                        <div style="text-align:center; margin-top:10px;">
                            <button class="btn-view-result"
                                onclick="window.location.href='match_result.php?id=<?php echo $m['id']; ?>'">View Result</button>
                        </div>
                    <?php else: ?>
                        <div style="display:flex; justify-content:space-between; margin-top:10px;">
                            <button class="btn-view-participants"
                                onclick="window.location.href='participants.php?id=<?php echo $m['id']; ?>'">View Joined</button>
                            <?php if ($isJoined): ?>
                                <button class="btn-data-outline" style="background:#4CAF50; color:#fff; border:none;">Joined</button>
                            <?php elseif ($isFull): ?>
                                <button class="btn-full-outline" style="width:48%;">Full</button>
                            <?php else: ?>
                                <button class="btn-join-outline"
                                    onclick="openJoinModal(<?php echo $m['id']; ?>, '<?php echo htmlspecialchars(addslashes($m['title'])); ?>', <?php echo $m['entry_fee']; ?>, '<?php echo $m['type']; ?>')">Join</button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="m-btn-row" style="margin-top:15px;">
                        <button class="btn-prize"
                            onclick="openPrizeModal('<?php echo htmlspecialchars(addslashes($m['prize_description'] ?? '')); ?>', '<?php echo $m['prize_pool']; ?>', '<?php echo $m['per_kill']; ?>')">Prize
                            List</button>
                        <button class="btn-room"
                            onclick="<?php echo $isJoined ? "openRoomModal('" . ($m['room_id'] ?: 'Wait') . "', '" . ($m['room_pass'] ?: 'Wait') . "')" : "alert('Please join the match to view Room Details.')"; ?>">ROOM
                            ID</button>
                    </div>

                    <div class="timer-display match-countdown" data-time="<?php echo strtotime($m['schedule_time']); ?>">
                        Loading...
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if (!empty($category['rules'])): ?>
        <div class="rules-section"
            style="margin-top:20px; padding:20px; background:#fff; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.05);">
            <h3
                style="margin-bottom:15px; font-size:18px; font-weight:600; color:var(--primary); border-bottom:1px solid #eee; padding-bottom:8px;">
                <i class="fas fa-file-contract"></i> Rules & Conditions
            </h3>
            <div class="rules-content" style="font-size:14px; color:#444; line-height:1.6;">
                <?php echo $category['rules']; ?>
            </div>
        </div>
    <?php endif; ?>
</section>

<!-- Prize Modal -->
<div id="prize-modal" class="modal">
    <div class="modal-box" style="text-align:center;">
        <h3 style="margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:10px;">Prize Breakdown</h3>
        <div id="prize-content"
            style="text-align:left; font-size:14px; line-height:1.6; white-space: pre-wrap; color:#333;"></div>
        <button class="btn-cancel" onclick="document.getElementById('prize-modal').classList.remove('active')"
            style="margin-top:20px; width:100%;">Close</button>
    </div>
</div>

<!-- Join Modal -->
<div id="join-modal" class="modal">
    <div class="modal-box" style="text-align:left;">
        <div style="text-align:center; margin-bottom:15px;">
            <h3 id="join-modal-title" style="font-size:16px; font-weight:700;">MATCH TITLE</h3>
            <p style="font-size:12px; color:#666;">Available Balance: <span id="join-modal-balance"
                    style="font-weight:700; color:var(--primary);">
                    <?php echo number_format($_SESSION['user_balance'] ?? 0, 2); ?>
                </span> ৳</p>
            <p style="font-size:12px; color:#666;">Match Entry Fee: <span id="join-modal-fee"
                    style="font-weight:700;">0</span> ৳</p>
        </div>

        <form action="join_match.php" method="POST">
            <input type="hidden" name="match_id" id="join-match-id">
            <div id="join-inputs-container"></div>
            <button type="submit" class="primary-btn" style="margin-top:20px;">Join Match</button>
        </form>
        <button class="btn-cancel" onclick="closeJoinModal()">Cancel</button>
    </div>
</div>
</div>

<!-- Room ID Modal -->
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

        // Try generic Clipboard API first
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

        // Ensure it's not visible but part of DOM
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

    function openPrizeModal(desc, total, perKill) {
        let content = "";
        if (desc && desc.trim() !== "") {
            content = desc;
        } else {
            content = "Total Prize Pool: " + total + "\nPer Kill: " + perKill;
        }
        document.getElementById('prize-content').innerText = content;
        document.getElementById('prize-modal').classList.add('active');
    }

    function openJoinModal(id, title, fee, type) {
        <?php if (!isLoggedIn()): ?>
            window.location.href = 'login.php';
            return;
        <?php endif; ?>

        const balance = <?php echo $_SESSION['user_balance'] ?? 0; ?>;
        if (balance < fee) {
            if (confirm("Insufficient Balance! Do you want to add money?")) {
                window.location.href = 'wallet.php?action=add';
            }
            return;
        }

        document.getElementById('join-modal').classList.add('active');
        document.getElementById('join-modal-title').innerText = title;
        document.getElementById('join-modal-fee').innerText = fee;
        document.getElementById('join-match-id').value = id;

        const container = document.getElementById('join-inputs-container');
        container.innerHTML = "";

        let count = 1;
        if (type.toLowerCase().includes('duo')) count = 2;
        if (type.toLowerCase().includes('squad')) count = 4;

        for (let i = 1; i <= count; i++) {
            container.innerHTML += `
            <div style="margin-bottom:15px;">
                <h5 style="font-size:12px; margin-bottom:5px; color:#666;">Player ${i} Details</h5>
                <div class="form-group" style="margin-bottom:8px;">
                    <input type="text" name="players[${i - 1}][ign]" class="input-field" placeholder="In-Game Name" required>
                </div>
                <div class="form-group" style="margin-bottom:8px;">
                     <input type="text" name="players[${i - 1}][uid]" class="input-field" placeholder="Player ID (UID)" required>
                </div>
            </div>
        `;
        }
    }
</script>

<?php require_once 'includes/footer.php'; ?>