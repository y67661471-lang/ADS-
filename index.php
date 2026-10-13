<?php
$pageTitle = "FRG TOUR BD";
require_once 'includes/functions.php';
require_once 'includes/header.php';

// Fetch Banners
$banners = [];
try {
    $stmt = $pdo->query("SELECT * FROM banners ORDER BY id DESC");
    $banners = $stmt->fetchAll();
} catch (PDOException $e) {
    // Handle error
}

// Fetch Categories
$categories = [];
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY id ASC");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    // Handle error
}
?>

<section id="home" class="app-section active">
    <!-- HERO BANNER -->
    <div class="hero-banner">
        <div class="alert-box">
            <marquee id="dynamic-notice">
                <?php echo htmlspecialchars(getSetting('notice_board') ?: 'Welcome to RRR Esports! Play daily matches and earn rewards.'); ?>
            </marquee>
        </div>
        <div class="slider-container">
            <div id="banner-slider-wrapper" class="slider-wrapper">
                <?php if (!empty($banners)): ?>
                    <?php foreach ($banners as $banner): ?>
                        <div class="slide" onclick="window.open('<?php echo htmlspecialchars($banner['link']); ?>', '_blank')">
                            <img src="<?php echo htmlspecialchars($banner['image_url']); ?>" alt="Banner">
                            <?php if ($banner['title']): ?>
                                <div class="slide-caption">
                                    <?php echo htmlspecialchars($banner['title']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="slide">
                        <img src="https://placehold.co/600x200?text=Welcome+to+RRR+Esports" alt="Default Banner">
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="section-title">Select Game Category</div>

    <div class="category-list">
        <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $cat): ?>
                <?php
                // Use thumbnail if available, else fallback to icon
                $bgUrl = !empty($cat['thumbnail_url']) ? $cat['thumbnail_url'] : $cat['icon_url'];
                // Use custom link if available, else default match link
                // Check if link is absolute (http) or relative
                $clickAction = "window.location.href='matches.php?cat_id=" . $cat['id'] . "'";
                if (!empty($cat['link'])) {
                    // if explicitly external, maybe window.open? user didn't specify, assume same tab for now unless http
                    $clickAction = "window.location.href='" . htmlspecialchars($cat['link']) . "'";
                }
                ?>
                <div class="game-card" onclick="<?php echo $clickAction; ?>">
                    <div class="game-card-bg" style="background-image: url('<?php echo htmlspecialchars($bgUrl); ?>');"></div>
                    <div class="game-card-content">
                        <img src="<?php echo htmlspecialchars($cat['icon_url']); ?>" class="game-avatar">
                        <div class="game-info">
                            <div class="game-title">
                                <?php echo htmlspecialchars($cat['title']); ?>
                            </div>
                            <!-- Counts could be dynamic, for now placeholder or query separately -->
                            <div class="match-count">Tap to View Matches</div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align:center; width:100%; padding:20px; color:#999;">No Categories Found</div>
        <?php endif; ?>
    </div>
</section>

<section class="app-download-section" style="text-align:center; padding: 20px; margin-top:20px; margin-bottom: 80px;">
    <a href="app/FRG TOUR BD.apk" style="display:inline-block; text-decoration:none;" download>
        <img src="assets/img/google_play_download.png" alt="Download App" style="height: 80px; width: auto;">
    </a>
</section>

<?php require_once 'includes/footer.php'; ?>