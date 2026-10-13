<?php
// Set header to output XML
header("Content-Type: application/xml; charset=utf-8");

// Include database connection
// Adjust path if necessary. Based on file structure, sitemap.php is in root.
$suppress_db_die = true;
require_once 'config/db.php';

// Base URL of the website
// You should verify this matches your actual live domain or local path.
// For dynamic detection:
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script = $_SERVER['SCRIPT_NAME']; // e.g., /1/rrr esports/sitemap.php
$dir = dirname($script); // e.g., /1/rrr esports
// Ensure no trailing slash duplication
$baseUrl = rtrim($protocol . "://" . $host . $dir, '/');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Static Pages -->
    <url>
        <loc>
            <?php echo $baseUrl; ?>/index.php
        </loc>
        <priority>1.0</priority>
        <changefreq>daily</changefreq>
    </url>
    <url>
        <loc>
            <?php echo $baseUrl; ?>/login.php
        </loc>
        <priority>0.8</priority>
    </url>
    <url>
        <loc>
            <?php echo $baseUrl; ?>/register.php
        </loc>
        <priority>0.8</priority>
    </url>
    <url>
        <loc>
            <?php echo $baseUrl; ?>/contact.php
        </loc>
        <priority>0.5</priority>
    </url>

    <?php
    // Fetch Categories
    try {
        if (isset($pdo) && $pdo) {
            $stmt = $pdo->query("SELECT id FROM categories");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Priority for categories is high as they are hubs
                echo "    <url>\n";
                echo "        <loc>" . $baseUrl . "/matches.php?cat_id=" . $row['id'] . "</loc>\n";
                echo "        <priority>0.9</priority>\n";
                echo "        <changefreq>daily</changefreq>\n";
                echo "    </url>\n";
            }
        }
    } catch (Exception $e) {
        // Silently continue or log error
    }

    // Fetch Matches
    try {
        if (isset($pdo) && $pdo) {
            // We link to match_result for completed matches, and participants/details for others
            $stmt = $pdo->query("SELECT id, status FROM matches ORDER BY id DESC LIMIT 1000");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "    <url>\n";
                if ($row['status'] === 'completed') {
                    echo "        <loc>" . $baseUrl . "/match_result.php?id=" . $row['id'] . "</loc>\n";
                } else {
                    echo "        <loc>" . $baseUrl . "/participants.php?id=" . $row['id'] . "</loc>\n";
                }
                echo "        <priority>0.7</priority>\n";
                echo "        <changefreq>weekly</changefreq>\n";
                echo "    </url>\n";
            }
        }
    } catch (Exception $e) {
        // Silently continue
    }
    ?>
</urlset>