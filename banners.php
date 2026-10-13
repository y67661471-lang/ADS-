<?php
require_once '../config/db.php';
require_once 'includes/header.php';
require_once 'includes/upload_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_banner'])) {
        $img = $_POST['image_url'];

        // Handle Upload
        if (isset($_FILES['banner_file']) && $_FILES['banner_file']['error'] === UPLOAD_ERR_OK) {
            $uploaded = handleUpload($_FILES['banner_file']);
            if (is_string($uploaded)) {
                $img = $uploaded;
            } else {
                // handle error? For now stick to text input if upload fails or is empty
            }
        }

        $link = $_POST['link'];
        $stmt = $pdo->prepare("INSERT INTO banners (image_url, link) VALUES (?, ?)");
        $stmt->execute([$img, $link]);
    } elseif (isset($_POST['delete_banner'])) {
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM banners WHERE id = ?")->execute([$id]);
    }
}

$banners = $pdo->query("SELECT * FROM banners")->fetchAll();
?>

<div class="header-bar">
    <h2 class="page-title">App Banners</h2>
</div>

<div class="panel">
    <h3>Manage Banners</h3>
    <form method="POST" enctype="multipart/form-data"
        style="margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:15px;">
        <input type="hidden" name="add_banner" value="1">
        <div class="form-grid">
            <div class="form-group">
                <input type="text" name="image_url" class="input-field" placeholder="Image URL">
                <input type="file" name="banner_file" class="input-field"
                    style="padding:10px; height:auto; margin-top:5px;">
            </div>
            <div class="form-group">
                <input type="text" name="link" class="input-field" placeholder="Target Link">
            </div>
        </div>
        <button class="action-btn btn-success" style="margin-top:10px;">+ Add Banner</button>
    </form>

    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:15px;">
        <?php foreach ($banners as $b): ?>
            <div style="position:relative;">
                <img src="<?php echo strpos($b['image_url'], 'http') === 0 ? htmlspecialchars($b['image_url']) : '../' . htmlspecialchars($b['image_url']); ?>"
                    style="width:100%; border-radius:8px;">
                <form method="POST" style="position:absolute; top:5px; right:5px;">
                    <input type="hidden" name="delete_banner" value="1">
                    <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                    <button class="action-btn btn-red" style="padding:4px 8px;">×</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
