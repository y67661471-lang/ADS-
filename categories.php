<?php
require_once '../config/db.php';
require_once 'includes/header.php';
require_once 'includes/upload_helper.php';

// Summernote & jQuery
echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
echo '<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">';
echo '<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_cat'])) {
        $title = $_POST['title'];
        $icon = $_POST['icon_url'];

        // Handle Icon Upload
        if (isset($_FILES['icon_file']) && $_FILES['icon_file']['error'] === UPLOAD_ERR_OK) {
            $uploaded = handleUpload($_FILES['icon_file']);
            if (is_string($uploaded))
                $icon = $uploaded;
        }

        // New Fields
        $thumbnail = $_POST['thumbnail_url'];
        // Handle Thumbnail Upload
        if (isset($_FILES['thumbnail_file']) && $_FILES['thumbnail_file']['error'] === UPLOAD_ERR_OK) {
            $uploaded = handleUpload($_FILES['thumbnail_file']);
            if (is_string($uploaded))
                $thumbnail = $uploaded;
        }

        $link = $_POST['link'];
        $rules = $_POST['rules'] ?? null;

        // Use icon as fallback for thumbnail if not provided
        if (empty($thumbnail))
            $thumbnail = $icon;

        $stmt = $pdo->prepare("INSERT INTO categories (title, icon_url, thumbnail_url, link, rules) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $icon, $thumbnail, $link, $rules]);
    }

    if (isset($_POST['delete_cat'])) {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        echo "<script>window.location.href='categories.php';</script>";
        exit;
    }

    if (isset($_POST['update_cat'])) {
        $id = $_POST['cat_id'];
        $title = $_POST['title'];
        $icon = $_POST['icon_url'];

        // Handle Icon Upload
        if (isset($_FILES['icon_file']) && $_FILES['icon_file']['error'] === UPLOAD_ERR_OK) {
            $uploaded = handleUpload($_FILES['icon_file']);
            if (is_string($uploaded))
                $icon = $uploaded;
        }

        $thumbnail = $_POST['thumbnail_url'];
        // Handle Thumbnail Upload
        if (isset($_FILES['thumbnail_file']) && $_FILES['thumbnail_file']['error'] === UPLOAD_ERR_OK) {
            $uploaded = handleUpload($_FILES['thumbnail_file']);
            if (is_string($uploaded))
                $thumbnail = $uploaded;
        }

        $link = $_POST['link'];
        $rules = $_POST['rules'] ?? null;

        // Use icon as fallback for thumbnail if not provided & not already set
        if (empty($thumbnail) && empty($_POST['current_thumbnail']))
            $thumbnail = $icon;

        // Keep existing if empty and no new file
        // (Note: This simple logic assumes if URL is empty and no file, we might want to keep old. 
        // But here we rely on the form being populated with current values usually. 
        // For files, if no new file is uploaded, handleUpload returns nothing or we just don't overwrite variable if we didn't call it.
        // Actually, simpler: if URL field is passed, use it. If file is passed, overwrite it.)

        // Refined logic: The form populates the URL fields. If user uploads file, it overwrites.

        $stmt = $pdo->prepare("UPDATE categories SET title=?, icon_url=?, thumbnail_url=?, link=?, rules=? WHERE id=?");
        $stmt->execute([$title, $icon, $thumbnail, $link, $rules, $id]);
        echo "<script>window.location.href='categories.php';</script>";
        exit;
    }
}

$cats = $pdo->query("SELECT * FROM categories")->fetchAll();
?>

<div class="header-bar">
    <h2 class="page-title">Categories</h2>
</div>

