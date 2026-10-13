<?php
require_once '../config/db.php';
require_once 'includes/header.php';
require_once 'includes/upload_helper.php';

// ADD / EDIT MATCH
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_match'])) {
        try {
            $title = $_POST['title'];
            $cat_id = $_POST['category_id'];
            $time = $_POST['schedule_time'];
            $prize = $_POST['prize_pool'];
            $per_kill = $_POST['per_kill'];
            $entry = $_POST['entry_fee'];
            $type = $_POST['type']; // Solo/Duo/Squad
            $map = $_POST['map'];
            $slots = $_POST['total_slots']; // Fixed variable name from $slots to match form

            // Handle Image Upload
            $img = '';
            if (isset($_POST['image_url']))
                $img = $_POST['image_url']; // Fallback/Manual URL

            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                $uploaded = handleUpload($_FILES['image_file']);
                if (is_string($uploaded))
                    $img = $uploaded;
            }

            // Default image if none provided
            if (empty($img))
                $img = 'assets/img/default_match.jpg'; // Placeholder or empty

            $redirect = $_POST['redirect_url'];
            $prize_desc = $_POST['prize_list'];

            // Insert into DB
            $stmt = $pdo->prepare("INSERT INTO matches (category_id, title, image_url, redirect_url, schedule_time, prize_pool, per_kill, entry_fee, type, map, total_slots, prize_description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ongoing')");
            $stmt->execute([$cat_id, $title, $img, $redirect, $time, $prize, $per_kill, $entry, $type, $map, $slots, $prize_desc]);

            // Success Message
            echo "<script>alert('Tournament Created Successfully!'); window.location.href='tournaments.php';</script>";

        } catch (PDOException $e) {
            // Error Message
            echo "<script>alert('Error Creating Tournament: " . addslashes($e->getMessage()) . "');</script>";
        }

    } elseif (isset($_POST['update_room'])) {
        try {
            $mid = $_POST['match_id'];
            $rid = $_POST['room_id'];
            $pass = $_POST['room_pass'];

            $stmt = $pdo->prepare("UPDATE matches SET room_id = ?, room_pass = ? WHERE id = ?");
            $stmt->execute([$rid, $pass, $mid]);

            echo "<script>alert('Room Details Updated!'); window.location.href='tournaments.php';</script>";

        } catch (PDOException $e) {
            echo "<script>alert('Error Updating Room: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
}

// Fetch Categories
$cats = $pdo->query("SELECT * FROM categories")->fetchAll();

// Fetch Matches (Pagination)
require_once 'includes/pagination_helper.php';
$limit = 20;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$total_matches = $pdo->query("SELECT COUNT(*) FROM matches")->fetchColumn();

$stmt = $pdo->prepare("SELECT m.*, c.title as cat_title FROM matches m JOIN categories c ON m.category_id = c.id ORDER BY m.id DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$matches = $stmt->fetchAll();
?>

<div class="header-bar">
    <h2 class="page-title">Tournaments</h2>
    <button class="action-btn btn-orange" onclick="document.getElementById('add-match-modal').style.display='flex'">+
        Create Match</button>
</div>

<div class="panel">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Match</th>
                    <th>Time</th>
                    <th>Joined</th>
                    <th>Room ID</th>
                    <th>Pass</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($matches as $m): ?>
                    <tr>
                        <td>
                            <?php echo $m['id']; ?>
                        </td>
                        <td>
                            <strong>
                                <?php echo htmlspecialchars($m['title']); ?>
                            </strong>
                            <br><small>
                                <?php echo htmlspecialchars($m['cat_title']); ?> |
                                <?php echo $m['type']; ?> | ৳
                                <?php echo $m['entry_fee']; ?>
                            </small>
                        </td>
                        <td>
                            <?php echo date("d M, h:i A", strtotime($m['schedule_time'])); ?>
                        </td>
                        <td>
                            <?php echo $m['joined_slots']; ?> /
                            <?php echo $m['total_slots']; ?>
                        </td>
                        <td>
                            <?php echo $m['room_id'] ? $m['room_id'] : '-'; ?>
                        </td>
                        <td>
                            <?php echo $m['room_pass'] ? $m['room_pass'] : '-'; ?>
                        </td>
                        <td>
                            <button class="action-btn btn-blue"
                                onclick="openRoomModal(<?php echo $m['id']; ?>, '<?php echo $m['room_id']; ?>', '<?php echo $m['room_pass']; ?>')">Room</button>
                            <a href="participants.php?mid=<?php echo $m['id']; ?>" class="action-btn btn-dark">Users</a>
                            <button class="action-btn btn-red">Del</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php renderPagination($page, $total_matches, $limit, 'tournaments.php'); ?>
    </div>
</div>

<!-- Create Match Modal -->
<div id="add-match-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Create Match</h3>
            <span class="close-modal" onclick="document.getElementById('add-match-modal').style.display='none'">×</span>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="create_match" value="1">
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" class="input-field" required>
            </div>
            <!-- Image & Redirect -->
            <div class="form-grid">
                <div class="form-group">
                    <label>Match Image</label>
                    <input type="text" name="image_url" class="input-field" placeholder="Image URL">
                    <input type="file" name="image_file" class="input-field"
                        style="padding:10px; height:auto; margin-top:5px;">
                </div>
                <div class="form-group">
                    <label>Redirect URL (Optional)</label>
                    <input type="text" name="redirect_url" class="input-field" placeholder="Redirect on click">
                </div>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" class="input-field">
                        <?php foreach ($cats as $c): ?>
                            <option value="<?php echo $c['id']; ?>">
                                <?php echo $c['title']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Time</label>
                    <input type="datetime-local" name="schedule_time" class="input-field" required>
                </div>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Entry Fee</label>
                    <input type="number" name="entry_fee" class="input-field" required>
                </div>
                <div class="form-group">
                    <label>Prize Pool</label>
                    <input type="number" name="prize_pool" class="input-field" required>
                </div>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Per Kill</label>
                    <input type="number" name="per_kill" class="input-field" value="0">
                </div>
                <div class="form-group">
                    <label>Total Slots</label>
                    <input type="number" name="total_slots" class="input-field" value="48">
                </div>
            </div>
            <div class="form-group">
                <label>Prize List (Description)</label>
                <textarea name="prize_list" class="input-field" style="height:80px;"
                    placeholder="1st Prize: 500&#10;2nd Prize: 200&#10;3rd Prize: 100"></textarea>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Type</label>
                    <select name="type" class="input-field">
                        <option>Solo</option>
                        <option>Duo</option>
                        <option>Squad</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Map</label>
                    <input type="text" name="map" class="input-field" value="Bermuda">
                </div>
            </div>
            <button type="submit" class="action-btn btn-success"
                style="width:100%; font-size:14px; padding:12px;">Create Now</button>
        </form>
    </div>
</div>

<!-- Room Modal -->
<div id="room-modal" class="modal">
    <div class="modal-content" style="max-width:400px;">
        <div class="modal-header">
            <h3>Update Room Details</h3>
            <span class="close-modal" onclick="document.getElementById('room-modal').style.display='none'">×</span>
        </div>
        <form method="POST">
            <input type="hidden" name="update_room" value="1">
            <input type="hidden" name="match_id" id="room-mid">
            <div class="form-group">
                <label>Room ID</label>
                <input type="text" name="room_id" id="room-id" class="input-field">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="text" name="room_pass" id="room-pass" class="input-field">
            </div>
            <button class="action-btn btn-blue" style="width:100%">Update</button>
        </form>
    </div>
</div>

<script>
    function openRoomModal(id, rid, pass) {
        document.getElementById('room-modal').style.display = 'flex';
        document.getElementById('room-mid').value = id;
        document.getElementById('room-id').value = rid;
        document.getElementById('room-pass').value = pass;
    }
</script>

<?php require_once 'includes/footer.php'; ?>