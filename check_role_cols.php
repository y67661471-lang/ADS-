<?php
require_once '../config/db.php';

try {
    $stmt = $pdo->query("DESCRIBE staff_roles");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<h1>Columns in staff_roles:</h1>";
    echo "<ul>";
    foreach ($columns as $col) {
        echo "<li>" . htmlspecialchars($col) . "</li>";
    }
    echo "</ul>";

    // Check closest match
    $likely = ['title', 'role', 'role_name', 'label'];
    foreach ($likely as $l) {
        if (in_array($l, $columns)) {
            echo "<h3>Found likely candidate for name: " . $l . "</h3>";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>