<div class="panel">
    <h3>Manage Categories</h3>
    <form method="POST" enctype="multipart/form-data" id="catForm"
        style="margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:15px;">
        <input type="hidden" name="add_cat" id="action_input" value="1">
        <input type="hidden" name="cat_id" id="cat_id">
        <div class="form-grid">
            <div class="form-group">
                <input type="text" name="title" id="cat_title" class="input-field" placeholder="Title (e.g. Free Fire)"
                    required>
            </div>
            <!-- Icon Input: URL or File -->
            <div class="form-group">
                <input type="text" name="icon_url" id="cat_icon" class="input-field" placeholder="Icon URL (Optional)">
                <input type="file" name="icon_file" class="input-field"
                    style="padding:10px; height:auto; margin-top:5px;">
            </div>
        </div>
        <div class="form-grid" style="margin-top:10px;">
            <!-- Thumbnail Input: URL or File (New) -->
            <div class="form-group">
                <input type="text" name="thumbnail_url" id="cat_thumb" class="input-field"
                    placeholder="Thumbnail URL (Bg Image)">
                <input type="file" name="thumbnail_file" class="input-field"
                    style="padding:10px; height:auto; margin-top:5px;">
            </div>
            <!-- Custom Link (New) -->
            <div class="form-group">
                <input type="text" name="link" id="cat_link" class="input-field" placeholder="Redirect Link (Optional)">
            </div>

            <!-- Rules Text Editor -->
            <div class="form-group" style="grid-column: 1 / -1; margin-top: 10px;">
                <label style="display:block; margin-bottom:5px; font-weight:500;">Rules & Conditions</label>
                <textarea name="rules" id="summernote"></textarea>
            </div>
        </div>
        <button class="action-btn btn-success" id="submit_btn" style="margin-top:10px;">+ Add Category</button>
        <button type="button" class="action-btn" id="cancel_btn" style="margin-top:10px; background:#666; display:none;"
            onclick="resetForm()">Cancel</button>
    </form>

    <script>
        $('#summernote').summernote({
            placeholder: 'Enter rules and conditions...',
            tabsize: 2,
            height: 200,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });

        function editCategory(c) {
            document.getElementById('action_input').name = 'update_cat';
            document.getElementById('cat_id').value = c.id;
            document.getElementById('cat_title').value = c.title;
            document.getElementById('cat_icon').value = c.icon_url;
            document.getElementById('cat_thumb').value = c.thumbnail_url;
            document.getElementById('cat_link').value = c.link;

            // Summernote
            $('#summernote').summernote('code', c.rules);

            document.getElementById('submit_btn').innerText = 'Update Category';
            document.getElementById('submit_btn').classList.remove('btn-success');
            document.getElementById('submit_btn').classList.add('btn-blue');
            document.getElementById('cancel_btn').style.display = 'inline-block';

            // Scroll to top
            window.scrollTo(0, 0);
        }

        function resetForm() {
            document.getElementById('catForm').reset();
            document.getElementById('action_input').name = 'add_cat';
            document.getElementById('cat_id').value = '';
            $('#summernote').summernote('reset');

            document.getElementById('submit_btn').innerText = '+ Add Category';
            document.getElementById('submit_btn').classList.add('btn-success');
            document.getElementById('submit_btn').classList.remove('btn-blue');
            document.getElementById('cancel_btn').style.display = 'none';
        }
    </script>

    <ul>
        <?php foreach ($cats as $c): ?>
            <li style="padding:10px; border-bottom:1px solid #eee; display:flex; gap:10px; align-items:center;">
                <?php
                $iconSrc = strpos($c['icon_url'], 'http') === 0 ? $c['icon_url'] : '../' . $c['icon_url'];
                $thumbSrc = !empty($c['thumbnail_url']) ? (strpos($c['thumbnail_url'], 'http') === 0 ? $c['thumbnail_url'] : '../' . $c['thumbnail_url']) : '';
                ?>
                <img src="<?php echo htmlspecialchars($iconSrc); ?>" style="width:30px; height:30px; object-fit:contain;">
                <div style="flex-grow:1;">
                    <strong><?php echo htmlspecialchars($c['title']); ?></strong>
                    <?php if ($thumbSrc): ?>
                        <br><small style="color:#666;">Thumb: <a href="<?php echo htmlspecialchars($thumbSrc); ?>"
                                target="_blank">View</a></small>
                    <?php endif; ?>
                    <?php if (!empty($c['link'])): ?>
                        <br><small style="color:#666;">Link: <?php echo htmlspecialchars($c['link']); ?></small>
                    <?php endif; ?>
                </div>

                <button class="action-btn btn-blue"
                    style="margin-right:5px; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;"
                    onclick='editCategory(<?php echo json_encode($c); ?>)'>Edit</button>

                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this category?');"
                    style="margin:0;">
                    <input type="hidden" name="delete_cat" value="1">
                    <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                    <button class="action-btn"
                        style="background:#ff4d4d; border:none; padding:5px 10px; color:white; border-radius:4px; cursor:pointer;">Delete</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<?php require_once 'includes/footer.php'; ?>